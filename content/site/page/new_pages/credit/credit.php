<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php
// Include language file
include_once('credit_lang.php');

// Include CSS inline to avoid path issues
$css_file_path = __DIR__ . '/credit.css';
$page_js = '/content/site/page/new_pages/credit/credit.js';
?>

<!-- Include page-specific CSS inline -->
<style>
<?php
if (file_exists($css_file_path)) {
    echo file_get_contents($css_file_path);
    echo "\n/* CSS successfully loaded from: $css_file_path */";
} else {
    echo "/* ERROR: CSS file not found at: $css_file_path */";
}
?>
</style>

<!-- Include Ion Range Slider CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ion-rangeslider@2.3.1/css/ion.rangeSlider.min.css">

<!-- Hero Section - Full Width -->
<section class="hero-section">
    <div class="hero-content">
        <div class="container">
            
            <!-- Text and image on the right side -->
            <div class="hero-container">
                <div class="hero-left-content">
                    <!-- Calculator va fi adăugat aici -->
                </div>
                
                <div class="hero-right-content">
                    <div class="hero-text">
                        <div class="hero-main-title">ПОКУПАЙТЕ АВТОМОБИЛЬ С ЛЁГКОСТЬЮ</div>
                        <div class="hero-subtitle">КРЕДИТ И ЛИЗИНГ В МОЛДОВЕ</div>
                    </div>
                </div>
                
                <div class="car-hero-container">
                    <img src="/content/site/page/new_pages/credit/credit-media/car__red_credit.webp" alt="Automobil credit" class="car-hero-image">
                </div>
            </div>
        </div>
    </div>
</section>

<div class="credit-page">
</div>

<!-- Include jQuery and Ion Range Slider JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ion-rangeslider@2.3.1/js/ion.rangeSlider.min.js"></script>

<!-- Include page-specific JS -->
<script src="<?php echo $page_js; ?>"></script>
