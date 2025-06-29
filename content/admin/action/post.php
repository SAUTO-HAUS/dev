<?php defined( '_DOIT' ) or die( 'Restricted access' );

if ( isset( $_POST['adm_out_submit'] ) ){//___________________________________________________________________________________________ADM OUT
	setcookie('sess', null, (time()-1), '/', '.'.$domain_name); unset($_COOKIE['sess']);	
} elseif ( isset( $_POST['adm_in_submit'] ) ){//___________________________________________________________________________________________PSWD check
	//if ( !isset($_SESSION['adm_try']) ){ $_SESSION['adm_try']=1; }
	//if($_SESSION['adm_try']>=5){ alertIt('Too many tries, you are banned for a while.'); }
	
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr WHERE `login`=:login AND `act`="1"');
	$pdo->execute(['login' => strtolower($_POST['login'])]);
	//if ( $pdo->rowCount() == 0 ){ $_SESSION['adm_try']++; }
	
	foreach ($pdo as $r){
		//$hash = password_hash($_POST['ssap_mda'], PASSWORD_DEFAULT); Создает хэш пароля при регистрации
		//if (password_needs_rehash($r['password'], PASSWORD_DEFAULT, ['cost' => 12])) { $hash = password_hash($r['password'], PASSWORD_DEFAULT, ['cost' => 12]); }
			
		if (password_verify($_POST['password'], $r['password'])) {
   			$hash = md5( time() );
			$cookie_val = $r['id'].'-'.$hash;
			$time = time()+(60*60*24*365);//1 year
			$sess_time = time()+(60*$r['sess_t']);
			
			setcookie('sess', $cookie_val, [
				'expires'=>$time, 'path'=>'/', 'domain'=>'.'.$domain_name,
				'secure'=>true, 'httponly'=>true, 'samesite'=>'Strict'
			]);
            $_COOKIE['sess'] = $cookie_val;
			
			setcookie('usr', $r['login'], [
				'expires'=>$time, 'path'=>'/', 'domain'=>'.'.$domain_name,
				'secure'=>true, 'httponly'=>true, 'samesite'=>'Strict'
			]);
            $_COOKIE['usr'] = $r['login'];
			
			setcookie('usr_id', $r['id'], [
				'expires'=>$time, 'path'=>'/', 'domain'=>'.'.$domain_name,
				'secure'=>true, 'httponly'=>true, 'samesite'=>'Strict'
			]);
            $_COOKIE['usr_id'] = $r['id'];
			
			$pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET `last_ip`=:last_ip, `this_ip`=:this_ip, `last_entrance`=:last_entrance, `this_entrance`=:this_entrance, `sess_e`=:sess_e, `cookie`=:cookie WHERE id=:id');
			$pdo->execute([
				'last_ip' => $r['this_ip'], 'this_ip' => myIp(),
				'last_entrance' => $r['this_entrance'], 'this_entrance' => time(),
				'sess_e' => $sess_time, 'cookie'=> $cookie_val, 'id' => $r['id']
			]);
			
			$user_type = $r['type'];
			$user_name = $r['name'];
		}
		else {
			//$_SESSION['adm_try']++;
		}
	}
}