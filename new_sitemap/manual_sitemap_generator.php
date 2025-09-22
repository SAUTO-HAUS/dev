<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUTO - Ручной генератор карты сайта</title>
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
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .check-icon.success {
            background: #28a745;
            color: white;
        }
        
        .check-icon.error {
            background: #dc3545;
            color: white;
        }
        
        .timestamp {
            color: #6c757d;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 SAUTO Ручной генератор карты сайта</h1>
            <p>Генерация карты сайта по требованию • Мониторинг в реальном времени • Профессиональный инструмент</p>
        </div>
        
        <div class="content">
            <div class="status-box">
                <strong>🔧 Ручная генерация запущена</strong><br>
                Время: <?php echo date('Y-m-d H:i:s'); ?><br>
                Процесс инициирован пользователем
            </div>

<?php
define('_DOIT', true);

echo "<div class='output'>";
echo "=== SAUTO SITEMAP MANUAL GENERATOR ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo "Current directory: " . getcwd() . "\n";
echo "Changing to sitemap directory...\n";

chdir('/home/sautom/public_html/new_sitemap');
echo "New directory: " . getcwd() . "\n";

echo "\nChecking required files...\n";
$files_to_check = [
    'generate_sitemap_real.php',
    'config.php',
    '../environment.php',
    '../content/default/functions.php',
    '../content/default/config.php',
    '../content/default/dbi.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file MISSING\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🚀 RUNNING SITEMAP GENERATION...\n";
echo "Command: /usr/local/bin/php generate_sitemap_real.php\n";
echo str_repeat("=", 50) . "\n\n";

$start_time = microtime(true);
exec('/usr/local/bin/php -d display_errors=1 -d error_reporting=E_ALL generate_sitemap_real.php 2>&1', $output, $return_code);
$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 2);

echo "📊 EXECUTION COMPLETED\n";
echo "Return code: " . $return_code . "\n";
echo "Execution time: {$execution_time} seconds\n";
echo str_repeat("=", 50) . "\n\n";
echo "📋 OUTPUT:\n";
echo implode("\n", $output);
echo "\n" . str_repeat("=", 50) . "\n";
echo "</div>";


if ($return_code === 0) {
    echo "<div class='status-box success'>";
    echo "<strong>✅ УСПЕХ!</strong><br>";
    echo "Генерация карты сайта завершена успешно<br>";
    echo "Время выполнения: {$execution_time} секунд<br>";
    
    echo "</div>";
} else {
    echo "<div class='status-box error'>";
    echo "<strong>❌ ОШИБКА!</strong><br>";
    echo "Генерация карты сайта не удалась с кодом: {$return_code}<br>";
    echo "Проверьте вывод выше для деталей";
    echo "</div>";
}
?>

            <div class="timestamp">
                <strong>🕒 Процесс завершен в:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                <strong>⚡ Общее время выполнения:</strong> <?php echo $execution_time; ?> секунд<br>
                <strong>🔗 Доступ к картам сайта:</strong><br>
                • <a href="/sitemap.xml" target="_blank">https://www.sauto.md/sitemap.xml</a> (главный индекс)<br>
                <?php
                // Detectează automat toate fișierele sitemap-X.xml pentru timestamp section
                $sitemap_files = glob('/home/sautom/public_html/sitemap-*.xml');
                sort($sitemap_files);
                
                foreach ($sitemap_files as $file) {
                    $filename = basename($file);
                    $file_size = round(filesize($file) / 1024, 2); 
                    echo "• <a href='/{$filename}' target='_blank'>https://www.sauto.md/{$filename}</a> ({$file_size} KB)<br>";
                }
                ?>
                <br><strong>📋 Просмотр логов:</strong><br>
                <?php
                // Afișează doar ultimele 20 de linii din fiecare log
                $cron_log_path = '/home/sautom/public_html/new_sitemap/cron.log';
                $gen_log_path = '/home/sautom/public_html/new_sitemap/sitemap_generation_real.log';
                
                if (file_exists($cron_log_path)) {
                    $cron_size = round(filesize($cron_log_path) / 1024, 2);
                    echo "• <a href='/new_sitemap/cron.log?tail=20' target='_blank'>https://www.sauto.md/new_sitemap/cron.log</a> (последние записи, {$cron_size} KB)<br>";
                }
                
                if (file_exists($gen_log_path)) {
                    $gen_size = round(filesize($gen_log_path) / 1024, 2);
                    echo "• <a href='/new_sitemap/sitemap_generation_real.log?tail=20' target='_blank'>https://www.sauto.md/new_sitemap/sitemap_generation_real.log</a> (последние записи, {$gen_size} KB)<br>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
