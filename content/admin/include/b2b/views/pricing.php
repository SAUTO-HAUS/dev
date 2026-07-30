<?php defined('_DOIT') or die('Restricted access');

/**
 * B2B pricing tables (Faza B). Same four tables as the retail parsing settings,
 * but the gh3sp_b2b_* set, shown only to logged-in partners. Korea markup,
 * customs and the EUR rate are NOT here — B2B reads those from retail.
 *
 * Saved through fn=save_pricing in ajax/b2b/ajax.php (gordon only).
 */

use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

// Reuse the parsing tier/param labels instead of duplicating ~12 x 3 strings.
require_once _ADM_PAGE.'/parsing/parsing_lang.php';
$pt = $parsing_lang;

$pfx = B2bConfig::prefix();

// Two use contexts:
//   * standalone page  -> /adminsauto/b2b/pricing (global) or ?user=X (per client);
//   * embedded         -> included inside the client page "Prețuri" tab, which sets
//                         $pricingEmbedded = true and $pricingUserId = the client id.
// Embedded mode drops the own header / message box and reuses the client page's.
$pricingEmbedded = $pricingEmbedded ?? false;
$pricingUserId   = $pricingEmbedded
    ? (int)($pricingUserId ?? 0)
    : (isset($_GET['user']) ? (int)$_GET['user'] : 0);

$pricingClient = null;
if ($pricingUserId > 0) {
    try {
        $stmt = $db->prepare('SELECT id, login, full_name, company_name, person_type, email, status
                                FROM '.B2bConfig::table('users').' WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $pricingUserId]);
        $pricingClient = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $pricingClient = null;
    }
    if (!$pricingClient) {
        echo $pricingEmbedded ? '<div class="b2ba-empty">404</div>' : '<div class="b2ba"><div class="b2ba-empty">404</div></div>';
        return;
    }
}
$perUser = ($pricingClient !== null);

$load = function (string $sql) use ($db): array {
    try { return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: []; }
    catch (Throwable $e) { return []; }
};

// A B2B table in the current scope. Per-user: the client's own rows if any (then
// it is "custom"), otherwise the global rows as an editable starting point.
$loadScoped = function (string $table, string $orderBy) use ($db, $pfx, $perUser, $pricingUserId): array {
    $t = $pfx.'_'.$table;
    if ($perUser) {
        try {
            $own = $db->prepare('SELECT * FROM '.$t.' WHERE b2b_user_id = :uid ORDER BY '.$orderBy);
            $own->execute([':uid' => $pricingUserId]);
            $rows = $own->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $rows = [];
        }
        if ($rows) {
            return [$rows, true];  // custom: the client has its own table
        }
    }
    try {
        $rows = $db->query('SELECT * FROM '.$t.' WHERE b2b_user_id IS NULL ORDER BY '.$orderBy)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $rows = [];
    }
    return [$rows, false];
};

[$commission, $commissionCustom] = $loadScoped('b2b_commission_tiers', 'sort_order, price_from');
[$delivery,   $deliveryCustom]   = $loadScoped('b2b_eu_tiers',         'sort_order, price_from');
[$euParams,   $euParamsCustom]   = $loadScoped('b2b_eu_params',        'sort_order, id');
[$krParams,   $krParamsCustom]   = $loadScoped('b2b_kr_params',        'sort_order, id');

$notMigrated = (!$commission && !$delivery && !$euParams && !$krParams);

