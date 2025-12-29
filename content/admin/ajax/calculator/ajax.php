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
    // Save commercial offer with PDF
    try {
        $client_name = isset($_POST['client_name']) ? trim($_POST['client_name']) : '';
        $brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
        $model = isset($_POST['model']) ? trim($_POST['model']) : '';
        $year = isset($_POST['year']) ? intval($_POST['year']) : 0;
        $vin = isset($_POST['vin']) ? strtoupper(trim($_POST['vin'])) : '';
        $pdf_lang = isset($_POST['pdf_lang']) ? $_POST['pdf_lang'] : 'ro';
        $calculation_data = isset($_POST['calculation_data']) ? $_POST['calculation_data'] : '{}';
        
        // Create table if not exists
        $db->exec('CREATE TABLE IF NOT EXISTS '.$prefx.'_calculator_offers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_name VARCHAR(255) NOT NULL,
            brand VARCHAR(100) NOT NULL,
            model VARCHAR(100) NOT NULL,
            year INT NOT NULL,
            vin VARCHAR(17),
            pdf_lang VARCHAR(5) DEFAULT "ro",
            calculation_data JSON,
            pdf_path VARCHAR(255),
            created_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_client_name (client_name),
            INDEX idx_vin (vin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        
        // Generate unique filename for PDF
        $pdf_filename = 'offer_' . date('Ymd_His') . '_' . uniqid() . '.pdf';
        $pdf_dir = $_SERVER['DOCUMENT_ROOT'] . '/media/calculator_offers/';
        
        // Create directory if not exists
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0755, true);
        }
        
        $pdf_path = '/media/calculator_offers/' . $pdf_filename;
        
        // Insert offer into database
        $pdo = $db->prepare('INSERT INTO '.$prefx.'_calculator_offers 
            (client_name, brand, model, year, vin, pdf_lang, calculation_data, pdf_path, created_by) 
            VALUES (:client_name, :brand, :model, :year, :vin, :pdf_lang, :calculation_data, :pdf_path, :created_by)');
        $pdo->execute([
            'client_name' => $client_name,
            'brand' => $brand,
            'model' => $model,
            'year' => $year,
            'vin' => $vin,
            'pdf_lang' => $pdf_lang,
            'calculation_data' => $calculation_data,
            'pdf_path' => $pdf_path,
            'created_by' => $user_id
        ]);
        
        $offer_id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'offer_id' => $offer_id,
            'pdf_path' => $pdf_path,
            'pdf_filename' => $pdf_filename
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
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        // Get total count
        $count_stmt = $db->query('SELECT COUNT(*) as total FROM '.$prefx.'_calculator_offers');
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get offers
        $pdo = $db->prepare('SELECT o.*, u.login as created_by_name 
            FROM '.$prefx.'_calculator_offers o 
            LEFT JOIN '.$prefx.'_users u ON o.created_by = u.id 
            ORDER BY o.created_at DESC 
            LIMIT :limit OFFSET :offset');
        $pdo->bindValue(':limit', $limit, PDO::PARAM_INT);
        $pdo->bindValue(':offset', $offset, PDO::PARAM_INT);
        $pdo->execute();
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

} else {
    echo json_encode(['success' => false, 'error' => 'Unknown function']);
    exit;
}
