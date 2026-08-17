<?php defined('_DOIT') or die('Restricted access');

/**
 * CRM Omnichannel Inbox Core
 * Handles FB Messenger, Instagram, Telegram sessions and AI pre-qualification
 */

// ── Channel helpers ──────────────────────────────────────────────────────────

function inbox_get_session(PDO $db, string $prefx, string $channel, string $sender_id, string $page_id = ''): ?object {
    if ($page_id) {
        $stmt = $db->prepare("SELECT * FROM {$prefx}_crm_inbox_sessions WHERE channel=:ch AND sender_id=:sid AND page_id=:pid LIMIT 1");
        $stmt->execute([':ch' => $channel, ':sid' => $sender_id, ':pid' => $page_id]);
    } else {
        $stmt = $db->prepare("SELECT * FROM {$prefx}_crm_inbox_sessions WHERE channel=:ch AND sender_id=:sid LIMIT 1");
        $stmt->execute([':ch' => $channel, ':sid' => $sender_id]);
    }
    return $stmt->fetchObject() ?: null;
}

function inbox_create_session(PDO $db, string $prefx, string $channel, string $sender_id,
                               string $sender_name = '', string $page_id = '', string $dept = 'stock'): int {
    // First try INSERT
    $stmt = $db->prepare("INSERT IGNORE INTO {$prefx}_crm_inbox_sessions
        (channel, sender_id, sender_name, department, page_id, status, ai_active)
        VALUES (:ch, :sid, :nm, :dept, :pid, 'ai', 1)");
    $stmt->execute([':ch'=>$channel, ':sid'=>$sender_id, ':nm'=>$sender_name, ':dept'=>$dept, ':pid'=>$page_id]);

    // Then UPDATE — also replace if current name looks like a raw numeric ID (no letters)
    if ($sender_name !== '') {
        $stmt2 = $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET sender_name=:nm, status=IF(status='closed','active',status), updated_at=NOW() WHERE channel=:ch AND sender_id=:sid AND page_id=:pid AND (sender_name='' OR sender_name IS NULL OR sender_name REGEXP '^[0-9]+$')");
        $stmt2->execute([':nm'=>$sender_name, ':ch'=>$channel, ':sid'=>$sender_id, ':pid'=>$page_id]);
    } else {
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET status=IF(status='closed','active',status), updated_at=NOW() WHERE channel=:ch AND sender_id=:sid AND page_id=:pid")
           ->execute([':ch'=>$channel, ':sid'=>$sender_id, ':pid'=>$page_id]);
    }

    $s = inbox_get_session($db, $prefx, $channel, $sender_id, $page_id);
    return $s ? (int)$s->id : 0;
}

function inbox_save_message(PDO $db, string $prefx, int $session_id, string $channel,
                             string $direction, string $sender_id, string $sender_name,
                             string $body, ?int $lead_id = null, bool $is_ai = false,
                             ?int $author_id = null, string $author_name = '', string $mid = '',
                             string $advert_title = '', string $advert_url = ''): int {
                                
    if ($direction === 'in') {
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET status=IF(status='closed','active',status), updated_at=NOW() WHERE id=:sid")
           ->execute([':sid' => $session_id]);
    }

    // If mid is provided, skip duplicate messages from repeated polling.
    // Backfill advert_title/url dacă mesajul exista deja fără ele (rezultat al unui
    // poll anterior cu cod vechi care nu captura topic-ul).
    if ($mid !== '') {
        $chk = $db->prepare("SELECT id FROM {$prefx}_crm_inbox_messages WHERE mid=:mid AND channel=:ch LIMIT 1");
        $chk->execute([':mid' => $mid, ':ch' => $channel]);
        $existing = $chk->fetchColumn();
        if ($existing) {
            if ($advert_url !== '' || $advert_title !== '') {
                $db->prepare("UPDATE {$prefx}_crm_inbox_messages
                    SET advert_title = :atitle, advert_url = :aurl
                    WHERE id = :id AND (advert_url = '' OR advert_url IS NULL)")
                   ->execute([':atitle' => $advert_title, ':aurl' => $advert_url, ':id' => $existing]);
            }
            return (int)$existing;
        }
    }

    $stmt = $db->prepare("INSERT INTO {$prefx}_crm_inbox_messages
        (session_id, lead_id, channel, direction, sender_id, sender_name, body, advert_title, advert_url, is_ai, author_id, author_name, mid)
        VALUES (:sid, :lid, :ch, :dir, :sndr, :snm, :body, :atitle, :aurl, :ai, :aid, :anm, :mid)");
    $stmt->execute([
        ':sid'   => $session_id,
        ':lid'   => $lead_id,
        ':ch'    => $channel,
        ':dir'   => $direction,
        ':sndr'  => $sender_id,
        ':snm'   => $sender_name,
        ':body'  => $body,
        ':atitle'=> $advert_title,
        ':aurl'  => $advert_url,
        ':ai'    => $is_ai ? 1 : 0,
        ':aid'   => $author_id,
        ':anm'   => $author_name,
        ':mid'   => $mid,
    ]);
    $msg_id = (int)$db->lastInsertId();
    if ($direction === 'in') {
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET unread_count = unread_count + 1, status = IF(status='closed','active',status), updated_at=NOW() WHERE id = :sid")
           ->execute([':sid' => $session_id]);
    }
    return $msg_id;
}

function inbox_get_history(PDO $db, string $prefx, int $session_id, int $limit = 50): array {
    $stmt = $db->prepare("SELECT *, (direction = 'internal') AS is_internal FROM (
        SELECT * FROM {$prefx}_crm_inbox_messages
        WHERE session_id=:sid AND direction IN ('in','out','internal')
        ORDER BY created_at DESC LIMIT $limit
    ) sub ORDER BY created_at ASC");
    $stmt->execute([':sid' => $session_id]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// ── Settings helpers ─────────────────────────────────────────────────────────

function inbox_get_fb_settings(PDO $db, string $prefx): array {
    $stmt = $db->query("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%facebook%' OR name LIKE '%instagram%'");
    $s = [];
    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $r) $s[$r->name] = $r->value;
    return $s;
}

function inbox_get_tg_settings(PDO $db, string $prefx, string $type = 'regular'): array {
    $stmt = $db->query("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%telegram%'");
    $s = [];
    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $r) $s[$r->name] = $r->value;
    return $s;
}

function inbox_is_ai_enabled(PDO $db, string $prefx, string $channel): bool {
    // No static cache: long-running daemons (poll_999_daemon) must see toggle
    // changes applied via Settings UI within seconds, not after process restart.
    $stmt = $db->prepare("SELECT v FROM {$prefx}_crm_settings WHERE k=:k LIMIT 1");

    $read = function(string $key) use ($stmt): bool {
        $stmt->execute([':k' => $key]);
        $v = $stmt->fetchColumn();
        return ($v === false || $v === null || $v === '' || $v === '1');
    };

    // Sub-channels of 999.md require BOTH master AND sub-channel enabled
    if (substr($channel, -6) === '_999md' && $channel !== '999md') {
        if (!$read('inbox_ai_enabled_999md')) return false;
    }
    return $read('inbox_ai_enabled_' . $channel);
}

function inbox_get_crm_ai_prompt(PDO $db, string $prefx, string $channel = 'default'): string {
    $stmt = $db->prepare("SELECT v FROM {$prefx}_crm_settings WHERE k=:k LIMIT 1");
    $stmt->execute([':k' => 'inbox_ai_prompt_' . $channel]);
    $v = $stmt->fetchColumn();
    if ($v) return $v;

    // Intermediate fallback for 999.md sub-accounts (e.g. sautohaus_999md → 999md → default)
    if (substr($channel, -6) === '_999md') {
        $stmt->execute([':k' => 'inbox_ai_prompt_999md']);
        $v = $stmt->fetchColumn();
        if ($v) return $v;
    }

    // fallback to default prompt
    $stmt->execute([':k' => 'inbox_ai_prompt_default']);
    $v = $stmt->fetchColumn();
    if ($v) return $v;

    return 'Ești asistentul auto al companiei Sauto-Haus din Moldova. Răspunzi politicos și scurt în limba în care scrie clientul (română sau rusă). Scopul tău este să afli ce mașină caută clientul și să obții numărul lui de telefon pentru a-l contacta un manager. Când clientul îți dă numărul de telefon, răspunde cu: LEAD_READY:[numărul]';
}

// ── AI pre-qualification ─────────────────────────────────────────────────────

function inbox_ai_reply(PDO $db, string $prefx, string $channel, array $history, string $new_message, string $car_context = ''): array {
    $log_root = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 4);

    // Channel-level kill switch (set in CRM settings)
    if (!inbox_is_ai_enabled($db, $prefx, $channel)) {
        return ['reply' => '', 'trigger' => false, 'phone' => ''];
    }

    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (!$api_key) {
        file_put_contents($log_root . '/logs/fb_ai.log',
            date('Y-m-d H:i:s') . " NO API KEY\n", FILE_APPEND | LOCK_EX);
        // Check phone directly even without AI
        $trigger_fallback = false;
        $phone_fallback   = '';
        $msg_no_urls = preg_replace('/https?:\/\/\S+/u', '', $new_message);
        if (preg_match('/(?:^|[\s,;:(\[])(?:(\+|00)[1-9]\d{6,14}|0[267]\d{7})(?![\d])/um', $msg_no_urls, $m2f)) {
            $trigger_fallback = true;
            $phone_fallback   = preg_replace('/\D/', '', $m2f[0]);
        }
        return ['reply' => '', 'trigger' => $trigger_fallback, 'phone' => $phone_fallback, 'car_label' => ''];
    }

    $stmt = $db->prepare("SELECT setting_value FROM {$prefx}_ai_settings WHERE setting_key='openai_model' LIMIT 1");
    $stmt->execute();
    $model = $stmt->fetchColumn() ?: 'gpt-4.1-mini';

    $system_prompt = inbox_get_crm_ai_prompt($db, $prefx, $channel);
    if ($car_context) {
        $system_prompt .= "\n\nClientul este interesat de această mașină din anunț: {$car_context}. Folosește aceste informații ca context în conversație.";
    }

    $messages = [['role' => 'system', 'content' => $system_prompt]];
    foreach ($history as $h) {
        $messages[] = ['role' => $h->direction === 'in' ? 'user' : 'assistant', 'content' => $h->body];
    }
    $messages[] = ['role' => 'user', 'content' => $new_message];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $api_key, 'Content-Type: application/json'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['model' => $model, 'messages' => $messages, 'max_completion_tokens' => 300]),
    ]);
    $resp = curl_exec($ch);
    $curl_err = curl_error($ch);
    curl_close($ch);

    file_put_contents($log_root . '/logs/fb_ai.log',
        date('Y-m-d H:i:s') . " openai_resp=" . substr($resp, 0, 500) . " curl_err=$curl_err\n", FILE_APPEND | LOCK_EX);

    $data  = json_decode($resp, true);
    $reply = trim($data['choices'][0]['message']['content'] ?? '');

    // Detect LEAD_READY trigger
    $trigger = false;
    $phone   = '';
    if (preg_match('/LEAD_READY:\s*([+\d\s\(\)-]{7,20})/u', $reply, $m)) {
        $trigger = true;
        $phone   = preg_replace('/\D/', '', $m[1]);
        // Remove trigger marker from visible reply
        $reply = preg_replace('/LEAD_READY:\s*[+\d\s\(\)-]{7,20}/u', '', $reply);
        $reply = trim($reply) ?: 'Mulțumim! Un manager vă va contacta în scurt timp.';
    }

    // Also detect phone number directly in user message (any international format)
    // Strip URLs first to avoid matching numbers inside links (e.g. 999.md/ro/103685085)
    // Require explicit + or 00 prefix to avoid matching prices/years (e.g. "19499 2023")
    $msg_no_urls = preg_replace('/https?:\/\/\S+/u', '', $new_message);
    if (!$trigger && preg_match('/(?:^|[\s,;:(\[])(?:(\+|00)[1-9]\d{6,14}|0[267]\d{7})(?![\d])/um', $msg_no_urls, $m2)) {
        $trigger = true;
        $phone   = preg_replace('/\D/', '', $m2[0]);
    }

    return ['reply' => $reply, 'trigger' => $trigger, 'phone' => $phone, 'car_label' => ''];
}

