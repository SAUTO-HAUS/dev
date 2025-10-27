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
$lng['ro']['w']['cars_on_order'] = 'Automobile la comandă';
$lng['ro']['w']['api_999md_main'] = 'API "999.md"';
$lng['ro']['w']['api_999md_separate'] = 'API "999.md"';
$lng['ro']['w']['account_login'] = 'Cont/Login:';
$lng['ro']['w']['api_token'] = 'Token API:';
$lng['ro']['w']['telegram_channel_1'] = 'Telegram';
$lng['ro']['w']['telegram_separate'] = 'Telegram';
$lng['ro']['w']['bot_token'] = 'Token Bot:';
$lng['ro']['w']['chat_id'] = 'Chat ID:';
$lng['ro']['w']['facebook_main'] = 'Facebook';
$lng['ro']['w']['facebook_same'] = 'Facebook';
$lng['ro']['w']['page_id'] = 'Page ID:';
$lng['ro']['w']['page_token'] = 'Page Token:';
$lng['ro']['w']['auto_publish_stock'] = 'Publicare automată la adăugarea automobilului în stoc';
$lng['ro']['w']['auto_publish_order'] = 'Publicare automată la adăugarea automobilului la comandă';
$lng['ro']['w']['save_settings'] = 'Salvează setările';
$lng['ro']['w']['separate_account'] = 'Cont separat pentru comenzi';
$lng['ro']['w']['same_page_id'] = 'ID pagină Facebook pentru comenzi';

// Placeholder translations - Romanian
$lng['ro']['w']['placeholder_main_999md_account'] = 'Contul principal 999.md';
$lng['ro']['w']['placeholder_main_999md_token'] = 'Token API pentru contul principal';
$lng['ro']['w']['placeholder_main_telegram_token'] = 'Token bot pentru canalul principal';
$lng['ro']['w']['placeholder_main_telegram_chat'] = 'Chat ID canalul principal';
$lng['ro']['w']['placeholder_main_facebook_page'] = 'ID pagina Facebook principală';
$lng['ro']['w']['placeholder_main_facebook_token'] = 'Token acces pentru pagina principală';
$lng['ro']['w']['placeholder_order_999md_account'] = 'Cont separat pentru comenzi';
$lng['ro']['w']['placeholder_order_999md_token'] = 'Token API pentru contul de comenzi';
$lng['ro']['w']['placeholder_order_telegram_token'] = 'Token bot pentru canalul de comenzi';
$lng['ro']['w']['placeholder_order_telegram_chat'] = 'Chat ID canalul de comenzi';
$lng['ro']['w']['placeholder_order_facebook_page'] = 'ID pagină Facebook pentru comenzi';
$lng['ro']['w']['placeholder_order_facebook_token'] = 'Token acces pentru pagina de comenzi';

// Russian
$lng['ru']['w']['publication_settings_title'] = 'Настройки публикации автомобилей';
$lng['ru']['w']['publication_settings_saved'] = 'Настройки публикации сохранены!';
$lng['ru']['w']['cars_in_stock'] = 'Автомобили в наличии';
$lng['ru']['w']['cars_on_order'] = 'Автомобили под заказ';
$lng['ru']['w']['api_999md_main'] = 'API "999.md"';
$lng['ru']['w']['api_999md_separate'] = 'API "999.md"';
$lng['ru']['w']['account_login'] = 'Аккаунт/Login:';
$lng['ru']['w']['api_token'] = 'API Token:';
$lng['ru']['w']['telegram_channel_1'] = 'Telegram';
$lng['ru']['w']['telegram_separate'] = 'Telegram';
$lng['ru']['w']['bot_token'] = 'Bot Token:';
$lng['ru']['w']['chat_id'] = 'Chat ID:';
$lng['ru']['w']['facebook_main'] = 'Facebook';
$lng['ru']['w']['facebook_same'] = 'Facebook';
$lng['ru']['w']['page_id'] = 'Page ID:';
$lng['ru']['w']['page_token'] = 'Page Token:';
$lng['ru']['w']['auto_publish_stock'] = 'Автоматическая публикация при добавлении автомобиля в наличии';
$lng['ru']['w']['auto_publish_order'] = 'Автоматическая публикация при добавлении автомобиля под заказ';
$lng['ru']['w']['save_settings'] = 'Сохранить настройки';
$lng['ru']['w']['separate_account'] = 'Отдельный аккаунт для заказов';
$lng['ru']['w']['same_page_id'] = 'ID Facebook страницы для заказов';

