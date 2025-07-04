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
            <h1 class="hero-title"><?php echo $lng['credit_title'] ?? 'Creditele Auto'; ?></h1>
            <p class="hero-subtitle"><?php echo $lng['credit_subtitle'] ?? 'Obțineți creditul auto perfect pentru nevoile dumneavoastră cu cele mai competitive rate din piață'; ?></p>
        </div>
    </div>
</section>

<div class="credit-page">

    <!-- Calculator Section -->
    <section class="calculator-section">
        <div class="calculator-wrapper">
            <div class="calculator-content">
                <h2 class="calculator-title"><?php echo $lng['calculator_title'] ?? 'Calculator Credit Auto'; ?></h2>
                
                <div class="calculator-grid">
                    <div class="input-group">
                        <label for="suma_creditului" class="input-label"><?php echo $lng['loan_amount_eur'] ?? 'Suma creditului (EUR)'; ?></label>
                        <input type="text" id="suma_creditului" class="input-field" value="25000" placeholder="€25,000">
                        <div class="slider-container">
                            <input type="text" id="suma-slider" value="" name="suma-slider" />
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="termen_creditului" class="input-label"><?php echo $lng['loan_term_months'] ?? 'Termenul (luni)'; ?></label>
                        <input type="text" id="termen_creditului" class="input-field" value="36" placeholder="36 luni">
                        <div class="slider-container">
                            <input type="text" id="termen-slider" value="" name="termen-slider" />
                        </div>
                    </div>
                </div>
                
                <div class="results-section">
                    <h3 class="result-title"><?php echo $lng['monthly_payment_est'] ?? 'Rata lunară estimată'; ?></h3>
                    <div class="result-amount" id="rata-lunara">€750</div>
                    <div class="result-range">
                        <?php echo $lng['payment_range_from'] ?? 'De la'; ?> <span id="rata-min">€650</span> <?php echo $lng['payment_range_to'] ?? 'până la'; ?> <span id="rata-max">€850</span>
                    </div>
                </div>
            </div>
            
            <div class="car-image-container">
                <img src="credit-media/car__red_credit.webp" alt="Automobil credit" class="car-image">
            </div>
        </div>
    </section>
</div>

<!-- Include jQuery and Ion Range Slider JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ion-rangeslider@2.3.1/js/ion.rangeSlider.min.js"></script>

<!-- Include page-specific JS -->
<script src="<?php echo $page_js; ?>"></script>
