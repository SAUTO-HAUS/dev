<?php

$lang = __post('lang') ?: 'ro';
$fromForm = __post('from_form') == '1';
$carType = __post('car_type') ?: 'in_stock';
$selectedPhotos = __post('selected_photos') ?: ''; 

$groqApiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
$openaiApiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';

$useOpenAI = !empty($openaiApiKey);

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
        
        $carImages = [];
        $stmtPhotos = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE it_id = :it_id ORDER BY pos ASC");
        $stmtPhotos->execute(['it_id' => $carId]);
        $photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
        
        $imgFormat = (usr_agent()==='IOS'||usr_agent()==='MAC') ? '.jpg' : '.webp';
        $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        $selectedPositions = [1, 2, 5, 8];
        
        $photoIndex = 0;
        foreach ($photos as $photo) {
            $photoIndex++;
            if (in_array($photoIndex, $selectedPositions)) {
                $carImages[] = $siteUrl . '/' . _CAR_IMG . '/' . $car['p_path'] . '/' . $carId . '/high/' . $photo['name'] . $imgFormat;
            }
        }
        
    } catch (PDOException $e) {
        $returnIt = ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        return;
    }
}

// Load AI settings from database (if available)
$aiSettings = [];
try {
    $stmtSettings = $db->query("SELECT setting_key, setting_value FROM {$prefx}_ai_settings");
    while ($row = $stmtSettings->fetch(PDO::FETCH_ASSOC)) {
        $aiSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Table doesn't exist yet, use defaults
}

// Default texts
$defaultCarTypeOrder = "This is a CAR TO ORDER (not in stock). The car will be imported from EU after the order is placed. Delivery time is typically 14-30 days. Focus on the model's features and what the buyer can expect.";
$defaultCarTypeStock = "This is a CAR IN STOCK (available immediately). The car is already imported and ready for viewing/purchase.";
$defaultImagePrompt = "I'm showing you photos of this car. Analyze them to identify VISIBLE features like: wheel type (alloy/steel), headlight type (LED/xenon/halogen), interior material (leather/cloth), infotainment screen, sunroof, parking sensors, etc. Use ONLY what you can clearly see in the photos for the 'Dotări' section.";

$carTypeOrderText = $aiSettings['car_type_order'] ?? $defaultCarTypeOrder;
$carTypeStockText = $aiSettings['car_type_stock'] ?? $defaultCarTypeStock;
$imagePromptText = $aiSettings['image_prompt'] ?? $defaultImagePrompt;

$carTypeText = ($carType === 'order') ? $carTypeOrderText : $carTypeStockText;

$fixedCarData = "Car data:
- Brand: " . ($car['br_nm'] ?? '') . "
- Model: " . ($car['mo_nm'] ?? '') . "
- Year: " . ($car['yr'] ?? '') . "
- Body type: " . ($car['bt'] ?? '') . "
- Mileage: " . ($car['mlg'] ?? '') . " km
- Engine volume: " . ($car['vol'] ?? '') . " cm³
- Power: " . ($car['hp'] ?? '') . " HP
- Fuel: " . ($car['fl'] ?? '') . "
- Transmission: " . ($car['tra'] ?? '') . "
- Drive: " . ($car['wd'] ?? '') . "
- Color: " . ($car['clr'] ?? '');

$fixedHtmlStructure = "HTML STRUCTURE (MUST follow this EXACT order):
1. <h2>{Brand} {Model} | {Engine} | {Fuel} | {Year}</h2> - USE PIPE SEPARATOR between brand/model, engine, fuel type and year!
2. <h3><span class=\"desc-icon desc-icon-features\"></span>Dotări</h3> then <ul> with 5-8 <li> items.
3. <h3><span class=\"desc-icon desc-icon-spec\"></span>Caracteristici tehnice</h3> then <ul> with detailed specs: engine type, power with kW and rpm, torque Nm, fuel system, real consumption l/100km, drivetrain (DO NOT include gearbox type here - it goes in section 6!)
4. <h3><span class=\"desc-icon desc-icon-engine\"></span>Detalii motor</h3> then <p><strong>Caracteristici constructive:</strong></p><ul> engine block material, cylinder head, turbosuflantă (da/nu), timing drive type (ONLY write 'curea' or 'lanț' - choose correct one for THIS engine!), emission standard, special features </ul> then <p><strong><span class=\"desc-icon desc-icon-oil\"></span>Mentenanță:</strong></p><ul> service interval ALWAYS 7000 km, oil specification (viscosity + ACEA class - choose correct for THIS engine!), oil capacity in litri, injection system notes </ul> - NEVER mention engine lifespan or km durability!
5. <h3><span class=\"desc-icon desc-icon-suspension\"></span>Detalii suspensie</h3> then <ul> with: front suspension type (McPherson/double wishbone/multi-link), rear suspension type (torsion beam/multi-link/independent), stabilizer bars (front/rear), shock absorbers type, any special features (adaptive suspension, air suspension if applicable for this model)
6. <h3><span class=\"desc-icon desc-icon-gearbox\"></span>Detalii cutie de viteze</h3> then <ul> - USE EXACTLY the transmission type from Car data above (Manuală/Automată/Robotizată)! gearbox type, clutch type, oil type and specification (IMPORTANT: for BMW write 'ZF Lifeguard 6', for Mercedes write 'MB 236.14', for VW/Audi/Skoda write 'G052182' - NEVER write 'Dexron' for these brands!), oil capacity as RANGE (X-X litri), gearbox service interval (70000-80000 km)
7. <h3><span class=\"desc-icon desc-icon-condition\"></span>Starea mașinii</h3> then <ul> with 4-5 items: country of import, interior condition (clean/needs cleaning), body condition (scratches/dents/good), suspension condition (noises/good), service status (serviced/needs attention)";

// FIXED PART 3 - JSON format (always at the end)
$fixedJsonFormat = 'IMPORTANT: Return EXACTLY in this JSON format:
{"ro": "<HTML in Romanian>", "ru": "<HTML in Russian>", "en": "<HTML in English>"}';

$defaultEditablePrompt = 'You are an expert automotive journalist and marketing copywriter. Generate DETAILED, ATTRACTIVE and PERSUASIVE HTML descriptions for this car in 3 languages: Romanian, Russian, and English.

YOUR GOAL: Write compelling text that will ATTRACT BUYERS and make them want to purchase or order this car. The text must be clear, beautiful, professional and sales-oriented.

IMPORTANT: ' . $carTypeText;

$editablePrompt = $aiSettings['ai_prompt'] ?? $defaultEditablePrompt;

$prompt = $editablePrompt . "\n\n" . $fixedCarData . "\n\n" . $fixedHtmlStructure . "\n\n" . $fixedJsonFormat;

// Choose API based on settings
$aiProvider = $aiSettings['ai_provider'] ?? 'openai';

if ($aiProvider === 'openai' && !empty($openaiApiKey)) {
    $apiUrl = "https://api.openai.com/v1/chat/completions";
    $apiKey = $openaiApiKey;
    $model = $aiSettings['openai_model'] ?? 'gpt-4o-mini';
    $useOpenAI = true;
} elseif ($aiProvider === 'groq' && !empty($groqApiKey)) {
    $apiUrl = "https://api.groq.com/openai/v1/chat/completions";
    $apiKey = $groqApiKey;
    $model = $aiSettings['groq_model'] ?? 'llama-3.3-70b-versatile';
    $fallbackModel = 'llama-3.1-8b-instant';
    $useOpenAI = false;
} elseif (!empty($openaiApiKey)) {
    // Fallback to OpenAI if selected provider key is missing
    $apiUrl = "https://api.openai.com/v1/chat/completions";
    $apiKey = $openaiApiKey;
    $model = $aiSettings['openai_model'] ?? 'gpt-4o-mini';
    $useOpenAI = true;
} else {
    // Fallback to Groq
    $apiUrl = "https://api.groq.com/openai/v1/chat/completions";
    $apiKey = $groqApiKey;
    $model = $aiSettings['groq_model'] ?? 'llama-3.3-70b-versatile';
    $fallbackModel = 'llama-3.1-8b-instant';
    $useOpenAI = false;
}

$userContent = [];

$analyzePhotos = ($aiSettings['analyze_photos'] ?? '0') === '1';

if ($useOpenAI && !empty($carImages) && $analyzePhotos) {
    foreach ($carImages as $imgUrl) {
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => ['url' => $imgUrl]
        ];
    }
    $prompt = $imagePromptText . "\n\n" . $prompt;
}