// Placeholder translations - Russian
$lng['ru']['w']['placeholder_main_999md_account'] = 'Основной аккаунт 999.md';
$lng['ru']['w']['placeholder_main_999md_token'] = 'API токен для основного аккаунта';
$lng['ru']['w']['placeholder_main_telegram_token'] = 'Bot token для основного канала';
$lng['ru']['w']['placeholder_main_telegram_chat'] = 'Chat ID основного канала';
$lng['ru']['w']['placeholder_main_facebook_page'] = 'ID основной Facebook страницы';
$lng['ru']['w']['placeholder_main_facebook_token'] = 'Access token для основной страницы';
$lng['ru']['w']['placeholder_order_999md_account'] = 'Отдельный аккаунт для заказов';
$lng['ru']['w']['placeholder_order_999md_token'] = 'API токен для аккаунта заказов';
$lng['ru']['w']['placeholder_order_telegram_token'] = 'Bot token для канала заказов';
$lng['ru']['w']['placeholder_order_telegram_chat'] = 'Chat ID канала заказов';
$lng['ru']['w']['placeholder_order_facebook_page'] = 'ID Facebook страницы для заказов';
$lng['ru']['w']['placeholder_order_facebook_token'] = 'Access token для страницы заказов';

// English
$lng['en']['w']['publication_settings_title'] = 'Car Publication Settings';
$lng['en']['w']['publication_settings_saved'] = 'Publication settings saved!';
$lng['en']['w']['cars_in_stock'] = 'Cars in Stock';
$lng['en']['w']['cars_on_order'] = 'Cars on Order';
$lng['en']['w']['api_999md_main'] = 'API "999.md"';
$lng['en']['w']['api_999md_separate'] = 'API "999.md"';
$lng['en']['w']['account_login'] = 'Account/Login:';
$lng['en']['w']['api_token'] = 'API Token:';
$lng['en']['w']['telegram_channel_1'] = 'Telegram';
$lng['en']['w']['telegram_separate'] = 'Telegram';
$lng['en']['w']['bot_token'] = 'Bot Token:';
$lng['en']['w']['chat_id'] = 'Chat ID:';
$lng['en']['w']['facebook_main'] = 'Facebook';
$lng['en']['w']['facebook_same'] = 'Facebook';
$lng['en']['w']['page_id'] = 'Page ID:';
$lng['en']['w']['page_token'] = 'Page Token:';
$lng['en']['w']['auto_publish_stock'] = 'Auto-publish when adding car in stock';
$lng['en']['w']['auto_publish_order'] = 'Auto-publish when adding car on order';
$lng['en']['w']['save_settings'] = 'Save Settings';
$lng['en']['w']['separate_account'] = 'Separate account for orders';
$lng['en']['w']['same_page_id'] = 'Facebook page ID for orders';

