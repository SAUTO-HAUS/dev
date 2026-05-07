<?php
/**
 * Facebook Messenger + Instagram Webhook
 * URL: https://www.sauto.md/fb_webhook.php
 */

// ── Webhook verification (GET) — handled BEFORE any includes ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $raw_qs = $_SERVER['QUERY_STRING'] ?? '';
    $qs = [];
    parse_str(str_replace(
        ['hub.mode', 'hub.verify_token', 'hub.challenge'],
        ['hub_mode',  'hub_verify_token',  'hub_challenge'],
        $raw_qs
    ), $qs);

    $mode           = $qs['hub_mode']         ?? '';
    $token_received = $qs['hub_verify_token'] ?? '';
    $challenge      = $qs['hub_challenge']    ?? '';
    $verify_token   = 'sauto123';

    file_put_contents(__DIR__ . '/logs/fb_verify.log',
        date('Y-m-d H:i:s') . "\n" .
        "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\n" .
        "QUERY_STRING: $raw_qs\n" .
        "mode: $mode | token: $token_received | challenge: $challenge\n" .
        "match: " . ($token_received === $verify_token ? 'YES' : 'NO') . "\n\n",
        FILE_APPEND | LOCK_EX);

    header('Content-Type: text/plain');
    if ($mode === 'subscribe' && $token_received === $verify_token) {
        http_response_code(200);
        echo $challenge;
    } elseif ($mode === 'subscribe') {
        http_response_code(403);
        echo 'Forbidden';
    } else {
        http_response_code(200);
        echo 'OK';
    }
    exit;
}

