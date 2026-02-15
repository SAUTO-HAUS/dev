<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/calculator/calc_translate.php';

if (!isset($user_role) || $user_role !== 'gordon') {
    echo '<span class="err">Access denied</span>';
    return;
}

$benzina_rates = [];
$diesel_rates = [];
$motocicleta_rates = [];
try {
    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_calculator_excise_rates ORDER BY fuel_type, capacity_min, age_min');
    $pdo->execute();
    while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
        if ($row['fuel_type'] === 'benzina') {
            $benzina_rates[] = $row;
        } elseif ($row['fuel_type'] === 'diesel') {
            $diesel_rates[] = $row;
        } elseif ($row['fuel_type'] === 'motocicleta') {
            $motocicleta_rates[] = $row;
        }
    }
} catch (Exception $e) {
    
}

$settings = [];
try {
    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_calculator_settings');
    $pdo->execute();
    while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row;
    }
} catch (Exception $e) {
   
}

$rtrn = '
<style>
    #rates-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        font-family: Arial, sans-serif;
    }
    
    #rates-container .rates-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    #rates-container .rates-header h1 {
        color: #333;
        font-size: 1.5rem;
        margin: 0;
    }
    
    #rates-container .back-btn {
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
    
    #rates-container .back-btn:hover {
        background: #555;
    }
    
    #rates-container .section {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
        overflow: hidden;
    }
    
    #rates-container .section-header {
        background: linear-gradient(135deg, #e2001a 0%, #bf0016 100%);
        color: #fff;
        padding: 1rem 1.5rem;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    #rates-container .section-header.diesel {
        background: linear-gradient(135deg, #333 0%, #555 100%);
    }
    
    #rates-container .section-header.motorcycle {
        background: linear-gradient(135deg, #ff6b00 0%, #cc5500 100%);
    }
    
    #rates-container .section-header.settings {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    }
    
    #rates-container .section-content {
        padding: 1.5rem;
    }
    
    #rates-container table {
        width: 100%;
        border-collapse: collapse;
    }
    
    #rates-container th, #rates-container td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }
    
    #rates-container th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
    }
    
    #rates-container input[type="number"],
    #rates-container input[type="text"] {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    
    #rates-container input:focus {
        outline: none;
        border-color: #e2001a;
    }
    
    #rates-container .save-btn {
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #e2001a 0%, #bf0016 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-top: 1rem;
    }
    
    #rates-container .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(226, 0, 26, 0.4);
    }
    
    #rates-container .save-btn.success {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    }
    
    #rates-container .save-btn.error {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }
    
    #rates-container .section-save {
        padding: 1rem 1.5rem;
        border-top: 1px solid #eee;
        text-align: right;
    }
    
    #rates-container .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    #rates-container .setting-item {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    #rates-container .setting-item label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
    }
    
    #rates-container .setting-item .desc {
        font-size: 0.75rem;
        color: #666;
        margin-top: 0.25rem;
    }
    
    #rates-container .success-msg {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: none;
    }
    
    #rates-container .success-msg.show {
        display: block;
    }
    
    @media (max-width:767px), (orientation: portrait), (max-height:500px) and (orientation: landscape) {
        #rates-container {
            padding: 0.5rem;
        }
        
        #rates-container .rates-header {
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
        }
        
        #rates-container .rates-header h1 {
            font-size: 1.1rem;
        }
        
        #rates-container .back-btn {
            width: 100%;
            text-align: center;
        }
        
        #rates-container .section {
            border-radius: 0;
            box-shadow: none;
            margin-bottom: 1rem;
        }
        
        #rates-container .section-header {
            padding: 0.75rem;
            font-size: 0.95rem;
        }
        
        #rates-container .section-content {
            padding: 0.5rem;
        }
        
        #rates-container .settings-grid {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        
        #rates-container .setting-item {
            padding: 0.75rem;
        }
        
        #rates-container table {
            display: block;
            overflow-x: auto;
        }
        
        #rates-container th, #rates-container td {
            padding: 0.4rem;
            font-size: 0.8rem;
        }
        
        #rates-container input[type="number"],
        #rates-container input[type="text"] {
            padding: 0.35rem;
            font-size: 0.8rem;
        }
        
        #rates-container .save-btn {
            width: 100%;
            padding: 0.75rem;
            font-size: 0.9rem;
        }
        
        #rates-container .section-save {
            padding: 0.75rem 0.5rem;
        }
    }
</style>

<div id="rates-container">
    <div class="rates-header">
        <h1>⚙️ '.$t['rates_title'].'</h1>
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/calc" class="back-btn">'.$t['back_to_calc'].'</a>
    </div>
    
    <div class="success-msg" id="success-msg">✓ '.$t['save_success'].'</div>
    
    <form id="rates-form">
        <!-- Settings Section -->
        <div class="section">
            <div class="section-header settings">'.$t['general_settings'].'</div>
            <div class="section-content">
                <div class="settings-grid">';

foreach ($settings as $key => $setting) {
    $label = isset($t['setting_'.$key]) ? $t['setting_'.$key] : str_replace('_', ' ', ucfirst($key));
    $desc = isset($t['setting_'.$key.'_desc']) ? $t['setting_'.$key.'_desc'] : $setting['description'];
    $rtrn .= '
                    <div class="setting-item">
                        <label>'.$label.'</label>
                        <input type="number" step="0.01" name="setting_'.$key.'" value="'.$setting['setting_value'].'">
                        <div class="desc">'.$desc.'</div>
                    </div>';
}