// ── Lead creation from inbox ─────────────────────────────────────────────────

function inbox_create_lead(PDO $db, string $prefx, int $session_id, string $phone,
                            string $channel, string $dept = 'stock', ?string $client_name = null,
                            ?string $car_label = null, string $page_id = ''): int {
    require_once __DIR__ . '/crm_core.php';
    $p = crm_normalize_phone($phone);

    // Check if lead already exists for this phone (only when phone is provided)
    if ($p) {
        $existing = crm_find_lead_by_phone($db, $prefx, $p);
        if ($existing) {
            // Link session to existing lead, update car_label if not set
            $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET lead_id=:lid, status='active' WHERE id=:sid")
               ->execute([':lid' => $existing->id, ':sid' => $session_id]);
            $db->prepare("UPDATE {$prefx}_crm_inbox_messages SET lead_id=:lid WHERE session_id=:sid AND lead_id IS NULL")
               ->execute([':lid' => $existing->id, ':sid' => $session_id]);
            if ($car_label && !$existing->car_label) {
                $db->prepare("UPDATE {$prefx}_crm_leads SET car_label=:cl WHERE id=:id")
                   ->execute([':cl' => $car_label, ':id' => $existing->id]);
            }
            return (int)$existing->id;
        }
    }

    // Map page_id → source name (more specific, checked first)
    $page_source_map = [
        'order_999md'      => '999 La Comanda EU',
        'korea_999md'      => 'Corea 999',
        'usa_999md'        => 'SUA 999',
        'sautohaus_999md'  => '999 Stock',
        'regular_999md'    => '999 Stock',
        'order_telegram'   => 'Telegram - la comanda',
        'regular_telegram' => 'Telegram Stock',
        'ordercars'        => 'Site - la comanda',
        'cars'             => 'Site stock cartela marfii',
        '725963964220309'  => 'Meta stock',
        '482777831588669'  => 'Filiala Pruncul',
    ];
    // Fallback: map channel+dept to source name
    $channel_source_map = [
        'telegram_stock'   => 'Telegram Stock',
        'telegram_order'   => 'Telegram - la comanda',
        'facebook_stock'   => 'Meta stock',
        'facebook_order'   => 'Meta la comanda',
        'instagram_stock'  => 'Meta stock',
        'instagram_order'  => 'Meta la comanda',
        '999md_stock'      => '999 Stock',
        '999md_order'      => '999 La Comanda EU',
        'viber_stock'      => 'Viber Stock',
        'viber_order'      => 'Viber - la comanda',
        'site_stock'       => 'Site stock cartela marfii',
        'site_order'       => 'Site la comanda',
    ];
    $source_name = ($page_id && isset($page_source_map[$page_id]))
        ? $page_source_map[$page_id]
        : ($channel_source_map["{$channel}_{$dept}"] ?? null);
    $source_id   = null;
    if ($source_name) {
        $ss = $db->prepare("SELECT id FROM {$prefx}_crm_sources WHERE name=:n LIMIT 1");
        $ss->execute([':n' => $source_name]);
        $source_id = $ss->fetchColumn() ?: null;
        if (!$source_id) {
            $db->prepare("INSERT INTO {$prefx}_crm_sources (name, color, phone_number) VALUES (:n, '#6c757d', '')")
               ->execute([':n' => $source_name]);
            $source_id = (int)$db->lastInsertId() ?: null;
        }
    }

    $stmt = $db->prepare("INSERT INTO {$prefx}_crm_leads
        (department, status, phone, source_id, client_name, car_label, origin, last_action_at)
        VALUES (:dept, 'active', :phone, :src, :nm, :cl, 'inbox', NOW())");
    $stmt->execute([':dept'=>$dept, ':phone'=>$p, ':src'=>$source_id, ':nm'=>$client_name, ':cl'=>$car_label]);
    $lead_id = (int)$db->lastInsertId();

    if ($lead_id) {
        // Link session and all messages to lead
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET lead_id=:lid, status='active', ai_active=0 WHERE id=:sid")
           ->execute([':lid' => $lead_id, ':sid' => $session_id]);
        $db->prepare("UPDATE {$prefx}_crm_inbox_messages SET lead_id=:lid WHERE session_id=:sid")
           ->execute([':lid' => $lead_id, ':sid' => $session_id]);
        crm_audit($db, $prefx, 'lead_created_from_' . $channel, 'lead', $lead_id, '', 'active');
    }

    return $lead_id;
}

// ── Send reply helpers ───────────────────────────────────────────────────────

function inbox_send_fb_message(string $recipient_id, string $text, string $page_token): bool {
    if (!$page_token || !$recipient_id) return false;
    $ch = curl_init('https://graph.facebook.com/v21.0/me/messages?access_token=' . urlencode($page_token));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['recipient' => ['id' => $recipient_id], 'message' => ['text' => $text]]),
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}

