<?php defined('_DOIT') or die('Restricted access');

// Access global variables from admin context
global $lng, $db, $prefx;

// Include PublicationService
require_once __DIR__ . '/PublicationService.php';
use App\Services\PublicationService;

// Initialize PublicationService
$publicationService = new PublicationService($db, $prefx);

// Ensure language is loaded
if (!isset($lng)) {
    // Set default language if not set
    if (!isset($_COOKIE['lang'])) {
        $_COOKIE['lang'] = 'ro';
    }
    require(_DEFAULT.'/language.php');
}

// Publication settings translations
// Romanian
$lng['ro']['w']['publication_settings_title'] = 'Setări publicare automobile';
$lng['ro']['w']['publication_settings_saved'] = 'Setările de publicare au fost salvate!';
$lng['ro']['w']['cars_in_stock'] = 'Automobile în stoc';
$lng['ro']['w']['cars_on_order'] = 'Automobile sub comandă';
$lng['ro']['w']['api_999md_main'] = 'API "Три Девятки МД" - Cont principal';
$lng['ro']['w']['api_999md_separate'] = 'API "Три Девятки МД" - Cont separat';
$lng['ro']['w']['account_login'] = 'Cont/Login:';
$lng['ro']['w']['api_token'] = 'Token API:';
$lng['ro']['w']['telegram_channel_1'] = 'Telegram - Canalul №1';
$lng['ro']['w']['telegram_separate'] = 'Telegram - Canal separat';
$lng['ro']['w']['bot_token'] = 'Bot Token:';
$lng['ro']['w']['chat_id'] = 'Chat ID:';
$lng['ro']['w']['facebook_main'] = 'Facebook - Pagina principală';
$lng['ro']['w']['facebook_same'] = 'Facebook - Aceeași pagină cu eticheta "Под заказ"';
$lng['ro']['w']['page_id'] = 'Page ID:';
$lng['ro']['w']['page_token'] = 'Page Token:';
$lng['ro']['w']['auto_publish_stock'] = 'Publicare automată la adăugarea automobilului în stoc';
$lng['ro']['w']['auto_publish_order'] = 'Publicare automată la adăugarea automobilului sub comandă';
$lng['ro']['w']['save_settings'] = 'Salvează setările';
$lng['ro']['w']['separate_account'] = 'Cont separat pentru comenzi';
$lng['ro']['w']['same_page_id'] = 'ID pagină Facebook pentru comenzi';

// Russian
$lng['ru']['w']['publication_settings_title'] = 'Настройки публикации автомобилей';
$lng['ru']['w']['publication_settings_saved'] = 'Настройки публикации сохранены!';
$lng['ru']['w']['cars_in_stock'] = 'Автомобили в наличии';
$lng['ru']['w']['cars_on_order'] = 'Автомобили под заказ';
$lng['ru']['w']['api_999md_main'] = 'API "Три Девятки МД" - Основной аккаунт';
$lng['ru']['w']['api_999md_separate'] = 'API "Три Девятки МД" - Отдельный аккаунт';
$lng['ru']['w']['account_login'] = 'Аккаунт/Login:';
$lng['ru']['w']['api_token'] = 'API Token:';
$lng['ru']['w']['telegram_channel_1'] = 'Telegram - Канал №1';
$lng['ru']['w']['telegram_separate'] = 'Telegram - Отдельный канал';
$lng['ru']['w']['bot_token'] = 'Bot Token:';
$lng['ru']['w']['chat_id'] = 'Chat ID:';
$lng['ru']['w']['facebook_main'] = 'Facebook - Основная страница';
$lng['ru']['w']['facebook_same'] = 'Facebook - Та же страница с меткой "Под заказ"';
$lng['ru']['w']['page_id'] = 'Page ID:';
$lng['ru']['w']['page_token'] = 'Page Token:';
$lng['ru']['w']['auto_publish_stock'] = 'Автоматическая публикация при добавлении автомобиля в наличии';
$lng['ru']['w']['auto_publish_order'] = 'Автоматическая публикация при добавлении автомобиля под заказ';
$lng['ru']['w']['save_settings'] = 'Сохранить настройки';
$lng['ru']['w']['separate_account'] = 'Отдельный аккаунт для заказов';
$lng['ru']['w']['same_page_id'] = 'ID Facebook страницы для заказов';

