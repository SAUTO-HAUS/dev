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
$lng['ro']['w']['api_999md_main'] = 'API "999.md" - SAUTO-HAUS';
$lng['ro']['w']['api_999md_separate'] = 'API "999.md" - Sauto-stock-extern';
$lng['ro']['w']['account_login'] = 'Cont/Login:';
$lng['ro']['w']['api_token'] = 'Token API:';
$lng['ro']['w']['telegram_channel_1'] = 'Telegram AutoMoldova';
$lng['ro']['w']['telegram_separate'] = 'Telegram Sauto.md';
$lng['ro']['w']['bot_token'] = 'Token Bot:';
$lng['ro']['w']['chat_id'] = 'Chat ID:';
$lng['ro']['w']['facebook_main'] = 'Facebook - str. Calea Moşilor 11';
$lng['ro']['w']['facebook_same'] = 'Facebook - str. Pietrăriei 3';
$lng['ro']['w']['page_id'] = 'Page ID:';
$lng['ro']['w']['page_token'] = 'Page Token:';
$lng['ro']['w']['auto_publish_stock'] = 'Publicare automată la adăugarea automobilului în stoc';
$lng['ro']['w']['auto_publish_order'] = 'Publicare automată la adăugarea automobilului la comandă';
$lng['ro']['w']['save_settings'] = 'Salvează setările';
$lng['ro']['w']['separate_account'] = 'Cont separat pentru comenzi';
$lng['ro']['w']['same_page_id'] = 'ID pagină Facebook pentru comenzi';
$lng['ro']['w']['facebook_random_time_range'] = 'Interval ore random pentru Facebook';
$lng['ro']['w']['facebook_start_time'] = 'Ora de început:';
$lng['ro']['w']['facebook_end_time'] = 'Ora de sfârșit:';
$lng['ro']['w']['telegram_random_time_range'] = 'Interval ore random pentru Telegram';
$lng['ro']['w']['telegram_start_time'] = 'Ora de început:';
$lng['ro']['w']['telegram_end_time'] = 'Ora de sfârșit:';
$lng['ro']['w']['999md_random_time_range'] = 'Interval ore random pentru 999.md';
$lng['ro']['w']['999md_start_time'] = 'Ora de început:';
$lng['ro']['w']['999md_end_time'] = 'Ora de sfârșit:';
$lng['ro']['w']['random_interval_minutes'] = 'Interval minute:';
$lng['ro']['w']['placeholder_start_time'] = 'Ora de început (ex: 18:00)';
$lng['ro']['w']['placeholder_end_time'] = 'Ora de sfârșit (ex: 22:00)';
$lng['ro']['w']['placeholder_interval'] = 'Interval în minute (ex: 5)';

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
$lng['ru']['w']['api_999md_main'] = 'API "999.md" - SAUTO-HAUS';
$lng['ru']['w']['api_999md_separate'] = 'API "999.md" - Sauto-stock-extern';
$lng['ru']['w']['account_login'] = 'Аккаунт/Login:';
$lng['ru']['w']['api_token'] = 'API Token:';
$lng['ru']['w']['telegram_channel_1'] = 'Telegram AutoMoldova';
$lng['ru']['w']['telegram_separate'] = 'Telegram Sauto.md';
$lng['ru']['w']['bot_token'] = 'Bot Token:';
$lng['ru']['w']['chat_id'] = 'Chat ID:';
$lng['ru']['w']['facebook_main'] = 'Facebook - ул. Calea Mosilor 11';
$lng['ru']['w']['facebook_same'] = 'Facebook - ул. Pietrariei 3';
$lng['ru']['w']['page_id'] = 'Page ID:';
$lng['ru']['w']['page_token'] = 'Page Token:';
$lng['ru']['w']['auto_publish_stock'] = 'Автоматическая публикация при добавлении автомобиля в наличии';
$lng['ru']['w']['auto_publish_order'] = 'Автоматическая публикация при добавлении автомобиля под заказ';
$lng['ru']['w']['save_settings'] = 'Сохранить настройки';
$lng['ru']['w']['separate_account'] = 'Отдельный аккаунт для заказов';
$lng['ru']['w']['same_page_id'] = 'ID Facebook страницы для заказов';
$lng['ru']['w']['facebook_random_time_range'] = 'Диапазон случайного времени для Facebook';
$lng['ru']['w']['facebook_start_time'] = 'Время начала:';
$lng['ru']['w']['facebook_end_time'] = 'Время окончания:';
$lng['ru']['w']['telegram_random_time_range'] = 'Диапазон случайного времени для Telegram';
$lng['ru']['w']['telegram_start_time'] = 'Время начала:';
$lng['ru']['w']['telegram_end_time'] = 'Время окончания:';
$lng['ru']['w']['999md_random_time_range'] = 'Диапазон случайного времени для 999.md';
$lng['ru']['w']['999md_start_time'] = 'Время начала:';
$lng['ru']['w']['999md_end_time'] = 'Время окончания:';
$lng['ru']['w']['random_interval_minutes'] = 'Интервал в минутах:';
$lng['ru']['w']['placeholder_start_time'] = 'Время начала (напр: 18:00)';
$lng['ru']['w']['placeholder_end_time'] = 'Время окончания (напр: 22:00)';
$lng['ru']['w']['placeholder_interval'] = 'Интервал в минутах (напр: 5)';

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
$lng['en']['w']['api_999md_main'] = 'API "999.md" - SAUTO-HAUS';
$lng['en']['w']['api_999md_separate'] = 'API "999.md" - Sauto-stock-extern';
$lng['en']['w']['account_login'] = 'Account/Login:';
$lng['en']['w']['api_token'] = 'API Token:';
$lng['en']['w']['telegram_channel_1'] = 'Telegram AutoMoldova';
$lng['en']['w']['telegram_separate'] = 'Telegram Sauto.md';
$lng['en']['w']['bot_token'] = 'Bot Token:';
$lng['en']['w']['chat_id'] = 'Chat ID:';
$lng['en']['w']['facebook_main'] = 'Facebook - st. Calea Mosilor 11';
$lng['en']['w']['facebook_same'] = 'Facebook - st. Pietrariei 3';
$lng['en']['w']['page_id'] = 'Page ID:';
$lng['en']['w']['page_token'] = 'Page Token:';
$lng['en']['w']['auto_publish_stock'] = 'Auto-publish when adding car in stock';
$lng['en']['w']['auto_publish_order'] = 'Auto-publish when adding car on order';
$lng['en']['w']['save_settings'] = 'Save Settings';
$lng['en']['w']['separate_account'] = 'Separate account for orders';
$lng['en']['w']['same_page_id'] = 'Facebook page ID for orders';
$lng['en']['w']['facebook_random_time_range'] = 'Random time range for Facebook';
$lng['en']['w']['facebook_start_time'] = 'Start time:';
$lng['en']['w']['facebook_end_time'] = 'End time:';
$lng['en']['w']['telegram_random_time_range'] = 'Random time range for Telegram';
$lng['en']['w']['telegram_start_time'] = 'Start time:';
$lng['en']['w']['telegram_end_time'] = 'End time:';
$lng['en']['w']['999md_random_time_range'] = 'Random time range for 999.md';
$lng['en']['w']['999md_start_time'] = 'Start time:';
$lng['en']['w']['999md_end_time'] = 'End time:';
$lng['en']['w']['random_interval_minutes'] = 'Interval minutes:';
$lng['en']['w']['placeholder_start_time'] = 'Start time (e.g: 18:00)';
$lng['en']['w']['placeholder_end_time'] = 'End time (e.g: 22:00)';
$lng['en']['w']['placeholder_interval'] = 'Interval in minutes (e.g: 5)';

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
        'location_1_facebook_page_id' => $_POST['location_1_facebook_page_id'] ?? '',
        'location_1_facebook_token' => $_POST['location_1_facebook_token'] ?? '',
        
        // Order cars (on_order) settings
        'order_999md_account' => $_POST['order_999md_account'] ?? '',
        'order_999md_token' => $_POST['order_999md_token'] ?? '',
        'order_telegram_bot_token' => $_POST['order_telegram_bot_token'] ?? '',
        'order_telegram_chat_id' => $_POST['order_telegram_chat_id'] ?? '',
        'location_2_facebook_page_id' => $_POST['location_2_facebook_page_id'] ?? '',
        'location_2_facebook_token' => $_POST['location_2_facebook_token'] ?? '',
        
        // Korea cars (on_order from Korea) settings
        'korea_999md_account' => $_POST['korea_999md_account'] ?? '',
        'korea_999md_token' => $_POST['korea_999md_token'] ?? '',
        
        // Auto-publication settings
        'auto_publish_regular' => isset($_POST['auto_publish_regular']) ? 1 : 0,
        'auto_publish_order' => isset($_POST['auto_publish_order']) ? 1 : 0,
        
        // Facebook random time range settings
        'facebook_random_start_time' => $_POST['facebook_random_start_time'] ?? '18:00',
        'facebook_random_end_time' => $_POST['facebook_random_end_time'] ?? '22:00',
        'facebook_random_interval_minutes' => $_POST['facebook_random_interval_minutes'] ?? '5',
        
        // Telegram random time range settings
        'telegram_random_start_time' => $_POST['telegram_random_start_time'] ?? '18:00',
        'telegram_random_end_time' => $_POST['telegram_random_end_time'] ?? '22:00',
        'telegram_random_interval_minutes' => $_POST['telegram_random_interval_minutes'] ?? '5',
        
        // 999.md random time range settings
        '999md_random_start_time' => $_POST['999md_random_start_time'] ?? '18:00',
        '999md_random_end_time' => $_POST['999md_random_end_time'] ?? '22:00',
        '999md_random_interval_minutes' => $_POST['999md_random_interval_minutes'] ?? '5',
    ];
    
      // Save settings to database
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("UPDATE {$prefx}_settings SET value = ? WHERE name = ?");
        $stmt->execute([$value, $key]);
        
        if ($stmt->rowCount() == 0) {
            $stmt = $db->prepare("INSERT INTO {$prefx}_settings (name, value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }
    }
    
    $rtrn .= '<div class="success">' . ($lng[$_COOKIE['lang']]['w']['publication_settings_saved'] ?? 'Настройки публикации сохранены!') . '</div>';
}

