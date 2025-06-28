<?php defined( '_DOIT' ) or die( 'Restricted access' );

$ajax_page_folder = _ADM_AJAX.'/seo';

//---------------------------------------------ADD NEW
if ( $_POST['fn']=='add_new' ){
	
	require_once ($ajax_page_folder.'/add_new.php');
	
	$returnIt = array(
		'fn'=>$_POST['fn'],
		'content'=>$content
	);
	
}
//---------------------------------------------EDIT
elseif ( $_POST['fn']=='edit' ){
	
	require_once ($ajax_page_folder.'/edit.php');
	
	$returnIt = array(
		'fn'=>$_POST['fn'],
		'it_id'=>$_POST['it_id']
	);
	
}
//---------------------------------------------DELETE
elseif ( $_POST['fn']=='delete' ){	
	$pdo = $db->prepare('DELETE FROM '.$prefx.'_seo2 WHERE `it_id`=:it_id AND `tp`=:tp AND `p1`=:p1 ');
	$pdo->execute(array( 'it_id' => $_POST['it_id'], 'tp' => $_POST['type'], 'p1' => $_POST['p1'] ));
	
	$returnIt = array(
		'fn'=>$_POST['fn'],
		'it_id'=>$_POST['it_id']
	);
}

?>