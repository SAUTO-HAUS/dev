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
    $allFoundCars = [];
    $modelCounts = [];
    
    // Search both sections with expanding price ranges
    foreach ($priceRanges as $range) {
        $priceLow = $basePrice * (1 - $range);
        $priceHigh = $basePrice * (1 + $range);
        
        // First: same section
        if (count($allFoundCars) < $limit) {
            $excludeIds = array_merge([$currentCar['id']], array_column($allFoundCars, 'id'));
            $newCars = fetchSimilarCars($db, $prefx, $currentCar, $priceLow, $priceHigh, $currentSection, $excludeIds, $limit, $modelCounts);
            foreach ($newCars as $car) {
                if (count($allFoundCars) >= $limit) break;
                $modelKey = $car['br'] . '_' . $car['mo'];
                if (!isset($modelCounts[$modelKey])) $modelCounts[$modelKey] = 0;
                if ($modelCounts[$modelKey] >= 2) continue;
                $modelCounts[$modelKey]++;
                $car['from_other_section'] = false;
                $allFoundCars[] = $car;
            }
        }
        
        // Second: other section (fill remaining slots)
        if (count($allFoundCars) < $limit) {
            $excludeIds = array_merge([$currentCar['id']], array_column($allFoundCars, 'id'));
            $newCars = fetchSimilarCars($db, $prefx, $currentCar, $priceLow, $priceHigh, $otherSection, $excludeIds, $limit, $modelCounts);
            foreach ($newCars as $car) {
                if (count($allFoundCars) >= $limit) break;
                $modelKey = $car['br'] . '_' . $car['mo'];
                if (!isset($modelCounts[$modelKey])) $modelCounts[$modelKey] = 0;
                if ($modelCounts[$modelKey] >= 2) continue;
                $modelCounts[$modelKey]++;
                $car['from_other_section'] = true;
                $allFoundCars[] = $car;
            }
        }
        
        if (count($allFoundCars) >= $limit) break;
    }
    
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
            CASE WHEN br = :br AND mo = :mo THEN 1 WHEN br = :br2 THEN 2 WHEN bt = :bt THEN 3 ELSE 9 END AS priority,
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
        $stmt->bindValue(':limit', $limit * 3, PDO::PARAM_INT);
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
    
    foreach ($cars as $car) {
        $pdo2 = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id AND `main`="1" LIMIT 1');
        $pdo2->execute(['it_id' => $car['id']]);
        $photo = $pdo2->fetch();
        
        $image_extension = (isset($car['catalog_type']) && $car['catalog_type'] === 'on_order') ? '.jpg' : $img_frmt;
        $p_src = $photo ? '/'._CAR_IMG.'/'.$car['p_path'].'/'.$car['id'].'/med/' : '/'._SITE_IMG.'/v2/';
        $p_name = $photo ? $photo['name'].$image_extension : 'no_image.svg';
        
        $page_type = (isset($car['catalog_type']) && $car['catalog_type'] === 'on_order') ? 'ordercars' : 'cars';
        
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
            <img src="'.$p_src.$p_name.'" loading="lazy" width="300" height="200" alt="car '.$car['br_nm'].' '.$car['mo_nm'].' id'.$car['id'].' main photo" />
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
        
        $html .= '
                </div>
            </div>
        </a>';
    }
    return $html;
}
