<?php

if (php_sapi_name() !== 'cli' && (($_GET['token'] ?? '') !== 'cron2026')) {
    http_response_code(403);
    die('Forbidden');
}

error_reporting(E_ALL);
date_default_timezone_set('Europe/Chisinau');
set_time_limit(0);
@ini_set('memory_limit', '512M');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $p = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($p)) require_once $p;
});

require_once __DIR__ . '/../environment.php';
require_once('../' . _DEFAULT . '/defines.php');
require('../' . _DEFAULT . '/dbi.php');
require_once('../' . _DEFAULT . '/functions.php');

use App\Core\Container;
use App\Services\Parsing\PublishQueue;

// Under CLI this is an EMPTY STRING, not missing, so `?? fallback` never fires —
// the photo import would then write to a path that does not exist.
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');

$prefx = 'gh3sp';
Container::set('db', $db);
Container::set('prefix', $prefx);

if (php_sapi_name() !== 'cli') header('Content-Type: text/plain; charset=utf-8');

$lockFile = __DIR__ . '/../logs/parsing_fix_missing_photos.lock';
@mkdir(dirname($lockFile), 0755, true);
$lockHandle = fopen($lockFile, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "another sweep is running\n";
    exit;
}
register_shutdown_function(function () use ($lockHandle) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
});

$dry = (($_GET['dry'] ?? '') === '1') || in_array('dry', array_slice($argv ?? [], 1), true);
$CAP       = 40;   // enqueue at most this many per run, so a bad day cannot flood the queue
$GRACE_MIN = 20;   // a car published minutes ago may still be having its photos imported

$log = function (string $line) {
    $stamp = date('Y-m-d H:i:s') . ' ' . $line;
    echo $stamp . "\n";
    @file_put_contents(__DIR__ . '/../logs/parsing_fix_missing_photos.log',
        $stamp . "\n", FILE_APPEND | LOCK_EX);
};

$C = $prefx . '_car_ctlg';
$P = $prefx . '_parsing_cars';

// Two flat index scans and a diff in PHP. A LEFT JOIN would expand to one row per
// PHOTO (over a million) before discarding all but a handful, and a correlated
// NOT EXISTS re-probes per car; both made this time out.
$cutoff = time() - $GRACE_MIN * 60;
$carIds = $db->query("SELECT id FROM {$C}
                      WHERE parsing_id IS NOT NULL AND `vis` = '1' AND `act` = '1'
                        AND `date` < {$cutoff}")->fetchAll(PDO::FETCH_COLUMN);
$withPht = $db->query("SELECT DISTINCT it_id FROM {$prefx}_car_pht")->fetchAll(PDO::FETCH_COLUMN);

$have    = array_flip(array_map('intval', $withPht));
$missing = [];
foreach ($carIds as $cid) { if (!isset($have[(int)$cid])) $missing[] = (int)$cid; }
unset($carIds, $withPht, $have);

if (!$missing) {
    $log("ok — no live ad has an empty gallery");
    exit;
}

// Only cars whose source URLs we still hold can be repaired; the rest need a
// human (there is nothing to re-import) and are just reported.
$in = implode(',', array_fill(0, count($missing), '?'));
$st = $db->prepare("SELECT c.id, c.parsing_id, c.br_nm, c.mo_nm, p.images_local, p.source
                    FROM {$C} c JOIN {$P} p ON p.id = c.parsing_id
                    WHERE c.id IN ({$in}) ORDER BY c.id DESC");
$st->execute($missing);

$queue = new PublishQueue();
$fixable = 0; $hopeless = 0; $queued = 0;

foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $imgs = json_decode((string)$r['images_local'], true);
    if (!is_array($imgs) || !$imgs) { $hopeless++; continue; }
    $fixable++;
    if ($queued >= $CAP) continue;

    $label = "ctlg {$r['id']} (parsing {$r['parsing_id']}) {$r['br_nm']} {$r['mo_nm']} — "
           . count($imgs) . " photos to import";
    if ($dry) { $log("would re-queue: {$label}"); $queued++; continue; }

    $queue->clearAutoPublishFailed((int)$r['parsing_id']);
    $queue->enqueue((int)$r['parsing_id'], 'sauto');
    $queued++;
    $log("re-queued: {$label}");
}

$log(($dry ? "DRY RUN — " : "")
   . "live ads with no photos: " . count($missing)
   . " | repairable: {$fixable} | queued now: {$queued}"
   . " | need a human (no stored URLs): {$hopeless}");
