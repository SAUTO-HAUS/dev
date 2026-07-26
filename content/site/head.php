<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;?>

<link rel="preconnect" href="https://code.jquery.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<script data-cfasync="false" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php
if (isset($t_mp[2]) && $t_mp[2] === 'rent') { ?>
<script data-cfasync="false" src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js" defer></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js" defer></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css"></noscript>
<?php } ?>

<script src="/<?php e(_DEFAULT)?>/js/js.cookie.min.js" defer></script>

<script src="/<?php e(_DEFAULT)?>/js/sitescripts.js?d=<?php echo date("GYimsd", filemtime(_DEFAULT.'/js/sitescripts.js')); ?>" defer></script>
<script src="/<?php e(_SITE)?>/js/sitescripts.js?d=<?php echo date("GYimsd", filemtime(_SITE.'/js/sitescripts.js')); ?>" defer></script>
<script src="/<?php e(_SITE)?>/js/faceted-filter.js?d=<?php echo date("GYimsd", filemtime(_SITE.'/js/faceted-filter.js')); ?>" defer></script>

<!-- Product Card Slider Assets -->
<link rel="stylesheet" href="/content/site/components/product-card-slider/product-card-slider.css?v=<?php echo date('GYimsd', filemtime(_SITE . '/components/product-card-slider/product-card-slider.css')); ?>">
<script src="/content/site/components/product-card-slider/product-card-slider.js?v=<?php echo date('GYimsd', filemtime(_SITE . '/components/product-card-slider/product-card-slider.js')); ?>" defer></script>

<link rel="stylesheet" href="/content/site/css/brand_seo.css?v=<?php echo date('GYimsd', filemtime(_SITE . '/css/brand_seo.css')); ?>">

<!-- Facebook Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '701415057290990');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=701415057290990&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
 
<!-- Google Tag Manager -->
<script data-cfasync="false">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KRRLB4X');</script>
<!-- End Google Tag Manager -->

<!-- Google tag (gtag.js) -->
<script data-cfasync="false" async src="https://www.googletagmanager.com/gtag/js?id=G-TP4GJ51GSL"></script>
<script data-cfasync="false">
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-TP4GJ51GSL');
  gtag('config', 'AW-964347386');
</script>

