<?php
include_once('environment.php');

if ( ( session_id()=='' || !isset($_SESSION) ) ){ session_start(); }

$requestTp = $_POST['tp'] ?? $_GET['tp'] ?? '';
if ( !in_array($requestTp, ['adm','ste'], true) ){ die( 'Restricted access' ); }
$returnIt = ['xsx'=>'1'];
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

define('_DOIT', 1);
define('_DEFAULT', $_SERVER["DOCUMENT_ROOT"].'/content/default');

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

    // Enhanced authentication check like in adm_chk.php
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr WHERE id = :id AND `act`="1" AND `cookie`=:cookie AND `this_ip`=:this_ip AND `sess_e`>:time_now');
	$pdo->execute(array(
		'id' => $sess[0],
		'cookie' => $cookie_sess,
		'this_ip' => myIp(),
		'time_now' => time()
	));
    
    $user = $pdo->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die(json_encode(['error' => 'Invalid session or expired']));
    }

    $user_id = $user['id'];
    $user_login = $user['login'];
    $user_type = $user['type'];
    $user_role = $user['role'] ?? $user['type'];
    $user_active = $user['act'];
    
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
    } elseif (in_array($user_role, ['publisher', 'publisher_limited']) && in_array($pg, ['docs', 'cars', 'ordercars', 'tyres', 'calculator'])) {
        // Allow access for publisher roles to their permitted modules
    } elseif (!rbac_has_permission($user_role, $pg, 'read')) {
        die('Restricted access');
    }

    // Special handling for ordercars to use separate ajax folder
    if ($pg === 'ordercars') {
        $ajaxFile = _ADM_AJAX . '/ordercars/order_ajax.php';
    } else {
        $ajaxFile = _ADM_AJAX . '/' . $pg . '/ajax.php';
    }
    if (file_exists($ajaxFile)) {
        require_once $ajaxFile;
    } else {
        die('Page not found');
    }
}
elseif ($_POST['tp']=='ste') {
    require_once (_SITE.'/ajax/ajax.php');
}

$db = null;

echo json_encode($returnIt);