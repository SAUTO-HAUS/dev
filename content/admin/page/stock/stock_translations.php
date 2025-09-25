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
    'second_branch' => 'Filiala secundară',
    'branch_calea' => 'str. Calea Moşilor 11',
    'branch_pietrariei' => 'str. Pietrăriei 3',
    'no_location' => 'Fără Locație',
    'total_general' => 'TOTAL GENERAL',
    'table_brand' => 'Marcă',
    'table_total' => 'Total',
    'table_main_branch' => 'Cantitate<br>(filiala principală)',
    'table_pruncul_branch' => 'Cantitate<br>(filiala "Pruncul")',
    'total_brands' => 'Total mărci',
    'main_active' => 'Osn. active',
    'main_inactive' => 'Osn. neactive',
    'branch_active' => 'Filială active',
    'branch_inactive' => 'Filială neactive',
    'show_all_ads' => 'Arată toate anunțurile',
    'stock_extern' => 'Stock extern',
    'expand_brand' => 'Extinde marca pentru a vedea modelele',
    'collapse_brand' => 'Restrânge marca',
    'scrie' => 'Scrie'
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
    'second_branch' => 'Второй филиал',
    'branch_calea' => 'ул. Каля Мошилор 11',
    'branch_pietrariei' => 'ул. Пьетрарией 3',
    'no_location' => 'Без местоположения',
    'total_general' => 'ОБЩИЙ ИТОГ',
    'table_brand' => 'Марка',
    'table_total' => 'Всего',
    'table_main_branch' => 'Количество<br>(основной филиал)',
    'table_pruncul_branch' => 'Количество<br>(филиал "Прункул")',
    'total_brands' => 'Всего брендов',
    'main_active' => 'Осн. активные',
    'main_inactive' => 'Осн. неактивные',
    'branch_active' => 'Филиал активные',
    'branch_inactive' => 'Филиал неактивные',
    'show_all_ads' => 'Показать все объявления',
    'stock_extern' => 'Stock extern',
    'expand_brand' => 'Развернуть марку для просмотра моделей',
    'collapse_brand' => 'Свернуть марку',
    'scrie' => 'Написать'
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
    'second_branch' => 'Second Branch',
    'branch_calea' => 'str. Calea Moşilor 11',
    'branch_pietrariei' => 'str. Pietrăriei 3',
    'no_location' => 'No Location',
    'total_general' => 'GRAND TOTAL',
    'table_brand' => 'Brand',
    'table_total' => 'Total',
    'table_main_branch' => 'Quantity<br>(main branch)',
    'table_pruncul_branch' => 'Quantity<br>(Pruncul branch)',
    'total_brands' => 'Total Brands',
    'main_active' => 'Main Active',
    'main_inactive' => 'Main Inactive',
    'branch_active' => 'Branch Active',
    'branch_inactive' => 'Branch Inactive',
    'show_all_ads' => 'Show All Ads',
    'stock_extern' => 'Stock extern',
    'expand_brand' => 'Expand brand to view models',
    'collapse_brand' => 'Collapse brand',
    'scrie' => 'Write'
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