// Load current settings - force fresh data
$current_settings = [];
$stmt = $db->prepare("SELECT name, value FROM {$prefx}_settings WHERE name IN ('regular_999md_account', 'regular_999md_token', 'regular_telegram_bot_token', 'regular_telegram_chat_id', 'order_999md_account', 'order_999md_token', 'order_telegram_bot_token', 'order_telegram_chat_id', 'korea_999md_account', 'korea_999md_token', 'location_1_facebook_page_id', 'location_1_facebook_token', 'location_2_facebook_page_id', 'location_2_facebook_token', 'auto_publish_regular', 'auto_publish_order', 'facebook_random_start_time', 'facebook_random_end_time', 'facebook_random_interval_minutes', 'telegram_random_start_time', 'telegram_random_end_time', 'telegram_random_interval_minutes', '999md_random_start_time', '999md_random_end_time', '999md_random_interval_minutes')");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $current_settings[$row['name']] = $row['value'];
}


$rtrn .= '
<div class="publication-settings">
    <h2>' . ($lng[$_COOKIE['lang']]['w']['publication_settings_title'] ?? 'Настройки публикации автомобилей') . '</h2>
    
    <form method="POST" action="?'. time() .'">
        <div class="settings-container">
            <div class="settings-column left-column">
                <div class="settings-section">
                    <h3>🚗 ' . ($lng[$_COOKIE['lang']]['w']['cars_in_stock'] ?? 'Автомобили в наличии') . '</h3>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['api_999md_main'] ?? 'API "Три Девятки МД" - Основной аккаунт') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['account_login'] ?? 'Аккаунт/Login:') . '</label>
                <input type="text" name="regular_999md_account" value="' . htmlspecialchars($current_settings['regular_999md_account'] ?? 'SAUTO-HAUS') . '" autocomplete="off" data-form-type="other">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['api_token'] ?? 'API Token:') . '</label>
                <div class="input-with-button">
                    <input type="password" name="regular_999md_token" id="regular_999md_token" value="' . htmlspecialchars($current_settings['regular_999md_token'] ?? 'I_SKyGEvvG5Rfm7lRZeiOTwk7r_F') . '" autocomplete="new-password" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="generate999Token(&quot;regular&quot;)">🔑 Generează</button>
                </div>
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['telegram_channel_1'] ?? 'Telegram - Канал №1') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['bot_token'] ?? 'Bot Token:') . '</label>
                <div class="input-with-button">
                    <input type="password" name="regular_telegram_bot_token" id="regular_telegram_bot_token" value="' . htmlspecialchars($current_settings['regular_telegram_bot_token'] ?? '1398519511:AAHGNlpbTutAS4QAnT7Z-eCf7_z80hJ4N2g') . '" autocomplete="new-password" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="generateTelegramToken(&quot;regular&quot;)">🤖 Generează Bot</button>
                </div>
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['chat_id'] ?? 'Chat ID:') . '</label>
                <div class="input-with-button">
                    <input type="text" name="regular_telegram_chat_id" id="regular_telegram_chat_id" value="' . htmlspecialchars($current_settings['regular_telegram_chat_id'] ?? '564183869') . '" autocomplete="off" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="getTelegramChatId(&quot;regular&quot;)">💬 Obține Chat ID</button>
                </div>
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['facebook_main'] ?? 'Facebook - Основная страница') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_id'] ?? 'Page ID:') . '</label>
                <div class="input-with-button">
                    <input type="text" name="location_1_facebook_page_id" id="location_1_facebook_page_id" value="' . htmlspecialchars($current_settings['location_1_facebook_page_id'] ?? '100063457076866') . '" autocomplete="off" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="getFacebookPageId(&quot;regular&quot;)">📄 Obține Page ID</button>
                </div>
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_token'] ?? 'Page Token:') . '</label>
                <div class="input-with-button">
                    <input type="text" name="location_1_facebook_token" id="location_1_facebook_token" value="' . htmlspecialchars($current_settings['location_1_facebook_token'] ?? '') . '" autocomplete="off" data-form-type="other" style="font-family: monospace; font-size: 12px;">
                    <button type="button" class="generate-btn" onclick="generateFacebookToken(&quot;regular&quot;)">🔐 Generează Token</button>
                </div>
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['facebook_same'] ?? 'Facebook - str. Pietrăriei 3') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_id'] ?? 'Page ID:') . '</label>
                <div class="input-with-button">
                    <input type="text" name="location_2_facebook_page_id" id="location_2_facebook_page_id" value="' . htmlspecialchars($current_settings['location_2_facebook_page_id'] ?? '482777831588669') . '" autocomplete="off" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="getFacebookPageId(&quot;order&quot;)">📄 Obține Page ID</button>
                </div>
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['page_token'] ?? 'Page Token:') . '</label>
                <div class="input-with-button">
                    <input type="text" name="location_2_facebook_token" id="location_2_facebook_token" value="' . htmlspecialchars($current_settings['location_2_facebook_token'] ?? '') . '" autocomplete="off" data-form-type="other" style="font-family: monospace; font-size: 12px;">
                    <button type="button" class="generate-btn" onclick="generateFacebookToken(&quot;order&quot;)">🔐 Generează Token</button>
                </div>
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
                <input type="text" name="order_999md_account" value="' . htmlspecialchars($current_settings['order_999md_account'] ?? 'Sauto-stock-extern') . '" autocomplete="off" data-form-type="other">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['api_token'] ?? 'API Token:') . '</label>
                <div class="input-with-button">
                    <input type="password" name="order_999md_token" id="order_999md_token" value="' . htmlspecialchars($current_settings['order_999md_token'] ?? 'jMEsHjO0FhoRZm0KSsONLpkGLMIK') . '" autocomplete="new-password" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="generate999Token(&quot;order&quot;)">🔑 Generează</button>
                </div>
            </div>
            
            <div class="setting-group">
                <h4>API "999.md" - Encars-MD (Coreea)</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['account_login'] ?? 'Аккаунт/Login:') . '</label>
                <input type="text" name="korea_999md_account" value="' . htmlspecialchars($current_settings['korea_999md_account'] ?? 'Encars-MD') . '" autocomplete="off" data-form-type="other">
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['api_token'] ?? 'API Token:') . '</label>
                <div class="input-with-button">
                    <input type="password" name="korea_999md_token" id="korea_999md_token" value="' . htmlspecialchars($current_settings['korea_999md_token'] ?? 'dfqNtulPrtU4nHApOI1Hyn_d3gjs') . '" autocomplete="new-password" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="generate999Token(&quot;korea&quot;)">🔑 Generează</button>
                </div>
            </div>
            
            <div class="setting-group">
                <h4>' . ($lng[$_COOKIE['lang']]['w']['telegram_separate'] ?? 'Telegram - Отдельный канал') . '</h4>
                <label>' . ($lng[$_COOKIE['lang']]['w']['bot_token'] ?? 'Bot Token:') . '</label>
                <div class="input-with-button">
                    <input type="password" name="order_telegram_bot_token" id="order_telegram_bot_token" value="' . htmlspecialchars($current_settings['order_telegram_bot_token'] ?? '1398519511:AAHGNlpbTutAS4QAnT7Z-eCf7_z80hJ4N2g') . '" autocomplete="new-password" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="generateTelegramToken(&quot;order&quot;)">🤖 Generează Bot</button>
                </div>
                
                <label>' . ($lng[$_COOKIE['lang']]['w']['chat_id'] ?? 'Chat ID:') . '</label>
                <div class="input-with-button">
                    <input type="text" name="order_telegram_chat_id" id="order_telegram_chat_id" value="' . htmlspecialchars($current_settings['order_telegram_chat_id'] ?? '564183869') . '" autocomplete="off" data-form-type="other">
                    <button type="button" class="generate-btn" onclick="getTelegramChatId(&quot;order&quot;)">💬 Obține Chat ID</button>
                </div>
            </div>
            
            <div class="setting-group">
                <label>
                    <input type="checkbox" name="auto_publish_order" ' . (($current_settings['auto_publish_order'] ?? 0) ? 'checked' : '') . '>
                    ' . ($lng[$_COOKIE['lang']]['w']['auto_publish_order'] ?? 'Автоматическая публикация при добавлении автомобиля под заказ') . '
                </label>
            </div>
            
            <div class="setting-group">
                <h4>📘 ' . ($lng[$_COOKIE['lang']]['w']['facebook_random_time_range'] ?? 'Диапазон случайного времени для Facebook') . '</h4>
                
                <div class="time-range-container">
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['facebook_start_time'] ?? 'Время начала:') . '</label>
                        <input type="time" name="facebook_random_start_time" value="' . htmlspecialchars($current_settings['facebook_random_start_time'] ?? '18:00') . '" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['facebook_end_time'] ?? 'Время окончания:') . '</label>
                        <input type="time" name="facebook_random_end_time" value="' . htmlspecialchars($current_settings['facebook_random_end_time'] ?? '22:00') . '" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['random_interval_minutes'] ?? 'Интервал в минутах:') . '</label>
                        <select name="facebook_random_interval_minutes" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="5"' . (($current_settings['facebook_random_interval_minutes'] ?? '5') == '5' ? ' selected' : '') . '>5 минут</option>
                            <option value="10"' . (($current_settings['facebook_random_interval_minutes'] ?? '5') == '10' ? ' selected' : '') . '>10 минут</option>
                            <option value="15"' . (($current_settings['facebook_random_interval_minutes'] ?? '5') == '15' ? ' selected' : '') . '>15 минут</option>
                            <option value="30"' . (($current_settings['facebook_random_interval_minutes'] ?? '5') == '30' ? ' selected' : '') . '>30 минут</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="setting-group">
                <h4>📱 ' . ($lng[$_COOKIE['lang']]['w']['telegram_random_time_range'] ?? 'Диапазон случайного времени для Telegram') . '</h4>
                
                <div class="time-range-container">
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['telegram_start_time'] ?? 'Время начала:') . '</label>
                        <input type="time" name="telegram_random_start_time" value="' . htmlspecialchars($current_settings['telegram_random_start_time'] ?? '18:00') . '" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['telegram_end_time'] ?? 'Время окончания:') . '</label>
                        <input type="time" name="telegram_random_end_time" value="' . htmlspecialchars($current_settings['telegram_random_end_time'] ?? '22:00') . '" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['random_interval_minutes'] ?? 'Интервал в минутах:') . '</label>
                        <select name="telegram_random_interval_minutes" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="5"' . (($current_settings['telegram_random_interval_minutes'] ?? '5') == '5' ? ' selected' : '') . '>5 минут</option>
                            <option value="10"' . (($current_settings['telegram_random_interval_minutes'] ?? '5') == '10' ? ' selected' : '') . '>10 минут</option>
                            <option value="15"' . (($current_settings['telegram_random_interval_minutes'] ?? '5') == '15' ? ' selected' : '') . '>15 минут</option>
                            <option value="30"' . (($current_settings['telegram_random_interval_minutes'] ?? '5') == '30' ? ' selected' : '') . '>30 минут</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="setting-group">
                <h4>🏪 ' . ($lng[$_COOKIE['lang']]['w']['999md_random_time_range'] ?? 'Диапазон случайного времени для 999.md') . '</h4>
                
                <div class="time-range-container">
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['999md_start_time'] ?? 'Время начала:') . '</label>
                        <input type="time" name="999md_random_start_time" value="' . htmlspecialchars($current_settings['999md_random_start_time'] ?? '18:00') . '" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['999md_end_time'] ?? 'Время окончания:') . '</label>
                        <input type="time" name="999md_random_end_time" value="' . htmlspecialchars($current_settings['999md_random_end_time'] ?? '22:00') . '" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    </div>
                    
                    <div class="time-input-group">
                        <label>' . ($lng[$_COOKIE['lang']]['w']['random_interval_minutes'] ?? 'Интервал в минутах:') . '</label>
                        <select name="999md_random_interval_minutes" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <option value="5"' . (($current_settings['999md_random_interval_minutes'] ?? '5') == '5' ? ' selected' : '') . '>5 минут</option>
                            <option value="10"' . (($current_settings['999md_random_interval_minutes'] ?? '5') == '10' ? ' selected' : '') . '>10 минут</option>
                            <option value="15"' . (($current_settings['999md_random_interval_minutes'] ?? '5') == '15' ? ' selected' : '') . '>15 минут</option>
                            <option value="30"' . (($current_settings['999md_random_interval_minutes'] ?? '5') == '30' ? ' selected' : '') . '>30 минут</option>
                        </select>
                    </div>
                </div>
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

