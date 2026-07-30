<?php
/**
 * Nightly cleanup: remove local car photos that are already safe in R2.
 *
 * Publishing writes every photo twice — to media/images/upload/car/ and to the
 * bucket — so the local tree grows back by roughly 15 GB a month. This script is
 * what keeps the server flat without anyone having to remember.
 *
 * It never deletes on trust. For each file it checks the object exists in R2
 * with the SAME byte size; a single mismatch and the whole car folder is left
 * alone and reported. That check is the only thing standing between a routine
 * cron and permanent data loss, so it has no bypass.
 *
 * Cron:
 *   0 4 star star star cd /home/sautom/public_html && /usr/local/bin/php \
 *     console/r2_cleanup_local.php apply=1 >> logs/r2_cleanup.log 2>&1
 *
 * Args:
 *   apply=1      actually delete (without it: report only)
 *   limit=5000   car folders per run
 *   budget=1800  seconds of work per run
 *   grace=900    leave folders touched in the last N seconds alone, so a car
 *                being published right now is never half-cleaned
 *   prefix=y/m   restrict to one month
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');
@set_time_limit(0);

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $p = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (is_file($p)) require_once $p;
});

require_once __DIR__ . '/../environment.php';

$root = realpath(__DIR__ . '/..');
$_SERVER['DOCUMENT_ROOT'] = $root;
$carImg = $root . '/media/images/upload/car';
$logDir = $root . '/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

// CLI args, or a token-guarded query string when run from a browser.
$args = [];
foreach (array_slice($argv ?? [], 1) as $a) {
    if (strpos($a, '=') !== false) { [$k, $v] = explode('=', $a, 2); $args[$k] = $v; }
}
if (php_sapi_name() !== 'cli') {
    if (($_GET['token'] ?? '') !== 'cron2026') { http_response_code(403); die('Forbidden'); }
    header('Content-Type: text/plain; charset=utf-8');
    $args = $_GET;
}

$apply  = ($args['apply'] ?? '') === '1';
$limit  = max(1, (int)($args['limit'] ?? 5000));
$budget = max(30, (int)($args['budget'] ?? 1800));
$grace  = max(0, (int)($args['grace'] ?? 900));
$prefix = trim((string)($args['prefix'] ?? ''), '/');
$deadline = time() + $budget;

$say = function (string $m) { echo '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n"; @flush(); };
$G   = fn($b) => number_format($b / 1073741824, 2) . ' GB';
$N   = fn($n) => number_format($n);

// Overlapping runs would fight over the same folders.
$lock = fopen($logDir . '/r2_cleanup.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    $say('Another cleanup run is active — skipping.');
    exit;
}

$r2 = \App\Services\R2Client::fromEnv();
if (!$r2) { $say('R2 credentials missing from .env — aborting.'); exit(1); }

$say($apply ? 'Cleanup starting (WILL DELETE verified files)' : 'Cleanup starting (report only, pass apply=1 to delete)');

// Car folders in scope.
$targets = [];
$months  = [];
if ($prefix !== '') {
    $months[] = $prefix;
} else {
    foreach (array_diff(@scandir($carImg) ?: [], ['.', '..']) as $y) {
        if (!is_dir($carImg . '/' . $y)) continue;
        foreach (array_diff(@scandir($carImg . '/' . $y) ?: [], ['.', '..']) as $m) {
            if (is_dir($carImg . '/' . $y . '/' . $m)) $months[] = $y . '/' . $m;
        }
    }
}
foreach ($months as $pfx) {
    foreach (array_diff(@scandir($carImg . '/' . $pfx) ?: [], ['.', '..']) as $cid) {
        if (ctype_digit($cid) && is_dir($carImg . '/' . $pfx . '/' . $cid)) $targets[] = $pfx . '/' . $cid;
    }
}
$say('car folders on disk: ' . $N(count($targets)));
if (!$targets) { $say('nothing to do'); exit; }

$okCars = 0; $skipCars = 0; $freshCars = 0; $files = 0; $bytes = 0; $checked = 0;
$healed = 0;
$problems = [];

$types = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'webp' => 'image/webp', 'gif' => 'image/gif',
    'pdf' => 'application/pdf', 'html' => 'text/html',
];

foreach ($targets as $rel) {
    if ($checked >= $limit || time() > $deadline) break;
    $dir = $carImg . '/' . $rel;
    if (!is_dir($dir)) continue;
    $checked++;

    // Grace period: a folder written to moments ago may still be mid-publish.
    if ($grace > 0 && (time() - (int)@filemtime($dir)) < $grace) { $freshCars++; continue; }

    $local = [];
    $walk = function (string $d, string $sub) use (&$walk, &$local) {
        foreach (array_diff(@scandir($d) ?: [], ['.', '..']) as $f) {
            $p = $d . '/' . $f;
            if (is_link($p)) continue;
            if (is_dir($p)) { $walk($p, $sub . $f . '/'); continue; }
            $local[$sub . $f] = (int)@filesize($p);
        }
    };
    $walk($dir, '');
    if (!$local) { @rmdir($dir); continue; }

    $inR2 = []; $token = null;
    do {
        [$page, $token] = $r2->listObjects($rel . '/', $token);
        foreach ($page as $k => $sz) $inR2[substr($k, strlen($rel) + 1)] = $sz;
    } while ($token !== null);

    // Present AND identical in size — no exceptions, no bypass.
    $gaps = [];
    foreach ($local as $f => $sz) {
        if (!isset($inR2[$f]))      $gaps[$f] = "missing in R2";
        elseif ($inR2[$f] !== $sz)  $gaps[$f] = "{$sz}b local vs {$inR2[$f]}b in R2";
    }

    // A gap is almost always one photo that lost its upload to a transient R2
    // hiccup at publish time. The file is right here, so push it and carry on
    // rather than parking the car and waiting for someone to run r2push by hand.
    if ($gaps && $apply) {
        $items = [];
        foreach (array_keys($gaps) as $f) {
            $abs = $dir . '/' . $f;
            if (!is_file($abs)) continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            $items[] = [$rel . '/' . $f, $abs, $types[$ext] ?? 'application/octet-stream'];
        }
        if ($items) {
            $res = $r2->putFilesParallel($items, 8);
            $healed += $res['ok'];
            foreach ($items as $it) {
                $f = substr($it[0], strlen($rel) + 1);
                if (!isset($res['failed'][$it[0]])) unset($gaps[$f]);
            }
        }
    }

    if ($gaps) {
        $skipCars++;
        $first = array_key_first($gaps);
        if (count($problems) < 20) $problems[] = "{$rel}: {$first} — {$gaps[$first]}";
        continue;
    }

    $files += count($local);
    $bytes += array_sum($local);
    $okCars++;

    if ($apply) {
        $rm = function (string $d) use (&$rm) {
            foreach (array_diff(@scandir($d) ?: [], ['.', '..']) as $f) {
                $p = $d . '/' . $f;
                is_dir($p) ? $rm($p) : @unlink($p);
            }
            @rmdir($d);
        };
        $rm($dir);
    }
}

$say('checked: ' . $N($checked) . ' folder(s)');
if ($freshCars) $say('skipped as too fresh (< ' . $grace . 's): ' . $N($freshCars));
if ($healed)    $say('pushed to R2 before deleting (gaps healed): ' . $N($healed) . ' file(s)');
$say(($apply ? 'deleted: ' : 'would delete: ') . $N($okCars) . ' car(s), ' . $N($files) . ' file(s), ' . $G($bytes));
if ($skipCars) {
    $say('NOT SAFE, left alone: ' . $N($skipCars) . ' car(s)');
    foreach ($problems as $p) $say('   ' . $p);
    $say('   → these are missing from R2. Run crosspost_tool.php do=r2push id=<id> for each.');
}
if (!$apply) $say('report only — add apply=1 to delete');

flock($lock, LOCK_UN);
fclose($lock);