// Placeholder translations - English
$lng['en']['w']['placeholder_main_999md_account'] = 'Main 999.md account';
$lng['en']['w']['placeholder_main_999md_token'] = 'API token for main account';
$lng['en']['w']['placeholder_main_telegram_token'] = 'Bot token for main channel';
$lng['en']['w']['placeholder_main_telegram_chat'] = 'Chat ID for main channel';
$lng['en']['w']['placeholder_main_facebook_page'] = 'Main Facebook page ID';
$lng['en']['w']['placeholder_main_facebook_token'] = 'Access token for main page';
$lng['en']['w']['placeholder_order_999md_account'] = 'Separate account for orders';
$lng['en']['w']['placeholder_order_999md_token'] = 'API token for orders account';
$lng['en']['w']['placeholder_order_telegram_token'] = 'Bot token for orders channel';
$lng['en']['w']['placeholder_order_telegram_chat'] = 'Chat ID for orders channel';
$lng['en']['w']['placeholder_order_facebook_page'] = 'Facebook page ID for orders';
$lng['en']['w']['placeholder_order_facebook_token'] = 'Access token for orders page';


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
        <div class="settings-container">
            <div class="settings-column left-column">
                <div class="settings-section">
                    <h3>🚗 ' . ($lng[$_COOKIE['lang']]['w']['cars_in_stock'] ?? 'Автомобили в наличии') . '</h3>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['api_999md_main'] ?? 'API "Три Девятки МД" - Основной аккаунт') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['account_login'] ?? 'Аккаунт/Login:') . '</label>
                <input type="text" name="regular_999md_account" value="' . htmlspecialchars($current_settings['regular_999md_account'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_main_999md_account'] ?? 'Основной аккаунт 999.md') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['api_token'] ?? 'API Token:') . '</label>
                <input type="password" name="regular_999md_token" value="' . htmlspecialchars($current_settings['regular_999md_token'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_main_999md_token'] ?? 'API токен для основного аккаунта') . '">
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['telegram_channel_1'] ?? 'Telegram - Канал №1') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['bot_token'] ?? 'Bot Token:') . '</label>
                <input type="password" name="regular_telegram_bot_token" value="' . htmlspecialchars($current_settings['regular_telegram_bot_token'] ?? '7459955785:AAGTMPvUkh2Fktar7ZpNlHBFsq43FH_DPsY') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_main_telegram_token'] ?? 'Bot token для основного канала') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['chat_id'] ?? 'Chat ID:') . '</label>
                <input type="text" name="regular_telegram_chat_id" value="' . htmlspecialchars($current_settings['regular_telegram_chat_id'] ?? '-1002605369940') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_main_telegram_chat'] ?? 'Chat ID основного канала') . '">
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['facebook_main'] ?? 'Facebook - Основная страница') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_id'] ?? 'Page ID:') . '</label>
                <input type="text" name="regular_facebook_page_id" value="' . htmlspecialchars($current_settings['regular_facebook_page_id'] ?? '482777831588669') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_main_facebook_page'] ?? 'ID основной Facebook страницы') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_token'] ?? 'Page Token:') . '</label>
                <input type="password" name="regular_facebook_token" value="' . htmlspecialchars($current_settings['regular_facebook_token'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_main_facebook_token'] ?? 'Access token для основной страницы') . '">
            </div>
            
            <div class="setting-group">
                <label>
                    <input type="checkbox" name="auto_publish_regular" ' . (($current_settings['auto_publish_regular'] ?? 0) ? 'checked' : '') . '>
                    ' . ($lng[$_COOKIE['lang']]['w']['auto_publish_stock'] ?? 'Автоматическая публикация при добавлении автомобиля в наличии') . '
                </label>
            </div>
                </div>
            </div>
            
            <div class="settings-column right-column">
                <div class="settings-section">
                    <h3>📋 ' . ($lng[$_COOKIE['lang']]['w']['cars_on_order'] ?? 'Автомобили под заказ') . '</h3>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['api_999md_separate'] ?? 'API "Три Девятки МД" - Отдельный аккаунт') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['account_login'] ?? 'Аккаунт/Login:') . '</label>
                <input type="text" name="order_999md_account" value="' . htmlspecialchars($current_settings['order_999md_account'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_order_999md_account'] ?? 'Отдельный аккаунт для заказов') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['api_token'] ?? 'API Token:') . '</label>
                <input type="password" name="order_999md_token" value="' . htmlspecialchars($current_settings['order_999md_token'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_order_999md_token'] ?? 'API токен для аккаунта заказов') . '">
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['telegram_separate'] ?? 'Telegram - Отдельный канал') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['bot_token'] ?? 'Bot Token:') . '</label>
                <input type="password" name="order_telegram_bot_token" value="' . htmlspecialchars($current_settings['order_telegram_bot_token'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_order_telegram_token'] ?? 'Bot token для канала заказов') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['chat_id'] ?? 'Chat ID:') . '</label>
                <input type="text" name="order_telegram_chat_id" value="' . htmlspecialchars($current_settings['order_telegram_chat_id'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_order_telegram_chat'] ?? 'Chat ID канала заказов') . '">
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['facebook_same'] ?? 'Facebook - Та же страница с меткой "Под заказ"') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_id'] ?? 'Page ID:') . '</label>
                <input type="text" name="order_facebook_page_id" value="' . htmlspecialchars($current_settings['order_facebook_page_id'] ?? '482777831588669') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_order_facebook_page'] ?? 'ID Facebook страницы для заказов') . '">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_token'] ?? 'Page Token:') . '</label>
                <input type="password" name="order_facebook_token" value="' . htmlspecialchars($current_settings['order_facebook_token'] ?? '') . '" placeholder="' . ($lng[$_COOKIE['lang']]['w']['placeholder_order_facebook_token'] ?? 'Access token для страницы заказов') . '">
            </div>
            
            <div class="setting-group">
                <label>
                    <input type="checkbox" name="auto_publish_order" ' . (($current_settings['auto_publish_order'] ?? 0) ? 'checked' : '') . '>
                    ' . ($lng[$_COOKIE['lang']]['w']['auto_publish_order'] ?? 'Автоматическая публикация при добавлении автомобиля под заказ') . '
                </label>
            </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">' . ($lng[$_COOKIE['lang']]['w']['save_settings'] ?? 'Сохранить настройки') . '</button>
        </div>
    </form>
