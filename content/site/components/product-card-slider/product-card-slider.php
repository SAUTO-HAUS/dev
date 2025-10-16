<?php
/**
 * Product Card Slider Functions
 * Enhanced car_card function with image slider support
 */

/**
 * Enhanced car_card function with slider support
 * 
 * @param string $v1 - Type of cards (new, archive, top, smlr, fltr, etc.)
 * @param string $lmt - Limit of cards to show
 * @param array $zreq - Request parameters for filtering
 * @param string $stts - Status filter (av, na)
 * @param bool $enable_slider - Enable slider functionality (default: true)
 * @return array - Array with ids, txt (HTML), and qu (quantity)
 */
function car_card_with_slider($v1='', $lmt='4', $zreq=null, $stts='av', $enable_slider=true) {
    global $prefx, $db, $img_frmt, $lng;
    
    $ar = [ 'ids'=>[], 'txt'=>'', 'qu'=>0 ];
    $debug_enabled = false;
    
    $specs_arr = ['yr'=>0, 'vol'=>0, 'fl'=>1, 'tra'=>1, 'mlg'=>0];
    $f_arr = ['bt'=>0, 'gr'=>0, 'br'=>0, 'mo'=>0, 'yr'=>1, 'fl'=>0, 'tra'=>0, 'wd'=>0, 'clr'=>0, 'mlg'=>1, 'vol'=>1, 'sts'=>1, 'prc'=>1];
    
    $query_args = ['lmt'=>$lmt];
    $sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1';
    
    // Apply the same filtering logic as original car_card function
    if ($v1=='new'){ $sql .= ' AND `vis`="1" AND `act`="1" '; }
    elseif ($v1=='archive'){ $sql .= ' AND `vis`="1" AND `act`="0" '; }
    elseif ($v1=='top'){ $sql .= ' AND `vis`="1" AND `act`="1" AND `top`="1" '; }
    elseif ($v1=='smlr'){ 
        if ($zreq!==null){
            $sql .= ' AND `act`="1" AND `n_a`="0" AND (`prc` BETWEEN :prc_min AND :prc_max ) AND `id`<>:prc_id ';
            $query_args['prc_min'] = (($zreq['prc']*1)-1000);
            $query_args['prc_max'] = (($zreq['prc']*1)+1000);
            $query_args['prc_id'] = $zreq['id'];
        }
        $sql .= ' ORDER BY RAND() DESC LIMIT :lmt';
    }
    
    if ($stts=='av'){ $sql .= ' AND `vis`="1" AND `act`="1" '; }
    else if ($stts=='na'){ $sql .= ' AND `vis`="1" AND `act`="0" '; }
    
    if ($v1!='smlr'){ $sql .= ' ORDER BY `n_a` ASC, `id` DESC LIMIT :lmt '; }
    
    try {
        $pdo = $db->prepare($sql);
        $pdo->execute($query_args);
        $results = $pdo->fetchAll(PDO::FETCH_ASSOC);
        
        $i=0;
        foreach ($results as $r){
            $ar['ids'][] = 'c'.$r['id']; 
            $ar['br'] = $r['br_nm']; 
            $ar['mo'] = $r['mo_nm']; 
            $ar['qu']++;
        }
    } catch (PDOException $e) {
        if ($debug_enabled) {
            $ar['txt'] .= "SQL Error: " . $e->getMessage() . "\n";
        }
    }

    // Generate HTML for each car
    foreach ($results as $r) {
        // Get all images for slider (not just main image)
        if ($enable_slider) {
            $pdo2 = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id ORDER BY `main` DESC, `pos` ASC'); 
        } else {
            $pdo2 = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id AND `main`="1" LIMIT 1'); 
        }
        $pdo2->execute([ 'it_id'=>$r['id'] ]); 
        $images = $pdo2->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate status badges
        $z_stat = '';
        if ( $r['n_a']==0 && $r['act']==1 ){
            $z_stat .= ( $r['soon']==1 ) ? '<div class="stat soon1">'.$lng['l']['stat']['soon1'].'</div>' : '';
            $z_stat .= ($r['top']==1) ? '<div class="stat top1">'.$lng['l']['stat']['top1'].'</div>' : '';
            $z_stat .= ($r['prc_n']!=0 && $r['prc_t']>time()) ? '<div class="stat prc_n">'.$lng['l']['stat']['prc_n'].'</div>' : '';
            $z_stat .= ($r['tva']==1) ? '<div class="stat top1">'.$lng['l']['stat']['vat'].'</div>' : '';
            $z_stat .= ($r['gift']==1) ? '<div class="stat gift">+ '.$lng['l']['stat']['gift'].'</div>' : '';
        }else{
            $z_stat .= '<div class="stat n_a1">'.$lng['l']['stat']['n_a1'].'</div>';
        }
        
        // Generate image slider HTML
        $image_html = generateImageSliderHTML($images, $r, $enable_slider);
        
        $ar['txt'] .= '
        <a class="it car" href="/'.$_COOKIE['lang'].'/cars/'.$r['id'].'">
            '.$image_html.'
            <div class="txt">
                <div class="status">'.$z_stat.'</div>
                <div class="name" style="margin-top: -3px;">'.$r['br_nm'].' '.$r['mo_nm'].'</div>';
                
        $ar['txt'] .= '<div class="specs">';
        
        foreach ($specs_arr as $k => $v){
            $r[$k] = $k=='mlg' ? number_format($r[$k]).' '.( isset($lng['l']['unit'][ $r['unit'] ]) ? $lng['l']['unit'][ $r['unit'] ] : $r['unit'] ) : $r[$k];
            $r[$k] = $k=='vol' ? $r[$k].' '.$lng['l']['unit']['cm3'] : $r[$k];
            
            $v1 = ( $v===1&&isset($lng['l']['car'][$k][$r[$k]]) ) ? $lng['l']['car'][$k][$r[$k]] : $r[$k];
            $ar['txt'] .= '
            <p class="ar">
                <span class="name">'.$lng['l']['car']['spec'][$k].'</span>
                <span class="space"></span>
                <span class="val">'.$v1.'</span>
            </p>';
        }
        
        // Add monthly payment
        if($r['prc'] > 100) {
            $monthly_payment = floor($r['prc'] * (9.2/1200) / (1 - pow(1 + (9.2/1200), -60)));
            $ar['txt'] .= '
            <p class="ar">
                <span class="name">'.(isset($lng['w']['monthly_payment']) ? $lng['w']['monthly_payment'] : 'Plată lunară').'</span>
                <span class="space"></span>
                <span class="val">'.(isset($lng['w']['from']) ? $lng['w']['from'] : 'de la').' <span style="color: #ff0000; font-weight: bold;">'.$monthly_payment.'</span> €</span>
            </p>';
        }
        
        // Calculate price
        if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
            $prc = number_format($r['prc_n'], 0, ',', ' ');
            $o_prc = number_format($r['prc'], 0, ',', ' ');
            $o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.$o_prc.'</span> &#8364;</span>';
        }else{
            $prc = number_format($r['prc'], 0, ',', ' ');
            $o_prc = 0;
            $o_prc_bl = '';
        }
        
        // Add import country info (same as original)
        $price_margin_style = 'margin-top: 40px;';
        if (!empty($r['import_country_id'])) {
            $country_name = '';
            $country_code = '';
            
            $langColumn = 'name_ro';
            if (isset($_COOKIE['lang']) && $_COOKIE['lang'] == 'ru') {
                $langColumn = 'name_ru';
                $country_label = 'Страна импорта';
            } elseif (isset($_COOKIE['lang']) && $_COOKIE['lang'] == 'en') {
                $langColumn = 'name_en';
                $country_label = 'Import country';
            } else {
                $country_label = 'Țara de import';
            }
            
            try {
                $stmt = $db->prepare("SELECT {$langColumn}, code FROM countries WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $r['import_country_id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $country_name = isset($result[$langColumn]) ? $result[$langColumn] : '';
                    $country_code = isset($result['code']) ? strtolower($result['code']) : '';
                }
            } catch (Exception $e) {
                // Silent error handling
            }
            
            if (!empty($country_name)) {
                $ar['txt'] .= '
                <p class="ar">
                    <span class="name">'.$country_label.'</span>
                    <span class="space"></span>
                    <span class="val">'.$country_name.'</span>
                </p>';
                
                if (!empty($country_code)) {
                    $ar['txt'] .= '
                <div style="text-align: right; margin-right:-3px; padding: 0; border: none;">
                    <img src="/media/images/flags/'.$country_code.'.svg" alt="'.$country_name.' flag" style="width: 36px; height: 30px; border: none; padding: 0;">
                </div>';
                }
                
                $price_margin_style = 'margin-top: -30px;';
            }
        }
        
        $ar['txt'] .= '
        </div>
        
        <div class="prc" style="'.$price_margin_style.'"> <strong class="val">'.($r['prc'] > 100 ? $prc.' &#8364;' : $lng['w']['negociabil']).'</strong> '.$o_prc_bl.'</div>
    </div>
</a>';
        
        $i++;
    }
    
    if ($i==0){
        $ar['txt'] .= '<div class="empty">'.$lng['t']['x']['no_offers'].'</div>';
    }
    
    return $ar;
}