// ── POST — load dependencies ──────────────────────────────────────────────────
try {
    define('_DOIT', 1);
    require_once __DIR__ . '/environment.php';
    require_once __DIR__ . '/content/default/defines.php';
    require_once __DIR__ . '/content/default/functions.php';
    require_once __DIR__ . '/content/default/config.php';
    require_once __DIR__ . '/content/default/dbi.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_core.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_inbox_core.php';
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/logs/fb_post_error.log',
        date('Y-m-d H:i:s') . ' INIT ERROR: ' . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

// ── Webhook events (POST) ────────────────────────────────────────────────────
$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

// Log for debugging
$log = __DIR__ . '/logs/fb_webhook.log';
if (is_writable(dirname($log))) {
    file_put_contents($log, date('Y-m-d H:i:s') . ' ' . substr($raw, 0, 1000) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

if (!is_array($payload) || empty($payload['entry'])) {
    file_put_contents(__DIR__ . '/logs/fb_post_error.log',
        date('Y-m-d H:i:s') . ' EMPTY PAYLOAD: ' . $raw . "\n", FILE_APPEND);
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

// Get FB settings from gh3sp_settings
$fb_settings = inbox_get_fb_settings($db, $prefx);

foreach ($payload['entry'] as $entry) {
    $page_id = $entry['id'] ?? '';

    // Determine channel: instagram or facebook based on object type
    $object_type = $payload['object'] ?? '';
    $channel = ($object_type === 'instagram') ? 'instagram' : 'facebook';

    // Find token and IG account ID
    $page_token  = '';
    $ig_acct_id  = '';

    if ($channel === 'instagram') {
        // For Instagram: entry.id is the Facebook Page ID that owns the IG account.
        // The settings store instagram_page_id_N (IG account ID) and instagram_token_N.
        // We also need the FB Page token in case we need it, but the IG token is the one
        // that has instagram_manage_messages scope.

        // First: try to match by FB page_id → find associated instagram_token_N
        foreach ($fb_settings as $key => $val) {
            if (strpos($key, 'facebook_page_id') !== false && $val === $page_id) {
                $suffix     = preg_replace('/^.*facebook_page_id/', '', $key); // e.g. '_1' or ''
                $ig_key     = 'instagram_page_id' . $suffix;
                $ig_tok_key = 'instagram_token' . $suffix;
                if (!empty($fb_settings[$ig_key]) && !empty($fb_settings[$ig_tok_key])) {
                    $ig_acct_id = $fb_settings[$ig_key];
                    $page_token = $fb_settings[$ig_tok_key];
                    break;
                }
            }
        }
        // Fallback: use first instagram_token_N available
        if (!$page_token) {
            foreach ($fb_settings as $key => $val) {
                if (preg_match('/^instagram_page_id/', $key) && $val) {
                    $suffix     = preg_replace('/^instagram_page_id/', '', $key);
                    $ig_tok_key = 'instagram_token' . $suffix;
                    if (!empty($fb_settings[$ig_tok_key])) {
                        $ig_acct_id = $val;
                        $page_token = $fb_settings[$ig_tok_key];
                        break;
                    }
                }
            }
        }
        file_put_contents(__DIR__ . '/logs/fb_post_error.log',
            date('Y-m-d H:i:s') . " IG_TOKEN_LOOKUP fb_page=$page_id ig_acct=$ig_acct_id token=" . substr($page_token,0,30) . "\n", FILE_APPEND);
    } else {
        // Facebook: match by facebook_page_id
        foreach ($fb_settings as $key => $val) {
            if (strpos($key, 'facebook_page_id') !== false && $val === $page_id) {
                $loc_key    = str_replace('_page_id', '_token', $key);
                $page_token = $fb_settings[$loc_key] ?? '';
                break;
            }
        }
        if (!$page_token) {
            foreach ($fb_settings as $key => $val) {
                if (strpos($key, 'facebook_token') !== false && $val) { $page_token = $val; break; }
            }
        }
    }

    $messaging_key = isset($entry['messaging']) ? 'messaging' : 'changes';

    $items = $entry[$messaging_key] ?? [];

    file_put_contents(__DIR__ . '/logs/fb_post_error.log',
        date('Y-m-d H:i:s') . " channel=$channel messaging_key=$messaging_key items_count=" . count($items) . " ig_acct=$ig_acct_id page=$page_id token_len=" . strlen($page_token) . "\n", FILE_APPEND);

    foreach ($items as $event) {
        if ($channel === 'instagram') {
            // Instagram can deliver via 'messaging' (direct) or 'changes' (wrapped in value)
            // Handle both formats
            if (isset($event['field']) && isset($event['value'])) {
                // 'changes' format: { field: "messages", value: { sender, recipient, message, ... } }
                $ev = $event['value'];
            } else {
                // 'messaging' format: direct event object
                $ev = $event;
            }

            $sender_id = $ev['sender']['id'] ?? '';
            $recipient = $ev['recipient']['id'] ?? '';
            $message   = $ev['message'] ?? null;

            file_put_contents(__DIR__ . '/logs/fb_post_error.log',
                date('Y-m-d H:i:s') . " ig_event sender=$sender_id recipient=$recipient has_msg=" . ($message ? 'yes' : 'no') . " is_echo=" . (isset($message['is_echo']) ? 'yes' : 'no') . "\n", FILE_APPEND);

            if (!$sender_id || !$message || isset($message['is_echo'])) continue;
            // Skip messages sent by the bot itself (ig_acct_id or fb page_id)
            if ($sender_id === $ig_acct_id || $sender_id === $page_id) continue;
            $text        = $message['text'] ?? '';
            $mid         = $message['mid'] ?? '';
            $attachments = $message['attachments'] ?? [];

            // Handle image attachments from customer
            if (!$text && !empty($attachments)) {
                $ig_sender_name = '';
                if ($page_token && $sender_id) {
                    $ig_name_resp = @file_get_contents(
                        "https://graph.instagram.com/{$sender_id}?fields=name,username&access_token=" . urlencode($page_token)
                    );
                    if ($ig_name_resp) {
                        $ig_name_data   = json_decode($ig_name_resp, true);
                        $ig_sender_name = $ig_name_data['name'] ?? $ig_name_data['username'] ?? '';
                    }
                }
                $session_id = inbox_create_session($db, $prefx, 'instagram', $sender_id, $ig_sender_name, $ig_acct_id ?: $page_id);
                $session    = inbox_get_session($db, $prefx, 'instagram', $sender_id);
                foreach ($attachments as $att) {
                    $att_type = $att['type'] ?? '';
                    $img_url  = $att['payload']['url'] ?? '';
                    if (!in_array($att_type, ['image','video']) || !$img_url) continue;
                    inbox_save_message($db, $prefx, $session_id, 'instagram', 'in',
                        $sender_id, $ig_sender_name, '[photo:' . $img_url . ']',
                        $session->lead_id ?? null, false, null, '', $mid);
                }
                if ($session && !$session->ai_active && $session->lead_id) {
                    $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")
                       ->execute([':id' => $session->lead_id]);
                }
                continue;
            }

            if (!$text) {
                file_put_contents(__DIR__ . '/logs/fb_post_error.log',
                    date('Y-m-d H:i:s') . " ig_event SKIP no text (attachments/stickers not supported)\n", FILE_APPEND);
                continue;
            }
            // Fetch Instagram sender name via Graph API
            $ig_sender_name = '';
            if ($page_token && $sender_id) {
                $ig_name_resp = @file_get_contents(
                    "https://graph.instagram.com/{$sender_id}?fields=name,username&access_token=" . urlencode($page_token)
                );
                if ($ig_name_resp) {
                    $ig_name_data   = json_decode($ig_name_resp, true);
                    $ig_sender_name = $ig_name_data['name'] ?? $ig_name_data['username'] ?? '';
                }
                file_put_contents(__DIR__ . '/logs/fb_post_error.log',
                    date('Y-m-d H:i:s') . " ig_name_lookup sender=$sender_id name=$ig_sender_name\n", FILE_APPEND);
            }
            file_put_contents(__DIR__ . '/logs/fb_post_error.log',
                date('Y-m-d H:i:s') . " ig_handle sender=$sender_id text=" . substr($text,0,100) . " ig_acct=" . ($ig_acct_id ?: $page_id) . "\n", FILE_APPEND);
            // Pass ig_acct_id as page_id so the send function uses the correct IG endpoint
            handle_inbox_message($db, $prefx, 'instagram', $sender_id, $ig_sender_name, $text, $mid, $ig_acct_id ?: $page_id, $page_token);
            continue;
        }

        $sender_id   = $event['sender']['id'] ?? '';
        $recipient   = $event['recipient']['id'] ?? '';
        $message     = $event['message'] ?? null;

        if (!$sender_id || !$message || isset($message['is_echo'])) continue;

        $text        = $message['text'] ?? '';
        $mid         = $message['mid'] ?? '';
        $attachments = $message['attachments'] ?? [];
        $sender_name = '';

        if ($page_token && $sender_id) {
            $name_resp = @file_get_contents("https://graph.facebook.com/{$sender_id}?fields=name&access_token={$page_token}");
            if ($name_resp) {
                $name_data   = json_decode($name_resp, true);
                $sender_name = $name_data['name'] ?? '';
            }
        }

        // Handle image attachments from customer
        if (!$text && !empty($attachments)) {
            $session_id = inbox_create_session($db, $prefx, 'facebook', $sender_id, $sender_name, $page_id);
            $session    = inbox_get_session($db, $prefx, 'facebook', $sender_id);
            foreach ($attachments as $att) {
                $att_type = $att['type'] ?? '';
                $img_url  = $att['payload']['url'] ?? '';
                if (!in_array($att_type, ['image','video']) || !$img_url) continue;
                inbox_save_message($db, $prefx, $session_id, 'facebook', 'in',
                    $sender_id, $sender_name, '[photo:' . $img_url . ']',
                    $session->lead_id ?? null, false, null, '', $mid);
            }
            if ($session && !$session->ai_active && $session->lead_id) {
                $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")
                   ->execute([':id' => $session->lead_id]);
            }
            continue;
        }

        if (!$text) continue;
        file_put_contents(__DIR__ . '/logs/fb_post_error.log',
            date('Y-m-d H:i:s') . " handle: channel=facebook sender=$sender_id text=$text page=$page_id token=" . substr($page_token,0,20) . "\n", FILE_APPEND);
        try {
            handle_inbox_message($db, $prefx, 'facebook', $sender_id, $sender_name, $text, $mid, $page_id, $page_token);
        } catch (Throwable $e) {
            file_put_contents(__DIR__ . '/logs/fb_post_error.log',
                date('Y-m-d H:i:s') . ' HANDLE ERROR: ' . $e->getMessage() . "\n" .
                $e->getTraceAsString() . "\n\n", FILE_APPEND);
        }
    }
}

http_response_code(200);
echo json_encode(['ok' => true]);
$db = null;
exit;

// ── Core handler ─────────────────────────────────────────────────────────────
function handle_inbox_message(PDO $db, string $prefx, string $channel, string $sender_id,
                               string $sender_name, string $text, string $mid,
                               string $page_id, string $page_token): void {
    try {
        $session_id = inbox_create_session($db, $prefx, $channel, $sender_id, $sender_name, $page_id);
    } catch (Throwable $e) {
        file_put_contents(__DIR__ . '/logs/fb_post_error.log',
            date('Y-m-d H:i:s') . ' CREATE_SESSION ERROR: ' . $e->getMessage() . "\n", FILE_APPEND);
        return;
    }
    file_put_contents(__DIR__ . '/logs/fb_post_error.log',
        date('Y-m-d H:i:s') . " session_id=$session_id channel=$channel sender=$sender_id page=$page_id\n", FILE_APPEND);

    $session = inbox_get_session($db, $prefx, $channel, $sender_id);

    try {
        $msg_id = inbox_save_message($db, $prefx, $session_id, $channel, 'in',
            $sender_id, $sender_name, $text,
            $session->lead_id ?? null, false, null, '', $mid);
        file_put_contents(__DIR__ . '/logs/fb_post_error.log',
            date('Y-m-d H:i:s') . " save_message OK msg_id=$msg_id session_id=$session_id\n", FILE_APPEND);
    } catch (Throwable $e) {
        file_put_contents(__DIR__ . '/logs/fb_post_error.log',
            date('Y-m-d H:i:s') . ' SAVE_MESSAGE ERROR: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
        return;
    }

    if ($session && !$session->ai_active) {
        if ($session->lead_id) {
            $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")
               ->execute([':id' => $session->lead_id]);
        }
        return;
    }

    $history = inbox_get_history($db, $prefx, $session_id, 50);
    $ai      = inbox_ai_reply($db, $prefx, $channel, $history, $text);

    file_put_contents(__DIR__ . '/logs/fb_ai.log',
        date('Y-m-d H:i:s') . " ai_reply=" . json_encode($ai) . "\n", FILE_APPEND | LOCK_EX);

    if ($ai['reply']) {
        $sent = false;
        if ($channel === 'instagram') {
            $sent = inbox_send_instagram_message($page_id, $sender_id, $ai['reply'], $page_token);
            file_put_contents(__DIR__ . '/logs/fb_ai.log',
                date('Y-m-d H:i:s') . " ig_send_result=" . ($sent ? 'OK' : 'FAIL') . " ig_account=$page_id sender=$sender_id token=" . substr($page_token,0,20) . "\n", FILE_APPEND | LOCK_EX);
        } elseif ($channel === 'facebook') {
            $sent = inbox_send_fb_message($sender_id, $ai['reply'], $page_token);
            file_put_contents(__DIR__ . '/logs/fb_ai.log',
                date('Y-m-d H:i:s') . " fb_send_result=" . ($sent ? 'OK' : 'FAIL') . "\n", FILE_APPEND | LOCK_EX);
        }
        inbox_save_message($db, $prefx, $session_id, $channel, 'out',
            'ai', 'AI Sauto', $ai['reply'],
            $session->lead_id ?? null, true);
    }

    $pg_id = $session->page_id ?? '';
    if ($ai['trigger'] && $ai['phone']) {
        $dept = $session->department ?? 'stock';
        inbox_create_lead($db, $prefx, $session_id, $ai['phone'], $channel, $dept, null, null, $pg_id);
    }
}
