<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;


// Include appropriate functions file based on current page
if (isset($t_mp[2]) && $t_mp[2] == 'ordercars') {
    include(_SITE_INCL.'/order_functions.php');
} else {
    include(_SITE_INCL.'/functions.php');
}

// Legacy redirect: /services/order -> /order (301)
if (isset($t_mp[2]) && $t_mp[2] === 'services' && isset($t_mp[3]) && $t_mp[3] === 'order') {
    $_lang = $_COOKIE['lang'] ?? 'ro';
    header('Location: /'.$_lang.'/order', true, 301);
    exit;
}

// Catalog redirect fallback: handle legacy ?srt=in_stock / ?srt=on_order URLs (current UI uses ?cat=...)
if (isset($_GET['srt']) && in_array($_GET['srt'], ['in_stock','on_order'], true) && isset($t_mp[2]) && in_array($t_mp[2], ['cars','ordercars'], true)) {
    $_srt_target = $_GET['srt'] === 'on_order' ? 'ordercars' : 'cars';
    if ($t_mp[2] !== $_srt_target) {
        $_srt_qs = $_GET;
        unset($_srt_qs['srt']);
        // Preserve brand/model from URL path (e.g. /cars/bmw/x5 -> /ordercars/bmw/x5)
        $_srt_path = '';
        if (isset($t_mp[3]) && $t_mp[3] !== '' && !is_numeric($t_mp[3])) {
            $_srt_brand = explode('?', $t_mp[3])[0];
            $_srt_path .= '/'.$_srt_brand;
            if (isset($t_mp[4]) && $t_mp[4] !== '' && !is_numeric($t_mp[4])) {
                $_srt_model = explode('?', $t_mp[4])[0];
                $_srt_path .= '/'.$_srt_model;
            }
        }
        $_srt_url = '/'.$_COOKIE['lang'].'/'.$_srt_target.$_srt_path;
        if (!empty($_srt_qs)) {
            $_srt_url .= '?'.http_build_query($_srt_qs);
        }
        header('Location: '.$_srt_url, true, 302);
        exit;
    }
}

if (!isset($GLOBALS['page_is_404'])) { $GLOBALS['page_is_404'] = false; }


if (isset($t_mp[2]) && $t_mp[2] == 'cars' && isset($t_mp[3]) && !isset($_GET['tg'])) {
    if (is_numeric($t_mp[3])) {
        $check_id = toNumber($t_mp[3]);
        
        $check_pdo = $db->prepare('SELECT id, catalog_type FROM '.$prefx.'_car_ctlg WHERE `id`= :id AND `vis`="1" AND `act`="1" LIMIT 1');
        $check_pdo->execute(['id' => $check_id]);
        $check_car = $check_pdo->fetch(PDO::FETCH_ASSOC);
        
        if (!$check_car || empty($check_car['catalog_type']) || $check_car['catalog_type'] !== 'in_stock') {
            $GLOBALS['page_is_404'] = true;
        }
        // A partner barred from the in-stock catalog must not reach the page via a
        // direct URL either. The in-stock page (cars.php) carries no B2B block, so
        // a 404 is the honest response; the listing already hides these cars.
        elseif (function_exists('b2b_is_client') && b2b_is_client() && !b2b_can_see_catalog('in_stock')) {
            $GLOBALS['page_is_404'] = true;
            if (function_exists('b2b_user_id')) {
                App\Services\B2b\B2bAudit::log(
                    b2b_user_id(),
                    App\Services\B2b\B2bAudit::REGION_DENIED,
                    ['car_id' => (int)$check_id, 'catalog_type' => 'in_stock'],
                    (int)$check_id
                );
            }
        }
    }
}

// Check TYRES detail page
if (isset($t_mp[2]) && $t_mp[2] == 'tyres' && isset($t_mp[3]) && !isset($q_mp[1])) {
    $check_id = toNumber($t_mp[3]);
    
    if ($check_id > 0) {
        $check_pdo = $db->prepare('SELECT id FROM '.$prefx.'_tyre_ctlg WHERE `id`= :id AND `vis`="1" LIMIT 1');
        $check_pdo->execute(['id' => $check_id]);
        
        if ($check_pdo->rowCount() == 0) {
            $GLOBALS['page_is_404'] = true;
        }
    } else {
        $GLOBALS['page_is_404'] = true;
    }
}

// Check OFFER_INFO detail page
if (isset($t_mp[2]) && $t_mp[2] == 'offer_info' && isset($t_mp[3])) {
    $check_pdo = $db->prepare('SELECT id, visible, active FROM '.$prefx.'_offer_catalog WHERE `id`=:id');
    $check_pdo->execute(['id' => $t_mp[3]]);
    $check_offer = $check_pdo->fetch(PDO::FETCH_ASSOC);
    
    if (!$check_offer || $check_offer['visible'] != 1 || $check_offer['active'] != 1) {
        $GLOBALS['page_is_404'] = true;
    }
}

// Check SERVICES page
if (isset($t_mp[2]) && $t_mp[2] == 'services' && isset($t_mp[3])) {
    if (!key_exists($t_mp[3], $serv_arr)) {
        $GLOBALS['page_is_404'] = true;
    }
}

// Check OFFERS page
if (isset($t_mp[2]) && $t_mp[2] == 'offers' && isset($t_mp[3])) {
    if (!in_array($t_mp[3], $offers_arr)) {
        $GLOBALS['page_is_404'] = true;
    }
}


if (isset($t_mp[2]) && $t_mp[2] == 'ordercars' && isset($t_mp[3]) && !isset($_GET['tg'])) {
    if (is_numeric($t_mp[3])) {
        $check_id = toNumber($t_mp[3]);

        $check_pdo = $db->prepare('SELECT id, catalog_type FROM '.$prefx.'_car_ctlg WHERE `id`= :id AND `vis`="1" AND `act`="1" LIMIT 1');
        $check_pdo->execute(['id' => $check_id]);
        $check_car = $check_pdo->fetch(PDO::FETCH_ASSOC);

        // Return 404 if car not found OR catalog_type is not 'on_order' (including NULL or empty values)
        if (!$check_car || empty($check_car['catalog_type']) || $check_car['catalog_type'] !== 'on_order') {
            $GLOBALS['page_is_404'] = true;
        }
        // Access control (spec 3.2): hiding the car from the grid is not enough, a
        // partner typing the URL directly must be refused here too. Catalog access
        // (on_order) is checked first, then region.
        elseif (function_exists('b2b_is_client') && b2b_is_client()) {
            if (!b2b_can_see_catalog('on_order')) {
                $GLOBALS['page_is_404'] = true; // silent 404, same as in-stock — no "restricted" hint
                App\Services\B2b\B2bAudit::log(
                    b2b_user_id(),
                    App\Services\B2b\B2bAudit::REGION_DENIED,
                    ['car_id' => (int)$check_id, 'catalog_type' => 'on_order'],
                    (int)$check_id
                );
            } else {
                $_b2b_region = App\Services\B2b\B2bRegions::regionForCar((int)$check_id);
                if ($_b2b_region !== null && !b2b_can_see_region($_b2b_region)) {
                    $GLOBALS['page_is_404'] = true; // silent 404, same as in-stock — no "restricted" hint
                    App\Services\B2b\B2bAudit::log(
                        b2b_user_id(),
                        App\Services\B2b\B2bAudit::REGION_DENIED,
                        ['car_id' => (int)$check_id, 'region' => $_b2b_region],
                        (int)$check_id
                    );
                } else {
                    // Audit: every car a partner looks at (acceptance criteria).
                    b2b_log_car_view((int)$check_id);
                }
            }
        }
    }
}

?>

<head>
    <?php include(_SITE.'/head.php'); ?>
</head>

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
		<button type="button" class="card-share-btn show-img-share" data-share-url="" data-copied-text="'.htmlspecialchars($lng['w']['link_copied'] ?? 'Link copiat', ENT_QUOTES).'" aria-label="'.htmlspecialchars($lng['w']['share'] ?? 'Distribuie', ENT_QUOTES).'" title="'.htmlspecialchars($lng['w']['share'] ?? 'Distribuie', ENT_QUOTES).'"><svg class="csb-ico" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="7" cy="12" r="2" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="17" cy="6" r="2" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="17" cy="18" r="2" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M15 7L8.5 11" stroke="currentColor" stroke-width="2"/><path d="M8.5 13.5L15 17" stroke="currentColor" stroke-width="2"/></svg><span class="csb-label"></span></button>
		<button type="button" class="card-fav-btn show-img-fav" data-fav-id="" data-fav-add="'.htmlspecialchars($lng['w']['fav_add'] ?? 'Adaugă în favorite', ENT_QUOTES).'" data-fav-remove="'.htmlspecialchars($lng['w']['fav_remove'] ?? 'Scoate din favorite', ENT_QUOTES).'" title="'.htmlspecialchars($lng['w']['fav_add'] ?? 'Adaugă în favorite', ENT_QUOTES).'"><svg class="cfb-ico" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></button>
		<div class="close"></div>
		<div class="left"></div>
		<div class="right"></div>
	</div>';