</div>

<style>
.publication-settings {
    max-width: 1400px;
    margin: 20px auto;
    padding: 30px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.publication-settings h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 40px;
    font-size: 28px;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.settings-container {
    display: flex;
    gap: 30px;
    align-items: flex-start;
}

.settings-column {
    flex: 1;
    min-height: 100%;
}

.left-column .settings-section {
    background: linear-gradient(135deg, #ffebee 0%, #fce4ec 100%);
    border: 2px solid #f44336;
}

.right-column .settings-section {
    background: linear-gradient(135deg, #fff3e0 0%, #fce4ec 100%);
    border: 2px solid #ff9800;
}

.settings-section {
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.settings-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.settings-section h3 {
    margin: 0 0 25px 0;
    color: #2c3e50;
    font-size: 22px;
    font-weight: 700;
    text-align: center;
    padding: 15px;
    background: rgba(255,255,255,0.9);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.setting-group {
    margin: 20px 0;
    padding: 20px;
    background: rgba(255,255,255,0.95);
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid rgba(255,255,255,0.8);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.setting-group:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(0,0,0,0.12);
}

.setting-group h4 {
    margin: 0 0 20px 0;
    color: #34495e;
    font-size: 16px;
    font-weight: 700;
    padding: 12px 16px;
    background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
    color: white;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
}

.setting-group label {
    display: block;
    margin: 15px 0 8px 0;
    font-weight: 600;
    color: #495057;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.setting-group input[type="text"],
.setting-group input[type="password"] {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    box-sizing: border-box;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(5px);
}

.setting-group input[type="text"]:focus,
.setting-group input[type="password"]:focus {
    outline: none;
    border-color: #e53e3e;
    box-shadow: 0 0 0 4px rgba(229, 62, 62, 0.15);
    background: white;
    transform: translateY(-1px);
}

.setting-group input[type="checkbox"] {
    margin-right: 12px;
    transform: scale(1.3);
    accent-color: #e53e3e;
}

.setting-group label:has(input[type="checkbox"]) {
    display: flex;
    align-items: center;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: none;
    font-size: 15px;
    font-weight: 500;
}

.setting-group label:has(input[type="checkbox"]):hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    transform: translateY(-1px);
}

.publication-settings .form-actions {
    text-align: center;
    margin: 25px 0 20px 0;
    padding: 15px 0;
}

.publication-settings .btn {
    padding: 18px 40px;
    background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
}

.publication-settings .btn:hover {
    background: linear-gradient(135deg, #c53030 0%, #e53e3e 100%);
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(229, 62, 62, 0.6);
}

.publication-settings .success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    padding: 20px 25px;
    border-radius: 12px;
    margin: 25px 0;
    border: 2px solid #28a745;
    font-weight: 600;
    text-align: center;
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
}

@media (max-width: 768px) {
    .settings-container {
        flex-direction: column;
        gap: 20px;
    }
    
    .publication-settings {
        margin: 10px;
        padding: 20px;
    }
    
    .publication-settings h2 {
        font-size: 24px;
    }
}
</style>';

// Content is returned via $rtrn variable, no need to echo here
?>
