<?php defined( '_DOIT' ) or die( 'Restricted access' );

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
        <h1>Calculator Vămuire Auto</h1>
        <p class="eur-rate">Curs BNM: <span>1 EUR = '.number_format($eur_rate, 4, '.', ' ').' MDL</span></p>
    </div>
    
    <div class="calc-form">
        <div class="form-row">
            <div class="form-group">
                <label>Tipul vehiculului</label>
                <select id="vehicle_type">
                    <option value="autoturism">Autoturism</option>
                    <option value="camion">Camion</option>
                </select>
            </div>
            <div class="form-group">
                <label>Anul producerii</label>
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
                <label>Capacitatea cilindrică (cm³)</label>
                <input type="number" id="capacity" placeholder="ex: 1998" min="0" max="10000">
            </div>
            <div class="form-group">
                <label>Prețul vehiculului (EUR)</label>
                <input type="number" id="price_eur" placeholder="ex: 15000" min="0">
            </div>
        </div>
        
        <div class="form-group">
            <label>Tipul combustibilului</label>
            <div class="fuel-types">
                <div class="fuel-type-btn active" data-fuel="benzina">Benzină</div>
                <div class="fuel-type-btn" data-fuel="diesel">Diesel</div>
                <div class="fuel-type-btn" data-fuel="hybrid">Hybrid</div>
                <div class="fuel-type-btn electric" data-fuel="electric">Electric</div>
            </div>
            
            <div class="hybrid-options" id="hybrid-options">
                <label><input type="radio" name="hybrid_type" value="plugin" checked> Plug-in Hybrid (-'.$hybrid_discount_plugin.'%)</label>
                <label><input type="radio" name="hybrid_type" value="full"> Full Hybrid (-'.$hybrid_discount_full.'%)</label>
                <label><input type="radio" name="hybrid_type" value="mild"> Mild Hybrid (fără reducere)</label>
            </div>
            
            <div class="electric-notice" id="electric-notice">
                <strong>✓ Vehiculele electrice sunt scutite de accize!</strong><br>
                Se achită doar TVA la valoarea în vamă.
            </div>
        </div>
        
        <button class="calc-btn" id="calculate-btn">Calculează</button>
    </div>
    
    <div class="results" id="results">
        <h2>Rezultatul calculului</h2>
        <div class="result-row">
            <span class="label">Valoarea în vamă (MDL)</span>
            <span class="value" id="res-value-mdl">-</span>
        </div>
        <div class="result-row">
            <span class="label">Acciza</span>
            <span class="value" id="res-excise">-</span>
        </div>
        <div class="result-row">
            <span class="label">TVA ('.$tva_rate.'%)</span>
            <span class="value" id="res-tva">-</span>
        </div>
        <div class="result-row">
            <span class="label">Taxă vamală</span>
            <span class="value" id="res-customs">-</span>
        </div>
        <div class="result-row total">
            <span class="label">TOTAL COSTURI VĂMUIRE</span>
            <span class="value" id="res-total">-</span>
        </div>
    </div>
    
    '.($is_super_admin ? '
    <div class="admin-actions">
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/usage" class="admin-btn">📊 Statistici utilizare</a>
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/rates" class="admin-btn">⚙️ Editare cote accize</a>
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
            if (baseFuel === "diesel" || baseFuel === "benzina") {
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
        
        // Display results
        document.getElementById("res-value-mdl").textContent = formatNumber(valueMdl) + " MDL";
        document.getElementById("res-excise").textContent = formatNumber(excise) + " MDL";
        document.getElementById("res-tva").textContent = formatNumber(tva) + " MDL";
        document.getElementById("res-customs").textContent = formatNumber(customsDuty) + " MDL";
        document.getElementById("res-total").textContent = formatNumber(total) + " MDL";
        
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
