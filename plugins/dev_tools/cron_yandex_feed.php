<?php
/**
 * Yandex YML Feed Generator - Cron Job
 * 
 * This script should be run via cron to automatically regenerate the Yandex feed
 * 
 * Cron setup: Add to crontab with desired schedule
 * Example: Run every 6 hours
 */

// Include shared generator function
require_once __DIR__ . '/yandex_feed_generator.php';

// Log file
$logFile = __DIR__ . '/yandex_feed_cron.log';

try {
    $startTime = microtime(true);
    
    // Generate feed
    $result = generateYandexFeed();
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    // Log success
    $logMessage = sprintf(
        "[%s] SUCCESS - Generated %d offers in %s seconds. File: %s (%s bytes)\n",
        $result['timestamp'],
        $result['offers'],
        $executionTime,
        basename($result['file']),
        number_format($result['size'])
    );
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    echo $logMessage;
    
} catch (Exception $e) {
    // Log error
    $logMessage = sprintf(
        "[%s] ERROR - %s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    );
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    echo $logMessage;
    exit(1);
}
?>
