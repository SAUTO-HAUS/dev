<?php
defined( '_DOIT' ) or die( 'Restricted access' );

// Debug info
echo "<!-- Debug: user_role = " . (isset($user_role) ? $user_role : 'NOT SET') . " -->";

// Check if user is super admin (gordon)
if (!isset($user_role) || $user_role !== 'gordon') {
    echo '<div style="color: red; text-align: center; padding: 50px;">Acces restricționat. Doar Super Admin poate gestiona configurarea numerelor de telefon.<br>';
    echo 'Current role: ' . (isset($user_role) ? $user_role : 'NOT SET') . '</div>';
    return;
}

use App\Services\PhoneReplacementService;

$phoneService = new PhoneReplacementService();

// Handle form submissions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_phone':
            $context = $_POST['context'];
            $phone = $_POST['phone_number'];
            $description = $_POST['description'];
            
            if ($context && $phone) {
                // Update in database
                $query = "UPDATE phone_config SET phone_number = ?, description = ?, updated_at = NOW() WHERE context = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$phone, $description, $context]);
                
                echo '<div style="color: green; margin: 20px 0;">Конфигурация была успешно обновлена!</div>';
            }
            break;
            
    }
}

// Get current phone configuration
try {
    $query = "SELECT * FROM phone_config ORDER BY id";
    $result = $db->query($query);
    $phoneConfigs = $result->fetchAll(PDO::FETCH_ASSOC);
    
    // If no configs exist, create default ones
    if (empty($phoneConfigs)) {
        $defaultConfigs = [
            ['prunkul', '+37379600747', 'Локация Прункул'],
            ['stock_website', '+37379600386', 'Группа Stock-website'],
            ['on_order', '+37379500735', 'Статус "в пути" (в транзите)'],
            ['website_all', '+37379600361', 'Все остальные случаи']
        ];
        
        foreach ($defaultConfigs as $config) {
            $insertQuery = "INSERT INTO phone_config (context, phone_number, description) VALUES (?, ?, ?)";
            $stmt = $db->prepare($insertQuery);
            $stmt->execute($config);
        }
        
        // Re-fetch configs
        $result = $db->query($query);
        $phoneConfigs = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    echo '<div style="color: red; padding: 20px;">Ошибка базы данных: ' . $e->getMessage() . '</div>';
    $phoneConfigs = [];
}

?>

<style>
.phone-config-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.phone-config-header {
    text-align: center;
    margin-bottom: 30px;
    color: #333;
}

.phone-config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.phone-config-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #e2001a;
}

.phone-config-card h3 {
    margin: 0 0 15px 0;
    color: #e2001a;
    font-size: 18px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
}

.form-group input, .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    height: 60px;
    resize: vertical;
}

.phone-config-container .btn {
    background: #e2001a;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
}

.phone-config-container .btn:hover {
    background: #c5001a;
}

.phone-config-container .btn-secondary {
    background: #6c757d;
}

.phone-config-container .btn-secondary:hover {
    background: #545b62;
}

.test-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-top: 30px;
}

.test-result {
    background: #e8f5e8;
    border: 1px solid #4caf50;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
}

.success-msg {
    background: #d4edda;
    color: #155724;
    padding: 10px 15px;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    margin-bottom: 20px;
}

.current-phone {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 10px;
    font-family: monospace;
    font-size: 16px;
    color: #e2001a;
    font-weight: bold;
}

.priority-info {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.priority-info h4 {
    margin: 0 0 10px 0;
    color: #856404;
}

.priority-list {
    list-style: none;
    padding: 0;
}

.priority-list li {
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
}

.priority-list li:last-child {
    border-bottom: none;
}
</style>

<div class="phone-config-container">
    <h2 class="phone-config-header">📞 Управление Конфигурацией Телефонов</h2>
    </div>

    <div class="phone-config-grid">
        <?php foreach ($phoneConfigs as $config): ?>
        <div class="phone-config-card">
            <h3><?php echo htmlspecialchars($config['description']); ?></h3>
            
            <div class="current-phone">
                Текущий номер: <?php echo htmlspecialchars($config['phone_number']); ?>
            </div>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="update_phone">
                <input type="hidden" name="context" value="<?php echo htmlspecialchars($config['context']); ?>">
                
                <div class="form-group">
                    <label>Телефон:</label>
                    <input type="text" 
                           id="phone_<?php echo $config['context']; ?>" 
                           name="phone_number" 
                           value="<?php echo htmlspecialchars($config['phone_number']); ?>"
                           pattern="\+373[0-9]{8}"
                           title="Format: +373XXXXXXXX"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="desc_<?php echo $config['context']; ?>">Описание:</label>
                    <textarea id="desc_<?php echo $config['context']; ?>" 
                              name="description"><?php echo htmlspecialchars($config['description']); ?></textarea>
                </div>
                
                <button type="submit" class="btn">Обновить</button>
            </form>
            
            <div style="margin-top: 15px; font-size: 12px; color: #666;">
                <strong>Context:</strong> <?php echo htmlspecialchars($config['context']); ?><br>
                <strong>Последнее обновление:</strong> <?php echo $config['updated_at']; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>


</div>
