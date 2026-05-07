<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_core.php');
    // ── TEST 222 ──────────────────────────────────────────────────

$fn      = trim($_POST['fn'] ?? $_POST['action'] ?? '');
$lead_id = (int)($_POST['id'] ?? 0);
$uid     = (int)$user_id;
$uname   = $user_name ?? '';

header('Content-Type: application/json');

switch ($fn) {

    // ── Take pool lead ──────────────────────────────────────────────────
    case 'take_lead':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $lead = crm_get_lead($db, $prefx, $lead_id);
        if (!$lead || $lead->owner_id) {
            echo json_encode(['ok'=>false,'msg'=>'Lead-ul nu mai este disponibil în pool.']); exit;
        }
        $stmt = $db->prepare("UPDATE {$prefx}_crm_leads SET owner_id=:uid, last_action_at=NOW(), origin='manual' WHERE id=:id AND owner_id IS NULL");
        $stmt->execute([':uid'=>$uid, ':id'=>$lead_id]);
        if ($stmt->rowCount() > 0) {
            crm_audit($db, $prefx, 'take_lead', 'lead', $lead_id, 'pool', (string)$uid, $uid, $uname);
            echo json_encode(['ok'=>true]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'Lead-ul a fost preluat deja de alt agent.']);
        }
        exit;

    // ── Mark as junk ───────────────────────────────────────────────────
    case 'mark_junk':
        if (!crm_can_junk($user_role, $user_id)) { echo json_encode(['ok'=>false,'msg'=>'Acces restricționat']); exit; }
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $reason   = trim($_POST['reason'] ?? '');
        $old_lead = crm_get_lead($db, $prefx, $lead_id);
        $stmt     = $db->prepare("UPDATE {$prefx}_crm_leads SET status='junk', junk_reason=:r, last_action_at=NOW(), is_rot=0 WHERE id=:id");
        $stmt->execute([':r'=>$reason, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'mark_junk', 'lead', $lead_id, $old_lead->status ?? '', 'junk', $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    case 'delete_lead':
        if (!crm_can_junk($user_role, $user_id)) { echo json_encode(['ok'=>false,'msg'=>'Acces restricționat']); exit; }
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $old_lead = crm_get_lead($db, $prefx, $lead_id);
        $old_status = $old_lead->status ?? 'active';
        $db->prepare("UPDATE {$prefx}_crm_leads SET status='junk', prev_status=:ps, last_action_at=NOW(), is_rot=0 WHERE id=:id")->execute([':ps'=>$old_status, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'archived', 'lead', $lead_id, $old_status, 'junk', $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    case 'restore_lead':
        if (!crm_can_junk($user_role, $user_id)) { echo json_encode(['ok'=>false,'msg'=>'Acces restricționat']); exit; }
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $old_lead = crm_get_lead($db, $prefx, $lead_id);
        $restore_to = $old_lead->prev_status ?? 'unprocessed';
        if (!in_array($restore_to, ['active','missed','unprocessed','processed','transaction','closed'])) $restore_to = 'unprocessed';
        $db->prepare("UPDATE {$prefx}_crm_leads SET status=:s, prev_status=NULL, last_action_at=NOW() WHERE id=:id AND status='junk'")->execute([':s'=>$restore_to, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'restored', 'lead', $lead_id, 'junk', $restore_to, $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    case 'set_doc_tx_status':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $tx_st = $_POST['tx_status'] ?? '';
        if (!in_array($tx_st, ['transaction','closed'])) { echo json_encode(['ok'=>false,'msg'=>'Status invalid']); exit; }
        $db->prepare("UPDATE {$prefx}_docs_ctlg SET tx_status=:s WHERE id=:id")
           ->execute([':s'=>$tx_st, ':id'=>$lead_id]);
        echo json_encode(['ok'=>true]);
        exit;

    case 'archive_doc':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $db->prepare("UPDATE {$prefx}_docs_ctlg SET archived=1 WHERE id=:id")
           ->execute([':id' => $lead_id]);
        echo json_encode(['ok'=>true]);
        exit;

    case 'unarchive_doc':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $db->prepare("UPDATE {$prefx}_docs_ctlg SET archived=0 WHERE id=:id")
           ->execute([':id' => $lead_id]);
        echo json_encode(['ok'=>true]);
        exit;

    case 'set_lead_status':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $new_st = $_POST['status'] ?? '';
        if (!in_array($new_st, ['transaction','closed'])) { echo json_encode(['ok'=>false,'msg'=>'Status invalid']); exit; }
        $db->prepare("UPDATE {$prefx}_crm_leads SET status=:s, last_action_at=NOW(), updated_at=NOW() WHERE id=:id AND status NOT IN ('junk','closed')")
           ->execute([':s'=>$new_st, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'doc_status_change', 'lead', $lead_id, 'vinzare_avans_confirm', $new_st, $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    case 'takeover_lead':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $lead = crm_get_lead($db, $prefx, $lead_id);
        if (!$lead) { echo json_encode(['ok'=>false,'msg'=>'Lead negăsit']); exit; }
        $old_owner = $lead->owner_id;
        $db->prepare("UPDATE {$prefx}_crm_leads SET owner_id=:uid, last_action_at=NOW(), origin='manual' WHERE id=:id")
           ->execute([':uid'=>$uid, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'manual_takeover', 'lead', $lead_id, (string)$old_owner, (string)$uid, $uid, $uname);
        if ($old_owner && (int)$old_owner !== $uid) {
            crm_notify($db, $prefx, (int)$old_owner, 'notif_takeover', 'takeover', $lead_id, [
                'phone' => $lead->phone ?? '',
                'name'  => $uname,
            ]);
        }
        crm_notify($db, $prefx, $uid, 'notif_takeover_self', 'info', $lead_id, [
            'phone' => $lead->phone ?? '',
        ]);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Change status ──────────────────────────────────────────────────
    case 'change_status':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $allowed  = ['transaction','closed','processed','active','missed','unprocessed'];
        $new_st   = in_array($_POST['status'] ?? '', $allowed) ? $_POST['status'] : null;
        if (!$new_st) { echo json_encode(['ok'=>false,'msg'=>'Status invalid']); exit; }
        if ($new_st === 'closed' && !crm_can_see_all($user_role, $user_id)) {
            echo json_encode(['ok'=>false,'msg'=>'Acces restricționat']); exit;
        }
        $old_lead = crm_get_lead($db, $prefx, $lead_id);
        $stmt     = $db->prepare("UPDATE {$prefx}_crm_leads SET status=:s, last_action_at=NOW() WHERE id=:id");
        $stmt->execute([':s'=>$new_st, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'change_status', 'lead', $lead_id, $old_lead->status ?? '', $new_st, $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Change department (stock ↔ order) ─────────────────────────────
    case 'change_dept':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $new_dept = in_array($_POST['dept'] ?? '', ['stock','order','pruncul']) ? $_POST['dept'] : null;
        if (!$new_dept) { echo json_encode(['ok'=>false,'msg'=>'Department invalid']); exit; }
        $old_lead = crm_get_lead($db, $prefx, $lead_id);
        $db->prepare("UPDATE {$prefx}_crm_leads SET department=:d, owner_id=NULL, last_action_at=NOW() WHERE id=:id")
           ->execute([':d'=>$new_dept, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'change_dept', 'lead', $lead_id, $old_lead->department ?? '', $new_dept, $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Save note ──────────────────────────────────────────────────────
    case 'save_note':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $body     = trim($_POST['body'] ?? '');
        $is_int   = isset($_POST['is_internal']) ? (int)$_POST['is_internal'] : 0;
        if (!$body) { echo json_encode(['ok'=>false,'msg'=>'Notița este goală']); exit; }
        $dir  = $is_int ? 'internal' : 'out';
        $stmt = $db->prepare("INSERT INTO {$prefx}_crm_messages (lead_id,channel,direction,is_internal,author_id,author_name,body) VALUES (:lid,'internal_note',:dir,:int,:aid,:anm,:body)");
        $stmt->execute([':lid'=>$lead_id,':dir'=>$dir,':int'=>$is_int,':aid'=>$uid,':anm'=>$uname,':body'=>$body]);
        $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")->execute([':id'=>$lead_id]);
        crm_audit($db, $prefx, 'add_note', 'lead', $lead_id, '', $is_int?'[internal]':'', $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    case 'delete_note':
        $msg_id = (int)($_POST['msg_id'] ?? 0);
        if (!$msg_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $msg_row = $db->prepare("SELECT lead_id FROM {$prefx}_crm_messages WHERE id=:id AND channel='internal_note' LIMIT 1");
        $msg_row->execute([':id'=>$msg_id]);
        $msg_lead_id = (int)($msg_row->fetchColumn() ?: 0);
        $db->prepare("DELETE FROM {$prefx}_crm_messages WHERE id=:id AND channel='internal_note'")->execute([':id'=>$msg_id]);
        if ($msg_lead_id) crm_audit($db, $prefx, 'delete_note', 'lead', $msg_lead_id, '', '', $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Save client name ───────────────────────────────────────────────
    case 'save_client_name':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $name = trim($_POST['name'] ?? '');
        $stmt = $db->prepare("UPDATE {$prefx}_crm_leads SET client_name=:n WHERE id=:id");
        $stmt->execute([':n'=>$name, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'set_client_name', 'lead', $lead_id, '', $name, $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    case 'save_phone':
        $lead_id = (int)($_POST['id'] ?? $_POST['lead_id'] ?? 0);
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $new_phone = crm_normalize_phone(trim($_POST['phone'] ?? ''));
        if (strlen($new_phone) < 6) { echo json_encode(['ok'=>false,'msg'=>'Număr prea scurt']); exit; }
        $db->prepare("UPDATE {$prefx}_crm_leads SET phone=:p WHERE id=:id")->execute([':p'=>$new_phone, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'set_phone', 'lead', $lead_id, '', $new_phone, $uid, $uname);
        echo json_encode(['ok'=>true, 'phone_fmt'=>crm_format_phone($new_phone)]);
        exit;

    case 'set_owner':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $owner_id = (int)($_POST['owner_id'] ?? 0);
        $old = $db->prepare("SELECT owner_id FROM {$prefx}_crm_leads WHERE id=:id");
        $old->execute([':id'=>$lead_id]);
        $old_owner = (string)($old->fetchColumn() ?? '');
        $stmt = $db->prepare("UPDATE {$prefx}_crm_leads SET owner_id=:o WHERE id=:id");
        $stmt->execute([':o'=>$owner_id ?: null, ':id'=>$lead_id]);
        crm_audit($db, $prefx, 'set_owner', 'lead', $lead_id, $old_owner, (string)$owner_id, $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Make outbound call ─────────────────────────────────────────────
    case 'make_call':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $phone = trim($_POST['phone'] ?? '');
        if (!$phone) { echo json_encode(['ok'=>false,'msg'=>'Număr lipsă']); exit; }

        // Use pbx_login if set, otherwise fall back to login
        $ur = $db->prepare("SELECT COALESCE(NULLIF(TRIM(pbx_login),''), login) AS pbx_lg FROM {$prefx}_adm_usr WHERE id=:id LIMIT 1");
        $ur->execute([':id'=>$uid]);
        $pbx_login = $ur->fetchColumn() ?: '';

        $result = crm_make_call($db, $prefx, crm_normalize_phone($phone), $pbx_login);

        if ($result['code'] >= 200 && $result['code'] < 300) {
            $old = crm_get_lead($db, $prefx, $lead_id);
            if ($old && in_array($old->status, ['active','missed','unprocessed'])) {
                $db->prepare("UPDATE {$prefx}_crm_leads SET status='processed', last_action_at=NOW() WHERE id=:id")->execute([':id'=>$lead_id]);
                crm_audit($db, $prefx, 'auto_processed', 'lead', $lead_id, $old->status, 'processed', $uid, $uname);
            }
            // Outbound counter + takeover
            $db->prepare("UPDATE {$prefx}_crm_leads SET outbound_count=outbound_count+1, outbound_last_user=:u, last_action_at=NOW() WHERE id=:id")->execute([':u'=>$uid,':id'=>$lead_id]);
            $check = crm_get_lead($db, $prefx, $lead_id);
            if ($check) {
                $threshold = (int)crm_get_setting($db, $prefx, 'takeover_threshold', 2);
                if ((int)$check->outbound_count >= $threshold && (int)$check->outbound_last_user === $uid && (int)$check->owner_id !== $uid) {
                    $old_owner = $check->owner_id;
                    $db->prepare("UPDATE {$prefx}_crm_leads SET owner_id=:u WHERE id=:id")->execute([':u'=>$uid,':id'=>$lead_id]);
                    crm_audit($db, $prefx, 'auto_takeover', 'lead', $lead_id, (string)$old_owner, (string)$uid, $uid, $uname);
                }
            }
            echo json_encode(['ok'=>true]);
        } else {
            echo json_encode(['ok'=>false,'msg'=>'PBX error: HTTP '.$result['code']]);
        }
        exit;

    // ── Save call filter settings ──────────────────────────────────────────
    case 'save_call_settings':
        if (!crm_can_settings($user_role, $user_id)) { echo json_encode(['ok'=>false,'msg'=>'Acces restricționat']); exit; }
        $min_dur = max(0, (int)($_POST['call_min_duration'] ?? 0));
        $db->prepare("INSERT INTO {$prefx}_crm_settings (k,v) VALUES ('call_min_duration',:v1) ON DUPLICATE KEY UPDATE v=:v2")
           ->execute([':v1' => (string)$min_dur, ':v2' => (string)$min_dur]);
        echo json_encode(['ok'=>true]);
        exit;

    // ── STT: transcribe call manually ─────────────────────────────────────
    case 'stt_transcribe':
        require_once(_ADM_INCL.'/crm/crm_stt.php');
        $call_id = (int)($_POST['call_id'] ?? 0);
        if (!$call_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $text = stt_transcribe_call($db, $prefx, $call_id);
        if ($text) {
            echo json_encode(['ok'=>true, 'transcript'=>$text]);
        } else {
            // Check status for better error message
            $s = $db->prepare("SELECT stt_status, recording_url FROM {$prefx}_crm_call_logs WHERE id=:id LIMIT 1");
            $s->execute([':id'=>$call_id]);
            $row = $s->fetchObject();
            $msg = 'Transcriere eșuată.';
            if (!$row || !$row->recording_url) $msg = 'Fără înregistrare audio.';
            elseif (!defined('OPENAI_API_KEY') || !OPENAI_API_KEY) $msg = 'OpenAI API Key lipsă.';
            echo json_encode(['ok'=>false,'msg'=>$msg]);
        }
        exit;

    // ── Inbox: send message ────────────────────────────────────────────────
    case 'inbox_send_photos':
        require_once(_ADM_INCL.'/crm/crm_inbox_core.php');
        $sid = (int)($_POST['sid'] ?? 0);
        if (!$sid || empty($_FILES['photos'])) { echo json_encode(['ok'=>false,'msg'=>'Date lipsă']); exit; }
        $sess_stmt = $db->prepare("SELECT * FROM {$prefx}_crm_inbox_sessions WHERE id=:id LIMIT 1");
        $sess_stmt->execute([':id'=>$sid]);
        $sess = $sess_stmt->fetchObject();
        $channel = $sess->channel ?? '';
        if (!$sess || !in_array($channel, ['telegram','facebook','instagram','viber'])) {
            echo json_encode(['ok'=>false,'msg'=>'Canal invalid']);
            exit;
        }

        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/crm/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $allowed = ['jpg','jpeg','png','gif','webp'];

        $saved = [];
        $files_raw = $_FILES['photos'];
        $count = is_array($files_raw['name']) ? count($files_raw['name']) : 1;
        for ($fi = 0; $fi < $count; $fi++) {
            $tmp  = is_array($files_raw['tmp_name']) ? $files_raw['tmp_name'][$fi] : $files_raw['tmp_name'];
            $name = is_array($files_raw['name'])     ? $files_raw['name'][$fi]     : $files_raw['name'];
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: 'jpg';
            if (!in_array($ext, $allowed) || !getimagesize($tmp)) continue;
            $fname = 'photo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest  = $upload_dir . $fname;
            move_uploaded_file($tmp, $dest);
            $saved[] = ['path' => $dest, 'url' => '/uploads/crm/' . $fname];
        }
        if (empty($saved)) { echo json_encode(['ok'=>false,'msg'=>'Fișiere invalide']); exit; }

        $caption = trim($_POST['caption'] ?? '');

        // Save caption first in DB (so order in CRM is: text → photos)
        if ($caption !== '') {
            inbox_save_message($db, $prefx, $sid, $channel, 'out', (string)$uid, $uname, $caption, $sess->lead_id ?: null, false, $uid, $uname, '');
        }

        // Save each photo as separate message in DB
        $last_id = 0;
        $previews = [];
        foreach ($saved as $s) {
            inbox_save_message($db, $prefx, $sid, $channel, 'out', (string)$uid, $uname, '[photo:'.$s['url'].']', $sess->lead_id ?: null, false, $uid, $uname, '');
            $last_id = (int)$db->lastInsertId();
            $previews[] = $s['url'];
        }
        $public_base = 'https://www.sauto.md';
        $send_log = $_SERVER['DOCUMENT_ROOT'] . '/logs/crm_send.log';

        if ($channel === 'telegram') {
            $tg = inbox_get_tg_settings($db, $prefx);
            $bot_token = ($sess->page_id === 'order_telegram')
                ? ($tg['order_telegram_chat_bot_token'] ?? $tg['order_telegram_bot_token'] ?? '')
                : ($tg['regular_telegram_chat_bot_token'] ?? $tg['regular_telegram_bot_token'] ?? '');
            // Telegram: caption attached to photo natively (appears above photos in chat)
            if (count($saved) === 1) {
                $payload = ['chat_id' => $sess->sender_id, 'photo' => $public_base . $saved[0]['url']];
                if ($caption !== '') $payload['caption'] = $caption;
                $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendPhoto");
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_TIMEOUT=>30,
                    CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS=>json_encode($payload)]);
                $resp = curl_exec($ch); curl_close($ch);
            } else {
                $media = array_map(fn($s) => ['type'=>'photo','media'=>$public_base.$s['url']], $saved);
                if ($caption !== '') $media[0]['caption'] = $caption;
                $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendMediaGroup");
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_TIMEOUT=>30,
                    CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS=>json_encode(['chat_id'=>$sess->sender_id,'media'=>$media])]);
                $resp = curl_exec($ch); curl_close($ch);
            }
            file_put_contents($send_log, date('Y-m-d H:i:s')." TG_PHOTO chat={$sess->sender_id} count=".count($saved)." resp=".substr($resp??'',0,120)."\n", FILE_APPEND|LOCK_EX);
        } elseif ($channel === 'facebook') {
            $fb = inbox_get_fb_settings($db, $prefx);
            $page_token = '';
            foreach ($fb as $k => $v) {
                if (strpos($k,'facebook_page_id') !== false && $v === $sess->page_id) {
                    $page_token = $fb[str_replace('_page_id','_token',$k)] ?? ''; break;
                }
            }
            if (!$page_token) foreach ($fb as $k => $v) {
                if (strpos($k,'facebook_token') !== false && $v) { $page_token = $v; break; }
            }
            // Text first, then photos
            if ($caption !== '') inbox_send_fb_message($sess->sender_id, $caption, $page_token);
            foreach ($saved as $s) inbox_send_fb_photo($sess->sender_id, $public_base.$s['url'], $page_token);
            file_put_contents($send_log, date('Y-m-d H:i:s')." FB_PHOTO sender={$sess->sender_id} count=".count($saved)." token_len=".strlen($page_token)."\n", FILE_APPEND|LOCK_EX);
        } elseif ($channel === 'instagram') {
            $fb = inbox_get_fb_settings($db, $prefx);
            $page_token = ''; $ig_acct_id = $sess->page_id;
            foreach ($fb as $k => $v) {
                if (preg_match('/^instagram_page_id/',$k) && $v === $ig_acct_id) {
                    $suffix = preg_replace('/^instagram_page_id/','',$k);
                    if (!empty($fb['instagram_token'.$suffix])) { $page_token = $fb['instagram_token'.$suffix]; break; }
                }
            }
            if (!$page_token) foreach ($fb as $k => $v) {
                if (preg_match('/^instagram_token/',$k) && $v) { $page_token = $v; break; }
            }
            // Text first, then photos
            if ($caption !== '') inbox_send_instagram_message($ig_acct_id, $sess->sender_id, $caption, $page_token);
            foreach ($saved as $s) inbox_send_instagram_photo($ig_acct_id, $sess->sender_id, $public_base.$s['url'], $page_token);
            file_put_contents($send_log, date('Y-m-d H:i:s')." IG_PHOTO sender={$sess->sender_id} count=".count($saved)." token_len=".strlen($page_token)."\n", FILE_APPEND|LOCK_EX);
        } elseif ($channel === 'viber') {
            $viber_token = crm_get_setting($db, $prefx, 'viber_bot_token', '');
            // Text first, then photos
            if ($caption !== '') inbox_send_viber_message($sess->sender_id, $caption, $viber_token);
            foreach ($saved as $s) inbox_send_viber_photo($sess->sender_id, $public_base.$s['url'], $viber_token);
            file_put_contents($send_log, date('Y-m-d H:i:s')." VIBER_PHOTO receiver={$sess->sender_id} count=".count($saved)." token_len=".strlen($viber_token)."\n", FILE_APPEND|LOCK_EX);
        }

        // Caption already sent by PHP above for all channels — tell JS not to send it again
        echo json_encode(['ok'=>true, 'previews'=>$previews, 'last_id'=>$last_id, 'caption_sent'=>($caption !== '')]);
        exit;

    case 'inbox_send':
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/crm_send.log',
            date('Y-m-d H:i:s')." ENTER inbox_send POST=".json_encode($_POST)."\n",
            FILE_APPEND|LOCK_EX);
        require_once(_ADM_INCL.'/crm/crm_inbox_core.php');
        $sid      = (int)($_POST['sid'] ?? 0);
        $body     = trim($_POST['body'] ?? '');
        $is_int   = (int)($_POST['is_internal'] ?? 0);
        if (!$sid || !$body) { echo json_encode(['ok'=>false,'msg'=>'Date lipsă']); exit; }

        $sess_stmt = $db->prepare("SELECT * FROM {$prefx}_crm_inbox_sessions WHERE id=:id LIMIT 1");
        $sess_stmt->execute([':id'=>$sid]);
        $sess = $sess_stmt->fetchObject();
        if (!$sess) { echo json_encode(['ok'=>false,'msg'=>'Sesiune negăsită']); exit; }

        // Block manager from sending to client when AI is active (internal notes are always allowed)
        if (!$is_int && $sess->ai_active) {
            echo json_encode(['ok'=>false,'msg'=>'AI este activ. Apasă "Preia" pentru a prelua conversația.']);
            exit;
        }

        inbox_save_message($db, $prefx, $sid, $sess->channel,
            $is_int ? 'internal' : 'out',
            (string)$uid, $uname, $body,
            $sess->lead_id ?: null, false, $uid, $uname, '');
        $new_msg_id = (int)$db->lastInsertId();

        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/logs/crm_send.log',
            date('Y-m-d H:i:s')." inbox_send sid=$sid is_int=$is_int channel={$sess->channel} ai_active={$sess->ai_active} msg_id=$new_msg_id\n",
            FILE_APPEND|LOCK_EX);

        // Send to channel if not internal
        if (!$is_int) {
            // 999md first — no need for fb_settings
            if ($sess->channel === '999md') {
                $acc_key = $sess->page_id ?: (($sess->department === 'order') ? 'order_999md' : 'sautohaus_999md');
                try {
                    require_once($_SERVER['DOCUMENT_ROOT'] . '/App/Services/Chat999Service.php');
                    $svc999  = new \App\Services\Chat999Service($db, $prefx, $acc_key);
                    $send_result = $svc999->sendMessage($sess->sender_id, $body);
                } catch (\Throwable $e) {
                    $log_root2 = !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname(__DIR__, 4);
                    file_put_contents($log_root2 . '/999_chat.log',
                        date('Y-m-d H:i:s') . " [admin_send ERROR] " . $e->getMessage() . "\n",
                        FILE_APPEND | LOCK_EX);
                }
            } else {
            $send_log = $_SERVER['DOCUMENT_ROOT'] . '/logs/crm_send.log';
            $fb_settings = inbox_get_fb_settings($db, $prefx);
            if ($sess->channel === 'facebook') {
                $page_token = '';
                foreach ($fb_settings as $k => $v) {
                    if (strpos($k, 'facebook_page_id') !== false && $v === $sess->page_id) {
                        $page_token = $fb_settings[str_replace('_page_id','_token',$k)] ?? '';
                        break;
                    }
                }
                if (!$page_token) {
                    foreach ($fb_settings as $k => $v) {
                        if (strpos($k, 'facebook_token') !== false && $v) { $page_token = $v; break; }
                    }
                }
                $ok = inbox_send_fb_message($sess->sender_id, $body, $page_token);
                file_put_contents($send_log, date('Y-m-d H:i:s')." FB sender={$sess->sender_id} token_len=".strlen($page_token)." ok=".($ok?'1':'0')."\n", FILE_APPEND|LOCK_EX);
            } elseif ($sess->channel === 'instagram') {
                $ig_token = '';
                foreach ($fb_settings as $k => $v) {
                    if (preg_match('/^instagram_page_id/', $k) && $v === $sess->page_id) {
                        $suffix   = preg_replace('/^instagram_page_id/', '', $k);
                        $ig_token = $fb_settings['instagram_token' . $suffix] ?? '';
                        break;
                    }
                }
                if (!$ig_token) {
                    foreach ($fb_settings as $k => $v) {
                        if (preg_match('/^instagram_token/', $k) && $v) { $ig_token = $v; break; }
                    }
                }
                $ig_sent = inbox_send_instagram_message($sess->page_id, $sess->sender_id, $body, $ig_token);
                file_put_contents($send_log, date('Y-m-d H:i:s')." IG page={$sess->page_id} sender={$sess->sender_id} token_len=".strlen($ig_token)." ok=".($ig_sent?'1':'0')."\n", FILE_APPEND|LOCK_EX);
            } elseif ($sess->channel === 'telegram') {
                $tg = inbox_get_tg_settings($db, $prefx);
                if ($sess->page_id === 'order_telegram') {
                    $bot_token = $tg['order_telegram_chat_bot_token'] ?? $tg['order_telegram_bot_token'] ?? '';
                } else {
                    $bot_token = $tg['regular_telegram_chat_bot_token'] ?? $tg['regular_telegram_bot_token'] ?? '';
                }
                $ok = inbox_send_tg_message($sess->sender_id, $body, $bot_token);
                file_put_contents($send_log, date('Y-m-d H:i:s')." TG chat={$sess->sender_id} page={$sess->page_id} token_len=".strlen($bot_token)." ok=".($ok?'1':'0')."\n", FILE_APPEND|LOCK_EX);
            } elseif ($sess->channel === 'viber') {
                $viber_token = crm_get_setting($db, $prefx, 'viber_bot_token', '');
                $ok = inbox_send_viber_message($sess->sender_id, $body, $viber_token);
                file_put_contents($send_log, date('Y-m-d H:i:s')." VIBER receiver={$sess->sender_id} token_len=".strlen($viber_token)." ok=".($ok?'1':'0')."\n", FILE_APPEND|LOCK_EX);
            }
            }
            // Mark session as active (manager took over)
            $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET status='active', ai_active=0, updated_at=NOW() WHERE id=:id")
               ->execute([':id'=>$sid]);
        }
        echo json_encode(['ok'=>true, 'id'=>$new_msg_id]);
        exit;

    // ── Inbox: toggle AI ───────────────────────────────────────────────────
    case 'inbox_toggle_ai':
        $sid   = (int)($_POST['sid'] ?? 0);
        $state = (int)($_POST['state'] ?? 0);
        if (!$sid) { echo json_encode(['ok'=>false]); exit; }
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET ai_active=:s, status=:st, updated_at=NOW() WHERE id=:id")
           ->execute([':s'=>$state, ':st'=>$state?'ai':'active', ':id'=>$sid]);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Inbox: close session ──────────────────────────────────────────────
    case 'inbox_close_session':
        $sid = (int)($_POST['sid'] ?? 0);
        if (!$sid) { echo json_encode(['ok'=>false]); exit; }
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET status='closed', ai_active=0, updated_at=NOW() WHERE id=:id")
           ->execute([':id'=>$sid]);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Inbox: reopen session ─────────────────────────────────────────────
    case 'inbox_reopen_session':
        $sid = (int)($_POST['sid'] ?? 0);
        if (!$sid) { echo json_encode(['ok'=>false]); exit; }
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET status='active', updated_at=NOW() WHERE id=:id")
           ->execute([':id'=>$sid]);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Inbox: create lead manually ────────────────────────────────────────
    case 'call_create_lead':
        require_once(_ADM_INCL.'/crm/crm_core.php');
        $call_id = (int)($_POST['call_id'] ?? 0);
        if (!$call_id) { echo json_encode(['ok'=>false,'msg'=>'ID apel invalid']); exit; }
        $call = $db->prepare("SELECT * FROM {$prefx}_crm_call_logs WHERE id=:id LIMIT 1");
        $call->execute([':id'=>$call_id]);
        $call = $call->fetchObject();
        if (!$call) { echo json_encode(['ok'=>false,'msg'=>'Apel negăsit']); exit; }
        if ($call->lead_id) { echo json_encode(['ok'=>true,'lead_id'=>(int)$call->lead_id,'exists'=>true]); exit; }

        $phone = crm_normalize_phone($call->phone);
        if (!$phone) { echo json_encode(['ok'=>false,'msg'=>'Telefon invalid']); exit; }

        // Source and dept from call
        $source_id = $call->source_id ? (int)$call->source_id : null;
        $dept = $call->department ?: 'stock';

        // Check existing lead by phone
        $existing = crm_find_lead_by_phone($db, $prefx, $phone);
        if ($existing) {
            $lid = (int)$existing->id;
            $db->prepare("UPDATE {$prefx}_crm_call_logs SET lead_id=:lid WHERE id=:id")->execute([':lid'=>$lid,':id'=>$call_id]);
            if ($source_id && !$existing->source_id) {
                $db->prepare("UPDATE {$prefx}_crm_leads SET source_id=:src WHERE id=:id")->execute([':src'=>$source_id,':id'=>$lid]);
            }
            echo json_encode(['ok'=>true,'lead_id'=>$lid,'exists'=>true]);
            exit;
        }

        // Owner = manager who answered the call; fallback to logged-in user
        $call_owner = $call->user_id ? (int)$call->user_id : $uid;
        $stmt = $db->prepare("INSERT INTO {$prefx}_crm_leads (department,status,phone,source_id,owner_id,origin,last_action_at) VALUES (:dept,'active',:phone,:src,:owner,'call',NOW())");
        $stmt->execute([':dept'=>$dept,':phone'=>$phone,':src'=>$source_id,':owner'=>$call_owner]);
        $new_lead_id = (int)$db->lastInsertId();
        if ($new_lead_id) {
            $db->prepare("UPDATE {$prefx}_crm_call_logs SET lead_id=:lid WHERE id=:id")->execute([':lid'=>$new_lead_id,':id'=>$call_id]);
            crm_audit($db, $prefx, 'lead_created_from_call', 'lead', $new_lead_id, '', 'active', $uid, $uname);
        }
        echo json_encode(['ok'=>true,'lead_id'=>$new_lead_id,'exists'=>false]);
        exit;

    case 'inbox_create_lead_manual':
        require_once(_ADM_INCL.'/crm/crm_inbox_core.php');
        $sid   = (int)($_POST['sid'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        if (!$sid || !$phone) { echo json_encode(['ok'=>false,'msg'=>'Date lipsă']); exit; }
        $sess_stmt = $db->prepare("SELECT * FROM {$prefx}_crm_inbox_sessions WHERE id=:id LIMIT 1");
        $sess_stmt->execute([':id'=>$sid]);
        $sess = $sess_stmt->fetchObject();
        if (!$sess) { echo json_encode(['ok'=>false,'msg'=>'Sesiune negăsită']); exit; }
        $lead_id = inbox_create_lead($db, $prefx, $sid, $phone, $sess->channel, $sess->department, null, null, $sess->page_id ?? '');
        if ($lead_id) {
            crm_audit($db, $prefx, 'lead_created_manual', 'lead', $lead_id, '', '', $uid, $uname);
            $db->prepare("UPDATE {$prefx}_crm_leads SET origin='manual' WHERE id=:id")->execute([':id'=>$lead_id]);
        }
        echo json_encode(['ok'=>true, 'lead_id'=>$lead_id]);
        exit;

    // ── Inbox: poll new messages ───────────────────────────────────────────
    case 'inbox_poll':
        $sid     = (int)($_POST['sid'] ?? 0);
        $last_id = (int)($_POST['last_id'] ?? 0);
        if (!$sid) { echo json_encode(['ok'=>false]); exit; }
        $db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET unread_count=0 WHERE id=:sid AND unread_count>0")
           ->execute([':sid' => $sid]);
        $stmt = $db->prepare("SELECT * FROM {$prefx}_crm_inbox_messages WHERE session_id=:sid AND id>:lid ORDER BY created_at ASC");
        $stmt->execute([':sid'=>$sid, ':lid'=>$last_id]);
        $new = $stmt->fetchAll(PDO::FETCH_OBJ);
        $out = [];
        foreach ($new as $m) {
            $out[] = ['id'=>(int)$m->id,'body'=>$m->body,'direction'=>$m->direction,'is_internal'=>($m->direction === 'internal' ? 1 : 0),'sender_name'=>$m->sender_name,'author_id'=>$m->author_id,'created_at'=>$m->created_at,'channel'=>$m->channel,'advert_title'=>$m->advert_title,'advert_url'=>$m->advert_url];
        }
        echo json_encode(['ok'=>true,'new_messages'=>$out]);
        exit;

    // ── Poll inbox for new/unread sessions ────────────────────────────────
    case 'poll_inbox':
        $stmt = $db->prepare("SELECT COALESCE(MAX(id),0) FROM {$prefx}_crm_inbox_messages WHERE direction='in'");
        $stmt->execute();
        $last_msg_id = (int)$stmt->fetchColumn();
        $stmt2 = $db->prepare("SELECT COALESCE(SUM(unread_count),0) FROM {$prefx}_crm_inbox_sessions WHERE status<>'closed'");
        $stmt2->execute();
        $total_unread = (int)$stmt2->fetchColumn();
        echo json_encode(['ok'=>true,'last_msg_id'=>$last_msg_id,'total_unread'=>$total_unread]);
        exit;

    // ── Poll new leads (for real-time badge update) ────────────────────────
    case 'poll_leads':
        $dept     = in_array($_POST['dept'] ?? '', ['stock','order']) ? $_POST['dept'] : 'stock';
        $since    = (int)($_POST['since'] ?? 0);
        $since_dt = $since ? date('Y-m-d H:i:s', $since) : date('Y-m-d H:i:s', time() - 60);

        $where = "l.department = :dept AND l.created_at > :since AND l.status NOT IN ('junk','closed')";
        $params = [':dept' => $dept, ':since' => $since_dt];
        if (!crm_can_see_all($user_role, $user_id)) {
            $where .= " AND (l.owner_id = :uid OR (l.status = 'unprocessed' AND l.owner_id IS NULL))";
            $params[':uid'] = $uid;
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE $where");
        $stmt->execute($params);
        $new_count = (int)$stmt->fetchColumn();

        echo json_encode(['ok' => true, 'new_count' => $new_count, 'ts' => time()]);
        exit;

    // ── Poll active call events (for incoming call popup) ──────────────────
    case 'poll_events':
        $since_ts = (int)($_POST['since'] ?? 0);
        $since_dt = $since_ts ? date('Y-m-d H:i:s', $since_ts) : date('Y-m-d H:i:s', time() - 10);

        $stmt = $db->prepare("SELECT * FROM {$prefx}_crm_call_events
            WHERE created_at > :since AND event_type IN ('INCOMING','ACCEPTED','COMPLETED','CANCELLED')
            ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([':since' => $since_dt]);
        $events = $stmt->fetchAll(PDO::FETCH_OBJ);

        $out = [];
        foreach ($events as $ev) {
            $lead_url = '';
            if ($ev->lead_id) {
                $lang_url = $_COOKIE['lang'] ?? 'ro';
                $lead_url = "/$lang_url/adminsauto/crm/lead?id={$ev->lead_id}";
            }
            $out[] = [
                'callid'     => $ev->callid,
                'type'       => $ev->event_type,
                'phone'      => crm_format_phone($ev->phone),
                'pbx_user'   => $ev->pbx_user,
                'lead_id'    => $ev->lead_id,
                'lead_url'   => $lead_url,
                'ts'         => strtotime($ev->created_at),
            ];
        }

        echo json_encode(['ok' => true, 'events' => $out, 'ts' => time()]);
        exit;

    // ── Phones: save (insert or update) ───────────────────────────────────────
    case 'phones_save':
        if (!crm_can_settings($user_role, $user_id)) { echo json_encode(['ok'=>false,'msg'=>'Acces restricționat']); exit; }

        $ph_id       = (int)($_POST['id'] ?? 0);
        $ph_number   = preg_replace('/\D/', '', trim($_POST['number'] ?? ''));
        $ph_label    = trim($_POST['label']       ?? '');
        $ph_type     = in_array($_POST['type'] ?? '', ['physical','virtual']) ? $_POST['type'] : 'physical';
        $ph_purpose  = trim($_POST['purpose']     ?? '');
        $ph_assigned = trim($_POST['assigned_to'] ?? '');
        $ph_ext      = trim($_POST['pbx_ext']     ?? '');
        $ph_source   = (int)($_POST['source_id']  ?? 0) ?: null;
        $ph_sort     = (int)($_POST['sort_order'] ?? 0);
        $ph_notes    = trim($_POST['notes']        ?? '');
        $ph_active   = (int)($_POST['active']      ?? 1);

        if (!$ph_number) { echo json_encode(['ok'=>false,'msg'=>'Număr lipsă']); exit; }

        // Normalize to 373... if Moldovan local format
        if (strlen($ph_number) === 8)                               $ph_number = '373' . $ph_number;
        elseif (strlen($ph_number) === 9 && $ph_number[0] === '0') $ph_number = '373' . substr($ph_number, 1);

        if ($ph_id > 0) {
            $stmt = $db->prepare("UPDATE {$prefx}_crm_phones SET
                number=:num, label=:lbl, type=:tp, purpose=:pur, assigned_to=:asgn,
                pbx_ext=:ext, source_id=:src, sort_order=:srt, notes=:nts, active=:act,
                updated_at=NOW()
                WHERE id=:id");
            $stmt->execute([
                ':num'=>$ph_number, ':lbl'=>$ph_label, ':tp'=>$ph_type, ':pur'=>$ph_purpose,
                ':asgn'=>$ph_assigned, ':ext'=>$ph_ext, ':src'=>$ph_source, ':srt'=>$ph_sort,
                ':nts'=>$ph_notes, ':act'=>$ph_active, ':id'=>$ph_id
            ]);
            crm_audit($db, $prefx, 'phones_update', 'phone', $ph_id, '', $ph_number, $uid, $uname);
        } else {
            $stmt = $db->prepare("INSERT INTO {$prefx}_crm_phones
                (number, label, type, purpose, assigned_to, pbx_ext, source_id, sort_order, notes, active)
                VALUES (:num,:lbl,:tp,:pur,:asgn,:ext,:src,:srt,:nts,:act)");
            $stmt->execute([
                ':num'=>$ph_number, ':lbl'=>$ph_label, ':tp'=>$ph_type, ':pur'=>$ph_purpose,
                ':asgn'=>$ph_assigned, ':ext'=>$ph_ext, ':src'=>$ph_source, ':srt'=>$ph_sort,
                ':nts'=>$ph_notes, ':act'=>$ph_active
            ]);
            crm_audit($db, $prefx, 'phones_add', 'phone', (int)$db->lastInsertId(), '', $ph_number, $uid, $uname);
        }
        echo json_encode(['ok'=>true]);
        exit;

    // ── Phones: delete ─────────────────────────────────────────────────────────
    case 'phones_delete':
        if (!crm_can_settings($user_role, $user_id)) { echo json_encode(['ok'=>false,'msg'=>'Acces restricționat']); exit; }
        $ph_id = (int)($_POST['id'] ?? 0);
        if (!$ph_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $db->prepare("DELETE FROM {$prefx}_crm_phones WHERE id=:id")->execute([':id'=>$ph_id]);
        crm_audit($db, $prefx, 'phones_delete', 'phone', $ph_id, '', '', $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    // ── Register Viber webhook ──────────────────────────────────────────────
    case 'viber_set_webhook':
        $token = trim($_POST['token'] ?? '');
        if (!$token) { echo json_encode(['ok'=>false,'msg'=>'Token missing']); exit; }

        $webhook_url = 'https://www.sauto.md/viber_webhook.php';
        $payload = json_encode([
            'url'          => $webhook_url,
            'event_types'  => ['message', 'conversation_started'],
            'send_name'    => true,
            'send_photo'   => false,
        ]);

        $ch = curl_init('https://chatapi.viber.com/pa/set_webhook');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Viber-Auth-Token: ' . $token,
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($resp, true);
        if ($code === 200 && isset($data['status']) && $data['status'] === 0) {
            crm_set_setting($db, $prefx, 'viber_bot_token', $token);
            echo json_encode(['ok'=>true, 'msg'=>'Webhook registered successfully']);
        } else {
            $err = 'HTTP '.$code.' status='.($data['status'] ?? '?').' msg='.($data['status_message'] ?? 'empty').' raw='.substr($resp,0,200);
            echo json_encode(['ok'=>false, 'msg'=>$err]);
        }
        exit;

    case 'add_task':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $body = trim($_POST['body'] ?? '');
        if (!$body) { echo json_encode(['ok'=>false,'msg'=>'Sarcina e goală']); exit; }
        $db->prepare("INSERT INTO {$prefx}_crm_tasks (lead_id,author_id,author_name,body) VALUES (:lid,:aid,:anm,:body)")
           ->execute([':lid'=>$lead_id,':aid'=>$uid,':anm'=>$uname,':body'=>$body]);
        $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")->execute([':id'=>$lead_id]);
        echo json_encode(['ok'=>true]);
        exit;

    case 'task_done':
        $task_id = (int)($_POST['task_id'] ?? 0);
        if (!$task_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $db->prepare("UPDATE {$prefx}_crm_tasks SET is_done=1, done_at=NOW() WHERE id=:id")->execute([':id'=>$task_id]);
        echo json_encode(['ok'=>true]);
        exit;

    case 'task_undone':
        $task_id = (int)($_POST['task_id'] ?? 0);
        if (!$task_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $db->prepare("UPDATE {$prefx}_crm_tasks SET is_done=0, done_at=NULL WHERE id=:id")->execute([':id'=>$task_id]);
        echo json_encode(['ok'=>true]);
        exit;

    case 'car_search':
        $q = trim($_POST['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode(['ok'=>true,'items'=>[]]); exit; }
        $like = '%'.$q.'%';
        $dept = in_array($_POST['dept'] ?? '', ['stock','order']) ? $_POST['dept'] : 'stock';
        $catalog_type = ($dept === 'order') ? 'on_order' : 'in_stock';
        $stmt = $db->prepare("SELECT id, br_nm, mo_nm, yr, prc, cur, p_path, catalog_type FROM {$prefx}_car_ctlg WHERE act=1 AND vis=1 AND n_a=0 AND is_at_client=0 AND catalog_type=:ct AND CONCAT(br_nm,' ',mo_nm,' ',COALESCE(yr,'')) LIKE :q ORDER BY br_nm, mo_nm LIMIT 20");
        $stmt->execute([':ct'=>$catalog_type, ':q'=>$like]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = [];
        foreach ($rows as $r) {
            $photo = null;
            $phStmt = $db->prepare("SELECT name, ff FROM {$prefx}_car_pht WHERE it_id=:id AND main=1 LIMIT 1");
            $phStmt->execute([':id'=>$r['id']]);
            $ph = $phStmt->fetch(PDO::FETCH_ASSOC);
            if ($ph) {
                $photo = '/media/images/upload/car/'.($r['p_path']??'').'/'.$r['id'].'/med/'.$ph['name'].'.'.$ph['ff'];
            }
            $items[] = [
                'id'          => $r['id'],
                'label'       => trim($r['br_nm'].' '.$r['mo_nm'].' '.($r['yr']??'')),
                'price'       => $r['prc'],
                'cur'         => $r['cur'],
                'photo'       => $photo,
                'stock'       => $r['catalog_type'] === 'in_stock',
            ];
        }
        echo json_encode(['ok'=>true,'items'=>$items]);
        exit;

    case 'save_car':
        if (!$lead_id) { echo json_encode(['ok'=>false,'msg'=>'ID invalid']); exit; }
        $car_id       = $_POST['car_id'] ? (int)$_POST['car_id'] : null;
        $car_label    = trim($_POST['car_label'] ?? '');
        $car_price    = $_POST['car_price'] !== '' ? (float)$_POST['car_price'] : null;
        $car_currency = trim($_POST['car_currency'] ?? '');
        $stmt = $db->prepare("UPDATE {$prefx}_crm_leads SET car_id=:car_id, car_label=:car_label, car_price=:car_price, car_currency=:car_currency WHERE id=:id");
        $stmt->execute([
            ':car_id'       => $car_id,
            ':car_label'    => $car_label ?: null,
            ':car_price'    => $car_price,
            ':car_currency' => $car_currency ?: null,
            ':id'           => $lead_id,
        ]);
        crm_audit($db, $prefx, 'save_car', 'lead', $lead_id, '', $car_label, $uid, $uname);
        echo json_encode(['ok'=>true]);
        exit;

    case 'get_notifications':
        $rows = $db->prepare("SELECT id, type, message, lead_id, is_read, created_at FROM {$prefx}_crm_notifications WHERE user_id=:uid ORDER BY created_at DESC LIMIT 30");
        $rows->execute([':uid'=>$uid]);
        $notifs = $rows->fetchAll(PDO::FETCH_OBJ);
        $unread = array_reduce($notifs, fn($c,$n) => $c + ($n->is_read ? 0 : 1), 0);
        echo json_encode(['ok'=>true,'unread'=>$unread,'items'=>$notifs]);
        exit;

    case 'mark_notifications_read':
        $db->prepare("UPDATE {$prefx}_crm_notifications SET is_read=1 WHERE user_id=:uid AND is_read=0")->execute([':uid'=>$uid]);
        echo json_encode(['ok'=>true]);
        exit;

    default:
        echo json_encode(['ok'=>false,'msg'=>'Funcție necunoscută: '.$fn]);
}
