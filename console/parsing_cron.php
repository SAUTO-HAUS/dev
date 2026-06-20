<?php
/**
 * Parsing module cron — runs active filters on schedule.
 *
 * Pick frequency in admin → Parsing → Setări (cron_frequency_minutes).
 * Recommended cron line (every 5 minutes — the script itself respects
 * each filter's frequency setting and skips filters that are not due yet):
 *
 *   star/5 star star star star php /path/to/site/console/parsing_cron.php >> /path/to/site/logs/parsing_cron.log 2>&1
 *
 * Run manually:  php console/parsing_cron.php
 * Dry-run a single filter:  php console/parsing_cron.php --filter=12
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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
require_once __DIR__ . '/parsing_trims_helper.php';   // translate_korean_trims()

use App\Core\Container;
use App\Services\Parsing\ParsingOrchestrator;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');

Container::set('db', $db);
Container::set('prefix', 'gh3sp');

$prefx = 'gh3sp';

$argFilterId = null;
foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--filter=(\d+)$/', $arg, $m)) {
        $argFilterId = (int)$m[1];
    }
}

// Prevent overlapping runs: if a previous run is still going (slow Encar),
// skip this one so we never hit the source with two concurrent processes.
$lockFile = __DIR__ . '/../logs/parsing_cron.lock';
$lockHandle = fopen($lockFile, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another run is still active — skipping.\n";
    exit;
}
register_shutdown_function(function () use ($lockHandle) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
});

echo "[" . date('Y-m-d H:i:s') . "] Parsing cron started\n";

$freqStmt = $db->prepare("SELECT setting_value FROM {$prefx}_parsing_settings WHERE setting_key = 'cron_frequency_minutes'");
$freqStmt->execute();
$frequencyMinutes = (int)($freqStmt->fetchColumn() ?: 60);

if ($argFilterId) {
    $sql = "SELECT * FROM {$prefx}_parsing_filters WHERE id = ? AND active = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$argFilterId]);
} else {
    $sql = "SELECT * FROM {$prefx}_parsing_filters
            WHERE active = 1
              AND (last_run_at IS NULL OR last_run_at < DATE_SUB(NOW(), INTERVAL ? MINUTE))
            ORDER BY last_run_at IS NULL DESC, last_run_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$frequencyMinutes]);
}

$filters = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orchestrator = new ParsingOrchestrator();

if (empty($filters)) {
    echo "[" . date('Y-m-d H:i:s') . "] No filters due to run (frequency: {$frequencyMinutes} min)\n";
} else {
    echo "[" . date('Y-m-d H:i:s') . "] Running " . count($filters) . " filter(s)\n";
}

foreach ($filters as $filter) {
    echo "[" . date('Y-m-d H:i:s') . "] → Filter #{$filter['id']} \"{$filter['name']}\"\n";
    try {
        $summary = $orchestrator->runFilter((int)$filter['id'], false, 'cron');
        foreach ($summary as $source => $stats) {
            if (isset($stats['error']) && $stats['error']) {
                echo "    [{$source}] ERROR: {$stats['error']}\n";
            } else {
                echo "    [{$source}] found={$stats['found']} imported={$stats['imported']} skipped={$stats['skipped']}\n";
            }
        }
    } catch (Throwable $e) {
        echo "    EXCEPTION: " . $e->getMessage() . "\n";
        @file_put_contents(
            __DIR__ . '/../logs/parsing_cron_errors.log',
            '[' . date('Y-m-d H:i:s') . "] Filter #{$filter['id']}: " . $e->getMessage() . "\n",
            FILE_APPEND
        );
    }
}

// Enrich cars still missing gearbox / full images (Encar detail API).
// 100 cars / 120s per run. Requests stay sequential (one detail page at a
// time) — Encar tolerates this rate, so we don't risk an IP ban.
try {
    $enriched = $orchestrator->enrichRecent(100, 120);
    echo "[" . date('Y-m-d H:i:s') . "] Enriched {$enriched} cars\n";
} catch (Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Enrich error: " . $e->getMessage() . "\n";
}

// Translate Korean Encar trims (car_sub_model + frozen title_ro) to Latin via
// Groq. parsing_enrich.php is NOT in cron, so this runs here, every 5 min.
try {
    $tr = translate_korean_trims($db, 'gh3sp');
    echo "[" . date('Y-m-d H:i:s') . "] Trims translated: {$tr}\n";
} catch (Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Trim translation error: " . $e->getMessage() . "\n";
}

// NOTE: AI enrichment (HP + drive_type) is intentionally NOT run here.
// It only fires on demand when the user clicks a card action button
// (Publică / 999 / Facebook / Telegram / Caracteristici / Editează),
// via ensureSpecsEnriched() → ajax 'ai_enrich_specs'. This keeps AI
// usage tied to real publishing intent instead of burning calls on
// every parsed car in the background.

try {
    $rejSql = 'UPDATE '.$prefx.'_parsing_cars
        SET status = "rejected", rejected_at = NOW()
        WHERE status = "proposed"
          AND (car_ctlg_id IS NULL OR car_ctlg_id = 0)   -- never touch published cars
          AND source IN ("ecarstrade", "openlane")
          AND COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(raw_data, "$.auction_end")),
                JSON_UNQUOTE(JSON_EXTRACT(raw_data, "$.BatchEndDate")),
                JSON_UNQUOTE(JSON_EXTRACT(raw_data, "$.raw_data.auction_end")),
                JSON_UNQUOTE(JSON_EXTRACT(raw_data, "$.raw_data.BatchEndDate"))
              ) IS NOT NULL
          AND STR_TO_DATE(
                REPLACE(REPLACE(COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(raw_data, "$.auction_end")),
                    JSON_UNQUOTE(JSON_EXTRACT(raw_data, "$.BatchEndDate")),
                    JSON_UNQUOTE(JSON_EXTRACT(raw_data, "$.raw_data.auction_end")),
                    JSON_UNQUOTE(JSON_EXTRACT(raw_data, "$.raw_data.BatchEndDate"))
                ), "T", " "), "Z", ""),
                "%Y-%m-%d %H:%i:%s"
              ) < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)';
    $rejStmt = $db->prepare($rejSql);
    $rejStmt->execute();
    echo "[" . date('Y-m-d H:i:s') . "] Rejected {$rejStmt->rowCount()} ended-auction cars\n";
} catch (Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Auction cleanup error: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Parsing cron finished\n";
