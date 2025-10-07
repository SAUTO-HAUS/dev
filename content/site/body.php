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
    <div class="close-banner" onclick="setConsent(true, true, true, true);">×</div>
    <div style="display: flex; align-items: center; justify-content: center; flex: 1;">
        <p><?php echo $lng['l']['consent']['base_txt'][0].$lng['l']['consent']['acpt_all'].$lng['l']['consent']['base_txt'][1].' <a href="/'.$_COOKIE['lang'].'/privacy" target="_blank" style="color:#000; border-bottom:1px solid #e2001a;">"'.$lng['l']['menu']['privacy'].'"</a>'; ?></p>
    </div>
    <div class="cons_btns">
        <button onclick="setConsent(true, true, true, true)" style="background: linear-gradient(135deg, #e2001a, #b8001a) !important; color: white !important; border: 1px solid #e2001a !important; padding: 1rem 3rem !important; font-size: 1.2rem !important; display: flex !important; align-items: center !important; justify-content: center !important; line-height: 1 !important; border-radius: 1.5rem !important;"><?php echo $lng['l']['consent']['acpt_all']; ?></button>
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

        <?php
        // Get contextual phone number with car data for car pages
        $carData = null;
        
        // If we're on a car page, get car data BEFORE cars.php loads
        if (isset($t_mp[2]) && $t_mp[2] == 'cars' && isset($t_mp[3])) {
            try {
                if (is_numeric($t_mp[3])) {
                    // Numeric ID format
                    $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_car_ctlg WHERE id = :id AND vis = "1" AND act = "1" LIMIT 1');
                    $pdo->execute(['id' => intval($t_mp[3])]);
                } else {
                    // Brand/model URL format
                    $brand = $t_mp[3];
                    $model = isset($t_mp[4]) ? $t_mp[4] : '';
                    
                    if ($brand && $model) {
                        $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_car_ctlg WHERE br = :brand AND mo = :model AND vis = "1" AND act = "1" ORDER BY id DESC LIMIT 1');
                        $pdo->execute(['brand' => $brand, 'model' => $model]);
                    }
                }
                
                if (isset($pdo) && $pdo->rowCount() > 0) {
                    $carData = $pdo->fetch();
                }
            } catch (Exception $e) {
                // In case of error, carData stays null
            }
        }
        
        $contextualPhone = PhoneHelper::getContextualPhone($t_mp, $carData);
        $formattedPhone = PhoneHelper::formatPhone($contextualPhone, 'display');
        ?>
        <a class="call" href="tel:<?php echo $contextualPhone; ?>" title="<?php echo $formattedPhone; ?>">
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

