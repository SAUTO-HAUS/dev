<?php 
defined( '_DOIT' ) or die( 'Restricted access' ); 
// Set 404 response code (in case of direct call)
http_response_code(404);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru" >
<head>
	<link rel="apple-touch-icon" sizes="180x180" href="/<?php echo _SITE_IMG;?>/favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/<?php echo _SITE_IMG;?>/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/<?php echo _SITE_IMG;?>/favicon/favicon-16x16.png">
	<link rel="manifest" href="/<?php echo _SITE_IMG;?>/favicon/manifest.json">
	<link rel="mask-icon" href="/<?php echo _SITE_IMG;?>/favicon/safari-pinned-tab.svg" color="#d3a6a9">
	<link rel="shortcut icon" href="/<?php echo _SITE_IMG;?>/favicon/favicon.ico">
	<meta name="apple-mobile-web-app-title" content="Sauto">
	<meta name="application-name" content="Sauto">
	<meta name="msapplication-config" content="/<?php echo _SITE_IMG;?>/favicon/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">
	
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta name="description" content="Page not found. Error 404.">
	<meta name="keywords" content="Error, 404">
	<meta name="robots" content="noindex, nofollow">
	<meta name="viewport" content="width=800"/>
	
	<link rel="stylesheet" type="text/css" href="/<?php echo _DEFAULT;?>/css/default.css?d=<?php echo date("GYimsd", filemtime(_DEFAULT.'/css/default.css')); ?>">
	
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
	<link rel="stylesheet" href="//code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
	
    <title><?php echo $lang_404.', '.$site_name; ?></title>


	<style>
		body {margin:0; padding:0; width:100%; height:100%;}
		#e404 {width:100%; height:100%; text-align:center; margin:100px auto 0;}
		a {color:#336FA1;}
		a:hover {font-weight:bold;}
		p {color:#D36B56;}
	</style>
</head>

<body>
	<div id="e404">
		<img src="/<?php echo _SITE_IMG;?>/logo.png" style="filter:grayscale(1); width:auto;" />
		<p><?php echo $lang_404_message;?></p>
		<p><a href="/<?php echo $_COOKIE['lang'];?>"><?php echo $lang_404_return;?></a></p>
	</div>
</body>
</html>