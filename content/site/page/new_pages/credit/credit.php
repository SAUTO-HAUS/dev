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
                <h3 class="calculator-title" style="font-size: 32px; font-weight: bold;"><?php echo get_translation('calculator_title', $current_lang, $lng); ?></h3>
                <div class="calculator-content">
                            <div class="calculator-field">
                                <div class="field-header" style="display: flex; align-items: center; justify-content: flex-start; gap: 20px; width: 100%;">
                                    <label class="field-label" style="color:rgb(95, 95, 95);"><?php echo get_translation('calc_suma', $current_lang, $lng); ?></label>
                                    <span class="field-value" id="view_suma_creditului" style="font-weight: bold;"><span style='font-size: 1em; font-weight: bold;'>25 000</span> €</span>
                                </div>
                                <input type="text" id="suma-creditului">
                                <div class="slider-labels">
                                    <span style="margin-top:-5px;">2 000 €</span>
                                    <span style="padding-right:20px; margin-top:-5px;">25 000 €</span>
                                    <span style="margin-top:-5px;">50 000 €</span>
                                </div>
                            </div>
                            
                            <div class="calculator-field">
                                <div class="field-header" style="display: flex; align-items: center; justify-content: flex-start; gap: 20px; width: 100%;">
                                    <label class="field-label" style="color:rgb(95, 95, 95);"><?php echo get_translation('calc_perioada', $current_lang, $lng); ?></label>
                                    <span class="field-value" id="view_termen_creditului" style="font-weight: bold;" data-months="<?php echo get_translation('calc_luni', $current_lang, $lng); ?>"><span style='font-size: 1em; font-weight: bold;'>30</span> <?php echo get_translation('calc_luni', $current_lang, $lng); ?></span>
                                </div>
                                <input type="text" id="termen-creditului">
                                <div class="slider-labels">
                                    <span style="margin-top:-5px;" class="months-label" data-months="<?php echo get_translation('calc_luni', $current_lang, $lng); ?>">6 <?php echo get_translation('calc_luni', $current_lang, $lng); ?></span>
                                    <span style="padding-right:50px; margin-top:-5px;" class="months-label" data-months="<?php echo get_translation('calc_luni', $current_lang, $lng); ?>">30 <?php echo get_translation('calc_luni', $current_lang, $lng); ?></span>
                                    <span style="margin-top:-5px;" class="months-label" data-months="<?php echo get_translation('calc_luni', $current_lang, $lng); ?>">60 <?php echo get_translation('calc_luni', $current_lang, $lng); ?></span>
                                </div>
                            </div>
                            
                            <div class="payment-result">
                                <span class="result-label"><?php echo get_translation('calc_rata_lunara', $current_lang, $lng); ?></span>
                                <span class="result-value" id="payment-display" 
                                      data-from="<?php echo get_translation('calc_title_plata', $current_lang, $lng); ?>" 
                                      data-to="<?php echo get_translation('calc_title_plata2', $current_lang, $lng); ?>">
                                    <span style="font-size: 0.85em;"><?php echo get_translation('calc_title_plata', $current_lang, $lng); ?></span> 175 <span style="font-size: 0.85em;"><?php echo get_translation('calc_title_plata2', $current_lang, $lng); ?></span> 215 €
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="hero-right-content">
                    <div class="hero-text">
                        <div class="hero-main-title"><?php echo get_translation('hero_main_title', $current_lang, $lng); ?></div>
                        <div class="hero-subtitle"><?php echo get_translation('hero_subtitle', $current_lang, $lng); ?></div>
                    </div>
                </div>
                
                <div class="car-hero-container">
                    <img src="/content/site/page/new_pages/credit/credit-media/car_red_credit.png" alt="Automobil credit" class="car-hero-image">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Credit Categories Section -->
