<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Core\Container;
use App\Db\Car;
use App\Helper\DefaultText;
use App\Services\Api999Service;

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$rtrn = '';
$zY = substr( md5( date('Y') ), 0, 4 );
$zM = substr( md5( date('m') ), 0, 4 );
$requestData = $_POST;

// Use simple Api999Service like in old working code
$api999Service = new Api999Service();

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
        include _ADM_PAGE.'/cars/features_form.php';
        $rtrn = ob_get_clean();
        $rtrn = [ 'bx_id' => __post('bx_id'), 'str' => $rtrn ];
    }
} elseif (__post('sub') == 'get_phone') {
    $feature_id = 16;
    $contacts = (new DefaultText)->getContacts(__post('account_id'));
    ob_start();
    include _ADM_PAGE.'/cars/feature_contact.php';
    $rtrn = ob_get_clean();
    $rtrn = [ 'bx_id' => __post('bx_id'), 'str' => $rtrn ];
} elseif (__post('sub') == 'get_features_depends') {
    if (!empty(__post('subcategory')) && !empty(__post('dependency_feature_id')) && !empty(__post('parent_option_id'))) {
        $types = $api999Service->getDependentOptions(__post('subcategory'), __post('dependency_feature_id'), __post('parent_option_id'));
        foreach ($types['Options'] as $cat) {
            $rtrn .= '<option value="'.$cat['id'].'">'.$cat['title'].'</option>';
        }
        $rtrn = [ 'bx_id' => __post('bx_id'), 'str' => $rtrn ];
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
    
    // Validate required fields
    if (empty($input['999_api_id'])) {
        $rtrn = ['error' => 'API ID is required'];
    } elseif (empty($input['car']['category'])) {
        $rtrn = ['error' => 'Category is required'];
    } elseif (empty($input['car']['subcategory'])) {
        $rtrn = ['error' => 'Subcategory is required'];
    } else {

    $features = [];

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

        // For SAUTO Personal, don't publish immediately - let cron job handle it
        if ($input['announcement_type'] === 'sauto_personal' && !empty($input['sauto_schedules'])) {
            // Create a fake successful response for SAUTO Personal
            $request = [
                'advert' => [
                    'id' => 'scheduled_' . time() // Temporary ID until cron job publishes
                ]
            ];
            __log("SAUTO Personal: Skipping immediate publication, will be handled by cron job");
        } else {
            // Normal publication for other announcement types
            $request = (new Api999Service($input['999_api_id']))->setAdvert($input["car"]["category"], $input["car"]["subcategory"], $input["car"]["subcategory_offer_types"], $features);
        }

        __log($request);
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
        if($advert['n_a_new'] != $isChecked){
            if(!empty($advert['999_id'])){
                $status = $isChecked == 0 ? 'public' : 'private';
                (new Api999Service($advert['999_api_id']))->changeAccessPolicy($advert, $status);
            }
        }
        // Handle SAUTO Personal custom scheduling
        if ($input['announcement_type'] === 'sauto_personal' && !empty($input['sauto_schedules'])) {
            __log("SAUTO Personal scheduling detected");
            __log("Raw schedules data: " . $input['sauto_schedules']);
            $schedulesData = json_decode($input['sauto_schedules'], true);
            __log("Parsed schedules data: " . json_encode($schedulesData));
            if ($schedulesData && is_array($schedulesData)) {
                $sautoSchedulingService = new \App\Services\SautoPersonalSchedulingService();
                $result = $sautoSchedulingService->saveSchedules(__post('carId'), 'in_stock', $schedulesData);
                __log("Save schedules result: " . ($result ? 'SUCCESS' : 'FAILED'));
            } else {
                __log("Invalid schedules data format");
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