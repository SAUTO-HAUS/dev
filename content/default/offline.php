<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru" >
<head>
	<link rel="apple-touch-icon" sizes="180x180" href="/<?php e(_SITE_IMG)?>/favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/<?php e(_SITE_IMG)?>/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/<?php e(_SITE_IMG)?>/favicon/favicon-16x16.png">
	<link rel="manifest" href="/<?php e(_SITE_IMG)?>/favicon/manifest.json">
	<link rel="mask-icon" href="/<?php e(_SITE_IMG)?>/favicon/safari-pinned-tab.svg" color="#d3a6a9">
	<link rel="shortcut icon" href="/<?php e(_SITE_IMG)?>/favicon/favicon.ico">
	<meta name="apple-mobile-web-app-title" content="Sauto">
	<meta name="application-name" content="Sauto">
	<meta name="msapplication-config" content="/<?php e(_SITE_IMG)?>/favicon/browserconfig.xml">
	<meta name="theme-color" content="#ffffff">

	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta name="description" content="Maintenance work. Offline.">
	<meta name="keywords" content="maintenance, work, offline">
	<meta name="robots" content="noindex, nofollow">
	<meta name="viewport" content="width=800"/>
	
	<link rel="stylesheet" type="text/css" href="<?php e(_DEFAULT)?>/default.css?d=<?php echo date("GYimsd", filemtime(_DEFAULT.'/default.css')); ?>">
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="//code.jquery.com/ui/1.12.0/jquery-ui.js"></script>
	
    <title><?php e($site_name) ?> - Offline</title>


	<style>
		body {margin:0; padding:0; width:100%; height:100%;}
		#offline {width:100%; height:100%; text-align:center; margin:100px auto 0;}
		a {color:#336FA1;}
		a:hover {font-weight:bold;}
		p {color:#D36B56;}
	</style>
</head>

<body>
	<div id="offline">
		<img src="/<?php e(_SITE_IMG)?>/logo.png" style="width:auto;" />
		<p><?php e('Maintenance work. Sorry.'); ?></p>
	</div>
</body>
</html>