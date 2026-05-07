<?php
/**
 * Moldcell PBX Hook
 * URL: https://www.sauto.md/pbx_hook.php
 */

$raw     = file_get_contents('php://input');

// Parse payload: JSON or form-urlencoded
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    parse_str($raw, $payload);
}
// Also merge $_POST in case PHP parsed it automatically
if (empty($payload) && !empty($_POST)) {
    $payload = $_POST;
}

// Log early
$log = __DIR__ . '/logs/pbx_early.log';
$ip  = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
file_put_contents($log, date('Y-m-d H:i:s') . ' IP=' . $ip . ' ' . substr($raw, 0, 1000) . PHP_EOL, FILE_APPEND | LOCK_EX);

http_response_code(200);
header('Content-Type: application/json');

// Validate auth key from Moldcell PBX
$auth_key = $_SERVER['HTTP_X_AUTH_TOKEN']
    ?? $_SERVER['HTTP_AUTHORIZATION']
    ?? ($payload['crm_token'] ?? $payload['key'] ?? $payload['token'] ?? '');
$expected_key = '62656e74';
if ($auth_key && $auth_key !== $expected_key) {
    echo json_encode(['status' => 'forbidden']);
    exit;
}

// Handle 'contact' command — must return JSON response (not just ok)
$command = $payload['cmd'] ?? $payload['command'] ?? $payload['event'] ?? $payload['type'] ?? '';

// Load dependencies
try {
    define('_DOIT', 1);
    define('_PBX_HOOK', 1);
    require_once __DIR__ . '/environment.php';
    require_once __DIR__ . '/content/default/defines.php';
    require_once __DIR__ . '/content/default/functions.php';
    require_once __DIR__ . '/content/default/config.php';
    require_once __DIR__ . '/content/default/dbi.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_core.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_stt.php';
    require_once __DIR__ . '/content/admin/include/crm/pbx_webhook_handler.php';
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/pbx_error.log',
        date('Y-m-d H:i:s') . ' INIT ERROR: ' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['status' => 'ok']);
    exit;
}

try {
    if ($command === 'contact') {
        pbx_handle_contact($payload, $db, $prefx);
    } elseif ($command === 'rating') {
        pbx_handle_rating($payload, $db, $prefx);
        echo json_encode(['status' => 'ok']);
    } elseif ($command === 'event') {
        pbx_handle_event($payload, $db, $prefx);
        echo json_encode(['status' => 'ok']);
    } elseif ($command === 'history') {
        pbx_handle_history($payload, $db, $prefx);
        echo json_encode(['status' => 'ok']);
    } else {
        pbx_handle_history($payload, $db, $prefx);
        echo json_encode(['status' => 'ok']);
    }
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/pbx_error.log',
        date('Y-m-d H:i:s') . ' HANDLE ERROR: ' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['status' => 'ok']);
}

$db = null;
exit;