function inbox_send_instagram_message(string $ig_user_id, string $recipient_id, string $text, string $ig_token): bool {
    if (!$ig_token || !$recipient_id || !$ig_user_id) {
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/fb_ai.log',
            date('Y-m-d H:i:s') . " instagram_send SKIP: ig_user=$ig_user_id recipient=$recipient_id token_len=" . strlen($ig_token) . "\n",
            FILE_APPEND | LOCK_EX);
        return false;
    }
    // Use graph.instagram.com with IGAAV token (Bearer auth)
    $payload = json_encode([
        'recipient' => ['id' => $recipient_id],
        'message'   => ['text' => $text],
    ]);
    $url = 'https://graph.instagram.com/v21.0/me/messages';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $ig_token,
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);
    $resp     = curl_exec($ch);
    $curl_err = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/fb_ai.log',
        date('Y-m-d H:i:s') . " instagram_send ig_user=$ig_user_id recipient=$recipient_id code=$code curl_err=$curl_err resp=" . substr($resp,0,400) . "\n",
        FILE_APPEND | LOCK_EX);
    return $code === 200;
}

function inbox_send_tg_message(string $chat_id, string $text, string $bot_token): bool {
    if (!$bot_token || !$chat_id) {
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/fb_ai.log',
            date('Y-m-d H:i:s') . " tg_send SKIP: chat_id=$chat_id token_len=" . strlen($bot_token) . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode(['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML']),
    ]);
    $resp     = curl_exec($ch);
    $curl_err = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/fb_ai.log',
        date('Y-m-d H:i:s') . " tg_send chat_id=$chat_id code=$code curl_err=$curl_err resp=" . substr($resp, 0, 300) . "\n", FILE_APPEND | LOCK_EX);
    return $code === 200;
}