/**
 * Generate image slider HTML
 * 
 * @param array $images - Array of image data
 * @param array $car_data - Car data
 * @param bool $enable_slider - Enable slider functionality
 * @return string - HTML for image slider
 */
function generateImageSliderHTML($images, $car_data, $enable_slider = true) {
    global $img_frmt;
    
    if (empty($images)) {
        // No images - show placeholder
        return '<img src="/'._SITE_IMG.'/v2/no_image.svg" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' no photo" />';
    }
    
    if (count($images) == 1 || !$enable_slider) {
        // Single image - no slider needed
        $image = $images[0];
        $p_src = '/'._CAR_IMG.'/'.$car_data['p_path'].'/'.$car_data['id'].'/med/';
        $p_name = $image['name'].$img_frmt;
        return '<img src="'.$p_src.$p_name.'" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' main photo" />';
    }
    
    // Multiple images - generate slider
    $html = '<div class="product-card-slider">';
    $html .= '<div class="product-card-slider__container">';
    $html .= '<div class="product-card-slider__track">';
    
    foreach ($images as $index => $image) {
        $p_src = '/'._CAR_IMG.'/'.$car_data['p_path'].'/'.$car_data['id'].'/med/';
        $p_name = $image['name'].$img_frmt;
        $html .= '<div class="product-card-slider__slide">';
        $html .= '<img src="'.$p_src.$p_name.'" alt="car '.$car_data['br_nm'].' '.$car_data['mo_nm'].' id'.$car_data['id'].' photo '.($index+1).'" />';
        $html .= '</div>';
    }
    
    $html .= '</div>'; // close track
    
    // Navigation arrows
    $html .= '<button class="product-card-slider__nav product-card-slider__nav--prev">‹</button>';
    $html .= '<button class="product-card-slider__nav product-card-slider__nav--next">›</button>';
    
    // Dots indicator
    if (count($images) <= 5) { // Only show dots for 5 or fewer images
        $html .= '<div class="product-card-slider__dots">';
        for ($i = 0; $i < count($images); $i++) {
            $active_class = $i === 0 ? ' product-card-slider__dot--active' : '';
            $html .= '<div class="product-card-slider__dot'.$active_class.'"></div>';
        }
        $html .= '</div>';
    }
    
    // Image counter
    $html .= '<div class="product-card-slider__counter">1/'.count($images).'</div>';
    
    $html .= '</div>'; // close container
    $html .= '</div>'; // close slider
    
    return $html;
}

/**
 * Include slider assets (CSS and JS)
 * Call this function in the page header
 */
function include_product_card_slider_assets() {
    echo '<link rel="stylesheet" href="/content/site/components/product-card-slider/product-card-slider.css?v='.time().'">';
    echo '<script src="/content/site/components/product-card-slider/product-card-slider.js?v='.time().'" defer></script>';
}
?>