$rtrn .= '
                </div>
                <div class="section-save">
                    <button type="button" class="save-btn section-save-btn" data-section="settings" data-original="💾 '.$t['save_changes'].'" data-success="✓ '.$t['save_success'].'" data-error="✗ '.$t['save_error'].'">💾 '.$t['save_changes'].'</button>
                </div>
            </div>
        </div>
        
        <!-- Benzina Rates -->
        <div class="section">
            <div class="section-header">'.$t['gasoline_rates'].'</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>'.$t['capacity_min'].'</th>
                            <th>'.$t['capacity_max'].'</th>
                            <th>'.$t['age_min'].'</th>
                            <th>'.$t['age_max'].'</th>
                            <th>'.$t['rate'].'</th>
                        </tr>
                    </thead>
                    <tbody>';

foreach ($benzina_rates as $rate) {
    $rtrn .= '
                        <tr>
                            <td><input type="number" name="rate_'.$rate['id'].'_capacity_min" value="'.$rate['capacity_min'].'"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_capacity_max" value="'.$rate['capacity_max'].'" placeholder="0 = nelimitat"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_age_min" value="'.$rate['age_min'].'"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_age_max" value="'.$rate['age_max'].'" placeholder="0 = nelimitat"></td>
                            <td><input type="number" step="0.01" name="rate_'.$rate['id'].'_rate" value="'.$rate['rate'].'"></td>
                        </tr>';
}

$rtrn .= '
                    </tbody>
                </table>
                <div class="section-save">
                    <button type="button" class="save-btn section-save-btn" data-section="benzina" data-original="💾 '.$t['save_changes'].'" data-success="✓ '.$t['save_success'].'" data-error="✗ '.$t['save_error'].'">💾 '.$t['save_changes'].'</button>
                </div>
            </div>
        </div>
        
        <!-- Diesel Rates -->
        <div class="section">
            <div class="section-header diesel">'.$t['diesel_rates'].'</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>'.$t['capacity_min'].'</th>
                            <th>'.$t['capacity_max'].'</th>
                            <th>'.$t['age_min'].'</th>
                            <th>'.$t['age_max'].'</th>
                            <th>'.$t['rate'].'</th>
                        </tr>
                    </thead>
                    <tbody>';

foreach ($diesel_rates as $rate) {
    $rtrn .= '
                        <tr>
                            <td><input type="number" name="rate_'.$rate['id'].'_capacity_min" value="'.$rate['capacity_min'].'"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_capacity_max" value="'.$rate['capacity_max'].'" placeholder="0 = nelimitat"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_age_min" value="'.$rate['age_min'].'"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_age_max" value="'.$rate['age_max'].'" placeholder="0 = nelimitat"></td>
                            <td><input type="number" step="0.01" name="rate_'.$rate['id'].'_rate" value="'.$rate['rate'].'"></td>
                        </tr>';
}

$rtrn .= '
                    </tbody>
                </table>
                <div class="section-save">
                    <button type="button" class="save-btn section-save-btn" data-section="diesel" data-original="💾 '.$t['save_changes'].'" data-success="✓ '.$t['save_success'].'" data-error="✗ '.$t['save_error'].'">💾 '.$t['save_changes'].'</button>
                </div>
            </div>
        </div>
        
        <!-- Motocicleta Rates -->
        <div class="section">
            <div class="section-header motorcycle">'.$t['motorcycle_rates'].'</div>
            <div class="section-content">
                <table>
                    <thead>
                        <tr>
                            <th>'.$t['capacity_min'].'</th>
                            <th>'.$t['capacity_max'].'</th>
                            <th>'.$t['age_min'].'</th>
                            <th>'.$t['age_max'].'</th>
                            <th>'.$t['rate'].'</th>
                        </tr>
                    </thead>
                    <tbody>';

foreach ($motocicleta_rates as $rate) {
    $rtrn .= '
                        <tr>
                            <td><input type="number" name="rate_'.$rate['id'].'_capacity_min" value="'.$rate['capacity_min'].'"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_capacity_max" value="'.$rate['capacity_max'].'" placeholder="0 = nelimitat"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_age_min" value="'.$rate['age_min'].'"></td>
                            <td><input type="number" name="rate_'.$rate['id'].'_age_max" value="'.$rate['age_max'].'" placeholder="0 = nelimitat"></td>
                            <td><input type="number" step="0.01" name="rate_'.$rate['id'].'_rate" value="'.$rate['rate'].'"></td>
                        </tr>';
}

$rtrn .= '
                    </tbody>
                </table>
                <div class="section-save">
                    <button type="button" class="save-btn section-save-btn" data-section="motocicleta" data-original="💾 '.$t['save_changes'].'" data-success="✓ '.$t['save_success'].'" data-error="✗ '.$t['save_error'].'">💾 '.$t['save_changes'].'</button>
                </div>
            </div>
        </div>
        
    </form>
</div>

<script>
document.querySelectorAll(".section-save-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        const section = this.dataset.section;
        const originalText = this.dataset.original;
        const successText = this.dataset.success;
        const errorText = this.dataset.error;
        const button = this;
        
        button.disabled = true;
        
        const formData = new FormData(document.getElementById("rates-form"));
        formData.append("tp", "adm");
        formData.append("pg", "calculator");
        formData.append("fn", "save_rates");
        formData.append("section", section);
        
        fetch("/ajax.php", {
            method: "POST",
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.textContent = successText;
                button.classList.add("success");
            } else {
                button.textContent = errorText;
                button.classList.add("error");
            }
            button.disabled = false;
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove("success", "error");
            }, 2000);
        })
        .catch(() => {
            button.textContent = errorText;
            button.classList.add("error");
            button.disabled = false;
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove("success", "error");
            }, 2000);
        });
    });
});
</script>';

echo $rtrn;
