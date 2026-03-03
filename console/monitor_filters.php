<?php

define('_DOIT', 1);
define('_DEFAULT', 'content/default');

$docRoot = dirname(__DIR__);
$_SERVER['DOCUMENT_ROOT'] = $docRoot;
$_SERVER['HTTP_HOST'] = 'sauto.md';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$SITE_URL           = 'https://sauto.md';
$FACET_ENDPOINT     = '/content/site/ajax/get_facets.php';
$RESPONSE_TIMEOUT   = 10;       
$MAX_RESPONSE_TIME  = 5;        
$MIN_EXPECTED_CARS  = 10;        
$ALERT_COOLDOWN_MIN = 30;       
$LOG_FILE           = $docRoot . '/console/logs/filter_monitor.log';
$COOLDOWN_FILE      = $docRoot . '/console/logs/filter_monitor_cooldown.txt';

$ALERT_EMAILS       = ['vlad@sauto.md', 'botnarenco1996@mail.com'];
$ALERT_FROM_EMAIL   = 'monitor@sauto.md';
$ALERT_FROM_NAME    = 'SAUTO Monitor';

$logDir = dirname($LOG_FILE);
if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }

$results = [];
$errors = [];
$startTime = microtime(true);

function monitorLog($msg, $level = 'INFO') {
    global $LOG_FILE;
    $ts = date('Y-m-d H:i:s');
    $line = "[{$ts}] [{$level}] {$msg}\n";
    @file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    if (php_sapi_name() === 'cli') {
        echo $line;
    }
}

monitorLog('Запуск проверки фильтров');

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $SITE_URL . $FACET_ENDPOINT,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['page' => 'in_stock', 'lang' => 'ro']),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => $RESPONSE_TIMEOUT,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT      => 'SAUTO-FilterMonitor/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$responseTime = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 3);
$curlError = curl_error($ch);
curl_close($ch);

$results['http_code'] = $httpCode;
$results['response_time'] = $responseTime . 's';

if ($httpCode !== 200) {
    $errors[] = "API вернул HTTP {$httpCode}" . ($curlError ? " ({$curlError})" : '');
    monitorLog("ОШИБКА: Facet API — HTTP {$httpCode}. cURL: {$curlError}", 'ERROR');
} else {
    monitorLog("OK: Facet API — HTTP 200 за {$responseTime}s");
}

if ($responseTime > $MAX_RESPONSE_TIME) {
    $errors[] = "Время ответа {$responseTime}s превышает порог {$MAX_RESPONSE_TIME}s";
    monitorLog("МЕДЛЕННО: Facet API — {$responseTime}s (порог: {$MAX_RESPONSE_TIME}s)", 'WARN');
}

if ($response) {
    $data = json_decode($response, true);

    if ($data === null) {
        $errors[] = 'Невалидный JSON ответ';
        monitorLog('ОШИБКА: Невалидный JSON от Facet API', 'ERROR');
    } elseif (!isset($data['success']) || $data['success'] !== true) {
        $errors[] = 'API вернул success=false: ' . ($data['error'] ?? 'неизвестно');
        monitorLog('ОШИБКА: API success=false: ' . ($data['error'] ?? ''), 'ERROR');
    } else {
        monitorLog('OK: Валидный JSON, success=true');

        $total = $data['data']['total'] ?? 0;
        $results['total_cars'] = $total;

        if ($total < $MIN_EXPECTED_CARS) {
            $errors[] = "Всего {$total} машин (ожидается >= {$MIN_EXPECTED_CARS})";
            monitorLog("ОШИБКА: {$total} машин в каталоге (мин: {$MIN_EXPECTED_CARS})", 'ERROR');
        } else {
            monitorLog("OK: {$total} машин в каталоге");
        }

        $facets = $data['data']['facets'] ?? [];
        $emptyFacets = [];
        foreach (['br', 'bt', 'fl', 'tra'] as $key) {
            if (empty($facets[$key])) {
                $emptyFacets[] = $key;
            }
        }

        if (!empty($emptyFacets)) {
            $errors[] = 'Пустые фасеты: ' . implode(', ', $emptyFacets);
            monitorLog('ОШИБКА: Пустые фасеты: ' . implode(', ', $emptyFacets), 'ERROR');
        } else {
            $counts = [];
            foreach ($facets as $k => $v) { $counts[] = "{$k}=" . count($v); }
            monitorLog('OK: Фасеты заполнены: ' . implode(', ', $counts));
        }
    }
}

