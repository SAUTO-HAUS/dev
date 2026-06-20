<?php
/**
 * Parsing availability check — marks imported cars as unavailable when the source
 * listing disappears or flips to SOLD.
 *
 * Runs in small batches (150) on an HOURLY schedule with 1.5s between requests,
 * so the request rate to Encar stays low and steady (no big nightly burst that
 * could look bot-like / trip rate limits). Each car is re-checked at most once
 * per ~day (20h window), rotating oldest-first; published/favorite get priority.
 *
 * Recommended cron (hourly):
 *   0 star star star star php /path/to/site/console/parsing_availability_check.php >> /path/to/site/logs/parsing_availability.log 2>&1
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';
    $classPath = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');

use App\Core\Container;
use App\Services\Parsing\AdapterFactory;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');
$prefx = 'gh3sp';

Container::set('db', $db);
Container::set('prefix', $prefx);

echo "[" . date('Y-m-d H:i:s') . "] Availability check started\n";

// Small batch per run, called hourly — keeps the request rate towards Encar low
// and steady (no big nightly burst that could look like a bot / trip rate limits).
// Priority: published + favorite first (those must stay real), then proposed; and
// within that, the longest-unchecked first so every car rotates through over time.
$sql = "SELECT id, source, source_id FROM {$prefx}_parsing_cars
        WHERE status IN ('proposed', 'published', 'favorite')
          AND (last_checked_at IS NULL
               OR last_checked_at < DATE_SUB(NOW(), INTERVAL 20 HOUR))
        ORDER BY
          (status = 'proposed') ASC,        -- published/favorite checked first
          last_checked_at IS NULL DESC,      -- never-checked next
          last_checked_at ASC                -- then oldest-checked
        LIMIT 150";
$stmt = $db->prepare($sql);
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cars)) {
    echo "[" . date('Y-m-d H:i:s') . "] Nothing to check\n";
    exit(0);
}

$markAvailable   = $db->prepare("UPDATE {$prefx}_parsing_cars SET last_checked_at = NOW() WHERE id = ?");
$markUnavailable = $db->prepare("UPDATE {$prefx}_parsing_cars SET status = 'unavailable', last_checked_at = NOW() WHERE id = ?");

$checked = 0; $unavailable = 0;
foreach ($cars as $car) {
    $adapter = AdapterFactory::create($car['source']);
    if (!$adapter) {
        echo "  Skip #{$car['id']}: no adapter for {$car['source']}\n";
        continue;
    }
    try {
        $available = $adapter->checkAvailability($car['source_id']);
        if ($available) {
            $markAvailable->execute([$car['id']]);
        } else {
            $markUnavailable->execute([$car['id']]);
            $unavailable++;
        }
        $checked++;
        // 1.5s between requests — slow, human-like pacing so Encar never sees a
        // burst. 150 cars/run => ~4 min, well within an hourly schedule.
        usleep(1500000);
    } catch (Throwable $e) {
        echo "  Error #{$car['id']}: " . $e->getMessage() . "\n";
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Checked: {$checked}, marked unavailable: {$unavailable}\n";
