<?php
/**
 * Yandex YML Feed Generator
 * Direct XML output without HTML wrapper
 * 
 * URL: https://www.sauto.md/api/data_feed/df_cars_yandex.php?key=sauto2025
 */

// Simple password protection
$key = $_GET['key'] ?? '';
if ($key !== 'sauto2025') {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}

// Set timezone
date_default_timezone_set('Europe/Chisinau');

// Output XML directly
header('Content-Type: text/xml; charset=utf-8');

try {
    // Configuration
    $inputFile = __DIR__ . '/df_cars.xml';
    $shopName = 'SAUTO.md';
    $shopUrl = 'https://www.sauto.md';
    
    // Load existing XML feed
    if (!file_exists($inputFile)) {
        throw new Exception("Input XML file not found");
    }
    
    $xml = simplexml_load_file($inputFile);
    
    if (!$xml) {
        throw new Exception("Failed to parse XML file");
    }
    
    // Start building YML
    $yml = new DOMDocument('1.0', 'UTF-8');
    $yml->formatOutput = true;
    
    // Root element
    $ymlCatalog = $yml->createElement('yml_catalog');
    $ymlCatalog->setAttribute('date', date('Y-m-d H:i'));
    $yml->appendChild($ymlCatalog);
    
    // Shop element
    $shop = $yml->createElement('shop');
    $ymlCatalog->appendChild($shop);
    
    // Shop info
    $shop->appendChild($yml->createElement('name', htmlspecialchars($shopName)));
    $shop->appendChild($yml->createElement('company', htmlspecialchars($shopName)));
    $shop->appendChild($yml->createElement('url', $shopUrl));
    
    // Currencies
    $currencies = $yml->createElement('currencies');
    $shop->appendChild($currencies);
    
    $currency = $yml->createElement('currency');
    $currency->setAttribute('id', 'EUR');
    $currency->setAttribute('rate', '1');
    $currencies->appendChild($currency);
    
    // Categories
    $categories = $yml->createElement('categories');
    $shop->appendChild($categories);
    
    $categoryMap = [];
    $categoryId = 1;
    
    // Offers
    $offers = $yml->createElement('offers');
    $shop->appendChild($offers);
    
    // Register Google namespace
    $xml->registerXPathNamespace('g', 'http://base.google.com/ns/1.0');
    
    foreach ($xml->channel->item as $item) {
        $g = $item->children('http://base.google.com/ns/1.0');
        
        // Extract data
        $carId = str_replace('c', '', (string)$g->id);
        $brand = (string)$g->brand;
        $model = (string)$g->model;
        $year = (string)$g->year;
        $price = (string)$g->price;
        $priceValue = preg_replace('/[^0-9]/', '', $price);
        $imageLink = (string)$g->image_link;
        $link = (string)$g->link;
        $color = (string)$g->color;
        $bodyStyle = (string)$g->body_style;
        $engine = (string)$g->engine;
        $mileage = (string)$g->mileage;
        
        // Collect all images (main + additional, max 10 for Yandex)
        $allImages = [];
        if (!empty($imageLink)) {
            $allImages[] = $imageLink;
        }
        foreach ($g->additional_image_link as $additionalImage) {
            if (count($allImages) < 10) {
                $allImages[] = (string)$additionalImage;
            }
        }
        
        // Get transmission and drivetrain from product_detail
        $transmission = '';
        $drivetrain = '';
        
        foreach ($g->product_detail as $detail) {
            $attrName = (string)$detail->children('http://base.google.com/ns/1.0')->attribute_name;
            $attrValue = (string)$detail->children('http://base.google.com/ns/1.0')->attribute_value;
            
            if ($attrName === 'Transmission') {
                $transmission = $attrValue;
            } elseif ($attrName === 'Drivetrain') {
                $drivetrain = $attrValue;
            }
        }
        
        // Create category if not exists
        if (!isset($categoryMap[$brand])) {
            $categoryMap[$brand] = $categoryId;
            $category = $yml->createElement('category', htmlspecialchars($brand));
            $category->setAttribute('id', $categoryId);
            $categories->appendChild($category);
            $categoryId++;
        }
        
        // Create offer
        $offer = $yml->createElement('offer');
        $offer->setAttribute('id', $carId);
        $offer->setAttribute('available', 'true');
        
        // Basic fields
        $offer->appendChild($yml->createElement('url', htmlspecialchars($link)));
        $offer->appendChild($yml->createElement('price', $priceValue));
        $offer->appendChild($yml->createElement('currencyId', 'EUR'));
        $offer->appendChild($yml->createElement('categoryId', $categoryMap[$brand]));
        
        // Pictures (all images, max 10 per Yandex specification)
        foreach ($allImages as $image) {
            $offer->appendChild($yml->createElement('picture', htmlspecialchars($image)));
        }
        
        // Name/Title
        $title = $year . ' ' . $brand . ' ' . $model;
        $offer->appendChild($yml->createElement('name', htmlspecialchars($title)));
        
        // Description
        $description = $year . ' ' . $color . ' ' . $brand . ' ' . $model . 
            ' [' . $engine . ' ' . $transmission . ' ' . $drivetrain . '] ' . $mileage;
        $offer->appendChild($yml->createElement('description', htmlspecialchars($description)));
        
        // Vehicle-specific params
        $params = [
            'Марка' => $brand,
            'Модель' => $model,
            'Год' => $year,
            'Пробег' => $mileage,
            'Тип кузова' => $bodyStyle,
            'Топливо' => $engine,
            'Коробка передач' => $transmission,
            'Привод' => $drivetrain,
            'Цвет' => $color
        ];
        
        foreach ($params as $paramName => $paramValue) {
            if (!empty($paramValue)) {
                $param = $yml->createElement('param', htmlspecialchars($paramValue));
                $param->setAttribute('name', $paramName);
                $offer->appendChild($param);
            }
        }
        
        $offers->appendChild($offer);
    }
    
    // Save XML to static .yml file
    $outputFile = __DIR__ . '/df_cars_yandex.yml';
    $xmlContent = $yml->saveXML();
    file_put_contents($outputFile, $xmlContent);
    
    // Output XML directly
    echo $xmlContent;
    
} catch (Exception $e) {
    // Even errors should be in XML format
    header('HTTP/1.0 500 Internal Server Error');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<error>' . htmlspecialchars($e->getMessage()) . '</error>';
}
?>
