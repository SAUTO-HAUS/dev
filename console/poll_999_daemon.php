<?php
/**
 * 999.md Chat Daemon — polls every 5 seconds
 * Cron watchdog (every minute):
 * * * * * * [ -f /tmp/poll_999_daemon.pid ] && kill -0 $(cat /tmp/poll_999_daemon.pid) 2>/dev/null || nohup /usr/local/bin/php /home/sautom/public_html/console/poll_999_daemon.php >> /home/sautom/public_html/logs/poll_999.log 2>&1 &
 */

define('_DOIT', 1);
require_once __DIR__ . '/../environment.php';
require_once __DIR__ . '/../content/default/defines.php';
require_once __DIR__ . '/../content/default/functions.php';
require_once __DIR__ . '/../content/default/config.php';
require_once __DIR__ . '/../content/default/dbi.php';
require_once __DIR__ . '/../content/admin/include/crm/crm_core.php';
require_once __DIR__ . '/../content/admin/include/crm/crm_inbox_core.php';
require_once __DIR__ . '/../App/Services/Chat999Service.php';

const POLL_INTERVAL = 2;
const MAX_RUNTIME   = 300; // restart every 5 minutes to free memory

// Single instance lock
$pid_file = '/tmp/poll_999_daemon.pid';
$pid_fp   = fopen($pid_file, 'c');
if (!flock($pid_fp, LOCK_EX | LOCK_NB)) {
    exit(0); // another instance running
}
fwrite($pid_fp, (string)getmypid());
ftruncate($pid_fp, strlen((string)getmypid()));

$accounts = [
    'sautohaus_999md' => 'stock',
    'regular_999md'   => 'stock',
    'order_999md'     => 'order',
    'korea_999md'     => 'order',
];

$daemon_start = time();
echo "[" . date('Y-m-d H:i:s') . "] daemon started pid=" . getmypid() . "\n";

while (time() - $daemon_start < MAX_RUNTIME) {
    $t0 = microtime(true);

    // Reconnect DB if needed
    try { $db->query('SELECT 1'); } catch (\Exception $e) {
        require __DIR__ . '/../content/default/dbi.php';
    }

    foreach ($accounts as $account_key => $dept) {
        $stmt = $db->prepare("SELECT value FROM {$prefx}_settings WHERE name=:k LIMIT 1");
        $stmt->execute([':k' => $account_key . '_session_key']);
        if (!$stmt->fetchColumn()) continue;

        try {
            $svc = new \App\Services\Chat999Service($db, $prefx, $account_key);
            [$messages, $contacts_count] = $svc->pollUnread();

            foreach ($messages as $msg) {
                $sender_id   = $msg['contact_user_id'];
                $sender_name = $msg['contact_login'] ?: $sender_id;
                $text        = $msg['text'];
                $all_mids         = $msg['all_mids']          ?? [$msg['msg_id']];
                $all_texts        = $msg['all_texts']         ?? [$text];
                $all_advert_titles= $msg['all_advert_titles'] ?? [];
                $all_advert_urls  = $msg['all_advert_urls']   ?? [];

                if (!$sender_id) continue;

                $session_id   = inbox_create_session($db, $prefx, '999md', $sender_id, $sender_name, $account_key, $dept);
                $session      = inbox_get_session($db, $prefx, '999md', $sender_id, $account_key);
                $advert_title = $msg['advert_title'] ?? '';
                $advert_url   = $msg['advert_url']   ?? '';
                if ($advert_title) {
                    $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET advert_title=:t, advert_url=:u WHERE id=:id")
                       ->execute([':t' => $advert_title, ':u' => $advert_url, ':id' => $session_id]);
                }

                // Save incoming messages (dedup by mid)
                foreach ($all_mids as $i => $m_id) {
                    $m_text  = trim($all_texts[$i] ?? '');
                    if (!$m_text || !$m_id) continue;
                    $m_atitle = $all_advert_titles[$i] ?? '';
                    $m_aurl   = $all_advert_urls[$i]   ?? '';
                    inbox_save_message($db, $prefx, $session_id, '999md', 'in',
                        $sender_id, $sender_name, $m_text,
                        $session->lead_id ?? null, false, null, '', $m_id,
                        $m_atitle, $m_aurl);
                }

                // Manager mode — skip AI
                if ($session && !$session->ai_active) {
                    if ($session->lead_id)
                        $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")
                           ->execute([':id' => $session->lead_id]);
                    continue;
                }

                if (!$text) continue;

                // Skip if already replied to last incoming message
                $last_in_mid = end($all_mids);
                $already = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_inbox_messages
                    WHERE session_id=:sid AND direction='out' AND is_ai=1
                    AND created_at >= (SELECT created_at FROM {$prefx}_crm_inbox_messages WHERE mid=:mid AND channel='999md' LIMIT 1)");
                $already->execute([':sid' => $session_id, ':mid' => $last_in_mid]);
                if ($already->fetchColumn() > 0) continue;

                // AI reply — trecem contextul anunțului ca AI-ul să știe despre ce mașină e vorba
                $history = inbox_get_history($db, $prefx, $session_id, 50);
                $car_context = '';
                if ($advert_title || $advert_url) {
                    $car_context = trim($advert_title . ' (' . $advert_url . ')');
                }
                $ai      = inbox_ai_reply($db, $prefx, '999md', $history, $text, $car_context);

                if ($ai['reply']) {
                    // Save FIRST to prevent duplicate
                    inbox_save_message($db, $prefx, $session_id, '999md', 'out',
                        'ai', 'AI Sauto', $ai['reply'], $session->lead_id ?? null, true);
                    $svc->sendMessage($sender_id, $ai['reply']);
                    echo "[" . date('Y-m-d H:i:s') . "] [$account_key] AI sent\n";
                }

                $pg_id = $session->page_id ?? '';
                if ($ai['trigger'] && $ai['phone']) {
                    inbox_create_lead($db, $prefx, $session_id, $ai['phone'], '999md', $dept, $sender_name ?: null, null, $pg_id);
                }
            }
        } catch (\Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] [$account_key] ERROR: " . $e->getMessage() . "\n";
        }
    }

    $sleep = max(0, POLL_INTERVAL - (int)(microtime(true) - $t0));
    sleep($sleep);
}

echo "[" . date('Y-m-d H:i:s') . "] daemon exiting after " . MAX_RUNTIME . "s\n";
flock($pid_fp, LOCK_UN);
fclose($pid_fp);
