<?php defined( '_DOIT' ) or die( 'Restricted access' );

//______________________________________ PATH _____________
define('_FILE', dirname(__FILE__));

define('_ROOT', $_SERVER["DOCUMENT_ROOT"]);
//-----------------------------------------
define('_CONTENT', 'content');

	define('_ADM', _CONTENT.'/admin');
		define('_ADM_AJAX', _ADM.'/ajax');
		define('_ADM_INCL', _ADM.'/include');
		define('_ADM_PAGE', _ADM.'/page');
		
	define('_SITE', _CONTENT.'/site');
		define('_SITE_AJAX', _SITE.'/ajax');
		define('_SITE_INCL', _SITE.'/include');
		define('_SITE_PAGE', _SITE.'/page');
//-----------------------------------------
define('_MEDIA', 'media');

	define('_IMAGES', _MEDIA.'/images');
		define('_SITE_IMG', _IMAGES.'/site');
		define('_UPLOAD_IMG', _IMAGES.'/upload');
			define('_CAR_IMG', _UPLOAD_IMG.'/car');
			define('_OFFER_IMG', _UPLOAD_IMG.'/offer');
			define('_TYRES_IMG', _UPLOAD_IMG.'/tyres');
            define('_TEAM_IMG', _UPLOAD_IMG.'/team');

	define('_VIDEO', _MEDIA.'/video');
	define('_FONTS', _MEDIA.'/fonts');
	define('_FILES', _MEDIA.'/files');
//-----------------------------------------
define('_PLUGINS', 'plugins');
//_________________________________________________________

?>