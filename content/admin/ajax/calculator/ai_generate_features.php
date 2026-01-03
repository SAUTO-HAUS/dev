<?php
defined('_DOIT') or die('Restricted access');

ignore_user_abort(true);
set_time_limit(180);

$returnIt = ['success' => false, 'error' => 'Unknown error'];

$brand = isset($_POST['brand']) ? $_POST['brand'] : '';
$carModel = isset($_POST['model']) ? $_POST['model'] : '';
$year = isset($_POST['year']) ? $_POST['year'] : '';
$fuel_type = isset($_POST['fuel_type']) ? $_POST['fuel_type'] : '';
$bodywork = isset($_POST['bodywork']) ? $_POST['bodywork'] : '';

if (empty($brand) || empty($carModel)) {
    $returnIt = ['success' => false, 'error' => 'Missing brand or model'];
    return;
}

$groqApiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
$openaiApiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';

// Load AI settings from database
$aiSettings = [];
try {
    $stmtSettings = $db->query("SELECT setting_key, setting_value FROM {$prefx}_ai_settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $aiSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table doesn't exist yet, use defaults
}

$carData = "Car data:
- Brand: {$brand}
- Model: {$carModel}
- Year: {$year}
- Fuel type: {$fuel_type}
- Body type: {$bodywork}";

$prompt = "Based on the car data below, generate a list of typical SAFETY features (Siguranță/Безопасность) and COMFORT features (Confort/Комфорт) that this car model typically has.

{$carData}

Generate realistic features based on the car's year and model. Include 5-8 items for each category.

IMPORTANT: Return EXACTLY in this JSON format:
{
  \"safety\": [\"Feature 1\", \"Feature 2\", ...],
  \"comfort\": [\"Feature 1\", \"Feature 2\", ...]
}

Use Romanian language for the features. Examples:
Safety: Sistem ABS, Airbag-uri frontale, Control stabilitate ESP, Senzori parcare, etc.
Comfort: Aer conditionat, Servodirectie, Geamuri electrice, Incalzire scaune, etc.";

// Choose API based on settings
$aiProvider = $aiSettings['ai_provider'] ?? 'openai';

if ($aiProvider === 'openai' && !empty($openaiApiKey)) {
    $apiUrl = "https://api.openai.com/v1/chat/completions";
    $apiKey = $openaiApiKey;
    $aiModel = $aiSettings['openai_model'] ?? 'gpt-4o-mini';
} elseif ($aiProvider === 'groq' && !empty($groqApiKey)) {
    $apiUrl = "https://api.groq.com/openai/v1/chat/completions";
    $apiKey = $groqApiKey;
    $aiModel = $aiSettings['groq_model'] ?? 'llama-3.3-70b-versatile';
} elseif (!empty($openaiApiKey)) {
    $apiUrl = "https://api.openai.com/v1/chat/completions";
    $apiKey = $openaiApiKey;
    $aiModel = 'gpt-4o-mini';
} else {
    $apiUrl = "https://api.groq.com/openai/v1/chat/completions";
    $apiKey = $groqApiKey;
    $aiModel = 'llama-3.3-70b-versatile';
}

if (empty($apiKey)) {
    $returnIt = ['success' => false, 'error' => 'No API key configured'];
    return;
}

$requestData = [
    'model' => $aiModel,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a car expert assistant. Generate accurate car features based on the model and year.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.7
];

// Use max_completion_tokens for newer OpenAI models, max_tokens for others
if (strpos($aiModel, 'gpt-4o') !== false || strpos($aiModel, 'gpt-5') !== false || strpos($aiModel, 'o1') !== false || strpos($aiModel, 'o3') !== false) {
    $requestData['max_completion_tokens'] = 1024;
} else {
    $requestData['max_tokens'] = 1024;
}

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlError) {
    $returnIt = ['success' => false, 'error' => 'Curl error: ' . $curlError];
    return;
}

if ($httpCode !== 200) {
    $returnIt = ['success' => false, 'error' => 'API error: ' . $httpCode . ' - ' . $response];
    return;
}

$responseData = json_decode($response, true);
if (!$responseData) {
    $returnIt = ['success' => false, 'error' => 'Invalid API response'];
    return;
}

$content = $responseData['choices'][0]['message']['content'] ?? '';

if (empty($content)) {
    $returnIt = ['success' => false, 'error' => 'Empty content from API'];
    return;
}

// Extract JSON from response
$jsonStart = strpos($content, '{');
$jsonEnd = strrpos($content, '}');
if ($jsonStart !== false && $jsonEnd !== false) {
    $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
    $features = json_decode($jsonStr, true);
    
    if ($features && isset($features['safety']) && isset($features['comfort'])) {
        $returnIt = [
            'success' => true,
            'safety' => $features['safety'],
            'comfort' => $features['comfort']
        ];
    } else {
        $returnIt = ['success' => false, 'error' => 'Invalid JSON structure: ' . $jsonStr];
    }
} else {
    $returnIt = ['success' => false, 'error' => 'No JSON found in response: ' . substr($content, 0, 200)];
}
