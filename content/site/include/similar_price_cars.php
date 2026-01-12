<?php

defined('_DOIT') or die('Restricted access');
function getSimilarPriceCars($currentCar, $limit = 8, $db, $prefx, $lng, $img_frmt) {
    $result = ['cars' => [], 'txt' => '', 'message' => '', 'has_cross_section' => false];
    
    if (empty($currentCar['prc']) || $currentCar['prc'] < 100) {
        return getSimilarByBrandModel($currentCar, $limit, $db, $prefx, $lng, $img_frmt);
    }
    
    $basePrice = (float)$currentCar['prc'];
    $currentSection = isset($currentCar['catalog_type']) ? $currentCar['catalog_type'] : 'in_stock';
    $otherSection = ($currentSection === 'in_stock') ? 'on_order' : 'in_stock';
    $priceRanges = [0.20, 0.30, 0.40];
    $sameSectionCars = [];
    $otherSectionCars = [];
    $modelCounts = [];
    $modelCountsOther = [];
    
    foreach ($priceRanges as $range) {
        if (count($sameSectionCars) >= $limit) break;
        $priceLow = $basePrice * (1 - $range);
        $priceHigh = $basePrice * (1 + $range);
        $excludeIds = array_merge([$currentCar['id']], array_column($sameSectionCars, 'id'));
        $newCars = fetchSimilarCars($db, $prefx, $currentCar, $priceLow, $priceHigh, $currentSection, $excludeIds, $limit, $modelCounts);
        foreach ($newCars as $car) {
            if (count($sameSectionCars) >= $limit) break;
            $modelKey = $car['br'] . '_' . $car['mo'];
            if (!isset($modelCounts[$modelKey])) $modelCounts[$modelKey] = 0;
            if ($modelCounts[$modelKey] >= 2) continue;
            $modelCounts[$modelKey]++;
            $car['from_other_section'] = false;
            $sameSectionCars[] = $car;
        }
    }
    
    if (count($sameSectionCars) < $limit) {
        foreach ($priceRanges as $range) {
            if (count($sameSectionCars) + count($otherSectionCars) >= $limit) break;
            $priceLow = $basePrice * (1 - $range);
            $priceHigh = $basePrice * (1 + $range);
            $excludeIds = array_merge([$currentCar['id']], array_column($sameSectionCars, 'id'), array_column($otherSectionCars, 'id'));
            $newCars = fetchSimilarCars($db, $prefx, $currentCar, $priceLow, $priceHigh, $otherSection, $excludeIds, $limit, $modelCountsOther);
            foreach ($newCars as $car) {
                if (count($sameSectionCars) + count($otherSectionCars) >= $limit) break;
                $modelKey = $car['br'] . '_' . $car['mo'];
                if (!isset($modelCountsOther[$modelKey])) $modelCountsOther[$modelKey] = 0;
                if ($modelCountsOther[$modelKey] >= 2) continue;
                $modelCountsOther[$modelKey]++;
                $car['from_other_section'] = true;
                $otherSectionCars[] = $car;
            }
        }
    }
    
    $allFoundCars = array_merge($sameSectionCars, $otherSectionCars);
    
    $sameSectionCars = array_filter($allFoundCars, fn($c) => !$c['from_other_section']);
    $otherSectionCars = array_filter($allFoundCars, fn($c) => $c['from_other_section']);
    
    // Step 3: Fallback to fresh arrivals
    $allCars = array_merge($sameSectionCars, $otherSectionCars);
    if (count($allCars) < $limit) {
        $needed = $limit - count($allCars);
        $excludeIds = array_merge([$currentCar['id']], array_column($allCars, 'id'));
        $freshCars = fetchFreshArrivals($db, $prefx, $currentCar, $basePrice * 1.6, $currentSection, $excludeIds, $needed);
        foreach ($freshCars as $car) {
            $car['from_other_section'] = ($car['catalog_type'] !== $currentSection);
            $allCars[] = $car;
        }
    }
    
    // Sort: same section first, then by price proximity
    usort($allCars, function($a, $b) use ($basePrice) {
        if ($a['from_other_section'] !== $b['from_other_section']) return $a['from_other_section'] ? 1 : -1;
        return abs($a['prc'] - $basePrice) - abs($b['prc'] - $basePrice);
    });
    
    $result['cars'] = array_slice($allCars, 0, $limit);
    $result['has_cross_section'] = count($otherSectionCars) > 0;
    if ($result['has_cross_section']) {
        $result['message'] = ($currentSection === 'in_stock') 
            ? ($lng['w']['similar_few_in_stock'] ?? '') 
            : ($lng['w']['similar_few_on_order'] ?? '');
    }
    $result['txt'] = generateSimilarCarsHTML($result['cars'], $currentSection, $db, $prefx, $lng, $img_frmt);
    return $result;
}

