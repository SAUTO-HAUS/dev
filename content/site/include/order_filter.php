<?php defined( '_DOIT' ) or die( 'Restricted access' );

if (isset($t_mp[3]) && !empty($t_mp[3]) && !is_numeric($t_mp[3])) {
	$_GET['tg'] = 'fltr';
	$_GET['br'] = str_replace('-', '_', explode('?', $t_mp[3])[0]);
	if (isset($t_mp[4]) && !empty($t_mp[4])) {
		$_GET['mo'] = str_replace('-', '_', explode('?', $t_mp[4])[0]);
	}
}

//----------------------------------------------------------------------------------------------CARS
if (!isset($t_mp[2]) || $t_mp[2]=='' || $t_mp[2]=='ordercars'){
	$f_arr = array();
	$content = '';
	
	$query_args = array();
	$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE `vis`="1" AND `act`="1" ORDER BY `br` ASC, `mo` ASC ';
	
	$pdo = $db->prepare($sql);
	$pdo->execute($query_args);
	foreach ($pdo as $r){
		foreach($f_it_t_arr['car']['get'] as $k => $v){
			if( (isset($r[$v])&&$r[$v]!='') || $v=='brmo'){
				$f_arr[$v]['get'] = $k;
				if ($v=='brmo'){
					$f_arr[$v]['br'][ $r['br'] ][ 'name' ] = $r['br'];
					$f_arr[$v]['br'][ $r['br'] ][ 'r_name' ] = $r['br_nm'];
					
					$f_arr[$v]['mo'][ $r['mo'] ][ 'name' ] = $r['mo'];
					$f_arr[$v]['mo'][ $r['mo'] ][ 'r_name' ] = $r['mo_nm'];
					$f_arr[$v]['mo'][ $r['mo'] ][ 'brand' ] = $r['br'];
				}else{
					if ( in_array($v, $f_it_t_arr['car']['inpt_sel']) ){
						$trltd = ( isset( $lng['l']['car'][$v][ $r[$v] ] ) && !is_int($r[$v]) ) ? $lng['l']['car'][$v][ $r[$v] ] : $r[$v];
						$f_arr[$v]['list'][$trltd]['val'] = $r[$v];
						$f_arr[$v]['list'][$trltd]['lng'] = $trltd;
					}
					elseif ( in_array($v, $f_it_t_arr['car']['inpt_txt']) ){
						$f_arr[$v]['list'][$r[$v]] = $r[$v];
					}
				}
			}
		}
	}
	
	echo '
	<form id="fltr" class="fltr f_car" action="" method="post" data-lng="'.$lng['w']['filter'].'">
		<div class="base">
			<select class="srch data sel br" data-tg="br" name="br" title="'.$lng['l']['car']['spec']['br'].'">
				<option value="" class="x" disabled="disabled" selected="selected">'.$lng['l']['car']['spec']['br'].'</option>
				<option value="" class="x">'.$lng['w']['all'].'</option>';
				//ksort($f_arr['brmo']['br']);
				foreach ($f_arr['brmo']['br'] as $v){
					$getBr = isset($_GET['br']) ? str_replace('-', '_', $_GET['br']) : null;
					$chkd = ( $getBr !== null && $v['name'] == $getBr ) ? ' selected="selected"' : '';
					echo '
					<option value="'.str_replace('_','-',$v['name']).'" data-parent="cars" '.$chkd.'>'.$v['r_name'].'</option>';
				}
			echo '
			</select>';
			
			$act = ( isset($_GET['br']) && is_string($_GET['br']) ) ? 'act' : '';
			
			echo '
			<select class="srch data sel mo '.$act.'" data-tg="mo" name="mo" title="'.$lng['l']['car']['spec']['mo'].'">
				<option value="" class="x" disabled="disabled" '; if (!isset($_GET['mo'])){echo ' selected="selected"';} echo '>'.$lng['l']['car']['spec']['mo'].'</option>
				<option value="" class="x">'.$lng['w']['all'].'</option>';
				//ksort($f_arr['brmo']['mo']);
				if (isset($_GET['br']) && $_GET['br'] !== '') {
	$getBr = str_replace('-', '_', $_GET['br']);
	$getMo = isset($_GET['mo']) ? str_replace('-', '_', $_GET['mo']) : null;
	foreach ($f_arr['brmo']['mo'] as $v) {
		if ($getBr == $v['brand']) {
			$chkd = ($getMo !== null && $v['name'] == $getMo) ? ' selected="selected"' : '';
			echo '<option value="'.str_replace('_','-',$v['name']).'" data-parent="'.str_replace('_','-',$v['brand']).'" '.$chkd.'>'.$v['r_name'].'</option>';
		}
	}
}
			echo '
			</select>
			<div class="list mo h" style="display:none;">';
				//ksort($f_arr['brmo']['mo']);
				foreach ($f_arr['brmo']['mo'] as $v){
					echo '
					<option value="'.str_replace('_','-',$v['name']).'" data-parent="'.str_replace('_','-',$v['brand']).'">'.$v['r_name'].'</option>';
				}
			echo '
			</div>';

			if ($isMobile==0){
				echo '
				<div class="bt n_m act" data-name="bt">';
					foreach ($f_cr_bt_arr as $k => $v){
						echo '
						<a href="/'.$_COOKIE['lang'].'/cars?tg=fltr&bt='.$k.'" title="'.$lng['l']['car']['bt'][$k].'" class="item">
							<div class="img '.$k.'" style="-webkit-mask-image:url(/'._SITE_IMG.'/v2/'.$k.'.svg); mask-image:url(/'._SITE_IMG.'/v2/'.$k.'.svg);"></div>
							<span class="ttl">'.$lng['l']['car']['bt'][$k].'</span>
						</a>';
					}
				echo '
				</div>';
			}
		echo '	
		</div>
		<div class="ext" data-parent="cars">
			<select class="srch data sel gr" data-tg="gr" name="gr" title="'.$lng['w']['group'].'">
				<option value="" disabled="disabled" class="x" '; if (!isset($_GET['gr']) || ($_GET['gr']!='car'&&$_GET['gr']!='com')){echo ' selected="selected"';} echo '>'.$lng['w']['group'].'</option>
				<option value="" class="x">'.$lng['w']['all'].'</option>
				<option value="car"'; if (isset($_GET['gr']) && $_GET['gr']=='car'){echo ' selected="selected"';} echo '>'.$lng['l']['car']['gr']['car'].'</option>
				<option value="com"'; if (isset($_GET['gr']) && $_GET['gr']=='com'){echo ' selected="selected"';} echo '>'.$lng['l']['car']['gr']['com'].'</option>
			</select>
			<select class="srch data sel bt" data-tg="bt" name="bt" title="'.$lng['l']['car']['spec']['bt'].'">
				<option value="" disabled="disabled" class="x" '; if (!isset($_GET['bt'])){echo ' selected="selected"';} echo '>'.$lng['l']['car']['spec']['bt'].'</option>
				<option value="" class="x">'.$lng['w']['all'].'</option>';
				foreach ($f_cr_bt_arr as $k => $v){
					$zArr = (isset($_GET['bt'])&&is_string($_GET['bt'])) ? explode("-", $_GET['bt'] ) : null;
					$chkd = ( (isset($_GET['bt'])&&is_string($_GET['bt']))&&in_array($k, $zArr) ) ? ' selected="selected"' : '';
					echo '
					<option value="'.$k.'" data-tg="bt" data-lng="'.$lng['l']['car']['bt'][$k].'" '.$chkd.'>'.$lng['l']['car']['bt'][$k].'</option>';
				}
			echo '
			</select>';
			$i=1;
			$zData = ' data-bt=""';
			foreach($f_it_xtd_arr['car'] as $k => $v){
				$zData .= isset($_GET[$k])?' data-'.$k.'="'.$_GET[$k].'"':' data-'.$k.'=""';
				if ($v['i']=='1'){
					$v0 = ''; $v1 = '';
					$zArr = isset($_GET[$k]) ? explode("-", $_GET[$k] ) : null;
					if ($zArr!==null){ $v0 = ' value="'.$zArr[0].'"'; $v1 = ' value="'.(isset($zArr[1])?$zArr[1]:$zArr[0]).'"';}
					
					echo '
					<div class="data '.$k.'" data-name="'.$k.'" data-type="text">
						<input '.$v0.' data-tg="'.$k.'" class="srch inp fr" list="list_'.$k.'" type="text" name="'.$k.'[]" placeholder="'.$v['t'].', '.$lng['w']['from'].'" title="'.$v['t'].' ['.$lng['w']['from'].']"/>
						<input '.$v1.' data-tg="'.$k.'" class="srch inp to" list="list_'.$k.'" type="text" name="'.$k.'[]" placeholder="'.$lng['w']['to'].'" title="'.$v['t'].' ['.$lng['w']['to'].']"/>
						<span class="unit">'.$v['unit'].'</span>
						<datalist id="list_'.$k.'">';
							ksort($f_arr[$k]['list']);
							foreach($f_arr[$k]['list'] as $lv){ echo '<option>'.$lv.'</option>'; }
						echo '
						</datalist> 
					</div>
					';
				}else{
					echo '
					<select class="srch data sel '.$k.'" data-tg="'.$k.'" name="'.$k.'" title="'.$v['t'].'">
						<option value="" disabled="disabled" class="x" '; if (!isset($_GET[$k])){echo ' selected="selected"';} echo '>'.$v['t'].'</option>
						<option value="" class="x">'.$lng['w']['all'].'</option>';
						ksort($f_arr[$k]['list']);
						foreach ($f_arr[$k]['list'] as $v){
							$zArr = isset($_GET[$k]) ? explode("-", $_GET[$k] ) : null;
							$chkd = ( isset($_GET[$k])&&in_array($v['val'], $zArr) ) ? ' selected="selected"' : '';
							echo '
							<option value="'.$v['val'].'" data-tg="'.$k.'" data-lng="'.$lng['l']['car']['spec'][$k].': '.$v['lng'].'" '.$chkd.'>'.$v['lng'].'</option>';
						}
					echo '
					</select>';
				}
				$i++;
			}
		echo '
		</div>
		<div class="ctrl">';
			$q_uri = isset($q_mp[1]) ? '?'.$q_mp[1] : '';
			echo '
			<div class="btns" data-link="/'.$_COOKIE['lang'].'/ordercars/" data-def="/'.$_COOKIE['lang'].'/ordercars">
				<div class="btn unst">
					<div class="img"></div>
					<span class="txt">'.$lng['w']['unset'].'</span>
				</div>
				<div class="btn advn">
					<div class="img"></div>
					<span class="txt gr">
						<span class="opn">'.$lng['w']['detailed'].'</span>
						<span class="cls">'.$lng['w']['simplified'].'</span>
					</span>
				</div>
				<a class="btn sbmt" href="/'.$_COOKIE['lang'].'/ordercars'.$q_uri.'" data-gr="'.(isset($_GET['gr'])?$_GET['gr']:'').'" data-br="'.(isset($_GET['br'])?str_replace('_','-',$_GET['br']):'').'" data-mo="'.(isset($_GET['mo'])?str_replace('_','-',$_GET['mo']):'').'" data-bt="'.(isset($_GET['bt'])?$_GET['bt']:'').'" data-srt="'.(isset($_GET['srt'])?$_GET['srt']:'').'"';

				// Add all detailed filter parameters as data attributes
				foreach($f_it_xtd_arr['car'] as $k => $v){
					if(isset($_GET[$k])) {
						echo ' data-'.$k.'="'.$_GET[$k].'"';
					}
				}

				echo '>
					<div class="img"></div>
					<span class="txt">'.$lng['w']['find'].' '.$lng['w']['auto'].'</span>
				</a>
			</div>
		</div>
	</form>';

	// Sort dropdown for ordercars page (catalog + sort criteria in one select)
	$srtCur = isset($_GET['srt']) ? $_GET['srt'] : '';
	$srtSortOpts = [
		'prc-asc' => $lng['w']['sort_prc_asc'],
		'prc-desc' => $lng['w']['sort_prc_desc'],
		'yr-desc' => $lng['w']['sort_yr_desc'],
		'yr-asc' => $lng['w']['sort_yr_asc'],
		'mlg-asc' => $lng['w']['sort_mlg_asc'],
		'mlg-desc' => $lng['w']['sort_mlg_desc'],
	];
	// If no sort active, show current catalog (on_order on /ordercars) as selected
	$srtSelectedHasValue = ($srtCur !== '' && isset($srtSortOpts[$srtCur]));
	echo '
	<div class="srt_wrap'.($srtSelectedHasValue?' has_value':'').'" data-page="ordercars">
		<div class="srt_cat_badge" data-cat="in_stock" title="'.$lng['w']['sort_in_stock'].'">← '.$lng['w']['sort_in_stock'].'</div>
		<div class="srt_lbl">
			<img src="/'._SITE_IMG.'/v2/sort.svg" alt="sort" />
			<span>'.$lng['w']['sort_by'].'</span>
		</div>
		<div class="srt_drop" tabindex="0">
			<div class="srt_drop_btn"><img class="srt_drop_ico" src="/'._SITE_IMG.'/v2/sort.svg" alt="sort" /> '.($srtSelectedHasValue?$srtSortOpts[$srtCur]:$lng['w']['sort_by']).'<span class="srt_drop_arr"></span></div>
			<ul class="srt_drop_list">';
				foreach ($srtSortOpts as $sk => $sv){
					echo '<li data-val="'.$sk.'"'.($srtCur===$sk?' class="active"':'').'>'.$sv.'</li>';
				}
	echo '
			</ul>
		</div>
	</div>';

//----------------------------------------------------------------------------------------------TYRES
}elseif ($t_mp[2]=='tyres'){
	$f_arr = array();
	$content = '';
	
	$query_args = array();
	$sql = 'SELECT * FROM '.$prefx.'_tyre_ctlg WHERE `vis`="1" AND `act`="1" ORDER BY `br` ASC, `mo` ASC ';
	
	$pdo = $db->prepare($sql);
	$pdo->execute($query_args);
	foreach ($pdo as $r){
		foreach($f_it_t_arr['tyre']['get'] as $k => $v){
			if( (isset($r[$v])&&$r[$v]!='') || $v=='brmo'){
				$f_arr[$v]['get'] = $k;
				if ($v=='brmo'){
					$f_arr[$v]['br'][ $r['br'] ][ 'name' ] = $r['br'];
					$f_arr[$v]['br'][ $r['br'] ][ 'r_name' ] = $r['br_nm'];
					
					$f_arr[$v]['mo'][ $r['mo'] ][ 'name' ] = $r['mo'];
					$f_arr[$v]['mo'][ $r['mo'] ][ 'r_name' ] = $r['mo_nm'];
					$f_arr[$v]['mo'][ $r['mo'] ][ 'brand' ] = $r['br'];
				}else{
					if ( in_array($v, $f_it_t_arr['tyre']['inpt_sel']) ){
						$trltd = ( isset( $lng['l']['tyre'][$v][ $r[$v] ] ) && !is_int($r[$v]) ) ? $lng['l']['tyre'][$v][ $r[$v] ] : $r[$v];
						if ($v=='c'){$trltd = $r[$v]==0?$lng['w']['no']:$lng['w']['yes'];}
						$f_arr[$v]['list'][$trltd]['val'] = $r[$v];
						$f_arr[$v]['list'][$trltd]['lng'] = $trltd;
					}
					elseif ( in_array($v, $f_it_t_arr['tyre']['inpt_txt']) ){
						$f_arr[$v]['list'][$r[$v]] = $r[$v];
					}
				}
			}
		}
	}
	
	echo '
	<form id="fltr" class="fltr f_tyre" action="" method="post" data-lng="'.$lng['w']['filter'].'">
		<div class="ext active">';
			$i=1;
			$zData = ' data-bt=""';
			foreach($f_it_xtd_arr['tyre'] as $k => $v){
				$zData .= isset($_GET[$k])?' data-'.$k.'="'.$_GET[$k].'"':' data-'.$k.'=""';
				if ($k=='brmo'){
					echo '
					<select class="srch data sel br" data-tg="br" name="br" title="'.$lng['l']['tyre']['spec']['br'].'">
						<option value="" class="x" disabled="disabled" selected="selected">'.$lng['l']['tyre']['spec']['br'].'</option>
						<option value="" class="x">'.$lng['w']['all'].'</option>';
						//ksort($f_arr['brmo']['br']);
						foreach ($f_arr['brmo']['br'] as $v){
							$zArr = isset($_GET['br']) ? explode("-", $_GET['br'] ) : null;
							$chkd = ( isset($_GET['br'])&&in_array($v['name'], $zArr) ) ? ' selected="selected"' : '';
							echo '
							<option value="'.$v['name'].'" data-parent="cars" '.$chkd.'>'.$v['r_name'].'</option>';
						}
					echo '
					</select>';
					
					$act = ( isset($_GET['br']) && is_string($_GET['br']) ) ? 'act' : '';
					
					echo '
					<select class="srch data sel mo '.$act.'" data-tg="mo" name="mo" title="'.$lng['l']['tyre']['spec']['mo'].'">
						<option value="" class="x" disabled="disabled" '; if (!isset($_GET['mo'])){echo ' selected="selected"';} echo '>'.$lng['l']['tyre']['spec']['mo'].'</option>
						<option value="" class="x">'.$lng['w']['all'].'</option>';
						//ksort($f_arr['brmo']['mo']);
						foreach ($f_arr['brmo']['mo'] as $v){
							$zArr = isset($_GET['mo']) ? explode("-", $_GET['mo'] ) : null;
							if ( isset($_GET['br']) && $_GET['br']==$v['brand'] ){
								$chkd = ( isset($_GET['mo'])&&in_array($v['name'], $zArr) ) ? ' selected="selected"' : '';
								echo '<option value="'.$v['name'].'" data-parent="'.$v['brand'].'" '.$chkd.'>'.$v['r_name'].'</option>';
							}
						}
					echo '
					</select>
					<div class="list mo h" style="display:none;">';
						//ksort($f_arr['brmo']['mo']);
						foreach ($f_arr['brmo']['mo'] as $v){
							echo '
							<option value="'.$v['name'].'" data-parent="'.$v['brand'].'">'.$v['r_name'].'</option>';
						}
					echo '
					</div>';
				}else{
					if ($v['i']=='1'){
						$v0 = ''; $v1 = '';
						$zArr = isset($_GET[$k]) ? explode("-", $_GET[$k] ) : null;
						if ($zArr!==null){ $v0 = ' value="'.$zArr[0].'"'; $v1 = ' value="'.$zArr[1].'"';}
						
						echo '
						<div class="data '.$k.'" data-name="'.$k.'" data-type="text">
							<input '.$v0.' data-tg="'.$k.'" class="srch inp fr" list="list_'.$k.'" type="text" name="'.$k.'[]" placeholder="'.$v['t'].', '.$lng['w']['from'].'" title="'.$v['t'].' ['.$lng['w']['from'].']"/>
							<input '.$v1.' data-tg="'.$k.'" class="srch inp to" list="list_'.$k.'" type="text" name="'.$k.'[]" placeholder="'.$lng['w']['to'].'" title="'.$v['t'].' ['.$lng['w']['to'].']"/>
							<span class="unit">'.$v['unit'].'</span>
							<datalist id="list_'.$k.'">';
								ksort($f_arr[$k]['list']);
								foreach($f_arr[$k]['list'] as $lv){
									echo '<option>'.$lv.'</option>';
								}
							echo '
							</datalist> 
						</div>
						';
					}else{
						echo '
						<select class="srch data sel '.$k.'" data-tg="'.$k.'" name="'.$k.'" title="'.$v['t'].'">
							<option value="" disabled="disabled" class="x" selected="selected">'.$v['t'].'</option>
							<option value="" class="x">'.$lng['w']['all'].'</option>';
							ksort($f_arr[$k]['list']);
							foreach ($f_arr[$k]['list'] as $v){
								$zArr = isset($_GET[$k]) ? explode("-", $_GET[$k] ) : null;
								$chkd = ( isset($_GET[$k])&&in_array($v['val'], $zArr) ) ? ' selected="selected"' : '';
								echo '
								<option value="'.$v['val'].'" data-tg="'.$k.'" data-lng="'.(isset($lng['l']['tyre']['spec'][$k])?$lng['l']['tyre']['spec'][$k]:$k).': '.$v['lng'].'" '.$chkd.'>'.$v['lng'].'</option>';
							}
						echo '
						</select>';
					}
				}
				$i++;
			}
		echo '
		</div>
		<div class="img"></div>
		<div class="ctrl">';
			$q_uri = isset($q_mp[1]) ? '?'.$q_mp[1] : '';
			echo '
			<div class="btns" data-link="/'.$_COOKIE['lang'].'/tyres?tg=fltr" data-def="/'.$_COOKIE['lang'].'/tyres">
				<div class="btn unst">
					<div class="img"></div>
					<span class="txt">'.$lng['w']['unset'].'</span>
				</div>
				<a class="btn sbmt" href="/'.$_COOKIE['lang'].'/tyres'.$q_uri.'" '.$zData.' data-br="'.(isset($_GET['br'])?$_GET['br']:'').'" data-mo="'.(isset($_GET['mo'])?$_GET['mo']:'').'">
					<div class="img"></div>
					<span class="txt">'.$lng['w']['find'].' '.$lng['w']['tyres'].'</span>
				</a>
			</div>
		</div>
	</form>';


//----------------------------------------------------------------------------------------------RENT
}elseif ($t_mp[2]=='rent'){
	//echo 'hi';
}
?>