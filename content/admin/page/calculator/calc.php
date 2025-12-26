<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/calculator/calc_translate.php';

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
        gap: 0.5rem;
        cursor: pointer;
        font-weight: normal;
    }
    
    #calculator-container .hybrid-fuel-select input[type="radio"] {
        margin: 0;
        vertical-align: middle;
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
        color: rgba(255,255,255,0.7);
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
    }
    
    #calculator-container .admin-btn:hover {
        background: #555;
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
                <div class="fuel-type-btn active" data-fuel="benzina">'.$t['gasoline'].'</div>
                <div class="fuel-type-btn" data-fuel="diesel">'.$t['diesel'].'</div>
                <div class="fuel-type-btn" data-fuel="hybrid">'.$t['hybrid'].'</div>
                <div class="fuel-type-btn electric" data-fuel="electric">'.$t['electric'].'</div>
            </div>
            
            <div class="hybrid-options" id="hybrid-options">
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
            <span class="value" id="res-value-mdl">-</span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['excise'].'</span>
            <span class="value" id="res-excise">-</span>
        </div>
        <div class="result-row" id="res-luxury-row" style="display:none;">
            <span class="label">'.$t['luxury_excise'].'</span>
            <span class="value" id="res-luxury">-</span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['customs_duty'].'</span>
            <span class="value" id="res-customs">-</span>
        </div>
        <div class="result-row total">
            <span class="label">'.$t['total'].'</span>
            <span class="value" id="res-total">-</span>
        </div>
        <div class="result-row total vehicle-total">
            <span class="label">'.$t['vehicle_total'].'</span>
            <span class="value" id="res-vehicle-total">-</span>
        </div>
    </div>
    
    '.($is_super_admin ? '
    <div class="admin-actions">
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/usage" class="admin-btn">📊 '.$t['usage_stats'].'</a>
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/rates" class="admin-btn">⚙️ '.$t['edit_rates'].'</a>
    </div>
    ' : '').'
</div>

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
        
        // Luxury excise (for vehicles over 600,000 MDL)
        let luxuryExcise = 0;
        const luxuryRow = document.getElementById("res-luxury-row");
        if (valueMdl > 1200000) {
            luxuryExcise = valueMdl * 0.03; // 3%
        } else if (valueMdl > 900000) {
            luxuryExcise = valueMdl * 0.02; // 2%
        } else if (valueMdl > 600000) {
            luxuryExcise = valueMdl * 0.01; // 1%
        }
        
        // Customs procedures fee (0.4% of customs value, max 1800 EUR)
        const maxFeeEur = 1800;
        let customsFee = valueMdl * 0.004;
        if (customsFee > maxFeeEur * EUR_RATE) {
            customsFee = maxFeeEur * EUR_RATE;
        }
        
        // Total (excise + luxury excise + customs fee)
        const total = excise + luxuryExcise + customsFee;
        
        // Display results with EUR equivalent
        document.getElementById("res-value-mdl").innerHTML = formatNumber(valueMdl) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(valueMdl / EUR_RATE) + " EUR</span>";
        document.getElementById("res-excise").innerHTML = formatNumber(excise) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(excise / EUR_RATE) + " EUR</span>";
        
        // Show/hide luxury excise row
        if (luxuryExcise > 0) {
            luxuryRow.style.display = "flex";
            document.getElementById("res-luxury").innerHTML = formatNumber(luxuryExcise) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(luxuryExcise / EUR_RATE) + " EUR</span>";
        } else {
            luxuryRow.style.display = "none";
        }
        
        document.getElementById("res-customs").innerHTML = formatNumber(customsFee) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(customsFee / EUR_RATE) + " EUR</span>";
        document.getElementById("res-total").innerHTML = formatNumber(total) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(total / EUR_RATE) + " EUR</span>";
        
        // Vehicle total (price + customs costs)
        const vehicleTotal = valueMdl + total;
        document.getElementById("res-vehicle-total").innerHTML = formatNumber(vehicleTotal) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(vehicleTotal / EUR_RATE) + " EUR</span>";
        
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
        return num.toLocaleString("ro-MD", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    
    function logUsage() {
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=log_usage"
        });
    }
})();
</script>';

echo $rtrn;
