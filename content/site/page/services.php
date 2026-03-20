<?php defined( '_DOIT' ) or die( 'Restricted access' ); 

// If this is a 404 page, show 404 content and exit
if (isset($GLOBALS['page_is_404']) && $GLOBALS['page_is_404'] === true) {
    include(_DEFAULT.'/404.php');
    exit;
}
?>
<style>
.payment-amount {
    color: #000;
}
.payment-number {
    color: #ff0000;
    font-weight: bold;
}
</style>
<?php


//$browserz = get_browser(null, true);
//print_r($browserz);

echo '
<div id="services">';
	if ( !isset($t_mp[3]) ){
		// No service slug provided - show services list
	
		echo '
		<h1 class="ttl">'.( mb_strtoupper( $lng['w']['services'] ) ).'</h1>
		<div class="grp big">
			<a href="/'.$_COOKIE['lang'].'/services/transportation">
				<div class="img" style="background-image:url(/media/images/site/v2/'.$serv_arr['transportation']['img'].');"></div>
				<h3 class="ttl">'.$lng['p']['services']['transportation']['name'].'</h3>
				<div class="txt">'.$lng['p']['services']['transportation']['ttl'].'</div>
			</a>
		</div>
		
		<div class="grp menu">';
			foreach ($serv_arr as $k => $v){
				if ($v['grp']=='menu'){
					echo '
					<a href="/'.$_COOKIE['lang'].'/'.($k=='tradein' ? 'tradein' : 'services/'.$k).'">
						<div class="img" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
						<h3 class="ttl">'.$lng['p']['services'][ $k ]['name'].'</h3>
						<div class="txt">'.$lng['p']['services'][ $k ]['ttl'].'</div>
					</a>';
				}
			}
		echo '
		</div>
		
		<!--
		<h2 class="ttl">'.( mb_strtoupper( $lng['w']['calculate'] ) ).'</h2>
		<div class="grp calc">';
			foreach ($serv_arr as $k => $v){
				if ($v['grp']=='calc'){
					echo '
					<a href="/'.$_COOKIE['lang'].'/services/'.$k.'">
						<div class="img" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
						<h3 class="ttl">'.$lng['p']['services'][ $k ]['name'].'</h3>
						<div class="txt">'.$lng['p']['services'][ $k ]['ttl'].'</div>
					</a>';
				}
			}
		echo '
		</div>
		-->
		
		<h2 class="ttl">'.( mb_strtoupper( $lng['w']['information'] ) ).'</h2>
		<div class="grp info">';
			foreach ($serv_arr as $k => $v){
				if ($v['grp']=='info'){
					echo '
					<a href="/'.$_COOKIE['lang'].'/services/'.$k.'">
						<div class="img" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
						<h3 class="ttl">'.$lng['p']['services'][ $k ]['name'].'</h3>
						<div class="txt">'.$lng['p']['services'][ $k ]['ttl'].'</div>
					</a>';
				}
			}
		echo '
		</div>
		
		';
	
	
	
		/*foreach( $serv_arr as $k => $v ){
			echo '
			<a class="menu" href="/'.$_COOKIE['lang'].'/services/'.$k.'">
				<div class="img">
					<div class="def" style="background-image:url(/media/images/site/v2/'.$v['img'].');"></div>
			';
		}*/
	
	}else{
		// Service slug provided (404 check already done in body.php)
		if ($t_mp[3]!='transportation'){ // credit

            $rtrnCalculatorBlock = "";
		    if($t_mp[2]=='services' && $t_mp[3]=='credit'){ // credit calculator // called in body.php
                $rtrnCalculatorBlock = '
                            <div style="clear: both"> </div>
                            <div class="spc_bx  d_right_b spc_bx_calc_b"> 
                                <div class="calc_head"> '.$lng['w']['calc_title'].' </div>
                                
                                <div class="calc_block_sum">
                                    <!-- title and display of current slider value -->
                                    <div class="calc_inpt_cont">
                                        <div class="calc_ipt_tl">
                                            '.$lng['w']['calc_title_sum_tl'].'
                                        </div>
                                        <div class="calc_inpt_blk">
                                            <!-- display current slider value -->
                                            <input type="text" id="view_suma_creditului"  class="clacl_inpt_vie">
                                        </div>
                                    </div>
                                    <!-- slider element -->
                                    <input type="text" id="suma-creditului" >
                                </div>
                                    
                                <div class="calc_block_terms">
                                    <!-- title and display of current slider value -->
                                    <div class="calc_inpt_cont">
                                        <div class="calc_ipt_tl">
                                            '.$lng['w']['calc_title_term_tl'].'
                                        </div>
                                        <div class="calc_inpt_blk">
                                            <!-- display current slider value -->
                                            <input type="text" id="view_termen_creditului"  class="clacl_inpt_vie">
                                        </div>
                                    </div>
                                        <!-- slider element -->
                                    <input type="text" id="termen-creditului" name="termen_creditului">
                                </div>
                                
                                <div style="clear: both"> </div>
                                
                                <!-- display calculator results -->
                                <div class="calc_btt_word">
                                    <div class="calc_btt_left">
                                        '.$lng['w']['calc_title_rata'].'
                                    </div>
                                    <div class="calc_btt_right">
                                        <div class="calc_btt_r1">
                                            <span class="calc_btt_r1_nrl">60</span> '.$lng['w']['calc_title_luni'].'
                                        </div>
                                        <div class="calc_btt_r2">
                                            '.'<div class="payment-amount">'.$lng['w']['calc_title_plata'].' <span class="payment-number calc_btt_r2_nrl">0</span> '.$lng['w']['calc_title_plata2'].' <span class="payment-number calc_btt_r3_nrl">0</span></div>'.'
                                        </div>
                                    </div>
                                </div> 
                                
                                <div style="clear: both"> </div>
                                
                            </div> ';
                /*
                 * <div class="calc_btn_btt">
                                    <div class="calc_btn_point">
                                        '.$lng['w']['calc_title_btn'].'
                                    </div>
                                </div>
                 * */
            }

			// Load language file specific for insurance
if ($t_mp[3] == 'insurance') {
			include_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/lang-insurance.php');
			include_once($_SERVER['DOCUMENT_ROOT'] . '/plugins/dev_tools/meta_gen.php');
			echo '
			<img class="m_img" src="/media/images/site/v2/'.$serv_arr[ $t_mp[3] ]['img'].'" />
			<h1>'.$sa['meta']['h1'].'</h1>';
			include_once($_SERVER['DOCUMENT_ROOT'] . '/content/site/page/insurance.php');
		} elseif ($t_mp[3] == 'tradein') {
			$lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
			header('Location: /' . $lang . '/tradein', true, 301);
			exit;
		} elseif ($t_mp[3] == 'sale') {
			include(_SITE_PAGE.'/new_pages/sale/sale.php');
		} elseif ($t_mp[3] == 'order') {
			include(_SITE_PAGE.'/new_pages/order/order.php');
		} elseif ($t_mp[3] == 'transportation') {

			$transportPhone = \App\Helper\PhoneHelper::getGeneralPhone();
			$formattedTransportPhone = \App\Helper\PhoneHelper::formatPhone($transportPhone, 'display');
			
			echo '
			<div class="transportation">
				<h1>'.$lng['t']['services']['transportation']['name'].'</h1>
				<a href="tel:'.$transportPhone.'" class="trnsprt_call" title="'.$lng['w']['call'].'">'.$formattedTransportPhone.'</a>
				<img class="m_img" src="/media/images/site/v2/transportation_img.jpg" />
				<h2>'.$lng['t']['services']['transportation']['ttl_1'].'</h2>
				<div class="txt">'.$lng['t']['services']['transportation']['txt_1'].'</div>
				
				<div class="ln"></div>
				
				<div class="blk">
					<h2>'.$lng['t']['services']['transportation']['ttl_2'].'</h2>
					'.$lng['t']['services']['transportation']['txt_2'].'
					<div class="abv">'.$lng['t']['services']['transportation']['txt_3'].'</div>
				</div>
				<div class="call_now">
					<img src="/media/images/site/call_now.jpg" />
					<h3>'.$lng['t']['services']['transportation']['txt_4'].'<br/><a href="tel:'.$transportPhone.'">'.$formattedTransportPhone.'</a></h3>
				</div>
			</div>';

		} else {
			echo '
			<img class="m_img" src="/media/images/site/v2/'.$serv_arr[ $t_mp[3] ]['img'].'" />
			<h1>'.$sa['meta']['h1'].'</h1>
			'.$rtrnCalculatorBlock.'
			<h2 class="ttl">'.$lng['p']['services'][ $t_mp[3] ]['ttl'].'</h2>
			<div class="txt">'.(isset($lang_offers[ $t_mp[3] ]['text']) ? $lang_offers[ $t_mp[3] ]['text'] : '').'</div>';
		}
	
	if ( in_array($t_mp[3], ['credit', 'transportation']) ){
		echo '<img src="/media/images/site/services/bnr_'.$t_mp[3].'.jpg" style="width:100%; margin:3rem 0; padding:0 2rem;" />';
	}
	}
}
echo '
</div>';

?>