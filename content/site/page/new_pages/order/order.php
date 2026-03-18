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

<!-- Hero Section -->
<section class="order-hero-section">
    <div class="order-hero-content">
        <!-- Left side - Text and Contact -->
        <div class="order-hero-left">
            <p class="order-hero-label"><?php echo get_order_translation('hero_label', $current_lang, $lng_order_page); ?></p>
            <h1 class="order-hero-title"><?php echo get_order_translation('hero_title', $current_lang, $lng_order_page); ?></h1>
            <p class="order-hero-subtitle"><?php echo get_order_translation('hero_subtitle', $current_lang, $lng_order_page); ?></p>
            
            <div class="order-hero-contact">
                <p class="order-contact-label"><?php echo get_order_translation('contact_label', $current_lang, $lng_order_page); ?></p>
                <div class="order-social-icons">
                    <a href="https://www.instagram.com/sauto.md/" target="_blank" rel="noopener" class="social-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                    </a>
                    <a href="https://www.facebook.com/sauto.md" target="_blank" rel="noopener" class="social-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                    </a>
                    <a href="https://t.me/sautostockextern" target="_blank" rel="noopener" class="social-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"></path><path d="M22 2l-7 20-4-9-9-4 20-7z"></path></svg>
                    </a>
                </div>
            </div>
        </div>
        
    </div>
</section>

<!-- Bitrix24 Form Section -->
<section class="order-form-section">
    <script data-b24-form="inline/10/rh1qfd" data-skip-moving="true">
        (function(w,d,u){
            var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
            var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
        })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_10.js');
    </script>
</section>
