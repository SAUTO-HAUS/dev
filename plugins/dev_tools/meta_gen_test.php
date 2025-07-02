<?php defined( '_DOIT' ) or die( 'Restricted access' );

require_once(dirname(dirname(dirname(__FILE__))) . '/content/default/language.php');

$sa = array();

$z2 = isset($t_mp[2]) ? $t_mp[2] : '';
$z3 = isset($t_mp[3]) ? $t_mp[3] : '';

$zrbt = 'index, follow';

// Check if this is a subdomain and set noindex for subdomains
$current_host = $_SERVER['HTTP_HOST'] ?? '';
if (!empty($current_host) && strpos($current_host, '.sauto.md') !== false && $current_host !== 'sauto.md' && $current_host !== 'www.sauto.md') {
    $zrbt = 'noindex, nofollow';
}

$r['ttl']='';$r['h1']='';$r['dsc']='';$r['kwd']='';

$sa['meta']['ttl'] = 'Vînzarea autoturismelor și utilitarelor.';
$current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
$sa['meta']['h1'] = isset($page_titles[$current_lang]['insurance']) ? $page_titles[$current_lang]['insurance'] : $page_titles['ro']['insurance'];
$sa['meta']['dsc'] = 'Sauto - Vânzare de mașini, pagina auto, vă oferim o gamă largă de mașini.';
$sa['meta']['kwd'] = 'sauto, md, Renault Megane, Renault Kadjar, Renault Clio, Nissan Qashqai, Nissan Juke, Nissan X Trail, Ford Focus, Opel Astra, Ford Transit, Ford Fiesta, Ford Fusion, VW Passat, VW Golf';

$sa['it']['img'] = 'https://www.sauto.md/media/images/site/sauto_new_logo_black.png';
	
//SELECT p.pid, p.cid, p.pname, c1.name1, c2.name2 FROM product p LEFT JOIN customer1 c1 ON p.cid = c1.cid
//'SELECT * FROM '.$prefx.'_car_ctlg c LEFT JOIN '.$prefx.'_car_pht p WHERE `id`=:id'