<section class="credit-categories-section">
    <div class="container">
        <!-- Category tabs -->
        <div class="category-tabs">
            <button class="tab-button active" data-category="personal">
                <?php echo get_translation('personal_credit_title', $current_lang, $lng); ?>
            </button>
            <button class="tab-button" data-category="business">
                <?php echo get_translation('business_credit_title', $current_lang, $lng); ?>
            </button>
            <button class="tab-button" data-category="leasing">
                <?php echo get_translation('leasing_title', $current_lang, $lng); ?>
            </button>
        </div>
        
        <!-- Category content -->
        <div class="category-content">
            <!-- Personal car credit content -->
            <div class="category-grid active" id="personal-content">
                <h2 class="category-title"><?php echo get_translation('personal_credit_title', $current_lang, $lng); ?></h2>
                <p class="category-description"><?php echo get_translation('personal_credit_desc', $current_lang, $lng); ?></p>
                
                <div class="personal-grid">
                    <!-- Item 1 - Large card spanning 2 rows -->
                    <div class="feature-card item1">
                        <div class="feature-icon">
                            <img src="/content/site/page/new_pages/credit/credit-media/icons-category/1-1.png" alt="Feature Icon" width="64" height="64">
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('personal_feature1_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('personal_feature1_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 2 -->
                    <div class="feature-card item2">
                        <div class="feature-icon">
                        <div class="feature-icon">
                            <img src="/content/site/page/new_pages/credit/credit-media/icons-category/1-2.png" alt="Feature Icon" width="64" height="64">
                        </div>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('personal_feature2_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('personal_feature2_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 3 -->
                    <div class="feature-card item3">
                        <div class="feature-icon">
                        <div class="feature-icon">
                            <img src="/content/site/page/new_pages/credit/credit-media/icons-category/1-3.png" alt="Feature Icon" width="64" height="64">
                        </div>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('personal_feature3_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('personal_feature3_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 4 -->
                    <div class="feature-card item4">
                        <div class="feature-icon">
                        <div class="feature-icon">
                            <img src="/content/site/page/new_pages/credit/credit-media/icons-category/1-4.png" alt="Feature Icon" width="64" height="64">
                        </div>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('personal_feature4_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('personal_feature4_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 5 -->
                    <div class="feature-card item5">
                        <div class="feature-icon">
                        <div class="feature-icon">
                            <img src="/content/site/page/new_pages/credit/credit-media/icons-category/1-5.png" alt="Feature Icon" width="64" height="64">
                        </div>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('personal_feature5_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('personal_feature5_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 6 -->
                    <div class="feature-card item6">
                        <div class="feature-icon">
                        <div class="feature-icon">
                            <img src="/content/site/page/new_pages/credit/credit-media/icons-category/1-6.png" alt="Feature Icon" width="64" height="64">
                        </div>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('personal_feature6_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('personal_feature6_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 7 -->
                    <div class="feature-card item7">
                        <div class="feature-icon">
                        <div class="feature-icon">
                            <img src="/content/site/page/new_pages/credit/credit-media/icons-category/1-7.png" alt="Feature Icon" width="64" height="64">
                        </div>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('personal_feature7_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('personal_feature7_desc', $current_lang, $lng); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Business car credit content -->
            <div class="category-grid" id="business-content">
                <h2 class="category-title"><?php echo get_translation('business_credit_title', $current_lang, $lng); ?></h2>
                <p class="category-description"><?php echo get_translation('business_credit_desc', $current_lang, $lng); ?></p>
                
                <div class="business-grid">
                    <!-- Item 1 - Large card spanning 2 rows -->
                    <div class="feature-card item1">
                        <div class="feature-icon">
                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                                <path d="M20 6H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('business_feature1_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('business_feature1_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 2 -->
                    <div class="feature-card item2">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M24 4C13 4 4 13 4 24s9 20 20 20 20-9 20-20S35 4 24 4zm0 36c-8.8 0-16-7.2-16-16S15.2 8 24 8s16 7.2 16 16-7.2 16-16 16z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('business_feature2_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('business_feature2_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 3 -->
                    <div class="feature-card item3">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M24 2l-6 6h12l-6-6zm0 44l6-6H18l6 6zM8 24l6-6v12l-6-6zm32 0l-6 6V18l6 6z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('business_feature3_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('business_feature3_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 4 -->
                    <div class="feature-card item4">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M36 16l-8 8 8 8-8-8 8-8zm-12 8l-8-8 8 8-8 8 8-8z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('business_feature4_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('business_feature4_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 5 -->
                    <div class="feature-card item5">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M24 12c-4.41 0-8 3.59-8 8s3.59 8 8 8 8-3.59 8-8-3.59-8-8-8z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('business_feature5_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('business_feature5_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 6 - Wide card spanning 2 columns -->
                    <div class="feature-card item6">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M24 4l4 8h8l-6 6 2 8-8-4-8 4 2-8-6-6h8z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('business_feature6_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('business_feature6_desc', $current_lang, $lng); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Leasing content -->
            <div class="category-grid" id="leasing-content">
                <h2 class="category-title"><?php echo get_translation('leasing_title', $current_lang, $lng); ?></h2>
                <p class="category-description"><?php echo get_translation('leasing_desc', $current_lang, $lng); ?></p>
                
                <div class="leasing-grid">
                    <!-- Item 1 - Large card spanning 2 rows -->
                    <div class="feature-card item1">
                        <div class="feature-icon">
                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                                <path d="M24 4L6 10v12c0 11.11 7.67 21.47 18 24 10.33-2.53 18-12.89 18-24V10L24 4z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('leasing_feature1_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('leasing_feature1_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 2 -->
                    <div class="feature-card item2">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M40 18H8l4-8h24l4 8zM8 42h32l-4-8H12l-4 8z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('leasing_feature2_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('leasing_feature2_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 3 -->
                    <div class="feature-card item3">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M24 2C13 4 4 13 4 24s9 20 20 20 20-9 20-20S35 4 24 4zm0 36c-8.8 0-16-7.2-16-16S15.2 8 24 8s16 7.2 16 16-7.2 16-16 16z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('leasing_feature3_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('leasing_feature3_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 4 -->
                    <div class="feature-card item4">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M16 8v8h16V8H16zm0 32h16v-8H16v8zM8 24h8V16H8v8zm24 0h8v-8h-8v8z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('leasing_feature4_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('leasing_feature4_desc', $current_lang, $lng); ?></p>
                    </div>
                    
                    <!-- Item 5 -->
                    <div class="feature-card item5">
                        <div class="feature-icon">
                            <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                                <path d="M24 12v12l8-8-8-8zm0 24v12l8-8-8-8zM12 24h12l-8-8-8 8zm24 0h12l-8-8-8 8z" fill="#e2001a"/>
                            </svg>
                        </div>
                        <h3 class="feature-title"><?php echo get_translation('leasing_feature5_title', $current_lang, $lng); ?></h3>
                        <p class="feature-description"><?php echo get_translation('leasing_feature5_desc', $current_lang, $lng); ?></p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<!-- Partners Section -->
<section class="partners-section">
    <div class="container">
        <div class="partners-content">
            <!-- Left side: Text content -->
            <div class="partners-text">
                <h2 class="partners-title"><?php echo get_translation('partners_title', $current_lang, $lng); ?></h2>
                <p class="partners-subtitle"><?php echo get_translation('partners_subtitle', $current_lang, $lng); ?></p>
                <button class="apply-button"><?php echo get_translation('submit_application', $current_lang, $lng); ?></button>
            </div>
            
            <!-- Center: Partners grid -->
            <div class="partners-grid">
                <!-- Top row: Microinvest, Maib Leasing, BT Leasing -->
                <div class="partner-card partner-1">
                    <div class="partner-logo microinvest-logo">
                        <img src="/content/site/page/new_pages/credit/credit-media/microinvest.png" alt="Microinvest">
                    </div>
                    <div class="partner-info">
                        <h3 class="partner-name">Microinvest.md</h3>
                        <p class="partner-desc"><?php echo get_translation('microinvest_desc', $current_lang, $lng); ?></p>
                    </div>
                </div>
                
                <div class="partner-card partner-2">
                    <div class="partner-logo maib-logo">
                        <img src="/content/site/page/new_pages/credit/credit-media/maib.png" alt="Maib Leasing">
                    </div>
                    <div class="partner-info">
                        <h3 class="partner-name">Leasing.md</h3>
                        <p class="partner-desc"><?php echo get_translation('maib_leasing_desc', $current_lang, $lng); ?></p>
                    </div>
                </div>
                
                <!-- BT Leasing with car image - top row right, spans both rows -->
                <div class="partner-card partner-3 bt-leasing-card">
                    <div class="partner-logo bt-logo">
                        <img style="margin-bottom: 40px;" src="/content/site/page/new_pages/credit/credit-media/bt.png" alt="BT Leasing">
                    </div>
                    <div class="partner-info">
                        <h3 class="partner-name">BT Leasing</h3>
                        <p class="partner-desc"><?php echo get_translation('bt_leasing_desc', $current_lang, $lng); ?></p>
                    </div>
                    <div class="car-image-container">
                        <img src="/content/site/page/new_pages/credit/credit-media/car-1.avif" alt="Car" class="extending-car">
                    </div>
                </div>
                
                <!-- Bottom row: Primero, Victoriabank -->
                <div class="partner-card partner-4">
                    <div class="partner-logo">
                        <img src="/content/site/page/new_pages/credit/credit-media/primero.png" alt="Primero">
                    </div>
                    <div class="partner-info">
                        <h3 class="partner-name">Primero.md</h3>
                        <p class="partner-desc"><?php echo get_translation('primero_desc', $current_lang, $lng); ?></p>
                    </div>
                </div>
                
                <div class="partner-card partner-5">
                    <div class="partner-logo victoria-logo">
                        <img src="/content/site/page/new_pages/credit/credit-media/victoria.png" alt="Victoriabank">
                    </div>
                    <div class="partner-info">
                        <h3 class="partner-name">Victoriabank</h3>
                        <p class="partner-desc"><?php echo get_translation('victoriabank_desc', $current_lang, $lng); ?></p>
                    </div>
                </div>
<section class="comments-section">
    <div class="container">
        <h2 class="comments-title"><?php echo get_translation('comments_title', $current_lang, $lng); ?></h2>
        
        <!-- Navigation under title -->
        <div class="comments-navigation">
            <button class="nav-btn" onclick="previousComment()">‹</button>
            <button class="nav-btn" onclick="nextComment()">›</button>
        </div>
        
        <div class="comments-slider">
            <!-- Comment 1 -->
            <div class="comment-card active" data-comment="1">
                <div class="comment-content">
                    <div class="quote-mark">"</div>
                    <p class="comment-text"><?php echo get_translation('comment_1_text', $current_lang, $lng); ?></p>
                </div>
                <div class="comment-author">
                    <div class="author-avatar">
                        <img src="/content/site/page/new_pages/credit/credit-media/avatars/avatar1.jpg" 
                             alt="<?php echo get_translation('comment_1_name', $current_lang, $lng); ?>" 
                             onerror="this.style.display='none'">
                    </div>
                    <div class="author-info">
                        <h4 class="author-name"><?php echo get_translation('comment_1_name', $current_lang, $lng); ?></h4>
                        <p class="author-location"><?php echo get_translation('comment_1_location', $current_lang, $lng); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="comment-card active" data-comment="2">
                <div class="comment-content">
                    <div class="quote-mark">"</div>
                    <p class="comment-text"><?php echo get_translation('comment_2_text', $current_lang, $lng); ?></p>
                </div>
                <div class="comment-author">
                    <div class="author-avatar">
                        <img src="/content/site/page/new_pages/credit/credit-media/avatars/avatar2.jpg" 
                             alt="<?php echo get_translation('comment_2_name', $current_lang, $lng); ?>" 
                             onerror="this.style.display='none'">
                    </div>
                    <div class="author-info">
                        <h4 class="author-name"><?php echo get_translation('comment_2_name', $current_lang, $lng); ?></h4>
                        <p class="author-location"><?php echo get_translation('comment_2_location', $current_lang, $lng); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="comment-card" data-comment="3">
                <div class="comment-content">
                    <div class="quote-mark">"</div>
                    <p class="comment-text"><?php echo get_translation('comment_3_text', $current_lang, $lng); ?></p>
                </div>
                <div class="comment-author">
                    <div class="author-avatar">
                        <img src="/content/site/page/new_pages/credit/credit-media/avatars/avatar3.jpg" 
                             alt="<?php echo get_translation('comment_3_name', $current_lang, $lng); ?>" 
                             onerror="this.style.display='none'">
                    </div>
                    <div class="author-info">
                        <h4 class="author-name"><?php echo get_translation('comment_3_name', $current_lang, $lng); ?></h4>
                        <p class="author-location"><?php echo get_translation('comment_3_location', $current_lang, $lng); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="comment-card" data-comment="4">
                <div class="comment-content">
                    <div class="quote-mark">"</div>
                    <p class="comment-text"><?php echo get_translation('comment_4_text', $current_lang, $lng); ?></p>
                </div>
                <div class="comment-author">
                    <div class="author-avatar">
                        <img src="/content/site/page/new_pages/credit/credit-media/avatars/avatar4.jpg" 
                             alt="<?php echo get_translation('comment_4_name', $current_lang, $lng); ?>" 
                             onerror="this.style.display='none'">
                    </div>
                    <div class="author-info">
                        <h4 class="author-name"><?php echo get_translation('comment_4_name', $current_lang, $lng); ?></h4>
                        <p class="author-location"><?php echo get_translation('comment_4_location', $current_lang, $lng); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="comment-card" data-comment="5">
                <div class="comment-content">
                    <div class="quote-mark">"</div>
                    <p class="comment-text"><?php echo get_translation('comment_5_text', $current_lang, $lng); ?></p>
                </div>
                <div class="comment-author">
                    <div class="author-avatar">
                        <img src="/content/site/page/new_pages/credit/credit-media/avatars/avatar5.jpg" 
                             alt="<?php echo get_translation('comment_5_name', $current_lang, $lng); ?>" 
                             onerror="this.style.display='none'">
                    </div>
                    <div class="author-info">
                        <h4 class="author-name"><?php echo get_translation('comment_5_name', $current_lang, $lng); ?></h4>
                        <p class="author-location"><?php echo get_translation('comment_5_location', $current_lang, $lng); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="comment-card" data-comment="6">
                <div class="comment-content">
                    <div class="quote-mark">"</div>
                    <p class="comment-text"><?php echo get_translation('comment_6_text', $current_lang, $lng); ?></p>
                </div>
                <div class="comment-author">
                    <div class="author-avatar">
                        <img src="/content/site/page/new_pages/credit/credit-media/avatars/avatar6.jpg" 
                             alt="<?php echo get_translation('comment_6_name', $current_lang, $lng); ?>" 
                             onerror="this.style.display='none'">
                    </div>
                    <div class="author-info">
                        <h4 class="author-name"><?php echo get_translation('comment_6_name', $current_lang, $lng); ?></h4>
                        <p class="author-location"><?php echo get_translation('comment_6_location', $current_lang, $lng); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="comment-card" data-comment="7">
                <div class="comment-content">
                    <div class="quote-mark">"</div>
                    <p class="comment-text"><?php echo get_translation('comment_7_text', $current_lang, $lng); ?></p>
                </div>
                <div class="comment-author">
                    <div class="author-avatar">
                        <img src="/content/site/page/new_pages/credit/credit-media/avatars/avatar7.jpg" 
                             alt="<?php echo get_translation('comment_7_name', $current_lang, $lng); ?>" 
                             onerror="this.style.display='none'">
                    </div>
                    <div class="author-info">
                        <h4 class="author-name"><?php echo get_translation('comment_7_name', $current_lang, $lng); ?></h4>
                        <p class="author-location"><?php echo get_translation('comment_7_location', $current_lang, $lng); ?></p>
                    </div>
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
