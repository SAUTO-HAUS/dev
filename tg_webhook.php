<?php
/**
 * Telegram Bot Webhook
 * URL: https://www.sauto.md/tg_webhook.php
 *
 * Register with:
 *   https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://www.sauto.md/tg_webhook.php
 *
 * Supports multiple bots (regular + order) from gh3sp_settings:
 *   regular_telegram_bot_token / order_telegram_bot_token
 */

define('_DOIT', 1);
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/content/default/defines.php';
require_once __DIR__ . '/content/default/functions.php';
require_once __DIR__ . '/content/default/config.php';
require_once __DIR__ . '/content/default/dbi.php';
require_once __DIR__ . '/content/admin/include/crm/crm_core.php';
require_once __DIR__ . '/content/admin/include/crm/crm_inbox_core.php';

$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

// Log for debugging
$log = __DIR__ . '/logs/tg_webhook.log';
if (is_writable(dirname($log))) {
    file_put_contents($log, date('Y-m-d H:i:s') . ' URI=' . ($_SERVER['REQUEST_URI'] ?? '') . ' ' . substr($raw, 0, 1000) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

if (!is_array($payload)) {
    http_response_code(200);
    echo 'ok';
    exit;
}

// Determine which bot received this by matching token in URL path
$tg_settings      = inbox_get_tg_settings($db, $prefx);
$bot_token        = '';
$dept             = 'stock';
$regular_token    = $tg_settings['regular_telegram_chat_bot_token'] ?? '';
$order_token      = $tg_settings['order_telegram_chat_bot_token']   ?? '';

// Telegram sends to the URL we registered — identify by bot_id in token
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$order_bot_id   = $order_token   ? explode(':', $order_token)[0]   : '';
$regular_bot_id = $regular_token ? explode(':', $regular_token)[0] : '';

// Check X-Telegram-Bot-Api-Secret-Token header or identify from payload
// Best method: check which bot_id matches the token used
// Telegram doesn't tell us which bot, so we identify by chat_id matching
// For now: try to match by checking if update came via order bot URL
if ($order_bot_id && strpos($request_uri, $order_bot_id) !== false) {
    $bot_token = $order_token;
    $dept      = 'order';
} elseif ($regular_bot_id && strpos($request_uri, $regular_bot_id) !== false) {
    $bot_token = $regular_token;
    $dept      = 'stock';
} else {
    // Fallback: use secret token header if set, otherwise check pending updates
    // Register bots with different paths to distinguish them
    // tg_webhook.php?bot=order or tg_webhook.php?bot=regular
    $bot_param = $_GET['bot'] ?? '';
    if ($bot_param === 'order' && $order_token) {
        $bot_token = $order_token;
        $dept      = 'order';
    } elseif ($bot_param === 'regular' && $regular_token) {
        $bot_token = $regular_token;
        $dept      = 'stock';
    } else {
        // Last fallback: regular
        $bot_token = $regular_token ?: $order_token;
        $dept      = $regular_token ? 'stock' : 'order';
    }
}

// Handle message — ignore channel_post (bot cannot reply to channels)
$message = $payload['message'] ?? null;
if (!$message) {
    http_response_code(200);
    echo 'ok';
    exit;
}

$chat_id     = (string)($message['chat']['id'] ?? '');
$sender_name = trim(($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? ''));
$sender_name = trim($sender_name) ?: ($message['from']['username'] ?? '');
$text        = $message['text'] ?? ($message['caption'] ?? '');
$mid         = (string)($message['message_id'] ?? '');

// Handle incoming photos from customer
if (!empty($message['photo']) && $chat_id) {
    $tg_page_id = ($dept === 'order') ? 'order_telegram' : 'regular_telegram';
    $session_id = inbox_create_session($db, $prefx, 'telegram', $chat_id, $sender_name, $tg_page_id, $dept);
    $session    = inbox_get_session($db, $prefx, 'telegram', $chat_id, $tg_page_id);

    // Telegram sends multiple sizes — use the largest one
    $photo_sizes = $message['photo'];
    $best        = end($photo_sizes);
    $file_id     = $best['file_id'] ?? '';

    if ($file_id) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/crm/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        // Get file path from Telegram
        $file_info_raw = file_get_contents("https://api.telegram.org/bot{$bot_token}/getFile?file_id=" . urlencode($file_id));
        $file_info     = json_decode($file_info_raw, true);
        $file_path_tg  = $file_info['result']['file_path'] ?? '';

        if ($file_path_tg) {
            $ext   = strtolower(pathinfo($file_path_tg, PATHINFO_EXTENSION)) ?: 'jpg';
            $fname = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest  = $upload_dir . $fname;
            $img_data = file_get_contents("https://api.telegram.org/file/bot{$bot_token}/{$file_path_tg}");
            if ($img_data !== false) {
                file_put_contents($dest, $img_data);
                $photo_url = '/uploads/crm/' . $fname;
                $body = '[photo:' . $photo_url . ']' . ($text !== '' ? "\n" . $text : '');
                inbox_save_message($db, $prefx, $session_id, 'telegram', 'in',
                    $chat_id, $sender_name, $body,
                    $session->lead_id ?? null, false, null, '', $mid);
                if ($session && !$session->ai_active && $session->lead_id) {
                    $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")
                       ->execute([':id' => $session->lead_id]);
                }
            }
        }
    }
    http_response_code(200);
    echo 'ok';
    exit;
}

if (!$chat_id || !$text) {
    http_response_code(200);
    echo 'ok';
    exit;
}

// Get or create session
$tg_page_id = ($dept === 'order') ? 'order_telegram' : 'regular_telegram';
$session_id = inbox_create_session($db, $prefx, 'telegram', $chat_id, $sender_name, $tg_page_id, $dept);

// Handle /start [param] — save car context and let AI greet with it
if (strpos(trim($text), '/start') === 0) {
    $start_param = trim(substr(trim($text), 6));
    if ($start_param && $session_id) {
        $car_context = str_replace('_', ' ', $start_param);
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET car_context=:ctx WHERE id=:id")
           ->execute([':ctx' => $car_context, ':id' => $session_id]);
        $session = inbox_get_session($db, $prefx, 'telegram', $chat_id, $tg_page_id);
        $text = 'Bună ziua! Sunt interesat de: ' . $car_context;
    } else {
        $welcome = ($dept === 'order')
            ? "Bună ziua! Sunt asistentul virtual Sauto — Auto la Comandă 🚗\nCu ce vă pot ajuta?"
            : "Bună ziua! Sunt asistentul virtual Sauto — Stocul Nostru 🚗\nCu ce vă pot ajuta?";
        inbox_send_tg_message($chat_id, $welcome, $bot_token);
        http_response_code(200);
        echo 'ok';
        exit;
    }
}
$session    = inbox_get_session($db, $prefx, 'telegram', $chat_id, $tg_page_id);

// Save incoming message
inbox_save_message($db, $prefx, $session_id, 'telegram', 'in',
    $chat_id, $sender_name, $text,
    $session->lead_id ?? null, false, null, '', $mid);

// If AI is off → manager handles it, skip AI reply
if ($session && !$session->ai_active) {
    if ($session->lead_id) {
        $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")
           ->execute([':id' => $session->lead_id]);
    }
    http_response_code(200);
    echo 'ok';
    exit;
}

// AI pre-qualification
$history     = inbox_get_history($db, $prefx, $session_id, 50);
$car_context = $session->car_context ?? '';
$ai          = inbox_ai_reply($db, $prefx, 'telegram', $history, $text, $car_context);

if ($ai['reply']) {
    $tg_log = __DIR__ . '/logs/tg_webhook.log';
    file_put_contents($tg_log, date('Y-m-d H:i:s') . ' SEND chat_id=' . $chat_id . ' token_len=' . strlen($bot_token) . ' token_prefix=' . substr($bot_token, 0, 10) . ' dept=' . $dept . ' reply=' . substr($ai['reply'], 0, 100) . PHP_EOL, FILE_APPEND | LOCK_EX);
    $tg_sent = inbox_send_tg_message($chat_id, $ai['reply'], $bot_token);
    file_put_contents($tg_log, date('Y-m-d H:i:s') . ' SEND_RESULT=' . ($tg_sent ? 'OK' : 'FAIL') . PHP_EOL, FILE_APPEND | LOCK_EX);
    inbox_save_message($db, $prefx, $session_id, 'telegram', 'out',
        'ai', 'AI Sauto', $ai['reply'],
        $session->lead_id ?? null, true);
}

$pg_id = $session->page_id ?? '';
if ($ai['trigger'] && $ai['phone']) {
    inbox_create_lead($db, $prefx, $session_id, $ai['phone'], 'telegram', $dept, null, null, $pg_id);
}

http_response_code(200);
echo 'ok';
$db = null;
exit;
