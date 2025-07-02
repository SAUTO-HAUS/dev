<?php defined( '_DOIT' ) or die( 'Restricted access' );

require_once(dirname(dirname(dirname(__FILE__))) . '/content/default/language.php');

$sa = array();

if ( isset($t_mp[1]) ){
	if ( in_array($t_mp[1], $lang_arr, true) ){ $zlng = $t_mp[1]; }
	else{ $zlng = 'ro'; }
}else{ $zlng = 'ro'; }

$z2 = isset($t_mp[2]) ? $t_mp[2] : '';
//$z3 = isset($t_mp[3]) ? $t_mp[3] : '';
$z3 = !isset($t_mp[3])?'':(in_array($z2, ['cars', 'tyres'])?( toNumber($t_mp[3])>0?toNumber($t_mp[3]):'' ):$t_mp[3]);

$zrbt = 'index, follow';

// Check if this is a subdomain and set noindex for subdomains
$current_host = $_SERVER['HTTP_HOST'] ?? '';
if (!empty($current_host) && strpos($current_host, '.sauto.md') !== false && $current_host !== 'sauto.md' && $current_host !== 'www.sauto.md') {
    $zrbt = 'noindex, nofollow';
}

$r['ttl']='';$r['h1']='';$r['dsc']='';$r['kwd']='';

$sa['meta']['ttl'] = 'Vînzarea autoturismelor și utilitarelor.';
$current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';

// Set language-dependent default H1 title
if ($current_lang == 'ru') {
    $sa['meta']['h1'] = 'Sauto – Автострахование в Кишиневе';
} elseif ($current_lang == 'en') {
    $sa['meta']['h1'] = 'Sauto – Car Insurance in Chișinău';
} else { // Romanian or default
    $sa['meta']['h1'] = 'Sauto - Asigurări Auto în Chișinău';
}

$sa['meta']['dsc'] = 'Sauto - Vânzare de mașini, pagina auto, vă oferim o gamă largă de mașini.';
$sa['meta']['kwd'] = 'sauto, md, Renault Megane, Renault Kadjar, Renault Clio, Nissan Qashqai, Nissan Juke, Nissan X Trail, Ford Focus, Opel Astra, Ford Transit, Ford Fiesta, Ford Fusion, VW Passat, VW Golf';

// Direct check for car filter URLs - apply SEO titles immediately 
if ($z2 == 'cars' && !is_numeric($z3) && isset($t_mp[3]) && !empty($t_mp[3])) {
    // This could be a brand in a clean URL
    $brand_code = str_replace('-', '_', $z3);
    $brand_name = '';
    $model_name = '';
    
    // Get brand name directly from URL segment
    $pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
    $pdo_brand->execute(['br' => $brand_code]);
    foreach ($pdo_brand as $brand_row) {
        $brand_name = $brand_row['br_nm'];
    }
    
    // If we have a brand and a model segment
    if (!empty($brand_name) && isset($t_mp[4]) && !empty($t_mp[4])) {
        $model_code = str_replace('-', '_', $t_mp[4]);
        
        $pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
        $pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
        foreach ($pdo_model as $model_row) {
            $model_name = $model_row['mo_nm'];
        }
        
        // Apply SEO titles if we have both brand and model
        if (!empty($model_name)) {
            $sa['meta']['ttl'] = "Cumpără {$brand_name} {$model_name} în Moldova";
            $sa['meta']['h1'] = "Cumpără {$brand_name} {$model_name} în Chișinău";
            $sa['meta']['dsc'] = "Automobile {$brand_name} {$model_name} în stoc și la comandă. Prețuri și oferte actuale.";
        }
    }
}

$sa['it']['img'] = 'https://www.sauto.md/media/images/site/sauto_new_logo_black.png';
	
//SELECT p.pid, p.cid, p.pname, c1.name1, c2.name2 FROM product p LEFT JOIN customer1 c1 ON p.cid = c1.cid
//'SELECT * FROM '.$prefx.'_car_ctlg c LEFT JOIN '.$prefx.'_car_pht p WHERE `id`=:id'

