<?php
/**
 * Resync published parsing cars' card price (car_ctlg.prc) to the live "MD total"
 * breakdown — aligns the existing ads, and can be re-run manually anytime. The
 * same alignment runs automatically after the pricing config is saved
 * (parsing_resync_card_prices in the parsing ajax handler).
 *
 *   preview : https://www.sauto.md/console/check_price_mismatch.php?token=cron2026
 *   apply   : ...&apply=1
 *   tune    : &maxdiff=500   (skip manual overrides bigger than this; 0 = sync all)
 */
$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && (($_GET['token'] ?? '') !== 'cron2026')) { http_response_code(403); die('Forbidden'); }

define('_DOIT', 1);
define('_DEFAULT', 'content/default');
chdir(__DIR__);
require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/defines.php');
require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');
$prefx = 'gh3sp';
// Only the breakdown helpers — NOT the full ajax handler (which runs access
// checks with relative paths that don't resolve in CLI).
require_once $_SERVER['DOCUMENT_ROOT'] . '/content/admin/page/parsing/parsing_pricing.php';
header('Content-Type: text/plain; charset=utf-8');

$maxDiff = (int)($_GET['maxdiff'] ?? 500);
$apply   = (($_GET['apply'] ?? '') === '1');
$listBig = (($_GET['listbig'] ?? '') === '1');

// fuel/vol/year come from car_ctlg (the AD) — same as the public price table.
$rows = $db->query("SELECT pc.car_ctlg_id, pc.source, pc.price_eur,
        cc.fl, cc.vol, cc.prc card_prc, cc.br_nm, cc.mo_nm, cc.yr
    FROM {$prefx}_parsing_cars pc JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
    WHERE pc.status='published' AND pc.car_ctlg_id>0
      AND pc.source IN ('encar','openlane','ecarstrade','auto1') AND pc.price_eur>0")->fetchAll(PDO::FETCH_ASSOC);

$fuelMap = ['gsl'=>'benzina','gmn'=>'benzina','gpn'=>'benzina','gas'=>'benzina','dsl'=>'diesel',
            'hbd'=>'hybrid','pih'=>'hybrid_plugin','pid'=>'diesel_hybrid','elc'=>'electric'];

$upd = $db->prepare("UPDATE {$prefx}_car_ctlg SET prc = ? WHERE id = ?");
$checked = 0; $updated = 0; $skipBig = 0; $big = [];

foreach ($rows as $r) {
    $checked++;
    $bdCar = ['price_eur' => (float)$r['price_eur'], 'fuel' => $fuelMap[$r['fl'] ?? ''] ?? '',
              'capacity' => (int)$r['vol'], 'year' => (int)$r['yr']];
    $bd = ($r['source'] === 'encar')
        ? parsing_md_breakdown_kr($db, $prefx, $bdCar)
        : parsing_md_breakdown_eu($db, $prefx, $bdCar);
    if (!$bd || empty($bd['total'])) continue;

    $total = (int)round($bd['total']);
    $card  = (int)round($r['card_prc']);
    if ($total === $card) continue;
    if ($maxDiff > 0 && abs($total - $card) > $maxDiff) {
        $skipBig++;
        $big[] = "  ad {$r['car_ctlg_id']}  {$r['br_nm']} {$r['mo_nm']} {$r['yr']}  "
               . "card={$card}€  total={$total}€  diff=".($total - $card)."€";
        continue;
    }

    if ($apply) $upd->execute([$total, (int)$r['car_ctlg_id']]);
    $updated++;
}

if ($listBig && $big) {
    echo "=== Big-diff ads (manual prices, skipped) ===\n";
    echo implode("\n", $big)."\n\n";
}

echo ($apply ? "APPLIED" : "PREVIEW")." (maxdiff={$maxDiff}€)\n";
echo "  checked: {$checked}\n";
echo "  ".($apply ? "updated" : "would update").": {$updated}\n";
echo "  skipped (manual / big diff): {$skipBig}\n";
if (!$apply) echo "\nAdd &apply=1 to write the changes.\n";
echo "Done.\n";
