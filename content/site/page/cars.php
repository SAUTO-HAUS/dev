<?php
defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;

// Include car description functions
require_once(__DIR__ . '/../include/car_description.php');

// If this is a 404 page, show 404 content and exit
if (isset($GLOBALS['page_is_404']) && $GLOBALS['page_is_404'] === true) {
    include(_DEFAULT.'/404.php');
    exit;
}

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
?>
    <style>
        .payment-amount {
            color: #000;
        }
        .payment-number {
            color: #ff0000;
            font-weight: bold;
        }
    </style>


<?php // webs25 ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.css" />

    <link rel="stylesheet" href="/content/site/css/cars_gallery.css?v=<?=time()?>" />
    <link rel="stylesheet" href="/content/site/css/car_description.css?v=<?=time()?>" />
    <script src="/content/site/js/car_description.js?v=<?=time()?>"></script>

<?php

// Debug log for all parameters
// file_put_contents('filter_debug.log', "\n\nNew request at: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
// file_put_contents('filter_debug.log', "URL: {$_SERVER['REQUEST_URI']}\n", FILE_APPEND);
// file_put_contents('filter_debug.log', "Query string: {$_SERVER['QUERY_STRING']}\n", FILE_APPEND);
// file_put_contents('filter_debug.log', "GET params: " . print_r($_GET, true) . "\n", FILE_APPEND);

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
$cr_lmt = $isMobile=='1' ? 960 : 960;

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
        // file_put_contents('filter_debug.log', "Fixed malformed query string to: {$query_string}\n", FILE_APPEND);
    }

    // Now parse the cleaned query string
    parse_str($query_string, $query_params);
    foreach ($query_params as $key => $value) {
        $_GET[$key] = $value;
    }

    // Log the corrected parameters
    // file_put_contents('filter_debug.log', "Corrected GET params: " . print_r($_GET, true) . "\n", FILE_APPEND);
}

