<?php
/**
 * One-off maintenance: re-translate Korean brand/model names that were stored
 * before the taxonomy lookup handled stray spaces / numeric keys.
 *
 * Run:  php console/parsing_retranslate_models.php
 * Dry run (no DB writes):  php console/parsing_retranslate_models.php --dry
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $p = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($p)) require_once $p;
});

require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');

use App\Core\Container;
use App\Services\Parsing\Adapters\EncarAdapter;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');
$prefx = 'gh3sp';
Container::set('db', $db);
Container::set('prefix', $prefx);

$dry = in_array('--dry', $argv ?? [], true);

// Korean Hangul range — used to find rows still holding untranslated names.
$hangul = '/[\x{AC00}-\x{D7A3}\x{1100}-\x{11FF}\x{3130}-\x{318F}]/u';

$adapter = new EncarAdapter();

// We need the raw Korean keys to look up the taxonomy. raw_data holds them.
$stmt = $db->query("SELECT id, brand, model, raw_data FROM {$prefx}_parsing_cars WHERE source = 'encar'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "[" . date('Y-m-d H:i:s') . "] Scanning " . count($rows) . " Encar cars" . ($dry ? " (DRY RUN)" : "") . "\n";

$upd = $db->prepare("UPDATE {$prefx}_parsing_cars SET brand = :br, model = :mo WHERE id = :id");
$fixed = 0;

foreach ($rows as $r) {
    $brandKr = preg_match($hangul, (string)$r['brand']) ? $r['brand'] : null;
    $modelKr = preg_match($hangul, (string)$r['model']) ? $r['model'] : null;
    if (!$brandKr && !$modelKr) continue; // already latin — skip

    // Pull the original Korean keys from raw_data when available.
    $raw = json_decode($r['raw_data'] ?? '{}', true) ?: [];
    $rawData = $raw['raw_data'] ?? $raw;
    $cat = $rawData['category'] ?? [];
    $krBrand = $cat['manufacturerName'] ?? $brandKr ?? $r['brand'];
    $krModel = $cat['modelGroupName']   ?? $modelKr ?? $r['model'];

    [$engBrand, $engModel] = $adapter->translateBrandModelPublic($krBrand, $krModel);

    // Only update if we actually got a non-Korean improvement.
    $newBrand = (!preg_match($hangul, $engBrand) && $engBrand !== '') ? $engBrand : $r['brand'];
    $newModel = (!preg_match($hangul, $engModel) && $engModel !== '') ? $engModel : $r['model'];

    if ($newBrand === $r['brand'] && $newModel === $r['model']) continue;

    echo "  #{$r['id']}: [{$r['brand']} / {$r['model']}] -> [{$newBrand} / {$newModel}]\n";
    if (!$dry) {
        $upd->execute([':br' => $newBrand, ':mo' => $newModel, ':id' => $r['id']]);
    }
    $fixed++;
}

echo "[" . date('Y-m-d H:i:s') . "] " . ($dry ? "Would fix" : "Fixed") . " {$fixed} cars\n";
