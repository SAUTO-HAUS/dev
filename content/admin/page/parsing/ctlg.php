<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/parsing/parsing_lang.php';
include_once _ADM_PAGE.'/parsing/parsing_pricing.php';
$t = $parsing_lang;

// Access — ids configured in include/parsing_access.php.
require_once(_ADM_INCL.'/parsing_access.php');
if (!parsing_has_access($user_id ?? 0)) {
    echo '<span class="err">'.$t['access_denied'].'</span>';
    return;
}

// Optional source filter: ?source=encar|ecarstrade|openlane|auto1
$sourceFilter = $_GET['source'] ?? '';
if (!in_array($sourceFilter, ['encar', 'ecarstrade', 'openlane', 'auto1'], true)) {
    $sourceFilter = '';
}
// Encar-only users are locked to the Encar source (other tabs hidden below).
$parsingEncarOnly = parsing_is_encar_only($user_id ?? 0);
if ($parsingEncarOnly) {
    $sourceFilter = 'encar';
    // Hide the published/settings/favorites tabs — keep only Filtre + Catalog.
    echo '<style>.parsing-tabs .tab[href$="/parsing/published"],'
        .'.parsing-tabs .tab[href$="/parsing/settings"],'
        .'.parsing-tabs .tab-favorites{display:none !important;}</style>';
}

