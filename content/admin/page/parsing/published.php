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

    // Only cars that were actually published to sauto: status published, OR
    // status unavailable BUT still linked to a sauto ad (car_ctlg_id set). This
    // excludes cars that went straight proposed -> unavailable without ever being
    // published (those have no car_ctlg_id and don't belong on this page).
    $where = '(pc.status = "published" OR (pc.status = "unavailable" AND pc.car_ctlg_id IS NOT NULL AND pc.car_ctlg_id > 0))'.$flt['sql'];

    $cntStmt = $db->prepare('SELECT COUNT(*) FROM '.$prefx.'_parsing_cars pc WHERE '.$where);
    $cntStmt->execute($flt['params']);
    $totalCount = (int)$cntStmt->fetchColumn();

    $pageSize = parsing_page_size();
    $offset   = (parsing_current_page() - 1) * $pageSize;

    // Price sorting. cc.prc is the MD landed price written by ParsingPublisher when
    // the car went live on sauto, so it is the same number the public page shows.
    // Cars without a linked ad (or with prc 0) sink to the bottom either way.
    // Whitelisted map, never interpolated from the request.
    $sortKeys = [
        ''        => 'pc.published_at DESC',
        'md_asc'  => 'CASE WHEN COALESCE(cc.prc, 0) > 0 THEN 0 ELSE 1 END, cc.prc ASC,  pc.published_at DESC',
        'md_desc' => 'CASE WHEN COALESCE(cc.prc, 0) > 0 THEN 0 ELSE 1 END, cc.prc DESC, pc.published_at DESC',
    ];
    $sortKey = isset($_GET['f_sort']) ? (string)$_GET['f_sort'] : '';
    $orderBy = $sortKeys[$sortKey] ?? $sortKeys[''];

    // Join car_ctlg to know if the sauto ad is hidden (vis = 0). Hidden ones
    // are pushed to the end and shown dimmed/red in the list. brand/model come from
    // car_list (single canonical sauto list) via sauto_br/mo, falling back to the
    // raw source name — same as the ctlg page and the filter dropdown, so the
    // displayed names and filtering stay consistent.
    // Brand/model come from car_list, the single canonical sauto list. Preference:
    // (1) the PUBLISHED catalog row''s own br/mo (cc → clc) — the definitive name
    // for a car that already lives on sauto; (2) the parsing sauto_br/mo mapping
    // (cl); (3) the raw source name. This keeps published showing exactly one
    // spelling per brand even for cars published before the backfill.
    $stmt = $db->prepare('SELECT pc.*, cc.vis AS ctlg_vis, cc.`999_id` AS ctlg_999_id,
            COALESCE(NULLIF(clc.br_nm, ""), NULLIF(cl.br_nm, ""), pc.brand) AS brand,
            COALESCE(NULLIF(clc.mo_nm, ""), NULLIF(cl.mo_nm, ""), pc.model) AS model
        FROM '.$prefx.'_parsing_cars pc
        LEFT JOIN '.$prefx.'_car_ctlg cc ON cc.id = pc.car_ctlg_id
        LEFT JOIN '.$prefx.'_car_list clc ON clc.br = cc.br AND clc.mo = cc.mo
        LEFT JOIN '.$prefx.'_car_list cl ON cl.br = pc.sauto_br AND cl.mo = pc.sauto_mo
        WHERE '.$where.'
        ORDER BY '.$orderBy.'
        LIMIT '.$pageSize.' OFFSET '.$offset);
    $stmt->execute($flt['params']);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

// Cross-post schedule status per car_ctlg_id, per platform. Lets the 999/TG/FB
// buttons show three states: published (green), pending/scheduled (yellow),
// error/failed (red). Batched: one query per table for the whole page.
// Priority when several rows exist for the same car+platform: error > pending >
// published (operator most needs to see a problem, then a wait, then done).
$scheduleStatus = []; // [ctlgId][platform] => 'error' | 'pending' | 'published'
$ctlgIds = array_values(array_filter(array_map(fn($c) => (int)($c['car_ctlg_id'] ?? 0), $cars)));
if (!empty($ctlgIds)) {
    $in = implode(',', array_fill(0, count($ctlgIds), '?'));
    $tables = [
        '999'      => $prefx.'_sauto_personal_schedules',
        'telegram' => $prefx.'_scheduled_telegram_posts',
        'facebook' => $prefx.'_scheduled_facebook_posts',
    ];
    $rank = ['error' => 3, 'pending' => 2, 'published' => 1];
    foreach ($tables as $platform => $table) {
        try {
            $q = $db->prepare('SELECT car_id, status FROM '.$table.' WHERE car_id IN ('.$in.')');
            $q->execute($ctlgIds);
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cid = (int)$row['car_id'];
                $st  = (string)$row['status'];
                if ($st === 'failed' || $st === 'postponed') { $state = 'error'; }
                elseif ($st === 'pending') { $state = 'pending'; }
                elseif ($st === 'published') { $state = 'published'; }
                else { continue; } // cancelled / unknown — ignore
                $cur = $scheduleStatus[$cid][$platform] ?? null;
                if ($cur === null || $rank[$state] > $rank[$cur]) {
                    $scheduleStatus[$cid][$platform] = $state;
                }
            }
        } catch (Exception $e) {
            // Table may be missing — skip that platform.
        }
    }
}

