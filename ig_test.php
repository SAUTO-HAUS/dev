<?php
/**
 * Instagram Send Test — diagnostic only, delete after use
 * Usage: https://www.sauto.md/ig_test.php?secret=sauto2024&recipient=IGSID&text=test
 */
if (($_GET['secret'] ?? '') !== 'sauto2024') { http_response_code(403); exit('Forbidden'); }

define('_DOIT', 1);
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/content/default/defines.php';
require_once __DIR__ . '/content/default/functions.php';
require_once __DIR__ . '/content/default/config.php';
require_once __DIR__ . '/content/default/dbi.php';

header('Content-Type: text/plain');

// ── RUN 999 CHAT POLLER ──────────────────────────────────────────────────────
if (isset($_GET['run999'])) {
    ob_implicit_flush(true);
    require_once __DIR__ . '/content/admin/include/crm/crm_core.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_inbox_core.php';
    require_once __DIR__ . '/App/Services/Chat999Service.php';

    $account_key = $_GET['acc'] ?? 'order_999md';
    $dept        = (strpos($account_key, 'order') !== false) ? 'order' : 'stock';
    echo "=== Run 999 poller: $account_key ===\n\n";

    $svc      = new \App\Services\Chat999Service($db, $prefx, $account_key);
    $messages = $svc->pollUnread();
    echo "Found: " . count($messages) . " messages to process\n\n";

    foreach ($messages as $msg) {
        $sender_id   = $msg['contact_user_id'];
        $sender_name = $msg['contact_login'] ?: $sender_id;
        $text        = $msg['text'];
        $mid         = $msg['msg_id'];
        $all_mids    = $msg['all_mids']  ?? [$mid];
        $all_texts   = $msg['all_texts'] ?? [$text];

        if (!$text || !$sender_id) continue;

        echo "Processing: [{$sender_name}] " . implode(' / ', $all_texts) . "\n";

        $session_id = inbox_create_session($db, $prefx, '999md', $sender_id, $sender_name, $account_key, $dept);
        $session    = inbox_get_session($db, $prefx, '999md', $sender_id);

        // Save each message individually
        foreach ($all_mids as $i => $m_id) {
            $m_text = $all_texts[$i] ?? '';
            inbox_save_message($db, $prefx, $session_id, '999md', 'in',
                $sender_id, $sender_name, $m_text,
                $session->lead_id ?? null, false, null, '', $m_id);
        }

        if ($session && $session->lead_id && !$session->ai_active) {
            echo "  → Manager mode, skip AI\n";
            continue;
        }

        $history = inbox_get_history($db, $prefx, $session_id, 50);
        $ai      = inbox_ai_reply($db, $prefx, '999md', $history, $text);

        if ($ai['reply']) {
            $sent = $svc->sendMessage($sender_id, $ai['reply']);
            echo "  → AI reply: " . substr($ai['reply'], 0, 60) . "... [" . ($sent ? 'SENT' : 'FAIL') . "]\n";
            inbox_save_message($db, $prefx, $session_id, '999md', 'out',
                'ai', 'AI Sauto', $ai['reply'],
                $session->lead_id ?? null, true);
        }

        if ($ai['trigger'] && $ai['phone']) {
            inbox_create_lead($db, $prefx, $session_id, $ai['phone'], '999md', $dept, $sender_name ?: null);
            echo "  → Lead created: {$ai['phone']}\n";
        }

        // Mark as read on 999.md - test different mutations
        echo "  → Testing markAsRead...\n";
        $read_token = '';
        $stmt_tok = $db->prepare("SELECT value FROM {$prefx}_settings WHERE name=:k LIMIT 1");
        $stmt_tok->execute([':k' => $account_key . '_access_token']);
        $read_token = $stmt_tok->fetchColumn() ?: '';

        foreach ([
            'mutation { markMessageAsRead(input: { contactUserId: "' . $sender_id . '" }) { success } }' => 'markMessageAsRead_contactUserId',
            'mutation { markMessageAsRead(input: { msgId: "' . $sender_id . '" }) { success } }' => 'markMessageAsRead_msgId',
            'mutation { markMessageAsRead(contactUserId: "' . $sender_id . '") { success } }' => 'markMessageAsRead_direct',
        ] as $q => $op) {
            $ch_r = curl_init('https://v2.simpalsid.com/graphql');
            curl_setopt_array($ch_r, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['operationName' => $op, 'query' => $q, 'variables' => new stdClass()]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Origin: https://999.md', 'accesstoken: ' . $read_token],
            ]);
            $r = curl_exec($ch_r); $c = curl_getinfo($ch_r, CURLINFO_HTTP_CODE); curl_close($ch_r);
            echo "  → [$op] HTTP $c: " . substr($r, 0, 150) . "\n";
        }
        $svc->markAsRead($sender_id);
    }

    echo "\nDone.\n";
    exit;
}