function fetchSimilarCars($db, $prefx, $currentCar, $priceLow, $priceHigh, $section, $excludeIds, $limit, &$modelCounts) {
    $excludeList = implode(',', array_map('intval', $excludeIds));
    $sql = "SELECT *, 
            CASE WHEN br = :br AND mo = :mo THEN 1 WHEN br = :br2 THEN 2 WHEN bt = :bt THEN 3 ELSE 4 END AS priority,
            ABS(prc - :base_price) AS price_diff
            FROM {$prefx}_car_ctlg 
            WHERE vis = '1' AND act = '1' AND n_a = '0'
            AND prc BETWEEN :price_low AND :price_high
            AND id NOT IN ({$excludeList})
            AND (catalog_type = :section OR (catalog_type IS NULL AND :section2 = 'in_stock'))
            ORDER BY priority ASC, price_diff ASC, id DESC LIMIT :limit";
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':br', $currentCar['br'], PDO::PARAM_STR);
        $stmt->bindValue(':br2', $currentCar['br'], PDO::PARAM_STR);
        $stmt->bindValue(':mo', $currentCar['mo'], PDO::PARAM_STR);
        $stmt->bindValue(':bt', $currentCar['bt'] ?? '', PDO::PARAM_STR);
        $stmt->bindValue(':base_price', $currentCar['prc'], PDO::PARAM_INT);
        $stmt->bindValue(':price_low', $priceLow, PDO::PARAM_INT);
        $stmt->bindValue(':price_high', $priceHigh, PDO::PARAM_INT);
        $stmt->bindValue(':section', $section, PDO::PARAM_STR);
        $stmt->bindValue(':section2', $section, PDO::PARAM_STR);
        $stmt->bindValue(':limit', 50, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Similar cars query error: ' . $e->getMessage());
        return [];
    }
}
function fetchFreshArrivals($db, $prefx, $currentCar, $maxPrice, $preferSection, $excludeIds, $limit) {
    $excludeList = implode(',', array_map('intval', $excludeIds));
    $sql = "SELECT * FROM {$prefx}_car_ctlg 
            WHERE vis = '1' AND act = '1' AND n_a = '0' AND prc <= :max_price AND prc > 100
            AND id NOT IN ({$excludeList})
            ORDER BY CASE WHEN catalog_type = :section THEN 0 ELSE 1 END, id DESC LIMIT :limit";
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':max_price', $maxPrice, PDO::PARAM_INT);
        $stmt->bindValue(':section', $preferSection, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Fresh arrivals query error: ' . $e->getMessage());
        return [];
    }
}
function getSimilarByBrandModel($currentCar, $limit, $db, $prefx, $lng, $img_frmt) {
    $result = ['cars' => [], 'txt' => '', 'message' => '', 'has_cross_section' => false];
    $currentSection = isset($currentCar['catalog_type']) ? $currentCar['catalog_type'] : 'in_stock';
    $sql = "SELECT *, CASE WHEN br = :br AND mo = :mo THEN 1 WHEN br = :br2 THEN 2 ELSE 3 END AS priority
            FROM {$prefx}_car_ctlg WHERE vis = '1' AND act = '1' AND n_a = '0' AND id <> :id
            ORDER BY CASE WHEN catalog_type = :section THEN 0 ELSE 1 END, priority ASC, id DESC LIMIT :limit";
    try {
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':br', $currentCar['br'], PDO::PARAM_STR);
        $stmt->bindValue(':br2', $currentCar['br'], PDO::PARAM_STR);
        $stmt->bindValue(':mo', $currentCar['mo'], PDO::PARAM_STR);
        $stmt->bindValue(':id', $currentCar['id'], PDO::PARAM_INT);
        $stmt->bindValue(':section', $currentSection, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cars as &$car) {
            $car['from_other_section'] = ($car['catalog_type'] !== $currentSection);
        }
        $result['cars'] = $cars;
        $result['has_cross_section'] = count(array_filter($cars, fn($c) => $c['from_other_section'])) > 0;
        $result['txt'] = generateSimilarCarsHTML($cars, $currentSection, $db, $prefx, $lng, $img_frmt);
    } catch (PDOException $e) {
        error_log('Similar by brand query error: ' . $e->getMessage());
    }
    return $result;
}
function generateSimilarCarsHTML($cars, $currentSection, $db, $prefx, $lng, $img_frmt) {
    if (empty($cars)) return '';
    $html = '';
    
    $is_mobile = (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone|iPad/', $_SERVER['HTTP_USER_AGENT']));
    
    foreach ($cars as $car) {
        $image_extension = (isset($car['catalog_type']) && $car['catalog_type'] === 'on_order') ? '.jpg' : $img_frmt;
        $page_type = (isset($car['catalog_type']) && $car['catalog_type'] === 'on_order') ? 'ordercars' : 'cars';
        
        if ($is_mobile) {
            $pdo2 = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id ORDER BY `main` DESC, `pos` ASC LIMIT 10');
            $pdo2->execute(['it_id' => $car['id']]);
            $all_images = $pdo2->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($all_images) > 1) {
                $image_html = '<div class="mobile-card-slider" data-lazy-load="pending"><div class="mobile-card-slider__container"><div class="mobile-card-slider__track">';
                foreach ($all_images as $idx => $img) {
                    $img_src = '/'._CAR_IMG.'/'.$car['p_path'].'/'.$car['id'].'/med/'.$img['name'].$image_extension;
                    if ($idx === 0) {
                        $image_html .= '<div class="mobile-card-slider__slide"><img src="'.$img_src.'" loading="lazy" width="300" height="200" alt="car '.$car['br_nm'].' '.$car['mo_nm'].' photo '.($idx+1).'" /></div>';
                    } else {
                        $image_html .= '<div class="mobile-card-slider__slide"><img src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\'%3E%3Crect width=\'100%25\' height=\'100%25\' fill=\'%23f0f0f0\'/%3E%3C/svg%3E" data-src="'.$img_src.'" loading="lazy" width="300" height="200" alt="car '.$car['br_nm'].' '.$car['mo_nm'].' photo '.($idx+1).'" /></div>';
                    }
                }
                $image_html .= '</div>';
                $image_html .= '<div class="mobile-card-slider__line-indicator"></div>';
                $image_html .= '</div></div>';
            } else {
                $p = $all_images[0] ?? null;
                $p_src = isset($p['name']) ? '/'._CAR_IMG.'/'.$car['p_path'].'/'.$car['id'].'/med/' : '/'._SITE_IMG.'/v2/';
                $p_name = isset($p['name']) ? $p['name'].$image_extension : 'no_image.svg';
                $image_html = '<img src="'.$p_src.$p_name.'" loading="lazy" width="300" height="200" alt="car '.$car['br_nm'].' '.$car['mo_nm'].' id'.$car['id'].' main photo" />';
            }
        } else {
            $pdo2 = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id AND `main`="1" LIMIT 1');
            $pdo2->execute(['it_id' => $car['id']]);
            $photo = $pdo2->fetch();
            
            $p_src = $photo ? '/'._CAR_IMG.'/'.$car['p_path'].'/'.$car['id'].'/med/' : '/'._SITE_IMG.'/v2/';
            $p_name = $photo ? $photo['name'].$image_extension : 'no_image.svg';
            $image_html = '<img src="'.$p_src.$p_name.'" loading="lazy" width="300" height="200" alt="car '.$car['br_nm'].' '.$car['mo_nm'].' id'.$car['id'].' main photo" />';
        }
        
        $year = $car['yr'];
        $fuel = $lng['l']['car']['fl'][$car['fl']] ?? $car['fl'];
        $transmission = $lng['l']['car']['tra'][$car['tra']] ?? $car['tra'];
        $volume = $car['vol'].' '.$lng['l']['unit']['cm3'];
        $mileage = number_format($car['mlg']).' '.($lng['l']['unit'][$car['unit']] ?? $car['unit']);
        
        $prc = number_format($car['prc'], 0, ',', ' ');
        $stock_status_class = ($car['catalog_type'] == 'on_order') ? ' on-order' : '';
        $stock_status_text = ($car['catalog_type'] == 'on_order') ? ($lng['w']['on_order'] ?? 'La comandă') : ($lng['w']['in_stock'] ?? 'În stoc');
        
        $html .= '
        <a class="it car" href="/'.$_COOKIE['lang'].'/'.$page_type.'/'.$car['id'].'">
            <div class="name">'.$car['br_nm'].' '.$car['mo_nm'].'</div>
            <div class="compact-info">
                <div class="line1">'.$year.' | '.$fuel.' | '.$volume.'</div>
                <div class="line2">'.$transmission.' | '.$mileage.'</div>
            </div>
            '.$image_html.'
            <div class="prc">
                <strong class="val">'.($car['prc'] > 100 ? $prc.' &#8364;' : ($lng['w']['negociabil'] ?? 'Negociabil')).'</strong>
                <span class="stock-status'.$stock_status_class.'">'.$stock_status_text.'</span>
            </div>
            <div class="txt">
                <div class="specs">';
        
        if($car['prc'] > 100) {
            $monthly_payment = floor($car['prc'] * (9.2/1200) / (1 - pow(1 + (9.2/1200), -60)));
            $html .= '
                    <p class="ar">
                        <span class="name">'.($lng['w']['monthly_payment'] ?? 'Plată lunară').'</span>
                        <span class="space"></span>
                        <span class="val">'.($lng['w']['from'] ?? 'de la').' <span style="color: #ff0000; font-weight: bold;">'.$monthly_payment.'</span> €</span>
                    </p>';
        }
        
        // Add import country with flag
        if (!empty($car['import_country_id'])) {
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
                $stmt->execute(['id' => $car['import_country_id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $country_name = $result[$langColumn] ?? '';
                    $country_code = isset($result['code']) ? strtolower($result['code']) : '';
                }
            } catch (Exception $e) {
                // Silent error handling
            }
            
            if (!empty($country_name)) {
                $html .= '
                    <p class="ar">
                        <span class="name">'.$country_label.'</span>
                        <span class="space"></span>
                        <span class="val">'.$country_name.'</span>
                    </p>';
                
                if (!empty($country_code)) {
                    $html .= '
                    <div style="text-align: right; margin-right:-3px; margin-top: -8px; padding: 0; border: none;">
                        <img src="/media/images/flags/'.$country_code.'.svg" alt="'.$country_name.' flag" style="width: 36px; height: 30px; border: none; padding: 0;">
                    </div>';
                }
            }
        }
        
        $html .= '
                </div>
            </div>
        </a>';
    }
    return $html;
}
