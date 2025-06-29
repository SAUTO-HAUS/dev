<?php
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

//???Работа людей, что подключали 999
spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

//Предустановки 
//Запрашивается в каждом файле php (Чтобы нельзя было запустить файл прямым запросом к нему)
define('_DOIT', 1);
//Делалось давно, когда была необходимость изменять структуру файлов и папок. На данный момент просто сокращение для удобства.
define('_DEFAULT', 'content/default');

//Подключение файлов предустановок, функций, настроек и языков
require_once (_DEFAULT.'/defines.php');
require_once (_DEFAULT.'/functions.php');
require_once (_DEFAULT.'/config.php');
require_once (_DEFAULT.'/language.php');

//???Работа людей, что подключали 999
use App\Lang;
use App\Core\Container;
$trans = new Lang($_COOKIE['lang']);

//Подключение файлов настроек базы данных, seo и предустановленных массивов
require (_DEFAULT.'/dbi.php');
require_once (_DEFAULT.'/seo.php');
require_once (_DEFAULT.'/arrays.php');

//???Работа людей, что подключали 999
Container::set('db', $db);
Container::set('prefix', $prefx);

//---https, www, etc. redirect
require_once(_DEFAULT.'/redirect.php');
//---ip blacklist
//require_once(_DEFAULT.'/blacklist.php');
//---Maintenance work
//if ( $offline == 1 && myIp()=='xx.xx.xx.xx' ){ require_once(_DEFAULT.'/offline.php'); die(); }

//$t_mp - это массив отвечающий за url (разделенное слэшем "/") www.sauto.md/ro/cars -> 0:www.sauto.md | 1:ro | 2:cars
if (isset($t_mp[2])&&$t_mp[2]==$admin_dir){//Если запрос к админке (admin_dir прописан в config.php)
	if (isset($_POST)) { include(_ADM.'/action/post.php'); }
	if (!empty($_COOKIE['sess'])) {  include(_ADM.'/action/adm_chk.php'); }
}

//начало html
echo '
<!DOCTYPE html>';

//---err 404
if( ( isset($t_mp[2]) && !in_array( $t_mp[2], $url_arr ) ) || ( isset($t_mp[2]) && $t_mp[2]=='' && isset($t_mp[3]) ) ) { include_once(_DEFAULT.'/404.php'); die(); }
//---OLD Internet Explorer
if ( checkBrowser() == 'old_ie' ){include(_DEFAULT.'/ie_sorry.php'); die(); }
//---mobile check
$isMobile = isMobile() ? '1' : '0';

//start content
if ( isset($_COOKIE['lang']) ){//если существует кука lang отвечающая за используемый язык (прописана в файле language.php)
	echo '
	<html lang="'.$_COOKIE['lang'].'" test-line >';
		if (isset($t_mp[2]) && $t_mp[2] == $admin_dir) {//Если запрос к админке
            require_once(_ADM . '/body.php');
        } else {//Если запрос НЕ к админке
            require_once(_SITE . '/body.php');
        }
	//конец html
	echo '
	</html>';
	$db->connection = null;
}