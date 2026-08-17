<?php
/**
 * SAUTO Personal Scheduling Cron Job
 * Runs every minute to check and republish scheduled SAUTO Personal ads
 */

// Security check - only allow CLI execution or manual trigger
if (php_sapi_name() !== 'cli' && !defined('MANUAL_CRON_TRIGGER')) {
    http_response_code(403);
    die('This script can only be run from command line or with proper access');
}

// Set error reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Europe/Chisinau');

// One run at a time. A run that uploads images can outlast the minute between crons,
// and two overlapping runs double the request rate on an IP 999 already rate-limits.
//
// Two ways this used to stop publishing for days without a word:
//   - fopen() failing (the lock file left behind by another user, /tmp not writable)
//     was treated exactly like "busy", so EVERY run exited immediately;
//   - a run hung inside an upload kept the lock forever, and nothing else ever ran.
// So: a lock we cannot take is a warning, not a stop, and a lock older than 30 minutes
// belongs to a run that is not coming back.
$lockFile = sys_get_temp_dir() . '/sauto_personal_cron.lock';
$lockFp = @fopen($lockFile, 'c');
if ($lockFp === false) {
    echo "[" . date('Y-m-d H:i:s') . "] WARNING: cannot open the lock file {$lockFile} — running without a lock\n";
} elseif (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    $age = time() - (int)@filemtime($lockFile);
    if ($age < 1800) {
        echo "[" . date('Y-m-d H:i:s') . "] Another run is still working (" . max(0, $age) . "s) — skipping this one\n";
        exit(0);
    }
    echo "[" . date('Y-m-d H:i:s') . "] WARNING: the lock has been held for {$age}s — the previous run is stuck, taking over\n";
} else {
    // Keep the mtime fresh so the staleness check above measures THIS run, not the
    // moment the file was first created.
    @touch($lockFile);
}

// USA import country id, resolved by code (Korea's 41 stays a legacy constant).
function usaCountryId($db) {
    static $id = null;
    if ($id === null) {
        $id = 0;
        try {
            // CA first: AutoTrader is autotrader.ca and the region was renamed to
            // Canada. US is still accepted below for everything published earlier.
            $id = (int)($db->query("SELECT id FROM countries WHERE code = 'CA' LIMIT 1")->fetchColumn() ?: 0);
            if (!$id) {
                $id = (int)($db->query("SELECT id FROM countries WHERE code = 'US' LIMIT 1")->fetchColumn() ?: 0);
            }
        } catch (\Throwable $e) { /* stays 0 → never matches */ }
    }
    return $id;
}

function enforcePhoneForAccount($features, $accountId) {
    $phoneMap = [
        2 => '37379600616',
        3 => '37379600326',
        4 => '37379603161',
        5 => '37378004642',
    ];
    if (empty($phoneMap[$accountId])) {
        return $features;
    }
    $phone = $phoneMap[$accountId];
    $found = false;
    foreach ($features as $idx => $f) {
        if ((string)($f['id'] ?? '') === '16') {
            $features[$idx]['value'] = [$phone];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $features[] = ['id' => '16', 'value' => [$phone]];
    }
    echo "[" . date('Y-m-d H:i:s') . "] Phone enforced for account {$accountId}: {$phone}\n";
    return $features;
}

// Accounts whose image allowance may be borrowed when the advert's own account is
// throttled. Account 2 is in the list because it has the same 1200/day and usually
// publishes few commercial ads — lending is safe now that a lender only gives away
// what its own waiting queue does not need (see imagesReservedForOwnQueue).
const IMAGE_UPLOAD_FALLBACKS = [3, 4, 5, 2];

// What one 999 account is allowed to upload per day. Four accounts × 1200 = the 4800
// images/day the catalog needs. Counted for real (see imagesUploadedToday), so an
// account is only skipped when it genuinely used its share.
const DAILY_IMAGE_LIMIT_PER_ACCOUNT = 1200;

// Cars published per run, PER CHANNEL. Every 999 account gets its own two slots each
// run, so the channels advance side by side instead of one draining before the next is
// touched: 62 American cars used to sit behind hundreds of older Korean ones purely
// because the queue was served oldest-first across all accounts.
// 999 rate-limits by IP, so what matters is images per minute, not how often the cron
// starts — 3 channels x 2 cars is ~60 images over ~70s, inside the 2-minute interval.
// The real ceiling stays the daily image quota per account, not this number.
const MAX_CARS_PER_ACCOUNT_PER_RUN = 2;

// Safety cap on a whole run, so a day with many active channels cannot outrun the
// cron interval.
const MAX_CARS_PER_RUN = 8;

/**
 * Upload the car's photos to 999.md and (re)build feature 14 (images).
 *
 * Mirrors content/admin/ajax/cars/999_catalog.php so the cron never relies on a
 * stale/empty feature 14 in the saved `999` column — it always rebuilds it from
 * gh3sp_car_pht (the source of truth). The advert stays on $accountId; images are
 * uploaded there, but if that account's upload endpoint is throttled (403) we retry
 * the upload on a healthy fallback account (image IDs are global — see note above).
 *
 * Stops re-hammering an account after its first 403 for THIS car, moving to the next
 * fallback instead. $rateLimited is set true only when the ORIGINAL account 403'd, so
 * the caller can still cool it down / avoid burning the car's retry_count.
 *
 * @return array List of 999.md image IDs (empty if none could be uploaded anywhere).
 */
/**
 * The cover photo as 999 should see it: the car cut out on white, for AutoTrader
 * cars only. Returns the path to send — the processed copy when it worked, the
 * original otherwise, so a failure never stops a publish.
 *
 * Cached as <name>_999.jpg beside the gallery: a republish reuses it instead of
 * spending another Photoroom credit.
 */
function photoroomCover999($carId, string $coverPath, string $galleryPath, $db, $prefx): string
{
    try {
        $st = $db->prepare("SELECT parsing_source FROM {$prefx}_car_ctlg WHERE id = ? LIMIT 1");
        $st->execute([$carId]);
        if ((string)$st->fetchColumn() !== 'autotrader') return $coverPath;
    } catch (\Throwable $e) { return $coverPath; }

    // The cache belongs beside the GALLERY file, never beside $coverPath: once the
    // originals moved to R2, $coverPath is a temp copy under /tmp that is deleted at
    // the end of the run, so the cache vanished with it and every single publish
    // attempt paid for another Photoroom credit.
    //
    // Extension stripped with a full-path pattern, not a trailing ".jpg": a .jpeg or
    // .png cover used to produce a cache path identical to the original, and the
    // processed bytes were written over the dealer's photo — the one sauto, Facebook
    // and Telegram publish.
    $cached = preg_replace('/\.[^.\/\\\\]+$/', '', $galleryPath) . '_999.jpg';
    if ($cached !== $galleryPath && is_file($cached) && filesize($cached) > 1000) {
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: cover already whitened for car {$carId}\n";
        return $cached;
    }

    if (!class_exists('\App\Services\Parsing\PhotoroomService')) {
        require_once __DIR__ . '/../App/Services/Parsing/PhotoroomService.php';
    }
    $svc = new \App\Services\Parsing\PhotoroomService();
    if (!$svc->isEnabled()) return $coverPath;

    $bytes = @file_get_contents($coverPath);
    if ($bytes === false || strlen($bytes) < 1000) return $coverPath;

    $clean = $svc->removeBackgroundToWhiteJpeg($bytes);
    if ($clean === null) {
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: Photoroom skipped for car {$carId}"
             . ($svc->lastError ? " ({$svc->lastError})" : '') . " — sending the original\n";
        return $coverPath;
    }
    // The gallery folder may not exist locally at all when the originals live on R2.
    @mkdir(dirname($cached), 0775, true);
    if (@file_put_contents($cached, $clean) === false) {
        // Cannot keep it: still publish the whitened cover, just pay again next time.
        $tmp = preg_replace('/\.[^.\/\\\\]+$/', '', $coverPath) . '_999.jpg';
        if (@file_put_contents($tmp, $clean) === false) return $coverPath;
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: cover whitened for car {$carId}, but the cache could not be written to " . dirname($cached) . " — the next publish will spend another credit\n";
        return $tmp;
    }

    echo "[" . date('Y-m-d H:i:s') . "] Feature 14: cover whitened for car {$carId} (1 Photoroom credit)\n";
    return $cached;
}

function buildImagesFeature14($carId, $accountId, $db, $prefx, &$rateLimited = false, $skipOwnUpload = false) {
    $rateLimited = false;
    $stmt = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE `it_id` = :carId ORDER BY `main` DESC, `pos`, `id`");
    $stmt->execute(['carId' => $carId]);
    $photos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($photos)) {
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: no photos in car_pht for car {$carId}\n";
        return [];
    }

    // Image cap on 999: every parsing car publishes max 10 photos here, whatever
    // it got on sauto (Encar goes up there in full, the auction sources at 20).
    // Account 4 (Korea) is always 10. Everything else keeps 20. Parsing is
    // detected by parsing_id.
    $isParsing = false;
    try {
        $pchk = $db->prepare("SELECT parsing_id FROM {$prefx}_car_ctlg WHERE id = :id");
        $pchk->execute(['id' => $carId]);
        $isParsing = !empty($pchk->fetchColumn());
    } catch (\Throwable $e) { /* column may be absent on old installs */ }
    // Images per ad on 999: 15 for SautoSUA (account 5), 10 for Encars-MD and every
    // other parsing car, 20 for a hand-made ad.
    if ((int)$accountId === 5) {
        $maxImages = 15;
    } else {
        $maxImages = ($accountId == 4 || $isParsing) ? 10 : 20;
    }
    $photos = array_slice($photos, 0, $maxImages);

    // This script has no autoloader — every class is required by hand.
    if (!class_exists('\App\Services\CarPhotoR2')) {
        require_once __DIR__ . '/../App/Services/R2Client.php';
        require_once __DIR__ . '/../App/Services/CarPhotoR2.php';
    }

    // Resolve every photo to a real file path once. Running from CLI cron —
    // $_SERVER['DOCUMENT_ROOT'] is unreliable, so resolve relative to this script.
    $mediaBase = __DIR__ . '/../media/images/upload/car/';
    $paths = [];
    $galleryPaths = [];   // permanent locations, even when the file itself is on R2
    foreach ($photos as $img) {
        $imgPath = $mediaBase . $img['path'] . '/' . $img['it_id'] . '/high/' . $img['name'] . '.' . $img['ff'];
        // Falls back to R2 once the local copies are gone; the temp it makes is
        // cleaned up on shutdown.
        $local = \App\Services\CarPhotoR2::localCopy($imgPath);
        if ($local === null) {
            echo "[" . date('Y-m-d H:i:s') . "] Feature 14: missing file {$imgPath}\n";
            continue;
        }
        $paths[] = $local;
        $galleryPaths[] = $imgPath;
    }
    if (empty($paths)) return [];

    // 999 only: the cover goes up with a clean white background. Doing it here and
    // not at import means sauto, Facebook and Telegram keep the dealer's original
    // photo, and Photoroom is paid for once per car that actually reaches 999 —
    // a fraction of what the catalogue publishes. The processed copy is cached next
    // to the gallery, so republishing the same ad costs nothing.
    $paths[0] = photoroomCover999($carId, $paths[0], $galleryPaths[0], $db, $prefx);

    $fallbacks = array_values(array_filter(
        IMAGE_UPLOAD_FALLBACKS, fn($a) => (int)$a !== (int)$accountId
    ));
    // Try the advert's OWN account first, UNLESS the caller already knows it's throttled
    // ($skipOwnUpload) — then go straight to the fallbacks so we don't waste a doomed 403
    // on the throttled account for every single car. IDs are global, so the advert still
    // publishes on $accountId either way.
    if ($skipOwnUpload) {
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: account {$accountId} known-throttled — uploading images on a fallback account\n";
        $rateLimited = true; // keep the account's cooldown fresh for the rest of the run
        $tryAccounts = $fallbacks;
    } else {
        $tryAccounts = array_merge([(int)$accountId], $fallbacks);
    }

    global $db, $prefx;

    $attempted = false;   // did any account actually get an upload request?

    foreach ($tryAccounts as $uploadAcc) {
        // Per-account daily image allowance. Skipping an exhausted account onto the
        // next one is exactly how the 4 channels add up to 4800 images a day.
        $usedToday = imagesUploadedToday($uploadAcc, $db, $prefx);
        if ($usedToday >= DAILY_IMAGE_LIMIT_PER_ACCOUNT) {
            echo "[" . date('Y-m-d H:i:s') . "] Feature 14: account {$uploadAcc} used {$usedToday}/"
                 . DAILY_IMAGE_LIMIT_PER_ACCOUNT . " images today — trying next account\n";
            continue;
        }
        // An idle account may lend all it has — that is the whole point of the
        // fallbacks. What it may NOT lend is the part its own waiting adverts still
        // need today: borrowed images never come back. SautoSUA reached 1025 images
        // in a day uploading Korean cars while publishing 17 of its own, so its own
        // USA queue never fit.
        if ((int)$uploadAcc !== (int)$accountId) {
            $reserved = imagesReservedForOwnQueue($uploadAcc, $db, $prefx);
            $free = DAILY_IMAGE_LIMIT_PER_ACCOUNT - $usedToday - $reserved;
            if ($free < count($paths)) {
                echo "[" . date('Y-m-d H:i:s') . "] Feature 14: account {$uploadAcc} keeps its remaining "
                     . max(0, DAILY_IMAGE_LIMIT_PER_ACCOUNT - $usedToday)
                     . " images for its own queue (needs {$reserved}) — not borrowing\n";
                continue;
            }
        }

        $got403 = false;
        $ipRateLimited = false;
        $attempted = true;
        $imageIds = uploadPhotoListOnAccount($paths, $uploadAcc, $carId, $got403, $ipRateLimited);
        // The ORIGINAL account being throttled is what the caller cools down / doesn't
        // penalise the car for — so only flag $rateLimited for it, not the fallbacks.
        if ((int)$uploadAcc === (int)$accountId && $got403) $rateLimited = true;

        if (!empty($imageIds)) {
            if ($uploadAcc != $accountId) {
                echo "[" . date('Y-m-d H:i:s') . "] Feature 14: uploaded via fallback account {$uploadAcc} (advert stays on account {$accountId})\n";
            }
            return $imageIds;
        }
        // The per-IP rate limit applies to every account equally — trying the next one
        // only adds requests to a connection 999 is already refusing. Stop, let the car
        // be postponed, and the next cron run (a minute later) finds the limit lifted.
        if ($ipRateLimited) {
            echo "[" . date('Y-m-d H:i:s') . "] Feature 14: per-IP rate limit — not trying other accounts, they share the same IP\n";
            break;
        }
        // Only advance to a fallback when the failure was a 403 throttle. A genuine
        // failure (missing files, other error) would fail identically elsewhere.
        if (!$got403) break;
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: account {$uploadAcc} throttled (403) — trying next account for images\n";
    }

    // Every account was skipped on quota alone — we never even tried an upload. That is
    // the day's allowance being full, not a broken car: tell the caller so it postpones
    // without burning one of the car's 72 retries. Without this a backlog bigger than
    // the daily quota silently kills its own cars after ~6 days of retrying.
    if (!$attempted) {
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: no account has image allowance left today — postponing car {$carId}\n";
        $rateLimited = true;
    }

    return [];
}

