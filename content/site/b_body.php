<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;



include(_SITE_INCL.'/functions.php'); ?>

<head>
	<?php include(_SITE.'/head.php'); ?>
</head>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-TP4GJ51GSL"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-TP4GJ51GSL');
</script>

<body class="ffd" <?php /*class="noselect ffd"*/ echo ' data-mbl="'.$isMobile.'" data-lng="'.$_COOKIE['lang'].'"'; ?> data-js="0" data-host="SAUTO">
	
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KRRLB4X" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	
	<?php /*
	<!-- Yandex.Metrika informer --> <a style="display:none;" href="https://metrika.yandex.ru/stat/?id=87984800&amp;from=informer" target="_blank" rel="nofollow"><img src="https://metrika-informer.com/informer/87984800/3_1_FFFFFFFF_EFEFEFFF_0_pageviews" style="width:88px; height:31px; border:0;" alt="Яндекс.Метрика" title="Яндекс.Метрика: данные за сегодня (просмотры, визиты и уникальные посетители)" class="ym-advanced-informer" data-cid="87984800" data-lang="ru" /></a> <!-- /Yandex.Metrika informer --> 
	<!-- Yandex.Metrika counter --> <script type="text/javascript" > (function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.yaCounter87984800 = new Ya.Metrika({ id:87984800, clickmap:true, trackLinks:true, accurateTrackBounce:true, trackHash:true, ecommerce:"dataLayer" }); } catch(e) { } }); var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = "text/javascript"; s.async = true; s.src = "https://cdn.jsdelivr.net/npm/yandex-metrica-watch/watch.js"; if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); } })(document, window, "yandex_metrika_callbacks"); </script> <!-- /Yandex.Metrika counter -->
	*/ ?>
	
	<!--<noscript><iframe src="//www.googletagmanager.com/ns.html?id=GTM-MG9WJ9" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>-->
	
	<div id="cons_bx" class="cons_bx" style="display:none;">
		<div style="max-width:50%; padding:0 1rem 0 0;">
			<p><?php echo $lng['l']['consent']['base_txt'][0].'<span style="border-bottom:1px solid #e2001a;">'.$lng['l']['consent']['acpt_all'].'</span>'.$lng['l']['consent']['base_txt'][1].' <a href="/'.$_COOKIE['lang'].'/privacy" target="_blank" style="color:#000;">"'.$lng['l']['menu']['privacy'].'"</a>'; ?></p>
		</div>
		<div class="cons_btns">
			<button onclick="setConsent(true, true, true, true)"><?php echo $lng['l']['consent']['acpt_all']; ?></button>
			<?php /*<button onclick="setConsent(true, false, false, false)">*/ ?><?php /*echo $lng['l']['consent']['essent'];*/ ?><?php /*</button>*/ ?>
			<button onclick="showPref()"><?php echo $lng['l']['consent']['cstm']; ?></button>
		</div>
	</div>
	
	<div id="pref_bx" class="cons_bx cons_pref" style="display:none;">
		<div style="display:flex; flex-flow:column; gap:10px;">
			<p class="ttl"><?php echo $lng['l']['consent']['cstmztn']; ?></p>
			<label> <span class="txt"><?php echo $lng['l']['consent']['func_ck']; ?></span> <div class="chk_bx def"><div class="dot"></div></div></label>
			<label><input checked="checked" type="checkbox" id="ad-storage" /> <span class="txt"><?php echo $lng['l']['consent']['ad_ck']; ?></span> <div class="chk_bx"><div class="dot"></div></div></label>
			<label><input checked="checked" type="checkbox" id="ad-user-data" /> <span class="txt"><?php echo $lng['l']['consent']['usr_dt_ck']; ?></span> <div class="chk_bx"><div class="dot"></div></div></label>
			<label><input checked="checked" type="checkbox" id="ad-personalization" /> <span class="txt"><?php echo $lng['l']['consent']['prsn_ck']; ?></span> <div class="chk_bx"><div class="dot"></div></div></label>
			<label><input checked="checked" type="checkbox" id="analytics-storage" /> <span class="txt"><?php echo $lng['l']['consent']['ana_ck']; ?></span> <div class="chk_bx"><div class="dot"></div></div></label>
		</div>
		<div class="cons_btns">
			<button onclick="savePref()"><?php echo $lng['l']['consent']['acpt_sel']; ?></button>
			<button onclick="hidePref()"><?php echo $lng['l']['consent']['back']; ?></button>
		</div>
	</div>
	
	<div id="overlay" class="noselect">
		<div class="close"></div> <div class="bg"></div> <div class="content"></div>
	</div>
	
	<?php 
	echo '
	<div id="show_img">
		<div class="status"></div>
		<div class="close"></div>
		<div class="left"></div>
		<div class="right"></div>
	</div>';
	//echo substr( md5('22') , 0, 4 );
	?>
	
	<div id="to_top" title="<?php echo $lang_to_top; ?>"></div>
	
	<header>
		<div class="def">
			<a id="top_logo" href="/<?php echo $_COOKIE['lang']; ?>/" title="<?php echo $lang_top_info; ?>" style="width:12rem;"><img src="/<?php echo _SITE_IMG; ?>/v2/logo_b.svg" alt="<?php echo $lang_main_logo; ?>" width="100%" /></a>
			
			<input type="checkbox" id="mm_cbx" />
			<nav id="main_menu" role="navigation">
				<div class="menu">
					<?php 
					foreach ($menu_arr as $k => $v){
						if ($v == '1'){
							echo '<a class="'.$k.' button '; if( isset($t_mp[2])&&$t_mp[2]==$k ){echo ' active';} echo '" href="/'.$_COOKIE['lang'].'/'.$k.'"><div>'.$lng['l']['menu'][$k].'</div></a>';
						}
					}
					?>
				</div>
				<div class="lang">
					<?php foreach( array_reverse($language) as $k => $v ){ 
						echo '<a href="/'.$k.$lang_mp.'" hreflang="'.$k.'" class="'.$k.' button '; if($t_mp[1]==$k){echo ' active';} echo'" title="'.$v.'"><div>'.strtoupper($k).'</div></a>';
					} ?>
				</div>
				<label for="mm_cbx" class="mm_lb"></label>
			</nav>
			
			<a class="call" <?php 
				if ( isset($t_mp[2])&&$t_mp[2]=='tyres' ){
					$tyresPhone = PhoneHelper::getGeneralPhone();
					echo'href="tel:'.$tyresPhone.'" title="'.PhoneHelper::formatPhone($tyresPhone, 'display').'"';
				} elseif ( isset($t_mp[2])&&$t_mp[2]=='services'&&isset($t_mp[3])&&$t_mp[3]=='order' ) {
					$orderPhone = PhoneHelper::getOrderPhone();
					echo'href="tel:'.$orderPhone.'" title="'.PhoneHelper::formatPhone($orderPhone, 'display').'"';
				} else {
					$generalPhone = PhoneHelper::getGeneralPhone();
					echo'href="tel:'.$generalPhone.'" title="'.PhoneHelper::formatPhone($generalPhone, 'display').'"';
				} ?>>
				<div class="txt"><?php echo $lng['w']['call']; ?></div>
				<div class="img"></div>
			</a>
		</div>
	</header>
	
	<div id="crumbs" data-lng-c="<?php echo $lng['w']['copied']; ?>">
		<?php 
		if ( isset($t_mp[2])&&$t_mp[2]!='' ){
			echo '<a href="/'.$_COOKIE['lang'].'/">'.$lng['w']['home_page'].'</a>';
			$t_2_val = isset( $lng['l']['menu'][ $t_mp[2] ] ) ? $lng['l']['menu'][ $t_mp[2] ] : $t_mp[2];
			
			if ( (isset($t_mp[3])&&$t_mp[3]!='')||(isset( $_GET['tg'] )) ){
				echo ' - <a href="/'.$_COOKIE['lang'].'/'.$t_mp[2].'">'.$t_2_val.'</a>';
				
				
				if ( $t_mp[2]=='services' && isset( $lng['p']['services'][ $t_mp[3] ]['name'] ) ){$t_3_val = $lng['p']['services'][ $t_mp[3] ]['name'];}
				elseif ( isset($_GET['tg'] ) && $_GET['tg']=='fltr' ){$t_3_val = $lng['w']['search'];} // strpos($t_mp[3], 'tg=fltr')
				elseif ( isset($t_mp[3])&&$t_mp[3]!='' ) {$t_3_val = $t_mp[3];}
				else {$t_3_val = '<span>#</span>';}

				echo ' - <span class="crnt cp_url" title="'.($t_mp[2]!='cars'?$lng['w']['copy'].' URL':$lng['w']['fnd_smlr']).'">'.$t_3_val.'</span>';
			}elseif ( isset($t_mp[3])&&$t_mp[3]=='' ){
				echo ' - <span class="crnt cp_url" title="'.$lng['w']['copy'].' URL">#</span>';
			}else{
				echo ' - <span class="crnt cp_url" title="'.$lng['w']['copy'].' URL">'.$t_2_val.'</span>';
			}
		}
		?>
	</div>
	
	<?php 	
	if ( !isset($t_mp[2]) || $t_mp[2]==''){
		echo '
		<div id="ann">';
			include(_SITE_INCL.'/slider.php');
			if ( $isMobile!='1' ){
				include(_SITE_INCL.'/mini.php');
			}
		echo '
		</div>';
	}
	
	if ( !isset($t_mp[2]) || $t_mp[2]=='' || ( ($t_mp[2]=='cars' || $t_mp[2]=='tyres' || $t_mp[2]=='rent') && (!isset($t_mp[3]) || $t_mp[3]=='') ) ){ include(_SITE_INCL.'/filter.php'); }
	?>
	
	<main role="main">
		<?php

