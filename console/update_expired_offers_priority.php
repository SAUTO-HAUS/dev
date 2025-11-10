<?php
/**
 * Cron Job: Update Priority for Expired Offers
 * 
 * This script automatically updates the priority field for on_order cars:
 * - Active offers (timer not expired): priority = 1.0
 * - Expired offers (timer expired): priority = 0.4
 * 
 * Run this script every 30 minutes via cron
 * Cron schedule: At minute 0 and 30 of every hour
 */

// Include necessary files
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../App/Core/Container.php');

try {
    $db = \App\Core\Container::get('db');
    $prefx = \App\Core\Container::get('prefix');
    
    $current_time = time();
    
    // Update expired offers to priority = 0.4
    $sql_expired = "UPDATE {$prefx}_car_ctlg 
                    SET priority = 0.4 
                    WHERE catalog_type = 'on_order' 
                    AND offer_timer_end > 0 
                    AND offer_timer_end < :current_time 
                    AND priority != 0.4";
    
    $stmt_expired = $db->prepare($sql_expired);
    $stmt_expired->execute(['current_time' => $current_time]);
    $expired_count = $stmt_expired->rowCount();
    
    // Update active offers to priority = 1.0
    $sql_active = "UPDATE {$prefx}_car_ctlg 
                   SET priority = 1.0 
                   WHERE catalog_type = 'on_order' 
                   AND offer_timer_end > 0 
                   AND offer_timer_end >= :current_time 
                   AND priority != 1.0";
    
    $stmt_active = $db->prepare($sql_active);
    $stmt_active->execute(['current_time' => $current_time]);
    $active_count = $stmt_active->rowCount();
    
    // Output for cron logging
    echo date('Y-m-d H:i:s') . " - Priority updated: {$expired_count} expired offers set to 0.4, {$active_count} active offers set to 1.0\n";
    
} catch (Exception $e) {
    // Output error for cron logging
    echo date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
}
