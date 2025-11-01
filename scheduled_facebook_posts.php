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
    require_once __DIR__ . '/environment.php';
    
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
    
    if (empty($settings['location_1_facebook_page_id']) || empty($settings['location_1_facebook_token'])) {
        throw new Exception('Facebook settings not configured');
    }
    
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
    require_once __DIR__ . '/App/Services/PublicationService.php';
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
            $facebookSettings = $publicationService->getFacebookSettings($carData);
            if (!$facebookSettings) {
                throw new Exception('Facebook settings not found for car location');
            }
            
            // Generate message
            $message = $publicationService->generateFacebookMessage($carData, $post['catalog_type']);
            
            // Get car photos
            $stmt = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE it_id = :car_id ORDER BY pos ASC LIMIT 1");
            $stmt->execute(['car_id' => $post['car_id']]);
            $photo = $stmt->fetch();
            
            if (!$photo) {
                throw new Exception("No photos found for car: {$post['car_id']}");
            }
            
            $photoPath = __DIR__ . '/content/admin/uploads/cars/' . $photo['name'];
            if (!file_exists($photoPath)) {
                throw new Exception("Photo file not found: {$photoPath}");
            }
            
            // Publish to Facebook
            $facebookPostId = publishToFacebook($facebookSettings, $message, $photoPath);
            
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
            
            echo "[" . date('Y-m-d H:i:s') . "] Successfully published post ID: {$post['id']}, Facebook ID: {$facebookPostId}\n";
            
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
function publishToFacebook($settings, $message, $photoPath) {
    $pageId = $settings['page_id'];
    $accessToken = $settings['token'];
    
    // Upload photo with message
    $url = "https://graph.facebook.com/v22.0/{$pageId}/photos";
    
    $postData = [
        'message' => $message,
        'source' => new CURLFile($photoPath),
        'access_token' => $accessToken
    ];
    
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
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: {$error}");
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($httpCode !== 200 || !isset($data['id'])) {
        $errorMsg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown Facebook API error';
        throw new Exception("Facebook API Error (HTTP {$httpCode}): {$errorMsg}");
    }
    
    return $data['id'];
}
?>