/**
 * Upload a list of image file paths to one 999.md account. Returns the uploaded image
 * IDs; sets $got403 when the account's /images endpoint returned 403 (throttle/block),
 * which tells the caller to try a different account.
 */
function uploadPhotoListOnAccount(array $paths, $accountId, $carId, &$got403 = false, &$ipRateLimited = false) {
    $got403 = false;
    $ipRateLimited = false;
    $api = new \App\Services\Api999Service($accountId);
    $imageIds = [];
    $paceUs = 700000;   // 0.7s between images, doubled whenever 999 tells us to slow down
    foreach ($paths as $imgPath) {
        // Upload one image, retrying on 429 (nginx network rate-limit — temporary,
        // means "slow down") with exponential backoff. A 403 (account blocked) is
        // NOT retried — bail out, every further upload on this account would fail too.
        $stop = false;
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $imageLink = $api->uploadImage($imgPath);
            if (is_array($imageLink) && !empty($imageLink['image_id'])) {
                $imageIds[] = $imageLink['image_id'];
                break;
            }
            $msg = is_string($imageLink) ? $imageLink : json_encode($imageLink, JSON_UNESCAPED_UNICODE);

            if (strpos($msg, '429') !== false) {
                // nginx throttled us — wait longer each time, then retry SAME image.
                $wait = 3 * $attempt; // 3s, 6s, 9s
                echo "[" . date('Y-m-d H:i:s') . "] Feature 14: 429 from nginx, backing off {$wait}s (attempt {$attempt}/4) for {$imgPath}\n";
                sleep($wait);
                if ($attempt === 4) {
                    // Still 429 after all retries — the IP is throttled. Treat like a
                    // throttle so the caller can try another account.
                    echo "[" . date('Y-m-d H:i:s') . "] Feature 14: still 429 after retries — account {$accountId} rate-limited, stopping uploads\n";
                    $got403 = true;
                    $stop = true;
                }
                continue;
            }
            if (strpos($msg, '403') !== false) {
                // 999 answers 403 "Too many requests" when the SERVER IP sent too many
                // requests in a short window — it never looks at which account we used.
                // So switching accounts is the worst possible move: three more requests
                // on an already-refused IP. Wait it out on the same account instead.
                $ipThrottle = stripos($msg, 'too many requests') !== false;
                echo "[" . date('Y-m-d H:i:s') . "] Feature 14: got 403 — account {$accountId} "
                     . ($ipThrottle ? 'hit the per-IP rate limit' : 'blocked') . ", "
                     . ($ipThrottle && $attempt < 4 ? "waiting" : "stopping uploads")
                     . " | 999 said: " . substr(preg_replace('/\s+/', ' ', $msg), 0, 200) . "\n";

                if ($ipThrottle) {
                    // Slow every later image down for the rest of this run, so we stop
                    // walking into the same wall car after car.
                    $paceUs = min(4000000, max($paceUs * 2, 2000000));
                    if ($attempt < 4) {
                        sleep(5 * $attempt);   // 5s, 10s, 15s
                        continue;              // retry the SAME image, SAME account
                    }
                    $ipRateLimited = true;     // tells the caller not to try other accounts
                }
                $got403 = true;
                $stop = true;
                break;
            }
            // Other error — log once and move to next image.
            echo "[" . date('Y-m-d H:i:s') . "] Feature 14: upload failed for {$imgPath}: {$msg}\n";
            break;
        }
        // A 403 on the very first image means the account is throttled — don't keep
        // half a set uploaded on it; let the caller retry the whole set elsewhere.
        if ($stop && empty($imageIds)) break;
        if ($stop) break;
        // Steady pace between every image so we never burst nginx. 0.7s normally,
        // doubled after each rate-limit answer. Even at 4s per image the cron can do
        // ~15 images/minute — far more than the ~3.3/minute that 4800/day needs.
        usleep($paceUs);
    }

    // Record what really went up, so the quota check works off facts, not a guess.
    global $db, $prefx;
    countUploadedImages($accountId, count($imageIds), $db, $prefx);

    echo "[" . date('Y-m-d H:i:s') . "] Feature 14: uploaded " . count($imageIds) . " image(s) for car {$carId} on account {$accountId}"
         . " (today on this account: " . imagesUploadedToday($accountId, $db, $prefx) . ")\n";
    return $imageIds;
}

