<?php
/**
 * Contact form endpoint — saves submission to CRM inbox (channel: site)
 */
define('_DOIT', 1);
require_once __DIR__ . '/../environment.php';
require_once __DIR__ . '/../content/default/defines.php';
require_once __DIR__ . '/../content/default/functions.php';
require_once __DIR__ . '/../content/default/config.php';
require_once __DIR__ . '/../content/default/dbi.php';
require_once __DIR__ . '/../content/admin/include/crm/crm_inbox_core.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit;
}

$name     = trim($_POST['name']     ?? '');
$phone    = trim($_POST['phone']    ?? '');
$message  = trim($_POST['message']  ?? '');
$source   = trim($_POST['source']   ?? 'site');
$page_url = mb_substr(strip_tags(trim($_POST['page_url'] ?? '')), 0, 500);
$gdpr    = !empty($_POST['gdpr']);

if (!$name || !$phone || !$gdpr) {
    echo json_encode(['ok' => false, 'msg' => 'Date lipsă']);
    exit;
}

// Sanitize
$name    = mb_substr(strip_tags($name),   0, 100);
$phone   = mb_substr(strip_tags($phone),  0, 30);
$message = mb_substr(strip_tags($message),0, 2000);
$source  = mb_substr(preg_replace('/[^a-z0-9_\-]/i', '', $source), 0, 50) ?: 'site';

// Unique sender_id based on phone
$sender_id = 'site_' . preg_replace('/\D/', '', $phone);

// Dept based on source page
$order_sources = ['ordercars', 'order', 'korea'];
$dept = in_array($source, $order_sources) ? 'order' : 'stock';

// Create or get session — page_id = source (credit / sale / tradein / site)
$sid = inbox_create_session($db, $prefx, 'site', $sender_id, $name, $source, $dept);

if (!$sid) {
    echo json_encode(['ok' => false, 'msg' => 'Eroare server']);
    exit;
}

// Save phone and source to session
$db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET sender_phone=:p, page_id=:pg, status='active', ai_active=0, updated_at=NOW() WHERE id=:id")
   ->execute([':p' => $phone, ':pg' => $source, ':id' => $sid]);

// Build message body
$body = "Nume: $name\nTelefon: $phone";
if ($message) $body .= "\nMesaj: $message";
if ($page_url && in_array($source, ['cars', 'ordercars'])) $body .= "\nPagina: $page_url";

inbox_save_message($db, $prefx, $sid, 'site', 'in', $sender_id, $name, $body);

// Auto-create lead only for specific pages
$lead_sources = ['cars', 'ordercars', 'order'];
if (in_array($source, $lead_sources)) {
    require_once __DIR__ . '/../content/admin/include/crm/crm_core.php';
    inbox_create_lead($db, $prefx, $sid, $phone, 'site', $dept, $name, null, $source);
}

echo json_encode(['ok' => true]);
