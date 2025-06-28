<?php defined( '_DOIT' ) or die( 'Restricted access' );

$content = '';
$qr=''; $tp='x'; $mix=0;
//--------------------INSERT SEO
//$z_page = explode('?', $_POST['page']);
$z_pg = str_ireplace('https://www.sauto.md/','', $_POST['page']);
$z_pg2 = explode('?', $z_pg);

if (isset($z_pg2[1])){
	$qr = str_ireplace('tg=fltr&','',$z_pg2[1]);
	$tp = 'fltr';
	if (strpos($z_pg2[1], '[:') !== false){ $mix = 1; }
}

$z_pg3 = explode('/', $z_pg2[0]);
$p1 = (isset($z_pg3[1])) ? $z_pg3[1] : 0;
$p2 = (isset($z_pg3[2])) ? $z_pg3[2] : 0;

if ($p1=='cars'||$p1=='tyres'){ $p2 = (int)$p2; }

if ( $p1!='0' && $p2!='0' && $qr=='' ){	$tp = 'item'; }
if ( $p1!='0' && $p2=='0' && $qr=='' ){	$tp = 'main'; }

$i=0;
$pdo = $db->prepare('SELECT * FROM '.$prefx.'_seo2 WHERE `tp`=:tp AND `p1`=:p1 AND `it_id`=:it_id ');
$pdo->execute(array( 'tp'=>$tp, 'p1'=>$p1, 'it_id'=>$p2 ));
foreach($pdo as $r){$i++;}

if ($i>0){
	echo $max_id;
	
	foreach($lang_arr as $val){
		$pdo = $db->prepare('UPDATE '.$prefx.'_seo2 
		SET `ttl`=:ttl, `h1`=:h1, `dsc`=:dsc, `txt`=:txt 
		WHERE `lng`=:lng AND `tp`=:tp AND `p1`=:p1 AND `it_id`=:it_id ');
		$pdo->execute(array(
			'lng'=>$val,
			'tp'=>$tp,
			'p1'=>$p1,
			'it_id'=>$p2,
			'ttl'=>$_POST['new_title_'.$val],
			'h1'=>$_POST['new_h1_'.$val],
			'dsc'=>$_POST['new_meta_desc_'.$val],
			'txt'=>$_POST['new_txt_'.$val]
		));
	}
}else{
	
	$pdo = $db->prepare('SELECT `value` FROM '.$prefx.'_info WHERE `name`="seo_it_id" AND `x1`=:tp AND `x2`=:p1 '); $pdo->execute(array('tp'=>$tp, 'p1'=>$p1));
	foreach($pdo as $r){$it_id = $r['value']*1; $it_id++;}
	
	$pdo = $db->prepare('UPDATE '.$prefx.'_info SET `value`=:value WHERE `name`="seo_it_id" AND `x1`=:tp AND `x2`=:p1');
	$pdo->execute(array( 'tp'=>$tp, 'p1'=>$p1, 'value'=>$it_id ));
	
	foreach($lang_arr as $val){
		$pdo = $db->prepare('INSERT INTO '.$prefx.'_seo2 (`lng`, `tp`, `p1`, `p2`, `qr`, `it_id`, `ttl`, `h1`, `dsc`, `txt`, `mix`) 
		VALUES (:lng, :tp, :p1, :p2, :qr, :it_id, :ttl, :h1, :dsc, :txt, :mix)');
		$pdo->execute(array(
		'lng'=>$val, 'tp'=>$tp, 'p1'=>$p1, 'p2'=>$p2, 'qr'=>$qr, 'it_id'=>$it_id, 'ttl'=>$_POST['new_title_'.$val], 'h1'=>$_POST['new_h1_'.$val], 'dsc'=>$_POST['new_meta_desc_'.$val], 'txt'=>$_POST['new_txt_'.$val], 'mix'=>$mix
		));
	}
}

?>