/*
        // var_dump( _SITE_PAGE);
        echo "<pre>";
            var_dump( $t_mp);
        echo "</pre>";
        // exit(); */

		if ( !isset($t_mp[2]) || $t_mp[2]=='') {include (_SITE_PAGE.'/home.php');}
		
		elseif ($t_mp[2]=='cars') {include (_SITE_PAGE.'/cars.php');}
		elseif ($t_mp[2]=='services') {include (_SITE_PAGE.'/services.php');}
		elseif ($t_mp[2]=='tyres') {include (_SITE_PAGE.'/tyres.php');}
		elseif ($t_mp[2]=='rent'&&(!isset($t_mp[3])&&!isset($q_mp[1]))) {include (_SITE_PAGE.'/rent.php');}
		elseif ( in_array( $t_mp[2], $info_arr ) ) {include (_SITE_PAGE.'/information.php');}
		elseif ($t_mp[2]=='contacts') {include (_SITE_PAGE.'/contacts.php');}
		
		elseif ($t_mp[2]=='dev_tools'){
			if ( !isset($t_mp[3]) ){echo 'What\'s up, doc?';}
			else {
				if ( $t_mp[3]=='api' ){include ('plugins/dev_tools/api_gen.php');}
				elseif ( $t_mp[3]=='sitemap_generator' ){include ('new_sitemap/sitemap_test_web.php');}
				elseif ( $t_mp[3]=='data_feed' ){include ('plugins/dev_tools/data_feed.php');}
				elseif ( $t_mp[3]=='bnm' ){include ('plugins/dev_tools/bnm.php');}
				elseif ( $t_mp[3]=='bnm_tmp' ){include ('plugins/dev_tools/bnm_tmp.php');}
				elseif ( $t_mp[3]=='devtest_hdsaj21kqiwncac23' ){include ('plugins/dev_tools/devtest.php');}
				elseif ( $t_mp[3]=='games_uwqjshdd204mdk9kladc' ){
					if ( isset($t_mp[4]) && file_exists('plugins/games/'.$t_mp[4].'/index.php') ){ include ('plugins/games/'.$t_mp[4].'/index.php'); }
					else {echo 'Games';}
				}
			}
		}
		
		?>
		<div class="clear"></div>
	</main>
	
	<footer>
		<div class="col logo">
			<a href="/<?php echo $_COOKIE['lang']; ?>/">
				<img src="/<?php echo _SITE_IMG; ?>/v2/logo_w.svg" alt="SAUTO" />
			</a>
			</a>
			<p class="txt"><?php echo $lng['t']['x']['logo_txt']; ?></p>
		</div>
		<?php 
		foreach($foo_arr as $l => $a){
			$zttl = (isset($lng['w'][$l])) ? $lng['w'][$l] : $l;
			echo '
			<div class="col">
				<div class="ttl">'.$zttl.'</div>';
				foreach($a as $k){
					if ($l=='vehicles'){$v=isset($lng['l']['car']['bt'][$k]) ? $lng['l']['car']['bt'][$k] : $k; $k = 'cars?tg=fltr&bt='.$k;}
					elseif ($l=='services'){$v=$lng['p']['services'][$k]['name']; $k='services/'.$k;}
					elseif ($l=='information'){$v=$lng['p']['information'][$k]['name'];}
					elseif ($l=='sitemap'&&$k=='*'){
						$i=1;
						while ( file_exists('sitemap'.$i.'.html') ){
							echo '<a href="/sitemap'.$i.'.html">'.$lng['w']['map'].' #'.$i.'</a>';
							$i++;
						}
						continue;
					}
					//$k = $a=='services'?'services/'.$k:$k;
					echo '<a href="/'.$_COOKIE['lang'].'/'.$k.'">'.$v.'</a>';
				}
			echo '
			</div>';
		}
		?>
		<div class="col cnts">
			<div class="ttl"><?php echo $lng['w']['contacts']; ?></div>
			<?php 
			$generalPhone = PhoneHelper::getGeneralPhone();
			$formattedPhone = PhoneHelper::formatPhone($generalPhone, 'display');
			?>
			<a href="tel:<?php echo $generalPhone; ?>" class="phone"><?php echo $formattedPhone; ?></a>
			<p>
				<?php echo $lng['t']['x']['address'][0].'
				<ul>
					<li><a onclick="navigate(47.03038049808741, 28.855162562579835)" style="cursor:pointer;" >'.$lng['t']['x']['address'][1].'</a>
					<li><a onclick="navigate(47.05765228741261, 28.77507935382036)" style="cursor:pointer;" >'.$lng['t']['x']['address'][2].'</a></li>
				</ul>'; ?>
			</p>
			<p><?php echo $lng['l']['date']['day']['mon']['l'].' - '.$lng['l']['date']['day']['fri']['l'].' 8:00 - 18:00<br/>'.$lng['l']['date']['day']['sat']['l'].' - '.$lng['l']['date']['day']['sun']['l'].' 9:00 - 16:00'; ?></p>
			<a href="mailto:info@sauto.md" class="mail">info@sauto.md</a>
			<p><?php echo $lng['t']['x']['social']; ?></p>
			<div class="sc">
				<?php
				foreach ($sc_ar as $k => $v){
					echo '<a class="'.$k.'" href="'.$v['url'].'" target="_blank" title="'.$v['name'].'" style="background-image:url(/media/images/site/v2/'.$v['img']['w'].');"></a>';
				}
				?>
			</div>
		</div>
	
			<?php 
			//foreach($footer_arr as $k){
				//echo '<a class="'; if( $t_mp[2]==$k ){echo ' active';} echo '" href="/'.$_COOKIE['lang'].'/'.$k.'">'.$lang_xtra_menu[$k].'</a>';
			//}
			?>
		<div id="copyrights"><?php echo date('Y') ?> <span title="Copyrighted">© Sauto S.R.L.</span></div>
	</footer>
	
	<?php 
	if ( (isset($t_mp[2])&&$t_mp[2]=='cars'&&isset($t_mp[3])) && isset($url_id) ) {
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE id=:id'); $pdo->execute(array( 'id' => $url_id ));
		foreach ($pdo as $r){$adw_itemid = $r['id']; $adw_totalvalue = ($r['prc']*1).' '.$r['cur']; $adw_pagetype = 'offerdetail';}
	} elseif ( (isset($t_mp[2])&&$t_mp[2]=='tyres'&&isset($t_mp[3])) && isset($url_id) ) {
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg WHERE id=:id'); $pdo->execute(array( 'id' => $url_id ));
		foreach ($pdo as $r){$adw_itemid = $r['id']; $adw_totalvalue = ($r['prc']*1).' '.$r['cur']; $adw_pagetype = 'offerdetail';}
	} else {
		if ((!isset($t_mp[2])||($t_mp[2]==''||$t_mp[2]=='index.php'))&&in_array($t_mp[1], $lang_arr, true)){$adw_pagetype = 'home';}
		else {$adw_pagetype = 'other';}
		
		$adw_itemid = '';
		$adw_totalvalue = 0;
	}
	
	echo '
	<!-- Google AdWords -->    <!-- NEEEEEEEDS TO CHECK -->
	<script type="text/javascript">
		var google_tag_params = {
			dynx_itemid: "'.$adw_itemid.'",
			dynx_pagetype: "'.$adw_pagetype.'",
			dynx_totalvalue: "'.$adw_totalvalue.'"
		};
	</script>';
	?>
	<script type="text/javascript">
		/* <![CDATA[ */
		var google_conversion_id = 865017510; 
		var google_custom_params = window.google_tag_params;
		var google_remarketing_only = true;
		/* ]]> */
	</script>
	<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js"></script>
	<noscript>
		<div style="display:inline;">
			<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/865017510/?value=0&amp;guid=ON&amp;script=0"/><?php //991949120 ?>
		</div>
	</noscript>
	<!-- End Google AdWords -->
	
