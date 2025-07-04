<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php
// Include language file
include_once('credit_lang.php');

// Include CSS and JS files for this page
$page_css = '/content/site/page/new_pages/credit/credit.css';
$page_js = '/content/site/page/new_pages/credit/credit.js';
?>

<!-- Include page-specific CSS -->
<link rel="stylesheet" href="<?php echo $page_css; ?>">

<!-- Include Ion Range Slider CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ion-rangeslider@2.3.1/css/ion.rangeSlider.min.css">

<!-- Hero Section - Full Width -->
<section class="hero-section">
    <div class="hero-content">
        <div class="container">
+
            
            <!-- Text și imagine în partea dreaptă -->
            <div class="hero-right-content">
                <div class="hero-text">
                    <h1 class="hero-title"><?php echo $lng['credit_title'] ?? 'Cumpărați automobilul cu ușurință Credit și Leasing în Moldova'; ?></h1>
                </div>
                <div class="car-hero-container">
                    <img src="credit-media/car__red_credit.webp" alt="Automobil credit" class="car-hero-image">
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
