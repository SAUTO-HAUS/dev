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

// Default prompt template (ONLY the editable part - Car data, HTML Structure and JSON format are added automatically in code)
$defaultPrompt = 'You are an expert automotive journalist and marketing copywriter. Generate DETAILED, ATTRACTIVE and PERSUASIVE HTML descriptions for this car in 3 languages: Romanian, Russian, and English.

YOUR GOAL: Write compelling text that will ATTRACT BUYERS and make them want to purchase or order this car. The text must be clear, beautiful, professional and sales-oriented.';

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
    .ai-settings .locked-section {background:#fff3cd; border:2px dashed #ffc107; padding:1rem; border-radius:.5rem; margin-bottom:.5rem; font-family:monospace; font-size:.8rem; white-space:pre-wrap; color:#856404;}
    .ai-settings .locked-label {background:#ffc107; color:#000; padding:.2rem .5rem; border-radius:.3rem; font-size:.75rem; font-weight:bold; display:inline-block; margin-bottom:.5rem;}
</style>

<div class="ai-settings">
    <h2>🤖 Настройки AI для описаний автомобилей</h2>
    
    '.(!empty($saved) ? '<div class="success-msg">✅ Настройки сохранены успешно!</div>' : '').'
    
    <form method="POST">
        <div class="form-group">
            <label>Текст для авто ПОД ЗАКАЗ:</label>
            <textarea name="car_type_order">'.htmlspecialchars($currentCarTypeOrder).'</textarea>
        </div>
        
        <div class="form-group">
            <label>Текст для авто В НАЛИЧИИ:</label>
            <textarea name="car_type_stock">'.htmlspecialchars($currentCarTypeStock).'</textarea>
        </div>
        
        <div class="form-group">
            <label>Промпт для анализа фотографий:</label>
            <textarea name="image_prompt">'.htmlspecialchars($currentImagePrompt).'</textarea>
        </div>
        
        <div class="form-group">
            <label>Основной промпт AI:</label>
            <div class="locked-label">🔒 ФИКСИРОВАННЫЕ ЧАСТИ (добавляются автоматически в код)</div>
            <div class="locked-section"><strong>1. Car data:</strong> (данные авто подставляются автоматически)
- Brand, Model, Year, Body type, Mileage, Engine volume, Power, Fuel, Transmission, Drive, Color

<strong>2. HTML STRUCTURE:</strong>
1. &lt;h2&gt;{Brand} {Model} | {Engine} | {Fuel} | {Year}&lt;/h2&gt;
2. &lt;h3&gt;Dotări&lt;/h3&gt; - desc-icon-features
3. &lt;h3&gt;Caracteristici tehnice&lt;/h3&gt; - desc-icon-spec  
4. &lt;h3&gt;Detalii motor&lt;/h3&gt; - desc-icon-engine + desc-icon-oil
5. &lt;h3&gt;Detalii suspensie&lt;/h3&gt; - desc-icon-suspension
6. &lt;h3&gt;Detalii cutie de viteze&lt;/h3&gt; - desc-icon-gearbox

<strong>3. JSON FORMAT:</strong>
{"ro": "...", "ru": "...", "en": "..."}</div>
            <div class="hint" style="margin-top:.5rem;">⬇️ Редактируемая часть (инструкции для AI):</div>
            <textarea name="ai_prompt" class="large">'.htmlspecialchars($currentPrompt).'</textarea>
        </div>
        
        <button type="submit" name="save_ai_settings" class="save-btn">💾 Сохранить настройки</button>
    </form>
</div>';
