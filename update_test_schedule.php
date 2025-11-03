<?php
/**
 * Update test schedule to current time + 2 minutes for testing
 */

require_once __DIR__ . '/environment.php';

try {
    $pdo = new PDO(
        'mysql:host=' . SQL_HOST . ';dbname=' . SQL_DB . ';charset=' . SQL_CHARSET,
        SQL_USER,
        SQL_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Update the schedule to be 2 minutes from now
    $newDateTime = date('Y-m-d H:i:s', strtotime('+2 minutes'));
    $newDate = date('Y-m-d', strtotime('+2 minutes'));
    $newTime = date('H:i:s', strtotime('+2 minutes'));
    
    $stmt = $pdo->prepare("
        UPDATE gh3sp_sauto_personal_schedules 
        SET schedule_date = ?, schedule_time = ?, status = 'pending'
        WHERE id = 1
    ");
    
    $result = $stmt->execute([$newDate, $newTime]);
    
    if ($result) {
        echo "✅ Schedule updated successfully!\n";
        echo "New schedule time: {$newDateTime}\n";
        echo "Status: pending\n";
        echo "\nNow you can test the cron job by running: php test_sauto_cron.php\n";
    } else {
        echo "❌ Failed to update schedule\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
