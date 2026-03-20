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
        <!-- Left side - Text -->
        <div class="order-hero-left">
            <h1 class="order-hero-title"><?php echo get_order_translation('hero_title', $current_lang, $lng_order_page); ?></h1>
            
            <a href="/ordercars" class="order-hero-button">
                <?php echo get_order_translation('hero_button', $current_lang, $lng_order_page); ?>
                <span class="order-hero-button-circle">
                    <img src="/content/site/page/new_pages/order/order-media/icons/right.svg" alt="Arrow" class="order-hero-button-arrow">
                </span>
            </a>
            
            <p class="order-hero-description"><?php echo get_order_translation('hero_description', $current_lang, $lng_order_page); ?></p>
        </div>
        
        <!-- Right side - Contact -->
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
</section>

<!-- Slider Section - Completed Projects -->
<?php
global $db;
$car_ids = [11760, 11759, 11758, 11756, 11753, 11747, 11744, 11705];
$placeholders = implode(',', array_fill(0, count($car_ids), '?'));

$cars = [];
try {
    $stmt = $db->prepare("SELECT * FROM gh3sp_car_ctlg WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)");
    $stmt->execute(array_merge($car_ids, $car_ids));
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cars as &$car) {
        try {
            $img_stmt = $db->prepare("SELECT name FROM gh3sp_car_pht WHERE it_id = ? AND main = 1 LIMIT 1");
            $img_stmt->execute([$car['id']]);
            $img = $img_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$img) {
                $img_stmt = $db->prepare("SELECT name FROM gh3sp_car_pht WHERE it_id = ? LIMIT 1");
                $img_stmt->execute([$car['id']]);
                $img = $img_stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            $car['main_image'] = $img ? $img['name'] : null;
        } catch (Exception $e) {
            $car['main_image'] = null;
        }
    }
    unset($car);
} catch (Exception $e) {
    $cars = [];
}
?>

<section class="order-slider-section">
    <div class="order-slider-container">
        <div class="order-slider-header">
            <div class="order-slider-title-wrapper">
                <h2 class="order-slider-title"><?php echo get_order_translation('slider_title', $current_lang, $lng_order_page); ?></h2>
                <p class="order-slider-subtitle"><?php echo get_order_translation('slider_subtitle', $current_lang, $lng_order_page); ?></p>
            </div>
            <div class="order-slider-nav">
                <button class="order-slider-arrow order-slider-prev" aria-label="Previous">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <button class="order-slider-arrow order-slider-next" aria-label="Next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
        
        <div class="order-slider-wrapper">
            <div class="order-slider-track">
                <?php foreach ($cars as $car): 
                    $car_path = !empty($car['p_path']) ? $car['p_path'] : '';
                    $car_name = $car['br_nm'] . ' ' . $car['mo_nm'];
                    $car_year = $car['yr'];
                    $car_mileage = !empty($car['mlg']) ? number_format($car['mlg'], 0, ' ', ' ') : '0';
                    $car_price = number_format($car['prc'], 0, ' ', ' ') . ' ';
                    
                    if ($car_path && !empty($car['main_image'])) {
                        $car_image_url = "/media/images/upload/car/{$car_path}/{$car['id']}/med/{$car['main_image']}.jpg";
                    } else {
                        $car_image_url = "/media/images/site/v2/no_image.svg";
                    }
                    
                    // Get car descriptions
                    $car_desc = isset($car_descriptions[$car['id']][$current_lang]) ? $car_descriptions[$car['id']][$current_lang] : null;
                ?>
                <div class="order-slider-card">
                    <div class="order-card-image">
                        <?php if ($car_image_url): ?>
                            <img src="<?php echo $car_image_url; ?>" alt="<?php echo $car_name; ?>" loading="lazy">
                        <?php endif; ?>
                    </div>
                    <div class="order-card-content">
                        <h3 class="order-card-title"><?php echo $car_name; ?></h3>
                        <div class="order-card-meta">
                            <span class="order-card-year"><?php echo get_order_translation('year_label', $current_lang, $lng_order_page); ?>: <?php echo $car_year; ?></span>
                            <span class="order-card-mileage"><?php echo get_order_translation('mileage_label', $current_lang, $lng_order_page); ?>: <?php echo $car_mileage; ?></span>
                        </div>
                        <?php if ($car_desc): ?>
                        <div class="order-card-description">
                            <p class="order-card-desc-item"><strong><?php echo get_order_translation('request_label', $current_lang, $lng_order_page); ?>:</strong> <?php echo $car_desc['request']; ?></p>
                            <p class="order-card-desc-item"><strong><?php echo get_order_translation('offer_label', $current_lang, $lng_order_page); ?>:</strong> <?php echo $car_desc['offer']; ?></p>
                            <p class="order-card-desc-item"><strong><?php echo get_order_translation('choice_label', $current_lang, $lng_order_page); ?>:</strong> <?php echo $car_desc['choice']; ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="order-card-footer">
                            <p class="order-card-price"><?php echo $car_price; ?>€</p>
                            <span class="order-card-button">
                                <?php echo get_order_translation('view_button', $current_lang, $lng_order_page); ?>
                            </span>
                            <?php /* <a href="/<?php echo $current_lang; ?>/car/<?php echo $car['id']; ?>" class="order-card-button">
                                <?php echo get_order_translation('view_button', $current_lang, $lng_order_page); ?>
                            </a> */ ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Process Steps Section -->
