<?php
/**
 * Telegram Scheduled Posts Cron Job
 * Runs every minute to check and publish scheduled Telegram posts
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
    
    echo "[" . date('Y-m-d H:i:s') . "] Telegram Cron Started\n";
    
    // Include PublicationService
    require_once __DIR__ . '/App/Services/PublicationService.php';
    $publicationService = new \App\Services\PublicationService($db, $prefx);
    
    // Get pending posts that should be published now
    $currentDateTime = date('Y-m-d H:i:s');
    $stmt = $db->prepare("
        SELECT * FROM {$prefx}_scheduled_telegram_posts 
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
            
            // Get Telegram settings based on car location
            $telegramSettings = $publicationService->getTelegramSettings($post['catalog_type']);
            if (!$telegramSettings) {
                throw new Exception('Telegram settings not found for car catalog type');
            }
            
            // Generate Telegram message using PublicationService
            $message = $publicationService->generateTelegramMessage($carData, $post['catalog_type']);
            
            // Get car photos (multiple photos like in AJAX)
            $photo_folder = __DIR__ . '/media/images/upload/car';
            $stmt = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE it_id = :car_id ORDER BY pos ASC LIMIT 10");
            $stmt->execute(['car_id' => $post['car_id']]);
            $photos = $stmt->fetchAll();
            
            if (empty($photos)) {
                throw new Exception("No photos found for car: {$post['car_id']}");
            }
            
            // Build media array like in AJAX
            $media = [];
            foreach ($photos as $photo) {
                $file_path = $photo_folder . '/' . $photo['path'] . '/' . $post['car_id'] . '/high/' . $photo['name'] . '.jpg';
                
                if (file_exists($file_path)) {
                    $media[] = [
                        'type' => 'photo',
                        'media' => new \CURLFile(
                            $file_path,
                            mime_content_type($file_path),
                            basename($file_path)
                        )
                    ];
                }
            }
            
            if (empty($media)) {
                throw new Exception("No valid photo files found for car: {$post['car_id']}");
            }
            
            // Include Telegram class
            require_once __DIR__ . '/content/admin/ajax/cars/CTelegram.php';
            
            // Initialize Telegram bot
            $bot = new Telegram([
                'bot_token' => $telegramSettings['bot_token'],
                'chat_id' => $telegramSettings['chat_id']
            ]);
            
            // Send album with caption
            $result = $bot->send_album_with_caption($media, $message, 'HTML');
            $response = json_decode($result, true);
            
            if (!$response || !$response['ok']) {
                throw new Exception("Telegram API error: " . ($response['description'] ?? 'Unknown error'));
            }
            
            $telegramMessageId = $response['result'][0]['message_id'] ?? null;
            
            // Update post as published
            $stmt = $db->prepare("
                UPDATE {$prefx}_scheduled_telegram_posts 
                SET status = 'published', 
                    telegram_message_id = :post_id, 
                    published_at = NOW(),
                    error_message = NULL
                WHERE id = :id
            ");
            $stmt->execute([
                'post_id' => $telegramMessageId,
                'id' => $post['id']
            ]);
            
            // Update car as published
            $stmt = $db->prepare("UPDATE {$prefx}_car_ctlg SET telegram_published = 1 WHERE id = :car_id");
            $stmt->execute(['car_id' => $post['car_id']]);
            
            // Log success
            $publicationService->logPublication(
                $post['car_id'], 
                $post['catalog_type'], 
                'telegram', 
                true, 
                "Published via cron at " . date('Y-m-d H:i:s')
            );
            
            echo "[" . date('Y-m-d H:i:s') . "] Successfully published post ID: {$post['id']}, Telegram ID: {$telegramMessageId}\n";
            
        } catch (Exception $e) {
            // Update post as failed
            $stmt = $db->prepare("
                UPDATE {$prefx}_scheduled_telegram_posts 
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
    
    echo "[" . date('Y-m-d H:i:s') . "] Telegram Cron Completed\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
