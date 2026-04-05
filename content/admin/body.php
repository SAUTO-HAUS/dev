<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<head>
	<?php include(_ADM.'/head.php'); ?>
</head>
<body class="ffd">
	<?php 
	//alertIt(bin2hex(openssl_random_pseudo_bytes(9)));
	echo '<div id="to_top" title="'.$lang_to_top.'"></div>';
	
	if ( isset($i_counts) && $i_counts==1 ){
		echo '
		<div id="prev_w_pos">'.$lng['w']['prev_pos'].'</div>
		<div id="overlay" class="noselect"><div class="close"></div><div class="bg"></div><div class="content"></div></div>
		<div id="action_menu" class="noselect"></div>';
		include(_ADM_INCL.'/content_box.php');
		
		// Include RBAC system
		require_once(_ADM_INCL.'/rbac.php');
		require_once(_ADM_INCL.'/rbac_config.php');
		$rbac = new RBAC($db, $prefx, $user_id);
		
		echo '
		<div id="main_admin">
			<div id="header">
				<div id="burger_btn" class="burger-btn"><span></span><span></span><span></span></div>
				<div class="info">
					<form method="post" action="" id="adm_out">
						<input type="submit" value="'.$adm_lang['exit'].'" name="adm_out_submit" id="adm_out_submit">
					</form>
					<a class="home" href="'.$site_url.'/'.$_COOKIE['lang'].'" target="_blank">'.$lng['w']['home_page'].'</a>
				</div>
				<div class="lng">';
					foreach($language as $k => $v){ 
						echo '<a href="/'.$k.$lang_mp.'" class="'.$k.' btn '.($t_mp[1]==$k?'act':'').'" title="'.$v.'">'.strtoupper($k).'</a>';
					}
				echo '
				</div>
				<div id="xInf" class="none" data-u="'.$user_name.'"></div>
			</div>
			
			<div id="menu-overlay"></div>
			<div id="menu" class="dev1">';
				// Use RBAC menu system
				$current_menu = rbac_update_admin_menu($user_type, $user_role ?? null);
				
				if (!empty($current_menu)) {
					foreach($current_menu as $k => $ar){
						$menu_name = isset($adm_lang[$k]) ? $adm_lang[$k] : ucfirst($k);
						
						// If module has only one option, make the label a direct link
						if (count($ar) === 1) {
							$single_action = $ar[0];
							$is_active = (isset($t_mp[3]) && $t_mp[3] === $k);
							echo '
							<input id="menu_bx_'.$k.'" type="radio" name="menu_bx" class="radio_inp none" '.($is_active?'checked="checked"':'').' />
							<div class="bx single-item '.($is_active?'active':'').' '.(in_array( $k, $hided_admin_menu, true )?'ghost':'').'">
								<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/'.$k.'/'.$single_action.'" class="nm '.($is_active?'act':'').'">'.$menu_name.'</a>
							</div>';
						} else {
							echo '
							<input id="menu_bx_'.$k.'" type="radio" name="menu_bx" class="radio_inp none" '.( (isset($t_mp[3])&&$t_mp[3]==$k)||(!isset($t_mp[3])&&$k=='sett')?'checked="checked"':'' ).' />
							<div class="bx '.(in_array( $k, $hided_admin_menu, true )?'ghost':'').'">
								<label for="menu_bx_'.$k.'" class="nm">'.$menu_name.'</label>';
								foreach ($ar as $v){
									if ( isset($restrict_admin_menu[$user_id]['page'][$k][$v]) ){continue;}
									
									$menu_name = isset($adm_lang[$k.'_'.$v]) ? $adm_lang[$k.'_'.$v] : (isset($adm_lang[$v]) ? $adm_lang[$v] : ucfirst($v));
									echo '
									<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.($k=='sett'&&$v=='info'?'':'/'.$k.'/'.$v).'" class="'.$k.' '.$v.' btn '.((isset($t_mp[4])&&$t_mp[3]==$k&&$t_mp[4]==$v)||(!isset($t_mp[3])&&$v=='info')?'act':'').'">'; 
										$qu = 0; $qu_x = 0;
										if ($k=='mail'){ 
											$qu = $db->query('SELECT COUNT(*) FROM '.$prefx.'_mail')->fetchColumn();
											if ( in_array($v, ['message', 'order']) ){ $qu_x = $db->query('SELECT COUNT(*) FROM '.$prefx.'_mail WHERE `seen`=0 AND `folder`="'.$v.'"')->fetchColumn(); }
										}
										echo $menu_name.($k=='mail'&&$qu_x>0?' :'.$qu_x:'').($k=='docs'?'':'').'
									</a>';
								}
							echo '
							</div>';
						}
					}
				}
				
				/*$smf = 'sitemap.xml';
				if (file_exists($smf)) {
					//echo '<a href="/'.$_COOKIE['lang'].'/dev_tools/sitemap_generator"><div class="button" title="Actualizați Sitemap.xml">Sitemap ['.date ("d.m.y H:i:s", filemtime($smf)).']</div></a>';
					echo '<a><div class="button" title="Sitemap.xml">Sitemap ['.date ("d.m.y H:i:s", filemtime($smf)).']</div></a>';
				}*/
			echo '
			</div>
			
			<div id="content">';
				// Check access using RBAC system
			$has_access = false;
			
			// Gordon (superadmin) always has full access
			if (isset($user_role) && $user_role === 'gordon') {
				$has_access = true;
			} elseif (isset($user_role) && in_array($user_role, ['admin', 'publisher', 'publisher_limited']) && isset($t_mp[3]) && in_array($t_mp[3], ['docs', 'cars', 'ordercars', 'tyres', 'sett', 'calculator'])) {
				// Direct access for admin and publisher roles to their permitted modules
				// Check appropriate permission based on action
				$required_permission = 'read';
				if (isset($t_mp[4]) && in_array($t_mp[4], ['add', 'create', 'detail'])) {
					$required_permission = 'create';
				} elseif (isset($t_mp[4]) && in_array($t_mp[4], ['edit', 'update'])) {
					$required_permission = 'update';
				} elseif (isset($t_mp[4]) && in_array($t_mp[4], ['delete'])) {
					$required_permission = 'delete';
				}
				$has_access = rbac_has_permission($user_role, $t_mp[3], $required_permission);
			} elseif (isset($t_mp[3])) {
				// Check if user has access to the module
				$has_access = isset($current_menu[$t_mp[3]]);
				
				// For specific actions, check internal actions array
				if ($has_access && isset($t_mp[4])) {
					global $rbac_internal_actions;
					if (isset($rbac_internal_actions[$user_role][$t_mp[3]])) {
						$has_access = in_array($t_mp[4], $rbac_internal_actions[$user_role][$t_mp[3]]);
					} else {
						// Fallback to menu check for backward compatibility
						$has_access = in_array($t_mp[4], $current_menu[$t_mp[3]]);
					}
				}
				
				// Additional RBAC permission check
				if ($has_access && isset($user_role)) {
					$has_access = rbac_has_permission($user_role, $t_mp[3], 'read');
				}
			} else {
				$has_access = true; // Allow home page
			}
				
				if ($has_access) {
					if ( isset($t_mp[3]) && isset($current_menu[$t_mp[3]]) ){
						if ( in_array($t_mp[3], ['cars','ordercars','tyres']) ){require_once(_ADM_INCL.'/filter.php');}
						if ( file_exists(_ADM.'/page/'.$t_mp[3].'.php') ){
							include(_ADM.'/page/'.$t_mp[3].'.php');
						} else {echo 'File not found';}
					} else {include(_ADM.'/page/home.php');}
				}else{echo 'Restricted access.';}
				echo '
			</div>
		</div>
		
		<div id="stts_bar">
			<div class="mcr_ln"></div>
			<div class="ln"></div>
			<div class="txt">
				<span class="passed">*</span>
				<span class="el">*</span>
			</div>
		</div>';
	} else {
		echo '
		<div id="main_admin">
			<form method="post" action="" id="adm_in">
				<input type="text" name="login" value="" placeholder="Login" class="input_field"/>
				<input type="password" name="password" value="" placeholder="Password" class="input_field"/>
				<input type="hidden" name="pass" value="" class="input_field"/>
				<input type="submit" value="Enter" name="adm_in_submit" id="adm_in_submit">
			</form>
		</div>';
	} ?>
</body>