<?php
/**
 * SAUTO Personal Scheduling Cron Job
 * Runs every minute to check and republish scheduled SAUTO Personal ads
 */

// Security check - only allow CLI execution or manual trigger
if (php_sapi_name() !== 'cli' && !defined('MANUAL_CRON_TRIGGER')) {
    http_response_code(403);
    die('This script can only be run from command line or with proper access');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Europe/Chisinau');

try {
    // Database connection using environment settings
    require_once __DIR__ . '/../environment.php';
    
    $db = new PDO(
        'mysql:host=' . SQL_HOST . ';dbname=' . SQL_DB . ';charset=' . SQL_CHARSET,
        SQL_USER,
        SQL_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    $prefx = 'gh3sp';
    
    echo "[" . date('Y-m-d H:i:s') . "] SAUTO Personal Cron Started\n";
    
    // Get 999.md API settings
    $settings = [];
    $stmt = $db->prepare("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%999md%'");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $settings[$row['name']] = $row['value'];
    }
    
    if (empty($settings['regular_999md_account']) || empty($settings['regular_999md_token']) ||
        empty($settings['order_999md_account']) || empty($settings['order_999md_token'])) {
        throw new Exception('999.md API settings not configured');
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] 999.md Settings loaded - Stock: {$settings['regular_999md_account']}, Order: {$settings['order_999md_account']}\n";
    
    // Get pending schedules that should be published now
    $currentDateTime = date('Y-m-d H:i:s');
    echo "[" . date('Y-m-d H:i:s') . "] Current server time: {$currentDateTime}\n";
    
    $stmt = $db->prepare("
        SELECT s.*, c.id as car_id, c.999_id as existing_999_id, s.catalog_type,
               CONCAT(s.schedule_date, ' ', s.schedule_time) as full_schedule_time
        FROM gh3sp_sauto_personal_schedules s
        LEFT JOIN {$prefx}_car_ctlg c ON s.car_id = c.id
        WHERE s.status = 'pending' 
        AND CONCAT(s.schedule_date, ' ', s.schedule_time) <= :current_time
        ORDER BY s.schedule_date, s.schedule_time
        LIMIT 10
    ");
    $stmt->execute(['current_time' => $currentDateTime]);
    $pendingSchedules = $stmt->fetchAll();
    
    // Debug: Show all pending schedules regardless of time
    $debugStmt = $db->prepare("
        SELECT s.*, CONCAT(s.schedule_date, ' ', s.schedule_time) as full_schedule_time
        FROM gh3sp_sauto_personal_schedules s
        WHERE s.status = 'pending'
        ORDER BY s.schedule_date, s.schedule_time
    ");
    $debugStmt->execute();
    $allPending = $debugStmt->fetchAll();
    
    echo "[" . date('Y-m-d H:i:s') . "] All pending schedules:\n";
    foreach ($allPending as $schedule) {
        echo "  - ID: {$schedule['id']}, Time: {$schedule['full_schedule_time']}, Should publish: " . 
             ($schedule['full_schedule_time'] <= $currentDateTime ? 'YES' : 'NO') . "\n";
    }
    
    if (empty($pendingSchedules)) {
        echo "[" . date('Y-m-d H:i:s') . "] No pending schedules to publish\n";
        exit(0);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Found " . count($pendingSchedules) . " schedules to process\n";
    
    // Include required services
    require_once __DIR__ . '/../App/Core/Container.php';
    require_once __DIR__ . '/../App/Services/PublicationService.php';
    require_once __DIR__ . '/../App/Services/Api999Service.php';
    
    // Initialize Container with database and prefix
    \App\Core\Container::set('db', $db);
    \App\Core\Container::set('prefix', $prefx);
    
    foreach ($pendingSchedules as $schedule) {
        try {
            echo "[" . date('Y-m-d H:i:s') . "] Processing schedule ID: {$schedule['id']}, Car ID: {$schedule['car_id']}, Type: {$schedule['catalog_type']}\n";
            
            // Determine which 999.md account to use based on catalog_type
            $catalogType = $schedule['catalog_type'];
            if ($catalogType === 'in_stock') {
                $apiAccount = $settings['regular_999md_account']; // SAUTO-HAUS
                $apiToken = $settings['regular_999md_token'];
                echo "[" . date('Y-m-d H:i:s') . "] Using STOCK account: {$apiAccount}\n";
            } elseif ($catalogType === 'on_order') {
                $apiAccount = $settings['order_999md_account']; // Sauto-stock-extern
                $apiToken = $settings['order_999md_token'];
                echo "[" . date('Y-m-d H:i:s') . "] Using ORDER account: {$apiAccount}\n";
            } else {
                throw new Exception("Unknown catalog_type: {$catalogType}");
            }
            
            if (!empty($schedule['existing_999_id'])) {
                // Car already has 999.md listing - republish it
                echo "[" . date('Y-m-d H:i:s') . "] Car {$schedule['car_id']} has existing 999.md ID: {$schedule['existing_999_id']} - republishing on {$apiAccount}\n";
                
                // Use 999.md API to republish/boost the ad with correct account
                $api999Service = \App\Services\Api999Service::createFromSettings($catalogType);
                $result = $api999Service->republishAdvert($schedule['existing_999_id']);
                
                if ($result && isset($result['success']) && $result['success']) {
                    // Update schedule as published
                    $stmt = $db->prepare("
                        UPDATE gh3sp_sauto_personal_schedules 
                        SET status = 'published', 
                            published_at = NOW(), 
                            `999_id` = :api_id
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'api_id' => $schedule['existing_999_id'],
                        'id' => $schedule['id']
                    ]);
                    
                    echo "[" . date('Y-m-d H:i:s') . "] ✅ Successfully republished car {$schedule['car_id']} on 999.md ({$apiAccount})\n";
                } else {
                    $errorMsg = isset($result['error']) ? $result['error'] : 'Unknown error during republish';
                    if (is_array($errorMsg)) {
                        $errorMsg = json_encode($errorMsg, JSON_UNESCAPED_UNICODE);
                    }
                    
                    // Update schedule as failed
                    $stmt = $db->prepare("
                        UPDATE gh3sp_sauto_personal_schedules 
                        SET status = 'failed', 
                            error_message = :error
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'error' => $errorMsg,
                        'id' => $schedule['id']
                    ]);
                    
                    echo "[" . date('Y-m-d H:i:s') . "] ❌ Failed to republish car {$schedule['car_id']} on {$apiAccount}: $errorMsg\n";
                }
            } else {
                // Car doesn't have 999.md listing yet - create new one for SAUTO Personal
                echo "[" . date('Y-m-d H:i:s') . "] Car {$schedule['car_id']} is new - creating first 999.md listing\n";
                
                // Get car data and features from database
                $carStmt = $db->prepare("
                    SELECT * FROM {$prefx}_car_ctlg 
                    WHERE id = :car_id
                ");
                $carStmt->execute(['car_id' => $schedule['car_id']]);
                $carData = $carStmt->fetch();
                
                if (!$carData || (empty($carData['features_json']) && empty($carData['999']))) {
                    $errorMsg = !$carData ? 'Car not found in database' : 'No features data saved for car';
                    $stmt = $db->prepare("
                        UPDATE gh3sp_sauto_personal_schedules 
                        SET status = 'failed', error_message = :error
                        WHERE id = :id
                    ");
                    $stmt->execute(['error' => $errorMsg, 'id' => $schedule['id']]);
                    echo "[" . date('Y-m-d H:i:s') . "] ❌ $errorMsg\n";
                    continue;
                }
                
                // Parse saved features - try features_json first, then 999 column
                $featuresData = null;
                echo "[" . date('Y-m-d H:i:s') . "] Checking for saved features data...\n";
                echo "[" . date('Y-m-d H:i:s') . "] features_json: " . (empty($carData['features_json']) ? 'EMPTY' : 'FOUND') . "\n";
                echo "[" . date('Y-m-d H:i:s') . "] 999 column: " . (empty($carData['999']) ? 'EMPTY' : 'FOUND') . "\n";
                
                if (!empty($carData['features_json'])) {
                    $featuresData = json_decode($carData['features_json'], true);
                    echo "[" . date('Y-m-d H:i:s') . "] Using features_json data\n";
                } elseif (!empty($carData['999'])) {
                    $featuresData = json_decode($carData['999'], true);
                    echo "[" . date('Y-m-d H:i:s') . "] Using 999 column data\n";
                }
                
                if (!$featuresData || !isset($featuresData['features'])) {
                    $stmt = $db->prepare("
                        UPDATE gh3sp_sauto_personal_schedules 
                        SET status = 'failed', error_message = 'Invalid features data format'
                        WHERE id = :id
                    ");
                    $stmt->execute(['id' => $schedule['id']]);
                    echo "[" . date('Y-m-d H:i:s') . "] ❌ Invalid features data format\n";
                    continue;
                }
                
                // Create new 999.md listing using saved data
                try {
                    $api999Service = \App\Services\Api999Service::createFromSettings($catalogType);
                    $result = $api999Service->setAdvert(
                        $featuresData['category_id'],
                        $featuresData['subcategory_id'], 
                        $featuresData['offer_type'],
                        $featuresData['features']
                    );
                    
                    if ($result && isset($result['advert']['id'])) {
                        $new999Id = $result['advert']['id'];
                        
                        // Update car with new 999.md ID
                        $updateCarStmt = $db->prepare("
                            UPDATE {$prefx}_car_ctlg 
                            SET 999_id = :new_999_id
                            WHERE id = :car_id
                        ");
                        $updateCarStmt->execute([
                            'new_999_id' => $new999Id,
                            'car_id' => $schedule['car_id']
                        ]);
                        
                        // Update schedule as published
                        $stmt = $db->prepare("
                            UPDATE gh3sp_sauto_personal_schedules 
                            SET status = 'published', published_at = NOW(), `999_id` = :api_id
                            WHERE id = :id
                        ");
                        $stmt->execute(['api_id' => $new999Id, 'id' => $schedule['id']]);
                        
                        echo "[" . date('Y-m-d H:i:s') . "] ✅ Successfully created new 999.md listing {$new999Id} for car {$schedule['car_id']}\n";
                    } else {
                        $errorMsg = isset($result['error']) ? $result['error'] : 'Failed to create 999.md listing';
                        if (is_array($errorMsg)) {
                            $errorMsg = json_encode($errorMsg, JSON_UNESCAPED_UNICODE);
                        }
                        $stmt = $db->prepare("
                            UPDATE gh3sp_sauto_personal_schedules 
                            SET status = 'failed', error_message = :error
                            WHERE id = :id
                        ");
                        $stmt->execute(['error' => $errorMsg, 'id' => $schedule['id']]);
                        echo "[" . date('Y-m-d H:i:s') . "] ❌ Failed to create listing: $errorMsg\n";
                    }
                } catch (Exception $e) {
                    $stmt = $db->prepare("
                        UPDATE gh3sp_sauto_personal_schedules 
                        SET status = 'failed', error_message = :error
                        WHERE id = :id
                    ");
                    $stmt->execute(['error' => $e->getMessage(), 'id' => $schedule['id']]);
                    echo "[" . date('Y-m-d H:i:s') . "] ❌ Exception: " . $e->getMessage() . "\n";
                }
            }
            
        } catch (Exception $e) {
            // Update schedule as failed
            $stmt = $db->prepare("
                UPDATE gh3sp_sauto_personal_schedules 
                SET status = 'failed', 
                    error_message = :error
                WHERE id = :id
            ");
            $stmt->execute([
                'error' => $e->getMessage(),
                'id' => $schedule['id']
            ]);
            
            echo "[" . date('Y-m-d H:i:s') . "] Failed to process schedule ID: {$schedule['id']}, Error: " . $e->getMessage() . "\n";
        }
        
        // Small delay between republishes
        sleep(1);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] SAUTO Personal Cron Completed\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