// English
$lng['en']['w']['publication_settings_title'] = 'Car Publication Settings';
$lng['en']['w']['publication_settings_saved'] = 'Publication settings saved!';
$lng['en']['w']['cars_in_stock'] = 'Cars in Stock';
$lng['en']['w']['cars_on_order'] = 'Cars on Order';
$lng['en']['w']['api_999md_main'] = 'API "Три Девятки МД" - Main Account';
$lng['en']['w']['api_999md_separate'] = 'API "Три Девятки МД" - Separate Account';
$lng['en']['w']['account_login'] = 'Account/Login:';
$lng['en']['w']['api_token'] = 'API Token:';
$lng['en']['w']['telegram_channel_1'] = 'Telegram - Channel №1';
$lng['en']['w']['telegram_separate'] = 'Telegram - Separate Channel';
$lng['en']['w']['bot_token'] = 'Bot Token:';
$lng['en']['w']['chat_id'] = 'Chat ID:';
$lng['en']['w']['facebook_main'] = 'Facebook - Main Page';
$lng['en']['w']['facebook_same'] = 'Facebook - Same page with "On Order" label';
$lng['en']['w']['page_id'] = 'Page ID:';
$lng['en']['w']['page_token'] = 'Page Token:';
$lng['en']['w']['auto_publish_stock'] = 'Auto-publish when adding car in stock';
$lng['en']['w']['auto_publish_order'] = 'Auto-publish when adding car on order';
$lng['en']['w']['save_settings'] = 'Save Settings';
$lng['en']['w']['separate_account'] = 'Separate account for orders';
$lng['en']['w']['same_page_id'] = 'Facebook page ID for orders';


// Publication Settings for Order Cars vs Regular Cars
$rtrn = '';

// Handle form submission
if ($_POST) {
    $settings = [
        // Regular cars (in_stock) settings
        'regular_999md_account' => $_POST['regular_999md_account'] ?? '',
        'regular_999md_token' => $_POST['regular_999md_token'] ?? '',
        'regular_telegram_bot_token' => $_POST['regular_telegram_bot_token'] ?? '',
        'regular_telegram_chat_id' => $_POST['regular_telegram_chat_id'] ?? '',
        'regular_facebook_page_id' => $_POST['regular_facebook_page_id'] ?? '',
        'regular_facebook_token' => $_POST['regular_facebook_token'] ?? '',
        
        // Order cars (on_order) settings
        'order_999md_account' => $_POST['order_999md_account'] ?? '',
        'order_999md_token' => $_POST['order_999md_token'] ?? '',
        'order_telegram_bot_token' => $_POST['order_telegram_bot_token'] ?? '',
        'order_telegram_chat_id' => $_POST['order_telegram_chat_id'] ?? '',
        'order_facebook_page_id' => $_POST['order_facebook_page_id'] ?? '',
        'order_facebook_token' => $_POST['order_facebook_token'] ?? '',
        
        // Auto-publication settings
        'auto_publish_regular' => isset($_POST['auto_publish_regular']) ? 1 : 0,
        'auto_publish_order' => isset($_POST['auto_publish_order']) ? 1 : 0,
    ];
    
    // Save settings to database
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("INSERT INTO {$prefx}_settings (name, value) VALUES (?, ?) AS new_values
                             ON DUPLICATE KEY UPDATE value = new_values.value");
        $stmt->execute([$key, $value]);
    }
    
    $rtrn .= '<div class="success">' . ($lng[$_COOKIE['lang']]['w']['publication_settings_saved'] ?? 'Настройки публикации сохранены!') . '</div>';
}

// Load current settings
$current_settings = [];
$stmt = $db->prepare("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%_999md_%' OR name LIKE '%_telegram_%' OR name LIKE '%_facebook_%' OR name LIKE 'auto_publish_%'");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $current_settings[$row['name']] = $row['value'];
}

