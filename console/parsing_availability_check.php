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
$sql = "SELECT id, source, source_id, car_ctlg_id FROM {$prefx}_parsing_cars
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
// Sold on the source: flag the sauto ad out of stock (n_a=1) AND drop the timer to 0.
// The cleanup cron then permanently deletes on_order + n_a=1 cars. in_stock stay.
$markSautoNa     = $db->prepare("UPDATE {$prefx}_car_ctlg SET n_a = 1, offer_timer_end = 0 WHERE id = ? AND (n_a <> 1 OR offer_timer_end <> 0)");
// And POSTPONE the car's still-pending 999 republish schedules (the already-posted
// ad stays). Postponed (not cancelled) so they resume automatically if the car comes
// back in stock — e.g. the operator unticks "out of stock".
$cancel999       = $db->prepare("UPDATE gh3sp_sauto_personal_schedules
    SET status = 'postponed' WHERE car_id = ? AND status = 'pending'");

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
            // Sold on source → flag the sauto ad out of stock (n_a=1) + drop timer.
            // The cleanup cron deletes on_order n_a=1 cars; in_stock stay flagged.
            $ctlgId = (int)($car['car_ctlg_id'] ?? 0);
            if ($ctlgId > 0) {
                $markSautoNa->execute([$ctlgId]);
                $cancelled = 0;
                try { $cancel999->execute([$ctlgId]); $cancelled = $cancel999->rowCount(); }
                catch (Throwable $e) {}
                echo "  Sold #{$car['id']} (ctlg {$ctlgId}): n_a=1"
                    . ($cancelled > 0 ? ", {$cancelled} x 999 schedule cancelled" : '') . "\n";
            }
        }
        $checked++;
        // 1.5s between requests — slow, human-like pacing so Encar never sees a
        // burst. 150 cars/run => ~4 min, well within an hourly schedule.
        usleep(1500000);
    } catch (Throwable $e) {
        echo "  Error #{$car['id']}: " . $e->getMessage() . "\n";
    }
}

// Reconcile: any car SOLD in /parsing/published (status=unavailable) that is still
// on sauto → flag out of stock (n_a=1) + drop timer. Cleanup cron deletes on_order.
$reconcileNa = $db->prepare("UPDATE {$prefx}_car_ctlg c
    INNER JOIN {$prefx}_parsing_cars pc ON pc.car_ctlg_id = c.id
    SET c.n_a = 1, c.offer_timer_end = 0
    WHERE pc.status = 'unavailable' AND pc.car_ctlg_id > 0
      AND (c.n_a <> 1 OR c.offer_timer_end <> 0)");
$reconcileNa->execute();
$reconciled = $reconcileNa->rowCount();
if ($reconciled > 0) {
    echo "[" . date('Y-m-d H:i:s') . "] 🔁 {$reconciled} sold car(s) set out of stock\n";
}
// Deletion of on_order + n_a=1 cars is handled by the dedicated cleanup cron.

echo "[" . date('Y-m-d H:i:s') . "] Checked: {$checked}, marked unavailable: {$unavailable}\n";
