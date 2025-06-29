<?php
echo "[DEBUG] Script started\n";
if ( !in_array($_POST['tp'], ['adm','ste'], true) ){
    echo "[DEBUG] Invalid tp value: ".$_POST['tp']."\n";
    die( 'Restricted access' );
}
echo "[DEBUG] tp value is valid: ".$_POST['tp']."\n";
$returnIt = ['xsx'=>'1'];
echo "[DEBUG] returnIt initialized\n";

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

echo "[DEBUG] About to load required files\n";
try {
    require_once (_DEFAULT.'/defines.php');
    echo "[DEBUG] defines.php loaded\n";
    require_once (_DEFAULT.'/functions.php');
    echo "[DEBUG] functions.php loaded\n";
    require_once (_DEFAULT.'/config.php');
    echo "[DEBUG] config.php loaded\n";
    require_once (_DEFAULT.'/language.php');
    echo "[DEBUG] language.php loaded\n";
    require_once (_DEFAULT.'/arrays.php');
    echo "[DEBUG] arrays.php loaded\n";
    require (_DEFAULT.'/dbi.php');
    echo "[DEBUG] dbi.php loaded\n";
    require_once (_DEFAULT.'/seo.php');
    echo "[DEBUG] seo.php loaded\n";
} catch (Exception $e) {
    echo "[DEBUG] ERROR loading required files: ".$e->getMessage()."\n";
}

echo "[DEBUG] About to import classes\n";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Core\Container;
use App\Lang;

try {
    echo "[DEBUG] Initializing Lang with cookie: ".($_COOKIE['lang'] ?? 'undefined')."\n";
    $trans = new Lang($_COOKIE['lang'] ?? 'en');
    echo "[DEBUG] Lang initialized\n";
    
    echo "[DEBUG] Setting Container values\n";
    Container::set('db', $db);
    echo "[DEBUG] db set in Container\n";
    Container::set('prefix', $prefx);
    echo "[DEBUG] prefix set in Container\n";
} catch (Exception $e) {
    echo "[DEBUG] ERROR in Container/Lang setup: ".$e->getMessage()."\n";
}

echo "[DEBUG] Checking for send_message request\n";
if (isset($_POST['fn']) && $_POST['fn']=='snd_msg'){
    echo "[DEBUG] Setting up PHPMailer\n";
    try {
        require _PLUGINS.'/PHPMailer/src/Exception.php';
        echo "[DEBUG] PHPMailer Exception loaded\n";
        require _PLUGINS.'/PHPMailer/src/PHPMailer.php';
        echo "[DEBUG] PHPMailer loaded\n";
        require _PLUGINS.'/PHPMailer/src/SMTP.php';
        echo "[DEBUG] PHPMailer SMTP loaded\n";
        $mail = new PHPMailer(true);
        echo "[DEBUG] PHPMailer initialized\n";
    } catch (Exception $e) {
        echo "[DEBUG] ERROR in PHPMailer setup: ".$e->getMessage()."\n";
    }
}

echo "[DEBUG] Checking request type\n";
if (__post('tp') == 'adm') {
    echo "[DEBUG] Processing admin request\n";
    $tp = __post('tp');
    $pg = __post('pg');
    echo "[DEBUG] Admin page requested: ".$pg."\n";
    
    $cookie_sess = $_COOKIE['sess'] ?? '';
    echo "[DEBUG] Session cookie: ".substr($cookie_sess, 0, 5)."...\n";
    
    try {
        echo "[DEBUG] Preparing database query\n";
        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr WHERE `cookie`=:cookie');
        echo "[DEBUG] Executing query\n";
        $pdo->execute(['cookie' => $cookie_sess]);
        echo "[DEBUG] Fetching user data\n";
        $user = $pdo->fetch(PDO::FETCH_ASSOC);
        echo "[DEBUG] User data fetched: ".($user ? "success" : "not found")."\n";

        if (!$user) {
            echo "[DEBUG] ERROR: No user found with this cookie\n";
            die('Authentication failed');
        }
        
        $user_id = $user['id'] ?? 0;
        echo "[DEBUG] User ID: ".$user_id."\n";
        $user_login = $user['login'] ?? '';
        echo "[DEBUG] User login: ".$user_login."\n";
        $user_type = $user['type'] ?? '';
        echo "[DEBUG] User type: ".$user_type."\n";
        $user_active = $user['act'] ?? 0;
        echo "[DEBUG] User active status: ".$user_active."\n";

        if ($user_active !== 1) {
            echo "[DEBUG] ERROR: User is not active\n";
            die('User is not active');
        }
        echo "[DEBUG] User is active\n";

        echo "[DEBUG] Checking user permissions for page ".$pg."\n";
        echo "[DEBUG] Available menu items for this user: ".(isset($admin_menu[$user_type]) ? implode(',', $admin_menu[$user_type]) : 'none')."\n";
        if (!isset($admin_menu[$user_type]) || !in_array($pg, $admin_menu[$user_type], true)) {
            echo "[DEBUG] ERROR: User does not have permission for this page\n";
            die('Restricted access');
        }
        echo "[DEBUG] User has permission for this page\n";

        $ajaxFile = _ADM_AJAX . '/' . $pg . '/ajax.php';
        echo "[DEBUG] Looking for ajax file: ".$ajaxFile."\n";
        if (file_exists($ajaxFile)) {
            echo "[DEBUG] Ajax file found, including it now\n";
            try {
                require_once $ajaxFile;
                echo "[DEBUG] Ajax file included successfully\n";
            } catch (Exception $e) {
                echo "[DEBUG] ERROR including ajax file: ".$e->getMessage()."\n";
                die('Error including page file');
            }
        } else {
            echo "[DEBUG] ERROR: Ajax file not found\n";
            die('Page not found');
        }
    } catch (Exception $e) {
        echo "[DEBUG] CRITICAL ERROR in admin section: ".$e->getMessage()."\n";
    }
}
elseif ($_POST['tp']=='ste') {
    echo "[DEBUG] Processing site request\n";
    try {
        echo "[DEBUG] Including site ajax file from: "._SITE."/ajax/ajax.php\n";
        require_once (_SITE.'/ajax/ajax.php');
        echo "[DEBUG] Site ajax file included successfully\n";
    } catch (Exception $e) {
        echo "[DEBUG] ERROR including site ajax file: ".$e->getMessage()."\n";
    }
}

echo "[DEBUG] Closing database connection\n";
try {
    $db->connection = null;
    echo "[DEBUG] Database connection closed\n";
} catch (Exception $e) {
    echo "[DEBUG] ERROR closing database connection: ".$e->getMessage()."\n";
}

echo "[DEBUG] Final return value: ".print_r($returnIt, true)."\n";
try {
    echo json_encode($returnIt);
    echo "[DEBUG] Response sent successfully\n";
} catch (Exception $e) {
    echo "[DEBUG] ERROR encoding response: ".$e->getMessage()."\n";
}
