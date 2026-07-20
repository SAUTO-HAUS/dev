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

function enforcePhoneForAccount($features, $accountId) {
    $phoneMap = [
        2 => '37379600616',
        3 => '37379600326',
        4 => '37379603161',
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

const IMAGE_UPLOAD_FALLBACKS = [3, 4];

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
function buildImagesFeature14($carId, $accountId, $db, $prefx, &$rateLimited = false, $skipOwnUpload = false) {
    $rateLimited = false;
    $stmt = $db->prepare("SELECT * FROM {$prefx}_car_pht WHERE `it_id` = :carId ORDER BY `main` DESC, `pos`, `id`");
    $stmt->execute(['carId' => $carId]);
    $photos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($photos)) {
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: no photos in car_pht for car {$carId}\n";
        return [];
    }

    // Image cap on 999: every parsing car (Encar/OpenLane/eCarsTrade) publishes max
    // 10 photos here — regardless of the higher sauto cap (Encar 20 there). Account 4
    // (Korea) is always 10. Everything else keeps 20. Detect parsing by parsing_id.
    $isParsing = false;
    try {
        $pchk = $db->prepare("SELECT parsing_id FROM {$prefx}_car_ctlg WHERE id = :id");
        $pchk->execute(['id' => $carId]);
        $isParsing = !empty($pchk->fetchColumn());
    } catch (\Throwable $e) { /* column may be absent on old installs */ }
    $maxImages = ($accountId == 4 || $isParsing) ? 10 : 20;
    $photos = array_slice($photos, 0, $maxImages);

    // Resolve every photo to a real file path once. Running from CLI cron —
    // $_SERVER['DOCUMENT_ROOT'] is unreliable, so resolve relative to this script.
    $mediaBase = __DIR__ . '/../media/images/upload/car/';
    $paths = [];
    foreach ($photos as $img) {
        $imgPath = $mediaBase . $img['path'] . '/' . $img['it_id'] . '/high/' . $img['name'] . '.' . $img['ff'];
        if (!file_exists($imgPath)) {
            echo "[" . date('Y-m-d H:i:s') . "] Feature 14: missing file {$imgPath}\n";
            continue;
        }
        $paths[] = $imgPath;
    }
    if (empty($paths)) return [];

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

    foreach ($tryAccounts as $uploadAcc) {
        $got403 = false;
        $imageIds = uploadPhotoListOnAccount($paths, $uploadAcc, $carId, $got403);
        // The ORIGINAL account being throttled is what the caller cools down / doesn't
        // penalise the car for — so only flag $rateLimited for it, not the fallbacks.
        if ((int)$uploadAcc === (int)$accountId && $got403) $rateLimited = true;

        if (!empty($imageIds)) {
            if ($uploadAcc != $accountId) {
                echo "[" . date('Y-m-d H:i:s') . "] Feature 14: uploaded via fallback account {$uploadAcc} (advert stays on account {$accountId})\n";
            }
            return $imageIds;
        }
        // Only advance to a fallback when the failure was a 403 throttle. A genuine
        // failure (missing files, other error) would fail identically elsewhere.
        if (!$got403) break;
        echo "[" . date('Y-m-d H:i:s') . "] Feature 14: account {$uploadAcc} throttled (403) — trying next account for images\n";
    }

    return [];
}

/**
 * Upload a list of image file paths to one 999.md account. Returns the uploaded image
 * IDs; sets $got403 when the account's /images endpoint returned 403 (throttle/block),
 * which tells the caller to try a different account.
 */
function uploadPhotoListOnAccount(array $paths, $accountId, $carId, &$got403 = false) {
    $got403 = false;
    $api = new \App\Services\Api999Service($accountId);
    $imageIds = [];
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
                echo "[" . date('Y-m-d H:i:s') . "] Feature 14: got 403 — account {$accountId} blocked/rate-limited, stopping uploads\n";
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
        // Steady pace between every image so we never burst nginx.
        usleep(700000); // 0.7s
    }

    echo "[" . date('Y-m-d H:i:s') . "] Feature 14: uploaded " . count($imageIds) . " image(s) for car {$carId} on account {$accountId}\n";
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

// Roughly how many images this account has uploaded TODAY = cars it published today
// × the per-car image cap (10 parsing / 20 otherwise). Used to tell a real daily-limit
// 403 (near the 1200 quota) from a transient throttle 403 (well below it).
function imagesUploadedToday($accountId, $db, $prefx) {
    $perCar = ((int)$accountId === 4) ? 10 : 20;
    $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefx}_sauto_personal_schedules s
        JOIN {$prefx}_car_ctlg c ON c.id = s.car_id
        WHERE c.`999_api_id` = ? AND s.status = 'published'
          AND DATE(s.published_at) = CURDATE()");
    $stmt->execute([$accountId]);
    return (int)$stmt->fetchColumn() * $perCar;
}

