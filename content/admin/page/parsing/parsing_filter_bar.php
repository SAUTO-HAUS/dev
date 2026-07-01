<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Shared client-side filter bar for the parsing catalog pages
 * (/parsing/ctlg, /parsing/published, /parsing/favorites).
 *
 * Brand/model dropdowns are populated only with values that actually exist in
 * gh3sp_parsing_cars across ALL statuses, so we never show a brand/model that
 * isn't in the parser. Filtering itself happens client-side in parsing.js
 * (parsingApplyCatalogFilter) by reading data-* attributes on each car card.
 *
 * Expects $db, $prefx and $t (parsing_lang) to be in scope.
 */

// Distinct brand/model pairs from the whole parsing catalog, WITH a car count
// per value so each dropdown option reads e.g. "BMW (50)", "5-Series (20)".
// Counts respect the current source sub-tab (?source=) so they match what's shown.
$pf_brands = [];        // brand => count
$pf_models = [];        // brand => [model => count]
$pf_fuelsReal = [];     // fuel_type => count
$pf_gearsReal = [];     // gearbox => count
$pf_seatsReal = [];     // seats => count
$pf_srcWhere = '';
$pf_srcParams = [];
if (!empty($sourceFilter)) {
    $pf_srcWhere = ' AND source = ?';
    $pf_srcParams[] = $sourceFilter;
}
// Counts must reflect ONLY the page that is showing this bar: ctlg counts the
// proposed cars, published counts published+unavailable, favorites counts
// favorites. The including page sets $pf_status_filter to the right status list.
$pf_statusWhere = '';
if (!empty($pf_status_filter) && is_array($pf_status_filter)) {
    $place = implode(',', array_fill(0, count($pf_status_filter), '?'));
    $pf_statusWhere = ' AND status IN ('.$place.')';
    $pf_srcParams = array_merge($pf_srcParams, $pf_status_filter);
}
// Optional extra WHERE fragment (no params) the including page can set, e.g. the
// published page restricts counts to cars actually published to sauto. Must be a
// safe, static SQL string (no user input).
$pf_extraWhere = !empty($pf_extra_where) ? ' AND ('.$pf_extra_where.')' : '';
try {
    $pfStmt = $db->prepare('SELECT brand, model, fuel_type, gearbox, seats, COUNT(*) AS cnt
        FROM '.$prefx.'_parsing_cars
        WHERE brand IS NOT NULL AND brand <> ""'.$pf_srcWhere.$pf_statusWhere.$pf_extraWhere.'
        GROUP BY brand, model, fuel_type, gearbox, seats
        ORDER BY brand ASC, model ASC');
    $pfStmt->execute($pf_srcParams);
    foreach ($pfStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $b = trim((string)$row['brand']);
        $m = trim((string)$row['model']);
        $cnt = (int)$row['cnt'];
        if ($b === '') continue;
        $pf_brands[$b] = ($pf_brands[$b] ?? 0) + $cnt;
        if ($m !== '') $pf_models[$b][$m] = ($pf_models[$b][$m] ?? 0) + $cnt;
        $f = trim((string)($row['fuel_type'] ?? ''));
        $g = trim((string)($row['gearbox'] ?? ''));
        $s = (int)($row['seats'] ?? 0);
        if ($f !== '') $pf_fuelsReal[$f] = ($pf_fuelsReal[$f] ?? 0) + $cnt;
        if ($g !== '') $pf_gearsReal[$g] = ($pf_gearsReal[$g] ?? 0) + $cnt;
        if ($s > 0)    $pf_seatsReal[$s] = ($pf_seatsReal[$s] ?? 0) + $cnt;
    }
    ksort($pf_seatsReal);  // 2, 4, 5, 7... in order
} catch (Exception $e) {
    // leave empty on error
}

// Format a count suffix like " (50)".
$pf_cnt = fn($n) => ((int)$n > 0 ? ' ('.number_format((int)$n, 0, '.', '.').')' : '');

// Build <option>s for the brand select (with count).
$pf_brandOptions = '';
foreach ($pf_brands as $b => $cnt) {
    $pf_brandOptions .= '<option value="'.htmlspecialchars($b, ENT_QUOTES).'">'.htmlspecialchars($b).$pf_cnt($cnt).'</option>';
}

// Fuel / gearbox label maps (for display); values come only from real data.
$pf_fuel = $fuelLabels ?? [
    'benzina' => 'Benzină', 'diesel' => 'Diesel', 'lpg' => 'LPG', 'hybrid' => 'Hybrid',
    'electric' => 'Electric', 'other' => 'Altele',
];
$pf_gear = $gearLabels ?? [
    'automat' => 'Automat', 'manual' => 'Manual', 'semi-auto' => 'Semi-auto', 'cvt' => 'CVT',
];

// Only show fuels/gearboxes that actually exist in the parsing catalog (with count).
$pf_fuelOptions = '';
foreach ($pf_fuelsReal as $k => $cnt) {
    $label = $pf_fuel[$k] ?? $k;
    $pf_fuelOptions .= '<option value="'.htmlspecialchars($k, ENT_QUOTES).'">'.htmlspecialchars($label).$pf_cnt($cnt).'</option>';
}
$pf_gearOptions = '';
foreach ($pf_gearsReal as $k => $cnt) {
    $label = $pf_gear[$k] ?? $k;
    $pf_gearOptions .= '<option value="'.htmlspecialchars($k, ENT_QUOTES).'">'.htmlspecialchars($label).$pf_cnt($cnt).'</option>';
}

$L = function ($key, $fallback) use ($t) { return $t[$key] ?? $fallback; };

// Current filter values (server-side filter → values come back via GET).
$gv = function ($k) { $v = $_GET[$k] ?? ''; return is_string($v) ? trim($v) : ''; };
$cur_brand = $gv('f_brand');
$cur_model = $gv('f_model');
$cur_fuel  = $gv('f_fuel');
$cur_gear  = $gv('f_gear');
$cur_seats = $gv('f_seats');
$cur_yf    = $gv('f_year_from');
$cur_yt    = $gv('f_year_to');
$cur_pf    = $gv('f_price_from');
$cur_pt    = $gv('f_price_to');
$hasFilter = ($cur_brand || $cur_model || $cur_fuel || $cur_gear || $cur_seats || $cur_yf || $cur_yt || $cur_pf || $cur_pt);

$sel = function ($a, $b) { return (string)$a === (string)$b ? ' selected' : ''; };

// Preserve the source sub-tab (ctlg only) across filter submits.
$cur_source = $gv('source');

// Models for the currently-selected brand (pre-render so reload keeps them).
$cur_brand_models = $cur_brand && isset($pf_models[$cur_brand]) ? $pf_models[$cur_brand] : []; // [model => count]
?>
<form id="parsing-catalog-filter" method="get"
      data-models='<?= htmlspecialchars(json_encode($pf_models, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'>
    <?php if ($cur_source !== ''): ?>
        <input type="hidden" name="source" value="<?= htmlspecialchars($cur_source, ENT_QUOTES) ?>">
    <?php endif; ?>

    <div class="pcf-grid">
        <label class="pcf-cell">
            <span class="pcf-label"><?= $L('lbl_brand', 'Marcă') ?></span>
            <select id="pcf-brand" name="f_brand" class="pcf-field">
                <option value=""><?= $L('opt_all', 'Toate') ?></option>
                <?php foreach ($pf_brands as $b => $bCnt): ?>
                    <option value="<?= htmlspecialchars($b, ENT_QUOTES) ?>"<?= $sel($b, $cur_brand) ?>><?= htmlspecialchars($b).$pf_cnt($bCnt) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="pcf-cell">
            <span class="pcf-label"><?= $L('lbl_model', 'Model') ?></span>
            <select id="pcf-model" name="f_model" class="pcf-field"<?= $cur_brand ? '' : ' disabled' ?>>
                <option value=""><?= $L('opt_all', 'Toate') ?></option>
                <?php foreach ($cur_brand_models as $m => $mCnt): ?>
                    <option value="<?= htmlspecialchars($m, ENT_QUOTES) ?>"<?= $sel($m, $cur_model) ?>><?= htmlspecialchars($m).$pf_cnt($mCnt) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="pcf-cell">
            <span class="pcf-label"><?= $L('lbl_fuel', 'Combustibil') ?></span>
            <select id="pcf-fuel" name="f_fuel" class="pcf-field">
                <option value=""><?= $L('opt_all', 'Toate') ?></option>
                <?php foreach ($pf_fuelsReal as $k => $kCnt): ?>
                    <option value="<?= htmlspecialchars($k, ENT_QUOTES) ?>"<?= $sel($k, $cur_fuel) ?>><?= htmlspecialchars($pf_fuel[$k] ?? $k).$pf_cnt($kCnt) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="pcf-cell">
            <span class="pcf-label"><?= $L('lbl_gearbox', 'Cutie') ?></span>
            <select id="pcf-gear" name="f_gear" class="pcf-field">
                <option value=""><?= $L('opt_all', 'Toate') ?></option>
                <?php foreach ($pf_gearsReal as $k => $kCnt): ?>
                    <option value="<?= htmlspecialchars($k, ENT_QUOTES) ?>"<?= $sel($k, $cur_gear) ?>><?= htmlspecialchars($pf_gear[$k] ?? $k).$pf_cnt($kCnt) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php
            $pf_yearMin = 2016;
            $pf_yearMax = (int)date('Y') + 1;
            $pf_years = range($pf_yearMax, $pf_yearMin);
        ?>
        <div class="pcf-cell pcf-cell-range">
            <span class="pcf-label"><?= $L('lbl_year', 'An') ?></span>
            <div class="pcf-range">
                <select name="f_year_from" class="pcf-field">
                    <option value=""><?= $L('filter_from', 'de la') ?></option>
                    <?php foreach ($pf_years as $y): ?>
                        <option value="<?= $y ?>"<?= $sel($y, $cur_yf) ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="pcf-dash">–</span>
                <select name="f_year_to" class="pcf-field">
                    <option value=""><?= $L('filter_to', 'până la') ?></option>
                    <?php foreach ($pf_years as $y): ?>
                        <option value="<?= $y ?>"<?= $sel($y, $cur_yt) ?>><?= $y ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="pcf-cell pcf-cell-range">
            <span class="pcf-label"><?= $L('lbl_price', 'Preț') ?> €</span>
            <div class="pcf-range">
                <input type="number" name="f_price_from" class="pcf-field" placeholder="<?= $L('filter_from', 'de la') ?>" min="0" value="<?= htmlspecialchars($cur_pf, ENT_QUOTES) ?>">
                <span class="pcf-dash">–</span>
                <input type="number" name="f_price_to" class="pcf-field" placeholder="<?= $L('filter_to', 'până la') ?>" min="0" value="<?= htmlspecialchars($cur_pt, ENT_QUOTES) ?>">
            </div>
        </div>

        <?php if (!empty($pf_seatsReal)): ?>
        <label class="pcf-cell">
            <span class="pcf-label"><?= $L('lbl_seats', 'Nr. locuri') ?></span>
            <select id="pcf-seats" name="f_seats" class="pcf-field">
                <option value=""><?= $L('opt_all', 'Toate') ?></option>
                <?php foreach ($pf_seatsReal as $k => $kCnt): ?>
                    <option value="<?= (int)$k ?>"<?= $sel($k, $cur_seats) ?>><?= (int)$k . $pf_cnt($kCnt) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endif; ?>

        <div class="pcf-actions pcf-cell">
            <button type="submit" class="pcf-apply"><?= $L('filter_apply', 'Aplică') ?></button>
            <a href="?<?= $cur_source !== '' ? 'source='.urlencode($cur_source) : '' ?>" class="pcf-reset"><?= $L('filter_reset', 'Resetează') ?></a>
        </div>
    </div>
</form>
