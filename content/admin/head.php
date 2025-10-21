<?php defined( '_DOIT' ) or die( 'Restricted access' );?>

<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<meta name="description" content="">
<meta name="keywords" content="">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1" id="mobile_viewport">

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

<link rel="stylesheet" type="text/css" href="/<?php e(_DEFAULT)?>/css/default.css?d=<?php echo date("GYimsd", filemtime(_DEFAULT.'/css/default.css')); ?>">
<link rel="stylesheet" type="text/css" href="/<?php e(_ADM)?>/css/style.css?d=<?php echo date("GYimsd", filemtime(_ADM.'/css/style.css')); ?>">
<link rel="stylesheet" type="text/css" href="/<?php e(_ADM)?>/css/media.css?d=<?php echo date("GYimsd", filemtime(_ADM.'/css/media.css')); ?>">
<link rel="stylesheet" type="text/css" href="/<?php e(_ADM)?>/css/cars-display-controls.css?d=<?php echo date("GYimsd", filemtime(_ADM.'/css/cars-display-controls.css')); ?>">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script src="/<?php e(_DEFAULT)?>/js/js.cookie.min.js"></script>
<!--<script type="application/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>-->

<script src="/<?php e(_DEFAULT)?>/js/mousewheel.js"></script>

<script src="/<?php e(_DEFAULT)?>/js/sitescripts.js?d=<?php echo date("GYimsd", filemtime(_DEFAULT.'/js/sitescripts.js')); ?>"></script>
<script src="/<?php e(_ADM)?>/js/sitescripts.js?d=<?php echo date("GYimsd", filemtime(_ADM.'/js/sitescripts.js')); ?>"></script>

<?php
// Conditional JavaScript loading to prevent conflicts between pages
$current_url = $_SERVER['REQUEST_URI'];
if (strpos($current_url, '/cars/') !== false) {
    // Load only cars.js for cars pages
    echo '<script src="/' . _ADM . '/js/cars.js?d=' . date("GYimsd", filemtime(_ADM.'/js/cars.js')) . '"></script>' . "\n";
} elseif (strpos($current_url, '/ordercars/') !== false) {
    // Load only ordercars.js for ordercars pages
    echo '<script src="/' . _ADM . '/js/ordercars.js?d=' . date("GYimsd", filemtime(_ADM.'/js/ordercars.js')) . '"></script>' . "\n";
} elseif (strpos($current_url, '/tyres/') !== false) {
    // Load only tyres.js for tyres pages
    echo '<script src="/' . _ADM . '/js/tyres.js?d=' . date("GYimsd", filemtime(_ADM.'/js/tyres.js')) . '"></script>' . "\n";
} else {
    // For other admin pages, load both (fallback)
    echo '<script src="/' . _ADM . '/js/cars.js?d=' . date("GYimsd", filemtime(_ADM.'/js/cars.js')) . '"></script>' . "\n";
    echo '<script src="/' . _ADM . '/js/tyres.js?d=' . date("GYimsd", filemtime(_ADM.'/js/tyres.js')) . '"></script>' . "\n";
}
?>

<title>Admin</title>