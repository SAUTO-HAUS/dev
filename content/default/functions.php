<?php defined( '_DOIT' ) or die( 'Restricted access' );

function e($value) {echo $value;}

function genRand($length) {return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()"), 0, $length);} //symbol randomizer

function myIp() {return $_SERVER['REMOTE_ADDR'];}

function timeNow() { return date( 'd.m.Y ( H:i:s )', time() ); }

function showErr(){
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

function checkArray($value, $array) {
	if (false !== $key = array_search($value, $array)) {
    	return $key;
	}
}

function alertIt($value) {
	echo '<script language="javascript">alert("'.$value.'")</script>';
}

function pdoSet($allowed, &$values, $source = array()) {
  $set = '';
  $values = array();
  if (!$source) $source = &$_POST;
  foreach ($allowed as $field) {
    if (isset($source[$field])) {
      $set.="`".str_replace("`","``",$field)."`". "=:$field, ";
      $values[$field] = $source[$field];
    }
  }
  return substr($set, 0, -2); 
}

/*
Вставка
	$allowed = array("name","surname","email"); // allowed fields
	$sql = "INSERT INTO users SET ".pdoSet($allowed,$values);
	$stm = $dbh->prepare($sql);
	$stm->execute($values);

Апдейт
	$allowed = array("name","surname","email","password"); // allowed fields
	$_POST['password'] = MD5($_POST['login'].$_POST['password']);
	$sql = "UPDATE users SET ".pdoSet($allowed,$values)." WHERE id = :id";
	$stm = $dbh->prepare($sql);
	$values["id"] = $_POST['id'];
	$stm->execute($values);
*/

function isMobile(){
	if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$_SERVER['HTTP_USER_AGENT'])||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($_SERVER['HTTP_USER_AGENT'],0,4))){
		return TRUE;
	}
	else {
		return FALSE;
	}
}

function rename_file($n_path, $n_file_type, $n_replace_from, $n_replace_to ){
	foreach (array_filter(glob($n_path."/*.".$n_file_type) ,"is_file") as $f)
	rename ($f, str_replace($n_replace_from, $n_replace_to, $f));
}

function checkBrowser(){
	$browser = 'unknown';
	$arr_browsers = array ('Firefox'=>'firefox', 'OPR'=>'opera', 'Chrome'=>'chrome', 'Safari'=>'safari', 'MSIE 6.0'=>'old_ie', 'MSIE 7.0'=>'old_ie', 'MSIE 8.0'=>'old_ie', 'MSIE 9.0'=>'ie', 'MSIE 10.0'=>'ie', 'Trident/7.0'=>'ie',);
	foreach ($arr_browsers as $key => $value) {
		if (stristr($_SERVER['HTTP_USER_AGENT'], $key)) {$browser = $value; break;}
	}
	return $browser;
}

//Session duration with buffer time to prevent premature logout
function setSessTime($sess_time){
	$sess_time = isset($sess_time) ? $sess_time : 10800;
	
	ini_set('session.gc_maxlifetime', $sess_time);
	session_set_cookie_params($sess_time);
	
	// Check if session is already started
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}
	
	$now = time();
	
	// Add buffer time (5 minutes) to prevent premature session destruction
	$buffer_time = 300; // 5 minutes buffer
	if (isset($_SESSION['discard_after']) && $now > ($_SESSION['discard_after'] + $buffer_time)) { 
		session_unset(); 
		session_destroy(); 
		session_start();
		__log("Session expired and recreated for user: " . ($_SESSION['user_id'] ?? 'unknown'));
	}
	$_SESSION['discard_after'] = $now + $sess_time;
}

//Remove directories and files in them
function removeIt($dir, $checker) {
    foreach (glob($dir) as $file) {
        if (is_dir($file)) { 
            removeIt("$file/*", false);
            rmdir($file);
        } else {
            unlink($file);
        }
    }
	if ($checker==true){ rmdir($dir); }
}

//create iframe video play.md
function playMD($path, $return) {
	$return = $return==null ? $return='min' : $return;
    $temp = explode("play.md/", $path);
	$new_path = !$temp[1] ? $temp[0] : $temp[1];
	if ($return=='max'){
		$new_path = '<iframe src="https://play.md/embed/'.$new_path.'?title=false&autoplay=false" width="100%" height="270" frameborder="0" scrolling="no" allowfullscreen></iframe>';
	}
	return $new_path;
}

