<?php defined('_DOIT') or die('Restricted access'); ?>

<?php
// Include language file
include_once('order_lang.php');

// Get current language
$current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';

// Helper function to get translations
function get_order_translation($key, $lang, $translations) {
    return $translations[$lang][$key] ?? '';
}

// Set up paths with cache busting
$css_file_path = __DIR__ . '/order.css';
$js_file_path = __DIR__ . '/order.js';
$page_css = '/content/site/page/new_pages/order/order.css';
$page_js = '/content/site/page/new_pages/order/order.js';

// Add cache busting timestamp
if (file_exists($css_file_path)) {
    $css_version = '?d=' . date("GYimsd", filemtime($css_file_path));
} else {
    $css_version = '?d=' . date("GYimsd", time());
}

if (file_exists($js_file_path)) {
    $js_version = '?d=' . date("GYimsd", filemtime($js_file_path));
} else {
    $js_version = '?d=' . date("GYimsd", time());
}

?>

<!-- Include page-specific CSS with cache busting -->
<link rel="stylesheet" type="text/css" href="<?php echo $page_css . $css_version; ?>">

<!-- Include page-specific JS with cache busting -->
<script src="<?php echo $page_js . $js_version; ?>"></script>
