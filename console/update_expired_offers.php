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

// Set timezone
date_default_timezone_set('Europe/Chisinau');

// Define security constant for included files
define('_DOIT', true);

try {
    // Database connection using environment settings
    require_once __DIR__ . '/../environment.php';
    
    $db = new PDO("mysql:host=" . SQL_HOST . ";dbname=" . SQL_DB . ";charset=" . SQL_CHARSET, SQL_USER, SQL_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $prefx = 'gh3sp';
    
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
    }

    // 1b. Mark expired on_order cars as out of stock (n_a=1), same as the manual
    // "Not available" toggle. Only flip cars not already n_a=1, so we can count
    // real changes. Reversible below when the timer is renewed.
    $sql_na_on = "UPDATE {$prefx}_car_ctlg
                  SET `n_a` = 1
                  WHERE catalog_type = 'on_order'
                  AND `n_a` = 0
                  AND offer_timer_end > 0
                  AND offer_timer_end < :current_time";
    $stmt_na_on = $db->prepare($sql_na_on);
    $stmt_na_on->execute(['current_time' => $current_time]);
    $na_on_count = $stmt_na_on->rowCount();

    if ($na_on_count > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] 🚫 Marked {$na_on_count} car(s) as out of stock - timer expired\n";
    }
    
    // ============================================================================
    // 2. RESTORE postponed posts if timer has been extended/renewed
    // ============================================================================
    $sql_restore = "UPDATE {$prefx}_sauto_personal_schedules s
                    INNER JOIN {$prefx}_car_ctlg c ON s.car_id = c.id
                    SET s.status = 'pending',
                        s.error_message = NULL
                    WHERE s.status = 'postponed'
                    AND c.catalog_type = 'on_order'
                    AND c.offer_timer_end > 0 
                    AND c.offer_timer_end >= :current_time";
    
    $stmt_restore = $db->prepare($sql_restore);
    $stmt_restore->execute(['current_time' => $current_time]);
    $restored_count = $stmt_restore->rowCount();

    if ($restored_count > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] 🔄 Restored {$restored_count} postponed post(s) - timer renewed\n";
    }

    // 2b. Bring expired cars back in stock (n_a=0) when the timer is renewed into
    // the future. Mirrors the postpone/restore logic so it's fully reversible.
    $sql_na_off = "UPDATE {$prefx}_car_ctlg
                   SET `n_a` = 0
                   WHERE catalog_type = 'on_order'
                   AND `n_a` = 1
                   AND offer_timer_end > 0
                   AND offer_timer_end >= :current_time";
    $stmt_na_off = $db->prepare($sql_na_off);
    $stmt_na_off->execute(['current_time' => $current_time]);
    $na_off_count = $stmt_na_off->rowCount();

    if ($na_off_count > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] ✅ Restored {$na_off_count} car(s) to in stock - timer renewed\n";
    }

    if ($postponed_count === 0 && $restored_count === 0 && $na_on_count === 0 && $na_off_count === 0) {
        echo "[" . date('Y-m-d H:i:s') . "] ✅ No changes needed - all scheduled posts are up to date\n";
    }
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
