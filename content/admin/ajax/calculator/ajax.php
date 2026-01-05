<?php defined( '_DOIT' ) or die( 'Restricted access' );

header('Content-Type: application/json');

$fn = isset($_POST['fn']) ? $_POST['fn'] : '';

if ($fn === 'save_eur_rate') {
    $rate = isset($_POST['rate']) ? floatval($_POST['rate']) : 0;
    
    if ($rate <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid rate']);
        exit;
    }
    
    try {
        // Check if setting exists
        $pdo = $db->prepare('SELECT id FROM '.$prefx.'_calculator_settings WHERE setting_key = "eur_rate"');
        $pdo->execute();
        $exists = $pdo->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            $pdo = $db->prepare('UPDATE '.$prefx.'_calculator_settings SET setting_value = :rate, updated_by = :user_id WHERE setting_key = "eur_rate"');
        } else {
            $pdo = $db->prepare('INSERT INTO '.$prefx.'_calculator_settings (setting_key, setting_value, description, updated_by) VALUES ("eur_rate", :rate, "EUR to MDL exchange rate", :user_id)');
        }
        $pdo->execute(['rate' => $rate, 'user_id' => $user_id]);
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    
} elseif ($fn === 'log_usage') {
    try {
        $pdo = $db->prepare('INSERT INTO '.$prefx.'_calculator_usage_log (`user_id`, `created_at`) VALUES (:user_id, NOW())');
        $pdo->execute(['user_id' => $user_id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
} elseif ($fn === 'save_rates') {
    if (!isset($user_role) || $user_role !== 'gordon') {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    try {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = str_replace('setting_', '', $key);
                
                $check = $db->prepare('SELECT id FROM '.$prefx.'_calculator_settings WHERE setting_key = :key');
                $check->execute(['key' => $setting_key]);
                $exists = $check->fetch(PDO::FETCH_ASSOC);
                
                if ($exists) {
                    $pdo = $db->prepare('UPDATE '.$prefx.'_calculator_settings SET `setting_value` = :value, `updated_by` = :user_id WHERE `setting_key` = :key');
                    $pdo->execute([
                        'value' => $value,
                        'user_id' => $user_id,
                        'key' => $setting_key
                    ]);
                } else {
                    $pdo = $db->prepare('INSERT INTO '.$prefx.'_calculator_settings (`setting_key`, `setting_value`, `updated_by`) VALUES (:key, :value, :user_id)');
                    $pdo->execute([
                        'key' => $setting_key,
                        'value' => $value,
                        'user_id' => $user_id
                    ]);
                }
            }
        }
        
        $rate_updates = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'rate_') === 0) {
                preg_match('/rate_(\d+)_(.+)/', $key, $matches);
                if (count($matches) === 3) {
                    $rate_id = $matches[1];
                    $field = $matches[2];
                    if (!isset($rate_updates[$rate_id])) {
                        $rate_updates[$rate_id] = [];
                    }
                    $rate_updates[$rate_id][$field] = $value;
                }
            }
        }
        
        foreach ($rate_updates as $rate_id => $fields) {
            $pdo = $db->prepare('UPDATE '.$prefx.'_calculator_excise_rates SET 
                `capacity_min` = :capacity_min,
                `capacity_max` = :capacity_max,
                `age_min` = :age_min,
                `age_max` = :age_max,
                `rate` = :rate,
                `updated_by` = :user_id
                WHERE `id` = :id');
            $pdo->execute([
                'capacity_min' => $fields['capacity_min'] ?? 0,
                'capacity_max' => $fields['capacity_max'] ?? 0,
                'age_min' => $fields['age_min'] ?? 0,
                'age_max' => $fields['age_max'] ?? 0,
                'rate' => $fields['rate'] ?? 0,
                'user_id' => $user_id,
                'id' => $rate_id
            ]);
        }
        
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    
} elseif ($fn === 'save_offer') {
    // Save commercial offer with images
    try {
        $client_name = isset($_POST['client_name']) ? trim($_POST['client_name']) : '';
        $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
        $model = isset($_POST['model']) ? trim($_POST['model']) : '';
        $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
        $bodywork = isset($_POST['bodywork']) ? trim($_POST['bodywork']) : '';
        $seats = isset($_POST['seats']) ? intval($_POST['seats']) : 0;
        $cylinder_capacity = isset($_POST['cylinder_capacity']) ? intval($_POST['cylinder_capacity']) : 0;
        $fuel_type = isset($_POST['fuel_type']) ? trim($_POST['fuel_type']) : '';
        $mileage = isset($_POST['mileage']) ? intval($_POST['mileage']) : 0;
        $engine_power = isset($_POST['engine_power']) ? intval($_POST['engine_power']) : 0;
        $transmission = isset($_POST['transmission']) ? trim($_POST['transmission']) : '';
        $drive_type = isset($_POST['drive_type']) ? trim($_POST['drive_type']) : '';
        $color = isset($_POST['color']) ? trim($_POST['color']) : '';
        $pdf_lang = isset($_POST['pdf_lang']) ? $_POST['pdf_lang'] : 'ro';
        $calculation_data = isset($_POST['calculation_data']) ? $_POST['calculation_data'] : '{}';
        $existing_offer_id = isset($_POST['offer_id']) ? intval($_POST['offer_id']) : 0;
        
        // Validate exactly 7 images
        if (!isset($_FILES['images']) || count($_FILES['images']['name']) !== 7) {
            echo json_encode(['success' => false, 'error' => 'Exactly 7 images are required']);
            exit;
        }
        
        // Check if updating existing offer or creating new one
        if ($existing_offer_id > 0) {
            // UPDATE existing offer
            $pdo = $db->prepare('UPDATE '.$prefx.'_calculator_offers SET
                client_name = :client_name, brand = :brand, model = :model, year = :year, 
                bodywork = :bodywork, seats = :seats, cylinder_capacity = :cylinder_capacity, 
                fuel_type = :fuel_type, mileage = :mileage, engine_power = :engine_power, 
                transmission = :transmission, drive_type = :drive_type, color = :color, 
                pdf_lang = :pdf_lang, calculation_data = :calculation_data
                WHERE id = :id');
            $pdo->execute([
                'client_name' => $client_name,
                'brand' => $brand,
                'model' => $model,
                'year' => $year,
                'bodywork' => $bodywork,
                'seats' => $seats,
                'cylinder_capacity' => $cylinder_capacity,
                'fuel_type' => $fuel_type,
                'mileage' => $mileage,
                'engine_power' => $engine_power,
                'transmission' => $transmission,
                'drive_type' => $drive_type,
                'color' => $color,
                'pdf_lang' => $pdf_lang,
                'calculation_data' => $calculation_data,
                'id' => $existing_offer_id
            ]);
            $offer_id = $existing_offer_id;
        } else {
            // INSERT new offer
            $pdo = $db->prepare('INSERT INTO '.$prefx.'_calculator_offers 
                (client_name, brand, model, year, bodywork, seats, cylinder_capacity, fuel_type, mileage, engine_power, transmission, drive_type, color, pdf_lang, calculation_data, created_by) 
                VALUES (:client_name, :brand, :model, :year, :bodywork, :seats, :cylinder_capacity, :fuel_type, :mileage, :engine_power, :transmission, :drive_type, :color, :pdf_lang, :calculation_data, :created_by)');
            $pdo->execute([
                'client_name' => $client_name,
                'brand' => $brand,
                'model' => $model,
                'year' => $year,
                'bodywork' => $bodywork,
                'seats' => $seats,
                'cylinder_capacity' => $cylinder_capacity,
                'fuel_type' => $fuel_type,
                'mileage' => $mileage,
                'engine_power' => $engine_power,
                'transmission' => $transmission,
                'drive_type' => $drive_type,
                'color' => $color,
                'pdf_lang' => $pdf_lang,
                'calculation_data' => $calculation_data,
                'created_by' => $user_id
            ]);
            $offer_id = $db->lastInsertId();
        }
        
        // Create directory for offer images
        $upload_base = $_SERVER['DOCUMENT_ROOT'] . '/uploads/calculator_offers';
        $offer_dir = $upload_base . '/' . $offer_id;
        
        if (!file_exists($upload_base)) {
            mkdir($upload_base, 0755, true);
        }
        if (!file_exists($offer_dir)) {
            mkdir($offer_dir, 0755, true);
        }
        
        // Process and save images
        $image_paths = [];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        for ($i = 0; $i < 7; $i++) {
            $tmp_name = $_FILES['images']['tmp_name'][$i];
            $file_type = $_FILES['images']['type'][$i];
            $file_size = $_FILES['images']['size'][$i];
            $error = $_FILES['images']['error'][$i];
            
            if ($error !== UPLOAD_ERR_OK) {
                throw new Exception('Upload error for image ' . ($i + 1));
            }
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type for image ' . ($i + 1));
            }
            
            if ($file_size > $max_size) {
                throw new Exception('File too large for image ' . ($i + 1));
            }
            
            // Generate filename
            $extension = 'jpg';
            if ($file_type === 'image/png') $extension = 'png';
            if ($file_type === 'image/webp') $extension = 'webp';
            
            $filename = 'img_' . ($i + 1) . '.' . $extension;
            $full_path = $offer_dir . '/' . $filename;
            $relative_path = '/uploads/calculator_offers/' . $offer_id . '/' . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($tmp_name, $full_path)) {
                throw new Exception('Failed to save image ' . ($i + 1));
            }
            
            $image_paths[] = $relative_path;
        }
        
        // Update offer with image paths
        $pdo = $db->prepare('UPDATE '.$prefx.'_calculator_offers SET images = :images WHERE id = :id');
        $pdo->execute([
            'images' => json_encode($image_paths),
            'id' => $offer_id
        ]);
        
        echo json_encode([
            'success' => true, 
            'offer_id' => $offer_id,
            'images' => $image_paths
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

} elseif ($fn === 'save_offer_pdf') {
    // Save the actual PDF file content
    try {
        $pdf_path = isset($_POST['pdf_path']) ? $_POST['pdf_path'] : '';
        $pdf_data = isset($_POST['pdf_data']) ? $_POST['pdf_data'] : '';
        
        if (empty($pdf_path) || empty($pdf_data)) {
            echo json_encode(['success' => false, 'error' => 'Missing PDF data']);
            exit;
        }
        
        // Decode base64 PDF data
        $pdf_content = base64_decode(preg_replace('#^data:application/pdf;base64,#i', '', $pdf_data));
        
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $pdf_path;
        
        if (file_put_contents($full_path, $pdf_content)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save PDF file']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

} elseif ($fn === 'get_offers') {
    // Get list of offers for catalog
    try {
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $search = isset($_POST['search']) ? trim($_POST['search']) : '';
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Build search condition
        $where = '';
        $searchParams = [];
        if (!empty($search)) {
            // Split search into words for AND logic (e.g., "bmw 2011" finds BMW from 2011)
            $words = preg_split('/\s+/', $search);
            $conditions = [];
            foreach ($words as $word) {
                $word = trim($word);
                if (empty($word)) continue;
                $wordParam = '%' . $word . '%';
                $conditions[] = '(client_name LIKE ? OR brand LIKE ? OR model LIKE ? OR year LIKE ? OR bodywork LIKE ? OR mileage LIKE ? OR transmission LIKE ? OR drive_type LIKE ? OR color LIKE ?)';
                for ($i = 0; $i < 9; $i++) {
                    $searchParams[] = $wordParam;
                }
            }
            if (!empty($conditions)) {
                $where = ' WHERE ' . implode(' AND ', $conditions);
            }
        }
        
        // Get total count
        $count_stmt = $db->prepare('SELECT COUNT(*) as total FROM '.$prefx.'_calculator_offers' . $where);
        if (!empty($searchParams)) {
            $count_stmt->execute($searchParams);
        } else {
            $count_stmt->execute();
        }
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get offers
        $sql = 'SELECT * FROM '.$prefx.'_calculator_offers' . $where . ' ORDER BY created_at DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $pdo = $db->prepare($sql);
        if (!empty($searchParams)) {
            $pdo->execute($searchParams);
        } else {
            $pdo->execute();
        }
        $offers = $pdo->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'offers' => $offers,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

} elseif ($fn === 'delete_offer') {
    // Delete an offer
    try {
        $offer_id = isset($_POST['offer_id']) ? intval($_POST['offer_id']) : 0;
        
        if ($offer_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid offer ID']);
            exit;
        }
        
        // Get PDF path before deleting
        $pdo = $db->prepare('SELECT pdf_path FROM '.$prefx.'_calculator_offers WHERE id = :id');
        $pdo->execute(['id' => $offer_id]);
        $offer = $pdo->fetch(PDO::FETCH_ASSOC);
        
        if ($offer && !empty($offer['pdf_path'])) {
            $full_path = $_SERVER['DOCUMENT_ROOT'] . $offer['pdf_path'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
        
        // Delete from database
        $pdo = $db->prepare('DELETE FROM '.$prefx.'_calculator_offers WHERE id = :id');
        $pdo->execute(['id' => $offer_id]);
        
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

} elseif ($fn === 'get_models') {
    // Get models for a brand
    try {
        $brand = isset($_POST['brand']) ? $_POST['brand'] : '';
        
        if (empty($brand)) {
            echo json_encode(['success' => false, 'error' => 'Brand required']);
            exit;
        }
        
        $pdo = $db->prepare('SELECT `mo`, `mo_nm` FROM '.$prefx.'_car_list WHERE `br` = :br ORDER BY `mo` ASC');
        $pdo->execute(['br' => $brand]);
        $models = $pdo->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'models' => $models]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

} elseif ($fn === 'get_offer') {
    // Get single offer by ID
    try {
        $offer_id = isset($_POST['offer_id']) ? intval($_POST['offer_id']) : 0;
        
        if ($offer_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid offer ID']);
            exit;
        }
        
        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_calculator_offers WHERE id = :id');
        $pdo->execute(['id' => $offer_id]);
        $offer = $pdo->fetch(PDO::FETCH_ASSOC);
        
        if ($offer) {
            echo json_encode(['success' => true, 'offer' => $offer]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Offer not found']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

} elseif ($fn === 'ai_generate_features') {
    require_once(__DIR__ . '/ai_generate_features.php');
    echo json_encode($returnIt);
    exit;

} elseif ($fn === 'update_offer_features') {
    try {
        $offer_id = intval(isset($_POST['offer_id']) ? $_POST['offer_id'] : 0);
        $features = isset($_POST['features']) ? $_POST['features'] : '';
        
        if ($offer_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid offer ID']);
            exit;
        }
        
        // Check if ai_features column exists, if not add it
        try {
            $db->query("SELECT ai_features FROM {$prefx}_calculator_offers LIMIT 1");
        } catch (PDOException $e) {
            $db->exec("ALTER TABLE {$prefx}_calculator_offers ADD COLUMN ai_features TEXT NULL");
        }
        
        $pdo = $db->prepare('UPDATE '.$prefx.'_calculator_offers SET ai_features = :features WHERE id = :id');
        $pdo->execute([
            'features' => $features,
            'id' => $offer_id
        ]);
        
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }

} else {
    echo json_encode(['success' => false, 'error' => 'Unknown function']);
    exit;
}
