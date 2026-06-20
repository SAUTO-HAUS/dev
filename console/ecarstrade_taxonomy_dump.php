<?php
/**
 * Build eCarsTrade taxonomy (brand → distinct models) from real listings.
 * eCarsTrade has no models endpoint, so we page each mark and collect the
 * distinct models from car titles. Saves ecarstrade_taxonomy.json.
 *
 *   CLI:  php console/ecarstrade_taxonomy_dump.php        (all brands)
 *   web:  /console/ecarstrade_taxonomy_dump.php?token=cron2026   (batch of 8)
 * Cron (weekly): 0 5 * * 1 cd /home/sautom/public_html && /usr/local/bin/php \
 *   console/ecarstrade_taxonomy_dump.php >> logs/ecarstrade_taxonomy.log 2>&1
 */

$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && ($_GET['token'] ?? '') !== 'cron2026') { http_response_code(403); die('Forbidden'); }
if (!$IS_CLI) header('Content-Type: text/plain; charset=utf-8');
ignore_user_abort(true); set_time_limit(0);
error_reporting(E_ALL); ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');

chdir(__DIR__);
define('_DOIT', 1); define('_DEFAULT', 'content/default');
spl_autoload_register(function ($c) {
    $p = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $c) . '.php';
    if (file_exists($p)) require_once $p;
});
require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
\App\Core\Container::set('db', $db);
\App\Core\Container::set('prefix', 'gh3sp');
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');

use App\Services\Parsing\Adapters\EcarsTradeAdapter;

$adapter = new EcarsTradeAdapter();

// Brand list (name → id) — mirror the adapter's MARK_IDS via reflection so we
// don't duplicate it. Use only canonical names (skip alias dupes by id).
$ref = new ReflectionClass(EcarsTradeAdapter::class);
$marks = $ref->getConstant('MARK_IDS');
$byId = [];
foreach ($marks as $name => $id) { if (!isset($byId[$id])) $byId[$id] = $name; }
$brands = array_flip($byId); // name → id, deduped

$out = __DIR__ . '/../App/Services/Parsing/Adapters/ecarstrade_taxonomy.json';
$taxonomy = ['generated_at' => date('Y-m-d H:i:s'), 'brands' => []];
$rebuild = $IS_CLI || !empty($_GET['force']);
if (!$rebuild && is_file($out)) {
    $prev = json_decode(file_get_contents($out), true);
    if (is_array($prev) && !empty($prev['brands'])) $taxonomy['brands'] = $prev['brands'];
}

$batch = $IS_CLI ? PHP_INT_MAX : max(1, (int)($_GET['limit'] ?? 8));
$done = 0; $i = 0;
foreach ($brands as $brand => $id) {
    $i++;
    if (!$rebuild && !empty($taxonomy['brands'][$brand])) { continue; } // resume
    if ($done >= $batch) break;

    echo "[" . date('H:i:s') . "] {$brand} (id {$id})... ";
    // ALL cars of this mark (incl. non-buy-now) → complete model list.
    $models = $adapter->collectModelsForBrand($brand);
    $list = [];
    foreach ($models as $name => $cnt) $list[] = ['name' => $name, 'count' => $cnt];
    $taxonomy['brands'][$brand] = ['id' => $id, 'models' => $list];
    echo array_sum($models) . " cars, " . count($list) . " models\n";

    // Incremental save so a web timeout still keeps progress.
    $taxonomy['generated_at'] = date('Y-m-d H:i:s');
    file_put_contents($out, json_encode($taxonomy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $done++;
    usleep(mt_rand(500000, 1000000));
}

$total = count($taxonomy['brands']);
echo "\n[" . date('H:i:s') . "] Saved {$total}/" . count($brands) . " brands → " . basename($out) . "\n";
if ($total < count($brands)) echo "Re-run to continue the rest.\n";