// ── TEST 999 CHAT POLLER ─────────────────────────────────────────────────────
if (isset($_GET['test999'])) {
    require_once __DIR__ . '/content/admin/include/crm/crm_core.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_inbox_core.php';
    require_once __DIR__ . '/App/Services/Chat999Service.php';

    $account_key = $_GET['acc'] ?? 'order_999md';
    echo "=== Test 999.md Chat: $account_key ===\n\n";

    // Show token from DB
    $stmt = $db->prepare("SELECT value FROM {$prefx}_settings WHERE name=:k LIMIT 1");
    $stmt->execute([':k' => $account_key . '_access_token']);
    $tok = $stmt->fetchColumn() ?: '';
    echo "Access token in DB: " . ($tok ? substr($tok,0,30).'... (len='.strlen($tok).')' : 'MISSING') . "\n\n";

    if (!$tok) {
        echo "ERROR: No access token. Run SQL first.\n";
        exit;
    }

    $svc = new \App\Services\Chat999Service($db, $prefx, $account_key);

    // Test refresh token
    echo "=== Test RefreshAccessToken ===\n";
    $session_key = '';
    $stmt2 = $db->prepare("SELECT value FROM {$prefx}_settings WHERE name=:k LIMIT 1");
    $stmt2->execute([':k' => $account_key . '_session_key']);
    $session_key = $stmt2->fetchColumn() ?: '';
    echo "Session key in DB: " . ($session_key ? substr($session_key,0,30).'...(len='.strlen($session_key).')' : 'MISSING') . "\n";

    if ($session_key) {
        $ref_ch = curl_init('https://v2.simpalsid.com/graphql');
        curl_setopt_array($ref_ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'operationName' => 'RefreshAccessToken',
                'query'         => 'mutation RefreshAccessToken { refreshAccessToken(input: { refreshToken: "' . addslashes($session_key) . '" }) { accessToken } }',
                'variables'     => (object)[],
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Origin: https://999.md'],
        ]);
        $ref_resp = curl_exec($ref_ch);
        $ref_code = curl_getinfo($ref_ch, CURLINFO_HTTP_CODE);
        curl_close($ref_ch);
        echo "HTTP $ref_code: $ref_resp\n\n";

        $ref_data = json_decode($ref_resp, true);
        $new_token = $ref_data['data']['refreshAccessToken']['accessToken'] ?? '';
        if ($new_token) {
            echo "NEW ACCESS TOKEN: " . substr($new_token,0,50) . "...\n";
            // Save to DB
            $db->prepare("UPDATE {$prefx}_settings SET value=:v WHERE name=:k")->execute([':v'=>$new_token, ':k'=>$account_key.'_access_token']);
            echo "Saved to DB!\n";
            $tok = $new_token;
        }
    }
    echo "\n";

    // Raw GraphQL test
    echo "=== Raw ChatContacts API call ===\n";
    $raw_ch = curl_init('https://v2.simpalsid.com/graphql');
    curl_setopt_array($raw_ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'operationName' => 'ChatContacts',
            'query'         => 'query ChatContacts($input: Chat_ContactListRequestInput!) { listContacts(input: $input) { contacts { id unreadCounter contact { login userId __typename } lastMessage { text direction isReadByMe __typename } __typename } __typename } }',
            'variables'     => ['input' => ['limit' => 20, 'skip' => 0]],
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Origin: https://999.md',
            'accesstoken: ' . $tok,
        ],
    ]);
    $raw_resp = curl_exec($raw_ch);
    $raw_code = curl_getinfo($raw_ch, CURLINFO_HTTP_CODE);
    curl_close($raw_ch);
    echo "HTTP $raw_code: " . substr($raw_resp, 0, 1000) . "\n\n";

    echo "=== Polling unread messages ===\n";
    $messages = $svc->pollUnread();
    echo "Found: " . count($messages) . " unread messages\n\n";

    foreach ($messages as $m) {
        echo "From: {$m['contact_login']} ({$m['contact_user_id']})\n";
        echo "Text: {$m['text']}\n";
        echo "Ad: {$m['advert_title']}\n";
        echo "---\n";
    }

    // Test send
    if (isset($_GET['send_to']) && isset($_GET['send_text'])) {
        echo "\n=== Test Send ===\n";
        $result = $svc->sendMessage($_GET['send_to'], $_GET['send_text']);
        echo "Result: " . ($result ? 'OK' : 'FAIL') . "\n";
    }
    exit;
}

