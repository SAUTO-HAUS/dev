<?php

$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && ($_GET['token'] ?? '') !== 'cron2026') {
    http_response_code(403);
    die('Forbidden');
}
if (!$IS_CLI) header('Content-Type: text/plain; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';
    $classPath = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');

use App\Core\Container;
use App\Services\Parsing\AdapterFactory;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');
$prefx = 'gh3sp';

Container::set('db', $db);
Container::set('prefix', $prefx);

$ts = '[' . date('Y-m-d H:i:s') . ']';

$adapter = AdapterFactory::create('ecarstrade');
if (!$adapter || !method_exists($adapter, 'pingSession')) {
    echo "$ts eCarsTrade adapter unavailable\n";
    exit(1);
}

$res = $adapter->pingSession();
if (!empty($res['alive'])) {
    echo "$ts keep-alive OK (session active)\n";
    exit(0);
}

// Not alive: log loudly so the operator knows to renew the cookie in Settings.
// 'no_cookie' = nothing configured yet; an http_* / non_card reason = expired.
echo "$ts keep-alive: session NOT active — reason: "
    . ($res['reason'] ?? 'unknown')
    . " (status " . ($res['status'] ?? '?') . "). Renew ECARSTRADE_COOKIE in Settings.\n";
exit(2);
