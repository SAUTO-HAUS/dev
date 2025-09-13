<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">

<script src="/<?php e(_DEFAULT)?>/js/js.cookie.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>

<script src="/<?php e(_DEFAULT)?>/js/sitescripts.js?d=<?php echo date("GYimsd", filemtime(_DEFAULT.'/js/sitescripts.js')); ?>"></script>
<script src="/<?php e(_SITE)?>/js/sitescripts.js?d=<?php echo date("GYimsd", filemtime(_SITE.'/js/sitescripts.js')); ?>"></script>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KRRLB4X');</script>
<!-- End Google Tag Manager -->

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-964347386"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-964347386');
</script>

<?php //<link rel="alternate" href="http://www.example.com/" hreflang="x-default">
	
$uri_x = (isset($t_mp[2])) ? str_replace('/'.$_COOKIE['lang'].'/', '/', $uri) : str_replace('/'.$_COOKIE['lang'], '', $uri);

//echo '<link rel="alternate" hreflang="x-default" href="'.$protocol.'://'.$http_host.''.$uri_x.'" />';
echo '<link rel="canonical" href="'.$protocol.'://'.$http_host.'/'.$_COOKIE['lang'].''.$uri_x.'" />';

foreach ($lang_arr as $lang){
	echo '<link rel="alternate" hreflang="'.$lang.'" href="'.$protocol.'://'.$http_host.'/'.$lang.''.$uri_x.'" />';
}
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
<link rel="stylesheet" type="text/css" href="/<?php e(_SITE)?>/css/media.css?d=<?php echo date("GYimsd", filemtime(_SITE.'/css/media.css')); ?>">
<link rel="stylesheet" href="/<?php e(_SITE)?>/css/consent-modal-v2.css?d=<?php echo date("GYimsd", filemtime(_SITE.'/css/consent-modal-v2.css')); ?>">
<!--
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300&display=swap" rel="stylesheet">
-->

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

<?php 
	if (isset($t_mp[2])&&$t_mp[2]=='cars') {
	
	/*
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_catalog WHERE `id`= :id AND `visible`="1" AND `active`="1"');
	$pdo->execute(array( 'id' => $url_id ));

	foreach ($pdo as $row){
		
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_photo WHERE `id`= :id AND main=1');
		$pdo->execute(array( 'id' => $url_id ));
		foreach ($pdo as $row2){
			$c_photo_name = $row2['name'];
		}
		
		echo '
		<script type="application/ld+json">
		{
			"@context": "https://schema.org/",
			"@type": "Vehicle",
			"name": "'.$row['brand_name'].' '.$row['model_name'].'",
			"image": "https://www.sauto.md/media/images/upload/car/'.$row['photo_path'].'/'.$row['id'].'/med/'.$c_photo_name.'.jpg",
			"productionDate": "'.$row['year'].'",
			"bodyType": "'.$row['bodytype'].'",
			"mileageFromOdometer": "'.$row['mileage'].' km",
			"vehicleEngine" : {
				"@type": "EngineSpecification",
				"name": "'.$row['engine'].' cc"
			},
			"description": "'.$row['bodytype'].' '.$row['year'].' '.$row['brand_name'].' '.$row['model_name'].' ('.$row['price'].' EUR) id:'.$row['id'].'",
			"offers":{
				"@type":"Offer",
				"price":"'.$row['price'].'.00",
				"priceCurrency":"EUR",
				"seller":{
					"@type":"Organization",
					"address":{
						"@type":"PostalAddress",
						"addressCountry":"MD",
						"streetAddress":"Calea Mosilor 11",
						"postalCode":"MD2024",
						"addressLocality":"Chisinau"
					},
					"telephone":"<?php echo PhoneHelper::getGeneralPhone(); ?>",
					"name":"Sauto SRL"
				}
			}
		}
		</script>
		';
		
	;}
	*/	
		/*echo '
		<script type="application/ld+json">
		{
			"@context": "https://schema.org/",
			"@type": "Vehicle",
			"name": "Ford C-MAX",
			"image": "https://www.sauto.md/media/images/upload/car/05a5/751d/9bf3/6977/med/0aa3d4ceac7d2e5f90eb96c8921270a6.jpg",
			"productionDate": "2013",
			"bodyType": "Universal",
			"mileageFromOdometer": "167000 km",
			"vehicleEngine" : {
				"@type": "EngineSpecification",
				"name": "998 cc"
			},
			"description": "Universal 2013 Ford C-MAX (7200 EUR)",
			"offers":{
				"@type":"Offer",
				"price":"7200.00",
				"priceCurrency":"EUR",
				"availabilityStarts":"2017-05-29",
				"seller":{
					"@type":"Organization",
					"address":{
						"@type":"PostalAddress",
						"addressCountry":"MD",
						"streetAddress":"Calea Mosilor 11",
						"postalCode":"MD2024",
						"addressLocality":"Chisinau"
					},
					"telephone":"<?php echo PhoneHelper::getGeneralPhone(); ?>",
					"name":"Sauto SRL"
				}
			}
		}
		</script>
		';
		
		echo '
		<script type="application/ld+json">
		{
			"@context": "https://schema.org/",
			"@type": "AutoDealer",
			"name": "Sauto.md",
			"image": "https://www.sauto.md/media/images/site/sauto_new_logo.png",
			"legalName": "Sauto SRL",
			"telephone": "<?php echo PhoneHelper::getGeneralPhone(); ?>",
			"address": "Moldova, Chisinau, Str. Calea Mosilor 11, MD2024",
			"makesOffer" : {
				"@type": "Offer",
				"priceSpecification": {
					"@type": "UnitPriceSpecification",
					"priceCurrency": "EUR",
					"price": "7200"
				},
				"itemOffered" : {
					"@type": "Car",
					"name": "Ford C-MAX",
					"image": "https://www.sauto.md/media/images/upload/car/05a5/751d/9bf3/6977/high/0aa3d4ceac7d2e5f90eb96c8921270a6.jpg",
					"modelDate": "2013",
					"bodyType": "Universal",
					"mileageFromOdometer": "167000 km",
					"vehicleEngine" : {
						"@type": "EngineSpecification",
						"name": "998 cc"
					},
					"description": "Universal 2013 Ford C-MAX (7200 EUR)"
				}
				
			}
		}
		</script>
		';*/
}
?>

