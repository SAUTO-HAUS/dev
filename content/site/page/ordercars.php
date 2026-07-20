<?php
defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;

require_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/includes/contact_form.php');

// Include order-specific functions for order cars
include_once( _SITE_INCL.'/order_functions.php' );

// Include car description functions
include_once( _SITE_INCL.'/car_description.php' );

// Parsing price helper (landed-cost breakdown for Encar cars).
include_once( _ADM_PAGE.'/parsing/parsing_pricing.php' );

// Include similar price cars function
include_once( _SITE_INCL.'/similar_price_cars.php' );

/**
 * Get country name by ID in the specified language
 * @param int $countryId - ID of the country
 * @param string $language - Language code (ro, ru, en)
 * @return string - Country name in the specified language or empty string if not found
 */
function getImportCountryName($countryId, $language = 'ro') {
    if (empty($countryId)) {
        return '';
    }

    global $db;
    $langColumn = 'name_ro'; // Default language

    if ($language == 'ru') {
        $langColumn = 'name_ru';
    } elseif ($language == 'en') {
        $langColumn = 'name_en';
    }

    try {
        $stmt = $db->prepare("SELECT {$langColumn} FROM countries WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $countryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && isset($result[$langColumn])) {
            return $result[$langColumn];
        }
    } catch (Exception $e) {
        // Silent error handling
    }

    return '';
}

/**
 * Region quick-filter buttons (Coreea / Europa / SUA) + the "learn more" order button.
 * Shown on the ordercars landing page AND on each region page; the button matching the
 * current region ($active) gets the `is-active` class for a distinct style.
 *
 * @param string $active  Active region slug ('korea'|'europe'|'usa') or '' for none.
 * @param string $lang    Current language code.
 * @param string $orderBtnText  Localized "learn more" label for the trailing order button.
 */
