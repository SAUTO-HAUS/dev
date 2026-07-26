<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * B2B proforma — served standalone (Route: /{lang}/b2b/invoice/{id}?k={access_key}).
 *
 * IDENTICAL to the "Cont de plata" the admin panel issues:
 * content/admin/include/docs/cars/con_plata.php + the print CSS from
 * content/admin/include/docs_print.php. Only the data comes from the B2B
 * invoice (frozen doc_meta), not from an admin form.
 */

use App\Services\B2b\B2bInvoice;
use App\Services\B2b\B2bAuth;

$lang = $_COOKIE['lang'] ?? 'ro';

$invoiceId = isset($t_mp[4]) ? (int)preg_replace('/\D+/', '', (string)$t_mp[4]) : 0;
$accessKey = isset($_GET['k']) ? (string)$_GET['k'] : '';
$invoice   = $invoiceId > 0 ? B2bInvoice::findSigned($invoiceId, $accessKey) : null;

if (!$invoice) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="'.htmlspecialchars($lang).'"><head><meta charset="UTF-8">'
       . '<title>404</title></head><body style="font-family:sans-serif;padding:3rem;text-align:center;">'
       . '<h1>404</h1><p>Documentul nu a fost găsit.</p></body></html>';
    return;
}

$client = B2bAuth::findById((int)$invoice['b2b_user_id']);
$car    = B2bInvoice::carSnapshot($invoice);
$dm     = B2bInvoice::docMeta($invoice);

$esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$currency = strtoupper((string)$invoice['currency']);
$iban = $currency === 'EUR' ? 'MD51VI022512000000094EUR'
      : ($currency === 'USD' ? 'MD51VI022512000000094USD' : 'MD64VI022512000000171MDL');

// Document fields, frozen on doc_meta; fall back to the car snapshot / profile.
$brand = ($dm['br'] ?? '') !== '' ? (string)$dm['br'] : trim((string)($car['br_nm'] ?? $car['br'] ?? ''));
$model = ($dm['mo'] ?? '') !== '' ? (string)$dm['mo'] : trim((string)($car['mo_nm'] ?? $car['mo'] ?? ''));
$vin   = ($dm['vin'] ?? '') !== '' ? (string)$dm['vin'] : (string)($car['vin'] ?? '');
$amount   = (float)$invoice['advance_amount'];
// Same money format as the admin doc: parseCurr() (comma thousands) + ".00".
$sumTxt   = function_exists('parseCurr')
    ? parseCurr($amount) . '.00'
    : ((int)$amount == $amount
        ? number_format($amount, 0, '.', ',') . '.00'
        : number_format($amount, 2, '.', ','));
$docDate  = ($dm['date'] ?? '') !== '' ? (string)$dm['date'] : (string)$invoice['created_at'];
$zdate    = date('d.m.Y', strtotime($docDate));
$invNo    = (string)$invoice['invoice_no'];

$buyerName = ($dm['buyer_name'] ?? '') !== '' ? (string)$dm['buyer_name'] : B2bAuth::displayName($client);
$buyerType = ($dm['buyer_type'] ?? '') === 'jur' ? 'jur' : 'fiz';
$buyerIdno = (string)($dm['buyer_idno'] ?? '');

// "cp" = persoană fizică, "cf" = persoană juridică (as in con_plata.php).
$buyerCode = $buyerType === 'fiz' ? 'cp' : 'cf';

