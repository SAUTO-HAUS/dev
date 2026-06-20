<?php
/**
 * Parsing publish-queue worker (sauto).
 *
 * Drains gh3sp_parsing_publish_queue: each pending job is published via
 * ParsingPublisher (catalog insert + photos). Runs sequentially so many enqueued
 * cars don't hammer the server, and so the operator can leave the admin page
 * while publishing continues here.
 *
 * Started two ways:
 *   - Web kick (fire-and-forget):  console/parsing_publish_worker_web.php?token=cron2026
 *   - Cron fallback (every minute): php /path/to/site/console/parsing_publish_worker.php
 * A lock prevents two workers running at once, so both triggers are safe.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');

// No time limit: a batch of cars (each downloading + resizing many photos) can
// run for minutes. Without this the process can die mid-batch, leaving later
// jobs unpublished.
set_time_limit(0);
@ini_set('memory_limit', '512M');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $classPath = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) require_once $classPath;
});

require_once __DIR__ . '/../environment.php';
require_once ('../' . _DEFAULT . '/defines.php');   // _CAR_IMG for photo paths
require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');

use App\Core\Container;
use App\Services\Parsing\PublishQueue;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');

Container::set('db', $db);
Container::set('prefix', 'gh3sp');

// One worker at a time.
$lockFile = __DIR__ . '/../logs/parsing_publish_worker.lock';
$lockHandle = fopen($lockFile, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another worker is active — skipping.\n";
    exit;
}
register_shutdown_function(function () use ($lockHandle) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
});

echo "[" . date('Y-m-d H:i:s') . "] Publish worker started\n";
try {
    $summary = (new PublishQueue())->processAll();
    echo "[" . date('Y-m-d H:i:s') . "] Done: published={$summary['done']} failed={$summary['failed']}\n";
} catch (Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . "\n";
}
echo "[" . date('Y-m-d H:i:s') . "] Publish worker finished\n";
