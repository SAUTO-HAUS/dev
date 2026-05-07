<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'].'/environment.php');

// DB connect
try {
    $db = new PDO('mysql:host='.SQL_HOST.';dbname='.SQL_DB.';charset='.SQL_CHARSET, SQL_USER, SQL_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { http_response_code(500); exit('DB error'); }

$prefx = 'gh3sp';

// Auth check via cookie
$user_id = 0;
$user_role = '';
if (!empty($_COOKIE['sess'])) {
    $sess = explode('-', $_COOKIE['sess']);
    $st = $db->prepare("SELECT id, role, type FROM {$prefx}_adm_usr WHERE id=:id AND act='1' AND cookie=:cookie AND sess_e>:now");
    $st->execute([':id' => (int)($sess[0] ?? 0), ':cookie' => $_COOKIE['sess'], ':now' => time()]);
    $u = $st->fetch(PDO::FETCH_OBJ);
    if ($u) { $user_id = (int)$u->id; $user_role = $u->role ?: $u->type; }
}

if (!$user_id) { http_response_code(403); exit('Unauthorized'); }

// RBAC: only roles with crm analytics access
$allowed = ['gordon', 'admin'];
if (!in_array($user_role, $allowed)) { http_response_code(403); exit('Forbidden'); }

// Params
$sel_year  = max(2020, min((int)date('Y'), (int)($_GET['y'] ?? date('Y'))));
$sel_month = max(1,    min(12,             (int)($_GET['m'] ?? date('n'))));
$user_f    = (int)($_GET['uid'] ?? 0);

$date_from = sprintf('%04d-%02d-01', $sel_year, $sel_month);
$date_to   = date('Y-m-t', strtotime($date_from));

$bw = "DATE(l.created_at) BETWEEN :df AND :dt AND l.status != 'junk'";
$bp = [':df' => $date_from, ':dt' => $date_to];
if ($user_f) { $bw .= " AND l.owner_id = :uid"; $bp[':uid'] = $user_f; }

$stmt = $db->prepare("
    SELECT l.id, l.status, l.department, l.phone, l.client_name,
           l.created_at, l.last_action_at, s.name AS source_name, u.name AS owner_name
    FROM {$prefx}_crm_leads l
    LEFT JOIN {$prefx}_crm_sources s ON s.id = l.source_id
    LEFT JOIN {$prefx}_adm_usr u ON u.id = l.owner_id
    WHERE $bw ORDER BY l.created_at DESC LIMIT 5000");
$stmt->execute($bp);
$rows = $stmt->fetchAll(PDO::FETCH_OBJ);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="crm_leads_' . $date_from . '_' . $date_to . '.csv"');
header('Cache-Control: no-cache');

echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['ID','Status','Dept','Telefon','Nume','Data','Ultima acțiune','Sursă','Manager'], ';');
foreach ($rows as $r) {
    fputcsv($out, [
        $r->id, $r->status, $r->department, $r->phone,
        $r->client_name ?? '', $r->created_at,
        $r->last_action_at ?? '', $r->source_name ?? '', $r->owner_name ?? ''
    ], ';');
}
fclose($out);
exit;