$cars = [];
$totalCount = 0;
// Counts per source for sub-tab badges.
$sourceCounts = ['' => 0, 'encar' => 0, 'ecarstrade' => 0, 'openlane' => 0, 'auto1' => 0];
try {
    $countStmt = $db->query('SELECT source, COUNT(*) AS c FROM '.$prefx.'_parsing_cars WHERE status = "proposed" GROUP BY source');
    foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $src = $row['source'] ?? '';
        if (isset($sourceCounts[$src])) {
            $sourceCounts[$src] = (int)$row['c'];
        }
        $sourceCounts[''] += (int)$row['c'];
    }
    $totalCount = $sourceFilter === '' ? $sourceCounts[''] : $sourceCounts[$sourceFilter];

    // Shared catalog filter (searches the whole DB, not just loaded rows).
    include_once _ADM_PAGE.'/parsing/parsing_filter_where.php';
    include_once _ADM_PAGE.'/parsing/parsing_pagination.php';
    $flt = parsing_catalog_filter_where('pc.', $db, $prefx);

    $where = 'pc.status = "proposed"';
    $bind = [];
    if ($sourceFilter !== '') { $where .= ' AND pc.source = ?'; $bind[] = $sourceFilter; }

    $where .= ' AND NOT (
        pc.source IN ("ecarstrade", "openlane")
        AND COALESCE(
            JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, "$.auction_end")),
            JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, "$.BatchEndDate")),
            JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, "$.raw_data.auction_end")),
            JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, "$.raw_data.BatchEndDate"))
        ) IS NOT NULL
        AND STR_TO_DATE(
            REPLACE(REPLACE(COALESCE(
                JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, "$.auction_end")),
                JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, "$.BatchEndDate")),
                JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, "$.raw_data.auction_end")),
                JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, "$.raw_data.BatchEndDate"))
            ), "T", " "), "Z", ""),
            "%Y-%m-%d %H:%i:%s"
        ) < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)
    )';
    // Auto1 dates its auctions with auctionEndDatetime — a MILLISECOND epoch
    // number, so it is compared in milliseconds (a FROM_UNIXTIME() here would be
    // rendered in the session timezone while the cutoff is UTC).
    $where .= ' AND NOT (
        pc.source = "auto1"
        AND COALESCE(
            JSON_EXTRACT(pc.raw_data, "$.auctionEndDatetime"),
            JSON_EXTRACT(pc.raw_data, "$.raw_data.auctionEndDatetime")
        ) IS NOT NULL
        AND COALESCE(
            JSON_EXTRACT(pc.raw_data, "$.auctionEndDatetime"),
            JSON_EXTRACT(pc.raw_data, "$.raw_data.auctionEndDatetime")
        ) < (UNIX_TIMESTAMP() - 3600) * 1000
    )';

    $where .= $flt['sql'];
    $bind = array_merge($bind, $flt['params']);

    // Count matching rows (respects source + filter) for pagination.
    $cntStmt = $db->prepare('SELECT COUNT(*) FROM '.$prefx.'_parsing_cars pc WHERE '.$where);
    $cntStmt->execute($bind);
    $matchCount = (int)$cntStmt->fetchColumn();
    if ($flt['sql'] !== '' || $sourceFilter !== '') $totalCount = $matchCount;

    $pageSize = parsing_page_size();
    $offset   = (parsing_current_page() - 1) * $pageSize;

    // Price sorting, same UI as /published, in two families:
    //   eur_* — the € price printed on the card (.car-price-val): price_final_eur
    //           plus the Encar band markup. Exactly the expression the price filter
    //           uses, so sorting and filtering agree. Plain SQL.
    //   md_*  — the full MD landed cost (the "MD:" line). Proposed cars have no
    //           stored MD price (the card computes it in the browser; car_ctlg.prc
    //           exists only after publishing), and MD is NOT a function of the €
    //           price alone — excise depends on engine cc, fuel and age, so a
    //           cheaper car can land higher. It is therefore ranked in PHP below.
    // Whitelisted map, never interpolated from the request.
    $priceExpr = parsing_displayed_price_sql('pc.', $db, $prefx);
    $defaultOrder = 'DATE_FORMAT(pc.found_at, "%Y-%m-%d %H:%i") DESC,
                 pc.price_final_eur IS NULL, pc.price_final_eur ASC,
                 pc.id DESC';
    $sortKeys = [
        ''         => $defaultOrder,
        'eur_asc'  => 'CASE WHEN COALESCE('.$priceExpr.', 0) > 0 THEN 0 ELSE 1 END, '.$priceExpr.' ASC,  pc.id DESC',
        'eur_desc' => 'CASE WHEN COALESCE('.$priceExpr.', 0) > 0 THEN 0 ELSE 1 END, '.$priceExpr.' DESC, pc.id DESC',
        'md_asc'   => $defaultOrder, // ordered in PHP (see below), not in SQL
        'md_desc'  => $defaultOrder,
    ];
    $sortKey = isset($_GET['f_sort']) ? (string)$_GET['f_sort'] : '';
    if (!isset($sortKeys[$sortKey])) $sortKey = '';
    $orderBy = $sortKeys[$sortKey];

    // MD sort: rank EVERY matching car (not just this page) with the same
    // functions ParsingPublisher uses to write car_ctlg.prc, so the order matches
    // both the "MD:" line on the card and the price the car gets once published.
    // Only runs when an MD sort is actually picked; the query is id + the five
    // fields the formula needs.
    $mdIds = null;
    if ($sortKey === 'md_asc' || $sortKey === 'md_desc') {
        $rankStmt = $db->prepare('SELECT pc.id, pc.source, pc.price_eur, pc.fuel_type,
                pc.engine_volume, pc.year, pc.title_ro, pc.model
            FROM '.$prefx.'_parsing_cars pc
            WHERE '.$where.'
            ORDER BY '.$defaultOrder);
        $rankStmt->execute($bind);

        $ranked = [];
        foreach ($rankStmt->fetchAll(PDO::FETCH_ASSOC) as $i => $r) {
            // Same inputs the card feeds parsingCalcMd: the raw price_eur and
            // fuel_type, and the engine cc from the DB or parsed from the title.
            // With no cc at all the excise — the biggest line — would be dropped
            // and the MD would be badly low, so the card shows "MD: …" instead;
            // those cars get md = 0 here and sink to the bottom.
            $cap = (int)($r['engine_volume'] ?? 0);
            if ($cap <= 0) $cap = (int)round(((float)parsing_engine_liters($r)) * 1000);
            $md = 0;
            if ((float)($r['price_eur'] ?? 0) > 0
                && ($cap > 0 || parsing_fuel_code($r['fuel_type'] ?? '') === 'electric')) {
                $bdCar = [
                    'price_eur' => (float)$r['price_eur'],
                    'fuel'      => (string)($r['fuel_type'] ?? ''),
                    'capacity'  => $cap,
                    'year'      => (int)($r['year'] ?? 0),
                ];
                $bd = ($r['source'] ?? '') === 'encar'
                    ? parsing_md_breakdown_kr($db, $prefx, $bdCar)
                    : parsing_md_breakdown_eu($db, $prefx, $bdCar);
                if ($bd && !empty($bd['total'])) $md = (int)round($bd['total']);
            }
            $ranked[] = ['id' => (int)$r['id'], 'md' => $md, 'pos' => $i];
        }

        $dir = $sortKey === 'md_asc' ? 1 : -1;
        usort($ranked, function ($a, $b) use ($dir) {
            // Cars with no MD yet stay last in BOTH directions, in the default
            // (found_at) order; 'pos' also keeps the sort deterministic on ties.
            if (($a['md'] > 0) !== ($b['md'] > 0)) return $a['md'] > 0 ? -1 : 1;
            if ($a['md'] > 0 && $a['md'] !== $b['md']) return ($a['md'] <=> $b['md']) * $dir;
            return $a['pos'] <=> $b['pos'];
        });
        $mdIds = array_column(array_slice($ranked, $offset, $pageSize), 'id');
    }

    // The page slice: normally WHERE + ORDER BY + LIMIT, but for an MD sort the
    // ids and their order are already decided above, so fetch exactly those.
    $listWhere = $where;
    $listBind  = $bind;
    $listOrder = $orderBy;
    $listLimit = ' LIMIT '.$pageSize.' OFFSET '.$offset;
    if ($mdIds !== null) {
        $in        = implode(',', array_map('intval', $mdIds));
        $listWhere = $in === '' ? '0' : 'pc.id IN ('.$in.')';
        $listBind  = [];
        $listOrder = $in === '' ? 'pc.id' : 'FIELD(pc.id, '.$in.')';
        $listLimit = '';
    }

    // brand/model come from car_list (the single canonical sauto list) via the
    // car''s sauto_br/sauto_mo mapping, falling back to the raw source name for
    // not-yet-mapped cars. This keeps the card data-brand/data-model identical to
    // the filter dropdown so client-side filtering matches.
    $stmt = $db->prepare('SELECT pc.*, pf.name AS filter_name,
            COALESCE(NULLIF(cl.br_nm, ""), pc.brand) AS brand,
            COALESCE(NULLIF(cl.mo_nm, ""), pc.model) AS model
        FROM '.$prefx.'_parsing_cars pc
        LEFT JOIN '.$prefx.'_parsing_filters pf ON pf.id = pc.filter_id
        LEFT JOIN '.$prefx.'_car_list cl ON cl.br = pc.sauto_br AND cl.mo = pc.sauto_mo
        WHERE '.$listWhere.'
        ORDER BY '.$listOrder.$listLimit);
    $stmt->execute($listBind);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$rtrn = '
<link rel="stylesheet" href="/content/admin/page/parsing/parsing.css?v='.filemtime(_ADM_PAGE.'/parsing/parsing.css').'">
<div id="parsing-container">
    <div class="parsing-header">
        <h1>'.$t['page_ctlg'].'</h1>
        <span class="counter">'.$totalCount.' '.$t['ctlg_count_label'].'</span>
        '.(parsing_has_access($user_id ?? 0)
            ? '<button type="button" id="parsing-clear-btn" class="btn-danger" style="margin-left:auto;" onclick="parsingClearCatalog()" title="'.htmlspecialchars($t['clear_catalog_hint'] ?? '').'">'.($t['btn_clear_catalog'] ?? 'Șterge catalogul (păstrează publicate)').'</button>'
            : '').'
    </div>

    <div class="parsing-tabs">
        <a href="/'.$admin_dir.'/parsing/filters" class="tab">'.$t['tab_filters'].'</a>
        <a href="/'.$admin_dir.'/parsing/ctlg" class="tab active">'.$t['tab_ctlg'].'</a>
        <a href="/'.$admin_dir.'/parsing/published" class="tab">'.$t['tab_published'].'</a>
        <a href="/'.$admin_dir.'/parsing/settings" class="tab">'.$t['tab_settings'].'</a>
        <a href="/'.$admin_dir.'/parsing/favorites" class="tab tab-favorites"><img src="/content/admin/page/parsing/media-parsing/favorite.png" alt=""> '.$t['tab_favorites'].'</a>
    </div>

    <div class="source-subtabs">
        '.($parsingEncarOnly ? '' : '<a href="/'.$admin_dir.'/parsing/ctlg" class="src-tab'.($sourceFilter === '' ? ' active' : '').'">
            '.$t['opt_all'].' <span class="src-count">'.$sourceCounts[''].'</span>
        </a>').'
        <a href="/'.$admin_dir.'/parsing/ctlg?source=encar" class="src-tab src-tab-encar'.($sourceFilter === 'encar' ? ' active' : '').'">
            <img src="/content/admin/page/parsing/media-parsing/encar-logo.webp" alt="Encar">
            <span class="src-count">'.$sourceCounts['encar'].'</span>
        </a>
        '.($parsingEncarOnly ? '' : '<a href="/'.$admin_dir.'/parsing/ctlg?source=ecarstrade" class="src-tab src-tab-ecarstrade'.($sourceFilter === 'ecarstrade' ? ' active' : '').'">
            <img src="/content/admin/page/parsing/media-parsing/ecarstrade-logo.svg" alt="e-CarsTrade">
            <span class="src-count">'.$sourceCounts['ecarstrade'].'</span>
        </a>
        <a href="/'.$admin_dir.'/parsing/ctlg?source=openlane" class="src-tab'.($sourceFilter === 'openlane' ? ' active' : '').'">
            <img src="/content/admin/page/parsing/media-parsing/openlane-logo.svg" alt="OpenLane">
            <span class="src-count">'.$sourceCounts['openlane'].'</span>
        </a>
        <a href="/'.$admin_dir.'/parsing/ctlg?source=auto1" class="src-tab src-tab-auto1'.($sourceFilter === 'auto1' ? ' active' : '').'">
            <img src="/content/admin/page/parsing/media-parsing/auto1.png" alt="AUTO1">
            <span class="src-count">'.$sourceCounts['auto1'].'</span>
        </a>').'
    </div>

';

$fuelLabels = [
    'benzina'       => $t['opt_gasoline'] ?? 'Benzină',
    'diesel'        => $t['opt_diesel'] ?? 'Diesel',
    'lpg'           => 'LPG',
    'hybrid'        => $t['opt_hybrid'] ?? 'Hybrid',
    'hybrid_plugin' => $t['opt_plugin_hybrid'] ?? 'Plug-in Hybrid',
    'diesel_hybrid' => $t['opt_diesel_hybrid'] ?? 'Diesel Hybrid',
    'gasoline_lpg'  => $t['opt_gasoline_lpg'] ?? 'Benzină+LPG',
    'gasoline_cng'  => $t['opt_gasoline_cng'] ?? 'Benzină+CNG',
    'electric'      => $t['opt_electric'] ?? 'Electric',
    'other'         => $t['opt_other'] ?? 'Altele',
];
$gearLabels = [
    'automat'   => $t['opt_automatic'] ?? 'Automat',
    'manual'    => $t['opt_manual'] ?? 'Manual',
    'semi-auto' => $t['opt_semi_auto'] ?? 'Semi-auto',
    'cvt'       => $t['opt_cvt'] ?? 'CVT',
];

// Shared catalog filter bar (brand/model/fuel/gear/year/price).
// Counts in the bar must reflect ONLY this page's cars (proposed).
$pf_status_filter = ['proposed'];
// Sort dropdown — same control as /published, on either price shown on the card:
// the € source price or the MD landed cost. Keys must match $sortKeys above.
$pf_sort = true;
$pf_sort_opts = [
    ''         => $t['sort_none'],
    'eur_asc'  => $t['sort_eur_asc'],
    'eur_desc' => $t['sort_eur_desc'],
    'md_asc'   => $t['sort_md_asc'],
    'md_desc'  => $t['sort_md_desc'],
];
ob_start();
include _ADM_PAGE.'/parsing/parsing_filter_bar.php';
$rtrn .= ob_get_clean();

$rtrn .= '<div class="proposed-grid">';

if (empty($cars)) {
    $rtrn .= '<div class="empty-state">'.$t['empty_proposed'].'</div>';
} else {
    foreach ($cars as $c) {
        $imgsRaw = json_decode($c['images_local'] ?? '[]', true) ?: [];
        $imgUrls = [];
        foreach ($imgsRaw as $img) {
            if (is_array($img) && !empty($img['path']) && !empty($img['name'])) {
                $imgUrls[] = '/' . trim($img['path'], '/') . '/' . $img['name'];
            } elseif (is_array($img) && !empty($img['url'])) {
                $imgUrls[] = $img['url'];
            } elseif (is_string($img) && $img !== '') {
                $imgUrls[] = $img;
            }
        }
        // OpenLane's image CDN blocks hotlinking (403 when the Referer is our
        // domain), so those URLs can't be loaded directly by the browser. Route
        // them through our image proxy (server-side fetch with openlane.eu
        // referer, disk-cached). Card thumbnails use sz=card → the proxy serves a
        // small ~600px copy (≈40KB) so the grid loads fast; the big gallery keeps
        // full resolution. Encar's CDN allows hotlinking, so it stays direct.
        $proxyOpenlane = function (array $list, bool $card): array {
            return array_map(function ($u) use ($card) {
                if (is_string($u) && stripos($u, 'images.openlane.eu') !== false) {
                    return '/ajax.php?tp=adm&pg=parsing&action=image_proxy'
                        . ($card ? '&sz=card' : '') . '&url=' . rawurlencode($u);
                }
                return $u;
            }, $list);
        };
        $cardUrls    = $proxyOpenlane($imgUrls, true);
        $galleryUrls = $proxyOpenlane($imgUrls, false);
        $noImage = empty($imgUrls);
        if ($noImage) {
            $cardUrls[]    = '/content/admin/page/parsing/parsing-media/no-image.png';
            $galleryUrls[] = '/content/admin/page/parsing/parsing-media/no-image.png';
        }
        // Slider card: max 10 images (fast load). Gallery loads up to 20 via JS.
        $sliderImgs = array_slice($cardUrls, 0, 10);
        $totalImgs  = $noImage ? 0 : count($cardUrls); // real total for counter
        $sliderJson = htmlspecialchars(json_encode($sliderImgs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        // Pass the full saved list for gallery open (no cap). For Encar search
        // imports only a few preview thumbs are saved, so the gallery also fetches
        // the complete photo set live (fetch_all_photos) when opened.
        $galleryJson = htmlspecialchars(json_encode(array_values($galleryUrls), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $imgUrls = $cardUrls; // cover img below uses $imgUrls[0]

        $subModel = trim((string)($c['car_sub_model'] ?? ''));
        $title = trim(($c['brand'] ?? '') . ' ' . ($c['model'] ?? '')
            . ($subModel !== '' ? ' ' . $subModel : '') . ' ' . ($c['year'] ?? ''));
        if (!empty($c['title_ro'])) $title = $c['title_ro'];
        // Trim (complectație) shown right after the model, as a separate field — it
        // is NOT part of the model name. Append it even when title_ro won (it usually
        // lacks the trim) so e.g. "Kia Carnival" becomes "Kia Carnival Prestige".
        if ($subModel !== '' && stripos($title, $subModel) === false) {
            $title = trim($title . ' ' . $subModel);
        }
        // OpenLane's title is "Make Model Trim - Fuel - Gearbox - hp - km": the
        // part after the first " - " duplicates what car-meta already shows, so
        // keep only the model+trim head for the <h3>.
        if (($c['source'] ?? '') === 'openlane' && strpos($title, ' - ') !== false) {
            $title = trim(explode(' - ', $title)[0]);
        }
        // eCarsTrade models carry engine/spec noise — keep a short clean title.
        if (($c['source'] ?? '') === 'ecarstrade') {
            $title = parsing_card_title($c);
        }
        // Encar (Korea): add the price-band markup so the card shows the marked
        // price (e.g. 9000 → 9300). Other sources show the raw final price.
        $priceDisplay = (float)($c['price_final_eur'] ?? 0);
        if (($c['source'] ?? '') === 'encar' && $priceDisplay > 0) {
            $priceDisplay = parsing_kr_marked_price($db, $prefx, $priceDisplay);
        }
        $priceFinal = $priceDisplay > 0
            ? number_format($priceDisplay, 0, '.', ' ') . ' €'
            : $t['price_loading'];
        // OpenLane auction end (BatchEndDate) → live countdown on the card.
        // The date comes WITHOUT a timezone ("2026-06-08T11:15:00") and is in
        // OpenLane's local time (Belgium, Europe/Brussels). Resolve it to an
        // absolute epoch (ms) here so the browser's own timezone can't shift it.
        $auctionEndTs = 0;
        if (($c['source'] ?? '') === 'openlane' && !empty($c['raw_data'])) {
            $rd = json_decode($c['raw_data'], true) ?: [];
            $rdItem = (!empty($rd['BatchEndDate']) || !empty($rd['CarId'])) ? $rd : ($rd['raw_data'] ?? $rd);
            $endStr = (string)($rdItem['BatchEndDate'] ?? $rdItem['EndDate'] ?? '');
            if ($endStr !== '') {
                try {
                    $dt = new DateTime($endStr, new DateTimeZone('Europe/Brussels'));
                    $auctionEndTs = $dt->getTimestamp() * 1000; // ms for JS
                } catch (\Throwable $e) { $auctionEndTs = 0; }
            }
        }
        // eCarsTrade stores auction_end as ISO UTC (data-utc-time="...Z"), which is
        // already correct — no offset. (Verified: raw "Z" matches the live site.)
        if (($c['source'] ?? '') === 'ecarstrade' && !empty($c['raw_data'])) {
            $rd = json_decode($c['raw_data'], true) ?: [];
            $rdItem = !empty($rd['auction_end']) ? $rd : ($rd['raw_data'] ?? $rd);
            $endStr = (string)($rdItem['auction_end'] ?? '');
            if ($endStr !== '') {
                try {
                    $dt = new DateTime($endStr);
                    $auctionEndTs = $dt->getTimestamp() * 1000; // UTC, ms for JS
                } catch (\Throwable $e) { $auctionEndTs = 0; }
            }
        }
        $fromLabel = $c['filter_name']
            ? $t['from_filter'].' '.htmlspecialchars($c['filter_name'])
            : $t['from_direct_link'];

        $rtrn .= '<div class="car-card"
            data-car-id="'.(int)$c['id'].'"
            data-found-at="'.(int)strtotime((string)($c['found_at'] ?? '')).'"
            data-brand="'.htmlspecialchars(trim((string)($c['brand'] ?? '')), ENT_QUOTES).'"
            data-model="'.htmlspecialchars(trim((string)($c['model'] ?? '')), ENT_QUOTES).'"
            data-fuel="'.htmlspecialchars((string)($c['fuel_type'] ?? ''), ENT_QUOTES).'"
            data-gear="'.htmlspecialchars((string)($c['gearbox'] ?? ''), ENT_QUOTES).'"
            data-year="'.(int)($c['year'] ?? 0).'"
            data-km="'.(int)($c['km'] ?? 0).'"
            data-source="'.htmlspecialchars((string)($c['source'] ?? ''), ENT_QUOTES).'"
            data-capacity="'.(int)($c['engine_volume'] ?? 0).'"
            data-capacity-display="'.(int)round(((float)parsing_engine_liters($c)) * 1000).'"
            data-price-eur="'.(int)($c['price_eur'] ?? 0).'"
            data-price="'.(int)($c['price_final_eur'] ?? 0).'">
            <div class="car-slider" data-images=\''.$sliderJson.'\' data-gallery=\''.$galleryJson.'\' data-index="0">
                <img src="'.htmlspecialchars($imgUrls[0]).'" alt="" class="car-cover" loading="lazy" id="cover-'.(int)$c['id'].'" onload="this.classList.add(\'is-loaded\')" onerror="this.classList.add(\'is-loaded\')" onclick="parsingOpenGallery('.(int)$c['id'].')">
                <button class="btn-favorite" onclick="parsingFavorite('.(int)$c['id'].')" title="'.$t['btn_favorite'].'"><img src="/content/admin/page/parsing/media-parsing/favorite.png" alt=""></button>
                '.(!$noImage && $totalImgs > 1 ? '
                <button class="slider-nav slider-prev" onclick="parsingSliderNav(this, -1)" type="button"><img src="/content/admin/page/parsing/media-parsing/left.svg" alt=""></button>
                <button class="slider-nav slider-next" onclick="parsingSliderNav(this, 1)" type="button"><img src="/content/admin/page/parsing/media-parsing/right.svg" alt=""></button>
                <div class="slider-counter"><span class="slider-current">1</span>/'.count($sliderImgs).'</div>
                ' : '').'
                '.($noImage ? '<button class="btn-redownload" onclick="parsingRedownloadImages('.(int)$c['id'].')" title="Re-descarcă imagini">↺</button>' : '').'
            </div>
            <div class="car-info">
                <div class="car-source">
                    '.($c['source'] === 'encar' ? '<img src="/content/admin/page/parsing/media-parsing/encar-logo.webp" alt="Encar" class="source-logo">' :
                      ($c['source'] === 'ecarstrade' ? '<img src="/content/admin/page/parsing/media-parsing/ecarstrade-logo.svg" alt="e-CarsTrade" class="source-logo source-logo-ecarstrade">' :
                      ($c['source'] === 'openlane' ? '<img src="/content/admin/page/parsing/media-parsing/openlane-logo.svg" alt="OpenLane" class="source-logo source-logo-openlane">' :
                      ($c['source'] === 'auto1' ? '<img src="/content/admin/page/parsing/media-parsing/auto1.png" alt="AUTO1" class="source-logo source-logo-auto1">' :
                      strtoupper($c['source']))))).'
                </div>
                <h3>'.htmlspecialchars($title).'</h3>
                <div class="car-meta" data-seats-label="'.htmlspecialchars($t['card_seats'] ?? 'locuri', ENT_QUOTES).'">
                    '.($c['year'] ? $c['year'] . ' · ' : '').'
                    '.($c['km'] ? number_format($c['km'], 0, '.', ' ') . ' km · ' : '').'
                    '.htmlspecialchars($fuelLabels[parsing_fuel_code($c['fuel_type'] ?? '')] ?? ($c['fuel_type'] ?? '')).'
                    <span class="car-meta-cc">'.(($_l = parsing_engine_liters($c)) !== '' ? ' ' . $_l . 'L' : '').'</span>
                    <span class="car-meta-gear">'.($c['gearbox'] ? ' · ' . htmlspecialchars($gearLabels[parsing_gear_code($c['gearbox'])] ?? $c['gearbox']) : '').'</span>
                    <span class="car-meta-seats">'.(!empty($c['seats']) ? ' · ' . (int)$c['seats'] . ' ' . htmlspecialchars($t['card_seats'] ?? 'locuri') : '').'</span>
                </div>
                '.($auctionEndTs > 0
                    ? '<div class="ol-countdown" data-end-ts="'.$auctionEndTs.'"><span class="ol-cd-label">'.$t['auction_ends'].'</span> <span class="ol-cd-time">…</span></div>'
                    : '<div class="ol-countdown ol-countdown-placeholder" aria-hidden="true"></div>').'
                <div class="car-price">
                    <span class="car-price-val">'.$priceFinal.'</span>
                    <button type="button" class="car-price-edit" title="'.($t['edit_price'] ?? 'Editează prețul').'" onclick="parsingEditPrice('.(int)$c['id'].', this)">✎</button>
                    <span class="car-price-md"></span>
                </div>
                <div class="car-actions">
                    <div class="publish-grid">
                        <button class="btn-publish-icon" onclick="parsingPublish('.(int)$c['id'].', \'sauto\')" title="'.$t['btn_publish_sauto'].'">
                            <img src="/content/admin/page/parsing/media-parsing/sauto-logo.svg" alt="sauto.md">
                        </button>
                        '.(!empty($c['source_url']) ? '<a class="btn-publish-icon btn-original-link" href="'.htmlspecialchars($c['source_url']).'" target="_blank" rel="noopener" title="'.$t['link_original'].'">
                            <img src="/content/admin/page/parsing/media-parsing/link.png" alt="">
                            <span>'.$t['link_original'].'</span>
                        </a>' : '').'
                    </div>
                    <div class="publish-grid action-grid">
                        <button class="btn-publish-icon" onclick="parsingShowCharacteristics('.(int)$c['id'].')" title="'.$t['btn_characteristics'].'">
                            <img src="/content/admin/page/parsing/media-parsing/characteristics.png" alt="">
                            <span>'.$t['btn_characteristics'].'</span>
                        </button>
                        <button class="btn-publish-icon" onclick="parsingEditCar('.(int)$c['id'].')" title="'.$t['btn_edit_car'].'">
                            <img src="/content/admin/page/parsing/media-parsing/edit.png" alt="">
                            <span>'.$t['btn_edit_car'].'</span>
                        </button>
                    </div>
                    '.($c['source'] === 'encar' ? '<button class="btn-report" onclick="parsingTestReport('.(int)$c['id'].')" title="'.($t['btn_report'] ?? 'Raport').' Encar">
                        <span>'.($t['btn_report'] ?? 'Raport').'</span>
                    </button>' : ($c['source'] === 'openlane' ? '<button class="btn-report" onclick="parsingOpenlaneReport('.(int)$c['id'].')" title="'.($t['btn_report'] ?? 'Raport').' OpenLane">
                        <span>'.($t['btn_report'] ?? 'Raport').'</span>
                    </button>' : ($c['source'] === 'ecarstrade' ? '<button class="btn-report" onclick="parsingEcarstradeReport('.(int)$c['id'].')" title="'.$t['btn_equipment'].' eCarsTrade">
                        <span>'.$t['btn_equipment'].'</span>
                    </button>' : ($c['source'] === 'auto1' ? '<button class="btn-report" onclick="parsingAuto1Report('.(int)$c['id'].')" title="'.($t['btn_report'] ?? 'Raport').' AUTO1">
                        <span>'.($t['btn_report'] ?? 'Raport').'</span>
                    </button>' : '')))).'
                    <button class="btn-reject" onclick="parsingReject('.(int)$c['id'].')">'.$t['btn_reject'].'</button>
                </div>
            </div>
        </div>';
    }
}

$rtrn .= '
    </div>
    '.parsing_render_pagination($totalCount).'
</div>

<div id="parsing-gallery-modal" class="parsing-modal" style="display:none;">
    <div class="parsing-gallery-content">
        <div class="gallery-img-wrap">
            <button class="gallery-close" onclick="parsingCloseGallery()">✕</button>
            <button class="gallery-nav gallery-prev" onclick="parsingGalleryNav(-1)" type="button"><img src="/content/admin/page/parsing/media-parsing/left.svg" alt=""></button>
            <img id="gallery-image" src="" alt="">
            <button class="gallery-nav gallery-next" onclick="parsingGalleryNav(1)" type="button"><img src="/content/admin/page/parsing/media-parsing/right.svg" alt=""></button>
            <div class="gallery-counter"><span id="gallery-current">1</span>/<span id="gallery-total">0</span></div>
            <div id="gallery-loading">Се загружают...</div>
        </div>
    </div>
</div>

<!-- Characteristics / Edit modal -->
<div id="parsing-car-modal" class="parsing-modal" style="display:none;">
    <div class="parsing-modal-content car-modal-content">
        <div class="modal-header">
            <h2 id="car-modal-title">'.$t['modal_characteristics_title'].'</h2>
            <button class="modal-close" onclick="parsingCloseCarModal()">✕</button>
        </div>
        <div id="car-modal-body" class="car-modal-body"></div>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="parsingCloseCarModal()">'.$t['btn_close'].'</button>
            <button id="car-modal-save" class="btn-primary" style="display:none;" onclick="parsingSaveCarEdits()">'.$t['btn_save'].'</button>
        </div>
    </div>
</div>

<script>
    window.PARSING_LANG = '.json_encode($t, JSON_UNESCAPED_UNICODE).';
    window.ADMIN_DIR = '.json_encode($admin_dir ?? 'adm').';
    window.PARSING_PRICING = '.json_encode(parsing_pricing_payload($db, $prefx), JSON_UNESCAPED_UNICODE).';
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="/content/admin/page/parsing/parsing.js?v='.filemtime(_ADM_PAGE.'/parsing/parsing.js').'"></script>
';

echo $rtrn;