try {
    require_once $docRoot . '/environment.php';
    require_once $docRoot . '/' . _DEFAULT . '/defines.php';
    require_once $docRoot . '/' . _DEFAULT . '/functions.php';

    $dsn = "mysql:host=" . SQL_HOST . ";dbname=" . SQL_DB . ";charset=" . (defined('SQL_CHARSET') ? SQL_CHARSET : 'utf8mb4');
    $dbStart = microtime(true);
    $db = new PDO($dsn, SQL_USER, SQL_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);

    $stmt = $db->query("SELECT COUNT(*) as cnt FROM gh3sp_car_ctlg WHERE vis='1' AND act='1'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $dbTime = round(microtime(true) - $dbStart, 3);

    $results['db_cars'] = (int)$row['cnt'];
    $results['db_time'] = $dbTime . 's';
    monitorLog("OK: БД — {$row['cnt']} активных машин за {$dbTime}s");

    $db = null;
} catch (Exception $e) {
    $errors[] = 'Ошибка БД: ' . $e->getMessage();
    monitorLog('ОШИБКА: БД — ' . $e->getMessage(), 'ERROR');
}

$cacheDir = $docRoot . '/cache';
if (!is_writable($cacheDir)) {
    $errors[] = 'Директория кэша недоступна для записи';
    monitorLog('ОШИБКА: Кэш недоступен: ' . $cacheDir, 'ERROR');
} else {
    $cacheCount = count(glob($cacheDir . '/facet_*.cache'));
    $results['cache_files'] = $cacheCount;
    monitorLog("OK: Кэш доступен, файлов фасетов: {$cacheCount}");
}

$totalTime = round(microtime(true) - $startTime, 3);
$hasError = !empty($errors);
$results['status'] = $hasError ? 'FAIL' : 'OK';
$results['errors'] = $errors;
$results['check_time'] = $totalTime . 's';
$results['timestamp'] = date('Y-m-d H:i:s');

monitorLog("Проверка завершена за {$totalTime}s — Статус: " . $results['status']);

if ($hasError) {
    $shouldAlert = true;
    if (file_exists($COOLDOWN_FILE)) {
        $lastAlert = (int)file_get_contents($COOLDOWN_FILE);
        if (time() - $lastAlert < $ALERT_COOLDOWN_MIN * 60) {
            $shouldAlert = false;
            monitorLog("Алерт пропущен (cooldown: {$ALERT_COOLDOWN_MIN} мин)");
        }
    }

    if ($shouldAlert) {
        try {
            require_once $docRoot . '/plugins/PHPMailer/src/Exception.php';
            require_once $docRoot . '/plugins/PHPMailer/src/PHPMailer.php';
            require_once $docRoot . '/plugins/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($ALERT_FROM_EMAIL, $ALERT_FROM_NAME);

            foreach ($ALERT_EMAILS as $email) {
                $mail->addAddress($email);
            }

            $mail->isHTML(true);
            $mail->Subject = 'SAUTO.md — Проблема с фильтрами';

            $errorList = '';
            foreach ($errors as $err) {
                $errorList .= "<li>{$err}</li>";
            }

            $mail->Body = "
                <h2 style='color:#e2001a;'>Проблема с фильтрами на SAUTO.md</h2>
                <p><b>Время:</b> {$results['timestamp']}</p>
                <h3>Обнаруженные проблемы:</h3>
                <ul>{$errorList}</ul>
                <h3>Детали:</h3>
                <table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse;font-family:monospace;'>
                    <tr><td>HTTP код</td><td>" . ($results['http_code'] ?? '-') . "</td></tr>
                    <tr><td>Время ответа API</td><td>" . ($results['response_time'] ?? '-') . "</td></tr>
                    <tr><td>Машин (API)</td><td>" . ($results['total_cars'] ?? '-') . "</td></tr>
                    <tr><td>Машин (DB)</td><td>" . ($results['db_cars'] ?? '-') . "</td></tr>
                    <tr><td>Время запроса DB</td><td>" . ($results['db_time'] ?? '-') . "</td></tr>
                    <tr><td>Файлов кэша</td><td>" . ($results['cache_files'] ?? '-') . "</td></tr>
                </table>
                <br/><p style='color:#999;font-size:11px;'>Автоматическая проверка. Повтор через {$ALERT_COOLDOWN_MIN} мин при сохранении проблемы.</p>
            ";

            $mail->AltBody = "SAUTO.md — Проблема с фильтрами\nВремя: {$results['timestamp']}\nОшибки: " . implode('; ', $errors);

            $mail->send();
            monitorLog('Email отправлен: ' . implode(', ', $ALERT_EMAILS));
            @file_put_contents($COOLDOWN_FILE, time());

        } catch (Exception $e) {
            monitorLog('Не удалось отправить email: ' . $e->getMessage(), 'ERROR');
        }
    }
} else {
    if (file_exists($COOLDOWN_FILE)) {
        @unlink($COOLDOWN_FILE);
    }
}

if (php_sapi_name() === 'cli' || isset($_GET['json'])) {
    echo "\n" . json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
