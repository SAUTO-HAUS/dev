<?php

ob_start();

include_once('environment.php');

if ( ( session_id()=='' || !isset($_SESSION) ) ){ session_start(); }
/*header('Cache-Control: no-cache');
header('Pragma: no-cache');*/

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

//ini_set("log_errors", 1);
//ini_set("error_log", "/tmp/fjkLsddjw2Kls9Msdxz3KFl.log");
//error_reporting(E_ALL);
//error_reporting(0);

// Work of people who connected 999
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

// Presets
// Requested in every PHP file (so that the file cannot be run by direct request)
define('_DOIT', 1);
// Was done long ago when it was necessary to change the file and folder structure. Currently just a shortcut for convenience.
define('_DEFAULT', 'content/default');

// Include files for presets, functions, settings and languages
require_once (_DEFAULT.'/defines.php');
require_once (_DEFAULT.'/functions.php');
require_once (_DEFAULT.'/config.php');
require_once (_DEFAULT.'/language.php');

// Work of people who connected 999
use App\Lang;
use App\Core\Container;
$trans = new Lang($_COOKIE['lang']);

// Include database settings, SEO and preset arrays files
require (_DEFAULT.'/dbi.php');
require_once (_DEFAULT.'/seo.php');
require_once (_DEFAULT.'/arrays.php');

// Work of people who connected 999
Container::set('db', $db);
Container::set('prefix', $prefx);

//---https, www, etc. redirect
require_once(_DEFAULT.'/redirect.php');
//---ip blacklist
//require_once(_DEFAULT.'/blacklist.php');
//---Maintenance work
//if ( $offline == 1 && myIp()=='xx.xx.xx.xx' ){ require_once(_DEFAULT.'/offline.php'); die(); }

//---Redirect old tyres URL format to /tyres
if (isset($t_mp[2]) && $t_mp[2] == 'tyres' && isset($t_mp[3]) && !is_numeric($t_mp[3])) {
    $lang = isset($t_mp[1]) ? $t_mp[1] : 'ro';
    header('Location: /' . $lang . '/tyres', true, 301);
    exit;
}

//$t_mp - array responsible for URL (separated by slash "/") www.sauto.md/ro/cars -> 0:www.sauto.md | 1:ro | 2:cars
if (isset($t_mp[2])&&$t_mp[2]==$admin_dir){ // If request to admin panel (admin_dir is defined in config.php)
	if (isset($_POST)) { include(_ADM.'/action/post.php'); }
	if (!empty($_COOKIE['sess'])) {  include(_ADM.'/action/adm_chk.php'); }
}

//---err 404 — unknown top-level route. Flag it and let body.php render the styled 404
// inside the normal layout (menu/footer). Admin panel keeps the old standalone 404.
if( ( isset($t_mp[2]) && !in_array( $t_mp[2], $url_arr ) ) || ( isset($t_mp[2]) && $t_mp[2]=='' && isset($t_mp[3]) ) ) {
    http_response_code(404);
    if (isset($t_mp[2]) && $t_mp[2] === $admin_dir) {
        include_once(_DEFAULT.'/404.php');
        die();
    }
    $GLOBALS['page_is_404'] = true;
    
}
//---OLD Internet Explorer
if ( checkBrowser() == 'old_ie' ){include(_DEFAULT.'/ie_sorry.php'); die(); }
//---mobile check
$isMobile = isMobile() ? '1' : '0';

//start content
if ( isset($_COOKIE['lang']) ){ // if lang cookie exists that is responsible for the used language (defined in language.php file)


        // Special routing for Telegram standalone pages
        if (isset($t_mp[2]) && $t_mp[2] == 'telegram') {
                require_once(_SITE . '/page/new_pages/telegram/telegram.php');
                $db->connection = null;
                exit();
        }
        if (isset($t_mp[2]) && $t_mp[2] == 'telegram_adv') {
                require_once(_SITE . '/page/new_pages/telegram_adv/telegram_adv.php');
                $db->connection = null;
                exit();
        }
        
        if (isset($t_mp[2]) && $t_mp[2] == 'vin-redirect') {
                require_once(_SITE . '/page/vin_redirect.php');
                $db->connection = null;
                exit();
        }
        if (isset($t_mp[2]) && $t_mp[2] == 'vin-check') {
                require_once(_SITE . '/page/vin_check.php');
                $db->connection = null;
                exit();
        }

        // Ajax requests — route to ajax.php and exit before HTML output
        if (isset($t_mp[3]) && $t_mp[3] === 'ajax') {
            require_once('ajax.php');
            $db->connection = null;
            exit();
        }

        echo '
<!DOCTYPE html>';
        echo '
        <html lang="'.$_COOKIE['lang'].'" >';
                if (isset($t_mp[2]) && $t_mp[2] == $admin_dir) { // If request to admin panel
            require_once(_ADM . '/body.php');
        } else { // If request NOT to admin panel
            require_once(_SITE . '/body.php');
        }
        // end html
        echo '
        </html>';
        $db->connection = null;
}