<!-- Yandex.Metrika counter -->
<script type="text/javascript">
   (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
   m[i].l=1*new Date();
   for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
   k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
   (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

   ym(100579107, "init", {
        clickmap:true,
        trackLinks:true,
        accurateTrackBounce:true,
        webvisor:false
   });
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/100579107" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->

<?php
// Resolve current language safely — Googlebot does not send cookies, so falling back to
// $_COOKIE['lang'] alone produces "/" canonical/hreflang URLs for the crawler.
$lang_for_url = $_COOKIE['lang'] ?? $zlng ?? 'ro';

$uri_x = (isset($t_mp[2])) ? str_replace('/'.$lang_for_url.'/', '/', $uri) : str_replace('/'.$lang_for_url, '', $uri);

// Strip query string from canonical to avoid duplicate content (filters/sorting indexed as same page)
// Exception: catalog pages already use clean URLs for brand/model paths
$canonical_uri = strpos($uri_x, '?') !== false ? substr($uri_x, 0, strpos($uri_x, '?')) : $uri_x;
$canonical_url = $protocol.'://'.$http_host.'/'.$lang_for_url.$canonical_uri;

echo '<link rel="canonical" href="'.$canonical_url.'" />';

foreach ($lang_arr as $lang){
	echo '<link rel="alternate" hreflang="'.$lang.'" href="'.$protocol.'://'.$http_host.'/'.$lang.$canonical_uri.'" />';
}
// x-default points to the Romanian version as the site's default — used by Google when
// the visitor's language does not match any of the declared hreflang variants.
echo '<link rel="alternate" hreflang="x-default" href="'.$protocol.'://'.$http_host.'/ro'.$canonical_uri.'" />';
?>

<link rel="apple-touch-icon" sizes="180x180" href="/<?php e(_SITE_IMG)?>/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/<?php e(_SITE_IMG)?>/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/<?php e(_SITE_IMG)?>/favicon/favicon-16x16.png">
<link rel="manifest" href="/<?php e(_SITE_IMG)?>/favicon/manifest.json">
<link rel="mask-icon" href="/<?php e(_SITE_IMG)?>/favicon/safari-pinned-tab.svg" color="#d3a6a9">
<link rel="shortcut icon" href="/<?php e(_SITE_IMG)?>/favicon/favicon.ico">
<meta name="apple-mobile-web-app-title" content="<?php e($site_name);?>">
<meta name="application-name" content="<?php e($site_name);?>">
<meta name="msapplication-config" content="/<?php e(_SITE_IMG)?>/favicon/browserconfig.xml">
<meta name="theme-color" content="#ffffff">

<link rel="stylesheet" type="text/css" href="/<?php e(_DEFAULT)?>/css/default.css?d=<?php echo date("GYimsd", filemtime(_DEFAULT.'/css/default.css')); ?>">
<link rel="stylesheet" type="text/css" href="/<?php e(_SITE)?>/css/style.css?d=<?php echo date("GYimsd", filemtime(_SITE.'/css/style.css')); ?>">
<link rel="stylesheet" type="text/css" href="/<?php e(_SITE)?>/css/mobile-call-button.css?d=<?php echo date("GYimsd", filemtime(_SITE.'/css/mobile-call-button.css')); ?>">
<link rel="stylesheet" type="text/css" href="/<?php e(_SITE)?>/css/media.css?d=<?php echo date("GYimsd", filemtime(_SITE.'/css/media.css')); ?>">
<link rel="stylesheet" href="/<?php e(_SITE)?>/css/consent-modal-v2.css?d=<?php echo date("GYimsd", filemtime(_SITE.'/css/consent-modal-v2.css')); ?>">
<!--
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
-->
<!-- Font Awesome - load asynchronously to not block render -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" media="print" onload="this.media='all'" />
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></noscript>

<?php 
include('plugins/dev_tools/meta_gen.php');
?>

<?php /*if (isset($t_mp[2])&&$t_mp[2]=='tyres'){ ?>
	<!-- Event snippet for tyre conversion page -->
	<script>
		gtag('event', 'conversion', {
			'send_to': 'AW-964347386/nOiUCITNz4sBEPqL68sD',
			'transaction_id': ''
		});
	</script>
<?php } */ ?>

<meta name="google-site-verification" content="xi-sPfXyALsoFfQj2jpMiB2bHLkIsxbEM7tPoq9vlRQ" />
<meta name="yandex-verification" content="61bf9aa380a8610d" />

<?php
// =====================================================================
// STRUCTURED DATA (Schema.org JSON-LD)
// =====================================================================

$_z2 = $t_mp[2] ?? '';
$_z3 = $t_mp[3] ?? '';

// ----- AutoDealer schema pe HOME (pagina principala) -----
if ($_z2 === '' || $_z2 === 'home') {
    $dealerPhone = PhoneHelper::getGeneralPhone();
    echo '
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "AutoDealer",
        "name": "Sauto.md",
        "legalName": "Sauto SRL",
        "url": "https://www.sauto.md/",
        "logo": "https://www.sauto.md/media/images/site/sauto_new_logo_black.png",
        "image": "https://www.sauto.md/media/images/site/sauto_new_logo_black.png",
        "telephone": "'.$dealerPhone.'",
        "email": "info@sauto.md",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Calea Moșilor 11",
            "addressLocality": "Chișinău",
            "postalCode": "MD2024",
            "addressCountry": "MD"
        },
        "areaServed": {
            "@type": "Country",
            "name": "Moldova"
        },
        "sameAs": [
            "https://www.facebook.com/sauto.md",
            "https://www.instagram.com/sauto.md/",
            "https://www.tiktok.com/@sauto.md",
            "https://t.me/sautohaus"
        ]
    }
    </script>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Sauto.md",
        "url": "https://www.sauto.md/",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "https://www.sauto.md/'.$_COOKIE['lang'].'/cars?tg=fltr&q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    ';
}

