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
            
            // Get car data to determine which 999.md account to use
            $carStmt = $db->prepare("SELECT 999_api_id FROM {$prefx}_car_ctlg WHERE id = :car_id");
            $carStmt->execute(['car_id' => $schedule['car_id']]);
            $carInfo = $carStmt->fetch();
            
            // Determine which 999.md account to use based on car's 999_api_id or catalog_type
            $catalogType = $schedule['catalog_type'];
            $apiAccountId = !empty($carInfo['999_api_id']) ? $carInfo['999_api_id'] : null;
            
            if ($catalogType === 'in_stock') {
                // For in_stock cars, use 999_api_id from car or default to regular account
                if ($apiAccountId == 1) {
                    $apiAccount = $settings['sautohaus_999md_account'] ?? 'SAUTO-HAUS';
                    $apiToken = $settings['sautohaus_999md_token'] ?? $settings['regular_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using STOCK account: {$apiAccount} (Account ID: 1)\n";
                } elseif ($apiAccountId == 2) {
                    $apiAccount = 'Sauto-auto-comerciale';
                    $apiToken = $settings['regular_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using STOCK account: {$apiAccount} (Account ID: 2)\n";
                } else {
                    // Default to regular account (currently Sauto-auto-comerciale)
                    $apiAccount = $settings['regular_999md_account'];
                    $apiToken = $settings['regular_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using STOCK account: {$apiAccount} (Default)\n";
                }
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
                // Create API service with the determined account ID
                $accountIdForApi = ($catalogType === 'in_stock') ? ($apiAccountId ?? 2) : 3;
                $api999Service = new \App\Services\Api999Service($accountIdForApi);
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
                
                // Apply smart engine volume conversion before API call
                echo "[" . date('Y-m-d H:i:s') . "] Car volume from DB: " . ($carData['vol'] ?? 'NULL') . " cm³\n";
                if (!empty($carData['vol'])) {
                    $engineVolumeCm3 = (int)$carData['vol'];
                    $engineVolumeLiters = $engineVolumeCm3 / 1000;
                    echo "[" . date('Y-m-d H:i:s') . "] Converted to: {$engineVolumeLiters} liters\n";
                    
                    // Map engine volume to 999.md option IDs
                    $volumeMap = [
                        0.7 => "43671", 0.8 => "43672", 0.9 => "43673", 1.0 => "43674",
                        1.1 => "43675", 1.2 => "43676", 1.3 => "43677", 1.4 => "43678",
                        1.5 => "43679", 1.6 => "43680", 1.7 => "43681", 1.8 => "43682",
                        1.9 => "43683", 2.0 => "43684", 2.1 => "43685", 2.2 => "43686",
                        2.3 => "43687", 2.4 => "43688", 2.5 => "43689", 2.6 => "43690",
                        2.7 => "43691", 2.8 => "43692", 2.9 => "43693", 3.0 => "43694",
                        3.1 => "43695", 3.2 => "43696", 3.3 => "43697", 3.4 => "43698",
                        3.5 => "43699", 3.6 => "43700", 3.8 => "43701", 3.9 => "43702",
                        4.0 => "43703", 4.2 => "43704", 4.3 => "43705", 4.4 => "43706",
                        4.5 => "43707", 4.6 => "43708", 4.7 => "43709", 4.8 => "43710",
                        5.0 => "43711", 5.2 => "43712", 5.3 => "43713", 5.4 => "43714",
                        5.5 => "43715", 5.6 => "43716", 5.7 => "43717", 5.8 => "43718",
                        5.9 => "43719", 6.0 => "43720", 6.2 => "43721", 6.4 => "43722",
                        6.6 => "43723", 6.7 => "43724"
                    ];
                    
                    // Round to nearest 0.1 liter
                    $roundedVolume = round($engineVolumeLiters, 1);
                    $optionId = $volumeMap[$roundedVolume] ?? null;
                    
                    // Check existing features and fix empty engine volume
                    $hasFeature103 = false;
                    $hasFeature2553 = false;
                    
                    foreach ($featuresData['features'] as $index => $feature) {
                        if ($feature['id'] === '103') {
                            $hasFeature103 = true;
                            echo "[" . date('Y-m-d H:i:s') . "] Found feature 103 with value: " . ($feature['value'] ?? 'NULL') . "\n";
                        }
                        if ($feature['id'] === '2553') {
                            $hasFeature2553 = true;
                            echo "[" . date('Y-m-d H:i:s') . "] Found feature 2553 with value: " . ($feature['value'] ?? 'NULL') . "\n";
                            // Check if feature 2553 is empty or invalid
                            if (empty($feature['value']) || trim($feature['value']) === '') {
                                if ($optionId) {
                                    $featuresData['features'][$index]['value'] = $optionId;
                                    echo "[" . date('Y-m-d H:i:s') . "] Updated feature 2553 to: {$optionId}\n";
                                }
                            }
                        }
                    }
                    
                    // If no engine volume features exist, add feature 2553 (liters)
                    if (!$hasFeature103 && !$hasFeature2553 && $optionId) {
                        $featuresData['features'][] = [
                            "id" => "2553",
                            "value" => $optionId
                        ];
                        echo "[" . date('Y-m-d H:i:s') . "] Added feature 2553 with value: {$optionId}\n";
                    }
                    
                    echo "[" . date('Y-m-d H:i:s') . "] Final check - hasFeature103: " . ($hasFeature103 ? 'YES' : 'NO') . ", hasFeature2553: " . ($hasFeature2553 ? 'YES' : 'NO') . ", optionId: " . ($optionId ?? 'NULL') . "\n";
                }
                
                // Create new 999.md listing using saved data
                try {
                    // Create API service with the determined account ID
                    $accountIdForApi = ($catalogType === 'in_stock') ? ($apiAccountId ?? 2) : 3;
                    $api999Service = new \App\Services\Api999Service($accountIdForApi);
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
