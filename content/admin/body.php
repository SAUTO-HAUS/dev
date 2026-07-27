<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Embed mode: serve only the page content, no admin layout
if (!empty($_GET['embed']) && isset($i_counts) && $i_counts == 1) {
    require_once(_ADM_INCL.'/rbac.php');
    require_once(_ADM_INCL.'/rbac_config.php');
    $rbac = new RBAC($db, $prefx, $user_id);
    require_once(_ADM_INCL.'/crm/crm_core.php');
    echo '<!DOCTYPE html><html lang="'.($_COOKIE['lang']??'ro').'"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">';
    echo '<link rel="stylesheet" href="/content/default/css/default.css">';
    echo '<link rel="stylesheet" href="/content/admin/css/style.css">';
    echo '<link rel="stylesheet" href="/content/admin/include/crm/crm.css">';
    echo '<style>body{margin:0;padding:0;background:#fff;font-family:"def",sans-serif;}</style>';
    echo '</head><body>';
    if (isset($t_mp[3]) && file_exists(_ADM.'/page/'.$t_mp[3].'.php')) {
        include(_ADM.'/page/'.$t_mp[3].'.php');
    }
    echo '</body></html>';
    exit;
}
?>

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
				<div class="lng" style="display:inline-flex;align-items:center;gap:0.3rem;">
				<div id="crm-bell-wrap" style="position:relative;display:inline-flex;align-items:center;margin-right:0.4rem;cursor:pointer;" onclick="crmBellToggle()">
					<img src="/content/admin/include/crm/icons/bell.svg" width="20" height="20" style="opacity:0.7;display:block;">
					<span id="crm-bell-badge" style="display:none;position:absolute;top:-6px;right:-8px;background:#E61E2D;color:#fff;font-size:0.62rem;font-weight:700;border-radius:50%;min-width:18px;height:18px;line-height:18px;text-align:center;padding:0 3px;box-shadow:0 1px 4px rgba(0,0,0,.25);"></span>
					<div id="crm-bell-dropdown" style="display:none;position:absolute;top:32px;right:0;left:auto;width:340px;max-height:400px;overflow-y:auto;background:#fff;border:1px solid #e8e8e8;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.13);z-index:9999;" onclick="event.stopPropagation()"></div>
				</div>';
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
				$current_menu = rbac_update_admin_menu($user_type, $user_role ?? null, $user_id ?? null);

					// Parsing menu — ids configured in include/parsing_access.php.
					require_once(_ADM_INCL.'/parsing_access.php');
					$parsingActions = parsing_menu_actions($user_id ?? 0);
					if (!empty($parsingActions)) {
						if (array_key_exists('ordercars', $current_menu)) {
							$rebuilt = [];
							foreach ($current_menu as $mk => $mv) {
								$rebuilt[$mk] = $mv;
								if ($mk === 'ordercars') $rebuilt['parsing'] = $parsingActions;
							}
							$current_menu = $rebuilt;
						} else {
							$current_menu = ['parsing' => $parsingActions] + $current_menu;
						}
					}
				
				// Visual grouping only. Low-traffic modules render inside one "rest" box
				// so the sidebar stays short. $current_menu itself must NOT change: the
				// access check further down authorises a page via isset($current_menu[...]).
				$rest_modules = ['tyres', 'brands_seo', 'viz', 'mail', 'stock'];
				$render_menu  = [];
				$rest_bucket  = [];
				foreach ($current_menu as $mk => $mv) {
					if (in_array($mk, $rest_modules, true) && !empty($mv)) {
						$acts = (array)$mv;
						foreach ($acts as $mact) {
							// Single-action module -> show the module name; otherwise the action name.
							$rest_bucket[$mk.'/'.$mact] = (count($acts) === 1) ? $mk : $mk.'_'.$mact;
						}
						continue;
					}
					$render_menu[$mk] = $mv;
				}
				if ($rest_bucket) {
					$sett_actions = null;
					if (array_key_exists('sett', $render_menu)) {
						$sett_actions = $render_menu['sett'];
						unset($render_menu['sett']);
					}
					$render_menu['rest'] = $rest_bucket;
					if ($sett_actions !== null) { $render_menu['sett'] = $sett_actions; }
				}

				// "New" clients = registered since the Super Admin last opened the list.
				// Opening /b2b/users advances a "seen" marker (the newest client id at
				// that moment), so the badge clears the instant he looks.
				$b2b_new_users    = 0;
				$b2b_new_requests = 0;
				try {
					$onB2bUsers = (($t_mp[3] ?? '') === 'b2b') && (($t_mp[4] ?? 'users') === 'users');
					if ($onB2bUsers) {
						$maxUserId = (int)$db->query('SELECT COALESCE(MAX(id),0) FROM '.$prefx.'_b2b_users')->fetchColumn();
						// Upsert without relying on a UNIQUE(name) index: UPDATE never
						// duplicates; INSERT only when the row is genuinely missing.
						$updSeen = $db->prepare('UPDATE '.$prefx.'_settings SET `value`=:v WHERE `name`="b2b_users_seen_id"');
						$updSeen->execute([':v' => (string)$maxUserId]);
						if ($updSeen->rowCount() === 0
							&& (int)$db->query('SELECT COUNT(*) FROM '.$prefx.'_settings WHERE `name`="b2b_users_seen_id"')->fetchColumn() === 0) {
							$db->prepare('INSERT INTO '.$prefx.'_settings (`name`,`value`) VALUES ("b2b_users_seen_id", :v)')->execute([':v' => (string)$maxUserId]);
						}
					}
					$seenUserId       = (int)($db->query('SELECT `value` FROM '.$prefx.'_settings WHERE `name`="b2b_users_seen_id" LIMIT 1')->fetchColumn() ?: 0);
					$b2b_new_users    = (int)$db->query('SELECT COUNT(*) FROM '.$prefx.'_b2b_users WHERE id > '.$seenUserId)->fetchColumn();
					$b2b_new_requests = (int)$db->query('SELECT COUNT(*) FROM '.$prefx.'_b2b_requests WHERE `status`="new"')->fetchColumn();
				} catch (\Throwable $e) { /* tables not migrated yet */ }

				if (!empty($render_menu)) {
					foreach($render_menu as $k => $ar){
						$menu_name = isset($adm_lang[$k]) ? $adm_lang[$k] : ucfirst($k);
						
						// If module has only one option, make the label a direct link.
						// "rest" always stays a group: its keys are "module/action" paths.
						if (count($ar) === 1 && $k !== 'rest') {
							$single_action = $ar[0];
							$is_active = (isset($t_mp[3]) && $t_mp[3] === $k);
							echo '
							<input id="menu_bx_'.$k.'" type="radio" name="menu_bx" class="radio_inp none" '.($is_active?'checked="checked"':'').' />
							<div class="bx single-item '.($is_active?'active':'').' '.(in_array( $k, $hided_admin_menu, true )?'ghost':'').'">
								<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/'.$k.'/'.$single_action.'" class="nm '.($is_active?'act':'').'">'.$menu_name.'</a>
							</div>';
						} else {
							echo '
							<input id="menu_bx_'.$k.'" type="radio" name="menu_bx" class="radio_inp none" '.( (isset($t_mp[3])&&($t_mp[3]==$k||($k=='rest'&&in_array($t_mp[3], $rest_modules, true))))||(!isset($t_mp[3])&&$k=='sett')?'checked="checked"':'' ).' />
							<div class="bx '.(in_array( $k, $hided_admin_menu, true )?'ghost':'').'">';
								$label_html = $menu_name;
								if ($k === 'b2b') {
									$grp_total = $b2b_new_users + $b2b_new_requests;
									if ($grp_total > 0) {
										$label_html = '<span class="nm-badge-wrap">'.$menu_name.'<span class="menu-badge">'.$grp_total.'</span></span>';
									}
								}
								echo '
								<label for="menu_bx_'.$k.'" class="nm">'.$label_html.'</label>';
								foreach ($ar as $vk => $v){
									// A grouping bucket (e.g. "rest") points at OTHER modules: the key is
									// "module/action" and the value is the adm_lang key for the label.
									// Numeric keys keep the original behaviour (action inside $k).
									$lbl_key = null;
									if (is_string($vk) && strpos($vk, '/') !== false) {
										list($mod, $act) = explode('/', $vk, 2);
										$lbl_key = $v;
									} else {
										$mod = $k; $act = $v;
									}

									if ( isset($restrict_admin_menu[$user_id]['page'][$mod][$act]) ){continue;}

									$menu_name = ($lbl_key !== null && isset($adm_lang[$lbl_key]))
										? $adm_lang[$lbl_key]
										: (isset($adm_lang[$mod.'_'.$act]) ? $adm_lang[$mod.'_'.$act] : (isset($adm_lang[$act]) ? $adm_lang[$act] : ucfirst($act)));
									echo '
									<a href="/'.$_COOKIE['lang'].'/'.$admin_dir.($mod=='sett'&&$act=='info'?'':'/'.$mod.'/'.$act).'" class="'.$mod.' '.$act.' btn '.((isset($t_mp[4])&&$t_mp[3]==$mod&&$t_mp[4]==$act)||(!isset($t_mp[3])&&$act=='info')?'act':'').'">';
										$qu_x = 0;
										if ($mod=='mail'){
											// Collapsed into a single entry: count every unread folder.
											$qu_x = ($lbl_key !== null)
												? $db->query('SELECT COUNT(*) FROM '.$prefx.'_mail WHERE `seen`=0 AND `folder` IN ("message","order")')->fetchColumn()
												: ( in_array($act, ['message', 'order']) ? $db->query('SELECT COUNT(*) FROM '.$prefx.'_mail WHERE `seen`=0 AND `folder`="'.$act.'"')->fetchColumn() : 0 );
										}
										
										$sub_badge = '';
										if ($mod=='b2b' && $act=='users' && $b2b_new_users>0) $sub_badge = '<span class="menu-badge menu-badge--sm">'.$b2b_new_users.'</span>';
										if ($mod=='b2b' && $act=='requests' && $b2b_new_requests>0) $sub_badge = '<span class="menu-badge menu-badge--sm">'.$b2b_new_requests.'</span>';
										echo $menu_name.($mod=='mail'&&$qu_x>0?' :'.$qu_x:'').$sub_badge.'
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

				// Parsing pages: access by the configured user-id groups.
				if (isset($t_mp[3]) && $t_mp[3] === 'parsing') {
					require_once(_ADM_INCL.'/parsing_access.php');
					$pAction = $t_mp[4] ?? 'filters';
					if (parsing_is_full($user_id ?? 0)) { $has_access = true; }
					elseif (parsing_is_limited($user_id ?? 0) && in_array($pAction, ['filters', 'ctlg', 'favorites', 'published'], true)) { $has_access = true; }
					elseif (parsing_is_encar_only($user_id ?? 0) && in_array($pAction, ['filters', 'ctlg'], true)) { $has_access = true; }
					else { $has_access = false; }
				} else
			
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