<style>

</style>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
	'ad_storage': 'denied',
	'ad_user_data': 'denied',
	'ad_personalization': 'denied',
	'analytics_storage': 'denied'
});

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
	// Cookie version system - increment this number to force all users to see consent banner again
	const CONSENT_VERSION = 3;
	localStorage.setItem( 'z_cks_alwd', '{"ad":'+(ad_cons?'true':'false')+', "usrDt":'+(usr_dt_cons?'true':'false')+', "prsn":'+(pers_cons?'true':'false')+', "ana":'+(ana_cons?'true':'false')+', "version":'+CONSENT_VERSION+'}' );
	localStorage.setItem( 'z_cks_alwd_t', unixTime() );
	localStorage.setItem( 'z_cks_alwd_v', '20325' );
}

$(document).ready(function(){
	
	if ( localStorage.getItem('z_cks_alwd') !== null && (localStorage.getItem('z_cks_alwd_t') !== null && parseInt( localStorage.getItem('z_cks_alwd_t') )>=1719846403) && (localStorage.getItem('z_cks_alwd_v') !== null && localStorage.getItem('z_cks_alwd_v')=='20325') ){
		const cksAlwdObj = JSON.parse( localStorage.getItem('z_cks_alwd') );
		
		// Check consent version - if version doesn't match, show banner again
		const CURRENT_VERSION = 3;
		const storedVersion = cksAlwdObj['version'] || 1;
		
		if (storedVersion >= CURRENT_VERSION) {
			var adCks = cksAlwdObj['ad']?true:false; var usrDtCks = cksAlwdObj['usrDt']?true:false; var prsnCks = cksAlwdObj['prsn']?true:false; var anaCks = cksAlwdObj['ana']?true:false;
			updateConsent(adCks, usrDtCks, prsnCks, anaCks);
		} else {
			// Version mismatch - clear old consent and show banner
			localStorage.removeItem('z_cks_alwd');
			localStorage.removeItem('z_cks_alwd_t');
			localStorage.removeItem('z_cks_alwd_v');
			document.getElementById('cons_bx').style.display = 'flex';
		}
		//if ( unixTime() >= localStorage.getItem('z_cks_alwd_t') ){}
	}else{
		if (localStorage.getItem('z_cks_alwd') !== null){localStorage.removeItem( 'z_cks_alwd' );}
		if (localStorage.getItem('z_cks_alwd_t') !== null){localStorage.removeItem( 'z_cks_alwd_t' );}
		if (localStorage.getItem('z_cks_alwd_v') !== null){localStorage.removeItem( 'z_cks_alwd_v' );}
		document.getElementById('cons_bx').style.display = 'flex';
	}
})
</script>

<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
<script async src="https://cdn.ampproject.org/v0.js"></script>
<script src="//code.jivosite.com/widget/TZSdzj6D1H" async></script>

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