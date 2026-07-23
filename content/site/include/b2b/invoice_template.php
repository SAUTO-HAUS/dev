<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * B2B proforma - print-ready document served standalone, without the site layout.
 *
 * Route: /{lang}/b2b/invoice/{id}?k={access_key}, intercepted in index.php the
 * same way the telegram pages are.
 *
 * Layout follows the "Cont de plata" document already used by the admin panel
 * (content/admin/include/docs/cars/con_plata.php) and reuses the bank details
 * from content/admin/include/docs_print.php, so a dealer proforma looks the same
 * as one issued from the back office.
 *
 * "Download PDF" uses the browser print dialog (@media print): plugins/mpdf has
 * no dependencies installed.
 */

use App\Services\B2b\B2bInvoice;

$lang = $_COOKIE['lang'] ?? 'ro';

$invoiceId = isset($t_mp[4]) ? (int)preg_replace('/\D+/', '', (string)$t_mp[4]) : 0;
$accessKey = isset($_GET['k']) ? (string)$_GET['k'] : '';

$invoice = $invoiceId > 0 ? B2bInvoice::findSigned($invoiceId, $accessKey) : null;

// A wrong key and a missing id return the same response, so proformas cannot be
// discovered by trial and error.
if (!$invoice) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="'.htmlspecialchars($lang).'"><head><meta charset="UTF-8">'
       . '<title>404</title></head><body style="font-family:sans-serif;padding:3rem;text-align:center;">'
       . '<h1>404</h1><p>Documentul nu a fost găsit.</p></body></html>';
    return;
}

$client = App\Services\B2b\B2bAuth::findById((int)$invoice['b2b_user_id']);
$car    = B2bInvoice::carSnapshot($invoice);

$esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$fmt = fn($n) => number_format((float)$n, 2, '.', ' ');

$currency = (string)$invoice['currency'];

// SAUTO SRL details, identical to docs_print.php. The IBAN depends on the
// document currency, as for documents issued from the admin panel.
$company = [
    'name'    => '“SAUTO” SRL',
    'address' => 'Republica Moldova, MD-2084, mun. Chişinău, or. Cricova, str. Chişinăului 84, ap. (of.) 39',
    'iban'    => $currency === 'EUR' ? 'MD51VI022512000000094EUR' : 'MD64VI022512000000171MDL',
    'bank'    => 'B.C. “VICTORIABANK” S.A., VICBMD2XXXX',
    'cf'      => '1017600006845',
    'tva'     => '0609417',
];

$L = [
    'ro' => [
        'doc'       => 'Cont de plată nr.',
        'date'      => 'Data',
        'supplier'  => 'Furnizor',
        'payer'     => 'Plătitor',
        'nr'        => '№',
        'goods'     => 'Denumirea mărfurilor (serviciilor)',
        'price'     => 'Preț',
        'row'       => 'Plata în avans pentru automobilul',
        'total'     => 'TOTAL',
        'sign'      => 'Semnătura',
        'stamp'     => 'L. Ş.',
        'print'     => 'Descarcă PDF',
        'back'      => 'Înapoi la cabinet',
        'idno'      => 'IDNO',
        'vat'       => 'c/TVA',
        'iban'      => 'IBAN',
        'bank'      => 'Banca',
        'repr'      => 'Reprezentant',
        'note'      => 'Documentul este generat automat și este valabil fără semnătură și ștampilă.',
    ],
    'ru' => [
        'doc'       => 'Счёт на оплату №',
        'date'      => 'Дата',
        'supplier'  => 'Поставщик',
        'payer'     => 'Плательщик',
        'nr'        => '№',
        'goods'     => 'Наименование товаров (услуг)',
        'price'     => 'Цена',
        'row'       => 'Авансовый платёж за автомобиль',
        'total'     => 'ИТОГО',
        'sign'      => 'Подпись',
        'stamp'     => 'М. П.',
        'print'     => 'Скачать PDF',
        'back'      => 'Назад в кабинет',
        'idno'      => 'IDNO',
        'vat'       => 'НДС',
        'iban'      => 'IBAN',
        'bank'      => 'Банк',
        'repr'      => 'Представитель',
        'note'      => 'Документ сформирован автоматически и действителен без подписи и печати.',
    ],
    'en' => [
        'doc'       => 'Payment invoice no.',
        'date'      => 'Date',
        'supplier'  => 'Supplier',
        'payer'     => 'Payer',
        'nr'        => 'No.',
        'goods'     => 'Description of goods (services)',
        'price'     => 'Price',
        'row'       => 'Advance payment for the vehicle',
        'total'     => 'TOTAL',
        'sign'      => 'Signature',
        'stamp'     => 'Stamp',
        'print'     => 'Download PDF',
        'back'      => 'Back to cabinet',
        'idno'      => 'IDNO',
        'vat'       => 'VAT code',
        'iban'      => 'IBAN',
        'bank'      => 'Bank',
        'repr'      => 'Representative',
        'note'      => 'This document is generated automatically and is valid without signature or stamp.',
    ],
];
$t = $L[$lang] ?? $L['ro'];

