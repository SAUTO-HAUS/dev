<?php
/**
 * Async photo dispatch worker
 * Called internally by ajax.php — NOT meant to be accessed directly from internet.
 */

$secret = $_POST['_secret'] ?? '';
$job_file = $_POST['_job'] ?? '';

if (!$secret || !$job_file) exit;

// Validate job file path is inside our tmp dir
$tmp_dir = __DIR__ . '/tmp/crm_jobs/';
$job_path = realpath($job_file);
if (!$job_path || strpos($job_path, realpath($tmp_dir)) !== 0) exit;

$job = json_decode(file_get_contents($job_path), true);
if (!$job || ($job['secret'] ?? '') !== $secret) exit;

// Delete job file immediately
@unlink($job_path);

define('_DOIT', 1);
require_once __DIR__ . '/environment.php';
require_once __DIR__ . '/content/default/defines.php';
require_once __DIR__ . '/content/default/functions.php';
require_once __DIR__ . '/content/default/config.php';
require_once __DIR__ . '/content/default/dbi.php';
require_once __DIR__ . '/content/admin/include/crm/crm_core.php';
require_once __DIR__ . '/content/admin/include/crm/crm_inbox_core.php';

http_response_code(200);
echo 'ok';
if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();

$channel      = $job['channel'];
$saved        = $job['saved'];
$sender_id    = $job['sender_id'];
$page_id      = $job['page_id'];
$caption      = $job['caption'] ?? '';
$public_base  = 'https://www.sauto.md';

if ($channel === 'telegram') {
    $tg = inbox_get_tg_settings($db, $prefx);
    $bot_token = ($page_id === 'order_telegram')
        ? ($tg['order_telegram_chat_bot_token'] ?? $tg['order_telegram_bot_token'] ?? '')
        : ($tg['regular_telegram_chat_bot_token'] ?? $tg['regular_telegram_bot_token'] ?? '');
    if (count($saved) === 1) {
        $payload = ['chat_id'=>$sender_id, 'photo'=>$public_base.$saved[0]['url']];
        if ($caption !== '') $payload['caption'] = $caption;
        $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendPhoto");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_TIMEOUT=>30,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode($payload)]);
        curl_exec($ch); curl_close($ch);
    } else {
        $media = array_map(fn($s) => ['type'=>'photo','media'=>$public_base.$s['url']], $saved);
        if ($caption !== '') $media[0]['caption'] = $caption;
        $ch = curl_init("https://api.telegram.org/bot{$bot_token}/sendMediaGroup");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_TIMEOUT=>30,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_POSTFIELDS=>json_encode(['chat_id'=>$sender_id,'media'=>$media])]);
        curl_exec($ch); curl_close($ch);
    }
} elseif ($channel === 'facebook') {
    $fb = inbox_get_fb_settings($db, $prefx);
    $page_token = '';
    foreach ($fb as $key => $val) {
        if (strpos($key, 'facebook_page_id') !== false && $val === $page_id) {
            $page_token = $fb[str_replace('_page_id','_token',$key)] ?? ''; break;
        }
    }
    if (!$page_token) foreach ($fb as $key => $val) {
        if (strpos($key,'facebook_token') !== false && $val) { $page_token = $val; break; }
    }
    foreach ($saved as $s) inbox_send_fb_photo($sender_id, $public_base.$s['url'], $page_token);
} elseif ($channel === 'instagram') {
    $fb = inbox_get_fb_settings($db, $prefx);
    $page_token = ''; $ig_acct_id = $page_id;
    foreach ($fb as $key => $val) {
        if (preg_match('/^instagram_page_id/', $key) && $val === $ig_acct_id) {
            $suffix = preg_replace('/^instagram_page_id/','',$key);
            if (!empty($fb['instagram_token'.$suffix])) { $page_token = $fb['instagram_token'.$suffix]; break; }
        }
    }
    if (!$page_token) foreach ($fb as $key => $val) {
        if (preg_match('/^instagram_token/',$key) && $val) { $page_token = $val; break; }
    }
    foreach ($saved as $s) inbox_send_instagram_photo($ig_acct_id, $sender_id, $public_base.$s['url'], $page_token);
} elseif ($channel === 'viber') {
    $bot_token = crm_get_setting($db, $prefx, 'viber_bot_token', '');
    foreach ($saved as $s) inbox_send_viber_photo($sender_id, $public_base.$s['url'], $bot_token);
}
