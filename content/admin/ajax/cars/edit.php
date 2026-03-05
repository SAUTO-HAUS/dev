<?php defined( '_DOIT' ) or die( 'Restricted access' );

$rtrn = ''; $zY = substr( md5( date('Y') ), 0, 4 ); $zM = substr( md5( date('m') ), 0, 4 );

if ( $_POST['sub']=='start' ){//*************************************************************************************************************** MAKE OVERLAY
	$it_br = [];
	
	//--------------------------------------ALL BRANDS
	$pdo = $db->prepare('SELECT `br`,`br_nm` FROM '.$prefx.'_car_list ORDER BY `br` ASC'); $pdo->execute();
	foreach ($pdo as $r){ $it_br[ $r['br'] ] = $r['br_nm']; }
	
	//--------------------------------------THIS ITEM
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id'); $pdo->execute([ 'id'=>$_POST['id'] ]);
	foreach ($pdo as $r){ $c_path = $r['p_path']; }
	
	//--------------------------------------ALL MODELS THIS BRAND
	$pdo = $db->prepare('SELECT `mo`,`mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br ORDER BY `mo` ASC'); $pdo->execute([ 'br'=>$r['br'] ]);
	foreach ($pdo as $r2){ $c_model[ $r2['mo'] ] = $r2['mo_nm']; }
	unset($r2);
	
	//---TITLE AND CLOSE BUTTON---
	$rtrn .= '
	<div class="bx id_'.$_POST['id'].'" data-bx_id="'.$_POST['id'].'">
		<div class="top">
			<div class="title">'.mb_strtoupper($lng['adm']['editing'], "UTF-8").'</div>
			<div class="close">'.mb_strtoupper($lng['adm']['close'], "UTF-8").'</div>
		</div>';
		
		//---ZOOM IMG BOX---
		$rtrn .= '<div class="zoom_box" class="ghost"></div>';
		
		//---NEW PHOTOS---
		$rtrn .= '
		<div class="img_bx">
			<h3 class="ttl gr_info ghost">'.$lng['w']['imgs'].'</h3>
			<label class="dd_plc">
				<input data-gr="edit" class="f" type="file" multiple="multiple" name="img[]" tabindex="1" /> <!-- accept="image/*,application/pdf,text/plain,application/msword,application/vnd.ms-excel,text/xml,application/vnd.oasis.opendocument.text,application/vnd.oasis.opendocument.spreadsheet,application/vnd.oasis.opendocument.charts,application/vnd.oasis.opendocument.presentations" -->
				<div class="txt drag ghost">DROP HERE</div>
				<div class="txt plus ghost"></div>
				<div class="inf h ghost">'.$lng['w']['qu'].': <span class="c">0</span> | '.$lng['w']['sz'].': <span class="s">0 B</span></div>
			</label>
			<!--<div class="prv docs h"><h3 class="ttl">lng6</h3><div class="its"></div></div>-->
			<div class="prv imgs h"><div class="its"></div></div>
			
			<div class="prv imgs ready">
				<div class="its bx">'; //--------------------------------------THIS ITEM PHOTOS
					$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id ORDER BY `pos` ASC');
					$pdo->execute([ 'it_id'=>$_POST['id'] ]);
					$i=1;
					foreach ($pdo as $p){
						$rtrn .= '
						<div class="it '.$p['id'].' f_img ext" data-id="'.$p['id'].'" this_img="/'.$photo_folder.'/'.$c_path.'/'.$p['it_id'].'/high/'.$p['name'].$img_frmt.'" data-n="'.$i.'" data-pos="'.$p['pos'].'" style="order:'.$i.';">
							<input id="main_img_'.$p['id'].'" type="radio" class="use main_img ext none" name="main_img" value="'.$p['id'].'" data-id="'.$p['id'].'" '.($p['main']=='1'?'checked="checked"':'').' />
							<input id="del_img_'.$p['id'].'" type="checkbox" class="use del_img ext none" name="del_img[]" value="'.$p['id'].'" data-id="'.$p['id'].'" />
							<div class="ico ghost"></div>
							<img class="img" src="/'.$photo_folder.'/'.$c_path.'/'.$p['it_id'].'/med/'.$p['name'].$img_frmt.'" />
							<div class="nm">'.$i.'</div>
							<label for="main_img_'.$p['id'].'" class="btn do_main photo_action" title="'.$adm_lang['main_photo'].'"></label>
							<label for="del_img_'.$p['id'].'" class="btn delete photo_action" title="'.$adm_lang['delete'].'"></label>
						</div>';
						$i++;
					}
				$rtrn .= '
				</div>
				<div class="action">
					<div class="it download_zip" title="Download ZIP" data-it_id="'.$_POST['id'].'">ZIP</div>
					<!--<div class="it chng_pos" title="'.$lng['w']['chng_pos'].'" data-ttl="'.$lng['w']['acpt_chng'].'" data-txt="'.$lng['w']['acpt'].'" data-it_id="'.$_POST['id'].'">'.$lng['w']['chng_pos'].'</div>-->
					
					<div class="it chng_pos" data-it_id="'.$_POST['id'].'">
						<div class="off" title="'.$lng['w']['chng_pos'].'">'.$lng['w']['chng_pos'].'</div>
						<div class="on" title="'.$lng['w']['acpt_chng'].'">'.$lng['w']['acpt'].'</div>
					</div>
					
				</div>
			</div>
		</div>';
		
		$rtrn .= '
		<div class="main_info">
			<h3 class="ttl gr_info ghost">'.$lng['w']['main'].'</h3>';
			//---CHECK BOXES---
			$rtrn .= '<div class="checks_cont">';
				$rtrn .= '<label><input type="checkbox" name="gift" class="no_need" tabindex="1" onchange="this.value = +this.checked;" '.($r['gift'] == 1 ? ' checked="checked" value="1" ' : ' value="0" ').'> Cadou</label>';
				$rtrn .= '<label><input type="checkbox" name="soon" class="no_need" tabindex="1" onchange="this.value = +this.checked;" '.($r['soon'] == 1 ? ' checked="checked" value="1" ' : ' value="0" ').'> '.$lang_soon.'</label>';
				$rtrn .= '<label><input type="checkbox" name="tva" class="no_need" tabindex="1" onchange="this.value = +this.checked;" '.($r['tva'] == 1 ? ' checked="checked" value="1" ' : ' value="0" ').'> TVA</label>';
				$rtrn .= '<label><input type="checkbox" name="top" class="no_need" tabindex="1" onchange="this.value = +this.checked;" '.($r['top'] == 1 ? ' checked="checked" value="1" ' : ' value="0" ').'> '.$lang_top_sales.'</label>';
				$rtrn .= '<label><input type="checkbox" name="n_a" class="no_need" tabindex="1" onchange="this.value = +this.checked;" '.($r['n_a'] == 1 ? ' checked="checked" value="1" ' : ' value="0" ').'> '.$lang_not_av.'</label>';
			$rtrn .= '</div>';
			
			//---TIMER---
			$prc_t = $r['prc_t']>time() ? date('Y-m-d', $r['prc_t']) : 0;
			$prc_n = $r['prc_t']>time() ? $r['prc_n'] : $r['prc'];
			$rtrn .= '
			<input type="date" class="timer_end in_icon timer no_need" name="prc_t" value="'.$prc_t.'" min="'.date('Y-m-d').'" title="End of Timer">
			<input type="text" class="timer_price in_icon money no_need" name="prc_n" value="'.$prc_n.'" title="Special Price" placeholder="'.$r['prc_n'].'">';
			
			//---GROUP---
			$rtrn .= '<select class="group need" name="gr" tabindex="1" title="'.mb_strtoupper($lng['w']['group'], "UTF-8").'">';
			foreach ($lng['l']['car']['gr'] as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['gr']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
			
			//---BRAND---
			$rtrn .= '<select class="brand need" name="br" tabindex="1">';
			foreach ($it_br as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['br']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
		
			//---MODEL---
			$rtrn .= '<select class="model need" name="mo" def_text="'.mb_strtoupper($lng['l']['car']['spec']['mo'], "UTF-8").'" tabindex="2">';
			foreach ($c_model as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['mo']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
			
			//---YEAR---
			$rtrn .= '<input class="year need nmb" name="yr" size="16" tabindex="3" placeholder="'.mb_strtoupper($lang_year, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_year, "UTF-8").'" value="'.$r['yr'].'">';
		
			//---BODYTYPE---
			$rtrn .= '<select class="bodytype need" name="bt" tabindex="4">';
			foreach ($lng['l']['car']['bt'] as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['bt']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
			
			//---SEATS---
			$rtrn .= '<input class="seats need nmb" type="text" name="sts" tabindex="12" placeholder="'.mb_strtoupper($lng['l']['car']['spec']['sts'], "UTF-8").'" title="'.mb_strtoupper($lng['l']['car']['spec']['sts'], "UTF-8").'" value="'.$r['sts'].'">';
			
			//---MILEAGE---
			$rtrn .= '<input class="mileage need nmb" name="mlg" size="11" tabindex="5" placeholder="'.mb_strtoupper($lang_mileage, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_mileage, "UTF-8").'" value="'.$r['mlg'].'">';
		
			//---KM_OR_MI---
			$rtrn .= '<select class="km_or_mi need" name="unit" tabindex="6">';
			foreach ($info_km_or_mi as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['unit']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
		
			//---ENGINE---
			$rtrn .= '<input class="engine need nmb" name="vol" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_engine, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_engine, "UTF-8").'" value="'.$r['vol'].'">';
			
			//---HP---
			$rtrn .= '<input class="hp need nmb" name="hp" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_hp, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_hp, "UTF-8").'" value="'.$r['hp'].'">';
		
			//---FUEL---
			$rtrn .= '<select class="fuel need" name="fl" tabindex="8">';
			foreach ($lng['l']['car']['fl'] as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['fl']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
		
			//---TRANSMISSION---
			$rtrn .= '<select class="transmission need" name="tra" tabindex="9">';
			foreach ($lng['l']['car']['tra'] as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['tra']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
		
			//---WHEEL DRIVE---
			$rtrn .= '<select class="wheel_drive need" name="wd" tabindex="10">';
			foreach ($lng['l']['car']['wd'] as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['wd']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
		
			//---COLOR---
			$rtrn .= '<select class="color need" name="clr" tabindex="11">';
			foreach ($lng['l']['car']['clr'] as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['clr']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
			
			//---VIN CODE---
			$rtrn .= '<input class="vin no_need" name="vin" size="16" tabindex="11" placeholder="VIN КОД" type="text" title="VIN КОД" value="'.$r['vin'].'">';
			
			//---LOCATION---
			$rtrn .= '<select class="location need" name="loc" title="'.$lng['w']['address'].'" tabindex="12">';
			foreach ($lng['t']['x']['address'] as $k => $v){ $v = ($k==0) ? $lng['w']['not_sel'] : $v; $rtrn .= '<option value="'.$k.'" '.($k==$r['loc']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
			
		$rtrn .= '
		</div>';
		
		$rtrn .= '
		<div class="add_info">';
			
			//--------------------------------------THIS ITEM
			$s_ar = array();
			$pdo = $db->prepare('SELECT * FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `tp`="item" AND `p1`="cars" AND `it_id`=:it_id ');
			$pdo->execute(array('it_id'=>$_POST['id']));
			foreach ($pdo as $rs){
				$s_ar[ $rs['lng'] ] = array( 'title'=>$rs['ttl'], 'h1'=>$rs['h1'], 'description'=>$rs['dsc'], 'keywords'=>$rs['kwd'] );
			}
			
			//---SEO---
			$rtrn .= '
			<div class="seo">
				<div class="button">SEO</div>
				<div class="content">';
					foreach($lang_arr as $v){
						$rtrn .= '
						<label class="'.$v.'" data-changed="0">
							<div class="lang_txt">'.mb_strtoupper($v, "UTF-8").'</div>
							<input class="change_checker no_need" name="seo_changed_'.$v.'" type="hidden" value="0">
							<input class="title no_need" name="title_'.$v.'" size="11" tabindex="1" placeholder="'.mb_strtoupper($adm_lang['title'], "UTF-8").'" type="text" title="'.mb_strtoupper($adm_lang['title'], "UTF-8").'" value="'.$s_ar[$v]['title'].'">
							<input class="meta_desc no_need" name="meta_desc_'.$v.'" size="11" tabindex="1" placeholder="'.mb_strtoupper($adm_lang['meta_desc'], "UTF-8").'" type="text" title="'.mb_strtoupper($adm_lang['meta_desc'], "UTF-8").'" value="'.$s_ar[$v]['description'].'">
							<input class="h1 no_need" name="h1_'.$v.'" size="11" tabindex="1" placeholder="H1" type="text" title="H1" value="'.$s_ar[$v]['h1'].'">
							<input class="h2 no_need" name="h2_'.$v.'" size="11" tabindex="1" placeholder="H2" type="text" title="H2" value="'.$s_ar[$v]['h2'].'">
							<input class="meta_key no_need" name="meta_key_'.$v.'" size="11" tabindex="1" placeholder="'.mb_strtoupper($adm_lang['meta_key'], "UTF-8").'" type="text" title="'.mb_strtoupper($adm_lang['meta_key'], "UTF-8").'" value="'.$s_ar[$v]['keywords'].'">
						</label>
						';
					}
					$rtrn .= '
				</div>
			</div>';
			
			//---COMMENT---
			$rtrn .= '
			<div class="txt" '.($_COOKIE['usr']=='ciumasu'&&$r['loc']==1?' style="display:none;"':'').'>
				<div class="button">'.$lng['w']['comment'].'</div>
				<div class="content">
					<label class="comment" data-changed="0">
						<textarea class="comment no_need" name="txt" size="11" tabindex="1" placeholder="'.$lng['w']['comment'].'" title="'.$lng['w']['comment'].'">'.$r['txt'].'</textarea>
					</label>
				</div>
			</div>';
			
		$rtrn .= '	
		</div>';
		
		$rtrn .= '<div class="price_info">';
			//---PRICE---
			$rtrn .= '<input class="price need nmb" type="text" name="prc" tabindex="16" placeholder="'.mb_strtoupper($lang_price, "UTF-8").'" title="'.mb_strtoupper($lang_price, "UTF-8").'" value="'.$r['prc'].'">';
		
			//---CURRENCY---
			$rtrn .= '<select class="currency need" name="cur" tabindex="17">';
			foreach ($lng['l']['cur'] as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k==$r['cur']?'selected':'').'>'.$v.'</option>'; }
			$rtrn .= '</select>';
		$rtrn .= '</div>';
		
		//---CONFIRM---
		$rtrn .= '<div class="confirm" data-fn="edit">'.mb_strtoupper($lang_confirm, "UTF-8").'</div>
	</div>';
		
}
elseif ( $_POST['sub']=='make_it' ){//*************************************************************************************************************** MAKE IT CARD
	$last_id = $_POST['last_id'];
	
	//$pdo = $db->prepare('UPDATE '.$prefx.'_car_ctlg SET `vis`="1" WHERE `id`=:id AND `act`="1"'); $pdo->execute(['id'=>$last_id]);
	
	//________________ MAKE ITEM BOX ________________
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id AND `act`="1" '); $pdo->execute(['id'=>$last_id]);
	
	foreach ($pdo as $r){
	//$r_cnt = mysqli_num_rows($result);
		$on_img =  $r['gift']==1 ? '<span class="top">Cadou</span>' : '';
		$on_img .= $r['tva']==1  ? '<span class="tva">TVA</span>' : '';
		$on_img .= $r['soon']==1 ? '<span class="soon" '.($_COOKIE['lang']=='ru'?'style="order:99;"':'').'>'.$lng['l']['stat']['soon1'].'</span>' : '';
		$on_img .= $r['n_a']==1  ? '<span class="not_av">'.$lng['l']['stat']['n_a1'].'</span>' : '';
		$on_img .= $r['top']==1  ? '<span class="top">'.$lng['l']['stat']['top1'].'</span>' : '';
		
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`= :it_id AND `main`="1"');
		$pdo->execute([ 'it_id' => $r['id'] ]);
		
		foreach ($pdo as $p){ $p_nm = $p['name']; $p_ff = $p['ff']; }
		
		$stts = ($r['vis']==0?' hided':'').($r['act']==0?' deleted':'');
		
		$z_msg = ( empty($r['txt']) || !trim($r['txt']) ) ? '' : 'act';
		$z_loc = ( in_array($user_login, ['comerzan']) ) ? 2 : 1;
		$av_k = ['id'=>0, 'br'=>0, 'mo'=>0, 'br_nm'=>1, 'mo_nm'=>1, 'yr'=>0, 'bt'=>0, 'mlg'=>1, 'unit'=>0, 'vol'=>0, 'hp'=>0, 'fl'=>0, 'tra'=>0, 'wd'=>0, 'sts'=>0, 'clr'=>0, 'prc'=>1, 'cur'=>1];
		
		$rtrn .= '
		<div class="bx'.$stts.'" data-id="'.$r['id'].'">
			<div class="icon comment '.$z_msg.'" title="'.$lng['w']['comment'].'"></div>
			<textarea class="comment_txt">'.$r['txt'].'</textarea>
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
					$rtrn .= '
					<div class="btn" data-fn="edit" title="'.$lng['adm']['edit'].'"> <div></div> </div>
					<div class="btn fn_av" data-fn="'.($r['n_a']==0?'av0':'av1').'" title="'.($r['n_a']==0?'-':'+').'" data-alt="'.($r['n_a']==0?'+':'-').'"> <div></div> </div>
					<div class="btn fn_hr" data-fn="'.($r['vis']==0?'reveal':'hide').'" title="'.$lng['adm'][($r['vis']==0?'reveal':'hide')].'" data-alt="'.$lng['adm'][($r['vis']==0?'hide':'reveal')].'" data-fn> <div></div> </div>
					<div class="btn fn_dre" data-fn="delete" title="'.$lng['adm']['delete'].'"> <div></div> </div>';
				} elseif ( $r['act'] == 0 ){
					$rtrn .= '
					<div class="btn fn_dre" data-fn="restore" title="'.$lng['adm']['restore'].'"> <div></div> </div>
					<div class="btn fn_dre" data-fn="erase" title="'.$lng['adm']['delete'].'"> <div></div> </div>';
				}
			$rtrn .= '
			</div>
			
			<div class="base_info">
				<div class="id" title="id">'.$r['id'].'</div>
				<div class="author" title="author">'.$r['author'].'</div>
				<div class="views" title="views"> '.$r['views'].' <div class="img"></div> </div>
				<div class="date" title="'.date('H:i:s', $r['date']).'">'.date('d.m.Y', $r['date']).'</div>
			</div>
			
			<div class="img" style="background-image:url(/'._CAR_IMG.'/'.$r['p_path'].'/'.$r['id'].'/med/'.$p_nm.$img_frmt.'), url(/media/images/site/no_image.png);">';
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
	
	//________________ RETURN ________________
	$rtrn = [ 'bx_id'=>$_POST['bx_id'], 'bx'=>$rtrn ];
}
elseif ( $_POST['sub']=='pos_img' ){//*************************************************************************************************************** SET POSITION FOR IMGs
	if ( count($_POST['pos_img']) !== 0 ){
		foreach ($_POST['pos_img'] as $pos => $id){
			$pdo = $db->prepare('UPDATE '.$prefx.'_car_pht SET `pos`=:pos WHERE `id`=:img_id AND `it_id`=:it_id'); $pdo->execute([ 'it_id'=>$_POST['bx_id'], 'img_id'=>$id, 'pos'=>$pos ]);
		}
	}
}