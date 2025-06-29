<?php
include_once('environment.php');

if ( !in_array($_POST['tp'], ['adm','ste'], true) ){ die( 'Restricted access' ); }
$returnIt = ['xsx'=>'1'];

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

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

if (__post('tp') == 'adm') {
    $tp = __post('tp');
    $pg = __post('pg');
    $cookie_sess = $_COOKIE['sess'];

	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr WHERE `cookie`=:cookie');
    $pdo->execute(['cookie' => $cookie_sess]);
    $user = $pdo->fetch(PDO::FETCH_ASSOC);

    $user_id = $user['id'];
    $user_login = $user['login'];
    $user_type = $user['type'];
    $user_active = $user['act'];

    if ($user_active !== 1) {
        die('User is not active');
    }

    if (!isset($admin_menu[$user_type]) || !in_array($pg, $admin_menu[$user_type], true)) {
        die('Restricted access');
    }

    $ajaxFile = _ADM_AJAX . '/' . $pg . '/ajax.php';
    if (file_exists($ajaxFile)) {
        require_once $ajaxFile;
    } else {
        die('Page not found');
    }
}
elseif ($_POST['tp']=='ste') {
    require_once (_SITE.'/ajax/ajax.php');
}

$db->connection = null;

echo json_encode($returnIt);