<?php

ignore_user_abort(true);
set_time_limit(180);

// Simple logging function
function logAI($message, $data = []) {
    $logFile = $_SERVER['DOCUMENT_ROOT'] . '/logs/ai_descriptions.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    if (!empty($data)) {
        $logMessage .= " | " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    $logMessage .= "\n";
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

$isBackgroundRequest = ($_POST['save_to_db'] ?? $_GET['save_to_db'] ?? '') == '1';

if ($isBackgroundRequest) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    if (ob_get_level()) ob_end_clean();
    header('Connection: close');
    header('Content-Length: 2');
    header('Content-Type: application/json');
    echo '{}';
    flush();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

$lang = __post('lang') ?: 'ro';
$fromForm = __post('from_form') == '1';
$carType = __post('car_type') ?: 'in_stock';
$selectedPhotos = __post('selected_photos') ?: ''; 

$groqApiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
$openaiApiKey = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';

$useOpenAI = !empty($openaiApiKey);

$carImages = [];
$carId = intval($_POST['car_id'] ?? $_GET['car_id'] ?? 0);

logAI("AI Request", ['car_id' => $carId, 'save_to_db' => ($_POST['save_to_db'] ?? $_GET['save_to_db'] ?? '0')]);

$saveToDbCheck = ($_POST['save_to_db'] ?? $_GET['save_to_db'] ?? '') == '1';
if ($saveToDbCheck && $carId > 0) {
    $stmtType = $db->prepare("SELECT catalog_type FROM {$prefx}_car_ctlg WHERE id = ? LIMIT 1");
    $stmtType->execute([$carId]);
    $typeRow = $stmtType->fetch(PDO::FETCH_ASSOC);
    $p1Value = ($typeRow && $typeRow['catalog_type'] === 'on_order') ? 'ordercars' : 'cars';
    
    logAI("Check existing", ['car_id' => $carId, 'catalog_type' => $typeRow['catalog_type'] ?? 'unknown', 'p1' => $p1Value]);
    
    $stmtCheckExisting = $db->prepare("SELECT id, LENGTH(params_html) as html_length FROM {$prefx}_seo2 WHERE it_id = ? AND tp = 'item' AND p1 = ? AND lng = 'ro' LIMIT 1");
    $stmtCheckExisting->execute([$carId, $p1Value]);
    $existingDesc = $stmtCheckExisting->fetch(PDO::FETCH_ASSOC);
    
    if ($existingDesc && !empty($existingDesc['html_length']) && $existingDesc['html_length'] > 10) {
        logAI("SKIPPED - has valid description", ['car_id' => $carId, 'html_length' => $existingDesc['html_length']]);
        $returnIt = ['success' => true, 'skipped' => true, 'reason' => 'Car already has AI description'];
        return;
    }
    
    if ($existingDesc && $existingDesc['html_length'] <= 10) {
        logAI("Found empty description - will regenerate", ['car_id' => $carId, 'html_length' => $existingDesc['html_length']]);
    }
    
    logAI("Proceeding with generation", ['car_id' => $carId]);
}

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
        'cur' => __post('currency') ?: '',
        'import_country' => __post('import_country') ?: ''
    ];
    
    // Load photos if car_id is provided (existing car)
    if ($carId > 0) {
        try {
            $stmtCar = $db->prepare("SELECT p_path FROM {$prefx}_car_ctlg WHERE id = :id LIMIT 1");
            $stmtCar->execute(['id' => $carId]);
            $carRow = $stmtCar->fetch(PDO::FETCH_ASSOC);
            
            if ($carRow) {
                $stmtPhotos = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE it_id = :it_id ORDER BY pos ASC");
                $stmtPhotos->execute(['it_id' => $carId]);
                $photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
                
                $photoPositionsSetting = '';
                try {
                    $stmtPos = $db->query("SELECT setting_value FROM {$prefx}_ai_settings WHERE setting_key = 'photo_positions' LIMIT 1");
                    $posRow = $stmtPos->fetch(PDO::FETCH_ASSOC);
                    if ($posRow && isset($posRow['setting_value'])) {
                        $photoPositionsSetting = $posRow['setting_value'];
                    }
                } catch (PDOException $e) {}
                
                $selectedPositions = [];
                if (!empty($photoPositionsSetting)) {
                    $selectedPositions = array_map('intval', array_filter(explode(',', $photoPositionsSetting)));
                }
                
                // Use local file path for base64 encoding - try .jpg first, then .webp
                $basePath = $_SERVER['DOCUMENT_ROOT'] . '/' . _CAR_IMG . '/' . $carRow['p_path'] . '/' . $carId . '/high/';
                
                $photoIndex = 0;
                foreach ($photos as $photo) {
                    $photoIndex++;
                    if (empty($selectedPositions) || in_array($photoIndex, $selectedPositions)) {
                        // Try .jpg first, then .webp
                        $jpgPath = $basePath . $photo['name'] . '.jpg';
                        $webpPath = $basePath . $photo['name'] . '.webp';
                        
                        if (file_exists($jpgPath)) {
                            $imageData = file_get_contents($jpgPath);
                            $base64 = base64_encode($imageData);
                            $carImages[] = 'data:image/jpeg;base64,' . $base64;
                        } elseif (file_exists($webpPath)) {
                            $imageData = file_get_contents($webpPath);
                            $base64 = base64_encode($imageData);
                            $carImages[] = 'data:image/webp;base64,' . $base64;
                        }
                    }
                }
            }
        } catch (PDOException $e) {}
    }
} else {
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
        
        // Get import country name from countries table
        if (!empty($car['import_country_id'])) {
            $stmtCountry = $db->prepare("SELECT name_ro FROM countries WHERE id = :id LIMIT 1");
            $stmtCountry->execute(['id' => $car['import_country_id']]);
            $countryRow = $stmtCountry->fetch(PDO::FETCH_ASSOC);
            $car['import_country'] = $countryRow['name_ro'] ?? '';
        } else {
            $car['import_country'] = '';
        }
        
        $carImages = [];
        $stmtPhotos = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE it_id = :it_id ORDER BY pos ASC");
        $stmtPhotos->execute(['it_id' => $carId]);
        $photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
        
        $photoPositionsSetting = ''; 
        try {
            $stmtPos = $db->query("SELECT setting_value FROM {$prefx}_ai_settings WHERE setting_key = 'photo_positions' LIMIT 1");
            $posRow = $stmtPos->fetch(PDO::FETCH_ASSOC);
            if ($posRow && isset($posRow['setting_value'])) {
                $photoPositionsSetting = $posRow['setting_value'];
            }
        } catch (PDOException $e) {}
        
        $selectedPositions = [];
        if (!empty($photoPositionsSetting)) {
            $selectedPositions = array_map('intval', array_filter(explode(',', $photoPositionsSetting)));
        }
        
        // Use local file path for base64 encoding - try .jpg first, then .webp
        $basePath = $_SERVER['DOCUMENT_ROOT'] . '/' . _CAR_IMG . '/' . $car['p_path'] . '/' . $carId . '/high/';
        
        $photoIndex = 0;
        foreach ($photos as $photo) {
            $photoIndex++;
            if (empty($selectedPositions) || in_array($photoIndex, $selectedPositions)) {
                // Try .jpg first, then .webp
                $jpgPath = $basePath . $photo['name'] . '.jpg';
                $webpPath = $basePath . $photo['name'] . '.webp';
                
                if (file_exists($jpgPath)) {
                    $imageData = file_get_contents($jpgPath);
                    $base64 = base64_encode($imageData);
                    $carImages[] = 'data:image/jpeg;base64,' . $base64;
                } elseif (file_exists($webpPath)) {
                    $imageData = file_get_contents($webpPath);
                    $base64 = base64_encode($imageData);
                    $carImages[] = 'data:image/webp;base64,' . $base64;
                }
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

$imagePromptText = $aiSettings['image_prompt'] ?? '';

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
- Color: " . ($car['clr'] ?? '') . "
- Import country: " . ($car['import_country'] ?? '');


$editablePrompt = $aiSettings['ai_prompt'] ?? '';

// Fix corrupted HTML encoding in prompt (multiple &amp; encoding)
while (strpos($editablePrompt, '&amp;') !== false) {
    $editablePrompt = html_entity_decode($editablePrompt, ENT_QUOTES, 'UTF-8');
}

// HTML structure with required sections and icons (fixed in code)
$htmlStructure = '
HTML STRUCTURE (MANDATORY - use span with icons exactly as shown):
1. <h2>{Brand} {Model} | {Engine} | {Fuel} | {Year}</h2>
2. <h3><span class="desc-icon desc-icon-features"></span>Equipment</h3>
3. <h3><span class="desc-icon desc-icon-spec"></span>Technical specifications</h3>
4. <h3><span class="desc-icon desc-icon-engine"></span>Engine details</h3>
   - oil subsection: <h4><span class="desc-icon desc-icon-oil"></span>Oil / Consumables</h4>
5. <h3><span class="desc-icon desc-icon-suspension"></span>Suspension details</h3>
6. <h3><span class="desc-icon desc-icon-gearbox"></span>Gearbox details</h3>
7. <h3><span class="desc-icon desc-icon-condition"></span>Vehicle condition</h3>

IMPORTANT: 
- Every h3 heading MUST start with <span class="desc-icon desc-icon-XXX"></span> to display the icon!
- Translate section titles to the OUTPUT language (RO: Echipare, Caracteristici tehnice, etc. / RU: Оснащение, Технические характеристики, etc. / EN: Equipment, Technical specifications, etc.)
- Use your knowledge database to provide REAL technical data for this specific car model (engine specs, transmission type, suspension type, oil capacity, timing belt/chain info, etc.) - do NOT write generic text!';

// JSON format is required for parsing the response
$jsonFormat = 'IMPORTANT: Return EXACTLY in this JSON format:
{"ro": "<HTML in Romanian>", "ru": "<HTML in Russian>", "en": "<HTML in English>"}';

$prompt = $editablePrompt . "\n\n" . $fixedCarData . "\n\n" . $htmlStructure . "\n\n" . $jsonFormat;

$aiProvider = 'openai';

if (empty($openaiApiKey)) {
    $returnIt = ['success' => false, 'error' => 'OpenAI API key not configured'];
    return;
}
$selectedModel = $aiSettings['openai_model'] ?? 'gpt-4.1-mini';
// GPT-5.x uses the new responses API endpoint.
$apiUrl = (strpos($selectedModel, 'gpt-5') !== false)
    ? "https://api.openai.com/v1/responses"
    : "https://api.openai.com/v1/chat/completions";
$apiKey = $openaiApiKey;
$model = $selectedModel;
$useOpenAI = true;

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

$maxTokens = 4096; 
if (strpos($model, 'gpt-4o') !== false) {
    $maxTokens = 8192; 
}

// GPT-5.x uses responses API with role/content structure
if (strpos($model, 'gpt-5') !== false) {
    $systemPrompt = 'You are a JSON generator. Always respond with valid JSON only, no markdown, no explanations.';
    
    if ($useOpenAI && !empty($carImages) && $analyzePhotos) {
        $userContentParts = [];
        foreach ($carImages as $imgUrl) {
            $userContentParts[] = [
                'type' => 'input_image',
                'image_url' => $imgUrl,
                'detail' => 'auto'
            ];
        }
        $userContentParts[] = [
            'type' => 'input_text',
            'text' => $prompt
        ];
        $requestData = [
            'model' => $model,
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
            'max_output_tokens' => 8192
        ];
    } else {
        $requestData = [
            'model' => $model,
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
            'max_output_tokens' => 8192
        ];
    }
} else {
    $requestData = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Respond with valid JSON only. Follow the user instructions exactly.'],
            ['role' => 'user', 'content' => $useOpenAI && !empty($carImages) && $analyzePhotos ? $userContent : $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => $maxTokens,
        'response_format' => ['type' => 'json_object']
    ];
}

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
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


// GPT-5.x returns output_text, older models return choices[0].message.content
if (strpos($model, 'gpt-5') !== false) {
    // Try different response formats
    if (isset($responseData['output_text'])) {
        $generatedContent = $responseData['output_text'];
    } elseif (isset($responseData['output'][0]['content'][0]['text'])) {
        $generatedContent = $responseData['output'][0]['content'][0]['text'];
    } elseif (isset($responseData['choices'][0]['message']['content'])) {
        $generatedContent = $responseData['choices'][0]['message']['content'];
    } else {
        $returnIt = ['success' => false, 'error' => 'Invalid API response', 'raw' => $responseData];
        return;
    }
} else {
    if (!isset($responseData['choices'][0]['message']['content'])) {
        $returnIt = ['success' => false, 'error' => 'Invalid API response', 'raw' => $responseData];
        return;
    }
    $generatedContent = $responseData['choices'][0]['message']['content'];
}
$generatedContent = preg_replace('/^```json?\s*/i', '', $generatedContent);
$generatedContent = preg_replace('/\s*```$/i', '', $generatedContent);
$generatedContent = trim($generatedContent);

$htmlData = json_decode($generatedContent, true);

if (!$htmlData || !isset($htmlData['ro'])) {
    $returnIt = ['success' => false, 'error' => 'Invalid JSON response', 'raw' => $generatedContent];
    return;
}

// Save to database if requested and car_id is provided
$saveToDb = ($_POST['save_to_db'] ?? $_GET['save_to_db'] ?? '') == '1';
$carIdForSave = intval($_POST['car_id'] ?? $_GET['car_id'] ?? 0);

if ($saveToDb && $carIdForSave > 0) {
    $langs = ['ro', 'ru', 'en'];
    $stmtType = $db->prepare("SELECT catalog_type FROM {$prefx}_car_ctlg WHERE id = ? LIMIT 1");
    $stmtType->execute([$carIdForSave]);
    $typeRow = $stmtType->fetch(PDO::FETCH_ASSOC);
    $p1Value = ($typeRow && $typeRow['catalog_type'] === 'on_order') ? 'ordercars' : 'cars';
    
    logAI("Saving to DB", ['car_id' => $carIdForSave, 'p1' => $p1Value]);

    foreach ($langs as $lng) {
        $htmlContent = $htmlData[$lng] ?? '';
        if (!empty($htmlContent)) {
            $stmtCheck = $db->prepare("SELECT id FROM {$prefx}_seo2 WHERE it_id = ? AND tp = 'item' AND p1 = ? AND lng = ? LIMIT 1");
            $stmtCheck->execute([$carIdForSave, $p1Value, $lng]);
            $existingRecord = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existingRecord) {
                $stmtUpdate = $db->prepare("UPDATE {$prefx}_seo2 SET params_html = ? WHERE id = ?");
                $stmtUpdate->execute([$htmlContent, $existingRecord['id']]);
            } else {
                $stmtInsert = $db->prepare("INSERT INTO {$prefx}_seo2 (it_id, tp, p1, p2, qr, lng, ttl, h1, dsc, kwd, txt, params_html) VALUES (?, 'item', ?, '', '', ?, '', '', '', '', '', ?)");
                $stmtInsert->execute([$carIdForSave, $p1Value, $lng, $htmlContent]);
            }
        } else {
            logAI("Skipped $lng - empty content");
        }
    }
}

$returnIt = [
    'success' => true, 
    'html_ro' => $htmlData['ro'] ?? '', 
    'html_ru' => $htmlData['ru'] ?? '', 
    'html_en' => $htmlData['en'] ?? '',
    'saved_to_db' => $saveToDb && $carIdForSave > 0
];
