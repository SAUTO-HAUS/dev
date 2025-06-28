<?php defined( '_DOIT' ) or die( 'Restricted access' );

$ajax_page_folder = _ADM_AJAX.'/mail';
//$photo_folder = _CAR_IMG;

//---------------------------------------------SEEN
if ( $_POST['fn']=='seen' ){
	$pdo = $db->prepare('UPDATE '.$prefx.'_mail SET `seen`=1 WHERE `id`=:id');
	$pdo->execute(array( 'id' => $_POST['id'] ));
		
	$returnIt = array(
		'fn'=>$_POST['fn'],
		'id'=>$_POST['id']
	);
}

//---------------------------------------------DELETE
elseif ( $_POST['fn']=='delete' ){
	foreach ($_POST['id'] as $key){
		$pdo = $db->prepare('DELETE FROM '.$prefx.'_mail WHERE `id`=:id ');
		$pdo->execute(array( 'id' => $key ));
	}
	
	$returnIt = array(
		'fn'=>$_POST['fn']
	);
}

//---------------------------------------------CHANGE FOLDER (MESSAGE/ORDER)
elseif ( $_POST['fn']=='folder' ){
	foreach ($_POST['id'] as $key){
		$pdo = $db->prepare('UPDATE '.$prefx.'_mail SET `folder`=:folder_name WHERE `id`=:id');
		$pdo->execute(array( 
			'id' => $key,
			'folder_name' => $_POST['folder_name']
		));
	}
		
	$returnIt = array(
		'fn'=>$_POST['fn']
	);
}

//---------------------------------------------CHANGE STATE (FAVORITE/ARCHIVE)
elseif ( $_POST['fn']=='state' ){
	if ( in_array( $_POST['state_name'], array('favorites', 'archive') ) ){
		
		$sql  = 'UPDATE '.$prefx.'_mail SET ';
		$sql .= ' `'.$_POST['state_name'].'`=1 ';
		$sql .= ' WHERE `id`=:id';
		
		foreach ($_POST['id'] as $key){
			$pdo = $db->prepare($sql);
			$pdo->execute(array( 'id' => $key ));
		}
		
		$returnIt = array(
			'fn'=>$_POST['fn']
		);
	}
}


?>