if ( in_array($z2, $url_arr) ){//if t_mp[2] is allowed part of url
	$z2=$z2==''?'home':$z2;
	
	//------------------------------------------------------------------------------- CTLG ROWS TAKE
	if( in_array($z2, ['cars', 'tyres']) && is_numeric($z3) && !isset($q_mp[1]) ){//if t_mp[2] has an item info, t_mp[3] is number
		$zl='';
		$p_type = $z2=='cars' ? 'car' : $z2;
		
		if ($z2=='cars'){$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id'); $pdo2 = $db->prepare('SELECT `name`, `main` FROM '.$prefx.'_car_pht WHERE `id`=:id AND `main`="1"'); $zl='car';}
		elseif ($z2=='tyres'){$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg WHERE `id`=:id'); $pdo2 = $db->prepare('SELECT `name`, `main` FROM '.$prefx.'_tyre_pht WHERE `it_id`=:id AND `main`="1"'); $zl='tyre';}
		
		$pdo->execute( array('id'=>$z3) );
		$ir=0;
		foreach ($pdo as $r){ foreach ($r as $k => $v){ $sa['it']['r'][$k] = $v; } $ir=1;}
		
		if ($ir==1){
			$zrbt = ( $z2=='cars' && ($r['n_a']=='1' || $r['act']=='0') ) ? 'noindex, follow' : $zrbt;
			
			$pdo2->execute(array('id'=>$r['id']));
			foreach ($pdo2 as $r2){ $sa['it']['img']='https://www.sauto.md/media/images/upload/'.$p_type.'/'.$sa['it']['r']['p_path'].'/'.$sa['it']['r']['id'].'/med/'.$r2['name'].'.jpg'; }
			unset($pdo2, $r2);
		}
	}
	//------------------------------------------------------------------------------- META START
	if ($z2 == 'cars' && !is_numeric($z3) && isset($t_mp[3])) {
		$brand_code = str_replace('-', '_', $z3);
		$brand_name = '';
		$model_name = '';
		
		// Get brand name directly from URL segment
		$pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
		$pdo_brand->execute(['br' => $brand_code]);
		foreach ($pdo_brand as $brand_row) {
			$brand_name = $brand_row['br_nm'];
		}
		
		// If we have a brand and a model segment
		if (!empty($brand_name) && isset($t_mp[4])) {
			$model_code = str_replace('-', '_', $t_mp[4]);
			
			$pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
			$pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
			foreach ($pdo_model as $model_row) {
				$model_name = $model_row['mo_nm'];
			}
			
			// Apply SEO titles if we have both brand and model
			if (!empty($model_name)) {
				$sa['meta']['ttl'] = "Cumpără {$brand_name} {$model_name} în Moldova";
				$sa['meta']['h1'] = "Cumpără {$brand_name} {$model_name} în Chișinău";
				$sa['meta']['dsc'] = "Automobile {$brand_name} {$model_name} în stoc și la comandă. Prețuri și oferte actuale.";
				goto skip_all_meta;
			}
		}
	}
	
	if( $z3=='' && !isset($q_mp[1]) ){//z2 any, no z3, no qr
		$pdo = $db->prepare('SELECT `ttl`, `h1`, `dsc`, `kwd` FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `lng`=:lng AND `tp`="main" AND `p1`=:p1 ');
		$pdo->execute(array( 'lng'=>$zlng, 'p1'=>$z2 ));
		$ir=0;
		foreach ($pdo as $r){$ir=1;}
		if ($ir==1){
			$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : $sa['meta']['ttl'];
			$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : $sa['meta']['h1'];
			$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : $sa['meta']['dsc'];
			$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] : $sa['meta']['kwd'];
		}
	}
	elseif ( $z3!='' && !isset($q_mp[1]) ){//z2 any, z3 isset, no qr
		// Debug information - log key values
		file_put_contents('debug_meta.log', "Clean URL Meta Debug:\n", FILE_APPEND);
		file_put_contents('debug_meta.log', "z2: {$z2}, z3: {$z3}\n", FILE_APPEND);
		file_put_contents('debug_meta.log', "GET params: " . print_r($_GET, true) . "\n", FILE_APPEND);
		file_put_contents('debug_meta.log', "t_mp: " . print_r($t_mp, true) . "\n\n", FILE_APPEND);
		
		// Try direct SEO for all brand/model combinations
		if ($z2 == 'cars' && !is_numeric($z3)) {
			// This could be a brand in a clean URL
			$brand_code = $z3;
			$brand_code = str_replace('-', '_', $brand_code);
			$brand_name = '';
			$model_name = '';
			
			// Get brand name directly from URL segment
			$pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
			$pdo_brand->execute(['br' => $brand_code]);
			foreach ($pdo_brand as $brand_row) {
				$brand_name = $brand_row['br_nm'];
			}
			
			// If we have a brand and a model segment
			if (!empty($brand_name) && isset($t_mp[4])) {
				$model_code = $t_mp[4]; 
				$model_code = str_replace('-', '_', $model_code);
				
				$pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
				$pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
				foreach ($pdo_model as $model_row) {
					$model_name = $model_row['mo_nm'];
				}
				
				// Log what we found
				file_put_contents('debug_meta.log', "Direct URL parsing: brand_name={$brand_name}, model_name={$model_name}\n\n", FILE_APPEND);
				
				// Apply SEO titles if we have both brand and model
				if (!empty($model_name)) {
					$sa['meta']['ttl'] = "Cumpără {$brand_name} {$model_name} în Moldova";
					$sa['meta']['h1'] = "Cumpără {$brand_name} {$model_name} în Chișinău";
					$sa['meta']['dsc'] = "Automobile {$brand_name} {$model_name} în stoc și la comandă. Prețuri și oferte actuale.";
					// Skip the rest of this section
					goto skip_regular_meta;
				}
			}
		}
		
		// Check if this is a clean URL for car filters (z3 is not numeric and is a brand name)
		if ($z2 == 'cars' && !is_numeric($z3) && isset($_GET['tg']) && $_GET['tg'] == 'fltr') {
			$brand_name = '';
			$model_name = '';
			
			// Get brand name
			if (isset($_GET['br'])) {
				$brand_code = $_GET['br'];
				$brand_code = str_replace('-', '_', $brand_code);
				
				$pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
				$pdo_brand->execute(['br' => $brand_code]);
				foreach ($pdo_brand as $brand_row) {
					$brand_name = $brand_row['br_nm'];
				}
			}
			
			// Get model name
			if (isset($_GET['mo']) && !empty($brand_name)) {
				$model_code = $_GET['mo'];
				$model_code = str_replace('-', '_', $model_code);
				
				$pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
				$pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
				foreach ($pdo_model as $model_row) {
					$model_name = $model_row['mo_nm'];
				}
			}
			
			// Apply SEO titles if we have both brand and model
			if (!empty($brand_name) && !empty($model_name)) {
				$sa['meta']['ttl'] = "Cumpără {$brand_name} {$model_name} în Moldova";
				$sa['meta']['h1'] = "Cumpără {$brand_name} {$model_name} în Chișinău";
				$sa['meta']['dsc'] = "Automobile {$brand_name} {$model_name} în stoc și la comandă. Prețuri și oferte actuale.";
				// Skip the rest of this section
				goto skip_regular_meta;
			}
		}
		
		$pdo = $db->prepare('SELECT `ttl`, `h1`, `dsc`, `kwd` FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `lng`=:lng AND `tp`="item" AND `p1`=:p1 AND `p2`=:p2 ');
		$pdo->execute(array( 'lng'=>$zlng, 'p1'=>$z2, 'p2'=>$z3 ));
		$ir=0;
		foreach ($pdo as $r){$ir=1;}
		if ($ir==1){
			if ($z2 == 'cars'){
				$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].' '.$lng['l']['car']['bt'][ $sa['it']['r']['bt'] ].' '.$lng['l']['car']['tra'][ $sa['it']['r']['tra'] ].' '.$sa['it']['r']['prc'].$lng['l']['cur'][ $sa['it']['r']['cur'] ].' '.$sa['it']['r']['vol'].' '.$lng['t']['seo']['car_inf_ttl'];
				$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].', id-'.$sa['it']['r']['id'];
				$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : $lng['w']['used_car'].' '.$sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].' '.$lng['l']['car']['clr'][ $sa['it']['r']['clr'] ].' '.$sa['it']['r']['vol'].' '.$lng['t']['seo']['car_inf_dsc'];
				$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] : mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['auto'].','.$lng['w']['buy'].','.$sa['it']['r']['br'].','.$sa['it']['r']['mo'].','.$lng['l'][$zl]['bt'][$sa['it']['r']['bt']].','.$lng['l'][$zl]['tra'][$sa['it']['r']['tra']].','.$lng['l'][$zl]['fl'][$sa['it']['r']['fl']].','.$lng['l'][$zl]['clr'][$sa['it']['r']['clr']].',id'.$sa['it']['r']['id'], "UTF-8");
			}elseif ($z2 == 'tyres'){
				$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : ( $sa['it']['r']['br'].' | '.$sa['it']['r']['w'].'/'.$sa['it']['r']['h'].' R'.$sa['it']['r']['d'].($sa['it']['r']['c']==1?'C':'').' | '.$lng['l']['tyre']['ss'][$sa['it']['r']['ss']].' | '.$sa['it']['r']['prc'].' '.$sa['it']['r']['cur'] );
				$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : ( $sa['it']['r']['br'].' '.$sa['it']['r']['w'].'/'.$sa['it']['r']['h'].' R'.$sa['it']['r']['d'].($sa['it']['r']['c']==1?'C':'').', id-'.$sa['it']['r']['id'] );
				$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : ( $lng['w']['sale'].' '.(mb_strtolower($lng['w']['tyres'], "UTF-8")).' '.$sa['it']['r']['br'].' '.$sa['it']['r']['w'].'/'.$sa['it']['r']['h'].' R'.$sa['it']['r']['d'].($sa['it']['r']['c']==1?'C':'').' [ID-'.$sa['it']['r']['id'].'] '.$lng['u']['for'].' '.$sa['it']['r']['prc'].$sa['it']['r']['cur'].' '.$lng['u']['in'].' '.$lng['w']['chisinau'].'/'.$lng['w']['moldova'].'(MD)' );
				$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] : mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['tyres'].','.$lng['w']['buy'].','.$sa['it']['r']['br'].','.$sa['it']['r']['w'].','.$sa['it']['r']['h'].',r'.$sa['it']['r']['d'].($sa['it']['r']['c']==1?'c':'').','.$lng['l']['tyre']['ss'][$sa['it']['r']['ss']].',id'.$sa['it']['r']['id'], "UTF-8");
			}elseif ($z2 == 'services'){
				$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : $sa['meta']['ttl'];
				$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : $sa['meta']['h1'];
				$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : $sa['meta']['dsc'];
				$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] : $sa['meta']['kwd'];
			}
		}
		
		skip_regular_meta:
	}
	elseif ( in_array($z2, array('cars', 'tyres')) && $z3=='' && isset($q_mp[1]) ){//z2 cars/tyres, no z3, qr isset
		$p_type = $z2=='cars' ? 'car' : $z2;
		$p_type = $z2=='tyres' ? 'tyre' : $p_type;
		$zqr = str_replace('tg=fltr&', '', $q_mp[1]);
		$zttl='';$zh1='';$zdsc='';$zkwd='';
		$pdo = $db->prepare('SELECT `ttl`, `h1`, `dsc`, `kwd` FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `lng`=:lng AND `tp`="fltr" AND `p1`=:p1 AND `qr`=:qr ');
		$pdo->execute(array( 'lng'=>$zlng, 'p1'=>$z2, 'qr'=>$zqr ));
		$ir=0;
		foreach ($pdo as $r){$ir=1;}
		
		// Check if we have brand and model parameters for cars (from GET params or URL segments)
		$has_brand = isset($_GET['br']);
		$has_model = isset($_GET['mo']);
		$brand_name = '';
		$model_name = '';
		
		// Get brand and model names if they exist
		if ($z2 == 'cars' && $has_brand) {
			$brand_code = $_GET['br'];
			// Fix for brand names with dashes
			$brand_code = str_replace('-', '_', $brand_code);
			
			$pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
			$pdo_brand->execute(['br' => $brand_code]);
			foreach ($pdo_brand as $brand_row) {
				$brand_name = $brand_row['br_nm'];
			}
			
			if ($has_model && !empty($brand_name)) {
				$model_code = $_GET['mo'];
				// Fix for model names with dashes
				$model_code = str_replace('-', '_', $model_code);
				
				$pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
				$pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
				foreach ($pdo_model as $model_row) {
					$model_name = $model_row['mo_nm'];
				}
			}
		}
		
		$i=0;
		foreach ($_GET as $k => $v){
			if($k=='tg'){continue;}
			$v = explode('-', $v);
			
			$zttl .= (isset($lng['l'][$p_type]['spec'][$k])) ? ', '.$lng['l'][$p_type]['spec'][$k].': ' : $k;
			$zh1 .= ($i==0) ? ' '.$lng['u']['where'] : ', '; $zh1 .= (isset($lng['l'][$p_type]['spec'][$k])) ? mb_strtolower(' '.$lng['l'][$p_type]['spec'][$k].': ', "UTF-8") : mb_strtolower(' '.$k.': ', "UTF-8");
			$zdsc .= ($i==0) ? ' '.$lng['u']['where'] : ','; $zdsc .= (isset($lng['l'][$p_type]['spec'][$k])) ? ' '.$lng['l'][$p_type]['spec'][$k].':' : ' '.$k.':';
			$zkwd .= (isset($lng['l'][$p_type]['spec'][$k])) ? ','.$lng['l'][$p_type]['spec'][$k] : ','.$k;
			$i2=0;
			foreach ($v as $it){
				$zttl .= ($i2==0) ? '' : '-'; $zttl .= (isset($lng['l'][$p_type][$k][$it])) ? $lng['l'][$p_type][$k][$it] : $it;
				$zh1 .= ($i2==0) ? '' : '-'; $zh1 .= (isset($lng['l'][$p_type][$k][$it])) ? mb_strtolower($lng['l'][$p_type][$k][$it], "UTF-8") : mb_strtolower($it, "UTF-8");
				$zdsc .= ($i2==0) ? '' : '-'; $zdsc .= (isset($lng['l'][$p_type][$k][$it])) ? $lng['l'][$p_type][$k][$it] : $it;
				$zkwd .= (isset($lng['l'][$p_type][$k][$it])) ? ','.$lng['l'][$p_type][$k][$it] : ','.$it;
				$i2++;
				if($i2>1){$zrbt = 'noindex, follow';}
			}
			$i++;
		}
		if ($ir==1 || ($has_brand && $has_model && !empty($brand_name) && !empty($model_name))){
			if ($z2 == 'cars'){
				if ($has_brand && $has_model && !empty($brand_name) && !empty($model_name)) {
					// SEO-formatted title and description for brand+model filters
					$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : "Cumpără {$brand_name} {$model_name} în Moldova";
					$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : "Cumpără {$brand_name} {$model_name} în Chișinău";
					$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : "Automobile {$brand_name} {$model_name} în stoc și la comandă. Prețuri și oferte actuale.";
				} else {
					// Default format for other car filters
					$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : $lng['w']['search'].' | '.$lng['w']['vehicles'].$zttl.' | Sauto Haus';
					$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : 'Sauto Haus. '.$lng['w']['vehicles'].$zh1;
					$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : $lng['w']['ctlg'].$zdsc.'. '.$lng['t']['seo']['dsc1'];
				}
				$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] :  mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['auto'].','.$lng['w']['buy'].$zkwd, "UTF-8");
			}elseif ($z2 == 'tyres'){
				$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : $lng['w']['search'].' | '.$lng['w']['tyres'].$zttl.' | Sauto Haus';
				$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : 'Sauto Haus. '.$lng['w']['tyres'].$zh1;
				$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : $lng['w']['ctlg'].$zdsc.'. '.$lng['t']['seo']['dsc2'];
				$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] : mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['tyres'].','.$lng['w']['buy'].$zkwd, "UTF-8");
			}
		}
		unset($zqr, $zttl, $zh1, $zdsc, $zkwd);
	}
}

