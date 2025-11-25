<?php
// Simple password protection
$password = 'sauto2025';
$input_pass = isset($_GET['pass']) ? $_GET['pass'] : '';

if ($input_pass !== $password) {
    die('Access denied. Please provide valid password.');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт по внедрению Facebook каталогов - SAUTO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 3px solid #e2001a;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            color: #e2001a;
            margin-bottom: 15px;
        }
        
        .meta-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .meta-info p {
            margin: 5px 0;
            font-size: 0.95rem;
        }
        
        h2 {
            color: #343a40;
            font-size: 1.8rem;
            margin: 40px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        h3 {
            color: #495057;
            font-size: 1.4rem;
            margin: 30px 0 15px 0;
        }
        
        h4 {
            color: #6c757d;
            font-size: 1.1rem;
            margin: 20px 0 10px 0;
        }
        
        p {
            margin: 15px 0;
        }
        
        ul, ol {
            margin: 15px 0 15px 30px;
        }
        
        li {
            margin: 8px 0;
        }
        
        .catalog-box {
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .catalog-box.pruncul {
            border-left-color: #6f42c1;
        }
        
        .catalog-box.orders {
            border-left-color: #fd7e14;
        }
        
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .highlight {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        .success {
            color: #28a745;
            font-weight: 600;
        }
        
        .warning {
            color: #ffc107;
            font-weight: 600;
        }
        
        .error {
            color: #dc3545;
            font-weight: 600;
        }
        
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .checklist {
            list-style: none;
            margin-left: 0;
        }
        
        .checklist li:before {
            content: "✅ ";
            margin-right: 10px;
        }
        
        .url-link {
            color: #007bff;
            text-decoration: none;
            word-break: break-all;
        }
        
        .url-link:hover {
            text-decoration: underline;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
            color: #6c757d;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Отчёт по изменениям - Facebook каталоги</h1>
        </div>
        
        <div class="meta-info">
            <p><strong>Дата:</strong> 25 ноября 2025</p>
            <p><strong>Задача:</strong> Разделение Facebook каталога на 3 отдельных фида</p>
            <p><strong>Сайт:</strong> <a href="https://www.sauto.md" class="url-link" target="_blank">https://www.sauto.md</a></p>
        </div>

        <h2>📝 Что было изменено</h2>

        <h3>1. Новые файлы</h3>
        <ul>
            <li><code>plugins/dev_tools/DataFeedGenerator.php</code> - класс для генерации фидов</li>
            <li><code>plugins/dev_tools/data_feed_pruncul.php</code> - генератор филиала Прункул</li>
            <li><code>plugins/dev_tools/data_feed_orders.php</code> - генератор под заказ</li>
            <li><code>plugins/dev_tools/generate_data_feeds.php</code> - скрипт для cron</li>
            <li><code>sql_scripts/create_data_feed_log_table.sql</code> - таблица логов</li>
            <li><code>view_logs.php</code> - страница мониторинга</li>
        </ul>

        <h3>2. Изменённые файлы</h3>
        <ul>
            <li><code>plugins/dev_tools/data_feed.php</code> - добавлена фильтрация <code>loc='1'</code></li>
            <li><code>plugins/dev_tools/data_feed_orders.php</code> - исправлен URL на <code>/ordercars/</code></li>
            <li><code>content/site/body.php</code> - добавлена валидация <code>catalog_type</code></li>
        </ul>

        <h3>3. Три каталога с фильтрацией</h3>

        <div class="catalog-box">
            <h4>🔵 Основной каталог (df_cars.xml)</h4>
            <div class="code-block">WHERE catalog_type = 'in_stock' 
  AND loc = '1'           -- только основной офис
  AND n_a = 0 
  AND vis = 1 
  AND act = 1</div>
            <p><strong>URL:</strong> <a href="https://www.sauto.md/api/data_feed/df_cars.xml" class="url-link" target="_blank">https://www.sauto.md/api/data_feed/df_cars.xml</a></p>
        </div>

        <div class="catalog-box pruncul">
            <h4>🟣 Филиал Прункул (df_cars_pruncul.xml)</h4>
            <div class="code-block">WHERE catalog_type = 'in_stock' 
  AND loc = '2'           -- только филиал Прункул
  AND n_a = 0 
  AND vis = 1 
  AND act = 1</div>
            <p><strong>URL:</strong> <a href="https://www.sauto.md/api/data_feed/df_cars_pruncul.xml" class="url-link" target="_blank">https://www.sauto.md/api/data_feed/df_cars_pruncul.xml</a></p>
        </div>

        <div class="catalog-box orders">
            <h4>🟠 Под заказ (df_cars_orders.xml)</h4>
            <div class="code-block">WHERE catalog_type = 'on_order' 
  AND offer_timer_end > UNIX_TIMESTAMP()  -- только активные таймеры
  AND n_a = 0 
  AND vis = 1 
  AND act = 1</div>
            <p><strong>URL:</strong> <a href="https://www.sauto.md/api/data_feed/df_cars_orders.xml" class="url-link" target="_blank">https://www.sauto.md/api/data_feed/df_cars_orders.xml</a></p>
            <p class="warning">⚠️ Важно: Автомобили под заказ доступны по URL <code>/ro/ordercars/ID</code></p>
        </div>

        <h3>4. Автоматическое обновление</h3>
        <div class="info-box">
            <p><strong>Cron Job:</strong> Каждый день в <span class="highlight">04:00</span></p>
            <div class="code-block">0 4 * * * cd /home/sautom/public_html/plugins/dev_tools && /usr/local/bin/php generate_data_feeds.php</div>
        </div>

        <h3>5. Страница мониторинга</h3>
        <div class="success-box">
            <p><strong>URL:</strong> <a href="https://www.sauto.md/view_logs.php?pass=sauto2025" class="url-link" target="_blank">https://www.sauto.md/view_logs.php?pass=sauto2025</a></p>
            <p>Показывает: когда прошёл пересчёт, сколько авто добавлено/удалено, итоговое количество.</p>
        </div>

        <h3>6. База данных</h3>
        <p>Таблица <code>gh3sp_data_feed_log</code> - логирование всех изменений</p>

        <div class="footer">
            <p><strong>Система готова к использованию!</strong> 🎉</p>
            <p>Все каталоги генерируются автоматически, логируются и доступны для интеграции с Facebook Commerce Manager.</p>
            <p style="margin-top: 20px; font-size: 0.9rem;">
                <a href="https://www.sauto.md/view_logs.php?pass=sauto2025" class="url-link" target="_blank">Открыть мониторинг</a>
            </p>
        </div>
    </div>
</body>
</html>
