<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>
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
	if ( !isset($t_mp[3]) || !key_exists( $t_mp[3], $serv_arr ) ){
	
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
					<a href="/'.$_COOKIE['lang'].'/services/'.$k.'">
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
				</div>
				<div class="txt">'.$lang_offers[$k]['name'].'</div>
			</a>
			';
		}*/
	
	}else{
		if ($t_mp[3]!='transportation'){ // credit

            $rtrnCalculatorBlock = "";
		    if($t_mp[2]=='services' && $t_mp[3]=='credit'){ // калькулятор кредита // вызывается в body.php
                $rtrnCalculatorBlock = '
                            <div style="clear: both"> </div>
                            <div class="spc_bx  d_right_b spc_bx_calc_b"> 
                                <div class="calc_head"> '.$lng['w']['calc_title'].' </div>
                                
                                <div class="calc_block_sum">
                                    <!-- заголовок и отображение текущего значения слайдера -->
                                    <div class="calc_inpt_cont">
                                        <div class="calc_ipt_tl">
                                            '.$lng['w']['calc_title_sum_tl'].'
                                        </div>
                                        <div class="calc_inpt_blk">
                                            <!-- отображение текущего значения слайдера -->
                                            <input type="text" id="view_suma_creditului"  class="clacl_inpt_vie">
                                        </div>
                                    </div>
                                    <!-- элемент вызова слайдера -->
                                    <input type="text" id="suma-creditului" >
                                </div>
                                    
                                <div class="calc_block_terms">
                                    <!-- заголовок и отображение текущего значения слайдера -->
                                    <div class="calc_inpt_cont">
                                        <div class="calc_ipt_tl">
                                            '.$lng['w']['calc_title_term_tl'].'
                                        </div>
                                        <div class="calc_inpt_blk">
                                        <!-- отображение текущего значения слайдера -->
                                            <input type="text" id="view_termen_creditului"  class="clacl_inpt_vie">
                                        </div>
                                    </div>
                                        <!-- элемент вызова слайдера -->
                                    <input type="text" id="termen-creditului" name="termen_creditului">
                                </div>
                                
                                <div style="clear: both"> </div>
                                
                                <!-- отображение результатов расчета калькулятора -->
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

			// Încărcăm fișierul de limbă specific pentru asigurare
if ($t_mp[3] == 'insurance') {
			include_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/lang-insurance.php');
			include_once($_SERVER['DOCUMENT_ROOT'] . '/plugins/dev_tools/meta_gen.php');
			echo '
			<img class="m_img" src="/media/images/site/v2/'.$serv_arr[ $t_mp[3] ]['img'].'" />
			<h1>'.$sa['meta']['h1'].'</h1>';
			include_once($_SERVER['DOCUMENT_ROOT'] . '/content/site/page/insurance.php');
		} else {
			// New trade-in page
                        if ($t_mp[3] == 'tradein') {
                                include(_SITE_PAGE.'/new_pages/tradein/tradein.php');
                        } elseif ($t_mp[3] == 'sale') {
                                include(_SITE_PAGE.'/new_pages/sale/sale.php');
                        } else {
                                echo '
                                <img class="m_img" src="/media/images/site/v2/'.$serv_arr[ $t_mp[3] ]['img'].'" />
				<h1>'.$sa['meta']['h1'].'</h1>
				'. $rtrnCalculatorBlock .'
				<h2 class="ttl">'.$lng['p']['services'][ $t_mp[3] ]['ttl'].'</h2>
				<div class="txt">'.(isset($lang_offers[ $t_mp[3] ]['text']) ? $lang_offers[ $t_mp[3] ]['text'] : '').'</div>';
			}
		}
		}
		
		if ($t_mp[3]=='order'){
			$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg ORDER BY `br` ASC');
			$pdo->execute();
			foreach ($pdo as $r){	$c_brand[ $r['br'] ] = $r['br_nm']; }
			
			echo '
			<img src="/media/images/site/services/bnr_'.$t_mp[3].'.jpg" style="width:100%; margin:3rem 0; padding:0 2rem;" />
			
			<div class="func" style="display: none">
				<div class="ready"><b>'./*$gSdk3pF_sent.*/'</b></div>
				<div class="line"></div>
				<select name="brand" class="item brand need" tabindex="1"> <option value="">'.mb_strtoupper($lang_brand, "UTF-8").'</option>';
					foreach ($c_brand as $k => $v){ echo '<option value="'.$k.'">'.$v.'</option>'; }
				echo '
				</select>
				<select name="model" class="item model need" def_text="'.mb_strtoupper($lang_model, "UTF-8").'" tabindex="2"><option value="">'.mb_strtoupper($lang_model, "UTF-8").'</option></select>
				<select name="fuel" class="item fuel need" tabindex="8"> <option value="">'.mb_strtoupper($lang_fuel, "UTF-8").'</option>';
					foreach ($info_fuel as $k => $v){ echo '<option value="'.$k.'">'.$v.'</option>'; }
				echo '
				</select>
				<input name="year" class="item numInput no_need" size="16" tabindex="3" placeholder="'.mb_strtoupper($lang_year, "UTF-8").'" type="number" min="1900" max="'.(date('Y')).'" title="'.mb_strtoupper($lang_year, "UTF-8").'">
				<input name="engine" class="item numInput no_need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_engine, "UTF-8").' cm³" type="number" min="1" max="10000" title="'.mb_strtoupper($lang_engine, "UTF-8").'">
				<textarea tabindex="14" name="xtra_info" class="no_need" cols="84" rows="5" spellcheck="false" placeholder="'.$lang_offers_order_textarea.'"></textarea>
				<input name="name" class="person need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_your_name, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_your_name, "UTF-8").'">
				<input name="phone" class="person numInput no_need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_your_phone, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_your_phone, "UTF-8").'">
				<input name="email" class="person need" size="16" tabindex="7" placeholder="EMAIL" type="text" title="EMAIL">
				<input type="button" value="'.$lang_send.'" class="order_submit">
				<input type="text" name="page" class="need" value="'.$_SERVER['REQUEST_URI'].'" style="display:none;" />
				<div class="line"></div>
			</div>';
					?>
            <script data-b24-form="inline/10/rh1qfd" data-skip-moving="true">
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_10.js');
            </script>
            <?php
		}elseif ($t_mp[3]=='transportation'){

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

		}
		
		if ( in_array($t_mp[3], ['sale', 'credit', 'transportation']) ){
			echo '<img src="/media/images/site/services/bnr_'.$t_mp[3].'.jpg" style="width:100%; margin:3rem 0; padding:0 2rem;" />';
		}
	}
echo '
</div>';

?>