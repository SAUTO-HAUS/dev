<?php
/**
 * Update Expired Offers Cron Job
 * 
 * This script runs periodically to postpone scheduled 999.md posts 
 * if the car's offer timer has expired.
 * 
 * Posts that were already published before timer expiration remain published.
 * Only pending posts scheduled after timer expiration are postponed.
 * 
 * Run: php console/update_expired_offers.php
 * Cron: Run every 5 minutes
 */

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../App/Core/Container.php');

try {
    $db = \App\Core\Container::get('db');
    $prefx = \App\Core\Container::get('prefix');
    
    $current_time = time();
    
    echo "[" . date('Y-m-d H:i:s') . "] Starting expired offers update...\n";
    
    // ============================================================================
    // 1. POSTPONE scheduled 999.md posts if offer timer has expired
    // ============================================================================
    $sql_postpone = "UPDATE {$prefx}_sauto_personal_schedules s
                     INNER JOIN {$prefx}_car_ctlg c ON s.car_id = c.id
                     SET s.status = 'postponed',
                         s.error_message = CONCAT('Timer expired at ', FROM_UNIXTIME(c.offer_timer_end))
                     WHERE s.status = 'pending'
                     AND c.catalog_type = 'on_order'
                     AND c.offer_timer_end > 0 
                     AND c.offer_timer_end < :current_time";
    
    $stmt_postpone = $db->prepare($sql_postpone);
    $stmt_postpone->execute(['current_time' => $current_time]);
    $postponed_count = $stmt_postpone->rowCount();
    
    if ($postponed_count > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] ⏸️  Postponed {$postponed_count} scheduled post(s) - timer expired\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ✅ No changes needed - all scheduled posts are up to date\n";
    }
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
