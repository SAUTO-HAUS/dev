<?php

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

use App\Services\Parsing\Adapters\OpenLaneAdapter;

$adapter = new OpenLaneAdapter();

// 1) Global facets → list of makes (+ total count).
echo "[" . date('H:i:s') . "] Fetching make list...\n";
$facets = $adapter->fetchFacets([]);
if (!is_array($facets) || empty($facets['CleanMake'])) {
    echo "FAILED — no CleanMake facet (cookie expired?). Aborting.\n";
    exit(1);
}
$makes = [];
foreach ($facets['CleanMake'] as $f) {
    $name = trim((string)($f['Key'] ?? ''));
    if ($name !== '') $makes[$name] = (int)($f['Value'] ?? 0);
}
echo "  " . count($makes) . " makes.\n\n";

// 2) For each make, pull its cars and collect distinct models.
// INCREMENTAL + RESUMABLE: load any existing taxonomy and SKIP makes already
// populated (models found). OpenLane rate-limits a burst of ~59 quick searches
// (empty results appear mid-run), so we (a) pause 2–4s between makes, (b) retry
// an empty make once after a longer wait, and (c) let you just re-run the script
// to fill in the makes that still came back empty.
$out = __DIR__ . '/../App/Services/Parsing/Adapters/openlane_taxonomy.json';
$taxonomy = ['generated_at' => date('Y-m-d H:i:s'), 'makes' => []];
// Resume from the existing file (skip makes already done) — UNLESS rebuilding
// from scratch. CLI/cron rebuilds (so refreshes catch new/removed models);
// web with ?force=1 also rebuilds. Web without force resumes (avoids re-doing
// work across the multiple runs the batch limit forces).
$rebuild = $IS_CLI || !empty($_GET['force']);
if (!$rebuild && is_file($out)) {
    $prev = json_decode(file_get_contents($out), true);
    if (is_array($prev) && !empty($prev['makes'])) $taxonomy['makes'] = $prev['makes'];
}

$collectModels = function (array $cars): array {
    $models = [];
    foreach ($cars as $c) {
        $m = trim((string)($c['model'] ?? ''));
        if ($m === '') continue;
        $models[$m] = ($models[$m] ?? 0) + 1;
    }
    ksort($models, SORT_NATURAL | SORT_FLAG_CASE);
    $list = [];
    foreach ($models as $name => $cnt) $list[] = ['name' => $name, 'count' => $cnt];
    return $list;
};

// CLI/cron: no browser timeout → process ALL makes in one run (PHP_INT_MAX).
// Web: small BATCH per request to dodge the server timeout (?limit=8 default).
// Either way it's resumable (skips makes already done).
$batch = $IS_CLI ? PHP_INT_MAX : max(1, (int)($_GET['limit'] ?? 8));
$processedThisRun = 0;

$i = 0; $filled = 0; $skipped = 0; $stillEmpty = []; $remaining = 0;
foreach ($makes as $make => $makeCount) {
    $i++;
    // Skip makes we already have models for (resume support).
    if (!empty($taxonomy['makes'][$make]['models'])) {
        $skipped++;
        continue;
    }
    // Batch limit reached — leave the rest for the next run.
    if ($processedThisRun >= $batch) { $remaining++; continue; }
    $processedThisRun++;

    $cars = $adapter->searchByFilter(['brand' => $make, '_max_results' => 5000, '_max_pages' => 100]);
    if (empty($cars) && $makeCount > 0) {
        // Likely rate-limited — wait longer and retry once.
        sleep(6);
        $cars = $adapter->searchByFilter(['brand' => $make, '_max_results' => 5000, '_max_pages' => 100]);
    }
    $modelList = $collectModels($cars);

    if (empty($modelList) && $makeCount > 0) {
        $stillEmpty[] = $make;
        echo sprintf("[%2d/%2d] %-20s ⚠️ 0 (expected %d) — re-run later\n", $i, count($makes), $make, $makeCount);
    } else {
        $taxonomy['makes'][$make] = ['count' => $makeCount, 'models' => $modelList];
        $filled++;
        echo sprintf("[%2d/%2d] %-20s %4d cars, %3d models\n", $i, count($makes), $make, count($cars), count($modelList));
        // Save after each success so a mid-run block never loses progress.
        $taxonomy['generated_at'] = date('Y-m-d H:i:s');
        file_put_contents($out, json_encode($taxonomy, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    sleep(mt_rand(2, 4)); // gentle pacing between makes to avoid rate-limit
}

echo "\n✅ This run: filled {$filled}. Already had: {$skipped}. Total makes in file: " . count($taxonomy['makes']) . "\n";
echo "   Saved to: $out (" . round(filesize($out) / 1024, 1) . " KB)\n";
if ($stillEmpty) {
    echo "\n⚠️ Empty this run (will retry on next run): " . implode(', ', $stillEmpty) . "\n";
}
if ($remaining > 0) {
    echo "\n➡️  {$remaining} makes still TODO. RE-RUN the same URL to continue:\n";
    echo "   /console/openlane_taxonomy_dump.php?token=cron2026\n";
} else {
    echo "\n🎉 ALL makes processed. Taxonomy complete.\n";
}
