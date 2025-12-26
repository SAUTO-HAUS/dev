<?php

$lang = __post('lang') ?: 'ro';
$fromForm = __post('from_form') == '1';
$carType = __post('car_type') ?: 'in_stock'; 

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

$carTypeText = ($carType === 'order') 
    ? "This is a CAR TO ORDER (not in stock). The car will be imported from EU after the order is placed. Delivery time is typically 14-30 days. Focus on the model's features and what the buyer can expect."
    : "This is a CAR IN STOCK (available immediately). The car is already imported and ready for viewing/purchase.";

$prompt = "You are an expert automotive journalist and marketing copywriter. Generate DETAILED, ATTRACTIVE and PERSUASIVE HTML descriptions for this car in 3 languages: Romanian, Russian, and English.

YOUR GOAL: Write compelling text that will ATTRACT BUYERS and make them want to purchase or order this car. The text must be clear, beautiful, professional and sales-oriented.

IMPORTANT: {$carTypeText}

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

HTML STRUCTURE (MUST follow this EXACT order):
1. <h2>{Brand} {Model} | {Engine} | {Year}</h2> - USE PIPE SEPARATOR between brand/model, engine and year!
2. <h3>✅ Dotări</h3> then <ul> with 8-12 <li> items - MUST be ATTRACTIVE and APPEALING to buyers! List the most desirable features: comfort, safety, technology, luxury items. Use enticing language that makes buyers want this car. NO boring technical specs here - only features that excite customers!
3. <h3>🔧 Caracteristici tehnice</h3> then <ul> with detailed specs: engine type, power with kW and rpm, torque Nm, fuel system, consumption l/100km, gearbox type, drivetrain
4. <h3>🔍 Detalii motor</h3> then <p><strong>Caracteristici constructive:</strong></p><ul> engine block material, cylinder head, turbo type, timing drive type (ONLY write 'curea' or 'lanț' - choose correct one for THIS engine!), emission standard, special features </ul> then <p><strong>Mentenanță:</strong></p><ul> service interval ALWAYS 7000 km, oil specification (viscosity + ACEA class - choose correct for THIS engine!), oil capacity in litri, injection system notes </ul> - NEVER mention engine lifespan or km durability!
5. <h3>⚙️ Detalii cutie de viteze</h3> then <ul> gearbox type, clutch type, flywheel type with wear notes, reliability notes, oil type (ATF for automatic, MTF for manual), oil specification (choose correct spec for THIS gearbox - NOT always Dexron VI!), oil capacity as RANGE (X-X litri), service interval in km

CRITICAL REQUIREMENTS:
- Section 'Dotări' MUST be the FIRST section (right after h2 title) - mobile layout depends on this!
- Each section must have REAL technical details based on your knowledge of this specific {$car['br_nm']} {$car['mo_nm']} model
- Use <strong> for labels in lists, <em> for notes/warnings
- Be VERY detailed like a professional car review - minimum 1500 characters per language
- Include specific engine codes, gearbox codes, technical specifications you know about this model
- Assess mileage realistically (high/low for this type of vehicle)
- Do NOT use generic filler text - every sentence must add real information
- NEVER add any <li> with 'Avertisment', 'Warning', 'Предупреждение' label - these are STRICTLY FORBIDDEN
- NEVER mention: 'check documents', 'verify history', 'before buying', 'before making an offer', 'verificați', 'проверьте' - FORBIDDEN
- Do NOT add any disclaimers or buyer advice - we are a professional dealership

IMPORTANT: Return EXACTLY in this JSON format:
{\"ro\": \"<HTML in Romanian>\", \"ru\": \"<HTML in Russian>\", \"en\": \"<HTML in English>\"}";

$apiUrl = "https://api.groq.com/openai/v1/chat/completions";

$requestData = [
    'model' => 'llama-3.1-405b-reasoning',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a JSON generator. Always respond with valid JSON only, no markdown, no explanations.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.7,
    'max_tokens' => 8192,
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
