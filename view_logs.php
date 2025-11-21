<?php
/**
 * Facebook Data Feeds - Change Logs Viewer
 * 
 * Simple monitoring page to view feed generation history and changes.
 * Shows when feeds were regenerated and what changed (added/removed cars).
 * 
 * @author SAUTO Development Team
 * @created 2025-11-21
 */

// Security check - password protection
$password = 'sauto2025';
$authenticated = false;

if (isset($_GET['pass']) && $_GET['pass'] === $password) {
    $authenticated = true;
}

if (!$authenticated) {
    http_response_code(403);
    die('Access denied. Use: ?pass=sauto2025');
}

// Database connection
define('_DOIT', 1);
define('_DEFAULT', $_SERVER["DOCUMENT_ROOT"].'/content/default');
require_once (_DEFAULT.'/defines.php');
require_once ($_SERVER["DOCUMENT_ROOT"].'/environment.php');
require_once (_DEFAULT.'/config.php');
require_once (_DEFAULT.'/dbi.php');

// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Auto-refresh functionality
$autoRefresh = isset($_GET['refresh']) ? (int)$_GET['refresh'] : 30;

// Get number of log entries to display
$logLimit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

// Fetch latest statistics
try {
    $sqlLatest = "SELECT * FROM {$prefx}_data_feed_latest_stats ORDER BY feed_type";
    $stmtLatest = $db->query($sqlLatest);
    $latestStats = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If view doesn't exist, fetch directly from table
    $sqlLatest = "SELECT feed_type, generation_date, cars_added, cars_removed, total_cars, execution_time, status 
                  FROM {$prefx}_data_feed_log 
                  WHERE id IN (
                      SELECT MAX(id) FROM {$prefx}_data_feed_log GROUP BY feed_type
                  ) 
                  ORDER BY feed_type";
    $stmtLatest = $db->query($sqlLatest);
    $latestStats = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch recent log entries
$sqlLogs = "SELECT * FROM {$prefx}_data_feed_log ORDER BY generation_date DESC LIMIT :limit";
$stmtLogs = $db->prepare($sqlLogs);
$stmtLogs->bindValue(':limit', $logLimit, PDO::PARAM_INT);
$stmtLogs->execute();
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalCars = 0;
foreach ($latestStats as $stat) {
    $totalCars += $stat['total_cars'];
}

// Feed type names in Russian
$feedNames = [
    'main' => 'Основной каталог',
    'pruncul' => 'Филиал Pruncul',
    'orders' => 'Авто под заказ'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUTO - Facebook Data Feeds Logs</title>
    <?php if ($autoRefresh > 0): ?>
    <meta http-equiv="refresh" content="<?= $autoRefresh ?>">
    <?php endif; ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #e2001a 0%, #b30015 100%);
            color: white;
            padding: 30px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header p {
            opacity: 0.95;
            font-size: 1.1rem;
        }
        
        .controls {
            background: white;
            padding: 20px 30px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .controls a {
            color: #e2001a;
            text-decoration: none;
            padding: 8px 16px;
            border: 2px solid #e2001a;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .controls a:hover {
            background: #e2001a;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 30px;
            background: white;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 12px;
            border-left: 5px solid #e2001a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            color: #e2001a;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .stat-row:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
        
        .stat-value {
            font-weight: 700;
            color: #212529;
        }
        
        .stat-value.success {
            color: #28a745;
        }
        
        .stat-value.error {
            color: #dc3545;
        }
        
        .log-section {
            background: white;
            margin: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .log-header {
            background: #343a40;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-header h2 {
            font-size: 1.5rem;
        }
        
        .log-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .log-table th {
            background: #495057;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .log-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .log-table tr:hover {
            background: #f8f9fa;
        }
        
        .feed-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .feed-badge.main {
            background: #007bff;
            color: white;
        }
        
        .feed-badge.pruncul {
            background: #6f42c1;
            color: white;
        }
        
        .feed-badge.orders {
            background: #fd7e14;
            color: white;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .change-indicator {
            font-weight: 600;
        }
        
        .change-indicator.positive {
            color: #28a745;
        }
        
        .change-indicator.negative {
            color: #dc3545;
        }
        
        .footer {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 0 0 12px 12px;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .log-table {
                font-size: 0.85rem;
            }
            
            .log-table th,
            .log-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Facebook Data Feeds - Мониторинг</h1>
            <p>Отслеживание изменений в каталогах автомобилей для Facebook рекламы</p>
        </div>
        
        <div class="controls">
            <a href="javascript:location.reload()">🔄 Обновить</a>
            <a href="?pass=<?= $password ?>&refresh=0">⏸️ Отключить авто-обновление</a>
            <a href="?pass=<?= $password ?>&refresh=30">⏱️ Авто-обновление 30с</a>
            <a href="?pass=<?= $password ?>&limit=50">📄 50 записей</a>
            <a href="?pass=<?= $password ?>&limit=100">📄 100 записей</a>
        </div>
        
        <!-- Current Statistics -->
        <div class="stats-grid">
            <?php foreach ($latestStats as $stat): ?>
            <div class="stat-card">
                <h3><?= $feedNames[$stat['feed_type']] ?></h3>
                <div class="stat-row">
                    <span class="stat-label">Всего автомобилей:</span>
                    <span class="stat-value"><?= number_format($stat['total_cars']) ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Последнее обновление:</span>
                    <span class="stat-value"><?= date('d.m.Y H:i', strtotime($stat['generation_date'])) ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Добавлено:</span>
                    <span class="stat-value positive">+<?= $stat['cars_added'] ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Удалено:</span>
                    <span class="stat-value negative">-<?= $stat['cars_removed'] ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Время генерации:</span>
                    <span class="stat-value"><?= $stat['execution_time'] ?>с</span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Статус:</span>
                    <span class="stat-value <?= $stat['status'] ?>"><?= $stat['status'] === 'success' ? '✅ Успешно' : '❌ Ошибка' ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Total Summary -->
            <div class="stat-card" style="border-left-color: #28a745;">
                <h3>📈 Общая статистика</h3>
                <div class="stat-row">
                    <span class="stat-label">Всего автомобилей:</span>
                    <span class="stat-value" style="color: #28a745; font-size: 1.5rem;"><?= number_format($totalCars) ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Активных каталогов:</span>
                    <span class="stat-value">3</span>
                </div>
            </div>
        </div>
        
        <!-- Recent Changes Log -->
        <div class="log-section">
            <div class="log-header">
                <h2>📜 История изменений</h2>
                <span>Последние <?= $logLimit ?> записей</span>
            </div>
            <div style="overflow-x: auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Дата и время</th>
                            <th>Каталог</th>
                            <th>Добавлено</th>
                            <th>Удалено</th>
                            <th>Всего</th>
                            <th>Время (с)</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d.m.Y H:i:s', strtotime($log['generation_date'])) ?></td>
                            <td>
                                <span class="feed-badge <?= $log['feed_type'] ?>">
                                    <?= $feedNames[$log['feed_type']] ?>
                                </span>
                            </td>
                            <td>
                                <span class="change-indicator positive">+<?= $log['cars_added'] ?></span>
                            </td>
                            <td>
                                <span class="change-indicator negative">-<?= $log['cars_removed'] ?></span>
                            </td>
                            <td><strong><?= number_format($log['total_cars']) ?></strong></td>
                            <td><?= $log['execution_time'] ?></td>
                            <td>
                                <span class="status-badge <?= $log['status'] ?>">
                                    <?= $log['status'] === 'success' ? '✅ Успех' : '❌ Ошибка' ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($log['error_message']): ?>
                        <tr>
                            <td colspan="7" style="background: #fff3cd; color: #856404; padding: 10px;">
                                <strong>Ошибка:</strong> <?= htmlspecialchars($log['error_message']) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>🕒 Последнее обновление страницы:</strong> <?= date('d.m.Y H:i:s') ?></p>
            <?php if ($autoRefresh > 0): ?>
            <p>⏱️ Авто-обновление через <?= $autoRefresh ?> секунд</p>
            <?php endif; ?>
            <p style="margin-top: 15px;">
                <strong>🔗 Ссылки на фиды:</strong><br>
                <a href="/api/data_feed/df_cars.xml" target="_blank" style="color: #007bff;">Основной</a> | 
                <a href="/api/data_feed/df_cars_pruncul.xml" target="_blank" style="color: #6f42c1;">Pruncul</a> | 
                <a href="/api/data_feed/df_cars_orders.xml" target="_blank" style="color: #fd7e14;">Orders</a>
            </p>
        </div>
    </div>
</body>
</html>
