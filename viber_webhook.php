<?php
/**
 * Viber Bot Webhook
 * URL: https://www.sauto.md/viber_webhook.php
 */

// ── Respond immediately to Viber verification BEFORE any includes ─────────────
$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

// Log raw request early (before includes that might fail)
$log = __DIR__ . '/logs/viber_early.log';
$ip  = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
file_put_contents($log, date('Y-m-d H:i:s') . ' IP=' . $ip . ' ' . substr($raw, 0, 500) . PHP_EOL, FILE_APPEND | LOCK_EX);

http_response_code(200);
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Accel-Buffering: no');

if (!is_array($payload)) {
    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

$event = $payload['event'] ?? '';

// Viber sends a webhook verification event on set_webhook — respond immediately
if ($event === 'webhook') {
    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

// ── Load dependencies only for real events ────────────────────────────────────
try {
    define('_DOIT', 1);
    require_once __DIR__ . '/environment.php';
    require_once __DIR__ . '/content/default/defines.php';
    require_once __DIR__ . '/content/default/functions.php';
    require_once __DIR__ . '/content/default/config.php';
    require_once __DIR__ . '/content/default/dbi.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_core.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_inbox_core.php';
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/viber_error.log',
        date('Y-m-d H:i:s') . ' INIT ERROR: ' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

// Log for debugging
$log2 = __DIR__ . '/logs/viber_webhook.log';
if (is_writable(dirname($log2))) {
    file_put_contents($log2, date('Y-m-d H:i:s') . ' ' . substr($raw, 0, 1000) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$bot_token = crm_get_setting($db, $prefx, 'viber_bot_token', '');
$dept      = 'stock';

// Validate auth token
$auth_header = $_SERVER['HTTP_X_VIBER_AUTH_TOKEN'] ?? '';
if ($bot_token && $auth_header && $auth_header !== $bot_token) {
    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

// Handle conversation_started — send welcome message
if ($event === 'conversation_started') {
    $sender_id   = $payload['user']['id']   ?? '';
    $sender_name = $payload['user']['name'] ?? '';

    if ($sender_id && $bot_token) {
        $welcome = crm_get_setting($db, $prefx, 'viber_welcome_msg',
            'Bună ziua! Sunt asistentul auto Sauto-Haus. Cu ce vă pot ajuta?');
        inbox_send_viber_message($sender_id, $welcome, $bot_token);
    }

    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

// Handle incoming message
if ($event !== 'message') {
    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

$sender_id   = $payload['sender']['id']   ?? '';
$sender_name = $payload['sender']['name'] ?? '';
$text        = $payload['message']['text'] ?? '';
$mid         = (string)($payload['message_token'] ?? '');
$msg_type    = $payload['message']['type'] ?? 'text';

if (!$sender_id) {
    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

// Handle incoming picture/video from customer
if ($msg_type === 'picture' || $msg_type === 'video') {
    $media_url = $payload['message']['media'] ?? '';
    if ($media_url) {
        $session_id = inbox_create_session($db, $prefx, 'viber', $sender_id, $sender_name, '', $dept);
        $session    = inbox_get_session($db, $prefx, 'viber', $sender_id);
        $caption    = $payload['message']['text'] ?? '';
        $body       = '[photo:' . $media_url . ']' . ($caption !== '' ? "\n" . $caption : '');
        inbox_save_message($db, $prefx, $session_id, 'viber', 'in',
            $sender_id, $sender_name, $body,
            $session->lead_id ?? null, false, null, '', $mid);
        if ($session && !$session->ai_active && $session->lead_id) {
            $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")
               ->execute([':id' => $session->lead_id]);
        }
    }
    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

if ($msg_type !== 'text' || !$text) {
    echo json_encode(['status' => 0, 'status_message' => 'ok']);
    exit;
}

try {
    $session_id = inbox_create_session($db, $prefx, 'viber', $sender_id, $sender_name, '', $dept);
    $session    = inbox_get_session($db, $prefx, 'viber', $sender_id);

    inbox_save_message($db, $prefx, $session_id, 'viber', 'in',
        $sender_id, $sender_name, $text,
        $session->lead_id ?? null, false, null, '', $mid);

    if ($session && !$session->ai_active) {
        if ($session->lead_id) {
            $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")
               ->execute([':id' => $session->lead_id]);
        }
        echo json_encode(['status' => 0, 'status_message' => 'ok']);
        exit;
    }

    $history = inbox_get_history($db, $prefx, $session_id, 50);
    $ai      = inbox_ai_reply($db, $prefx, 'viber', $history, $text);

    if ($ai['reply'] && $bot_token) {
        inbox_send_viber_message($sender_id, $ai['reply'], $bot_token);
        inbox_save_message($db, $prefx, $session_id, 'viber', 'out',
            'ai', 'AI Sauto', $ai['reply'],
            $session->lead_id ?? null, true);
    }

    $pg_id = $session->page_id ?? '';
    if ($ai['trigger'] && $ai['phone']) {
        inbox_create_lead($db, $prefx, $session_id, $ai['phone'], 'viber', $dept, null, null, $pg_id);
    }
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/viber_error.log',
        date('Y-m-d H:i:s') . ' HANDLE ERROR: ' . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
}

echo json_encode(['status' => 0, 'status_message' => 'ok']);
$db = null;
exit;
