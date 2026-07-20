<?php

date_default_timezone_set('Europe/Chisinau');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $path = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($path)) require_once $path;
});

require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');

use App\Core\Container;
use App\Services\Api999Service;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');
$prefx = 'gh3sp';

Container::set('db', $db);
Container::set('prefix', $prefx);

// Landed-cost breakdown helpers (same ones the public page uses).
$pricingFile = $_SERVER['DOCUMENT_ROOT'] . '/content/admin/page/parsing/parsing_pricing.php';
if (is_file($pricingFile)) require_once $pricingFile;
if (!function_exists('parsing_md_breakdown_eu')) {
    exit(1); // pricing helpers not loaded — nothing to do
}

$maxDiff = 1500;

// Server cron only — refuse to run from a browser.
if (php_sapi_name() !== 'cli') { http_response_code(403); die('CLI only'); }

// car_ctlg.fl (sauto fuel code) → internal fuel — same map as the public page.
$fuelMap = [
    'gsl' => 'benzina', 'gmn' => 'benzina', 'gpn' => 'benzina', 'gas' => 'benzina',
    'dsl' => 'diesel',
    'hbd' => 'hybrid', 'pih' => 'hybrid_plugin', 'pid' => 'diesel_hybrid',
    'elc' => 'electric',
];

$now = time();
$rows = $db->query("SELECT pc.car_ctlg_id, pc.source, pc.price_eur,
        cc.fl, cc.vol, cc.yr, cc.prc AS card_prc, cc.cur,
        cc.`999_id`, cc.`999_api_id`, cc.`999`
    FROM {$prefx}_parsing_cars pc
    JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
    WHERE pc.status = 'published' AND pc.car_ctlg_id > 0
      AND pc.source IN ('encar','openlane','ecarstrade','auto1')
      AND pc.price_eur > 0
      AND (cc.prc_t = 0 OR cc.prc_t <= {$now})")->fetchAll(PDO::FETCH_ASSOC);

$updPrc = $db->prepare("UPDATE {$prefx}_car_ctlg SET prc = ? WHERE id = ?");
$upd999 = $db->prepare("UPDATE {$prefx}_car_ctlg SET `999` = ? WHERE id = ?");

foreach ($rows as $r) {
    $bdCar = [
        'price_eur' => (float)$r['price_eur'],
        'fuel'      => $fuelMap[$r['fl'] ?? ''] ?? '',
        'capacity'  => (int)$r['vol'],
        'year'      => (int)$r['yr'],
    ];
    $bd = ($r['source'] === 'encar')
        ? parsing_md_breakdown_kr($db, $prefx, $bdCar)
        : parsing_md_breakdown_eu($db, $prefx, $bdCar);
    if (!$bd || empty($bd['total'])) continue;

    $total = (int)round($bd['total']);
    $card  = (int)round($r['card_prc']);

    // Big gap = price was set manually → leave both prc and 999 untouched.
    if ($maxDiff > 0 && abs($total - $card) > $maxDiff) continue;

    // Phase 1: rewrite the frozen card price when it drifted.
    if ($total !== $card) {
        $updPrc->execute([$total, (int)$r['car_ctlg_id']]);
    }

    // Phase 2: keep 999.md in sync (prc alone never reaches 999). Only when the car
    // is live on 999 and its JSON price differs from the freshly computed total.
    if (empty($r['999_id']) || empty($r['999'])) continue;
    $json = json_decode($r['999'], true);
    if (!is_array($json) || empty($json['features'])) continue;

    $changed = false;
    foreach ($json['features'] as &$f) {
        // The price feature is the one whose unit is a currency.
        $unit = strtolower((string)($f['unit'] ?? ''));
        if (in_array($unit, ['eur', 'usd', 'mdl', 'ron'], true)) {
            if ((string)($f['value'] ?? '') !== (string)$total) {
                $f['value'] = (string)$total;
                $changed = true;
            }
            break;
        }
    }
    unset($f);
    if (!$changed) continue;

    try {
        $api = new Api999Service($r['999_api_id']);
        $res = $api->updateAdvert((int)$r['999_id'], $json['features']);
        if (!empty($res['success']) || (is_array($res) && empty($res['error']))) {
            $upd999->execute([json_encode($json, JSON_UNESCAPED_UNICODE), (int)$r['car_ctlg_id']]);
        }
    } catch (\Throwable $e) {
        // best-effort — skip this car's 999 update on error
    }
    usleep(400000); // ~0.4s between 999 calls — stay under rate limits.
}
