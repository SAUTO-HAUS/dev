<?php

if ($t_mp[2] == 'car'){
	
	include(__DIR__ . '/lang.php');
	
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_catalog WHERE `id`= :id AND `visible`="1" AND `active`="1"');
	$pdo->execute(array( 'id' => $url_id ));
	
	foreach ($pdo as $row){
		$c_id = $row['id'];
		$c_brand = $row['brand'];
		$c_model = $row['model'];
		$c_brand_name = $row['brand_name'];
		$c_model_name = $row['model_name'];
		$c_year = $row['year'];
		$c_path = $row['photo_path'];
	;}
	
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_photo WHERE `id`= :id AND main=1');
	$pdo->execute(array( 'id' => $url_id ));
	foreach ($pdo as $row){
		$c_photo_name = $row['name'];
	}
	
	echo '
	<meta property="og:title" content="'.$c_brand_name.' '.$c_model_name.' ('. $c_year.') , Sauto.md">
	<meta property="og:image" content="/'._CAR_IMG.'/'.$c_path.'/'.$c_id.'/high/'.$c_photo_name.'.jpg">
	ABCD
	';

}
?>