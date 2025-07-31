<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Include RBAC system
require_once dirname(__DIR__) . '/include/rbac.php';
require_once dirname(__DIR__) . '/include/rbac_config.php';

$rtrn = '';

// Get current user role from session
$current_user_role = null;
if (isset($_COOKIE['sess']) && !empty($_COOKIE['sess'])) {
    $sess = explode("-", $_COOKIE['sess']);
    $pdo = $db->prepare('SELECT role, type FROM '.$prefx.'_adm_usr WHERE id = :id AND act = "1"');
    $pdo->execute(['id' => $sess[0]]);
    $user_data = $pdo->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
        $current_user_role = $user_data['role'] ?? $user_data['type'];
    }
}

// Check if user has permission to manage users (Gordon or admin role)
$can_manage_users = ($current_user_role === 'gordon' || rbac_has_permission($current_user_role, 'sett', 'write'));

// Process user management actions
if (isset($_POST['user_management_action']) && $can_manage_users) {
    $action = $_POST['user_management_action'];
    $userId = (int)$_POST['user_id'];
    $value = $_POST['user_value'];
    
    try {
        switch($action) {
            case 'name':
                $pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET name = :name WHERE id = :id');
                $pdo->execute(['name' => $value, 'id' => $userId]);
                echo 'success';
                exit;
                
            case 'login':
                $pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET login = :login WHERE id = :id');
                $pdo->execute(['login' => $value, 'id' => $userId]);
                echo 'success';
                exit;
                
            case 'password':
                $hashedPassword = password_hash($value, PASSWORD_DEFAULT);
                $pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET password = :password WHERE id = :id');
                $pdo->execute(['password' => $hashedPassword, 'id' => $userId]);
                echo 'success';
                exit;
                
            case 'active':
                $activeValue = ($value === 'true') ? 1 : 0;
                $pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET act = :act WHERE id = :id');
                $pdo->execute(['act' => $activeValue, 'id' => $userId]);
                echo 'success';
                exit;
                
            case 'delete':
                $pdo = $db->prepare('DELETE FROM '.$prefx.'_adm_usr WHERE id = :id');
                $pdo->execute(['id' => $userId]);
                echo 'success';
                exit;
        }
    } catch (Exception $e) {
        echo 'error: ' . $e->getMessage();
        exit;
    }
}

