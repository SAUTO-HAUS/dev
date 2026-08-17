<?php
/**
 * Removes the dealer adverts from ads ALREADY published, using the fingerprints
 * build_banner_hashes.php collected: a picture that appears under cars of different
 * makes is an advert, so it is deleted wherever it sits.
 *
 * Deletes the gallery row, the local high/med files and the R2 copies, then renumbers
 * so positions stay 1..N and the first photo is the cover.
 *
 * Runs in batches on a time budget and continues on its own.
 *
 *   preview : https://www.sauto.md/console/clean_banners.php?token=cron2026
 *   apply   : ...&apply=1
 *   one ad  : ...&car=<car_ctlg_id>&apply=1
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
$out = function (string $l) use ($isWeb) { echo $isWeb ? htmlspecialchars($l) . "<br>\n" : $l . "\n"; };

$apply  = (($_GET['apply'] ?? '') === '1');
$oneCar = (int)($_GET['car'] ?? 0);
$after  = (int)($_GET['after'] ?? 0);
$budget = max(5, min(45, (int)($_GET['seconds'] ?? 15)));
$deadline = microtime(true) + $budget;

$table = "{$prefx}_parsing_img_hash";

// The advert list: one picture, several DIFFERENT cars. Same rule as the report.
$adverts = [];
foreach ($db->query("SELECT h.hash
        FROM {$table} h
        JOIN {$prefx}_parsing_cars pc ON pc.id = h.listing_id
        GROUP BY h.hash
        HAVING COUNT(DISTINCT pc.id) >= 2
           AND COUNT(DISTINCT CONCAT(COALESCE(pc.brand,''),'|',COALESCE(pc.model,''))) >= 2
    ")->fetchAll(PDO::FETCH_COLUMN) as $h) {
    $adverts[$h] = true;
}
if (!$adverts) { $out("No adverts known yet — run build_banner_hashes.php first."); return; }
$out("Known adverts: " . count($adverts) . ($apply ? " — DELETING" : " — preview"));

$sql = "SELECT pc.car_ctlg_id FROM {$prefx}_parsing_cars pc
        JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
        WHERE pc.source = 'autotrader' AND pc.car_ctlg_id > " . ($oneCar > 0 ? 0 : $after);
if ($oneCar > 0) $sql .= " AND pc.car_ctlg_id = {$oneCar}";
$sql .= " GROUP BY pc.car_ctlg_id ORDER BY pc.car_ctlg_id LIMIT 200";
$cars = $db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
if (!$cars) { $out("Nothing left to check."); return; }

$base = rtrim(\App\Services\CarPhotoR2::base(), '/');
$photosStmt = $db->prepare("SELECT id, pos, main, path, name, ff FROM {$prefx}_car_pht
                            WHERE it_id = ? ORDER BY pos ASC, id ASC");
$delStmt = $db->prepare("DELETE FROM {$prefx}_car_pht WHERE id = ?");
$posStmt = $db->prepare("UPDATE {$prefx}_car_pht SET pos = ?, main = ? WHERE id = ?");

$checked = 0; $hit = 0; $removed = 0; $lastId = $after; $stopped = false;

foreach ($cars as $carId) {
    if (microtime(true) >= $deadline) { $stopped = true; break; }
    $carId = (int)$carId;
    $photosStmt->execute([$carId]);
    $photos = $photosStmt->fetchAll(PDO::FETCH_ASSOC);
    $checked++;
    if (count($photos) < 2) { $lastId = $carId; continue; }

    $drop = [];
    foreach ($photos as $p) {
        $rel = trim((string)$p['path'], '/') . '/' . $carId . '/high/' . $p['name'] . '.' . ($p['ff'] ?: 'jpg');
        $bytes = \App\Services\CarPhotoR2::read($base . '/' . $rel);
        if ($bytes === null || $bytes === '') continue;          // unreadable → keep
        $hash = \App\Services\Parsing\BannerImage::fingerprint($bytes);
        if ($hash !== null && isset($adverts[$hash])) $drop[] = $p;
    }

    if ($drop && count($drop) < count($photos)) {
        $hit++;
        $out("  ad {$carId} — " . count($drop) . " advert(s) at photo "
             . implode(', ', array_column($drop, 'pos')));
        if ($apply) {
            foreach ($drop as $p) {
                $stem = trim((string)$p['path'], '/') . '/' . $carId;
                foreach (['high', 'med'] as $size) {
                    $abs = $base . '/' . $stem . '/' . $size . '/' . $p['name'] . '.' . ($p['ff'] ?: 'jpg');
                    if (is_file($abs)) @unlink($abs);
                    try { \App\Services\CarPhotoR2::delete($abs); } catch (\Throwable $e) {}
                }
                $delStmt->execute([$p['id']]);
                $removed++;
            }
            // Close the gaps and keep photo 1 as the cover.
            $photosStmt->execute([$carId]);
            $pos = 0;
            foreach ($photosStmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
                $pos++;
                $posStmt->execute([$pos, $pos === 1 ? 1 : 0, $p['id']]);
            }
        }
    }
    $lastId = $carId;
}

$out("checked {$checked} ad(s), {$hit} with adverts" . ($apply ? ", {$removed} photo(s) deleted" : ''));

if ($oneCar > 0) return;

$left = (int)$db->query("SELECT COUNT(DISTINCT pc.car_ctlg_id) FROM {$prefx}_parsing_cars pc
    JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
    WHERE pc.source = 'autotrader' AND pc.car_ctlg_id > {$lastId}")->fetchColumn();
$out("{$left} ad(s) left");

if ($isWeb && $left > 0) {
    $next = '?token=cron2026&seconds=' . $budget . '&after=' . $lastId . ($apply ? '&apply=1' : '');
    echo '<meta http-equiv="refresh" content="1;url=' . htmlspecialchars($next) . '">';
    echo '<p>continuing…</p>';
} elseif (!$isWeb && $left > 0) {
    $out("run again with &after={$lastId}");
} else {
    $out($apply ? "done" : "add &apply=1 to delete them");
}