// ── SAVE CORRECT TG TOKENS ───────────────────────────────────────────────────
if (isset($_GET['save_tg'])) {
    $regular_new = '8704209713:AAGsLqt8T_Ki6NiwIW6CceC5cQhnOVA2qW0';
    $order_new   = '8598347907:AAErCPWZkDW92w32nV3jtjciNuqOWdlqczo';

    foreach ([
        'regular_telegram_bot_token' => $regular_new,
        'order_telegram_bot_token'   => $order_new,
    ] as $name => $val) {
        $check = $db->prepare("SELECT COUNT(*) FROM {$prefx}_settings WHERE name=:n");
        $check->execute([':n' => $name]);
        if ($check->fetchColumn() > 0) {
            $db->prepare("UPDATE {$prefx}_settings SET value=:v WHERE name=:n")->execute([':v'=>$val,':n'=>$name]);
            echo "UPDATED $name\n";
        } else {
            $db->prepare("INSERT INTO {$prefx}_settings (name,value) VALUES (:n,:v)")->execute([':n'=>$name,':v'=>$val]);
            echo "INSERTED $name\n";
        }
    }

    // Verify
    $stmt = $db->query("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%telegram_bot_token%'");
    echo "\nCurrent DB values:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $r) {
        echo "  {$r->name} = " . substr($r->value,0,20) . "...(len=".strlen($r->value).")\n";
    }
    exit;
}

// ── TELEGRAM DIAGNOSTICS ─────────────────────────────────────────────────────
if (isset($_GET['tg'])) {
    require_once __DIR__ . '/content/admin/include/crm/crm_core.php';
    require_once __DIR__ . '/content/admin/include/crm/crm_inbox_core.php';

    $tg_settings = inbox_get_tg_settings($db, $prefx);
    echo "=== TG Settings in DB ===\n";
    foreach ($tg_settings as $k => $v) {
        echo "  $k = " . (strpos($k,'token')!==false ? substr($v,0,20).'...(len='.strlen($v).')' : $v) . "\n";
    }

    $regular_token = $tg_settings['regular_telegram_bot_token'] ?? '';
    $order_token   = $tg_settings['order_telegram_bot_token']   ?? '';
    $tg_chat_id    = $_GET['chat_id'] ?? '';
    $tg_msg        = $_GET['msg']     ?? 'Test Sauto CRM bot';

    // Check bot info
    foreach (['regular' => $regular_token, 'order' => $order_token] as $type => $tok) {
        if (!$tok) { echo "\n[$type] token empty — skip\n"; continue; }
        echo "\n=== Bot $type getMe ===\n";
        $ch = curl_init("https://api.telegram.org/bot{$tok}/getMe");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>8]);
        $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        echo "HTTP $c: $r\n";

        if ($tg_chat_id) {
            echo "\n=== Bot $type sendMessage to $tg_chat_id ===\n";
            $ch = curl_init("https://api.telegram.org/bot{$tok}/sendMessage");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode(['chat_id'=>$tg_chat_id,'text'=>$tg_msg]),
            ]);
            $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            echo "HTTP $c: $r\n";
        }
    }

    // Show last tg_webhook.log
    $tg_log = __DIR__ . '/content/admin/include/crm/tg_webhook.log';
    echo "\n=== Last tg_webhook.log ===\n";
    if (file_exists($tg_log) && filesize($tg_log) > 0) {
        $lines = file($tg_log);
        echo implode('', array_slice($lines, -40));
    } else {
        echo "(empty or not found)\n";
    }
    exit;
}

