<?php
/**
 * Facebook Scheduled Posts Cron Job
 * Runs every minute to check and publish scheduled Facebook posts
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
    
    echo "[" . date('Y-m-d H:i:s') . "] Facebook Cron Started\n";
    
    // Get Facebook settings
    $settings = [];
    $stmt = $db->prepare("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%facebook%'");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $settings[$row['name']] = $row['value'];
    }
    
    // Check if at least one location has Facebook settings configured
    $hasLocation1 = !empty($settings['location_1_facebook_page_id']) && !empty($settings['location_1_facebook_token']);
    $hasLocation2 = !empty($settings['location_2_facebook_page_id']) && !empty($settings['location_2_facebook_token']);
    
    if (!$hasLocation1 && !$hasLocation2) {
        throw new Exception('No Facebook settings configured for any location');
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Facebook settings available for: ";
    if ($hasLocation1) echo "Location 1 ";
    if ($hasLocation2) echo "Location 2 ";
    echo "\n";
    
    // Get pending posts that should be published now
    $currentDateTime = date('Y-m-d H:i:s');
    $stmt = $db->prepare("
        SELECT * FROM {$prefx}_scheduled_facebook_posts 
        WHERE status = 'pending' 
        AND CONCAT(scheduled_date, ' ', scheduled_time) <= :current_time
        ORDER BY scheduled_date, scheduled_time
        LIMIT 10
    ");
    $stmt->execute(['current_time' => $currentDateTime]);
    $pendingPosts = $stmt->fetchAll();
    
    if (empty($pendingPosts)) {
        echo "[" . date('Y-m-d H:i:s') . "] No pending posts to publish\n";
        exit(0);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Found " . count($pendingPosts) . " posts to publish\n";
    
    // Include PublicationService
    require_once __DIR__ . '/../App/Services/PublicationService.php';
    $publicationService = new \App\Services\PublicationService($db, $prefx);
    
    foreach ($pendingPosts as $post) {
        try {
            echo "[" . date('Y-m-d H:i:s') . "] Processing post ID: {$post['id']}, Car ID: {$post['car_id']}\n";
            
            // Get car data
            $stmt = $db->prepare("SELECT * FROM {$prefx}_car_ctlg WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $post['car_id']]);
            $carData = $stmt->fetch();
            
            if (!$carData) {
                throw new Exception("Car not found: {$post['car_id']}");
            }
            
            // Get Facebook settings based on car location
            $carLocation = $carData['loc'] ?? 1;
            echo "[" . date('Y-m-d H:i:s') . "] Car location: {$carLocation}\n";
            
            $facebookSettings = $publicationService->getFacebookSettings($carData);
            if (!$facebookSettings) {
                throw new Exception('Facebook settings not found for car location ' . $carLocation);
            }
            
            // Log which settings are being used
            $tokenPreview = substr($facebookSettings['token'], 0, 10) . '...' . substr($facebookSettings['token'], -10);
            echo "[" . date('Y-m-d H:i:s') . "] Using Page ID: {$facebookSettings['page_id']}\n";
            echo "[" . date('Y-m-d H:i:s') . "] Using Token: {$tokenPreview}\n";
            
            // Generate message
            $message = $publicationService->generateFacebookMessage($carData, $post['catalog_type']);
            
            // Get car photos (multiple photos like Telegram)
            $stmt = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE it_id = :car_id ORDER BY pos ASC LIMIT 10");
            $stmt->execute(['car_id' => $post['car_id']]);
            $photos = $stmt->fetchAll();
            
            if (empty($photos)) {
                throw new Exception("No photos found for car: {$post['car_id']}");
            }
            
            // Build correct photo path using the same structure as in AJAX
            // Auto-detect site structure for photo path
            $document_root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__);
            
            // Ensure we have the full path
            if (!$document_root || $document_root === dirname(__DIR__)) {
                $document_root = '/home/sautom/public_html';
            }
            
            if (strpos($document_root, 'testline8392.sauto.md') !== false) {
                // Test site: public_html/testline8392.sauto.md/media
                $photo_folder = $document_root . '/media/images/upload/car';
            } else {
                // Production site: public_html/media  
                $photo_folder = $document_root . '/media/images/upload/car';
            }
            
            echo "[" . date('Y-m-d H:i:s') . "] Photo folder: {$photo_folder}\n";
            echo "[" . date('Y-m-d H:i:s') . "] Found " . count($photos) . " photos in database for car {$post['car_id']}\n";
            
            // Build media array like Telegram
            $media = [];
            foreach ($photos as $photo) {
                $photoPath = $photo_folder . '/' . $photo['path'] . '/' . $post['car_id'] . '/high/' . $photo['name'] . '.jpg';
                
                if (file_exists($photoPath)) {
                    $media[] = $photoPath;
                    echo "[" . date('Y-m-d H:i:s') . "] Found photo: {$photoPath}\n";
                } else {
                    echo "[" . date('Y-m-d H:i:s') . "] Photo not found: {$photoPath}\n";
                    
                    // Try alternative paths
                    $alt_paths = [
                        $photo_folder . '/' . $photo['path'] . '/' . $post['car_id'] . '/' . $photo['name'] . '.jpg',
                        $photo_folder . '/' . $post['car_id'] . '/high/' . $photo['name'] . '.jpg',
                        $photo_folder . '/' . $post['car_id'] . '/' . $photo['name'] . '.jpg'
                    ];
                    
                    foreach ($alt_paths as $alt_path) {
                        if (file_exists($alt_path)) {
                            $media[] = $alt_path;
                            echo "[" . date('Y-m-d H:i:s') . "] Found alternative photo: {$alt_path}\n";
                            break;
                        }
                    }
                }
            }
            
            echo "[" . date('Y-m-d H:i:s') . "] Total media files found: " . count($media) . "\n";
            
            if (empty($media)) {
                throw new Exception("No valid photo files found for car: {$post['car_id']}");
            }
            
            // Publish to Facebook with multiple photos
            $facebookPostId = publishToFacebook($facebookSettings, $message, $media);
            
            // Update post as published
            $stmt = $db->prepare("
                UPDATE {$prefx}_scheduled_facebook_posts 
                SET status = 'published', 
                    facebook_post_id = :post_id, 
                    published_at = NOW(),
                    error_message = NULL
                WHERE id = :id
            ");
            $stmt->execute([
                'post_id' => $facebookPostId,
                'id' => $post['id']
            ]);
            
            // Update car table to show Facebook icon in admin
            $stmt = $db->prepare("
                UPDATE {$prefx}_car_ctlg 
                SET facebook_published = 1 
                WHERE id = :car_id
            ");
            $stmt->execute(['car_id' => $post['car_id']]);
            
            echo "[" . date('Y-m-d H:i:s') . "] Successfully published post ID: {$post['id']}, Facebook ID: {$facebookPostId}\n";
            echo "[" . date('Y-m-d H:i:s') . "] Updated car {$post['car_id']} facebook_published status\n";
            
        } catch (Exception $e) {
            // Update post as failed
            $stmt = $db->prepare("
                UPDATE {$prefx}_scheduled_facebook_posts 
                SET status = 'failed', 
                    error_message = :error
                WHERE id = :id
            ");
            $stmt->execute([
                'error' => $e->getMessage(),
                'id' => $post['id']
            ]);
            
            echo "[" . date('Y-m-d H:i:s') . "] Failed to publish post ID: {$post['id']}, Error: " . $e->getMessage() . "\n";
        }
        
        // Small delay between posts
        sleep(2);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Facebook Cron Completed\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Publish photo and message to Facebook
 */
