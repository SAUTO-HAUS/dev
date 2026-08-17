<?php defined( '_DOIT' ) or die( 'Restricted access' );

$redirect = 0;
$is_https = isset($is_https) ? (int)$is_https : 0;

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off") ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                ? 'https' : 'http';

if ($protocol === 'http' && $is_https === 1) {
    $protocol = 'https';
    $redirect = 1;
} elseif ($protocol === 'https' && $is_https === 0) {
    $protocol = 'http';
    $redirect = 1;
}

if ( (substr($http_host, 0, 4) !== 'www.') && $is_www == 1 ) {
	$http_host = 'www.'.$http_host; $redirect = 1;
}
if ( (substr($http_host, 0, 4) === 'www.') && $is_www == 0 ) {
	$http_host = str_replace('www.', '', $http_host); $redirect = 1;
}

//---------------------------------999 ads redirect

/*if ( strpos($uri, 'utm_campaign') ){
	
	$pdo = $db->prepare('UPDATE '.$prefx.'_visitors SET `value`=`value`+1 WHERE `source`="numbers" ');
	$pdo->execute();
	
	$uri = '';
	$redirect = 1;
}*/

//----------------------------------old site checker

if (isset($q_mp[1])){//query check
	if (isset($_GET['tg'])&&$_GET['tg']=='filter'){//tg=filter
		$get=[];
        $get['tg']='fltr';
        $qr='tg='.$get['tg'];
        $q_uri = explode('?', $uri);

		foreach($_GET as $k=>$ar){
			if($k=='tg') {
                continue;
            }
			if ($k=='cr'){
                foreach($_GET['cr'] as $k => $ar) {
                    $get['br'][]=$k;
                    foreach($ar as $v) {
                        $get['mo'][] = $v;
                    }
                }
            } else {
                $k=(isset($o2n['s'][$k]))?$o2n['s'][$k]:$k;
                foreach($ar as $v) {
                    $get[$k][] = $o2n['v'][$v] ?? $v;
                }
            }
		}
		foreach($get as $k=>$ar){
            if($k=='tg'){
                continue;
            }
            $i=0;
            foreach($ar as $v) {
                $qr.=($i==0)?'&'.$k.'=':'-';
                $qr.=$v;$i++;
            }
        }
        $uri = $q_uri[0].'?'.$qr;
        $redirect = 1;
	}
}


if ( strpos($uri, 'catalog') ){
	if ( strpos($uri, 'car_info') ){
		$car_id = isset($old_mp[3]) ? (int)$old_mp[3] : 0;
		if ($car_id > 0) {
			$pdo = $db->prepare('SELECT catalog_type FROM '.$prefx.'_car_ctlg WHERE id=:id LIMIT 1');
			$pdo->execute(['id' => $car_id]);
			$car = $pdo->fetch(PDO::FETCH_ASSOC);
			$section = (isset($car['catalog_type']) && $car['catalog_type'] == 'on_order') ? 'ordercars' : 'cars';
			$uri = '/'.$_COOKIE['lang'].'/'.$section.'/'.$car_id;
		} else {
			$uri = str_replace(['catalog?1','catalog?','catalog'], 'cars', $uri);
			$uri = str_replace(['scar_info?', 'car_info?'], '/', $uri);
		}
	} else {
		$uri = str_replace(['catalog?1','catalog?','catalog'], 'cars', $uri);
	}
	$redirect = 1;
}

if ( strpos($uri, 'offers?offer') ){
	$uri = str_replace('offers?offer_info?', 'offer/', $uri);
	$uri = str_replace('?1', '', $uri);
	$redirect = 1;
}

if ( strpos($uri, 'offers') ) {
	$uri = str_replace('offers', 'services', $uri);
	$redirect = 1;
}
if ( strpos($uri, 'conditions') ) {
	$uri = str_replace('conditions', 'services', $uri);
	$redirect = 1;
}

if ( strpos($uri, '/car/') ){
	$uri = str_replace('/car/', '/cars/', $uri);
	$redirect = 1;
}