skip_all_meta:
//--------------------------------ECHO_META, TITLE, OG

// Set the meta information
echo '
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<meta name="robots" content="'.$zrbt.'" />
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" id="mobile_viewport" />

';

// Force standardized titles for brand/model pages - FINAL OVERRIDE before output
if ($z2 == 'cars' && !is_numeric($z3) && isset($t_mp[3]) && !empty($t_mp[3]) && isset($t_mp[4]) && !empty($t_mp[4])) {
    $brand_code = str_replace('-', '_', $t_mp[3]);
    $model_code = str_replace('-', '_', $t_mp[4]);
    
    $brand_name = '';
    $model_name = '';
    
    $pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
    $pdo_brand->execute(['br' => $brand_code]);
    foreach ($pdo_brand as $row) {
        $brand_name = $row['br_nm'];
    }
    
    if (!empty($brand_name)) {
        $pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
        $pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
        foreach ($pdo_model as $row) {
            $model_name = $row['mo_nm'];
        }
        
        if (!empty($model_name)) {
            if ($zlng == 'ro') {
                $title_to_show = "Cumpără {$brand_name} {$model_name} în Moldova | Sauto Haus";
            } elseif ($zlng == 'ru') {
                $title_to_show = "Купить {$brand_name} {$model_name} в Молдове | Sauto Haus";
            } else {
                $title_to_show = "Buy {$brand_name} {$model_name} in Moldova | Sauto Haus";
            }
            
            // Log the title that will be shown
            file_put_contents('debug_title_final.log', "Page: /cars/{$brand_code}/{$model_code}, Title: {$title_to_show}\n", FILE_APPEND);
            
            // Use this title instead of the one from $sa['meta']['ttl']
            echo '<title>'.$title_to_show.'</title>';
            echo '<meta property="og:title" content="'.$title_to_show.'">
<meta property="og:description" content="'.$sa['meta']['dsc'].'">
<meta property="og:type" content="website">
<meta property="og:image" content="'.$sa['it']['img'].'">
<meta property="og:site_name" content="Sauto.md">
<meta property="og:url" content="'.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'">

<meta name="description" content="'.$sa['meta']['dsc'].'" />
<meta name="keywords" content="'.$sa['meta']['kwd'].'" />
';
            // Skip the normal title output
            goto skip_regular_title;
        }
    }
}

// Regular title output if we didn't use our forced title
echo '<title>'.$sa['meta']['ttl'].'</title>
<meta property="og:title" content="'.$sa['meta']['ttl'].'">
<meta property="og:description" content="'.$sa['meta']['dsc'].'">
<meta property="og:type" content="website">
<meta property="og:image" content="'.$sa['it']['img'].'">
<meta property="og:site_name" content="Sauto.md">
<meta property="og:url" content="'.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'">

<meta name="description" content="'.$sa['meta']['dsc'].'" />
<meta name="keywords" content="'.$sa['meta']['kwd'].'" />
';

skip_regular_title:
unset($zrbt);