function publishToFacebook($settings, $message, $mediaPaths) {
    $pageId = $settings['page_id'];
    $accessToken = $settings['token'];
    
    echo "[" . date('Y-m-d H:i:s') . "] Publishing to Facebook - received " . (is_array($mediaPaths) ? count($mediaPaths) : 1) . " media files\n";
    
    if (is_array($mediaPaths) && count($mediaPaths) > 1) {
        // Multiple photos - create album
        return publishFacebookAlbum($pageId, $accessToken, $message, $mediaPaths);
    } else {
        // Single photo - use photos endpoint
        $photoPath = is_array($mediaPaths) ? $mediaPaths[0] : $mediaPaths;
        echo "[" . date('Y-m-d H:i:s') . "] Using single photo: {$photoPath}\n";
        
        $url = "https://graph.facebook.com/v22.0/{$pageId}/photos";
        $postData = [
            'message' => $message,
            'source' => new CURLFile($photoPath),
            'access_token' => $accessToken
        ];
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "[" . date('Y-m-d H:i:s') . "] Facebook API response code: {$httpCode}\n";
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: {$error}");
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    echo "[" . date('Y-m-d H:i:s') . "] Facebook API response: " . json_encode($data) . "\n";
    
    if ($httpCode !== 200 || !isset($data['id'])) {
        $errorMsg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown Facebook API error';
        throw new Exception("Facebook API Error (HTTP {$httpCode}): {$errorMsg}");
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Facebook post created successfully with ID: {$data['id']}\n";
    return $data['id'];
}

/**
 * Publish multiple photos as Facebook album
 */
function publishFacebookAlbum($pageId, $accessToken, $message, $mediaPaths) {
    echo "[" . date('Y-m-d H:i:s') . "] Creating Facebook album with " . count($mediaPaths) . " photos\n";
    
    // Step 1: Upload photos without publishing
    $photoIds = [];
    foreach ($mediaPaths as $index => $photoPath) {
        echo "[" . date('Y-m-d H:i:s') . "] Uploading photo " . ($index + 1) . ": " . basename($photoPath) . "\n";
        
        $uploadUrl = "https://graph.facebook.com/v22.0/{$pageId}/photos";
        $uploadData = [
            'source' => new CURLFile($photoPath),
            'published' => 'false',
            'access_token' => $accessToken
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $uploadUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $uploadData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 60
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error uploading photo " . ($index + 1) . ": {$error}");
        }
        
        curl_close($ch);
        
        $data = json_decode($response, true);
        if ($httpCode !== 200 || !isset($data['id'])) {
            $errorMsg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            throw new Exception("Failed to upload photo " . ($index + 1) . " (HTTP {$httpCode}): {$errorMsg}");
        }
        
        $photoIds[] = ['media_fbid' => $data['id']];
        echo "[" . date('Y-m-d H:i:s') . "] Photo " . ($index + 1) . " uploaded with ID: {$data['id']}\n";
    }
    
    // Step 2: Create post with attached media
    echo "[" . date('Y-m-d H:i:s') . "] Creating post with " . count($photoIds) . " attached photos\n";
    
    $postUrl = "https://graph.facebook.com/v22.0/{$pageId}/feed";
    $postData = [
        'message' => $message,
        'attached_media' => json_encode($photoIds),
        'access_token' => $accessToken
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $postUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "[" . date('Y-m-d H:i:s') . "] Facebook album API response code: {$httpCode}\n";
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error creating album: {$error}");
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    echo "[" . date('Y-m-d H:i:s') . "] Facebook album API response: " . json_encode($data) . "\n";
    
    if ($httpCode !== 200 || !isset($data['id'])) {
        $errorMsg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown Facebook API error';
        throw new Exception("Facebook Album API Error (HTTP {$httpCode}): {$errorMsg}");
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Facebook album created successfully with ID: {$data['id']}\n";
    return $data['id'];
}
?>