// A 403 on image upload can mean TWO different things:
//   (a) the real daily 1200-image quota is exhausted → cool down until tomorrow;
//   (b) a transient throttle (999/nginx "too many requests right now") that hits
//       well below 1200 → a full-day cooldown wrongly parks the account and loses
//       the ~200-400 images of headroom it still has.
// So: only cool down until tomorrow when we're actually near the quota; otherwise
// back off a few minutes and let the next cron run retry.
function setAccountCooldownUntilTomorrow($accountId, $db, $prefx) {
    $uploadedToday = imagesUploadedToday($accountId, $db, $prefx);
    $DAILY_QUOTA = 1200;

    if ($uploadedToday < $DAILY_QUOTA - 150) {
        // Well under the quota → transient throttle, not the daily cap. Short cooldown.
        $until = time() + 600; // 10 min
        $stmt = $db->prepare("INSERT INTO {$prefx}_settings (name, value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)");
        $stmt->execute(["999md_upload_cooldown_{$accountId}", (string)$until]);
        echo "[" . date('Y-m-d H:i:s') . "] Account {$accountId} got a 403 at ~{$uploadedToday}/{$DAILY_QUOTA} images (transient throttle) — short 10-min cooldown\n";
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
        
        if ($featureId === '13' || $featureId === 13) {
            $descriptionText = (string)($feature['value'] ?? '');
            $descriptionText = preg_replace(
                '/Detalii despre automobil:\s*\n'
                . 'https?:\/\/\S+\s*\n'
                . 'Toate automobilele modelului[^\n]*\n'
                . 'https?:\/\/\S+\s*\n'
                . 'Toate automobilele mărcii[^\n]*\n'
                . 'https?:\/\/\S+\s*/u',
                '',
                $descriptionText
            );
            $descriptionText = trim((string)$descriptionText);

            if (!empty($carData['br']) && !empty($carData['mo'])) {
                $stmtCarList = $db->prepare("SELECT br_nm, mo_nm FROM {$prefx}_car_list WHERE br = ? AND mo = ? LIMIT 1");
                $stmtCarList->execute([$carData['br'], $carData['mo']]);
                $carListInfo = $stmtCarList->fetch(PDO::FETCH_ASSOC);

                if ($carListInfo && !empty($carListInfo['br_nm']) && !empty($carListInfo['mo_nm'])) {
                    $brandSlug = strtolower(str_replace('_', '-', $carData['br']));
                    $modelSlug = strtolower(str_replace('_', '-', $carData['mo']));
                    $brandText = $carListInfo['br_nm'];
                    $modelText = $carListInfo['mo_nm'];

                    $section = ($carData['catalog_type'] === 'on_order') ? 'ordercars' : 'cars';

                    $carLink = "https://www.sauto.md/ro/{$section}/{$carData['id']}";
                    $modelLink = "https://www.sauto.md/ro/{$section}/{$brandSlug}/{$modelSlug}";
                    $brandLink = "https://www.sauto.md/ro/{$section}/{$brandSlug}";

                    $linksText = "Detalii despre automobil:\n{$carLink}\nToate automobilele modelului {$modelText}:\n{$modelLink}\nToate automobilele mărcii {$brandText}:\n{$brandLink}";

                    $descriptionText = $linksText . ($descriptionText !== '' ? "\n\n" . $descriptionText : '');
                }
            }

            $feature['value'] = $descriptionText;
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
    
    return $updatedFeatures;
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
    $cooldownAccounts = [];
    $cdStmt = $db->query("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '999md_upload_cooldown_%'");
    foreach ($cdStmt->fetchAll() as $row) {
        if ((int)$row['value'] > time()) {
            $cooldownAccounts[] = (int)str_replace('999md_upload_cooldown_', '', $row['name']);
        }
    }
    $cooldownSql = '';
    if (!empty($cooldownAccounts)) {
        $cooldownSql = ' AND (c.`999_api_id` IS NULL OR c.`999_api_id` NOT IN (' . implode(',', array_map('intval', $cooldownAccounts)) . ')) ';
        echo "[" . date('Y-m-d H:i:s') . "] Accounts on cooldown (excluded this run): " . implode(',', $cooldownAccounts) . "\n";
    }

    $stmt = $db->prepare("
        SELECT s.*, c.id as car_id, c.`999_id` as existing_999_id, s.catalog_type,
               CONCAT(s.schedule_date, ' ', s.schedule_time) as full_schedule_time
        FROM gh3sp_sauto_personal_schedules s
        LEFT JOIN {$prefx}_car_ctlg c ON s.car_id = c.id
        WHERE s.status = 'pending'
        AND CONCAT(s.schedule_date, ' ', s.schedule_time) <= :current_time
        {$cooldownSql}
        ORDER BY s.schedule_date, s.schedule_time
        LIMIT 10
    ");
    $stmt->execute(['current_time' => $currentDateTime]);
    $pendingSchedules = $stmt->fetchAll();
    
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
            $carStmt = $db->prepare("SELECT `999_api_id`, import_country_id, gr, n_a FROM {$prefx}_car_ctlg WHERE id = :car_id");
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

                if ($isCom && $importCountry === 41) {
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
                echo "[" . date('Y-m-d H:i:s') . "] Авто {$schedule['car_id']} имеет 999.md ID: {$schedule['existing_999_id']} - обновление и републикация на {$apiAccount}\n";

                if ($catalogType === 'in_stock') {
                    $accountIdForApi = $apiAccountId ?? 2;
                } elseif ($catalogType === 'on_order') {
                    if ($apiAccountId == 4) $accountIdForApi = 4;
                    elseif ($apiAccountId == 2) $accountIdForApi = 2;
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
                
                // CRITICAL FIX: Update features with FRESH data from car card before publishing
                echo "[" . date('Y-m-d H:i:s') . "] Обновление features актуальными данными из карточки авто...\n";
                $featuresData['features'] = updateFeaturesWithFreshData($featuresData['features'], $carData, $db, $prefx);
                
                // Apply smart engine volume conversion before API call
                echo "[" . date('Y-m-d H:i:s') . "] Car volume from DB: " . ($carData['vol'] ?? 'NULL') . " cm³\n";
                if (!empty($carData['vol'])) {
                    $engineVolumeCm3 = (int)$carData['vol'];
                    $engineVolumeLiters = $engineVolumeCm3 / 1000;
                    echo "[" . date('Y-m-d H:i:s') . "] Converted to: {$engineVolumeLiters} liters\n";
                    
                    // Map engine volume to 999.md option IDs
                    $volumeMap = [
                        0.7 => "43671", 0.8 => "43672", 0.9 => "43673", 1.0 => "43674",
                        1.1 => "43675", 1.2 => "43676", 1.3 => "43677", 1.4 => "43678",
                        1.5 => "43679", 1.6 => "43680", 1.7 => "43681", 1.8 => "43682",
                        1.9 => "43683", 2.0 => "43684", 2.1 => "43685", 2.2 => "43686",
                        2.3 => "43687", 2.4 => "43688", 2.5 => "43689", 2.6 => "43690",
                        2.7 => "43691", 2.8 => "43692", 2.9 => "43693", 3.0 => "43694",
                        3.1 => "43695", 3.2 => "43696", 3.3 => "43697", 3.4 => "43698",
                        3.5 => "43699", 3.6 => "43700", 3.8 => "43701", 3.9 => "43702",
                        4.0 => "43703", 4.2 => "43704", 4.3 => "43705", 4.4 => "43706",
                        4.5 => "43707", 4.6 => "43708", 4.7 => "43709", 4.8 => "43710",
                        5.0 => "43711", 5.2 => "43712", 5.3 => "43713", 5.4 => "43714",
                        5.5 => "43715", 5.6 => "43716", 5.7 => "43717", 5.8 => "43718",
                        5.9 => "43719", 6.0 => "43720", 6.2 => "43721", 6.4 => "43722",
                        6.6 => "43723", 6.7 => "43724"
                    ];
                    
                    // Round to nearest 0.1 liter
                    $roundedVolume = round($engineVolumeLiters, 1);
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
                        }
                    }
                } catch (Exception $e) {
                    $exMsg = $e->getMessage();
                    $isInsufficientBalance = (stripos($exMsg, 'insufficient balance') !== false || stripos($exMsg, 'insufficient funds') !== false || stripos($exMsg, 'баланс') !== false);
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
            
        } catch (Exception $e) {
            $exMsg = $e->getMessage();
            $isInsufficientBalance = (stripos($exMsg, 'insufficient balance') !== false || stripos($exMsg, 'insufficient funds') !== false || stripos($exMsg, 'баланс') !== false);
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
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
