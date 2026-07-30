<?php
/**
 * Copy every car photo from media/images/upload/car/ into Cloudflare R2.
 *
 * Filesystem-driven on purpose: walking the folders (rather than car_pht rows)
 * also picks up /doc/ PDFs and index.html, which have no DB row — nothing may be
 * left behind, because the whole point is to delete the local copies afterwards.
 *
 * Resumable: the folder list is built once into logs/r2_migrate.queue and a
 * position pointer advances through it, so a cron can chip away at it.
 *
 * Cron (every 5 min, flock keeps runs from overlapping):
 *   star/5 star star star star cd /home/sautom/public_html && /usr/local/bin/php \
 *     console/r2_migrate.php >> logs/r2_migrate.log 2>&1
 *
 * Args:  budget=240   seconds of work per run
 *        conc=10      parallel uploads
 *        rebuild=1    rebuild the queue and start over
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');
@set_time_limit(0);
// The verify pass holds every R2 key in one array (~1.1M entries). Raise it here
// rather than relying on .htaccess: under CloudLinux Isolation those php_value
// lines are ignored.
@ini_set('memory_limit', '1024M');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $p = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (is_file($p)) require_once $p;
});

require_once __DIR__ . '/../environment.php';
require('../' . _DEFAULT . '/dbi.php');

$root = realpath(__DIR__ . '/..');
$_SERVER['DOCUMENT_ROOT'] = $root;

$carImg   = $root . '/media/images/upload/car';
$logDir   = $root . '/logs';
$queueF   = $logDir . '/r2_migrate.queue';
$stateF   = $logDir . '/r2_migrate.state';
$failF    = $logDir . '/r2_migrate_failures.log';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

// Parse "key=value" args (CLI) or query string (browser, token-guarded).
$args = [];
foreach (array_slice($argv ?? [], 1) as $a) {
    if (strpos($a, '=') !== false) { [$k, $v] = explode('=', $a, 2); $args[$k] = $v; }
}
if (php_sapi_name() !== 'cli') {
    if (($_GET['token'] ?? '') !== 'cron2026') { http_response_code(403); die('Forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
    $args = $_GET;
}
$budget = max(30, (int)($args['budget'] ?? 240));
$conc   = max(1, min(32, (int)($args['conc'] ?? 10)));
$deadline = time() + $budget;

// flush() on every line: with stdout redirected to a log, PHP block-buffers the
// output and a long pass looks frozen until it finishes.
$say = function (string $m) { echo '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n"; @flush(); };

// Read-only progress, checked BEFORE the lock so it works while the cron runs.
if (($args['status'] ?? '') === '1') {
    $st = is_file($stateF) ? json_decode((string)file_get_contents($stateF), true) : null;
    $q  = is_file($queueF) ? count(file($queueF, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : 0;
    if (!is_array($st) || $q === 0) { $say('No migration state yet.'); exit; }
    $pct  = round($st['pos'] / $q * 100, 1);
    $gb   = $st['bytes'] / 1073741824;
    $rate = 0;
    if (!empty($st['started'])) {
        $elapsed = max(1, time() - strtotime($st['started']));
        $rate = $gb / ($elapsed / 3600); // GB per hour, averaged over the whole run
    }
    if ($st['pos'] >= $q) {
        echo "\n";
        echo "  ============================================\n";
        echo "   MIGRATION COMPLETE\n";
        echo "  ============================================\n";
        echo '   folders:  ' . number_format($st['pos']) . "\n";
        echo '   objects:  ' . number_format($st['objects']) . "\n";
        echo '   uploaded: ' . number_format($gb, 2) . " GB\n";
        echo '   failures: ' . (int)$st['failed']
            . ((int)$st['failed'] > 0 ? '  ← see logs/r2_migrate_failures.log' : '  ✓') . "\n";
        echo "\n   Next: turn the */5 cron OFF, then run the verify pass\n";
        echo "   before anything is deleted locally.\n\n";
        exit;
    }

    $say("progress: {$st['pos']}/{$q} folders ({$pct}%)");
    $say('uploaded: ' . number_format($st['objects']) . ' objects, ' . number_format($gb, 2) . ' GB'
        . ($st['failed'] ? ", {$st['failed']} FAILED" : ''));
    if ($rate > 0) {
        $left = $gb / max(0.01, $st['pos']) * ($q - $st['pos']);
        $say('rate: ' . number_format($rate, 1) . ' GB/h — about '
            . number_format($left / max(0.01, $rate), 1) . ' h left');
    }
    exit;
}

