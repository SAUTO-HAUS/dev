<?php defined('_DOIT') or die('Restricted access'); ?>

<?php
include_once('calculator_lang.php');

$current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
$t = isset($lng_calculator_page[$current_lang]) ? $lng_calculator_page[$current_lang] : $lng_calculator_page['ro'];

$css_file_path = __DIR__ . '/calculator.css';
$js_file_path  = __DIR__ . '/calculator.js';
$page_css = '/content/site/page/new_pages/calculator/calculator.css';
$page_js  = '/content/site/page/new_pages/calculator/calculator.js';

$css_version = file_exists($css_file_path) ? '?d=' . date("GYimsd", filemtime($css_file_path)) : '?d=' . date("GYimsd", time());
$js_version  = file_exists($js_file_path)  ? '?d=' . date("GYimsd", filemtime($js_file_path))  : '?d=' . date("GYimsd", time());

// Get EUR rate live from BNM, fallback to DB cache
$eur_rate = 19.50;
try {
    $bnm_xml = @simplexml_load_file(
        'https://bnm.md/ru/official_exchange_rates?get_xml=1&date=' . date('d.m.Y'),
        'SimpleXMLElement',
        LIBXML_NOCDATA
    );
    if ($bnm_xml) {
        foreach ($bnm_xml->Valute as $valute) {
            if ((string)$valute->CharCode === 'EUR') {
                $rate = floatval(str_replace(',', '.', (string)$valute->Value));
                if ($rate > 0) {
                    $eur_rate = $rate;
                }
                break;
            }
        }
    }
} catch (Exception $e) {}

// Fallback to DB cache if BNM fetch failed
if ($eur_rate === 19.50) {
    try {
        $stmt = $db->prepare('SELECT `value` FROM ' . $prefx . '_exchange WHERE `name` = "EUR" LIMIT 1');
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && floatval($row['value']) > 0) {
            $eur_rate = floatval($row['value']);
        }
    } catch (Exception $e) {}
}

