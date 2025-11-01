<?php
use App\Services\Api999Service;
use App\Db\Car;
use App\Db\Adverts;

defined( '_DOIT' ) or die( 'Restricted access' );

$ajax_folder = _ADM_AJAX.'/cars';
$photo_folder = _CAR_IMG;
$rtrn = 'none';

set_time_limit(0);

if ( __post('fn')=='search'||__post('fn')=='more'||__post('fn')=='filter' ){
	$arr_types = ['br','mo','author','id','vis','act']; 
    $query_args = []; $all_search = []; $search = [];
	require_once( $ajax_folder.'/filter.php' );
	require_once( $ajax_folder.'/catalog.php' );
	$returnIt = [
        'fn'=>__post('fn'),
        'it_pos'=>$c_id,
        'rtrn'=>$rtrn,
        'search'=>$search
    ];
}
elseif ( __post('fn')=='saveBooster' ){
    $car = (new Car())->getCarById(__post('id'));

    $returnIt = [];
    if (!empty($car['999_id'])) {
        $response = (new Api999Service($car['999_api_id']))->updateAdvertBoosterSettings($car['999_id'], __post('period'), __post('daily_limit'), __post('click_price'));

        if ($response['success'] === true) {
            $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `999_booster`=:999_booster WHERE `id`=:id');
            $pdo->execute([
                'id' => __post('id'),
                '999_booster' => json_encode([
                    'period' => __post('period'),
                    'daily_limit' => __post('daily_limit'),
                    'click_price' => __post('click_price'),
                    'status' => 'in_progress',
                    'end_date' => time() + __post('period') * 24 * 60 * 60
                ])
            ]);
            $returnIt['status'] = true;
        } else {
            $returnIt = $response;
        }
    }
}
elseif ( __post('fn')=='pauseBooster' ) {
    $car = (new Car())->getCarById(__post('id'));
    if (!empty($car['999_id'])) {
        $booster = json_decode($car['999_booster'], true);

        $response = (new Api999Service($car['999_api_id']))->pauseAdvertBooster($car['999_id']);
        if ($response['success'] === true) {
            $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `999_booster`=:999_booster WHERE `id`=:id');
            $pdo->execute([
                'id' => __post('id'),
                '999_booster' => json_encode([
                    'status' => 'pause',
                    'period' => $booster['period'],
                    'daily_limit' => $booster['daily_limit'],
                    'end_date' => $booster['end_date']
                ])
            ]);
            $returnIt['status'] = true;
        }
        $returnIt['response'] = $response;
    }
}
//---------------------------------------------ADD NEW
elseif ( __post('fn')=='add_new' ){
	require_once($ajax_folder.'/add_new.php');
	$returnIt = [
        'fn'=>__post('fn'),
        'sub'=>__post('sub'),
        'rtrn'=>$rtrn
    ];
}
elseif ( __post('fn')=='999_catalog' ){
    require_once($ajax_folder.'/' . __post('fn') . '.php');
    $returnIt = [
        'fn'=>__post('fn'),
        'sub'=>__post('sub'),
        'rtrn'=>$rtrn
    ];
}
//---------------------------------------------EDIT
elseif ( __post('fn')=='edit' ){
	require_once($ajax_folder.'/edit.php');
	$returnIt = [ 'fn'=>__post('fn'), 'sub'=>__post('sub'), 'rtrn'=>$rtrn ];
}
//---------------------------------------------NOT AVAILABLE ITEM
elseif ( __post('fn')=='av0' ) {
    $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `n_a`=1 WHERE `id`=:id');
    $pdo->execute([ 'id' => __post('id') ]);

    $car = (new Car())->getCarById(__post('id'));
    if (!empty($car['999_id'])) {
        (new Api999Service($car['999_api_id']))->changeAccessPolicy($car);
        (new Adverts())->changeActiveStatus(__post('id'), 0);
    }
}
//---------------------------------------------AVAILABLE ITEM
elseif ( __post('fn')=='av1' ){
    $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `n_a`=0 WHERE `id`=:id');
    $pdo->execute([ 'id' => __post('id') ]);

    $car = (new Car())->getCarById(__post('id'));
    if (!empty($car['999_id'])) {
        (new Api999Service($car['999_api_id']))->changeAccessPolicy($car, 'public');
        (new Adverts())->changeActiveStatus(__post('id'), 1);
    }
}
//---------------------------------------------HIDE
elseif ( __post('fn')=='hide' ) {
    $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `vis`=0 WHERE `id`=:id');
    $pdo->execute([ 'id' => __post('id') ]);

    $car = (new Car())->getCarById(__post('id'));
    if (!empty($car['999_id'])) {
        (new Api999Service($car['999_api_id']))->changeAccessPolicy($car);
        (new Adverts())->changeActiveStatus(__post('id'), 0);
    }
}
//---------------------------------------------REVEAL
elseif ( __post('fn')=='reveal' ) {
    $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `vis`=1 WHERE `id`=:id');
    $pdo->execute([ 'id' => __post('id') ]);

    $car = (new Car())->getCarById(__post('id'));
    if (!empty($car['999_id'])) {
        (new Api999Service($car['999_api_id']))->changeAccessPolicy($car, 'public');
        (new Adverts())->changeActiveStatus(__post('id'), 1);
    }
}
//---------------------------------------------DELETE
elseif (__post('fn')=='delete') {
    $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `act`=0, `del_t`=:del_t WHERE `id`=:id');
    $pdo->execute([ 'id' => __post('id'), 'del_t' => time()+(60*60*24*30) ]);

    $car = (new Car())->getCarById(__post('id'));
    if (!empty($car['999_id'])) {
        (new Api999Service($car['999_api_id']))->changeAccessPolicy($car);
        (new Adverts())->changeActiveStatus(__post('id'), 0);
    }
}//+30 days
//---------------------------------------------RESTORE
elseif ( __post('fn')=='restore' ) {
    $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `act`=1, `del_t`=0 WHERE `id`=:id');
    $pdo->execute([ 'id' => __post('id') ]);

    if (!empty($car['999_id'])) {
        (new Api999Service($car['999_api_id']))->changeAccessPolicy($car, 'public');
        (new Adverts())->changeActiveStatus(__post('id'), 1);
    }
}
//---------------------------------------------ERASE
elseif ( __post('fn')=='erase' ){
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id');
	$pdo->execute([ 'id' => __post('id') ]);
	foreach ($pdo as $r) {
        $it_id = $r['id'];
        $p_path = $r['p_path'];
    }
	
	$checker = 0;
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id');
	$pdo->execute([ 'it_id' => __post('id') ]);
	foreach ($pdo as $p){ $checker = 1; }
	
	if ($checker == 1){
		if ( (isset($it_id)&&$it_id!='') && (isset($p_path)&&$p_path!='') ){
			$dir_name = $photo_folder.'/'.$p_path.'/'.$it_id.'/';
			if ( file_exists($dir_name) ){
				removeIt( $dir_name, true );
			}
		}
		$pdo = $db->prepare('DELETE FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id');
        $pdo->execute([ 'it_id' => __post('id') ]);
	;}
	$pdo = $db->prepare('DELETE FROM '.$prefx.'_car_ctlg WHERE `id`=:id AND `act`="0" ');
    $pdo->execute([ 'id' => __post('id') ]);
	$pdo = $db->prepare('DELETE FROM '.$prefx.'_seo2 WHERE `tp`="item" AND `p1`="cars" AND `it_id`=:it_id ');
    $pdo->execute([ 'it_id' => __post('id') ]);
	$returnIt = [
        'fn'=>__post('fn'),
        'id'=>__post('photo_id')
    ];
}
//---------------------------------------------DOWNLOAD ZIP
elseif ( __post('fn')=='download_zip' ){
	foreach( glob('tmp/*.zip') as $file ){
        if ( file_exists($file) ) {
            unlink($file);
        }
    }
	
	$zip_name = substr( md5( date('Ymd').rand(1,1000) ), 3, 15 );
	$zip_path = 'tmp/'.$zip_name.'.zip';
	$zip = new ZipArchive;
	$zip->open($zip_path, ZipArchive::CREATE);
	
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id');
	$pdo->execute([ 'it_id' => __post('it_id') ]);
	$i=1;
	foreach ($pdo as $file){
		$it_id = $file['it_id'];
		$p_path = $file['path'];
		$c_name = $file['name'];
		$file_path = $photo_folder.'/'.$p_path.'/'.$it_id.'/high/'.$c_name.'.jpg';
  		
  		if( file_exists($file_path) ) {
            $zip->addFile($file_path, $i.'.jpg');
        } else {
            echo"file ---".$file_path."--- does not exist";
        }
		$i++;
	}
	$zip->close();
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $returnIt = [
        'fn' => __post('fn'),
        'url' => $protocol . $_SERVER['HTTP_HOST'] . '/' . $zip_path
    ];
}
elseif ( __post('fn') == 'update_n_a_new' ) {
//    $carId = __post('id'); // Получаем ID машины
//    $n_a_new = __post('n_a_new');
//    $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `n_a` = :n_a_new WHERE `id` = :id');
//    $pdo->execute([
//        'id' => $carId,
//        'n_a_new' => $n_a_new
//    ]);
//    $car = (new Car())->getCarById($carId);
//    $status = $n_a_new ? 'private' : 'public';
//    $response = (new Api999Service($car['999_api_id']))->changeAccessPolicy($car, $status);
//
//    $returnIt = [
//        'fn' => __post('fn'),
//        'status' => 'success',
//        'car_id' => $carId,
//        'n_a_new' => $n_a_new
//    ];
}



