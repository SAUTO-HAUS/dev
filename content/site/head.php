<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">

<script src="/<?php e(_DEFAULT)?>/js/js.cookie.min.js"></script>

<!-- Google Tag Manager - COMPLETELY DISABLED UNTIL CONSENT -->
<script>
// GTM will be loaded only after explicit consent via ConsentManager
console.log('GTM blocked until consent is given');
</script>
<!-- End Google Tag Manager -->

<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>

<script src="/<?php e(_DEFAULT)?>/js/sitescripts.js?d=<?php echo date("GYimsd", filemtime(_DEFAULT.'/js/sitescripts.js')); ?>"></script>
<script src="/<?php e(_SITE)?>/js/sitescripts.js?d=<?php echo date("GYimsd", filemtime(_SITE.'/js/sitescripts.js')); ?>"></script>

<!-- Google Ads - COMPLETELY DISABLED UNTIL CONSENT -->
<script>
// Google Ads will be loaded only after explicit consent via ConsentManager
console.log('Google Ads blocked until consent is given');
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


<!-- Modern Consent Manager CSS -->
<link rel="stylesheet" href="/content/site/css/consent-modal.css">

<!-- GDPR PROTECTION - Block all tracking until consent -->
<script>
// Block all tracking functions until consent is given
window.dataLayer = window.dataLayer || [];

// Override tracking functions until consent is given
window.gtag = function() {
    if (window.consentManager && 
        (window.consentManager.getConsent('analytics_storage') || 
         window.consentManager.getConsent('ad_storage'))) {
        window.dataLayer.push(arguments);
        // Load Google Analytics after consent
        if (!window._gaLoaded && window.consentManager.getConsent('analytics_storage')) {
            const script = document.createElement('script');
            script.async = true;
            script.src = 'https://www.googletagmanager.com/gtag/js?id=G-TP4GJ51GSL';
            document.head.appendChild(script);
            window._gaLoaded = true;
            console.log('Google Analytics loaded after consent');
        }
    } else {
        console.log('gtag blocked - no consent');
    }
};

// Override fbq to block calls without consent  
window.fbq = function() {
    if (window.consentManager && window.consentManager.getConsent('ad_storage')) {
        if (window._fbq_real) {
            window._fbq_real.apply(this, arguments);
        }
    } else {
        console.log('fbq blocked - no consent');
    }
};

// Block Yandex Metrica
window.yaCounter87984800 = {
    reachGoal: function() {
        if (window.consentManager && window.consentManager.getConsent('analytics_storage')) {
            if (window._ym_real) {
                window._ym_real.reachGoal.apply(this, arguments);
            }
        } else {
            console.log('Yandex Metrica blocked - no consent');
        }
    }
};
</script>

<!-- Modern Consent Manager JavaScript -->
<script src="/content/site/js/consent-manager.js"></script>

<script>
// Legacy support for existing consent functions

// Backward compatibility functions
function showPref() {
	if (window.consentManager) {
		window.consentManager.showModal();
	}
}

function hidePref() {
	if (window.consentManager) {
		window.consentManager.hideConsentInterface();
	}
}

// Legacy setConsent function for backward compatibility
function setConsent(adCons, usrDtCons, persCons, anaCons) {
	if (window.consentManager) {
		window.consentManager.acceptCustom({
			functionality_storage: true,
			security_storage: true,
			ad_storage: adCons,
			ad_user_data: usrDtCons,
			ad_personalization: persCons,
			analytics_storage: anaCons,
			personalization_storage: anaCons
		});
	}
}

function savePref() {
	if (window.consentManager) {
		window.consentManager.saveCustomPreferences();
	}
}

// ConsentManager will be initialized by the included JS file

// Clean up old consent data on page load
$(document).ready(function(){
	// Remove old consent storage keys
	['z_cks_alwd', 'z_cks_alwd_t', 'z_cks_alwd_v'].forEach(key => {
		if (localStorage.getItem(key) !== null) {
			localStorage.removeItem(key);
		}
	});
});
</script>

<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
<script async src="https://cdn.ampproject.org/v0.js"></script>
<script src="//code.jivosite.com/widget/TZSdzj6D1H" async></script>

<!-- Facebook Pixel - COMPLETELY DISABLED UNTIL CONSENT -->
<script>
// Facebook Pixel will be loaded only after explicit consent via ConsentManager
console.log('Facebook Pixel blocked until consent is given');
</script>
<!-- End Facebook Pixel Code -->