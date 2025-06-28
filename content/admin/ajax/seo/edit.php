<?php defined( '_DOIT' ) or die( 'Restricted access' );

$content = '';

//-------------------- UPDATE SEO
foreach($lang_arr as $val){
	$pdo = $db->prepare('UPDATE '.$prefx.'_seo2 
	SET `ttl`=:ttl, `h1`=:h1, `dsc`=:dsc, `txt`=:txt 
	WHERE `lng`=:lng AND `tp`=:tp AND `p1`=:p1 AND `it_id`=:it_id ');
	$pdo->execute(array(
		'lng'=>$val,
		'tp'=>$_POST['type'],
		'p1'=>$_POST['p1'],
		'it_id'=>$_POST['it_id'],
		'ttl'=>$_POST['new_title_'.$val],
		'h1'=>$_POST['new_h1_'.$val],
		'dsc'=>$_POST['new_meta_desc_'.$val],
		'txt'=>$_POST['new_txt_'.$val]
	));
}

?>