if ( !isset($t_mp[2]) || $t_mp[2]=='' || ( ($t_mp[2]=='cars' || $t_mp[2]=='tyres' || $t_mp[2]=='rent') && (!isset($t_mp[3]) || $t_mp[3]=='' || (($t_mp[2]=='cars') && isset($t_mp[3]))) ) ){ include(_SITE_INCL.'/filter.php'); }
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
    elseif ($t_mp[2]=='credit') {include (_SITE_PAGE.'/new_pages/credit/credit.php');}
    elseif ($t_mp[2]=='tradein') {include (_SITE_PAGE.'/new_pages/tradein/tradein.php');}
    elseif ($t_mp[2]=='rent'&&(!isset($t_mp[3])&&!isset($q_mp[1]))) {include (_SITE_PAGE.'/rent.php');}
    elseif ( in_array( $t_mp[2], $info_arr ) ) {include (_SITE_PAGE.'/information.php');}
    elseif ($t_mp[2]=='contacts') {include (_SITE_PAGE.'/contacts.php');}
    elseif ($t_mp[2]=='telegram') {include (_SITE_PAGE.'/new_pages/telegram/telegram.php');}

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
    <div class="footer-content">
        <div class="columns-wrapper">
            <!-- First Column Container - Services -->
            <div class="column-container col-1-container">
                <div class="col col-services">
                    <?php
                    foreach($foo_arr as $l => $a){
                        if ($l == 'services') {
                            $zttl = (isset($lng['w'][$l])) ? $lng['w'][$l] : $l;
                            echo '
                                <div class="section">
                                    <div class="ttl">'.$zttl.'</div>';
                            foreach($a as $k){
                                $v=$lng['p']['services'][$k]['name']; 
                                $k='services/'.$k;
                                echo '<a href="/'.$_COOKIE['lang'].'/'.$k.'">'.$v.'</a>';
                            }
                            echo '
                                </div>';
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Second Column Container - Information -->
            <div class="column-container col-2-container">
                <div class="col col-information">
                    <?php
                    foreach($foo_arr as $l => $a){
                        if ($l == 'information') {
                            $zttl = (isset($lng['w'][$l])) ? $lng['w'][$l] : $l;
                            echo '
                                <div class="section">
                                    <div class="ttl">'.$zttl.'</div>';
                            foreach($a as $k){
                                $v=$lng['p']['information'][$k]['name'];
                                echo '<a href="/'.$_COOKIE['lang'].'/'.$k.'">'.$v.'</a>';
                            }
                            echo '
                                </div>';
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Third Column Container - Contacts -->
            <div class="column-container col-3-container">
                <div class="col col-contacts">
                    <div class="ttl"><?php echo $lng['w']['contacts']; ?></div>
                    <?php
                    $generalPhone = PhoneHelper::getGeneralPhone();
                    $formattedGeneralPhone = PhoneHelper::formatPhone($generalPhone, 'display');
                    ?>
                    <a href="tel:<?php echo $generalPhone; ?>" class="phone"><?php echo $formattedGeneralPhone; ?></a>
                    <p class="address">
                        <i class="fa-solid fa-location-dot"></i>
                        <?php echo $lng['t']['x']['address'][0].'<br/>
                            <strong>'.$lng['t']['x']['address'][1].'</strong><br/>
                            <strong>'.$lng['t']['x']['address'][2].'</strong>'; ?>
                    </p>
                    <p class="schedule">
                        <i class="fa-solid fa-clock"></i>
                        <?php echo $lng['l']['date']['day']['mon']['l'].' - '.$lng['l']['date']['day']['fri']['l'].' <strong>8:00 - 18:00</strong><br/>'.$lng['l']['date']['day']['sat']['l'].' - '.$lng['l']['date']['day']['sun']['l'].' <strong>9:00 - 16:00</strong>'; ?>
                    </p>
                    <a href="mailto:info@sauto.md" class="mail">
                        <i class="fa-solid fa-envelope"></i>
                        info@sauto.md
                    </a>
                </div>
            </div>

            <!-- Fourth Column Container - Social Media -->
            <div class="column-container col-4-container">
                <div class="col col-social">
                    <div class="ttl"><?php echo isset($lng['w']['social']) ? $lng['w']['social'] : 'Rețelele sociale'; ?></div>
                    <div class="sc">
                        <?php
                        foreach ($sc_ar as $k => $v){
                            echo '<a class="'.$k.'" href="'.$v['url'].'" target="_blank" title="'.$v['name'].'" style="background-image:url(/media/images/site/social/'.$v['img']['w'].');"></a>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div> <!-- Close columns-wrapper -->
        
        <!-- Logo and description above copyright -->
        <div class="footer-bottom">
            <div class="footer-logo-section">
                <a href="/<?php echo $_COOKIE['lang']; ?>/">
                    <img src="/<?php echo _SITE_IMG; ?>/v2/logo_w.svg" alt="SAUTO" />
                </a>
                <p class="footer-description"><?php echo $lng['t']['x']['logo_txt']; ?></p>
            </div>
            <div id="copyrights"><?php echo date('Y') ?> <span title="Copyrighted"> Sauto S.R.L.</span></div>
        </div>
    </div> <!-- Close footer-content -->

    <?php
    //foreach($footer_arr as $k){
    //echo '<a class="'; if( $t_mp[2]==$k ){echo ' active';} echo '" href="/'.$_COOKIE['lang'].'/'.$k.'">'.$lang_xtra_menu[$k].'</a>';
    //}
    ?>
</footer>

<?php
// Initialize AdWords variables with default values
$adw_itemid = '';
$adw_pagetype = 'other';
$adw_totalvalue = '0';

if (isset($t_mp[2])) {
    if ($t_mp[2]=='cars' && isset($t_mp[3]) && isset($url_id)) {
        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE id=:id');
        $pdo->execute(array('id' => $url_id));
        foreach ($pdo as $r) {
            $adw_itemid = $r['id'];
            $adw_totalvalue = ($r['prc']*1).' '.$r['cur'];
            $adw_pagetype = 'offerdetail';
        }
    } elseif ($t_mp[2]=='tyres' && isset($t_mp[3]) && isset($url_id)) {
        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg WHERE id=:id');
        $pdo->execute(array('id' => $url_id));
        foreach ($pdo as $r) {
            $adw_itemid = $r['id'];
            $adw_totalvalue = ($r['prc']*1).' '.$r['cur'];
            $adw_pagetype = 'offerdetail';
        }
    } elseif ($t_mp[2]=='' || $t_mp[2]=='index.php') {
        if (isset($t_mp[1]) && in_array($t_mp[1], $lang_arr, true)) {
            $adw_pagetype = 'home';
        }
    }
} else {
    if (isset($t_mp[1]) && in_array($t_mp[1], $lang_arr, true)) {
        $adw_pagetype = 'home';
    }
}
?>
<!-- Google AdWords -->
<script type="text/javascript">
    var google_tag_params = {
        dynx_itemid: "<?php echo $adw_itemid; ?>",
        dynx_pagetype: "<?php echo $adw_pagetype; ?>",
        dynx_totalvalue: "<?php echo $adw_totalvalue; ?>"
    };
</script>
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

<?php if (empty($_COOKIE['lang']) || ($_COOKIE['lang'] == 'ru')):?>
    <script>
        (function(w,d,u){
            var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
            var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
        })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_2_ydxvti.js');
    </script>
<?php elseif($_COOKIE['lang'] == 'en'):?>
    <script>
        (function(w,d,u){
            var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
            var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
        })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_6_kv018j.js');
    </script>
<?php elseif($_COOKIE['lang'] == 'ro'):?>
    <script>
        (function(w,d,u){
            var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
            var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
        })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_8_l03bx1.js');
    </script>
<?php endif;?>
</body>

<?php
if(isset($t_mp[2]) && ($t_mp[2]=='cars' || ($t_mp[2]=='services' && isset($t_mp[3]) && $t_mp[3]=='credit')) ) {
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
                    updateRateTimeout = setTimeout(updateRate, 1500); // устанавливаем новый
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
                from: 24,
                step: 1,
                onStart: function(data) {
                    $input_termen_creditului.prop("value", data.from);
                },
                onChange: function (data) {
                    $input_termen_creditului.prop("value", data.from);

                    clearTimeout(updateRateTimeout); // сбрасываем предыдущий таймер
                    updateRateTimeout = setTimeout(updateRate, 1500); // устанавливаем новый
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
                    updateRateTimeout = setTimeout(updateRate, 1500); // устанавливаем новый
                }, 1500);
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
                    updateRateTimeout = setTimeout(updateRate, 1500); // устанавливаем новый
                }, 1500);
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


<?php
if(isset($t_mp[2]) && ($t_mp[2]=='cars' ) ) {
    ?>
    <script>
        function openParamsPopAuto(type) {
            if(type == 'open') {
                $('.block_txt_params_pop').addClass('active');
            }
            else {
                $('.block_txt_params_pop').removeClass('active');
            }
        }
    </script>
    <div class="block_txt_params_pop">
        <?php
        // webs25
        $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_seo2 WHERE `it_id`=:it_id AND lng = :lng LIMIT 1');
        $pdo->execute(['it_id' => $r['id'], 'lng' => $_COOKIE['lang'] ]);
        $rseo = $pdo->fetch();
        // var_dump( $rseo);
        $rseo['params_html'] = (html_entity_decode($rseo['params_html']));
        ?>
        <div class="param_pop_header">
            <div class="blk_pop_logo">
                <img src="/media/images/site/v2/logo_b.svg" alt="Sauto, автомобили из Европы." width="100%">
            </div>

            <div class="block_pop_close" onclick=" openParamsPopAuto('hide') ">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 16 16" data-testid="Curtain__closer" class="IconSvg IconSvg_name_SvgCrossS IconSvg_size_24 Curtain__closer Curtain__closer_small"><path fill="currentColor" d="m6.94 8-4.76 4.77 1.06 1.06L8 9.062l4.76 4.768 1.06-1.06L9.06 8l4.76-4.77-1.06-1.06L8 6.938 3.24 2.17 2.18 3.23z"></path></svg>
            </div>

        </div>

        <?
        $ICON_SVG_PARAMS = [
            /* Год выпуска (календарь) */
            'year' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="4" width="18" height="16" rx="2"></rect>
  <path d="M8 2v4M16 2v4M3 10h18"></path>
</svg>',

            /* Пробег (спидометр/одометр) */
            'mileage' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M20 13a8 8 0 10-16 0"></path>
  <path d="M12 13l3-4"></path>
  <rect x="6" y="14.5" width="12" height="3" rx="1"></rect>
</svg>
SVG
            ,

            /* Объём двигателя (мотор) */
            'engine' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="8" width="13" height="8" rx="2"></rect>
  <path d="M16 10h2l3 3v3h-3"></path>
  <path d="M7 6v2M11 6v2M7 16v2M11 16v2"></path>
</svg>
SVG
            ,

            /* Трансмиссия (двунаправленные стрелки) */
            'transmission' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M7 4v8a3 3 0 003 3h4"></path>
  <path d="M14 4l3 3-3 3"></path>
  <path d="M10 20l-3-3 3-3"></path>
  <path d="M14 15h3v5h-3"></path>
</svg>
SVG
            ,

            /* Тип топлива (колонка) */
            'fuel' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="3" width="10" height="18" rx="2"></rect>
  <path d="M13 7H3"></path>
  <path d="M16 7l3 3v7a2 2 0 01-2 2h-1"></path>
  <path d="M18 13c0-1.5-1-2-2-2"></path>
</svg>
SVG
            ,

            /* Климат-контроль (термометр/снежинка) */
            'climate' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M12 2v8"></path>
  <path d="M9 6h6"></path>
  <circle cx="12" cy="15" r="4"></circle>
  <path d="M12 11v8"></path>
</svg>
SVG
            ,

            /* Круиз-контроль (компас) */
            'cruise' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <circle cx="12" cy="12" r="9"></circle>
  <path d="M15 9l-3 6-3-1.5L15 9z"></path>
</svg>
SVG
            ,

            /* Парктроники (буква P + дуги) */
            'parking' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M6 20V4h6a4 4 0 010 8H6"></path>
  <path d="M17 8.5c1.5 1.2 1.5 3.8 0 5"></path>
  <path d="M19.5 7c2.4 2.2 2.4 6.8 0 9"></path>
</svg>
SVG
            ,

            /* Навигация (пин на карте/стрела) */
            'navigation' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <circle cx="12" cy="10" r="3.5"></circle>
  <path d="M12 21c4-3.8 6-6.8 6-9a6 6 0 10-12 0c0 2.2 2 5.2 6 9z"></path>
</svg>
SVG
            ,

            /* Подогрев сидений (кресло + волны) */
            'heated_seat' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M6 12v3a3 3 0 003 3h7"></path>
  <path d="M8 12V8a2 2 0 012-2h1a2 2 0 012 2v4"></path>
  <path d="M5 7c1 1 1 2 0 3M9 7c1 1 1 2 0 3M13 7c1 1 1 2 0 3"></path>
</svg>
SVG
            ,

            /* Bluetooth */
            'bluetooth' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M7 7l10 10-5 5V2l5 5L7 17"></path>
</svg>',

            /* USB (трезубец) */
            'usb' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M12 3v12"></path>
  <path d="M9 6l3-3 3 3"></path>
  <circle cx="12" cy="18" r="3"></circle>
  <path d="M6 12h3M15 12h3"></path>
</svg>'
        ];
        ?>

        <h1 class="name"> <?=$r['br_nm']?> <?=$r['mo_nm']?> <span class="fl"> <?=$lng['l']['car']['fl'][$r['fl']]?> </span> </h1>
        <div class="block_txt_params">
            <? if(trim($rseo['params_html']) != '') { ?>

                <?
                foreach ($ICON_SVG_PARAMS AS $code => $svg ) {
                    $rseo['params_html'] = str_replace('#'.$code, $svg, $rseo['params_html']);
                }
                ?>
                <?=$rseo['params_html']?>
            <? } else {?>
                <h2> <?=$lng['w']['not_params_w']?> </h2>

                <div class="lng">
                    <?
                    //var_dump( $language);

                    foreach($language as $k => $v){
                        $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_seo2 WHERE `it_id`=:it_id AND lng = :lng LIMIT 1');
                        $pdo->execute(['it_id' => $r['id'], 'lng' => $k ]);
                        $rseo = $pdo->fetch();
                        if(trim($rseo['params_html']) != '') {
                            echo '<a href="/'.$k.$lang_mp.'" class="'.$k.' btn '.($t_mp[1]==$k?'act':'').'" title="'.$v.'">'.strtoupper($k).'</a>';
                        }
                    }

                    ?>
                </div>
            <?} ?>
            <?/*<h2>Общая информация</h2>
            <p>Просторный семейный автомобиль с надёжным дизельным двигателем и автоматической коробкой передач.</p>

            <h2>Технические характеристики</h2>
            <ul>
                <li>Год выпуска: 2018</li>
                <li>🚗 Пробег: 124 000 км</li>
                <li>Объем двигателя: 1.6 л</li>
                <li>Трансмиссия: Автомат</li>
                <li>Тип топлива: Дизель</li>
            </ul>

            <h2>Комплектация</h2>
            <ul>
                <li>Климат-контроль</li>
                <li>Круиз-контроль</li>
                <li>Парктроники</li>
                <li>Навигация</li>
                <li>Подогрев сидений</li>
                <li>Bluetooth и USB</li>
            </ul> */?>
        </div>
    </div>



    <?php // webs25 ?>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.js"></script>
    <script>
        /* ГАЛЕРЕЯ/КАРУСЕЛЬ ДЛЯ heroCarousel
        Требования:
        1) свайпы влево/вправо
        2) «кусочки» соседних слайдов видны (делаем slidesPerPage:'auto' + центрирование)
        3) клик по фото открывает Fancybox
        4) внутри Fancybox — свайпы
        5) внутри Fancybox — наш блок с названием/ценой (плашка)
        6) внутри Fancybox — индикатор количества
        7) последний слайд — HTML 2×3 сетка (#moreLinks) как у тебя
        8) горизонтальные свайпы листают
        9) вертикальный вниз — закрывает (dragToClose:true)
        10) вертикальный вверх — открывает миниатюры (делаю жест через Panzoom)
        11) pinch-to-zoom — по умолчанию у Fancybox
        12) возврат на тот же слайд — синхронизация встроена
        */

        $(function(){
            const heroEl = document.getElementById('heroCarousel');

            if (heroEl) {
                heroEl.classList.add('is-booting');  /* прячем все, кроме первого */
            }

            const heroCarousel = new Carousel(heroEl, {
                /* показываем по ширине элемента слайдов */
                slidesPerPage: 'auto',
                /* выравниваем по центру, чтобы были видны «кусочки» слева/справа */
                center: true,
                /* бесконечность выключена, чтобы отрабатывать «последний слайд-заглушку» */
                infinite: false,
                /* немного «вязкости» для приятного ощущение свайпа */
                friction: 0.12,
                /* без стрелок и точек — они не нужны в твоём дизайне */
                Arrows: false,
                Dots: false
            });
            heroCarousel.on('ready', ()=>{

                heroEl.classList.remove('is-booting');
                heroEl.classList.add('is-ready');

                // берём все картинки с классом .lazy внутри карусели
                const imgs = heroEl.querySelectorAll('img.lazy');

                imgs.forEach((img) => {
                    // если картинка уже загружена (например из кэша)
                    if (img.complete && img.naturalWidth > 0) {
                        img.classList.remove('lazy');
                    } else {
                        // иначе ждём событие загрузки
                        img.addEventListener('load', () => {
                            img.classList.remove('lazy');
                        }, { once: true });

                        img.addEventListener('error', () => {
                            img.classList.remove('lazy'); // даже если ошибка загрузки
                        }, { once: true });
                    }
                });
            });


            function createInfoBar(fb){
                const bar = document.createElement('div');
                bar.className = 'fbx-info';
                bar.innerHTML = `
                <div class="fbx-info__row">
                    <div class="fbx-info__price"></div>
                    <div class="fbx-info__title"></div>
                </div>

                            <div class="call-block">
                                <a href="tel:<?=$dynamicPhone?>" class="call-link">
                                  <svg xmlns="http://www.w3.org/2000/svg"
                                       viewBox="0 0 24 24"
                                       width="28" height="28"
                                       fill="white">
                                    <path d="M6.62 10.79a15.464 15.464 0 006.59 6.59l2.2-2.2a1
                                             1 0 011.01-.24c1.12.37 2.33.57 3.58.57.55 0 1
                                             .45 1 1v3.5c0 .55-.45 1-1 1C10.07 21 3 13.93
                                             3 5.5c0-.55.45-1 1-1H7.5c.55 0 1 .45
                                             1 1 0 1.25.2 2.46.57 3.58.11.33.03.7-.24
                                             1.01l-2.21 2.2z"/>
                                  </svg>
                                </a>
                          </div>
              `;
                fb.container.appendChild(bar);
                fb._infoBar = bar;
            }

            function updateInfoBar(fb){
                const bar = fb._infoBar;
                if (!bar) return;

                const slide = fb.getSlide && fb.getSlide();

                if (!slide) {
                    /* карусель ещё не отдала текущий слайд — попробуем на следующем тике */
                    requestAnimationFrame(() => updateInfoBar(fb));
                    return;
                }

                /* html-слайд типа «Смотреть ещё» — плашку прячем */
                if (slide.type !== 'image') {
                    bar.style.display = 'none';
                    return;
                }

                /* ВАЖНО: когда снова image — обратно показываем плашку */
                bar.style.display = '';

                /* ===== название/цена из data-* текущего триггера ===== */
                const trg   = slide.triggerEl || slide.el || null;
                const title = trg?.dataset?.title || '';
                const price = trg?.dataset?.price || '';
                bar.querySelector('.fbx-info__title').textContent = title;
                bar.querySelector('.fbx-info__price').textContent = price;

                /* ===== считаем по DOM, а не по внутренним массивам ===== */
                const group = (trg?.getAttribute('data-fancybox') || 'product');
                const all   = Array.from(document.querySelectorAll(`[data-fancybox="${group}"]`));
                const isImage = (a) => ((a.getAttribute('data-type') || 'image').toLowerCase() === 'image');
                const total = all.filter(isImage).length;

                const currentGlobalIndex = slide.index; /* 0-based */
                let pos = 0;
                for (let i = 0; i < all.length; i++){
                    if (isImage(all[i])) pos++;
                    if (i === currentGlobalIndex) break;
                }

                if (!Number.isFinite(pos) || pos < 1) pos = 1;
                if (!Number.isFinite(total) || total < 1) pos = total = 0;

                /* если нужна надпись pos/total — раскомментируй свой вывод тут */
                // bar.querySelector('.fbx-info__count').textContent = `${pos} / ${total}`;
            }


            /* читаем Set избранного из localStorage */
            function favLoad(){
                try{
                    const raw = localStorage.getItem('favSet');
                    const arr = raw ? JSON.parse(raw) : [];
                    return new Set(Array.isArray(arr) ? arr : []);
                }catch(_){ return new Set(); }
            }

            /* сохраняем Set в localStorage */
            function favSave(set){
                try{
                    localStorage.setItem('favSet', JSON.stringify(Array.from(set)));
                }catch(_){}
            }

            /* получаем id текущего слайда (из data-id у триггера) */
            function getCurrentId(fb){
                const slide = fb.getSlide && fb.getSlide();
                const trg = slide && (slide.triggerEl || slide.el);
                return trg?.dataset?.id || null;
            }

            /* обновляем состояние/вид кнопки под текущий слайд */
            function updateFavBtn(fb){
                const btn = fb._favBtn;
                if (!btn) return;

                const slide = fb.getSlide && fb.getSlide();
                if (!slide) {
                    /* карусель ещё не отдала текущий слайд — пробуем на следующем тике */
                    requestAnimationFrame(() => updateFavBtn(fb));
                    return;
                }

                if (slide.type !== 'image') {
                    btn.style.display = 'none';
                    return;
                }

                const trg = slide.triggerEl || slide.el || null;
                const id  = trg?.dataset?.id || null;
                if (!id) {
                    btn.style.display = 'none';
                    return;
                }

                btn.style.display = '';
                const set   = favLoad();
                const isFav = set.has(id);
                btn.classList.toggle('is-active', isFav);
                btn.setAttribute('aria-pressed', String(isFav));
                btn.title = isFav ? 'Убрать из избранного' : 'Добавить в избранное';
            }



            /* обработчик клика по кнопке */
            function onFavClick(fb){
                const id = getCurrentId(fb);
                if (!id) return;

                const set = favLoad();
                if (set.has(id)) set.delete(id); else set.add(id);
                favSave(set);
                updateFavBtn(fb);
            }



            function getThumbsPlugin(fb){
                // в Fancybox v5 плагин живёт в fb.plugins (а не только в fb.Carousel.plugins)
                // return fb?.plugins?.Thumbs || fb?.carousel?.plugins?.Thumbs || null;
                return fb?.plugins?.Thumbs || null;
            }

            function showThumbs(fb){
                const thumbs = getThumbsPlugin(fb);
                if (!thumbs) return;
                fb.container.classList.add('is-thumbs'); // даём CSS разрешение показывать
                thumbs.show?.();                         // плагин уже строит/показывает ленту

                console.log(" add('is-thumbs') ");
            }

            function hideThumbs(fb){
                const thumbs = getThumbsPlugin(fb);
                thumbs?.hide?.();
                fb.container.classList.remove('is-thumbs');

                console.log(" remove('is-thumbs') ");
            }



            /*
            if (window.Carousel?.Plugins?.Thumbs) {
                Carousel.Plugins.Thumbs.defaults.showOnStart = false;
            }
            */
            /* FIX: готовим «HTML-строку» для последнего слайда один раз на старте */
            (function prepareMoreLinksAsHtml(){
                /* находим триггер «more» и исходный блок-контент */
                const moreTrig = document.querySelector('[data-fancybox="product"][data-id="more"]');
                const moreSrc  = document.getElementById('moreLinks');
                if (!moreTrig || !moreSrc) return;

                /* Берём HTML содержимое, чтобы Fancybox не трогал живой DOM узел */
                const html = moreSrc.innerHTML;        /* содержимое сетки 2×3 */
                const wrapper = `<div class="more-grid">${html}</div>`; /* сохраняем класс-обёртку */

                /* Переключаем триггер на тип html + src = строка HTML
                   => Fancybox будет рендерить КОПИЮ, а не переносить живой #moreLinks */
                moreTrig.setAttribute('data-type', 'html');
                moreTrig.setAttribute('data-src', wrapper);

                /* Чтобы не было конфликтов, уберём href на #moreLinks */
                moreTrig.removeAttribute('href');
            })();


            /* ОДИН общий bind — вверх = открыть миниатюры, вниз = закрыть модалку */
            Fancybox.bind('[data-fancybox="product"]', {
                // groupAll: true,
                dragToClose: false,

                /* Отключаем все действия, связанные с зумом/панорамированием */
                contentClick: false,   /* по клику по контенту ничего не делать (v5 по умолчанию мог toggleZoom) */
                wheel: false,          /* колесо мыши не масштабирует и не листает */
                Images: {
                    /* подстраховка: просим не создавать panzoom/zoom */
                    Panzoom: {
                        zoom: true,       /* запрет на программный/дабл-тап зум (если поддерживается сборкой) */
                        touch: true,      /* запрет панорамирования контента внутри кадра */
                        panOnlyZoomed: true
                    }
                },
                /* v5: встроенный счётчик называется infobar */
                Toolbar: {
                    enabled: true,
                    display: {
                        left: ['close'],
                        middle: ['infobar'],
                        right: []
                    }
                },
                /* миниатюры подключены, но старт скрытый */
                Carousel: {
                    Arrows: false, // убирает стрелки «влево/вправо»
                    Dots: false    // убирает точки
                },

                on: {
                    /* FIX: использовать событие v5 — работаем со слайдами через carousel */
                    'Carousel.init': (fb, carousel) => {
                        /* /* определяем наш inline-слайд */
                        const isMore = (s) => {
                            if (!s) return false;
                            if (s.triggerEl?.dataset?.id === 'more') return true;
                            const src = s.src;
                            if (typeof src === 'string') return src === '#moreLinks';
                            return src?.nodeType === 1 && src.id === 'moreLinks';
                        };

                        /* 1) находим индекс inline-слайда, если он уже попал */
                        let idx = carousel.slides.findIndex(isMore);

                        /* 2) если не попал — добавляем из нашего скрытого триггера */
                        if (idx === -1) {
                            const tr = document.querySelector('#heroCarousel [data-fancybox="product"][data-id="more"]');
                            if (tr) {
                                carousel.addSlide({ type: 'html', src: '#moreLinks', triggerEl: tr });
                                idx = carousel.slides.length - 1;
                            }
                        }

                        /* 3) если есть, но не последний — переносим в конец */
                        if (idx > -1 && idx !== carousel.slides.length - 1) {
                            const slide = carousel.slides[idx];
                            carousel.removeSlide(idx);
                            carousel.addSlide(slide);
                        }
                    },

                    /* готово: создаём плашку, обновляем и прячем миниатюры */
                    ready: (fb) => {
                        console.log('---- .ready');

                        /* на всякий случай сбросим флаг и спрячем превью как только они появятся */
                        fb.container.classList.remove('is-thumbs');

                        createInfoBar(fb);

                        /* первый апдейт — на следующий тик (когда карусель уже будет инициализирована) */
                        requestAnimationFrame(() => updateInfoBar(fb));

                        hideThumbs(fb);
                        // hideThumbs(fb); /* гарантированно скрыть превью на старте */

                        /* ===== КНОПКА ИЗБРАННОГО (правый верх) ===== */
                        const fav = document.createElement('button');
                        fav.className = 'fav-btn';
                        fav.type = 'button';
                        fav.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
                        fav.setAttribute('aria-label', 'Добавить в избранное');
                        /* pointer-events у контейнера выключен — поэтому кнопка должна быть кликабельна сама */
                        fav.addEventListener('click', (e) => { e.stopPropagation(); onFavClick(fb); });
                        fb.container.appendChild(fav);
                        fb._favBtn = fav;
                        updateFavBtn(fb);

                        /* ===========================
                           РЕГИСТРИРУЕМ ЖЕСТЫ НА КОНТЕЙНЕРЕ
                           =========================== */
                        const el = fb.container;

                        const THRESH = 40; /* порог в пикселях */
                        let activeId = null, startX = 0, startY = 0;

                        /* сохраняем обработчики на инстансе, чтобы потом снять */
                        fb.__onTouchStart = function(ev){
                            /* игнорируем, если касание началось на ленте миниатюр */
                            if (ev.target.closest('.f-thumbs')) return;

                            const t = ev.touches && ev.touches[0];
                            if (!t) return;

                            /* игнор pinch: если 2+ пальца, не трогаем */
                            if (ev.touches.length !== 1) { activeId = null; return; }

                            activeId = t.identifier;
                            startX = t.clientX;
                            startY = t.clientY;
                        };

                        fb.__onTouchEnd = function(ev){
                            if (activeId === null) return;

                            /* найдём именно тот палец, который начался на touchstart */
                            let t = null;
                            if (ev.changedTouches){
                                for (let i = 0; i < ev.changedTouches.length; i++){
                                    if (ev.changedTouches[i].identifier === activeId){
                                        t = ev.changedTouches[i]; break;
                                    }
                                }
                            }
                            if (!t) { activeId = null; return; }

                            const dx = t.clientX - startX;
                            const dy = t.clientY - startY;
                            const absX = Math.abs(dx);
                            const absY = Math.abs(dy);
                            const THRESH_V = 40;   /* вертикальный порог */

                            /* текущий слайд и его panzoom (v5 разные поля) */
                            const slide = fb.getSlide && fb.getSlide();
                            const pz = slide && (slide.Panzoom || slide.panzoom || slide._Panzoom || slide._panzoom || null);
                            const scale = (pz && (pz.content?.scale ?? pz.scale)) || 1;

                            /* 1) Вертикальный жест доминирует */
                            if (absY > absX && absY > THRESH_V){
                                if (dy < 0){
                                    /* ВВЕРХ — показать миниатюры */
                                    showThumbs(fb);
                                } else {
                                    /* ВНИЗ — закрыть галерею */
                                    fb.close();
                                }
                                activeId = null;
                                return;
                            }

                            /* 2) ГОРИЗОНТАЛЬ: листать только если в зуме И жест доминирует по X И мы у края */
                            if (scale > 1 && absX > absY) {
                                const THRESH_H = 90;      /* порог по горизонтали (был 50) — делаем менее чувствительным */
                                const RATIO_DOM = 1.25;   /* X должен быть заметно больше Y */
                                const EDGE_TOL = 16;      /* допускаем маленький люфт у края, px */

                                if (absX > THRESH_H && absX > absY * RATIO_DOM) {
                                    /* проверяем: упрёмся ли в край в сторону жеста */
                                    let atEdge = false;

                                    /* Попытка «умного» способа — если у Panzoom есть координаты и границы */
                                    const c = pz && pz.content;
                                    if (c && typeof c.x === 'number' && typeof c.minX === 'number' && typeof c.maxX === 'number') {
                                        /* dx < 0 — тянем влево, у края слева => x <= minX + EDGE_TOL */
                                        if (dx < 0) atEdge = (c.x <= c.minX + EDGE_TOL);
                                        /* dx > 0 — тянем вправо, у края справа => x >= maxX - EDGE_TOL */
                                        else       atEdge = (c.x >= c.maxX - EDGE_TOL);
                                    } else {
                                        /* Фолбэк: если не можем прочитать границы — требуем ещё больший свайп */
                                        atEdge = absX > 140;
                                    }

                                    if (atEdge) {
                                        /* мгновенно сбрасываем зум/пан, чтобы листалка не конфликтовала */
                                        try { pz && pz.reset && pz.reset(0); } catch(_) {}

                                        const goNext = dx < 0;
                                        requestAnimationFrame(function(){
                                            if (goNext && typeof fb.next === 'function') fb.next();
                                            else if (!goNext && typeof fb.prev === 'function') fb.prev();
                                        });

                                        activeId = null;
                                        return;
                                    }
                                }
                            }

                            activeId = null;
                        };

                        /* вешаем слушатели (passive:true — чтобы не ломать скроллы/перформанс) */
                        el.addEventListener('touchstart', fb.__onTouchStart, { passive: true });
                        el.addEventListener('touchend',   fb.__onTouchEnd,   { passive: true });
                        el.addEventListener('touchcancel',fb.__onTouchEnd,   { passive: true });
                    },

                    /* обновляем плашку при смене кадра */
                    'Carousel.change': (fb) => {
                        console.log('Carousel.change');

                        updateInfoBar(fb);
                        updateFavBtn(fb);
                    },

                    /* подстраховка: если Fancybox вдруг показал ленту сам — спрячем */
                    'Carousel.ready': (fb) => {
                        console.log('Carousel.ready');
                        hideThumbs(fb);
                        updateInfoBar(fb); /* ← гарантируем апдейт сразу после готовности карусели */
                        updateFavBtn(fb);

                        // разовый дебаг — посмотри в консоль при первом открытии:
                        const t = getThumbsPlugin(fb);
                    },

                    load: (fb) => {
                        console.log('Carousel.load');

                        updateInfoBar(fb);
                        updateFavBtn(fb);
                    },

                    done: (fb) => {
                        console.log('Carousel.done');

                        updateInfoBar(fb);
                        updateFavBtn(fb);
                    },

                    /* снимаем слушатели и сбрасываем флаг при закрытии */
                    closing: (fb) => {
                        try {
                            const el = fb.container;
                            if (fb.__onTouchStart) el.removeEventListener('touchstart', fb.__onTouchStart);
                            if (fb.__onTouchEnd)   el.removeEventListener('touchend', fb.__onTouchEnd);
                            if (fb.__onTouchEnd)   el.removeEventListener('touchcancel', fb.__onTouchEnd);
                        } catch(_){}

                        if (fb._favBtn) {
                            try { fb._favBtn.remove(); } catch(_){}
                            fb._favBtn = null;
                        }

                        fb.container.classList.remove('is-thumbs');
                    }
                }
            });
        });

    </script>

    <script>
        /* нормализуем URL без #hash для корректного сравнения */
        function stripHash(u){
            try {
                const x = new URL(u, window.location.href);
                return x.origin + x.pathname + x.search; /* без hash */
            } catch(_){
                return ''; /* если referrer кривой/пустой */
            }
        }

        /* тип захода (Navigation Timing v2) с фолбэком */
        function getNavType(){
            try {
                const nav = performance.getEntriesByType('navigation')[0];
                if (nav && nav.type) return nav.type; /* 'navigate' | 'reload' | 'back_forward' | 'prerender' */
            } catch(_){}
            if (performance && performance.navigation) {
                switch (performance.navigation.type) {
                    case 0: return 'navigate';
                    case 1: return 'reload';
                    case 2: return 'back_forward';
                }
            }
            return 'navigate';
        }

        /* основная логика автопоказа */
        (function autoOpenGalleryOnce(){
            const navType = getNavType();           /* ожидаем 'navigate' при переходе по ссылке */
            const ref     = document.referrer || '';/* откуда пришли */
            const here    = stripHash(window.location.href);
            const from    = stripHash(ref);

            /* УСЛОВИЯ:
               1) пришли по навигации (НЕ reload, НЕ back/forward)
               2) referrer существует (не прямой заход) И это не та же страница
               3) ещё не открывали в этой сессии (чтобы не надоедать при повторных переходах внутри SPA/сайта)
            */
            const okByType   = (navType === 'navigate');
            const hasRef     = !!from;
            const isSamePage = hasRef && (from === here);
            //const already    = sessionStorage.getItem('autoGalleryShown') === '1';

            if (okByType && hasRef && !isSamePage) {
                setTimeout(function () {

                    const slides = Array.from(document.querySelectorAll('.f-carousel__slide.is-selected'));
                    console.log('slides');
                    console.log( slides);

                    if (slides.length && $(window).width() < 640 ) {
                        location.href = '#product-1';
                        console.log('slides 2');
                        // Fancybox.show(slides, { startIndex: 0 });
                        // $('.f-carousel__slide.is-selected a').click();

                        // sessionStorage.setItem('autoGalleryShown', '1');
                    }
                },500);
            }
        })();
    </script>
<?php } ?>
