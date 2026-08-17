<?php
include_once('environment.php');

// This endpoint returns JSON. PHP warnings/notices printed into the output
// stream produce "<br /><b>Warning</b>: ..." before the JSON, which breaks
// JSON.parse on the client ("Unexpected token '<'"). Keep errors logged but
// out of the response body.
@ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

// Same cookie attributes as index.php — the session cookie must not be issued
// without Secure/SameSite just because it happened to be created by an AJAX hit.
if ( ( session_id()=='' || !isset($_SESSION) ) ){
	session_set_cookie_params([
		'lifetime' => 0,
		'path'     => '/',
		'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	session_start();
}

$requestTp = $_POST['tp'] ?? $_GET['tp'] ?? '';
if ( !in_array($requestTp, ['adm','ste'], true) ){ die( 'Restricted access' ); }
$returnIt = ['xsx'=>'1'];
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

defined('_DOIT') or define('_DOIT', 1);
defined('_DEFAULT') or define('_DEFAULT', $_SERVER["DOCUMENT_ROOT"].'/content/default');

require_once (_DEFAULT.'/defines.php');
require_once (_DEFAULT.'/functions.php');
require_once (_DEFAULT.'/config.php');
require_once (_DEFAULT.'/language.php');
require_once (_DEFAULT.'/arrays.php');
require (_DEFAULT.'/dbi.php');
require_once (_DEFAULT.'/seo.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Core\Container;
use App\Lang;

$trans = new Lang($_COOKIE['lang']);
Container::set('db', $db);
Container::set('prefix', $prefx);

require_once (_SITE_INCL.'/b2b/b2b_bootstrap.php');

if (isset($_POST['fn']) && $_POST['fn']=='snd_msg'){
	require _PLUGINS.'/PHPMailer/src/Exception.php'; require _PLUGINS.'/PHPMailer/src/PHPMailer.php'; require _PLUGINS.'/PHPMailer/src/SMTP.php';
	$mail = new PHPMailer(true);
}

if (__post('tp') == 'adm' || (isset($_GET['tp']) && $_GET['tp'] == 'adm')) {
    $tp = __post('tp') ?: $_GET['tp'] ?? '';
    $pg = __post('pg') ?: $_GET['pg'] ?? '';
    
    // Check for session cookie existence
    if (!isset($_COOKIE['sess']) || empty($_COOKIE['sess'])) {
        die(json_encode(['error' => 'No session cookie']));
    }
    
    $cookie_sess = $_COOKIE['sess'];
    $sess = explode("-", $cookie_sess);

    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr WHERE id = :id AND `act`="1" AND `cookie`=:cookie AND `sess_e`>:time_now');
    $pdo->execute(array(
        'id' => $sess[0],
        'cookie' => $cookie_sess,
        'time_now' => time()
    ));
    
    $user = $pdo->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die(json_encode(['error' => 'Invalid session or expired']));
    }

    $user_id = $user['id'];
    $user_login = $user['login'];
    $user_name = $user['name'];
    $user_type = $user['type'];
    $user_role = $user['role'] ?? $user['type'];
    $user_active = $user['act'];

    // Keep the session alive on AJAX activity too (create/delete/edit), not just full page loads.
    // Enforce a minimum 1-year session so users are never logged out while working.
    $sess_minutes = max((int)$user['sess_t'], 525600); // 525600 min = 1 year
    $pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET `sess_e`=:sess_e WHERE id=:id');
    $pdo->execute(['sess_e' => time()+(60*$sess_minutes), 'id' => $user_id]);

    // Store user information in session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_role'] = $user_role;
    $_SESSION['user_name'] = $user['name'];

    // Include RBAC system for permission checks
    require_once(_ADM_INCL.'/rbac_config.php');
    
    // Check RBAC permissions instead of old admin_menu
    // Gordon (superadmin) always has access
    if ($user_role === 'gordon') {
        // Allow full access for gordon
    } elseif ($pg === 'crm' && in_array($_POST['fn'] ?? '', ['get_notifications', 'mark_notifications_read'])) {
        // Notification endpoints accessible to all logged-in users
    } elseif ($pg === 'parsing') {
        // Parsing access is ID-based (include/parsing_access.php), not role-based.
        // Let any logged-in user through here; parsing/ajax.php gates by user_id.
    } elseif (in_array($user_role, ['publisher', 'publisher_limited']) && in_array($pg, ['docs', 'cars', 'ordercars', 'tyres', 'calculator'])) {
        // Allow access for publisher roles to their permitted modules
    } elseif (!rbac_has_permission($user_role, $pg, 'read')) {
        die('Restricted access');
    }

    // Special handling for ordercars to use separate ajax folder
    if ($pg === 'ordercars') {
        $ajaxFile = _ADM_AJAX . '/ordercars/order_ajax.php';
    } elseif ($pg === 'crm' && isset($_GET['section'])) {
        require _ADM_AJAX . '/crm/analytics_section.php';
        exit;
    } else {
        $ajaxFile = _ADM_AJAX . '/' . $pg . '/ajax.php';
    }
    if (file_exists($ajaxFile)) {
        require $ajaxFile;
    } else {
        die(json_encode(['error'=>'File not found', 'path'=>$ajaxFile]));
    }
}
elseif ($_POST['tp']=='ste') {
    require_once (_SITE.'/ajax/ajax.php');
}

$db = null;

echo json_encode($returnIt);