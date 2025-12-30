<?php

$lang = __post('lang') ?: 'ro';
$fromForm = __post('from_form') == '1';
$carType = __post('car_type') ?: 'in_stock'; 

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
        $stmtPhotos = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE it_id = :it_id ORDER BY pos ASC LIMIT 5");
        $stmtPhotos->execute(['it_id' => $carId]);
        $photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
        
        $imgFormat = (usr_agent()==='IOS'||usr_agent()==='MAC') ? '.jpg' : '.webp';
        $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        foreach ($photos as $photo) {
            $carImages[] = $siteUrl . '/' . _CAR_IMG . '/' . $car['p_path'] . '/' . $carId . '/high/' . $photo['name'] . $imgFormat;
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

HTML STRUCTURE (MUST follow this EXACT order):
1. <h2>{Brand} {Model} | {Engine} | {Fuel} | {Year}</h2> - USE PIPE SEPARATOR between brand/model, engine, fuel type and year!
2. <h3><span class=\"desc-icon desc-icon-features\"></span>Dotări</h3> then <ul> with 5-8 <li> items.
   
   ⚠️ VERY IMPORTANT - READ CAREFULLY:
   You are writing for a REAL car dealership. Customers will SEE the actual car. If you write features the car doesn't have, we look like LIARS and lose the sale!
   
   YOU DO NOT HAVE ACCESS TO:
   - Photos of this specific car
   - The actual equipment list
   - What optional extras were ordered
   - Interior material (cloth/leather)
   
   THEREFORE, write ONLY features that are 100% GUARANTEED on EVERY single unit of this model:
   
   BUDGET CARS (Dacia, Lada, Daewoo, Chevrolet Spark/Aveo, Renault Symbol, Fiat Punto):
   - These cars are BASIC! Maximum you can write: manual AC (if available), power steering, front electric windows, radio, 2 front airbags, central locking
   - FORBIDDEN for budget cars: touchscreen, navigation, leather, xenon/LED, parking sensors, camera, heated seats, electric seats, cruise control, climate control
   
   MID-RANGE (Ford, Opel, VW, Skoda, Hyundai, Kia, Toyota, Mazda, Peugeot, Citroen):
   - Safe to write: AC, all electric windows, ABS, ESP, 4-6 airbags, audio system with AUX/USB, onboard computer
   - FORBIDDEN: leather, navigation, parking sensors, camera, heated seats, electric seats (unless 2018+ premium trim)
   
   PREMIUM (BMW, Mercedes, Audi, Lexus, Volvo, Porsche, Jaguar, Land Rover):
   - Safe to write: automatic climate control, all electric windows, ABS, ESP, 6+ airbags, cruise control, onboard computer, audio system with Bluetooth
   - STILL FORBIDDEN even for premium: leather seats, navigation, parking sensors, cameras, sunroof, heated/ventilated seats, electric seats - these are ALWAYS optional!
   
   GOLDEN RULE: When in doubt, DON'T write it. It's better to list 5 real features than 10 fake ones.
3. <h3><span class=\"desc-icon desc-icon-spec\"></span>Caracteristici tehnice</h3> then <ul> with detailed specs: engine type, power with kW and rpm, torque Nm, fuel system, real consumption l/100km, drivetrain (DO NOT include gearbox type here - it goes in section 5!)
4. <h3><span class=\"desc-icon desc-icon-engine\"></span>Detalii motor</h3> then <p><strong>Caracteristici constructive:</strong></p><ul> engine block material, cylinder head, turbosuflantă (da/nu), timing drive type (ONLY write 'curea' or 'lanț' - choose correct one for THIS engine!), emission standard, special features </ul> then <p><strong><span class=\"desc-icon desc-icon-oil\"></span>Mentenanță:</strong></p><ul> service interval ALWAYS 7000 km, oil specification (viscosity + ACEA class - choose correct for THIS engine!), oil capacity in litri, injection system notes </ul> - NEVER mention engine lifespan or km durability!
5. <h3><span class=\"desc-icon desc-icon-gearbox\"></span>Detalii cutie de viteze</h3> then <ul> - USE EXACTLY the transmission type from Car data above (Manuală/Automată/Robotizată)! gearbox type, clutch type, oil type and specification (IMPORTANT: for BMW write 'ZF Lifeguard 6', for Mercedes write 'MB 236.14', for VW/Audi/Skoda write 'G052182' - NEVER write 'Dexron' for these brands!), oil capacity as RANGE (X-X litri), gearbox service interval (70000-80000 km)





IMPORTANT: Return EXACTLY in this JSON format:
{\"ro\": \"<HTML in Romanian>\", \"ru\": \"<HTML in Russian>\", \"en\": \"<HTML in English>\"}";

// Choose API based on available key
if ($useOpenAI) {
    $apiUrl = "https://api.openai.com/v1/chat/completions";
    $apiKey = $openaiApiKey;
    $model = 'gpt-5.2';
} else {
    $apiUrl = "https://api.groq.com/openai/v1/chat/completions";
    $apiKey = $groqApiKey;
    $model = 'llama-3.3-70b-versatile';
    $fallbackModel = 'llama-3.1-8b-instant'; 
}

$userContent = [];

if ($useOpenAI && !empty($carImages)) {
    foreach ($carImages as $imgUrl) {
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => ['url' => $imgUrl]
        ];
    }
    $prompt = "I'm showing you photos of this car. Analyze them to identify VISIBLE features like: wheel type (alloy/steel), headlight type (LED/xenon/halogen), interior material (leather/cloth), infotainment screen, sunroof, parking sensors, etc. Use ONLY what you can clearly see in the photos for the 'Dotări' section.\n\n" . $prompt;
}

$userContent[] = ['type' => 'text', 'text' => $prompt];

$requestData = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a JSON generator. Always respond with valid JSON only, no markdown, no explanations.'],
        ['role' => 'user', 'content' => $useOpenAI && !empty($carImages) ? $userContent : $prompt]
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
