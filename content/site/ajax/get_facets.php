<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('_DOIT', 1);
define('_DEFAULT', 'content/default');

require_once $_SERVER['DOCUMENT_ROOT'] . '/environment.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/' . _DEFAULT . '/defines.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/' . _DEFAULT . '/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/' . _DEFAULT . '/config.php';

$sql_host = SQL_HOST;
$sql_db = SQL_DB;
$sql_user = SQL_USER;
$sql_pass = SQL_PASS;
$sql_charset = defined('SQL_CHARSET') ? SQL_CHARSET : 'utf8mb4';

$dsn = "mysql:host=$sql_host;dbname=$sql_db;charset=$sql_charset";
$opt = [
    PDO::ATTR_PERSISTENT         => false,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
];

require_once $_SERVER['DOCUMENT_ROOT'] . '/App/Services/FacetService.php';

use App\Services\FacetService;

try {
    $db = new PDO($dsn, $sql_user, $sql_pass, $opt);

    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    
    if (empty($requestData)) {
        $requestData = array_merge($_GET, $_POST);
    }
    
    $filters = [];
    $filterKeys = ['br', 'mo', 'gr', 'bt', 'fl', 'tra', 'wd', 'clr', 'yr', 'mlg', 'vol', 'prc', 'sts'];
    
    foreach ($filterKeys as $key) {
        if (isset($requestData[$key]) && $requestData[$key] !== '') {
            $filters[$key] = $requestData[$key];
        }
    }
    
    $catalogType = 'all';
    if (isset($requestData['catalog_type'])) {
        $catalogType = $requestData['catalog_type'];
    } elseif (isset($requestData['page'])) {
        if ($requestData['page'] === 'ordercars') {
            $catalogType = 'on_order';
        } elseif ($requestData['page'] === 'cars') {
            $catalogType = 'in_stock';
        }
    }
    
    $facetService = new FacetService($db, $prefx);
    
    $facets = $facetService->getFacets($filters, $catalogType);
    
    // Get translations - define all possible values with their translations
    $lang = isset($requestData['lang']) ? $requestData['lang'] : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro');
    
    // Complete translations for all filter values
    $allTranslations = [
        'ro' => [
            'bt' => ['sdn'=>'Sedan', 'suv'=>'SUV', 'hbk'=>'Hatchback', 'unv'=>'Universal', 'cup'=>'Coupe', 'crv'=>'Crossover', 'mnv'=>'Minivan', 'pkp'=>'Pickup', 'van'=>'Furgon', 'mbs'=>'Microbus', 'cbr'=>'Cabriolet', 'cmb'=>'Combi', 'rod'=>'Roadster', 'frg'=>'Frigider', 'crr'=>'Purtător'],
            'fl' => ['gsl'=>'Benzină', 'gmn'=>'Benzină / Gaz (metan)', 'gpn'=>'Benzină / Gaz (propan)', 'hbd'=>'Hybrid', 'dsl'=>'Diesel', 'pih'=>'Plug-in Hybrid', 'elc'=>'Electricitate', 'gas'=>'Gaz'],
            'tra' => ['tpt'=>'Tiptronic', 'atm'=>'Automată', 'mnl'=>'Mecanică', 'rbt'=>'Robotizată', 'vrr'=>'Variator'],
            'wd' => ['44'=>'4x4', 're'=>'Din spate', 'fr'=>'Din față'],
            'clr' => ['l_grn'=>'Verde deschis', 'blu'=>'Albastru', 'brn'=>'Brun', 'cmn'=>'Purpuriu', 'cml'=>'Cameleon', 'bge'=>'Bej', 'wht'=>'Alb', 'vns'=>'Roșu visiniu', 'azr'=>'Azuriu', 'ylw'=>'Galben', 'grn'=>'Verde', 'gld'=>'Auriu', 'red'=>'Roșu', 'orn'=>'Portocaliu', 'pnk'=>'Roz', 'slv'=>'Argintiu', 'gra'=>'Gri', 'd_grn'=>'Verde închis', 'prp'=>'Violet', 'blk'=>'Negru', 'wap'=>'Asfalt umed', 'wat'=>'Asfalt umed', 'snd'=>'Nisip'],
            'gr' => ['car'=>'Autoturisme', 'com'=>'Autocomerciale']
        ],
        'ru' => [
            'bt' => ['sdn'=>'Седан', 'suv'=>'Внедорожник', 'hbk'=>'Хэтчбэк', 'unv'=>'Универсал', 'cup'=>'Купе', 'crv'=>'Кроссовер', 'mnv'=>'Минивэн', 'pkp'=>'Пикап', 'van'=>'Фургон', 'mbs'=>'Микроавтобус', 'cbr'=>'Кабриолет', 'cmb'=>'Комби', 'rod'=>'Родстер', 'frg'=>'Холодильник', 'crr'=>'Шасси'],
            'fl' => ['gsl'=>'Бензин', 'gmn'=>'Бензин / Газ (метан)', 'gpn'=>'Бензин / Газ (пропан)', 'hbd'=>'Гибрид', 'dsl'=>'Дизель', 'pih'=>'Plug-in Гибрид', 'elc'=>'Электричество', 'gas'=>'Газ'],
            'tra' => ['tpt'=>'Типтроник', 'atm'=>'Автомат', 'mnl'=>'Механика', 'rbt'=>'Робот', 'vrr'=>'Вариатор'],
            'wd' => ['44'=>'4x4', 're'=>'Задний', 'fr'=>'Передний'],
            'clr' => ['l_grn'=>'Салатовый', 'blu'=>'Синий', 'brn'=>'Коричневый', 'cmn'=>'Малиновый', 'cml'=>'Хамелеон', 'bge'=>'Бежевый', 'wht'=>'Белый', 'vns'=>'Бордовый', 'azr'=>'Лазурный', 'ylw'=>'Жёлтый', 'grn'=>'Зелёный', 'gld'=>'Золотой', 'red'=>'Красный', 'orn'=>'Оранжевый', 'pnk'=>'Розовый', 'slv'=>'Серебристый', 'gra'=>'Серый', 'd_grn'=>'Тёмно-зелёный', 'prp'=>'Фиолетовый', 'blk'=>'Чёрный', 'wap'=>'Мокрый асфальт', 'wat'=>'Мокрый асфальт', 'snd'=>'Песочный'],
            'gr' => ['car'=>'Легковые', 'com'=>'Коммерческие']
        ],
        'en' => [
            'bt' => ['sdn'=>'Sedan', 'suv'=>'SUV', 'hbk'=>'Hatchback', 'unv'=>'Station wagon', 'cup'=>'Coupe', 'crv'=>'Crossover', 'mnv'=>'Minivan', 'pkp'=>'Truck', 'van'=>'Full size van', 'mbs'=>'Full size van', 'cbr'=>'Convertible', 'cmb'=>'Combi', 'rod'=>'Roadster', 'frg'=>'Refrigerator', 'crr'=>'Chassis'],
            'fl' => ['gsl'=>'Gasoline', 'gmn'=>'Gasoline / Gas (methane)', 'gpn'=>'Gasoline / Gas (propane)', 'hbd'=>'Hybrid', 'dsl'=>'Diesel', 'pih'=>'Plug-in Hybrid', 'elc'=>'Electric', 'gas'=>'Gas'],
            'tra' => ['tpt'=>'Tiptronic', 'atm'=>'Automatic', 'mnl'=>'Manual', 'rbt'=>'Robot', 'vrr'=>'Variator'],
            'wd' => ['44'=>'AWD', 're'=>'RWD', 'fr'=>'FWD'],
            'clr' => ['l_grn'=>'Light Green', 'blu'=>'Blue', 'brn'=>'Brown', 'cmn'=>'Crimson', 'cml'=>'Chameleon', 'bge'=>'Beige', 'wht'=>'White', 'vns'=>'Vinous', 'azr'=>'Azure', 'ylw'=>'Yellow', 'grn'=>'Green', 'gld'=>'Gold', 'red'=>'Red', 'orn'=>'Orange', 'pnk'=>'Pink', 'slv'=>'Silver', 'gra'=>'Gray', 'd_grn'=>'Dark Green', 'prp'=>'Purple', 'blk'=>'Black', 'wap'=>'Wet Asphalt', 'wat'=>'Wet Asphalt', 'snd'=>'Sand'],
            'gr' => ['car'=>'Cars', 'com'=>'Commercial']
        ]
    ];
    
    $currentLangTranslations = $allTranslations[$lang] ?? $allTranslations['ro'];
    
    $facets['translations'] = [
        'bt' => $currentLangTranslations['bt'],
        'fl' => $currentLangTranslations['fl'],
        'tra' => $currentLangTranslations['tra'],
        'wd' => $currentLangTranslations['wd'],
        'clr' => $currentLangTranslations['clr'],
        'gr' => $currentLangTranslations['gr']
    ];
    
    $facets['validation'] = [
        'range_filters' => ['yr', 'mlg', 'vol', 'prc', 'sts'],
        'select_filters' => ['br', 'mo', 'gr', 'bt', 'fl', 'tra', 'wd', 'clr']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $facets,
        'filters_applied' => $filters,
        'catalog_type' => $catalogType
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