// ── verify ───────────────────────────────────────────────────────────────────
// The gate before anything is deleted locally. Lists the WHOLE bucket once, then
// walks the disk fresh (not the old queue, which was a snapshot — cars published
// during the migration are not in it) and re-uploads whatever is missing or the
// wrong size. Also reports keys in R2 whose car no longer exists on disk.
//   ?verify=1            check + upload what is missing
//   &dry=1               report only, upload nothing
if (($args['verify'] ?? '') === '1') {
    $dry = ($args['dry'] ?? '') === '1';

    // Listing the bucket takes minutes, so a */5 cron would stack runs on top of
    // each other. Second one just leaves.
    $vlock = fopen($logDir . '/r2_verify.lock', 'c');
    if ($vlock === false || !flock($vlock, LOCK_EX | LOCK_NB)) {
        $say('A verify pass is already running — skipping.');
        exit;
    }

    $r2v = \App\Services\R2Client::fromEnv();
    if (!$r2v) { $say('R2 credentials missing — aborting.'); exit(1); }

    $say('Listing the whole bucket … (1000 keys per request)');
    $inR2 = []; $token = null; $pages = 0;
    do {
        [$page, $token] = $r2v->listObjects('', $token);
        foreach ($page as $k => $sz) $inR2[$k] = $sz;
        if (++$pages % 100 === 0) $say('  … ' . number_format(count($inR2)) . ' keys');
    } while ($token !== null);
    $say('R2 holds ' . number_format(count($inR2)) . ' object(s).');

    $types = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif',
        'pdf' => 'application/pdf', 'html' => 'text/html',
    ];

    $missing = []; $wrongSize = 0; $onDisk = 0;
    $scan = function (string $absDir, string $relDir) use (&$scan, &$missing, &$wrongSize, &$onDisk, $inR2, $types) {
        foreach (array_diff(@scandir($absDir) ?: [], ['.', '..']) as $f) {
            $abs = $absDir . '/' . $f;
            if (is_link($abs)) continue;
            if (is_dir($abs)) { $scan($abs, $relDir . '/' . $f); continue; }
            $onDisk++;
            $key  = $relDir . '/' . $f;
            $size = (int)@filesize($abs);
            if (!isset($inR2[$key])) {
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                $missing[] = [$key, $abs, $types[$ext] ?? 'application/octet-stream'];
            } elseif ($inR2[$key] !== $size) {
                $wrongSize++;
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                $missing[] = [$key, $abs, $types[$ext] ?? 'application/octet-stream'];
            }
        }
    };

    $say('Walking the disk …');
    foreach (array_diff(@scandir($carImg) ?: [], ['.', '..']) as $y) {
        if (!is_dir($carImg . '/' . $y)) continue;
        foreach (array_diff(@scandir($carImg . '/' . $y) ?: [], ['.', '..']) as $m) {
            $mp = $carImg . '/' . $y . '/' . $m;
            if (!is_dir($mp)) continue;
            foreach (array_diff(@scandir($mp) ?: [], ['.', '..']) as $cid) {
                if (!ctype_digit($cid) || !is_dir($mp . '/' . $cid)) continue;
                $scan($mp . '/' . $cid, $y . '/' . $m . '/' . $cid);
            }
        }
    }

    // Keys in R2 with no file on disk: cars deleted after their photos were
    // copied. Harmless but paid for, so they get reported for a later cleanup.
    $stale = count($inR2) - ($onDisk - count($missing));

    echo "\n";
    $say('files on disk : ' . number_format($onDisk));
    $say('objects in R2 : ' . number_format(count($inR2)));
    $say('MISSING       : ' . number_format(count($missing) - $wrongSize));
    $say('wrong size    : ' . number_format($wrongSize));
    $say('stale in R2   : ' . number_format(max(0, $stale)) . '  (car deleted since — cleanup later)');

    if (!$missing) { $say('✓ every local file is in R2, byte-for-byte.'); exit; }
    if ($dry)      { $say('Dry run — nothing uploaded. Drop &dry=1 to fix.'); exit; }

    $say('Uploading ' . number_format(count($missing)) . ' file(s) …');
    $res = $r2v->putFilesParallel($missing, $conc);
    $say('uploaded ' . number_format($res['ok']) . ', ' . number_format($res['bytes'] / 1048576, 1) . ' MB');
    if ($res['failed']) {
        $say('STILL FAILING: ' . count($res['failed']));
        foreach (array_slice($res['failed'], 0, 10, true) as $k => $why) $say("   {$k} :: {$why}");
        $say('Re-run the verify pass; do NOT delete anything locally yet.');
    } else {
        $say('✓ all gaps closed — re-run with &dry=1 to confirm a clean pass.');
    }
    exit;
}

