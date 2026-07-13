<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;

require_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/includes/contact_form.php');

$rtrn = ''; $card = '';
$cr_lmt = $isMobile=='1' ? 910 : 960;

if ( !isset($t_mp[3]) ){
	if ( !isset($q_mp[1]) ){//_____________BASE CATALOGUE
		$rtrn .= '
		<div class="gr">
			<h1>'.$sa['meta']['h1'].'</h1>
			<div class="cnt list">';
				$card = tyre_card($prefx, $db, $img_frmt, $lng, 'new', $cr_lmt, null); $rtrn .= $card['txt'];
				//$rtrn .= tyre_card($prefx, $db, $img_frmt, $lng, 'archive', $cr_lmt, null);
			$rtrn .= '
			</div>
		</div>
		<div class="gr">
			<h3>'.$lng['t']['x']['our_serv'].'</h3>
			<div class="cnt">';
				foreach ($grp_arr['o_serv'] as $v){
					$rtrn .= '
					<a href="/'.$_COOKIE['lang'].'/services/'.$v['href'].'" class="lnk">
						<img src="/'._SITE_IMG.'/v2/'.$v['img'].'.svg" />
						<h4 class="ttl">'.$v['ttl'].'</h4>
						<p class="txt">'.$v['txt'].'</p>
					</a>';
				}
			$rtrn .= '
			</div>
		</div>';
	}else{//_____________FILTERED CATALOGUE
		if ( isset($_GET['tg']) && $_GET['tg']=='fltr' ){
			$rtrn .= '
			<div class="gr">
				<div class="cnt list">';
					$card = tyre_card($prefx, $db, $img_frmt, $lng, 'fltr', $cr_lmt, $_GET, 'av'); $rtrn .= $card['txt'];
					//$card = tyre_card($prefx, $db, $img_frmt, $lng, 'fltr', $cr_lmt, $_GET, 'na'); $rtrn .= $card['txt'];
				$rtrn .= '
				</div>
			</div>';
		}else{$rtrn .= 'Something wrong with your URL string.';}
	}
}else{
	if ( isset($q_mp[1]) ){//_____________NONE
		
	}else{//_____________ITEM PAGE
		$chkr_av = 0;
		$it_id = toNumber($t_mp[3]);
		
		if ( $it_id > 0 ){
			$spec_ar = array('br', 'mo', 'w', 'h', 'd', 'ss');
			
			$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg WHERE `id`= :id AND `vis`="1" LIMIT 1');
			$pdo->execute(array( 'id' => $it_id ));
			
			foreach ($pdo as $r){
				$chkr_av = 1;
				//Update views
				$pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `views`=`views`+1 WHERE `id`=:id');
				$pdo->execute(array( 'id' => $r['id'] ));
				
				//Collect photos
				$pdo = $db->prepare('SELECT `name`, `main`, `ff` FROM '.$prefx.'_tyre_pht WHERE `it_id`=:it_id'); 
				$pdo->execute(array('it_id'=>$r['id']));
				$img = array();
				$i=2;
				foreach($pdo as $r2){
					if ($r2['main']=='1'){ $img['main'] = $r2['name']; $img['main_ff'] = ($r2['ff']!=''?'.'.$r2['ff']:$img_frmt); $ii = 1;}else{$ii = $i; $i++;}
					$img['all'][$ii] = ['name'=>$r2['name'], 'main'=>$r2['main'], 'ff'=>($r2['ff']!=''?'.'.$r2['ff']:$img_frmt)];
				}
				unset($r2, $ii);
				ksort($img['all']);
				
				$z_stat = '';
				if ( $r['n_a']==0 && $r['act']==1 ){
					$z_stat .= ( $r['soon']==1 ) ? '<div class="stat soon1">'.$lng['l']['stat']['soon1'].'</div>' : '<div class="stat n_a0">'.$lng['l']['stat']['n_a0'].'</div>';
					$z_stat .= ($r['new']==1) ? '<div class="stat new1">'.$lng['l']['stat']['new1'].'</div>' : '';
					//$z_stat .= ($r['top']==1) ? '<div class="stat top1">'.$lng['l']['stat']['top1'].'</div>' : '';
					//$z_stat .= ($r['prc_n']!=0 && $r['prc_t']>time()) ? '<div class="stat prc_n">'.$lng['l']['stat']['prc_n'].'</div>' : '';
				}else{
					$z_stat .= '<div class="stat n_a1">'.$lng['l']['stat']['n_a1'].'</div>';
				}
				
				$rtrn .= '
				<div class="pht_bx">
					<div class="list">
						<div class="phts">';
							$img_cnt = 0;
							foreach($img['all'] as $k => $v){
								$img_cnt++;
								$act = ($v['main']=='1') ? 'act' : '';
								$rtrn .= '<img class="item '.$act.'" alt="'.$r['br_nm'].' '.$r['mo_nm'].' '.$r['w'].'/'.$r['h'].' R'.$r['d'].' id'.$r['id'].' photo #'.$img_cnt.'" data-pos="'.$k.'" src="/media/images/upload/tyres/'.$r['p_path'].'/'.$r['id'].'/med/'.$v['name'].$v['ff'].'" width="100%" height="auto" />';
							}
						$rtrn .= '
						</div>
					</div>
					<div class="big_pht" role="img" aria-label="'.$r['br_nm'].' '.$r['mo_nm'].' '.$r['w'].'/'.$r['h'].' R'.$r['d'].' id'.$r['id'].' large photo" data-pos="1" data-cnt="'.$img_cnt.'" style="background-image:url(/media/images/upload/tyres/'.$r['p_path'].'/'.$r['id'].'/high/'.$img['main'].$img['main_ff'].');" data-src="/media/images/upload/tyres/'.$r['p_path'].'/'.$r['id'].'/high/'.$img['main'].$img['main_ff'].'"></div>
				</div>
				<div class="spc_bx">
					<h1 class="name">'.$r['br_nm'].' '.$r['mo_nm'].' '.$r['w'].'/'.$r['h'].' R'.$r['d'].($r['c']==1?'C':'').'<span class="id">id-'.$r['id'].'</span></h1>
					<div class="status">'.$z_stat.'</div>
					<h2 class="ttl">'.$lng['w']['characteristics'].'</h2>';
					foreach ($spec_ar as $v){
						$v_lng = isset($lng['l']['tyre'][$v][$r[$v]]) ? $lng['l']['tyre'][$v][$r[$v]] : $r[$v];
						$v_lng = $v == 'br' ? $r['br_nm'] : $v_lng;
						$v_lng = $v == 'mo' ? $r['mo_nm'] : $v_lng;
						$v_lng = $v == 'w' ? $r['w'].' mm' : $v_lng;
						$v_lng = $v == 'h' ? $r['h'].'%' : $v_lng;
						$v_lng = $v == 'd' ?$r['d'].'”' : $v_lng;
						$v_lng = $v == 'clr' ? $v_lng.'<span class="crcl" style="background-image:linear-gradient(135deg, '.$clr_arr[$r[$v]].')"></span>' : $v_lng;
						
						if (isset($r[$v])&&$r[$v]!=''){
							$rtrn .= '
							<p class="ar">
								<span class="name">'.$lng['l']['tyre']['spec'][$v].'</span>
								<span class="space"></span>
								<span class="val">'.$v_lng.'</span>
							</p>';
						}
					}
					
					if ( $r['prc_t']!=0 && $r['prc_t']>time() ){
						$prc = $r['prc_n'];
						$o_prc = $r['prc'];
						$o_prc_bl = '<span class="o_val" title="'.$lng['w']['o_prc'].'"><span class="i">'.parseCurr($o_prc).'</span> '.( symb_rplc($r['cur']) ).'</span>';
					}else{
						$prc = $r['prc'];
						$o_prc = 0;
						$o_prc_bl = '';
					}

                    // BITRIX disabled: click/8/v38vrz, click/18/pelh05, click/28/4qgfkc
                    $form = '';
					
					$rtrn .= '
					<div class="prc">
						<div class="cntr" data-prc="'.$prc.'" data-o_prc="'.$o_prc.'">
							<div class="btn mns">-</div>
							<div class="val">1</div>
							<div class="btn pls">+</div>
						</div>
						<span class="val" title="'.$lng['w']['prc'].'"><span class="i">'.parseCurr($prc).'</span> <span class="cur">'.( symb_rplc($r['cur']) ).'</span></span>
						'.$o_prc_bl.'
					</div>
					<div class="doit">
						<a class="btn call" href="tel:'.PhoneHelper::getGeneralPhone().'" title="'.PhoneHelper::formatPhone(PhoneHelper::getGeneralPhone(), 'display').'">'.$lng['w']['call'].'</a>
						
						<div class="btn msg2" onclick="openTyreContactModal()" style="cursor:pointer;padding-top:0;padding-bottom:0;line-height:3rem;">
							'.$lng['w']['message'].'
                        </div>
						
						<div class="btn msg" style="display: none">
							'.$lng['w']['message'].'
							<div class="data" style="display:none;">
								<form id="snd_msg" class="snd_msg" action="" method="post">
									<h3 class="ttl">'.$lng['w']['message'].'</h3>
									<input class="inp use" type="text" name="name" placeholder="'.$lng['w']['ur_name'].'" title="'.$lng['w']['ur_name'].'" />
									<input class="inp use imp" type="text" name="email" placeholder="Email*" title="Email" />
									<input class="inp use imp" type="text" name="phone" placeholder="'.$lng['w']['phone_numb'].'*" title="'.$lng['w']['phone_numb'].'" />
									<textarea class="inp use txt" name="msg" spellcheck="false" placeholder="'.$lng['w']['message'].'" title="'.$lng['w']['message'].'"></textarea>
									<input class="use" type="hidden" name="page" value="'.$_SERVER['REQUEST_URI'].'" />
									<input class="use" type="hidden" name="target" value="overlay" />
									<div class="agmt">
										<input type="checkbox" name="agmt" id="f_agmt" class="cbx cnfrm" checked="checked" />
										<span class="txt"><label for="f_agmt">'.$lng['t']['x']['prs_dat_agr'][1].'</label> <a class="x" href="/'.$_COOKIE['lang'].'/privacy" target="_blank" title="'.$lng['t']['x']['prs_dat_agr']['ttl'].'">'.$lng['t']['x']['prs_dat_agr'][2].'</a></span>
									</div>
									<input class="btn sbmt" type="submit" value="'.$lng['w']['send'].'" onclick="event.preventDefault();" data-sent="'.$lng['w']['msg_snt'].'" data-sending="'.$lng['w']['sending'].'" data-req_fld="'.$lng['w']['req_not_filled'].'" />
								</form>
							</div>
						</div>
					</div>
				</div>
				<!--<div class="inf_bx">
					<div class="menu">
						<div class="btn act" data-name="spec">
							'.$lng['w']['characteristics'].'
							<div class="ln"></div>
						</div>
						<div class="btn" data-name="rev">
							'.$lng['w']['reviews'].'
							<div class="ln"></div>
						</div>
					</div>
					<div class="bx">
						<div class="cnt act" data-name="spec">';
							for ($i=1; $i<2; $i++){
								$rtrn .= 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. ';
							}
						$rtrn .= '
						</div>
						<div class="cnt" data-name="rev">
							Отзывов пока нет.
						</div>
					</div>
				</div>-->
				<div class="smlr gr">
					<h3>'.$lng['w']['smlr_items'].'</h3>
					<div class="cnt">';
						$card = tyre_card($prefx, $db, $img_frmt, $lng, 'smlr', 4, $r); $rtrn .= $card['txt'];
					$rtrn .= '
					</div>
				</div>';
			}
			
			if ( $chkr_av == 1 ){ $rtrn .= '<script> $(document).ready(function(){ $("#crumbs .crnt").text("'.$r['br_nm'].' '.$r['mo_nm'].' '.$r['w'].'/'.$r['h'].' R'.$r['d'].', ['.$r['id'].']"); }) </script>'; }
			else{ 
				// Tyre not found - return 404 instead of showing no_item block
				http_response_code(404);
				include(_DEFAULT.'/404.php');
				exit;
			}
		}
	}
}

echo $rtrn;

// Tyre contact modal
$_tyre_lang = $_COOKIE['lang'] ?? 'ro';
ob_start();
sauto_contact_form(['lang' => $_tyre_lang, 'source' => 'tyres']);
$_tyre_form = ob_get_clean();
echo '
<div id="tyre-contact-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)closeTyreContactModal()">
    <div style="position:relative;background:#fff;padding:2rem;border-radius:12px;max-width:520px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <button onclick="closeTyreContactModal()" style="position:absolute;top:12px;right:12px;background:none;border:none;cursor:pointer;color:#aaa;padding:6px;line-height:1;border-radius:50%;transition:color 0.15s,background 0.15s;" onmouseover="this.style.color=\'#E61E2D\';this.style.background=\'#fff0f0\'" onmouseout="this.style.color=\'#aaa\';this.style.background=\'none\'"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        '.$_tyre_form.'
    </div>
</div>
<script>
function openTyreContactModal(){document.getElementById("tyre-contact-modal").style.display="flex";document.body.style.overflow="hidden";}
function closeTyreContactModal(){document.getElementById("tyre-contact-modal").style.display="none";document.body.style.overflow="";}
</script>
';
?>