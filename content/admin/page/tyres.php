<?php defined( '_DOIT' ) or die( 'Restricted access' );

if ( isset($t_mp[4]) ){
	$rtrn = '';
	if ( $t_mp[4]=='ctlg'){
		//$i_max = $isMobile=='1' ? 25 : 49;
		$i_max = $isMobile=='1' ? 999 : 999;
		
		$query_args = [];
		$sql = 'SELECT * FROM '.$prefx.'_tyre_ctlg WHERE 1=1 AND `act`="1" ';
		$sql .= ' ORDER BY `id` DESC';
		$sql .= ' LIMIT '.($i_max+1);
		
		$i = 0;
		
		$rtrn = '
		<div class="ctlg_dspl_tp"></div>
		<section class="ctlg">';
		
		$pdo = $db->prepare($sql);
		$pdo->execute();
		
		$rtrn .= '
		<div id="add_new" class="bx" title="'.$lng['adm']['add'].'"> <div></div> </div>
		
		<style>
			.bx:hover > .adm_menu > .edit, .bx:hover > .adm_menu > .hide {height:50%; background-size:auto 25%; float:left;}
			.bx:hover > .adm_menu > .erase {float:right;}
		</style>
		';
		
		foreach ($pdo as $r){
			$on_img = '';
			//$on_img =  $r['gift']==1  ? '<span class="top">Cadou</span>' : '';
			//$on_img .=  $r['tva']==1  ? '<span class="tva">TVA</span>' : '';
			//$on_img .= $r['soon']==1 ? '<span class="soon" '.($_COOKIE['lang']=='ruXXXXXXX'?'style="order:1;"':'').'>'.$lng['l']['stat']['soon1'].'</span>' : '';
			$on_img .= $r['n_a']==1  ? '<span class="not_av">'.$lng['l']['stat']['n_a1'].'</span>' : '';
			//$on_img .= $r['top']==1  ? '<span class="top">'.$lng['l']['stat']['top1'].'</span>' : '';
			
			$new_item = 0;
			if ($r['new']!=''&&$r['new']=='1'){
				if ( ( time() - $r['date'] ) > 2678400 ){//30days
					$pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `new`=0 WHERE `id`=:id');
					$pdo->execute([ 'id' => $r['id'] ]);
				}
				else {$new_item = 1;}
			}
			
			$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_pht WHERE `it_id`=:it_id AND `main`="1"');
			$pdo->execute([ 'it_id' => $r['id'] ]);
			
			foreach ($pdo as $p){ $p_nm = $p['name']; $p_ff = $p['ff']; }
			
			$stts = $r['vis']==0 ? ' hided' : '' ;
			$diam_c = $r['c']==0 ? '' : 'C';
			//$stts = ($r['vis']==0?' hided':'').($r['act']==0?' deleted':'');
			
			$z_msg = ( empty($r['txt']) || !trim($r['txt']) ) ? '' : 'act';
			//$z_loc = ( in_array($user_login, ['comerzan']) ) ? 2 : 1;
			$av_k = ['id'=>0, 'br'=>0, 'mo'=>0, 'br_nm'=>1, 'mo_nm'=>1, 'w'=>0, 'h'=>0, 'd'=>1, 'c'=>0, 'ss'=>0, 'prc'=>1, 'cur'=>1];
			
			$rtrn .= '
			<div class="bx'.$stts.'" data-id="'.$r['id'].'">
				<div class="icon comment '.$z_msg.'" title="'.$lng['w']['comment'].'"></div>
				<textarea class="comment_txt">'.($_COOKIE['usr']=='ciumasu'&&$r['loc']==1?'Informatie restrictionata':$r['txt']).'</textarea>
				
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
				
				<div class="img" style="background-image:url(/'._TYRES_IMG.'/'.$r['p_path'].'/'.$r['id'].'/med/'.$p_nm.($p_ff!=''?'.'.$p_ff:$img_frmt).'), url(/media/images/site/no_image.png);" title="'.$r['br'].' '.$r['w'].'/'.$r['h'].' R'.$r['d'].'">';
					//if($r['top']){$rtrn .= '<div class="top-sales" title="Top Sales">'.$lng['l']['stat']['top1'].'</div>';}
					if( $r['act'] == 0 ){$rtrn .= '<div class="remove_after" timer="'.( $r['del_t']-time() ).'" ra="'.$r['del_t'].'">**, **:**:**</div>';}
					$rtrn .= '
					<a class="url" href="'.$site_url.'/'.$_COOKIE['lang'].'/tyres/'.$r['id'].'" target="_blank" title="To the item page"><div class="ico"></div></a>
					<div class="on_img ghost">'.$on_img.'</div>
				</div>
				
				<div class="nm">
					<span class="br">'.$r['br_nm'].' '.$r['mo_nm'].'</span>
					<span class="mo">'.$r['w'].'/'.$r['h'].' R'.$r['d'].$diam_c.'</span>
				</div>
		
				<div class="info">';
					foreach( [ 'w'=>['x'=>0, 'u'=>'mm'], 'h'=>['x'=>0, 'u'=>'%'], 'd'=>['x'=>0, 'u'=>'"'.$diam_c.''], 'ss'=>['x'=>1] ] as $k => $v ){
						$rtrn .= '
						<div class="it">
							<span class="ttl">'.(isset($lng['l']['tyre']['spec'][$k])?$lng['l']['tyre']['spec'][$k]:strtoupper($k)).': </span>
							<span class="spc"></span>
							<span class="val">'.( $v['x']==0?$r[$k]:(isset($lng['l']['tyre'][$k][$r[$k]])?$lng['l']['tyre'][$k][$r[$k]]:$r[$k]) ).( isset($v['u'])?' '.$v['u']:'' ).'</span>
						</div>';
					}
				$rtrn .= '
				</div>
		
				<div class="prc_wrap">
					<div class="prc" title="'.$lng['w']['prc'].'">'.$r['prc'].' <span>'.$lng['l']['cur'][$r['cur']].'</span></div>
				</div>
			</div>';
			
			$i++;
			if($i==$i_max){break;}
		}
		
		$rtrn .= '
		</section>';
		
		if($i==$i_max){ $rtrn .= '<div id="more_it" data-i="1">'.$lang_more.'</div>'; }
		
		$rtrn .= '
		<div id="it_cnt" data-count="'.($i_max+1).'" data-pos="'.$r['id'].'"></div>';
	}
	elseif ( $t_mp[4]=='br_lst' ){
		$rtrn = '';
		
		$qry_arg = [];
		$sql = 'SELECT * FROM '.$prefx.'_tyre_list ';
		$sql .= ' ORDER BY `br` ASC, `mo` ASC'; //`mo` + 0 ASC
		
		$pdo = $db->prepare($sql);
		$pdo->execute($qry_arg);
		
		/*
		$rtrn .= '<div id="add_new" class="bx" title="'.$lng['adm']['add'].'"> <div></div> </div>';
		$br_l = ''; $br_l_ar = []; $mo_l = ''; $ttl = '';
		foreach ($pdo as $r){
			if ( $br_l != substr($r['br'], 0, 1) ){ $br_l = substr($r['br'], 0, 1); $br_l_ar[] = $br_l; $rtrn .= '<a name="br_l_lst_'.$br_l.'" style="width:97%; display:block; margin:1rem 0; text-align:right; color:var(--clr); font-size:2rem; text-transform:uppercase;">'.$br_l.'</a>'; }
			if ( $ttl != $r['br_nm'] ){ $ttl = $r['br_nm']; $rtrn .= '<div style="width:100%; margin:1rem 0; text-align:center; border-top:1px solid #292929; font-size:1.1rem; color:var(--clr); padding-top:1rem;">'.$r['br_nm'].'</div>'; }
			if ( $mo_l!=substr($r['mo'], 0, 1) ){$mo_l = substr($r['mo'], 0, 1); $rtrn .= '<div style="width:100%; text-transform:uppercase; text-align:center; font-size:.7rem; border-top:1px dashed #ddd; margin-top:1rem;">'.$mo_l.'</div>';}
			$rtrn .= '<span style="color:#ccc; font-size:.7rem;">'.$r['br_nm'].'</span> <span>'.$r['mo_nm'].'</span><br/>';
		}
		
		$rtrn .= 
		'<div id="az_bx" style="position:fixed; right:0; top:10%; display:flex; flex-flow:column wrap; text-transform:uppercase; background-color:#fff9; padding:1rem; text-align:center;">';
			$az_ar = range('a', 'z');
			
			foreach($az_ar as $v){
				$rtrn .= ( in_array($v, $br_l_ar)?'<a href="#br_l_lst_'.$v.'">'.$v.'</a>':'<a class="ghost" style="color:#d5d5d5;">'.$v.'</a>' );
			}
		$rtrn .= 
		'</div>';
		*/
		
		$br_mo_ar = [];
		foreach ($pdo as $r){
			if ( !isset($br_mo_ar[ $r['br'] ]) ){ $br_mo_ar[ $r['br'] ]['br_nm'] = $r['br_nm']; }
			$br_mo_ar[ $r['br'] ]['mo_ar'][ $r['mo'] ] = $r['mo_nm'];
		}
		
		
		
		$rtrn .= '
		<style>
			.pg_ttl {display:inline-block; border-bottom:2px solid; margin-bottom:2rem; padding:0 5rem 0 1rem;}
			
			#br_mo {display:flex; flex-flow:row wrap;}
			#br_mo > .bx {width:14rem;}
			#br_mo > .bx > .ttl {text-align:center;}
			#br_mo > .bx > .lst {width:100%; height:20rem; overflow-y:scroll; padding:0 1rem; border-top:1px solid var(--clr); border-bottom:1px solid #eee; margin-top:.5rem;}
			#br_mo > .bx > .lst .el {width:100%; height:1rem; margin:.5rem 0; cursor:pointer; transition:.2s color;}
			#br_mo > .bx > .lst .el.act,
			#br_mo > .bx > .lst .el:hover {color:var(--clr);}
			#br_mo > .bx > .lst .el.act:before {content:"> ";}
			
			#br_mo > .bx > .btn.add_new {text-align:center; background-color:#eee; margin:1rem 2rem 0; padding:.5rem 1rem; cursor:pointer; transition:.3s;}
			#br_mo > .bx > .btn.add_new:hover {background-color:var(--clr); color:#fff;}
		</style>
		
		<script>
			$(document).ready(function(){
				$("#br_mo > .bx > .lst .el").on("click", function(e){
					if ( !$(this).hasClass("act") && e.target === this ){
						$(this).closest(".lst").find(".el.act").removeClass("act");
						$(this).addClass("act");
						
						if ( $(this).closest(".bx").hasClass("br") ){
							$("#br_mo > .bx.mo > .lst > .gr").addClass("none");
							$("#br_mo > .bx.mo > .lst > .gr[data-par=\""+$(this).data("br")+"\"]").removeClass("none").children(".el.act").removeClass("act");
							$("#br_mo").data({"br":$(this).data("br"), "br_nm":$(this).data("br_nm")}).attr({"data-br":$(this).data("br"), "data-br_nm":$(this).data("br_nm")}).removeAttr("data-mo data-mo_nm")
						}
						else if ( $(this).closest(".bx").hasClass("mo") ){
							$("#br_mo").data({"mo":$(this).data("mo"), "mo_nm":$(this).data("mo_nm")}).attr({"data-mo":$(this).data("mo"), "data-mo_nm":$(this).data("mo_nm")})
						}
					} else {
						return;
					}
				})
				var zzzz = $("#br_mo > .ttlz").text();
				console.log(zzzz)
				console.log( zzzz.toLowerCase().replace(/\ |\-|\//g,"_").replace(/\&/g,"_and_").replace(/\!/g,"I").replace(/\+/g,"_plus_").replace(/\_\_\_|\_\_/g,"_").replace(/^\_|\_$/g,"") )
			})
		</script>
		
		<div class="pg_ttl">ELEMENT LIST EDITOR</div>
		<div id="br_mo">
			<div class="ttlz none">Cadillac ESV/EXT</div>
			<div class="bx br">
				<div class="ttl">Brand</div>
				<div class="lst">';
					foreach( $br_mo_ar as $br => $ar ){
						$rtrn .= '<div class="el" data-br="'.$br.'" data-br_nm="'.$ar['br_nm'].'">'.$ar['br_nm'].'</div>';
					}
				$rtrn .= '
				</div>
				<div class="btn add_new">New</div>
			</div>
			<div class="bx mo">
				<div class="ttl">Model</div>
				<div class="lst">';
					foreach( $br_mo_ar as $br => $ar){
						$rtrn .= '
						<div class="gr none" data-par="'.$br.'">';
							foreach ( $ar['mo_ar'] as $mo => $mo_nm ){
								$rtrn .= '<div class="el" data-mo="'.$mo.'" data-mo_nm="'.$mo_nm.'">'.$mo_nm.'</div>';
							}
						$rtrn .= '
						</div>';
					}
				$rtrn .= '
				</div>
				<div class="btn add_new">New</div>
			</div>
			<div class="bx yr">
				<div class="ttl">Year</div>
				<div class="lst"></div>
				<div class="btn add_new">New</div>
			</div>
			<div class="bx cnfg">
				<div class="ttl">Config</div>
				<div class="lst"></div>
				<div class="btn add_new">New</div>
			</div>
			<div class="bx ngn">
				<div class="ttl">Engine</div>
				<div class="lst"></div>
				<div class="btn add_new">New</div>
			</div>
		</div>';
		
		
	}
	elseif ( $t_mp[4]=='add' ){
		$redirect_url = '/' . $_COOKIE['lang'] . '/' . $admin_dir . '/tyres/ctlg';
		header('Location: ' . $redirect_url);
		exit;
	}
	else {
		$rtrn = '<span class="err">Check the URL</span>';
	}
	echo $rtrn;
}
?>