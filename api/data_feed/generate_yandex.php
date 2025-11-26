<?php
/**
 * Yandex YML Feed Generator - Web Version
 * 
 * URL: https://www.sauto.md/api/data_feed/generate_yandex.php?key=sauto2025
 */

// Simple password protection
$key = $_GET['key'] ?? '';
if ($key !== 'sauto2025') {
    die('Access denied');
}

// Set timezone
date_default_timezone_set('Europe/Chisinau');

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Yandex YML Generator</title></head><body>";
echo "<h1>Yandex YML Feed Generator</h1>";
echo "<p>Started: " . date('Y-m-d H:i:s') . "</p>";

try {
    // Configuration
    $inputFile = __DIR__ . '/df_cars.xml';
    $outputFile = __DIR__ . '/df_cars_yandex.yml';
    $shopName = 'SAUTO.md';
    $shopUrl = 'https://www.sauto.md';
    
    // Load existing XML feed
    if (!file_exists($inputFile)) {
        throw new Exception("Input XML file not found: {$inputFile}");
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
    
    $totalCars = 0;
    
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
        
        // Picture
        $offer->appendChild($yml->createElement('picture', htmlspecialchars($imageLink)));
        
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
        $totalCars++;
    }
    
    // Save YML file
    $yml->save($outputFile);
    
    echo "<p>✅ <strong>Yandex YML feed generated successfully!</strong></p>";
    echo "<p>📊 Total cars: <strong>{$totalCars}</strong></p>";
    echo "<p>🔗 <a href='df_cars_yandex.yml' download>Download YML Feed</a></p>";
    
    // Display XML content
    echo "<hr>";
    echo "<h3>Generated YML Content:</h3>";
    echo "<pre style='background:#f5f5f5; padding:15px; border:1px solid #ddd; overflow:auto; max-height:600px;'>";
    echo htmlspecialchars($yml->saveXML());
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
