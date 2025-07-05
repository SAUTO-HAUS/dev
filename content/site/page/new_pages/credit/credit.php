<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php
// Include language file
include_once('credit_lang.php');

// Include CSS inline to avoid path issues
$css_file_path = __DIR__ . '/credit.css';
$page_js = '/content/site/page/new_pages/credit/credit.js';
?>

<!-- Include Ion Range Slider CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ion-rangeslider@2.3.1/css/ion.rangeSlider.min.css">

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
            <!-- Credit calculator container - positioned higher -->
            <div class="credit-calculator-container">
                <h3 class="calculator-title" style="font-size: 32px; font-weight: bold;"><?php echo $lng['w']['calculator_title']; ?></h3>
                <div class="calculator-content">
                            <div class="calculator-field">
                                <div class="field-header" style="display: flex; align-items: center; justify-content: flex-start; gap: 20px; width: 100%;">
                                    <label class="field-label"><?php echo $lng['w']['loan_amount_eur']; ?></label>
                                    <span class="field-value" id="view_suma_creditului" style="font-weight: bold;">€ 25 000</span>
                                </div>
                                <input type="text" id="suma-creditului">
                                <div class="slider-labels">
                                    <span style="margin-top:-5px;">€ 2 000</span>
                                    <span style="padding-right:20px; margin-top:-5px;">€ 25 000</span>
                                    <span style="margin-top:-5px;">€ 50 000</span>
                                </div>
                            </div>
                            
                            <div class="calculator-field">
                                <div class="field-header" style="display: flex; align-items: center; justify-content: flex-start; gap: 20px; width: 100%;">
                                    <label class="field-label"><?php echo $lng['w']['loan_term_months']; ?></label>
                                    <span class="field-value" id="view_termen_creditului" style="font-weight: bold;">30 <?php echo $lng['w']['calc_title_luni']; ?></span>
                                </div>
                                <input type="text" id="termen-creditului">
                                <div class="slider-labels">
                                    <span style="margin-top:-5px;">6 <?php echo $lng['w']['calc_title_luni']; ?></span>
                                    <span style="padding-right:50px; margin-top:-5px;">30 <?php echo $lng['w']['calc_title_luni']; ?></span>
                                    <span style="margin-top:-5px;">60 <?php echo $lng['w']['calc_title_luni']; ?></span>
                                </div>
                            </div>
                            
                            <div class="payment-result">
                                <span class="result-label"><?php echo $lng['w']['monthly_payment_est']; ?></span>
                                <span class="result-value" id="payment-display" 
                                      data-from="<?php echo $lng['w']['calc_title_plata']; ?>" 
                                      data-to="<?php echo $lng['w']['calc_title_plata2']; ?>">
                                    <?php echo $lng['w']['calc_title_plata']; ?> 175 <?php echo $lng['w']['calc_title_plata2']; ?> 215 €
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="hero-right-content">
                    <div class="hero-text">
                        <div class="hero-main-title"><?php echo $lng['w']['hero_main_title']; ?></div>
                        <div class="hero-subtitle"><?php echo $lng['w']['hero_subtitle']; ?></div>
                    </div>
                </div>
                
                <div class="car-hero-container">
                    <img src="/content/site/page/new_pages/credit/credit-media/car__red_credit.png" alt="Automobil credit" class="car-hero-image">
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
