<?php
// Stock module translations
// Separate translation file for stock management module

// Romanian translations
$stock_lang_ro = array(
    'stock' => 'Stoc Sauto',
    'stock_report' => 'Raport Stoc',
    'stock_summary' => 'Rezumat Stoc',
    'location' => 'Locație',
    'brand' => 'Marcă',
    'model' => 'Model',
    'quantity' => 'Cantitate',
    'total' => 'Total',
    'total_cars' => 'Total Mașini',
    'branch_calea' => 'str. Calea Moşilor 11',
    'branch_pietrariei' => 'str. Pietrăriei 3',
    'no_location' => 'Fără Locație',
    'total_general' => 'TOTAL GENERAL'
);

// Russian translations
$stock_lang_ru = array(
    'stock' => 'Склад Sauto',
    'stock_report' => 'Отчет по складу',
    'stock_summary' => 'Сводка по складу',
    'location' => 'Местоположение',
    'brand' => 'Марка',
    'model' => 'Модель',
    'quantity' => 'Количество',
    'total' => 'Итого',
    'total_cars' => 'Итого Машин',
    'branch_calea' => 'ул. Каля Мошилор 11',
    'branch_pietrariei' => 'ул. Пьетрарией 3',
    'no_location' => 'Без местоположения',
    'total_general' => 'ОБЩИЙ ИТОГ'
);

// English translations
$stock_lang_en = array(
    'stock' => 'Stock Sauto',
    'stock_report' => 'Stock Report',
    'stock_summary' => 'Stock Summary',
    'location' => 'Location',
    'brand' => 'Brand',
    'model' => 'Model',
    'quantity' => 'Quantity',
    'total' => 'Total',
    'total_cars' => 'Total Cars',
    'branch_calea' => 'str. Calea Moşilor 11',
    'branch_pietrariei' => 'str. Pietrăriei 3',
    'no_location' => 'No Location',
    'total_general' => 'GRAND TOTAL'
);

// Function to get stock translations based on language
function get_stock_lang($lang = 'ro') {
    global $stock_lang_ro, $stock_lang_ru, $stock_lang_en;
    
    switch($lang) {
        case 'ru':
            return $stock_lang_ru;
        case 'en':
            return $stock_lang_en;
        case 'ro':
        default:
            return $stock_lang_ro;
    }
}

// Auto-detect language and set stock_lang variable
if (isset($_COOKIE['lang'])) {
    $stock_lang = get_stock_lang($_COOKIE['lang']);
} else {
    $stock_lang = get_stock_lang('ro'); // Default to Romanian
}
?>
