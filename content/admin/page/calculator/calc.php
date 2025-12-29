<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/calculator/calc_translate.php';

$admin_dir = isset($_COOKIE['admin_dir']) ? $_COOKIE['admin_dir'] : 'adminsauto';

// Get all brands for select
$brands = [];
try {
    $pdo = (new \App\Db\Brand())->getBrands();
    foreach ($pdo as $r) {
        $brands[$r['br']] = $r['br_nm'];
    }
} catch (Exception $e) {}

$eur_rate = 19.50;

$settings = [];
try {
    $pdo = $db->prepare('SELECT `setting_key`, `setting_value` FROM '.$prefx.'_calculator_settings');
    $pdo->execute();
    while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
   
}

$eur_rate = isset($settings['eur_rate']) ? floatval($settings['eur_rate']) : 19.50;
$tva_rate = isset($settings['tva_rate']) ? floatval($settings['tva_rate']) : 20;
$hybrid_discount_full = isset($settings['hybrid_discount_full']) ? floatval($settings['hybrid_discount_full']) : 25;
$hybrid_discount_plugin = isset($settings['hybrid_discount_plugin']) ? floatval($settings['hybrid_discount_plugin']) : 50;
$damage_protection_rate = isset($settings['damage_protection_rate']) ? floatval($settings['damage_protection_rate']) : 1.2;

$excise_rates = [];
try {
    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_calculator_excise_rates ORDER BY fuel_type, capacity_min, age_min');
    $pdo->execute();
    $excise_rates = $pdo->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    
}

$is_super_admin = isset($user_role) && $user_role === 'gordon';