/**
 * Per-account daily image-upload cooldown. 999.md allows ~1200 image uploads/day;
 * when an account hits the limit it returns 403 on /images. Instead of retrying
 * every minute (and spamming the log), we mark the account "on cooldown until
 * tomorrow" so the cron skips it entirely until the quota resets.
 *
 * Stored in gh3sp_settings as "999md_upload_cooldown_<accountId>" = unix timestamp
 * (when the cooldown ends). Cleared automatically once that time has passed.
 */
function isAccountOnCooldown($accountId, $db, $prefx) {
    $stmt = $db->prepare("SELECT value FROM {$prefx}_settings WHERE name = ?");
    $stmt->execute(["999md_upload_cooldown_{$accountId}"]);
    $until = (int)$stmt->fetchColumn();
    return $until > time() ? $until : 0;
}

// How many images this account really uploaded TODAY. Counted for real, one by one,
// as the uploads happen — the old version multiplied "cars published today" by a
// flat 20, which overshot by 2x on parsing cars (they carry 10) and parked healthy
// accounts at half their quota. An account that uploaded nothing reads 0, so a 403
// on it can never be mistaken for the daily limit.
function uploadCounterKey($accountId) {
    return "999md_uploads_" . date('Ymd') . "_{$accountId}";
}

function imagesUploadedToday($accountId, $db, $prefx) {
    $stmt = $db->prepare("SELECT value FROM {$prefx}_settings WHERE name = ?");
    $stmt->execute([uploadCounterKey($accountId)]);
    return (int)$stmt->fetchColumn();
}

function countUploadedImages($accountId, $n, $db, $prefx) {
    if ($n <= 0) return;
    $stmt = $db->prepare("INSERT INTO {$prefx}_settings (name, value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE value = CAST(value AS UNSIGNED) + ?");
    $stmt->execute([uploadCounterKey($accountId), (string)$n, $n]);
}

// Photos one advert carries on a given account — mirrors the $maxImages rule used
// when the images are actually picked. Only an estimate here: it turns "cars still
// waiting" into "images still needed".
function imagesPerCarForAccount($accountId) {
    if ((int)$accountId === 5) return 15;      // USA / autotrader
    if ((int)$accountId === 4 || (int)$accountId === 3) return 10; // parsing channels
    return 20;
}

