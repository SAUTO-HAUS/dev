<?php defined('_DOIT') or die('Restricted access');

// CRM roles mapped from global RBAC
define('CRM_ROLE_ADMIN',     'gordon');
define('CRM_ROLE_SALES_MGR', 'admin');
define('CRM_ROLE_AGENT',     'publisher');
define('CRM_ROLE_LOGISTICS', 'publisher_limited');

// Lead statuses
define('CRM_STATUS_ACTIVE',      'active');
define('CRM_STATUS_MISSED',      'missed');
define('CRM_STATUS_UNPROCESSED', 'unprocessed');
define('CRM_STATUS_PROCESSED',   'processed');
define('CRM_STATUS_TRANSACTION', 'transaction');
define('CRM_STATUS_CLOSED',      'closed');
define('CRM_STATUS_JUNK',        'junk');

define('CRM_DEPT_STOCK', 'stock');
define('CRM_DEPT_ORDER', 'order');

// ---- RBAC ----

define('CRM_ALLOWED_USER_ID', 28);

$crm_allowed_roles = ['gordon', 'admin', 'publisher', 'publisher_limited'];

function crm_has_access($user_role, $user_id = null) {
    global $crm_allowed_roles;
    return in_array($user_role, $crm_allowed_roles, true);
}

function crm_can_see_all($user_role, $user_id = null) {
    return in_array($user_role, ['gordon', 'admin'], true);
}

function crm_can_settings($user_role, $user_id = null) {
    return $user_role === 'gordon';
}

function crm_can_analytics($user_role, $user_id = null) {
    return in_array($user_role, ['gordon', 'admin'], true);
}

function crm_can_junk($user_role, $user_id = null) {
    return in_array($user_role, ['gordon', 'admin'], true);
}

// ---- Settings ----

function crm_get_setting(PDO $db, string $prefx, string $key, $default = null) {
    $stmt = $db->prepare("SELECT v FROM {$prefx}_crm_settings WHERE k = :k LIMIT 1");
    $stmt->execute([':k' => $key]);
    $row = $stmt->fetch(PDO::FETCH_OBJ);
    return $row ? $row->v : $default;
}

function crm_set_setting(PDO $db, string $prefx, string $key, $value) {
    $stmt = $db->prepare("INSERT INTO {$prefx}_crm_settings (k, v) VALUES (:k, :v)
                          ON DUPLICATE KEY UPDATE v = :v2");
    $stmt->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
}

function crm_get_all_settings(PDO $db, string $prefx): array {
    $stmt = $db->query("SELECT k, v FROM {$prefx}_crm_settings");
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $row) {
        $out[$row->k] = $row->v;
    }
    return $out;
}

// ---- Phone normalization ----

function crm_normalize_phone(string $phone): string {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 8)                               return '373' . $phone;
    if (strlen($phone) === 9 && $phone[0] === '0')          return '373' . substr($phone, 1);
    return $phone;
}

// Normalize diversion number to 8-digit local format (e.g. "79600326")
// Accepts: "37379600326", "079600326", "79600326", "+37379600326", "079 600 326"
function crm_normalize_diversion(string $d): string {
    $d = preg_replace('/\D/', '', $d);
    if (strlen($d) === 11 && substr($d, 0, 3) === '373') return substr($d, 3); // 37379600326 → 79600326
    if (strlen($d) === 9  && $d[0] === '0')              return substr($d, 1); // 079600326   → 79600326
    return $d;
}

// Returns true if diversion is an internal PBX fixed line (prefix 62, 68500xxx etc.) — not a real source number
function crm_is_internal_diversion(string $d): bool {
    $d = preg_replace('/\D/', '', $d);
    // Strip 373 prefix if present
    if (strlen($d) === 11 && substr($d, 0, 3) === '373') $d = substr($d, 3);
    // Internal: starts with 62 (fixed office lines) or is a short ext (<=4 digits)
    return strlen($d) <= 4 || substr($d, 0, 2) === '62';
}

function crm_format_phone(string $phone): string {
    $p = crm_normalize_phone($phone);
    if (strlen($p) === 11 && strpos($p, '373') === 0) {
        return '+373 ' . substr($p, 3, 2) . ' ' . substr($p, 5, 3) . ' ' . substr($p, 8, 3);
    }
    return '+' . $p;
}

// ---- Work hours ----

function crm_is_work_hours(PDO $db, string $prefx, ?int $timestamp = null): bool {
    if ($timestamp === null) $timestamp = time();
    $s = crm_get_all_settings($db, $prefx);

    $work_days = explode(',', $s['work_days'] ?? '1,2,3,4,5');
    $dow = (int)date('N', $timestamp);
    if (!in_array((string)$dow, $work_days)) return false;

    $cur = date('H:i', $timestamp);
    return ($cur >= ($s['work_hours_start'] ?? '09:00') && $cur < ($s['work_hours_end'] ?? '18:00'));
}

