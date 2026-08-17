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

$cars = [];
$totalCount = 0;
try {
    // Shared catalog filter (searches the whole DB, not just loaded rows).
    include_once _ADM_PAGE.'/parsing/parsing_filter_where.php';
    include_once _ADM_PAGE.'/parsing/parsing_pagination.php';
    $flt = parsing_catalog_filter_where('pc.', $db, $prefx);

    $where = 'pc.status = "favorite"'.$flt['sql'];

    $cntStmt = $db->prepare('SELECT COUNT(*) FROM '.$prefx.'_parsing_cars pc WHERE '.$where);
    $cntStmt->execute($flt['params']);
    $totalCount = (int)$cntStmt->fetchColumn();

    $pageSize = parsing_page_size();
    $offset   = (parsing_current_page() - 1) * $pageSize;

    // brand/model from car_list (single canonical list) via sauto_br/mo, fallback
    // to raw source name — same as ctlg/published so display + filtering match.
    $stmt = $db->prepare('SELECT pc.*, pf.name AS filter_name,
            COALESCE(NULLIF(cl.br_nm, ""), pc.brand) AS brand,
            COALESCE(NULLIF(cl.mo_nm, ""), pc.model) AS model
        FROM '.$prefx.'_parsing_cars pc
        LEFT JOIN '.$prefx.'_parsing_filters pf ON pf.id = pc.filter_id
        LEFT JOIN '.$prefx.'_car_list cl ON cl.br = pc.sauto_br AND cl.mo = pc.sauto_mo
        WHERE '.$where.'
        ORDER BY DATE_FORMAT(pc.found_at, "%Y-%m-%d %H:%i") DESC,
                 pc.price_final_eur IS NULL, pc.price_final_eur ASC,
                 pc.id DESC
        LIMIT '.$pageSize.' OFFSET '.$offset);
    $stmt->execute($flt['params']);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$rtrn = '
<link rel="stylesheet" href="/content/admin/page/parsing/parsing.css?v='.filemtime(_ADM_PAGE.'/parsing/parsing.css').'">
<div id="parsing-container">
    <div class="parsing-header">
        <h1>'.$t['page_favorites'].'</h1>
        <span class="counter">'.$totalCount.' '.$t['favorites_count_label'].'</span>
    </div>

    <div class="parsing-tabs">
        <a href="/'.$admin_dir.'/parsing/filters" class="tab">'.$t['tab_filters'].'</a>
        <a href="/'.$admin_dir.'/parsing/ctlg" class="tab">'.$t['tab_ctlg'].'</a>
        <a href="/'.$admin_dir.'/parsing/published" class="tab">'.$t['tab_published'].'</a>
        <a href="/'.$admin_dir.'/parsing/settings" class="tab">'.$t['tab_settings'].'</a>
        <a href="/'.$admin_dir.'/parsing/favorites" class="tab tab-favorites active"><img src="/content/admin/page/parsing/media-parsing/favorite.png" alt=""> '.$t['tab_favorites'].'</a>
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

// Shared catalog filter bar. Counts reflect this page's cars (favorites).
$pf_status_filter = ['favorite'];
ob_start();
include _ADM_PAGE.'/parsing/parsing_filter_bar.php';
$rtrn .= ob_get_clean();

$rtrn .= '<div class="proposed-grid">';

if (empty($cars)) {
    $rtrn .= '<div class="empty-state">'.$t['empty_favorites'].'</div>';
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
        // OpenLane CDN blocks hotlinking — route through our proxy. Card uses
        // sz=card (proxy serves ~600px); the big gallery keeps full resolution.
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
        $sliderImgs = array_slice($cardUrls, 0, 10);
        $totalImgs  = $noImage ? 0 : count($cardUrls);
        $sliderJson = htmlspecialchars(json_encode($sliderImgs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        // Full saved list for the gallery (no 20 cap); the gallery also fetches
        // the complete photo set live from the source when opened.
        $galleryJson = htmlspecialchars(json_encode(array_values($galleryUrls), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        $imgUrls = $cardUrls; // cover img below uses $imgUrls[0]

        // Trim (complectație) shown right after the model, as a separate field.
        $subModel = trim((string)($c['car_sub_model'] ?? ''));
        $title = trim(($c['brand'] ?? '') . ' ' . ($c['model'] ?? '')
            . ($subModel !== '' ? ' ' . $subModel : '') . ' ' . ($c['year'] ?? ''));
        if (!empty($c['title_ro'])) $title = $c['title_ro'];
        // OpenLane's title is "Make Model Trim - Fuel - Gearbox - hp - km": the
        // part after the first " - " duplicates car-meta, so keep only the head.
        if (($c['source'] ?? '') === 'openlane' && strpos($title, ' - ') !== false) {
            $title = trim(explode(' - ', $title)[0]);
        }
        if (($c['source'] ?? '') === 'ecarstrade') {
            $title = parsing_card_title($c);
        }
        // Encar (Korea) / AutoTrader (America): show the price-band marked price.
        $priceDisplay = (float)($c['price_final_eur'] ?? 0);
        if ($priceDisplay > 0) {
            if (($c['source'] ?? '') === 'encar')          $priceDisplay = parsing_kr_marked_price($db, $prefx, $priceDisplay);
            elseif (($c['source'] ?? '') === 'autotrader') $priceDisplay = parsing_us_marked_price($db, $prefx, $priceDisplay);
        }
        $priceFinal = $priceDisplay > 0
            ? number_format($priceDisplay, 0, '.', ' ') . ' €'
            : $t['price_loading'];
        // Auction countdown: OpenLane local time (Brussels), eCarsTrade ISO UTC.
        $auctionEndTs = 0;
        if (!empty($c['raw_data'])) {
            $rd = json_decode($c['raw_data'], true) ?: [];
            if (($c['source'] ?? '') === 'openlane') {
                $rdItem = (!empty($rd['BatchEndDate']) || !empty($rd['CarId'])) ? $rd : ($rd['raw_data'] ?? $rd);
                $endStr = (string)($rdItem['BatchEndDate'] ?? $rdItem['EndDate'] ?? '');
                if ($endStr !== '') {
                    try { $auctionEndTs = (new DateTime($endStr, new DateTimeZone('Europe/Brussels')))->getTimestamp() * 1000; }
                    catch (\Throwable $e) { $auctionEndTs = 0; }
                }
            } elseif (($c['source'] ?? '') === 'ecarstrade') {
                // eCarsTrade stores auction_end as ISO UTC ("...Z") — already correct, no offset.
                $rdItem = !empty($rd['auction_end']) ? $rd : ($rd['raw_data'] ?? $rd);
                $endStr = (string)($rdItem['auction_end'] ?? '');
                if ($endStr !== '') {
                    try { $auctionEndTs = (new DateTime($endStr))->getTimestamp() * 1000; }
                    catch (\Throwable $e) { $auctionEndTs = 0; }
                }
            }
        }

        $rtrn .= '<div class="car-card"
            data-car-id="'.(int)$c['id'].'"
            data-brand="'.htmlspecialchars(trim((string)($c['brand'] ?? '')), ENT_QUOTES).'"
            data-model="'.htmlspecialchars(trim((string)($c['model'] ?? '')), ENT_QUOTES).'"
            data-fuel="'.htmlspecialchars((string)($c['fuel_type'] ?? ''), ENT_QUOTES).'"
            data-gear="'.htmlspecialchars((string)($c['gearbox'] ?? ''), ENT_QUOTES).'"
            data-year="'.(int)($c['year'] ?? 0).'"
            data-source="'.htmlspecialchars((string)($c['source'] ?? ''), ENT_QUOTES).'"
            data-capacity="'.(int)($c['engine_volume'] ?? 0).'"
            data-capacity-display="'.(int)round(((float)parsing_engine_liters($c)) * 1000).'"
            data-price-eur="'.(int)($c['price_eur'] ?? 0).'"
            data-price="'.(int)($c['price_final_eur'] ?? 0).'">
            <div class="car-slider" data-images=\''.$sliderJson.'\' data-gallery=\''.$galleryJson.'\' data-index="0">
                <img src="'.htmlspecialchars($imgUrls[0]).'" alt="" class="car-cover" loading="lazy" id="cover-'.(int)$c['id'].'" onload="this.classList.add(\'is-loaded\')" onerror="this.classList.add(\'is-loaded\')" onclick="parsingOpenGallery('.(int)$c['id'].')">
                <button class="btn-favorite is-favorite" onclick="parsingUnfavorite('.(int)$c['id'].')" title="'.$t['btn_unfavorite'].'"><img src="/content/admin/page/parsing/media-parsing/favorite.png" alt=""></button>
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
                      ($c['source'] === 'autotrader' ? '<img src="/content/admin/page/parsing/media-parsing/logo-autotrader.svg" alt="AutoTrader" class="source-logo source-logo-autotrader">' :
                      strtoupper($c['source'])))))).'
                </div>
                <h3>'.htmlspecialchars($title).'</h3>
                <div class="car-meta" data-seats-label="'.htmlspecialchars($t['card_seats'] ?? 'locuri', ENT_QUOTES).'">
                    '.($c['year'] ? $c['year'] . ' · ' : '').'
                    '.($c['km'] ? number_format($c['km'], 0, '.', ' ') . ' km · ' : '').'
                    '.htmlspecialchars($fuelLabels[parsing_fuel_code($c['fuel_type'] ?? '')] ?? ($c['fuel_type'] ?? '')).'
                    <span class="car-meta-cc">'.(($_l = parsing_engine_liters($c)) !== '' ? ' ' . $_l . 'L' : '').'</span>'.'
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
<script src="/content/admin/page/parsing/parsing.js?v='.filemtime(_ADM_PAGE.'/parsing/parsing.js').'"></script>
';

echo $rtrn;