</body>

<?php
if($t_mp[2]=='cars' || ($t_mp[2]=='services' && $t_mp[3]=='credit') ) {
    ?>
    <!--Plugin CSS file with desired skin-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/css/ion.rangeSlider.min.css"/>

    <!--Plugin JavaScript file-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/js/ion.rangeSlider.min.js"></script>

    <script>
        $(document).ready(function () {
            let updateRateTimeout;
            let inputSumaTimeout;
            let inputTermenTimeout;

            var $input_suma_creditului = $("#view_suma_creditului");
            // Сумма кредита
            const sliderSuma = $("#suma-creditului").ionRangeSlider({
                skin: "round",
                min: 2000,
                max: 50000,
                from: 2000,
                step: 500,
                onStart: function(data) {
                    $input_suma_creditului.prop("value", data.from);
                },
                onChange: function (data) {
                    $input_suma_creditului.prop("value", data.from);

                    clearTimeout(updateRateTimeout); // сбрасываем предыдущий таймер
                    updateRateTimeout = setTimeout(updateRate, 300); // устанавливаем новый
                    <?php /* console.log("Сумма изменена:", data.from);
                    updateRate(); */ ?>
                }
            }).data("ionRangeSlider");

            var $input_termen_creditului = $("#view_termen_creditului");
            // Срок кредита
            const sliderTermen = $("#termen-creditului").ionRangeSlider({
                skin: "round",
                min: 6,
                max: 60,
                from: 60,
                step: 1,
                onStart: function(data) {
                    $input_termen_creditului.prop("value", data.from);
                },
                onChange: function (data) {
                    $input_termen_creditului.prop("value", data.from);
                    
                    clearTimeout(updateRateTimeout); // сбрасываем предыдущий таймер
                    updateRateTimeout = setTimeout(updateRate, 300); // устанавливаем новый
                    <?php /* console.log("Срок изменен:", data.from);
                    updateRate(); */ ?>
                }
            }).data("ionRangeSlider");

            // Ввод вручную — СУММА
            $input_suma_creditului.on("input", function () {
                clearTimeout(inputSumaTimeout);
                inputSumaTimeout = setTimeout(() => {
                    let val = parseInt($(this).val(), 10);
                    if (isNaN(val)) val = 2000;
                    val = Math.max(2000, Math.min(50000, val)); // ограничение min/max
                    val = Math.round(val / 500) * 500; // округление до ближайшего 500
                    $(this).val(val);
                    sliderSuma.update({ from: val });

                    clearTimeout(updateRateTimeout); // сбрасываем предыдущий таймер
                    updateRateTimeout = setTimeout(updateRate, 300); // устанавливаем новый
                }, 300);
            });

            // Ввод вручную — СРОК
            $input_termen_creditului.on("input", function () {
                clearTimeout(inputTermenTimeout);
                inputTermenTimeout = setTimeout(() => {
                    let val = parseInt($(this).val(), 10);
                    if (isNaN(val)) val = 6;
                    val = Math.max(6, Math.min(60, val));
                    $(this).val(val);
                    sliderTermen.update({ from: val });

                    clearTimeout(updateRateTimeout); // сбрасываем предыдущий таймер
                    updateRateTimeout = setTimeout(updateRate, 300); // устанавливаем новый
                }, 300);
            });

            function updateRate() {
                const suma = $("#suma-creditului").data("ionRangeSlider").result.from;
                const termen = $("#termen-creditului").data("ionRangeSlider").result.from;
                console.log(`Сумма: ${suma}, Срок: ${termen}`);
                $('.calc_btt_r1_nrl').text( termen);

                <?php /* // Тут можешь вызвать свою логику перерасчета RATA LUNARĂ */ ?>

                    const formData = new FormData();
                    formData.append("tp", 'ste');
                    formData.append("fn", 'calculator');
                    formData.append("suma", suma);
                    formData.append("termen", termen);

                    fetch( "/ajax.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(async response => {
                        const data = await response.text(); // response.json()
                        var datajson = JSON.parse( data);

                        console.log( response);
                        console.log( data);
                        console.log( datajson);

                        $('.calc_btt_r2_nrl').text( datajson['min_suma']);
                        $('.calc_btt_r3_nrl').text( datajson['max_suma']);
                    })
                    .catch(error => {
                        console.error("Ошибка:", error);
                    });

            }

            updateRate();
        });
    </script>

<?php } ?>
