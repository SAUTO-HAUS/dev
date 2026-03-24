<?php defined( '_DOIT' ) or die( 'Restricted access' );

$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1 ';

foreach($arr_types as $k){ if ( isset($_POST[$k.'_search'])&&$_POST[$k.'_search']!='all' ) {$sql .= ' AND `'.$k.'` = :'.$k.''; $query_args[$k] = $_POST[$k.'_search'];} }

if (isset($_POST['status_search']) && $_POST['status_search'] != 'all') {
	switch ($_POST['status_search']) {
		case 'active':    $sql .= ' AND `act`="1"'; break;
		case 'sterse':    $sql .= ' AND `act`="0"'; break;
		case 'nu_stoc':   $sql .= ' AND `n_a`="1"'; break;
		case 'la_client': $sql .= ' AND `is_at_client`="1"'; break;
	}
}

//MORE ITEMS------------------------------------------------------------------
if ($_POST['fn']=='more'){
	$sql .= ' AND `id`<=:id ';
	$query_args["id"] = $_POST["it_pos"];
} elseif ($_POST['fn']=='search'){
	$_POST["it_qu"]--;
}

$sql .= ' ORDER BY `act` DESC, `n_a` ASC, `is_at_client` ASC, `vis` DESC, `id` DESC';
$sql .= ' LIMIT :it_qu';
$query_args["it_qu"] = ($_POST["it_qu"]+1);

$pdo = $db->prepare($sql);
$pdo->execute($query_args);


//if ($_POST['fn']!='more'){ $cars[] = '<div id="add_new" class="car_box" title="Добавить"> <div></div> </div>'; }