if ( isset($t_mp[4]) ){
	if ( isset($t_mp[4]) && $t_mp[4]=='adm_usr' ){
		$rtrn .= '
		<style>
			input[type="checkbox"] {accent-color:var(--clr);}
		
			.it {display:flex; flex-flow:row wrap; align-items:center; margin:.5rem 0; transition:margin .3s;}
			
			.it > .hdr {display:flex; flex-flow:row wrap; align-items:center; cursor:pointer;}
			.it > .hdr > .stts {padding:.25rem; float:left; border-radius:50%; margin-right:.2rem;}
			.it > .hdr > .stts.on {background-color:#7ea22f;}
			.it > .hdr > .stts.off {background-color:#aa4c4c;}
			.it > .hdr > .stts.you {background-color:#407ebf;}
			.it > .hdr > .sep {margin:.2rem; font-size:.7rem;}
			
			.it > .hdr > .usr.on {}
			.it > .hdr > .usr.off {color:#aaa;}
			
			.it > .btns_wrp {width:100%; position:relative; pointer-events:none;}
			.it > .btns_wrp > .btns {width:100%; height:3rem; display:flex; flex-flow:row wrap; justify-content:left; align-items:center; position:absolute; opacity:0; transition:.1s; transition-delay:unset;}
			.it > .btns_wrp > .btns > .btn {cursor:pointer; background-color:var(--clr); color:#fff; padding:.3rem .5rem; border-radius:.5rem; margin-right:1rem;}
			
			input[name="it_chk"] {display:none;}
			input[name="it_chk"]:checked + .it {margin:1rem 0 3rem 0;}
			input[name="it_chk"]:checked + .it > .hdr {cursor:auto;}
			input[name="it_chk"]:checked + .it > .hdr > .usr {color:var(--clr);}
			input[name="it_chk"]:checked + .it > .btns_wrp {pointer-events:auto; margin:1rem 0;}
			input[name="it_chk"]:checked + .it > .btns_wrp > .btns {opacity:1; transition:.3s; transition-delay:.2s;}
		</style>
		<script>
			// User management functionality
			document.addEventListener("DOMContentLoaded", function() {
				document.addEventListener("click", function(e) {
					if (e.target.classList.contains("btn")) {
						e.preventDefault();
						var action = e.target.getAttribute("data-nm");
						var userItem = e.target.closest(".it");
						var userId = userItem.getAttribute("data-id");
						var userName = userItem.querySelector("[data-login]").textContent;
						var userLogin = userItem.querySelector("[data-login]").getAttribute("data-login");
						
						handleUserAction(action, userId, userName, userLogin);
					}
				});
			});
			
			function handleUserAction(action, userId, userName, userLogin) {
				switch(action) {
					case "nm":
						var newName = prompt("Introduce\u021bi numele nou:", userName);
						if (newName && newName !== userName) {
							updateUser(userId, "name", newName);
						}
						break;
					case "lgn":
						var newLogin = prompt("Introduce\u021bi login-ul nou:", userLogin);
						if (newLogin && newLogin !== userLogin) {
							updateUser(userId, "login", newLogin);
						}
						break;
					case "pass":
						var newPassword = prompt("Introduce\u021bi parola nou\u0103 pentru " + userName + ":");
						if (newPassword) {
							updateUser(userId, "password", newPassword);
						}
						break;
					case "del":
						if (confirm("Sunte\u021bi sigur c\u0103 dori\u021bi s\u0103 \u0219terge\u021bi utilizatorul \"" + userName + "\"?")) {
							updateUser(userId, "delete", true);
						}
						break;
				}
			}
			
			function updateUser(userId, field, value) {
				var formData = new FormData();
				formData.append("user_management_action", field);
				formData.append("user_id", userId);
				formData.append("user_value", value);
				
				fetch(window.location.href, {
					method: "POST",
					body: formData
				})
				.then(function(response) { return response.text(); })
				.then(function(data) {
					if (data.indexOf("success") !== -1) {
						alert("Actualizare reu\u0219it\u0103!");
						location.reload();
					} else {
						alert("Eroare: " + data);
					}
				})
				.catch(function(error) {
					alert("A ap\u0103rut o eroare la actualizarea utilizatorului.");
				});
			}

		</script>
		';
		
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr ORDER BY `name` ASC'); // GROUP BY `nm`
		$pdo->execute();
		
		foreach ($pdo as $r){
			//if ($r['type'] != 'dev'){
				$rtrn .= '
				<input type="radio" id="it_chk_'.$r['id'].'" name="it_chk">
				<label for="it_chk_'.$r['id'].'" class="it" data-id="'.$r['id'].'">
					<div class="hdr">
						<div class="stts '.( isset($_COOKIE['usr'])&&$_COOKIE['usr']==$r['login']?'you':($r['on']=='0'?'off':'on') ).'"></div>
						<div class="usr '.( $r['act']=='0'?'off':'on' ).'">
							<span data-login="'.$r['login'].'">'.$r['name'].'</span> <span class="sep">|</span> <span>'.$r['login'].'</span>
						</div>
					</div>
					<div class="btns_wrp">
						<div class="btns">
							<div class="btn" data-nm="nm">Edit name</div>
							<div class="btn" data-nm="lgn">Edit login</div>
							<div class="btn" data-nm="pass">New password</div>
							<div class="btn" data-nm="act">Active <input type="checkbox" '.($r['act']==0?'':'checked="checked"').'></div>
							<div class="btn" data-nm="del">Delete user</div>
						</div>
					</div>
				</label>';
			//}
		}
	}
	elseif ( $t_mp[4]=='annc' ){
		if ( isset($t_mp[5]) ){
			$link_back = ''; $count = count($t_mp);
			foreach($t_mp as $v){ if(--$count<=0){break;} $link_back .= $v.'/';}
			$rtrn .= '<a href="'.$link_back.'">Back</a><br/><br/>';
			
			if ( $t_mp[5]=='sunday' ){
				$u_ar = ['Cenușa Loghin2', 'Babalevschi Cătălin1', 'Carp Dumitru', 'Malițov Daniel4', 'Buimestru Daniel3'];
				
				foreach ($u_ar as $u){
					$rtrn .= '<p>'.$u.'</p>';
				}
			}
		}else{
			$rtrn .= '<a href="'.$_SERVER['REQUEST_URI'].'/sunday">Sunday work</a>';
		}
		
	}
}
echo $rtrn;

?>