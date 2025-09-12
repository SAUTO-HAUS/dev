<?php
// Stock module translations
// Separate translation file for stock management module

// Romanian translations
$stock_lang_ro = array(
    'stock' => 'Stoc Sauto',
    'stock_report' => 'Distribuție pe mărci',
    'stock_summary' => 'Rezumat pe depozit',
    'location' => 'Locație',
    'brand' => 'Marcă',
    'model' => 'Model',
    'quantity' => 'Cantitate',
    'total' => 'Total',
    'total_cars' => 'Total automobile',
    'main_branch' => 'Filiala principală',
    'pruntul_branch' => 'Filiala "Pruncul"',
    'branch_calea' => 'str. Calea Moşilor 11',
    'branch_pietrariei' => 'str. Pietrăriei 3',
    'no_location' => 'Fără Locație',
    'total_general' => 'TOTAL GENERAL',
    'table_brand' => 'Marcă',
    'table_total' => 'Cantitate total',
    'table_main_branch' => 'Cantitate<br>(filiala principală)',
    'table_pruncul_branch' => 'Cantitate<br>(filiala "Pruncul")',
    'total_brands' => 'Total mărci'
);

// Russian translations
$stock_lang_ru = array(
    'stock' => 'Склад Sauto',
    'stock_report' => 'Распределение по брендам',
    'stock_summary' => 'Сводка по складу',
    'location' => 'Местоположение',
    'brand' => 'Марка',
    'model' => 'Модель',
    'quantity' => 'Количество',
    'total' => 'Итого',
    'total_cars' => 'Всего автомобилей',
    'main_branch' => 'Основной филиал',
    'pruntul_branch' => 'Филиал "Прункул"',
    'branch_calea' => 'ул. Каля Мошилор 11',
    'branch_pietrariei' => 'ул. Пьетрарией 3',
    'no_location' => 'Без местоположения',
    'total_general' => 'ОБЩИЙ ИТОГ',
    'table_brand' => 'Марка',
    'table_total' => 'Количество всего',
    'table_main_branch' => 'Количество<br>(основной филиал)',
    'table_pruncul_branch' => 'Количество<br>(филиал "Прункул")',
    'total_brands' => 'Всего брендов'
);

// English translations
$stock_lang_en = array(
    'stock' => 'Stock Sauto',
    'stock_report' => 'Brand Distribution',
    'stock_summary' => 'Warehouse Summary',
    'location' => 'Location',
    'brand' => 'Brand',
    'model' => 'Model',
    'quantity' => 'Quantity',
    'total' => 'Total',
    'total_cars' => 'Total Cars',
    'main_branch' => 'Main Branch',
    'pruntul_branch' => 'Pruncul Branch',
    'branch_calea' => 'str. Calea Moşilor 11',
    'branch_pietrariei' => 'str. Pietrăriei 3',
    'no_location' => 'No Location',
    'total_general' => 'GRAND TOTAL',
    'table_brand' => 'Brand',
    'table_total' => 'Total Quantity',
    'table_main_branch' => 'Quantity<br>(main branch)',
    'table_pruncul_branch' => 'Quantity<br>(Pruncul branch)',
    'total_brands' => 'Total Brands'
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
