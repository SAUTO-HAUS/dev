<?php
defined('_DOIT') or die('Restricted access');

ignore_user_abort(true);
set_time_limit(180);

$brand = __post('brand') ?: '';
$model = __post('model') ?: '';
$year = __post('year') ?: '';
$fuel_type = __post('fuel_type') ?: '';
$bodywork = __post('bodywork') ?: '';

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
- Model: {$model}
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
    $model = $aiSettings['openai_model'] ?? 'gpt-4o-mini';
} elseif ($aiProvider === 'groq' && !empty($groqApiKey)) {
    $apiUrl = "https://api.groq.com/openai/v1/chat/completions";
    $apiKey = $groqApiKey;
    $model = $aiSettings['groq_model'] ?? 'llama-3.3-70b-versatile';
} elseif (!empty($openaiApiKey)) {
    $apiUrl = "https://api.openai.com/v1/chat/completions";
    $apiKey = $openaiApiKey;
    $model = 'gpt-4o-mini';
} else {
    $apiUrl = "https://api.groq.com/openai/v1/chat/completions";
    $apiKey = $groqApiKey;
    $model = 'llama-3.3-70b-versatile';
}

if (empty($apiKey)) {
    $returnIt = ['success' => false, 'error' => 'No API key configured'];
    return;
}

$requestData = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a car expert assistant. Generate accurate car features based on the model and year.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'max_tokens' => 1024,
    'temperature' => 0.7
];

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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $returnIt = ['success' => false, 'error' => 'API error: ' . $httpCode];
    return;
}

$responseData = json_decode($response, true);
$content = $responseData['choices'][0]['message']['content'] ?? '';

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
        $returnIt = ['success' => false, 'error' => 'Invalid JSON response'];
    }
} else {
    $returnIt = ['success' => false, 'error' => 'No JSON found in response'];
}
