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
	$uri = str_replace(['catalog?1','catalog?','catalog'], 'cars', $uri);
	if ( strpos($uri, 'car_info') ){
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_catalog WHERE `id`=:id');
		$pdo->execute( ['id' => $old_mp[3]] );
		foreach ($pdo as $row){
			$c_brand = $row['brand'];
			$c_model = $row['model'];
		}
		$uri = str_replace(['scar_info?', 'car_info?'], '/'.$c_brand.'-'.$c_model.'-', $uri);
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

// Special handling for car filters - we want to keep clean URLs
// Format: /lang/cars/brand-model but process as brand/model internally
if (isset($_GET['tg']) && $_GET['tg'] == 'fltr' && isset($_GET['br'])) {
    // Convert query parameters to clean URL with brand-model format
    $clean_url = '/'.$_COOKIE['lang'].'/cars/'.str_replace('_', '-', $_GET['br']);
    if (isset($_GET['mo'])) {
        $clean_url .= '-'.str_replace('_', '-', $_GET['mo']);
    }
    
    if ($uri !== $clean_url) {
        $uri = $clean_url;
        $redirect = 1;
    }
}

// Handle clean URLs for car filters and single car pages
if ((isset($t_mp[2]) && ($t_mp[2]=='cars' || $t_mp[2]=='ordercars')) && isset($t_mp[3])) {
    // Check if this is a numeric ID (single car) or a brand/model format
    if (!is_numeric($t_mp[3])) {
        $url_segments = explode('-', $t_mp[3]);
        $last_segment = end($url_segments);
        
        if (is_numeric($last_segment) && count($url_segments) > 1) {
            file_put_contents('debug_redirect.log', "Old URL format detected in redirect.php: {$t_mp[3]}\n", FILE_APPEND);
        } else {
            $_GET['tg'] = 'fltr';
            
            // Handle URLs like cars/ds-automobiles-ds-7-crossback
            $url_parts = explode('/', $uri);
            $brand_model = end($url_parts);
            
            // Try to match the brand first from the database
            $pdo = $db->prepare('SELECT `br`, `br_nm` FROM '.$prefx.'_car_list GROUP BY `br`, `br_nm` ORDER BY LENGTH(`br`) DESC');
            $pdo->execute();
            $brands = $pdo->fetchAll(PDO::FETCH_ASSOC);
            
            $found_brand = false;
            foreach ($brands as $brand) {
                $brand_url = str_replace('_', '-', $brand['br']);
                if (strpos($brand_model, $brand_url) === 0) {
                    // Found the brand, everything after it is the model
                    $found_brand = true;
                    $model = trim(substr($brand_model, strlen($brand_url)), '-');
                    
                    $_GET['br'] = $brand['br'];
                    if (!empty($model)) {
                        $_GET['mo'] = str_replace('-', '_', $model);
                    }
                    
                    // Set t_mp array to match old format for compatibility
                    $t_mp[3] = $brand_url;
                    if (!empty($model)) {
                        $t_mp[4] = $model;
                    }
                    break;
                }
            }
            
            if (!$found_brand) {
                // Fallback: try to split at the last hyphen
                $last_brand_pos = strrpos($brand_model, '-');
                if ($last_brand_pos !== false) {
                    $brand = substr($brand_model, 0, $last_brand_pos);
                    $model = substr($brand_model, $last_brand_pos + 1);
                    
                    $_GET['br'] = str_replace('-', '_', $brand);
                    $_GET['mo'] = str_replace('-', '_', $model);
                    
                    $t_mp[3] = $brand;
                    $t_mp[4] = $model;
                } else {
                    // No model specified, just brand
                    $_GET['br'] = str_replace('-', '_', $brand_model);
                    $t_mp[3] = $brand_model;
                }
            }
            
            // Log for debugging
            file_put_contents('debug_redirect.log', "Processing URL: " . print_r($t_mp, true) . "\n", FILE_APPEND);
            file_put_contents('debug_redirect.log', "Set GET params: " . print_r($_GET, true) . "\n", FILE_APPEND);
            file_put_contents('debug_redirect.log', "Final URI: {$uri}\n\n", FILE_APPEND);
        }
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
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: '.$protocol.'://'.$http_host . $uri);
	exit();
}
