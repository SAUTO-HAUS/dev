<?php
/**
 * Parsing session keep-alive — ONE cron for BOTH cookie-based sources (OpenLane +
 * eCarsTrade). Makes a cheap authenticated call to each so the site extends the
 * login session, delaying cookie expiry. Encar needs no cookie, so it's skipped.
 *
 * Run every ~10-15 min:
 *   star/10 star star star star php /path/to/site/console/parsing_keepalive.php >> /path/to/site/logs/parsing_keepalive.log 2>&1
 *
 * Or via browser (no server terminal):
 *   https://www.sauto.md/console/parsing_keepalive.php?token=cron2026
 *
 * It cannot create a new cookie — when a session finally dies, renew that source's
 * cookie in Parsing → Settings.
 */

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
$anyDead = false;

foreach (['openlane', 'ecarstrade'] as $source) {
    $adapter = AdapterFactory::create($source);
    if (!$adapter || !method_exists($adapter, 'pingSession')) {
        echo "$ts [$source] adapter unavailable\n";
        continue;
    }

    $res = $adapter->pingSession();
    if (!empty($res['alive'])) {
        echo "$ts [$source] keep-alive OK (session active)\n";
        continue;
    }

    // Not alive: log loudly so the operator knows to renew the cookie in Settings.
    $anyDead = true;
    $envVar = $source === 'openlane' ? 'OPENLANE_COOKIE/OPENLANE_RVT' : 'ECARSTRADE_COOKIE';
    echo "$ts [$source] session NOT active — reason: "
        . ($res['reason'] ?? 'unknown')
        . " (status " . ($res['status'] ?? '?') . "). Renew {$envVar} in Settings.\n";
}

exit($anyDead ? 2 : 0);