$rtrn = '
<style>
    #calculator-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 2rem 1rem;
        font-family: Arial, sans-serif;
    }
    
    #calculator-container .calc-header {
        text-align: center;
        margin-bottom: 1rem;
    }
    
    #calculator-container .calc-header h1 {
        color: #333;
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }
    
    #calculator-container .calc-header .eur-rate-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    #calculator-container .calc-header .eur-rate-row label {
        color: #666;
        font-size: 0.95rem;
    }
    
    #calculator-container .calc-header .eur-rate-input {
        width: 100px;
        padding: 0.4rem 0.6rem;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: bold;
        color: #e2001a;
        text-align: center;
    }
    
    #calculator-container .calc-header .eur-rate-input:focus {
        outline: none;
        border-color: #e2001a;
    }
    
    #calculator-container .inline-rate-input {
        width: 50px;
        padding: 0.2rem 0.4rem;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 0.9rem;
        text-align: center;
    }
    
    #calculator-container .inline-rate-input:focus {
        outline: none;
        border-color: #e2001a;
    }
    
    #calculator-container .editable-value {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    #calculator-container .editable-input {
        width: 90px;
        padding: 0.3rem 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.95rem;
        font-weight: 600;
        text-align: right;
        background: #f9f9f9;
    }
    
    #calculator-container .editable-input:focus {
        outline: none;
        border-color: #e2001a;
        background: #fff;
    }
    
    #calculator-container .editable-input.eur-input {
        width: 70px;
        color: #666;
        font-weight: normal;
    }
    
    #calculator-container .result-row.total .editable-input {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.3);
        color: #fff;
        font-weight: 600;
    }
    
    #calculator-container .result-row.total .editable-input:focus {
        background: rgba(255,255,255,0.3);
        border-color: rgba(255,255,255,0.5);
    }
    
    #calculator-container .pdf-export-row {
        padding: 1.5rem 0 0;
        text-align: center;
    }
    
    #calculator-container .pdf-btn {
        padding: 0.75rem 2rem;
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    #calculator-container .pdf-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
    }
    
    #calculator-container .pdf-lang-select {
        padding: 0.75rem 1rem;
        border: 2px solid #28a745;
        border-radius: 8px;
        font-size: 1rem;
        cursor: pointer;
        background: #fff;
        margin-right: 0.5rem;
    }
    
    #calculator-container .pdf-lang-select:focus {
        outline: none;
        border-color: #1e7e34;
    }
    
    #calculator-container .commercial-offer-section {
        margin-top: 1.5rem;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 12px;
        border: 2px dashed #dee2e6;
    }
    
    #calculator-container .offer-title {
        margin: 0 0 1rem 0;
        font-size: 1.1rem;
        color: #333;
        text-align: center;
    }
    
    #calculator-container .offer-fields {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    #calculator-container .offer-field {
        display: flex;
        flex-direction: column;
    }
    
    #calculator-container .offer-field-full {
        grid-column: 1 / -1;
    }
    
    #calculator-container .offer-field label {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 0.3rem;
    }
    
    #calculator-container .offer-field input,
    #calculator-container .offer-field select {
        padding: 0.6rem 0.8rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        width: 100%;
        box-sizing: border-box;
    }
    
    #calculator-container .offer-field input:focus,
    #calculator-container .offer-field select:focus {
        outline: none;
        border-color: #e2001a;
    }
    
    #calculator-container .save-offer-btn {
        width: 100%;
        margin-top: 1rem;
        padding: 0.85rem;
        background: linear-gradient(135deg, #e2001a 0%, #bf0016 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    #calculator-container .save-offer-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(226, 0, 26, 0.4);
    }
    
    #calculator-container .save-offer-btn.success {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    }
    
    #calculator-container .save-offer-btn.error {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }
    
    #calculator-container .calc-header .eur-rate-row span {
        color: #666;
        font-size: 0.95rem;
    }
    
    #calculator-container .calc-header .save-rate-btn {
        padding: 0.4rem 1rem;
        background: #e2001a;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 0.9rem;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    #calculator-container .calc-header .save-rate-btn:hover {
        background: #bf0016;
    }
    
    #calculator-container .calc-header .save-rate-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    #calculator-container .calc-header .save-rate-btn.success {
        background: #28a745;
    }
    
    #calculator-container .calc-header .save-rate-btn.error {
        background: #ff6b6b;
    }
    
    #calculator-container .calc-form {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    #calculator-container .form-row {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    
    #calculator-container .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    #calculator-container .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: #333;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    #calculator-container .form-group select,
    #calculator-container .form-group input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s, box-shadow 0.3s;
        background: #fff;
    }
    
    #calculator-container .form-group select:focus,
    #calculator-container .form-group input:focus {
        outline: none;
        border-color: #e2001a;
        box-shadow: 0 0 0 3px rgba(226, 0, 26, 0.1);
    }
    
    #calculator-container .fuel-types {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    #calculator-container .fuel-type-btn {
        flex: 1;
        min-width: 100px;
        padding: 0.75rem 1rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        background: #fff;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
        font-size: 0.85rem;
    }
    
    #calculator-container .fuel-type-btn:hover {
        border-color: #e2001a;
    }
    
    #calculator-container .fuel-type-btn.active {
        background: #e2001a;
        border-color: #e2001a;
        color: #fff;
    }
    
    #calculator-container .fuel-type-btn.electric.active {
        background: #28a745;
        border-color: #28a745;
    }
    
    #calculator-container .hybrid-options {
        margin-top: 1rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        display: none;
    }
    
    #calculator-container .hybrid-options.show {
        display: block;
    }
    
    #calculator-container .hybrid-fuel-select {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #ddd;
    }
    
    #calculator-container .hybrid-fuel-select label,
    #calculator-container .hybrid-type-select label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-weight: normal;
        line-height: 1;
    }
    
    #calculator-container .hybrid-fuel-select input[type="radio"] {
        margin: 0 0.5rem 0 0;
        vertical-align: middle;
        -webkit-appearance: radio;
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }
    
    #calculator-container .hybrid-type-select {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    #calculator-container .hybrid-type-btn {
        flex: 1;
        min-width: 120px;
        padding: 0.75rem 1rem;
        border: 2px solid #28a745;
        border-radius: 8px;
        background: #fff;
        color: #333;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
    }
    
    #calculator-container .hybrid-type-btn:hover {
        background: #e8f5e9;
    }
    
    #calculator-container .hybrid-type-btn.active {
        background: #28a745;
        color: #fff;
        border-color: #28a745;
    }
    
    #calculator-container .hybrid-fuel-select input[type="radio"] {
        accent-color: #e2001a;
    }
    
    #calculator-container .calc-btn {
        width: 100%;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #e2001a 0%, #bf0016 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-top: 1rem;
    }
    
    #calculator-container .calc-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(226, 0, 26, 0.4);
    }
    
    #calculator-container .results {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        padding: 2rem;
        display: none;
    }
    
    #calculator-container .results.show {
        display: block;
    }
    
    #calculator-container .results h2 {
        color: #333;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    #calculator-container .result-row {
        display: flex;
        justify-content: space-between;
        padding: 1rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    #calculator-container .result-row:last-child {
        border-bottom: none;
    }
    
    #calculator-container .result-row .label {
        color: #666;
    }
    
    #calculator-container .result-row .value {
        font-weight: 600;
        color: #333;
    }
    
    #calculator-container .result-row.total {
        background: linear-gradient(135deg, #e2001a 0%, #bf0016 100%);
        color: #fff;
        margin: 1rem -2rem 0;
        padding: 1.5rem 2rem;
        border-radius: 0;
    }
    
    #calculator-container .result-row.total.vehicle-total {
        background: linear-gradient(135deg, #555 0%, #333 100%);
        margin: 0 -2rem -2rem;
        border-radius: 0 0 12px 12px;
    }
    
    #calculator-container .result-row.total .label,
    #calculator-container .result-row.total .value {
        color: #fff;
        font-size: 1.2rem;
    }
    
    #calculator-container .eur-equiv {
        font-size: 0.75em;
        color: #888;
        font-weight: normal;
        margin-left: 0.5rem;
    }
    
    #calculator-container .result-row.total .eur-equiv {
        color: #fff;
    }
    
    #calculator-container .admin-actions {
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 2px solid #f0f0f0;
        display: flex;
        gap: 1rem;
        justify-content: center;
    }
    
    #calculator-container .admin-btn {
        padding: 0.75rem 1.5rem;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        font-size: 0.9rem;
        transition: background 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        line-height: 1;
    }
    
    #calculator-container .admin-btn:hover {
        background: #555;
    }
    
    #calculator-container .admin-btn .btn-icon {
        display: inline-block;
        line-height: 1;
        vertical-align: middle;
    }
    
    #calculator-container .electric-notice {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
        display: none;
    }
    
    #calculator-container .electric-notice.show {
        display: block;
    }
</style>