function oc_render_regions($active, $lang, $orderBtnText) {
    $flag_dir = '/content/admin/page/parsing/media-parsing';
    $regions = [
        'korea'  => ['ro' => 'Coreea', 'ru' => 'Корея',  'en' => 'Korea',  'flag' => 'south-korea-fl.png'],
        'europe' => ['ro' => 'Europa', 'ru' => 'Европа',  'en' => 'Europe', 'flag' => 'european-fl.png'],
        'usa'    => ['ro' => 'SUA',    'ru' => 'США',     'en' => 'USA',    'flag' => 'united-states-fl.png'],
    ];
    $out = '<div class="oc_regions">';
    foreach ($regions as $rk => $rv) {
        $label = $rv[$lang] ?? $rv['ro'];
        $cls = 'oc_region_btn' . ($rk === $active ? ' is-active' : '');
        $aria = ($rk === $active) ? ' aria-current="page"' : '';
        $out .= '<a class="'.$cls.'" href="/'.$lang.'/ordercars/'.$rk.'" title="'.$label.'"'.$aria.'>';
        $out .= '<img src="'.$flag_dir.'/'.$rv['flag'].'" alt="'.$label.'" />';
        $out .= '<span>'.$label.'</span>';
        $out .= '</a>';
    }
    $out .= '<a href="/'.$lang.'/order" class="order-hero-button oc_order_btn" style="white-space: nowrap;">';
    $out .= $orderBtnText;
    $out .= '<span class="order-hero-button-circle">';
    $out .= '<img src="/content/site/page/new_pages/order/order-media/icons/right.svg" alt="Arrow" class="order-hero-button-arrow">';
    $out .= '</span>';
    $out .= '</a>';
    $out .= '</div>';
    return $out;
}
?>
    <style>
        .payment-amount {
            color: #000;
        }
        .payment-number {
            color: #ff0000;
            font-weight: bold;
        }
        /* Load more button spinner animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .load-more-btn {
            background: #e2001a;
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0.25rem 0.75rem rgba(226, 0, 26, 0.2);
            white-space: nowrap;
        }
        .load-more-btn:hover {
            background: #c40017;
            transform: translateY(-0.125rem);
            box-shadow: 0 0.5rem 1.25rem rgba(226, 0, 26, 0.3);
        }
        .load-more-btn:active {
            transform: translateY(0);
            box-shadow: 0 0.25rem 0.75rem rgba(226, 0, 26, 0.2);
        }
        .load-more-btn:disabled {
            cursor: not-allowed;
            opacity: 0.7;
            transform: none !important;
        }
    </style>


<?php // webs25 ?>
<?php
    $carsGalleryVersion = date('GYimsd', filemtime(_SITE . '/css/cars_gallery.css'));
    $carDescriptionCssVersion = date('GYimsd', filemtime(_SITE . '/css/car_description.css'));
    $carDescriptionJsVersion = date('GYimsd', filemtime(_SITE . '/js/car_description.js'));
?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.css" />

    <link rel="stylesheet" href="/content/site/css/cars_gallery.css?v=<?=$carsGalleryVersion?>" />
    
    <!-- Car Description Assets -->
    <link rel="stylesheet" href="/content/site/css/car_description.css?v=<?=$carDescriptionCssVersion?>" />
    <script src="/content/site/js/car_description.js?v=<?=$carDescriptionJsVersion?>" defer></script>

<?php

// Initialize variables
$rtrn = '';
$card = '';
$trnslt_ar = [
    'clr' => 'exterior_color',
    'tra' => 'transmission',
    'bt' => 'body_style',
    'fl' => 'fuel_type',
    'wd' => 'drivetrain'
];
$cr_lmt = 32; // Cars per page (pagination 1, 2, 3...)
$per_page = 32;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Sold/removed car (404) — render a friendly message + similar-car cards INSIDE the normal
// site layout (header/menu/footer stay). Set the 404 status but keep the page usable.
if (!empty($GLOBALS['page_is_404'])) {
    http_response_code(404);
    $GLOBALS['is_404_car'] = true; // so head.php meta can go noindex if it checks this
    include(_SITE_INCL.'/car_404.php');
    // car_404.php fills $rtrn; skip the rest of the catalog logic.
    echo $rtrn;
    return;
}
$page_offset = ($current_page - 1) * $per_page;

// Process query parameters first
if (isset($_SERVER['QUERY_STRING'])) {
    // Fix malformed URLs with multiple question marks
    $query_string = $_SERVER['QUERY_STRING'];
    if (strpos($query_string, '?') !== false) {
        // URL has multiple question marks, fix it
        $parts = explode('?', $query_string);
        $fixed_query = $parts[0]; // Start with first part

        // Collect parameters from all parts
        $all_params = [];
        foreach ($parts as $part) {
            $part_params = explode('&', $part);
            foreach ($part_params as $param) {
                if (strpos($param, '=') !== false) {
                    list($key, $value) = explode('=', $param, 2);
                    if (!empty($key) && !isset($all_params[$key])) {
                        $all_params[$key] = $value;
                    }
                }
            }
        }

        // Rebuild clean query string
        $query_string = http_build_query($all_params);

    }

    // Now parse the cleaned query string
    parse_str($query_string, $query_params);
    foreach ($query_params as $key => $value) {
        $_GET[$key] = $value;
    }

    // Log the corrected parameters

}

// Import regions that live on a clean path (/ordercars/korea) instead of a query string.
// The path segment carries only the region; all other filters/sorting stay in the query
// string, so combining works exactly like brand pages (/ordercars/ford?srt=...).
$oc_region_slugs = ['korea', 'europe', 'usa'];

// Then handle clean URLs for car filters and single car pages
if (isset($t_mp[3]) && !is_numeric($t_mp[3])) {
    // Remove any query string from the URL segments
    $seg = strtolower(explode('?', $t_mp[3])[0]);
    $_GET['tg'] = 'fltr';

    if (in_array($seg, $oc_region_slugs, true)) {
        // Region page: set the import-country filter, NOT a brand.
        $_GET['ic'] = $seg;
    } else {
        $_GET['br'] = str_replace('-', '_', $seg);

        if (isset($t_mp[4])) {
            $model = explode('?', $t_mp[4])[0];
            $_GET['mo'] = str_replace('-', '_', $model);
        }
    }

    // Log for debugging
    // file_put_contents('debug_sql.log', "\nProcessing URL segments:\n", FILE_APPEND);
    // file_put_contents('debug_sql.log', "Brand: {$brand}\n", FILE_APPEND);
    // file_put_contents('debug_sql.log', "Model: " . (isset($model) ? $model : "not set") . "\n", FILE_APPEND);
    // file_put_contents('debug_sql.log', "Final GET params: " . print_r($_GET, true) . "\n", FILE_APPEND);
}


// Process the page based on parameters
if (isset($_GET['tg']) && $_GET['tg'] == 'fltr') {
    // Ensure brand and model are properly formatted for database query
    if (isset($_GET['br'])) {
        $_GET['br'] = str_replace('-', '_', $_GET['br']);
    }
    if (isset($_GET['mo'])) {
        $_GET['mo'] = str_replace('-', '_', $_GET['mo']);
    }

    // Log SQL parameters for debugging
    // file_put_contents('debug_sql.log', "Processing filtered catalog with params: " . print_r($_GET, true) . "\n", FILE_APPEND);

    // Pagination active on all filtered pages
    $is_brand_page = true;

    // This is a filtered catalogue page with pagination
    $card = $car_card('fltr', $per_page, $_GET, 'av', $page_offset, true);

    // Handle body type filter - set appropriate H1 or hide it
    if(isset($_GET['bt']) && !isset($_GET['br'])) {
        // Get body type name from language files
        $body_type_code = $_GET['bt'];
        $body_type_name = isset($lng['l']['car']['bt'][$body_type_code]) ? $lng['l']['car']['bt'][$body_type_code] : $body_type_code;

        // Set language-specific H1 for body type filter with uppercase body type and red color
        $body_type_upper = mb_strtoupper($body_type_name, 'UTF-8');
        $body_type_red = '<span style="color: #ff0000;">'.$body_type_upper.'</span>';

        if ($zlng == 'ro') {
            $sa['meta']['h1'] = "{$body_type_red} | Disponibil pentru vânzare și Trade-In";
        } elseif ($zlng == 'ru') {
            $sa['meta']['h1'] = "{$body_type_red} | Доступно для продажи и Trade-In";
        } else { // English
            $sa['meta']['h1'] = "{$body_type_red} | Available for sale and Trade-In";
        }

        $sa['meta']['ttl'] = $sa['meta']['h1'] . " | Sauto Haus";
        $sa['meta']['dsc'] = "Automobile de tip {$body_type_name} în stoc și la comandă. Prețuri și oferte actuale.";
    }
    // Import region filter (ic) without brand/body type - set a region H1
    elseif(!isset($_GET['br']) && !isset($_GET['bt']) && !empty($_GET['ic'])) {
        $ic_key = strtolower($_GET['ic']);
        $ic_names = [
            'korea'  => ['ro' => 'Coreea', 'ru' => 'Корея',  'en' => 'Korea'],
            'europe' => ['ro' => 'Europa', 'ru' => 'Европа',  'en' => 'Europe'],
            'usa'    => ['ro' => 'SUA',    'ru' => 'США',     'en' => 'USA'],
        ];
        if (isset($ic_names[$ic_key])) {
            $ic_lang = $_COOKIE['lang'] ?? 'ro';
            $ic_label = $ic_names[$ic_key][$ic_lang] ?? $ic_names[$ic_key]['ro'];
            $ic_red = '<span style="color: #ff0000;">'.mb_strtoupper($ic_label, 'UTF-8').'</span>';
            if ($zlng == 'ru') {
                $sa['meta']['h1'] = 'Авто под заказ из '.$ic_red.' в Молдову';
            } elseif ($zlng == 'en') {
                $sa['meta']['h1'] = 'Cars on order from '.$ic_red.' to Moldova';
            } else {
                $sa['meta']['h1'] = 'Auto la comandă din '.$ic_red.' în Moldova';
            }
        } else {
            $sa['meta']['h1'] = '';
        }
    }
    // Check if we have other filters without specific handling - hide H1
    elseif(!isset($_GET['br']) && !isset($_GET['bt'])) {
        // For general filters without brand or body type, don't show irrelevant H1
        $sa['meta']['h1'] = '';
    }
    // Check if we have a brand filter and apply SEO personalized titles
    elseif(isset($_GET['br'])) {
        $brand_code = str_replace('-', '_', $_GET['br']);

        // Get the brand name from the database
        $pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
        $pdo_brand->execute(['br' => $brand_code]);
        $brand_name = '';
        foreach ($pdo_brand as $brand_row) {
            $brand_name = $brand_row['br_nm'];
        }

        if (!empty($brand_name)) {
            // Check if we also have a model filter
            if (isset($_GET['mo'])) {
                $model_code = str_replace('-', '_', $_GET['mo']);

                $pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
                $pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
                $model_name = '';
                foreach ($pdo_model as $model_row) {
                    $model_name = $model_row['mo_nm'];
                }

                if (!empty($model_name)) {
                    // Override meta values with SEO personalized ones for brand+model
                    $sa['meta']['ttl'] = "Cumpără {$brand_name} {$model_name} în Moldova";

                    // Create red-colored text for brand and model
                    $brand_model_red = '<span style="color: #ff0000;">'.$brand_name.' '.$model_name.'</span>';

                    // Use language-specific h1 content with red brand and model
                    if ($zlng == 'ro') {
                        $sa['meta']['h1'] = $brand_model_red." | Disponibil pentru vânzare și Trade-In";
                    } elseif ($zlng == 'ru') {
                        $sa['meta']['h1'] = $brand_model_red." | Доступно для продажи и Trade-In";
                    } else { // English or any other language
                        $sa['meta']['h1'] = $brand_model_red." | Available for sale and Trade-In";
                    }

                    $sa['meta']['dsc'] = "Automobile {$brand_name} {$model_name} în stoc și la comandă. Prețuri și oferte actuale.";

                    // Debug logging
                    // file_put_contents('debug_cars.log', "Applied SEO meta for: {$brand_name} {$model_name}\n", FILE_APPEND);
                    // file_put_contents('debug_cars.log', "Titlu: {$sa['meta']['ttl']}\n", FILE_APPEND);
                }
            } else {
                // Override meta values with SEO personalized ones for brand only
                $sa['meta']['ttl'] = "Cumpără {$brand_name} în Moldova";

                // Create red-colored text for brand only
                $brand_red = '<span style="color: #ff0000;">'.$brand_name.'</span>';

                // Use language-specific h1 content with red brand
                if ($zlng == 'ro') {
                    $sa['meta']['h1'] = $brand_red." | Disponibil pentru vânzare și Trade-In";
                } elseif ($zlng == 'ru') {
                    $sa['meta']['h1'] = $brand_red." | Доступно для продажи и Trade-In";
                } else { // English or any other language
                    $sa['meta']['h1'] = $brand_red." | Available for sale and Trade-In";
                }

                $sa['meta']['dsc'] = "Automobile {$brand_name} în stoc și la comandă. Prețuri și oferte actuale.";

                // Logging for debugging
                // file_put_contents('debug_cars.log', "Applied SEO meta for brand: {$brand_name}\n", FILE_APPEND);
                // file_put_contents('debug_cars.log', "Titlu: {$sa['meta']['ttl']}\n", FILE_APPEND);
            }
        }
    }

    // No debug display
    $rtrn .= '<div class="gr">';
    // Only display H1 if it's not empty and not the default insurance title
    if (!empty($sa['meta']['h1']) &&
        strpos($sa['meta']['h1'], 'Автострахование') === false &&
        strpos($sa['meta']['h1'], 'Car Insurance') === false &&
        strpos($sa['meta']['h1'], 'Asigurări Auto') === false) {
        $rtrn .= '<h1 style="font-size: inherit;">'.$sa['meta']['h1'].'</h1>';
    }

    // On a region page (/ordercars/korea|europe|usa) show the same region buttons as the
    // landing page, with the current region marked active.
    $_oc_region = (!isset($_GET['br']) && !isset($_GET['bt']) && !empty($_GET['ic'])) ? strtolower($_GET['ic']) : '';
    if (in_array($_oc_region, ['korea', 'europe', 'usa'], true)) {
        include_once(_SITE_PAGE.'/new_pages/order/order_lang.php');
        $_oc_lang = $_COOKIE['lang'] ?? 'ro';
        $order_button_text = $lng_order_page[$_oc_lang]['learn_more'] ?? 'Learn more';
        $rtrn .= '<style>@media (max-width: 767px) { .order-hero-button-circle { margin-left: 0.5rem !important; } }</style>';
        $rtrn .= '<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/order/order.css">';
        $rtrn .= oc_render_regions($_oc_region, $_oc_lang, $order_button_text);
    }

    $rtrn .= '<div class="cnt list" id="brand-cars-container" data-total="'.(int)($card['total'] ?? 0).'" data-loaded="'.(int)$card['qu'].'">';
    $rtrn .= $card['txt'];
    $rtrn .= '</div>';

    // Numbered pagination (1, 2, 3 ... N)
    $rtrn .= render_pagination($current_page, $per_page, (int)($card['total'] ?? 0), $_GET);

    // SEO description now appears right after the "Load more" button (or after cars if no button)
    if(isset($_GET['br']) && !isset($_GET['mo'])) {
        $brand_code = str_replace('-', '_', $_GET['br']);
        $current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';

        try {
            $pdo_brand_seo = $db->prepare('SELECT `description_'.$current_lang.'` as description FROM '.$prefx.'_brands_seo WHERE `brand_code`=:brand_code LIMIT 1');
            $pdo_brand_seo->execute(['brand_code' => $brand_code]);
            $brand_seo = $pdo_brand_seo->fetch(PDO::FETCH_ASSOC);

            if($brand_seo && !empty(trim($brand_seo['description']))) {
                $rtrn .= '<div class="brand-description">';
                $rtrn .= $brand_seo['description'];
                $rtrn .= '</div>';
            }
        } catch (PDOException $e) {
        }
    }
    $rtrn .= '</div>';

    // Add Facebook tracking if we have results
    if (isset($card['qu']) && $card['qu'] > 0) {
        $trnslt_txt = '';
        foreach($trnslt_ar as $k=>$v){
            if (isset($_GET[$k], $lng_x['car'][$k][$_GET[$k]])) {
                $trnslt_txt .= ',' . $v . ': "' . $lng_x['car'][$k][$_GET[$k]] . '"';
            }
        }

        $rtrn .= '<script>';
        $rtrn .= 'fbq("track", "ViewContent", {';
        $rtrn .= 'content_ids:["'.implode('","',$card['ids']).'"],';
        $rtrn .= 'content_type:"vehicle"';
        if(isset($_GET['br'])) { $rtrn .= ',make:"'.$card['br'].'"'; }
        if(isset($_GET['mo'])) { $rtrn .= ',model:"'.$card['mo'].'"'; }
        $rtrn .= $trnslt_txt;
        $rtrn .= '});</script>';
    } else {
        // No results in the current region. If a region (ic) is active, the same filters MAY
        // match cars in other regions (e.g. RAV4 Plug-in exists in Europe but not Korea).
        // Retry WITHOUT the region; if that finds cars, show them under an explanatory heading
        // instead of a dead end. If it's still empty (absurd price etc.), keep "no offers".
        {
            $fallback = null;
            if (!empty($_GET['ic'])) {
                $fb_get = $_GET;
                unset($fb_get['ic']);
                unset($fb_get['tg']); // let car_card treat it as a fresh filtered search
                $fb_get['tg'] = 'fltr';
                $fallback = $car_card('fltr', $per_page, $fb_get, 'av', 0, true);
            }

            if ($fallback && !empty($fallback['txt']) && ($fallback['qu'] ?? 0) > 0) {
                // Region label for the message (Coreea / Europa / SUA in the current language).
                $ic_names = [
                    'korea'  => ['ro' => 'Coreea', 'ru' => 'Кореи',  'en' => 'Korea'],
                    'europe' => ['ro' => 'Europa', 'ru' => 'Европы', 'en' => 'Europe'],
                    'usa'    => ['ro' => 'SUA',    'ru' => 'США',    'en' => 'USA'],
                ];
                $ic_k = strtolower($_GET['ic']);
                $ic_lbl = $ic_names[$ic_k][$zlng] ?? ($ic_names[$ic_k]['ro'] ?? $_GET['ic']);
                if ($zlng == 'ru') {
                    $fb_msg = 'В наличии из '.$ic_lbl.' по этому запросу пока нет. Показываем подходящие авто из других регионов:';
                } elseif ($zlng == 'en') {
                    $fb_msg = 'Nothing from '.$ic_lbl.' matches this search yet. Showing matching cars from other regions:';
                } else {
                    $fb_msg = 'Din '.$ic_lbl.' nu am găsit pentru această căutare. Îți arătăm mașini potrivite din alte regiuni:';
                }

                $rtrn = '<div class="gr">';
                $rtrn .= '<h1 style="font-size: inherit;">'.$sa['meta']['h1'].'</h1>';
                $rtrn .= '<div class="region-fallback-msg" style="margin:1rem 0;padding:.9rem 1.1rem;background:#fff5f5;border-left:4px solid #e2001a;border-radius:.4rem;color:#333;font-size:1rem;">'.$fb_msg.'</div>';
                $rtrn .= '<div class="cnt list" id="brand-cars-container" data-total="'.(int)($fallback['total'] ?? 0).'" data-loaded="'.(int)$fallback['qu'].'">';
                $rtrn .= $fallback['txt'];
                $rtrn .= '</div>';
                $rtrn .= render_pagination($current_page, $per_page, (int)($fallback['total'] ?? 0), $fb_get);
                $rtrn .= '</div>';
            } else {
                // Genuinely nothing anywhere (e.g. impossible price) — keep the plain message.
                $rtrn = '<div class="gr">';
                $rtrn .= '<h1 style="font-size: inherit;">'.$sa['meta']['h1'].'</h1>';
                $rtrn .= '<div class="cnt list">';
                $rtrn .= '<div class="no-results">'.$lng['t']['x']['no_offers'].'</div>';
                $rtrn .= '</div>';
                $rtrn .= '</div>';
            }
        }
    }
}
// Base catalogue page
elseif (!isset($t_mp[3])) {
    include_once(_SITE_PAGE.'/new_pages/order/order_lang.php');
    $current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
    $order_button_text = isset($lng_order_page[$current_lang]['learn_more']) ? $lng_order_page[$current_lang]['learn_more'] : 'Learn more';
    
    $rtrn .= '<div class="gr">';
    $rtrn .= '<style>@media (max-width: 767px) { .order-hero-button-circle { margin-left: 0.5rem !important; } }</style>';
    $rtrn .= '<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/order/order.css">';
    $rtrn .= '<h1 class="gr-h1">'.$sa['meta']['h1'].'</h1>';

    // Region quick-filter buttons (no active region on the base landing page).
    $_oc_lang = $_COOKIE['lang'] ?? 'ro';
    $rtrn .= oc_render_regions('', $_oc_lang, $order_button_text);

    // Use filtered mode with empty filters so pagination works
    $is_brand_page = true;
    $card = $car_card('fltr', $per_page, [], 'av', $page_offset, true);

    $rtrn .= '<div class="cnt list" id="brand-cars-container" data-total="'.(int)($card['total'] ?? 0).'" data-loaded="'.(int)$card['qu'].'">';
    $rtrn .= $card['txt'];
    $rtrn .= '</div>';

    // Numbered pagination
    $rtrn .= render_pagination($current_page, $per_page, (int)($card['total'] ?? 0), $_GET);

    $rtrn .= '</div>';
}
// Sample filter page
elseif (isset($_GET['tg']) && $_GET['tg']=='smpl' && isset($_GET['v'])) {
    $card = $car_card('smpl', $cr_lmt, $_GET, 'av');
    $rtrn .= '
    <div class="gr">
        <h1 style="font-size: inherit;">';
    if ($_GET['v']=='gD2ksAsmc5L'){$rtrn .= 'NISSAN QASHQAI, RENAULT KADJAR, FORD KUGA';}
    $rtrn .= '
        </h1>
        <div class="cnt list">'.$card['txt'].'</div>
    </div>';
}
// Single car page (using clean URL brand/model format or ID)
elseif (is_numeric($t_mp[3]) || (isset($t_mp[3]) && !is_numeric($t_mp[3]) && !isset($_GET['tg']))) {
    $it_id = 0;
    $chkr_av = 0;

    // Check if we're using clean URLs (brand/model format)
    if (!is_numeric($t_mp[3])) {
        $brand = str_replace('-', '_', $t_mp[3]);
        $model = isset($t_mp[4]) ? str_replace('-', '_', $t_mp[4]) : null;

        $sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE `br`=:brand';
        $params = ['brand' => $brand];

        if ($model) {
            $sql .= ' AND `mo`=:model';
            $params['model'] = $model;
        }

        $sql .= ' AND `vis`="1" AND `act`="1" AND `catalog_type`="on_order" LIMIT 1';

        $pdo = $db->prepare($sql);
        $pdo->execute($params);
        $car = $pdo->fetch(PDO::FETCH_ASSOC);

        if ($car) {
            $it_id = $car['id'];
            // file_put_contents('debug_sql.log', "Found car with ID: {$it_id}\n", FILE_APPEND);
        } else {
            // file_put_contents('debug_sql.log', "No car found for brand: {$brand}, model: {$model}\n", FILE_APPEND);
        }
    } else {
        $it_id = toNumber($t_mp[3]);

        if ( $it_id > 0 ){
            $spec_ar = ['yr', 'bt', 'mlg', 'vol', 'hp', 'fl', 'tra', 'wd', 'clr', 'sts', 'delivery_time', 'advance_amount', 'loc', 'import_country_id'];

            //Update views
            try {
                $view_update = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `views` = `views` + 1 WHERE `id` = :id');
                $view_update->execute(['id' => $it_id]);
            } catch (PDOException $e) {
                if ($e->getCode() == 1615) {
                    try {
                        $view_update = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `views` = `views` + 1 WHERE `id` = :id');
                        $view_update->execute(['id' => $it_id]);
                    } catch (PDOException $e2) {}
                }
            }

            try {
                $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id AND `vis`="1" AND `act`="1" LIMIT 1');
                $pdo->execute(['id' => $it_id]);
            } catch (PDOException $e) {
                if ($e->getCode() == 1615) {
                    try {
                        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id AND `vis`="1" AND `act`="1" LIMIT 1');
                        $pdo->execute(['id' => $it_id]);
                    } catch (PDOException $e2) {
                        $pdo = [];
                    }
                } else {
                    $pdo = [];
                }
            }

            foreach ($pdo as $r){
                $chkr_av = 1;

                //Collect photos
                $pdo = $db->prepare('SELECT `name`, `main`, `ff` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id ORDER BY `pos` ASC');
                $pdo->execute(['it_id'=>$r['id']]);
                $img = ['all'=>[], 'main'=>'', 'main_ff'=>''];
                $ii = 1;
                foreach($pdo as $r2){
                    if ($r2['main']=='1'){ $img['main'] = $r2['name']; $img['main_ff'] = $r2['ff']; }
                    $img['all'][$ii] = ['name'=>$r2['name'], 'main'=>$r2['main'], 'ff'=>$r2['ff']];
                    $ii++;
                }
                unset($r2, $ii);

                // An expired offer timer counts as out of stock even before the cron
                // flips n_a in the DB — so the card drops into the "not available"
                // branch below (badge + "find similar" link), with no timer text.
                $timer_expired = !empty($r['offer_timer_end']) && ($r['offer_timer_end'] - time()) <= 0;
                $effective_n_a = ((int)$r['n_a'] === 1 || $timer_expired) ? 1 : 0;

                $z_stat = '';
                if ( $effective_n_a==0 && $r['act']==1 ){
                    // For on_order catalog type, show "On Order" status and timer
                    if (isset($r['catalog_type']) && $r['catalog_type'] === 'on_order') {
                        // Add "On Order" status with same style as in cards
                        $on_order_text = $lng['w']['on_order'] ?? 'On Order';
                        $z_stat .= '<div class="stat stat-onorder" style="background-color: #CE3226; color: #fff; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; vertical-align: top; padding: 0.3rem 0.5rem !important; margin: 0.3rem 0.3rem 0 0 !important;"><span class="stock-status on-order">'.$on_order_text.'</span></div>';

                        // Add offer timer if it exists and is still running
                        if (!empty($r['offer_timer_end'])) {
                            $time_remaining = $r['offer_timer_end'] - time();
                            // Add "Offer expires in:" text with line break
                            $expires_text = 'Oferta expiră<br>peste:';
                            if (isset($_COOKIE['lang'])) {
                                if ($_COOKIE['lang'] == 'ru') $expires_text = 'Предложение<br>истекает через:';
                                elseif ($_COOKIE['lang'] == 'en') $expires_text = 'Offer expires<br>in:';
                            }
                            $z_stat .= '<div class="stat" style="padding: 0.3rem 0.5rem 0 0.5rem !important; margin: 0.3rem 0.3rem 0 0 !important; background: transparent; font-weight: 600; color: #333; font-size: 1rem; display: inline-block; vertical-align: top; line-height: 1.3;">'.$expires_text.'</div>';
                            $days = floor($time_remaining / 86400);
                            $hours = floor(($time_remaining % 86400) / 3600);
                            $minutes = floor(($time_remaining % 3600) / 60);
                            $seconds = $time_remaining % 60;
                            $z_stat .= '<div class="stat" style="padding:0; margin:0; background:transparent; display: inline-block; vertical-align: top; margin-top: 0.5rem !important;"><div class="timer-display" data-end-time="'.$r['offer_timer_end'].'">'.sprintf('%02d:%02d:%02d:%02d', $days, $hours, $minutes, $seconds).'</div></div>';
                        }
                    } else {
                        // For regular cars, show all statuses
                        $z_stat .= ( $r['soon']==1 ) ? '<div class="stat soon1">'.$lng['l']['stat']['soon1'].'</div>' : '<div class="stat n_a0">'.$lng['l']['stat']['n_a0'].'</div>';
                        $z_stat .= ($r['top']==1) ? '<div class="stat top1">'.$lng['l']['stat']['top1'].'</div>' : '';
                        $z_stat .= ($r['prc_n']!=0 && $r['prc_t']>time()) ? '<div class="stat prc_n">'.$lng['l']['stat']['prc_n'].'</div>' : '';
                        $z_stat .= ($r['tva']==1) ? '<div class="stat top1">'.$lng['l']['stat']['vat'].'</div>' : '';
                        $z_stat .= ($r['gift']==1) ? '<div class="stat gift">+ '.$lng['l']['stat']['gift'].'</div>' : '';
                    }

                // Add import country with prominent style
                $import_country_id = isset($r['import_country_id']) ? $r['import_country_id'] : null;

                    // Force database check if we don't have import_country_id
                    if (empty($import_country_id)) {
                        $stmt = $db->prepare("SELECT import_country_id FROM ".$prefx."_car_ctlg WHERE id = :id LIMIT 1");
                        $stmt->execute(['id' => $r['id']]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result && isset($result['import_country_id'])) {
                            $import_country_id = $result['import_country_id'];
                        }
                    }

                    if (!empty($import_country_id)) {
                        $country_name = getImportCountryName($import_country_id, $_COOKIE['lang']);
                    }
                }else{
                    $z_stat .= '
						<div class="stat n_a1">'.$lng['l']['stat']['n_a1'].'</div>
						<a href="/'.$_COOKIE['lang'].'/ordercars/'.buildCarUrl($r['br'], $r['mo']).'" class="stat soon1">'.$lng['w']['fnd_smlr'].'</a>
					';
                }

                /*
                <script> fbq("track", "ViewContent", {
                    content_ids: ["c'.$it_id.'"],
                    content_type: "vehicle",
                    make: "",
                    model: "",
                    year: "",
                    state_of_vehicle: "Used",
                    mileage.value: "",
                    mileage.unit: "",
                    exterior_color: "",
                    transmission: "",
                    body_style: "",
                    fuel_type: "",
                    drivetrain: "",
                    price: "",
                    currency: ""
                });</script>
                */


                $rtrn .= '
                        <script> fbq("track", "ViewContent", {
                            content_ids: ["c'.$it_id.'"]
                            ,content_type: "vehicle"
                            ,make: "'.$r['br_nm'].'"
                            ,model: "'.$r['mo_nm'].'"
                            ,year: '.$r['yr'].'
                            ,state_of_vehicle: "Used"';
                foreach($trnslt_ar as $k=>$v){ $rtrn .= isset( $lng_x['car'][$k][$r[$k]] )?','.$v.': "'.$lng_x['car'][$k][$r[$k]].'"':''; }
                $rtrn .= '
                            '.($r['prc']>100?',price: '.$r['prc'].',currency: "'.$r['cur'].'"':'').'
                        });</script>
                        
                        <div class="pht_bx">
                            <input type="checkbox" id="img_bx_sz" class="cbx none">
                            <div class="list">
                                <label class="btn max" for="img_bx_sz"></label>
                                <div class="phts">';
                $img_cnt = 0;
                foreach($img['all'] as $k => $v){
                    $img_cnt++;
                    $act = ($v['main']=='1') ? 'act' : '';
                    $v_ext = !empty($v['ff']) ? '.'.$v['ff'] : $img_frmt;
                    $z_src_med  = '/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/med/'.$v['name'].$v_ext;
                    $z_src_high = '/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$v['name'].$v_ext;
                    $rtrn .= '<img class="item '.$act.'" alt="car '.$r['br_nm'].' '.$r['mo_nm'].' id'.$r['id'].' photo #'.$img_cnt.'" data-pos="'.$k.'" data-high="'.$z_src_high.'" src="'.$z_src_med.'" width="100%" height="auto" />';
                }
                $rtrn .= '
                                </div>
                            </div>';
                $main_ext = !empty($img['main_ff']) ? '.'.$img['main_ff'] : $img_frmt;
                $z_src = isset($img['main'])?'/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$img['main'].$main_ext:'';
                //$z_src = (@getimagesize($site_url.$z_src)?$z_src:'');
                $rtrn .= '<div class="big_pht" role="img" aria-label="car '.$r['br_nm'].' '.$r['mo_nm'].' id'.$r['id'].' large photo" data-pos="1" data-cnt="'.$img_cnt.'" style="background-image:url('.$z_src.');" data-src="'.$z_src.'">'.car_share_btn($r['id'], 'ordercars', $lng).car_fav_btn($r['id'], $lng).($img_cnt>1?'<div class="bp-nav bp-left" role="button" aria-label="Anterior"></div><div class="bp-nav bp-right" role="button" aria-label="Următor"></div>':'').'</div>';
                $rtrn .= '</div>';
                
                

                $cur = $r['cur'];
                if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
                    $prc = $r['prc_n'];
                    $o_prc = $r['prc'];
                    $o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.parseCurr($o_prc).'</span> '.( symb_rplc($r['cur']) ).'</span>';
                }else{
                    $prc = $r['prc'];
                    $o_prc = 0;
                    $o_prc_bl = '';
                }

                // var_dump( $spec_ar);
                ?>
                <?php // webs25  ?>
                <div class="wrapf-carousel 11">
                    <?= car_share_btn($r['id'], 'ordercars', $lng) ?>
                    <?= car_fav_btn($r['id'], $lng) ?>
                    <div class="f-carousel" id="heroCarousel">

                        <?php 
                        if(empty($img) || !$img['main'] ) {
                            ?>
                            <div class="f-carousel__slide">
                                <a href="/media/images/placeholder_car.png" data-fancybox="product" data-id="p<?=$img_cnt?>"
                                   data-title="<?=$r['br']?> <?=$r['mo']?>, <?=$r['yr']?>, <?=$r['mlg']?>, <?=$lng['l']['car']['fl'][$r['fl']]?> <?=$lng['l']['car']['tra'][$r['tra']]?>"
                                   data-price="<?=$prc?> <?=$cur?>">
                                    <img src="/media/images/placeholder_car.png" loading="lazy" class="lazy" alt="<?=$img_cnt?>">
                                </a>
                            </div>
                            <?php 
                        }

                        if(!empty($img) ) {
                            $img_cnt = 0;
                            foreach($img['all'] as $k => $v){
                                $img_cnt++;
                                // For order cars, use .jpg extension instead of $img_frmt
                                $image_extension = (isset($r['catalog_type']) && $r['catalog_type'] === 'on_order') ? '.jpg' : $img_frmt;
                                $z_src = '/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/med/'.$v['name'].$image_extension;

                                $z_src2 = isset($img['main'])?'/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$v['name'].$image_extension:'';
                                ?>
                                <div class="f-carousel__slide">
                                    <a href="<?=$z_src2?>" data-fancybox="product" data-id="p<?=$img_cnt?>"
                                       data-title="<?=$r['br']?> <?=$r['mo']?>, <?=$r['yr']?>, <?=$r['mlg']?>, <?=$lng['l']['car']['fl'][$r['fl']]?> <?=$lng['l']['car']['tra'][$r['tra']]?>"
                                       data-price="<?=$prc?> <?=$cur?>">
                                         <img src="<?=$z_src?>" loading="lazy" class="lazy" alt="<?=$img_cnt?>">
                                    </a>
                                </div>
                                <?php 
                            }
                        } ?>

                    </div>
                </div>



                <a style="display:none"
                   data-fancybox="product"
                   href="#moreLinks"
                   data-type="inline"
                   data-id="more"
                   data-title="Смотреть ещё"
                   data-price="">
                    <span aria-hidden="true"></span>
                </a>

                <?php
                $card = $car_card('smlr', 6, $r);
                // var_dump( $card);
                ?>
                <div id="moreLinks" class="more-grid" style="display:none">
                    <?=$card['txt']?>
                </div>


                <?php

                $rtrn .= '<div class="spc_bx">';
                $rtrn .= '<h1 class="name">'.$r['br_nm'].' '.$r['mo_nm'].' <span class="fl">'. $r['mlg'].', '.$lng['l']['car']['fl'][$r['fl']].', '.$lng['l']['car']['tra'][$r['tra']].'</span></h1>';

                // Generate mobile timer HTML if exists. Skip for sold cars (n_a=1).
                $mobile_timer_html = '';
                if (!empty($r['offer_timer_end']) && empty($r['n_a'])) {
                    $time_remaining_mobile = $r['offer_timer_end'] - time();
                    if ($time_remaining_mobile > 0) {
                        // Add "Offer expires in:" text for mobile with line break
                        $expires_text_mobile = 'Oferta expiră<br>peste:';
                        if (isset($_COOKIE['lang'])) {
                            if ($_COOKIE['lang'] == 'ru') $expires_text_mobile = 'Предложение<br>истекает через:';
                            elseif ($_COOKIE['lang'] == 'en') $expires_text_mobile = 'Offer expires<br>in:';
                        }
                        $days_m = floor($time_remaining_mobile / 86400);
                        $hours_m = floor(($time_remaining_mobile % 86400) / 3600);
                        $minutes_m = floor(($time_remaining_mobile % 3600) / 60);
                        $seconds_m = $time_remaining_mobile % 60;
                        $mobile_timer_html = '<div class="mobile-only-timer" style="display: inline-flex; align-items: center; margin-left: 10px; gap: 5px;"><span style="font-weight: 600; color: #333; font-size: 0.85rem; line-height: 1.2;">'.$expires_text_mobile.'</span><div style="display: inline-block;"><div class="timer-display" data-end-time="'.$r['offer_timer_end'].'">'.sprintf('%02d:%02d:%02d:%02d', $days_m, $hours_m, $minutes_m, $seconds_m).'</div></div></div>';
                    } else {
                        // Timer expired → no "expired" text; the car shows as out of stock
                        // via its status badge instead.
                        $mobile_timer_html = '';
                    }
                }

                $rtrn .= ' <div class="prc pricemobile" style="display: flex; align-items: center; justify-content: space-between;">
                                        <span class="val" title="'.$lng['w']['prc'].'">'.( $r['prc']>100 ? '<span class="i">'.parseCurr($prc).'</span> <span class="cur">'.( symb_rplc($r['cur']) ).'</span>' : '<span style="font-size: 1.5rem;">'.$lng['w']['negociabil'] ).'</span></span>
                                        '.$o_prc_bl.'
                                        '.$mobile_timer_html.'
                                    </div> 
                            
                                    <div class="clear"> </div>
                            ';

                // Collect information about import country
                $import_country_id = isset($r['import_country_id']) ? $r['import_country_id'] : null;

                // Check directly in database if we don't find import_country_id
                if (empty($import_country_id)) {
                    $stmt = $db->prepare("SELECT import_country_id FROM ".$prefx."_car_ctlg WHERE id = :id LIMIT 1");
                    $stmt->execute(['id' => $r['id']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result && isset($result['import_country_id'])) {
                        $import_country_id = $result['import_country_id'];
                    }
                }

                // Prepare information about import country
                $import_country_html = '';
                if (!empty($import_country_id)) {
                    $country_name = getImportCountryName($import_country_id, $_COOKIE['lang']);
                    if (!empty($country_name)) {
                        if ($_COOKIE['lang'] == 'ru') {
                            $country_label = 'Страна импорта';
                        } elseif ($_COOKIE['lang'] == 'en') {
                            $country_label = 'Import country';
                        } else {
                            $country_label = 'Țara de import';
                        }

                        // Get the country code for the flag from the database
                        $country_code = '';
                        $stmt = $db->prepare("SELECT code FROM countries WHERE id = :id LIMIT 1");
                        $stmt->execute(['id' => $import_country_id]);
                        $flag_result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($flag_result && isset($flag_result['code'])) {
                            $country_code = strtolower($flag_result['code']);
                        }

                        // Check if the flag exists and add it
                        $flag_html = '';
                        if (!empty($country_code) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/media/images/flags/' . $country_code . '.svg')) {
                            $flag_html = '<img src="/media/images/flags/' . $country_code . '.svg" alt="' . $country_name . ' flag" style="width: 38px; height: 32px;">';
                        }

                        $import_country_text = '<div class="import-country-box" style="margin-top: 10px; font-weight: bold; min-width: 200px;">
                            <div style="text-align: right;"><span style="color: #666; font-weight: 500;">' . $country_label . ': </span><span style="color: #000000; font-weight: bold;">' . $country_name . '</span></div>
                            ' . (!empty($flag_html) ? $flag_html : '') . '
                        </div>';
                    }
                }

                $rtrn .= '<div class="status">'.$z_stat.'</div>';

                // Display the characteristics title
                $rtrn .= '<div>';
                // Title and import country text aligned horizontally
                $rtrn .= '<div style="display: flex; justify-content: space-between; align-items: center; margin: 0; padding: 0;">';

                // Smaller title font for Russian and English to fit better
                $title_style = '';
                if ($_COOKIE['lang'] == 'ru' || $_COOKIE['lang'] == 'en') {
                    $title_style = ' style="margin: 0; padding: 0; font-size: 1.2rem;"';
                } else {
                    $title_style = ' style="margin: 0; padding: 0;"';
                }

                $rtrn .= '<h2 class="ttl desktop"'.$title_style.'>'.$lng['w']['characteristics'].'</h2>';
                if (!empty($import_country_text)) {
                    $rtrn .= $import_country_text;
                }
                $rtrn .= '</div>';
                $rtrn .= '</div>';
                // Add red line between title and specifications - make it thicker and more visible
                $rtrn .= '<hr style="border: none; height: 1.5px; background-color: #ff0000; margin-top: 8px; margin-bottom: 15px; width: 100%;">';

                ?>


                    <?php

                    foreach ($spec_ar as $v){
                        if ($v=='loc' && $r[$v]=='0'){continue;}
                        // Skip import_country_id in the list because we already displayed it above
                        if ($v == 'import_country_id') {continue;}
                        $v_lng = isset($lng['l']['car'][$v][$r[$v]]) ? $lng['l']['car'][$v][$r[$v]] : $r[$v];
                        $v_lng = $v == 'mlg' ? parseCurr($r[$v]).' '.$lng['l']['unit'][ $r['unit'] ] : $v_lng;
                        $v_lng = $v == 'vol' ? $r[$v].' '.$lng['l']['unit']['cm3'] : $v_lng;
                        $v_lng = $v == 'hp' ? $r[$v].' '.$lng['l']['unit']['hp'].' ('.( round($r['hp']*0.735,0) ).' '.$lng['l']['unit']['kw'].')' : $v_lng;
                        $v_lng = $v == 'clr' ? $v_lng.( isset($clr_arr[$r[$v]])?'<span class="crcl" style="background-image:linear-gradient(135deg, '.$clr_arr[$r[$v]].')"></span>':'' ) : $v_lng;
                        $v_lng = $v == 'loc' ? $lng['t']['x']['address'][$r[$v]] : $v_lng;
                        $v_lng = $v == 'delivery_time' ? $r[$v].' '.$lng['l']['unit']['days'] : $v_lng;
                        $v_lng = $v == 'advance_amount' ? parseCurr(intval($r[$v])).' '.$lng['l']['cur'][$r['cur']] : $v_lng;
                        if ($v == 'import_country_id' && !empty($r[$v])) {
                            $country_name = getImportCountryName($r[$v], $_COOKIE['lang']);
                            if (!empty($country_name)) {
                                $v_lng = $country_name;
                            }
                        }

                        if (isset($r[$v])&&$r[$v]!=''){
                            $rtrn .= '
                            <p class="ar '.$v.'  param_line_desktop">
                                <span class="name">'.($v == 'import_country_id' ? ($_COOKIE['lang'] == 'ru' ? 'Страна импорта' : ($_COOKIE['lang'] == 'en' ? 'Import country' : 'Țara de import')) : $lng['l']['car']['spec'][$v]).'</span>
                                <span class="space"></span>
                                <span class="val">'.$v_lng.'</span>
                            </p>';
                        }
                    }

                ?>

                <?php
$iconTelegramParams = array(
    /* 'yr'  => year of manufacture (calendar) */
    'yr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" role="img" >
                <rect x="3" y="5" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                <line x1="16" y1="3" x2="16" y2="7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <line x1="8" y1="3" x2="8" y2="7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <line x1="3" y1="11" x2="21" y2="11" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
              </svg>',

    /* 'bt'  => body type (car) */
    'bt' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <path d="M3 13 L5 8 H19 L21 13 V17 H19 A1 1 0 0 1 17 15 H7 A1 1 0 0 1 5 17 H3 z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                <circle cx="7.5" cy="17.5" r="1.4" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <circle cx="16.5" cy="17.5" r="1.4" fill="none" stroke="currentColor" stroke-width="1.4"/>
              </svg>',

    /* 'mlg' => mileage (road / odometer) */
    'mlg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <path d="M3 17 L21 17" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                <path d="M6 17 L9 8 L12 14 L15 9 L18 17" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="10" r="1.2" fill="currentColor"/>
              </svg>',

    /* 'vol' => engine volume (engine block icon) */
    'vol' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <rect x="3.5" y="7" width="17" height="10" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <rect x="6" y="4" width="3" height="4" rx="0.6" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <line x1="9.5" y1="9.5" x2="14.5" y2="9.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                <line x1="9.5" y1="12.5" x2="14.5" y2="12.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
              </svg>',

    /* 'hp'  => power (gauge / arrow) */
    'hp' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <path d="M12 3 A9 9 0 1 0 21 12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M12 12 L16 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="1.2" fill="currentColor"/>
              </svg>',

    /* 'fl'  => fuel (electricity / charge) */
    'fl' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <rect x="3.5" y="5" width="13" height="14" rx="1.2" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <path d="M21 9 v6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                <path d="M9 9 L12 12 L10.5 12 L13 15 L9 15 L11.5 11.5 Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                <rect x="6.5" y="7" width="6" height="3" rx="0.4" fill="none" stroke="currentColor" stroke-width="1.1"/>
              </svg>',

    /* 'tra' => transmission (gear / gearbox) */
    'tra' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/>
                <g stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
                  <line x1="12" y1="2" x2="12" y2="5"/>
                  <line x1="12" y1="19" x2="12" y2="22"/>
                  <line x1="2" y1="12" x2="5" y2="12"/>
                  <line x1="19" y1="12" x2="22" y2="12"/>
                </g>
              </svg>',

    /* 'wd'  => drivetrain (arrow/axle — rear/front/all-wheel, color can be changed) */
    'wd' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <circle cx="6" cy="17" r="1.6" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <circle cx="18" cy="17" r="1.6" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <path d="M6 17 L10 10 L14 14 L18 10" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>',

    /* 'clr' => color (paint drop) */
    'clr' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <path d="M12 2 C14.5 6 18 9 18 12 A6 6 0 1 1 6 12 C6 9 9.5 6 12 2 Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                <circle cx="12" cy="15" r="0.9" fill="currentColor"/>
              </svg>',

    /* 'sts' => number of seats (people icons) */
    'sts' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <circle cx="8" cy="8" r="1.6" fill="none" stroke="currentColor" stroke-width="1.3"/>
                <path d="M6 12 C6 11 7 10 8 10 C9 10 10 11 10 12" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                <circle cx="16" cy="8" r="1.6" fill="none" stroke="currentColor" stroke-width="1.3"/>
                <path d="M14 12 C14 11 15 10 16 10 C17 10 18 11 18 12" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
              </svg>',

    /* 'loc' => address (location marker) */
    'loc' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <path d="M12 2 C8 2 5 5.5 5 9.5 C5 14.5 12 22 12 22 C12 22 19 14.5 19 9.5 C19 5.5 16 2 12 2 Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                <circle cx="12" cy="10" r="1.4" fill="currentColor"/>
              </svg>',

    /* 'delivery_time' => delivery time (clock/calendar) */
    'delivery_time' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <path d="M12 7 L12 12 L16 16" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="1" fill="currentColor"/>
              </svg>',

    /* 'advance_amount' => advance payment amount (banknote) */
    'advance_amount' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" role="img">
                <rect x="2" y="8" width="20" height="8" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.4"/>
                <circle cx="12" cy="12" r="1.8" fill="none" stroke="currentColor" stroke-width="1.2"/>
                <path d="M12 10.5 L12 13.5 M10.5 12 L13.5 12" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                <circle cx="6" cy="10" r="0.8" fill="currentColor"/>
                <circle cx="18" cy="14" r="0.8" fill="currentColor"/>
              </svg>',
);


                $rtrn .= '<div class="param_b  param_bl">';
                foreach ($spec_ar as $v){
                    if ($v=='loc' && $r[$v]=='0'){continue;}
                    // Skip import_country_id in the list because we already displayed it above
                    if ($v == 'import_country_id') {continue;}
                    $v_lng = isset($lng['l']['car'][$v][$r[$v]]) ? $lng['l']['car'][$v][$r[$v]] : $r[$v];
                    $v_lng = $v == 'mlg' ? parseCurr($r[$v]).' '.$lng['l']['unit'][ $r['unit'] ] : $v_lng;
                    $v_lng = $v == 'vol' ? $r[$v].' '.$lng['l']['unit']['cm3'] : $v_lng;
                    $v_lng = $v == 'hp' ? $r[$v].' '.$lng['l']['unit']['hp'].' ('.( round($r['hp']*0.735,0) ).' '.$lng['l']['unit']['kw'].')' : $v_lng;
                    $v_lng = $v == 'clr' ? $v_lng.( isset($clr_arr[$r[$v]])?'<span class="crcl" style="background-image:linear-gradient(135deg, '.$clr_arr[$r[$v]].')"></span>':'' ) : $v_lng;
                    $v_lng = $v == 'loc' ? $lng['t']['x']['address'][$r[$v]] : $v_lng;
                    $v_lng = $v == 'delivery_time' ? $r[$v].' '.$lng['l']['unit']['days'] : $v_lng;
                    $v_lng = $v == 'advance_amount' ? parseCurr(intval($r[$v])).' '.$lng['l']['cur'][$r['cur']] : $v_lng;
                    if ($v == 'import_country_id' && !empty($r[$v])) {
                        $country_name = getImportCountryName($r[$v], $_COOKIE['lang']);
                        if (!empty($country_name)) {
                            $v_lng = $country_name;
                        }
                    }

                    if (isset($r[$v])&&$r[$v]!=''){
                        $rtrn .= '
                            <p class="ar '.$v.'  param_line_mobile">
                                <span class="icon_param_line">
                                    '. $iconTelegramParams[ $v ] .'
                                </span>
                                <span class="right_param_l">
                                    <span class="val">'.$v_lng.'</span>
                                    <span class="name">'.($v == 'import_country_id' ? ($_COOKIE['lang'] == 'ru' ? 'Страна импорта' : ($_COOKIE['lang'] == 'en' ? 'Import country' : 'Țara de import')) : $lng['l']['car']['spec'][$v]).'</span>
                                </span>
                            </p>';
                    }
                }

                $rtrn .= '</div>';

                // ... (rest of the code remains the same)

                // webs25
                $p1Value = (isset($r['catalog_type']) && $r['catalog_type'] === 'on_order') ? 'ordercars' : 'cars';
                $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_seo2 WHERE `it_id`=:it_id AND `tp`="item" AND `p1`=:p1 AND lng = :lng LIMIT 1');
                $pdo->execute(['it_id' => $r['id'], 'p1' => $p1Value, 'lng' => $_COOKIE['lang']]);
                $rseo = $pdo->fetch() ?: [];
                $rseo['params_html'] = isset($rseo['params_html']) ? html_entity_decode($rseo['params_html']) : '';

                $bnt_params_mobile = '  ';
                $bnt_params_desktop = '  ';
                if( trim($rseo['params_html']) != '' ) {
                    $bnt_params_mobile = ' <div class="btn_params mobile" onclick=" openParamsPopAuto(\'open\')  " > '.$lng['w']['characteristics'].' </div> ';
                    $bnt_params_desktop = ' <div class="btn_params desktop" onclick=" openParamsPopAuto(\'open\')  " > '.$lng['w']['characteristics'].' </div> ';
                }
                
                $dynamicPhone = ((int)$import_country_id === 41) ? '37368689995' : PhoneHelper::getOrderPhone();

                $waPhone = ((int)$import_country_id === 41) ? '37368689995' : '37369535167';
                $waLang   = $_COOKIE['lang'] ?? 'ro';
                $waCarUrl = 'https://www.sauto.md/' . $waLang . '/ordercars/' . (int)$r['id'];
                $waUrl    = 'https://wa.me/' . $waPhone . '?text=' . rawurlencode($waCarUrl);
                $waIcon  = '<svg viewBox="-1.66 0 740.824 740.824" width="28" height="28" fill="#ffffff" aria-hidden="true" style="vertical-align:middle;"><path fill-rule="evenodd" clip-rule="evenodd" d="M630.056 107.658C560.727 38.271 468.525.039 370.294 0 167.891 0 3.16 164.668 3.079 367.072c-.027 64.699 16.883 127.855 49.016 183.523L0 740.824l194.666-51.047c53.634 29.244 114.022 44.656 175.481 44.682h.151c202.382 0 367.128-164.689 367.21-367.094.039-98.088-38.121-190.32-107.452-259.707m-259.758 564.8h-.125c-54.766-.021-108.483-14.729-155.343-42.529l-11.146-6.613-115.516 30.293 30.834-112.592-7.258-11.543c-30.552-48.58-46.689-104.729-46.665-162.379C65.146 198.865 202.065 62 370.419 62c81.521.031 158.154 31.81 215.779 89.482s89.342 134.332 89.311 215.859c-.07 168.242-136.987 305.117-305.211 305.117m167.415-228.514c-9.176-4.591-54.286-26.782-62.697-29.843-8.41-3.061-14.526-4.591-20.644 4.592-6.116 9.182-23.7 29.843-29.054 35.964-5.351 6.122-10.703 6.888-19.879 2.296-9.175-4.591-38.739-14.276-73.786-45.526-27.275-24.32-45.691-54.36-51.043-63.542-5.352-9.183-.569-14.148 4.024-18.72 4.127-4.11 9.175-10.713 13.763-16.07 4.587-5.356 6.116-9.182 9.174-15.303 3.059-6.122 1.53-11.479-.764-16.07-2.294-4.591-20.643-49.739-28.29-68.104-7.447-17.886-15.012-15.466-20.644-15.746-5.346-.266-11.469-.323-17.585-.323-6.117 0-16.057 2.296-24.468 11.478-8.41 9.183-32.112 31.374-32.112 76.521s32.877 88.763 37.465 94.885c4.587 6.122 64.699 98.771 156.741 138.502 21.891 9.45 38.982 15.093 52.307 19.323 21.981 6.979 41.983 5.994 57.793 3.633 17.628-2.633 54.285-22.19 61.932-43.616 7.646-21.426 7.646-39.791 5.352-43.617-2.293-3.826-8.41-6.122-17.585-10.714"/></svg>';
                $whatsappBtn = '<a class="btn btn-whatsapp" href="' . $waUrl . '" target="_blank" rel="noopener" aria-label="WhatsApp">' . $waIcon . '</a>';

                $rtrn .= '
                            <div class="prc  desktop">
                                <span class="val" title="'.$lng['w']['prc'].'">'.( $r['prc']>100 ? '<span class="i">'.parseCurr($prc).'</span> <span class="cur">'.( symb_rplc($r['cur']) ).'</span>' : '<span style="font-size: 1.5rem;">'.$lng['w']['negociabil'] ).'</span></span>
                                '.$o_prc_bl.'
                                '.$bnt_params_desktop.'
                            </div>
                            <div class="doit">
                                '. $bnt_params_mobile .'
                                
                                <a class="btn call" href="tel:'.$dynamicPhone.'">'.$lng['w']['call'].'</a>
                                
                                '.$whatsappBtn.'
 
                                

                                <div class="btn msg" style="display: none" >
                                    '.$lng['w']['message'].'

                                   
                                    
                                    <div class="data" style="display:none;">
                                        <form id="snd_msg" class="snd_msg" action="" method="post">
                                            <h3 class="ttl">'.$lng['w']['message'].'</h3>
                                            <input class="inp use" type="text" name="name" placeholder="'.$lng['w']['ur_name'].'" title="'.$lng['w']['ur_name'].'" />
                                            <input class="inp use imp" type="text" name="email" placeholder="Email*" title="Email" />
                                            <input class="inp use imp" type="text" name="phone" placeholder="'.$lng['w']['phone_numb'].'*" title="'.$lng['w']['phone_numb'].'" />
                                            <textarea class="inp use txt" name="msg" spellcheck="false" placeholder="'.$lng['w']['message'].'" title="'.$lng['w']['message'].'"></textarea>
                                            <input class="use" type="hidden" name="page" value="'.$_SERVER['REQUEST_URI'].'" />
                                            <input class="use" type="hidden" name="target" value="overlay" />
                                            <div class="agmt">
                                                <input type="checkbox" name="agmt" id="f_agmt" class="cbx cnfrm" checked="checked" />
                                                <span class="txt"><label for="f_agmt">'.$lng['t']['x']['prs_dat_agr'][1].'</label> <a class="x" href="/'.$_COOKIE['lang'].'/privacy" target="_blank" title="'.$lng['t']['x']['prs_dat_agr']['ttl'].'">'.$lng['t']['x']['prs_dat_agr'][2].'</a></span>
                                            </div>
                                            <input class="btn sbmt" type="submit" value="'.$lng['w']['send'].'" onclick="event.preventDefault();" data-sent="'.$lng['w']['msg_snt'].'" data-sending="'.$lng['w']['sending'].'" data-req_fld="'.$lng['w']['req_not_filled'].'" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                            </div>';

                // Landed-cost breakdown for parsing cars (computed once, reused by
                // mobile + desktop below). Encar uses the Korea route (sea RoRo),
                // OpenLane / eCarsTrade use the Europe route (road delivery). Empty
                // for non-parsing cars.
                $mdTable = '';
                $encarReport = ''; // Encar inspection report + equipment (public block)
                $parsingSrc = $r['parsing_source'] ?? '';
                if (!empty($r['parsing_id']) && in_array($parsingSrc, ['encar', 'openlane', 'ecarstrade', 'auto1'], true)) {
                    // The breakdown needs the SOURCE car price (in EUR), not the
                    // car_ctlg `prc` — that already holds the full landed MD price,
                    // so feeding it back would double-count customs/costs. Read the
                    // original price from the parsing entry via parsing_id.
                    $srcPriceEur = 0.0;
                    try {
                        $ps = $db->prepare('SELECT price_eur FROM '.$prefx.'_parsing_cars WHERE id = ? LIMIT 1');
                        $ps->execute([(int)$r['parsing_id']]);
                        $srcPriceEur = (float)($ps->fetchColumn() ?: 0);
                    } catch (Exception $e) {}

                    if ($srcPriceEur > 0) {
                        // Keep the hybrid sub-type so customs uses the right base
                        // fuel + discount (same as /calc): hbd = full hybrid (petrol,
                        // 25% off), pih = plug-in petrol (50% off), pid = plug-in
                        // diesel (50% off, diesel excise base).
                        $fuelMap = [
                            'gsl' => 'benzina', 'gmn' => 'benzina', 'gpn' => 'benzina', 'gas' => 'benzina',
                            'dsl' => 'diesel',
                            'hbd' => 'hybrid', 'pih' => 'hybrid_plugin', 'pid' => 'diesel_hybrid',
                            'elc' => 'electric',
                        ];
                        $carForBd = [
                            'price_eur' => $srcPriceEur,
                            'fuel'      => $fuelMap[$r['fl'] ?? ''] ?? '',
                            'capacity'  => (int)($r['vol'] ?? 0),
                            'year'      => (int)($r['yr'] ?? 0),
                        ];
                        $bd = ($parsingSrc === 'encar')
                            ? parsing_md_breakdown_kr($db, $prefx, $carForBd)
                            : parsing_md_breakdown_eu($db, $prefx, $carForBd);
                        $mdTable = parsing_md_price_table($bd, $_COOKIE['lang']);
                    }

                    // Encar cars also carry an official inspection report + equipment
                    // (report_data). Render it the same way the admin "Raport" modal
                    // does so the public page shows it as a static block under the
                    // MD price. Best-effort — silent if there's no report or it fails.
                    if ($parsingSrc === 'encar') {
                        try {
                            $rps = $db->prepare('SELECT report_data FROM '.$prefx.'_parsing_cars WHERE id = ? LIMIT 1');
                            $rps->execute([(int)$r['parsing_id']]);
                            $rpRow = $rps->fetch(PDO::FETCH_ASSOC);
                            $report = ($rpRow && !empty($rpRow['report_data'])) ? json_decode($rpRow['report_data'], true) : null;
                            if (is_array($report)) {
                                include_once _ADM_PAGE.'/parsing/parsing_report.php';
                                // Public page: two separate accordions — "Istoric"
                                // (inspection) and "Dotări" (equipment). VIN + photos
                                // hidden (3rd arg true).
                                $reportLang = $_COOKIE['lang'] ?? 'ro';
                                $histHtml = parsing_report_html($report, $reportLang, true, 'history');
                                $equipHtml = parsing_report_html($report, $reportLang, true, 'equipment');
                                // Both calls emit the same <style> block; keep it
                                // once (on the first) and strip it from the second.
                                $equipHtml = preg_replace('#<style>.*?</style>#s', '', $equipHtml, 1);
                                $encarReport = $histHtml . $equipHtml;
                            }
                        } catch (Throwable $e) { $encarReport = ''; }
                    }
                    // OpenLane: the Condition (damages) + Equipment HTML was baked into
                    // report_data['openlane_report'][lang] at publish time. Show it as a
                    // static block (always open) — no live OpenLane call here.
                    elseif ($parsingSrc === 'openlane') {
                        try {
                            $rps = $db->prepare('SELECT report_data FROM '.$prefx.'_parsing_cars WHERE id = ? LIMIT 1');
                            $rps->execute([(int)$r['parsing_id']]);
                            $rpRow = $rps->fetch(PDO::FETCH_ASSOC);
                            $report = ($rpRow && !empty($rpRow['report_data'])) ? json_decode($rpRow['report_data'], true) : null;
                            $olRep = $report['openlane_report'] ?? null;
                            if (is_array($olRep)) {
                                $reportLang = $_COOKIE['lang'] ?? 'ro';
                                $encarReport = $olRep[$reportLang] ?? ($olRep['ro'] ?? '');
                            }
                        } catch (Throwable $e) { $encarReport = ''; }
                    }
                    // Auto1: condition + equipment, baked in all three languages at
                    // publish time (ParsingPublisher::bakeAuto1Report) because
                    // rendering it live needs an authenticated API round-trip.
                    elseif ($parsingSrc === 'auto1') {
                        try {
                            $rps = $db->prepare('SELECT report_data FROM '.$prefx.'_parsing_cars WHERE id = ? LIMIT 1');
                            $rps->execute([(int)$r['parsing_id']]);
                            $rpRow = $rps->fetch(PDO::FETCH_ASSOC);
                            $report = ($rpRow && !empty($rpRow['report_data'])) ? json_decode($rpRow['report_data'], true) : null;
                            $a1Rep = $report['auto1_report'] ?? null;
                            if (is_array($a1Rep)) {
                                $reportLang = $_COOKIE['lang'] ?? 'ro';
                                $encarReport = $a1Rep[$reportLang] ?? ($a1Rep['ro'] ?? '');
                            }
                        } catch (Throwable $e) { $encarReport = ''; }
                    }
                }

               // Mobile: Encar parsing cars show the landed-cost table here (this
               // spot is in the main flow, visible on mobile). Others get the
               // normal mobile accordions. The desktop column renders its own
               // copy below (hidden on mobile via .md-price-mobile-only).
                if ($mdTable !== '') {
                    $rtrn .= '<div class="md-price-mobile-only">'.$mdTable
                           . ($encarReport !== '' ? '<div class="encar-report-public">'.$encarReport.'</div>' : '')
                           . '</div>';
                } else {
                    $parsedHtml = parseEquipmentSection($rseo['params_html']);
                    $rtrn .= getMobileAccordions($parsedHtml, $_COOKIE['lang'], $rseo['params_html']);
                }


                // Order info grid in left column (similar to html description in cars.php)
                $rtrn .= '<div class="pht_bx d_left_b">';
                
                // Encar parsing cars: landed-cost table here for DESKTOP (this
                // column is hidden on mobile; the mobile copy is rendered above).
                if ($mdTable !== '') {
                    $rtrn .= '<div class="md-price-desktop-only">'.$mdTable
                           . ($encarReport !== '' ? '<div class="encar-report-public">'.$encarReport.'</div>' : '')
                           . '</div>';
                } else {
                    $rtrn .= getDesktopDescriptionBlock($rseo['params_html'], $_COOKIE['lang']);
                }

                $rtrn .= '</div>';

                $rtrn .= '
                            <div class="spc_bx  d_right_b">

                                <div class="credit-calculator-container">
                                    <h3 class="calculator-title">'.$lng['w']['calc_title'].'</h3>
                                    <div class="calculator-content">

                                        <div class="calculator-field">
                                            <div class="field-header">
                                                <label for="view_suma_creditului" class="field-label">'.$lng['w']['calc_title_sum_tl'].'</label>
                                                <input type="text" id="view_suma_creditului" class="field-value clacl_inpt_vie" inputmode="numeric">
                                            </div>
                                            <input type="text" id="suma-creditului">
                                        </div>

                                        <div class="calculator-field">
                                            <div class="field-header">
                                                <label for="view_termen_creditului" class="field-label">'.$lng['w']['calc_title_term_tl'].'</label>
                                                <input type="text" id="view_termen_creditului" class="field-value clacl_inpt_vie" inputmode="numeric">
                                            </div>
                                            <input type="text" id="termen-creditului" name="termen_creditului">
                                        </div>

                                        <div class="payment-result">
                                            <span class="result-label">'.$lng['w']['calc_title_rata'].' (<span class="calc_btt_r1_nrl">24</span> '.$lng['w']['calc_title_luni'].')</span>
                                            <span class="result-value">'.$lng['w']['calc_title_plata'].' <span class="payment-number calc_btt_r2_nrl">0</span> '.$lng['w']['calc_title_plata2'].' <span class="payment-number calc_btt_r3_nrl">0</span> €</span>
                                        </div>

                                    </div>
                                </div>

 ';

                $seo_link_texts = [
                    'ro' => [
                        'all_series' => 'Toate',
                        'all_brand' => 'Toate automobilele',
                        'suffix' => ' la comandă'
                    ],
                    'ru' => [
                        'all_series' => 'Все',
                        'all_brand' => 'Все автомобили',
                        'suffix' => ' под заказ'
                    ],
                    'en' => [
                        'all_series' => 'All',
                        'all_brand' => 'All',
                        'suffix' => ' on order'
                    ]
                ];
                
                $current_lang = $_COOKIE['lang'];

                // --- CarVertical VIN Check Block ---
                $vin_check_texts = [
                    'ro' => ['title' => 'Verifică istoricul după VIN cod', 'subtitle' => '', 'btn' => 'Verifică acum', 'unavailable' => 'VIN indisponibil'],
                    'ru' => ['title' => 'Проверить историю по VIN коду', 'subtitle' => '', 'btn' => 'Проверить сейчас', 'unavailable' => 'VIN недоступен'],
                    'en' => ['title' => 'Check history by VIN code', 'subtitle' => '', 'btn' => 'Check now', 'unavailable' => 'VIN unavailable'],
                ];
                $vt = $vin_check_texts[$_COOKIE['lang']] ?? $vin_check_texts['ro'];
                $vin_val = trim($r['vin'] ?? '');
                $vin_enabled = isset($r['vin_check_enabled']) ? (int)$r['vin_check_enabled'] : 0;
                $cv_config = include($_SERVER['DOCUMENT_ROOT'] . '/App/config/carvertical.php');
                
                // Only show VIN check block if toggle is enabled
                if ($vin_enabled == 1 && strlen($vin_val) === 17 && !empty($cv_config['affiliate_id'])) {
                    $cv_url = '/'.$_COOKIE['lang'].'/vin-redirect?id='.$r['id'];
                    $rtrn .= '
                    <div style="margin:20px 0;padding:20px 24px;background:linear-gradient(135deg,#8a8a8a,#5a5a5a);border-radius:0.5rem;text-align:center;">
                        <div style="font-size:18px;font-weight:700;color:#fff;margin-bottom:4px;">'.$vt['title'].'</div>
                        <div style="font-size:14px;color:#ccc;margin-bottom:14px;">'.$vt['subtitle'].'</div>
                        <a href="'.$cv_url.'" target="_blank" rel="noopener" class="vin-attention-btn" style="display:inline-block;padding:12px 36px;background:#e2001a;color:#fff;font-size:15px;font-weight:600;border-radius:0.5rem;text-decoration:none;" onclick="if(typeof gtag===\'function\'){gtag(\'event\',\'vin.check.click\',{event_category:\'VIN Check\',event_label:\'Car ID: '.$r['id'].'\',car_id:'.$r['id'].',vin:\''.$vin_val.'\'});}">'.$vt['btn'].'</a>
                    </div>
                    <style>
                        @keyframes vinAttention {
                            0%, 100% {
                                transform: scale(1);
                                box-shadow: 0 4px 15px rgba(226,0,26,0.4), 0 0 0 0 rgba(226,0,26,0.7);
                            }
                            50% {
                                transform: scale(1.15);
                                box-shadow: 0 8px 35px rgba(226,0,26,0.7), 0 0 30px 8px rgba(226,0,26,0);
                            }
                        }
                        .vin-attention-btn {
                            animation: vinAttention 1.8s ease-in-out infinite;
                        }
                        .vin-attention-btn:hover {
                            animation-play-state: paused;
                            transform: scale(1.15);
                            box-shadow: 0 8px 35px rgba(226,0,26,0.7);
                        }
                    </style>';
                }


                $link_text_series = $seo_link_texts[$current_lang]['all_series'];
                $link_text_brand = $seo_link_texts[$current_lang]['all_brand'];
                $suffix = $seo_link_texts[$current_lang]['suffix'];
                
                $model_display = ($current_lang == 'ro') ? 'Seria ' . $r['mo_nm'] : $r['mo_nm'];
                
                $brand_series_url = buildCarUrl($r['br'], $r['mo']);
                $brand_url = buildCarUrl($r['br']);
                
                $rtrn .= '
                <div class="seo_links_block" style="margin: 20px 0 0 0; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                    <a href="/'.$current_lang.'/ordercars/'.$brand_series_url.'" target="_blank" class="seo-link-btn" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 28px; background: linear-gradient(135deg, #e63946, #d62828); color: #fff; text-decoration: none; font-size: 15px; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 4px 15px rgba(230,57,70,0.35); transition: all 0.3s ease; flex: 1; min-width: 250px; text-align: center;">
                        '.$link_text_series.' '.$r['br_nm'].' '.$model_display.$suffix.'
                    </a>
                    <a href="/'.$current_lang.'/ordercars/'.$brand_url.'" target="_blank" class="seo-link-btn" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 14px 28px; background: linear-gradient(135deg, #e63946, #d62828); color: #fff; text-decoration: none; font-size: 15px; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 4px 15px rgba(230,57,70,0.35); transition: all 0.3s ease; flex: 1; min-width: 250px; text-align: center;">
                        '.$link_text_brand.' '.$r['br_nm'].$suffix.'
                    </a>
                </div>
                <style>
                    .seo-link-btn:hover {
                        transform: scale(1.05);
                        box-shadow: 0 6px 20px rgba(230,57,70,0.5);
                    }
                </style>
                                
                            </div> ';

                $rtrn .= '<script>
                            $(document).ready(function () {
                                let updateRateTimeout;
                                let userIsEditing = false;
                                
                                var $input_suma_creditului = $("#view_suma_creditului");
                                // Credit amount slider
                                // Get car price from PHP
                                const carPrice = '.($r['prc'] > 2000 ? $r['prc'] : 2000).';
                                const sliderSuma = $("#suma-creditului").ionRangeSlider({
                                    skin: "round",
                                    min: 2000,
                                    max: 50000,
                                    from: Math.min(Math.max(carPrice, 2000), 50000),
                                    step: 500,
                                    onStart: function(data) {
                                        if (!$input_suma_creditului.val()) {
                                            $input_suma_creditului.prop("value", data.from);
                                        }
                                    },
                                    onChange: function (data) {
                                        if (!userIsEditing) {
                                            $input_suma_creditului.prop("value", data.from);
                                            clearTimeout(updateRateTimeout);
                                            updateRateTimeout = setTimeout(updateRate, 300);
                                        }
                                    }
                                }).data("ionRangeSlider");
                                
                                var $input_termen_creditului = $("#view_termen_creditului");
                                // Credit term slider
                                const sliderTermen = $("#termen-creditului").ionRangeSlider({
                                    skin: "round",
                                    min: 6,
                                    max: 60,
                                    from: 60,
                                    step: 1,
                                    onStart: function(data) {
                                        if (!$input_termen_creditului.val()) {
                                            $input_termen_creditului.prop("value", data.from);
                                        }
                                    },
                                    onChange: function (data) {
                                        if (!userIsEditing) {
                                            $input_termen_creditului.prop("value", data.from);
                                            clearTimeout(updateRateTimeout);
                                            updateRateTimeout = setTimeout(updateRate, 300);
                                        }
                                    }
                                }).data("ionRangeSlider");
                                
                                // Manual input - AMOUNT
                                $input_suma_creditului.on("focus", function() {
                                    userIsEditing = true;
                                    $(this).select();
                                }).on("blur", function() {
                                    userIsEditing = false;
                                    let val = parseInt($(this).val(), 10);
                                    if (isNaN(val)) val = 2000;
                                    val = Math.max(2000, Math.min(50000, val));
                                    val = Math.round(val / 500) * 500;
                                    $(this).val(val);
                                    sliderSuma.update({ from: val });
                                    updateRate();
                                }).on("input", function() {
                                    // Digits only — strip anything else as it is typed/pasted.
                                    var clean = $(this).val().replace(/\D+/g, "");
                                    if (clean !== $(this).val()) $(this).val(clean);
                                    let val = parseInt(clean, 10);
                                    if (!isNaN(val)) {
                                        val = Math.max(2000, Math.min(50000, val));
                                        val = Math.round(val / 500) * 500;
                                        sliderSuma.update({ from: val });
                                        updateRate();
                                    }
                                });
                                
                                // Manual input - TERM
                                $input_termen_creditului.on("focus", function() {
                                    userIsEditing = true;
                                    $(this).select();
                                }).on("blur", function() {
                                    userIsEditing = false;
                                    let val = parseInt($(this).val(), 10);
                                    if (isNaN(val)) val = 6;
                                    val = Math.max(6, Math.min(60, val));
                                    $(this).val(val);
                                    sliderTermen.update({ from: val });
                                    updateRate();
                                }).on("input", function() {
                                    // Digits only — strip anything else as it is typed/pasted.
                                    var clean = $(this).val().replace(/\D+/g, "");
                                    if (clean !== $(this).val()) $(this).val(clean);
                                    let val = parseInt(clean, 10);
                                    if (!isNaN(val)) {
                                        val = Math.max(6, Math.min(60, val));
                                        sliderTermen.update({ from: val });
                                        updateRate();
                                    }
                                });
                                
                                function updateRate() {
                                    // Get values directly from input fields for consistency
                                    const suma = parseInt($input_suma_creditului.val(), 10);
                                    const termen = parseInt($input_termen_creditului.val(), 10);
                                    
                                    // Check if values are valid
                                    if (isNaN(suma) || isNaN(termen)) {
                                        return; // Avoid calculation with invalid values
                                    }
                                    
                                    $(".calc_btt_r1_nrl").text(termen);
                                    
                                    // Calculate minimum rate (9.2%)
                                    const dobanda_min = 9.2; // percentage
                                    const rata_lunara_min = dobanda_min / 1200; // convert to decimal and monthly (9.2/100/12)
                                    const pmtMin = suma * rata_lunara_min / (1 - Math.pow(1 + rata_lunara_min, -termen));
                                    
                                    // Calculate maximum rate (24%)
                                    const dobanda_max = 24; // percentage
                                    const rata_lunara_max = dobanda_max / 1200; // convert to decimal and monthly (24/100/12)
                                    const pmtMax = suma * rata_lunara_max / (1 - Math.pow(1 + rata_lunara_max, -termen));
                                    
                                    // Round to integers
                                    const rata_min_final = Math.floor(pmtMin);
                                    const rata_max_final = Math.floor(pmtMax); // Use Math.floor for both rates for consistency
                                    
                                    // Display rates
                                    $(".calc_btt_r2_nrl").text(rata_min_final);
                                    $(".calc_btt_r3_nrl").text(rata_max_final);
                                }
                                
                                // Set initial values
                                const initialAmount = Math.min(Math.max(carPrice, 2000), 50000);
                                $input_suma_creditului.val(initialAmount);
                                $input_termen_creditului.val(60);
                                
                                // Update sliders to be synchronized
                                sliderSuma.update({ from: initialAmount });
                                sliderTermen.update({ from: 60 });                         

                                // Delay first calculation to ensure all components are properly initialized
                                setTimeout(updateRate, 100);
                            });
                            </script>';
                          
                /*
                 * <div class="calc_btn_btt">
                            <div class="calc_btn_point">
                                '.$lng['w']['calc_title_btn'].'
                            </div>
{{ ... }}
                 */



                /*
                $rtrn .= '
                <!--<div class="inf_bx">
                    <div class="menu">
                        <div class="btn act" data-name="desc">
                            '.$lng['w']['description'].'
                            <div class="ln"></div>
                        </div>
                        <div class="btn" data-name="spec">
                            '.$lng['w']['characteristics'].'
                            <div class="ln"></div>
                        </div>
                    </div>
                    <div class="bx">
                        <div class="cnt act" data-name="desc">';
                            for ($i=1; $i<20; $i++){
                                $rtrn .= 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. ';
                            }
                        $rtrn .= '
                        </div>
                        <div class="cnt" data-name="spec">';
                            for ($i=1; $i<2; $i++){
                                $rtrn .= 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. ';
                            }
                        $rtrn .= '
                        </div>
                    </div>
                </div>-->';
                */
               // Similar Price Cars Block - new algorithm
                $similarPriceResult = getSimilarPriceCars($r, 8, $db, $prefx, $lng, $img_frmt);
                
                $rtrn .= '
                <div class="smlr gr similar-price-block">
                    <h3>' . ($lng['w']['similar_price_title'] ?? 'Автомобили по схожей цене') . '</h3>';
                
                if (!empty($similarPriceResult['message'])) {
                    $rtrn .= '<p class="cross-section-notice">' . $similarPriceResult['message'] . '</p>';
                }
                
                $rtrn .= '<div class="cnt">';
                $rtrn .= $similarPriceResult['txt'];
                $rtrn .= '
                    </div>
                </div>';
            }

            if ( $chkr_av == 1 ){ 
                $rtrn .= '<script> $(document).ready(function(){ $("#crumbs .crnt").html("<a style=\"color:inherit;\" href=\"/"+$("body").data("lng")+"/ordercars/'.buildCarUrl($r['br'], $r['mo']).'\">'.$r['br_nm'].' '.$r['mo_nm'].'</a>"); }) </script>'; 
            } else {
                // car not found, return 404 instead of showing no item block
                http_response_code(404);
                include(_DEFAULT.'/404.php');
                exit;
            }
        }
    }
}