$rtrn .= '
<div class="publication-settings">
    <h2>' . ($lng[$_COOKIE['lang']]['w']['publication_settings_title'] ?? 'Настройки публикации автомобилей') . '</h2>
    
    <form method="POST">
        <div class="settings-section">
            <h3>🚗 ' . ($lng[$_COOKIE['lang']]['w']['cars_in_stock'] ?? 'Автомобили в наличии') . ' (catalog_type = "in_stock")</h3>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['api_999md_main'] ?? 'API "Три Девятки МД" - Основной аккаунт') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['account_login'] ?? 'Аккаунт/Login:') . '</label>
                <input type="text" name="regular_999md_account" value="' . htmlspecialchars($current_settings['regular_999md_account'] ?? '') . '" placeholder="Основной аккаунт 999.md">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['api_token'] ?? 'API Token:') . '</label>
                <input type="password" name="regular_999md_token" value="' . htmlspecialchars($current_settings['regular_999md_token'] ?? '') . '" placeholder="API токен для основного аккаунта">
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['telegram_channel_1'] ?? 'Telegram - Канал №1') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['bot_token'] ?? 'Bot Token:') . '</label>
                <input type="password" name="regular_telegram_bot_token" value="' . htmlspecialchars($current_settings['regular_telegram_bot_token'] ?? '7459955785:AAGTMPvUkh2Fktar7ZpNlHBFsq43FH_DPsY') . '" placeholder="Bot token для основного канала">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['chat_id'] ?? 'Chat ID:') . '</label>
                <input type="text" name="regular_telegram_chat_id" value="' . htmlspecialchars($current_settings['regular_telegram_chat_id'] ?? '-1002605369940') . '" placeholder="Chat ID основного канала">
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['facebook_main'] ?? 'Facebook - Основная страница') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_id'] ?? 'Page ID:') . '</label>
                <input type="text" name="regular_facebook_page_id" value="' . htmlspecialchars($current_settings['regular_facebook_page_id'] ?? '482777831588669') . '" placeholder="ID основной Facebook страницы">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_token'] ?? 'Page Token:') . '</label>
                <input type="password" name="regular_facebook_token" value="' . htmlspecialchars($current_settings['regular_facebook_token'] ?? '') . '" placeholder="Access token для основной страницы">
            </div>
            
            <div class="setting-group">
                <label>
                    <input type="checkbox" name="auto_publish_regular" ' . (($current_settings['auto_publish_regular'] ?? 0) ? 'checked' : '') . '>
                    ' . ($lng[$_COOKIE['lang']]['w']['auto_publish_stock'] ?? 'Автоматическая публикация при добавлении автомобиля в наличии') . '
                </label>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>📋 ' . ($lng[$_COOKIE['lang']]['w']['cars_on_order'] ?? 'Автомобили под заказ') . ' (catalog_type = "on_order")</h3>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['api_999md_separate'] ?? 'API "Три Девятки МД" - Отдельный аккаунт') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['account_login'] ?? 'Аккаунт/Login:') . '</label>
                <input type="text" name="order_999md_account" value="' . htmlspecialchars($current_settings['order_999md_account'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['separate_account'] ?? 'Отдельный аккаунт для заказов') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['api_token'] ?? 'API Token:') . '</label>
                <input type="password" name="order_999md_token" value="' . htmlspecialchars($current_settings['order_999md_token'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['api_token'] ?? 'API токен для аккаунта заказов') . '">
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['telegram_separate'] ?? 'Telegram - Отдельный канал') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['bot_token'] ?? 'Bot Token:') . '</label>
                <input type="password" name="order_telegram_bot_token" value="' . htmlspecialchars($current_settings['order_telegram_bot_token'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['bot_token'] ?? 'Bot token для канала заказов') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['chat_id'] ?? 'Chat ID:') . '</label>
                <input type="text" name="order_telegram_chat_id" value="' . htmlspecialchars($current_settings['order_telegram_chat_id'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['chat_id'] ?? 'Chat ID канала заказов') . '">
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['facebook_same'] ?? 'Facebook - Та же страница с меткой "Под заказ"') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_id'] ?? 'Page ID:') . '</label>
                <input type="text" name="order_facebook_page_id" value="' . htmlspecialchars($current_settings['order_facebook_page_id'] ?? '482777831588669') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['same_page_id'] ?? 'ID Facebook страницы для заказов') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_token'] ?? 'Page Token:') . '</label>
                <input type="password" name="order_facebook_token" value="' . htmlspecialchars($current_settings['order_facebook_token'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['page_token'] ?? 'Access token для страницы заказов') . '">
            </div>
            
            <div class="setting-group">
                <label>
                    <input type="checkbox" name="auto_publish_order" ' . (($current_settings['auto_publish_order'] ?? 0) ? 'checked' : '') . '>
                    ' . ($lng[$_COOKIE['lang']]['w']['auto_publish_order'] ?? 'Автоматическая публикация при добавлении автомобиля под заказ') . '
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">' . ($lng[$_COOKIE['lang']]['w']['save_settings'] ?? 'Сохранить настройки') . '</button>
        </div>
    </form>
</div>

<style>
.publication-settings {
    max-width: 900px;
    margin: 20px auto;
    padding: 25px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.publication-settings h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
    font-size: 24px;
    font-weight: 300;
}

.settings-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 25px;
    margin: 25px 0;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
}

.settings-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 500;
    padding-bottom: 10px;
    border-bottom: 2px solid #007cba;
    display: inline-block;
}

.setting-group {
    margin: 20px 0;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #f1f3f4;
}

.setting-group h4 {
    margin: 0 0 15px 0;
    color: #495057;
    font-size: 16px;
    font-weight: 600;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #007cba;
}

.setting-group label {
    display: block;
    margin: 15px 0 8px 0;
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.setting-group input[type="text"],
.setting-group input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.setting-group input[type="text"]:focus,
.setting-group input[type="password"]:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
}

.setting-group input[type="checkbox"] {
    margin-right: 10px;
    transform: scale(1.2);
}

.publication-settings .form-actions {
    text-align: center;
    margin: 40px 0 20px 0;
    padding: 20px 0;
}

.publication-settings .btn {
    padding: 15px 30px;
    background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 124, 186, 0.3);
}

.publication-settings .btn:hover {
    background: linear-gradient(135deg, #005a87 0%, #004066 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 124, 186, 0.4);
}

.publication-settings .success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px 0;
    border: 1px solid #c3e6cb;
    font-weight: 500;
    text-align: center;
}
</style>';

echo $rtrn;
?>
