<?php defined( '_DOIT' ) or die( 'Restricted access' );

$i_counts=0;
if ( isset($_COOKIE['sess'])&&!empty($_COOKIE['sess']) ){
	$sess = explode("-", $_COOKIE['sess'] );

	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr WHERE id = :id AND `act`="1" AND `cookie`=:cookie AND `sess_e`>:time_now');
	$pdo->execute(array(
		'id' => $sess[0],
		'cookie' => $_COOKIE['sess'],
		'time_now' => time()
	));
	
	foreach ($pdo as $r){
		$i_counts++;
		$user_id = $r['id'];
		$user_type = $r['type'];
		$user_role = $r['role'] ?? $r['type']; // New RBAC role field
		$user_branch_id = $r['branch_id'];
		$user_name = $r['name'];
		$user_login = $r['login'];
		$user_active = $r['act'];
		
		// Store user info in session for RBAC
		$_SESSION['user_id'] = $user_id;
		$_SESSION['user_role'] = $user_role;
		$_SESSION['user_branch_id'] = $user_branch_id;
		$_SESSION['user_name'] = $user_name;
		
		$sess_minutes = max((int)$r['sess_t'], 525600); 
		$sess_time = time()+(60*$sess_minutes);
		$pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET `sess_e`=:sess_e WHERE id=:id');
		$pdo->execute(array(
			'sess_e' => $sess_time,
			'id' => $r['id']
		));
		
		$sess_dur = $r['sess_t'];
		$sess_end = $r['sess_e'];
	}
}
?>