// Then handle clean URLs for car filters and single car pages
if (isset($t_mp[3]) && !is_numeric($t_mp[3])) {
    // Remove any query string from the URL segments
    $brand = explode('?', $t_mp[3])[0];
    $_GET['tg'] = 'fltr';
    $_GET['br'] = str_replace('-', '_', $brand);

    if (isset($t_mp[4])) {
        $model = explode('?', $t_mp[4])[0];
        $_GET['mo'] = str_replace('-', '_', $model);
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

    // This is a filtered catalogue page
    $card = $car_card('fltr', $cr_lmt, $_GET, 'av');

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
    $rtrn .= '<div class="cnt list">';
    $rtrn .= $card['txt'];
    $rtrn .= '</div>';

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
        // No results found - add a message
        if (empty($card['txt'])) {
            $rtrn = '<div class="gr">';
            $rtrn .= '<h1 style="font-size: inherit;">'.$sa['meta']['h1'].'</h1>';
            $rtrn .= '<div class="cnt list">';
            $rtrn .= '<div class="no-results">'.$lng['w']['no_results'].'</div>';
            $rtrn .= '</div>';
            $rtrn .= '</div>';

            // file_put_contents('debug_sql.log', "No results found for filter parameters: " . print_r($_GET, true) . "\n", FILE_APPEND);
        }
    }
}
// Base catalogue page
elseif (!isset($t_mp[3])) {
    $rtrn .= '<div class="gr">';
    $rtrn .= '<h1>'.$sa['meta']['h1'].'</h1>';
    
    // Add multilingual text and button for ordercars using language.php
    $ordercars_text = $lng['w']['ordercars_promo_text'];
    $ordercars_button_text = $lng['w']['ordercars_button_text'];
    
    $rtrn .= '<div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #ff0000; border-radius: 4px;">';
    $rtrn .= '<p style="margin: 0 0 10px 0; color: #333; font-size: 16px;">'.$ordercars_text.'</p>';
    $rtrn .= '<a href="/'.$_COOKIE['lang'].'/ordercars" style="display: inline-block; background-color: #ff0000; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor=\'#cc0000\'" onmouseout="this.style.backgroundColor=\'#ff0000\'">'.$ordercars_button_text.'</a>';
    $rtrn .= '</div>';
    
    $rtrn .= '<div class="cnt list">';
    $card = $car_card('new', $cr_lmt, null);
    $rtrn .= $card['txt'];
    $rtrn .= '</div>';
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
        $url_segments = explode('-', $t_mp[3]);
        $last_segment = end($url_segments);
        
        if (is_numeric($last_segment) && count($url_segments) > 1) {
            $old_car_id = (int)$last_segment;
            
            try {
                $pdo_check = $db->prepare('SELECT `id` FROM '.$prefx.'_car_ctlg WHERE `id`= :id AND `vis`="1" AND `act`="1" LIMIT 1');
                $pdo_check->execute(['id' => $old_car_id]);
                $old_car = $pdo_check->fetch(PDO::FETCH_ASSOC);
                
                if (!$old_car) {
                    // file_put_contents('debug_sql.log', "Old URL format detected with ID {$old_car_id} - car not found, returning 404\n", FILE_APPEND);
                    http_response_code(404);
                    include(_DEFAULT.'/404.php');
                    exit;
                }
                
                $it_id = $old_car_id;
                // file_put_contents('debug_sql.log', "Old URL format detected with ID {$old_car_id} - car found\n", FILE_APPEND);
            } catch (PDOException $e) {
                error_log('PDO Error checking old car ID ' . $old_car_id . ': ' . $e->getMessage());
                http_response_code(404);
                include(_DEFAULT.'/404.php');
                exit;
            }
        } else {

            $brand = str_replace('-', '_', $t_mp[3]);
            $model = isset($t_mp[4]) ? str_replace('-', '_', $t_mp[4]) : null;

            $sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE `br`=:brand';
            $params = ['brand' => $brand];

            if ($model) {
                $sql .= ' AND `mo`=:model';
                $params['model'] = $model;
            }

            $sql .= ' AND `vis`="1" AND `act`="1" AND `catalog_type`="in_stock" LIMIT 1';

            $pdo = $db->prepare($sql);
            $pdo->execute($params);
            $car = $pdo->fetch(PDO::FETCH_ASSOC);

            if ($car) {
                $it_id = $car['id'];
                // file_put_contents('debug_sql.log', "Found car with ID: {$it_id}\n", FILE_APPEND);
            } else {
                // file_put_contents('debug_sql.log', "No car found for brand: {$brand}, model: {$model}\n", FILE_APPEND);
            }
        }
    } else {
        $it_id = toNumber($t_mp[3]);

        if ( $it_id > 0 ){
            $spec_ar = ['yr', 'bt', 'mlg', 'vol', 'hp', 'fl', 'tra', 'wd', 'clr', 'sts', 'loc', 'import_country_id'];

            //Update views
            try {
                $view_update = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `views` = `views` + 1 WHERE `id` = :id');
                $view_update->execute(['id' => $it_id]);
            } catch (PDOException $e) {
                // Log the error but don't crash the page
                error_log('PDO Error updating views for car ID ' . $it_id . ': ' . $e->getMessage());
            }

            try {
                $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id AND `vis`="1" AND `act`="1" LIMIT 1');
                $pdo->execute(['id' => $it_id]);
            } catch (PDOException $e) {
                // Log the error but don't crash the page
                error_log('PDO Error selecting car data for ID ' . $it_id . ': ' . $e->getMessage());
                // file_put_contents('debug_sql.log', "PDO Error selecting car data for ID: {$it_id} - " . $e->getMessage() . "\n", FILE_APPEND);
                // Create empty result to prevent foreach errors
                $pdo = [];
            }

            foreach ($pdo as $r){
                $chkr_av = 1;

                //Collect photos
                $pdo = $db->prepare('SELECT `name`, `main` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id ORDER BY `pos` ASC');
                $pdo->execute(['it_id'=>$r['id']]);
                $img = [];
                $i=2;
                foreach($pdo as $r2){
                    if ($r2['main']=='1'){ $img['main'] = $r2['name']; $ii = 1;}else{$ii = $i; $i++;}
                    $img['all'][$ii] = ['name'=>$r2['name'], 'main'=>$r2['main']];
                }
                unset($r2, $ii);
                //if (isset($img['all'])){ ksort($img['all']); }else{ $img = ['all'=>[], 'main'=>'unknown']; }

                $z_stat = '';
                if ( $r['n_a']==0 && $r['act']==1 ){
                    $z_stat .= ( $r['soon']==1 ) ? '<div class="stat soon1">'.$lng['l']['stat']['soon1'].'</div>' : '<div class="stat n_a0">'.$lng['l']['stat']['n_a0'].'</div>';
                    $z_stat .= ($r['top']==1) ? '<div class="stat top1">'.$lng['l']['stat']['top1'].'</div>' : '';
                    $z_stat .= ($r['prc_n']!=0 && $r['prc_t']>time()) ? '<div class="stat prc_n">'.$lng['l']['stat']['prc_n'].'</div>' : '';
                    $z_stat .= ($r['tva']==1) ? '<div class="stat top1">'.$lng['l']['stat']['vat'].'</div>' : '';
                    $z_stat .= ($r['gift']==1) ? '<div class="stat gift">+ '.$lng['l']['stat']['gift'].'</div>' : '';

                    // Adăugare țară de import cu stil evident
                    $import_country_id = isset($r['import_country_id']) ? $r['import_country_id'] : null;

                    // Forțăm verificarea în baza de date dacă nu avem import_country_id
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
                        if (!empty($country_name)) {
                            $country_label = $_COOKIE['lang'] == 'ru' ? 'Страна импорта' : ($_COOKIE['lang'] == 'en' ? 'Import country' : 'Țara de import');
                            $z_stat .= '<div class="stat import"><b>'.$country_label.':</b> '.$country_name.'</div>';
                        }
                    }
                }else{
                    $z_stat .= '
						<div class="stat n_a1">'.$lng['l']['stat']['n_a1'].'</div>
						<a href="/'.$_COOKIE['lang'].'/cars/'.buildCarUrl($r['br'], $r['mo']).'" class="stat soon1">'.$lng['w']['fnd_smlr'].'</a>
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
                    $z_src = '/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/med/'.$v['name'].$img_frmt;
                    $rtrn .= '<img class="item '.$act.'" alt="car '.$r['br_nm'].' '.$r['mo_nm'].' id'.$r['id'].' photo #'.$img_cnt.'" data-pos="'.$k.'" src="'.$z_src.'" width="100%" height="auto" />';
                }
                $rtrn .= '
                                </div>
                            </div>';
                $z_src = isset($img['main'])?'/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$img['main'].$img_frmt:'';
                //$z_src = (@getimagesize($site_url.$z_src)?$z_src:'');
                $rtrn .= '<div class="big_pht" role="img" aria-label="car '.$r['br_nm'].' '.$r['mo_nm'].' id'.$r['id'].' large photo" data-pos="1" data-cnt="'.$img_cnt.'" style="background-image:url('.$z_src.');" data-src="'.$z_src.'"></div>';
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
                    <div class="f-carousel" id="heroCarousel">

                        <?
                        if(empty($img) || !$img['main'] ) {
                            ?>
                            <div class="f-carousel__slide">
                                <a href="/media/images/placeholder_car.png" data-fancybox="product" data-id="p<?=$img_cnt?>"
                                   data-title="<?=$r['br']?> <?=$r['mo']?>, <?=$r['yr']?>, <?=$r['mlg']?>, <?=$lng['l']['car']['fl'][$r['fl']]?> <?=$lng['l']['car']['tra'][$r['tra']]?>"
                                   data-price="<?=$prc?> <?=$cur?>">
                                    <img src="/media/images/placeholder_car.png" loading="lazy" class="lazy" alt="<?=$img_cnt?>">
                                </a>
                            </div>
                            <?
                        }

                        if(!empty($img) ) {
                            $img_cnt = 0;
                            foreach($img['all'] as $k => $v){
                                $img_cnt++;
                                $z_src = '/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/med/'.$v['name'].$img_frmt;

                                $z_src2 = isset($img['main'])?'/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$v['name'].$img_frmt:'';
                                ?>
                                <div class="f-carousel__slide">
                                    <a href="<?=$z_src2?>" data-fancybox="product" data-id="p<?=$img_cnt?>"
                                       data-title="<?=$r['br']?> <?=$r['mo']?>, <?=$r['yr']?>, <?=$r['mlg']?>, <?=$lng['l']['car']['fl'][$r['fl']]?> <?=$lng['l']['car']['tra'][$r['tra']]?>"
                                       data-price="<?=$prc?> <?=$cur?>">
                                        <img src="<?=$z_src?>" loading="lazy" class="lazy" alt="<?=$img_cnt?>">
                                    </a>
                                </div>
                                <?
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

                $rtrn .= ' <div class="prc pricemobile">
                                        <span class="val" title="'.$lng['w']['prc'].'">'.( $r['prc']>100 ? '<span class="i">'.parseCurr($prc).'</span> <span class="cur">'.( symb_rplc($r['cur']) ).'</span>' : '<span style="font-size: 1.5rem;">'.$lng['w']['negociabil'] ).'</span></span>
                                        '.$o_prc_bl.'
                                    </div> 
                            
                                    <div class="clear"> </div>
                            ';

                // Colectăm informațiile despre țara de import
                $import_country_id = isset($r['import_country_id']) ? $r['import_country_id'] : null;

                // Verificăm direct în baza de date dacă nu găsim import_country_id
                if (empty($import_country_id)) {
                    $stmt = $db->prepare("SELECT import_country_id FROM ".$prefx."_car_ctlg WHERE id = :id LIMIT 1");
                    $stmt->execute(['id' => $r['id']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result && isset($result['import_country_id'])) {
                        $import_country_id = $result['import_country_id'];
                    }
                }

                // Pregătim informația despre țara de import
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
                            $flag_html = '<img src="/media/images/flags/' . $country_code . '.svg" alt="' . $country_name . ' flag" style="width: 38px; height: 32px; display: block; margin-left: auto;">';
                        }

                        // Prepare the import country text with same styling as product cards
                        $import_country_text = '<div style="font-weight: bold; text-align: right; min-width: 200px;"><span style="color: #666; font-weight: 500;">' . $country_label . ': </span><span style="color: #000000; font-weight: bold;">' . $country_name . '</span></div>';
                    }
                }

                $rtrn .= '<div class="status">'.$z_stat.'</div>';

                // Display the characteristics title
                $rtrn .= '<div>';
                // Add flag above if exists
                if (!empty($flag_html)) {
                    $rtrn .= '<div style="text-align: right; margin-bottom: -5px; margin-top: -20px; margin-right: -5px;">' . $flag_html . '</div>';
                }
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
                ?>


                <?
                if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
                    $prc = $r['prc_n'];
                    $o_prc = $r['prc'];
                    $o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.parseCurr($o_prc).'</span> '.( symb_rplc($r['cur']) ).'</span>';
                }
                else{
                    $prc = $r['prc'];
                    $o_prc = 0;
                    $o_prc_bl = '';
                }

                // Get dynamic phone number based on car data and context
                $dynamicPhone = PhoneHelper::getCarPhone($r, 'car_page');

                // webs25
                $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_seo2 WHERE `it_id`=:it_id AND lng = :lng LIMIT 1');
                $pdo->execute(['it_id' => $r['id'], 'lng' => $_COOKIE['lang']]);
                $rseo = $pdo->fetch();
                // var_dump( $rseo);
                $rseo['params_html'] = (html_entity_decode($rseo['params_html']));

                $bnt_params_mobile = '  ';
                $bnt_params_desktop = '  ';
                if( trim($rseo['params_html']) != '' ) {
                    $bnt_params_mobile = ' <div class="btn_params mobile" onclick=" openParamsPopAuto(\'open\')  " > '.$lng['w']['characteristics'].' </div> ';
                    $bnt_params_desktop = ' <div class="btn_params desktop" onclick=" openParamsPopAuto(\'open\')  " > '.$lng['w']['characteristics'].' </div> ';
                }

                $rtrn .= '
                            <div class="prc  desktop">
                                <span class="val" title="'.$lng['w']['prc'].'">'.( $r['prc']>100 ? '<span class="i">'.parseCurr($prc).'</span> <span class="cur">'.( symb_rplc($r['cur']) ).'</span>' : '<span style="font-size: 1.5rem;">'.$lng['w']['negociabil'] ).'</span></span>
                                '.$o_prc_bl.'
                                '.$bnt_params_desktop.'
                            </div>
                            <div class="doit">
                                '. $bnt_params_mobile .'
                                
                                <a class="btn call" href="tel:'.$dynamicPhone.'">'.$lng['w']['call'].'</a>
                                
                                 <div class="btn msg2">
                                  <script data-b24-form="click/6/ijhsqr" data-skip-moving="true">
(function(w,d,u){
var s=d.createElement(\'script\');s.async=true;s.src=u+\'?\'+(Date.now()/180000|0);
var h=d.getElementsByTagName(\'script\')[0];h.parentNode.insertBefore(s,h);
})(window,document,\'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_6.js\');
</script>
                                </div>
                                
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

                // Mobile accordions (under specs, before calculator)
                $rtrn .= '<div style="clear:both;"></div>';
                $parsedHtml = parseEquipmentSection($rseo['params_html']);
                $rtrn .= getMobileAccordions($parsedHtml, $_COOKIE['lang']);

                // Desktop description
                $rtrn .= '<div class="pht_bx d_left_b">';
                $rtrn .= getDesktopDescriptionBlock($rseo['params_html'], $_COOKIE['lang'], 'stock');
                $rtrn .= '</div>';

                $rtrn .= '
                            <div class="spc_bx  d_right_b"> 
                                <!--Plugin CSS file with desired skin-->
                                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/css/ion.rangeSlider.min.css"/>
                                <!--Plugin JavaScript file-->
                                <script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/js/ion.rangeSlider.min.js"></script>
                                <div class="calc_head"> '.$lng['w']['calc_title'].' </div>
                                
                                <div class="calc_block_sum">
                                <!-- заголовок и отображение текущего значения слайдера -->
                                    <div class="calc_inpt_cont">
                                        <div class="calc_ipt_tl">
                                            '.$lng['w']['calc_title_sum_tl'].'
                                        </div>
                                        <div class="calc_inpt_blk">
                                        <!-- отображение текущего значения слайдера -->
                                            <input type="text" id="view_suma_creditului"  class="clacl_inpt_vie">
                                        </div>
                                    </div>
                                 <!-- элемент вызова слайдера -->
                                    <input type="text" id="suma-creditului" >
                                </div>
                                    
                                <div class="calc_block_terms">
                                <!-- заголовок и отображение текущего значения слайдера -->
                                    <div class="calc_inpt_cont">
                                        <div class="calc_ipt_tl">
                                            '.$lng['w']['calc_title_term_tl'].'
                                        </div>
                                        <div class="calc_inpt_blk">
                                        <!-- отображение текущего значения слайдера -->
                                            <input type="text" id="view_termen_creditului"  class="clacl_inpt_vie">
                                        </div>
                                    </div>
                                     <!-- элемент вызова слайдера -->
                                    <input type="text" id="termen-creditului" name="termen_creditului">
                                </div>
                                
                                
                                <div style="clear: both"> </div>
                                
                                <!-- отображение результатов расчета калькулятора -->
                                <div class="calc_btt_word">
                                    <div class="calc_btt_left">
                                        '.$lng['w']['calc_title_rata'].'
                                    </div>
                                    <div class="calc_btt_right">
                                        <div class="calc_btt_r1">
                                            <span class="calc_btt_r1_nrl"> 24 </span> '.$lng['w']['calc_title_luni'].'
                                        </div>
                                        <div class="calc_btt_r2">
                                            <span class="payment-amount">'.$lng['w']['calc_title_plata'].' <span class="payment-number calc_btt_r2_nrl">0</span> '.$lng['w']['calc_title_plata2'].' <span class="payment-number calc_btt_r3_nrl">0</span></span>
                                        </div>
                                    </div>
                                </div> 
                                
                                <div style="clear: both"> </div>
                                
                                ';
                $seo_link_texts = [
                    'ro' => [
                        'all_series' => 'Toate',
                        'all_brand' => 'Toate automobilele',
                        'suffix' => ' în stoc'
                    ],
                    'ru' => [
                        'all_series' => 'Все',
                        'all_brand' => 'Все автомобили',
                        'suffix' => ' в наличии'
                    ],
                    'en' => [
                        'all_series' => 'All',
                        'all_brand' => 'All',
                        'suffix' => ' in stock'
                    ]
                ];
                
                $current_lang = $_COOKIE['lang'];
                $link_text_series = $seo_link_texts[$current_lang]['all_series'];
                $link_text_brand = $seo_link_texts[$current_lang]['all_brand'];
                $suffix = $seo_link_texts[$current_lang]['suffix'];
                
                $model_display = ($current_lang == 'ro') ? 'Seria ' . $r['mo_nm'] : $r['mo_nm'];
                
                $brand_series_url = buildCarUrl($r['br'], $r['mo']);
                $brand_url = buildCarUrl($r['br']);
                
                $rtrn .= '
                <div class="seo_links_block" style="margin: 20px 0; padding: 15px; background-color: #FFE6E6; border-radius: 4px;">
                    <a href="/'.$current_lang.'/cars/'.$brand_series_url.'" target="_blank" style="display: block; margin-bottom: 10px; color: #333; text-decoration: none; font-size: 17px;" onmouseover="this.style.textDecoration=\'underline\'; this.style.color=\'#ff0000\'" onmouseout="this.style.textDecoration=\'none\'; this.style.color=\'#333\'">
                        '.$link_text_series.' '.$r['br_nm'].' '.$model_display.$suffix.'
                    </a>
                    <a href="/'.$current_lang.'/cars/'.$brand_url.'" target="_blank" style="display: block; color: #333; text-decoration: none; font-size: 17px;" onmouseover="this.style.textDecoration=\'underline\'; this.style.color=\'#ff0000\'" onmouseout="this.style.textDecoration=\'none\'; this.style.color=\'#333\'">
                        '.$link_text_brand.' '.$r['br_nm'].$suffix.'
                    </a>
                </div>
                            </div> ';

                $rtrn .= '<div style="clear:both"></div>';

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
                                    let val = parseInt($(this).val(), 10);
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
                                    let val = parseInt($(this).val(), 10);
                                    if (!isNaN(val)) {
                                        val = Math.max(6, Math.min(60, val));
                                        sliderTermen.update({ from: val });
                                        updateRate();
                                    }
                                });
                                
                                function updateRate() {
                                    // Obținem valorile direct din input fields pentru consistență
                                    const suma = parseInt($input_suma_creditului.val(), 10);
                                    const termen = parseInt($input_termen_creditului.val(), 10);
                                    
                                    // Verificăm dacă valorile sunt valide
                                    if (isNaN(suma) || isNaN(termen)) {
                                        return; // Evităm calculul cu valori invalide
                                    }
                                    
                                    $(".calc_btt_r1_nrl").text(termen);
                                    
                                    // Calcul pentru rata minimă (9.2%)
                                    const dobanda_min = 9.2; // procente
                                    const rata_lunara_min = dobanda_min / 1200; // convertim în decimal și lunar (9.2/100/12)
                                    const pmtMin = suma * rata_lunara_min / (1 - Math.pow(1 + rata_lunara_min, -termen));
                                    
                                    // Calcul pentru rata maximă (24%)
                                    const dobanda_max = 24; // procente
                                    const rata_lunara_max = dobanda_max / 1200; // convertim în decimal și lunar (24/100/12)
                                    const pmtMax = suma * rata_lunara_max / (1 - Math.pow(1 + rata_lunara_max, -termen));
                                    
                                    // Rotunjire la numere întregi
                                    const rata_min_final = Math.floor(pmtMin);
                                    const rata_max_final = Math.floor(pmtMax); // Folosim Math.floor pentru ambele rate pentru consistență
                                    
                                    // Afișare rate
                                    $(".calc_btt_r2_nrl").text(rata_min_final);
                                    $(".calc_btt_r3_nrl").text(rata_max_final);
                                }
                                
                                // Setare valori inițiale
                                const initialAmount = Math.min(Math.max(carPrice, 2000), 50000);
                                $input_suma_creditului.val(initialAmount);
                                $input_termen_creditului.val(60);
                                
                                // Actualizăm și slider-ele pentru a fi sincronizate
                                sliderSuma.update({ from: initialAmount });
                                sliderTermen.update({ from: 60 });
                                
                                // Amânăm prima calculare pentru a ne asigura că toate componentele sunt inițializate corect
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
                $rtrn .= '
                <div class="smlr gr">
                    <h3>'.$lng['t']['seo']['car_inf_h3'].'</h3>
                    <div class="cnt">';
                $card = $car_card('smlr', 4, $r); 
                $rtrn .= $card['txt'];
                $rtrn .= '
                    </div>
                </div>';
            }

            if ( $chkr_av == 1 ){ 
                $rtrn .= '<script> $(document).ready(function(){ $("#crumbs .crnt").html("<a style=\"color:inherit;\" href=\"/"+$("body").data("lng")+"/cars/'.buildCarUrl($r['br'], $r['mo']).'\">'.$r['br_nm'].' '.$r['mo_nm'].'</a>"); }) </script>'; 
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