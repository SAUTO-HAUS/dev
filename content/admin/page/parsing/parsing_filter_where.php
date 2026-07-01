<?php defined( '_DOIT' ) or die( 'Restricted access' );

function parsing_catalog_filter_where(string $col = '', $db = null, string $prefx = ''): array
{
    $g = function ($k) { $v = $_GET[$k] ?? ''; return is_string($v) ? trim($v) : ''; };

    $sql = '';
    $params = [];

    $brand = $g('f_brand');
    if ($brand !== '') { $sql .= " AND {$col}brand = ?"; $params[] = $brand; }

    $model = $g('f_model');
    if ($model !== '') { $sql .= " AND {$col}model = ?"; $params[] = $model; }

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
