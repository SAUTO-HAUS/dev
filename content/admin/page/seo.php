<?php defined( '_DOIT' ) or die( 'Restricted access' );

//$trnsltd = ( isset( ${'info_'.$v}[ $row[$v] ] ) && !is_int($row[$v]) ) ? ${'info_'.$v}[ $row[$v] ] : $row[$v];

$content = '';

$trnstn = .25;

$content .= '
<style>
	#s {}
	#s > .new {margin:0 0 10px 0;}
	#s > .new > .b {width:100% !important; text-align:center; overflow:hidden; position:relative; line-height:3.5em !important; border: 1px solid #ccc;}
	#s > .new > .b:hover,
	#s > .new > .chkr:checked ~ .b {background-color:#444;}
	#s > .new > .b > span {display:block; transform-origin:center;}
	#s > .new > .chkr:checked ~ .b > span {font-size:1em; transform:rotate(360deg);}
	#s > .new > .b > .z1 {transition:'.$trnstn.'s;}
	#s > .new > .b:hover > .z1 {color:#fff;}
	#s > .new > .chkr:checked ~ .b > .z1 {color:transparent;}
	#s > .new > .b > .z2 {transition:'.$trnstn.'s; position:absolute; margin:0 auto; top:0; right:0; bottom:0; left:0; color:transparent; transform:rotate(180deg);}
	#s > .new > .chkr:checked ~ .b > .z2 {color:#fff;}
	
	#s > .ii > .i {margin:2px 0 0 0; transition:'.$trnstn.'s; overflow:hidden;}
	#s > .ii > .i.deleted {height:0px !important; transform:scale(0);} /*animation:delete 1s linear 0s 1 forwards;*/
	#s > .ii > .i.created {background-color:#5e3;}
	#s > .ii > .i > .b, #s > .new > .b {width:80%; cursor:pointer; display:block; padding:0 10px; line-height:2.5em; background-color:#ececec; color:#6c6c6c; cursor:pointer; box-sizing:border-box; float:left; transition:'.$trnstn.'s; margin:0 0 2px 0;}
	#s > .ii > .i:hover > .b {background-color:#444; color:#ececec;}
	#s .i > .c {max-height:0vh; width:100%; overflow:hidden; transition:'.$trnstn.'s; transform:scale(0);}
	#s .i > .chkr {display:none;}
	#s .i > .chkr:checked ~ .c {max-height:100vh; border-bottom:1px solid #aaa; padding-bottom:10px; transform:scale(1);}
	#s .i > .c > label {}
	#s .i > .c > label > .lang_txt {width:100%; background-color:#eee; color:#555; text-align:center; box-sizing:border-box; line-height:2em; transition:'.($trnstn/2).'s; float:left;}
	#s .i > .c > label:hover > .lang_txt {background-color:#aa4b4b; color:#ececec;}
	#s .i > .c > label > textarea, #s .i > .c > .new_page {border:none; border-top:1px solid #e6e4e4 !important; float:left; box-sizing:border-box; padding:10px; transition:'.($trnstn/2).'s; resize:none; height:5rem;}
	
	#s .new_title, #s .new_meta_desc, #s .new_h1, #s .new_txt {width:50%;}
	#s .new_title, #s .new_h1 {border-left:1px solid #e6e4e4 !important; border-right:1px solid #e6e4e4 !important;}
	#s .new_meta_desc, #s .new_txt {border-right:1px solid #e6e4e4 !important;}
	#s .new_page {width:100%; border-bottom:1px solid #e6e4e4 !important;}
	
	#s .copy,
	#s .del {width:10%; color:transparent; box-sizing:border-box; position:relative; background-color:#ecebeb; float:left; line-height:2.5em; border-left:1px solid #fff; cursor:pointer; transition:'.$trnstn.'s;}
	#s .copy:hover,
	#s .del:hover {background-color:#c9192d;}
	#s .copy:hover > .icon,
	#s .del:hover > .icon {-webkit-background-color:#fff; background-color:#fff;}
	
	#s .copy > .icon {width:100%; height:100%; 
		-webkit-background-color:#979797; -webkit-mask-image:url("/media/images/site/icon/copy.svg"); -webkit-mask-repeat:no-repeat; -webkit-mask-size:auto 50%; -webkit-mask-position:center; -webkit-backface-visibility:hidden;
		background-color:#979797; mask-image:url("/media/images/site/icon/copy.svg"); mask-repeat:no-repeat; mask-size:auto 50%; mask-position:center;
		display:block; position:absolute; top:0; left:0; transition:'.$trnstn.'s;
	}
	
	#s .del > .icon {width:100%; height:100%; 
		-webkit-background-color:#979797; -webkit-mask-image:url("/media/images/site/delete.svg"); -webkit-mask-repeat:no-repeat; -webkit-mask-size:auto 50%; -webkit-mask-position:center; -webkit-backface-visibility:hidden;
		background-color:#979797; mask-image:url("/media/images/site/delete.svg"); mask-repeat:no-repeat; mask-size:auto 50%; mask-position:center;
		display:block; position:absolute; top:0; left:0; transition:'.$trnstn.'s;
	}
	
	#s .copy.copied {background-color:#86BF6A !important;}
	#s .copy.copied > .icon {-webkit-background-color:#fff; background-color:#fff;}
	
	#s .i > .c > .exec {width:100%; border:none; line-height:3.5em; cursor:pointer; display:block; background-color:#81A86E; color:#fff; margin:10px auto 0; float:left; transition:'.($trnstn/2).'s; opacity:1;}
	#s .i > .c > .exec:hover {background-color:#86BF6A;}
	#s .i > .c > .exec.ghost {opacity:0 !important;}

	@media (max-width:767px), (orientation: portrait), (max-height:500px) and (orientation: landscape) {
		#s > .ii > .i > .b, #s > .new > .b {width:60%; font-size:.75rem; padding:0 5px; line-height:2.2em;}
		#s .copy, #s .del {width:20%; line-height:2.2em;}
		#s .new_title, #s .new_meta_desc, #s .new_h1, #s .new_txt {width:100%;}
		#s .new_title, #s .new_h1 {border-right:1px solid #e6e4e4 !important;}
		#s .i > .c > label > textarea, #s .i > .c > .new_page {width:100%; height:4rem; font-size:.85rem;}
		#s .i > .c > label > .lang_txt {font-size:.8rem;}
		#s .i > .c > .exec {font-size:.85rem; line-height:3em;}
		#s > .new > .b {line-height:3em;}
	}
	
