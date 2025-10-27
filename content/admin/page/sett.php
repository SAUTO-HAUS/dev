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
                
            case 'add_user':
                $name = $_POST['user_name'];
                $login = $_POST['user_login'];
                $password = $_POST['user_password'];
                $role = $_POST['user_role'];
                $branch_id = !empty($_POST['user_branch']) ? (int)$_POST['user_branch'] : null;
                
                $checkPdo = $db->prepare('SELECT COUNT(*) FROM '.$prefx.'_adm_usr WHERE login = :login');
                $checkPdo->execute(['login' => $login]);
                if ($checkPdo->fetchColumn() > 0) {
                    echo 'error: Login already exists';
                    exit;
                }
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $pdo = $db->prepare('INSERT INTO '.$prefx.'_adm_usr (name, login, password, type, role, branch_id, act, created_at) VALUES (:name, :login, :password, :type, :role, :branch_id, 1, NOW())');
                $pdo->execute([
                    'name' => $name,
                    'login' => $login,
                    'password' => $hashedPassword,
                    'type' => $role, 
                    'role' => $role,
                    'branch_id' => $branch_id
                ]);
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
		
			.it {display:flex; flex-flow:row wrap; align-items:center; margin:.3rem 0; padding:.5rem; border:1px solid #ddd; border-radius:4px; transition:all .2s;}
			.it:hover {border-color:var(--clr); background:#f9f9f9;}
			
			.it > .hdr {display:flex; align-items:center; cursor:pointer; flex:1;}
			.it > .hdr > .stts {width:8px; height:8px; border-radius:50%; margin-right:.5rem;}
			.it > .hdr > .stts.on {background-color:#7ea22f;}
			.it > .hdr > .stts.off {background-color:#aa4c4c;}
			.it > .hdr > .stts.you {background-color:#407ebf;}
			.it > .hdr > .sep {margin:0 .4rem; font-size:.75rem; color:#666;}
			
			.it > .hdr > .usr.on {font-size:.9rem;}
			.it > .hdr > .usr.off {color:#999; font-size:.9rem;}
			
			.it > .btns_wrp {width:100%; position:relative; pointer-events:none;}
			.it > .btns_wrp > .btns {width:100%; height:2.5rem; display:flex; align-items:center; position:absolute; opacity:0; transition:.2s; gap:.5rem;}
			.it > .btns_wrp > .btns > .btn {cursor:pointer; background-color:var(--clr); color:#fff; padding:.25rem .5rem; border-radius:3px; font-size:.8rem; border:none; transition:all .2s ease;}
			.it > .btns_wrp > .btns > .btn:hover {background-color:#333; transform:translateY(-1px); box-shadow:0 2px 4px rgba(0,0,0,0.2);}
			
			/* Add User Styles */
			.add-user-container {margin-top:2rem; padding:1rem; border:1px solid #ddd; border-radius:.5rem; background-color:#f9f9f9;}
			.add-user-form {display:none; margin-top:1rem;}
			.add-user-form.show {display:block;}
			.form-row {margin-bottom:1rem;}
			.form-row label {display:block; margin-bottom:.3rem; font-weight:bold;}
			.form-row input, .form-row select {width:100%; max-width:300px; padding:.5rem; border:1px solid #ccc; border-radius:.3rem;}
			.add-user-btn {background-color:var(--clr); color:#fff; padding:.5rem 1rem; border:none; border-radius:.5rem; cursor:pointer; margin-right:.5rem;}
			.add-user-btn:hover {opacity:.8;}
			.cancel-btn {background-color:#666; color:#fff; padding:.5rem 1rem; border:none; border-radius:.5rem; cursor:pointer;}
			.cancel-btn:hover {opacity:.8;}
			
			input[name="it_chk"] {display:none;}
			input[name="it_chk"]:checked + .it {margin:1rem 0 1rem 0;}
			input[name="it_chk"]:checked + .it > .hdr {cursor:auto;}
			input[name="it_chk"]:checked + .it > .hdr > .usr {color:var(--clr);}
			input[name="it_chk"]:checked + .it > .btns_wrp {pointer-events:auto; margin:1rem 0;}
			input[name="it_chk"]:checked + .it > .btns_wrp > .btns {opacity:1; transition:.3s; transition-delay:.2s;}
		</style>
		<script>
			// User management functionality
			document.addEventListener("DOMContentLoaded", function() {
				// Handle button clicks for user management
				document.addEventListener("click", function(e) {
					if (e.target.classList.contains("btn") && e.target.getAttribute("data-nm")) {
						e.preventDefault();
						e.stopPropagation();
						
						var action = e.target.getAttribute("data-nm");
						var userItem = e.target.closest(".it");
						var userId = userItem.getAttribute("data-id");
						var userName = userItem.querySelector("[data-login]").textContent;
						var userLogin = userItem.querySelector("[data-login]").getAttribute("data-login");
						
						handleUserAction(action, userId, userName, userLogin);
					}
				});
				
				// Handle special case for Active checkbox
				document.addEventListener("change", function(e) {
					if (e.target.type === "checkbox" && e.target.closest(".btn[data-nm=\"act\"]")) {
						e.preventDefault();
						e.stopPropagation();
						
						var userItem = e.target.closest(".it");
						var userId = userItem.getAttribute("data-id");
						var isActive = e.target.checked;
						
						updateUser(userId, "active", isActive ? "true" : "false");
					}
				});
			});
			
			function handleUserAction(action, userId, userName, userLogin) {
				switch(action) {
					case "nm":
						var newName = prompt("Введите новое имя:", userName);
						if (newName && newName !== userName) {
							updateUser(userId, "name", newName);
						}
						break;
					case "lgn":
						var newLogin = prompt("Введите новый логин:", userLogin);
						if (newLogin && newLogin !== userLogin) {
							updateUser(userId, "login", newLogin);
						}
						break;
					case "pass":
						var newPassword = prompt("Введите новый пароль для " + userName + ":");
						if (newPassword) {
							updateUser(userId, "password", newPassword);
						}
						break;
					case "del":
						if (confirm("Вы уверены, что хотите удалить пользователя \"" + userName + "\"?")) {
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
						alert("Обновление успешно!");
						location.reload();
					} else {
						alert("Ошибка: " + data);
					}
				})
				.catch(function(error) {
					alert("Произошла ошибка при обновлении пользователя.");
				});
			}

			
			// Add User functionality
			function showAddUserForm() {
				// Force clear all fields first
				document.getElementById("addUserName").value = "";
				document.getElementById("addUserLogin").value = "";
				document.getElementById("addUserPassword").value = "";
				document.getElementById("addUserRole").value = "";
				document.getElementById("addUserBranch").value = "";
				
				// Then show the form
				document.getElementById("addUserForm").classList.add("show");
				document.getElementById("showAddUserBtn").style.display = "none";
				
				// Force focus to first field to trigger any remaining autocomplete clearing
				setTimeout(function() {
					document.getElementById("addUserName").focus();
					document.getElementById("addUserName").value = "";
				}, 100);
			}
			
			function hideAddUserForm() {
				document.getElementById("addUserForm").classList.remove("show");
				document.getElementById("showAddUserBtn").style.display = "inline-block";
				// Clear form
				document.getElementById("addUserName").value = "";
				document.getElementById("addUserLogin").value = "";
				document.getElementById("addUserPassword").value = "";
				document.getElementById("addUserRole").value = "";
				document.getElementById("addUserBranch").value = "";
			}
			
			function submitAddUser() {
				var name = document.getElementById("addUserName").value.trim();
				var login = document.getElementById("addUserLogin").value.trim();
				var password = document.getElementById("addUserPassword").value;
				var role = document.getElementById("addUserRole").value;
				var branch = document.getElementById("addUserBranch").value;
				
				if (!name || !login || !password) {
					alert("Пожалуйста, заполните все обязательные поля (Имя, Логин, Пароль)");
					return;
				}
				
				if (password.length < 6) {
					alert("Пароль должен содержать минимум 6 символов");
					return;
				}
				
				var formData = new FormData();
				formData.append("user_management_action", "add_user");
				formData.append("user_name", name);
				formData.append("user_login", login);
				formData.append("user_password", password);
				formData.append("user_role", role);
				formData.append("user_branch", branch);
				
				fetch(window.location.href, {
					method: "POST",
					body: formData
				})
				.then(function(response) { return response.text(); })
				.then(function(data) {
					if (data.indexOf("success") !== -1) {
						alert("Пользователь успешно добавлен!");
						location.reload();
					} else {
						alert("Ошибка: " + data);
					}
				})
				.catch(function(error) {
					alert("Произошла ошибка при добавлении пользователя.");
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
		
		// Add User interface at the bottom
		if ($can_manage_users) {
			$rtrn .= '
				<div class="add-user-container">
					<button id="showAddUserBtn" class="add-user-btn" onclick="showAddUserForm()">+ Add User</button>
					
					<div id="addUserForm" class="add-user-form">
						<h3>Add New User</h3>
						
						<div class="form-row">
							<label for="addUserName">Name *</label>
							<input type="text" id="addUserName" name="add_user_name" placeholder="Enter full name" autocomplete="off" value="" required>
						</div>
						
						<div class="form-row">
							<label for="addUserLogin">Login *</label>
							<input type="text" id="addUserLogin" name="add_user_login" placeholder="Enter login username" autocomplete="off" value="" required>
						</div>
						
						<div class="form-row">
							<label for="addUserPassword">Password *</label>
							<input type="password" id="addUserPassword" name="add_user_password" placeholder="Enter password (min 6 chars)" autocomplete="new-password" value="" required>
						</div>
						
						<div class="form-row">
							<label for="addUserRole">Role *</label>
							<select id="addUserRole">
								<option value="">Select role...</option>
								<option value="publisher">Publisher</option>
								<option value="publisher_limited">Publisher Limited</option>
								<option value="admin">Admin</option>';
								
			// Only Gordon can create other Gordon users
			if ($current_user_role === 'gordon') {
				$rtrn .= '<option value="gordon">Gordon (Superadmin)</option>';
			}
			
			$rtrn .= '
							</select>
						</div>
						
						<div class="form-row">
							<label for="addUserBranch">Branch (for Publisher Limited)</label>
							<select id="addUserBranch">
								<option value="">Select branch (optional)</option>';
								
			// Get branches for dropdown
			$branchPdo = $db->prepare('SELECT * FROM '.$prefx.'_branches WHERE active = 1 ORDER BY name ASC');
			$branchPdo->execute();
			foreach ($branchPdo as $branch) {
				$rtrn .= '<option value="'.$branch['id'].'">'.$branch['name'].'</option>';
			}
			
			$rtrn .= '
							</select>
						</div>
						
						<div class="form-row">
							<button type="button" class="add-user-btn" onclick="submitAddUser()">Create User</button>
							<button type="button" class="cancel-btn" onclick="hideAddUserForm()">Cancel</button>
						</div>
					</div>
				</div>';
		}
	}
	elseif ( $t_mp[4]=='roles' ){
		// Include roles management page
		include dirname(__FILE__) . '/roles.php';
	}
	elseif ( $t_mp[4]=='phone_config' ){
		// Include phone configuration management page
		include dirname(__FILE__) . '/phone_config.php';
	}
	elseif ( $t_mp[4]=='publication_settings' ){
		// Include publication settings management page
		include $_SERVER['DOCUMENT_ROOT'] . '/App/Services/publication_settings.php';
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