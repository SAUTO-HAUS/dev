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
                $pdo = $db->prepare('UPDATE '.$prefx.'_calculator_settings SET `setting_value` = :value, `updated_by` = :user_id WHERE `setting_key` = :key');
                $pdo->execute([
                    'value' => $value,
                    'user_id' => $user_id,
                    'key' => $setting_key
                ]);
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
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown function']);
}