// Get calculator settings from DB
$settings = [];
try {
    $stmt = $db->prepare('SELECT `setting_key`, `setting_value` FROM ' . $prefx . '_calculator_settings');
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {}

$tva_rate              = isset($settings['tva_rate'])              ? floatval($settings['tva_rate'])              : 20;
$hybrid_discount_full  = isset($settings['hybrid_discount_full'])  ? floatval($settings['hybrid_discount_full'])  : 25;
$hybrid_discount_plugin = isset($settings['hybrid_discount_plugin']) ? floatval($settings['hybrid_discount_plugin']) : 50;
$damage_protection_rate = isset($settings['damage_protection_rate']) ? floatval($settings['damage_protection_rate']) : 1.2;

// Get excise rates
$excise_rates = [];
try {
    $stmt = $db->prepare('SELECT * FROM ' . $prefx . '_calculator_excise_rates ORDER BY fuel_type, capacity_min, age_min');
    $stmt->execute();
    $excise_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Generate year options
$year_options = '';
$current_year = (int)date('Y');
for ($y = $current_year; $y >= 1990; $y--) {
    $year_options .= '<option value="' . $y . '">' . $y . '</option>';
}
?>

<link rel="stylesheet" type="text/css" href="<?php echo $page_css . $css_version; ?>">
<script src="<?php echo $page_js . $js_version; ?>" defer></script>

<section class="calc-page-section">
    <div class="calc-page-inner">

        <!-- Left: Calculator (70%) -->
        <div class="calc-page-left">
            <div class="calc-page-header">
                <h1 class="calc-page-title"><?php echo $t['page_title']; ?></h1>
                <div class="calc-eur-rate">
                    <?php echo $t['eur_rate_label']; ?>
                    <span class="calc-eur-value" id="eur-rate-display"><?php echo number_format($eur_rate, 2, ',', ' '); ?></span>
                    MDL <span class="calc-eur-source">(BNM)</span>
                </div>
            </div>

            <div class="calc-form-box">
                <!-- Row 1: vehicle type + year -->
                <div class="calc-form-row">
                    <div class="calc-form-group">
                        <label><?php echo $t['vehicle_type']; ?></label>
                        <select id="calc-vehicle-type">
                            <option value="autoturism"><?php echo $t['car']; ?></option>
                            <option value="camion"><?php echo $t['truck']; ?></option>
                        </select>
                        <span class="calc-select-arrow">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                    <div class="calc-form-group">
                        <label><?php echo $t['year']; ?></label>
                        <select id="calc-year">
                            <?php echo $year_options; ?>
                        </select>
                        <span class="calc-select-arrow">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </span>
                    </div>
                </div>

                <!-- Row 2: capacity + price -->
                <div class="calc-form-row">
                    <div class="calc-form-group">
                        <label><?php echo $t['capacity']; ?></label>
                        <input type="number" id="calc-capacity" placeholder="<?php echo $t['capacity_placeholder']; ?>" min="0" max="10000">
                    </div>
                    <div class="calc-form-group">
                        <label><?php echo $t['price_eur']; ?></label>
                        <input type="number" id="calc-price-eur" placeholder="<?php echo $t['price_placeholder']; ?>" min="0">
                    </div>
                </div>

                <!-- Row 3: transport price (left aligned) -->
                <div class="calc-form-row">
                    <div class="calc-form-group">
                        <label><?php echo $t['transport_price']; ?></label>
                        <input type="text" id="calc-transport-eur" value="~ 1000" readonly>
                    </div>
                    <div class="calc-form-group calc-form-group--empty"></div>
                </div>

                <!-- Fuel type buttons -->
                <div class="calc-fuel-row">
                    <button type="button" class="calc-fuel-btn" data-fuel="benzina"><?php echo $t['gasoline']; ?></button>
                    <button type="button" class="calc-fuel-btn" data-fuel="diesel"><?php echo $t['diesel']; ?></button>
                    <button type="button" class="calc-fuel-btn active" data-fuel="hybrid"><?php echo $t['hybrid']; ?></button>
                    <button type="button" class="calc-fuel-btn" data-fuel="electric"><?php echo $t['electric']; ?></button>
                </div>

                <!-- Hybrid sub-options -->
                <div class="calc-hybrid-options show" id="calc-hybrid-options">
                    <div class="calc-hybrid-fuel-row">
                        <label class="calc-radio-label">
                            <input type="radio" name="hybrid_fuel" value="benzina" checked>
                            <?php echo $t['gasoline']; ?>
                        </label>
                        <label class="calc-radio-label">
                            <input type="radio" name="hybrid_fuel" value="diesel">
                            <?php echo $t['diesel']; ?>
                        </label>
                    </div>
                    <div class="calc-hybrid-type-row">
                        <button type="button" class="calc-hybrid-type-btn active" data-hybrid="plugin">
                            <?php echo $t['plugin_hybrid']; ?>
                            <span class="calc-hybrid-badge">- <?php echo $hybrid_discount_plugin; ?>%</span>
                        </button>
                        <button type="button" class="calc-hybrid-type-btn" data-hybrid="full">
                            <?php echo $t['full_hybrid']; ?>
                            <span class="calc-hybrid-badge">- <?php echo $hybrid_discount_full; ?>%</span>
                        </button>
                        <button type="button" class="calc-hybrid-type-btn" data-hybrid="mild">
                            <?php echo $t['mild_hybrid']; ?>
                            <span class="calc-hybrid-badge calc-hybrid-badge--none"><?php echo $t['no_discount']; ?></span>
                        </button>
                    </div>
                    <p class="calc-hybrid-note">Reducere aplicată automat*</p>
                </div>

                <!-- Electric notice -->
                <div class="calc-electric-notice" id="calc-electric-notice">
                    <strong><?php echo $t['electric_notice']; ?></strong><br>
                    <?php echo $t['electric_notice_sub']; ?>
                </div>

                <button type="button" class="calc-submit-btn" id="calc-calculate-btn">
                    <?php echo $t['calculate']; ?>
                </button>
            </div>

            <!-- Results box -->
            <div class="calc-results-box" id="calc-results" style="display:none;">
                <div class="calc-results-header" id="calc-results-toggle">
                    <h2 class="calc-results-title"><?php echo $t['results']; ?></h2>
                    <span class="calc-results-chevron">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </span>
                </div>
                <div class="calc-results-body" id="calc-results-body">
                    <!-- Summary rows -->
                    <div class="calc-summary-row">
                        <span class="calc-summary-label"><?php echo $t['year']; ?></span>
                        <span class="calc-summary-value" id="res-year-display">—</span>
                    </div>
                    <div class="calc-summary-row">
                        <span class="calc-summary-label"><?php echo $t['capacity']; ?></span>
                        <span class="calc-summary-value" id="res-capacity-display">—</span>
                    </div>
                    <div class="calc-summary-row">
                        <span class="calc-summary-label"><?php echo $t['price_eur']; ?></span>
                        <span class="calc-summary-value" id="res-price-display">—</span>
                    </div>

                    <!-- Tax rows -->
                    <div class="calc-tax-block">
                        <div class="calc-tax-row" id="res-excise-row">
                            <span class="calc-tax-dot"></span>
                            <span class="calc-tax-label"><?php echo $t['excise']; ?></span>
                            <span class="calc-tax-value" id="res-excise">—</span>
                        </div>
                        <div class="calc-tax-row" id="res-luxury-row" style="display:none;">
                            <span class="calc-tax-dot"></span>
                            <span class="calc-tax-label"><?php echo $t['luxury_excise']; ?></span>
                            <span class="calc-tax-value" id="res-luxury">—</span>
                        </div>
                        <div class="calc-tax-row">
                            <span class="calc-tax-dot"></span>
                            <span class="calc-tax-label"><?php echo $t['customs_duty']; ?></span>
                            <span class="calc-tax-value" id="res-customs">—</span>
                        </div>
                    </div>

                    <!-- Total taxes -->
                    <div class="calc-total-taxes-row">
                        <span class="calc-total-taxes-label"><?php echo $t['total']; ?></span>
                        <span class="calc-total-taxes-value" id="res-total">—</span>
                    </div>

                    <!-- Grand total -->
                    <div class="calc-grand-total-row">
                        <span class="calc-grand-total-label"><?php echo $t['vehicle_total']; ?></span>
                        <span class="calc-grand-total-value" id="res-vehicle-total">—</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Bitrix24 Form + promo block (30%) -->
        <div class="calc-page-right">
            <script data-b24-form="inline/10/rh1qfd" data-skip-moving="true">
                (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_10.js');
            </script>

            <div class="calc-promo-block">
                <div class="calc-promo-image" style="background-image:url('/content/site/page/new_pages/calculator/calculator-media/calc-img-1.jpg');">
                    <div class="calc-promo-text">
                        <h3 class="calc-promo-title"><?php echo $t['promo_title']; ?></h3>
                        <p class="calc-promo-subtitle"><?php echo $t['promo_subtitle']; ?></p>
                    </div>
                </div>
                <div class="calc-promo-footer">
                    <a href="/<?php echo $current_lang; ?>/ordercars" class="calc-promo-btn"><?php echo $t['promo_btn']; ?></a>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
window.CALC_CONFIG = {
    eurRate: <?php echo json_encode($eur_rate); ?>,
    tvaRate: <?php echo json_encode($tva_rate); ?>,
    hybridDiscountPlugin: <?php echo json_encode($hybrid_discount_plugin); ?>,
    hybridDiscountFull: <?php echo json_encode($hybrid_discount_full); ?>,
    damageProtectionRate: <?php echo json_encode($damage_protection_rate); ?>,
    exciseRates: <?php echo json_encode($excise_rates); ?>
};
</script>
