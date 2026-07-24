<?php defined('_DOIT') or die('Restricted access');

/**
 * B2B pricing tables (Faza B). Same four tables as the retail parsing settings,
 * but the gh3sp_b2b_* set, shown only to logged-in partners. Korea markup,
 * customs and the EUR rate are NOT here — B2B reads those from retail.
 *
 * Saved through fn=save_pricing in ajax/b2b/ajax.php (gordon only).
 */

use App\Services\B2b\B2bConfig;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

// Reuse the parsing tier/param labels instead of duplicating ~12 x 3 strings.
require_once _ADM_PAGE.'/parsing/parsing_lang.php';
$pt = $parsing_lang;

$pfx = B2bConfig::prefix();

$load = function (string $sql) use ($db): array {
    try { return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: []; }
    catch (Throwable $e) { return []; }
};

$commission = $load('SELECT * FROM '.$pfx.'_b2b_commission_tiers ORDER BY sort_order, price_from');
$delivery   = $load('SELECT * FROM '.$pfx.'_b2b_eu_tiers ORDER BY sort_order, price_from');
$euParams   = $load('SELECT * FROM '.$pfx.'_b2b_eu_params ORDER BY sort_order, id');
$krParams   = $load('SELECT * FROM '.$pfx.'_b2b_kr_params ORDER BY sort_order, id');

$notMigrated = (!$commission && !$delivery && !$euParams && !$krParams);

// Retail values, shown read-only beside the B2B ones so the admin sees what the
// non-logged visitor pays without opening /adminsauto/parsing/settings.
$rCommission = $load('SELECT * FROM '.$pfx.'_parsing_commission_tiers ORDER BY sort_order, price_from');
$rDelivery   = $load('SELECT * FROM '.$pfx.'_parsing_eu_tiers ORDER BY sort_order, price_from');
$rEuMap = $rKrMap = [];
foreach ($load('SELECT * FROM '.$pfx.'_parsing_eu_params') as $p) { $rEuMap[(string)$p['param_key']] = $p; }
foreach ($load('SELECT * FROM '.$pfx.'_parsing_kr_params') as $p) { $rKrMap[(string)$p['param_key']] = $p; }

// Retail value for the price band that contains $priceFrom (read-only reference).
$retailTierVal = function (array $retailRows, float $priceFrom, string $field): ?int {
    foreach ($retailRows as $r) {
        $from = (float)$r['price_from'];
        $to   = ($r['price_to'] === null || $r['price_to'] === '') ? INF : (float)$r['price_to'];
        if ($priceFrom >= $from && $priceFrom <= $to) {
            return (int)round((float)$r[$field]);
        }
    }
    return null;
};

/** Renders a tier table (price_from / price_to / B2B value / public value). */
$tierRows = function (array $rows, string $valueField, array $retailRows) use ($pt, $retailTierVal) {
    $out = '';
    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $to = ($r['price_to'] === null || $r['price_to'] === '') ? '' : (int)$r['price_to'];

        // Public price for the same band (matched by the row's starting price).
        $pub = $retailTierVal($retailRows, (float)$r['price_from'], $valueField);
        $pubCell = $pub === null ? '&mdash;' : $pub.' &euro;';

        $out .= '<tr data-row data-id="'.$id.'">'
              . '<td><input type="number" min="0" step="1" class="b2bp-from" value="'.(int)$r['price_from'].'"></td>'
              . '<td><input type="number" min="0" step="1" class="b2bp-to" value="'.$to.'" placeholder="'.b2b_adm_esc($pt['eu_price_to_unlimited'] ?? '').'"></td>'
              . '<td><input type="number" min="0" step="1" class="b2bp-val" value="'.(int)$r[$valueField].'"></td>'
              . '<td class="b2bp-ref">'.$pubCell.'</td>'
              . '<td><button type="button" class="b2bp-del" title="'.b2b_adm_esc($pt['tier_remove'] ?? '').'">&times;</button></td>'
              . '</tr>';
    }
    return $out;
};

/** Renders a param table (label / enabled / B2B amount / retail reference). */
$paramRows = function (array $rows, string $labelPfx, array $retailMap) use ($pt, $t) {
    $out = '';
    foreach ($rows as $r) {
        $id    = (int)$r['id'];
        $key   = (string)$r['param_key'];
        $label = $pt[$labelPfx.$key] ?? ucfirst(str_replace('_', ' ', $key));
        $on    = (int)$r['enabled'] === 1 ? ' checked' : '';
        $amt   = (string)(int)round((float)$r['amount_eur']);

        // Retail reference for this exact param (matched by key).
        $ref = '&mdash;';
        if (isset($retailMap[$key])) {
            $rp  = $retailMap[$key];
            $ref = (string)(int)round((float)$rp['amount_eur']).' &euro;';
            if ((int)$rp['enabled'] !== 1) { $ref .= ' <span class="b2bp-off">'.b2b_adm_esc($t['pricing_off']).'</span>'; }
        }

        $out  .= '<tr data-row data-id="'.$id.'">'
              . '<td class="b2bp-label">'.b2b_adm_esc($label).'</td>'
              . '<td class="b2bp-c"><input type="checkbox" class="b2bp-en"'.$on.'></td>'
              . '<td><input type="number" min="0" step="1" class="b2bp-val" value="'.$amt.'"> <span class="b2bp-u">&euro;</span></td>'
              . '<td class="b2bp-ref">'.$ref.'</td>'
              . '</tr>';
    }
    return $out;
};