// Images an account must keep for the adverts of its OWN that are already due.
// Cars scheduled for a later date are not counted — they get tomorrow's allowance,
// and counting them would freeze lending permanently on a long queue.
function imagesReservedForOwnQueue($accountId, $db, $prefx) {
    static $cache = [];
    $accountId = (int)$accountId;
    if (isset($cache[$accountId])) return $cache[$accountId];

    $stmt = $db->prepare("SELECT COUNT(*)
        FROM {$prefx}_sauto_personal_schedules s
        JOIN {$prefx}_car_ctlg c ON c.id = s.car_id
        WHERE c.`999_api_id` = ?
          AND (c.n_a IS NULL OR c.n_a <> 1)
          AND ((s.status = 'pending' AND s.schedule_date <= CURDATE())
               OR (s.status = 'postponed' AND s.retry_count < 72))");
    $stmt->execute([$accountId]);
    $waiting = (int)$stmt->fetchColumn();

    return $cache[$accountId] = min(
        DAILY_IMAGE_LIMIT_PER_ACCOUNT,
        $waiting * imagesPerCarForAccount($accountId)
    );
}

// A 403 on image upload can mean TWO different things:
//   (a) the real daily 1200-image quota is exhausted → cool down until tomorrow;
//   (b) a transient throttle (999/nginx "too many requests right now") that hits
//       well below 1200 → a full-day cooldown wrongly parks the account and loses
//       the ~200-400 images of headroom it still has.
// So: only cool down until tomorrow when we're actually near the quota; otherwise
// back off a few minutes and let the next cron run retry.
/**
 * One account is out of money on 999. Park THAT account for an hour and let the
 * run move on to the other channels.
 *
 * Without this the queue stalls for everybody: rows are published oldest first,
 * so a few hundred cars belonging to an empty account sit at the head, each run
 * spends its two slots postponing two of them, and USA/Europe cars behind them
 * wait for hours — even though their own accounts have money and nothing wrong
 * with them.
 *
 * Kept separate from the image cooldown: an account with no balance can still
 * host images for other channels, it just cannot create its own adverts.
 */
function setAccountBalanceCooldown($accountId, $db, $prefx) {
    $until = time() + 3600;
    $stmt = $db->prepare("INSERT INTO {$prefx}_settings (name, value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)");
    $stmt->execute(["999md_balance_cooldown_{$accountId}", (string)$until]);
    echo "[" . date('Y-m-d H:i:s') . "] Account {$accountId} has no balance — skipping its cars until "
         . date('H:i', $until) . ", other channels continue\n";
    return $until;
}

function setAccountCooldownUntilTomorrow($accountId, $db, $prefx) {
    $uploadedToday = imagesUploadedToday($accountId, $db, $prefx);
    $DAILY_QUOTA = DAILY_IMAGE_LIMIT_PER_ACCOUNT;

    if ($uploadedToday < $DAILY_QUOTA - 150) {
        // Well under the quota → per-IP rate limit, not the daily cap. 999 lifts that
        // within minutes, so a short pause is enough. Parking the account until
        // tomorrow here is what used to stall publishing at a fraction of the quota.
        $until = time() + 300; // 5 min
        $stmt = $db->prepare("INSERT INTO {$prefx}_settings (name, value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)");
        $stmt->execute(["999md_upload_cooldown_{$accountId}", (string)$until]);
        echo "[" . date('Y-m-d H:i:s') . "] Account {$accountId} got a 403 at ~{$uploadedToday}/{$DAILY_QUOTA} images (per-IP rate limit, not the daily cap) — 5-min pause\n";
        return $until;
    }

    // Near/at the quota → real daily limit. Reset at the start of the next day.
    $until = strtotime('tomorrow 00:05');
    $stmt = $db->prepare("INSERT INTO {$prefx}_settings (name, value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)");
    $stmt->execute(["999md_upload_cooldown_{$accountId}", (string)$until]);
    echo "[" . date('Y-m-d H:i:s') . "] Account {$accountId} hit daily image limit (~{$uploadedToday}/{$DAILY_QUOTA}) — cooldown until " . date('Y-m-d H:i', $until) . "\n";
    return $until;
}

function updateFeaturesWithFreshData($features, $carData, $db, $prefx) {
    $updatedFeatures = [];
    
    foreach ($features as $feature) {
        $featureId = $feature['id'];
        
        if ($featureId === '2' || $featureId === 2) {
            if (!empty($carData['prc']) && $carData['prc'] > 0) {
                $currency = !empty($carData['cur']) ? strtolower($carData['cur']) : 'eur';
                $feature['value'] = (string)$carData['prc'];
                $feature['unit'] = $currency;
                echo "[" . date('Y-m-d H:i:s') . "] Обновлена цена: {$carData['prc']} {$currency}\n";
            }
        }
        
        if (($featureId === '103' || $featureId === 103 || $featureId === '2553' || $featureId === 2553) && !empty($carData['vol'])) {
            $engineVolumeCm3 = (int)$carData['vol'];
            if ($featureId === '2553' || $featureId === 2553) {
                $engineVolumeLiters = number_format(round($engineVolumeCm3 / 1000, 1), 1, '.', '');
                $volumeMap = [
                    "0.7" => "43671", "0.8" => "43672", "0.9" => "43673", "1.0" => "43674",
                    "1.1" => "43675", "1.2" => "43676", "1.3" => "43677", "1.4" => "43678",
                    "1.5" => "43679", "1.6" => "43680", "1.7" => "43681", "1.8" => "43682",
                    "1.9" => "43683", "2.0" => "43684", "2.1" => "43685", "2.2" => "43686",
                    "2.3" => "43687", "2.4" => "43688", "2.5" => "43689", "2.6" => "43690",
                    "2.7" => "43691", "2.8" => "43692", "2.9" => "43693", "3.0" => "43694",
                    "3.1" => "43695", "3.2" => "43696", "3.3" => "43697", "3.4" => "43698",
                    "3.5" => "43699", "3.6" => "43700", "3.8" => "43701", "3.9" => "43702",
                    "4.0" => "43703", "4.2" => "43704", "4.3" => "43705", "4.4" => "43706",
                    "4.5" => "43707", "4.6" => "43708", "4.7" => "43709", "4.8" => "43710",
                    "5.0" => "43711", "5.2" => "43712", "5.3" => "43713", "5.4" => "43714",
                    "5.5" => "43715", "5.6" => "43716", "5.7" => "43717", "5.8" => "43718",
                    "5.9" => "43719", "6.0" => "43720", "6.2" => "43721", "6.4" => "43722",
                    "6.6" => "43723", "6.7" => "43724", "8.0" => "43763"
                ];
                
                $optionId = $volumeMap[$engineVolumeLiters] ?? null;
                if ($optionId) {
                    $feature['value'] = $optionId;
                }
            } else {
                $feature['value'] = (string)$engineVolumeCm3;
            }
        }
        
        if (($featureId === '4' || $featureId === 4) && !empty($carData['yr'])) {
            $feature['value'] = (string)$carData['yr'];
        }
        
        if (($featureId === '5' || $featureId === 5) && isset($carData['mlg'])) {
            $feature['value'] = (string)$carData['mlg'];
        }
        
        $updatedFeatures[] = $feature;
    }

    // Sauto links on top of the description. Also CREATES feature 13 when the saved
    // payload has none (parsing payloads with an empty text), which the old inline
    // version couldn't do — it only rewrote an existing one.
    return \App\Helper\Ad999Links::apply($db, $prefx, $carData, $updatedFeatures);
}

try {
    // Database connection using environment settings
    require_once __DIR__ . '/../environment.php';
    
    $db = new PDO(
        'mysql:host=' . SQL_HOST . ';dbname=' . SQL_DB . ';charset=' . SQL_CHARSET,
        SQL_USER,
        SQL_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    $prefx = 'gh3sp';
    
    echo "[" . date('Y-m-d H:i:s') . "] SAUTO Personal Cron Started\n";
    
    // Get 999.md API settings
    $settings = [];
    $stmt = $db->prepare("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%999md%'");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $settings[$row['name']] = $row['value'];
    }
    
    if (empty($settings['regular_999md_account']) || empty($settings['regular_999md_token']) ||
        empty($settings['order_999md_account']) || empty($settings['order_999md_token'])) {
        throw new Exception('999.md API settings not configured');
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] 999.md Settings loaded - Stock: {$settings['regular_999md_account']}, Order: {$settings['order_999md_account']}\n";
    
    // Get pending schedules that should be published now
    $currentDateTime = date('Y-m-d H:i:s');
    echo "[" . date('Y-m-d H:i:s') . "] Current server time: {$currentDateTime}\n";

    // Accounts that hit their daily image limit (403) are on cooldown — EXCLUDE their
    // cars from the batch, otherwise the oldest 10 are all from a blocked account and
    // the cron never reaches cars on accounts that CAN still publish.
    // Two reasons to skip an account's cars this run: its image quota is spent, or
    // it has no money on 999. Both mean "cannot publish on this channel right now";
    // excluding them is what lets the other channels keep going instead of the whole
    // queue stalling behind the oldest rows of a blocked one.
    $cooldownAccounts = [];
    $cdStmt = $db->query("SELECT name, value FROM {$prefx}_settings
        WHERE name LIKE '999md_upload_cooldown_%' OR name LIKE '999md_balance_cooldown_%'");
    foreach ($cdStmt->fetchAll() as $row) {
        if ((int)$row['value'] > time()) {
            $cooldownAccounts[] = (int)str_replace(['999md_upload_cooldown_', '999md_balance_cooldown_'], '', $row['name']);
        }
    }
    $cooldownAccounts = array_values(array_unique($cooldownAccounts));
    $cooldownSql = '';
    if (!empty($cooldownAccounts)) {
        $cooldownSql = ' AND (c.`999_api_id` IS NULL OR c.`999_api_id` NOT IN (' . implode(',', array_map('intval', $cooldownAccounts)) . ')) ';
        echo "[" . date('Y-m-d H:i:s') . "] Accounts on cooldown (excluded this run): " . implode(',', $cooldownAccounts) . "\n";
    }

    // Two cars per CHANNEL per run: each 999 account is served on its own, so a queue
    // that happens to be older on one channel cannot hold the others back. Within a
    // channel the order stays oldest first.
    $accStmt = $db->prepare("
        SELECT c.`999_api_id` acc, COUNT(*) n
        FROM gh3sp_sauto_personal_schedules s
        LEFT JOIN {$prefx}_car_ctlg c ON s.car_id = c.id
        WHERE s.status = 'pending'
        AND CONCAT(s.schedule_date, ' ', s.schedule_time) <= :current_time
        {$cooldownSql}
        GROUP BY c.`999_api_id`
        ORDER BY c.`999_api_id`
    ");
    $accStmt->execute(['current_time' => $currentDateTime]);
    $accountsDue = $accStmt->fetchAll();

    $rowStmt = $db->prepare("
        SELECT s.*, c.id as car_id, c.`999_id` as existing_999_id, s.catalog_type,
               CONCAT(s.schedule_date, ' ', s.schedule_time) as full_schedule_time
        FROM gh3sp_sauto_personal_schedules s
        LEFT JOIN {$prefx}_car_ctlg c ON s.car_id = c.id
        WHERE s.status = 'pending'
        AND CONCAT(s.schedule_date, ' ', s.schedule_time) <= :current_time
        AND (c.`999_api_id` <=> :acc)
        ORDER BY s.schedule_date, s.schedule_time
        LIMIT " . MAX_CARS_PER_ACCOUNT_PER_RUN . "
    ");

    $pendingSchedules = [];
    foreach ($accountsDue as $a) {
        if (count($pendingSchedules) >= MAX_CARS_PER_RUN) break;
        $rowStmt->execute(['current_time' => $currentDateTime, 'acc' => $a['acc']]);
        foreach ($rowStmt->fetchAll() as $row) { $pendingSchedules[] = $row; }
    }

    if ($accountsDue) {
        $parts = [];
        foreach ($accountsDue as $a) { $parts[] = ($a['acc'] ?: '?') . ':' . $a['n']; }
        echo "[" . date('Y-m-d H:i:s') . "] Due per account (" . implode(' ', $parts) . ") — taking "
             . MAX_CARS_PER_ACCOUNT_PER_RUN . " from each, " . count($pendingSchedules) . " car(s) this run\n";
    }
    
    $postponedStmt = $db->prepare("
        SELECT s.*, c.id as car_id, c.`999_id` as existing_999_id, s.catalog_type,
               CONCAT(s.schedule_date, ' ', s.schedule_time) as full_schedule_time
        FROM gh3sp_sauto_personal_schedules s
        LEFT JOIN {$prefx}_car_ctlg c ON s.car_id = c.id
        WHERE s.status = 'postponed'
        AND (c.n_a IS NULL OR c.n_a <> 1)
        AND s.retry_count < 72
        AND (s.last_retry_at IS NULL OR s.last_retry_at <= DATE_SUB(:current_time, INTERVAL 2 HOUR))
        {$cooldownSql}
        ORDER BY s.schedule_date, s.schedule_time
        LIMIT 5
    ");
    $postponedStmt->execute(['current_time' => $currentDateTime]);
    $postponedSchedules = $postponedStmt->fetchAll();
    
    if (!empty($postponedSchedules)) {
        echo "[" . date('Y-m-d H:i:s') . "] Found " . count($postponedSchedules) . " postponed schedules to retry (insufficient balance)\n";
        $pendingSchedules = array_merge($pendingSchedules, $postponedSchedules);
    }
    
    // Debug: Count pending schedules
    $debugStmt = $db->prepare("
        SELECT COUNT(*) as total_pending
        FROM gh3sp_sauto_personal_schedules s
        WHERE s.status = 'pending'
    ");
    $debugStmt->execute();
    $totalPending = $debugStmt->fetchColumn();
    
    // Count postponed schedules
    $debugPostponedStmt = $db->prepare("
        SELECT COUNT(*) as total_postponed
        FROM gh3sp_sauto_personal_schedules s
        WHERE s.status = 'postponed' AND s.retry_count < 72
    ");
    $debugPostponedStmt->execute();
    $totalPostponed = $debugPostponedStmt->fetchColumn();
    
    echo "[" . date('Y-m-d H:i:s') . "] Total pending schedules: {$totalPending}, postponed (awaiting retry): {$totalPostponed}\n";
    
    if (empty($pendingSchedules)) {
        echo "[" . date('Y-m-d H:i:s') . "] No pending schedules to publish\n";
        exit(0);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Found " . count($pendingSchedules) . " schedules to process\n";
    
    // Include required services
    require_once __DIR__ . '/../App/Core/Container.php';
    require_once __DIR__ . '/../App/Services/PublicationService.php';
    require_once __DIR__ . '/../App/Services/Api999Service.php';
    require_once __DIR__ . '/../App/Helper/Ad999Links.php';
    // No autoloader here — every class is loaded by hand. A missing one is an Error,
    // not an Exception, so it would kill the whole run instead of failing one car.
    require_once __DIR__ . '/../App/Services/Parsing/Build999Payload.php';
    
    // Initialize Container with database and prefix
    \App\Core\Container::set('db', $db);
    \App\Core\Container::set('prefix', $prefx);
    
    // Accounts that got rate-limited (429/403) this run — once an account trips,
    // we skip the rest of its cars this run so we stop hammering a throttled IP.
    $rateLimitedAccounts = [];

    foreach ($pendingSchedules as $schedule) {
        try {
            echo "[" . date('Y-m-d H:i:s') . "] Processing schedule ID: {$schedule['id']}, Car ID: {$schedule['car_id']}, Type: {$schedule['catalog_type']}\n";
            
            // Get car data to determine which 999.md account to use
            $carStmt = $db->prepare("SELECT `999_api_id`, import_country_id, gr, n_a, parsing_source FROM {$prefx}_car_ctlg WHERE id = :car_id");
            $carStmt->execute(['car_id' => $schedule['car_id']]);
            $carInfo = $carStmt->fetch();

            // Out of stock (n_a=1) → don't publish. Postpone this schedule so it
            // resumes automatically if the car comes back in stock. Works for both
            // in_stock and on_order cars, regardless of how it went out of stock
            // (manual toggle, expired offer timer, or sold at the source).
            if (!empty($carInfo) && (int)$carInfo['n_a'] === 1) {
                $db->prepare("UPDATE gh3sp_sauto_personal_schedules
                    SET status = 'postponed', error_message = 'Car out of stock'
                    WHERE id = :id")->execute(['id' => $schedule['id']]);
                echo "[" . date('Y-m-d H:i:s') . "] ⏸️ Skip car {$schedule['car_id']}: out of stock (n_a=1), schedule postponed\n";
                continue;
            }

            // Determine which 999.md account to use based on car's 999_api_id or catalog_type
            $catalogType = $schedule['catalog_type'];
            $apiAccountId = !empty($carInfo['999_api_id']) ? $carInfo['999_api_id'] : null;

            // Auto-correct 999_api_id for on_order cars based on import country / group
            // (mirrors UI logic in content/admin/js/ordercars.js)
            if ($catalogType === 'on_order') {
                $importCountry = (int)($carInfo['import_country_id'] ?? 0);
                $isCom = ($carInfo['gr'] ?? '') === 'com';

                $isUsa = ($carInfo['parsing_source'] ?? '') === 'autotrader'
                      || ($importCountry > 0 && $importCountry === usaCountryId($db));

                if ($isUsa) {
                    // Every AutoTrader car goes to SautoSUA, commercial included.
                    $expectedAccountId = 5;
                } elseif ($isCom && $importCountry === 41) {
                    $expectedAccountId = 4; // Commercial FROM KOREA (Encar) → Encars-MD
                } elseif ($isCom) {
                    $expectedAccountId = 2; // Commercial, other origin → Sauto-auto-comerciale
                } elseif ($importCountry === 41) {
                    $expectedAccountId = 4; // Encars-MD (Korea, non-commercial)
                } else {
                    $expectedAccountId = 3; // Sauto-stock-extern
                }

                if ($apiAccountId != $expectedAccountId) {
                    echo "[" . date('Y-m-d H:i:s') . "] Auto-correcting 999_api_id {$apiAccountId} → {$expectedAccountId} for car {$schedule['car_id']} (country={$importCountry}, gr=" . ($carInfo['gr'] ?? 'NULL') . ")\n";
                    $apiAccountId = $expectedAccountId;
                    $fixStmt = $db->prepare("UPDATE {$prefx}_car_ctlg SET `999_api_id` = :acc WHERE id = :car_id");
                    $fixStmt->execute(['acc' => $expectedAccountId, 'car_id' => $schedule['car_id']]);
                }
            } elseif ($catalogType === 'in_stock') {
                $isCom = ($carInfo['gr'] ?? '') === 'com';
                $importCountry = (int)($carInfo['import_country_id'] ?? 0);
                $expectedComAccountId = ($isCom && $importCountry === 41) ? 4 : 2;
                if ($isCom && $apiAccountId != $expectedComAccountId) {
                    echo "[" . date('Y-m-d H:i:s') . "] Auto-correcting 999_api_id {$apiAccountId} → {$expectedComAccountId} (commercial stock, country={$importCountry}) for car {$schedule['car_id']}\n";
                    $apiAccountId = $expectedComAccountId;
                    $fixStmt = $db->prepare("UPDATE {$prefx}_car_ctlg SET `999_api_id` = :acc WHERE id = :car_id");
                    $fixStmt->execute(['acc' => $expectedComAccountId, 'car_id' => $schedule['car_id']]);
                }
            }
            
            if ($catalogType === 'in_stock') {
                // For in_stock cars, use 999_api_id from car or default to regular account
                if ($apiAccountId == 1) {
                    $apiAccount = $settings['sautohaus_999md_account'] ?? 'SAUTO-HAUS';
                    $apiToken = $settings['sautohaus_999md_token'] ?? $settings['regular_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using STOCK account: {$apiAccount} (Account ID: 1)\n";
                } elseif ($apiAccountId == 2) {
                    $apiAccount = 'Sauto-auto-comerciale';
                    $apiToken = $settings['regular_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using STOCK account: {$apiAccount} (Account ID: 2)\n";
                } else {
                    // Default to regular account (currently Sauto-auto-comerciale)
                    $apiAccount = $settings['regular_999md_account'];
                    $apiToken = $settings['regular_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using STOCK account: {$apiAccount} (Default)\n";
                }
            } elseif ($catalogType === 'on_order') {
                // Korean cars → Encars-MD
                if ($apiAccountId == 4) {
                    $apiAccount = $settings['korea_999md_account'] ?? 'Encars-MD';
                    $apiToken = $settings['korea_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using ORDER account: {$apiAccount} (Korean cars - Account ID: 4)\n";
                } elseif ($apiAccountId == 5) {
                    // USA cars (AutoTrader) -> SautoSUA
                    $apiAccount = $settings['usa_999md_account'] ?? 'SautoSUA';
                    $apiToken = $settings['usa_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using ORDER account: {$apiAccount} (USA cars - Account ID: 5)
";
                } elseif ($apiAccountId == 2) {
                    // Commercial cars → Sauto-auto-comerciale (regular_999md_token)
                    $apiAccount = $settings['regular_999md_account'] ?? 'Sauto-auto-comerciale';
                    $apiToken = $settings['regular_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using ORDER account: {$apiAccount} (Commercial - Account ID: 2)\n";
                } else {
                    // Default order → Sauto-stock-extern
                    $apiAccount = $settings['order_999md_account']; // Sauto-stock-extern
                    $apiToken = $settings['order_999md_token'];
                    echo "[" . date('Y-m-d H:i:s') . "] Using ORDER account: {$apiAccount} (Account ID: 3)\n";
                }
            } else {
                throw new Exception("Unknown catalog_type: {$catalogType}");
            }
            
            if (!empty($schedule['existing_999_id'])) {
                // ONE republish per car per day. A car carries a schedule row per planned
                // renewal, and after its first publish every remaining row turns into a
                // republish — car 70731 held both slots of every run for 40 minutes,
                // renewing the same advert dozens of times while the other channels
                // waited. A renewal is monthly by design; several in one day are
                // redundant rows, not work. Close them without touching the API.
                $already = $db->prepare("SELECT COUNT(*) FROM gh3sp_sauto_personal_schedules
                    WHERE car_id = :car AND status = 'published'
                      AND published_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
                $already->execute(['car' => $schedule['car_id']]);
                if ((int)$already->fetchColumn() > 0) {
                    $db->prepare("UPDATE gh3sp_sauto_personal_schedules
                        SET status = 'published', published_at = NOW(), `999_id` = :api_id
                        WHERE id = :id")
                       ->execute(['api_id' => $schedule['existing_999_id'], 'id' => $schedule['id']]);
                    echo "[" . date('Y-m-d H:i:s') . "] Car {$schedule['car_id']} was already (re)published today — closing schedule {$schedule['id']} without a second republish\n";
                    continue;
                }

                echo "[" . date('Y-m-d H:i:s') . "] Авто {$schedule['car_id']} имеет 999.md ID: {$schedule['existing_999_id']} - обновление и републикация на {$apiAccount}\n";

                if ($catalogType === 'in_stock') {
                    $accountIdForApi = $apiAccountId ?? 2;
                } elseif ($catalogType === 'on_order') {
                    if ($apiAccountId == 4) $accountIdForApi = 4;
                    elseif ($apiAccountId == 2) $accountIdForApi = 2;
                    elseif ($apiAccountId == 5) $accountIdForApi = 5;
                    else $accountIdForApi = 3;
                } else {
                    $accountIdForApi = 3;
                }
                $api999Service = new \App\Services\Api999Service($accountIdForApi);

                $carStmt = $db->prepare("SELECT * FROM {$prefx}_car_ctlg WHERE id = :car_id");
                $carStmt->execute(['car_id' => $schedule['car_id']]);
                $carData = $carStmt->fetch();
                
                if ($carData && (!empty($carData['features_json']) || !empty($carData['999']))) {
                    $featuresData = null;
                    if (!empty($carData['features_json'])) {
                        $featuresData = json_decode($carData['features_json'], true);
                    } elseif (!empty($carData['999'])) {
                        $featuresData = json_decode($carData['999'], true);
                    }
                    
                    if ($featuresData && isset($featuresData['features'])) {
                        $updatedFeatures = updateFeaturesWithFreshData($featuresData['features'], $carData, $db, $prefx);
                        $updatedFeatures = enforcePhoneForAccount($updatedFeatures, $accountIdForApi);

                        echo "[" . date('Y-m-d H:i:s') . "] Обновление объявления актуальными данными перед републикацией...\n";
                        $updateResult = $api999Service->updateAdvert($schedule['existing_999_id'], $updatedFeatures);
                        
                        if ($updateResult) {
                            echo "[" . date('Y-m-d H:i:s') . "] ✅ Объявление обновлено актуальными данными\n";
                            $featuresData['features'] = $updatedFeatures;
                            $updatedFeaturesJson = json_encode($featuresData, JSON_UNESCAPED_UNICODE);
                            $updateDbStmt = $db->prepare("UPDATE {$prefx}_car_ctlg SET `999` = :features WHERE id = :car_id");
                            $updateDbStmt->execute(['features' => $updatedFeaturesJson, 'car_id' => $schedule['car_id']]);
                        } else {
                            echo "[" . date('Y-m-d H:i:s') . "] ⚠️ Предупреждение: Не удалось обновить данные, продолжаем републикацию\n";
                        }
                    }
                }
                
                $result = $api999Service->republishAdvert($schedule['existing_999_id']);
                
                if ($result && isset($result['success']) && $result['success']) {
                       $stmt = $db->prepare("
                        UPDATE gh3sp_sauto_personal_schedules 
                        SET status = 'published', 
                            published_at = NOW(), 
                            `999_id` = :api_id
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'api_id' => $schedule['existing_999_id'],
                        'id' => $schedule['id']
                    ]);
                    
                    echo "[" . date('Y-m-d H:i:s') . "] ✅ Успешно републиковано авто {$schedule['car_id']} на 999.md ({$apiAccount})\n";
                } else {
                    $errorMsg = isset($result['error']) ? $result['error'] : 'Unknown error during republish';
                    if (is_array($errorMsg)) {
                        $errorMsg = json_encode($errorMsg, JSON_UNESCAPED_UNICODE);
                    }
                    
                    $isInsufficientBalance = (stripos($errorMsg, 'insufficient balance') !== false || stripos($errorMsg, 'insufficient funds') !== false || stripos($errorMsg, 'баланс') !== false);
                    if ($isInsufficientBalance) {
                        // Park the CHANNEL for an hour, not just this car: otherwise its rows
                        // keep occupying the head of the queue and the other channels starve.
                        setAccountBalanceCooldown($accountIdForApi ?? $apiAccountId ?? 0, $db, $prefx);
                    }
                    $currentRetryCount = isset($schedule['retry_count']) ? (int)$schedule['retry_count'] : 0;
                    
                    if ($isInsufficientBalance && $currentRetryCount < 72) {
                        $stmt = $db->prepare("
                            UPDATE gh3sp_sauto_personal_schedules 
                            SET status = 'postponed', 
                                error_message = :error,
                                retry_count = retry_count + 1,
                                last_retry_at = NOW()
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            'error' => $errorMsg,
                            'id' => $schedule['id']
                        ]);
                        
                        echo "[" . date('Y-m-d H:i:s') . "] ⏸️ Insufficient balance - postponed republish for car {$schedule['car_id']} (retry " . ($currentRetryCount + 1) . "/72, next retry in 2h)\n";
                    } else {
                        $stmt = $db->prepare("
                            UPDATE gh3sp_sauto_personal_schedules 
                            SET status = 'failed', 
                                error_message = :error
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            'error' => $errorMsg,
                            'id' => $schedule['id']
                        ]);
                        
                        echo "[" . date('Y-m-d H:i:s') . "] ❌ Ошибка републикации авто {$schedule['car_id']} на {$apiAccount}: $errorMsg\n";
                    }
                }
            } else {
                // Car doesn't have 999.md listing yet - create new one for SAUTO Personal
                echo "[" . date('Y-m-d H:i:s') . "] Авто {$schedule['car_id']} новое - создание первого объявления на 999.md\n";
                
                // Get car data and features from database
                $carStmt = $db->prepare("
                    SELECT * FROM {$prefx}_car_ctlg 
                    WHERE id = :car_id
                ");
                $carStmt->execute(['car_id' => $schedule['car_id']]);
                $carData = $carStmt->fetch();
                
                if (!$carData || (empty($carData['features_json']) && empty($carData['999']))) {
                    $errorMsg = !$carData ? 'Car not found in database' : 'No features data saved for car';
                    $stmt = $db->prepare("
                        UPDATE gh3sp_sauto_personal_schedules 
                        SET status = 'failed', error_message = :error
                        WHERE id = :id
                    ");
                    $stmt->execute(['error' => $errorMsg, 'id' => $schedule['id']]);
                    echo "[" . date('Y-m-d H:i:s') . "] ❌ $errorMsg\n";
                    continue;
                }
                
                // Parse saved features - try features_json first, then 999 column
                $featuresData = null;
                echo "[" . date('Y-m-d H:i:s') . "] Checking for saved features data...\n";
                echo "[" . date('Y-m-d H:i:s') . "] features_json: " . (empty($carData['features_json']) ? 'EMPTY' : 'FOUND') . "\n";
                echo "[" . date('Y-m-d H:i:s') . "] 999 column: " . (empty($carData['999']) ? 'EMPTY' : 'FOUND') . "\n";
                
                if (!empty($carData['features_json'])) {
                    $featuresData = json_decode($carData['features_json'], true);
                    echo "[" . date('Y-m-d H:i:s') . "] Using features_json data\n";
                } elseif (!empty($carData['999'])) {
                    $featuresData = json_decode($carData['999'], true);
                    echo "[" . date('Y-m-d H:i:s') . "] Using 999 column data\n";
                }
                
                if (!$featuresData || !isset($featuresData['features'])) {
                    $stmt = $db->prepare("
                        UPDATE gh3sp_sauto_personal_schedules 
                        SET status = 'failed', error_message = 'Invalid features data format'
                        WHERE id = :id
                    ");
                    $stmt->execute(['id' => $schedule['id']]);
                    echo "[" . date('Y-m-d H:i:s') . "] ❌ Invalid features data format\n";
                    continue;
                }
                
                // A payload stored as commercial (660) is published exactly as saved, so
                // the rules that Build999Payload applies while BUILDING never get a
                // second chance. Two cases need one:
                //   - pickups: 999 has no commercial category for them, so they must go
                //     out as a normal car (659) or the ad is rejected outright;
                //   - vans saved without the commercial-only fields (1408 km, 152 weight),
                //     which 999 answers with 400 "Completați câmpul".
                // Rebuild only then — a complete commercial payload may carry manual
                // edits and must be left alone.
                if ((string)($featuresData['subcategory_id'] ?? '') === '660') {
                    $asCar = \App\Services\Parsing\Build999Payload::mustPublishAsCar($carData);
                    $vals  = array_column($featuresData['features'], 'value', 'id');
                    $incomplete = empty($vals['1408']) || empty($vals['152']);

                    if ($asCar || $incomplete) {
                        $why = $asCar ? 'pickup — 999 has no commercial category for it'
                                      : 'commercial payload missing feature 1408/152';
                        $rebuilt = (new \App\Services\Parsing\Build999Payload($db, $prefx))->build($schedule['car_id']);
                        if ($rebuilt && !empty($rebuilt['payload']['features'])) {
                            $featuresData = $rebuilt['payload'];
                            echo "[" . date('Y-m-d H:i:s') . "] Rebuilt payload as subcategory "
                                 . $featuresData['subcategory_id'] . " for {$carData['br_nm']} {$carData['mo_nm']} ({$why})\n";
                        } else {
                            // Sending the stored payload anyway is a guaranteed 400 — and it
                            // costs a full photo upload first (10 images off the account's
                            // daily quota) before 999 rejects it. Worse, the car keeps a
                            // schedule row per renewal, so the next run picks the next row of
                            // the SAME car and burns the quota again. Fail it once, loudly,
                            // with the fields the builder needs so it can be repaired.
                            $note = "Cannot build a valid 999 payload ({$why}). Car data: "
                                  . "br='" . ($carData['br'] ?? '') . "' mo='" . ($carData['mo'] ?? '')
                                  . "' mo_nm='" . ($carData['mo_nm'] ?? '') . "' yr='" . ($carData['yr'] ?? '')
                                  . "' bt='" . ($carData['bt'] ?? '') . "' fl='" . ($carData['fl'] ?? '')
                                  . "' wd='" . ($carData['wd'] ?? '') . "' tra='" . ($carData['tra'] ?? '')
                                  . "' mlg='" . ($carData['mlg'] ?? '') . "' prc='" . ($carData['prc'] ?? '') . "'";
                            $db->prepare("UPDATE gh3sp_sauto_personal_schedules
                                SET status = 'failed', error_message = :note WHERE id = :id")
                               ->execute(['note' => $note, 'id' => $schedule['id']]);
                            echo "[" . date('Y-m-d H:i:s') . "] ❌ car {$schedule['car_id']} ({$carData['br_nm']} {$carData['mo_nm']}): {$note}\n";
                            continue;
                        }
                    }
                }

                // CRITICAL FIX: Update features with FRESH data from car card before publishing
                echo "[" . date('Y-m-d H:i:s') . "] Обновление features актуальными данными из карточки авто...\n";
                $featuresData['features'] = updateFeaturesWithFreshData($featuresData['features'], $carData, $db, $prefx);
                
                // Apply smart engine volume conversion before API call
                echo "[" . date('Y-m-d H:i:s') . "] Car volume from DB: " . ($carData['vol'] ?? 'NULL') . " cm³\n";
                if (!empty($carData['vol'])) {
                    $engineVolumeCm3 = (int)$carData['vol'];
                    $engineVolumeLiters = $engineVolumeCm3 / 1000;
                    echo "[" . date('Y-m-d H:i:s') . "] Converted to: {$engineVolumeLiters} liters\n";
                    
                    // Map engine volume to 999.md option IDs. Keys MUST be strings: PHP
                    // truncates a float array key to int, so 0.7…0.9 all collapse onto
                    // key 0 and 5.0…5.9 onto key 5, the last entry of each bucket winning.
                    // Every car went out with the biggest volume in its bucket — a 5.7 L
                    // sent as 5.9 (43719), a 2.0 L as 2.9.
                    $volumeMap = [
                        "0.7" => "43671", "0.8" => "43672", "0.9" => "43673", "1.0" => "43674",
                        "1.1" => "43675", "1.2" => "43676", "1.3" => "43677", "1.4" => "43678",
                        "1.5" => "43679", "1.6" => "43680", "1.7" => "43681", "1.8" => "43682",
                        "1.9" => "43683", "2.0" => "43684", "2.1" => "43685", "2.2" => "43686",
                        "2.3" => "43687", "2.4" => "43688", "2.5" => "43689", "2.6" => "43690",
                        "2.7" => "43691", "2.8" => "43692", "2.9" => "43693", "3.0" => "43694",
                        "3.1" => "43695", "3.2" => "43696", "3.3" => "43697", "3.4" => "43698",
                        "3.5" => "43699", "3.6" => "43700", "3.8" => "43701", "3.9" => "43702",
                        "4.0" => "43703", "4.2" => "43704", "4.3" => "43705", "4.4" => "43706",
                        "4.5" => "43707", "4.6" => "43708", "4.7" => "43709", "4.8" => "43710",
                        "5.0" => "43711", "5.2" => "43712", "5.3" => "43713", "5.4" => "43714",
                        "5.5" => "43715", "5.6" => "43716", "5.7" => "43717", "5.8" => "43718",
                        "5.9" => "43719", "6.0" => "43720", "6.2" => "43721", "6.4" => "43722",
                        "6.6" => "43723", "6.7" => "43724", "8.0" => "43763"
                    ];

                    // Round to nearest 0.1 liter, formatted so "2" becomes "2.0"
                    $roundedVolume = number_format(round($engineVolumeLiters, 1), 1, '.', '');
                    $optionId = $volumeMap[$roundedVolume] ?? null;
                    
                    // Check existing features and fix empty engine volume
                    $hasFeature103 = false;
                    $hasFeature2553 = false;
                    
                    foreach ($featuresData['features'] as $index => $feature) {
                        if ($feature['id'] === '103') {
                            $hasFeature103 = true;
                            echo "[" . date('Y-m-d H:i:s') . "] Found feature 103 with value: " . ($feature['value'] ?? 'NULL') . "\n";
                        }
                        if ($feature['id'] === '2553') {
                            $hasFeature2553 = true;
                            echo "[" . date('Y-m-d H:i:s') . "] Found feature 2553 with value: " . ($feature['value'] ?? 'NULL') . "\n";
                            // Check if feature 2553 is empty or invalid
                            if (empty($feature['value']) || trim($feature['value']) === '') {
                                if ($optionId) {
                                    $featuresData['features'][$index]['value'] = $optionId;
                                    echo "[" . date('Y-m-d H:i:s') . "] Updated feature 2553 to: {$optionId}\n";
                                }
                            }
                        }
                    }
                    
                    // If feature 2553 doesn't exist, add it (even if feature 103 exists)
                    if (!$hasFeature2553 && $optionId) {
                        $featuresData['features'][] = [
                            "id" => "2553",
                            "value" => $optionId
                        ];
                        echo "[" . date('Y-m-d H:i:s') . "] Added feature 2553 with value: {$optionId}\n";
                    }

                    // Feature 585 (model as TEXT) belongs ONLY to commercial ads
                    // (subcategory 660). Normal cars (659) carry the model on feature
                    // 21 (a select), so 585 must NOT be added there.
                    if ((string)($featuresData['subcategory_id'] ?? '') === '660') {
                        $hasFeature585 = false;
                        $model585 = '';
                        if (!empty($carData['br']) && !empty($carData['mo'])) {
                            $mStmt = $db->prepare("SELECT mo_nm FROM {$prefx}_car_list WHERE br = ? AND mo = ? LIMIT 1");
                            $mStmt->execute([$carData['br'], $carData['mo']]);
                            $model585 = trim((string)$mStmt->fetchColumn());
                        }
                        foreach ($featuresData['features'] as $idx585 => $f585) {
                            if (($f585['id'] ?? '') === '585') {
                                $hasFeature585 = true;
                                if (empty($f585['value']) && $model585 !== '') {
                                    $featuresData['features'][$idx585]['value'] = $model585;
                                    echo "[" . date('Y-m-d H:i:s') . "] Filled feature 585 (model): {$model585}\n";
                                }
                                break;
                            }
                        }
                        if (!$hasFeature585 && $model585 !== '') {
                            $featuresData['features'][] = ['id' => '585', 'value' => $model585];
                            echo "[" . date('Y-m-d H:i:s') . "] Added feature 585 (model): {$model585}\n";
                        }
                    }

                    echo "[" . date('Y-m-d H:i:s') . "] Final check - hasFeature103: " . ($hasFeature103 ? 'YES' : 'NO') . ", hasFeature2553: " . ($hasFeature2553 ? 'YES' : 'NO') . ", optionId: " . ($optionId ?? 'NULL') . "\n";
                }
                
                // Create new 999.md listing using saved data
                try {
                    // Create API service with the determined account ID
                    if ($catalogType === 'in_stock') {
                        $accountIdForApi = $apiAccountId ?? 2;
                    } elseif ($catalogType === 'on_order') {
                        if ($apiAccountId == 4) $accountIdForApi = 4;
                        elseif ($apiAccountId == 2) $accountIdForApi = 2;
                        elseif ($apiAccountId == 5) $accountIdForApi = 5;
                        else $accountIdForApi = 3;
                    } else {
                        $accountIdForApi = 3;
                    }
                    $api999Service = new \App\Services\Api999Service($accountIdForApi);

                    // Daily image-upload limit (999.md: ~1200 images/day) OR a persistent
                    // per-account throttle (account 2 stays 403 even at 0 images). If this
                    // account is on cooldown we DON'T skip the car when a healthy fallback
                    // upload account exists — image IDs are global, so we just upload the
                    // photos on the fallback and still publish on this account. Only skip
                    // when there's genuinely nowhere left to upload.
                    $cooldownUntil = isAccountOnCooldown($accountIdForApi, $db, $prefx);
                    $accountThrottled = (!empty($rateLimitedAccounts[$accountIdForApi]) || $cooldownUntil);
                    $fallbackAvailable = false;
                    foreach (IMAGE_UPLOAD_FALLBACKS as $fb) {
                        if ((int)$fb === (int)$accountIdForApi) continue;
                        if (empty($rateLimitedAccounts[$fb]) && !isAccountOnCooldown($fb, $db, $prefx)) { $fallbackAvailable = true; break; }
                    }
                    if ($accountThrottled && !$fallbackAvailable) {
                        // Throttled AND no healthy fallback — postpone. NOT the car's fault,
                        // so DON'T burn retry_count; it retries once a quota frees up.
                        $note = $cooldownUntil
                            ? 'Account image upload throttled - waiting for reset'
                            : 'Account rate-limited this run (will retry)';
                        $stmt = $db->prepare("
                            UPDATE gh3sp_sauto_personal_schedules
                            SET status = 'postponed',
                                last_retry_at = NOW(),
                                error_message = :note
                            WHERE id = :id
                        ");
                        $stmt->execute(['note' => $note, 'id' => $schedule['id']]);
                        $when = $cooldownUntil ? ' until ' . date('H:i', $cooldownUntil) : '';
                        echo "[" . date('Y-m-d H:i:s') . "] ⏸️ Account {$accountIdForApi} throttled{$when}, no fallback - postponing car {$schedule['car_id']}\n";
                        continue;
                    }
                    // If the advert's own account is throttled but a fallback is healthy,
                    // skip uploading on the throttled account entirely (it would just 403
                    // again) and go straight to the fallback for THIS car's photos.
                    $skipOwnUpload = $accountThrottled && $fallbackAvailable;

                    // (Re)build feature 14 (images) from car_pht — the saved `999` column
                    // for cars created via /parsing has no images, which 999.md rejects
                    // with feature_id 14 "Completați câmpul".
                    $imgRateLimited = false;
                    $imageIds = buildImagesFeature14($schedule['car_id'], $accountIdForApi, $db, $prefx, $imgRateLimited, $skipOwnUpload);
                    if ($imgRateLimited) {
                        // Remember for the rest of this run AND persist a cooldown until
                        // tomorrow (the daily quota likely ran out), so future runs skip it.
                        $rateLimitedAccounts[$accountIdForApi] = true;
                        setAccountCooldownUntilTomorrow($accountIdForApi, $db, $prefx);
                    }
                    if (empty($imageIds)) {
                        // No usable images. If it was a rate-limit (403, $imgRateLimited),
                        // it's not the car's fault — DON'T burn retry_count (cooldown was
                        // just set; it'll retry tomorrow). Only a genuine "no photos / upload
                        // failed" case counts toward the 72 retries.
                        $currentRetryCount = isset($schedule['retry_count']) ? (int)$schedule['retry_count'] : 0;
                        if ($imgRateLimited) {
                            $stmt = $db->prepare("
                                UPDATE gh3sp_sauto_personal_schedules
                                SET status = 'postponed', last_retry_at = NOW(),
                                    error_message = 'Account daily image limit reached - waiting for reset'
                                WHERE id = :id
                            ");
                            $stmt->execute(['id' => $schedule['id']]);
                            echo "[" . date('Y-m-d H:i:s') . "] ⏸️ Daily image limit - postponed car {$schedule['car_id']} (no retry burned)\n";
                        } elseif ($currentRetryCount >= 72) {
                            // 72 is the documented ceiling, but nothing enforced it here:
                            // car 75320 has no photos at all in car_pht and came back to
                            // the head of the queue at retry 950, taking a slot every run
                            // to fail the same way. A car with no photos will not grow
                            // some by itself — stop asking.
                            $stmt = $db->prepare("
                                UPDATE gh3sp_sauto_personal_schedules
                                SET status = 'failed',
                                    error_message = 'No photos in car_pht after 72 retries — car has no images to publish'
                                WHERE id = :id
                            ");
                            $stmt->execute(['id' => $schedule['id']]);
                            echo "[" . date('Y-m-d H:i:s') . "] ❌ car {$schedule['car_id']} has no photos and passed 72 retries — giving up on schedule {$schedule['id']}\n";
                        } else {
                            $stmt = $db->prepare("
                                UPDATE gh3sp_sauto_personal_schedules
                                SET status = 'postponed', retry_count = retry_count + 1, last_retry_at = NOW(),
                                    error_message = 'No images uploaded for feature 14 (will retry)'
                                WHERE id = :id
                            ");
                            $stmt->execute(['id' => $schedule['id']]);
                            echo "[" . date('Y-m-d H:i:s') . "] ⏸️ No images for feature 14 - postponed car {$schedule['car_id']} (retry " . ($currentRetryCount + 1) . "/72)\n";
                        }
                        continue;
                    }
                    // Drop any stale feature 14, then add the freshly uploaded one.
                    $featuresData['features'] = array_values(array_filter(
                        $featuresData['features'],
                        fn($f) => (string)($f['id'] ?? '') !== '14'
                    ));
                    $featuresData['features'][] = ['id' => '14', 'value' => $imageIds];

                    $featuresData['features'] = enforcePhoneForAccount($featuresData['features'], $accountIdForApi);
                    $result = $api999Service->setAdvert(
                        $featuresData['category_id'],
                        $featuresData['subcategory_id'],
                        $featuresData['offer_type'],
                        $featuresData['features']
                    );
                    
                    if ($result && isset($result['advert']['id'])) {
                        $new999Id = $result['advert']['id'];
                        
                        // Update car with new 999.md ID
                        $updateCarStmt = $db->prepare("
                            UPDATE {$prefx}_car_ctlg
                            SET `999_id` = :new_999_id
                            WHERE id = :car_id
                        ");
                        $updateCarStmt->execute([
                            'new_999_id' => $new999Id,
                            'car_id' => $schedule['car_id']
                        ]);

                        // Persist WHAT WE PUBLISHED, not the payload we started from —
                        // the links/phone/images were added in memory only. Without this
                        // the stored copy stays link-less, and the next push of it (price
                        // resync, car edit, renewal) wipes the links off the live ad.
                        $db->prepare("UPDATE {$prefx}_car_ctlg SET `999` = :js WHERE id = :car_id")
                           ->execute([
                               'js' => json_encode($featuresData, JSON_UNESCAPED_UNICODE),
                               'car_id' => $schedule['car_id'],
                           ]);

                        // Update schedule as published
                        $stmt = $db->prepare("
                            UPDATE gh3sp_sauto_personal_schedules 
                            SET status = 'published', published_at = NOW(), `999_id` = :api_id
                            WHERE id = :id
                        ");
                        $stmt->execute(['api_id' => $new999Id, 'id' => $schedule['id']]);
                        
                        echo "[" . date('Y-m-d H:i:s') . "] ✅ Successfully created new 999.md listing {$new999Id} for car {$schedule['car_id']}\n";
                    } else {
                        // Capture full API response — could be {"code":400,"errors":[...],"message":"...","reason":"..."} or other shape
                        if (isset($result['error'])) {
                            $errorMsg = is_array($result['error']) ? json_encode($result['error'], JSON_UNESCAPED_UNICODE) : (string)$result['error'];
                        } elseif (isset($result['message']) || isset($result['errors']) || isset($result['reason'])) {
                            $errorMsg = json_encode($result, JSON_UNESCAPED_UNICODE);
                        } else {
                            $errorMsg = 'Failed to create 999.md listing | raw: ' . json_encode($result, JSON_UNESCAPED_UNICODE);
                        }

                        $isInsufficientBalance = (stripos($errorMsg, 'insufficient balance') !== false || stripos($errorMsg, 'insufficient funds') !== false || stripos($errorMsg, 'баланс') !== false);
                        if ($isInsufficientBalance) {
                            // Park the CHANNEL for an hour, not just this car: otherwise its rows
                            // keep occupying the head of the queue and the other channels starve.
                            setAccountBalanceCooldown($accountIdForApi ?? $apiAccountId ?? 0, $db, $prefx);
                        }
                        $currentRetryCount = isset($schedule['retry_count']) ? (int)$schedule['retry_count'] : 0;
                        
                        if ($isInsufficientBalance && $currentRetryCount < 72) {
                            $stmt = $db->prepare("
                                UPDATE gh3sp_sauto_personal_schedules 
                                SET status = 'postponed', 
                                    error_message = :error,
                                    retry_count = retry_count + 1,
                                    last_retry_at = NOW()
                                WHERE id = :id
                            ");
                            $stmt->execute(['error' => $errorMsg, 'id' => $schedule['id']]);
                            echo "[" . date('Y-m-d H:i:s') . "] ⏸️ Insufficient balance - postponed create for car {$schedule['car_id']} (retry " . ($currentRetryCount + 1) . "/72, next retry in 2h)\n";
                        } else {
                            $stmt = $db->prepare("
                                UPDATE gh3sp_sauto_personal_schedules
                                SET status = 'failed', error_message = :error
                                WHERE id = :id
                            ");
                            $stmt->execute(['error' => $errorMsg, 'id' => $schedule['id']]);
                            echo "[" . date('Y-m-d H:i:s') . "] ❌ Failed to create listing: $errorMsg\n";

                            // 999 rejected the ad itself, so every other queued row for this
                            // car would be rejected the same way — and each attempt spends a
                            // full photo upload BEFORE the rejection. One Dodge Ram burned
                            // 580 images in a day this way, half of the account's allowance,
                            // without publishing anything. The renewal rows are pointless
                            // regardless: there is no live advert for them to renew.
                            $kill = $db->prepare("UPDATE gh3sp_sauto_personal_schedules
                                SET status = 'failed', error_message = :error
                                WHERE car_id = :car AND id <> :id AND status IN ('pending','postponed')");
                            $kill->execute([
                                'error' => 'First publish failed for this car: ' . mb_substr($errorMsg, 0, 400),
                                'car'   => $schedule['car_id'],
                                'id'    => $schedule['id'],
                            ]);
                            $alsoFailed = $kill->rowCount();
                            if ($alsoFailed > 0) {
                                echo "[" . date('Y-m-d H:i:s') . "] Dropped {$alsoFailed} further queued row(s) for car {$schedule['car_id']} — they would fail identically\n";
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // Throwable, not Exception: a PHP Error (missing class, type error)
                    // is not an Exception, so it escaped here, hit the outer handler and
                    // ended the whole run with exit(1) — leaving the row 'pending' for the
                    // next run to pick, fail on, and die again. Catching it here costs one
                    // car instead of the entire queue.
                    $exMsg = $e->getMessage();
                    $isInsufficientBalance = (stripos($exMsg, 'insufficient balance') !== false || stripos($exMsg, 'insufficient funds') !== false || stripos($exMsg, 'баланс') !== false);
                    if ($isInsufficientBalance) {
                        // Park the CHANNEL for an hour, not just this car: otherwise its rows
                        // keep occupying the head of the queue and the other channels starve.
                        setAccountBalanceCooldown($accountIdForApi ?? $apiAccountId ?? 0, $db, $prefx);
                    }
                    $currentRetryCount = isset($schedule['retry_count']) ? (int)$schedule['retry_count'] : 0;
                    
                    if ($isInsufficientBalance && $currentRetryCount < 72) {
                        $stmt = $db->prepare("
                            UPDATE gh3sp_sauto_personal_schedules 
                            SET status = 'postponed', 
                                error_message = :error,
                                retry_count = retry_count + 1,
                                last_retry_at = NOW()
                            WHERE id = :id
                        ");
                        $stmt->execute(['error' => $exMsg, 'id' => $schedule['id']]);
                        echo "[" . date('Y-m-d H:i:s') . "] ⏸️ Insufficient balance (exception) - postponed for car {$schedule['car_id']} (retry " . ($currentRetryCount + 1) . "/72, next retry in 2h)\n";
                    } else {
                        $stmt = $db->prepare("
                            UPDATE gh3sp_sauto_personal_schedules 
                            SET status = 'failed', error_message = :error
                            WHERE id = :id
                        ");
                        $stmt->execute(['error' => $exMsg, 'id' => $schedule['id']]);
                        echo "[" . date('Y-m-d H:i:s') . "] ❌ Exception: " . $exMsg . "\n";
                    }
                }
            }
            
        } catch (\Throwable $e) {
            // Throwable, not Exception: a PHP Error (missing class, type error, calling a
            // method on null) is NOT an Exception, so it escaped this handler AND the
            // outer one and killed the entire run. The row stayed 'pending', the next run
            // picked the same two oldest rows, died again — and publishing stopped dead
            // without a single failed row to show for it.
            $exMsg = $e->getMessage();
            $isInsufficientBalance = (stripos($exMsg, 'insufficient balance') !== false || stripos($exMsg, 'insufficient funds') !== false || stripos($exMsg, 'баланс') !== false);
            if ($isInsufficientBalance) {
                // Park the CHANNEL for an hour, not just this car: otherwise its rows
                // keep occupying the head of the queue and the other channels starve.
                setAccountBalanceCooldown($accountIdForApi ?? $apiAccountId ?? 0, $db, $prefx);
            }
            $currentRetryCount = isset($schedule['retry_count']) ? (int)$schedule['retry_count'] : 0;

            if ($isInsufficientBalance && $currentRetryCount < 72) {
                $stmt = $db->prepare("
                    UPDATE gh3sp_sauto_personal_schedules 
                    SET status = 'postponed', 
                        error_message = :error,
                        retry_count = retry_count + 1,
                        last_retry_at = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    'error' => $exMsg,
                    'id' => $schedule['id']
                ]);
                echo "[" . date('Y-m-d H:i:s') . "] ⏸️ Insufficient balance - postponed schedule {$schedule['id']} for car {$schedule['car_id']} (retry " . ($currentRetryCount + 1) . "/72, next retry in 2h)\n";
            } else {
                $stmt = $db->prepare("
                    UPDATE gh3sp_sauto_personal_schedules 
                    SET status = 'failed', 
                        error_message = :error
                    WHERE id = :id
                ");
                $stmt->execute([
                    'error' => $exMsg,
                    'id' => $schedule['id']
                ]);
                echo "[" . date('Y-m-d H:i:s') . "] Failed to process schedule ID: {$schedule['id']}, Error: " . $exMsg . "\n";
            }
        }
        
        // Small delay between republishes
        sleep(1);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] SAUTO Personal Cron Completed\n";
    
} catch (\Throwable $e) {
    // File and line too — a bare message ("Class not found") tells you nothing about
    // which of the hand-written require_once lines is missing.
    echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage()
         . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}
