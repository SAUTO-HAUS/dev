<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Only Gordon (superadmin) can access this page
$sess = explode("-", $_COOKIE['sess']);
$pdo = $db->prepare('SELECT role, type FROM '.$prefx.'_adm_usr WHERE id = :id AND act = "1"');
$pdo->execute(['id' => $sess[0]]);
$user_data = $pdo->fetch(PDO::FETCH_ASSOC);
$current_user_role = $user_data['role'] ?? $user_data['type'];

if ($current_user_role !== 'gordon') {
    $rtrn = '<div style="padding:2rem;color:#c00;">Доступ запрещен. Только для суперадмина.</div>';
    return;
}

// Create table if not exists
$db->exec("CREATE TABLE IF NOT EXISTS {$prefx}_ai_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Default prompt template
$defaultPrompt = 'You are an expert automotive journalist and marketing copywriter. Generate DETAILED, ATTRACTIVE and PERSUASIVE HTML descriptions for this car in 3 languages: Romanian, Russian, and English.

YOUR GOAL: Write compelling text that will ATTRACT BUYERS and make them want to purchase or order this car. The text must be clear, beautiful, professional and sales-oriented.

IMPORTANT: {$carTypeText}

Car data:
- Brand: {$car[\'br_nm\']}
- Model: {$car[\'mo_nm\']}
- Year: {$car[\'yr\']}
- Body type: {$car[\'bt\']}
- Mileage: {$car[\'mlg\']} km
- Engine volume: {$car[\'vol\']} cm³
- Power: {$car[\'hp\']} HP
- Fuel: {$car[\'fl\']}
- Transmission: {$car[\'tra\']}
- Drive: {$car[\'wd\']}
- Color: {$car[\'clr\']}

HTML STRUCTURE (MUST follow this EXACT order):
1. <h2>{Brand} {Model} | {Engine} | {Fuel} | {Year}</h2> - USE PIPE SEPARATOR between brand/model, engine, fuel type and year!
2. <h3><span class="desc-icon desc-icon-features"></span>Dotări</h3> then <ul> with 5-8 <li> items.
3. <h3><span class="desc-icon desc-icon-spec"></span>Caracteristici tehnice</h3> then <ul> with detailed specs: engine type, power with kW and rpm, torque Nm, fuel system, real consumption l/100km, drivetrain (DO NOT include gearbox type here - it goes in section 5!)
4. <h3><span class="desc-icon desc-icon-engine"></span>Detalii motor</h3> then <p><strong>Caracteristici constructive:</strong></p><ul> engine block material, cylinder head, turbosuflantă (da/nu), timing drive type (ONLY write \'curea\' or \'lanț\' - choose correct one for THIS engine!), emission standard, special features </ul> then <p><strong><span class="desc-icon desc-icon-oil"></span>Mentenanță:</strong></p><ul> service interval ALWAYS 7000 km, oil specification (viscosity + ACEA class - choose correct for THIS engine!), oil capacity in litri, injection system notes </ul> - NEVER mention engine lifespan or km durability!
5. <h3><span class="desc-icon desc-icon-suspension"></span>Detalii suspensie</h3> then <ul> with: front suspension type (McPherson/double wishbone/multi-link), rear suspension type (torsion beam/multi-link/independent), stabilizer bars (front/rear), shock absorbers type, any special features (adaptive suspension, air suspension if applicable for this model)
6. <h3><span class="desc-icon desc-icon-gearbox"></span>Detalii cutie de viteze</h3> then <ul> - USE EXACTLY the transmission type from Car data above (Manuală/Automată/Robotizată)! gearbox type, clutch type, oil type and specification (IMPORTANT: for BMW write \'ZF Lifeguard 6\', for Mercedes write \'MB 236.14\', for VW/Audi/Skoda write \'G052182\' - NEVER write \'Dexron\' for these brands!), oil capacity as RANGE (X-X litri), gearbox service interval (70000-80000 km)

IMPORTANT: Return EXACTLY in this JSON format:
{"ro": "<HTML in Romanian>", "ru": "<HTML in Russian>", "en": "<HTML in English>"}';

$defaultCarTypeOrder = 'This is a CAR TO ORDER (not in stock). The car will be imported from EU after the order is placed. Delivery time is typically 14-30 days. Focus on the model\'s features and what the buyer can expect.';
$defaultCarTypeStock = 'This is a CAR IN STOCK (available immediately). The car is already imported and ready for viewing/purchase.';
$defaultImagePrompt = 'I\'m showing you photos of this car. Analyze them to identify VISIBLE features like: wheel type (alloy/steel), headlight type (LED/xenon/halogen), interior material (leather/cloth), infotainment screen, sunroof, parking sensors, etc. Use ONLY what you can clearly see in the photos for the \'Dotări\' section.';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_settings'])) {
    $prompt = $_POST['ai_prompt'] ?? '';
    $carTypeOrder = $_POST['car_type_order'] ?? '';
    $carTypeStock = $_POST['car_type_stock'] ?? '';
    $imagePrompt = $_POST['image_prompt'] ?? '';
    
    // Save settings
    $stmt = $db->prepare("INSERT INTO {$prefx}_ai_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value2");
    
    $stmt->execute(['key' => 'ai_prompt', 'value' => $prompt, 'value2' => $prompt]);
    $stmt->execute(['key' => 'car_type_order', 'value' => $carTypeOrder, 'value2' => $carTypeOrder]);
    $stmt->execute(['key' => 'car_type_stock', 'value' => $carTypeStock, 'value2' => $carTypeStock]);
    $stmt->execute(['key' => 'image_prompt', 'value' => $imagePrompt, 'value2' => $imagePrompt]);
    
    $saved = true;
}

// Load current settings
$settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM {$prefx}_ai_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$currentPrompt = $settings['ai_prompt'] ?? $defaultPrompt;
$currentCarTypeOrder = $settings['car_type_order'] ?? $defaultCarTypeOrder;
$currentCarTypeStock = $settings['car_type_stock'] ?? $defaultCarTypeStock;
$currentImagePrompt = $settings['image_prompt'] ?? $defaultImagePrompt;

$rtrn = '
<style>
    .ai-settings {padding:1rem;}
    .ai-settings h2 {margin-bottom:1rem; color:#333;}
    .ai-settings .form-group {margin-bottom:1.5rem;}
    .ai-settings label {display:block; font-weight:bold; margin-bottom:.5rem; color:#555;}
    .ai-settings textarea {width:100%; min-height:200px; padding:.75rem; border:1px solid #ccc; border-radius:.5rem; font-family:monospace; font-size:.85rem; line-height:1.4; resize:vertical;}
    .ai-settings textarea.large {min-height:400px;}
    .ai-settings textarea:focus {border-color:var(--clr); outline:none; box-shadow:0 0 0 2px rgba(0,100,200,0.1);}
    .ai-settings .hint {font-size:.8rem; color:#888; margin-top:.3rem;}
    .ai-settings .save-btn {background:var(--clr); color:#fff; border:none; padding:.75rem 2rem; border-radius:.5rem; cursor:pointer; font-size:1rem; transition:all .2s;}
    .ai-settings .save-btn:hover {opacity:.9; transform:translateY(-1px);}
    .ai-settings .success-msg {background:#d4edda; color:#155724; padding:1rem; border-radius:.5rem; margin-bottom:1rem;}
    .ai-settings .reset-btn {background:#dc3545; color:#fff; border:none; padding:.5rem 1rem; border-radius:.3rem; cursor:pointer; font-size:.85rem; margin-left:1rem;}
    .ai-settings .reset-btn:hover {opacity:.9;}
    .ai-settings .variables {background:#f8f9fa; padding:1rem; border-radius:.5rem; margin-bottom:1rem; font-size:.85rem;}
    .ai-settings .variables code {background:#e9ecef; padding:.1rem .3rem; border-radius:.2rem;}
</style>

<div class="ai-settings">
    <h2>🤖 Настройки AI для описаний автомобилей</h2>
    
    '.(!empty($saved) ? '<div class="success-msg">✅ Настройки сохранены успешно!</div>' : '').'
    
    <div class="variables">
        <strong>Доступные переменные:</strong><br>
        <code>{$car[\'br_nm\']}</code> - Марка, 
        <code>{$car[\'mo_nm\']}</code> - Модель, 
        <code>{$car[\'yr\']}</code> - Год, 
        <code>{$car[\'bt\']}</code> - Тип кузова, 
        <code>{$car[\'mlg\']}</code> - Пробег, 
        <code>{$car[\'vol\']}</code> - Объем двигателя, 
        <code>{$car[\'hp\']}</code> - Мощность, 
        <code>{$car[\'fl\']}</code> - Топливо, 
        <code>{$car[\'tra\']}</code> - КПП, 
        <code>{$car[\'wd\']}</code> - Привод, 
        <code>{$car[\'clr\']}</code> - Цвет,
        <code>{$carTypeText}</code> - Текст типа авто (в наличии/под заказ)
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label>Текст для авто ПОД ЗАКАЗ:</label>
            <textarea name="car_type_order">'.htmlspecialchars($currentCarTypeOrder).'</textarea>
            <div class="hint">Этот текст будет использован когда авто под заказ (не в наличии)</div>
        </div>
        
        <div class="form-group">
            <label>Текст для авто В НАЛИЧИИ:</label>
            <textarea name="car_type_stock">'.htmlspecialchars($currentCarTypeStock).'</textarea>
            <div class="hint">Этот текст будет использован когда авто в наличии</div>
        </div>
        
        <div class="form-group">
            <label>Промпт для анализа фотографий:</label>
            <textarea name="image_prompt">'.htmlspecialchars($currentImagePrompt).'</textarea>
            <div class="hint">Инструкция для AI при анализе фотографий автомобиля</div>
        </div>
        
        <div class="form-group">
            <label>Основной промпт AI:</label>
            <textarea name="ai_prompt" class="large">'.htmlspecialchars($currentPrompt).'</textarea>
            <div class="hint">Основные инструкции для генерации описания. Используйте переменные выше.</div>
        </div>
        
        <button type="submit" name="save_ai_settings" class="save-btn">💾 Сохранить настройки</button>
    </form>
</div>';