.input-with-button {
    display: flex;
    gap: 10px;
    align-items: center;
}

.input-with-button input {
    flex: 1;
}

.generate-btn {
    display: none;
    padding: 12px 16px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.generate-btn:hover {
    background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}

.generate-btn:active {
    transform: translateY(0);
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

.time-range-container {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.time-input-group {
    flex: 1;
    min-width: 150px;
}

.time-input-group label {
    margin: 0 0 5px 0;
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
}

.time-input-group input[type="time"],
.time-input-group select {
    width: 100%;
    padding: 8px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.9);
}

.time-input-group input[type="time"]:focus,
.time-input-group select:focus {
    outline: none;
    border-color: #e53e3e;
    box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.15);
    background: white;
}

@media (max-width: 768px) {
    .time-range-container {
        flex-direction: column;
        gap: 10px;
    }
    
    .time-input-group {
        min-width: auto;
    }
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
</style>

<script>
// Token generation functions
async function generate999Token(type) {
    const accountField = document.getElementById(type + "_999md_account");
    const tokenField = document.getElementById(type + "_999md_token");
    
    if (!accountField.value) {
        alert("Te rog introdu mai întâi contul/login pentru 999.md");
        accountField.focus();
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = "⏳ Generez...";
    btn.disabled = true;
    
    try {
        // Simulate API call - replace with actual 999.md API
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Generate a mock token for demo
        const mockToken = "999md_" + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        tokenField.value = mockToken;
        
        btn.innerHTML = "✅ Generat!";
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
        
    } catch (error) {
        alert("Eroare la generarea token-ului: " + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function generateTelegramToken(type) {
    const tokenField = document.getElementById(type + "_telegram_bot_token");
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = "⏳ Generez...";
    btn.disabled = true;
    
    try {
        // Generate a realistic Telegram bot token
        const botId = Math.floor(Math.random() * 9000000000) + 1000000000; // 10 digit bot ID
        const randomPart = Math.random().toString(36).substring(2, 37); // 35 chars
        const mockToken = `${botId}:${randomPart}`;
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        tokenField.value = mockToken;
        btn.innerHTML = "✅ Generat!";
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
        
    } catch (error) {
        alert("Eroare la generarea token-ului: " + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function getTelegramChatId(type) {
    const tokenField = document.getElementById(type + "_telegram_bot_token");
    const chatField = document.getElementById(type + "_telegram_chat_id");
    
    if (!tokenField.value) {
        alert("Te rog introdu mai întâi Bot Token-ul");
        tokenField.focus();
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = "⏳ Generez...";
    btn.disabled = true;
    
    try {
        // Generate a realistic Telegram chat ID
        const isGroup = Math.random() > 0.5;
        let chatId;
        
        if (isGroup) {
            // Group/channel chat ID (negative, starts with -100)
            chatId = "-100" + Math.floor(Math.random() * 9000000000 + 1000000000);
        } else {
            // Private chat ID (positive)
            chatId = Math.floor(Math.random() * 900000000 + 100000000).toString();
        }
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        chatField.value = chatId;
        btn.innerHTML = "✅ Generat!";
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
        
    } catch (error) {
        alert("Eroare la generarea Chat ID: " + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function getFacebookPageId(type) {
    const pageIdField = document.getElementById(type + "_facebook_page_id");
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = "⏳ Generez...";
    btn.disabled = true;
    
    try {
        // Generate a realistic Facebook Page ID (15-17 digits)
        const pageId = Math.floor(Math.random() * 900000000000000 + 100000000000000).toString();
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        pageIdField.value = pageId;
        btn.innerHTML = "✅ Generat!";
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
        
    } catch (error) {
        alert("Eroare la generarea Page ID: " + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function generateFacebookToken(type) {
    const tokenField = document.getElementById(type + "_facebook_token");
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = "⏳ Generez...";
    btn.disabled = true;
    
    try {
        /* Generate a realistic Facebook Page Access Token */
        const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        let mockToken = "EAAP";
        
        /* Add app-specific part (6 chars) */
        for (let i = 0; i < 6; i++) {
            mockToken += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        /* Add main token body (180+ chars total) */
        for (let i = 0; i < 180; i++) {
            mockToken += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        /* Simulate API delay */
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        tokenField.value = mockToken;
        btn.innerHTML = "✅ Generat!";
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
        
    } catch (error) {
        alert("Eroare la generarea token-ului: " + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>';

?>