// Reference column shown read-only beside the edited values:
//   global mode   -> the retail (non-logged) price, from gh3sp_parsing_*;
//   per-client mode -> the global B2B price (what every other partner pays).
if ($perUser) {
    $refLabel    = $t['pricing_global_b2b'];
    $rCommission = $load('SELECT * FROM '.$pfx.'_b2b_commission_tiers WHERE b2b_user_id IS NULL ORDER BY sort_order, price_from');
    $rDelivery   = $load('SELECT * FROM '.$pfx.'_b2b_eu_tiers WHERE b2b_user_id IS NULL ORDER BY sort_order, price_from');
    $rEuMap = $rKrMap = [];
    foreach ($load('SELECT * FROM '.$pfx.'_b2b_eu_params WHERE b2b_user_id IS NULL') as $p) { $rEuMap[(string)$p['param_key']] = $p; }
    foreach ($load('SELECT * FROM '.$pfx.'_b2b_kr_params WHERE b2b_user_id IS NULL') as $p) { $rKrMap[(string)$p['param_key']] = $p; }
} else {
    $refLabel    = $t['pricing_public'];
    $rCommission = $load('SELECT * FROM '.$pfx.'_parsing_commission_tiers ORDER BY sort_order, price_from');
    $rDelivery   = $load('SELECT * FROM '.$pfx.'_parsing_eu_tiers ORDER BY sort_order, price_from');
    $rEuMap = $rKrMap = [];
    foreach ($load('SELECT * FROM '.$pfx.'_parsing_eu_params') as $p) { $rEuMap[(string)$p['param_key']] = $p; }
    foreach ($load('SELECT * FROM '.$pfx.'_parsing_kr_params') as $p) { $rKrMap[(string)$p['param_key']] = $p; }
}

// Per-card status strip (badge + reset), shown only in per-client mode.
$cardStatus = function (bool $custom, string $section) use ($perUser, $t) {
    if (!$perUser) return '';
    $badge = '<span class="b2bp-tag b2bp-tag--'.($custom ? 'custom' : 'global').'">'
           . b2b_adm_esc($custom ? $t['pricing_tag_custom'] : $t['pricing_tag_global']).'</span>';
    $reset = $custom
        ? '<button type="button" class="b2ba-btn b2ba-btn--soft b2ba-btn--sm" data-b2b-pricing-reset data-section="'
          . b2b_adm_esc($section).'">'.b2b_adm_esc($t['pricing_reset']).'</button>'
        : '';
    return '<div class="b2bp-status">'.$badge.$reset.'</div>';
};

// Region flags in the card titles, same assets/style as /adminsauto/parsing/settings.
$flagStyle = 'height:1.1em;width:auto;vertical-align:-0.15em;margin-right:0.4rem;';
$flagEu = '<img src="/content/admin/page/parsing/media-parsing/flag-europe.svg" alt="Europa" style="'.$flagStyle.'">';
$flagKr = '<img src="/content/admin/page/parsing/media-parsing/flag-korea.svg" alt="Coreea" style="'.$flagStyle.'">';

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

/** Renders a tier table (price_from / price_to / B2B value / public value).
 *  data-label on every cell is what the mobile card layout prints above the field
 *  (the <thead> is hidden there); $valueLabel is the card's own value-column title. */
$tierRows = function (array $rows, string $valueField, array $retailRows, string $valueLabel) use ($pt, $retailTierVal, $perUser, $refLabel) {
    $lFrom = b2b_adm_esc($pt['eu_col_price_from'] ?? '');
    $lTo   = b2b_adm_esc($pt['eu_col_price_to'] ?? '');
    $lVal  = b2b_adm_esc($valueLabel);
    $lRef  = b2b_adm_esc($refLabel);
    $out = '';
    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $to = ($r['price_to'] === null || $r['price_to'] === '') ? '' : (int)$r['price_to'];

        // Reference price for the same band (matched by the row's starting price):
        // retail globally, global B2B per client.
        $pub = $retailTierVal($retailRows, (float)$r['price_from'], $valueField);
        $pubCell = $pub === null ? '&mdash;' : $pub.' &euro;';

        // Flag the value when it differs from its reference: retail (public) on
        // the global page, global B2B on the per-client page. A missing reference
        // counts as "custom" only per client (globally there's nothing to compare).
        $val  = (int)$r[$valueField];
        $diff = ($pub !== null && $val !== $pub) || ($perUser && $pub === null);

        $out .= '<tr data-row data-id="'.$id.'"'.($diff ? ' class="b2bp-diff"' : '').'>'
              . '<td data-label="'.$lFrom.'"><input type="number" min="0" step="1" class="b2bp-from" value="'.(int)$r['price_from'].'"></td>'
              . '<td data-label="'.$lTo.'"><input type="number" min="0" step="1" class="b2bp-to" value="'.$to.'" placeholder="'.b2b_adm_esc($pt['eu_price_to_unlimited'] ?? '').'"></td>'
              . '<td class="b2bp-vcell'.($diff ? ' is-diff' : '').'" data-label="'.$lVal.'"><input type="number" min="0" step="1" class="b2bp-val" value="'.$val.'" data-ref="'.($pub === null ? '' : $pub).'"></td>'
              . '<td class="b2bp-ref" data-label="'.$lRef.'"><span class="b2bp-refv">'.$pubCell.'</span></td>'
              . '<td><button type="button" class="b2bp-del" title="'.b2b_adm_esc($pt['tier_remove'] ?? '').'">&times;</button></td>'
              . '</tr>';
    }
    return $out;
};

