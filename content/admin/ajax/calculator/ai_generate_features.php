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
$offer_id = isset($_POST['offer_id']) ? intval($_POST['offer_id']) : 0;

if (empty($brand) || empty($carModel)) {
    $returnIt = ['success' => false, 'error' => 'Missing brand or model'];
    return;
}

// Get offer images if offer_id is provided
$offerImages = [];
if ($offer_id > 0) {
    try {
        $stmtOffer = $db->prepare("SELECT images FROM {$prefx}_calculator_offers WHERE id = :id LIMIT 1");
        $stmtOffer->execute(['id' => $offer_id]);
        $offerRow = $stmtOffer->fetch(PDO::FETCH_ASSOC);
        if ($offerRow && !empty($offerRow['images'])) {
            $imagePaths = json_decode($offerRow['images'], true);
            if (is_array($imagePaths)) {
                $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                foreach ($imagePaths as $path) {
                    $offerImages[] = $siteUrl . $path;
                }
            }
        }
    } catch (PDOException $e) {
        // Ignore errors, just don't use images
    }
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

$prompt = "Based on the car data below, generate a list of SAFETY features (Siguranță) and COMFORT features (Confort) that this specific car HAS.

{$carData}

IMPORTANT RULES:
1. Generate REAL and ACCURATE features that THIS SPECIFIC car model and year ACTUALLY HAS - based on real manufacturer specifications
2. Do NOT generate generic or standard features - each feature must be VERIFIED for this exact model
3. Do NOT use words like 'optional', 'in functie de', 'pe unele echipari', 'disponibil' - these are FORBIDDEN
4. Each feature must be stated as a FACT, not a possibility
5. Include EXACTLY 7 items for Safety and EXACTLY 7 items for Comfort
6. Use simple, clear Romanian language without qualifiers
7. If you are not 100% sure a feature exists on this model - DO NOT include it

IMPORTANT: Return EXACTLY in this JSON format:
{
  \"safety\": [\"Feature 1\", \"Feature 2\", ...],
  \"comfort\": [\"Feature 1\", \"Feature 2\", ...]
}

Examples of CORRECT features:
Safety: Anti-lock braking system (ABS), Traction control system (TCS/ASR/TRC), Side airbags, Parking sensors, Surround view camera, Anti-theft device, Summer tires
Comfort: Air conditioning, Power steering, Height-adjustable steering column, Sunroof, Heated side mirrors, Central locking";

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

// Check if we should analyze photos (setting from ai_prompt page)
$analyzePhotos = ($aiSettings['analyze_photos'] ?? '0') === '1';
$useOpenAIVision = $aiProvider === 'openai' && !empty($offerImages) && $analyzePhotos;

// Build user content - with or without images
$userContent = [];

if ($useOpenAIVision) {
    // Add images first for vision analysis
    foreach ($offerImages as $imgUrl) {
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => ['url' => $imgUrl]
        ];
    }
    // Add prompt with instruction to analyze images
    $imagePrompt = "Analyze the car images above and the car data below to generate accurate SAFETY and COMFORT features. Look at the images to identify visible features like: LED lights, sunroof, parking sensors, alloy wheels, leather seats, navigation screen, etc.\n\n" . $prompt;
    $userContent[] = ['type' => 'text', 'text' => $imagePrompt];
} else {
    $userContent[] = ['type' => 'text', 'text' => $prompt];
}

$requestData = [
    'model' => $aiModel,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a car expert assistant. Generate accurate car features based on the model, year, and images if provided.'],
        ['role' => 'user', 'content' => $useOpenAIVision ? $userContent : $prompt]
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
        $returnIt = ['success' => false, 'error' => 'Invalid JSON structure'];
    }
} else {
    $returnIt = ['success' => false, 'error' => 'No JSON found in response'];
}
