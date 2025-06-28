<?php defined( '_DOIT' ) or die( 'Restricted access' );

$ajax_folder = _ADM_AJAX.'/tyres';
$photo_folder = _TYRES_IMG;
$rtrn = 'none';

if ( $_POST['fn']=='search'||$_POST['fn']=='more'||$_POST['fn']=='filter' ){
	$arr_types = ['br','mo','w','h','d','c','ss','author','id','vis','act']; $query_args = []; $all_search = []; $search = [];
	require_once( $ajax_folder.'/filter.php' );
	require_once( $ajax_folder.'/catalog.php' );
	$returnIt = [ 'fn'=>$_POST['fn'], 'it_pos'=>$c_id, 'rtrn'=>$rtrn, 'search'=>$search ];
}
//---------------------------------------------ADD NEW
elseif ( $_POST['fn']=='add_new' ){
	require_once($ajax_folder.'/add_new.php');
	$returnIt = [ 'fn'=>$_POST['fn'], 'sub'=>$_POST['sub'], 'rtrn'=>$rtrn ];
}
//---------------------------------------------EDIT
elseif ( $_POST['fn']=='edit' ){
	require_once($ajax_folder.'/edit.php');
	$returnIt = [ 'fn'=>$_POST['fn'], 'sub'=>$_POST['sub'], 'rtrn'=>$rtrn ];
}
//---------------------------------------------NOT AVAILABLE ITEM
elseif ( $_POST['fn']=='av0' ){ $pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `n_a`=1 WHERE `id`=:id'); $pdo->execute([ 'id' => $_POST['id'] ]); }
//---------------------------------------------AVAILABLE ITEM
elseif ( $_POST['fn']=='av1' ){ $pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `n_a`=0 WHERE `id`=:id'); $pdo->execute([ 'id' => $_POST['id'] ]); }
//---------------------------------------------HIDE
elseif ( $_POST['fn']=='hide' ){ $pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `vis`=0 WHERE `id`=:id'); $pdo->execute([ 'id' => $_POST['id'] ]); }
//---------------------------------------------REVEAL
elseif ( $_POST['fn']=='reveal' ){ $pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `vis`=1 WHERE `id`=:id'); $pdo->execute([ 'id' => $_POST['id'] ]); }
//---------------------------------------------DELETE
elseif ( $_POST['fn']=='delete' ){ $pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `act`=0, `del_t`=:del_t WHERE `id`=:id'); $pdo->execute([ 'id' => $_POST['id'], 'del_t' => time()+(60*60*24*30) ]); }//+30 days
//---------------------------------------------RESTORE
elseif ( $_POST['fn']=='restore' ){ $pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `act`=1, `del_t`=0 WHERE `id`=:id'); $pdo->execute([ 'id' => $_POST['id'] ]); }
//---------------------------------------------ERASE
elseif ( $_POST['fn']=='erase' ){
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg WHERE `id`=:id');
	$pdo->execute([ 'id' => $_POST['id'] ]);
	foreach ($pdo as $r){ $it_id = $r['id']; $p_path = $r['p_path']; }
	
	$checker = 0;
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_pht WHERE `it_id`=:it_id');
	$pdo->execute([ 'it_id' => $_POST['id'] ]);
	foreach ($pdo as $p){ $checker = 1; }
	
	if ($checker == 1){
		if ( (isset($it_id)&&$it_id!='') && (isset($p_path)&&$p_path!='') ){
			$dir_name = $photo_folder.'/'.$p_path.'/'.$it_id.'/';
			if ( file_exists($dir_name) ){
				removeIt( $dir_name, true );
			}
		;}
		$pdo = $db->prepare('DELETE FROM '.$prefx.'_tyre_pht WHERE `it_id`=:it_id'); $pdo->execute([ 'it_id' => $_POST['id'] ]);
	;}
	$pdo = $db->prepare('DELETE FROM '.$prefx.'_tyre_ctlg WHERE `id`=:id AND `act`="0" '); $pdo->execute([ 'id' => $_POST['id'] ]);
	$pdo = $db->prepare('DELETE FROM '.$prefx.'_seo2 WHERE `tp`="item" AND `p1`="tyres" AND `it_id`=:it_id '); $pdo->execute([ 'it_id' => $_POST['id'] ]);
	$returnIt = [ 'fn'=>$_POST['fn'], 'id'=>$_POST['photo_id'] ];
}
?>