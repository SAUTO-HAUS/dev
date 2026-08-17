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

// Cron runs it from CLI. From a browser it needs the token, so it can also be
// triggered by hand — there is no shell on the server, and this is the only job
// that pushes corrected prices to 999.md.
//   /console/resync_prices_daily.php?token=cron2026
$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && ($_GET['token'] ?? '') !== 'cron2026') {
    http_response_code(403);
    die('Forbidden');
}
// Each 999 push is paced at ~0.4s, so a full catalog outruns the WEB SERVER's
// connection timeout — which set_time_limit() cannot raise. From a browser the
// 999 phase therefore runs in batches: card prices are always fixed in full
// (pure DB, fast), and only $max999 ads are pushed per request. No offset to
// track — a pushed ad no longer differs, so the next batch simply picks up the
// remaining ones. The page reloads itself until nothing is left.
$max999 = PHP_INT_MAX;
if (!$IS_CLI) {
    header('Content-Type: text/html; charset=utf-8');
    ignore_user_abort(true);
    set_time_limit(0);
    $max999 = max(1, (int)($_GET['limit999'] ?? 25));
    echo '<pre style="font:13px/1.5 monospace">';
}

// Single-instance lock, same idea as the availability cron: draining the 999
// queue can take minutes, so a tighter schedule (or a manual browser run on top
// of the cron) could otherwise push the same ad twice at once.
$lockFile = sys_get_temp_dir() . '/resync_prices_daily.lock';
$lockHandle = fopen($lockFile, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo '['.date('Y-m-d H:i:s')."] Alta rulare este in curs — ies\n";
    if (!$IS_CLI) echo '</pre>';
    exit(0);
}

// car_ctlg.fl (sauto fuel code) → internal fuel — same map as the public page.
$fuelMap = [
    'gsl' => 'benzina', 'gmn' => 'benzina', 'gpn' => 'benzina', 'gas' => 'benzina',
    'dsl' => 'diesel',
    'hbd' => 'hybrid', 'pih' => 'hybrid_plugin', 'pid' => 'diesel_hybrid',
    'elc' => 'electric',
];

$now = time();
// Walked in chunks, keyed on cc.id: the row carries the car's whole 999 JSON
// payload, so loading all ~30k of them at once would be tens of megabytes and
// only grows with the catalog. Keyset paging (id > last) instead of OFFSET —
// stable, and our updates never change the ordering key.
$CHUNK = 2000;
$fetchChunk = $db->prepare("SELECT pc.car_ctlg_id, pc.source, pc.price_eur,
        cc.fl, cc.vol, cc.yr, cc.prc AS card_prc, cc.cur,
        cc.br, cc.mo, cc.catalog_type,
        cc.`999_id`, cc.`999_api_id`, cc.`999`
    FROM {$prefx}_parsing_cars pc
    JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
    WHERE pc.status = 'published' AND pc.car_ctlg_id > 0
      AND pc.source IN ('encar','openlane','ecarstrade','auto1','autotrader')
      AND pc.price_eur > 0
      AND (cc.prc_t = 0 OR cc.prc_t <= {$now})
      AND cc.id > :last
    ORDER BY cc.id
    LIMIT {$CHUNK}");

$updPrc = $db->prepare("UPDATE {$prefx}_car_ctlg SET prc = ? WHERE id = ?");
$upd999 = $db->prepare("UPDATE {$prefx}_car_ctlg SET `999` = ? WHERE id = ?");

// Counters for the summary — a silent run tells the operator nothing about
// whether it actually did anything.
$statChecked = 0; $statCardUpdated = 0; $statSkippedBig = 0; $stat999 = 0;
$statPending999 = 0;   // still out of sync on 999 when the batch cap is reached

$lastId = 0;
while (true) {
    $fetchChunk->execute([':last' => $lastId]);
    $rows = $fetchChunk->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) break;

foreach ($rows as $r) {
    $lastId = (int)$r['car_ctlg_id'];
    $statChecked++;
    $bdCar = [
        'price_eur' => (float)$r['price_eur'],
        'fuel'      => $fuelMap[$r['fl'] ?? ''] ?? '',
        'capacity'  => (int)$r['vol'],
        'year'      => (int)$r['yr'],
    ];
    $bd = parsing_md_breakdown_for($db, $prefx, $r['source'] ?? '', $bdCar);
    if (!$bd || empty($bd['total'])) continue;

    $total = (int)round($bd['total']);
    $card  = (int)round($r['card_prc']);

    // Big gap = price was set manually → leave both prc and 999 untouched.
    if ($maxDiff > 0 && abs($total - $card) > $maxDiff) { $statSkippedBig++; continue; }

    // Phase 1: rewrite the frozen card price when it drifted.
    if ($total !== $card) {
        $updPrc->execute([$total, (int)$r['car_ctlg_id']]);
        $statCardUpdated++;
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

    // Batch cap reached (browser run): count what is left and keep going through
    // the list — the card prices below still get fixed.
    if ($stat999 >= $max999) { $statPending999++; continue; }

    // The stored payload of an auto-published car has no sauto links (the publish cron
    // adds them in memory) — putting them back here keeps the price push from wiping
    // the description block off the live ad.
    $json['features'] = \App\Helper\Ad999Links::apply($db, $prefx, [
        'id'           => (int)$r['car_ctlg_id'],
        'br'           => $r['br'],
        'mo'           => $r['mo'],
        'catalog_type' => $r['catalog_type'],
    ], $json['features']);

    try {
        $api = new Api999Service($r['999_api_id']);
        $res = $api->updateAdvert((int)$r['999_id'], $json['features']);
        if (!empty($res['success']) || (is_array($res) && empty($res['error']))) {
            $upd999->execute([json_encode($json, JSON_UNESCAPED_UNICODE), (int)$r['car_ctlg_id']]);
            $stat999++;
        }
    } catch (\Throwable $e) {
        // best-effort — skip this car's 999 update on error
    }
    usleep(400000); // ~0.4s between 999 calls — stay under rate limits.
}

} // end chunk loop

echo '['.date('Y-m-d H:i:s')."] Resync preturi (toleranta {$maxDiff} EUR)\n";
echo "  verificate      : {$statChecked}\n";
echo "  pret card scris : {$statCardUpdated}\n";
echo "  999.md impins   : {$stat999}\n";
echo "  sarite (diferenta peste toleranta, pret manual): {$statSkippedBig}\n";

if (!$IS_CLI && $statPending999 > 0) {
    // Card prices are already done; only 999 pushes remain. Reload to continue
    // with the next batch instead of holding one long request open.
    $next = '?token='.rawurlencode($_GET['token'] ?? '').'&limit999='.$max999;
    echo "\n  999.md ramase   : {$statPending999} — continui automat in 3 secunde...\n";
    echo '</pre><meta http-equiv="refresh" content="3;url='.htmlspecialchars($next, ENT_QUOTES).'">';
} elseif (!$IS_CLI) {
    echo "\n  GATA — nimic de continuat.\n</pre>";
}
