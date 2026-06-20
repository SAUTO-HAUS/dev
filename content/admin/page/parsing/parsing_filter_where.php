<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Build a shared SQL WHERE fragment + params from the catalog filter GET params
 * (brand, model, fuel, gear, year_from/to, price_from/to). Used by ctlg /
 * published / favorites so the filter searches the WHOLE DB, not just the
 * rows already loaded in the page.
 *
 * Returns ['sql' => ' AND ...', 'params' => [...]] — sql is appended to the
 * page's existing WHERE, params are merged into the prepared statement.
 *
 * The column alias prefix (e.g. "pc.") can be passed so it works with JOINs.
 */
function parsing_catalog_filter_where(string $col = ''): array
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

    $yFrom = $g('f_year_from');
    if ($yFrom !== '' && ctype_digit($yFrom)) { $sql .= " AND {$col}year >= ?"; $params[] = (int)$yFrom; }
    $yTo = $g('f_year_to');
    if ($yTo !== '' && ctype_digit($yTo)) { $sql .= " AND {$col}year <= ?"; $params[] = (int)$yTo; }

    $pFrom = $g('f_price_from');
    if ($pFrom !== '' && ctype_digit($pFrom)) { $sql .= " AND {$col}price_final_eur >= ?"; $params[] = (int)$pFrom; }
    $pTo = $g('f_price_to');
    if ($pTo !== '' && ctype_digit($pTo)) { $sql .= " AND {$col}price_final_eur <= ?"; $params[] = (int)$pTo; }

    return ['sql' => $sql, 'params' => $params];
}