// Load FB/IG settings
$stmt = $db->query("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%facebook%' OR name LIKE '%instagram%'");
$fb = [];
foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $r) $fb[$r->name] = $r->value;

echo "=== FB/IG Settings ===\n";
foreach ($fb as $k => $v) {
    echo "  $k = " . (strpos($k,'token')!==false ? substr($v,0,30).'...' : $v) . "\n";
}

$ig_acct_id = $fb['instagram_page_id_1'] ?? '';
$ig_token   = $fb['instagram_token_1']   ?? '';
$recipient  = $_GET['recipient'] ?? '';
$text       = $_GET['text'] ?? 'Test reply from Sauto CRM';

echo "\n=== Instagram Account ID: $ig_acct_id ===\n";
echo "IG Token: " . substr($ig_token,0,30) . "...\n";
echo "Recipient: $recipient\n\n";

// Test: Check IG token debug info
echo "=== IG Token Debug ===\n";
$fb_app_token = ($fb['location_1_facebook_token'] ?? $fb['location_2_facebook_token'] ?? '');
if ($fb_app_token && $ig_token) {
    $ch = curl_init('https://graph.facebook.com/debug_token?input_token=' . urlencode($ig_token) . '&access_token=' . urlencode($fb_app_token));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
    $r = curl_exec($ch); curl_close($ch);
    $d = json_decode($r, true);
    echo "Valid: " . ($d['data']['is_valid'] ? 'YES' : 'NO') . "\n";
    echo "Scopes: " . implode(', ', $d['data']['scopes'] ?? []) . "\n";
    echo "Expires: " . ($d['data']['expires_at'] ?? 'never') . "\n\n";
}

// Test: New IG token
echo "=== Test New IG Token ===\n";
$ig_token_new = 'IGAAVNp1kJbzJBZAGFwNjBSenBhcHlxSVhUMm9WUnJzWGc5QTFRRmpFamgtdnlobGRjZAUZAQcHdLX2xIak5OZAHBhc3BNZAzZA5YUhuZAEVLY2x4QlBMbkRfUjJoZAmZA6UTFkUFNBNXhrSHhuaThPQ0YtME5IVW5SQUdlNVhXWWFXbm14QQZDZD';
$ig_acct_id = '17841403194594979';

// Check token
$ch = curl_init('https://graph.facebook.com/debug_token?input_token=' . urlencode($ig_token_new) . '&access_token=' . urlencode($ig_token_new));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
$r = curl_exec($ch); curl_close($ch);
$d = json_decode($r, true);
echo "Valid: " . (($d['data']['is_valid'] ?? false) ? 'YES' : 'NO') . "\n";
echo "Type: " . ($d['data']['type'] ?? 'unknown') . "\n";
echo "Scopes: " . implode(', ', $d['data']['scopes'] ?? []) . "\n";
echo "Expires: " . ($d['data']['expires_at'] ?? 'never') . "\n\n";

// Test IG account info
echo "=== IG Account Info ===\n";
$ch = curl_init("https://graph.facebook.com/v21.0/{$ig_acct_id}?fields=id,name,username&access_token=" . urlencode($ig_token_new));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
$r = curl_exec($ch); curl_close($ch);
echo $r . "\n\n";