// One run at a time — a second run would re-upload the same folders.
$lock = fopen($logDir . '/r2_migrate.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    $say('Another run is active — skipping.');
    exit;
}

$r2 = \App\Services\R2Client::fromEnv();
if (!$r2) { $say('R2 credentials missing from .env — aborting.'); exit(1); }

// ── queue ────────────────────────────────────────────────────────────────────
if (($args['rebuild'] ?? '') === '1') { @unlink($queueF); @unlink($stateF); }

if (!is_file($queueF)) {
    $say('Building folder queue …');
    $fh = fopen($queueF, 'w');
    $n = 0;
    foreach (array_diff(@scandir($carImg) ?: [], ['.', '..']) as $y) {
        if (!is_dir($carImg . '/' . $y)) continue;
        foreach (array_diff(@scandir($carImg . '/' . $y) ?: [], ['.', '..']) as $m) {
            $mp = $carImg . '/' . $y . '/' . $m;
            if (!is_dir($mp)) continue;
            foreach (array_diff(@scandir($mp) ?: [], ['.', '..']) as $cid) {
                if (!ctype_digit($cid) || !is_dir($mp . '/' . $cid)) continue;
                fwrite($fh, $y . '/' . $m . '/' . $cid . "\n");
                $n++;
            }
        }
    }
    fclose($fh);
    $say("Queue built: {$n} car folder(s).");
}

$state = is_file($stateF) ? json_decode((string)file_get_contents($stateF), true) : null;
if (!is_array($state)) {
    $state = ['pos' => 0, 'folders' => 0, 'objects' => 0, 'bytes' => 0, 'failed' => 0, 'started' => date('c')];
}

$queue = file($queueF, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
$total = count($queue);
if ($state['pos'] >= $total) {
    $say("Nothing left: {$state['folders']} folder(s), {$state['objects']} object(s), "
        . number_format($state['bytes'] / 1073741824, 2) . ' GB uploaded.');
    exit;
}

// ── work ─────────────────────────────────────────────────────────────────────
$types = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'webp' => 'image/webp', 'gif' => 'image/gif',
    'pdf' => 'application/pdf', 'html' => 'text/html',
];

// Every file under a car folder, keyed by its path relative to the car-img root
// so the R2 key matches the public URL with only the prefix stripped.
$collect = function (string $absDir, string $relDir) use (&$collect, $types): array {
    $out = [];
    foreach (array_diff(@scandir($absDir) ?: [], ['.', '..']) as $f) {
        $abs = $absDir . '/' . $f;
        if (is_link($abs)) continue;
        if (is_dir($abs)) { $out = array_merge($out, $collect($abs, $relDir . '/' . $f)); continue; }
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        $out[] = [$relDir . '/' . $f, $abs, $types[$ext] ?? 'application/octet-stream'];
    }
    return $out;
};

$runFolders = 0; $runObjects = 0; $runBytes = 0; $runFailed = 0;

while ($state['pos'] < $total && time() < $deadline) {
    $rel = $queue[$state['pos']];
    $abs = $carImg . '/' . $rel;

    if (is_dir($abs)) {
        $items = $collect($abs, $rel);
        if ($items) {
            $res = $r2->putFilesParallel($items, $conc);
            $runObjects += $res['ok'];
            $runBytes   += $res['bytes'];
            if ($res['failed']) {
                $runFailed += count($res['failed']);
                foreach ($res['failed'] as $k => $why) {
                    @file_put_contents($failF, date('c') . " {$k} :: {$why}\n", FILE_APPEND);
                }
            }
        }
    }
    // A folder deleted since the queue was built is simply skipped — the pointer
    // must still advance or the run would spin on it forever.
    $state['pos']++;
    $runFolders++;
}

$state['folders'] += $runFolders;
$state['objects'] += $runObjects;
$state['bytes']   += $runBytes;
$state['failed']  += $runFailed;
file_put_contents($stateF, json_encode($state), LOCK_EX);

$pct  = $total > 0 ? round($state['pos'] / $total * 100, 1) : 100;
$left = $total - $state['pos'];
$say("+{$runFolders} folder(s), +{$runObjects} object(s), "
    . number_format($runBytes / 1048576, 1) . ' MB'
    . ($runFailed ? " , {$runFailed} FAILED" : ''));
$say("progress: {$state['pos']}/{$total} ({$pct}%) — {$left} folder(s) left, total "
    . number_format($state['bytes'] / 1073741824, 2) . ' GB / ' . $state['objects'] . ' objects'
    . ($state['failed'] ? " , {$state['failed']} failures (see logs/r2_migrate_failures.log)" : ''));

flock($lock, LOCK_UN);
fclose($lock);