/** Renders a param table (label / enabled / B2B amount / retail reference). */
$paramRows = function (array $rows, string $labelPfx, array $retailMap) use ($pt, $t, $perUser, $refLabel) {
    $lEn  = b2b_adm_esc($pt['eu_col_enabled'] ?? '');
    $lAmt = b2b_adm_esc($pt['eu_col_amount'] ?? '');
    $lRef = b2b_adm_esc($refLabel);
    $out = '';
    foreach ($rows as $r) {
        $id     = (int)$r['id'];
        $key    = (string)$r['param_key'];
        $label  = $pt[$labelPfx.$key] ?? ucfirst(str_replace('_', ' ', $key));
        $onBool = (int)$r['enabled'] === 1;
        $on     = $onBool ? ' checked' : '';
        $amtI   = (int)round((float)$r['amount_eur']);

        // Reference for this exact param (matched by key): retail globally,
        // global B2B per client. Flag amount / on-off differences from it (both
        // pages); a missing reference counts as custom only per client.
        $ref    = '&mdash;';
        $refAmt = '';
        $refOn  = '';
        $diff   = $perUser; // no reference counterpart => custom by definition (per client)
        if (isset($retailMap[$key])) {
            $rp      = $retailMap[$key];
            $refAmtI = (int)round((float)$rp['amount_eur']);
            $refOnB  = (int)$rp['enabled'] === 1;
            $refAmt  = (string)$refAmtI;
            $refOn   = $refOnB ? '1' : '0';
            $ref     = $refAmtI.' &euro;';
            if (!$refOnB) { $ref .= ' <span class="b2bp-off">'.b2b_adm_esc($t['pricing_off']).'</span>'; }
            $diff = ($amtI !== $refAmtI || $onBool !== $refOnB);
        }

        $out  .= '<tr data-row data-id="'.$id.'" data-key="'.b2b_adm_esc($key).'"'.($diff ? ' class="b2bp-diff"' : '').'>'
              . '<td class="b2bp-label">'.b2b_adm_esc($label).'</td>'
              . '<td class="b2bp-c" data-label="'.$lEn.'"><input type="checkbox" class="b2bp-en"'.$on.'></td>'
              . '<td class="b2bp-vcell'.($diff ? ' is-diff' : '').'" data-label="'.$lAmt.'"><input type="number" min="0" step="1" class="b2bp-val" value="'.$amtI.'" data-ref="'.$refAmt.'" data-ref-en="'.$refOn.'"> <span class="b2bp-u">&euro;</span></td>'
              . '<td class="b2bp-ref" data-label="'.$lRef.'"><span class="b2bp-refv">'.$ref.'</span></td>'
              . '</tr>';
    }
    return $out;
};

?>