// Per-channel published counts — ONLY when viewing a single saved filter (URL has
// filter_id), so the header shows that filter's breakdown (e.g. via the "999"
// button). Without a filter it would just count the whole catalog, which is noise,
// so we skip the line entirely. The count must NOT be narrowed by the crosspost999
// marker (that flag is for the button's list, not the site total), so build a
// filter-only WHERE here instead of reusing $where.
$statLine = '';
$statFilterId = isset($_GET['filter_id']) && ctype_digit((string)$_GET['filter_id']) ? (int)$_GET['filter_id'] : 0;
if ($statFilterId > 0) {
    $stat = ['site' => 0, '999' => 0, 'fb' => 0, 'tg' => 0];
    // Same "published to sauto" base as the list, scoped to this filter only.
    $sWhere = '(pc.status = "published" OR (pc.status = "unavailable" AND pc.car_ctlg_id IS NOT NULL AND pc.car_ctlg_id > 0))
               AND pc.filter_id = ? AND pc.car_ctlg_id > 0';
    try {
        $q = $db->prepare('SELECT COUNT(*) FROM '.$prefx.'_parsing_cars pc
            JOIN '.$prefx.'_car_ctlg cc ON cc.id = pc.car_ctlg_id WHERE '.$sWhere);
        $q->execute([$statFilterId]);
        $stat['site'] = (int)$q->fetchColumn();

        $chTables = ['999' => $prefx.'_sauto_personal_schedules',
                     'fb'  => $prefx.'_scheduled_facebook_posts',
                     'tg'  => $prefx.'_scheduled_telegram_posts'];
        foreach ($chTables as $k => $tbl) {
            try {
                $q = $db->prepare('SELECT COUNT(DISTINCT pc.car_ctlg_id)
                    FROM '.$prefx.'_parsing_cars pc
                    JOIN '.$tbl.' s ON s.car_id = pc.car_ctlg_id AND s.status = "published"
                    WHERE '.$sWhere);
                $q->execute([$statFilterId]);
                $stat[$k] = (int)$q->fetchColumn();
            } catch (Exception $e) { /* table missing — leave 0 */ }
        }
    } catch (Exception $e) { /* keep zeros */ }

    $statLine = '<span class="fstat-live">'.$stat['site'].' '.($t['stat_on_site'] ?? 'на сайте').'</span>'
        . '<span class="fstat-sep">/</span><span class="fstat-999">'.$stat['999'].' '.($t['stat_on_999'] ?? 'на 999.md').'</span>'
        . '<span class="fstat-sep">/</span><span class="fstat-fb">'.$stat['fb'].' FB</span>'
        . '<span class="fstat-sep">/</span><span class="fstat-tg">'.$stat['tg'].' TG</span>';
}

$rtrn = '
<link rel="stylesheet" href="/content/admin/page/parsing/parsing.css?v='.filemtime(_ADM_PAGE.'/parsing/parsing.css').'">
<div id="parsing-container">
    <div class="parsing-header">
        <h1>'.$t['page_published'].'</h1>
        <span class="counter">'.$totalCount.' '.$t['published_count_label'].'</span>
        <div class="pub-link-search">
            <input type="text" id="pcf-link-input" class="pcf-field"
                   placeholder="'.htmlspecialchars($t['locate_placeholder'] ?? 'link sauto.md sau 999.md').'"
                   onkeydown="if(event.key===\'Enter\'){event.preventDefault();parsingLocateByLink();}">
            <button type="button" class="pcf-apply" onclick="parsingLocateByLink()">'.($t['filter_search'] ?? 'Caută').'</button>
        </div>
    </div>
    <div id="pcf-link-msg" class="pcf-link-msg"></div>

    <div class="parsing-tabs">
        <a href="/'.$admin_dir.'/parsing/filters" class="tab">'.$t['tab_filters'].'</a>
        <a href="/'.$admin_dir.'/parsing/ctlg" class="tab">'.$t['tab_ctlg'].'</a>
        <a href="/'.$admin_dir.'/parsing/published" class="tab active">'.$t['tab_published'].'</a>
        <a href="/'.$admin_dir.'/parsing/settings" class="tab">'.$t['tab_settings'].'</a>
        <a href="/'.$admin_dir.'/parsing/favorites" class="tab tab-favorites"><img src="/content/admin/page/parsing/media-parsing/favorite.png" alt=""> '.$t['tab_favorites'].'</a>
        <button type="button" class="tab tab-999-stats" onclick="parsingShow999Stats()">'.($t['btn_999_stats'] ?? 'Publicări 999').'</button>
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

// Shared catalog filter bar. Counts must match the list EXACTLY: only cars
// actually published to sauto (published, or unavailable-but-still-linked) —
// same condition as the list $where above.
$pf_extra_where = 'pc.status = "published" OR (pc.status = "unavailable" AND pc.car_ctlg_id IS NOT NULL AND pc.car_ctlg_id > 0)';
$pf_sort = true;
ob_start();
include _ADM_PAGE.'/parsing/parsing_filter_bar.php';
$rtrn .= ob_get_clean();

// Per-filter cross-post summary, on its own row right under the catalog filter bar.
if ($statLine !== '') {
    $rtrn .= '<div class="parsing-chan-stats">'.$statLine.'</div>';
}

$rtrn .= '<div class="proposed-grid">';

function crossPostBtn(string $target, array $c, string $titleKey, string $logo, string $alt, array $t, string $schedState = ''): string {
    $flagCol = ['999' => 'published_999', 'facebook' => 'published_fb', 'telegram' => 'published_tg'][$target];
    $img = '<img src="/content/admin/page/parsing/media-parsing/'.$logo.'" alt="'.$alt.'">';
    $ctlgId = (int)($c['car_ctlg_id'] ?? 0);

    // 999 — authoritative proof of publication: car_ctlg.999_id is set only AFTER
    // the ad is live. If present → GREEN clickable link to the 999.md ad, even if
    // a stale pending/error schedule row lingers.
    if ($target === '999' && !empty($c['ctlg_999_id'])) {
        $url = 'https://999.md/'.rawurlencode((string)$c['ctlg_999_id']);
        return '<a class="btn-publish-icon is-published has-link" href="'.$url.'" target="_blank" rel="noopener" title="'.($t['view_on_999'] ?? ('Vezi anunțul pe '.$alt)).'">'
             . $img . '<span class="pub-check">✓</span></a>';
    }

    // The SCHEDULE state wins over the published_xxx flag (that flag is set the
    // moment a cross-post is queued, BEFORE the cron actually posts).
    // Priority: error (most urgent) > pending (waiting) > published (done).

    // RED — error (failed / postponed). NOT clickable (cron retries on its own).
    if ($schedState === 'error') {
        $errTitle = str_replace('%s', $alt, $t['xpost_failed_on'] ?? 'Eroare la publicarea pe %s');
        return '<button class="btn-publish-icon is-failed" disabled title="'.htmlspecialchars($errTitle, ENT_QUOTES).'">'
             . $img . '<span class="pub-fail">✗</span></button>';
    }
    // YELLOW — pending (scheduled, not posted yet). NOT clickable (already queued).
    if ($schedState === 'pending') {
        $pendTitle = str_replace('%s', $alt, $t['xpost_pending_on'] ?? 'Programat pe %s — în așteptare');
        return '<button class="btn-publish-icon is-pending" disabled title="'.htmlspecialchars($pendTitle, ENT_QUOTES).'">'
             . $img . '<span class="pub-clock"></span></button>';
    }

    // GREEN — published (schedule says published, or the flag is set with no
    // pending/error schedule above). FB/TG have no stored url → inert ✓ badge.
    if ($schedState === 'published' || !empty($c[$flagCol])) {
        $alreadyTitle = str_replace('%s', $alt, $t['already_published_on'] ?? 'Deja publicat pe %s');
        return '<button class="btn-publish-icon is-published" disabled title="'.htmlspecialchars($alreadyTitle, ENT_QUOTES).'">'
             . $img . '<span class="pub-check">✓</span></button>';
    }

    // GRAY — nothing yet. Plain publish button.
    return '<button class="btn-publish-icon" onclick="parsingCrossPost(\''.$target.'\', '.$ctlgId.')" title="'.($t[$titleKey] ?? $alt).'">'
         . $img . '</button>';
}

if (empty($cars)) {
    $rtrn .= '<div class="empty-state">'.$t['empty_published'].'</div>';
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
        // Encar (Korea): show the price-band marked price (e.g. 9000 → 9300).
        $priceDisplay = (float)($c['price_final_eur'] ?? 0);
        if (($c['source'] ?? '') === 'encar' && $priceDisplay > 0) {
            $priceDisplay = parsing_kr_marked_price($db, $prefx, $priceDisplay);
        }
        $priceFinal = $priceDisplay > 0
            ? number_format($priceDisplay, 0, '.', ' ') . ' €'
            : '-';
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

        // Status badge reflects the SOURCE auction timer, not the sauto status:
        // auction still running → "Activ" (green); expired/sold → "Vândut" (red).
        // JS flips it live from data-end-ts (same timestamp as the countdown). The
        // server renders the initial state from the DB status + the parsed end time.
        $isUnavailable = ($c['status'] === 'unavailable');
        $statusBadge = $isUnavailable
            ? '<span class="badge badge-off status-timer" data-end-ts="'.$auctionEndTs.'">'.$t['status_unavailable'].'</span>'
            : '<span class="badge badge-on status-timer" data-end-ts="'.$auctionEndTs.'">'.$t['status_published'].'</span>';

        // Link to the live sauto.md ad if we have its catalog id.
        $sautoLink = '';
        if (!empty($c['car_ctlg_id'])) {
            // Return to THIS exact published view after saving — including its current
            // filters (filter_id, crosspost999, …), not a bare /parsing/published.
            $returnUrl = '/'.$admin_dir.'/parsing/published'
                . (!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');
            $sautoLink = '<a class="btn-published-view" href="/'.$admin_dir.'/ordercars/detail?id='.(int)$c['car_ctlg_id']
                . '&from=published&return='.rawurlencode($returnUrl).'">'
                . ($t['action_view_sauto'] ?? 'Vezi pe sauto') . '</a>';
        }

        // "House" icon → the car's public page on sauto.md (same as the ordercars
        // catalog). Only for cars actually published (car_ctlg_id present).
        $publicLink = '';
        if (!empty($c['car_ctlg_id'])) {
            $siteBase = (isset($site_url) && $site_url !== '') ? $site_url : 'https://www.sauto.md';
            $lang = $_COOKIE['lang'] ?? 'ro';
            $publicUrl = $siteBase.'/'.$lang.'/ordercars/'.(int)$c['car_ctlg_id'];
            $publicLink = '<a class="btn-publish-icon btn-sauto-page" href="'.htmlspecialchars($publicUrl).'" target="_blank" rel="noopener" title="'.($t['to_item_page'] ?? 'Vezi pe sauto.md').'"><span class="ico-house"></span></a>';
        }

        // Report / equipment button (per source).
        $reportBtn = '';
        if ($c['source'] === 'encar') {
            $reportBtn = '<button class="btn-report" onclick="parsingTestReport('.(int)$c['id'].')" title="'.($t['btn_report'] ?? 'Raport').' Encar"><span>'.($t['btn_report'] ?? 'Raport').'</span></button>';
        } elseif ($c['source'] === 'openlane') {
            $reportBtn = '<button class="btn-report" onclick="parsingOpenlaneReport('.(int)$c['id'].')" title="'.($t['btn_report'] ?? 'Raport').' OpenLane"><span>'.($t['btn_report'] ?? 'Raport').'</span></button>';
        } elseif ($c['source'] === 'ecarstrade') {
            $reportBtn = '<button class="btn-report" onclick="parsingEcarstradeReport('.(int)$c['id'].')" title="'.$t['btn_equipment'].' eCarsTrade"><span>'.$t['btn_equipment'].'</span></button>';
        } elseif ($c['source'] === 'auto1') {
            $reportBtn = '<button class="btn-report" onclick="parsingAuto1Report('.(int)$c['id'].')" title="'.($t['btn_report'] ?? 'Raport').' AUTO1"><span>'.($t['btn_report'] ?? 'Raport').'</span></button>';
        }
        // House icon + Report on ONE row (like the FB/TG grid).
        $housePlusReport = ($publicLink !== '' || $reportBtn !== '')
            ? '<div class="house-report-row">'.$publicLink.$reportBtn.'</div>'
            : '';

        // Ad hidden on sauto (vis = 0) → dim the card red (pushed to the end by the query).
        $isHidden = isset($c['ctlg_vis']) && (string)$c['ctlg_vis'] === '0';
        $hiddenClass = $isHidden ? ' is-hidden-on-sauto' : '';

        $rtrn .= '<div class="car-card car-card-published'.$hiddenClass.'"
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
                '.$statusBadge.'
                '.($isHidden ? '<span class="hidden-on-sauto-badge">'.($t['hidden_on_sauto'] ?? 'Ascuns pe sauto').'</span>' : '').'
                '.(!$noImage && $totalImgs > 1 ? '
                <button class="slider-nav slider-prev" onclick="parsingSliderNav(this, -1)" type="button"><img src="/content/admin/page/parsing/media-parsing/left.svg" alt=""></button>
                <button class="slider-nav slider-next" onclick="parsingSliderNav(this, 1)" type="button"><img src="/content/admin/page/parsing/media-parsing/right.svg" alt=""></button>
                <div class="slider-counter"><span class="slider-current">1</span>/'.count($sliderImgs).'</div>
                ' : '').'
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
                    <span class="car-meta-cc">'.(($_l = parsing_engine_liters($c)) !== '' ? ' ' . $_l . 'L' : '').'</span>'.'
                    <span class="car-meta-gear">'.($c['gearbox'] ? ' · ' . htmlspecialchars($gearLabels[parsing_gear_code($c['gearbox'])] ?? $c['gearbox']) : '').'</span>
                    <span class="car-meta-seats">'.(!empty($c['seats']) ? ' · ' . (int)$c['seats'] . ' ' . htmlspecialchars($t['card_seats'] ?? 'locuri') : '').'</span>
                </div>
                '.($auctionEndTs > 0
                    ? '<div class="ol-countdown" data-end-ts="'.$auctionEndTs.'"><span class="ol-cd-label">'.$t['auction_ends'].'</span> <span class="ol-cd-time">…</span></div>'
                    : '').'
                <div class="car-price">'.$priceFinal.'<span class="car-price-md"></span></div>
                <div class="car-actions">
                    '.$sautoLink.'
                    '.$housePlusReport.'
                    '.(!empty($c['source_url']) ? '<a class="btn-publish-icon btn-original-link" href="'.htmlspecialchars($c['source_url']).'" target="_blank" rel="noopener" title="'.$t['link_original'].'">
                        <img src="/content/admin/page/parsing/media-parsing/link.png" alt="">
                        <span>'.$t['link_original'].'</span>
                    </a>' : '').'
                    <div class="publish-grid-2x2" data-ctlg="'.(int)($c['car_ctlg_id'] ?? 0).'">
                        '.crossPostBtn('facebook', $c, 'btn_publish_facebook', 'facebook-logo.svg', 'Facebook', $t, $scheduleStatus[(int)($c['car_ctlg_id'] ?? 0)]['facebook'] ?? '').'
                        '.crossPostBtn('telegram', $c, 'btn_publish_telegram', 'teleg-logo.svg',    'Telegram', $t, $scheduleStatus[(int)($c['car_ctlg_id'] ?? 0)]['telegram'] ?? '').'
                        '.(function() use ($c, $t, $scheduleStatus) {
                            // "FB + TG" — cross-post to Facebook AND Telegram only (NOT 999).
                            // Enqueues only the ones still free (not published/pending/error).
                            $ctlgId = (int)($c['car_ctlg_id'] ?? 0);
                            $st = $scheduleStatus[$ctlgId] ?? [];
                            $map = ['facebook' => 'published_fb', 'telegram' => 'published_tg'];
                            $free = []; $blocked = false;
                            foreach ($map as $platform => $flag) {
                                $state = $st[$platform] ?? '';
                                if ($state === 'pending' || $state === 'error') { $blocked = true; continue; }
                                if ($state === 'published' || !empty($c[$flag])) { continue; }
                                $free[] = $platform;
                            }
                            $label = $t['btn_publish_fbtg'] ?? 'FB + TG';
                            if (!$free) {
                                if ($blocked) {
                                    $waitLabel = $t['btn_publish_all_pending'] ?? 'În proces';
                                    return '<button class="btn-publish-icon btn-publish-fbtg is-pending" disabled title="'.htmlspecialchars($waitLabel).'">'
                                         . '<span class="pub-all-label">'.htmlspecialchars($label).'</span><span class="pub-clock"></span></button>';
                                }
                                $doneLabel = $t['btn_published_fbtg'] ?? 'FB + TG ✓';
                                return '<button class="btn-publish-icon btn-publish-fbtg is-published" disabled title="'.htmlspecialchars($doneLabel).'">'
                                     . '<span class="pub-all-label">'.htmlspecialchars($label).'</span><span class="pub-check">✓</span></button>';
                            }
                            return '<button class="btn-publish-icon btn-publish-fbtg" onclick="parsingCrossPostAll('.$ctlgId.', '.htmlspecialchars(json_encode($free), ENT_QUOTES).')" title="'.htmlspecialchars($label).'">'
                                 . '<span class="pub-all-label">'.htmlspecialchars($label).'</span></button>';
                        })().'
                        '.crossPostBtn('999',      $c, 'btn_publish_999',      '999-logo.svg',     '999.md', $t, $scheduleStatus[(int)($c['car_ctlg_id'] ?? 0)]['999'] ?? '').'
                        '.(function() use ($c, $t, $scheduleStatus) {
                            // "Publish to all" — enqueue only platforms that are truly
                            // free: NOT published, NOT scheduled (pending), NOT error.
                            // A pending/error platform is already queued — re-queuing
                            // would duplicate.
                            $ctlgId = (int)($c['car_ctlg_id'] ?? 0);
                            $st = $scheduleStatus[$ctlgId] ?? [];
                            $map = ['999' => 'published_999', 'facebook' => 'published_fb', 'telegram' => 'published_tg'];
                            $free = []; $blocked = [];
                            foreach ($map as $platform => $flag) {
                                if ($platform === '999' && !empty($c['ctlg_999_id'])) { continue; } // live 999 ad = done
                                $state = $st[$platform] ?? '';
                                if ($state === 'pending' || $state === 'error') { $blocked[] = $platform; continue; }
                                if ($state === 'published' || !empty($c[$flag])) { continue; } // done
                                $free[] = $platform;
                            }
                            $label = $t['btn_publish_all'] ?? 'Publică pe toate';
                            if (!$free) {
                                if ($blocked) {
                                    $waitLabel = $t['btn_publish_all_pending'] ?? 'În proces';
                                    return '<button class="btn-publish-icon btn-publish-all is-pending" disabled title="'.htmlspecialchars($waitLabel).'">'
                                         . '<span class="pub-all-label">'.htmlspecialchars($waitLabel).'</span><span class="pub-clock"></span></button>';
                                }
                                $doneLabel = $t['btn_published_all'] ?? 'Publicat pe toate';
                                return '<button class="btn-publish-icon btn-publish-all is-published" disabled title="'.htmlspecialchars($doneLabel).'">'
                                     . '<span class="pub-all-label">'.htmlspecialchars($doneLabel).'</span><span class="pub-check">✓</span></button>';
                            }
                            return '<button class="btn-publish-icon btn-publish-all" onclick="parsingCrossPostAll('.$ctlgId.', '.htmlspecialchars(json_encode($free), ENT_QUOTES).')" title="'.htmlspecialchars($label).'">'
                                 . '<span class="pub-all-label">'.htmlspecialchars($label).'</span></button>';
                        })().'
                    </div>
                    '.(parsing_is_full($user_id ?? 0)
                        ? '<button type="button" class="btn-remove-published" onclick="parsingRemovePublished('.(int)$c['id'].')" title="'.htmlspecialchars($t['action_remove_published'] ?? 'Șterge anunț').'">'
                            . '<span>'.($t['action_remove_published'] ?? 'Șterge anunț').'</span></button>'
                        : '').'
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

<!-- Characteristics modal -->
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
