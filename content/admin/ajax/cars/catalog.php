<?php defined( '_DOIT' ) or die( 'Restricted access' );

$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1 ';

foreach($arr_types as $k){ if ( isset($_POST[$k.'_search'])&&$_POST[$k.'_search']!='all' ) {$sql .= ' AND `'.$k.'` = :'.$k.''; $query_args[$k] = $_POST[$k.'_search'];} }

//MORE ITEMS------------------------------------------------------------------
if ($_POST['fn']=='more'){
	$sql .= ' AND `id`<=:id ';
	$query_args["id"] = $_POST["it_pos"];
} elseif ($_POST['fn']=='search'){
	$_POST["it_qu"]--;
}

$sql .= ' ORDER BY `id` DESC';
$sql .= ' LIMIT :it_qu';
$query_args["it_qu"] = ($_POST["it_qu"]+1);

$pdo = $db->prepare($sql);
$pdo->execute($query_args);

//if ($_POST['fn']!='more'){ $cars[] = '<div id="add_new" class="car_box" title="Добавить"> <div></div> </div>'; }

$i = 0;
$rtrn = '';
foreach ($pdo as $r){
//$r_cnt = mysqli_num_rows($result);
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
				$rtrn .= '
				<a class="btn edit" href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/cars/detail?id=' . $r['id'].'" title="'.$lng['adm']['edit'].'"> <div></div> </a><!--data-fn="edit"-->
				<div class="btn fn_av" data-fn="'.($r['n_a']==0?'av0':'av1').'" title="'.($r['n_a']==0?'-':'+').'" data-alt="'.($r['n_a']==0?'+':'-').'"> <div></div> </div>';
				if ( in_array($user_type, ['dev', 'sad']) ){
					$rtrn .= '
					<div class="btn fn_hr" data-fn="'.($r['vis']==0?'reveal':'hide').'" title="'.$lng['adm'][($r['vis']==0?'reveal':'hide')].'" data-alt="'.$lng['adm'][($r['vis']==0?'hide':'reveal')].'" data-fn> <div></div> </div>
					<div class="btn fn_dre" data-fn="delete" title="'.$lng['adm']['delete'].'"> <div></div> </div>';
				}
			} elseif ( $r['act'] == 0 && in_array($user_type, ['dev', 'sad']) ){
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

$c_id = $i<=$_POST['it_qu'] ? null : $r['id'];
?>