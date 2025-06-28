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
		
		//if ( isset($t_mp[3])&&in_array($t_mp[3], $admin_menu[$user_type], true) ){
		if ( isset($t_mp[3]) && isset( $admin_menu_dev1[$user_type][ $t_mp[3] ] ) ){
			if ( file_exists(_ADM.'/js/'.$t_mp[3].'.js') ){
				echo '<script src="/'._ADM.'/js/'.$t_mp[3].'.js?d='.date("GYimsd", filemtime(_ADM."/js/".$t_mp[3].".js")).'"></script>';
			}
		}
		
		echo '
		<div id="main_admin">
			<div id="header">
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
			
			<div id="menu" class="dev1">';
				if ( isset($admin_menu_dev1[$user_type]) ){
					foreach($admin_menu_dev1[$user_type] as $k => $ar){
						$menu_name = isset($adm_lang[$k]) ? $adm_lang[$k] : ucfirst($k);
						
						echo '
						<input id="menu_bx_'.$k.'" type="radio" name="menu_bx" class="radio_inp none" '.( (isset($t_mp[3])&&$t_mp[3]==$k)||(!isset($t_mp[3])&&$k=='sett')?'checked="checked"':'' ).' />
						<div class="bx '.(in_array( $k, $hided_admin_menu, true )?'ghost':'').'">
							<label for="menu_bx_'.$k.'" class="nm">'.$menu_name.'</label>';
							foreach ($ar as $v){
								if ( isset($restrict_admin_menu[$user_id]['page'][$k][$v]) ){continue;}
								
								$menu_name = isset($adm_lang[$v]) ? $adm_lang[$v] : ucfirst($v);
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
				
				/*$smf = 'sitemap.xml';
				if (file_exists($smf)) {
					//echo '<a href="/'.$_COOKIE['lang'].'/dev_tools/sitemap_generator"><div class="button" title="Actualizați Sitemap.xml">Sitemap ['.date ("d.m.y H:i:s", filemtime($smf)).']</div></a>';
					echo '<a><div class="button" title="Sitemap.xml">Sitemap ['.date ("d.m.y H:i:s", filemtime($smf)).']</div></a>';
				}*/
			echo '
			</div>
			
			<div id="content">';
				if ( isset($admin_menu_dev1[$user_type]) && ( (isset($t_mp[3]) && isset($t_mp[4]) && !isset($restrict_admin_menu[$user_id]['page'][$t_mp[3]][$t_mp[4]])) || !isset($t_mp[3]) ) ){
					//if ( isset($t_mp[3]) && in_array($t_mp[3], $admin_menu[$user_type], true) ){
					if ( isset($t_mp[3]) && isset($admin_menu_dev1[$user_type][$t_mp[3]]) ){
						if ( in_array($t_mp[3], ['cars','tyres']) ){require_once(_ADM_INCL.'/filter.php');}
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