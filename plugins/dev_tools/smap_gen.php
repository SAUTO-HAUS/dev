<?php defined( '_DOIT' ) or die( 'Restricted access' );

$sm_arr = array(
	'd10' => array('c'=>'daily', 'p'=>'1.0'),
	'd9' => array('c'=>'daily', 'p'=>'0.9'),
	'd8' => array('c'=>'daily', 'p'=>'0.8'),
	'd7' => array('c'=>'daily', 'p'=>'0.7'),
	'm7' => array('c'=>'monthly', 'p'=>'0.7'),
	'm6' => array('c'=>'monthly', 'p'=>'0.6'),
	'm5' => array('c'=>'monthly', 'p'=>'0.5'),
	'n0' => array('c'=>'never', 'p'=>'0.0')
);

$it = array();

$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg'); $pdo->execute(); $i=0;
foreach ($pdo as $r){
	$it['cars'][ $r['act'] ][$i]['id'] = $r['id'];
	$it['cars'][ $r['act'] ][$i]['name'] = $r['br_nm'].' '.$r['mo_nm'].', '.$r['id'];
	/*foreach($r as $k => $v){
		$it['cars']['list'][$k] = $v;
	}*/
	$i++;
}

$pdo = $db->prepare('SELECT `id`, `act`, `br`, `w`, `h`, `d` FROM '.$prefx.'_tyre_ctlg'); $pdo->execute(); $i=0;
foreach ($pdo as $r){ $it['tyres'][ $r['act'] ][$i]['id'] = $r['id']; $it['tyres'][ $r['act'] ][$i]['name'] = $r['br'].' '.$r['w'].'/'.$r['h'].' R'.$r['d'].', '.$r['id']; $i++; }

function sm_url($l, $c, $p){
	return '<url><loc>https://www.sauto.md/'.$l.'</loc><changefreq>'.$c.'</changefreq><priority>'.$p.'</priority></url>';
}

function html_start($i){
	return '<!DOCTYPE html><html><head>
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
	<meta name="description" content="Sitemap"><meta name="keywords" content="Sitemap"><meta charset="utf-8"><title>Sitemap HTML Page '.$i.'</title><base target="_blank">
	<style type="text/css">h1{width:100%;text-align:center;float:left;} a{width:33%;display:block;margin:1rem 0;padding:.5rem;box-sizing:border-box;text-align:center;float:left; font-family:Verdana; font-size:.9rem; text-decoration:none; transition:.3s; border-right:1px solid #eee; text-transform:capitalize;} a:hover{color:#e2001a;}</style>
	</head><body><h1>Sitemap HTML Page '.$i.'</h1>';
}
$html_end = '</body></html>';

$smap_xml = '';
$smap_html = array(1=>html_start(1));

$smap_xml .= '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

$i = 1; $i2 = 1;

foreach ($lang_arr as $zlang){
	foreach ($seo_url_arr as $gr => $ar){
		if ($gr != 'sub'){
			foreach($ar as $k => $v){
				$loc = ($k=='home') ? '' : $k;
				$smap_xml .= sm_url( $zlang.'/'.$loc, $sm_arr[ $v['sm'] ]['c'], $sm_arr[ $v['sm'] ]['p'] );
				
				if ($i2>999){$i++; $i2=1; $smap_html[$i-1] .= $html_end; $smap_html[$i] = html_start($i);}
				$smap_html[$i] .= "\n".'<a href="'.$zlang.'/'.$loc.'">'.$k.', '.$zlang.'</a>'; $i2++;
				
				if ( isset($v['sub']) && $v['sub'] == 1 ){
					foreach( $seo_url_arr['sub'][$k] as $sk => $sv){
						if ($sk == 'it_act' || $sk == 'it_arh'){
							
							$act = ( isset( $it[$k][1] ) ) ? $it[$k][1] : '';
							$arh = ( isset( $it[$k][0] ) ) ? $it[$k][0] : '';
							$link = ( $sk == 'it_act' ) ? $act : $arh; if ($link==''){break;}
							$sv['sm'] = ( $sk == 'it_act' ) ? 'd10' : 'n0';
							
							foreach ($link as $lk){
								$smap_xml .= sm_url( $zlang.'/'.$k.'/'.$lk['id'], $sm_arr[ $sv['sm'] ]['c'], $sm_arr[ $sv['sm'] ]['p'] );
								
								if ($i2>999){$i++; $i2=1; $smap_html[$i-1] .= $html_end; $smap_html[$i] = html_start($i);}
								$smap_html[$i] .= "\n".'<a href="https://www.sauto.md/'.$zlang.'/'.$k.'/'.$lk['id'].'">'.$lk['name'].', '.$zlang.'</a>'; $i2++;
							}
						}elseif($sk == 'landing'){
							
							//$smap_html[$i] .= ''; $i2++;
						}else{
							$smap_xml .= sm_url( $zlang.'/'.$k.'/'.$sk, $sm_arr[ $sv['sm'] ]['c'], $sm_arr[ $sv['sm'] ]['p'] );
							
							if ($i2>999){$i++; $i2=1; $smap_html[$i-1] .= $html_end; $smap_html[$i] = html_start($i);}
							$smap_html[$i] .= "\n".'<a href="https://www.sauto.md/'.$zlang.'/'.$k.'/'.$sk.'">'.$sk.', '.$zlang.'</a>'; $i2++;
						}
					}
				}
			}
		}
	}
}

$smap_html[$i] .= $html_end;

$smap_xml .= '
</urlset>';

$pf = fopen('sitemap.xml', 'w');
if (!$pf) {
	echo 'Cannot create file!';
	return;
}

fwrite ($pf, $smap_xml);
fclose ($pf);

echo 'File created. Done.';

foreach($smap_html as $k => $v){
	$zsrc = fopen('sitemap'.$k.'.html','w'); fwrite($zsrc, $v); fclose($zsrc);
}
unset($zsrc);

/*
<lastmod>2005-01-01</lastmod>
*/

?>

<script>setTimeout(function (){window.history.go(-1)}, 1000);</script>