if ( in_array($z2, $url_arr) ){//if t_mp[2] is allowed part of url
	$exist = 0;
	$z2=$z2==''?'home':$z2;
	
	//------------------------------------------------------------------------------- CTLG ROWS TAKE
	if( in_array($z2, array('cars', 'tyres')) && is_numeric($z3) && !isset($q_mp[1]) ){//if t_mp[2] has an item info, t_mp[3] is number
		$zl='';
		$p_type = $z2=='cars' ? 'car' : $z2;
		
		if ($z2=='cars'){$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id'); $pdo2 = $db->prepare('SELECT `name`, `main` FROM '.$prefx.'_car_pht WHERE `id`=:id AND `main`="1"'); $zl='car';}
		elseif ($z2=='tyres'){$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg WHERE `id`=:id'); $pdo2 = $db->prepare('SELECT `name`, `main` FROM '.$prefx.'_tyre_pht WHERE `it_id`=:id AND `main`="1"'); $zl='tyre';}
		
		$pdo->execute( array('id'=>$z3) );
		foreach ($pdo as $r){ foreach ($r as $k => $v){ $sa['it']['r'][$k] = $v; } $exist = 1; }
		
		$zrbt = ( $z2=='cars' && (isset($r['n_a'])&&($r['n_a']=='1' || $r['act']=='0')) ) ? 'noindex, follow' : $zrbt;
		
		if ($exist==1){
			$pdo2->execute(array('id'=>$r['id']));
			foreach ($pdo2 as $r2){ $sa['it']['img']='https://www.sauto.md/media/images/upload/'.$p_type.'/'.$sa['it']['r']['p_path'].'/'.$sa['it']['r']['id'].'/med/'.$r2['name'].'.jpg'; }
			unset($pdo2, $r2);
		}
	}
	//------------------------------------------------------------------------------- META START
	if( $z3=='' && !isset($q_mp[1]) ){//z2 any, no z3, no qr
		$pdo = $db->prepare('SELECT `ttl`, `h1`, `dsc`, `kwd` FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `lng`=:lng AND `tp`="main" AND `p1`=:p1 ');
		$pdo->execute(array( 'lng'=>$_COOKIE['lang'], 'p1'=>$z2 ));
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
		$pdo = $db->prepare('SELECT `ttl`, `h1`, `dsc`, `kwd` FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `lng`=:lng AND `tp`="item" AND `p1`=:p1 AND `p2`=:p2 ');
		$pdo->execute(array( 'lng'=>$_COOKIE['lang'], 'p1'=>$z2, 'p2'=>$z3 ));
		$ir=0;
		foreach ($pdo as $r){$ir=1;}
		if ($ir==1){
			if ($z2 == 'cars' && $exist==1){
				$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].' '.$lng['t']['seo']['car_inf_ttl'];
				$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].', id-'.$sa['it']['r']['id'];
				$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : $lng['w']['used_car'].' '.$sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].' [id-'.$sa['it']['r']['id'].'] '.$lng['t']['seo']['car_inf_dsc'];
				$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] : mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['auto'].','.$lng['w']['buy'].','.$sa['it']['r']['br'].','.$sa['it']['r']['mo'].','.$lng['l'][$zl]['bt'][$sa['it']['r']['bt']].','.$lng['l'][$zl]['tra'][$sa['it']['r']['tra']].','.$lng['l'][$zl]['fl'][$sa['it']['r']['fl']].','.$lng['l'][$zl]['clr'][$sa['it']['r']['clr']].',id'.$sa['it']['r']['id'], "UTF-8");
			}elseif ($z2 == 'tyres' && $exist==1){
				$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : ( $sa['it']['r']['br'].' | '.$sa['it']['r']['w'].'/'.$sa['it']['r']['h'].' R'.$sa['it']['r']['d'].' | '.$lng['l']['tyre']['ss'][$sa['it']['r']['ss']].' | '.$sa['it']['r']['prc'].' '.$sa['it']['r']['cur'] );
				$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : ( $sa['it']['r']['br'].' '.$sa['it']['r']['w'].'/'.$sa['it']['r']['h'].' R'.$sa['it']['r']['d'].', id-'.$sa['it']['r']['id'] );
				$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : ( $lng['w']['sale'].' '.(mb_strtolower($lng['w']['tyres'], "UTF-8")).' '.$sa['it']['r']['br'].' '.$sa['it']['r']['w'].'/'.$sa['it']['r']['h'].' R'.$sa['it']['r']['d'].' [ID-'.$sa['it']['r']['id'].'] '.$lng['u']['for'].' '.$sa['it']['r']['prc'].$sa['it']['r']['cur'].' '.$lng['u']['in'].' '.$lng['w']['chisinau'].'/'.$lng['w']['moldova'].'(MD)' );
				$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] : mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['tyres'].','.$lng['w']['buy'].','.$sa['it']['r']['br'].','.$sa['it']['r']['w'].','.$sa['it']['r']['h'].',r'.$sa['it']['r']['d'].','.$lng['l']['tyre']['ss'][$sa['it']['r']['ss']].',id'.$sa['it']['r']['id'], "UTF-8");
			}elseif ($z2 == 'services'){
				$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : $sa['meta']['ttl'];
				$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : $sa['meta']['h1'];
				$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : $sa['meta']['dsc'];
				$sa['meta']['kwd'] = ($r['kwd']!='') ? $r['kwd'] : $sa['meta']['kwd'];
			}
		}
	}elseif ( in_array($z2, array('cars', 'tyres')) && $z3=='' && isset($q_mp[1]) ){//z2 cars/tyres, no z3, qr isset
		$p_type = $z2=='cars' ? 'car' : $z2;
		$p_type = $z2=='tyres' ? 'tyre' : $p_type;
		$zqr = str_replace('tg=fltr&', '', $q_mp[1]);
		$zttl='';$zh1='';$zdsc='';$zkwd='';
		$pdo = $db->prepare('SELECT `ttl`, `h1`, `dsc`, `kwd` FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `lng`=:lng AND `tp`="fltr" AND `p1`=:p1 AND `qr`=:qr ');
		$pdo->execute(array( 'lng'=>$_COOKIE['lang'], 'p1'=>$z2, 'qr'=>$zqr ));
		$ir=0;
		foreach ($pdo as $r){$ir=1;}
		
		$i=0;
		foreach ($_GET as $k => $v){
			if($k=='tg'){continue;}
			$v = explode('-', $v);
			
			$zttl .= (isset($lng['l'][$p_type]['spec'][$k])) ? ' | '.$lng['l'][$p_type]['spec'][$k].': ' : $k;
			$zh1 .= ($i==0) ? ' '.$lng['u']['where'] : ' | '; $zh1 .= (isset($lng['l'][$p_type]['spec'][$k])) ? mb_strtolower(' '.$lng['l'][$p_type]['spec'][$k].': ', "UTF-8") : mb_strtolower(' '.$k.': ', "UTF-8");
			$zdsc .= ($i==0) ? ' '.$lng['u']['where'] : ' '.$lng['u']['and']; $zdsc .= (isset($lng['l'][$p_type]['spec'][$k])) ? ' '.$lng['l'][$p_type]['spec'][$k].':' : ' '.$k.':';
			$zkwd .= (isset($lng['l'][$p_type]['spec'][$k])) ? ','.$lng['l'][$p_type]['spec'][$k] : ','.$k;
			$i2=0;
			foreach ($v as $it){
				$zttl .= ($i2==0) ? '' : ', '; $zttl .= (isset($lng['l'][$p_type][$k][$it])) ? $lng['l'][$p_type][$k][$it] : $it;
				$zh1 .= ($i2==0) ? '' : ', '; $zh1 .= (isset($lng['l'][$p_type][$k][$it])) ? mb_strtolower($lng['l'][$p_type][$k][$it], "UTF-8") : mb_strtolower($it, "UTF-8");
				$zdsc .= ($i2==0) ? '' : ','; $zdsc .= (isset($lng['l'][$p_type][$k][$it])) ? $lng['l'][$p_type][$k][$it] : $it;
				$zkwd .= (isset($lng['l'][$p_type][$k][$it])) ? ','.$lng['l'][$p_type][$k][$it] : ','.$it;
				$i2++;
				if($i2>1){$zrbt = 'noindex, follow';}
			}
			$i++;
		}
		if ($ir==1){
			if ($z2 == 'cars'){
				$sa['meta']['ttl'] = ($r['ttl']!='') ? $r['ttl'] : $lng['w']['search'].' | '.$lng['w']['vehicles'].$zttl.' | Sauto Haus';
				$sa['meta']['h1'] = ($r['h1']!='') ? $r['h1'] : 'Sauto Haus. '.$lng['w']['vehicles'].$zh1;
				$sa['meta']['dsc'] = ($r['dsc']!='') ? $r['dsc'] : $lng['w']['ctlg'].$zdsc.'. '.$lng['t']['seo']['dsc1'];
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

//--------------------------------ECHO_META, TITLE, OG
echo '
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<meta name="robots" content="'.$zrbt.'" />
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" id="mobile_viewport" />

<meta property="og:title" content="'.$sa['meta']['ttl'].'">
<meta property="og:description" content="'.$sa['meta']['dsc'].'">
<meta property="og:type" content="website">
<meta property="og:url" content="https://www.sauto.md'.$mp.'">
<meta property="og:image" content="'.$sa['it']['img'].'">
<meta property="og:site_name" content="Sauto.md">

<meta name="description" content="'.$sa['meta']['dsc'].'" />
<meta name="keywords" content="'.$sa['meta']['kwd'].'" />
<title>'.$sa['meta']['ttl'].'</title>';

unset($zrbt);
?>