function inbox_send_viber_message(string $receiver_id, string $text, string $bot_token): bool {
    if (!$bot_token || !$receiver_id) return false;
    $ch = curl_init('https://chatapi.viber.com/pa/send_message');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Viber-Auth-Token: ' . $bot_token,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'receiver'        => $receiver_id,
            'min_api_version' => 1,
            'type'            => 'text',
            'text'            => $text,
        ]),
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return false;
    $data = json_decode($resp, true);
    return isset($data['status']) && $data['status'] === 0;
}

function inbox_send_fb_photo(string $recipient_id, string $image_url, string $page_token): bool {
    if (!$page_token || !$recipient_id) return false;
    $ch = curl_init('https://graph.facebook.com/v21.0/me/messages?access_token=' . urlencode($page_token));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode([
            'recipient' => ['id' => $recipient_id],
            'message'   => ['attachment' => ['type' => 'image', 'payload' => ['url' => $image_url, 'is_reusable' => true]]],
        ]),
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/crm_send.log',
        date('Y-m-d H:i:s')." fb_photo url=$image_url code=$code resp=".substr($resp,0,300)."\n", FILE_APPEND|LOCK_EX);
    return $code === 200;
}

function inbox_send_instagram_photo(string $ig_user_id, string $recipient_id, string $image_url, string $ig_token): bool {
    if (!$ig_token || !$recipient_id || !$ig_user_id) return false;
    $ch = curl_init('https://graph.instagram.com/v21.0/me/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $ig_token,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'recipient' => ['id' => $recipient_id],
            'message'   => ['attachment' => ['type' => 'image', 'payload' => ['url' => $image_url, 'is_reusable' => true]]],
        ]),
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}

function inbox_send_viber_photo(string $receiver_id, string $image_url, string $bot_token): bool {
    if (!$bot_token || !$receiver_id) return false;
    $ch = curl_init('https://chatapi.viber.com/pa/send_message');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Viber-Auth-Token: ' . $bot_token,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'receiver'        => $receiver_id,
            'min_api_version' => 1,
            'type'            => 'picture',
            'text'            => '',
            'media'           => $image_url,
        ]),
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200;
}