$userContent[] = ['type' => 'text', 'text' => $prompt];

// Log AI request info
$startTime = microtime(true);
$logInfo = [
    'provider' => $aiProvider,
    'model' => $model,
    'analyze_photos' => $analyzePhotos ? 'YES' : 'NO',
    'images_count' => count($carImages),
    'images_sent' => ($useOpenAI && !empty($carImages) && $analyzePhotos) ? count($carImages) : 0,
    'prompt_length' => strlen($prompt)
];
error_log("AI Generate START: " . json_encode($logInfo));

$requestData = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a JSON generator. Always respond with valid JSON only, no markdown, no explanations.'],
        ['role' => 'user', 'content' => $useOpenAI && !empty($carImages) && $analyzePhotos ? $userContent : $prompt]
    ],
    'temperature' => 0.7,
    'response_format' => ['type' => 'json_object']
];

if (strpos($model, 'gpt-5') !== false) {
    $requestData['max_completion_tokens'] = 8192;
} else {
    $requestData['max_tokens'] = 8192;
}

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);
error_log("AI Generate END: duration={$duration}s, httpCode={$httpCode}, model={$model}");

if ($curlError) {
    $returnIt = ['success' => false, 'error' => 'cURL error: ' . $curlError];
    return;
}

if ($httpCode !== 200) {
    if ($httpCode === 429 && isset($fallbackModel) && $model !== $fallbackModel) {
        // Try with fallback model
        $requestData['model'] = $fallbackModel;
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $model = $fallbackModel; 
        }
    }
    
    if ($httpCode === 429) {
        $errorData = json_decode($response, true);
        $waitTime = '';
        if (isset($errorData['error']['message'])) {
            if (preg_match('/try again in (\d+m[\d.]+s|\d+[\d.]+s)/i', $errorData['error']['message'], $matches)) {
                $waitTime = $matches[1];
            }
        }
        $waitMsg = $waitTime ? " Попробуйте через {$waitTime}." : " Попробуйте позже.";
        $returnIt = ['success' => false, 'error' => "Лимит запросов исчерпан.{$waitMsg}"];
        return;
    }
    
    if ($httpCode !== 200) {
        $returnIt = ['success' => false, 'error' => 'API error (HTTP ' . $httpCode . '): ' . $response];
        return;
    }
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