$brandTxt = $brand !== '' ? ucwords(strtolower(str_replace('_', ' ', $brand))) : '';
$modelTxt = $model !== '' ? ucwords(str_replace('_', ' ', $model)) : '';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="<?= $esc($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Cont de plata nr. <?= $esc($invNo) ?></title>
<link rel="stylesheet" type="text/css" href="/content/default/css/default.css">
<style>
    @media print {
        @page { size:auto; size:A4 portrait; margin:0; }
        * { -webkit-print-color-adjust:exact !important; color-adjust:exact !important; print-color-adjust:exact !important; }
        .inv-actions { display:none !important; }
    }

    html, body { min-height:auto !important; height:auto !important; margin:0; padding:0; }
    body { background:#f4f5f7; }

    .inv-actions { max-width:210mm; margin:14px auto 8px; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; padding:0 6px; }
    .inv-btn { display:inline-block; padding:10px 20px; border-radius:8px; font:600 14px/1 Arial,sans-serif; text-decoration:none; cursor:pointer; border:1px solid #e2001a; background:#e2001a; color:#fff; }
    .inv-btn--ghost { background:#fff; color:#e2001a; }

    .base { font-family:"def_l"; color:#000; filter:grayscale(1); -webkit-filter:grayscale(1); }
    .base > .pg { width:210mm; min-height:296mm; margin:0 auto; padding:5mm 10mm; background:#fff; position:relative; box-sizing:border-box; box-shadow:0 8px 30px rgba(20,20,40,.10); }
    .base > .pg.bg { background:#fffc url("/media/images/site/print/bg_pg.webp") repeat center / contain; background-blend-mode:soft-light; }

    .date { text-align:center; padding:15mm 0 0; float:left; }
    .logo { float:right; }
    .ln { border-bottom:1px solid; clear:both; padding:2mm; }
    .cont { width:100%; float:left; padding:5mm 0 0; font-size:0.8rem; margin:0; }
    .ttl { text-align:center; padding:15mm 0 0; clear:both; font-size:1.4rem; font-weight:bold; }

    .flx { display:flex; flex-flow:column wrap; justify-content:space-evenly; }
    .flx > .ws { flex-grow:1; }

    table { width:100%; border-collapse:collapse; margin-top:5mm; }
    tr { border:1px solid; }
    tr > td { text-align:center; padding:3mm 0; }
    tr > td:not(:first-child) { border-left:1px solid; }
    tr > td.id { width:10%; } tr > td.nm { width:50%; } tr > td.prc { width:40%; }

    .txt_up { text-transform:uppercase; }
    .txt_cpt { text-transform:capitalize; }
    .buyer { margin-top:1rem; }

    .sign { margin-top:20mm; }
    .sign > * { width:40%; display:flex; flex-flow:row; justify-content:space-between; align-items:center; }
    .sign > * > .ln { flex-grow:1; line-height:0; text-align:center; position:relative; }
    .sign > .s1 { float:left; position:relative; }
    .sign > .s2 { float:right; position:relative; }

    @media screen and (max-width:767px), screen and (orientation:portrait) and (max-width:900px) {
        .base > .pg { width:100% !important; min-height:auto !important; padding:5mm 5mm !important; }
        .base > .pg.bg { background-size:cover; }
        .cont { font-size:0.72rem; }
        .logo { max-width:90px; }
        .logo img { max-width:100%; height:auto; }
        .ttl { font-size:1.1rem; padding-top:8mm; }
        .sign > * { width:48%; }
        .sign { margin-top:10mm; }
        table { font-size:0.72rem; }
    }
</style>
</head>
<body>

<div class="inv-actions">
    <button type="button" class="inv-btn" onclick="window.print()"><?= $lang === 'ru' ? 'Печать' : ($lang === 'en' ? 'Print' : 'Printează') ?></button>
    <a class="inv-btn inv-btn--ghost" href="/<?= $esc($lang) ?>/b2b/invoices"><?= $lang === 'ru' ? 'В кабинет' : ($lang === 'en' ? 'To cabinet' : 'La cabinet') ?></a>
</div>

<div id="p_cont" class="base">
    <div class="pg bg">
        <div class="date"><?= $esc($zdate) ?></div>
        <img class="logo" src="/media/images/site/v2/logo_b.svg" width="33%" />
        <div class="ln"></div>
        <span class="cont">
            <b>“SAUTO” SRL</b><br/>
            <span>Republica Moldova, MD-2084, mun.Chişinau</span><br/>
            <span>or.Cricova, str.Chisinaului 84, ap.(of.) 39</span><br/>
            <span>IBAN: <b><?= $esc($iban) ?></b></span><br/>
            <span>în B.C.“VICTORIABANK S.A.”, <b>VICBMD2XXXX</b></span><br/>
            <span>c/f <b>1017600006845</b>, c/TVA <b>0609417</b></span>
        </span>
        <div class="cont"></div>
        <div class="ttl">Cont de plata nr. <?= $esc($invNo) ?></div>
        <div class="flx">
            <table>
                <tr>
                    <td class="id">№</td>
                    <td class="nm txt_up">Denumirea marfuri (serviciilor)<br/>Название товара (услуг)</td>
                    <td class="prc">Pret pentru o unitate<br/>Цена за единицу<br/><?= $esc($currency) ?></td>
                </tr>
                <tr>
                    <td class="id">1</td>
                    <td class="nm txt_up"><span>Plata in avans pentru automobilul </span><?= $esc(trim($brandTxt.' '.$modelTxt)) ?><?= $vin !== '' ? '<br/>VIN: '.$esc($vin) : '' ?></td>
                    <td class="prc"><?= $esc($sumTxt) ?></td>
                </tr>
                <tr>
                    <td class="id"></td>
                    <td class="nm txt_up">TOTAL</td>
                    <td class="prc"><?= $esc($sumTxt) ?></td>
                </tr>
            </table>
            <div class="buyer">Platitor: <span class="txt_cpt"><?= $esc(mb_strtolower($buyerName)) ?></span>, <?= $esc($buyerCode) ?> <span class="txt_up"><?= $esc($buyerIdno) ?></span></div>
            <div class="ws"></div>
            <div class="sign">
                <div class="s1">Semnatura<div class="ln"></div></div>
                <div class="s2">L. Ş.<div class="ln"></div></div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