// ---- Sources ----

function crm_find_source(PDO $db, string $prefx, string $diversion): ?object {
    if (empty($diversion)) return null;
    $d = crm_normalize_diversion($diversion); // always 8-digit local, e.g. "79600326"
    // Match against phone_number normalized to 8 digits (strip spaces, leading 0 or 373)
    $stmt = $db->prepare("
        SELECT * FROM {$prefx}_crm_sources
        WHERE active = 1
          AND REGEXP_REPLACE(REPLACE(phone_number,' ',''), '^(373|0)', '') = :d
        LIMIT 1
    ");
    $stmt->execute([':d' => $d]);
    return $stmt->fetchObject() ?: null;
}

function crm_get_sources(PDO $db, string $prefx): array {
    $stmt = $db->query("SELECT * FROM {$prefx}_crm_sources ORDER BY sort_order ASC, id ASC");
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// ---- Leads ----

function crm_find_lead_by_phone(PDO $db, string $prefx, string $phone): ?object {
    $p = crm_normalize_phone($phone);
    $stmt = $db->prepare("SELECT * FROM {$prefx}_crm_leads
                          WHERE phone = :p AND status NOT IN ('junk','closed')
                          ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([':p' => $p]);
    return $stmt->fetchObject() ?: null;
}

function crm_get_lead(PDO $db, string $prefx, int $id): ?object {
    $stmt = $db->prepare("SELECT l.*, s.name AS source_name, s.color AS source_color
                          FROM {$prefx}_crm_leads l
                          LEFT JOIN {$prefx}_crm_sources s ON s.id = l.source_id
                          WHERE l.id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    return $stmt->fetchObject() ?: null;
}

function crm_find_user_by_pbx_login(PDO $db, string $prefx, string $pbx_user): ?object {
    if (empty($pbx_user)) return null;
    $stmt = $db->prepare("SELECT id, name, department FROM {$prefx}_adm_usr WHERE pbx_login = :u LIMIT 1");
    $stmt->execute([':u' => $pbx_user]);
    return $stmt->fetchObject() ?: null;
}

// ---- Audit ----

function crm_audit(PDO $db, string $prefx, string $action, string $entity_type, int $entity_id,
                   string $old = '', string $new = '', ?int $user_id = null, string $user_name = ''): void {
    $stmt = $db->prepare("INSERT INTO {$prefx}_crm_audit_log
        (user_id, user_name, action, entity_type, entity_id, old_value, new_value, ip)
        VALUES (:uid, :uname, :action, :etype, :eid, :old, :new, :ip)");
    $stmt->execute([
        ':uid'    => $user_id,
        ':uname'  => $user_name,
        ':action' => $action,
        ':etype'  => $entity_type,
        ':eid'    => $entity_id,
        ':old'    => $old,
        ':new'    => $new,
        ':ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
}

// ---- Lead rotting CSS class ----

function crm_rot_class(?string $last_action_at, int $rot_warn_hours = 24, int $rot_max_hours = 48): string {
    if (empty($last_action_at)) return 'crm-rot-0';
    $hours = (time() - strtotime($last_action_at)) / 3600;
    if ($hours >= 72) return 'crm-rot-3'; 
    if ($hours >= 48) return 'crm-rot-2'; 
    if ($hours >= 24) return 'crm-rot-1'; 
    return 'crm-rot-0';
}

// ---- PBX API (CRM → PBX Moldcell) ----

function crm_pbx_request(PDO $db, string $prefx, string $method, string $endpoint, array $data = []): array {
    $api_base = crm_get_setting($db, $prefx, 'pbx_api_base', 'https://sauto.pbx.moldcell.md/crmapi/v1');
    $api_key  = crm_get_setting($db, $prefx, 'pbx_api_key',  'bf5dab82-f0db-4c89-b4a8-a9879a89e0de');
    $url = rtrim($api_base, '/') . '/' . ltrim($endpoint, '/');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-KEY: ' . $api_key,
        'Content-Type: application/json'
    ]);

    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else {
        if (!empty($data)) $url .= '?' . http_build_query($data);
        curl_setopt($ch, CURLOPT_URL, $url);
    }

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $http_code, 'body' => $response, 'data' => json_decode($response, true)];
}

function crm_get_lang_strings(string $lang = 'ro'): array {
    static $cache = null;
    if ($cache === null) {
        $crm_l = [];
        include __DIR__ . '/crm_lang.php';
        $cache = $crm_l;
    }
    return $cache[$lang] ?? $cache['ro'] ?? [];
}

function crm_notify(PDO $db, string $prefx, int $user_id, string $message_key, string $type = 'info', ?int $lead_id = null, array $params = []): void {
    try {
        $user_lang = $_COOKIE['lang'] ?? 'ro';

        $lc = crm_get_lang_strings($user_lang);

        // Resolve message: if key exists in lang, use it; otherwise use as literal
        $message = isset($lc[$message_key]) ? $lc[$message_key] : $message_key;

        // Replace placeholders {phone}, {name} etc.
        foreach ($params as $k => $v) {
            $message = str_replace('{'.$k.'}', $v, $message);
        }

        $db->prepare("INSERT INTO {$prefx}_crm_notifications (user_id, type, message, lead_id) VALUES (:uid,:type,:msg,:lid)")
           ->execute([':uid'=>$user_id, ':type'=>$type, ':msg'=>$message, ':lid'=>$lead_id]);
    } catch (\Throwable $e) {
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/crm_notify_error.log',
            date('Y-m-d H:i:s') . ' user_id=' . $user_id . ' key=' . $message_key . ' err=' . $e->getMessage() . "\n",
            FILE_APPEND | LOCK_EX);
    }
}

function crm_make_call(PDO $db, string $prefx, string $phone, string $pbx_user_login): array {
    return crm_pbx_request($db, $prefx, 'POST', '/makecall', [
        'phone' => $phone,
        'user'  => $pbx_user_login
    ]);
}

// ---- TTL / Lead expiry ----

/**
 * Move leads that have been inactive longer than lead_ttl_hours back to unprocessed pool.
 * Only affects leads in status active/missed/processed that have an owner.
 * Called on leads list load — lightweight, uses index on last_action_at.
 */
function crm_process_ttl(PDO $db, string $prefx): void {
    $ttl_hours = (int)crm_get_setting($db, $prefx, 'lead_ttl_hours', 48);
    if ($ttl_hours <= 0) return;

    $db->prepare("
        UPDATE {$prefx}_crm_leads
        SET status = 'unprocessed', owner_id = NULL, last_action_at = NOW()
        WHERE status IN ('active','missed','processed')
          AND owner_id IS NOT NULL
          AND last_action_at < DATE_SUB(NOW(), INTERVAL :h HOUR)
    ")->execute([':h' => $ttl_hours]);
}

/**
 * Auto-archive leads that have been rotting for 72h+ (crm-rot-3).
 * Moves active/unprocessed/processed leads (not missed) to junk with is_rot=1.
 */
function crm_process_rot_archive(PDO $db, string $prefx): void {
    $db->prepare("
        UPDATE {$prefx}_crm_leads
        SET status = 'junk', prev_status = status, is_rot = 1, last_action_at = NOW()
        WHERE status IN ('active','unprocessed','processed')
          AND last_action_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)
    ")->execute();
}

// ---- Status labels & colors ----

function crm_status_label(string $status, string $lang = 'ro'): string {
    $labels = [
        'ro' => [
            'active'      => 'ACTIV',
            'missed'      => 'RATAT',
            'unprocessed' => 'NEPRELUCRAT',
            'processed'   => 'ÎN LUCRU',
            'transaction' => 'TRANZACȚIE',
            'closed'      => 'ÎNCHIS',
            'junk'        => 'NECALITATIV',
        ],
        'ru' => [
            'active'      => 'АКТИВНЫЙ',
            'missed'      => 'ПРОПУЩЕН',
            'unprocessed' => 'НЕОБРАБОТАН',
            'processed'   => 'ОБРАБОТАН',
            'transaction' => 'СДЕЛКА',
            'closed'      => 'ЗАКРЫТ',
            'junk'        => 'НЕКАЧЕСТВЕННЫЙ',
        ],
        'en' => [
            'active'      => 'ACTIVE',
            'missed'      => 'MISSED',
            'unprocessed' => 'UNPROCESSED',
            'processed'   => 'PROCESSED',
            'transaction' => 'TRANSACTION',
            'closed'      => 'CLOSED',
            'junk'        => 'JUNK',
        ],
    ];
    return $labels[$lang][$status] ?? strtoupper($status);
}

function crm_status_color(string $status): string {
    $colors = [
        'active'      => '#22c55e',
        'missed'      => '#ef4444',
        'unprocessed' => '#f97316',
        'processed'   => '#3b82f6',
        'transaction' => '#8b5cf6',
        'closed'      => '#16a34a',
        'junk'        => '#6b7280',
    ];
    return $colors[$status] ?? '#888';
}
