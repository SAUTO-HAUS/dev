<?php
/**
 * Cron job to recalculate R/M/P tech string for all cars
 * Run every Thursday at 02:00 server time
 * Crontab: 0 2 * * 4 php /path/to/console/update_tech_string.php
 */

define('_DOIT', 1);
require_once __DIR__ . '/../content/default/defines.php';
require_once __DIR__ . '/../content/default/functions.php';
require_once __DIR__ . '/../environment.php';
require_once __DIR__ . '/../content/default/config.php';
require_once __DIR__ . '/../content/default/dbi.php';

$log_file = __DIR__ . '/logs/tech_string_update.log';

function logMessage($message, $log_file) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

logMessage("Starting R/M/P tech string recalculation...", $log_file);

try {
    // Get all active cars from in_stock catalog
    $sql = "SELECT id, date, status_changed_at, n_a FROM {$prefx}_car_ctlg WHERE catalog_type = 'in_stock' AND act = 1";
    $stmt = $db->query($sql);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = count($cars);
    logMessage("Found $count cars to process", $log_file);
    
    $current_time = time();
    $seconds_per_week = 7 * 24 * 60 * 60;
    
    foreach ($cars as $car) {
        $weeks_since_creation = 0;
        $weeks_since_status_change = 0;
        
        if (!empty($car['date'])) {
            $weeks_since_creation = floor(($current_time - $car['date']) / $seconds_per_week);
        }
        
        if (!empty($car['n_a']) && $car['n_a'] == 1 && !empty($car['status_changed_at'])) {
            $status_timestamp = strtotime($car['status_changed_at']);
            if ($status_timestamp) {
                $weeks_since_status_change = floor(($current_time - $status_timestamp) / $seconds_per_week);
            }
        }
        
        $tech_string = 'R' . $weeks_since_creation . '-M' . $weeks_since_status_change . '-P10';
        
        // Log each car's tech string (optional, can be removed for production)
        // logMessage("Car ID {$car['id']}: $tech_string", $log_file);
    }
    
    logMessage("R/M/P tech string recalculation completed successfully for $count cars", $log_file);
    
} catch (PDOException $e) {
    logMessage("Database error: " . $e->getMessage(), $log_file);
    exit(1);
}

logMessage("Cron job finished", $log_file);