if ((isset($t_mp[2]) && ($t_mp[2]=='cars' || $t_mp[2]=='ordercars')) && isset($t_mp[3]) && !is_numeric($t_mp[3])) {
    $section = $t_mp[2];

    // Import regions on /ordercars are a clean-path filter (import country), NOT a brand.
    // Set ic here so the later brand-lookup block does not force br=korea (empty catalog).
    if ($section === 'ordercars' && in_array(strtolower(explode('?', $t_mp[3])[0]), ['korea', 'europe', 'canada', 'usa', 'china'], true)) {
        $_GET['tg'] = 'fltr';
        $_GET['ic'] = strtolower(explode('?', $t_mp[3])[0]);
    } elseif (isset($t_mp[4]) && !empty($t_mp[4])) {
        $_GET['tg'] = 'fltr';
    } else {
        $brand_model = explode('?', $t_mp[3])[0];
        
        $pdo = $db->prepare('SELECT `br`, `br_nm` FROM '.$prefx.'_car_list GROUP BY `br`, `br_nm` ORDER BY LENGTH(`br`) DESC');
        $pdo->execute();
        $brands = $pdo->fetchAll(PDO::FETCH_ASSOC);
        
        $found_brand = false;
        foreach ($brands as $brand_row) {
            $brand_url = strtolower(str_replace('_', '-', $brand_row['br']));
            
            if (strpos($brand_model, $brand_url) === 0) {
                $remainder = substr($brand_model, strlen($brand_url));
                
                if ($remainder === '' || $remainder === false) {
                    $found_brand = true;
                    $_GET['tg'] = 'fltr';
                    $_GET['br'] = $brand_row['br'];
                    $t_mp[3] = $brand_url;
                    break;
                } elseif ($remainder[0] === '-') {
                    $found_brand = true;
                    $model_part = substr($remainder, 1);
                    
                    if (!empty($model_part)) {
                        $lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
                        $new_url = '/'.$lang.'/'.$section.'/'.$brand_url.'/'.$model_part;
                        
                        $qs = isset($q_mp[1]) ? '?'.$q_mp[1] : '';
                        
                        header('HTTP/1.1 301 Moved Permanently');
                        header('Location: '.$protocol.'://'.$http_host.$new_url.$qs);
                        exit();
                    }
                    break;
                }
            }
        }
        
        if (!$found_brand) {
            $_GET['tg'] = 'fltr';
            $_GET['br'] = str_replace('-', '_', $brand_model);
            $t_mp[3] = $brand_model;
        }
    }
}

if (isset($_GET['tg']) && $_GET['tg'] == 'fltr' && isset($_GET['br']) && !isset($t_mp[3])) {
    $section = (isset($t_mp[2]) && $t_mp[2] == 'ordercars') ? 'ordercars' : 'cars';
    $clean_url = '/'.$_COOKIE['lang'].'/'.$section.'/'.str_replace('_', '-', strtolower($_GET['br']));
    if (isset($_GET['mo']) && !empty($_GET['mo'])) {
        $clean_url .= '/'.str_replace('_', '-', strtolower($_GET['mo']));
    }
    
    if ($uri !== $clean_url) {
        $uri = $clean_url;
        $redirect = 1;
    }
}

if ( strpos($uri, '/tyre/') ){
	$uri = str_replace('/tyre/', '/tyres/', $uri);
	$redirect = 1;
}

if ( strpos($uri, '/virtual') ){
	$uri = str_replace('/virtual', '/contacts', $uri);
	$redirect = 1;
}

//-----------------------------------

if ( strpos($uri, 'index.php') ){
	$uri = str_replace('index.php', '', $uri);
	$redirect = 1;
}

if ( !in_array($t_mp[1], $lang_arr, true) ) {
	if( isset($_COOKIE['lang']) ) {
        $uri = '/'.$_COOKIE['lang'] . $uri;
    } elseif( $t_mp[1]=='md' ) {
        $uri = str_replace('md', 'ro', $uri);
    } else {
        $uri = '/' . $default_lang . $uri;
    }
	$redirect = 1;
}

if ( substr($uri, -1) == '/' ){
	$uri = substr($uri, 0, -1);
	$redirect = 1;
}

if ($redirect == 1){
	// Preserve the query string (filters/sort/region ?ic=) — dropping it here lost the
	// import region when redirecting brand/model URLs like /ordercars/toyota/rav-4?ic=korea.
	$redir_qs = '';
	if (isset($q_mp[1]) && $q_mp[1] !== '' && strpos($uri, '?') === false) {
		$redir_qs = '?'.$q_mp[1];
	}
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: '.$protocol.'://'.$http_host . $uri . $redir_qs);
	exit();
}