<section class="order-process-section">
    <div class="order-process-header">
        <h2 class="order-process-title"><?php echo get_order_translation('process_title', $current_lang, $lng_order_page); ?></h2>
    </div>
    <div class="order-process-container">
        <div class="order-process-scroll-container">
            <div class="order-process-preview order-process-preview-prev" style="display: none;">
                <h3 class="order-process-preview-title"></h3>
            </div>
            <div class="order-process-main">
                <div class="order-process-content">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="order-process-step" data-step="<?php echo $i; ?>">
                        <div class="order-process-number order-process-number-mobile"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></div>
                        <h3 class="order-process-step-title"><?php echo get_order_translation("step_{$i}_title", $current_lang, $lng_order_page); ?></h3>
                        <p class="order-process-step-text"><?php echo get_order_translation("step_{$i}_text", $current_lang, $lng_order_page); ?></p>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="order-process-number">01</div>
            </div>
            <div class="order-process-preview order-process-preview-next">
                <h3 class="order-process-preview-title"></h3>
            </div>
        </div>
    </div>
</section>

<!-- Buying Steps Section -->
<section class="order-buying-section">
    <div class="order-buying-container">
        <div class="order-buying-header">
            <h2 class="order-buying-title"><?php echo get_order_translation('buying_title', $current_lang, $lng_order_page); ?></h2>
            <p class="order-buying-subtitle"><?php echo get_order_translation('buying_subtitle', $current_lang, $lng_order_page); ?></p>
        </div>
        <div class="order-buying-content">
            <div class="order-buying-cards">
                <div class="order-buying-card">
                    <span class="order-buying-card-number"><?php echo get_order_translation('buying_step_label_1', $current_lang, $lng_order_page); ?></span>
                    <p class="order-buying-card-text"><?php echo get_order_translation('buying_step_1', $current_lang, $lng_order_page); ?></p>
                </div>
                <div class="order-buying-card">
                    <span class="order-buying-card-number"><?php echo get_order_translation('buying_step_label_2', $current_lang, $lng_order_page); ?></span>
                    <p class="order-buying-card-text"><?php echo get_order_translation('buying_step_2', $current_lang, $lng_order_page); ?></p>
                </div>
                <div class="order-buying-card">
                    <span class="order-buying-card-number"><?php echo get_order_translation('buying_step_label_3', $current_lang, $lng_order_page); ?></span>
                    <p class="order-buying-card-text"><?php echo get_order_translation('buying_step_3', $current_lang, $lng_order_page); ?></p>
                </div>
            </div>
            <div class="order-buying-image">
                <img src="/content/site/page/new_pages/order/order-media/section-5.jpg" alt="<?php echo get_order_translation('buying_title', $current_lang, $lng_order_page); ?>">
            </div>
        </div>
    </div>
</section>

<!-- Reviews Section -->
<section class="order-reviews-section">
    <div class="order-reviews-container">
        <div class="order-reviews-header">
            <h2 class="order-reviews-title"><?php echo get_order_translation('reviews_title', $current_lang, $lng_order_page); ?></h2>
            <div class="order-reviews-nav">
                <button class="order-reviews-prev" aria-label="Previous">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>
                <button class="order-reviews-next" aria-label="Next">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="order-reviews-slider-wrapper">
            <div class="order-reviews-slider">
                <?php for ($i = 1; $i <= 8; $i++): ?>
                <div class="order-review-card">
                    <div class="order-review-header">
                        <img src="/content/site/page/new_pages/order/order-media/avatar-<?php echo $i; ?>.jpg" alt="<?php echo get_order_translation("review_{$i}_name", $current_lang, $lng_order_page); ?>" class="order-review-avatar">
                        <div class="order-review-info">
                            <h3 class="order-review-name"><?php echo get_order_translation("review_{$i}_name", $current_lang, $lng_order_page); ?></h3>
                            <p class="order-review-car"><?php echo get_order_translation("review_{$i}_car", $current_lang, $lng_order_page); ?></p>
                        </div>
                    </div>
                    <p class="order-review-text"><?php echo get_order_translation("review_{$i}_text", $current_lang, $lng_order_page); ?></p>
                    <div class="order-review-rating">
                        <div class="order-review-stars">
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                            <span class="star">★</span>
                        </div>
                        <span class="order-review-score">5.0</span>
                    </div>
                </div>
                <?php endfor; ?>
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

<!-- Second Hero Section -->
<section class="order-hero2-section">
    <div class="order-hero2-content">
        <div class="order-hero2-left">
            <h1 class="order-hero2-title"><?php echo get_order_translation('hero2_title', $current_lang, $lng_order_page); ?></h1>
            <p class="order-hero2-description"><?php echo get_order_translation('hero2_description', $current_lang, $lng_order_page); ?></p>
            <a href="/ordercars" class="order-hero2-button">
                <?php echo get_order_translation('hero_button', $current_lang, $lng_order_page); ?>
                <span class="order-hero2-button-circle">
                    <img src="/content/site/page/new_pages/order/order-media/icons/right.svg" alt="Arrow" class="order-hero2-button-arrow">
                </span>
            </a>
        </div>
    </div>
</section>
