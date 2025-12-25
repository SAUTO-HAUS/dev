<?php
// Called from ajax.php via fn=ai_generate

$lang = __post('lang') ?: 'ro';
$fromForm = __post('from_form') == '1';

$groqApiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';

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

$prompt = "Generate professional HTML descriptions for this car in 3 languages: Romanian, Russian, and English.

Car data:
- Brand: {$car['br_nm']}
- Model: {$car['mo_nm']}
- Year: {$car['yr']}
- Mileage: {$car['mlg']} km
- Engine volume: {$car['vol']} cm³
- Power: {$car['hp']} HP
- Fuel: {$car['fl']}
- Transmission: {$car['tra']}
- Drive: {$car['wd']}
- Color: {$car['clr']}
- Price: {$car['prc']} {$car['cur']}

HTML requirements:
1. Use semantic HTML tags (h2, h3, p, ul, li)
2. Create sections: 'Equipment' and 'Description' (translated to each language)
3. Add relevant emojis
4. Be descriptive and professional
5. Do NOT include <html>, <head>, <body> tags

IMPORTANT: Return the response EXACTLY in this JSON format (no other text):
{\"ro\": \"<HTML in Romanian>\", \"ru\": \"<HTML in Russian>\", \"en\": \"<HTML in English>\"}";

$apiUrl = "https://api.groq.com/openai/v1/chat/completions";

$requestData = [
    'model' => 'llama-3.3-70b-versatile',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a JSON generator. Always respond with valid JSON only, no markdown, no explanations.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.5,
    'max_tokens' => 4096,
    'response_format' => ['type' => 'json_object']
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $groqApiKey
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

$generatedContent = $responseData['choices'][0]['message']['content'];
$generatedContent = preg_replace('/^```json?\s*/i', '', $generatedContent);
$generatedContent = preg_replace('/\s*```$/i', '', $generatedContent);
$generatedContent = trim($generatedContent);

$htmlData = json_decode($generatedContent, true);

if (!$htmlData || !isset($htmlData['ro'])) {
    $returnIt = ['success' => false, 'error' => 'Invalid JSON response', 'raw' => $generatedContent];
    return;
}

$returnIt = ['success' => true, 'html_ro' => $htmlData['ro'] ?? '', 'html_ru' => $htmlData['ru'] ?? '', 'html_en' => $htmlData['en'] ?? ''];
