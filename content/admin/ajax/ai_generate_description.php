<?php
defined('_DOIT') or define('_DOIT', true);

require_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/db.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

$lang = isset($_POST['lang']) ? $_POST['lang'] : 'ro';
$fromForm = isset($_POST['from_form']) && $_POST['from_form'] == '1';

$openRouterApiKey = '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';

if ($fromForm) {
    $car = [
        'br_nm' => $_POST['brand'] ?? '',
        'mo_nm' => $_POST['model'] ?? '',
        'yr' => $_POST['year'] ?? '',
        'mlg' => $_POST['mileage'] ?? '',
        'vol' => $_POST['volume'] ?? '',
        'hp' => $_POST['hp'] ?? '',
        'fl' => $_POST['fuel'] ?? '',
        'tra' => $_POST['transmission'] ?? '',
        'wd' => $_POST['wheelDrive'] ?? '',
        'clr' => $_POST['color'] ?? '',
        'prc' => $_POST['price'] ?? '',
        'cur' => $_POST['currency'] ?? ''
    ];
} else {
    $carId = isset($_POST['car_id']) ? intval($_POST['car_id']) : 0;
    if ($carId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid car ID']);
        exit;
    }
    
    global $db, $prefx;
    $table = $prefx . '_car_ctlg';
    
    try {
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $carId]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$car) {
            echo json_encode(['success' => false, 'error' => 'Car not found']);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

$langNames = ['ro' => 'română', 'ru' => 'rusă', 'en' => 'engleză'];
$langName = $langNames[$lang] ?? 'română';

$prompt = "Generează o descriere HTML profesională pentru acest automobil în limba {$langName}.

Date despre mașină:
- Marcă: {$car['br_nm']}
- Model: {$car['mo_nm']}
- An: {$car['yr']}
- Kilometraj: {$car['mlg']} km
- Volum motor: {$car['vol']} cm³
- Putere: {$car['hp']} CP
- Combustibil: {$car['fl']}
- Transmisie: {$car['tra']}
- Tracțiune: {$car['wd']}
- Culoare: {$car['clr']}
- Preț: {$car['prc']} {$car['cur']}

Cerințe pentru HTML:
1. Folosește tag-uri HTML semantice (h2, h3, p, ul, li)
2. Creează secțiuni: 'Dotări' (equipment/features) și 'Descriere' (description)
3. Secțiunea 'Dotări' trebuie să aibă un h2 sau h3 cu cuvântul 'Dotări' (sau echivalent în limba cerută)
4. Adaugă emoji-uri relevante pentru fiecare secțiune
5. Fii descriptiv și profesional
6. NU include tag-uri <html>, <head>, <body> - doar conținutul
7. Folosește clase CSS simple dacă e necesar

Returnează DOAR codul HTML, fără explicații.";

$apiUrl = "https://openrouter.ai/api/v1/chat/completions";

$requestData = [
    'model' => 'google/gemini-2.0-flash-exp:free',
    'messages' => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.7,
    'max_tokens' => 2048
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $openRouterApiKey,
    'HTTP-Referer: https://sauto.md',
    'X-Title: Sauto Car Description Generator'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'cURL error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'error' => 'API error (HTTP ' . $httpCode . '): ' . $response]);
    exit;
}

$responseData = json_decode($response, true);

if (!isset($responseData['choices'][0]['message']['content'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid API response', 'raw' => $responseData]);
    exit;
}

$generatedHtml = $responseData['choices'][0]['message']['content'];
$generatedHtml = preg_replace('/^```html?\s*/i', '', $generatedHtml);
$generatedHtml = preg_replace('/\s*```$/i', '', $generatedHtml);
$generatedHtml = trim($generatedHtml);

echo json_encode(['success' => true, 'html' => $generatedHtml, 'lang' => $lang]);
