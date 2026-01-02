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
    
    #calculator-container .new-offer-btn {
        width: 100%;
        margin-top: 0.5rem;
        padding: 0.85rem;
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px solid #6c757d;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    #calculator-container .new-offer-btn:hover {
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }
    
    #calculator-container .field-error {
        border: 2px solid #dc3545 !important;
        box-shadow: 0 0 5px rgba(220, 53, 69, 0.5) !important;
    }
    
    #calculator-container .offer-images-section {
        margin-top: 1.5rem;
        padding: 1rem;
        background: #fff;
        border-radius: 8px;
        border: 2px dashed #dee2e6;
    }
    
    #calculator-container .offer-images-title {
        margin: 0 0 1rem 0;
        font-size: 0.95rem;
        color: #333;
        font-weight: 600;
    }
    
    #calculator-container .images-upload-area {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    #calculator-container .upload-images-btn {
        padding: 0.6rem 1.2rem;
        background: linear-gradient(135deg, #e2001a 0%, #bf0016 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    #calculator-container .upload-images-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(226, 0, 26, 0.4);
    }
    
    #calculator-container .upload-hint {
        font-size: 0.8rem;
        color: #6c757d;
        font-style: italic;
    }
    
    #calculator-container .offer-images-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        min-height: 50px;
    }
    
    @media (max-width: 600px) {
        #calculator-container .offer-images-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    #calculator-container .image-item {
        position: relative;
        aspect-ratio: 4/3;
        border: 2px solid #28a745;
        border-radius: 8px;
        overflow: hidden;
        cursor: grab;
        transition: all 0.2s ease;
        background: #f8f9fa;
    }
    
    #calculator-container .image-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: scale(1.02);
    }
    
    #calculator-container .image-item.dragging {
        opacity: 0.5;
        cursor: grabbing;
    }
    
    #calculator-container .image-item.drag-over {
        border-color: #e2001a;
        border-width: 3px;
    }
    
    #calculator-container .image-item .image-number {
        position: absolute;
        top: 0.25rem;
        left: 0.25rem;
        background: #e2001a;
        color: #fff;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 600;
        z-index: 2;
    }
    
    #calculator-container .image-item .preview-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    #calculator-container .image-item .remove-image {
        position: absolute;
        top: 0.35rem;
        right: 0.35rem;
        background: #dc3545;
        color: #fff;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: bold;
        opacity: 0.9;
        transition: all 0.2s;
        z-index: 2;
    }
    
    #calculator-container .image-item .remove-image:hover {
        opacity: 1;
        transform: scale(1.1);
        background: #c82333;
    }
    
    #calculator-container .image-item:hover .remove-image {
        opacity: 1;
    }
    
    #calculator-container .images-counter {
        margin-top: 0.75rem;
        text-align: center;
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    #calculator-container .images-counter.complete {
        color: #28a745;
        font-weight: 600;
    }
    
    #calculator-container .images-counter.incomplete {
        color: #dc3545;
    }
    
    #calculator-container .offer-images-section.field-error {
        border-color: #dc3545;
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
    
    /* Toggle Switch Styles */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
        margin-right: 10px;
        vertical-align: middle;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    .toggle-switch input:checked + .toggle-slider {
        background-color: #e2001a;
    }
    
    .toggle-switch input:checked + .toggle-slider:before {
        transform: translateX(26px);
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
        <div class="result-row">
            <span class="label">
                <label class="toggle-switch" for="enable-polishing">
                    <input type="checkbox" id="enable-polishing">
                    <span class="toggle-slider"></span>
                </label>
                <label for="enable-polishing" style="cursor:pointer;">'.$t['polishing'].'</label>
            </span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-polishing-mdl" data-field="polishing" step="1" disabled> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-polishing-eur" data-field="polishing" step="1" disabled> EUR</span></span>
        </div>
        <div class="result-row">
            <span class="label">
                <label class="toggle-switch" for="enable-painting">
                    <input type="checkbox" id="enable-painting">
                    <span class="toggle-slider"></span>
                </label>
                <label for="enable-painting" style="cursor:pointer;">'.$t['painting'].'</label>
            </span>
            <span class="value editable-value"><input type="number" class="editable-input" id="res-painting-mdl" data-field="painting" step="1" disabled> MDL <span class="eur-equiv">~ <input type="number" class="editable-input eur-input" id="res-painting-eur" data-field="painting" step="1" disabled> EUR</span></span>
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
                <div class="offer-field">
                    <label for="offer-bodywork">'.$t['bodywork'].'</label>
                    <select id="offer-bodywork" name="bodywork">
                        <option value="">'.$t['bodywork'].'</option>';
                        foreach ($lng['l']['car']['bt'] as $k => $v) {
                            $rtrn .= '<option value="'.$k.'">'.$v.'</option>';
                        }
                        $rtrn .= '</select>
                </div>
                <div class="offer-field">
                    <label for="offer-seats">'.$t['seats'].'</label>
                    <select id="offer-seats" name="seats">
                        <option value="">'.$t['seats'].'</option>';
                        for ($s = 2; $s <= 15; $s++) {
                            $selected = ($s == 5) ? ' selected' : '';
                            $rtrn .= '<option value="'.$s.'"'.$selected.'>'.$s.'</option>';
                        }
                        $rtrn .= '</select>
                </div>
                <div class="offer-field">
                    <label for="offer-mileage">'.$t['mileage'].'</label>
                    <input type="number" id="offer-mileage" name="mileage" placeholder="'.$t['mileage'].'" min="0">
                </div>
                <div class="offer-field">
                    <label for="offer-engine-power">'.$t['engine_power'].'</label>
                    <input type="number" id="offer-engine-power" name="engine_power" placeholder="'.$t['engine_power'].'" min="0">
                </div>
                <div class="offer-field">
                    <label for="offer-transmission">'.$t['transmission'].'</label>
                    <select id="offer-transmission" name="transmission">
                        <option value="">'.$t['transmission'].'</option>';
                        foreach ($lng['l']['car']['tra'] as $k => $v) {
                            $rtrn .= '<option value="'.$k.'">'.$v.'</option>';
                        }
                        $rtrn .= '</select>
                </div>
                <div class="offer-field">
                    <label for="offer-drive-type">'.$t['drive_type'].'</label>
                    <select id="offer-drive-type" name="drive_type">
                        <option value="">'.$t['drive_type'].'</option>';
                        foreach ($lng['l']['car']['wd'] as $k => $v) {
                            $rtrn .= '<option value="'.$k.'">'.$v.'</option>';
                        }
                        $rtrn .= '</select>
                </div>
                <div class="offer-field">
                    <label for="offer-color">'.$t['color'].'</label>
                    <select id="offer-color" name="color">
                        <option value="">'.$t['color'].'</option>';
                        foreach ($lng['l']['car']['clr'] as $k => $v) {
                            $rtrn .= '<option value="'.$k.'">'.$v.'</option>';
                        }
                        $rtrn .= '</select>
                </div>
            </div>
            
            <div class="offer-images-section" id="offer-images-section">
                <h4 class="offer-images-title">📷 '.$t['offer_images'].'</h4>
                <div class="images-upload-area" id="images-upload-area">
                    <input type="file" id="offer-images-input" accept="image/jpeg,image/png,image/webp" multiple style="display:none;">
                    <button type="button" class="upload-images-btn" id="upload-images-btn">📷 '.$t['select_images'].'</button>
                    <span class="upload-hint">'.$t['drag_to_reorder'].'</span>
                </div>
                <div class="offer-images-grid" id="offer-images-grid"></div>
                <div class="images-counter" id="images-counter">0 / 6</div>
            </div>
            
            <button type="button" class="save-offer-btn" id="save-offer-btn">💾 '.$t['save_offer'].'</button>
            <button type="button" class="new-offer-btn" id="new-offer-btn" onclick="location.reload();">🔄 '.$t['new_offer'].'</button>
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
        const fields = ["excise", "luxury", "customs", "damage", "export", "bank", "auction", "pollution", "shipping", "accessories", "transaction", "polishing", "painting"];
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
    
    // Checkbox event listeners for Polizare and Vopsire
    document.getElementById("enable-polishing").addEventListener("change", function() {
        const mdlInput = document.getElementById("res-polishing-mdl");
        const eurInput = document.getElementById("res-polishing-eur");
        if (this.checked) {
            mdlInput.disabled = false;
            eurInput.disabled = false;
            mdlInput.value = 4500;
            eurInput.value = Math.round(4500 / EUR_RATE);
        } else {
            mdlInput.disabled = true;
            eurInput.disabled = true;
            mdlInput.value = 0;
            eurInput.value = 0;
        }
        recalculateTotals();
    });
    
    document.getElementById("enable-painting").addEventListener("change", function() {
        const mdlInput = document.getElementById("res-painting-mdl");
        const eurInput = document.getElementById("res-painting-eur");
        if (this.checked) {
            mdlInput.disabled = false;
            eurInput.disabled = false;
            mdlInput.value = 3000;
            eurInput.value = Math.round(3000 / EUR_RATE);
        } else {
            mdlInput.disabled = true;
            eurInput.disabled = true;
            mdlInput.value = 0;
            eurInput.value = 0;
        }
        recalculateTotals();
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
            results: "Ofertă comercială",
            value_mdl: "Valoarea in vama",
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
            polishing: "Polizare si curatire chimica",
            painting: "Vopsire",
            total: "TOTAL COSTURI VAMUIRE",
            vehicle_total: "SUMA TOTALA VEHICUL",
            sales_manager: "Manager vanzari",
            address: "Chisinau str Calea Mosilor 11"
        },
        ru: {
            results: "Результат расчёта",
            value_mdl: "Таможенная стоимость",
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
            polishing: "Полировка и химчистка",
            painting: "Покраска",
            total: "ИТОГО РАСХОДЫ НА РАСТАМОЖКУ",
            vehicle_total: "ОБЩАЯ СУММА ЗА АВТОМОБИЛЬ",
            sales_manager: "Менеджер по продажам",
            address: "Кишинев ул. Каля Мошилор 11"
        },
        en: {
            results: "Commercial Offer",
            value_mdl: "Customs Value",
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
            polishing: "Polishing and Chemical Cleaning",
            painting: "Painting",
            total: "TOTAL CUSTOMS COSTS",
            vehicle_total: "TOTAL VEHICLE COST",
            sales_manager: "Sales Manager",
            address: "Chisinau str Calea Mosilor 11"
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
            polishing: { mdl: document.getElementById("res-polishing-mdl").value || "0", eur: document.getElementById("res-polishing-eur").value || "0" },
            painting: { mdl: document.getElementById("res-painting-mdl").value || "0", eur: document.getElementById("res-painting-eur").value || "0" },
            total: { mdl: document.getElementById("res-total-mdl").value || "0", eur: document.getElementById("res-total-eur").value || "0" },
            vehicle: { mdl: document.getElementById("res-vehicle-total-mdl").value || "0", eur: document.getElementById("res-vehicle-total-eur").value || "0" }
        };
        
        // For Russian - use html2canvas for text (supports Cyrillic), then add graphics with jsPDF
        if (lang === "ru") {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            
            // Load images first
            const bgImg = new Image();
            const logoImg = new Image();
            let bgLoaded = false, logoLoaded = false;
            
            bgImg.src = "/content/admin/page/calculator/img/pdf-bg.jpg";
            logoImg.src = "/content/admin/page/calculator/img/logo.png";
            
            function generateRuPDF() {
                if (!bgLoaded || !logoLoaded) return;
                
                // Background: 3% from top, 75% height
                const imgY = pageHeight * 0.03;
                const imgHeight = pageHeight * 0.75;
                doc.addImage(bgImg, "JPEG", 0, imgY, pageWidth, imgHeight);
                
                // Logo with black background
                const bgPadding = 8;
                const bgX = imgY;
                const logoWidth = 50;
                const logoHeight = 20;
                const bgWidth = logoWidth + bgPadding * 2;
                const bgHeight = logoHeight + bgPadding + imgY;
                doc.setFillColor(0, 0, 0);
                doc.rect(bgX, 0, bgWidth, bgHeight, "F");
                doc.addImage(logoImg, "PNG", bgX + bgPadding, bgPadding, logoWidth, logoHeight);
                
                // Create HTML content for text (Cyrillic support)
                const pdfContent = document.createElement("div");
                pdfContent.id = "pdf-temp-content";
                pdfContent.style.cssText = "position:absolute;left:-9999px;width:500px;padding:20px;font-family:Arial,sans-serif;background:rgba(0,0,0,0.5);border-radius:5px;";
                
                // Build table rows dynamically
                let tableRows = `
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.value_mdl}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.value.mdl))} MDL  (${formatNumber(parseFloat(values.value.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.excise}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.excise.mdl))} MDL  (${formatNumber(parseFloat(values.excise.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.customs_duty}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.customs.mdl))} MDL  (${formatNumber(parseFloat(values.customs.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.damage_protection}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.damage.mdl))} MDL  (${formatNumber(parseFloat(values.damage.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.export_declaration}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.exportDecl.mdl))} MDL  (${formatNumber(parseFloat(values.exportDecl.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.bank_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.bank.mdl))} MDL  (${formatNumber(parseFloat(values.bank.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.auction_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.auction.mdl))} MDL  (${formatNumber(parseFloat(values.auction.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.pollution_tax}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.pollution.mdl))} MDL  (${formatNumber(parseFloat(values.pollution.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.shipping_docs}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.shipping.mdl))} MDL  (${formatNumber(parseFloat(values.shipping.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.accessories}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.accessories.mdl))} MDL  (${formatNumber(parseFloat(values.accessories.eur))} EUR)</td></tr>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.transaction_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.transaction.mdl))} MDL  (${formatNumber(parseFloat(values.transaction.eur))} EUR)</td></tr>`;
                
                // Add polishing and painting only if enabled
                if (document.getElementById("enable-polishing").checked) {
                    tableRows += `<tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.polishing}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.polishing.mdl))} MDL  (${formatNumber(parseFloat(values.polishing.eur))} EUR)</td></tr>`;
                }
                if (document.getElementById("enable-painting").checked) {
                    tableRows += `<tr style="border-bottom:1px solid rgba(255,255,255,0.2);"><td style="padding:6px 0;">${t.painting}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.painting.mdl))} MDL  (${formatNumber(parseFloat(values.painting.eur))} EUR)</td></tr>`;
                }
                
                pdfContent.innerHTML = `
                    <h1 style="text-align:center;font-size:24px;margin-bottom:15px;font-weight:bold;color:#fff;">${t.results}</h1>
                    <table style="width:100%;border-collapse:collapse;font-size:12px;color:#fff;">
                        ${tableRows}
                    </table>
                    <div style="background:#e2001a;color:#fff;padding:10px 12px;margin-top:15px;display:flex;justify-content:space-between;font-weight:bold;font-size:13px;">
                        <span>${t.total}</span>
                        <span>${formatNumber(parseFloat(values.total.mdl))} MDL  (${formatNumber(parseFloat(values.total.eur))} EUR)</span>
                    </div>
                    <div style="background:#555;color:#fff;padding:10px 12px;display:flex;justify-content:space-between;font-weight:bold;font-size:13px;">
                        <span>${t.vehicle_total}</span>
                        <span>${formatNumber(parseFloat(values.vehicle.mdl))} MDL  (${formatNumber(parseFloat(values.vehicle.eur))} EUR)</span>
                    </div>
                `;
                document.body.appendChild(pdfContent);
                
                html2canvas(pdfContent, { scale: 2, useCORS: true, backgroundColor: null }).then(canvas => {
                    const imgData = canvas.toDataURL("image/png");
                    const contentWidth = pageWidth - 30;
                    const contentHeight = (canvas.height * contentWidth) / canvas.width;
                    const contentY = logoHeight + bgPadding + imgY + 3;
                    
                    doc.addImage(imgData, "PNG", 15, contentY, contentWidth, contentHeight);
                    document.body.removeChild(pdfContent);
                    
                    // Create footer with Cyrillic support
                    const footerContent = document.createElement("div");
                    footerContent.style.cssText = "position:absolute;left:-9999px;width:400px;padding:10px;font-family:Arial,sans-serif;background:#fff;display:flex;gap:40px;";
                    footerContent.innerHTML = `
                        <div style="font-size:11px;color:#333;">
                            <div style="font-weight:bold;font-size:12px;margin-bottom:4px;">SAUTO SRL</div>
                            <div>+373 68 68 99 95</div>
                            <div>info@sauto.md</div>
                            <div>${t.address}</div>
                        </div>
                        <div style="font-size:11px;color:#333;">
                            <div style="font-weight:bold;font-size:12px;margin-bottom:4px;">CARP DUMITRU</div>
                            <div>${t.sales_manager}</div>
                            <div>+373 62166880</div>
                            <div>carp@sauto.md</div>
                        </div>
                    `;
                    document.body.appendChild(footerContent);
                    
                    html2canvas(footerContent, { scale: 2, useCORS: true, backgroundColor: "#ffffff" }).then(footerCanvas => {
                        const footerImgData = footerCanvas.toDataURL("image/png");
                        const footerWidth = 120;
                        const footerHeight = (footerCanvas.height * footerWidth) / footerCanvas.width;
                        
                        // Position footer in 22% bottom space, centered vertically
                        const bottomSpaceStart = pageHeight * 0.78;
                        const bottomSpaceHeight = pageHeight * 0.22;
                        const footerY = bottomSpaceStart + (bottomSpaceHeight - footerHeight) / 2;
                        
                        doc.addImage(footerImgData, "PNG", 15, footerY, footerWidth, footerHeight);
                        document.body.removeChild(footerContent);
                        
                        // Red rectangle bottom right
                        const rectWidth = 50;
                        const rectHeight = 60;
                        const rectX = pageWidth - rectWidth - (pageHeight * 0.03);
                        const rectY = pageHeight - rectHeight;
                        doc.setFillColor(226, 0, 26);
                        doc.rect(rectX, rectY, rectWidth, rectHeight, "F");
                        
                        doc.save("calculator_auto_" + new Date().toLocaleDateString("ro-RO").replace(/\./g, "-") + ".pdf");
                    });
                });
            }
            
            bgImg.onload = function() { bgLoaded = true; generateRuPDF(); };
            logoImg.onload = function() { logoLoaded = true; generateRuPDF(); };
            bgImg.onerror = function() { bgLoaded = true; generateRuPDF(); };
            logoImg.onerror = function() { logoLoaded = true; generateRuPDF(); };
            return;
        }
        
        // For RO and EN - use jsPDF text (smaller file size)
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        
        // Add background image and logo
        const bgImg = new Image();
        const logoImg = new Image();
        let bgLoaded = false, logoLoaded = false;
        
        bgImg.src = "/content/admin/page/calculator/img/pdf-bg.jpg";
        logoImg.src = "/content/admin/page/calculator/img/logo.png";
        
        function checkAndGenerate() {
            if (bgLoaded && logoLoaded) {
                // Background: 3% from top, 75% height, 22% space at bottom
                const imgY = pageHeight * 0.03;
                const imgHeight = pageHeight * 0.75;
                doc.addImage(bgImg, "JPEG", 0, imgY, pageWidth, imgHeight);
                
                // Logo with black background: 3% margin on left (same as background image has on top)
                const bgPadding = 8;
                const bgX = imgY;  // same 3% margin as background image top
                const bgY = 0;  // starts at very top of page
                const logoWidth = 50;
                const logoHeight = 20;
                const bgWidth = logoWidth + bgPadding * 2;
                const bgHeight = logoHeight + bgPadding + imgY;  // extend down to overlap with background image
                // Black background
                doc.setFillColor(0, 0, 0);
                doc.rect(bgX, bgY, bgWidth, bgHeight, "F");
                // Logo centered inside black background (equal padding left/right/top)
                doc.addImage(logoImg, "PNG", bgX + bgPadding, bgPadding, logoWidth, logoHeight);
                
                generatePDFContent();
            }
        }
        
        bgImg.onload = function() { bgLoaded = true; checkAndGenerate(); };
        logoImg.onload = function() { logoLoaded = true; checkAndGenerate(); };
        bgImg.onerror = function() { bgLoaded = true; checkAndGenerate(); };
        logoImg.onerror = function() { logoLoaded = true; checkAndGenerate(); };
        
        function generatePDFContent() {
        // Start Y position below logo with black background
        const logoHeight = 20;
        const bgPadding = 8;
        const imgY = pageHeight * 0.03;
        let y = logoHeight + bgPadding + imgY + 10;
        
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
        
        // Add polishing and painting only if enabled
        if (document.getElementById("enable-polishing").checked) {
            results.push({ label: t.polishing, mdl: values.polishing.mdl, eur: values.polishing.eur });
        }
        if (document.getElementById("enable-painting").checked) {
            results.push({ label: t.painting, mdl: values.painting.mdl, eur: values.painting.eur });
        }
        
        // Semi-transparent dark background for title AND text area
        const titleHeight = 35;  // space for title
        const totalBgHeight = titleHeight + results.length * 8;
        doc.setFillColor(0, 0, 0);
        doc.setGState(new doc.GState({opacity: 0.5}));
        doc.rect(15, y, pageWidth - 30, totalBgHeight, "F");
        doc.setGState(new doc.GState({opacity: 1}));
        
        // Title (inside the semi-transparent background)
        y += 15;
        doc.setFontSize(32);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(255, 255, 255);
        doc.text(removeDiacritics(t.results), pageWidth / 2, y, { align: "center" });
        y += 20;
        
        doc.setFontSize(13);
        
        doc.setTextColor(255, 255, 255);
        results.forEach((item, index) => {
            const valueText = formatNumber(parseFloat(item.mdl)) + " MDL  (" + formatNumber(parseFloat(item.eur)) + " EUR)";
            doc.setFont("helvetica", "normal");
            doc.text(item.label, 20, y);
            doc.setFont("helvetica", "bold");
            doc.text(valueText, pageWidth - 20, y, { align: "right" });
            // Subtle solid line under each row
            doc.setDrawColor(255, 255, 255);
            doc.setLineWidth(0.1);
            doc.setGState(new doc.GState({opacity: 0.2}));
            doc.line(20, y + 2, pageWidth - 20, y + 2);
            doc.setGState(new doc.GState({opacity: 1}));
            doc.setLineWidth(0.5);  // reset line width
            y += 8;
        });
        
        // Totals
        y += 5;
        doc.setDrawColor(200);
        doc.line(20, y, pageWidth - 20, y);
        y += 10;
        
        // Total customs
        doc.setFillColor(226, 0, 26);
        doc.rect(15, y - 5, pageWidth - 30, 14, "F");
        doc.setTextColor(255, 255, 255);
        doc.setFont("helvetica", "bold");
        doc.setFontSize(14);
        doc.text(t.total, 20, y + 4);
        doc.text(formatNumber(parseFloat(values.total.mdl)) + " MDL  (" + formatNumber(parseFloat(values.total.eur)) + " EUR)", pageWidth - 20, y + 4, { align: "right" });
        y += 17;
        
        // Vehicle total
        doc.setFillColor(85, 85, 85);
        doc.rect(15, y - 5, pageWidth - 30, 14, "F");
        doc.setTextColor(255, 255, 255);
        doc.text(t.vehicle_total, 20, y + 4);
        doc.text(formatNumber(parseFloat(values.vehicle.mdl)) + " MDL  (" + formatNumber(parseFloat(values.vehicle.eur)) + " EUR)", pageWidth - 20, y + 4, { align: "right" });
        
        // Reset text color
        doc.setTextColor(0, 0, 0);
        
        // Footer info - left side (company info)
        // Center vertically in the 22% bottom space
        const bottomSpaceStart = pageHeight * 0.78;  // where background image ends
        const bottomSpaceHeight = pageHeight * 0.22;  // 22% of page
        const footerTextHeight = 18;  // height of footer text block (4 lines * ~4.5mm)
        const footerY = bottomSpaceStart + (bottomSpaceHeight - footerTextHeight) / 2 + 4;
        doc.setFontSize(12);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(50, 50, 50);
        doc.text("SAUTO SRL", 20, footerY);
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text("+373 68 68 99 95", 20, footerY + 6);
        doc.text("info@sauto.md", 20, footerY + 12);
        doc.text(removeDiacritics(t.address), 20, footerY + 18);
        
        // Footer info - right side (manager info)
        doc.setFontSize(12);
        doc.setFont("helvetica", "bold");
        doc.text("CARP DUMITRU", 90, footerY);
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text(removeDiacritics(t.sales_manager), 90, footerY + 6);
        doc.text("+373 62166880", 90, footerY + 12);
        doc.text("carp@sauto.md", 90, footerY + 18);
        
        const rectWidth = 50;
        const rectHeight = 60;
        const rectX = pageWidth - rectWidth - (pageHeight * 0.03);  // 3% margin from right
        const rectY = pageHeight - rectHeight;  // at bottom
        doc.setFillColor(226, 0, 26); 
        doc.rect(rectX, rectY, rectWidth, rectHeight, "F");
        
        // Save PDF
        doc.save("calculator_auto_" + new Date().toLocaleDateString("ro-RO").replace(/\./g, "-") + ".pdf");
        }
    });
    
    // Image upload handling - multi-select with drag & drop reordering
    let offerImages = []; // Array of {file: File, dataUrl: string}
    const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_TYPES = ["image/jpeg", "image/png", "image/webp"];
    const IMAGES_REQUIRED_TEXT = "'.$t['images_required'].'";
    const IMAGE_TOO_LARGE_TEXT = "'.$t['image_too_large'].'";
    const INVALID_IMAGE_TYPE_TEXT = "'.$t['invalid_image_type'].'";
    
    const imagesInput = document.getElementById("offer-images-input");
    const uploadBtn = document.getElementById("upload-images-btn");
    const imagesGrid = document.getElementById("offer-images-grid");
    
    function updateImagesCounter() {
        const count = offerImages.length;
        const counter = document.getElementById("images-counter");
        counter.textContent = count + " / 6";
        counter.classList.remove("complete", "incomplete");
        if (count === 6) {
            counter.classList.add("complete");
        } else if (count > 0) {
            counter.classList.add("incomplete");
        }
    }
    
    function renderImages() {
        imagesGrid.innerHTML = "";
        offerImages.forEach((imgData, index) => {
            const item = document.createElement("div");
            item.className = "image-item";
            item.draggable = true;
            item.dataset.index = index;
            
            // Number badge
            const numBadge = document.createElement("span");
            numBadge.className = "image-number";
            numBadge.textContent = index + 1;
            item.appendChild(numBadge);
            
            // Preview image
            const img = document.createElement("img");
            img.className = "preview-image";
            img.src = imgData.dataUrl;
            item.appendChild(img);
            
            // Remove button
            const removeBtn = document.createElement("button");
            removeBtn.type = "button";
            removeBtn.className = "remove-image";
            removeBtn.innerHTML = "×";
            removeBtn.onclick = function(ev) {
                ev.stopPropagation();
                removeImage(index);
            };
            item.appendChild(removeBtn);
            
            // Drag events
            item.addEventListener("dragstart", handleDragStart);
            item.addEventListener("dragend", handleDragEnd);
            item.addEventListener("dragover", handleDragOver);
            item.addEventListener("drop", handleDrop);
            item.addEventListener("dragleave", handleDragLeave);
            
            imagesGrid.appendChild(item);
        });
        updateImagesCounter();
    }
    
    let draggedIndex = null;
    
    function handleDragStart(e) {
        draggedIndex = parseInt(this.dataset.index);
        this.classList.add("dragging");
        e.dataTransfer.effectAllowed = "move";
    }
    
    function handleDragEnd(e) {
        this.classList.remove("dragging");
        document.querySelectorAll(".image-item").forEach(item => {
            item.classList.remove("drag-over");
        });
    }
    
    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = "move";
        this.classList.add("drag-over");
    }
    
    function handleDragLeave(e) {
        this.classList.remove("drag-over");
    }
    
    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove("drag-over");
        const targetIndex = parseInt(this.dataset.index);
        
        if (draggedIndex !== null && draggedIndex !== targetIndex) {
            // Swap images
            const temp = offerImages[draggedIndex];
            offerImages[draggedIndex] = offerImages[targetIndex];
            offerImages[targetIndex] = temp;
            renderImages();
        }
        draggedIndex = null;
    }
    
    function removeImage(index) {
        offerImages.splice(index, 1);
        renderImages();
    }
    
    uploadBtn.addEventListener("click", function() {
        imagesInput.click();
    });
    
    imagesInput.addEventListener("change", function() {
        const files = Array.from(this.files);
        
        // Validate and add files
        let validFiles = [];
        for (const file of files) {
            if (!ALLOWED_TYPES.includes(file.type)) {
                alert(INVALID_IMAGE_TYPE_TEXT + ": " + file.name);
                continue;
            }
            if (file.size > MAX_IMAGE_SIZE) {
                alert(IMAGE_TOO_LARGE_TEXT + ": " + file.name);
                continue;
            }
            validFiles.push(file);
        }
        
        // Limit to 6 total
        const slotsAvailable = 6 - offerImages.length;
        if (validFiles.length > slotsAvailable) {
            validFiles = validFiles.slice(0, slotsAvailable);
        }
        
        // Read files and add to array
        let loadedCount = 0;
        validFiles.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                offerImages.push({
                    file: file,
                    dataUrl: e.target.result
                });
                loadedCount++;
                if (loadedCount === validFiles.length) {
                    renderImages();
                }
            };
            reader.readAsDataURL(file);
        });
        
        // Reset input
        this.value = "";
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
        
        console.log(\'Saving offer - brand value:\', brand, \'brand text:\', brandName);
        console.log(\'Saving offer - model value:\', model, \'model text:\', modelName);
        
        const year = document.getElementById("offer-year").value;
        const bodywork = document.getElementById("offer-bodywork").value.trim();
        const seats = document.getElementById("offer-seats").value;
        const mileage = document.getElementById("offer-mileage").value;
        const enginePower = document.getElementById("offer-engine-power").value;
        const transmission = document.getElementById("offer-transmission").value.trim();
        const driveType = document.getElementById("offer-drive-type").value.trim();
        const color = document.getElementById("offer-color").value.trim();
        
        // Validate ALL fields with visual feedback
        let hasError = false;
        
        const fields = {
            clientName: { el: document.getElementById("offer-client-name"), val: clientName },
            brand: { el: document.getElementById("offer-brand"), val: brand },
            model: { el: document.getElementById("offer-model"), val: model },
            year: { el: document.getElementById("offer-year"), val: year },
            bodywork: { el: document.getElementById("offer-bodywork"), val: bodywork },
            seats: { el: document.getElementById("offer-seats"), val: seats },
            mileage: { el: document.getElementById("offer-mileage"), val: mileage },
            enginePower: { el: document.getElementById("offer-engine-power"), val: enginePower },
            transmission: { el: document.getElementById("offer-transmission"), val: transmission },
            driveType: { el: document.getElementById("offer-drive-type"), val: driveType },
            color: { el: document.getElementById("offer-color"), val: color }
        };
        
        // Remove previous error classes and check each field
        for (const key in fields) {
            fields[key].el.classList.remove("field-error");
            if (!fields[key].val) {
                fields[key].el.classList.add("field-error");
                hasError = true;
            }
        }
        
        // Validate images - exactly 6 required
        const imagesSection = document.getElementById("offer-images-section");
        imagesSection.classList.remove("field-error");
        
        if (offerImages.length !== 6) {
            hasError = true;
            imagesSection.classList.add("field-error");
            alert(IMAGES_REQUIRED_TEXT);
        }
        
        if (hasError) {
            // Scroll to first error field
            document.querySelector(".field-error")?.scrollIntoView({ behavior: "smooth", block: "center" });
            return;
        }
        
        btn.disabled = true;
        btn.textContent = "⏳ Se salvează...";
        
        // Get calculator input values
        const calculatorInputs = {
            vehicleType: document.querySelector(\'input[name="vehicle-type"]:checked\')?.value || "",
            productionYear: document.getElementById("year")?.value || "",
            cylinderCapacity: document.getElementById("capacity")?.value || "",
            vehiclePrice: document.getElementById("price_eur")?.value || "",
            transportPrice: document.getElementById("transport_eur")?.value || "",
            fuelType: document.querySelector(\'input[name="fuel-type"]:checked\')?.value || ""
        };
        
        // Get calculation result values
        const values = {
            inputs: calculatorInputs,
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
            polishing: { mdl: document.getElementById("res-polishing-mdl").value || "0", eur: document.getElementById("res-polishing-eur").value || "0" },
            painting: { mdl: document.getElementById("res-painting-mdl").value || "0", eur: document.getElementById("res-painting-eur").value || "0" },
            total: { mdl: document.getElementById("res-total-mdl").value || "0", eur: document.getElementById("res-total-eur").value || "0" },
            vehicle: { mdl: document.getElementById("res-vehicle-total-mdl").value || "0", eur: document.getElementById("res-vehicle-total-eur").value || "0" }
        };
        
        try {
            // Build FormData for file upload
            const formData = new FormData();
            formData.append("tp", "adm");
            formData.append("pg", "calculator");
            formData.append("fn", "save_offer");
            formData.append("client_name", clientName);
            formData.append("brand", brand);
            formData.append("model", model);
            formData.append("year", year);
            formData.append("bodywork", bodywork);
            formData.append("seats", seats);
            formData.append("mileage", mileage);
            formData.append("engine_power", enginePower);
            formData.append("transmission", transmission);
            formData.append("drive_type", driveType);
            formData.append("color", color);
            formData.append("pdf_lang", lang);
            formData.append("calculation_data", JSON.stringify(values));
            
            // Append images (in order)
            offerImages.forEach((imgData, i) => {
                formData.append("images[]", imgData.file, "image_" + (i + 1) + ".jpg");
            });
            
            // Save offer to database with images
            const saveResponse = await fetch("/ajax.php", {
                method: "POST",
                body: formData
            });
            
            const saveData = await saveResponse.json();
            
            if (!saveData.success) {
                throw new Error(saveData.error || "Failed to save offer");
            }
            
            btn.textContent = "✓ " + OFFER_SAVED_TEXT;
            btn.classList.add("success");
            
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
    
    // Flag to prevent brand change event when loading from Edit
    let isLoadingFromEdit = false;
    
    // Load models when brand changes
    document.getElementById("offer-brand").addEventListener("change", function() {
        // Skip if we\'re loading from Edit
        if (isLoadingFromEdit) {
            console.log(\'Skipping brand change event - loading from Edit\');
            return;
        }
        
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
    
    // Load offer data from sessionStorage if editing
    const editOfferData = sessionStorage.getItem("editOffer");
    if (editOfferData) {
        try {
            const offer = JSON.parse(editOfferData);
            
            console.log("Loading offer data:", offer);
            
            // Set flag to prevent brand change event
            isLoadingFromEdit = true;
            
            // Pre-populate commercial offer fields
            if (offer.client_name) {
                document.getElementById("offer-client-name").value = offer.client_name;
            }
            
            // Handle brand and model with proper async loading
            if (offer.brand) {
                const brandSelect = document.getElementById("offer-brand");
                const modelSelect = document.getElementById("offer-model");
                
                // Debug: log available brands
                console.log(\'Available brands in select:\', Array.from(brandSelect.options).map(o => o.value + \' = \' + o.text));
                console.log(\'Trying to set brand:\', offer.brand);
                
                // Set brand first - try by value, then by text
                brandSelect.value = offer.brand;
                
                // If brand not found by value, try to find by text
                if (!brandSelect.value && offer.brand) {
                    for (let i = 0; i < brandSelect.options.length; i++) {
                        if (brandSelect.options[i].text === offer.brand || brandSelect.options[i].value === offer.brand.toLowerCase().replace(/\s+/g, \'_\')) {
                            brandSelect.selectedIndex = i;
                            console.log(\'Brand found by text search:\', brandSelect.options[i].value, \'=\', brandSelect.options[i].text);
                            break;
                        }
                    }
                }
                
                console.log(\'Set brand:\', offer.brand, \'Selected text:\', brandSelect.options[brandSelect.selectedIndex]?.text);
                console.log(\'Brand select value after setting:\', brandSelect.value);
                
                // Trigger brand change to load models (cars.js will handle this)
                if (offer.model && brandSelect.value) {
                    console.log(\'Triggering brand change to load models for:\', brandSelect.value);
                    
                    // Temporarily enable brand change event
                    isLoadingFromEdit = false;
                    
                    // Trigger change event to load models via cars.js
                    brandSelect.dispatchEvent(new Event(\'change\', { bubbles: true }));
                    
                    // Wait for models to load, then set the model
                    const checkModelsLoaded = setInterval(() => {
                        if (modelSelect.options.length > 1) {
                            clearInterval(checkModelsLoaded);
                            
                            console.log(\'Models loaded, setting model:\', offer.model);
                            console.log(\'Available models:\', Array.from(modelSelect.options).map(o => o.value + \' = \' + o.text));
                            
                            // Try to set model by value first
                            modelSelect.value = offer.model;
                            
                            // If not found, try by text
                            if (!modelSelect.value && offer.model) {
                                console.log(\'Model not found by value, searching by text...\');
                                for (let i = 0; i < modelSelect.options.length; i++) {
                                    const optValue = modelSelect.options[i].value;
                                    const optText = modelSelect.options[i].text;
                                    
                                    if (optText === offer.model || 
                                        optValue === offer.model.toLowerCase() ||
                                        optText.toLowerCase() === offer.model.toLowerCase()) {
                                        modelSelect.selectedIndex = i;
                                        console.log(\'Model found by search:\', optValue, \'=\', optText);
                                        break;
                                    }
                                }
                            }
                            
                            console.log(\'Final model value:\', modelSelect.value, \'text:\', modelSelect.options[modelSelect.selectedIndex]?.text);
                            
                            // Re-disable brand change event
                            isLoadingFromEdit = true;
                        }
                    }, 100);
                    
                    // Timeout after 3 seconds
                    setTimeout(() => {
                        clearInterval(checkModelsLoaded);
                        isLoadingFromEdit = true;
                    }, 3000);
                }
            }
            
            if (offer.year) document.getElementById("offer-year").value = offer.year;
            if (offer.bodywork) document.getElementById("offer-bodywork").value = offer.bodywork;
            if (offer.seats) document.getElementById("offer-seats").value = offer.seats;
            if (offer.mileage) document.getElementById("offer-mileage").value = offer.mileage;
            if (offer.engine_power) document.getElementById("offer-engine-power").value = offer.engine_power;
            if (offer.transmission) document.getElementById("offer-transmission").value = offer.transmission;
            if (offer.drive_type) document.getElementById("offer-drive-type").value = offer.drive_type;
            if (offer.color) document.getElementById("offer-color").value = offer.color;
            
            // Pre-populate calculator results if calculation_data exists
            if (offer.calculation_data) {
                // Parse calculation_data if it is a string
                let calcData = offer.calculation_data;
                if (typeof calcData === "string") {
                    calcData = JSON.parse(calcData);
                }
                
                console.log("Loading calculation data (parsed):", calcData);
                
                // Load calculator input fields if they exist
                if (calcData.inputs) {
                    console.log(\'Loading calculator inputs:\', calcData.inputs);
                    
                    // Set vehicle type radio button
                    if (calcData.inputs.vehicleType) {
                        const vehicleTypeRadio = document.querySelector(\'input[name="vehicle-type"][value="\' + calcData.inputs.vehicleType + \'"]\');
                        if (vehicleTypeRadio) {
                            vehicleTypeRadio.checked = true;
                            console.log(\'Set vehicle type:\', calcData.inputs.vehicleType);
                        }
                    }
                    
                    // Set other input fields
                    if (calcData.inputs.productionYear && document.getElementById("year")) {
                        document.getElementById("year").value = calcData.inputs.productionYear;
                        console.log(\'Set year:\', calcData.inputs.productionYear);
                    }
                    if (calcData.inputs.cylinderCapacity && document.getElementById("capacity")) {
                        document.getElementById("capacity").value = calcData.inputs.cylinderCapacity;
                        console.log(\'Set capacity:\', calcData.inputs.cylinderCapacity);
                    }
                    if (calcData.inputs.vehiclePrice && document.getElementById("price_eur")) {
                        document.getElementById("price_eur").value = calcData.inputs.vehiclePrice;
                        console.log(\'Set price:\', calcData.inputs.vehiclePrice);
                    }
                    if (calcData.inputs.transportPrice && document.getElementById("transport_eur")) {
                        document.getElementById("transport_eur").value = calcData.inputs.transportPrice;
                        console.log(\'Set transport:\', calcData.inputs.transportPrice);
                    }
                    
                    // Set fuel type radio button
                    if (calcData.inputs.fuelType) {
                        const fuelTypeRadio = document.querySelector(\'input[name="fuel-type"][value="\' + calcData.inputs.fuelType + \'"]\');
                        if (fuelTypeRadio) {
                            fuelTypeRadio.checked = true;
                            console.log(\'Set fuel type:\', calcData.inputs.fuelType);
                        }
                    }
                }
                
                // Set all result values
                if (calcData.value) {
                    document.getElementById("res-value-mdl").value = calcData.value.mdl || 0;
                    document.getElementById("res-value-eur").value = calcData.value.eur || 0;
                }
                if (calcData.excise) {
                    document.getElementById("res-excise-mdl").value = calcData.excise.mdl || 0;
                    document.getElementById("res-excise-eur").value = calcData.excise.eur || 0;
                }
                if (calcData.luxury) {
                    document.getElementById("res-luxury-mdl").value = calcData.luxury.mdl || 0;
                    document.getElementById("res-luxury-eur").value = calcData.luxury.eur || 0;
                }
                if (calcData.customs) {
                    document.getElementById("res-customs-mdl").value = calcData.customs.mdl || 0;
                    document.getElementById("res-customs-eur").value = calcData.customs.eur || 0;
                }
                if (calcData.damage) {
                    document.getElementById("res-damage-mdl").value = calcData.damage.mdl || 0;
                    document.getElementById("res-damage-eur").value = calcData.damage.eur || 0;
                }
                if (calcData.exportDecl) {
                    document.getElementById("res-export-mdl").value = calcData.exportDecl.mdl || 0;
                    document.getElementById("res-export-eur").value = calcData.exportDecl.eur || 0;
                }
                if (calcData.bank) {
                    document.getElementById("res-bank-mdl").value = calcData.bank.mdl || 0;
                    document.getElementById("res-bank-eur").value = calcData.bank.eur || 0;
                }
                if (calcData.auction) {
                    document.getElementById("res-auction-mdl").value = calcData.auction.mdl || 0;
                    document.getElementById("res-auction-eur").value = calcData.auction.eur || 0;
                }
                if (calcData.pollution) {
                    document.getElementById("res-pollution-mdl").value = calcData.pollution.mdl || 0;
                    document.getElementById("res-pollution-eur").value = calcData.pollution.eur || 0;
                }
                if (calcData.shipping) {
                    document.getElementById("res-shipping-mdl").value = calcData.shipping.mdl || 0;
                    document.getElementById("res-shipping-eur").value = calcData.shipping.eur || 0;
                }
                if (calcData.accessories) {
                    document.getElementById("res-accessories-mdl").value = calcData.accessories.mdl || 0;
                    document.getElementById("res-accessories-eur").value = calcData.accessories.eur || 0;
                }
                if (calcData.transaction) {
                    document.getElementById("res-transaction-mdl").value = calcData.transaction.mdl || 0;
                    document.getElementById("res-transaction-eur").value = calcData.transaction.eur || 0;
                }
                if (calcData.polishing) {
                    const polishingCheckbox = document.getElementById("enable-polishing");
                    if (calcData.polishing.mdl > 0) {
                        polishingCheckbox.checked = true;
                        document.getElementById("res-polishing-mdl").disabled = false;
                        document.getElementById("res-polishing-eur").disabled = false;
                        document.getElementById("res-polishing-mdl").value = calcData.polishing.mdl || 0;
                        document.getElementById("res-polishing-eur").value = calcData.polishing.eur || 0;
                    }
                }
                if (calcData.painting) {
                    const paintingCheckbox = document.getElementById("enable-painting");
                    if (calcData.painting.mdl > 0) {
                        paintingCheckbox.checked = true;
                        document.getElementById("res-painting-mdl").disabled = false;
                        document.getElementById("res-painting-eur").disabled = false;
                        document.getElementById("res-painting-mdl").value = calcData.painting.mdl || 0;
                        document.getElementById("res-painting-eur").value = calcData.painting.eur || 0;
                    }
                }
                if (calcData.total) {
                    document.getElementById("res-total-mdl").value = calcData.total.mdl || 0;
                    document.getElementById("res-total-eur").value = calcData.total.eur || 0;
                }
                if (calcData.vehicle) {
                    document.getElementById("res-vehicle-total-mdl").value = calcData.vehicle.mdl || 0;
                    document.getElementById("res-vehicle-total-eur").value = calcData.vehicle.eur || 0;
                }
            }
            
            // Clear sessionStorage after loading
            sessionStorage.removeItem("editOffer");
            
            // Show results section and scroll to calculator
            setTimeout(() => {
                const resultsSection = document.getElementById("results");
                if (resultsSection) {
                    resultsSection.style.display = "block";
                }
                
                // Scroll to calculator section
                const calculator = document.querySelector(".calculator");
                if (calculator) {
                    calculator.scrollIntoView({ behavior: "smooth", block: "start" });
                } else {
                    // Fallback to results section
                    resultsSection?.scrollIntoView({ behavior: "smooth", block: "start" });
                }
                
                // Reset flag after everything is loaded
                isLoadingFromEdit = false;
                console.log(\'Finished loading from Edit - brand change event re-enabled\');
            }, 500);
            
        } catch (e) {
            console.error("Error loading offer data:", e);
            sessionStorage.removeItem("editOffer");
            isLoadingFromEdit = false;
        }
    }
})();
</script>';

echo $rtrn;