//echo substr( md5('22') , 0, 4 );
?>

<?php
$cur_pg = $t_mp[2] ?? '';
if ($cur_pg === '') { $cur_pg = $t_mp[1] ?? ''; }
$is_collection_pg = in_array($cur_pg, ['favorites', 'compare'], true);
?>
<div id="to_top"<?php echo $is_collection_pg ? ' class="no-mob"' : ''; ?> title="<?php echo $lang_to_top; ?>"></div>

<?php
// Floating favourites shortcut points to /favorites (localStorage). A logged-in
// B2B partner has their saved cars in the cabinet instead, so hide it for them.
if (!(function_exists('b2b_is_client') && b2b_is_client())):
    $fav_lbl = ['ro'=>'Favorite','ru'=>'Избранное','en'=>'Favorites'][$_COOKIE['lang']] ?? 'Favorite';
?>
<a id="fav_float"<?php echo $cur_pg === 'favorites' ? ' class="is-here"' : ''; ?> href="/<?php echo $_COOKIE['lang']; ?>/favorites" title="<?php echo $fav_lbl; ?>" aria-label="<?php echo $fav_lbl; ?>">
	<span class="fav-float-ico">
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
		<span class="fav-nav-count" style="display:none;">0</span>
	</span>
	<span class="fav-float-lbl"><?php echo $fav_lbl; ?></span>
</a>
<?php $cmp_lbl = ['ro'=>'Comparare','ru'=>'Сравнение','en'=>'Compare'][$_COOKIE['lang']] ?? 'Comparare'; ?>
<a id="compare_float"<?php echo $cur_pg === 'compare' ? ' class="is-here"' : ''; ?> href="/<?php echo $_COOKIE['lang']; ?>/compare" title="<?php echo $cmp_lbl; ?>" aria-label="<?php echo $cmp_lbl; ?>">
	<span class="cmp-float-ico">
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M7 20h10"/><path d="M6 6l6-1 6 1"/><path d="M12 3v17"/><path d="M9 12L6 6l-3 6a3 3 0 0 0 6 0"/><path d="M21 12l-3-6-3 6a3 3 0 0 0 6 0"/></svg>
		<span class="cmp-nav-count" style="display:none;">0</span>
	</span>
	<span class="cmp-float-lbl"><?php echo $cmp_lbl; ?></span>
</a>
<?php endif; ?>

<script>
(function(){
	var KEY='sauto_compare';
	function get(){ try{ return (JSON.parse(localStorage.getItem(KEY))||[]).map(Number).filter(Boolean); }catch(e){ return []; } }
	function save(a){ try{ localStorage.setItem(KEY, JSON.stringify(a)); }catch(e){} }
	function syncFloat(){
		var n=get().length, f=document.getElementById('compare_float');
		if(f){ f.classList.toggle('has-cmp', n>0); var c=f.querySelector('.cmp-nav-count'); if(c){ c.textContent=n; c.style.display=n>0?'inline-block':'none'; } }
		document.querySelectorAll('[data-cmp-count]').forEach(function(el){ el.textContent=n; });
	}
	function syncBtns(){
		var list=get();
		document.querySelectorAll('.card-compare-btn[data-compare-id]').forEach(function(b){
			var on=list.indexOf(parseInt(b.dataset.compareId,10))!==-1;
			b.classList.toggle('is-cmp', on);
			var lbl=on?b.dataset.cmpRemove:b.dataset.cmpAdd;
			if(lbl){ b.title=lbl; b.setAttribute('aria-label',lbl); }
		});
	}
	var CMP_MAX=10, CMP_MAX_MSG=<?php echo json_encode(['ro'=>'Poți compara maximum 10 mașini.','ru'=>'Можно сравнить не более 10 авто.','en'=>'You can compare up to 10 cars.'][$_COOKIE['lang']] ?? 'Poți compara maximum 10 mașini.', JSON_UNESCAPED_UNICODE); ?>;
	function toggle(id){
		id=parseInt(id,10); if(!id) return false;
		var a=get(), i=a.indexOf(id), added=false;
		if(i===-1){ if(a.length>=CMP_MAX){ alert(CMP_MAX_MSG); return false; } a.push(id); added=true; }
		else a.splice(i,1);
		save(a); syncBtns(); syncFloat();
		return added;
	}
	// Same balance icon as the compare button, for the fly-to-cabinet animation.
	var CMP_FLY_SVG='<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M7 20h10"/><path d="M6 6l6-1 6 1"/><path d="M12 3v17"/><path d="M9 12L6 6l-3 6a3 3 0 0 0 6 0"/><path d="M21 12l-3-6-3 6a3 3 0 0 0 6 0"/></svg>';
	document.addEventListener('click', function(e){
		var b=e.target.closest ? e.target.closest('.card-compare-btn') : null;
		if(!b) return;
		e.preventDefault(); e.stopPropagation();
		var added=toggle(b.dataset.compareId);
		b.classList.remove('cmp-pop'); void b.offsetWidth; b.classList.add('cmp-pop');
		// Logged-in partner: fly the balance icon to the cabinet button on add, like
		// favourites. Self-guards (no-op) when there's no cabinet button (guests).
		if(added && window.SautoFlyToCabinet){ try{ window.SautoFlyToCabinet(b, {html:CMP_FLY_SVG, className:'fav-fly fav-fly--stroke'}); }catch(_){} }
	}, true);
	function init(){ syncBtns(); syncFloat(); }
	if(document.readyState!=='loading') init(); else document.addEventListener('DOMContentLoaded', init);
	window.SautoCompare={ get:get, toggle:toggle, clear:function(){ save([]); syncBtns(); syncFloat(); }, sync:init };
})();
</script>