<div id="b2ba-pricing" class="<?= $pricingEmbedded ? 'b2bp-embed' : 'b2ba' ?>"
     data-saved-msg="<?= b2b_adm_esc($t['saved']) ?>"
     data-b2b-pricing-user="<?= $perUser ? (int)$pricingUserId : '' ?>"
     data-reset-msg="<?= b2b_adm_esc($t['pricing_reset_done']) ?>">

    <?php if (!$pricingEmbedded): ?>
        <?php if ($perUser): ?>
            <a class="b2ba-back" href="/<?= b2b_adm_esc($lang) ?>/<?= b2b_adm_esc($admin_dir) ?>/b2b/user?id=<?= (int)$pricingUserId ?>">&larr; <?= b2b_adm_esc(B2bAuth::displayName($pricingClient)) ?></a>
        <?php endif; ?>

        <div class="b2ba-head">
            <h1 class="b2ba-h1">
                <?= b2b_adm_esc($perUser ? $t['pricing_title_user'] : $t['pricing_title']) ?>
                <?php if ($perUser): ?><span class="b2bp-who"><?= b2b_adm_esc(B2bAuth::displayName($pricingClient)) ?></span><?php endif; ?>
            </h1>
        </div>

        <div class="b2ba-msg" id="b2ba-msg" role="status" aria-live="polite"></div>
    <?php endif; ?>

    <?php if ($notMigrated): ?>
        <div class="b2ba-warn"><?= b2b_adm_esc($t['pricing_not_migrated']) ?></div>
    <?php endif; ?>

    <p class="b2ba-hint"><?= b2b_adm_esc($perUser ? $t['pricing_hint_user'] : $t['pricing_hint']) ?></p>

    <!-- Commission tiers (Europe + Korea) -->
    <div class="b2ba-card b2bp-card" data-section="commission" data-value="commission">
        <h2 class="b2ba-h2"><?= $flagEu.$flagKr ?><?= b2b_adm_esc($t['pricing_commission']) ?></h2>
        <?= $cardStatus($commissionCustom, 'commission') ?>
        <table class="b2bp-table">
            <thead><tr>
                <th><?= b2b_adm_esc($pt['eu_col_price_from']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_price_to']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_commission']) ?></th>
                <th class="b2bp-ref"><?= b2b_adm_esc($refLabel) ?></th>
                <th></th>
            </tr></thead>
            <tbody><?= $tierRows($commission, 'commission', $rCommission, $pt['eu_col_commission']) ?></tbody>
        </table>
        <div class="b2ba-card__foot">
            <button type="button" class="b2ba-btn b2ba-btn--ghost" data-b2b-tier-add><?= b2b_adm_esc($pt['tier_add'] ?? '+') ?></button>
            <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-pricing-save><?= b2b_adm_esc($t['save']) ?></button>
        </div>
    </div>

    <!-- Europe delivery tiers -->
    <div class="b2ba-card b2bp-card" data-section="delivery" data-value="delivery">
        <h2 class="b2ba-h2"><?= $flagEu ?><?= b2b_adm_esc($t['pricing_delivery']) ?></h2>
        <?= $cardStatus($deliveryCustom, 'delivery') ?>
        <table class="b2bp-table">
            <thead><tr>
                <th><?= b2b_adm_esc($pt['eu_col_price_from']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_price_to']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_delivery']) ?></th>
                <th class="b2bp-ref"><?= b2b_adm_esc($refLabel) ?></th>
                <th></th>
            </tr></thead>
            <tbody><?= $tierRows($delivery, 'delivery', $rDelivery, $pt['eu_col_delivery']) ?></tbody>
        </table>
        <div class="b2ba-card__foot">
            <button type="button" class="b2ba-btn b2ba-btn--ghost" data-b2b-tier-add><?= b2b_adm_esc($pt['tier_add'] ?? '+') ?></button>
            <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-pricing-save><?= b2b_adm_esc($t['save']) ?></button>
        </div>
    </div>

    <!-- Europe fixed costs -->
    <div class="b2ba-card b2bp-card" data-section="eu_params">
        <h2 class="b2ba-h2"><?= $flagEu ?><?= b2b_adm_esc($t['pricing_eu_params']) ?></h2>
        <?= $cardStatus($euParamsCustom, 'eu_params') ?>
        <table class="b2bp-table b2bp-params">
            <thead><tr>
                <th><?= b2b_adm_esc($pt['eu_col_param']) ?></th>
                <th class="b2bp-c"><?= b2b_adm_esc($pt['eu_col_enabled']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_amount']) ?></th>
                <th class="b2bp-ref"><?= b2b_adm_esc($refLabel) ?></th>
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
        <h2 class="b2ba-h2"><?= $flagKr ?><?= b2b_adm_esc($t['pricing_kr_params']) ?></h2>
        <?= $cardStatus($krParamsCustom, 'kr_params') ?>
        <table class="b2bp-table b2bp-params">
            <thead><tr>
                <th><?= b2b_adm_esc($pt['eu_col_param']) ?></th>
                <th class="b2bp-c"><?= b2b_adm_esc($pt['eu_col_enabled']) ?></th>
                <th><?= b2b_adm_esc($pt['eu_col_amount']) ?></th>
                <th class="b2bp-ref"><?= b2b_adm_esc($refLabel) ?></th>
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