$i = 0;
$rtrn = '';
foreach ($pdo as $r){
	$i++;
	if($i>$_POST['it_qu']){break;}
	
	$on_img =  $r['gift']==1  ? '<span class="top">Cadou</span>' : '';
	$on_img .= $r['tva']==1  ? '<span class="tva">TVA</span>' : '';
	$on_img .= $r['soon']==1 ? '<span class="soon" '.($_COOKIE['lang']=='ru'?'style="order:99;"':'').'>'.$lng['l']['stat']['soon1'].'</span>' : '';
	$on_img .= $r['n_a']==1  ? '<span class="not_av">'.$lng['l']['stat']['n_a1'].'</span>' : '';
	$on_img .= $r['top']==1  ? '<span class="top">'.$lng['l']['stat']['top1'].'</span>' : '';
	
	$new_item = 0;
	if ($r['new']!=''){
		if ( ( time() - $r['new'] ) > 172800 ){
			$pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `new`=0 WHERE `id`=:id');
			$pdo->execute([ 'id' => $r['id'] ]);
		}
		else {$new_item = 1;}
	}

	$p_nm = '';
	$p_ff = '';
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`= :it_id AND `main`="1"');
	$pdo->execute([ 'it_id' => $r['id'] ]);
	foreach ($pdo as $p){ $p_nm = $p['name']; $p_ff = $p['ff']; }
	
	$stts = ($r['vis']==0?' hided':'').($r['act']==0?' deleted':'');
	
	$z_msg = ( empty($r['txt']) || !trim($r['txt']) ) ? '' : 'act';
	$z_loc = ( in_array($user_login, ['comerzan']) ) ? 2 : 1;
	$av_k = ['id'=>0, 'br'=>0, 'mo'=>0, 'br_nm'=>1, 'mo_nm'=>1, 'yr'=>0, 'bt'=>0, 'mlg'=>1, 'unit'=>0, 'vol'=>0, 'hp'=>0, 'fl'=>0, 'tra'=>0, 'wd'=>0, 'sts'=>0, 'clr'=>0, 'prc'=>1, 'cur'=>1];
	
	// Build publication icons HTML
	$pub_icons_html = '<div class="log_sauto_999">';
	
	// SAUTO logo
	if (!empty($r['br'])) {
		$pub_icons_html .= '<img src="/media/images/site/v2/logo_b.svg" class="log_sauto" title="Published on SAUTO" alt="Published on SAUTO"/>';
	}
	
	// 999.md icon
	$pending999Schedules = [];
	$failed999Schedule = null;
	$all999Schedules = [];
	try {
		$stmt = $db->prepare("SELECT * FROM {$prefx}_sauto_personal_schedules WHERE car_id = ? AND status = 'pending' AND catalog_type = 'in_stock' ORDER BY schedule_date, schedule_time");
		$stmt->execute([$r['id']]);
		$pending999Schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		$stmt = $db->prepare("SELECT * FROM {$prefx}_sauto_personal_schedules WHERE car_id = ? AND status = 'failed' AND catalog_type = 'in_stock' ORDER BY updated_at DESC LIMIT 1");
		$stmt->execute([$r['id']]);
		$failed999Schedule = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		$stmt = $db->prepare("SELECT * FROM {$prefx}_sauto_personal_schedules WHERE car_id = ? AND catalog_type = 'in_stock' ORDER BY schedule_date, schedule_time");
		$stmt->execute([$r['id']]);
		$all999Schedules = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	} catch (Exception $e) {
		$pending999Schedules = [];
		$failed999Schedule = null;
		$all999Schedules = [];
	}
	
	if (!empty($r['999_id']) || !empty($pending999Schedules) || $failed999Schedule || !empty($all999Schedules)) {
		$adverts = (new \App\Db\Adverts())->getActiveAdvertsByCarId($r['id']);
		$tooltip = '';
		if (!empty($adverts)) {
			$tooltip = ($lng['cars']['date_next_public'] ?? 'Next publication') . ':<br>';
			foreach ($adverts as $advert) {
				$tooltip .= 'Clone #' . $advert['type'] . ' - ' . $advert['publish_datetime'] . '<br>';
			}
		}
		
		if (!empty($all999Schedules)) {
			foreach ($all999Schedules as $schedule) {
				$scheduleDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $schedule['schedule_date'] . ' ' . $schedule['schedule_time']);
				$formattedDate = $scheduleDateTime ? $scheduleDateTime->format('d.m.Y, H:i') : $schedule['schedule_date'] . ' ' . $schedule['schedule_time'];
				
				switch ($schedule['status']) {
					case 'published': $statusText = 'опубликовано'; break;
					case 'pending': $statusText = 'в ожидании'; break;
					case 'failed': $statusText = 'неудачно'; break;
					case 'cancelled': $statusText = 'отменено'; break;
					case 'postponed': $statusText = 'отложено (таймер истёк)'; break;
					default: $statusText = $schedule['status'];
				}
				
				$tooltip .= ($tooltip ? '<br>' : '') . $formattedDate . ' - ' . $statusText;
			}
		}
		if ($failed999Schedule) {
			$tooltip .= ($tooltip ? '<br>' : '') . 'Ошибка: ' . ($failed999Schedule['error_message'] ?? 'Неизвестная ошибка');
		}
		
		if (!empty($r['999_id'])) {
			$pub_icons_html .= '<a href="https://999.md/'.$r['999_id'].'" target="_blank"><img src="/media/images/site/logo_999.svg" class="log_999" data-tooltip="'.htmlspecialchars($tooltip).'" alt="Published on 999"/></a>';
		} elseif ($failed999Schedule) {
			$pub_icons_html .= '<img src="/media/images/site/logo_999.svg" class="log_999" style="filter: hue-rotate(0deg) saturate(2) brightness(0.8) contrast(1.2); color: #dc3545;" data-tooltip="'.htmlspecialchars($tooltip).'" alt="Failed 999"/>';
		} else {
			$pub_icons_html .= '<img src="/media/images/site/logo_999.svg" class="log_999" style="filter: grayscale(100%) opacity(0.6);" data-tooltip="'.htmlspecialchars($tooltip).'" alt="Scheduled for 999"/>';
		}
	}
	$pub_icons_html .= '</div>';
	
	// Telegram & Facebook icons
	$tg_fb_icons_html = '';
	if ($r['telegram_published'] >= 1 || $r['facebook_published'] >= 1) {
		$tg_fb_icons_html .= '<div class="icon_list_cattg">';
		
		if ($r['telegram_published'] >= 1) {
			$tg_title = $r['telegram_published'] == 1 ? 'Опубликовано в Telegram' : 'Запланировано в Telegram';
			$tg_color = $r['telegram_published'] == 1 ? '#0088cc' : '#888888';
			$tg_fb_icons_html .= '<div class="icon_tg" title="'.$tg_title.'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512"><circle cx="248" cy="256" r="248" fill="'.$tg_color.'"/><path fill="#ffffff" d="M248,8C111.033,8,0,119.033,0,256S111.033,504,248,504,496,392.967,496,256,384.967,8,248,8ZM362.952,176.66c-3.732,39.215-19.881,134.378-28.1,178.3-3.476,18.584-10.322,24.816-16.948,25.425-14.4,1.326-25.338-9.517-39.287-18.661-21.827-14.308-34.158-23.215-55.346-37.177-24.485-16.135-8.612-25,5.342-39.5,3.652-3.793,67.107-61.51,68.335-66.746.153-.655.3-3.1-1.154-4.384s-3.59-.849-5.135-.5q-3.283.746-104.608,69.142-14.845,10.194-26.894,9.934c-8.855-.191-25.888-5.006-38.551-9.123-15.531-5.048-27.875-7.717-26.8-16.291q.84-6.7,18.45-13.7,108.446-47.248,144.628-62.3c68.872-28.647,83.183-33.623,92.511-33.789,2.052-.034,6.639.474,9.61,2.885a10.452,10.452,0,0,1,3.53,6.716A43.765,43.765,0,0,1,362.952,176.66Z"/></svg></div>';
		}
		
		if ($r['facebook_published'] >= 1) {
			$fb_title = $r['facebook_published'] == 1 ? 'Опубликовано в Facebook' : 'Запланировано в Facebook';
			$fb_color = $r['facebook_published'] == 1 ? '#1877F2' : '#888888';
			$tg_fb_icons_html .= '<div class="icon_tg" title="'.$fb_title.'"><svg style="top: 7px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><circle cx="256" cy="256" r="256" fill="'.$fb_color.'"/><path fill="#ffffff" d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.5 90.9 225.8 209 245v-173h-63v-72h63v-55c0-62.3 37-96.5 93.7-96.5 27.1 0 55.5 4.8 55.5 4.8v61h-31.2c-30.8 0-40.4 19.1-40.4 38.7v46.1h68.8l-11 72h-57.8v173c118.1-19.2 209-121.5 209-245z"/></svg></div>';
		}
		
		$tg_fb_icons_html .= '</div>';
	}

	$rtrn .= '
	<div class="bx'.$stts.'" data-id="'.$r['id'].'">
		'.$pub_icons_html.'
		'.$tg_fb_icons_html.'
		<div class="icon comment '.$z_msg.'" title="'.$lng['w']['comment'].'"></div>
		<textarea class="comment_txt">'.(isset($restrict_admin_menu[$user_id]['act']['com']['cars'])&&$r['loc']==1?'Informatie restrictionata':$r['txt']).'</textarea>
		<div class="icon print '.$z_msg.'" title="'.$lng['w']['print'].'"></div>
		<div class="print_bx">
			<form target="_blank" action="/print.php" method="post">
				<input class="none" type="text" name="src" value="adm" />
				<input class="none" type="text" name="qSd4b_print" value="1" />
				<select name="loc" class="sel" title="'.$lng['w']['address'].'">
					<option value="1" '.(($z_loc==1)?'selected="selected"':'').'>'.$lng['t']['x']['address'][1].'</option>
					<option value="2" '.(($z_loc==2)?'selected="selected"':'').'>'.$lng['t']['x']['address'][2].'</option>
				</select>
				<select name="drct" class="sel" title="'.$lng['w']['orientation'].'">
					<option value="v" selected="selected">'.$lng['w']['vertically'].'</option>
					<option value="h">'.$lng['w']['horizontally'].'</option>
				</select>
				<select name="theme" class="sel" title="'.$lng['w']['clr_thm'].'">
					<option value="0">'.$lng['w']['grsc'].'</option>
					<option value="1" selected="selected">'.$lng['w']['clrd'].'</option>
				</select>
				
				<div class="ttl">'.$r['br_nm'].' '.$r['mo_nm'].' <span class="zx">id: '.$r['id'].'</span></div>
				
				<div class="cnt">';				
					foreach ($r as $k2 => $v2){
						if ( isset($av_k[$k2]) ){
							$rtrn .= '<label '.(($av_k[$k2]==1)?'class="act"':'').'>'.(($av_k[$k2]==1)?'<span class="ttl">'.$lng['l']['car']['spec'][$k2].'</span>':'').'<input type="text" name="'.$k2.'" value="'.$v2.'" /></label>';
						}
					}
					$rtrn .= '
					<label class="act"><span class="ttl">'.$lng['w']['exchange'].' [Trade-in]</span><input type="text" name="exchange" value="'.($r['prc']+1000).'" /></label>
					<label class="act"><span class="ttl">'.$lng['l']['car']['spec']['cons'].'</span><input type="text" name="cons" value="" placeholder="L/100" /></label>
					<label class="act"><span class="ttl">'.$lng['l']['car']['spec']['tnk'].'</span><input type="text" name="tnk" value="" placeholder="L" /></label>
				</div>
				
				<div class="cur none">';
					$pdo = $db->prepare('SELECT * FROM '.$prefx.'_exchange');
					$pdo->execute();
					foreach($pdo as $cur){
						$rtrn .= '<input type="text" name="cur_'.$cur['name'].'" value="'.$cur['value'].'" />';
					}
				$rtrn .= '
				</div>
				
				<input class="btn" type="submit" value="'.$lng['w']['further'].'" />
			</form>
		</div>
		<div class="adm_menu">';
			if( $r['act'] == 1 ){
				$detail_section = ($r['catalog_type'] == 'on_order') ? 'ordercars' : 'cars';
				$rtrn .= '
				<a class="btn edit" href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/'.$detail_section.'/detail?id=' . $r['id'].'" title="'.$lng['adm']['edit'].'"> <div></div> </a><!--data-fn="edit"-->
				<div class="btn fn_av" data-fn="'.($r['n_a']==0?'av0':'av1').'" title="'.($r['n_a']==0?'-':'+').'" data-alt="'.($r['n_a']==0?'+':'-').'"> <div></div> </div>';
				// DISABLED: hide/reveal and delete buttons
				// if ( in_array($user_type, ['dev', 'sad']) ){
				// 	$rtrn .= '
				// 	<div class="btn fn_hr" data-fn="'.($r['vis']==0?'reveal':'hide').'" title="'.$lng['adm'][($r['vis']==0?'reveal':'hide')].'" data-alt="'.$lng['adm'][($r['vis']==0?'hide':'reveal')].'" data-fn> <div></div> </div>
				// 	<div class="btn fn_dre" data-fn="delete" title="'.$lng['adm']['delete'].'"> <div></div> </div>';
				// }
			} elseif ( $r['act'] == 0 && in_array($user_type, ['dev', 'sad']) ){
				$rtrn .= '
				<div class="btn fn_dre" data-fn="restore" title="'.$lng['adm']['restore'].'"> <div></div> </div>';
				// DISABLED: erase button
				// <div class="btn fn_dre" data-fn="erase" title="'.$lng['adm']['delete'].'"> <div></div> </div>';
			}
		$rtrn .= '
		</div>
		
		<div class="base_info">
			<div class="id" title="id">'.$r['id'].'</div>
			<div class="author" title="author">'.$r['author'].'</div>
			<div class="views" title="views"> '.$r['views'].' <div class="img"></div> </div>
			<div class="date" title="'.date('H:i:s', $r['date']).'">'.date('d.m.Y', $r['date']).'</div>
		</div>
		';
	
	// Check for HTML description
	$hasHtml = false;
	try {
		$seoStmt = $db->prepare("SELECT params_html FROM {$prefx}_seo2 WHERE it_id = ? AND tp = 'item' AND p1 = 'cars' AND lng = 'ro' LIMIT 1");
		$seoStmt->execute([$r['id']]);
		$seoRow = $seoStmt->fetch(\PDO::FETCH_ASSOC);
		$hasHtml = !empty($seoRow['params_html']) && strlen(trim($seoRow['params_html'])) > 10;
	} catch (Exception $e) {
		$hasHtml = false;
	}
	$htmlIndicator = '<div class="html-indicator" title="'.($hasHtml ? 'HTML описание есть' : 'HTML описание отсутствует').'" style="position:absolute;top:5px;left:5px;width:14px;height:14px;border-radius:3px;text-align:center;line-height:14px;font-size:9px;font-weight:bold;color:#fff;background:'.($hasHtml ? '#28a745' : '#dc3545').';z-index:10;">'.($hasHtml ? '✓' : '✗').'</div>';
	
	$rtrn .= '
		<div class="img" style="background-image:url(/'._CAR_IMG.'/'.$r['p_path'].'/'.$r['id'].'/med/'.$p_nm.( !empty($p_ff) ? ('.'.$p_ff) : $img_frmt ).'), url(/media/images/site/no_image.png);position:relative;">'.$htmlIndicator;
			//if($r['top']){$rtrn .= '<div class="top-sales" title="Top Sales">'.$lng['l']['stat']['top1'].'</div>';}
			if( $r['act'] == 0 ){$rtrn .= '<div class="remove_after" timer="'.( $r['del_t']-time() ).'" ra="'.$r['del_t'].'">**, **:**:**</div>';}
			$rtrn .= '
			<a class="url" href="'.$site_url.'/'.$_COOKIE['lang'].'/cars/'.$r['id'].'" target="_blank" title="To the item page"><div class="ico"></div></a>
			<div class="on_img">'.$on_img.'</div>
		</div>
		
		<div class="nm">
			<span class="br">'.$r['br_nm'].'</span>
			<span class="mo">'.$r['mo_nm'].'</span>
		</div>

		<div class="info">';
			foreach( [ 'yr'=>['x'=>0], 'vol'=>['x'=>0, 'u'=>'cm3'], 'fl'=>['x'=>1], 'tra'=>['x'=>1] ] as $k => $v ){
				$rtrn .= '
				<div class="it">
					<span class="ttl">'.(isset($lng['l']['car']['spec'][$k])?$lng['l']['car']['spec'][$k]:strtoupper($k)).': </span>
					<span class="spc"></span>
					<span class="val">'.( $v['x']==0?$r[$k]:(isset($lng['l']['car'][$k][$r[$k]])?$lng['l']['car'][$k][$r[$k]]:$r[$k]) ).( isset($v['u'])?' '.$v['u']:'' ).'</span>
				</div>';
			}
		$rtrn .= '
		</div>

		<div class="prc_wrap">
			<div class="prc" title="'.$lng['w']['prc'].'">'.$r['prc'].' <span>'.$lng['l']['cur'][$r['cur']].'</span></div>
		</div>
	</div>';
}

$c_id = $i<=$_POST['it_qu'] ? null : $r['id'];
?>