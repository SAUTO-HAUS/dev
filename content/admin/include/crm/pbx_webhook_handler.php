<?php
/**
 * PBX Moldcell Webhook Handler — PDO version
 */
defined('_PBX_HOOK') or die('Restricted access');

require_once(__DIR__.'/crm_core.php');
require_once(__DIR__.'/crm_stt.php');

function pbx_handle_history(array $payload, PDO $db, string $prefx): void {
    $pbx_call_id   = $payload['callid']      ?? $payload['id']         ?? $payload['call_id'] ?? '';
    $direction     = strtolower($payload['direction'] ?? $payload['type'] ?? 'in');
    $type          = $direction === 'out' ? 'out' : 'in';
    $phone_raw     = $payload['phone']       ?? $payload['client']     ?? $payload['src'] ?? '';
    $diversion     = $payload['diversion']   ?? $payload['via']        ?? $payload['dst'] ?? '';
    $telnum        = $payload['telnum']      ?? '';
    $telnum_name   = $payload['telnum_name'] ?? $payload['groupRealName'] ?? '';
    $pbx_user      = $payload['user']        ?? $payload['agent']      ?? '';
    $duration      = (int)($payload['duration'] ?? $payload['billsec'] ?? 0);
    $wait          = (int)($payload['wait']     ?? $payload['wait_sec']?? 0);
    $status_raw    = $payload['status']      ?? $payload['disposition'] ?? '';
    $recording_url = $payload['link']        ?? $payload['record']     ?? $payload['recording'] ?? $payload['record_url'] ?? null;
    $missed_status = isset($payload['missedStatus']) ? (int)$payload['missedStatus'] : (isset($payload['missed_status']) ? (int)$payload['missed_status'] : null);
    $rating        = isset($payload['rating']) ? (int)$payload['rating'] : null;
    $start_raw     = $payload['start']       ?? $payload['calldate']   ?? date('Y-m-d H:i:s');

    if (!$pbx_call_id || !$phone_raw) return;

    $phone     = crm_normalize_phone($phone_raw);
    $start_at  = date('Y-m-d H:i:s', is_numeric($start_raw) ? (int)$start_raw : strtotime($start_raw));
    $raw_json  = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $status_lc   = strtolower($status_raw);
    $is_answered = $duration > 0
        && $status_lc !== 'missed'
        && strpos($status_lc, 'no_answer') === false
        && strpos($status_lc, 'busy') === false
        && strpos($status_lc, 'notavailable') === false
        && strpos($status_lc, 'notallowed') === false
        && strpos($status_lc, 'notfound') === false
        && $status_lc !== 'cancel';

    // Determine granular call status for display
    if ($is_answered) {
        $call_status = 'answered';
    } elseif ($status_lc === 'cancel') {
        $call_status = 'cancel';
    } elseif (in_array($status_lc, ['busy', 'notavailable', 'notallowed', 'notfound'])) {
        $call_status = 'busy';
    } else {
        $call_status = 'missed';
    }

    // Identify source:
    // If diversion is internal (prefix 62, short ext) → skip it, try telnum
    // If telnum_name present → diversion is the real line number → use it
    // Otherwise try both
    $diversion_is_internal = crm_is_internal_diversion($diversion);
    if ($telnum_name && !$diversion_is_internal) {
        $source = crm_find_source($db, $prefx, $diversion);
    } elseif ($diversion_is_internal) {
        $source = $telnum ? crm_find_source($db, $prefx, $telnum) : null;
    } else {
        $source = crm_find_source($db, $prefx, $diversion);
        if (!$source && $telnum) $source = crm_find_source($db, $prefx, $telnum);
    }
    $source_id = $source ? (int)$source->id : null;

    // Find internal user
    $pbx_obj   = crm_find_user_by_pbx_login($db, $prefx, $pbx_user);
    $user_id   = $pbx_obj ? (int)$pbx_obj->id : null;

    // Dept: source wins if recognized, else groupRealName hint, else agent's department, else 'stock'
    $agent_dept = ($pbx_obj && !empty($pbx_obj->department)) ? $pbx_obj->department : null;
    $group_dept = '';
    if ($telnum_name) {
        $tln = strtolower($telnum_name);
        if (strpos($tln, 'order') !== false || strpos($tln, 'comanda') !== false || strpos($tln, 'команд') !== false) $group_dept = 'order';
        elseif (strpos($tln, 'stock') !== false || strpos($tln, 'stoc') !== false || strpos($tln, 'сток') !== false) $group_dept = 'stock';
    }
    $dept = $source ? $source->department : ($group_dept ?: ($agent_dept ?? 'stock'));

    $diversion_num   = crm_normalize_diversion($diversion ?: $telnum); // 8-digit for JOIN
    $diversion_label = $diversion ?: $telnum;
    if (!$source_id && $telnum_name) $diversion_label = $telnum_name;

    // ── Deduplicate: same phone+minute ────────────────────────────────
    $start_minute = substr($start_at, 0, 16); // 'Y-m-d H:i'
    $dup = $db->prepare("SELECT id, pbx_call_id, status, duration, source_id FROM {$prefx}_crm_call_logs WHERE phone=:ph AND DATE_FORMAT(start_at,'%Y-%m-%d %H:%i')=:sm LIMIT 1");
    $dup->execute([':ph'=>$phone, ':sm'=>$start_minute]);
    $existing_log = $dup->fetchObject();
    if ($existing_log) {
        $existing_id = (int)$existing_log->id;
        $existing_answered = $existing_log->status === 'answered';
        $current_answered  = $call_status === 'answered';

        // Patch source_id: only if current has telnum_name (reliable) or existing has no source at all
        $existing_has_reliable_source = $existing_log->source_id && !$telnum_name;
        if ($source_id && !$existing_has_reliable_source) {
            $db->prepare("UPDATE {$prefx}_crm_call_logs SET source_id=:src, diversion=:div, diversion_label=:dlbl WHERE id=:id")
               ->execute([':src'=>$source_id, ':div'=>$diversion_num, ':dlbl'=>$diversion_label, ':id'=>$existing_id]);
        }

        // Keep existing if it's better (answered wins, longer duration wins, source already patched above)
        if ($existing_answered && !$current_answered) return;
        if ($existing_answered && $current_answered && $duration <= (int)$existing_log->duration) return;
        if (!$existing_answered && !$current_answered && $duration <= (int)$existing_log->duration) return;

        // Current is better — delete old and insert new below
        // Preserve source_id from original if new event has none (e.g. redirected call loses original line)
        if (!$source_id && $existing_log->source_id) {
            $source_id = (int)$existing_log->source_id;
        }
        $db->prepare("DELETE FROM {$prefx}_crm_call_logs WHERE id=:id")->execute([':id'=>$existing_id]);
    }

    // ── 1. Write call log ──────────────────────────────────────────────
    $ins = $db->prepare("INSERT INTO {$prefx}_crm_call_logs
        (pbx_call_id,type,status,phone,diversion,diversion_label,source_id,pbx_user,user_id,department,start_at,duration,wait,recording_url,rating,missed_status,raw_payload)
        VALUES (:cid,:type,:status,:phone,:div,:dlbl,:src,:pu,:uid,:dept,:start,:dur,:wait,:rec,:rat,:ms,:raw)
        ON DUPLICATE KEY UPDATE
            duration=VALUES(duration), wait=VALUES(wait), status=VALUES(status),
            recording_url=VALUES(recording_url), rating=VALUES(rating), missed_status=VALUES(missed_status),
            source_id=COALESCE(source_id, VALUES(source_id)),
            diversion=COALESCE(NULLIF(diversion,''), VALUES(diversion)),
            diversion_label=COALESCE(NULLIF(diversion_label,''), VALUES(diversion_label))");

    $ins->execute([
        ':cid'   => $pbx_call_id,
        ':type'  => $type,
        ':status'=> $call_status,
        ':phone' => $phone,
        ':div'   => $diversion_num,
        ':dlbl'  => $diversion_label,
        ':src'   => $source_id,
        ':pu'    => $pbx_user,
        ':uid'   => $user_id,
        ':dept'  => $dept,
        ':start' => $start_at,
        ':dur'   => $duration,
        ':wait'  => $wait,
        ':rec'   => $recording_url,
        ':rat'   => $rating,
        ':ms'    => $missed_status,
        ':raw'   => $raw_json,
    ]);

    // Get call log id
    $call_log_id = (int)$db->lastInsertId();
    if (!$call_log_id) {
        $r = $db->prepare("SELECT id FROM {$prefx}_crm_call_logs WHERE pbx_call_id=:cid LIMIT 1");
        $r->execute([':cid'=>$pbx_call_id]);
        $call_log_id = (int)$r->fetchColumn();
    }

    // ── 2. Duration threshold — skip lead creation but still show in /calls ──
    $min_dur = (int)crm_get_setting($db, $prefx, 'call_min_duration', 10);
    if ($duration < $min_dur) {
        // Still link to existing lead if phone matches
        if ($call_log_id) {
            $existing = crm_find_lead_by_phone($db, $prefx, $phone);
            if ($existing) {
                $db->prepare("UPDATE {$prefx}_crm_call_logs SET lead_id=:lid WHERE id=:id")
                   ->execute([':lid'=>(int)$existing->id, ':id'=>$call_log_id]);
            }
        }
        return;
    }

    // ── 3. Find existing lead ──────────────────────────────────────────
    $existing = crm_find_lead_by_phone($db, $prefx, $phone);

    if ($existing) {
        $lid = (int)$existing->id;
        // Link call log
        $db->prepare("UPDATE {$prefx}_crm_call_logs SET lead_id=:lid WHERE id=:id")->execute([':lid'=>$lid,':id'=>$call_log_id]);
        // Patch source on lead if it has none and call has a known source
        if ($source_id && !$existing->source_id) {
            $db->prepare("UPDATE {$prefx}_crm_leads SET source_id=:src WHERE id=:id")
               ->execute([':src'=>$source_id, ':id'=>$lid]);
        }

        if ($type === 'out' && $is_answered && $user_id) {
            $upd = $db->prepare("UPDATE {$prefx}_crm_leads SET outbound_count=outbound_count+1, outbound_last_user=:u, last_action_at=NOW() WHERE id=:id");
            $upd->execute([':u'=>$user_id,':id'=>$lid]);

            $check = crm_get_lead($db, $prefx, $lid);
            $threshold = (int)crm_get_setting($db, $prefx, 'takeover_threshold', 2);
            if ($check && (int)$check->outbound_count >= $threshold
                && (int)$check->outbound_last_user === $user_id
                && (int)$check->owner_id !== $user_id) {
                $old_owner = $check->owner_id;
                $db->prepare("UPDATE {$prefx}_crm_leads SET owner_id=:u WHERE id=:id")->execute([':u'=>$user_id,':id'=>$lid]);
                crm_audit($db, $prefx, 'auto_takeover', 'lead', $lid, (string)$old_owner, (string)$user_id);
                // Notify old owner
                $new_name_r = $db->prepare("SELECT name FROM {$prefx}_adm_usr WHERE id=:id LIMIT 1");
                $new_name_r->execute([':id'=>$user_id]);
                $new_name = $new_name_r->fetchColumn() ?: 'alt manager';
                $lead_phone = $check->phone ?? '';
                crm_notify($db, $prefx, (int)$old_owner, 'notif_takeover', 'takeover', $lid, ['phone' => $lead_phone, 'name' => $new_name]);
            }

            if ($check && in_array($check->status, ['active','missed','unprocessed'])) {
                $db->prepare("UPDATE {$prefx}_crm_leads SET status='processed', last_action_at=NOW() WHERE id=:id")->execute([':id'=>$lid]);
                crm_audit($db, $prefx, 'auto_processed', 'lead', $lid, $check->status, 'processed');
            }
        } else {
            $db->prepare("UPDATE {$prefx}_crm_leads SET last_action_at=NOW() WHERE id=:id")->execute([':id'=>$lid]);
        }
        stt_maybe_transcribe($db, $prefx, $call_log_id, $duration, $recording_url);
        return;
    }

    // ── 4. Create new lead ─────────────────────────────────────────────
    $is_work = crm_is_work_hours($db, $prefx);

    if ($type === 'in') {
        $status   = $is_answered ? 'active' : ($is_work ? 'missed' : 'unprocessed');
        $owner_id = ($is_answered || $is_work) ? $user_id : null;
    } else {
        $status   = 'active';
        $owner_id = $user_id;
        // For outbound calls without a source, inherit source from the most recent inbound call from this number
        if (!$source_id) {
            $prev_src = $db->prepare("SELECT source_id FROM {$prefx}_crm_call_logs WHERE phone=:ph AND type='in' AND source_id IS NOT NULL ORDER BY start_at DESC LIMIT 1");
            $prev_src->execute([':ph' => $phone]);
            $inherited = $prev_src->fetchColumn();
            if ($inherited) $source_id = (int)$inherited;
        }
    }

    $new_stmt = $db->prepare("INSERT INTO {$prefx}_crm_leads (department,status,phone,source_id,owner_id,origin,last_action_at) VALUES (:dept,:status,:phone,:src,:owner,'call',NOW())");
    $new_stmt->execute([':dept'=>$dept,':status'=>$status,':phone'=>$phone,':src'=>$source_id,':owner'=>$owner_id]);
    $new_lead_id = (int)$db->lastInsertId();

    if ($new_lead_id) {
        $db->prepare("UPDATE {$prefx}_crm_call_logs SET lead_id=:lid WHERE id=:id")->execute([':lid'=>$new_lead_id,':id'=>$call_log_id]);
        crm_audit($db, $prefx, 'lead_created_from_call', 'lead', $new_lead_id, '', $status);
    }

    // Auto Speech-to-Text if enabled and recording available
    stt_maybe_transcribe($db, $prefx, $call_log_id, $duration, $recording_url);
}

function pbx_handle_rating(array $payload, PDO $db, string $prefx): void {
    $pbx_call_id = $payload['id'] ?? $payload['callid'] ?? '';
    $rating      = (int)($payload['rating'] ?? 0);
    if (!$pbx_call_id) return;
    $stmt = $db->prepare("UPDATE {$prefx}_crm_call_logs SET rating=:r WHERE pbx_call_id=:cid");
    $stmt->execute([':r'=>$rating,':cid'=>$pbx_call_id]);
}

/**
 * contact — PBX asks CRM: who is this caller? who is responsible?
 * Used for: caller name on IP phone screen + auto-routing to responsible agent.
 * Response: JSON { contact_name, responsible }
 */
function pbx_handle_contact(array $payload, PDO $db, string $prefx): void {
    $phone_raw = $payload['phone'] ?? '';
    if (!$phone_raw) {
        echo json_encode(['contact_name' => '', 'responsible' => '']);
        return;
    }

    $phone = crm_normalize_phone($phone_raw);
    $lead  = crm_find_lead_by_phone($db, $prefx, $phone);

    $contact_name = '';
    $responsible  = '';

    if ($lead) {
        $contact_name = $lead->client_name ?? '';
        if ($lead->owner_id) {
            $usr = $db->prepare("SELECT COALESCE(NULLIF(TRIM(pbx_login),''), login) AS pbx_lg FROM {$prefx}_adm_usr WHERE id=:id LIMIT 1");
            $usr->execute([':id' => $lead->owner_id]);
            $responsible = $usr->fetchColumn() ?: '';
        }
    }

    // Override the global response — contact returns immediately
    header('Content-Type: application/json');
    echo json_encode(['contact_name' => $contact_name, 'responsible' => $responsible]);
    exit;
}

/**
 * event — PBX notifies CRM of real-time call events.
 * Types: INCOMING, ACCEPTED, COMPLETED, CANCELLED, OUTGOING, TRANSFERRED
 * Stored in crm_call_events for polling by the frontend.
 */
function pbx_handle_event(array $payload, PDO $db, string $prefx): void {
    $event_type  = strtoupper($payload['type'] ?? '');
    $phone_raw   = $payload['phone'] ?? '';
    $pbx_user    = $payload['user'] ?? '';
    $callid      = $payload['callid'] ?? '';
    $diversion   = $payload['diversion'] ?? '';

    if (!$event_type || !$phone_raw || !$callid) return;

    $phone = crm_normalize_phone($phone_raw);
    $lead  = crm_find_lead_by_phone($db, $prefx, $phone);

    // Write event to call_events table (used by polling endpoint)
    $stmt = $db->prepare("INSERT INTO {$prefx}_crm_call_events
        (callid, event_type, phone, pbx_user, diversion, lead_id, created_at)
        VALUES (:cid, :evt, :phone, :usr, :div, :lid, NOW())
        ON DUPLICATE KEY UPDATE event_type=VALUES(event_type), created_at=NOW()");
    $stmt->execute([
        ':cid'   => $callid,
        ':evt'   => $event_type,
        ':phone' => $phone,
        ':usr'   => $pbx_user,
        ':div'   => preg_replace('/\D/', '', $diversion),
        ':lid'   => $lead ? (int)$lead->id : null,
    ]);
}