// ----- Vehicle schema pe paginile detaliu (cars/15234, ordercars/15234) -----
if (in_array($_z2, ['cars', 'ordercars'], true) && is_numeric($_z3)) {
    $car_id = (int)$_z3;
    $car_cond = ($_z2 === 'cars') ? 'in_stock' : 'on_order';

    $pdo_car = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id AND `vis`="1" AND `act`="1" AND `catalog_type`=:ct LIMIT 1');
    $pdo_car->execute(['id' => $car_id, 'ct' => $car_cond]);
    $car_row = $pdo_car->fetch(PDO::FETCH_ASSOC);

    if ($car_row) {
        $pdo_pht = $db->prepare('SELECT `name` FROM '.$prefx.'_car_pht WHERE `it_id`=:id AND `main`="1" LIMIT 1');
        $pdo_pht->execute(['id' => $car_id]);
        $pht_row = $pdo_pht->fetch(PDO::FETCH_ASSOC);
        $car_img = $pht_row
            ? 'https://www.sauto.md/media/images/upload/car/'.$car_row['p_path'].'/'.$car_row['id'].'/high/'.$pht_row['name'].'.jpg'
            : 'https://www.sauto.md/media/images/site/sauto_new_logo_black.png';

        $car_name      = trim(($car_row['br_nm'] ?? '').' '.($car_row['mo_nm'] ?? ''));
        $car_year      = $car_row['yr'] ?? '';
        $car_mileage   = $car_row['mlg'] ?? '';
        $car_engine_cc = $car_row['vol'] ?? '';
        $car_price     = (!empty($car_row['prc_n']) && $car_row['prc_n'] > 0 && $car_row['prc_n'] < ($car_row['prc'] ?? 0))
                          ? $car_row['prc_n'] : ($car_row['prc'] ?? '');
        $car_currency  = $car_row['cur'] ?? 'EUR';
        $car_currency  = strtoupper($car_currency) === 'EUR' ? 'EUR' : (strtoupper($car_currency) === 'USD' ? 'USD' : 'EUR');
        $car_color     = $car_row['clr'] ?? '';
        $car_bodytype  = $car_row['bt'] ?? '';
        $car_fuel      = $car_row['fl'] ?? '';
        $car_trans     = $car_row['tra'] ?? '';
        $car_vin       = $car_row['vin'] ?? '';

        $dealerPhone = PhoneHelper::getGeneralPhone();
        $availability = ($_z2 === 'cars') ? 'InStock' : 'PreOrder';

        $vehicle = [
            '@context' => 'https://schema.org',
            '@type'    => 'Vehicle',
            'name'     => $car_name,
            'image'    => $car_img,
            'url'      => 'https://www.sauto.md/'.$_COOKIE['lang'].'/'.$_z2.'/'.$car_id,
            'brand'    => [
                '@type' => 'Brand',
                'name'  => $car_row['br_nm'] ?? '',
            ],
            'model'              => $car_row['mo_nm'] ?? '',
            'vehicleIdentificationNumber' => $car_vin ?: null,
            'modelDate'          => $car_year ? (string)$car_year : null,
            'productionDate'     => $car_year ? (string)$car_year : null,
            'mileageFromOdometer'=> $car_mileage ? ['@type'=>'QuantitativeValue','value'=>(int)$car_mileage,'unitCode'=>'KMT'] : null,
            'vehicleEngine'      => $car_engine_cc ? ['@type'=>'EngineSpecification','engineDisplacement'=>['@type'=>'QuantitativeValue','value'=>(int)$car_engine_cc,'unitCode'=>'CMQ']] : null,
            'color'              => $car_color ? ($lng['l']['car']['clr'][$car_color] ?? $car_color) : null,
            'bodyType'           => $car_bodytype ? ($lng['l']['car']['bt'][$car_bodytype] ?? $car_bodytype) : null,
            'fuelType'           => $car_fuel ? ($lng['l']['car']['fl'][$car_fuel] ?? $car_fuel) : null,
            'vehicleTransmission'=> $car_trans ? ($lng['l']['car']['tra'][$car_trans] ?? $car_trans) : null,
            'offers'             => [
                '@type'         => 'Offer',
                'price'         => $car_price ? (string)$car_price : '0',
                'priceCurrency' => $car_currency,
                'availability'  => 'https://schema.org/'.$availability,
                'itemCondition' => 'https://schema.org/UsedCondition',
                'url'           => 'https://www.sauto.md/'.$_COOKIE['lang'].'/'.$_z2.'/'.$car_id,
                'seller'        => [
                    '@type'    => 'AutoDealer',
                    'name'     => 'Sauto.md',
                    'telephone'=> $dealerPhone,
                    'address'  => [
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => 'Calea Moșilor 11',
                        'addressLocality' => 'Chișinău',
                        'postalCode'      => 'MD2024',
                        'addressCountry'  => 'MD',
                    ],
                ],
            ],
        ];

        // Strip null fields for clean JSON
        $vehicle = array_filter($vehicle, function($v) { return $v !== null; });

        echo '
        <script type="application/ld+json">'.json_encode($vehicle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).'</script>
        ';
    }
    unset($pdo_car, $car_row, $pdo_pht, $pht_row);
}

// ----- BreadcrumbList pe paginile catalog cu brand/model -----
if (in_array($_z2, ['cars', 'ordercars'], true) && isset($t_mp[3]) && !is_numeric($t_mp[3]) && !empty($t_mp[3])) {
    $brand_code = str_replace('-', '_', $t_mp[3]);
    $brand_name = '';
    $pdo_b = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
    $pdo_b->execute(['br' => $brand_code]);
    foreach ($pdo_b as $row) { $brand_name = $row['br_nm']; }

    if (!empty($brand_name)) {
        $crumbs = [
            ['@type'=>'ListItem','position'=>1,'name'=>'Sauto','item'=>'https://www.sauto.md/'.$_COOKIE['lang'].'/'],
            ['@type'=>'ListItem','position'=>2,'name'=>($_z2==='cars'?'Catalog':'La comandă'),'item'=>'https://www.sauto.md/'.$_COOKIE['lang'].'/'.$_z2],
            ['@type'=>'ListItem','position'=>3,'name'=>$brand_name,'item'=>'https://www.sauto.md/'.$_COOKIE['lang'].'/'.$_z2.'/'.$t_mp[3]],
        ];
        if (isset($t_mp[4]) && !empty($t_mp[4])) {
            $model_code = str_replace('-', '_', $t_mp[4]);
            $model_name = '';
            $pdo_m = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
            $pdo_m->execute(['br' => $brand_code, 'mo' => $model_code]);
            foreach ($pdo_m as $row) { $model_name = $row['mo_nm']; }
            if (!empty($model_name)) {
                $crumbs[] = ['@type'=>'ListItem','position'=>4,'name'=>$model_name,'item'=>'https://www.sauto.md/'.$_COOKIE['lang'].'/'.$_z2.'/'.$t_mp[3].'/'.$t_mp[4]];
            }
        }
        echo '
        <script type="application/ld+json">'.json_encode([
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $crumbs,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'</script>
        ';
    }
}
?>