elseif ( __post('fn')=='sendToFacebookCars' ){

    // /debug_token?input_token={TOKEN}&access_token={APP_ID}|{APP_SECRET}
    // https://graph.facebook.com/debug_token?input_token=EAA71HdmzXoEBO4MbZCfIlrtMS9X4qUMYzfX0C3okReZAfBnJgxh1WvPLzcup4ZAUOujIDDCTgx13SxcZCPR8WI1pBaRgH9YjQprKcKJptwR62AIaZAkxeLcayDYhdSxKF1XvquStSWMD1smvCLNAj4kzRGZADOlLCWjVdC3SAsYka4zOnj4gmPy3FjGObCeolm1dbhiDHaueztZBZAynwhZCy&access_token=4210158229216897



    $carId = $it_id = __post('id');
    $local_id = __post('local_id');
    // Get default schedule time from database settings
    $default_time = '20:00'; // Fallback default
    try {
        $stmt = $db->prepare("SELECT value FROM {$prefx}_settings WHERE name = 'facebook_default_schedule_time' LIMIT 1");
        $stmt->execute();
        $setting = $stmt->fetch();
        if ($setting && !empty($setting['value'])) {
            $default_time = $setting['value'];
        }
    } catch (Exception $e) {
        // Use fallback if database query fails
    }
    
    $schedule_time = __post('schedule_time') ?: $default_time;

    $_COOKIE['lang']='ro';
    require (_DEFAULT.'/language.php');
  
    // Use PublicationService for regular cars
    require_once $_SERVER['DOCUMENT_ROOT'] . '/App/Services/PublicationService.php';
    $publicationService = new \App\Services\PublicationService($db, $prefx);
    
    // First get car data to determine location
    $pdo_temp = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id LIMIT 1');
    $pdo_temp->execute(['id' => $it_id]);
    $tempCarData = $pdo_temp->fetch(\PDO::FETCH_ASSOC);
    
    // Get Facebook settings based on car location
    $facebookSettings = $publicationService->getFacebookSettings($tempCarData);
    if (!$facebookSettings) {
        echo json_encode(['success' => false, 'message' => 'Facebook settings for regular cars not configured']);
        exit;
    }
  
    // Use PhoneReplacementService for dynamic phone numbers
    require_once $_SERVER['DOCUMENT_ROOT'] . '/App/Services/PhoneReplacementService.php';
    $phoneService = new \App\Services\PhoneReplacementService();
    $phone = $phoneService->getGeneralPhone();
    $car_title_name = "";

    // Use settings from PublicationService
    define('APP_ID', '1082088863732549'); // Keep existing app settings
    define('APP_SECRET', '77368f52ab263907ee1fe3ea72909289');
    define('PAGE_ID', $facebookSettings['page_id']);
    define('PAGE_TOKEN', $facebookSettings['token']);

    define('GRAPH_VER', 'v22.0');


    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id LIMIT 1');
    $pdo->execute(['id' => $it_id]);

    $caption_lines = [ ];
    $brand_auto = "";
    $model_auto = "";
    $year_auto = "";
    $price_auto = "";
    $transmission_auto = "";
    $fuel_auto = "";
    $engine_auto = "";
    $mileage_auto = "";
    $color_auto = "";
    $body_auto = "";
    $drive_auto = "";
    $loc_auto = "";
    $carData = null;

    foreach ($pdo as $r){
        $carData = $r;
        $brand_auto = $r['br'];
        $model_auto = $r['mo'];
        $year_auto = $r['yr'];
        $price_auto = $r['prc'];
        $transmission_auto = $r['tra'];
        $fuel_auto = $r['fl'];
        $engine_auto = $r['vol'];
        $mileage_auto = $r['mlg'];
        $color_auto = $r['clr'];
        $body_auto = $r['bt'];
        $drive_auto = $r['wd'];
        $loc_auto = $r['loc'];

        $cur = $r['cur'];
        if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
            $prc = $r['prc_n'];
            $o_prc = $r['prc'];
            // $o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.parseCurr($o_prc).'</span> '.( symb_rplc($r['cur']) ).'</span>';
        }else{
            $prc = $r['prc'];
            $o_prc = 0;
            // $o_prc_bl = '';
        }

        $car_title_name = ''.$r['br_nm'].' '.$r['mo_nm'].'' . "\n"; // . ' '. $lng['l']['car']['fl'][$r['fl']];
        // $caption_lines[] = $car_title_name;

        $spec_ar = ['yr', 'bt', 'mlg', 'vol', 'hp', 'fl', 'tra', 'wd', 'clr', 'sts', 'loc'];
        $iconTelegramParams = array(
            'yr'  => '📅',       // Год выпуска
            'bt'  => '🚙',       // Тип кузова
            'mlg' => '🛣️',      // Пробег
            'vol' => '⚙️',       // Объем двигателя
            'hp'  => '💪',       // Мощность
            'fl'  => '🔌⛽',     // Топливо
            'tra' => '🔄',       // КПП
            'wd'  => '⬆️',       // Привод
            'clr' => '⚪',       // Цвет
            'sts' => '👥',       // Количество мест
            'loc' => '📍',       // Адрес
        );
        foreach ($spec_ar as $v){
            if ($v=='loc' && $r[$v]=='0'){continue;}

            $v_lng = isset($lng['l']['car'][$v][$r[$v]]) ? $lng['l']['car'][$v][$r[$v]] : $r[$v];
            $v_lng = $v == 'mlg' ? parseCurr($r[$v]).' '.$lng['l']['unit'][ $r['unit'] ] : $v_lng;
            $v_lng = $v == 'vol' ? $r[$v].' '.$lng['l']['unit']['cm3'] : $v_lng;
            $v_lng = $v == 'hp' ? $r[$v].' '.$lng['l']['unit']['hp'].' ('.( round($r['hp']*0.735,0) ).' '.$lng['l']['unit']['kw'].')' : $v_lng;
            $v_lng = $v == 'clr' ? $v_lng.( isset($clr_arr[$r[$v]])?'':'' ) : $v_lng;
            $v_lng = $v == 'loc' ? $lng['t']['x']['address'][$r[$v]] : $v_lng;

            if (isset($r[$v])&&$r[$v]!=''){
                //var_dump( $lng['l']['car']['spec'][$v]); var_dump( $v_lng);
                $v_lng = str_replace("<sup>", "", $v_lng);
                $v_lng = str_replace("</sup>", "", $v_lng);

                if ($v=='loc') {
                    $caption_lines[] = $iconTelegramParams[ $v] . " ". $lng['l']['car']['spec'][$v] . ": Chișinău, ". $v_lng;
                }
                else {
                    $caption_lines[] = $iconTelegramParams[ $v] . " ". $lng['l']['car']['spec'][$v] . ": ". $v_lng;
                }

                // $caption_lines[] = $iconTelegramParams[ $v] . " ". $lng['l']['car']['spec'][$v] . ": ". $v_lng;
            }
        }
    }

    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id  ORDER BY `pos` ASC');
    $pdo->execute([ 'it_id' => $it_id ]);
    $i=1;
    $photo_urls = $car['photos'] = $car['photo_urls'] = $car['photo_paths'] = array();
    foreach ($pdo as $file){
        if($i>10){break;}

        $it_id = $file['it_id'];
        $p_path = $file['path'];
        $c_name = $file['name'];
        $file_path = $photo_folder.'/'.$p_path.'/'.$it_id.'/high/'.$c_name.'.jpg';

        if( file_exists($file_path) ) {
            // $photo_urls[] = "https://sauto.md/" . $file_path;
            $car['photos'][] = "https://www.sauto.md/" . $file_path;

            $car['photo_urls'][]  = "https://www.sauto.md/" . $file_path;
            $car['photo_paths'][] = $file_path;
        }
        $i++;
    }