?>

<div class="b2ba" id="b2ba-pricing" data-saved-msg="<?= b2b_adm_esc($t['saved']) ?>">
    <div class="b2ba-head">
        <h1 class="b2ba-h1"><?= b2b_adm_esc($t['pricing_title']) ?></h1>
    </div>

    <div class="b2ba-msg" id="b2ba-msg" role="status" aria-live="polite"></div>

    <?php if ($notMigrated): ?>
        <div class="b2ba-warn"><?= b2b_adm_esc($t['pricing_not_migrated']) ?></div>
    <?php endif; ?>

    <p class="b2ba-hint"><?= b2b_adm_esc($t['pricing_hint']) ?></p>

    <!-- Commission tiers (Europe + Korea) -->
    <div class="b2ba-card b2bp-card" data-section="commission" data-value="commission">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['pricing_commission']) ?></h2>
        <table class="b2bp-table">
            <thead><tr>
                <th><?= b2b_adm_esc($pt['eu_col_price_from']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_price_to']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_commission']) ?></th>
                <th class="b2bp-ref"><?= b2b_adm_esc($t['pricing_public']) ?></th>
                <th></th>
            </tr></thead>
            <tbody><?= $tierRows($commission, 'commission', $rCommission) ?></tbody>
        </table>
        <div class="b2ba-card__foot">
            <button type="button" class="b2ba-btn b2ba-btn--ghost" data-b2b-tier-add><?= b2b_adm_esc($pt['tier_add'] ?? '+') ?></button>
            <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-pricing-save><?= b2b_adm_esc($t['save']) ?></button>
        </div>
    </div>

    <!-- Europe delivery tiers -->
    <div class="b2ba-card b2bp-card" data-section="delivery" data-value="delivery">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['pricing_delivery']) ?></h2>
        <table class="b2bp-table">
            <thead><tr>
                <th><?= b2b_adm_esc($pt['eu_col_price_from']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_price_to']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_delivery']) ?></th>
                <th class="b2bp-ref"><?= b2b_adm_esc($t['pricing_public']) ?></th>
                <th></th>
            </tr></thead>
            <tbody><?= $tierRows($delivery, 'delivery', $rDelivery) ?></tbody>
        </table>
        <div class="b2ba-card__foot">
            <button type="button" class="b2ba-btn b2ba-btn--ghost" data-b2b-tier-add><?= b2b_adm_esc($pt['tier_add'] ?? '+') ?></button>
            <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-pricing-save><?= b2b_adm_esc($t['save']) ?></button>
        </div>
    </div>

    <!-- Europe fixed costs -->
    <div class="b2ba-card b2bp-card" data-section="eu_params">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['pricing_eu_params']) ?></h2>
        <table class="b2bp-table b2bp-params">
            <thead><tr>
                <th><?= b2b_adm_esc($pt['eu_col_param']) ?></th>
                <th class="b2bp-c"><?= b2b_adm_esc($pt['eu_col_enabled']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_amount']) ?></th>
                <th class="b2bp-ref"><?= b2b_adm_esc($t['pricing_public']) ?></th>
            </tr></thead>
            <tbody><?= $paramRows($euParams, 'eu_param_', $rEuMap) ?></tbody>
        </table>
        <div class="b2ba-card__foot">
            <span></span>
            <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-pricing-save><?= b2b_adm_esc($t['save']) ?></button>
        </div>
    </div>

    <!-- Korea fixed costs -->
    <div class="b2ba-card b2bp-card" data-section="kr_params">
        <h2 class="b2ba-h2"><?= b2b_adm_esc($t['pricing_kr_params']) ?></h2>
        <table class="b2bp-table b2bp-params">
            <thead><tr>
                <th><?= b2b_adm_esc($pt['eu_col_param']) ?></th>
                <th class="b2bp-c"><?= b2b_adm_esc($pt['eu_col_enabled']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_amount']) ?></th>
                <th class="b2bp-ref"><?= b2b_adm_esc($t['pricing_public']) ?></th>
            </tr></thead>
            <tbody><?= $paramRows($krParams, 'kr_param_', $rKrMap) ?></tbody>
        </table>
        <div class="b2ba-card__foot">
            <span></span>
            <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-pricing-save><?= b2b_adm_esc($t['save']) ?></button>
        </div>
    </div>
</div>

<script src="/content/admin/include/b2b/b2b_pricing_admin.js?v=<?= file_exists(_ROOT.'/content/admin/include/b2b/b2b_pricing_admin.js') ? date('YmdHis', filemtime(_ROOT.'/content/admin/include/b2b/b2b_pricing_admin.js')) : '1' ?>" defer></script>
