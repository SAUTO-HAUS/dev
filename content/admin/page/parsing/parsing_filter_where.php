<?php defined( '_DOIT' ) or die( 'Restricted access' );

function parsing_catalog_filter_where(string $col = '', $db = null, string $prefx = ''): array
{
    $g = function ($k) { $v = $_GET[$k] ?? ''; return is_string($v) ? trim($v) : ''; };

    $sql = '';
    $params = [];

    // Filter-card "999" button: show only cars of one saved filter that this
    // cron cross-posted to 999 (crosspost_999 = 1).
    $filterId = $g('filter_id');
    if ($filterId !== '' && ctype_digit($filterId)) { $sql .= " AND {$col}filter_id = ?"; $params[] = (int)$filterId; }
    if ($g('crosspost999') === '1') { $sql .= " AND {$col}crosspost_999 = 1"; }

    // Brand/model filter matches the DISPLAYED name. Same 3-step preference the
    // dropdown/cards use: (1) the published catalog row''s br/mo (via car_ctlg_id),
    // (2) the parsing sauto_br/mo mapping, (3) the raw source name. Mirrors those
    // exactly so a selected option actually matches. $prefx names the tables.
    $ctlgBr = "(SELECT clc.br_nm FROM {$prefx}_car_list clc JOIN {$prefx}_car_ctlg cc ON cc.br=clc.br AND cc.mo=clc.mo WHERE cc.id = {$col}car_ctlg_id LIMIT 1)";
    $mapBr  = "(SELECT cl.br_nm FROM {$prefx}_car_list cl WHERE cl.br = {$col}sauto_br AND cl.mo = {$col}sauto_mo LIMIT 1)";
    $ctlgMo = "(SELECT clc.mo_nm FROM {$prefx}_car_list clc JOIN {$prefx}_car_ctlg cc ON cc.br=clc.br AND cc.mo=clc.mo WHERE cc.id = {$col}car_ctlg_id LIMIT 1)";
    $mapMo  = "(SELECT cl.mo_nm FROM {$prefx}_car_list cl WHERE cl.br = {$col}sauto_br AND cl.mo = {$col}sauto_mo LIMIT 1)";
    $displayBrand = $prefx !== ''
        ? "COALESCE(NULLIF({$ctlgBr}, ''), NULLIF({$mapBr}, ''), {$col}brand)"
        : "{$col}brand";
    $displayModel = $prefx !== ''
        ? "COALESCE(NULLIF({$ctlgMo}, ''), NULLIF({$mapMo}, ''), {$col}model)"
        : "{$col}model";

    $brand = $g('f_brand');
    if ($brand !== '') { $sql .= " AND {$displayBrand} = ?"; $params[] = $brand; }

    $model = $g('f_model');
    if ($model !== '') { $sql .= " AND {$displayModel} = ?"; $params[] = $model; }

    $fuel = $g('f_fuel');
    if ($fuel !== '') { $sql .= " AND {$col}fuel_type = ?"; $params[] = $fuel; }

    $gear = $g('f_gear');
    if ($gear !== '') { $sql .= " AND {$col}gearbox = ?"; $params[] = $gear; }

    $seats = $g('f_seats');
    if ($seats !== '' && ctype_digit($seats)) { $sql .= " AND {$col}seats = ?"; $params[] = (int)$seats; }

    $yFrom = $g('f_year_from');
    if ($yFrom !== '' && ctype_digit($yFrom)) { $sql .= " AND {$col}year >= ?"; $params[] = (int)$yFrom; }
    $yTo = $g('f_year_to');
    if ($yTo !== '' && ctype_digit($yTo)) { $sql .= " AND {$col}year <= ?"; $params[] = (int)$yTo; }

    $pFrom = $g('f_price_from');
    $pTo   = $g('f_price_to');
    if (($pFrom !== '' && ctype_digit($pFrom)) || ($pTo !== '' && ctype_digit($pTo))) {
        $priceExpr = parsing_displayed_price_sql($col, $db, $prefx);
        if ($pFrom !== '' && ctype_digit($pFrom)) { $sql .= " AND {$priceExpr} >= ?"; $params[] = (int)$pFrom; }
        if ($pTo   !== '' && ctype_digit($pTo))   { $sql .= " AND {$priceExpr} <= ?"; $params[] = (int)$pTo; }
    }

    return ['sql' => $sql, 'params' => $params];
}

function parsing_displayed_price_sql(string $col, $db, string $prefx): string
{
    static $cache = [];
    $key = $col . '|' . $prefx;
    if (isset($cache[$key])) return $cache[$key];

    $plain = "{$col}price_final_eur";
    if (!$db || $prefx === '') { return $cache[$key] = $plain; }

    $tiers = [];
    try {
        $stmt = $db->prepare('SELECT price_from, price_to, markup FROM '
            . $prefx . '_parsing_kr_markup_tiers ORDER BY sort_order, price_from');
        $stmt->execute();
        $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        return $cache[$key] = $plain;
    }
    if (!$tiers) { return $cache[$key] = $plain; }

    $cases = '';
    foreach ($tiers as $t) {
        $from = (int)($t['price_from'] ?? 0);
        $toRaw = $t['price_to'];
        $markup = (int)($t['markup'] ?? 0);
        if ($toRaw === null || $toRaw === '') {
            $cases .= " WHEN {$col}price_final_eur >= {$from} THEN {$markup}";
        } else {
            $to = (int)$toRaw;
            $cases .= " WHEN {$col}price_final_eur >= {$from} AND {$col}price_final_eur <= {$to} THEN {$markup}";
        }
    }
    if ($cases === '') { return $cache[$key] = $plain; }

    $markupExpr = "(CASE{$cases} ELSE 0 END)";
    $expr = "({$col}price_final_eur + CASE WHEN {$col}source = 'encar' THEN {$markupExpr} ELSE 0 END)";
    return $cache[$key] = $expr;
}
