<?php
// Build App/Services/Parsing/Adapters/autotrader_taxonomy.json — the make/model
// list the /parsing filter UI uses for AutoTrader.ca.
//
// AutoTrader ships its taxonomy inside the page state (__NEXT_DATA__): the make
// list comes with any search page, but a make's MODELS only appear on that
// make's own page — so this is one request per make (~120), a couple of minutes.
// No cookie or account is involved, so unlike the other sources this can never
// fail on expired credentials.
//
// Unlike the other sources, this taxonomy is a STATIC catalog (every make/model
// AutoScout24 knows), not a snapshot of live stock — it only changes when a new
// model is launched. So it runs MONTHLY, not weekly like Encar/OpenLane/
// eCarsTrade/Auto1, whose lists move with their inventory.
//
// CRON (monthly, 1st at 07:00):
//   0 7 1 * * cd /home/sautom/public_html && /usr/local/bin/php console/autotrader_taxonomy_dump.php >> .../logs/autotrader_taxonomy.log 2>&1
// Web:  /console/autotrader_taxonomy_dump.php?token=cron2026

$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && ($_GET['token'] ?? '') !== 'cron2026') {
    http_response_code(403); die('Forbidden');
}
if (!$IS_CLI) header('Content-Type: text/plain; charset=utf-8');
ignore_user_abort(true);
set_time_limit(0);
error_reporting(E_ALL); ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');
spl_autoload_register(function ($c) {
    $p = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $c) . '.php';
    if (file_exists($p)) require_once $p;
});
require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
\App\Core\Container::set('db', $db);
\App\Core\Container::set('prefix', 'gh3sp');
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');

use App\Services\Parsing\Adapters\AutotraderAdapter;

$adapter = new AutotraderAdapter();
$outFile = __DIR__ . '/../App/Services/Parsing/Adapters/autotrader_taxonomy.json';

echo "[" . date('H:i:s') . "] Fetching AutoTrader makes...\n";
$taxonomy = $adapter->buildTaxonomy(function ($done, $total, $make, $models) {
    echo "  [$done/$total] $make — $models models\n";
    if (function_exists('ob_flush')) { @ob_flush(); }
    flush();
});

if (empty($taxonomy['makes'])) {
    echo "FAILED — no makes resolved. Keeping the previous taxonomy.\n";
    exit(1);
}

$models = 0;
foreach ($taxonomy['makes'] as $m) { $models += count($m['models'] ?? []); }

// Shrink guard. A make whose page answers slowly (or 5xx) yields zero models and
// is dropped, so a flaky run would quietly delete brands from the filter
// dropdown. The catalog itself only grows — a real drop of more than 10% means
// the run was bad, not that AutoTrader removed 12 brands overnight.
if (is_file($outFile)) {
    $prev = json_decode((string)file_get_contents($outFile), true);
    $prevMakes = is_array($prev) ? count($prev['makes'] ?? []) : 0;
    $prevModels = 0;
    foreach (($prev['makes'] ?? []) as $m) { $prevModels += count($m['models'] ?? []); }

    if ($prevMakes > 0 && (count($taxonomy['makes']) < $prevMakes * 0.9 || $models < $prevModels * 0.9)) {
        echo "ABORTED — new taxonomy is much smaller than the current one "
           . "(" . count($taxonomy['makes']) . "/$models vs $prevMakes/$prevModels). "
           . "Keeping the previous file; re-run when the site answers reliably.\n";
        exit(1);
    }
}

// Write via a temp file so a crash mid-write can never leave the UI with a
// truncated taxonomy (the filter page reads this file on every load).
$tmp = $outFile . '.tmp';
file_put_contents($tmp, json_encode($taxonomy, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
rename($tmp, $outFile);

echo "[" . date('H:i:s') . "] DONE — " . count($taxonomy['makes']) . " makes, $models models → " . basename($outFile) . "\n";
