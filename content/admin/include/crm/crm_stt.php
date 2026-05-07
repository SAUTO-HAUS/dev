<?php defined('_DOIT') or die('Restricted access');

function stt_is_enabled(PDO $db, string $prefx): bool {
    return (int)crm_get_setting($db, $prefx, 'stt_enabled', 0) === 1;
}

/**
 * Transcrie un apel după call_log id.
 * Returnează textul transcrierii sau '' la eroare.
 */
function stt_transcribe_call(PDO $db, string $prefx, int $call_log_id): string {
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    if (!$api_key) return '';

    // Fetch call log
    $stmt = $db->prepare("SELECT * FROM {$prefx}_crm_call_logs WHERE id=:id LIMIT 1");
    $stmt->execute([':id' => $call_log_id]);
    $call = $stmt->fetchObject();

    if (!$call || empty($call->recording_url)) return '';
    if ($call->stt_status === 'done' && !empty($call->transcript)) return $call->transcript;

    $min_dur = (int)crm_get_setting($db, $prefx, 'stt_min_dur', 30);
    if ((int)$call->duration < $min_dur) return '';

    $language = crm_get_setting($db, $prefx, 'stt_language', 'auto');

    // Mark as pending
    $db->prepare("UPDATE {$prefx}_crm_call_logs SET stt_status='pending' WHERE id=:id")
       ->execute([':id' => $call_log_id]);

    // Download audio to temp file
    $tmp = sys_get_temp_dir() . '/crm_audio_' . $call_log_id . '_' . time() . '.mp3';
    $downloaded = stt_download_audio($call->recording_url, $tmp);

    if (!$downloaded || !file_exists($tmp) || filesize($tmp) < 1000) {
        $db->prepare("UPDATE {$prefx}_crm_call_logs SET stt_status='error' WHERE id=:id")
           ->execute([':id' => $call_log_id]);
        @unlink($tmp);
        return '';
    }

    // Send to Whisper API
    $transcript = stt_whisper_api($tmp, $api_key, $language === 'auto' ? null : $language);
    @unlink($tmp);

    if (empty($transcript)) {
        $db->prepare("UPDATE {$prefx}_crm_call_logs SET stt_status='error' WHERE id=:id")
           ->execute([':id' => $call_log_id]);
        return '';
    }

    // Save transcript to call_log
    $db->prepare("UPDATE {$prefx}_crm_call_logs SET transcript=:t, stt_status='done' WHERE id=:id")
       ->execute([':t' => $transcript, ':id' => $call_log_id]);

    // Save to lead (first 500 chars for marquee in list view)
    if ($call->lead_id) {
        $existing = $db->prepare("SELECT transcript FROM {$prefx}_crm_leads WHERE id=:id LIMIT 1");
        $existing->execute([':id' => $call->lead_id]);
        $lead_transcript = $existing->fetchColumn();

        // Only update if empty or shorter
        if (empty($lead_transcript)) {
            $db->prepare("UPDATE {$prefx}_crm_leads SET transcript=:t WHERE id=:id")
               ->execute([':t' => mb_substr($transcript, 0, 1000), ':id' => $call->lead_id]);
        }
    }

    return $transcript;
}

/**
 * Download audio file from URL to local temp path.
 * Supports HTTP Basic Auth via URL credentials (PBX may require token in URL).
 */
function stt_download_audio(string $url, string $dest): bool {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'SautoCRM/1.0',
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || empty($data)) return false;
    return file_put_contents($dest, $data) !== false;
}

/**
 * Send audio file to OpenAI Whisper API.
 * Returns transcript text or empty string on error.
 */
function stt_whisper_api(string $file_path, string $api_key, ?string $language = null): string {
    if (!file_exists($file_path)) return '';

    $post_fields = [
        'file'  => new CURLFile($file_path, 'audio/mpeg', basename($file_path)),
        'model' => 'whisper-1',
    ];
    if ($language) {
        $post_fields['language'] = $language;
    }
    // Prompt helps Whisper understand automotive context in Romanian/Russian
    $post_fields['prompt'] = 'Sauto-Haus, dealer auto Moldova. Conversație despre mașini, prețuri, comenzi.';

    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post_fields,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $api_key,
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return '';
    $data = json_decode($resp, true);
    return trim($data['text'] ?? '');
}

/**
 * Auto-transcribe after webhook — called from pbx_webhook_handler.php
 * Only if STT enabled and recording_url present and duration >= min_dur.
 */
function stt_maybe_transcribe(PDO $db, string $prefx, int $call_log_id, int $duration, ?string $recording_url): void {
    if (!$recording_url) return;
    if (!stt_is_enabled($db, $prefx)) return;

    $min_dur = (int)crm_get_setting($db, $prefx, 'stt_min_dur', 30);
    if ($duration < $min_dur) return;

    // Run in background using ignore_user_abort to not block webhook response
    ignore_user_abort(true);
    stt_transcribe_call($db, $prefx, $call_log_id);
}