// Test send capability
echo "=== Test IG Messages Endpoint ===\n";
$ch = curl_init("https://graph.facebook.com/v21.0/{$ig_acct_id}/messages");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'recipient' => ['id' => 'TEST_INVALID'],
        'message'   => ['text' => 'test'],
        'messaging_type' => 'RESPONSE',
        'access_token' => $ig_token_new,
    ]),
]);
$r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
echo "HTTP $c: $r\n\n";

// Set Viber webhook
if (isset($_GET['set_viber'])) {
    $viber_token = '5088bca0c4a7e402-4504f41b8fb1fb39-1889bca258a94d7d';
    $ch = curl_init('https://chatapi.viber.com/pa/set_webhook');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Viber-Auth-Token: ' . $viber_token],
        CURLOPT_POSTFIELDS => json_encode([
            'url'       => 'https://www.sauto.md/viber_webhook.php',
            'event_types' => ['message', 'conversation_started'],
            'send_name' => true,
        ]),
    ]);
    $r = curl_exec($ch); curl_close($ch);
    echo "=== Viber Webhook Set ===\n$r\n\n";

    // Save token to DB
    $check = $db->prepare("SELECT COUNT(*) FROM {$prefx}_settings WHERE name='viber_bot_token'");
    $check->execute();
    if ($check->fetchColumn() > 0) {
        $db->prepare("UPDATE {$prefx}_settings SET value=:v WHERE name='viber_bot_token'")->execute([':v'=>$viber_token]);
    } else {
        $db->prepare("INSERT INTO {$prefx}_settings (name,value) VALUES ('viber_bot_token',:v)")->execute([':v'=>$viber_token]);
    }
    echo "Token saved to DB.\n\n";
    exit;
}

// Test Instagram API with IGAAV token via graph.instagram.com
echo "=== Test graph.instagram.com endpoint ===\n";
$igaav_token = 'IGAAVNp1kJbzJBZAFpia1E5SVNWVmlER2J2elZArWWZAPQWVHZA19fM3BDbUpDaE5sbTVNNXlDUng5eURsNlBZANEtEbm9KblB1ZAHVxV2FfWHZAvR1dtaThVNEhwOW5yVGJyQkU5RU5YT0FENTVfM25LX2VwTWg1WTdPeWhYZAW5LUXAwbwZDZD';
$ch = curl_init('https://graph.instagram.com/v25.0/me/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $igaav_token,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'recipient' => ['id' => '2141840089926427'],
        'message'   => ['text' => 'Test Sauto CRM - buna ziua!'],
    ]),
]);
$r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
echo "HTTP $c: $r\n\n";

// Save IGAAV token to DB
echo "=== Save IGAAV token to DB ===\n";
$igaav_new = 'IGAAVNp1kJbzJBZAFpia1E5SVNWVmlER2J2elZArWWZAPQWVHZA19fM3BDbUpDaE5sbTVNNXlDUng5eURsNlBZANEtEbm9KblB1ZAHVxV2FfWHZAvR1dtaThVNEhwOW5yVGJyQkU5RU5YT0FENTVfM25LX2VwTWg1WTdPeWhYZAW5LUXAwbwZDZD';
$upd = $db->prepare("UPDATE {$prefx}_settings SET value=:v WHERE name='instagram_token_1'");
$upd->execute([':v' => $igaav_new]);
echo "Rows updated: " . $upd->rowCount() . "\n\n";

// Generate Long-Lived Page Token using App Secret
echo "=== Generate Long-Lived Token ===\n";
$app_id     = '1137294825141008';
$app_secret = '7648486532d781f4e034f6392c7d9d11';
$short_tok  = $fb['location_1_facebook_token'] ?? '';

// Exchange short-lived for long-lived user token
$ch = curl_init("https://graph.facebook.com/v21.0/oauth/access_token?grant_type=fb_exchange_token&client_id={$app_id}&client_secret={$app_secret}&fb_exchange_token=" . urlencode($short_tok));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15]);
$r = curl_exec($ch); curl_close($ch);
$d = json_decode($r, true);
$long_token = $d['access_token'] ?? '';
echo "Long-lived token: " . ($long_token ? substr($long_token,0,40).'...' : 'FAILED: '.$r) . "\n";

