<?php
/**
 * Learns the dealers' banners from the ads already published, so the filter works
 * from the FIRST car of a dealer instead of only from the second.
 *
 * Fingerprints every photo of every published AutoTrader ad and stores it. A photo
 * of a car exists once in the whole catalogue, so any fingerprint tied to two or
 * more listings is an advert. No threshold, no AI.
 *
 * Runs in batches and continues on its own (galleries are thousands of files, one
 * request would hit the server's timeout).
 *
 *   scan   : https://www.sauto.md/console/build_banner_hashes.php?token=cron2026
 *   report : ...?token=cron2026&report=1        what was found, plus the live ads
 *   reset  : ...?token=cron2026&reset=1         start the scan over
 */
$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && (($_GET['token'] ?? '') !== 'cron2026')) { http_response_code(403); die('Forbidden'); }

define('_DOIT', 1);
define('_DEFAULT', 'content/default');
chdir(__DIR__);
spl_autoload_register(function ($c) {
    $p = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $c) . '.php';
    if (is_file($p)) require_once $p;
});
require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/defines.php');
require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');
$prefx = 'gh3sp';
\App\Core\Container::set('db', $db);
\App\Core\Container::set('prefix', $prefx);
@set_time_limit(0);

if (!function_exists('imagecreatefromstring')) { die("GD is not available.\n"); }

$isWeb = php_sapi_name() !== 'cli';
if ($isWeb) header('Content-Type: text/html; charset=utf-8');
$out = function (string $line) use ($isWeb) { echo $isWeb ? htmlspecialchars($line) . "<br>\n" : $line . "\n"; };

$table = "{$prefx}_parsing_img_hash";
if (!\App\Services\Parsing\BannerImage::ensureStore($db, $prefx)) {
    die("Could not create {$table} — check the DB user's rights.\n");
}