// ─────────── 2. Generate Facebook message using PublicationService ───────────
    $car['name'] = $car_title_name;
    
    // Get fresh car data for message generation
    $pdo_msg = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id LIMIT 1');
    $pdo_msg->execute(['id' => $it_id]);
    $carDataForMessage = $pdo_msg->fetch(\PDO::FETCH_ASSOC);
    $carDataForMessage['id'] = $it_id;
    
    // Generate message using PublicationService
    $message = $publicationService->generateFacebookMessage($carDataForMessage, 'in_stock');

// ─────────── 4. Schedule Facebook Post ───────────
    try {
        // Calculate scheduled datetime
        $scheduled_date = date('Y-m-d');
        $scheduled_datetime = $scheduled_date . ' ' . $schedule_time . ':00';
        
        // If the time has already passed today, schedule for tomorrow
        if (strtotime($scheduled_datetime) <= time()) {
            $scheduled_date = date('Y-m-d', strtotime('+1 day'));
        }
        
        // Insert into scheduled posts table with retry logic for error 1615
        try {
            $stmt = $db->prepare("
                INSERT INTO {$prefx}_scheduled_facebook_posts 
                (car_id, catalog_type, scheduled_date, scheduled_time, status) 
                VALUES (:car_id, :catalog_type, :scheduled_date, :scheduled_time, 'pending')
            ");
            
            $stmt->execute([
                'car_id' => $it_id,
                'catalog_type' => 'in_stock',
                'scheduled_date' => $scheduled_date,
                'scheduled_time' => $schedule_time . ':00'
            ]);
        } catch (PDOException $e) {
            // Retry once for error 1615 (prepared statement needs re-preparation)
            if ($e->getCode() == 1615 || strpos($e->getMessage(), '1615') !== false) {
                $stmt = $db->prepare("
                    INSERT INTO {$prefx}_scheduled_facebook_posts 
                    (car_id, catalog_type, scheduled_date, scheduled_time, status) 
                    VALUES (:car_id, :catalog_type, :scheduled_date, :scheduled_time, 'pending')
                ");
                
                $stmt->execute([
                    'car_id' => $it_id,
                    'catalog_type' => 'in_stock',
                    'scheduled_date' => $scheduled_date,
                    'scheduled_time' => $schedule_time . ':00'
                ]);
            } else {
                throw $e; // Re-throw other errors
            }
        }
        
        // Update car as scheduled for Facebook
        $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `facebook_published`=:facebook_published WHERE `id`= :id ');
        $pdo->execute([ 'id' => $it_id, 'facebook_published' => 2 ]); // 2 = scheduled
        
        $returnIt['status'] = true;
        $returnIt['message'] = "Programat pentru publicare la {$scheduled_date} {$schedule_time}";
        
        // Log scheduling
        $publicationService->logPublication($it_id, 'in_stock', 'facebook', true, "Scheduled for {$scheduled_date} {$schedule_time}");
        
    } catch (Exception $e) {
        $returnIt['status'] = false;
        $returnIt['message'] = 'Eroare la programare: ' . $e->getMessage();
        
        // Log failed scheduling
        $publicationService->logPublication($it_id, 'in_stock', 'facebook', false, 'Scheduling error: ' . $e->getMessage());
    }

}
elseif ( __post('fn')=='sendToTelegramCars' ){

    $it_id = __post('id');

    $_COOKIE['lang']='ro';
    require (_DEFAULT.'/language.php');

    $phone = "+(373)69-977-674";
    $car_title_name = "";

    // var_dump( $prefx); gh3sp



    $price_auto = 0;
    $year_auto = 0;
    $marka_auto = "";
    $model_auto = "";

    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id LIMIT 1');
    $pdo->execute(['id' => $it_id]);

    $caption_lines = [ ];
    foreach ($pdo as $r){

        // var_dump( $r);  exit();

        $cur = $r['cur'];
        if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
            $prc = $r['prc_n'];
            $o_prc = $r['prc'];
            // $o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.parseCurr($o_prc).'</span> '.( symb_rplc($r['cur']) ).'</span>';
        }else{
            $prc = $r['prc'];
            $o_prc = 0;
            // $o_prc_bl = '';
        }

        $r['br_nm'] = str_replace(" ", "", $r['br_nm']);
        $r['mo_nm'] = str_replace(" ", "", $r['mo_nm']);

        $car_title_name = '<b>#' . $r['br_nm'] . $r['mo_nm'] . '</b>';
        $caption_lines[] = $car_title_name;

        // новая строка: Модель, год, цена в евро
        $caption_lines[] = '✅ <b>' . $r['yr'] . ', ' . parseCurr($prc) . ' ' . symb_rplc($cur) . '</b>';


        $price_auto = $prc;
        $year_auto =  $r['yr'];
        $marka_auto = $r['br_nm'];
        $model_auto = $r['mo_nm'];


        $spec_ar = ['bt', 'mlg', 'vol', 'hp', 'fl', 'tra', 'wd', 'clr', 'sts', 'loc'];
        $iconTelegramParams = array(
            'yr'  => '📅',       // Год выпуска
            'bt'  => '🚙',       // Тип кузова
            'mlg' => '🛣️',      // Пробег
            'vol' => '⚙️',       // Объем двигателя
            'hp'  => '💪',       // Мощность
            'fl'  => '🔌⛽',     // Топливо
            'tra' => '🔄',       // КПП
            'wd'  => '⬆️',       // Привод
            'clr' => '⚪',       // Цвет
            'sts' => '👥',       // Количество мест
            'loc' => '📍',       // Адрес
        );

        foreach ($spec_ar as $v){
            if ($v=='loc' && $r[$v]=='0'){continue;}

            $v_lng = isset($lng['l']['car'][$v][$r[$v]]) ? $lng['l']['car'][$v][$r[$v]] : $r[$v];
            $v_lng = $v == 'mlg' ? parseCurr($r[$v]).' '.$lng['l']['unit'][ $r['unit'] ] : $v_lng;
            $v_lng = $v == 'vol' ? $r[$v].' '.$lng['l']['unit']['cm3'] : $v_lng;
            $v_lng = $v == 'hp' ? $r[$v].' '.$lng['l']['unit']['hp'].' ('.( round($r['hp']*0.735,0) ).' '.$lng['l']['unit']['kw'].')' : $v_lng;
            $v_lng = $v == 'clr' ? $v_lng.( isset($clr_arr[$r[$v]])?'':'' ) : $v_lng;
            $v_lng = $v == 'loc' ? $lng['t']['x']['address'][$r[$v]] : $v_lng;


            if (isset($r[$v])&&$r[$v]!=''){
                $v_lng = str_replace("sup", "i", $v_lng);

                if ($v=='loc') {
                    $caption_lines[] = $iconTelegramParams[ $v] . " ". $lng['l']['car']['spec'][$v] . ": Chișinău, ". $v_lng;
                }
                else {
                    $caption_lines[] = $iconTelegramParams[ $v] . " ". $lng['l']['car']['spec'][$v] . ": ". $v_lng;
                }

            }
        }
    }

    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id  ORDER BY `pos` ASC');
    $pdo->execute([ 'it_id' => $it_id ]);
    $i=1;
    $photo_urls = array();
    $media = array(); // Initialize media array
    foreach ($pdo as $file){
        if($i>10){break;}

        $it_id = $file['it_id'];
        $p_path = $file['path'];
        $c_name = $file['name'];
        $file_path = $photo_folder.'/'.$p_path.'/'.$it_id.'/high/'.$c_name.'.jpg';

        if( file_exists($file_path) ) {
            // $photo_urls[] = "https://sauto.md/" . $file_path;
            $media[] = [
                'type'  => 'photo',
                'media' => new \CURLFile(
                    $file_path,
                    mime_content_type($file_path), // например, image/jpeg
                    basename($file_path)
                )
            ];
        }
        $i++;
    }

    //$caption_lines[] = "\n\n" . $phone;
    // строка с хэштег-комментарием
    $caption_lines[] = '📌 Apasă pe hashtag pentru a vedea alte mașini similare';

    // $caption_lines[] = "\n 🔽 Comentariile le citim și răspundem imediat";
    $caption_lines[] = "\n <a href='https://t.me/Sauto_B24_bot?start=".$marka_auto."_".$model_auto."_".$price_auto."_".$year_auto."'>👉 Comentariile le citim și răspundem imediat 👈</a>";


    // Use PublicationService for cars
    require_once $_SERVER['DOCUMENT_ROOT'] . '/App/Services/PublicationService.php';
    $publicationService = new \App\Services\PublicationService($db, $prefx);
    
    // First get car data to determine catalog type
    $pdo_temp = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id LIMIT 1');
    $pdo_temp->execute(['id' => $it_id]);
    $tempCarData = $pdo_temp->fetch(\PDO::FETCH_ASSOC);
    
    // Determine catalog type from car data
    $catalogType = $tempCarData['catalog_type'] ?? 'in_stock';
    
    // Get Telegram settings based on catalog type
    $telegramSettings = $publicationService->getTelegramSettings($catalogType);
    if (!$telegramSettings) {
        echo json_encode(['success' => false, 'message' => 'Telegram settings for regular cars not configured']);
        exit;
    }

    include_once "CTelegram.php";

    $bot_token = $telegramSettings['bot_token'];
    $chat_id = $telegramSettings['chat_id'];
    $Cbot = new Telegram( array('bot_token' => $bot_token, 'chat_id'=> $chat_id ) );

    // 1) Собираем массив ссылок или file_id ваших фото (до 18 штук)

    // 2) Формируем подпись: заголовок + список характеристик с эмодзи
    $caption = implode("\n", $caption_lines);

    // 3) Отправляем «одно» объявление
    $res = $Cbot->send_album_with_caption($media, $caption, 'HTML');

    $res = json_decode( $res , true);

    if($res['ok']) {
        /*
        $captionkeyboard = html_entity_decode('&nbsp;');

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔼 Comentariile le citim și răspundem imediat', 'url' => "https://t.me/Sauto_B24_bot?start=" . $marka_auto . "_" . $model_auto . "_" . $price_auto . "_" . $year_auto . ""]
                ]
            ]
        ];
        $res2 = $Cbot->send_caption_with_button("\xE3\x85\xA4", $keyboard);
        */
    }

    // var_dump( $media);
    // var_dump( $caption);
    // var_dump( $res);

    $returnIt = [];
    if($res['ok']) {
        $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `telegram_published`=:telegram_published WHERE `id`= :id ');
        $pdo->execute([ 'id' => $it_id, 'telegram_published' => 1 ]);
        
        // Log successful publication
        $publicationService->logPublication($it_id, 'in_stock', 'telegram', true, 'Published to regular cars channel');
        
        $returnIt['status'] = true;
    }
    else {
        // Log failed publication with detailed error
        $error_msg = isset($res['description']) ? $res['description'] : 'Unknown error';
        $publicationService->logPublication($it_id, 'in_stock', 'telegram', false, $error_msg);
        
        $returnIt['status'] = false;
        $returnIt['error'] = $error_msg;
        $returnIt['telegram_response'] = $res;
    }
}
