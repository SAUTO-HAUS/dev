<?php
/**
 * POST /api/v1/gdpr/consent — records a visitor's cookie decision.
 *
 * Public endpoint, no auth (the banner runs before anyone logs in), so it is
 * rate limited. The limiter hashes the caller IP into a short-lived file
 * bucket; the IP itself is never written anywhere and never touches the
 * consent table — storing it would breach the very law this module serves.
 */
define('_DOIT', 1);
require_once __DIR__ . '/../../../environment.php';
require_once __DIR__ . '/../../../content/default/defines.php';
require_once __DIR__ . '/../../../content/default/functions.php';
require_once __DIR__ . '/../../../content/default/config.php';
require_once __DIR__ . '/../../../content/default/dbi.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

// --- rate limiting: max 3 writes per minute per client ---------------------

/**
 * Real client IP. The site sits behind Cloudflare, so REMOTE_ADDR is an edge
 * node — without CF-Connecting-IP every visitor would share one bucket.
 * Used only to derive the throttle key below.
 */
function gdpr_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) { return $ip; }
        }
    }
    return '0.0.0.0';
}

function gdpr_rate_limited(string $ip, int $max = 3, int $window = 60): bool {
    $dir = __DIR__ . '/../../../tmp/gdpr_rl';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    // Salted hash: the bucket file name cannot be reversed into an address.
    $key  = substr(hash('sha256', $ip . '|' . SQL_DB . '|gdpr_consent'), 0, 32);
    $file = $dir . '/' . $key;
    $now  = time();

    $hits = [];
    if (is_file($file)) {
        $raw  = @file_get_contents($file);
        $hits = $raw ? array_filter(array_map('intval', explode(',', $raw))) : [];
    }
    $hits = array_values(array_filter($hits, static fn($t) => ($now - $t) < $window));

    if (count($hits) >= $max) { return true; }

    $hits[] = $now;
    @file_put_contents($file, implode(',', $hits), LOCK_EX);

    // Opportunistic cleanup so the bucket directory cannot grow without bound.
    if (mt_rand(1, 200) === 1) {
        foreach ((array)glob($dir . '/*') as $f) {
            if (is_file($f) && ($now - (int)filemtime($f)) > 3600) { @unlink($f); }
        }
    }
    return false;
}

if (gdpr_rate_limited(gdpr_client_ip())) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'msg' => 'Too many requests']);
    exit;
}

// --- payload ---------------------------------------------------------------

$raw     = file_get_contents('php://input');
$payload = json_decode($raw ?: '[]', true);
if (!is_array($payload)) { $payload = $_POST; }

$consentId = (string)($payload['consent_id'] ?? '');
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $consentId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid consent_id']);
    exit;
}

$status = (string)($payload['global_status'] ?? '');
if (!in_array($status, ['accept_all', 'reject_all', 'custom_selection'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid global_status']);
    exit;
}

$trackers  = is_array($payload['trackers'] ?? null) ? $payload['trackers'] : [];
$analytics = !empty($trackers['analytics']) ? 1 : 0;
$marketing = !empty($trackers['marketing']) ? 1 : 0;

// A refusal must never be recorded with a tracker enabled, whatever the client
// claims — the admin UI treats this table as the authoritative proof.
if ($status === 'reject_all') { $analytics = 0; $marketing = 0; }
if ($status === 'accept_all') { $analytics = 1; $marketing = 1; }

$version = (string)($payload['policy_version'] ?? 'v1.0');
$version = preg_replace('/[^A-Za-z0-9._\-]/', '', $version);
$version = $version !== '' ? substr($version, 0, 16) : 'v1.0';

// --- write -----------------------------------------------------------------

try {
    $ins = $db->prepare('INSERT INTO '.$prefx.'_cookie_consent_logs
        (`consent_id`, `action_timestamp`, `global_status`, `tracker_analytics`, `tracker_marketing`, `policy_version`)
        VALUES (:cid, :ts, :st, :ana, :mkt, :ver)');
    $ins->execute([
        ':cid' => strtolower($consentId),
        ':ts'  => gmdate('Y-m-d H:i:s'),   // UTC, per spec
        ':st'  => $status,
        ':ana' => $analytics,
        ':mkt' => $marketing,
        ':ver' => $version,
    ]);
    echo json_encode(['ok' => true]);
} catch (\Throwable $e) {
    error_log('[gdpr-consent] '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Storage error']);
}

$db = null;