// ── report ──────────────────────────────────────────────────────────────────
if (($_GET['report'] ?? '') === '1') {
    // Only a picture spread over DIFFERENT cars is an advert. The same car imported
    // twice shares its whole gallery legitimately, so brand+model must differ.
    $rows = $db->query("SELECT h.hash, COUNT(DISTINCT pc.id) n,
                               COUNT(DISTINCT CONCAT(COALESCE(pc.brand,''),'|',COALESCE(pc.model,''))) models
            FROM {$table} h
            JOIN {$prefx}_parsing_cars pc ON pc.id = h.listing_id
            GROUP BY h.hash
            HAVING n >= 2 AND models >= 2
            ORDER BY models DESC, n DESC")->fetchAll(PDO::FETCH_ASSOC);
    $out("ADVERTS (same picture across different cars): " . count($rows));
    $sample = $db->prepare("SELECT DISTINCT CONCAT(COALESCE(pc.brand,'?'),' ',COALESCE(pc.model,'')) car,
                                   pc.car_ctlg_id
        FROM {$table} h JOIN {$prefx}_parsing_cars pc ON pc.id = h.listing_id
        WHERE h.hash = ? LIMIT 4");
    // Which photo of the ad is it? The table keeps only the fingerprint, so re-hash
    // the galleries of the few ads involved — enough to hand over an exact link.
    $base = rtrim(\App\Services\CarPhotoR2::base(), '/');
    $gallery = $db->prepare("SELECT pos, path, name, ff FROM {$prefx}_car_pht WHERE it_id = ? ORDER BY pos");
    $posOf = function (int $carId, string $wanted) use ($gallery, $base) {
        static $cache = [];
        if (!isset($cache[$carId])) {
            $cache[$carId] = [];
            $gallery->execute([$carId]);
            foreach ($gallery->fetchAll(PDO::FETCH_ASSOC) as $p) {
                $rel = trim((string)$p['path'], '/') . '/' . $carId . '/high/' . $p['name'] . '.' . ($p['ff'] ?: 'jpg');
                $bytes = \App\Services\CarPhotoR2::read($base . '/' . $rel);
                if ($bytes === null || $bytes === '') continue;
                $h = \App\Services\Parsing\BannerImage::fingerprint($bytes);
                if ($h !== null) $cache[$carId][$h] = (int)$p['pos'];
            }
        }
        return $cache[$carId][$wanted] ?? 0;
    };

    foreach (array_slice($rows, 0, 20) as $r) {
        $sample->execute([$r['hash']]);
        $out("  {$r['hash']} — {$r['models']} different car(s):");
        foreach ($sample->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $carId = (int)$s['car_ctlg_id'];
            $pos = $posOf($carId, (string)$r['hash']);
            $out("      " . trim($s['car']) . " — https://www.sauto.md/ro/ordercars/{$carId}"
                 . ($pos ? "  photo {$pos}" : ''));
        }
    }
    $total = (int)$db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    $out("");
    $out("fingerprints stored: {$total}");
    return;
}

// ── reset ───────────────────────────────────────────────────────────────────
if (($_GET['reset'] ?? '') === '1') {
    $db->exec("DELETE FROM {$table}");
    $db->prepare("DELETE FROM {$prefx}_settings WHERE name = 'banner_hash_scan_done'")->execute();
    $out("cleared — open this page without &reset=1 to scan again.");
    return;
}

// ── scan, one batch per request ─────────────────────────────────────────────
// Work to a TIME budget, not a car count: a gallery can be 25 photos and each one
// may come from R2 over the network, so "15 cars" is unpredictable while "15
// seconds" always fits inside the server's request timeout.
$budget = max(5, min(45, (int)($_GET['seconds'] ?? 15)));
$deadline = microtime(true) + $budget;
$batch = max(1, min(40, (int)($_GET['batch'] ?? 5)));
$after = (int)($_GET['after'] ?? 0);   // last car_ctlg_id done

$cars = $db->query("SELECT pc.id AS listing_id, pc.car_ctlg_id
    FROM {$prefx}_parsing_cars pc
    JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
    WHERE pc.source = 'autotrader' AND pc.car_ctlg_id > {$after}
    ORDER BY pc.car_ctlg_id LIMIT {$batch}")->fetchAll(PDO::FETCH_ASSOC);

if (!$cars) {
    $out("Scan finished — every published AutoTrader gallery has been fingerprinted.");
    $out("Open ...&report=1 to see the adverts it found.");
    return;
}

$base = rtrim(\App\Services\CarPhotoR2::base(), '/');
$photos = $db->prepare("SELECT pos, path, name, ff FROM {$prefx}_car_pht WHERE it_id = ? ORDER BY pos");

$done = 0; $hashed = 0; $lastId = $after; $stoppedEarly = false;
foreach ($cars as $c) {
    if (microtime(true) >= $deadline) { $stoppedEarly = true; break; }
    $carId = (int)$c['car_ctlg_id'];
    $photos->execute([$carId]);
    foreach ($photos->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $rel = trim((string)$p['path'], '/') . '/' . $carId . '/high/' . $p['name'] . '.' . ($p['ff'] ?: 'jpg');
        $bytes = \App\Services\CarPhotoR2::read($base . '/' . $rel);
        if ($bytes === null || $bytes === '') continue;
        $hash = \App\Services\Parsing\BannerImage::fingerprint($bytes);
        if ($hash === null) continue;
        \App\Services\Parsing\BannerImage::remember($db, $prefx, $hash, (int)$c['listing_id']);
        $hashed++;
    }
    // Only move the marker past a car that was finished, so nothing is skipped.
    $lastId = $carId;
    $done++;
}

$remaining = (int)$db->query("SELECT COUNT(*) FROM {$prefx}_parsing_cars pc
    JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
    WHERE pc.source = 'autotrader' AND pc.car_ctlg_id > {$lastId}")->fetchColumn();

$out("done {$done} ad(s), {$hashed} photo(s) fingerprinted — {$remaining} ad(s) left");

if ($done === 0 && $stoppedEarly) {
    $out("The time budget ran out before a single gallery finished — try &seconds=30.");
}

if ($isWeb) {
    // Continue by itself: each request stays well inside the server's timeout.
    $next = '?token=cron2026&batch=' . $batch . '&seconds=' . $budget . '&after=' . $lastId;
    echo '<meta http-equiv="refresh" content="1;url=' . htmlspecialchars($next) . '">';
    echo '<p>continuing…</p>';
} elseif ($remaining > 0) {
    $out("run again with &after={$lastId}");
}