$carLabel = trim((string)($car['title'] ?? ''));
if ($carLabel === '') {
    $carLabel = '#' . (int)$invoice['car_id'];
}
$vin = trim((string)($car['vin'] ?? ''));

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="<?= $esc($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $esc($t['doc'].' '.$invoice['invoice_no']) ?></title>
<style>
    :root { --ink:#191919; --muted:#6b6b6b; --line:#e2e2e2; --brand:#e2001a; }
    *{ box-sizing:border-box; }
    body{
        margin:0; padding:24px 16px;
        background:#f4f5f7; color:var(--ink);
        font-family:"Helvetica Neue",Arial,sans-serif; font-size:14px; line-height:1.5;
    }
    .inv-actions{
        max-width:820px; margin:0 auto 16px;
        display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;
    }
    .inv-btn{
        display:inline-block; padding:10px 20px; border-radius:8px;
        font-size:14px; font-weight:600; text-decoration:none; cursor:pointer;
        border:1px solid var(--brand); background:var(--brand); color:#fff;
    }
    .inv-btn--ghost{ background:#fff; color:var(--brand); }
    .sheet{
        max-width:820px; margin:0 auto; padding:40px;
        background:#fff; border-radius:6px;
        box-shadow:0 8px 30px rgba(20,20,40,.08);
    }
    .inv-top{ display:flex; justify-content:space-between; align-items:flex-start; gap:24px; }
    .inv-logo{ width:190px; height:auto; }
    .inv-date{ color:var(--muted); font-size:13px; text-align:right; }
    .inv-ttl{
        margin:28px 0 24px; padding-bottom:12px;
        border-bottom:2px solid var(--brand);
        font-size:20px; font-weight:700; text-align:center; text-transform:uppercase;
    }
    .parties{ display:flex; gap:32px; flex-wrap:wrap; }
    .party{ flex:1 1 260px; min-width:240px; }
    .party h3{
        margin:0 0 8px; font-size:12px; font-weight:700;
        text-transform:uppercase; letter-spacing:.6px; color:var(--muted);
    }
    .party .nm{ font-weight:700; font-size:15px; margin-bottom:4px; }
    .party p{ margin:2px 0; font-size:13px; }
    .party .lbl{ color:var(--muted); }
    table.items{ width:100%; border-collapse:collapse; margin:28px 0 0; }
    table.items th, table.items td{
        padding:12px 10px; border-bottom:1px solid var(--line); text-align:left; vertical-align:top;
    }
    table.items th{
        font-size:11px; text-transform:uppercase; letter-spacing:.5px;
        color:var(--muted); border-bottom:2px solid var(--line);
    }
    table.items .num{ width:44px; }
    table.items .amt{ text-align:right; white-space:nowrap; }
    table.items tr.total td{
        border-bottom:none; border-top:2px solid var(--brand);
        font-size:17px; font-weight:700; padding-top:16px;
    }
    table.items tr.total .amt{ color:var(--brand); }
    .vin{ display:block; margin-top:4px; font-size:12px; color:var(--muted); }
    .signs{ display:flex; gap:48px; margin-top:56px; }
    .signs div{ flex:1; font-size:13px; color:var(--muted); }
    .signs .ln{ margin-top:34px; border-bottom:1px solid var(--ink); }
    .note{ margin-top:32px; font-size:11px; color:var(--muted); text-align:center; }

    @media print{
        body{ background:#fff; padding:0; }
        .inv-actions{ display:none; }
        .sheet{ max-width:none; margin:0; padding:0; box-shadow:none; border-radius:0; }
        @page{ size:A4; margin:18mm 16mm; }
    }
    @media (max-width:640px){
        body{ padding:12px 8px; }
        .sheet{ padding:22px 18px; }
        .inv-top{ flex-direction:column; }
        .inv-date{ text-align:left; }
        .signs{ flex-direction:column; gap:24px; }
    }
</style>
</head>
<body>

<div class="inv-actions">
    <button type="button" class="inv-btn" onclick="window.print()"><?= $esc($t['print']) ?></button>
    <a class="inv-btn inv-btn--ghost" href="/<?= $esc($lang) ?>/b2b/invoices"><?= $esc($t['back']) ?></a>
</div>

<div class="sheet">
    <div class="inv-top">
        <img class="inv-logo" src="/media/images/site/v2/logo_b.svg" alt="Sauto" />
        <div class="inv-date">
            <?= $esc($t['date']) ?>: <strong><?= $esc(date('d.m.Y', strtotime((string)$invoice['created_at']))) ?></strong>
        </div>
    </div>

    <h1 class="inv-ttl"><?= $esc($t['doc'].' '.$invoice['invoice_no']) ?></h1>

    <div class="parties">
        <div class="party">
            <h3><?= $esc($t['supplier']) ?></h3>
            <div class="nm"><?= $esc($company['name']) ?></div>
            <p><?= $esc($company['address']) ?></p>
            <p><span class="lbl"><?= $esc($t['iban']) ?>:</span> <strong><?= $esc($company['iban']) ?></strong></p>
            <p><span class="lbl"><?= $esc($t['bank']) ?>:</span> <?= $esc($company['bank']) ?></p>
            <p><span class="lbl"><?= $esc($t['idno']) ?>:</span> <?= $esc($company['cf']) ?>
               &nbsp; <span class="lbl"><?= $esc($t['vat']) ?>:</span> <?= $esc($company['tva']) ?></p>
        </div>

        <div class="party">
            <h3><?= $esc($t['payer']) ?></h3>
            <!-- Signup collects only the person's name; company details are added
                 from the admin panel and are shown only once they exist. -->
            <div class="nm"><?= $esc(\App\Services\B2b\B2bAuth::displayName($client)) ?></div>
            <?php if (!empty($client['vat_code'])): ?>
                <p><span class="lbl"><?= $esc($t['vat']) ?>:</span> <?= $esc($client['vat_code']) ?></p>
            <?php endif; ?>
            <?php if (!empty($client['legal_address'])): ?>
                <p><?= $esc($client['legal_address']) ?></p>
            <?php endif; ?>
            <?php if (!empty($client['bank_iban'])): ?>
                <p><span class="lbl"><?= $esc($t['iban']) ?>:</span> <?= $esc($client['bank_iban']) ?></p>
            <?php endif; ?>
            <?php if (!empty($client['bank_name'])): ?>
                <p><span class="lbl"><?= $esc($t['bank']) ?>:</span> <?= $esc($client['bank_name']) ?></p>
            <?php endif; ?>
            <?php if (!empty($client['company_name'])): ?>
                <p><span class="lbl"><?= $esc($t['repr']) ?>:</span> <?= $esc($client['full_name'] ?? '') ?></p>
            <?php endif; ?>
            <p><?= $esc($client['email'] ?? '') ?> &middot; <?= $esc($client['phone_number'] ?? '') ?></p>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th class="num"><?= $esc($t['nr']) ?></th>
                <th><?= $esc($t['goods']) ?></th>
                <th class="amt"><?= $esc($t['price']) ?>, <?= $esc($currency) ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="num">1</td>
                <td>
                    <?= $esc($t['row']) ?> <strong><?= $esc($carLabel) ?></strong>
                    <?php if ($vin !== ''): ?><span class="vin">VIN: <?= $esc($vin) ?></span><?php endif; ?>
                </td>
                <td class="amt"><?= $esc($fmt($invoice['advance_amount'])) ?></td>
            </tr>
            <tr class="total">
                <td></td>
                <td><?= $esc($t['total']) ?></td>
                <td class="amt"><?= $esc($fmt($invoice['advance_amount'])) ?> <?= $esc($currency) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="signs">
        <div><?= $esc($t['sign']) ?><div class="ln"></div></div>
        <div><?= $esc($t['stamp']) ?><div class="ln"></div></div>
    </div>

    <p class="note"><?= $esc($t['note']) ?></p>
</div>

</body>
</html>
