<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;?>

<link rel="preconnect" href="https://code.jquery.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<script data-cfasync="false" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script data-cfasync="false" src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js" defer></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css"></noscript>

<script src="/<?php e(_DEFAULT)?>/js/js.cookie.min.js" defer></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js" defer></script>

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
        webvisor:true
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
        $car_price     = $car_row['prc'] ?? '';
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

<!-- AMP removed - site is not AMP, was loading unnecessary ~100KB -->