//First letter to Uppercase
function mb_ucfirst($string, $encoding)
{
	$string = mb_strtolower($string, $encoding);
    $strlen = mb_strlen($string, $encoding);
    $firstChar = mb_substr($string, 0, 1, $encoding);
    $then = mb_substr($string, 1, $strlen - 1, $encoding);
    return mb_strtoupper($firstChar, $encoding) . $then;
}

//Chk device type
function usr_agent(){
	$uag = $_SERVER['HTTP_USER_AGENT'];
	
	$ds = 'NONE';
	
	$ds = stripos($uag,"iPod") ? 'IOS' : $ds;
	$ds = stripos($uag,"iPhone") ? 'IOS' : $ds;
	$ds = stripos($uag,"iPad") ? 'IOS' : $ds;
	$ds = stripos($uag,"Android") ? 'ANDROID' : $ds;
	$ds = stripos($uag,"webOS") ? 'WEBOS' : $ds;
	//$ds = stripos($uag,"webOS") ? 'WEBOS' : $ds;
	
	$ds = stripos($uag,"Mac OS") ? 'MAC' : $ds;
	//$ds = stripos($uag,"Safari") ? 'MAC' : $ds;
	
	$ds = $ds==='NONE' ? 'PC' : $ds;
	return $ds;
}

//Add , . to numbers
function parseCurr($value) {
	// Convert to float to avoid number_format() warning
	$numValue = floatval($value);
	if ( intval($numValue) == $numValue ) {
		$return = number_format($numValue, 0, ".", ",");
	}else{
		$return = number_format($numValue, 2, ".", ",");
		//$return = rtrim($return, 0);
	}
	return $return;
}

function symb_rplc($val){
	switch ( strtoupper($val) ){
		case 'EUR':
			return '&#8364;';
			break;
		case 'MDL':
			return 'MDL';
			break;
		default:
			return $val;
	}
}

function toNumber($v=0){
	$v = (int)preg_replace('/[^0-9.]+/', '', $v );
	return $v;
}

if (!function_exists('is_local_ip')) {
    /**
     * Check if is local
     * @return bool
     */
    function is_local_ip()
    {
        return in_array($_SERVER['SERVER_ADDR'], get_local_ip_list());
    }
}

if (!function_exists('get_local_ip_list')) {
    /**
     * List of local ip
     * @return array
     */
    function get_local_ip_list()
    {
        return [
            '::1',
            '127.0.0.1',
        ];
    }
}

if (!function_exists('dd')) {
    function dd(...$args) {
        echo '<pre>';
        foreach ($args as $arg) {
            var_dump($arg);
        }
        echo '</pre>';
        die;
    }
}

if (!function_exists('__')) {
    function __($key, $replace = []): string
    {
        global $trans;
        return $trans->get($key, $replace);
    }
}

if (!function_exists('__post')) {
    function __post($key, $default = null, $sanitize = true, bool $allowEmpty = false)
    {
        if (!isset($_POST[$key])) {
            return $default;
        }
        $value = $_POST[$key];

        if (!$allowEmpty && is_string($value) && trim($value) === '') {
            return $default;
        }

        if ($sanitize && is_string($value)) {
            $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }
}

if (!function_exists('__get')) {
    function __get($key, $default = null, $sanitize = true)
    {
        if (isset($_GET[$key])) {
            $value = $_GET[$key];
            if ($sanitize && is_string($value)) {
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            return $value;
        }
        return $default;
    }
}

if (!function_exists('__log')) {
    function __log($message, $logFile = 'app.log', $echo = false)
    {
        $logPath = $_SERVER['DOCUMENT_ROOT'] . '/logs/';
        $timestamp = date('Y-m-d H:i:s');

        if (is_countable($message))
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);

        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;

        if (!is_dir($logPath)) {
            mkdir($logPath, 0777, true);
        }

        $logFilePath = $logPath . $logFile;

        file_put_contents($logFilePath, $logMessage, FILE_APPEND);

        if ($echo) {
            echo $logMessage;
        }
    }
}

/**
 * generate proper car URL 
 * @param string 
 * @param string 
 * @return string 
 */
function buildCarUrl($brand, $model = '') {
    $brand_clean = str_replace('_', '-', $brand);
    if (empty($model)) {
        return $brand_clean;
    }
    $model_clean = str_replace('_', '-', $model);
    return $brand_clean . '-' . $model_clean;
}