<header>
    <div class="def">
        <a id="top_logo" href="/<?php echo $_COOKIE['lang']; ?>/" title="<?php echo $lang_top_info; ?>" style="width:12rem;"><img src="/<?php echo _SITE_IMG; ?>/v2/logo_b.svg" alt="<?php echo $lang_main_logo; ?>" width="100%" /></a>

        <input type="checkbox" id="mm_cbx" />
        <nav id="main_menu" role="navigation">
            <div class="menu">
                <?php
                foreach ($menu_arr as $k => $v){
                    if ($v == '1'){
                        // A B2B partner without access to a catalog simply doesn't see
                        // it in the menu (no "restricted" message — the section is just
                        // not there for them). Guests always see everything.
                        if ($k === 'cars'      && function_exists('b2b_can_see_catalog') && !b2b_can_see_catalog('in_stock')) { continue; }
                        if ($k === 'ordercars' && function_exists('b2b_can_see_catalog') && !b2b_can_see_catalog('on_order')) { continue; }
                        echo '<a class="'.$k.' button '; if( isset($t_mp[2])&&$t_mp[2]==$k ){echo ' active';} echo '" href="/'.$_COOKIE['lang'].'/'.$k.'"><div>'.$lng['l']['menu'][$k].'</div></a>';
                    }
                }

                // B2B entry point. Registration must be reachable by a dealer who
                // has no account yet (spec 2.1), so this is always rendered, not
                // only for an existing session.
                //
                // Two placements, one visible at a time: this menu entry shows
                // inside the burger on mobile, the header button next to "call"
                // shows on desktop. See .b2b-menu-only / .b2b-header-btn in b2b.css.
                include_once(_SITE_PAGE.'/b2b/_layout.php');
                echo b2b_assets(false); // CSS only; B2B pages add the JS themselves

                $_b2b_logged  = function_exists('b2b_is_client') && b2b_is_client();
                $_b2b_on_page = isset($t_mp[2]) && in_array($t_mp[2], ['b2b', 'b2b-login', 'b2b-register'], true);
                $_b2b_href    = $_b2b_logged ? '/'.$_COOKIE['lang'].'/b2b/cabinet' : '/'.$_COOKIE['lang'].'/b2b-register';

                // Guests get two entry points: log in (returning dealer) then
                // register (new one). Each highlights only on its own page.
                $_on_login       = isset($t_mp[2]) && $t_mp[2] === 'b2b-login';
                $_on_register    = isset($t_mp[2]) && $t_mp[2] === 'b2b-register';
                $_b2b_login_href = '/'.$_COOKIE['lang'].'/b2b-login';
                $_b2b_login_lbl  = b2b_t('login');
                $_b2b_reg_href   = '/'.$_COOKIE['lang'].'/b2b-register';
                $_b2b_reg_lbl    = b2b_t('header_register');

                // Logged in: "Cabinetul meu" + an initials avatar (e.g. "GB"), like
                // the cabinet hero. Not logged in: the plain "Register" label.
                $_b2b_label    = b2b_t('header_register');
                $_b2b_initials = '';
                if ($_b2b_logged) {
                    $_b2b_user = function_exists('b2b_user') ? b2b_user() : null;
                    $_b2b_full = $_b2b_user ? trim((string)($_b2b_user['full_name'] ?? '')) : '';
                    foreach (preg_split('/\s+/', $_b2b_full) ?: [] as $w) {
                        if ($w !== '') { $_b2b_initials .= mb_strtoupper(mb_substr($w, 0, 1)); if (mb_strlen($_b2b_initials) >= 2) break; }
                    }
                    if ($_b2b_initials === '') { $_b2b_initials = 'B'; }
                    $_b2b_initials = htmlspecialchars($_b2b_initials, ENT_QUOTES, 'UTF-8');
                    $_b2b_label    = b2b_t('cabinet_mine');
                }

                // Logout sits next to the cabinet button, only while logged in.
                $_b2b_logout_href = '/'.$_COOKIE['lang'].'/b2b/logout';
                $_b2b_logout_lbl  = htmlspecialchars(b2b_t('logout'), ENT_QUOTES, 'UTF-8');
                $_b2b_ico_exit    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';

                // Expose the CSRF token so the favourites heart can mirror a saved
                // car into the partner's cabinet (gh3sp_b2b_saved_cars). Guests get
                // nothing here, so the heart stays localStorage-only for them.
                if ($_b2b_logged && function_exists('b2b_csrf_token')) {
                    echo '<script>window.B2B_FAV={csrf:'.json_encode(b2b_csrf_token(), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG).'};</script>';
                }

                // Wrapper so the two entries can be pinned to the top bar of the open
                // mobile menu (next to the × close), where dealers actually see them.
                // On desktop the entries stay hidden (.b2b-menu-only).
                echo '<div class="b2b-menu-top">';
                if ($_b2b_logged) {
                    echo '<a class="b2b button b2b-nav-link b2b-menu-only b2b-menu-only--user'.($_b2b_on_page ? ' active' : '').'" href="'.$_b2b_href.'">'
                       . '<span class="b2b-header-btn__avatar">'.$_b2b_initials.'</span>'
                       . '<div>'.$_b2b_label.'</div></a>';
                    echo '<a class="b2b button b2b-nav-link b2b-menu-only b2b-menu-out" href="'.$_b2b_logout_href.'">'
                       . '<div>'.$_b2b_logout_lbl.'</div></a>';
                } else {
                    echo '<a class="b2b button b2b-nav-link b2b-menu-only'.($_on_login ? ' active' : '').'" href="'.$_b2b_login_href.'">'
                       . '<div>'.$_b2b_login_lbl.'</div></a>';
                    echo '<a class="b2b button b2b-nav-link b2b-menu-only'.($_on_register ? ' active' : '').'" href="'.$_b2b_reg_href.'">'
                       . '<div>'.$_b2b_reg_lbl.'</div></a>';
                }
                echo '</div>';
                ?>
            </div>
            <?php
            // data-current drives the desktop dropdown label (style.css renders it
            // via ::before), because the loop below only prints the OTHER languages.
            $_lang_cur = strtoupper($_COOKIE['lang'] ?? $default_lang);
            ?>
            <div class="lang" data-current="<?php echo htmlspecialchars($_lang_cur, ENT_QUOTES); ?>">
                <div class="lang_list">
                <?php foreach( array_reverse($language) as $k => $v ){
                    echo '<a href="/'.$k.$lang_mp.'" hreflang="'.$k.'" class="'.$k.' button '; if($t_mp[1]==$k){echo ' active';} echo'" title="'.$v.'"><div>'.strtoupper($k).'</div></a>';
                } ?>
                </div>
            </div>
            <label for="mm_cbx" class="mm_lb" data-close="<?php 
                $close_text = array(
                    'ro' => 'Închide',
                    'ru' => 'Закрыть', 
                    'en' => 'Close'
                );
                echo $close_text[$_COOKIE['lang']];
                ?>">
                <span class="menu-text">
                    <?php 
                    $menu_text = array(
                        'ro' => 'Meniu',
                        'ru' => 'Меню', 
                        'en' => 'Menu'
                    );
                    echo $menu_text[$_COOKIE['lang']];
                    ?>
                </span>
            </label>
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
        <?php
        // Desktop counterpart of the menu entry above: placed to the RIGHT of "call"
        // so a dealer finds registration/cabinet without digging through the menu.
        // Hidden on mobile, where the burger entry takes over.
        if ($_b2b_logged) {
            echo '<a class="b2b-header-btn b2b-header-btn--user'.($_b2b_on_page ? ' is-active' : '').'" href="'.$_b2b_href.'" title="'.$_b2b_label.'">'
               . '<span class="b2b-header-btn__avatar">'.$_b2b_initials.'</span>'
               . '<span class="b2b-header-btn__txt">'.$_b2b_label.'</span></a>';
            echo '<a class="b2b-header-out" href="'.$_b2b_logout_href.'" title="'.$_b2b_logout_lbl.'" aria-label="'.$_b2b_logout_lbl.'">'.$_b2b_ico_exit.'</a>';
        } else {
            // Login = secondary text link, Register = primary outlined pill.
            echo '<a class="b2b-header-login'.($_on_login ? ' is-active' : '').'" href="'.$_b2b_login_href.'" title="'.$_b2b_login_lbl.'">'.$_b2b_login_lbl.'</a>';
            echo '<a class="b2b-header-btn'.($_on_register ? ' is-active' : '').'" href="'.$_b2b_reg_href.'" title="'.$_b2b_reg_lbl.'">'
               . '<span class="b2b-header-btn__txt">'.$_b2b_reg_lbl.'</span></a>';
        }
        ?>
    </div>
</header>

<?php
// Logged-in partners keep quick access to their cabinet + logout while scrolling:
// a small fixed cluster (desktop only) that fades in once the header scrolls away.
if ($_b2b_logged):
    echo '<div class="b2b-float" aria-hidden="true">'
       . '<a class="b2b-header-btn b2b-header-btn--user'.($_b2b_on_page ? ' is-active' : '').'" href="'.$_b2b_href.'" title="'.$_b2b_label.'">'
       . '<span class="b2b-header-btn__avatar">'.$_b2b_initials.'</span>'
       . '<span class="b2b-header-btn__txt">'.$_b2b_label.'</span></a>'
       . '<a class="b2b-header-out" href="'.$_b2b_logout_href.'" title="'.$_b2b_logout_lbl.'" aria-label="'.$_b2b_logout_lbl.'">'.$_b2b_ico_exit.'</a>'
       . '</div>';
    echo '<script>(function(){var f=document.querySelector(".b2b-float");var a=document.querySelector("header .b2b-header-btn");var o=document.querySelector("header .b2b-header-out")||a;var anchor=a||document.querySelector("header");if(!f||!anchor)return;function align(){var el=o||a;if(!el)return;var r=el.getBoundingClientRect();var vw=document.documentElement.clientWidth;if(r.width)f.style.right=Math.max(0,Math.round(vw-r.right))+"px";}align();window.addEventListener("resize",align,{passive:true});if("IntersectionObserver" in window){new IntersectionObserver(function(e){f.classList.toggle("is-shown",!e[0].isIntersecting);},{threshold:0}).observe(anchor);}else{var on=false;function u(){var s=(window.pageYOffset||document.documentElement.scrollTop)>(anchor.offsetHeight||120);if(s!==on){on=s;f.classList.toggle("is-shown",s);}}window.addEventListener("scroll",u,{passive:true});u();}})();</script>';
endif;
?>

