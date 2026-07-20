<?php
/**
 * PHP environment probe. The site returns a blank HTTP 500 whenever the runtime
 * changes (version switch, CageFS/PHP Selector isolation) because errors are
 * hidden. This forces them visible and boots the real stack step by step,
 * printing the last step reached before the crash.
 *
 * Browser (no server terminal):
 *   https://www.sauto.md/console/php84_check.php?token=cron2026
 *
 * Run it WHILE the broken setting is active (e.g. Isolation enabled), then
 * compare against a working run. DELETE FROM SERVER once done.
 */

$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && ($_GET['token'] ?? '') !== 'cron2026') {
    http_response_code(403);
    die('Forbidden');
}
if (!$IS_CLI) header('Content-Type: text/plain; charset=utf-8');

// Override the ini which hides every fatal behind a bare 500
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', '0');

$step = 'startup';
// Fatals bypass normal output, so report the last reached step from the shutdown handler
register_shutdown_function(function () use (&$step) {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR], true)) {
        echo "\n\n!!! FATAL during step: {$step}\n";
        echo "{$e['message']}\n  in {$e['file']}:{$e['line']}\n";
    } else {
        echo "\n\nDone. Last step: {$step}\n";
    }
});

function h($t) { echo "\n=== {$t} ===\n"; }

h('Environment');
echo 'PHP        : ' . PHP_VERSION . "\n";
echo 'SAPI       : ' . php_sapi_name() . "\n";
echo 'php.ini    : ' . (php_ini_loaded_file() ?: '(none)') . "\n";
echo 'error_log  : ' . (ini_get('error_log') ?: '(default)') . "\n";
echo 'session.sp : ' . (ini_get('session.save_path') ?: '(default)') . "\n";
echo 'short_tag  : ' . (ini_get('short_open_tag') ? 'On' : 'Off') . "\n";

h('Extensions the site uses');
$needed = ['pdo_mysql', 'curl', 'mbstring', 'gd', 'dom', 'simplexml', 'zip', 'json', 'fileinfo', 'openssl', 'intl', 'session', 'iconv'];
$missing = [];
foreach ($needed as $ext) {
    $ok = extension_loaded($ext);
    if (!$ok) $missing[] = $ext;
    printf("%-12s %s\n", $ext, $ok ? 'OK' : 'MISSING  <-- enable in PHP Selector');
}
if ($missing) {
    echo "\n>>> Enable these in cPanel -> Select PHP Version -> Extensions:\n    " . implode(', ', $missing) . "\n";
}

h('Session write test');
$step = 'session_start';
// A save_path left over from another PHP build fails only here, not at boot
if (@session_start()) {
    $_SESSION['probe'] = 1;
    echo "session_start OK -> " . session_save_path() . "\n";
} else {
    echo "session_start FAILED -> save_path not writable: " . session_save_path() . "\n";
}

h('Booting the real stack');
chdir(__DIR__ . '/..');
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

$boot = [
    'environment.php'       => 'environment.php',
    'defines.php'           => _DEFAULT . '/defines.php',
    'functions.php'         => _DEFAULT . '/functions.php',
    'config.php'            => _DEFAULT . '/config.php',
    'language.php'          => _DEFAULT . '/language.php',
    'dbi.php (PDO connect)' => _DEFAULT . '/dbi.php',
    'seo.php'               => _DEFAULT . '/seo.php',
    'arrays.php'            => _DEFAULT . '/arrays.php',
];

foreach ($boot as $label => $file) {
    $step = $label;
    echo "-> {$label} ... ";
    require_once $file;
    echo "OK\n";
}

$step = 'Lang + Container';
echo "-> Lang + Container ... ";
$trans = new App\Lang($_COOKIE['lang'] ?? 'ro');
App\Core\Container::set('db', $db);
App\Core\Container::set('prefix', $prefx);
echo "OK\n";

$step = 'DB query';
echo "-> test query ... ";
$db->query('SELECT 1')->fetch();
echo "OK\n";

$step = 'complete';
