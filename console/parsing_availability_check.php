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
// A full 2000-car batch runs ~13 min; never let PHP's time limit kill it mid-run.
set_time_limit(0);

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

// Single-instance lock. The cron runs every 5 min but a run can take up to the
// ~9-min time budget, so runs could overlap and double the request rate.
// flock lets an already-running instance finish and makes the new one exit
// immediately — so we can schedule aggressively without ever overlapping.
$lockFile = sys_get_temp_dir() . '/parsing_availability_check.lock';
$lockHandle = fopen($lockFile, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another run is still in progress — skipping\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Availability check started\n";

$sql = "SELECT id, source, source_id, car_ctlg_id FROM {$prefx}_parsing_cars
        WHERE status IN ('proposed', 'published', 'favorite')
          AND (last_checked_at IS NULL
               OR last_checked_at < DATE_SUB(NOW(), INTERVAL 2 HOUR))
        ORDER BY
          (status = 'proposed') ASC,        -- published/favorite checked first
          last_checked_at IS NULL DESC,      -- never-checked next
          last_checked_at ASC                -- then oldest-checked
        LIMIT 12000";
$stmt = $db->prepare($sql);
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Time budget: process cars until ~9 min elapse, then stop cleanly (the cron fires
// again every 5 min; flock guarantees no overlap). This makes the batch auto-size
// to whatever fits — no need to retune LIMIT as the catalog grows to 30k+.
$startTs      = microtime(true);
$timeBudget   = 9 * 60; // seconds

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

// Cars are checked in waves, all requests in flight together. One at a time the
// run spent about half its time waiting on Encar's ~0.4s latency, so a 30k
// catalog needed ~7.4h to rotate and a sold car could stay on the site for most
// of a day. A wave of 6 brings that down to roughly an hour.
$WAVE  = 6;
$PAUSE = 200000;   // 0.2s between waves, measured safe against Encar

$checked = 0; $unavailable = 0; $budgetHit = false;
foreach (array_chunk($cars, $WAVE) as $wave) {
    // Stop once the time budget is spent — leaves the rest for the next run,
    // keeping each run inside the 10-min cron window regardless of catalog size.
    if (microtime(true) - $startTs >= $timeBudget) {
        $budgetHit = true;
        break;
    }

    // Resolve every Encar car in this wave with one parallel burst. Other
    // sources stay one-at-a-time below — they are a handful of cars, and
    // OpenLane sits behind Cloudflare where a burst is not welcome.
    $verdict  = [];
    $encarIds = [];
    foreach ($wave as $c) {
        if (($c['source'] ?? '') === 'encar' && !empty($c['source_id'])) $encarIds[] = (string)$c['source_id'];
    }
    if ($encarIds) {
        try {
            $enc = AdapterFactory::create('encar');
            if ($enc && method_exists($enc, 'checkAvailabilityBatch')) {
                $verdict = $enc->checkAvailabilityBatch($encarIds, $WAVE);
            }
        } catch (Throwable $e) {
            echo "  Batch failed, falling back one by one: " . $e->getMessage() . "\n";
        }
    }

foreach ($wave as $car) {
    $adapter = AdapterFactory::create($car['source']);
    if (!$adapter) {
        echo "  Skip #{$car['id']}: no adapter for {$car['source']}\n";
        continue;
    }
    try {
        // Use the batch answer when we have one; otherwise ask for this car alone
        // and keep the old per-car pacing for it.
        $sid = (string)$car['source_id'];
        if (array_key_exists($sid, $verdict)) {
            $available = $verdict[$sid];
        } else {
            $available = $adapter->checkAvailability($sid);
            usleep(400000);
        }
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
    } catch (Throwable $e) {
        echo "  Error #{$car['id']}: " . $e->getMessage() . "\n";
    }
}   // cars in this wave

    // One pause per wave, not per car — that is where the speed-up comes from.
    usleep($PAUSE);
}   // waves

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

$elapsed = round(microtime(true) - $startTs);
echo "[" . date('Y-m-d H:i:s') . "] Checked: {$checked}, marked unavailable: {$unavailable}"
    . ", elapsed: {$elapsed}s" . ($budgetHit ? " (time budget hit — more cars pending)" : "") . "\n";
