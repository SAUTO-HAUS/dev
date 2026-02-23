<?php 
use LaLit\Array2XML;

include ($_SERVER["DOCUMENT_ROOT"].'/plugins/lalit/Constants.php');
include ($_SERVER["DOCUMENT_ROOT"].'/plugins/lalit/InitTrait.php');
include ($_SERVER["DOCUMENT_ROOT"].'/plugins/lalit/Array2XML.php');

$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE `n_a`=0 AND `vis`=1 AND `act`=1 AND (`is_at_client`=0 OR `is_at_client` IS NULL) AND `catalog_type`=\'in_stock\' AND `loc`=\'1\' ';
$pdo = $db->prepare($sql);
$pdo->execute();
$i=0; $ar=[];
foreach ($pdo as $r){
	$pdo = $db->prepare('SELECT `name`, `main` FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id LIMIT 20'); 
	$pdo->execute(array('it_id'=>$r['id']));
	$imgs=[];
	foreach($pdo as $im){
		if ($im['main']===1){ $imgs['m'][] = ['@value'=>'https://www.sauto.md/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$im['name'].'.jpg']; }
		else{ $imgs['x'][] = ['@value'=>'https://www.sauto.md/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$im['name'].'.jpg']; }
	}
	
	$ar[$i]['g:id'] = 'c'.$r['id'];
	$ar[$i]['g:title'] = $r['yr'].' '.$r['br_nm'].' '.$r['mo_nm'].' (id-'.$r['id'].')';
	$ar[$i]['g:description'] = $r['yr'].' '.$lng['l']['car']['clr'][$r['clr']].' '.$r['br_nm'].' '.$r['mo_nm'].' ['.$lng['l']['car']['fl'][$r['fl']].' '.$lng['l']['car']['tra'][$r['tra']].' '.$lng['l']['car']['wd'][$r['wd']].'] for '.$r['prc'].' '.$r['cur'];
	$ar[$i]['g:link'] = 'https://www.sauto.md/ro/cars/'.$r['id'];
	$ar[$i]['g:brand'] = $r['br_nm'];
	$ar[$i]['g:image_link'] = $imgs['m'];
	if (isset($imgs['x'][0])){$ar[$i]['g:additional_image_link'] = $imgs['x'];}
	$ar[$i]['g:product_type'] = 'Cars > '.$r['br_nm'].' > '.$r['mo_nm']; //&gt;
	$ar[$i]['g:condition'] = 'used';
	$ar[$i]['g:availability'] = 'in_stock';
	$ar[$i]['g:color'] = $lng['l']['car']['clr'][$r['clr']];
	$ar[$i]['g:price'] = $r['prc'].' '.$r['cur'];
	
	$ar[$i]['g:product_detail'] = [
		[ 'g:section_name'=>'Info','g:attribute_name'=>'Year','g:attribute_value'=>$r['yr'] ],
		[ 'g:section_name'=>'Info','g:attribute_name'=>'Body style','g:attribute_value'=>$lng['l']['car']['bt'][$r['bt']] ],
		[ 'g:section_name'=>'Info','g:attribute_name'=>'Fuel','g:attribute_value'=>$lng['l']['car']['fl'][$r['fl']] ],
		[ 'g:section_name'=>'Info','g:attribute_name'=>'Transmission','g:attribute_value'=>$lng['l']['car']['tra'][$r['tra']] ],
		[ 'g:section_name'=>'Info','g:attribute_name'=>'Drivetrain','g:attribute_value'=>$lng['l']['car']['wd'][$r['wd']] ],
		[ 'g:section_name'=>'Info','g:attribute_name'=>'Mileage','g:attribute_value'=>$r['mlg'].' '.$r['unit'] ]
	];
	
	$ar[$i]['g:google_product_category'] = '916';
	$ar[$i]['g:vehicle_fulfillment'] = 'in_store';
	$ar[$i]['g:model'] = $r['mo_nm'];
	$ar[$i]['g:year'] = $r['yr'];
	$ar[$i]['g:mileage'] = $r['mlg'].' '.(strtoupper($r['unit']));
	$ar[$i]['g:body_style'] = $lng['l']['car']['bt'][$r['bt']];
	$ar[$i]['g:engine'] = $lng['l']['car']['fl'][$r['fl']];
	
	$i++;
}

$rss=['@attributes'=>['xmlns:g'=>'http://base.google.com/ns/1.0','version'=>'2.0']];
$rss['channel'] = ['title'=>'Sauto, cars feed', 'link'=>[ '@attributes'=>['rel'=>'self', 'href'=>'https://www.sauto.md/ro/cars'] ]];
$rss['channel']['item'] = $ar;

$xml = Array2XML::createXML('rss', $rss);
//echo $xml->saveXML();
$xml->save('api/data_feed/df_cars.xml');
?>