echo $rtrn;

echo '<script>(function(){var r=document.querySelector("body > .srt_row");if(!r)return;var h=document.querySelector("main .gr > h1, main .gr > div > h1");if(!h||!h.textContent.trim())return;h.classList.add("srt_h1");r.insertBefore(h,r.firstChild);})();</script>';

// Ordercar contact modal
$_oc_lang = $_COOKIE['lang'] ?? 'ro';
ob_start();
sauto_contact_form(['lang' => $_oc_lang, 'source' => 'ordercars']);
$_oc_form = ob_get_clean();
echo '
<div id="ordercar-contact-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)closeOrdercarContactModal()">
    <div style="position:relative;background:#fff;padding:2rem;border-radius:12px;max-width:520px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <button onclick="closeOrdercarContactModal()" style="position:absolute;top:12px;right:12px;background:none;border:none;cursor:pointer;color:#aaa;padding:6px;line-height:1;border-radius:50%;transition:color 0.15s,background 0.15s;" onmouseover="this.style.color=\'#E61E2D\';this.style.background=\'#fff0f0\'" onmouseout="this.style.color=\'#aaa\';this.style.background=\'none\'"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        '.$_oc_form.'
    </div>
</div>
<script>
function openOrdercarContactModal(){document.getElementById("ordercar-contact-modal").style.display="flex";document.body.style.overflow="hidden";}
function closeOrdercarContactModal(){document.getElementById("ordercar-contact-modal").style.display="none";document.body.style.overflow="";}
</script>
';
