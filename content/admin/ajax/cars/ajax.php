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
    define('APP_ID', '1082088863732549');
    define('APP_SECRET', '77368f52ab263907ee1fe3ea72909289');
    define('PAGE_ID', '725963964220309');

    // long user tokern paik
    //define('PAGE_TOKEN', 'EAAPYJ3JWk0UBO2hpvYZAYjlnhEV2bKZAdZCTkPAkiCuNVPQnP8FrVuITZCX8DMsZB2d0OqvkXTr0JJs5lM0yKg9QSvt7uhtEcFLgclEONGHcq9P4tZBVDcq8HycG8bZA9KZCfcgC7JblkqeoB3INfpo3TyZAPNi7EaTqPMwSZBRXMX3MLw923CRNnU81cYNTtjijL2XCap0RrLQJZCnTGaq');
    //define('PAGE_TOKEN', 'EAAPYJ3JWk0UBO8RKssEdMuJ6MZAB7O1Vpr0Fq9YyBarZB9pFiplHIDdKSpi8Lt45ft6ZAck5hYZBSeTeJFzFlHYx3AUo45ZCxZCYeTuQZB0LSLO6bePh9nBr9BmbTJSSFjndPhuCIObEt0ALVHUMl3ELAOS5KHl6FIDX4jIocv7Ce6VFlBoZCVjsarTzUN0tkUcx8oWuTRNKHkZAvGzAa');
    // Page-token sauto - paik
    define('PAGE_TOKEN', 'EAAPYJ3JWk0UBO6G3TndhaeRaj06GTzZC9nmIqBzdwTEzVekE6z3Yr5xH5wUFH3cVycDcugKYvOORP7cSuLk6diRDBiGhW2m8F3ZA8iUKPgsv9p85Y6ZA22ZAvTR5ulW24A6eFKJtL7ndCQlVsgiReI7yybKAbB20NaH5wOrOuz22SUryrwNgcjdQYjyiH5DPfNaPG4rbyXFzbqdBQqFbA5t8');

    define('GRAPH_VER', 'v22.0');


    $carId = $it_id = __post('id');

    $_COOKIE['lang']='ro';
    require (_DEFAULT.'/language.php');

    $phone = "+37379600446";
    $car_title_name = "";

    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`= :id LIMIT 1');
    $pdo->execute(['id' => $it_id]);

    $caption_lines = [ ];
    $brand_auto = "";
    $model_auto = "";
    foreach ($pdo as $r){
        // print_r( $r);
        $brand_auto = $r['br'];
        $model_auto = $r['mo'];

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

// ─────────── 2. Собираем текст поста с emoji ───────────
    $car['name'] = $car_title_name;

    $message = "🔹 {$car['name']} ". parseCurr( $prc) . " €";
    $message.= "\n\n" . $caption = implode("\n", $caption_lines);
    $message.= "\n\n" . $phone;
    // $message.= "\n\n" . parseCurr( $prc) . " " . symb_rplc( $cur);
    /*$message.= "\n\n" ."👉 Alte modele aici: https://www.sauto.md/ro/cars?tg=fltr&mo=" .
        urlencode(strtolower($r['mo'])) . "&br=" .
        urlencode(strtolower($r['br'])) .
        "&utm_source=social&utm_medium=organic&utm_campaign=new_auto";*/

    $message .= "\n\n". "👉 Alte modele aici: " . "https://www.sauto.md/ro/cars/".$brand_auto."-".$model_auto."?utm_source=social&utm_medium=organic&utm_campaign=new_auto";

// ─────────── 3. Универсальный вызов Graph API ───────────
    function graphCall( $endpoint, array $params = [],  $method = 'POST') {
        $url = "https://graph.facebook.com/" . GRAPH_VER . $endpoint;
        if ($method === 'GET') $url .= '?' . http_build_query($params);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            // CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_SSL_VERIFYPEER => true,
            // CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($raw, true);

        // var_dump(" graphCall ");  var_dump( $data);

        return $data;
    }

// ─────────── 4. Загружаем ≤10 фото “временными” ───────────
    $mediaFbids = [];
    foreach (array_slice($car['photo_paths'], 0, 10) as $localPath) {
        $curlFile = new \CURLFile(
            $localPath,
            mime_content_type($localPath),
            basename($localPath)
        );

        $upload = graphCall(
            '/' . PAGE_ID . '/photos',
            [
                'access_token' => PAGE_TOKEN,
                'published'    => 'false',
                // 'temporary'    => 'true',
                // вместо 'url' передаём файл
                'source'       => $curlFile,
            ],
            'POST'
        );

        if (!empty($upload['id'])) {
            $mediaFbids[] = $upload['id'];
        }
    }
    $attached = [];
    foreach ($mediaFbids as $i => $fbid) {
        $attached["attached_media[$i]"] = json_encode(
            ['media_fbid' => $fbid], JSON_UNESCAPED_SLASHES
        );
    }

// ─────────── 6. Публикуем черновик поста ───────────
    // var_dump( "message");  // var_dump( $message);

    $post = graphCall(
        '/' . PAGE_ID . '/feed',
        $attached + [
            'message' => $message,
            // 'published' => 'true',   // 2025/05/18
            // 'unpublished_content_type' => 'DRAFT', // 2025/05/18
            'access_token' => PAGE_TOKEN
        ],
        'POST'
    );

// ─────────── 7. Отдаём результат фронтенду ───────────
    // header('Content-Type: application/json');

    $returnIt = [];
    $post_id_arr = explode('_', $post['id']);

    $plink = graphCall(
        '/' . $post['id'],
        ['fields' => 'permalink_url', 'access_token' => PAGE_TOKEN],
        'GET'
    );
    // echo "📎 Перманентная ссылка: ", $plink['permalink_url'], PHP_EOL;

    if($post_id_arr['0']>0){
        // echo json_encode(['status' => 'ok', 'post_id' => $post['id']]);

        try {
            // Проверяем соединение с базой данных перед выполнением запроса
            if (!$db) {
                throw new Exception('Database connection lost');
            }
            
            $pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `facebook_published`=:facebook_published WHERE `id`= :id ');
            $pdo->execute([ 'id' => $it_id, 'facebook_published' => 1 ]);
            $returnIt['status'] = true;
            $returnIt['post_id'] = $post['id'];
            
            error_log('Facebook publication successful for car ID: ' . $it_id . ', post_id: ' . $post['id']);
        } catch (PDOException $e) {
            error_log('Facebook publication database error for car ID ' . $it_id . ': ' . $e->getMessage());
            
            // Если ошибка 1615 - попробуем переподключиться и повторить
            if ($e->getCode() == 1615 || strpos($e->getMessage(), '1615') !== false) {
                try {
                    // Создаем новое соединение
                    $new_pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `facebook_published`=:facebook_published WHERE `id`= :id ');
                    $new_pdo->execute([ 'id' => $it_id, 'facebook_published' => 1 ]);
                    $returnIt['status'] = true;
                    $returnIt['post_id'] = $post['id'];
                    
                    error_log('Facebook publication successful after retry for car ID: ' . $it_id);
                } catch (Exception $retry_e) {
                    error_log('Facebook publication failed after retry for car ID ' . $it_id . ': ' . $retry_e->getMessage());
                    $returnIt['status'] = true; // Все равно возвращаем успех, так как пост опубликован
                    $returnIt['post_id'] = $post['id'];
                    $returnIt['db_warning'] = 'Database update failed but post was published';
                }
            } else {
                // Для других ошибок логируем и продолжаем
                $returnIt['status'] = true; // Пост опубликован, это главное
                $returnIt['post_id'] = $post['id'];
                $returnIt['db_warning'] = 'Database update failed: ' . $e->getMessage();
            }
        } catch (Exception $e) {
            error_log('Facebook publication general error for car ID ' . $it_id . ': ' . $e->getMessage());
            $returnIt['status'] = true; // Пост опубликован, это главное
            $returnIt['post_id'] = $post['id'];
            $returnIt['db_warning'] = 'Database update failed: ' . $e->getMessage();
        }
    }
    else {
        $returnIt['status'] = false;
        $returnIt['message'] = ($post);
        $returnIt['post_id'] = 0;
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


    include_once "CTelegram.php";

    $bot_token = "8169302156:AAEe1j7AASXegfKRdWB-rSiaKY-PSgqkGgo";
    $bot_token = "7459955785:AAGTMPvUkh2Fktar7ZpNlHBFsq43FH_DPsY"; // sauto
    $chat_id = '-1002605369940';
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
        $returnIt['status'] = true;
    }
    else {
        $returnIt['status'] = false;
    }
}

