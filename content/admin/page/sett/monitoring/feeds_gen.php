<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUTO - Ручной генератор фидов</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #e2001a 0%, #b30015 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .status-box {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 0 8px 8px 0;
        }
        
        .status-box.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .status-box.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .status-box.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .output {
            background: #1e1e1e;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow-x: auto;
            white-space: pre-wrap;
            margin: 20px 0;
        }
        
        .file-check {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .file-check:last-child {
            border-bottom: none;
        }
        
        .check-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .check-icon.success {
            background: #28a745;
            color: white;
        }
        
        .check-icon.error {
            background: #dc3545;
            color: white;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 10px 5px;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn.success {
            background: #28a745;
        }
        
        .btn.success:hover {
            background: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Генератор фидов Facebook/Yandex</h1>
            <p>Ручная генерация каталогов автомобилей</p>
        </div>
        
        <div class="content">
            <div class="status-box">
                <h3>ℹ️ Информация</h3>
                <p>Этот инструмент регенерирует все фиды из базы данных и удаляет неактивные автомобили.</p>
                <p><strong>Генерируются 3 фида:</strong></p>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Основной каталог (in_stock, основная локация)</li>
                    <li>Филиал Pruncul (in_stock, локация Pruncul)</li>
                    <li>Авто под заказ (on_order с активным таймером)</li>
                </ul>
            </div>

<?php
// Security check - must be in admin panel
if (!defined('_DOIT')) {
    die('Access denied');
}

// Set timezone
date_default_timezone_set('Europe/Chisinau');

// Change to feeds directory
define('_DOIT', 1);

echo "<div class='output'>";
echo "=== SAUTO DATA FEEDS MANUAL GENERATOR ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo "Current directory: " . getcwd() . "\n";
echo "Changing to feeds directory...\n";

chdir('/home/sautom/public_html/plugins/dev_tools');
echo "New directory: " . getcwd() . "\n";

echo "\nChecking required files...\n";
$files_to_check = [
    'generate_data_feeds.php',
    'DataFeedGenerator.php',
    'yandex_feed_generator.php',
    '../../environment.php',
    '../../content/default/functions.php',
];

$all_files_exist = true;
foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $status = $exists ? '✅' : '❌';
    echo "$status $file\n";
    if (!$exists) {
        $all_files_exist = false;
    }
}

if (!$all_files_exist) {
    echo "\n❌ ERROR: Some required files are missing!\n";
    echo "</div>";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🚀 RUNNING FEEDS GENERATION...\n";
echo "Command: /usr/local/bin/php generate_data_feeds.php\n";
echo str_repeat("=", 50) . "\n\n";

$start_time = microtime(true);
exec('/usr/local/bin/php -d display_errors=1 -d error_reporting=E_ALL generate_data_feeds.php 2>&1', $output, $return_code);
$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 2);

// Display output
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
if ($return_code === 0) {
    echo "✅ FEEDS GENERATION COMPLETED SUCCESSFULLY!\n";
} else {
    echo "❌ FEEDS GENERATION FAILED (Exit code: $return_code)\n";
}
echo str_repeat("=", 50) . "\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "🔄 CONVERTING TO YANDEX YML FORMAT...\n";
echo "Command: /usr/local/bin/php cron_yandex_feed.php\n";
echo str_repeat("=", 50) . "\n\n";

$yandex_start_time = microtime(true);
exec('/usr/local/bin/php -d display_errors=1 -d error_reporting=E_ALL cron_yandex_feed.php 2>&1', $yandex_output, $yandex_return_code);
$yandex_end_time = microtime(true);
$yandex_execution_time = round($yandex_end_time - $yandex_start_time, 2);

// Display Yandex output
foreach ($yandex_output as $line) {
    echo $line . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
if ($yandex_return_code === 0) {
    echo "✅ YANDEX CONVERSION COMPLETED SUCCESSFULLY!\n";
} else {
    echo "❌ YANDEX CONVERSION FAILED (Exit code: $yandex_return_code)\n";
}
echo str_repeat("=", 50) . "\n";

echo "</div>";

$status_class = ($return_code === 0 && $yandex_return_code === 0) ? 'success' : 'error';
?>

            <div class="status-box <?php echo $status_class; ?>">
                <h3><?php echo ($return_code === 0 && $yandex_return_code === 0) ? '✅ Генерация завершена успешно!' : '❌ Ошибка генерации'; ?></h3>
                <strong>🕒 Процесс завершен в:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                <strong>⚡ Время генерации фидов:</strong> <?php echo $execution_time; ?> секунд<br>
                <strong>⚡ Время конвертации Yandex:</strong> <?php echo $yandex_execution_time; ?> секунд<br>
                <strong>⚡ Общее время:</strong> <?php echo round($execution_time + $yandex_execution_time, 2); ?> секунд<br>
                <br>
                <strong>🔗 Доступ к фидам:</strong><br>
                <?php
                // Detect feed files
                $feed_files = [
                    '/home/sautom/public_html/api/data_feed/df_cars.xml' => 'Основной каталог (XML)',
                    '/home/sautom/public_html/api/data_feed/df_cars_pruncul.xml' => 'Филиал Pruncul (XML)',
                    '/home/sautom/public_html/api/data_feed/df_cars_orders.xml' => 'Под заказ (XML)',
                    '/home/sautom/public_html/api/data_feed/df_cars_yandex.yml' => 'Yandex YML'
                ];
                
                foreach ($feed_files as $file => $name) {
                    if (file_exists($file)) {
                        $filename = basename($file);
                        $file_size = round(filesize($file) / 1024, 2);
                        $file_time = date('Y-m-d H:i:s', filemtime($file));
                        echo "• <a href='/api/data_feed/{$filename}' target='_blank'>{$name}</a> ({$file_size} KB, обновлен: {$file_time})<br>";
                    }
                }
                ?>
                <br><strong>📋 Просмотр логов:</strong><br>
                <?php
                $cron_log_path = '/home/sautom/public_html/api/data_feed/cron.log';
                $yandex_log_path = '/home/sautom/public_html/plugins/dev_tools/yandex_feed_cron.log';
                
                if (file_exists($cron_log_path)) {
                    $cron_size = round(filesize($cron_log_path) / 1024, 2);
                    echo "• <a href='/api/data_feed/cron.log' target='_blank'>Лог генерации фидов</a> ({$cron_size} KB)<br>";
                }
                
                if (file_exists($yandex_log_path)) {
                    $yandex_size = round(filesize($yandex_log_path) / 1024, 2);
                    echo "• <a href='/plugins/dev_tools/yandex_feed_cron.log' target='_blank'>Лог конвертации Yandex</a> ({$yandex_size} KB)<br>";
                }
                ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="javascript:location.reload()" class="btn success">🔄 Сгенерировать заново</a>
                <a href="/<?php echo $_COOKIE['lang'] ?? 'ro'; ?>/<?php echo $admin_dir ?? 'adminsauto'; ?>/sett/monitoring/feeds" class="btn">📊 Вернуться к мониторингу</a>
            </div>
        </div>
    </div>
</body>
</html>
