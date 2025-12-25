<?php
// Called from ajax.php via fn=ai_generate

$lang = __post('lang') ?: 'ro';
$fromForm = __post('from_form') == '1';

$openRouterApiKey = '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';

if ($fromForm) {
    $car = [
        'br_nm' => __post('brand') ?: '',
        'mo_nm' => __post('model') ?: '',
        'yr' => __post('year') ?: '',
        'mlg' => __post('mileage') ?: '',
        'vol' => __post('volume') ?: '',
        'hp' => __post('hp') ?: '',
        'fl' => __post('fuel') ?: '',
        'tra' => __post('transmission') ?: '',
        'wd' => __post('wheelDrive') ?: '',
        'clr' => __post('color') ?: '',
        'prc' => __post('price') ?: '',
        'cur' => __post('currency') ?: ''
    ];
} else {
    $carId = intval(__post('car_id'));
    if ($carId <= 0) {
        $returnIt = ['success' => false, 'error' => 'Invalid car ID'];
        return;
    }
    
    $table = $prefx . '_car_ctlg';
    
    try {
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $carId]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$car) {
            $returnIt = ['success' => false, 'error' => 'Car not found'];
            return;
        }
    } catch (PDOException $e) {
        $returnIt = ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        return;
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
    $returnIt = ['success' => false, 'error' => 'cURL error: ' . $curlError];
    return;
}

if ($httpCode !== 200) {
    $returnIt = ['success' => false, 'error' => 'API error (HTTP ' . $httpCode . '): ' . $response];
    return;
}

$responseData = json_decode($response, true);

if (!isset($responseData['choices'][0]['message']['content'])) {
    $returnIt = ['success' => false, 'error' => 'Invalid API response', 'raw' => $responseData];
    return;
}

$generatedHtml = $responseData['choices'][0]['message']['content'];
$generatedHtml = preg_replace('/^```html?\s*/i', '', $generatedHtml);
$generatedHtml = preg_replace('/\s*```$/i', '', $generatedHtml);
$generatedHtml = trim($generatedHtml);

$returnIt = ['success' => true, 'html' => $generatedHtml, 'lang' => $lang];