if ($long_token) {
    // Get permanent Page token
    $ch = curl_init("https://graph.facebook.com/v21.0/725963964220309?fields=access_token&access_token=" . urlencode($long_token));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>15]);
    $r2 = curl_exec($ch); curl_close($ch);
    $d2 = json_decode($r2, true);
    $page_token = $d2['access_token'] ?? '';
    echo "Page token: " . ($page_token ? substr($page_token,0,40).'... (len='.strlen($page_token).')' : 'FAILED: '.$r2) . "\n";

    if ($page_token) {
        // Test send capability
        echo "\n=== Test Send with new Page Token ===\n";
        $ch = curl_init("https://graph.facebook.com/v21.0/17841403194594979/messages");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'recipient'      => ['id' => '2141840089926427'],
                'message'        => ['text' => 'Test Sauto CRM'],
                'messaging_type' => 'RESPONSE',
                'access_token'   => $page_token,
            ]),
        ]);
        $r3 = curl_exec($ch); $c3 = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        echo "HTTP $c3: $r3\n";

        if ($c3 === 200) {
            // Save to DB
            $upd = $db->prepare("UPDATE {$prefx}_settings SET value=:v WHERE name='instagram_token_1'");
            $upd->execute([':v' => $page_token]);
            echo "\n✓ Token salvat in DB!\n";
        }
    }
}

// Test: Use FB Page token to send via IG endpoint
echo "=== Test FB Page Token → IG Messages Endpoint ===\n";
$fb_page_token = $fb['location_1_facebook_token'] ?? '';
$recipient_test = $_GET['recipient'] ?? '';
if ($recipient_test) {
    $ch = curl_init("https://graph.facebook.com/v21.0/{$ig_acct_id}/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode([
            'recipient'      => ['id' => $recipient_test],
            'message'        => ['text' => 'Test din Sauto CRM'],
            'messaging_type' => 'RESPONSE',
            'access_token'   => $fb_page_token,
        ]),
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    echo "HTTP $c: $r\n\n";
} else {
    echo "Adauga ?recipient=SENDER_ID pentru test trimitere\n\n";
}

// Show last webhook logs
echo "=== Last Webhook Logs (fb_post_error.log) ===\n";
$log_paths = [
    '/home/sautom/public_html/fb_post_error.log',
    '/home/sautom/public_html/fb_ai.log',
    __DIR__ . '/fb_post_error.log',
    __DIR__ . '/fb_ai.log',
];
foreach ($log_paths as $lp) {
    if (file_exists($lp) && filesize($lp) > 0) {
        echo "File: $lp\n";
        $lines = file($lp);
        echo implode('', array_slice($lines, -30));
        echo "\n---\n";
    }
}
echo "\n";

// Test: GET IG account info (with saved ID)
echo "=== IG Account Info (saved ID: $ig_acct_id) ===\n";
$ch = curl_init("https://graph.facebook.com/v21.0/{$ig_acct_id}?fields=id,name,username&access_token=" . urlencode($ig_token));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
$r = curl_exec($ch); curl_close($ch);
echo $r . "\n\n";

if ($recipient) {
    // Test: Send message via IG token + IG account endpoint
    echo "=== Test: /{ig_acct_id}/messages with IG token (Bearer) ===\n";
    $ch = curl_init("https://graph.facebook.com/v21.0/{$ig_acct_id}/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $ig_token],
        CURLOPT_POSTFIELDS => json_encode(['recipient'=>['id'=>$recipient],'message'=>['text'=>$text],'messaging_type'=>'RESPONSE']),
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    echo "HTTP $c: $r\n\n";

    // Test: Send message via IG token + query param
    echo "=== Test: /{ig_acct_id}/messages with IG token (query param) ===\n";
    $ch = curl_init("https://graph.facebook.com/v21.0/{$ig_acct_id}/messages?access_token=" . urlencode($ig_token));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['recipient'=>['id'=>$recipient],'message'=>['text'=>$text],'messaging_type'=>'RESPONSE']),
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    echo "HTTP $c: $r\n\n";
}

echo "Done.\n";