<div id="crumbs" data-lng-c="<?php echo $lng['w']['copied']; ?>">
    <?php
    if ( isset($t_mp[2]) && in_array($t_mp[2], ['b2b', 'b2b-login', 'b2b-register', 'b2b-forgot', 'b2b-reset'], true) ){
        // Friendly, localized crumbs for the partner area — clients do not know the
        // raw "b2b" / "invoices" / "b2b-login" URL words.
        $_bcl  = $_COOKIE['lang'] ?? 'ro';
        $_b2bc = [
            'ro' => ['root'=>'Cabinet', 'invoices'=>'Conturi de plată', 'cont-plata'=>'Cont de plată', 'b2b-login'=>'Autentificare', 'b2b-register'=>'Înregistrare', 'b2b-forgot'=>'Resetare parolă', 'b2b-reset'=>'Resetare parolă'],
            'ru' => ['root'=>'Кабинет', 'invoices'=>'Счета на оплату', 'cont-plata'=>'Счёт на оплату', 'b2b-login'=>'Вход', 'b2b-register'=>'Регистрация', 'b2b-forgot'=>'Сброс пароля', 'b2b-reset'=>'Сброс пароля'],
            'en' => ['root'=>'Cabinet', 'invoices'=>'Payment invoices', 'cont-plata'=>'Payment invoice', 'b2b-login'=>'Login', 'b2b-register'=>'Registration', 'b2b-forgot'=>'Password reset', 'b2b-reset'=>'Password reset'],
        ];
        $_bm  = $_b2bc[$_bcl] ?? $_b2bc['ro'];
        echo '<a href="/'.$_bcl.'/">'.$lng['w']['home_page'].'</a>';
        if (in_array($t_mp[2], ['b2b-login', 'b2b-register', 'b2b-forgot', 'b2b-reset'], true)){
            echo ' - <span class="crnt cp_url" title="'.$lng['w']['copy'].' URL">'.$_bm[$t_mp[2]].'</span>';
        }else{
            $_sub = (isset($t_mp[3]) && $t_mp[3] !== '' && $t_mp[3] !== 'cabinet') ? $t_mp[3] : '';
            if ($_sub !== ''){
                echo ' - <a href="/'.$_bcl.'/b2b/cabinet">'.$_bm['root'].'</a>';
                echo ' - <span class="crnt cp_url" title="'.$lng['w']['copy'].' URL">'.($_bm[$_sub] ?? $_sub).'</span>';
            }else{
                echo ' - <span class="crnt cp_url" title="'.$lng['w']['copy'].' URL">'.$_bm['root'].'</span>';
            }
        }
    }
    elseif ( isset($t_mp[2]) && in_array($t_mp[2], ['favorites', 'compare'], true) ){
        // Localized single crumb for the favourites / compare collection pages.
        $_fcl = $_COOKIE['lang'] ?? 'ro';
        $_fc  = [
            'favorites' => ['ro'=>'Favorite','ru'=>'Избранное','en'=>'Favorites'],
            'compare'   => ['ro'=>'Comparare','ru'=>'Сравнение','en'=>'Compare'],
        ];
        $_lbl = $_fc[$t_mp[2]][$_fcl] ?? $_fc[$t_mp[2]]['ro'];
        echo '<a href="/'.$_fcl.'/">'.$lng['w']['home_page'].'</a>';
        echo ' - <span class="crnt cp_url" title="'.$lng['w']['copy'].' URL">'.$_lbl.'</span>';
    }
    elseif ( isset($t_mp[2])&&$t_mp[2]!='' ){
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
$_show_links_pages = ['', 'cars', 'ordercars', 'services', 'calculator'];
$_cur_page = isset($t_mp[2]) ? $t_mp[2] : '';
$_on_services_main = ($_cur_page == 'services' && (!isset($t_mp[3]) || $t_mp[3] == ''));
$_hide_nav_on_mobile = $isMobile == '1' && in_array($_cur_page, ['cars', 'ordercars', 'services']);
if ( in_array($_cur_page, $_show_links_pages) && ($_cur_page != 'services' || $_on_services_main) && !$_hide_nav_on_mobile && empty($GLOBALS['page_is_404']) ):
?>
<?php if ($_cur_page == ''): 
    $_hb_lang = $_COOKIE['lang'] ?? 'ro';
    $_hb_txt = [
        'ro' => ['import' => 'Import auto din', 'see' => 'Vezi catalog auto', 'tagline' => ['Transparență', 'Calitate', 'Livrare Sigură'], 'available' => ['Oferte', 'disponibile']],
        'ru' => ['import' => 'Импорт авто из',  'see' => 'Смотреть каталог авто', 'tagline' => ['Прозрачность', 'Качество', 'Безопасная доставка'], 'available' => ['Доступные', 'предложения']],
        'en' => ['import' => 'Car import from', 'see' => 'See car catalog', 'tagline' => ['Transparency', 'Quality', 'Safe Delivery'], 'available' => ['Available', 'offers']],
    ];
    $_hb = $_hb_txt[$_hb_lang] ?? $_hb_txt['ro'];

    // Total available cars (in-stock + on-order) with an active, non-expired status.
    // EFFECTIVE_NA_SQL also treats an expired offer timer as unavailable (same as the cards).
    $_hb_count = 0;
    try {
        $_hb_sql = 'SELECT COUNT(*) FROM '.$prefx.'_car_ctlg
            WHERE catalog_type IN ("in_stock","on_order") AND `vis`="1" AND `act`="1"
              AND '.EFFECTIVE_NA_SQL.' = 0';
        $_hb_count = (int)$db->query($_hb_sql)->fetchColumn();
    } catch (Exception $e) { $_hb_count = 0; }
    $_hb_regions = [
        'korea'  => ['ro' => 'Coreea', 'ru' => 'Кореи', 'en' => 'Korea'],
        'europe' => ['ro' => 'Europa', 'ru' => 'Европы', 'en' => 'Europe'],
        'usa'    => ['ro' => 'SUA',    'ru' => 'США',    'en' => 'USA'],
        // Hidden until there are on-order cars from China; uncomment to restore.
        // 'china'  => ['ro' => 'China',  'ru' => 'Китая',  'en' => 'China'],
    ];
?>
<div id="home_banner">
    <img src="/media/images/site/banner-home.jpg" alt="" fetchpriority="high" decoding="async" width="2136" height="1280">
    <div class="hb_overlay">
        <div class="hb_title"><?php echo $_hb['import']; ?></div>
        <div class="hb_regions">
            <?php foreach ($_hb_regions as $_rk => $_rv):

                if (function_exists('b2b_can_see_region') && !b2b_can_see_region($_rk)) { continue; }
                $_rlabel = $_rv[$_hb_lang] ?? $_rv['ro'];
            ?>
            <a class="hb_region_btn" href="/<?php echo $_hb_lang; ?>/ordercars/<?php echo $_rk; ?>" title="<?php echo $_hb['see'].' '.$_rlabel; ?>"><?php echo $_rlabel; ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <a class="hb_right hb_region_btn" href="/<?php echo $_hb_lang; ?>/ordercars" title="<?php echo $_hb['see']; ?>">
        <span class="hb_avail"><span><?php echo $_hb['available'][0]; ?></span><span><?php echo $_hb['available'][1]; ?></span></span>
        <span class="hb_count"><?php echo number_format($_hb_count, 0, '', '.'); ?></span>
    </a>
    <div class="hb_tagline"><?php
        foreach ($_hb['tagline'] as $_ti => $_tword) {
            if ($_ti > 0) echo '<span class="hb_dot">·</span>';
            echo $_tword;
        }
    ?></div>
</div>
<?php endif; ?>
<div id="nav_links">
    <a href="/<?php echo $_COOKIE['lang']; ?>/calculator">
        <div class="img" style="background-image:url(/media/images/links/link-calc3.jpg);"></div>
        <span class="ttl"><?php echo $lng['p']['services']['calc_customs']['name']; ?></span>
    </a>
    <a href="/<?php echo $_COOKIE['lang']; ?>/tradein">
        <div class="img" style="background-image:url(/media/images/links/link-trade.jpg);"></div>
        <span class="ttl"><?php echo $lng['p']['services']['tradein']['name']; ?></span>
    </a>
    <a href="/<?php echo $_COOKIE['lang']; ?>/order">
        <div class="img" style="background-image:url(/media/images/links/link-order.jpg);"></div>
        <span class="ttl"><?php echo $lng['p']['services']['order']['name']; ?></span>
    </a>
</div>
<?php endif; ?>

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

if ($isMobile == '1') {
    $_m_cur_page = isset($t_mp[2]) ? $t_mp[2] : '';
    $_m_show_nav = in_array($_m_cur_page, ['cars', 'ordercars', 'services']) && (!isset($t_mp[3]) || $t_mp[3] == '');
    if ($_m_show_nav) {
        echo '<div id="mob_nav_links">';
        echo '<a href="/'.$_COOKIE['lang'].'/calculator">'.$lng['p']['services']['calc_customs']['name'].'</a>';
        echo '<a href="/'.$_COOKIE['lang'].'/tradein">'.$lng['p']['services']['tradein']['name'].'</a>';
        echo '<a href="/'.$_COOKIE['lang'].'/order">'.$lng['p']['services']['order']['name'].'</a>';
        echo '</div>';
    }
}

if ( !isset($t_mp[2]) || $t_mp[2]=='' || ( ($t_mp[2]=='cars' || $t_mp[2]=='tyres' || $t_mp[2]=='rent') && (!isset($t_mp[3]) || $t_mp[3]=='' || ($t_mp[2]=='cars' && isset($t_mp[3]) && !is_numeric($t_mp[3]))) ) ){ include(_SITE_INCL.'/filter.php'); }
elseif ( $t_mp[2]=='ordercars' && (!isset($t_mp[3]) || $t_mp[3]=='' || !is_numeric($t_mp[3])) ){ include(_SITE_INCL.'/order_filter.php'); }
?>

<main role="main">
    <?php

    /*
            // var_dump( _SITE_PAGE);
            echo "<pre>";
                var_dump( $t_mp);
            echo "</pre>";
            // exit(); */

    if(isset($t_mp[2]) && ($t_mp[2]=='cars' || $t_mp[2]=='ordercars' || ($t_mp[2]=='services' && isset($t_mp[3]) && $t_mp[3]=='credit')) ) {
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/css/ion.rangeSlider.min.css"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/js/ion.rangeSlider.min.js"></script>
    <?php }

    if ( !empty($GLOBALS['page_is_404']) && !(isset($t_mp[2]) && in_array($t_mp[2], ['cars','ordercars'], true) && isset($t_mp[3]) && is_numeric($t_mp[3])) ) {
        include(_SITE_INCL.'/page_404.php');
    }
    elseif ( !isset($t_mp[2]) || $t_mp[2]=='') {
        // On home, if a filter/sort is active, render the cars listing instead of the home landing page
        if (isset($_GET['tg']) && $_GET['tg']=='fltr') {
            include (_SITE_PAGE.'/cars.php');
        } else {
            include (_SITE_PAGE.'/home.php');
        }
    }

    elseif ($t_mp[2]=='cars') {include (_SITE_PAGE.'/cars.php');}
    elseif ($t_mp[2]=='ordercars') {include (_SITE_PAGE.'/ordercars.php');}
    elseif ($t_mp[2]=='services') {include (_SITE_PAGE.'/services.php');}
    elseif ($t_mp[2]=='tyres') {include (_SITE_PAGE.'/tyres.php');}
    elseif ($t_mp[2]=='credit') {include (_SITE_PAGE.'/new_pages/credit/credit.php');}
    elseif ($t_mp[2]=='tradein') {include (_SITE_PAGE.'/new_pages/tradein/tradein.php');}
    elseif ($t_mp[2]=='order') {include (_SITE_PAGE.'/new_pages/order/order.php');}
    elseif ($t_mp[2]=='rent'&&(!isset($t_mp[3])&&!isset($q_mp[1]))) {include (_SITE_PAGE.'/rent.php');}
    elseif ( in_array( $t_mp[2], $info_arr ) ) {include (_SITE_PAGE.'/information.php');}
    elseif ($t_mp[2]=='favorites') {include (_SITE_PAGE.'/favorites.php');}
    elseif ($t_mp[2]=='compare')   {include (_SITE_PAGE.'/compare.php');}
    elseif ($t_mp[2]=='contacts') {include (_SITE_PAGE.'/contacts.php');}
    elseif ($t_mp[2]=='calculator') {include (_SITE_PAGE.'/new_pages/calculator/calculator.php');}
    elseif ($t_mp[2]=='telegram') {include (_SITE_PAGE.'/new_pages/telegram/telegram.php');}

    //---B2B module: registration, 2-step login, dealer cabinet
    elseif ($t_mp[2]=='b2b-register') {include (_SITE_PAGE.'/b2b/register.php');}
    elseif ($t_mp[2]=='b2b-login')    {include (_SITE_PAGE.'/b2b/login.php');}
    elseif ($t_mp[2]=='b2b-forgot')   {include (_SITE_PAGE.'/b2b/forgot.php');}
    elseif ($t_mp[2]=='b2b-reset')    {include (_SITE_PAGE.'/b2b/reset.php');}
    elseif ($t_mp[2]=='b2b')          {include (_SITE_PAGE.'/b2b/cabinet.php');}

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

    else {
        if (isset($_GET['r404dbg'])) {
            echo '<!-- R404DBG: t_mp2='.htmlspecialchars($t_mp[2] ?? 'UNSET')
                .' page_is_404='.(!empty($GLOBALS['page_is_404'])?'1':'0')
                .' info_arr='.htmlspecialchars(json_encode($info_arr ?? null)).' -->';
        }
        include(_SITE_INCL.'/page_404.php');
    }

    ?>
    <div class="clear"></div>
</main>

<?php 
if (!isset($t_mp[2]) || $t_mp[2]=='') { 
    $floatingPhone = PhoneHelper::getGeneralPhone();
?>
<div id="mobile-call-button" class="mobile-call-button-green">
    <a href="tel:<?php echo $floatingPhone; ?>" class="call-button-inner-green">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="white">
            <path d="M6.62 10.79a15.464 15.464 0 006.59 6.59l2.2-2.2a1
                     1 0 011.01-.24c1.12.37 2.33.57 3.58.57.55 0 1
                     .45 1 1v3.5c0 .55-.45 1-1 1C10.07 21 3 13.93
                     3 5.5c0-.55.45-1 1-1H7.5c.55 0 1 .45
                     1 1 0 1.25.2 2.46.57 3.58.11.33.03.7-.24
                     1.01l-2.21 2.2z"></path>
        </svg>
    </a>
</div>
<?php } elseif (isset($t_mp[2]) && $t_mp[2]=='cars' && (!isset($t_mp[3]) || $t_mp[3]=='' || isset($_GET['tg']))) { 
    $floatingPhone = PhoneHelper::getStockPhone();
?>
<div id="mobile-call-button" class="mobile-call-button-green">
    <a href="tel:<?php echo $floatingPhone; ?>" class="call-button-inner-green">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="white">
            <path d="M6.62 10.79a15.464 15.464 0 006.59 6.59l2.2-2.2a1
                     1 0 011.01-.24c1.12.37 2.33.57 3.58.57.55 0 1
                     .45 1 1v3.5c0 .55-.45 1-1 1C10.07 21 3 13.93
                     3 5.5c0-.55.45-1 1-1H7.5c.55 0 1 .45
                     1 1 0 1.25.2 2.46.57 3.58.11.33.03.7-.24
                     1.01l-2.21 2.2z"></path>
        </svg>
    </a>
</div>
<?php } elseif (isset($t_mp[2]) && $t_mp[2]=='ordercars' && (!isset($t_mp[3]) || $t_mp[3]=='' || !is_numeric($t_mp[3]))) { 
    $floatingPhone = PhoneHelper::getOrderPhone();
?>
<div id="mobile-call-button" class="mobile-call-button-green">
    <a href="tel:<?php echo $floatingPhone; ?>" class="call-button-inner-green">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="white">
            <path d="M6.62 10.79a15.464 15.464 0 006.59 6.59l2.2-2.2a1
                     1 0 011.01-.24c1.12.37 2.33.57 3.58.57.55 0 1
                     .45 1 1v3.5c0 .55-.45 1-1 1C10.07 21 3 13.93
                     3 5.5c0-.55.45-1 1-1H7.5c.55 0 1 .45
                     1 1 0 1.25.2 2.46.57 3.58.11.33.03.7-.24
                     1.01l-2.21 2.2z"></path>
        </svg>
    </a>
</div>
<?php } ?>

<footer>
    <div class="footer-content">
        <div class="columns-wrapper">

            <!-- Column 1: Company Logo -->
            <div class="column-container col-logo-container">
                <div class="col col-logo">
                    <div class="footer-logo-section">
                        <a href="/<?php echo $_COOKIE['lang']; ?>/">
                            <img src="/<?php echo _SITE_IMG; ?>/v2/logo_w.svg" alt="SAUTO" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- Column 2: Services -->
            <div class="column-container col-services-container">
                <div class="col col-services">
                    <div class="services-wrapper">
                        <!-- Main title spanning full width -->
                        <div class="services-main-title"><?php echo (isset($lng['w']['services'])) ? $lng['w']['services'] : 'Servicii'; ?></div>
                        
                        <!-- Subcolumns container -->
                        <div class="services-subcolumns">
                            <!-- Subcol 1 -->
                            <div class="services-subcolumn">
                                <div class="section">
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/services/sale"><?php echo $lng['p']['services']['sale']['name']; ?></a>
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/tradein"><?php echo $lng['p']['services']['tradein']['name']; ?></a>
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/services/estimation"><?php echo $lng['p']['services']['estimation']['name']; ?></a>
                                </div>
                            </div>
                            
                            <!-- Subcol 2 -->
                            <div class="services-subcolumn">
                                <div class="section">
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/services/testdrive"><?php echo $lng['p']['services']['testdrive']['name']; ?></a>
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/services/insurance"><?php echo $lng['p']['services']['insurance']['name']; ?></a>
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/order"><?php echo $lng['p']['services']['order']['name']; ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 3: Information -->
            <div class="column-container col-information-container">
                <div class="col col-information">
                    <div class="information-wrapper">
                        <!-- Main title spanning full width -->
                        <div class="information-main-title"><?php echo (isset($lng['w']['information'])) ? $lng['w']['information'] : 'Informații'; ?></div>
                        
                        <!-- Subcolumns container -->
                        <div class="information-subcolumns">
                            <!-- Subcol 1 -->
                            <div class="information-subcolumn">
                                <div class="section">
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/about"><?php echo $lng['p']['information']['about']['name']; ?></a>
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/credit"><?php echo $lng['p']['information']['credit']['name']; ?></a>
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/warranty"><?php echo $lng['p']['information']['warranty']['name']; ?></a>
                                </div>
                            </div>
                            
                            <!-- Subcol 2 -->
                            <div class="information-subcolumn">
                                <div class="section">
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/privacy"><?php echo $lng['p']['information']['privacy']['name']; ?></a>
                                    <a href="/<?php echo $_COOKIE['lang']; ?>/terms"><?php echo $lng['p']['information']['terms']['name']; ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 4: Contact Information -->
            <div class="column-container col-contacts-container">
                <div class="col col-contacts">
                    <div class="contacts-wrapper">
                        <!-- Main title spanning full width -->
                        <div class="contacts-main-title"><?php echo $lng['w']['contacts']; ?></div>
                        
                        <!-- Subcolumns container -->
                        <div class="contacts-subcolumns">
                            <!-- Subcol 1 -->
                            <div class="contacts-subcolumn">
                                <div class="section">
                                    <p class="location-item">
                                        <?php echo $lng['t']['x']['address'][0]; ?>
                                    </p>
                                    <p class="location-item">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <?php echo $lng['t']['x']['address'][1]; ?>
                                    </p>
                                    <p class="location-item">
                                        <i class="fa-solid fa-location-dot"></i>
                                        <?php echo $lng['t']['x']['address'][2]; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Subcol 2 -->
                            <div class="contacts-subcolumn">
                                <div class="section">
                                    <?php
                                    if ( isset($t_mp[2])&&$t_mp[2]=='cars' ) {
                                        $footerPhone = PhoneHelper::getStockPhone();
                                    } elseif ( isset($t_mp[2])&&$t_mp[2]=='ordercars' ) {
                                        $footerPhone = PhoneHelper::getOrderPhone();
                                    } else {
                                        $footerPhone = PhoneHelper::getGeneralPhone();
                                    }
                                    $formattedGeneralPhone = PhoneHelper::formatPhone($footerPhone, 'display');
                                    ?>
                                    <a href="tel:<?php echo $footerPhone; ?>" class="phone-item">
                                        <i class="fa-solid fa-phone"></i>
                                        <?php echo $formattedGeneralPhone; ?>
                                    </a>
                                    <p class="schedule-item">
                                        <?php echo $lng['l']['date']['day']['mon']['l'].' - '.$lng['l']['date']['day']['fri']['l']; ?> <strong>8:00 - 18:00</strong>
                                    </p>
                                    <p class="schedule-item">
                                        <?php echo $lng['l']['date']['day']['sat']['l'].' - '.$lng['l']['date']['day']['sun']['l']; ?> <strong>9:00 - 16:00</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Col 5 -->
            <div class="column-container col-social-container">
                <div class="col col-social">
                    <div class="ttl"><?php echo isset($lng['w']['social']) ? $lng['w']['social'] : 'Rețelele sociale'; ?></div>
                    <div class="sc">
                        <?php
                        foreach ($sc_ar as $k => $v){
                            echo '<a class="'.$k.'" href="'.$v['url'].'" target="_blank" title="'.$v['name'].'" style="background-image:url(/media/images/site/social/'.$v['img']['w'].');"></a>';
                        }
                        ?>
                    </div>
                    <!-- Email moved here -->
                    <a href="mailto:info@sauto.md" class="social-email">
                        <i class="fa-solid fa-envelope"></i>
                        info@sauto.md
                    </a>
                </div>
            </div>
        </div> <!-- Close columns-wrapper -->
        
        <!--description above copyright -->
        <div class="footer-bottom">
            <div class="footer-description"><?php echo $lng['t']['x']['logo_txt']; ?></div>
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
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js" defer></script>
<noscript>
    <div style="display:inline;">
        <img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/865017510/?value=0&amp;guid=ON&amp;script=0"/><?php //991949120 ?>
    </div>
</noscript>
<!-- End Google AdWords -->
 
<!-- BITRIX site_button disabled
<?php if (empty($_COOKIE['lang']) || ($_COOKIE['lang'] == 'ru')):?>
    <script>
        // Delay chat widget loading to improve mobile CWV
        if ('requestIdleCallback' in window) {
            requestIdleCallback(function() {
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_2_ydxvti.js');
            });
        } else {
            setTimeout(function() {
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_2_ydxvti.js');
            }, 3000);
        }
    </script>
<?php elseif($_COOKIE['lang'] == 'en'):?>
    <script>
        if ('requestIdleCallback' in window) {
            requestIdleCallback(function() {
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_6_kv018j.js');
            });
        } else {
            setTimeout(function() {
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_6_kv018j.js');
            }, 3000);
        }
    </script>
<?php elseif($_COOKIE['lang'] == 'ro'):?>
    <script>
        if ('requestIdleCallback' in window) {
            requestIdleCallback(function() {
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_8_l03bx1.js');
            });
        } else {
            setTimeout(function() {
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/60000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/site_button/loader_8_l03bx1.js');
            }, 3000);
        }
    </script>
<?php endif;?>
-->
</body>

<?php
if(isset($t_mp[2]) && ($t_mp[2]=='cars' || ($t_mp[2]=='services' && isset($t_mp[3]) && $t_mp[3]=='credit')) ) {
    ?>
    <script>
        $(document).ready(function () {
            if ($("#suma-creditului").length === 0 || $("#termen-creditului").length === 0) return;

            let updateRateTimeout;
            let inputSumaTimeout;
            let inputTermenTimeout;

            var $input_suma_creditului = $("#view_suma_creditului");
            // Loan amount
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

                    clearTimeout(updateRateTimeout); // reset previous timer
                    updateRateTimeout = setTimeout(updateRate, 1500); // set new timer
                
}
            }).data("ionRangeSlider");

            var $input_termen_creditului = $("#view_termen_creditului");
            // Loan term
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

                    clearTimeout(updateRateTimeout); // reset previous timer
                    updateRateTimeout = setTimeout(updateRate, 1500); // set new timer
                    <?php /* console.log("Срок изменен:", data.from);
                    updateRate(); */ ?>
                }
            }).data("ionRangeSlider");

            // Manual input — AMOUNT
            $input_suma_creditului.on("input", function () {
                clearTimeout(inputSumaTimeout);
                inputSumaTimeout = setTimeout(() => {
                    let val = parseInt($(this).val(), 10);
                    if (isNaN(val)) val = 2000;
                    val = Math.max(2000, Math.min(50000, val)); // min/max limit
                    val = Math.round(val / 500) * 500; // round to nearest 500
                    $(this).val(val);
                    sliderSuma.update({ from: val });

                    clearTimeout(updateRateTimeout); // reset previous timer
                    updateRateTimeout = setTimeout(updateRate, 1500); // set new timer
                }, 1500);
            });

            // Manual input — TERM
            $input_termen_creditului.on("input", function () {
                clearTimeout(inputTermenTimeout);
                inputTermenTimeout = setTimeout(() => {
                    let val = parseInt($(this).val(), 10);
                    if (isNaN(val)) val = 6;
                    val = Math.max(6, Math.min(60, val));
                    $(this).val(val);
                    sliderTermen.update({ from: val });

                    clearTimeout(updateRateTimeout); // reset previous timer
                    updateRateTimeout = setTimeout(updateRate, 1500); // set new timer
                }, 1500);
            });

            function updateRate() {
                var sumaSlider = $("#suma-creditului").data("ionRangeSlider");
                var termenSlider = $("#termen-creditului").data("ionRangeSlider");
                if (!sumaSlider || !termenSlider) return;
                const suma = sumaSlider.result.from;
                const termen = termenSlider.result.from;
                console.log(`Сумма: ${suma}, Срок: ${termen}`);
                $('.calc_btt_r1_nrl').text( termen);

                <?php /* // Here you can call your own logic for recalculating MONTHLY PAYMENT */ ?>

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
                        const data = await response.text(); 
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
if(isset($t_mp[2]) && ($t_mp[2]=='cars' || $t_mp[2]=='ordercars') ) {
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
    <?php

    if (isset($r) && is_array($r)) { ?>
    <div class="block_txt_params_pop">
        <?php
        // webs25
        $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_seo2 WHERE `it_id`=:it_id AND lng = :lng LIMIT 1');
        $pdo->execute(['it_id' => $r['id'], 'lng' => $_COOKIE['lang'] ]);
        $rseo = $pdo->fetch();
        if (!is_array($rseo)) { $rseo = ['params_html' => '']; }
        $rseo['params_html'] = html_entity_decode($rseo['params_html'] ?? '');
        ?>
        <div class="param_pop_header">
            <div class="blk_pop_logo">
                <img src="/media/images/site/v2/logo_b.svg" alt="Sauto, автомобили из Европы." width="100%">
            </div>

            <div class="block_pop_close" onclick=" openParamsPopAuto('hide') ">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 16 16" data-testid="Curtain__closer" class="IconSvg IconSvg_name_SvgCrossS IconSvg_size_24 Curtain__closer Curtain__closer_small"><path fill="currentColor" d="m6.94 8-4.76 4.77 1.06 1.06L8 9.062l4.76 4.768 1.06-1.06L9.06 8l4.76-4.77-1.06-1.06L8 6.938 3.24 2.17 2.18 3.23z"></path></svg>
            </div>

        </div>

        <?php 
        $ICON_SVG_PARAMS = [
            'year' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="4" width="18" height="16" rx="2"></rect>
  <path d="M8 2v4M16 2v4M3 10h18"></path>
</svg>',

            'mileage' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M20 13a8 8 0 10-16 0"></path>
  <path d="M12 13l3-4"></path>
  <rect x="6" y="14.5" width="12" height="3" rx="1"></rect>
</svg>
SVG
            ,

            'engine' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="8" width="13" height="8" rx="2"></rect>
  <path d="M16 10h2l3 3v3h-3"></path>
  <path d="M7 6v2M11 6v2M7 16v2M11 16v2"></path>
</svg>
SVG
            ,

            'transmission' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M7 4v8a3 3 0 003 3h4"></path>
  <path d="M14 4l3 3-3 3"></path>
  <path d="M10 20l-3-3 3-3"></path>
  <path d="M14 15h3v5h-3"></path>
</svg>
SVG
            ,

            'fuel' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <rect x="3" y="3" width="10" height="18" rx="2"></rect>
  <path d="M13 7H3"></path>
  <path d="M16 7l3 3v7a2 2 0 01-2 2h-1"></path>
  <path d="M18 13c0-1.5-1-2-2-2"></path>
</svg>
SVG
            ,

            'climate' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M12 2v8"></path>
  <path d="M9 6h6"></path>
  <circle cx="12" cy="15" r="4"></circle>
  <path d="M12 11v8"></path>
</svg>
SVG
            ,

            'cruise' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <circle cx="12" cy="12" r="9"></circle>
  <path d="M15 9l-3 6-3-1.5L15 9z"></path>
</svg>
SVG
            ,

            'parking' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M6 20V4h6a4 4 0 010 8H6"></path>
  <path d="M17 8.5c1.5 1.2 1.5 3.8 0 5"></path>
  <path d="M19.5 7c2.4 2.2 2.4 6.8 0 9"></path>
</svg>
SVG
            ,

            'navigation' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <circle cx="12" cy="10" r="3.5"></circle>
  <path d="M12 21c4-3.8 6-6.8 6-9a6 6 0 10-12 0c0 2.2 2 5.2 6 9z"></path>
</svg>
SVG
            ,

            'heated_seat' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M6 12v3a3 3 0 003 3h7"></path>
  <path d="M8 12V8a2 2 0 012-2h1a2 2 0 012 2v4"></path>
  <path d="M5 7c1 1 1 2 0 3M9 7c1 1 1 2 0 3M13 7c1 1 1 2 0 3"></path>
</svg>
SVG
            ,

            'bluetooth' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M7 7l10 10-5 5V2l5 5L7 17"></path>
</svg>',

            'usb' => '
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
  <path d="M12 3v12"></path>
  <path d="M9 6l3-3 3 3"></path>
  <circle cx="12" cy="18" r="3"></circle>
  <path d="M6 12h3M15 12h3"></path>
</svg>'
        ];
        ?>

        <h2 class="name"> <?=$r['br_nm']?> <?=$r['mo_nm']?> <span class="fl"> <?=$lng['l']['car']['fl'][$r['fl']]?> </span> </h2>
        <div class="block_txt_params">
            <?php  if(trim($rseo['params_html']) != '') { ?>

                <?php 
                foreach ($ICON_SVG_PARAMS AS $code => $svg ) {
                    $rseo['params_html'] = str_replace('#'.$code, $svg, $rseo['params_html']);
                }
                ?>
                <?=$rseo['params_html']?>
            <?php  } else {?>
                <h2> <?=$lng['w']['not_params_w']?> </h2>

                <div class="lng">
                    <?php 
                    //var_dump( $language);

                    foreach($language as $k => $v){
                        $pdo = $db->prepare('SELECT * FROM ' . $prefx . '_seo2 WHERE `it_id`=:it_id AND lng = :lng LIMIT 1');
                        $pdo->execute(['it_id' => $r['id'], 'lng' => $k ]);
                        $rseo = $pdo->fetch();
                        if(is_array($rseo) && trim($rseo['params_html'] ?? '') != '') {
                            echo '<a href="/'.$k.$lang_mp.'" class="'.$k.' btn '.($t_mp[1]==$k?'act':'').'" title="'.$v.'">'.strtoupper($k).'</a>';
                        }
                    }

                    ?>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php }  ?>



    <?php // webs25 ?>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.js"></script>
    <script>

        $(function(){
            const heroEl = document.getElementById('heroCarousel');

            if (heroEl) {
                heroEl.classList.add('is-booting');
            }

            const heroCarousel = new Carousel(heroEl, {
                slidesPerPage: 'auto',
                center: true,
                infinite: false,
                friction: 0.12,
                Arrows: false,
                Dots: false
            });
        
            heroCarousel.on('ready', ()=>{

                heroEl.classList.remove('is-booting');
                heroEl.classList.add('is-ready');

                const imgs = heroEl.querySelectorAll('img.lazy');

                imgs.forEach((img) => {
                    if (img.complete && img.naturalWidth > 0) {
                        img.classList.remove('lazy');
                    } else {
                        img.addEventListener('load', () => {
                            img.classList.remove('lazy');
                        }, { once: true });

                        img.addEventListener('error', () => {
                            img.classList.remove('lazy'); 
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
                                <a href="tel:<?= $dynamicPhone ?? '' ?>" class="call-link">
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
                    requestAnimationFrame(() => updateInfoBar(fb));
                    return;
                }

                if (slide.type !== 'image') {
                    bar.style.display = 'none';
                    return;
                }

                bar.style.display = '';

                const trg   = slide.triggerEl || slide.el || null;
                const title = trg?.dataset?.title || '';
                const price = trg?.dataset?.price || '';
                bar.querySelector('.fbx-info__title').textContent = title;
                bar.querySelector('.fbx-info__price').textContent = price;

                const group = (trg?.getAttribute('data-fancybox') || 'product');
                const all   = Array.from(document.querySelectorAll(`[data-fancybox="${group}"]`));
                const isImage = (a) => ((a.getAttribute('data-type') || 'image').toLowerCase() === 'image');
                const total = all.filter(isImage).length;

                const currentGlobalIndex = slide.index;
                let pos = 0;
                for (let i = 0; i < all.length; i++){
                    if (isImage(all[i])) pos++;
                    if (i === currentGlobalIndex) break;
                }

                if (!Number.isFinite(pos) || pos < 1) pos = 1;
                if (!Number.isFinite(total) || total < 1) pos = total = 0;

                // bar.querySelector('.fbx-info__count').textContent = `${pos} / ${total}`;
            }


            // Use the SAME store as the rest of the site (sauto_favorites) so the
            // Fancybox heart stays in sync with the card/product hearts.
            function favLoad(){
                try{
                    const raw = localStorage.getItem('sauto_favorites');
                    const arr = raw ? JSON.parse(raw) : [];
                    return new Set((Array.isArray(arr) ? arr : []).map(Number));
                }catch(_){ return new Set(); }
            }

            function favSave(set){
                try{
                    localStorage.setItem('sauto_favorites', JSON.stringify(Array.from(set).map(Number)));
                }catch(_){}
            }

            // The Fancybox slide has no car id (only photo index). Derive the real car id
            // from the slide's image URL (/car/.../<id>/...), falling back to the page heart.
            function getCurrentId(fb){
                try{
                    const slide = fb && fb.getSlide && fb.getSlide();
                    const trg = slide && (slide.triggerEl || slide.el);
                    let src = '';
                    if (trg){ src = trg.getAttribute('href') || (trg.querySelector && trg.querySelector('img') && trg.querySelector('img').getAttribute('src')) || ''; }
                    const m = src.match(/\/car\/[^]*?\/(\d+)\//);
                    if (m) return Number(m[1]);
                }catch(_){}
                const b = document.querySelector('.wrapf-carousel .card-fav-btn[data-fav-id], .big_pht .card-fav-btn[data-fav-id], .card-fav-btn[data-fav-id]');
                return b ? Number(b.getAttribute('data-fav-id')) : null;
            }

            function updateFavBtn(fb){
                const btn = fb._favBtn;
                if (!btn) return;
                btn.style.display = '';
                const id = getCurrentId(fb);
                const isFav = id ? favLoad().has(id) : false;
                btn.classList.toggle('is-active', isFav);
                btn.setAttribute('aria-pressed', String(isFav));
                btn.title = isFav ? 'Убрать из избранного' : 'Добавить в избранное';
            }

            function onFavClick(fb){
                const id = getCurrentId(fb);
                if (!id) return;
                const set = favLoad();
                if (set.has(id)) set.delete(id); else set.add(id);
                favSave(set);
                updateFavBtn(fb);
                // Keep all other hearts + the floating button + count in sync.
                if (typeof window.sautoFavSync === 'function') window.sautoFavSync();
            }



            function getThumbsPlugin(fb){
                // return fb?.plugins?.Thumbs || fb?.carousel?.plugins?.Thumbs || null;
                return fb?.plugins?.Thumbs || null;
            }

            function showThumbs(fb){
                const thumbs = getThumbsPlugin(fb);
                if (!thumbs) return;
                fb.container.classList.add('is-thumbs'); 
                thumbs.show?.();                         

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

            (function prepareMoreLinksAsHtml(){

                const moreTrig = document.querySelector('[data-fancybox="product"][data-id="more"]');
                const moreSrc  = document.getElementById('moreLinks');
                if (!moreTrig || !moreSrc) return;

                const html = moreSrc.innerHTML;        
                const wrapper = `<div class="more-grid">${html}</div>`; 
                moreTrig.setAttribute('data-type', 'html');
                moreTrig.setAttribute('data-src', wrapper);

                moreTrig.removeAttribute('href');
            })();


            Fancybox.bind('[data-fancybox="product"]', {
                // groupAll: true,
                dragToClose: false,


                contentClick: false,  
                wheel: false,         
                Images: {

                    Panzoom: {
                        zoom: true,       
                        touch: true,      
                        panOnlyZoomed: true
                    }
                },

                Toolbar: {
                    enabled: true,
                    display: {
                        left: ['close'],
                        middle: ['infobar'],
                        right: []
                    }
                },

                Carousel: {
                    Arrows: false, 
                    Dots: false    
                },

                on: {

                    'Carousel.init': (fb, carousel) => {
                        const isMore = (s) => {
                            if (!s) return false;
                            if (s.triggerEl?.dataset?.id === 'more') return true;
                            const src = s.src;
                            if (typeof src === 'string') return src === '#moreLinks';
                            return src?.nodeType === 1 && src.id === 'moreLinks';
                        };

                        let idx = carousel.slides.findIndex(isMore);

                        if (idx === -1) {
                            const tr = document.querySelector('#heroCarousel [data-fancybox="product"][data-id="more"]');
                            if (tr) {
                                carousel.addSlide({ type: 'html', src: '#moreLinks', triggerEl: tr });
                                idx = carousel.slides.length - 1;
                            }
                        }

                        if (idx > -1 && idx !== carousel.slides.length - 1) {
                            const slide = carousel.slides[idx];
                            carousel.removeSlide(idx);
                            carousel.addSlide(slide);
                        }
                    },

                    ready: (fb) => {
                        console.log('---- .ready');

                        fb.container.classList.remove('is-thumbs');

                        createInfoBar(fb);

                        requestAnimationFrame(() => updateInfoBar(fb));

                        hideThumbs(fb);

                        const fav = document.createElement('button');
                        fav.className = 'fav-btn';
                        fav.type = 'button';
                        fav.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
                        fav.setAttribute('aria-label', 'Добавить в избранное');
                        fav.addEventListener('click', (e) => { e.stopPropagation(); onFavClick(fb); });
                        fb.container.appendChild(fav);
                        fb._favBtn = fav;
                        updateFavBtn(fb);

                        // Share button in the Fancybox viewer (above the heart) — copies the car URL.
                        const shr = document.createElement('button');
                        shr.className = 'share-btn';
                        shr.type = 'button';
                        shr.innerHTML = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="7" cy="12" r="2" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="17" cy="6" r="2" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="17" cy="18" r="2" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M15 7L8.5 11" stroke="currentColor" stroke-width="2"/><path d="M8.5 13.5L15 17" stroke="currentColor" stroke-width="2"/></svg>';
                        shr.setAttribute('aria-label', 'Distribuie');
                        shr.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const url = location.origin + location.pathname;
                            if (navigator.share) { try { navigator.share({ url: url }); } catch(err){} }
                            else if (navigator.clipboard) { navigator.clipboard.writeText(url).then(function(){ shr.classList.add('is-copied'); setTimeout(function(){ shr.classList.remove('is-copied'); }, 1500); }).catch(function(){}); }
                        });
                        fb.container.appendChild(shr);

                        const el = fb.container;

                        const THRESH = 40;
                        let activeId = null, startX = 0, startY = 0;

                        fb.__onTouchStart = function(ev){
                            if (ev.target.closest('.f-thumbs')) return;

                            const t = ev.touches && ev.touches[0];
                            if (!t) return;

                            if (ev.touches.length !== 1) { activeId = null; return; }

                            activeId = t.identifier;
                            startX = t.clientX;
                            startY = t.clientY;
                        };

                        fb.__onTouchEnd = function(ev){
                            if (activeId === null) return;

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
                            const THRESH_V = 40;

                            const slide = fb.getSlide && fb.getSlide();
                            const pz = slide && (slide.Panzoom || slide.panzoom || slide._Panzoom || slide._panzoom || null);
                            const scale = (pz && (pz.content?.scale ?? pz.scale)) || 1;

                            if (absY > absX && absY > THRESH_V){
                                if (dy < 0){
                                    showThumbs(fb);
                                } else {
                                    fb.close();
                                }
                                activeId = null;
                                return;
                            }

                            if (scale > 1 && absX > absY) {
                                const THRESH_H = 90;
                                const RATIO_DOM = 1.25;
                                const EDGE_TOL = 16;

                                if (absX > THRESH_H && absX > absY * RATIO_DOM) {
                                    let atEdge = false;

                                    const c = pz && pz.content;
                                    if (c && typeof c.x === 'number' && typeof c.minX === 'number' && typeof c.maxX === 'number') {
                                        if (dx < 0) atEdge = (c.x <= c.minX + EDGE_TOL);
                                        else       atEdge = (c.x >= c.maxX - EDGE_TOL);
                                    } else {
                                        atEdge = absX > 140;
                                    }

                                    if (atEdge) {
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

                        el.addEventListener('touchstart', fb.__onTouchStart, { passive: true });
                        el.addEventListener('touchend',   fb.__onTouchEnd,   { passive: true });
                        el.addEventListener('touchcancel',fb.__onTouchEnd,   { passive: true });
                    },

                    'Carousel.change': (fb) => {
                        console.log('Carousel.change');

                        updateInfoBar(fb);
                        updateFavBtn(fb);
                    },

                    'Carousel.ready': (fb) => {
                        console.log('Carousel.ready');
                        hideThumbs(fb);
                        updateInfoBar(fb);
                        updateFavBtn(fb);

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
        function stripHash(u){
            try {
                const x = new URL(u, window.location.href);
                return x.origin + x.pathname + x.search; 
            } catch(_){
                return ''; 
            }
        }

        function getNavType(){
            try {
                const nav = performance.getEntriesByType('navigation')[0];
                if (nav && nav.type) return nav.type; 
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

        /* disabled: auto-open fullscreen gallery on mobile
        (function autoOpenGalleryOnce(){
            const navType = getNavType();           
            const ref     = document.referrer || '';
            const here    = stripHash(window.location.href);
            const from    = stripHash(ref);


            const okByType   = (navType === 'navigate');
            const hasRef     = !!from;
            const isSamePage = hasRef && (from === here);

            if (okByType && hasRef && !isSamePage) {
                setTimeout(function () {
                    const slides = Array.from(document.querySelectorAll('.f-carousel__slide.is-selected'));
                    if (slides.length && $(window).width() < 640 ) {
                        location.href = '#product-1';
                    }
                },500);
            }
        })();
        */
    </script>
<?php } ?>