<div id="calculator-container">
    <div class="calc-header">
        <h1>'.$t['title'].'</h1>
        <div class="eur-rate-row">
            <label>'.$t['eur_rate_label'].'</label>
            <input type="number" step="0.0001" id="eur-rate-input" class="eur-rate-input" value="'.number_format($eur_rate, 4, '.', '').'">
            <span>'.$t['eur_rate_mdl'].'</span>
            <button type="button" id="save-rate-btn" class="save-rate-btn" data-original="'.$t['save'].'" data-success="'.$t['rate_saved_btn'].'" data-error="'.$t['rate_error_btn'].'">'.$t['save'].'</button>
        </div>
    </div>
    
    <div class="calc-form">
        <div class="form-row">
            <div class="form-group">
                <label>'.$t['vehicle_type'].'</label>
                <select id="vehicle_type">
                    <option value="autoturism">'.$t['car'].'</option>
                    <option value="motocicleta">'.$t['motorcycle'].'</option>
                    <option value="camion">'.$t['truck'].'</option>
                </select>
            </div>
            <div class="form-group">
                <label>'.$t['year'].'</label>
                <select id="year">';
                    $current_year = date('Y');
                    for ($y = $current_year; $y >= 1990; $y--) {
                        $rtrn .= '<option value="'.$y.'">'.$y.'</option>';
                    }
                $rtrn .= '
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>'.$t['capacity'].'</label>
                <input type="number" id="capacity" placeholder="'.$t['capacity_placeholder'].'" min="0" max="10000">
            </div>
            <div class="form-group">
                <label>'.$t['price_eur'].'</label>
                <input type="number" id="price_eur" placeholder="'.$t['price_placeholder'].'" min="0">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <!-- empty for alignment -->
            </div>
            <div class="form-group">
                <label>'.$t['transport_price'].'</label>
                <input type="number" id="transport_eur" placeholder="'.$t['transport_placeholder'].'" min="0" value="0">
            </div>
        </div>
        
        <div class="form-group">
            <label>'.$t['fuel_type'].'</label>
            <div class="fuel-types">
                <div class="fuel-type-btn" data-fuel="benzina">'.$t['gasoline'].'</div>
                <div class="fuel-type-btn" data-fuel="diesel">'.$t['diesel'].'</div>
                <div class="fuel-type-btn active" data-fuel="hybrid">'.$t['hybrid'].'</div>
                <div class="fuel-type-btn electric" data-fuel="electric">'.$t['electric'].'</div>
            </div>
            
            <div class="hybrid-options show" id="hybrid-options">
                <div class="hybrid-fuel-select">
                    <label><input type="radio" name="hybrid_fuel" value="benzina" checked> '.$t['gasoline'].'</label>
                    <label><input type="radio" name="hybrid_fuel" value="diesel"> '.$t['diesel'].'</label>
                </div>
                <div class="hybrid-type-select">
                    <div class="hybrid-type-btn active" data-hybrid="plugin">'.$t['plugin_hybrid'].' (-'.$hybrid_discount_plugin.'%)</div>
                    <div class="hybrid-type-btn" data-hybrid="full">'.$t['full_hybrid'].' (-'.$hybrid_discount_full.'%)</div>
                    <div class="hybrid-type-btn" data-hybrid="mild">'.$t['mild_hybrid'].'</div>
                </div>
            </div>
            
            <div class="electric-notice" id="electric-notice">
                <strong>✓ '.$t['electric_notice'].'</strong><br>
                '.$t['electric_notice_sub'].'
            </div>
        </div>
        
        <button class="calc-btn" id="calculate-btn">'.$t['calculate'].'</button>
    </div>
    
    <div class="results" id="results">
        <h2>'.$t['results'].'</h2>
        <div class="result-row">
            <span class="label">'.$t['value_mdl'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-value-mdl" data-field="value" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-value-eur" data-field="value" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['excise'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-excise-mdl" data-field="excise" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-excise-eur" data-field="excise" step="1"> EUR</span></span>
        </div>
        <div class="result-row" id="res-luxury-row" style="display:none;">
            <span class="label">'.$t['luxury_excise'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-luxury-mdl" data-field="luxury" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-luxury-eur" data-field="luxury" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['customs_duty'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-customs-mdl" data-field="customs" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-customs-eur" data-field="customs" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['damage_protection'].' (<input type="number" step="0.1" min="0" max="10" id="damage-rate-input" class="inline-rate-input" value="'.$damage_protection_rate.'">%)</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-damage-mdl" data-field="damage" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-damage-eur" data-field="damage" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['export_declaration'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-export-mdl" data-field="export" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-export-eur" data-field="export" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['bank_commission'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-bank-mdl" data-field="bank" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-bank-eur" data-field="bank" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['auction_commission'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-auction-mdl" data-field="auction" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-auction-eur" data-field="auction" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['pollution_tax'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-pollution-mdl" data-field="pollution" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-pollution-eur" data-field="pollution" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['shipping_docs'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-shipping-mdl" data-field="shipping" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-shipping-eur" data-field="shipping" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['accessories'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-accessories-mdl" data-field="accessories" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-accessories-eur" data-field="accessories" step="1"> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['transaction_commission'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-transaction-mdl" data-field="transaction" step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-transaction-eur" data-field="transaction" step="1"> EUR</span></span>
        </div>
        <div class="result-row total">
            <span class="label">'.$t['total'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-total-mdl" readonly step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-total-eur" readonly step="1"> EUR</span></span>
        </div>
        <div class="result-row total vehicle-total">
            <span class="label">'.$t['vehicle_total'].'</span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-vehicle-total-mdl" readonly step="1"> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-vehicle-total-eur" readonly step="1"> EUR</span></span>
        </div>
        <div class="pdf-export-row">
            <select id="pdf-lang-select" class="pdf-lang-select">
                <option value="ro">Română</option>
                <option value="ru">Русский</option>
                <option value="en">English</option>
            </select>
            <button type="button" class="pdf-btn" id="export-pdf-btn">📄 '.$t['export_pdf'].'</button>
        </div>
        
        <div class="commercial-offer-section">
            <h3 class="offer-title">'.$t['commercial_offer'].'</h3>
            <div class="offer-fields">
                <div class="offer-field">
                    <label for="offer-client-name">'.$t['client_name'].'</label>
                    <input type="text" id="offer-client-name" name="u_nm" placeholder="'.$t['client_name'].'">
                </div>
                <div class="offer-field">
                    <label for="offer-brand">'.$t['brand'].'</label>
                    <select id="offer-brand" name="br">
                        <option value="">'.$t['brand'].'</option>';
                        foreach ($brands as $k => $v) {
                            $rtrn .= '<option value="'.$k.'">'.htmlspecialchars($v).'</option>';
                        }
                        $rtrn .= '</select>
                </div>
                <div class="offer-field">
                    <label for="offer-model">'.$t['model'].'</label>
                    <select id="offer-model" name="mo">
                        <option value="">'.$t['model'].'</option>
                    </select>
                </div>
                <div class="offer-field">
                    <label for="offer-year">'.$t['year_vehicle'].'</label>
                    <select id="offer-year" name="yr">
                        <option value="">'.$t['year_vehicle'].'</option>';
                        for ($y = date('Y'); $y >= 2010; $y--) {
                            $rtrn .= '<option value="'.$y.'">'.$y.'</option>';
                        }
                        $rtrn .= '</select>
                </div>
                <div class="offer-field offer-field-full">
                    <label for="offer-vin">'.$t['vin_code'].'</label>
                    <input type="text" id="offer-vin" name="vin" placeholder="'.$t['vin_code'].'" maxlength="17" style="text-transform:uppercase;">
                </div>
            </div>
            <button type="button" class="save-offer-btn" id="save-offer-btn">💾 '.$t['save_offer'].'</button>
        </div>
    </div>
    
    <div class="admin-actions">
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/ctlg" class="admin-btn"><span class="btn-icon">📋</span> '.$t['catalog'].'</a>
        '.($is_super_admin ? '
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/usage" class="admin-btn"><span class="btn-icon">📊</span> '.$t['usage_stats'].'</a>
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/rates" class="admin-btn"><span class="btn-icon">⚙️</span> '.$t['edit_rates'].'</a>
        ' : '').'
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
(function() {
    let EUR_RATE = '.$eur_rate.';
    const RATE_ORIGINAL = "'.$t['save'].'";
    const RATE_SAVED = "'.$t['rate_saved_btn'].'";
    const RATE_ERROR = "'.$t['rate_error_btn'].'";
    
    // Save EUR rate button
    document.getElementById("save-rate-btn").addEventListener("click", function() {
        const btn = this;
        const input = document.getElementById("eur-rate-input");
        const newRate = parseFloat(input.value);
        
        if (isNaN(newRate) || newRate <= 0) {
            btn.textContent = RATE_ERROR;
            btn.classList.remove("success");
            btn.classList.add("error");
            setTimeout(() => {
                btn.textContent = RATE_ORIGINAL;
                btn.classList.remove("error");
            }, 2000);
            return;
        }
        
        btn.disabled = true;
        
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=save_eur_rate&rate=" + encodeURIComponent(newRate)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                EUR_RATE = newRate;
                btn.textContent = RATE_SAVED;
                btn.classList.remove("error");
                btn.classList.add("success");
            } else {
                btn.textContent = RATE_ERROR;
                btn.classList.remove("success");
                btn.classList.add("error");
            }
        })
        .catch(() => {
            btn.textContent = RATE_ERROR;
            btn.classList.remove("success");
            btn.classList.add("error");
        })
        .finally(() => {
            btn.disabled = false;
            setTimeout(() => {
                btn.textContent = RATE_ORIGINAL;
                btn.classList.remove("success", "error");
            }, 2000);
        });
    });
    const TVA_RATE = '.$tva_rate.';
    const HYBRID_DISCOUNT_PLUGIN = '.$hybrid_discount_plugin.';
    const HYBRID_DISCOUNT_FULL = '.$hybrid_discount_full.';
    const DAMAGE_PROTECTION_RATE = '.$damage_protection_rate.';
    const EXCISE_RATES = '.json_encode($excise_rates).';
    
    // Fuel type selection
    document.querySelectorAll(".fuel-type-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.querySelectorAll(".fuel-type-btn").forEach(b => b.classList.remove("active"));
            this.classList.add("active");
            
            const fuel = this.dataset.fuel;
            document.getElementById("hybrid-options").classList.toggle("show", fuel === "hybrid");
            document.getElementById("electric-notice").classList.toggle("show", fuel === "electric");
        });
    });
    
    // Hybrid type selection
    document.querySelectorAll(".hybrid-type-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            document.querySelectorAll(".hybrid-type-btn").forEach(b => b.classList.remove("active"));
            this.classList.add("active");
        });
    });
    
    // Recalculate when damage rate changes
    document.getElementById("damage-rate-input").addEventListener("input", function() {
        if (document.getElementById("results").classList.contains("show")) {
            document.getElementById("calculate-btn").click();
        }
    });
    
    // Calculate button
    document.getElementById("calculate-btn").addEventListener("click", function() {
        const vehicleType = document.getElementById("vehicle_type").value;
        const year = parseInt(document.getElementById("year").value);
        const capacity = parseInt(document.getElementById("capacity").value) || 0;
        const priceEur = parseFloat(document.getElementById("price_eur").value) || 0;
        const transportEur = parseFloat(document.getElementById("transport_eur").value) || 0;
        const totalPriceEur = priceEur + transportEur;
        
        const activeFuel = document.querySelector(".fuel-type-btn.active");
        let fuelType = activeFuel ? activeFuel.dataset.fuel : "benzina";
        
        // Calculate vehicle age
        const currentYear = new Date().getFullYear();
        const age = currentYear - year;
        
        // Get current EUR rate from input
        EUR_RATE = parseFloat(document.getElementById("eur-rate-input").value) || EUR_RATE;
        
        // Calculate value in MDL (vehicle + transport)
        const valueMdl = totalPriceEur * EUR_RATE;
        
        // Calculate excise
        let excise = 0;
        
        if (fuelType === "electric") {
            excise = 0;
        } else {
            // Find matching rate
            let baseFuel = fuelType;
            if (fuelType === "hybrid") {
                baseFuel = document.querySelector("input[name=\"hybrid_fuel\"]:checked").value;
            }
            
            // For motorcycles, use motorcycle rates
            if (vehicleType === "motocicleta") {
                const rate = findExciseRate("motocicleta", capacity, age);
                excise = rate * capacity;
            } else if (baseFuel === "diesel" || baseFuel === "benzina") {
                const rate = findExciseRate(baseFuel, capacity, age);
                excise = rate * capacity;
                
                // Apply hybrid discount
                if (fuelType === "hybrid") {
                    const activeHybridBtn = document.querySelector(".hybrid-type-btn.active");
                    const hybridType = activeHybridBtn ? activeHybridBtn.dataset.hybrid : "plugin";
                    if (hybridType === "plugin") {
                        excise = excise * (1 - HYBRID_DISCOUNT_PLUGIN / 100);
                    } else if (hybridType === "full") {
                        excise = excise * (1 - HYBRID_DISCOUNT_FULL / 100);
                    }
                }
            }
        }
        
        // Luxury excise (for vehicles over 600,000 MDL) - Anexa nr.2 Codul Fiscal Moldova
        let luxuryExcise = 0;
        const luxuryRow = document.getElementById("res-luxury-row");
        if (valueMdl >= 600000 && valueMdl <= 700000) {
            luxuryExcise = valueMdl * 0.02; // 2%
        } else if (valueMdl > 700000 && valueMdl <= 800000) {
            luxuryExcise = valueMdl * 0.03; // 3%
        } else if (valueMdl > 800000 && valueMdl <= 900000) {
            luxuryExcise = valueMdl * 0.04; // 4%
        } else if (valueMdl > 900000 && valueMdl <= 1000000) {
            luxuryExcise = valueMdl * 0.05; // 5%
        } else if (valueMdl > 1000000 && valueMdl <= 1200000) {
            luxuryExcise = valueMdl * 0.06; // 6%
        } else if (valueMdl > 1200000 && valueMdl <= 1400000) {
            luxuryExcise = valueMdl * 0.07; // 7%
        } else if (valueMdl > 1400000 && valueMdl <= 1600000) {
            luxuryExcise = valueMdl * 0.08; // 8%
        } else if (valueMdl > 1600000 && valueMdl <= 1800000) {
            luxuryExcise = valueMdl * 0.09; // 9%
        } else if (valueMdl > 1800000) {
            luxuryExcise = valueMdl * 0.10; // 10%
        }
        
        // Customs procedures fee (0.4% of customs value, max 1800 EUR)
        const maxFeeEur = 1800;
        let customsFee = valueMdl * 0.004;
        if (customsFee > maxFeeEur * EUR_RATE) {
            customsFee = maxFeeEur * EUR_RATE;
        }
        
        // Damage protection (configurable % of vehicle price, not including transport)
        const damageRate = parseFloat(document.getElementById("damage-rate-input").value) || DAMAGE_PROTECTION_RATE;
        const damageProtection = priceEur * EUR_RATE * (damageRate / 100);
        
        // Export declaration (MRN) - fixed 50 EUR
        const exportDeclaration = 50 * EUR_RATE;
        
        // Bank commission SWIFT - fixed 25 EUR
        const bankCommission = 25 * EUR_RATE;
        
        // Auction commission - fixed 350 EUR
        const auctionCommission = 350 * EUR_RATE;
        
        // Pollution tax - fixed 85 EUR
        const pollutionTax = 85 * EUR_RATE;
        
        // Shipping documents - fixed 20 EUR
        const shippingDocs = 20 * EUR_RATE;
        
        // Accessories - 0 EUR (placeholder)
        const accessories = 0;
        
        // Transaction commission - 0 EUR (placeholder)
        const transactionCommission = 0;
        
        // Total
        const total = excise + luxuryExcise + customsFee + damageProtection + exportDeclaration + bankCommission + auctionCommission + pollutionTax + shippingDocs + accessories + transactionCommission;
        
        // Display results in editable inputs
        setResultValue("value", valueMdl);
        setResultValue("excise", excise);
        
        // Show/hide luxury excise row
        if (luxuryExcise > 0) {
            luxuryRow.style.display = "flex";
            setResultValue("luxury", luxuryExcise);
        } else {
            luxuryRow.style.display = "none";
            setResultValue("luxury", 0);
        }
        
        setResultValue("customs", customsFee);
        setResultValue("damage", damageProtection);
        setResultValue("export", exportDeclaration);
        setResultValue("bank", bankCommission);
        setResultValue("auction", auctionCommission);
        setResultValue("pollution", pollutionTax);
        setResultValue("shipping", shippingDocs);
        setResultValue("accessories", accessories);
        setResultValue("transaction", transactionCommission);
        
        // Calculate and display totals
        recalculateTotals();
        
        document.getElementById("results").classList.add("show");
        
        // Log usage
        logUsage();
    });
    
    function findExciseRate(fuelType, capacity, age) {
        for (const rate of EXCISE_RATES) {
            if (rate.fuel_type !== fuelType) continue;
            
            const capMin = parseInt(rate.capacity_min);
            const capMax = parseInt(rate.capacity_max);
            const ageMin = parseInt(rate.age_min);
            const ageMax = parseInt(rate.age_max);
            
            const capMatch = capacity >= capMin && (capMax === 0 || capacity <= capMax);
            const ageMatch = age >= ageMin && (ageMax === 0 || age <= ageMax);
            
            if (capMatch && ageMatch) {
                return parseFloat(rate.rate);
            }
        }
        return 0;
    }
    
    function formatNumber(num) {
        return Math.round(num).toLocaleString("ro-MD", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    
    // Set value in both MDL and EUR inputs
    function setResultValue(field, mdlValue) {
        const mdlInput = document.getElementById("res-" + field + "-mdl");
        const eurInput = document.getElementById("res-" + field + "-eur");
        if (mdlInput) mdlInput.value = Math.round(mdlValue);
        if (eurInput) eurInput.value = Math.round(mdlValue / EUR_RATE);
    }
    
    // Recalculate totals based on current input values
    function recalculateTotals() {
        const fields = ["excise", "luxury", "customs", "damage", "export", "bank", "auction", "pollution", "shipping", "accessories", "transaction"];
        let totalMdl = 0;
        
        fields.forEach(field => {
            const mdlInput = document.getElementById("res-" + field + "-mdl");
            if (mdlInput) {
                const val = parseFloat(mdlInput.value) || 0;
                // Skip luxury if row is hidden
                if (field === "luxury" && document.getElementById("res-luxury-row").style.display === "none") {
                    return;
                }
                totalMdl += val;
            }
        });
        
        const valueMdl = parseFloat(document.getElementById("res-value-mdl").value) || 0;
        const vehicleTotalMdl = valueMdl + totalMdl;
        
        // Update total inputs
        document.getElementById("res-total-mdl").value = Math.round(totalMdl);
        document.getElementById("res-total-eur").value = Math.round(totalMdl / EUR_RATE);
        document.getElementById("res-vehicle-total-mdl").value = Math.round(vehicleTotalMdl);
        document.getElementById("res-vehicle-total-eur").value = Math.round(vehicleTotalMdl / EUR_RATE);
    }
    
    // Add event listeners for editable inputs
    document.querySelectorAll(".editable-input:not([readonly])").forEach(input => {
        input.addEventListener("input", function() {
            const field = this.dataset.field;
            const isEur = this.classList.contains("eur-input");
            const mdlInput = document.getElementById("res-" + field + "-mdl");
            const eurInput = document.getElementById("res-" + field + "-eur");
            
            if (isEur) {
                // EUR changed, update MDL
                const eurVal = parseFloat(this.value) || 0;
                mdlInput.value = Math.round(eurVal * EUR_RATE);
            } else {
                // MDL changed, update EUR
                const mdlVal = parseFloat(this.value) || 0;
                eurInput.value = Math.round(mdlVal / EUR_RATE);
            }
            
            // Recalculate totals
            recalculateTotals();
        });
    });
    
    function logUsage() {
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=log_usage"
        });
    }
    
    // Export PDF function
    function removeDiacritics(str) {
        return str
            .replace(/ă/g, "a").replace(/Ă/g, "A")
            .replace(/â/g, "a").replace(/Â/g, "A")
            .replace(/î/g, "i").replace(/Î/g, "I")
            .replace(/ș/g, "s").replace(/Ș/g, "S")
            .replace(/ț/g, "t").replace(/Ț/g, "T")
            .replace(/ş/g, "s").replace(/Ş/g, "S")
            .replace(/ţ/g, "t").replace(/Ţ/g, "T");
    }
    
    // PDF translations
    const pdfTranslations = {
        ro: {
            results: "Rezultatul calculului",
            value_mdl: "Valoarea in vama (MDL)",
            excise: "Acciza",
            customs_duty: "Taxa proceduri vamale",
            damage_protection: "Protectie impotriva daunelor",
            export_declaration: "Declaratia de export (MRN)",
            bank_commission: "Comision bancar SWIFT",
            auction_commission: "Comision licitatie",
            pollution_tax: "Taxa de poluare",
            shipping_docs: "Livrarea documentelor",
            accessories: "Accesorii",
            transaction_commission: "Comision pentru tranzactie",
            total: "TOTAL COSTURI VAMUIRE",
            vehicle_total: "SUMA TOTALA VEHICUL"
        },
        ru: {
            results: "Результат расчёта",
            value_mdl: "Таможенная стоимость (MDL)",
            excise: "Акциз",
            customs_duty: "Сбор за таможенные процедуры",
            damage_protection: "Защита от повреждений",
            export_declaration: "Декларация экспорта (MRN)",
            bank_commission: "Банковская комиссия SWIFT",
            auction_commission: "Комиссия аукциона",
            pollution_tax: "Налог на загрязнение",
            shipping_docs: "Доставка документов",
            accessories: "Аксессуары",
            transaction_commission: "Комиссия за транзакцию",
            total: "ИТОГО РАСХОДЫ НА РАСТАМОЖКУ",
            vehicle_total: "ОБЩАЯ СУММА ЗА АВТОМОБИЛЬ"
        },
        en: {
            results: "Calculation Results",
            value_mdl: "Customs Value (MDL)",
            excise: "Excise",
            customs_duty: "Customs Procedures Fee",
            damage_protection: "Damage Protection",
            export_declaration: "Export Declaration (MRN)",
            bank_commission: "Bank Commission SWIFT",
            auction_commission: "Auction Commission",
            pollution_tax: "Pollution Tax",
            shipping_docs: "Document Shipping",
            accessories: "Accessories",
            transaction_commission: "Transaction Commission",
            total: "TOTAL CUSTOMS COSTS",
            vehicle_total: "TOTAL VEHICLE COST"
        }
    };
    
    document.getElementById("export-pdf-btn").addEventListener("click", function() {
        const lang = document.getElementById("pdf-lang-select").value;
        const t = pdfTranslations[lang];
        
        // Get all values
        const values = {
            value: { mdl: document.getElementById("res-value-mdl").value || "0", eur: document.getElementById("res-value-eur").value || "0" },
            excise: { mdl: document.getElementById("res-excise-mdl").value || "0", eur: document.getElementById("res-excise-eur").value || "0" },
            customs: { mdl: document.getElementById("res-customs-mdl").value || "0", eur: document.getElementById("res-customs-eur").value || "0" },
            damage: { mdl: document.getElementById("res-damage-mdl").value || "0", eur: document.getElementById("res-damage-eur").value || "0" },
            exportDecl: { mdl: document.getElementById("res-export-mdl").value || "0", eur: document.getElementById("res-export-eur").value || "0" },
            bank: { mdl: document.getElementById("res-bank-mdl").value || "0", eur: document.getElementById("res-bank-eur").value || "0" },
            auction: { mdl: document.getElementById("res-auction-mdl").value || "0", eur: document.getElementById("res-auction-eur").value || "0" },
            pollution: { mdl: document.getElementById("res-pollution-mdl").value || "0", eur: document.getElementById("res-pollution-eur").value || "0" },
            shipping: { mdl: document.getElementById("res-shipping-mdl").value || "0", eur: document.getElementById("res-shipping-eur").value || "0" },
            accessories: { mdl: document.getElementById("res-accessories-mdl").value || "0", eur: document.getElementById("res-accessories-eur").value || "0" },
            transaction: { mdl: document.getElementById("res-transaction-mdl").value || "0", eur: document.getElementById("res-transaction-eur").value || "0" },
            total: { mdl: document.getElementById("res-total-mdl").value || "0", eur: document.getElementById("res-total-eur").value || "0" },
            vehicle: { mdl: document.getElementById("res-vehicle-total-mdl").value || "0", eur: document.getElementById("res-vehicle-total-eur").value || "0" }
        };
        
        // For Russian - use html2canvas (supports Cyrillic)
        if (lang === "ru") {
            const pdfContent = document.createElement("div");
            pdfContent.id = "pdf-temp-content";
            pdfContent.style.cssText = "position:absolute;left:-9999px;width:700px;padding:40px;font-family:Arial,sans-serif;background:#fff;";
            pdfContent.innerHTML = `
                <h1 style="text-align:center;font-size:18px;margin-bottom:10px;font-weight:bold;">${t.results}</h1>
                <p style="text-align:center;color:#666;margin-bottom:20px;font-size:10px;">${new Date().toLocaleDateString("ro-RO")} ${new Date().toLocaleTimeString("ro-RO")}</p>
                <hr style="border:none;border-top:1px solid #ccc;margin-bottom:20px;">
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <tr><td style="padding:8px 0;">${t.value_mdl}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.value.mdl))} MDL  (${formatNumber(parseFloat(values.value.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.excise}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.excise.mdl))} MDL  (${formatNumber(parseFloat(values.excise.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.customs_duty}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.customs.mdl))} MDL  (${formatNumber(parseFloat(values.customs.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.damage_protection}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.damage.mdl))} MDL  (${formatNumber(parseFloat(values.damage.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.export_declaration}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.exportDecl.mdl))} MDL  (${formatNumber(parseFloat(values.exportDecl.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.bank_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.bank.mdl))} MDL  (${formatNumber(parseFloat(values.bank.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.auction_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.auction.mdl))} MDL  (${formatNumber(parseFloat(values.auction.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.pollution_tax}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.pollution.mdl))} MDL  (${formatNumber(parseFloat(values.pollution.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.shipping_docs}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.shipping.mdl))} MDL  (${formatNumber(parseFloat(values.shipping.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.accessories}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.accessories.mdl))} MDL  (${formatNumber(parseFloat(values.accessories.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.transaction_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.transaction.mdl))} MDL  (${formatNumber(parseFloat(values.transaction.eur))} EUR)</td></tr>
                </table>
                <div style="background:#e2001a;color:#fff;padding:12px 15px;margin-top:20px;display:flex;justify-content:space-between;font-weight:bold;font-size:12px;">
                    <span>${t.total}</span>
                    <span>${formatNumber(parseFloat(values.total.mdl))} MDL  (${formatNumber(parseFloat(values.total.eur))} EUR)</span>
                </div>
                <div style="background:#555;color:#fff;padding:12px 15px;display:flex;justify-content:space-between;font-weight:bold;font-size:12px;">
                    <span>${t.vehicle_total}</span>
                    <span>${formatNumber(parseFloat(values.vehicle.mdl))} MDL  (${formatNumber(parseFloat(values.vehicle.eur))} EUR)</span>
                </div>
            `;
            document.body.appendChild(pdfContent);
            
            html2canvas(pdfContent, { scale: 2, useCORS: true, backgroundColor: "#ffffff" }).then(canvas => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF("p", "mm", "a4");
                const imgData = canvas.toDataURL("image/jpeg", 0.92);
                const pageWidth = doc.internal.pageSize.getWidth();
                const imgWidth = pageWidth - 20;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                doc.addImage(imgData, "JPEG", 10, 10, imgWidth, imgHeight);
                doc.save("calculator_auto_" + new Date().toLocaleDateString("ro-RO").replace(/\./g, "-") + ".pdf");
                
                document.body.removeChild(pdfContent);
            });
            return;
        }
        
        // For RO and EN - use jsPDF text (smaller file size)
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        const pageWidth = doc.internal.pageSize.getWidth();
        let y = 20;
        
        // Title
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.text(t.results, pageWidth / 2, y, { align: "center" });
        y += 15;
        
        // Date
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text(new Date().toLocaleDateString("ro-RO") + " " + new Date().toLocaleTimeString("ro-RO"), pageWidth / 2, y, { align: "center" });
        y += 15;
        
        // Line
        doc.setDrawColor(200);
        doc.line(20, y, pageWidth - 20, y);
        y += 10;
        
        // Results data
        const results = [
            { label: t.value_mdl, mdl: values.value.mdl, eur: values.value.eur },
            { label: t.excise, mdl: values.excise.mdl, eur: values.excise.eur },
            { label: t.customs_duty, mdl: values.customs.mdl, eur: values.customs.eur },
            { label: t.damage_protection, mdl: values.damage.mdl, eur: values.damage.eur },
            { label: t.export_declaration, mdl: values.exportDecl.mdl, eur: values.exportDecl.eur },
            { label: t.bank_commission, mdl: values.bank.mdl, eur: values.bank.eur },
            { label: t.auction_commission, mdl: values.auction.mdl, eur: values.auction.eur },
            { label: t.pollution_tax, mdl: values.pollution.mdl, eur: values.pollution.eur },
            { label: t.shipping_docs, mdl: values.shipping.mdl, eur: values.shipping.eur },
            { label: t.accessories, mdl: values.accessories.mdl, eur: values.accessories.eur },
            { label: t.transaction_commission, mdl: values.transaction.mdl, eur: values.transaction.eur }
        ];
        
        doc.setFontSize(11);
        results.forEach(item => {
            doc.setFont("helvetica", "normal");
            doc.text(item.label, 20, y);
            doc.setFont("helvetica", "bold");
            doc.text(formatNumber(parseFloat(item.mdl)) + " MDL  (" + formatNumber(parseFloat(item.eur)) + " EUR)", pageWidth - 20, y, { align: "right" });
            y += 8;
        });
        
        // Totals
        y += 5;
        doc.setDrawColor(200);
        doc.line(20, y, pageWidth - 20, y);
        y += 10;
        
        // Total customs
        doc.setFillColor(226, 0, 26);
        doc.rect(15, y - 5, pageWidth - 30, 12, "F");
        doc.setTextColor(255, 255, 255);
        doc.setFont("helvetica", "bold");
        doc.setFontSize(12);
        doc.text(t.total, 20, y + 3);
        doc.text(formatNumber(parseFloat(values.total.mdl)) + " MDL  (" + formatNumber(parseFloat(values.total.eur)) + " EUR)", pageWidth - 20, y + 3, { align: "right" });
        y += 15;
        
        // Vehicle total
        doc.setFillColor(85, 85, 85);
        doc.rect(15, y - 5, pageWidth - 30, 12, "F");
        doc.setTextColor(255, 255, 255);
        doc.text(t.vehicle_total, 20, y + 3);
        doc.text(formatNumber(parseFloat(values.vehicle.mdl)) + " MDL  (" + formatNumber(parseFloat(values.vehicle.eur)) + " EUR)", pageWidth - 20, y + 3, { align: "right" });
        
        // Reset text color
        doc.setTextColor(0, 0, 0);
        
        // Save PDF
        doc.save("calculator_auto_" + new Date().toLocaleDateString("ro-RO").replace(/\./g, "-") + ".pdf");
    });
    
    // Save offer button
    const OFFER_SAVED_TEXT = "'.$t['offer_saved'].'";
    const OFFER_ERROR_TEXT = "'.$t['offer_error'].'";
    const OFFER_SAVE_TEXT = "💾 '.$t['save_offer'].'";
    
    document.getElementById("save-offer-btn").addEventListener("click", async function() {
        const btn = this;
        const lang = document.getElementById("pdf-lang-select").value;
        const t = pdfTranslations[lang];
        
        // Get offer fields
        const clientName = document.getElementById("offer-client-name").value.trim();
        const brandSelect = document.getElementById("offer-brand");
        const modelSelect = document.getElementById("offer-model");
        const brand = brandSelect.value;
        const brandName = brandSelect.options[brandSelect.selectedIndex]?.text || brand;
        const model = modelSelect.value;
        const modelName = modelSelect.options[modelSelect.selectedIndex]?.text || model;
        const year = document.getElementById("offer-year").value;
        const vin = document.getElementById("offer-vin").value.trim().toUpperCase();
        
        // Validate required fields
        if (!clientName || !brand || !model || !year) {
            alert("Completați toate câmpurile obligatorii!");
            return;
        }
        
        btn.disabled = true;
        btn.textContent = "⏳ Se salvează...";
        
        // Get calculation values
        const values = {
            value: { mdl: document.getElementById("res-value-mdl").value || "0", eur: document.getElementById("res-value-eur").value || "0" },
            excise: { mdl: document.getElementById("res-excise-mdl").value || "0", eur: document.getElementById("res-excise-eur").value || "0" },
            customs: { mdl: document.getElementById("res-customs-mdl").value || "0", eur: document.getElementById("res-customs-eur").value || "0" },
            damage: { mdl: document.getElementById("res-damage-mdl").value || "0", eur: document.getElementById("res-damage-eur").value || "0" },
            exportDecl: { mdl: document.getElementById("res-export-mdl").value || "0", eur: document.getElementById("res-export-eur").value || "0" },
            bank: { mdl: document.getElementById("res-bank-mdl").value || "0", eur: document.getElementById("res-bank-eur").value || "0" },
            auction: { mdl: document.getElementById("res-auction-mdl").value || "0", eur: document.getElementById("res-auction-eur").value || "0" },
            pollution: { mdl: document.getElementById("res-pollution-mdl").value || "0", eur: document.getElementById("res-pollution-eur").value || "0" },
            shipping: { mdl: document.getElementById("res-shipping-mdl").value || "0", eur: document.getElementById("res-shipping-eur").value || "0" },
            accessories: { mdl: document.getElementById("res-accessories-mdl").value || "0", eur: document.getElementById("res-accessories-eur").value || "0" },
            transaction: { mdl: document.getElementById("res-transaction-mdl").value || "0", eur: document.getElementById("res-transaction-eur").value || "0" },
            total: { mdl: document.getElementById("res-total-mdl").value || "0", eur: document.getElementById("res-total-eur").value || "0" },
            vehicle: { mdl: document.getElementById("res-vehicle-total-mdl").value || "0", eur: document.getElementById("res-vehicle-total-eur").value || "0" }
        };
        
        try {
            // Save offer to database (only data, no PDF)
            const saveResponse = await fetch("/ajax.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "tp=adm&pg=calculator&fn=save_offer" +
                    "&client_name=" + encodeURIComponent(clientName) +
                    "&brand=" + encodeURIComponent(brandName) +
                    "&model=" + encodeURIComponent(modelName) +
                    "&year=" + encodeURIComponent(year) +
                    "&vin=" + encodeURIComponent(vin) +
                    "&pdf_lang=" + encodeURIComponent(lang) +
                    "&calculation_data=" + encodeURIComponent(JSON.stringify(values))
            });
            
            const saveData = await saveResponse.json();
            
            if (!saveData.success) {
                throw new Error(saveData.error || "Failed to save offer");
            }
            
            btn.textContent = "✓ " + OFFER_SAVED_TEXT;
            btn.classList.add("success");
            
            // Clear form
            document.getElementById("offer-client-name").value = "";
            document.getElementById("offer-brand").value = "";
            document.getElementById("offer-model").value = "";
            document.getElementById("offer-year").value = "";
            document.getElementById("offer-vin").value = "";
            
        } catch (error) {
            console.error("Error saving offer:", error);
            btn.textContent = "✗ " + OFFER_ERROR_TEXT;
            btn.classList.add("error");
        }
        
        setTimeout(() => {
            btn.disabled = false;
            btn.textContent = OFFER_SAVE_TEXT;
            btn.classList.remove("success", "error");
        }, 3000);
    });
    
    // Load models when brand changes
    document.getElementById("offer-brand").addEventListener("change", function() {
        const brand = this.value;
        const modelSelect = document.getElementById("offer-model");
        const defaultText = "'.$t['model'].'";
        
        // Reset model select
        modelSelect.innerHTML = "<option value=\"\">" + defaultText + "</option>";
        
        if (!brand) return;
        
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=get_models&brand=" + encodeURIComponent(brand)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.models) {
                data.models.forEach(m => {
                    const opt = document.createElement("option");
                    opt.value = m.mo;
                    opt.textContent = m.mo_nm;
                    modelSelect.appendChild(opt);
                });
            }
        })
        .catch(err => console.error("Error loading models:", err));
    });
})();
</script>';

echo $rtrn;
