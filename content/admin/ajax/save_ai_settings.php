<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/content/admin/include/init.php';

header('Content-Type: application/json');

if (!isset($_POST['ajax_save_ai_settings'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS {$prefx}_ai_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value LONGTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $stmt = $db->prepare("INSERT INTO {$prefx}_ai_settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value2");
    
    $settings = [
        'openai_model' => $_POST['openai_model'] ?? 'gpt-4o-mini',
        'analyze_photos' => $_POST['analyze_photos'] ?? '0',
        'car_type_order' => $_POST['car_type_order'] ?? '',
        'car_type_stock' => $_POST['car_type_stock'] ?? '',
        'image_prompt' => $_POST['image_prompt'] ?? '',
        'ai_prompt' => $_POST['ai_prompt'] ?? ''
    ];
    
    foreach ($settings as $key => $value) {
        $stmt->execute(['key' => $key, 'value' => $value, 'value2' => $value]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
