<?php
// Check Facebook setup
try {
    $db = new PDO('mysql:host=localhost;dbname=sauto_db;charset=utf8mb4', 'root', '');
    $prefx = 'gh3sp';
    
    echo "=== FACEBOOK SETTINGS CHECK ===\n";
    
    // Check Facebook settings
    $stmt = $db->prepare("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%facebook%' ORDER BY name");
    $stmt->execute();
    $settings = $stmt->fetchAll();
    
    if (empty($settings)) {
        echo "❌ NO Facebook settings found!\n";
        echo "Need to run: INSERT INTO {$prefx}_settings...\n";
    } else {
        echo "✅ Facebook settings found:\n";
        foreach ($settings as $setting) {
            $value = strlen($setting['value']) > 20 ? substr($setting['value'], 0, 20) . '...' : $setting['value'];
            echo "  - {$setting['name']} = {$value}\n";
        }
    }
    
    echo "\n=== SCHEDULED POSTS TABLE CHECK ===\n";
    
    // Check table structure
    $stmt = $db->prepare("DESCRIBE {$prefx}_scheduled_facebook_posts");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "✅ Table exists with columns:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    // Check for pending posts
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$prefx}_scheduled_facebook_posts WHERE status = 'pending'");
    $stmt->execute();
    $pendingCount = $stmt->fetch()['count'];
    
    echo "\n📊 Pending posts: {$pendingCount}\n";
    
    echo "\n=== SYSTEM READY STATUS ===\n";
    
    $hasSettings = !empty($settings) && count($settings) >= 3;
    $hasTable = !empty($columns);
    
    if ($hasSettings && $hasTable) {
        echo "🚀 SYSTEM READY! Can configure cron job now.\n";
    } else {
        echo "⚠️  SYSTEM NOT READY:\n";
        if (!$hasSettings) echo "  - Missing Facebook settings\n";
        if (!$hasTable) echo "  - Missing scheduled posts table\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