</style>
';

$seo_ar = array();
$pdo = $db->prepare('SELECT * FROM '.$prefx.'_seo2 ORDER BY `it_id` DESC');
$pdo->execute();

$content .= '
<div id="s">
	<div class="i new" data-id="new">
		<input type="checkbox" class="chkr" id="chkbx_add" />
		<label for="chkbx_add" class="b">
			<span class="noselect z1">'.$adm_lang['add'].'</span>
			<span class="noselect z2">'.$adm_lang['close'].'</span>	
		</label>
		<div class="c">
			<input class="new_page collect" name="new_page" size="11" tabindex="1" placeholder="'.mb_strtoupper($adm_lang['page'], "UTF-8").'" type="text" title="'.mb_strtoupper($adm_lang['page'], "UTF-8").'" value="" data-name="page">';
			foreach($lang_arr as $v){
				$content .= '
				<label class="lng_cnt '.$v.'" data-changed="0" data-lang="'.$v.'">
					<div class="lang_txt">'.mb_strtoupper($v, "UTF-8").'</div>
					<input class="change_checker" name="seo_changed_'.$v.'" type="hidden" value="0">
					<textarea class="new_title collect" name="new_title_'.$v.'" tabindex="1" placeholder="Title" title="Title" data-lang="'.$v.'" data-name="title"></textarea>
					<textarea class="new_meta_desc collect" name="new_meta_desc_'.$v.'" tabindex="1" placeholder="Description" title="Description" data-lang="'.$v.'" data-name="description"></textarea>
					<textarea class="new_h1 collect" name="new_h1_'.$v.'" tabindex="1" placeholder="H1" title="H1" data-lang="'.$v.'" data-name="h1"></textarea>
					<textarea class="new_txt collect" name="new_txt_'.$v.'" tabindex="1" placeholder="Text" title="Text" data-lang="'.$v.'" data-name="text"></textarea>
				</label>';
			}
			$content .= '
				<input type="button" class="exec ghost" title="'.$adm_lang['add'].'" value="'.$adm_lang['add'].'" data-item_id="new">
		</div>
	</div>
	<div class="ii">';

		foreach($pdo as $r){
			if (!($r['tp']=='item'&&$r['p1']=='cars')){
				$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ][ $r['lng'] ] = array( 'title'=>$r['ttl'], 'description'=>$r['dsc'], 'h1'=>$r['h1'], 'text'=>$r['txt'] );
				
				if ($r['qr']!=''){
					$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['qr'] = 1;
					$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['pg'] = $r['qr'];
					$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['ttl'] = $r['qr'];
				} else{ 
					$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['qr'] = 0;
					$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['pg'] = ($r['p2']!='') ? $r['p1'].'/'.$r['p2'] : $r['p1'];
					$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['ttl'] = (isset($lng['l']['menu'][$r['p1']])) ? $lng['l']['menu'][$r['p1']] : $r['p1'];
					if (isset($r['p2']) && $r['p2']!=''){
						$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['ttl'] .= (isset($lng['p'][$r['p1']][$r['p2']]['name'])) ? ' - '.$lng['p'][$r['p1']][$r['p2']]['name'] : ' - '.$r['p2'];
					}
				}
				
				$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['p1'] = $r['p1'];
				$seo_ar[ $r['tp'] ][ $r['p1'] ][ $r['mix'] ][ $r['it_id'] ]['tag'] = $r['tp'].' '.$r['p1'].' '.$r['p2'].' '.$r['qr'];
			}
		}
		
		foreach($seo_ar as $tp => $ar){
			$tp_lng = (isset($lng['w'][$tp])) ? $lng['w'][$tp] : $tp;
			$content .= '<div style="width:100%; text-transform:uppercase; margin:4rem 0 1rem;">'.$tp_lng.'</div>';
			
			foreach($ar as $p1 => $ar){
				if ($tp=='fltr'){ $content .= '<div style="width:100%; text-transform:uppercase; margin:1rem 0; font-size:.8rem;">'.$p1.'</div>'; }
				foreach($ar as $mix => $ar){ $mix_lng = ($mix==0) ? 'SOLO' : 'MIXED';
					if ($tp=='fltr'){ $content .= '<div style="width:100%; text-transform:uppercase; margin:1rem 0; font-size:.6rem;">'.$mix_lng.'</div>'; }
					foreach($ar as $id => $v){
						$pn = '';
						$pn_ar2 = array();
						
						if ( $v['qr']==1 ){//----FILTER
							
						}else{//----OTHER
							$trnsltd = ucfirst($v['pg']);
						}
						
						$content .= '
						<div class="i" id="item_'.$id.'" data-grp="'.$seo_ar[$tp][$p1][$mix][$id]['p1'].'">
							<input type="checkbox" class="chkr" id="chkbx_'.$tp.$id.'" />
							
							<label for="chkbx_'.$tp.$id.'" class="b" title="'.$pn.'">'.$v['ttl'].'</label>
							
							<div class="copy" title="'.$adm_lang['copy'].'"><span class="icon"></span>x</div>
							<div class="del" title="'.$adm_lang['delete'].'" data-tp="'.$tp.'" data-p1="'.$p1.'" data-it_id="'.$id.'"><span class="icon"></span>x</div>
							<div class="c">';
								foreach($lang_arr as $v2){
									$content .= '
									<label class="lng_cnt '.$v2.'" data-changed="0" data-lang="'.$v2.'">
										<div class="lang_txt">'.mb_strtoupper($v2, "UTF-8").'</div>
										<input class="change_checker" name="seo_changed_'.$v2.'" type="hidden" value="0">
										<textarea class="new_title collect" name="new_title_'.$v2.'" tabindex="1" placeholder="Title" title="Title" data-lang="'.$v2.'" data-name="title">'.($v[$v2]['title'] ?? '').'</textarea>
										<textarea class="new_meta_desc collect" name="new_meta_desc_'.$v2.'" tabindex="1" placeholder="Description" title="Description" data-lang="'.$v2.'" data-name="description">'.($v[$v2]['description'] ?? '').'</textarea>
										<textarea class="new_h1 collect" name="new_h1_'.$v2.'" tabindex="1" placeholder="H1" title="H1" data-lang="'.$v2.'" data-name="h1">'.($v[$v2]['h1'] ?? '').'</textarea>
										<textarea class="new_txt collect" name="new_txt_'.$v2.'" tabindex="1" placeholder="Text" title="Text" data-lang="'.$v2.'" data-name="text">'.($v[$v2]['text'] ?? '').'</textarea>
									</label>';
								}
								$content .= '
									<input type="button" class="exec ghost" title="'.$adm_lang['apply'].'" value="'.$adm_lang['apply'].'" data-tp="'.$tp.'" data-p1="'.$p1.'" data-it_id="'.$id.'">
							</div>
						</div>';
					}
				}
			}
			
			
			/*
			foreach($ar as $id => $zlang){
				$pn = '';
				$pn_ar2 = array();
				
				$pg = urldecode($zlang['pg']);
			
				if ( strpos($pg, 'tg=filter&') !== false ){//________________________________FILTER
					$pn_ar2 = array();
					$pn = str_replace( 'tg=filter&', '', $pg );
					$pn2 = str_replace('cr[', '', $pn, $count);
					
					if ($count>0){
						$pn2 = str_replace('][', '[', $pn2);
					}
					
					if (strpos($pn, '&') !== false){
						$pn_ar = explode('&', $pn2);
						foreach($pn_ar as $k => $ar){
							$pn_ar['xpld'] = explode('[]=', $ar);
							foreach($pn_ar['xpld'] as $v){
								$pn_ar2[ $pn_ar['xpld'][0] ][ $pn_ar['xpld'][1] ] = $pn_ar['xpld'][1];
							}
						}
					}else{
						$pn_ar = array();
						$pn_ar['xpld'] = explode('[]=', $pn2);
						$pn_ar2[ $pn_ar['xpld'][0] ][ $pn_ar['xpld'][1] ] = $pn_ar['xpld'][1];
					}
					
					$trnsltd = '';
					
					foreach($pn_ar2 as $k => $ar){
						$t_trsl = isset($fltr_types_ar[ 'get' ][ $k ]) ? $fltr_types_ar[ 'get' ][ $k ] : ucfirst($k);
						$trnsltd_key = isset( ${'lang_'.$t_trsl} ) ? ${'lang_'.$t_trsl} : $t_trsl;
						$trnsltd .= ( $trnsltd ? ' | ' : '' ).$trnsltd_key.' : ';
						$n=0;
						foreach($ar as $v){
							$trnsltd_val = isset( ${'info_'.$t_trsl}[ $v ] ) ? ${'info_'.$t_trsl}[ $v ] : ucfirst($v);
							$trnsltd .= ($n==0 ? '' : ', ').$trnsltd_val;
							$n++;
						}
					}
				}elseif ( strpos($pg, '/cars/') !== false || strpos($pg, '/ordercars/') !== false ){//________________________________CAR
					
				}else{//________________________________PAGE
					$trnsltd = ucfirst($zlang['pg']);
				}
				
				$content .= '
				<div class="i" id="item_'.$id.'" data-grp="'.$seo_ar[$tp][$id][ 'grp' ].'">
					<input type="checkbox" class="chkr" id="chkbx_'.$id.'" />
					<label for="chkbx_'.$id.'" class="b" title="'.$pn.'">'.$trnsltd.'</label>
					<div class="copy" title="'.$adm_lang['copy'].'"><span class="icon"></span>x</div>
					<div class="del" title="'.$adm_lang['delete'].'" data-item_id="'.$id.'"><span class="icon"></span>x</div>
					<div class="c">';
						foreach($lang_arr as $v){
							$content .= '
							<label class="lng_cnt '.$v.'" data-changed="0" data-lang="'.$v.'">
								<div class="lang_txt">'.mb_strtoupper($v, "UTF-8").'</div>
								<input class="change_checker" name="seo_changed_'.$v.'" type="hidden" value="0">
								<textarea class="new_title collect" name="new_title_'.$v.'" tabindex="1" placeholder="'.mb_strtoupper($adm_lang['title'], "UTF-8").'" title="'.mb_strtoupper($adm_lang['title'], "UTF-8").'" data-lang="'.$v.'" data-name="title">'.$zlang[$v]['title'].'</textarea>
								<textarea class="new_meta_desc collect" name="new_meta_desc_'.$v.'" tabindex="1" placeholder="'.mb_strtoupper($adm_lang['meta_desc'], "UTF-8").'" title="'.mb_strtoupper($adm_lang['meta_desc'], "UTF-8").'" data-lang="'.$v.'" data-name="description">'.$zlang[$v]['description'].'</textarea>
								<textarea class="new_h1 collect" name="new_h1_'.$v.'" tabindex="1" placeholder="H1" title="H1" data-lang="'.$v.'" data-name="h1">'.$zlang[$v]['h1'].'</textarea>
								<textarea class="new_meta_key collect" name="new_meta_key_'.$v.'" tabindex="1" placeholder="'.mb_strtoupper($adm_lang['meta_key'], "UTF-8").'" title="'.mb_strtoupper($adm_lang['meta_key'], "UTF-8").'" data-lang="'.$v.'" data-name="keywords">'.$zlang[$v]['keywords'].'</textarea>
							</label>';
						}
						$content .= '
							<input type="button" class="exec ghost" title="'.$adm_lang['apply'].'" value="'.$adm_lang['apply'].'" data-item_id="'.$id.'" data-item_grp="'.$zlang['grp'].'">
					</div>
				</div>';
			}*/
		}

	$content .= '
	</div>
</div>';
//var_dump($pn_ar);
echo $content;
unset($seo_ar, $content, $v2);

?>