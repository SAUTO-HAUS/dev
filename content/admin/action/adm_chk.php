<?php defined( '_DOIT' ) or die( 'Restricted access' );

$i_counts=0;
if ( isset($_COOKIE['sess'])&&!empty($_COOKIE['sess']) ){
	$sess = explode("-", $_COOKIE['sess'] );
	
	$ip_check_exception = ($sess[0] == 27);
	
	if ($ip_check_exception) {
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr WHERE id = :id AND `act`="1" AND `cookie`=:cookie AND `sess_e`>:time_now');
		$pdo->execute(array(
			'id' => $sess[0],
			'cookie' => $_COOKIE['sess'],
			'time_now' => time()
		));
	} else {
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr WHERE id = :id AND `act`="1" AND `cookie`=:cookie AND `this_ip`=:this_ip AND `sess_e`>:time_now');
		$pdo->execute(array(
			'id' => $sess[0],
			'cookie' => $_COOKIE['sess'],
			'this_ip' => myIp(),
			'time_now' => time()
		));
	}
	
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
		
		// Update cookie only if more than 5 minutes have passed since last update
		$last_update = $r['sess_e'] - (60 * $r['sess_t']);
		$time_since_update = time() - $last_update;
		
		if ($time_since_update > 300) { // 5 minutes
			$hash = md5( time() );
			$cookie_val = $r['id'].'-'.$hash;
			
			$time = time()+(60*60*24*365);//1 year
			$sess_time = time()+(60*$r['sess_t']);
			
			$current_domain = $_SERVER['HTTP_HOST'];
			setcookie('sess', $cookie_val, $time, '/', $current_domain); 
			$_COOKIE['sess'] = $cookie_val;
			
			$pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET `sess_e`=:sess_e, `cookie`=:cookie WHERE id=:id');
			$pdo->execute(array(
				'sess_e' => $sess_time,
				'cookie'=> $cookie_val,
				'id' => $r['id']
			));
			
			__log("Admin session updated for user: " . $r['login']);
		} else {
			// Just extend session time without updating cookie
			$sess_time = time()+(60*$r['sess_t']);
			$pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET `sess_e`=:sess_e WHERE id=:id');
			$pdo->execute(array(
				'sess_e' => $sess_time,
				'id' => $r['id']
			));
		}
		
		$sess_dur = $r['sess_t'];
		$sess_end = $r['sess_e'];
	}
}
?>