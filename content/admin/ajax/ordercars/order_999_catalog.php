<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Core\Container;
use App\Db\Car;
use App\Helper\DefaultText;
use App\Services\Api999Service;

/*error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/

$rtrn = '';
$zY = substr( md5( date('Y') ), 0, 4 );
$zM = substr( md5( date('m') ), 0, 4 );
$requestData = $_POST;

// Try to use order cars settings, fallback to default if not configured
try {
    $api999Service = Api999Service::createFromSettings('on_order');
} catch (Exception $e) {
    // Fallback to default API service if order settings not configured
    $api999Service = new Api999Service();
}

if (__post('sub') == 'get_subcategory') {
    if (!empty(__post('category'))) {
        $subcategories = $api999Service->getSubcategories(__post('category'));
        foreach ($subcategories['subcategories'] as $cat) {
            $rtrn .= '<option value="'.$cat['id'].'">'.$cat['title'].'</option>';
        }
        $rtrn = [ 'bx_id'=>__post('bx_id'), 'str'=>$rtrn ];
    }
} elseif (__post('sub') == 'get_subcategory_offer_types') {
    if (!empty(__post('category')) && !empty(__post('subcategory'))) {
        $types = $api999Service->getSubcategoryOfferTypes(__post('category'), __post('subcategory'));
        foreach ($types['offer_types'] as $cat) {
            $rtrn .= '<option value="'.$cat['id'].'">'.$cat['title'].'</option>';
        }
        $rtrn = [ 'bx_id'=>__post('bx_id'), 'str'=>$rtrn ];
    }
} elseif (__post('sub') == 'get_features') {
    if (!empty(__post('category')) && !empty(__post('subcategory')) && !empty(__post('offer_type'))) {
        $types = $api999Service->getSubcategoryFeatures(__post('category'), __post('subcategory'), __post('offer_type'));
        $feature_id = __post('subcategory');
        ob_start();
        include _ADM_PAGE.'/ordercars/order_features_form.php';
        $rtrn = ob_get_clean();
        $rtrn = [ 'bx_id' => __post('bx_id'), 'str' => $rtrn ];
    }
} elseif (__post('sub') == 'get_phone') {
    $feature_id = 16;
    $contacts = (new DefaultText)->getContacts(__post('account_id'));
    ob_start();
    include _ADM_PAGE.'/ordercars/order_feature_contact.php';
    $rtrn = ob_get_clean();
    $rtrn = [ 'bx_id' => __post('bx_id'), 'str' => $rtrn ];
} elseif (__post('sub') == 'get_features_depends') {
    if (!empty(__post('subcategory')) && !empty(__post('dependency_feature_id')) && !empty(__post('parent_option_id'))) {
        $types = $api999Service->getDependentOptions(__post('subcategory'), __post('dependency_feature_id'), __post('parent_option_id'));
        foreach ($types['Options'] as $cat) {
            $rtrn .= '<option value="'.$cat['id'].'">'.$cat['title'].'</option>';
        }
        $rtrn = [ 'bx_id'=>__post('bx_id'), 'str'=>$rtrn ];
    }
} elseif (__post('sub') == 'update_text') {
    // Update only the description text (feature 13) on 999.md without republishing
    $pdo = Container::get('db');
    $carId = __post('carId');
    $newText = __post('text');
    
    if (empty($carId) || empty($newText)) {
        $rtrn = ['error' => 'Missing carId or text'];
    } else {
        $advert = (new Car())->getCarById($carId);
        
        if (empty($advert['999_id'])) {
            $rtrn = ['error' => 'Car has no 999.md listing yet'];
        } else {
            // Get current features from DB
            $advertFeatures = json_decode($advert['999'], true);
            $features = $advertFeatures['features'] ?? [];
            
            // Update feature 13 (description)
            $feature13Found = false;
            foreach ($features as $index => $feature) {
                if ($feature['id'] === '13' || $feature['id'] === 13) {
                    $features[$index]['value'] = $newText;
                    $feature13Found = true;
                    break;
                }
            }
            if (!$feature13Found) {
                $features[] = ['id' => '13', 'value' => $newText];
            }
            
            // Update on 999.md
            try {
                $response = (new Api999Service($advert['999_api_id']))->updateAdvert($advert['999_id'], $features);
                
                // Save updated features to DB
                $stmt = $pdo->prepare("
                    UPDATE gh3sp_car_ctlg
                    SET `999` = :featuresJson
                    WHERE id = :carId
                ");
                $stmt->execute([
                    ':featuresJson' => json_encode([
                        'category_id' => $advertFeatures['category_id'],
                        'subcategory_id' => $advertFeatures['subcategory_id'],
                        'offer_type' => $advertFeatures['offer_type'],
                        'announcement_type' => $advertFeatures['announcement_type'] ?? null,
                        'features' => $features,
                    ]),
                    ':carId' => $carId
                ]);
                
                $rtrn = ['success' => true, 'message' => 'Text updated on 999.md'];
            } catch (Exception $e) {
                $rtrn = ['error' => $e->getMessage()];
            }
        }
    }
} elseif (__post('sub') == 'set_999') {

    // Validate form data
    if (empty($_POST['form_data'])) {
        $rtrn = ['error' => 'Form data is empty'];
    } else {

    $request = null;
    $pdo = Container::get('db');
    $carId = __post('carId');
    $isChecked = $_POST['isChecked'] == 'false' ? 0 : 1;
    $advert = (new Car())->getCarById($carId);
    if($advert['n_a_new'] != $isChecked){
        if(!empty($advert['999_id'])){
            $status = $isChecked == 0 ? 'public' : 'private';
            (new Api999Service($advert['999_api_id']))->changeAccessPolicy($advert, $status);
        }
    }
    
    // Parse form data
    parse_str($_POST['form_data'], $input);
    
    // If 999_api_id is missing (disabled field not submitted), get it from database or default to 3 for order cars
    if (empty($input['999_api_id'])) {
        if (!empty($carId)) {
            $stmt = $pdo->prepare("SELECT 999_api_id FROM gh3sp_car_ctlg WHERE id = ?");
            $stmt->execute([$carId]);
            $carApiData = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!empty($carApiData['999_api_id'])) {
                $input['999_api_id'] = $carApiData['999_api_id'];
                __log("Using 999_api_id from database: {$input['999_api_id']}");
            } else {
                // Default to API ID 3 (Sauto-stock-extern) for order cars
                $input['999_api_id'] = 3;
                __log("Defaulting to 999_api_id = 3 (Sauto-stock-extern) for order cars");
            }
        } else {
            // Default to API ID 3 (Sauto-stock-extern) for new order cars
            $input['999_api_id'] = 3;
            __log("Defaulting to 999_api_id = 3 (Sauto-stock-extern) for new order cars");
        }
    }
    
    // Validate required fields
    if (empty($input['999_api_id'])) {
        $rtrn = ['error' => 'API ID is required'];
    } elseif (empty($input['car']['category'])) {
        $rtrn = ['error' => 'Category is required'];
    } elseif (empty($input['car']['subcategory'])) {
        $rtrn = ['error' => 'Subcategory is required'];
    } else {

    $features = [];

    // Get car data for engine volume conversion AND price
    $carData = null;
    if (!empty($carId)) {
        $stmt = $pdo->prepare("SELECT vol, prc, cur FROM gh3sp_car_ctlg WHERE id = ?");
        $stmt->execute([$carId]);
        $carData = $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // Safety check for features array
    if (!empty($input["feature"]) && is_array($input["feature"])) {
        foreach ($input["feature"] as $id => $value) {
        if (is_string($value) && trim($value) === "") {
            continue;
        }

        $feature = ["id" => (string)$id];

        if ($value === "on") {
            $feature["value"] = true;
        } elseif ($id == 15) {
            //__log($value);
            $feature["value"] = array_map('trim', explode(",", $value));
            //__log($feature["value"]);
        } elseif ($id == 16) {
            $feature["value"] = array_map('trim', $value);
        } elseif (isset($input["feature_units"][$id])) {
            $feature["value"] = (string)$value;
            $feature["unit"] = $input["feature_units"][$id];
        } else {
            $feature["value"] = trim((string)$value);
        }

        $features[] = $feature;
        }
    }
    
    // CRITICAL: Validate and ensure price (feature 2) exists for SAUTO Personal
    if ($input['announcement_type'] === 'sauto_personal') {
        $hasPrice = false;
        foreach ($features as $feature) {
            if ($feature['id'] === '2' && !empty($feature['value']) && is_numeric($feature['value']) && $feature['value'] > 0) {
                $hasPrice = true;
                break;
            }
        }
        
        // If price is missing or invalid, add it from car table
        if (!$hasPrice && !empty($carData['prc']) && $carData['prc'] > 0) {
            $currency = !empty($carData['cur']) ? strtolower($carData['cur']) : 'eur';
            $features[] = [
                "id" => "2",
                "value" => (string)$carData['prc'],
                "unit" => $currency
            ];
            __log("SAUTO Personal: Auto-added missing price {$carData['prc']} {$currency} for car {$carId}");
        }
    }

    // Smart engine volume conversion for SAUTO Personal schedules
    if ($input['announcement_type'] === 'sauto_personal' && !empty($carData['vol'])) {
        $engineVolumeCm3 = (int)$carData['vol'];
        $engineVolumeLiters = number_format(round($engineVolumeCm3 / 1000, 1), 1, '.', '');
        
        $volumeMap = [
            "0.7" => "43671", "0.8" => "43672", "0.9" => "43673", "1.0" => "43674",
            "1.1" => "43675", "1.2" => "43676", "1.3" => "43677", "1.4" => "43678",
            "1.5" => "43679", "1.6" => "43680", "1.7" => "43681", "1.8" => "43682",
            "1.9" => "43683", "2.0" => "43684", "2.1" => "43685", "2.2" => "43686",
            "2.3" => "43687", "2.4" => "43688", "2.5" => "43689", "2.6" => "43690",
            "2.7" => "43691", "2.8" => "43692", "2.9" => "43693", "3.0" => "43694",
            "3.1" => "43695", "3.2" => "43696", "3.3" => "43697", "3.4" => "43698",
            "3.5" => "43699", "3.6" => "43700", "3.8" => "43701", "3.9" => "43702",
            "4.0" => "43703", "4.2" => "43704", "4.3" => "43705", "4.4" => "43706",
            "4.5" => "43707", "4.6" => "43708", "4.7" => "43709", "4.8" => "43710",
            "5.0" => "43711", "5.2" => "43712", "5.3" => "43713", "5.4" => "43714",
            "5.5" => "43715", "5.6" => "43716", "5.7" => "43717", "5.8" => "43718",
            "5.9" => "43719", "6.0" => "43720", "6.2" => "43721", "6.4" => "43722",
            "6.6" => "43723", "6.7" => "43724", "8.0" => "43763"
        ];
        $optionId = $volumeMap[$engineVolumeLiters] ?? null;
        
        // Check existing features and fix empty engine volume
        $hasFeature103 = false;
        $hasFeature2553 = false;
        $feature2553Index = -1;
        $feature103Index = -1;
        
        foreach ($features as $index => $feature) {
            if ($feature['id'] === '103') {
                $hasFeature103 = true;
                $feature103Index = $index;
            }
            if ($feature['id'] === '2553') {
                $hasFeature2553 = true;
                $feature2553Index = $index;
                // Check if feature 2553 is empty or invalid
                if (empty($feature['value']) || trim($feature['value']) === '') {
                    if ($optionId) {
                        $features[$index]['value'] = $optionId;
                        __log("SAUTO Personal: Fixed empty feature 2553 - {$engineVolumeCm3}cc -> {$engineVolumeLiters}L -> option {$optionId}");
                    }
                }
            }
        }
        
        // If no engine volume features exist, add the appropriate one
        if (!$hasFeature103 && !$hasFeature2553 && $engineVolumeCm3 > 0 && $optionId) {
            $features[] = [
                "id" => "2553",
                "value" => $optionId
            ];
            __log("SAUTO Personal: Auto-added engine volume {$engineVolumeCm3}cc -> {$engineVolumeLiters}L -> option {$optionId}");
        }
        
        // If only feature 103 exists but form expects 2553, add 2553 as well
        if ($hasFeature103 && !$hasFeature2553 && isset($input['feature']['2553']) && $optionId) {
            $features[] = [
                "id" => "2553",
                "value" => $optionId
            ];
            __log("SAUTO Personal: Added feature 2553 ({$engineVolumeLiters}L -> option {$optionId}) alongside existing feature 103");
        }
    }

    // Add dynamic links to description (feature 13) for on_order cars
    if (!empty($carId)) {
        $stmt = $pdo->prepare("SELECT br, mo FROM gh3sp_car_ctlg WHERE id = ?");
        $stmt->execute([$carId]);
        $carInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($carInfo && !empty($carInfo['br']) && !empty($carInfo['mo'])) {
            // Get brand and model names from car_list table
            $stmtCarList = $pdo->prepare("SELECT br_nm, mo_nm FROM gh3sp_car_list WHERE br = ? AND mo = ? LIMIT 1");
            $stmtCarList->execute([$carInfo['br'], $carInfo['mo']]);
            $carListInfo = $stmtCarList->fetch(\PDO::FETCH_ASSOC);
            
            if ($carListInfo && !empty($carListInfo['br_nm']) && !empty($carListInfo['mo_nm'])) {
                $brandSlug = strtolower(str_replace('_', '-', $carInfo['br']));
                $modelSlug = strtolower(str_replace('_', '-', $carInfo['mo']));
                $brandText = $carListInfo['br_nm'];
                $modelText = $carListInfo['mo_nm'];
                
                $carLink = "https://www.sauto.md/ro/ordercars/{$carId}";
                $modelLink = "https://www.sauto.md/ro/ordercars/{$brandSlug}-{$modelSlug}";
                $brandLink = "https://www.sauto.md/ro/ordercars/{$brandSlug}";
                
                $linksText = "\n\nDetalii despre automobil:\n{$carLink}\nToate automobilele modelului {$modelText}:\n{$modelLink}\nToate automobilele mărcii {$brandText}:\n{$brandLink}";
                
                $feature13Found = false;
                foreach ($features as $index => $feature) {
                    if ($feature['id'] === '13') {
                        $features[$index]['value'] .= $linksText;
                        __log("Added dynamic links to description for on_order car {$carId}");
                        $feature13Found = true;
                        break;
                    }
                }
                
                // If feature 13 doesn't exist, create it with just the links
                if (!$feature13Found) {
                    $features[] = [
                        "id" => "13",
                        "value" => $linksText
                    ];
                    __log("Created feature 13 with dynamic links for on_order car {$carId}");
                }
            }
        }
    }

    if (!empty($advert['999_id'])) {

        $advertFeatures = json_decode($advert['999'], true);
        $responseUpdate = (new Api999Service($advert['999_api_id']))->updateAdvert($advert['999_id'], $features);

        $stmt = $pdo->prepare("
            UPDATE gh3sp_car_ctlg
            SET `999` = :featuresJson, `n_a_new` = :n_a_new
            WHERE id = :carId
        ");
        //__log($responseUpdate);
        //__log($features);

        $stmt->execute([
            ':featuresJson' => json_encode([
                'category_id' => $advertFeatures["category_id"],
                'subcategory_id' => $advertFeatures["subcategory_id"],
                'offer_type' => $advertFeatures["offer_type"],
                'announcement_type' => $advertFeatures["announcement_type"] ?? null,
                'features' => $features,
            ]),
            ':carId' => $carId,
            ':n_a_new' => $isChecked
        ]);
    } else {
        $imgs0 = (new Car())->getCarsImg($carId);

        $images999 = [];

        if (!empty($imgs0)) {
            $i = 1;
            $imgs = array_chunk($imgs0, 20);

            if (!empty($imgs[0]) && is_array($imgs[0])) {
                foreach ($imgs[0] as $img) {
                    $imgPath = $_SERVER['DOCUMENT_ROOT'] . '/media/images/upload/car/' . $img['path'] . '/' . $img['it_id'] . '/high/' . $img['name'] . '.' . $img['ff'];

                    if (!file_exists($imgPath)) {
                        continue;
                    }

                    $i++;
                    $imageLink = (new Api999Service($input['999_api_id']))->uploadImage($imgPath);
                    $st = "[" . date('Y-m-d H:i:s') . "] imageLink ".$carId." Response: " . json_encode($imageLink, JSON_UNESCAPED_UNICODE);
                    __log($st);

                    if (is_array($imageLink) && !empty($imageLink['image_id'])) {
                        $images999[] = $imageLink['image_id'];
                    }
                    if ($i % 4 == 0) {
                        sleep(1);
                    }
                }
            }
        }

        if (!empty($images999)) {
            $features[] = ["id" => "14", "value" => $images999];
        }

        $featuresJson = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Debug: Check announcement type and schedules
        __log("DEBUG: announcement_type = " . ($input['announcement_type'] ?? 'NOT SET'));
        __log("DEBUG: sauto_schedules = " . ($input['sauto_schedules'] ?? 'NOT SET'));
        __log("DEBUG: 999_api_id = " . ($input['999_api_id'] ?? 'NOT SET'));

        // For SAUTO Personal, don't publish immediately - let cron job handle it
        if ($input['announcement_type'] === 'sauto_personal' && !empty($input['sauto_schedules'])) {
            // Create a successful response for SAUTO Personal
            $request = [
                'success' => true,
                'scheduled' => true,
                'advert' => [
                    'id' => 'scheduled_' . time() // Temporary ID until cron job publishes
                ]
            ];
            __log("SAUTO Personal: Skipping immediate publication, will be handled by cron job");
        } else {
            __log("DEBUG: Making API call with 999_api_id = " . $input['999_api_id']);
            // Normal publication for other announcement types
            $request = (new Api999Service($input['999_api_id']))->setAdvert($input["car"]["category"], $input["car"]["subcategory"], $input["car"]["subcategory_offer_types"], $features);
        }

        __log("DEBUG: Request result = " . json_encode($request));
        __log($features);

        // Check if API call was successful
        if (empty($request) || !isset($request['advert']['id'])) {
            $rtrn = ['error' => 'Failed to submit to 999.md API'];
        } else {

        $featuresJsonData = json_encode([
            'category_id' => $input["car"]["category"],
            'subcategory_id' => $input["car"]["subcategory"],
            'offer_type' => $input["car"]["subcategory_offer_types"],
            'announcement_type' => $input["announcement_type"],
            'scenario' => $input["scenario"] ?? 'maximal',
            'features' => $features,
        ]);
        
        // Check if features_json column exists
        $columnsStmt = $pdo->prepare("SHOW COLUMNS FROM gh3sp_car_ctlg LIKE 'features_json'");
        $columnsStmt->execute();
        $hasFeatureJsonColumn = $columnsStmt->rowCount() > 0;
        
        if ($hasFeatureJsonColumn) {
            $stmt = $pdo->prepare("
                UPDATE gh3sp_car_ctlg
                SET `999` = :featuresJson, `features_json` = :featuresJson2, `999_id` = :car999Id, `promotions` = :promotions, `999_api_id` = :999_api_id, `n_a_new` = :n_a_new
                WHERE id = :carId
            ");
            $stmt->execute([
                ':featuresJson' => $featuresJsonData,
                ':featuresJson2' => $featuresJsonData,
                ':car999Id' => ($input['announcement_type'] === 'sauto_personal' && !empty($input['sauto_schedules'])) ? null : $request['advert']['id'],
                ':carId' => $carId,
                ':promotions' => $input['promotions'] ?? 'basic',
                ':999_api_id' => $input['999_api_id'],
                ':n_a_new' => $isChecked
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE gh3sp_car_ctlg
                SET `999` = :featuresJson, `999_id` = :car999Id, `promotions` = :promotions, `999_api_id` = :999_api_id, `n_a_new` = :n_a_new
                WHERE id = :carId
            ");
            $stmt->execute([
                ':featuresJson' => $featuresJsonData,
                ':car999Id' => ($input['announcement_type'] === 'sauto_personal' && !empty($input['sauto_schedules'])) ? null : $request['advert']['id'],
                ':carId' => $carId,
                ':promotions' => $input['promotions'] ?? 'basic',
                ':999_api_id' => $input['999_api_id'],
                ':n_a_new' => $isChecked
            ]);
        }
        if($advert['n_a_new'] != $isChecked){
            if(!empty($advert['999_id'])){
                $status = $isChecked == 0 ? 'public' : 'private';
                (new Api999Service($advert['999_api_id']))->changeAccessPolicy($advert, $status);
            }
        }
        // Handle SAUTO Personal custom scheduling
        if ($input['announcement_type'] === 'sauto_personal' && !empty($input['sauto_schedules'])) {
            $schedulesData = json_decode($input['sauto_schedules'], true);
            if ($schedulesData && is_array($schedulesData)) {
                // Get catalog_type from database
                $catalogType = 'on_order'; // Default for order cars page
                if (!empty(__post('carId'))) {
                    $stmt = $pdo->prepare("SELECT catalog_type FROM gh3sp_car_ctlg WHERE id = ?");
                    $stmt->execute([__post('carId')]);
                    $carInfo = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if (!empty($carInfo['catalog_type'])) {
                        $catalogType = $carInfo['catalog_type'];
                    }
                }
                
                $sautoSchedulingService = new \App\Services\SautoPersonalSchedulingService();
                $sautoSchedulingService->saveSchedules(__post('carId'), $catalogType, $schedulesData);
                __log("SAUTO Personal: Schedules saved successfully for car ID: " . __post('carId') . " with catalog_type: " . $catalogType);
            }
        } elseif (($input['promotions'] ?? 'basic') == 'test') {
            (new Api999Service($input['999_api_id']))->setTestAdvertSchedules($images999, $request['advert']['id'], __post('carId'));
        } else {
            (new Api999Service($input['999_api_id']))->setAdvertSchedules($input, $images999, $request['advert']['id'], __post('carId'));
        }
        }
    }
    }
    }
    __log($request);
    $rtrn = $request;
}