<style>
/* Share button on car cards (top-right corner): black icon only, no label. */
.card-share-btn{position:absolute;top:4px;right:4px;z-index:5;box-sizing:border-box;display:flex;align-items:center;justify-content:center;gap:0;width:44px;height:44px;padding:0;background:rgba(255,255,255,0.8);border:1px solid rgba(255,255,255,0.6);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.12);color:#111;cursor:pointer;line-height:1;transition:background .15s,transform .12s,box-shadow .15s;-webkit-tap-highlight-color:transparent;}
.card-share-btn:hover{background:#000;border-color:#000;color:#fff;box-shadow:0 4px 14px rgba(0,0,0,.25);transform:translateY(-1px);}
.card-share-btn:active{transform:scale(.96);}
/* Label is hidden by default — only shown (as "Copiat") after a successful copy. */
.card-share-btn .csb-label{display:none;font-size:.62rem;font-weight:700;letter-spacing:.01em;color:#fff;}
.card-share-btn .csb-ico{width:26px;height:26px;display:block;fill:none;}
.card-share-btn .csb-ico path,.card-share-btn .csb-ico circle{fill:none;stroke:currentColor;}
/* After copy: red pill with white "Copiat", icon hidden, auto width for the text. */
.card-share-btn.is-copied{width:auto;min-width:36px;padding:0 10px;background:#d30909;border-color:#d30909;color:#fff;border-radius:10px;}
.card-share-btn.is-copied .csb-label{display:inline;color:#fff;}
.card-share-btn.is-copied .csb-ico{display:none;}
/* Make sure the card is a positioning context for the absolute button. */
.it.car,.car_box.similar{position:relative;}
/* Image wrapper inside cards: anchors the favorite button ON the photo.
   The original card CSS targets `.it > img` (direct child); the wrapper broke that
   chain, so re-apply the image-slot sizing to the wrapper + its inner image. */
.it.car .card-img-wrap{position:relative;display:block;width:100%;float:left;}
.it.car .card-img-wrap > img{width:100%;height:13rem;object-fit:cover;object-position:center;display:block;background:#fff url(/media/images/site/v2/no_image.svg) no-repeat center / 30%;}
.it.car .card-img-wrap > .mobile-card-slider{width:100%;display:block;}
@media (min-width:1600px) and (max-width:2199px){ .it.car .card-img-wrap > img{height:15rem;} }
@media (min-width:2200px) and (max-width:2999px){ .it.car .card-img-wrap > img{height:17rem;} }
@media (min-width:3000px){ .it.car .card-img-wrap > img{height:19rem;} }
@media (max-width:767px){ .it.car .card-img-wrap > img{height:13rem;} }
/* Mobile: bigger button, more spacing from the corner. */
@media (max-width:767px){
	.card-share-btn{top:12px;right:12px;width:44px;height:44px;border-radius:12px;}
	.card-share-btn .csb-label{font-size:.72rem;}
	.card-share-btn .csb-ico{width:26px;height:26px;}
	.card-share-btn.is-copied{width:auto;border-radius:12px;}
}
/* Favorite (heart) button — same white box as the share button, top-right of the image. */
.card-fav-btn{position:absolute;top:54px;right:4px;z-index:6;box-sizing:border-box;display:flex;align-items:center;justify-content:center;width:44px;height:44px;padding:0;background:rgba(255,255,255,0.8);border:1px solid rgba(255,255,255,0.6);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.12);cursor:pointer;line-height:1;transition:background .15s,transform .12s,box-shadow .15s;-webkit-tap-highlight-color:transparent;}
.card-fav-btn:hover{background:#fff;transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,0,0,.25);}
.card-fav-btn:active{transform:scale(.92);}
/* Heart icon: outline (empty) by default, fully red-filled when favorited. */
.card-fav-btn .cfb-ico{width:26px;height:26px;display:block;fill:none;stroke:#111;stroke-width:2;transition:fill .15s,stroke .15s;}
.card-fav-btn:hover .cfb-ico{stroke:#e2001a;}
.card-fav-btn.is-fav .cfb-ico{fill:#e2001a;stroke:#e2001a;}
.card-fav-btn.fav-pop{animation:favPop .28s ease;}
@keyframes favPop{0%{transform:scale(1);}45%{transform:scale(1.28);}100%{transform:scale(1);}}
/* Product page carousel + fullscreen slider — share on top, heart below. */
.wrapf-carousel > .card-share-btn{top:12px;right:12px;width:44px;height:44px;border-radius:12px;z-index:8;}
.wrapf-carousel > .card-share-btn .csb-ico{width:26px;height:26px;}
.wrapf-carousel > .card-fav-btn{top:64px;right:12px;width:44px;height:44px;border-radius:12px;z-index:8;}
.wrapf-carousel > .card-fav-btn .cfb-ico{width:26px;height:26px;}
/* Desktop product gallery big photo */
main > .pht_bx > .big_pht{position:relative;}
main > .pht_bx > .big_pht > .card-share-btn{top:12px;right:12px;width:44px;height:44px;border-radius:12px;z-index:8;}
main > .pht_bx > .big_pht > .card-share-btn .csb-ico{width:26px;height:26px;}
main > .pht_bx > .big_pht > .card-fav-btn{top:64px;right:12px;width:44px;height:44px;border-radius:12px;z-index:8;}
main > .pht_bx > .big_pht > .card-fav-btn .cfb-ico{width:26px;height:26px;}
/* In-place prev/next arrows on the big photo (change image without fullscreen). */
.big_pht > .bp-nav{position:absolute;top:50%;transform:translateY(-50%);width:44px;height:44px;z-index:7;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,0.78);box-shadow:0 1px 4px rgba(0,0,0,.18);cursor:pointer;opacity:0;transition:opacity .2s,background .15s,transform .12s;-webkit-tap-highlight-color:transparent;}
.big_pht:hover > .bp-nav{opacity:1;}
.big_pht > .bp-nav:hover{background:#fff;}
.big_pht > .bp-nav:active{transform:translateY(-50%) scale(.92);}
.big_pht > .bp-nav::before{content:"";width:14px;height:14px;border-right:3px solid #111;border-bottom:3px solid #111;box-sizing:border-box;}
.big_pht > .bp-left{left:12px;}
.big_pht > .bp-left::before{transform:rotate(135deg);margin-left:5px;}
.big_pht > .bp-right{right:12px;}
.big_pht > .bp-right::before{transform:rotate(-45deg);margin-right:5px;}
@media (max-width:767px){ .big_pht > .bp-nav{opacity:1;} }
#show_img .show-img-share{position:absolute;top:10%;right:20%;width:44px;height:44px;border-radius:12px;z-index:3;}
#show_img .show-img-share .csb-ico{width:26px;height:26px;}
#show_img .show-img-fav{position:absolute;top:calc(10% + 52px);right:20%;width:44px;height:44px;border-radius:12px;z-index:3;}
#show_img .show-img-fav .cfb-ico{width:26px;height:26px;}
#fav_float{position:fixed;right:40px;top:40px;z-index:11;display:none;flex-direction:column;align-items:center;gap:5px;text-decoration:none;cursor:pointer;}
#fav_float.has-favs{display:flex;}
#fav_float > .fav-float-ico{position:relative;display:block;width:50px;height:50px;}
#fav_float > .fav-float-ico > svg{width:50px;height:50px;padding:13px;box-sizing:border-box;fill:#e2001a;background:#fff;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,.2);transition:transform .15s,box-shadow .15s;}
#fav_float:hover > .fav-float-ico > svg{transform:translateY(-2px);box-shadow:0 5px 14px rgba(0,0,0,.3);}
#fav_float > .fav-float-lbl{font-family:"def_l";font-size:.95rem;font-weight:700;color:#000;line-height:1;text-align:center;}
#fav_float > .fav-float-ico > .fav-nav-count{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;line-height:18px;padding:0 5px;background:#e2001a;color:#fff;border-radius:9px;font-size:.7rem;font-weight:700;text-align:center;box-sizing:border-box;}
/* Mobile/portrait: favorites float goes bottom-left, directly above #to_top.
   Kept here (not in media.css) so it loads after the desktop rule above and wins. */
@media (max-width:999px), (orientation: portrait){
	#fav_float{top:auto;right:auto;left:10px;bottom:100px;}
	#fav_float > .fav-float-lbl{display:none;}
}
/* Favorites nav count badge */
.favorites .fav-nav-count{display:inline-block;min-width:18px;height:18px;line-height:18px;padding:0 5px;margin-left:4px;background:#e2001a;color:#fff;border-radius:9px;font-size:.7rem;font-weight:700;text-align:center;vertical-align:middle;}
main .gr.fav-page > h1{margin-top:1vw;margin-bottom:0.5vw;}
.fav-loading{padding:2rem 0;text-align:center;color:#888;font-size:1.5rem;}
.fav-empty{padding:2rem 1rem;text-align:center;color:#555;font-size:1.1rem;}
@media (max-width:767px){
	.card-fav-btn{top:62px;right:12px;width:44px;height:44px;border-radius:12px;}
	.card-fav-btn .cfb-ico{width:26px;height:26px;}
}
</style>
<script data-cfasync="false">
// Google Consent Mode v2
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
	'ad_storage': 'denied',
	'ad_user_data': 'denied',
	'ad_personalization': 'denied',
	'analytics_storage': 'denied'
});

function unixTime(){ return Math.floor(Date.now() / 1000); }

// Consent UI helper functions
function showPref(){document.getElementById('cons_bx').style.display = 'none'; var prefBox = document.getElementById('pref_bx'); prefBox.style.display = 'flex'; prefBox.style.visibility = 'visible'; prefBox.style.opacity = '1'; prefBox.style.alignItems = 'center'; prefBox.style.justifyContent = 'center';}
function hidePref(){document.getElementById('pref_bx').style.display = 'none'; document.getElementById('pref_bx').style.visibility = 'hidden'; document.getElementById('pref_bx').style.opacity = '0'; document.getElementById('cons_bx').style.display = 'flex';}

function setConsent(adCons, usrDtCons, persCons, anaCons) {
	updateConsent(adCons, usrDtCons, persCons, anaCons);
	document.getElementById('cons_bx').style.display = 'none';
}

function savePref() {
	const adCons = document.getElementById('ad-storage').checked;
	const usrDtCons = document.getElementById('ad-user-data').checked;
	const persCons = document.getElementById('ad-personalization').checked;
	const anaCons = document.getElementById('analytics-storage').checked;
	setConsent(adCons, usrDtCons, persCons, anaCons);
	document.getElementById('pref_bx').style.display = 'none';
	document.getElementById('pref_bx').style.visibility = 'hidden';
	document.getElementById('pref_bx').style.opacity = '0';
}

function updateConsent(ad_cons, usr_dt_cons, pers_cons, ana_cons ) {
	gtag('consent', 'update', {
		'ad_storage': ad_cons ? 'granted' : 'denied',
		'ad_user_data': usr_dt_cons ? 'granted' : 'denied',
		'ad_personalization': pers_cons ? 'granted' : 'denied',
		'analytics_storage': ana_cons ? 'granted' : 'denied'
	});
	// Send page_view after consent is granted for analytics
	if (ana_cons) {
		gtag('event', 'page_view', {
			page_title: document.title,
			page_location: window.location.href
		});
	}
	const CONSENT_VERSION = 5;
	localStorage.setItem( 'z_cks_alwd', '{"ad":'+(ad_cons?'true':'false')+', "usrDt":'+(usr_dt_cons?'true':'false')+', "prsn":'+(pers_cons?'true':'false')+', "ana":'+(ana_cons?'true':'false')+', "version":'+CONSENT_VERSION+'}' );
	localStorage.setItem( 'z_cks_alwd_t', unixTime() );
}

$(document).ready(function(){
	const ONE_YEAR = 365 * 24 * 3600;
	const CURRENT_VERSION = 5;
	const stored = localStorage.getItem('z_cks_alwd');
	const storedTime = parseInt( localStorage.getItem('z_cks_alwd_t') || '0' );

	function clearConsent() {
		localStorage.removeItem('z_cks_alwd');
		localStorage.removeItem('z_cks_alwd_t');
		localStorage.removeItem('z_cks_alwd_v');
	}

	if ( stored !== null && storedTime >= 1719846403 && (unixTime() - storedTime) < ONE_YEAR ) {
		const cksAlwdObj = JSON.parse( stored );
		const storedVersion = cksAlwdObj['version'] || 1;

		if (storedVersion >= CURRENT_VERSION) {
			var adCks = cksAlwdObj['ad']?true:false; var usrDtCks = cksAlwdObj['usrDt']?true:false; var prsnCks = cksAlwdObj['prsn']?true:false; var anaCks = cksAlwdObj['ana']?true:false;
			updateConsent(adCks, usrDtCks, prsnCks, anaCks);
		} else {
			clearConsent();
			document.getElementById('cons_bx').style.display = 'flex';
		}
	} else {
		clearConsent();
		document.getElementById('cons_bx').style.display = 'flex';
	}
})
</script>

<script data-cfasync="false">
// Card "Share" button: copy the car URL to clipboard, show check + "link copied" toast.
// Delegated on document so it also covers cards injected after load (similar prices, etc.).
(function(){
	function copyText(text){
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function(resolve, reject){
			try {
				var ta = document.createElement('textarea');
				ta.value = text; ta.setAttribute('readonly',''); ta.style.position='absolute'; ta.style.left='-9999px';
				document.body.appendChild(ta); ta.select();
				var ok = document.execCommand('copy');
				document.body.removeChild(ta);
				ok ? resolve() : reject();
			} catch(e){ reject(e); }
		});
	}
	function showCopied(btn){
		var label = btn.querySelector('.csb-label');
		if (label && btn._csbOrig == null) { btn._csbOrig = label.innerHTML; }
		btn.classList.add('is-copied');
		if (label) { label.textContent = btn.getAttribute('data-copied-text') || 'Link copiat'; }
		clearTimeout(btn._csbT);
		btn._csbT = setTimeout(function(){
			btn.classList.remove('is-copied');
			if (label && btn._csbOrig != null) { label.innerHTML = btn._csbOrig; }
		}, 1600);
	}
	document.addEventListener('click', function(e){
		var btn = e.target.closest && e.target.closest('.card-share-btn');
		if (!btn) return;
		// Inside an <a> card — don't follow the link.
		e.preventDefault();
		e.stopPropagation();
		var url = btn.getAttribute('data-share-url');
		if (!url) return;
		if (url.indexOf('http') !== 0) { url = location.origin + url; }
		// On phones, open the OS share sheet (Viber / Telegram / WhatsApp / Mail…).
		if (navigator.share) {
			try { navigator.share({ url: url }); } catch (err) {}
			return;
		}
		// Desktop (no Web Share API): copy the link + show "Copiat".
		copyText(url).then(function(){ showCopied(btn); }).catch(function(){});
	}, true);
})();
</script>

<script data-cfasync="false">
// Favorites (wishlist) — localStorage based, no account needed.
(function(){
	var KEY = 'sauto_favorites';

	function getFavs(){
		try { var v = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(v) ? v.map(Number).filter(Boolean) : []; }
		catch(e){ return []; }
	}
	function saveFavs(list){
		try { localStorage.setItem(KEY, JSON.stringify(list)); } catch(e){}
	}
	function toggleFav(id){
		id = Number(id); if (!id) return false;
		var list = getFavs(); var i = list.indexOf(id);
		if (i === -1) { list.push(id); } else { list.splice(i, 1); }
		saveFavs(list);
		return i === -1; // true if now favorited
	}

	// Reflect current state on every heart button + the nav count badge.
	function syncUI(){
		var favs = getFavs();
		document.querySelectorAll('.card-fav-btn').forEach(function(btn){
			var id = Number(btn.getAttribute('data-fav-id'));
			var on = id && favs.indexOf(id) !== -1;
			btn.classList.toggle('is-fav', !!on);
			// Tooltip: "remove" when already favorited, "add" otherwise.
			var t = on ? btn.getAttribute('data-fav-remove') : btn.getAttribute('data-fav-add');
			if (t) { btn.setAttribute('title', t); btn.setAttribute('aria-label', t); }
		});
		document.querySelectorAll('.fav-nav-count').forEach(function(b){
			b.textContent = favs.length;
			b.style.display = favs.length ? 'inline-block' : 'none';
		});
		// Floating favorites button: only show when there is at least one favorite.
		var floatBtn = document.getElementById('fav_float');
		if (floatBtn) { floatBtn.classList.toggle('has-favs', favs.length > 0); }
	}

	// Keep the fullscreen viewer (#show_img) heart pointed at the current car.
	function currentCarId(){
		var b = document.querySelector('.wrapf-carousel .card-fav-btn[data-fav-id], .pht_bx .card-fav-btn[data-fav-id]');
		return b ? b.getAttribute('data-fav-id') : '';
	}

	function renderFavEmptyIfNeeded(){
		var box = document.getElementById('fav_container');
		var empty = document.getElementById('fav_empty');
		if (!box || !empty) return;
		empty.style.display = box.querySelector('.it') ? 'none' : 'block';
	}


	document.addEventListener('click', function(e){
		var btn = e.target.closest && e.target.closest('.card-fav-btn');
		if (!btn) return;
		e.preventDefault();
		e.stopPropagation();
		// The fullscreen viewer heart has no id of its own — borrow the product's.
		if (btn.classList.contains('show-img-fav') && !btn.getAttribute('data-fav-id')) {
			btn.setAttribute('data-fav-id', currentCarId());
		}
		var id = btn.getAttribute('data-fav-id');
		if (!id) return;
		var nowFav = toggleFav(id);
		btn.classList.remove('fav-pop'); void btn.offsetWidth; btn.classList.add('fav-pop');
		syncUI();
		// B2B partner: mirror the favourite into their cabinet (gh3sp_b2b_saved_cars),
		// so saved cars show up under /b2b/cabinet. Fire-and-forget; localStorage
		// (above) stays the source of truth for the /favorites page.
		if (window.B2B_FAV && window.B2B_FAV.csrf) {
			var favBody = new URLSearchParams();
			favBody.set('tp', 'ste');
			favBody.set('fn', nowFav ? 'b2b_save_car' : 'b2b_unsave_car');
			favBody.set('car_id', id);
			favBody.set('csrf', window.B2B_FAV.csrf);
			fetch('/ajax.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: favBody.toString(),
				credentials: 'same-origin'
			}).catch(function(){});
			// Live-update the cabinet "saved" counter (present only on the cabinet page).
			var savedNum = document.querySelector('[data-b2b-count="saved"]');
			if (savedNum) {
				var n = (parseInt(savedNum.textContent, 10) || 0) + (nowFav ? 1 : -1);
				savedNum.textContent = n < 0 ? 0 : n;
			}
		}
		// On a favourites-style list — the guest /favorites page (#fav_container) or
		// the B2B cabinet grid (.b2b-cars) — removing a fav drops its card at once,
		// no refresh. The catalog is NOT one of these, so its cards stay put.
		if (!nowFav) {
			document.querySelectorAll('#fav_container [data-fav-id="'+id+'"], .b2b-cars [data-fav-id="'+id+'"]').forEach(function(card){
				var it = card.closest('.it');
				if (it) it.remove();
			});
			renderFavEmptyIfNeeded();
			// Cabinet: when the grid empties, reveal the (always-present) empty state.
			var cabGrid = document.querySelector('.b2b-cars');
			if (cabGrid && !cabGrid.querySelector('.it')) {
				var cabEmpty = document.getElementById('b2b_cabinet_empty');
				if (cabEmpty) cabEmpty.style.display = '';
				var cabWrap = document.getElementById('b2b_cabinet_grid') || cabGrid;
				cabWrap.style.display = 'none';
			}
		}
	}, true);

	// When opening the fullscreen viewer, point its heart + share button at the current car.
	document.addEventListener('click', function(e){
		if (e.target.closest && e.target.closest('.big_pht')) {
			var fav = document.querySelector('#show_img .show-img-fav');
			if (fav) { fav.setAttribute('data-fav-id', currentCarId()); setTimeout(syncUI, 0); }
			var sh = document.querySelector('#show_img .show-img-share');
			// The product page URL IS the car's share URL (strip any query/hash).
			if (sh) { sh.setAttribute('data-share-url', location.origin + location.pathname); }
		}
	});

	// /favorites page — fetch cards for the saved IDs via AJAX.
	function loadFavoritesPage(){
		var box = document.getElementById('fav_container');
		if (!box) return;
		var loading = document.getElementById('fav_loading');
		var empty = document.getElementById('fav_empty');
		var ids = getFavs();
		if (!ids.length) { if (empty) empty.style.display = 'block'; return; }
		if (loading) loading.style.display = 'block';

		var form = new URLSearchParams();
		form.append('tp', 'ste');
		form.append('fn', 'fav_cars');
		form.append('ids', ids.join(','));

		fetch('/ajax.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form.toString() })
			.then(function(r){ return r.json(); })
			.then(function(data){
				if (loading) loading.style.display = 'none';
				if (data && data.html && (data.count > 0)) {
					box.innerHTML = data.html;
					// Prune localStorage of IDs that no longer exist/are inactive.
					if (Array.isArray(data.ids)) {
						var valid = data.ids.map(Number);
						var cleaned = getFavs().filter(function(id){ return valid.indexOf(id) !== -1; });
						saveFavs(cleaned);
					}
					// Cards were injected after page load, so initialize their sliders + lazy-load now.
					try { if (typeof initSliderLazyLoading === 'function') initSliderLazyLoading(); } catch(e){}
					try { if (typeof initProductCardSliders === 'function') initProductCardSliders(); } catch(e){}
					try {
						if (typeof initMobileCardSliders === 'function' && (window.innerWidth <= 768 || /Mobile|Android|iPhone|iPad/.test(navigator.userAgent))) {
							initMobileCardSliders();
						}
					} catch(e){}
					syncUI();
				} else {
					if (empty) empty.style.display = 'block';
				}
			})
			.catch(function(){ if (loading) loading.style.display = 'none'; if (empty) empty.style.display = 'block'; });
	}

	// Expose so other scripts (e.g. the Fancybox viewer heart) can refresh all hearts.
	window.sautoFavSync = syncUI;

	function init(){ syncUI(); loadFavoritesPage(); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); }
	else { init(); }
})();
</script>

<!-- AMP removed - site is not AMP, was loading unnecessary ~100KB -->
