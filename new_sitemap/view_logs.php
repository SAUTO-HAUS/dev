<?php
/**
 * SAUTO Sitemap Logs Viewer
 * Simple page to view cron.log and sitemap_generation_real.log
 */

// Security check - only allow from specific IPs or with password
$allowedIPs = ['127.0.0.1', '::1']; // Add your IPs here
$clientIP = $_SERVER['REMOTE_ADDR'] ?? '';

// Simple password protection (optional)
$password = 'sauto2025'; // Change this password
$authenticated = false;

if (isset($_GET['pass']) && $_GET['pass'] === $password) {
    $authenticated = true;
} elseif (in_array($clientIP, $allowedIPs)) {
    $authenticated = true;
}

if (!$authenticated) {
    http_response_code(403);
    die('Access denied. Use: ?pass=sauto2025');
}

// Log file paths
$cronLogPath = __DIR__ . '/cron.log';
$sitemapLogPath = __DIR__ . '/sitemap_generation_real.log';

// Function to read log file with tail functionality
function readLogFile($filePath, $lines = 100) {
    if (!file_exists($filePath)) {
        return "Log file not found: " . basename($filePath);
    }
    
    $content = file_get_contents($filePath);
    if (empty($content)) {
        return "Log file is empty: " . basename($filePath);
    }
    
    // Get last N lines
    $allLines = explode("\n", $content);
    $lastLines = array_slice($allLines, -$lines);
    
    return implode("\n", $lastLines);
}

// Auto-refresh functionality
$autoRefresh = isset($_GET['refresh']) ? (int)$_GET['refresh'] : 30;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUTO - Sitemap Logs Viewer</title>
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
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 20px;
            line-height: 1.4;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: #2d3748;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            border-bottom: 3px solid #e2001a;
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #e2001a;
        }
        
        .controls {
            background: #2d3748;
            padding: 15px 20px;
            border-bottom: 1px solid #4a5568;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .controls a {
            color: #63b3ed;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #63b3ed;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .controls a:hover {
            background: #63b3ed;
            color: white;
        }
        
        .log-section {
            background: #2d3748;
            margin-bottom: 20px;
            border-radius: 0 0 8px 8px;
        }
        
        .log-header {
            background: #4a5568;
            padding: 15px 20px;
            border-bottom: 1px solid #718096;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-header h2 {
            color: #e2001a;
            font-size: 1.2rem;
        }
        
        .log-info {
            font-size: 0.9rem;
            color: #a0aec0;
        }
        
        .log-content {
            background: #1a202c;
            padding: 20px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #4a5568;
        }
        
        .log-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .log-content::-webkit-scrollbar-track {
            background: #2d3748;
        }
        
        .log-content::-webkit-scrollbar-thumb {
            background: #4a5568;
            border-radius: 4px;
        }
        
        .timestamp {
            color: #68d391;
        }
        
        .error {
            color: #fc8181;
        }
        
        .success {
            color: #68d391;
        }
        
        .info {
            color: #63b3ed;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #a0aec0;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .log-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗂️ SAUTO Sitemap Logs</h1>
            <p>Мониторинг генерации карты сайта и cron заданий в реальном времени</p>
        </div>
        
        <div class="controls">
            <a href="javascript:location.reload()">🔄 Refresh</a>
        </div>
        
        <!-- Cron Log Section -->
        <div class="log-section">
            <div class="log-header">
                <h2>📅 Cron Log</h2>
                <div class="log-info">
                    <?php if (file_exists($cronLogPath)): ?>
                        Last modified: <?= date('Y-m-d H:i:s', filemtime($cronLogPath)) ?>
                        | Size: <?= number_format(filesize($cronLogPath)) ?> bytes
                    <?php else: ?>
                        File not found
                    <?php endif; ?>
                </div>
            </div>
            <div class="log-content"><?= htmlspecialchars(readLogFile($cronLogPath, $_GET['lines'] ?? 100)) ?></div>
        </div>
        
        <!-- Sitemap Generation Log Section -->
        <div class="log-section">
            <div class="log-header">
                <h2>🗺️ Sitemap Generation Log</h2>
                <div class="log-info">
                    <?php if (file_exists($sitemapLogPath)): ?>
                        Last modified: <?= date('Y-m-d H:i:s', filemtime($sitemapLogPath)) ?>
                        | Size: <?= number_format(filesize($sitemapLogPath)) ?> bytes
                    <?php else: ?>
                        File not found
                    <?php endif; ?>
                </div>
            </div>
            <div class="log-content"><?= htmlspecialchars(readLogFile($sitemapLogPath, $_GET['lines'] ?? 100)) ?></div>
        </div>
        
        <div class="footer">
            <p>🕒 Last updated: <?= date('Y-m-d H:i:s') ?> 
            <?php if ($autoRefresh > 0): ?>
                | Auto-refresh: <?= $autoRefresh ?>s
            <?php endif; ?>
            </p>
            <p>SAUTO Sitemap Monitoring System</p>
        </div>
    </div>
    
    <script>
        // Highlight log entries
        document.addEventListener('DOMContentLoaded', function() {
            const logContents = document.querySelectorAll('.log-content');
            
            logContents.forEach(function(content) {
                let html = content.innerHTML;
                
                // Highlight timestamps
                html = html.replace(/(\[?\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]?)/g, '<span class="timestamp">$1</span>');
                
                // Highlight errors
                html = html.replace(/(ERROR|ОШИБКА|Failed|failed|Error)/gi, '<span class="error">$1</span>');
                
                // Highlight success
                html = html.replace(/(SUCCESS|УСПЕХ|Success|success|successfully|завершено|completed)/gi, '<span class="success">$1</span>');
                
                // Highlight info
                html = html.replace(/(INFO|ИНФО|Started|started|начал|Получено)/gi, '<span class="info">$1</span>');
                
                content.innerHTML = html;
            });
        });
    </script>
</body>
</html>
