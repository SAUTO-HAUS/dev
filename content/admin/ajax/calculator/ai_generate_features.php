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

$prompt = "You are a car expert. Based on the car data below, generate REAL and SPECIFIC safety and comfort features that THIS EXACT car model has according to manufacturer specifications.

{$carData}

RULES:
1. Use your knowledge of this SPECIFIC car model to list REAL features from the manufacturer's specification sheet
2. Include features that are ACTUALLY present on this model and year - not generic features
3. Be specific: instead of 'airbags' say exactly how many and where (e.g., '6 airbag-uri: 2 frontale, 2 laterale, 2 cortina')
4. Include EXACTLY 7 items for Safety and EXACTLY 7 items for Comfort
5. Use Romanian language
6. Do NOT use words like 'optional', 'disponibil', 'in functie de echipare'
7. YOU MUST return exactly 7 safety and 7 comfort features - never empty arrays

Return ONLY valid JSON (no markdown, no explanation):
{\"safety\": [\"...\", \"...\", \"...\", \"...\", \"...\", \"...\", \"...\"], \"comfort\": [\"...\", \"...\", \"...\", \"...\", \"...\", \"...\", \"...\"]}";

// Choose API based on settings
$aiProvider = $aiSettings['ai_provider'] ?? 'openai';

if ($aiProvider === 'openai' && !empty($openaiApiKey)) {
    $aiModel = $aiSettings['openai_model'] ?? 'gpt-4o-mini';
    if (strpos($aiModel, 'gpt-5') !== false) {
        $apiUrl = "https://api.openai.com/v1/responses";
    } else {
        $apiUrl = "https://api.openai.com/v1/chat/completions";
    }
    $apiKey = $openaiApiKey;
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

if (strpos($aiModel, 'gpt-5') !== false) {
    $systemPrompt = 'You are a car expert assistant. Generate accurate car features based on the model, year, and images if provided. Return ONLY valid JSON.';
    
    if ($useOpenAIVision) {
        $userContentParts = [];
        foreach ($offerImages as $imgUrl) {
            $userContentParts[] = [
                'type' => 'input_image',
                'image_url' => $imgUrl,
                'detail' => 'auto'
            ];
        }
        $imagePrompt = "Analyze the car images above and the car data below to generate accurate SAFETY and COMFORT features. Look at the images to identify visible features like: LED lights, sunroof, parking sensors, alloy wheels, leather seats, navigation screen, etc.\n\n" . $prompt;
        $userContentParts[] = [
            'type' => 'input_text',
            'text' => $imagePrompt
        ];
        $requestData = [
            'model' => $aiModel,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [['type' => 'input_text', 'text' => $systemPrompt]]
                ],
                [
                    'role' => 'user',
                    'content' => $userContentParts
                ]
            ],
            'max_output_tokens' => 2048
        ];
    } else {
        $requestData = [
            'model' => $aiModel,
            'input' => [
                [
                    'role' => 'system',
                    'content' => [['type' => 'input_text', 'text' => $systemPrompt]]
                ],
                [
                    'role' => 'user',
                    'content' => [['type' => 'input_text', 'text' => $prompt]]
                ]
            ],
            'max_output_tokens' => 2048
        ];
    }
} else {
    $requestData = [
        'model' => $aiModel,
        'messages' => [
            ['role' => 'system', 'content' => 'You are a car expert assistant. Generate accurate car features based on the model, year, and images if provided.'],
            ['role' => 'user', 'content' => $useOpenAIVision ? $userContent : $prompt]
        ],
        'temperature' => 0.7
    ];
    
    // Use max_completion_tokens for newer OpenAI models, max_tokens for others
    if (strpos($aiModel, 'gpt-4o') !== false || strpos($aiModel, 'o1') !== false || strpos($aiModel, 'o3') !== false) {
        $requestData['max_completion_tokens'] = 1024;
    } else {
        $requestData['max_tokens'] = 1024;
    }
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

if (strpos($aiModel, 'gpt-5') !== false) {
    if (isset($responseData['output_text'])) {
        $content = $responseData['output_text'];
    } elseif (isset($responseData['output'][0]['content'][0]['text'])) {
        $content = $responseData['output'][0]['content'][0]['text'];
    } elseif (isset($responseData['choices'][0]['message']['content'])) {
        $content = $responseData['choices'][0]['message']['content'];
    } else {
        $content = '';
    }
} else {
    $content = $responseData['choices'][0]['message']['content'] ?? '';
}

if (empty($content)) {
    $returnIt = ['success' => false, 'error' => 'Empty content from API', 'debug' => $response];
    return;
}

// Extract JSON from response
$jsonStart = strpos($content, '{');
$jsonEnd = strrpos($content, '}');
if ($jsonStart !== false && $jsonEnd !== false) {
    $jsonStr = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
    $features = json_decode($jsonStr, true);
    
    if ($features && isset($features['safety']) && isset($features['comfort'])) {
        // Check if arrays are not empty
        if (empty($features['safety']) || empty($features['comfort'])) {
            $returnIt = ['success' => false, 'error' => 'AI returned empty arrays', 'raw_content' => $content];
        } else {
            $returnIt = [
                'success' => true,
                'safety' => $features['safety'],
                'comfort' => $features['comfort']
            ];
        }
    } else {
        $returnIt = ['success' => false, 'error' => 'Invalid JSON structure', 'raw_content' => $content];
    }
} else {
    $returnIt = ['success' => false, 'error' => 'No JSON found in response', 'raw_content' => $content];
}
