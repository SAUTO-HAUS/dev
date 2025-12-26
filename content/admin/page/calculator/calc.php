<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/calculator/calc_translate.php';

$eur_rate = 19.50; 
try {
    $pdo = $db->prepare('SELECT `value` FROM '.$prefx.'_exchange WHERE `name`="EUR"');
    $pdo->execute();
    $result = $pdo->fetch(PDO::FETCH_ASSOC);
    if ($result && !empty($result['value'])) {
        $eur_rate = floatval($result['value']);
    }
} catch (Exception $e) {
   
}

$settings = [];
try {
    $pdo = $db->prepare('SELECT `setting_key`, `setting_value` FROM '.$prefx.'_calculator_settings');
    $pdo->execute();
    while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
   
}

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
        padding: 2rem;
        font-family: Arial, sans-serif;
    }
    
    #calculator-container .calc-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    #calculator-container .calc-header h1 {
        color: #333;
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }
    
    #calculator-container .calc-header .eur-rate {
        color: #666;
        font-size: 0.9rem;
    }
    
    #calculator-container .calc-header .eur-rate span {
        color: #e2001a;
        font-weight: bold;
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
    
    #calculator-container .hybrid-options label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        cursor: pointer;
        font-weight: normal;
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
        margin: 1rem -2rem -2rem;
        padding: 1.5rem 2rem;
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
        <p class="eur-rate">'.$t['bnm_rate'].': <span>1 EUR = '.number_format($eur_rate, 4, '.', ' ').' MDL</span></p>
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
        
        <div class="form-group">
            <label>'.$t['fuel_type'].'</label>
            <div class="fuel-types">
                <div class="fuel-type-btn active" data-fuel="benzina">'.$t['gasoline'].'</div>
                <div class="fuel-type-btn" data-fuel="diesel">'.$t['diesel'].'</div>
                <div class="fuel-type-btn" data-fuel="hybrid">'.$t['hybrid'].'</div>
                <div class="fuel-type-btn electric" data-fuel="electric">'.$t['electric'].'</div>
            </div>
            
            <div class="hybrid-options" id="hybrid-options">
                <label><input type="radio" name="hybrid_type" value="plugin" checked> '.$t['plugin_hybrid'].' (-'.$hybrid_discount_plugin.'%)</label>
                <label><input type="radio" name="hybrid_type" value="full"> '.$t['full_hybrid'].' (-'.$hybrid_discount_full.'%)</label>
                <label><input type="radio" name="hybrid_type" value="mild"> '.$t['mild_hybrid'].'</label>
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
        <div class="result-row">
            <span class="label">'.$t['tva'].' ('.$tva_rate.'%)</span>
            <span class="value" id="res-tva">-</span>
        </div>
        <div class="result-row">
            <span class="label">'.$t['customs_duty'].'</span>
            <span class="value" id="res-customs">-</span>
        </div>
        <div class="result-row total">
            <span class="label">'.$t['total'].'</span>
            <span class="value" id="res-total">-</span>
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
    const EUR_RATE = '.$eur_rate.';
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
    
    // Calculate button
    document.getElementById("calculate-btn").addEventListener("click", function() {
        const vehicleType = document.getElementById("vehicle_type").value;
        const year = parseInt(document.getElementById("year").value);
        const capacity = parseInt(document.getElementById("capacity").value) || 0;
        const priceEur = parseFloat(document.getElementById("price_eur").value) || 0;
        
        const activeFuel = document.querySelector(".fuel-type-btn.active");
        let fuelType = activeFuel ? activeFuel.dataset.fuel : "benzina";
        
        // Calculate vehicle age
        const currentYear = new Date().getFullYear();
        const age = currentYear - year;
        
        // Calculate value in MDL
        const valueMdl = priceEur * EUR_RATE;
        
        // Calculate excise
        let excise = 0;
        
        if (fuelType === "electric") {
            excise = 0;
        } else {
            // Find matching rate
            let baseFuel = fuelType === "hybrid" ? "benzina" : fuelType;
            
            // For motorcycles, use motorcycle rates
            if (vehicleType === "motocicleta") {
                const rate = findExciseRate("motocicleta", capacity, age);
                excise = rate * capacity;
            } else if (baseFuel === "diesel" || baseFuel === "benzina") {
                const rate = findExciseRate(baseFuel, capacity, age);
                excise = rate * capacity;
                
                // Apply hybrid discount
                if (fuelType === "hybrid") {
                    const hybridType = document.querySelector("input[name=\"hybrid_type\"]:checked").value;
                    if (hybridType === "plugin") {
                        excise = excise * (1 - HYBRID_DISCOUNT_PLUGIN / 100);
                    } else if (hybridType === "full") {
                        excise = excise * (1 - HYBRID_DISCOUNT_FULL / 100);
                    }
                }
            }
        }
        
        // Calculate TVA (on value + excise)
        const tvaBase = valueMdl + excise;
        const tva = tvaBase * (TVA_RATE / 100);
        
        // Customs duty (usually 0 for EU cars)
        const customsDuty = 0;
        
        // Total
        const total = excise + tva + customsDuty;
        
        // Display results with EUR equivalent
        document.getElementById("res-value-mdl").innerHTML = formatNumber(valueMdl) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(valueMdl / EUR_RATE) + " EUR</span>";
        document.getElementById("res-excise").innerHTML = formatNumber(excise) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(excise / EUR_RATE) + " EUR</span>";
        document.getElementById("res-tva").innerHTML = formatNumber(tva) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(tva / EUR_RATE) + " EUR</span>";
        document.getElementById("res-customs").innerHTML = formatNumber(customsDuty) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(customsDuty / EUR_RATE) + " EUR</span>";
        document.getElementById("res-total").innerHTML = formatNumber(total) + " MDL <span class=\"eur-equiv\">~ " + formatNumber(total / EUR_RATE) + " EUR</span>";
        
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
            const ageMatch = age >= ageMin && (ageMax === 0 || age < ageMax);
            
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
