<?php

/**
 * Serves a client ID-card scan from /media/docs_id, which is closed to the web.
 *
 *   /content/admin/include/docs_id_photo.php?f=<stored name>
 *
 * The whole point of the folder being closed is this check: an ID card is
 * handed only to a logged-in admin, never to whoever holds the link. Same
 * session test the AJAX router uses (cookie -> gh3sp_adm_usr, active, not
 * expired).
 */

include_once($_SERVER['DOCUMENT_ROOT'].'/environment.php');

define('_DOIT', 1);
define('_DEFAULT', $_SERVER['DOCUMENT_ROOT'].'/content/default');

// Deliberately lean: the constants, the DB and the path helpers, nothing else —
// this file only reads one file off disk and streams it.
require_once (_DEFAULT.'/defines.php');
require (_DEFAULT.'/dbi.php');
require_once (__DIR__.'/docs/id_photo.php');

$prefx = 'gh3sp';

function docs_id_photo_deny(int $code, string $msg): void
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    die($msg);
}

// ---- admin session
$cookie = $_COOKIE['sess'] ?? '';
if ($cookie === '') {
    docs_id_photo_deny(403, 'Forbidden');
}
$sess = explode('-', $cookie);

try {
    $stmt = $db->prepare('SELECT `id` FROM '.$prefx.'_adm_usr
        WHERE `id`=:id AND `act`="1" AND `cookie`=:cookie AND `sess_e`>:now LIMIT 1');
    $stmt->execute([':id' => (int)($sess[0] ?? 0), ':cookie' => $cookie, ':now' => time()]);
    if (!$stmt->fetchColumn()) {
        docs_id_photo_deny(403, 'Forbidden');
    }
} catch (Throwable $e) {
    docs_id_photo_deny(500, 'Error');
}

// ---- file
$path = docs_id_photo_path($_GET['f'] ?? '');
if ($path === '') {
    docs_id_photo_deny(404, 'Not found');
}

$ext   = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

header('Content-Type: '.($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: '.filesize($path));
// Private: an ID scan must not sit in a shared proxy cache.
header('Cache-Control: private, max-age=300');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="id.'.$ext.'"');
readfile($path);
