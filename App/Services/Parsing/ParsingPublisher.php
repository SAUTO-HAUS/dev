<?php

namespace App\Services\Parsing;

use App\Core\Container;
use App\Services\AutoPublicationService;
use Exception;
use PDO;

class ParsingPublisher
{
    private $db;
    private $prefix;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
    }

    public function publish(int $parsingCarId, string $target = 'both'): array
    {
        $row = $this->loadParsingCar($parsingCarId);
        if (!$row) {
            return ['success' => false, 'error' => 'Parsing car not found'];
        }

        $existingCtlgId = !empty($row['car_ctlg_id']) ? (int)$row['car_ctlg_id'] : null;

        // Cross-posting to 999 / Facebook / Telegram from the Published page:
        // the car is already on sauto.md (has a car_ctlg row), so we don't
        // re-insert — we publish to ONLY the chosen platform.
        if (in_array($target, ['999', 'facebook', 'telegram'], true)) {
            if (!$existingCtlgId) {
                return ['success' => false, 'error' => 'Mașina trebuie publicată pe sauto.md înainte.'];
            }
            try {
                $service = new AutoPublicationService($this->db, $this->prefix);
                $res = $service->publishToPlatform($existingCtlgId, $target);
                if (empty($res['success'])) {
                    return ['success' => false, 'error' => $res['error'] ?? 'Publish failed'];
                }
                // Mark the specific platform flag on the parsing row.
                $flagCol = ['999' => 'published_999', 'facebook' => 'published_fb', 'telegram' => 'published_tg'][$target] ?? null;
                if ($flagCol) {
                    $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars SET `'.$flagCol.'` = 1 WHERE id = ?')
                             ->execute([$parsingCarId]);
                }
                return ['success' => true, 'car_ctlg_id' => $existingCtlgId];
            } catch (Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // sauto / all: insert into the catalog (only if not already there).
        if ($row['status'] === 'published') {
            return ['success' => false, 'error' => 'Already published'];
        }

        try {
            $this->db->beginTransaction();

            $carCtlgId = null;
            if (in_array($target, ['sauto', 'all'], true)) {
                $carCtlgId = $this->insertIntoCarCtlg($row);
                $this->markPublishedOnSauto($parsingCarId, $carCtlgId);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'DB error: ' . $e->getMessage()];
        }

        // Photos AFTER the catalog row exists (folder + filename use car_ctlg_id)
        // and OUTSIDE the transaction (download + resize is slow). Stored exactly
        // like the sauto upload handler so the public gallery finds them.
        if (in_array($target, ['sauto', 'all'], true) && $carCtlgId) {
            $this->processPhotos($carCtlgId, $row);
            // OpenLane: bake the Condition + Equipment report into parsing_cars.
            // report_data so the public product page shows it from the DB (no live
            // OpenLane call). Best-effort — never blocks publishing.
            $this->bakeOpenlaneReport($parsingCarId, $row);
        }

        // Publishing to sauto ONLY must not auto-post to FB/Telegram/999 (operator
        // cross-posts those on demand from /parsing/published). Auto-publication
        // runs only for the explicit 'all' target. For 'sauto', force the FB/TG
        // flags to 0 so no hook leaves fake "published" badges on the card.
        if ($target === 'all' && $carCtlgId) {
            $this->triggerAutoPublication($carCtlgId);
        } elseif ($target === 'sauto' && $carCtlgId) {
            $this->db->prepare('UPDATE '.$this->prefix.'_car_ctlg
                SET telegram_published = 0, facebook_published = 0 WHERE id = ?')
                ->execute([$carCtlgId]);
        }

        $this->propagatePublicationFlags($parsingCarId, $carCtlgId);

        return ['success' => true, 'car_ctlg_id' => $carCtlgId];
    }

    /**
     * For an OpenLane car: fetch the detail once, render the Condition + Equipment
     * report HTML (3 langs) and store it under parsing_cars.report_data so the
     * public product page can show it from the DB. Best-effort; never throws.
     */
    private function bakeOpenlaneReport(int $parsingCarId, array $row): void
    {
        try {
            if (($row['source'] ?? '') !== 'openlane') return;
            // Already baked? skip.
            if (!empty($row['report_data']) && strpos((string)$row['report_data'], 'openlane_report') !== false) return;

            $raw  = json_decode($row['raw_data'] ?? '{}', true) ?: [];
            $item = (!empty($raw['CarId']) ? $raw : ($raw['raw_data'] ?? $raw));
            $auctionId = (string)($item['AuctionId'] ?? '');
            if ($auctionId === '') return;

            $adapter = AdapterFactory::create('openlane');
            if (!$adapter || !method_exists($adapter, 'fetchDetailRaw')) return;
            $detail = $adapter->fetchDetailRaw($auctionId);
            if (!is_array($detail)) return;

            // Shared renderer (same file the admin modal + public page use).
            $helper = dirname(__DIR__, 3) . '/content/admin/page/parsing/parsing_openlane_report.php';
            if (!function_exists('parsing_openlane_report_html') && is_file($helper)) {
                require_once $helper;
            }
            if (!function_exists('parsing_openlane_report_html')) return;

            $byLang = [];
            foreach (['ro', 'ru', 'en'] as $rl) {
                $byLang[$rl] = parsing_openlane_report_html($detail, $rl, 0);
            }
            $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars SET report_data = ? WHERE id = ?')
                     ->execute([json_encode(['openlane_report' => $byLang], JSON_UNESCAPED_UNICODE), $parsingCarId]);
        } catch (\Throwable $e) {
            // best-effort — publishing must not fail because of the report
        }
    }

    public function unpublish(int $parsingCarId): array
    {
        $row = $this->loadParsingCar($parsingCarId);
        if (!$row) {
            return ['success' => false, 'error' => 'Parsing car not found'];
        }

        try {
            $this->db->beginTransaction();

            if (!empty($row['car_ctlg_id'])) {
                $ctlgId = (int)$row['car_ctlg_id'];

                // Cancel any pending cross-post schedules (999 / Telegram / Facebook)
                // BEFORE the car_ctlg row is gone. The crons only pick status='pending'
                // rows; flipping these to 'cancelled' stops them from posting a car
                // that no longer exists on sauto. Keyed by car_id = car_ctlg id.
                foreach ([
                    $this->prefix.'_sauto_personal_schedules',
                    $this->prefix.'_scheduled_telegram_posts',
                    $this->prefix.'_scheduled_facebook_posts',
                ] as $schedTable) {
                    try {
                        $cancel = $this->db->prepare('UPDATE '.$schedTable.'
                            SET status = "cancelled" WHERE car_id = ? AND status = "pending"');
                        $cancel->execute([$ctlgId]);
                    } catch (Exception $e) {
                        // Table may not exist in some installs — ignore and continue.
                    }
                }

                $stmt = $this->db->prepare('DELETE FROM '.$this->prefix.'_car_pht WHERE it_id = ?');
                $stmt->execute([$ctlgId]);

                $stmt = $this->db->prepare('DELETE FROM '.$this->prefix.'_car_ctlg WHERE id = ?');
                $stmt->execute([$ctlgId]);
            }

            $stmt = $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars
                SET status = "rejected", car_ctlg_id = NULL,
                    published_sauto = 0, published_999 = 0, published_fb = 0, published_tg = 0,
                    rejected_at = NOW()
                WHERE id = ?');
            $stmt->execute([$parsingCarId]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'DB error: ' . $e->getMessage()];
        }

        if (!empty($row['ad_999_id'])) {
            $this->archive999Ad($row['ad_999_id']);
        }

        return ['success' => true];
    }

    private function loadParsingCar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM '.$this->prefix.'_parsing_cars WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function insertIntoCarCtlg(array $parsingRow): int
    {
        $importUserId = $this->getSetting('import_user_id', '1');

        // Prefer the sauto brand/model the browser already resolved (BMW 318 →
        // Seria 3 via match_model). Validate they exist in the catalog; if not,
        // fall back to resolving from the raw brand/model names.
        $brandId = null; $modelId = null;
        if (!empty($parsingRow['sauto_br'])) {
            $brandId = $this->validBrandCode((string)$parsingRow['sauto_br']);
        }
        if (!$brandId) {
            $brandId = $this->resolveBrandId($parsingRow['brand'] ?? '');
        }
        if ($brandId && !empty($parsingRow['sauto_mo'])) {
            $modelId = $this->validModelCode($brandId, (string)$parsingRow['sauto_mo']);
        }
        if ($brandId && !$modelId) {
            $modelId = $this->resolveModelId($parsingRow['model'] ?? '', $brandId);
        }
        if (!$brandId || !$modelId) {
            throw new Exception("Brand/model not in sauto catalog: {$parsingRow['brand']} / {$parsingRow['model']}. Edit the car first to map manually.");
        }

        // Use the OFFICIAL sauto names for the matched codes (so the card shows
        // "Seria 3", not the raw "316"). Fall back to the raw names if a row is
        // somehow missing the display name.
        $names = $this->catalogNames($brandId, $modelId);
        $brName = $names['br_nm'] ?: ($parsingRow['brand'] ?? '');
        $moName = $names['mo_nm'] ?: ($parsingRow['model'] ?? '');

        // Map our codes to the catalog's codes — IDENTICAL to the autopublish form
        // (order_car.php / order_add_new1.php). The car_ctlg columns are NOT NULL,
        // so missing values become '' (never null) and codes are mapped (never raw).
        $fuelMap = [
            'benzina' => 'gsl', 'gasoline' => 'gsl', 'diesel' => 'dsl', 'lpg' => 'gas',
            'hybrid' => 'hbd', 'gasoline_lpg' => 'gmn', 'gasoline_cng' => 'gmn',
            'electric' => 'elc', 'diesel_hybrid' => 'pid', 'hybrid_plugin' => 'pih',
        ];
        $gearMap = ['automat' => 'atm', 'manual' => 'mnl', 'semi-auto' => 'tpt', 'cvt' => 'vrr'];
        $bodyMap = [
            'sedan' => 'sdn', 'suv' => 'suv', 'hatchback' => 'hbk', 'wagon' => 'unv',
            'coupe' => 'cup', 'crossover' => 'crv', 'minivan' => 'mnv', 'pickup' => 'pkp',
            'van' => 'van', 'convertible' => 'cbr', 'microbus' => 'mbs',
        ];
        $colorMap = [
            '흰색' => 'wht', '검정' => 'blk', '검정색' => 'blk', '은색' => 'slv', '회색' => 'gra',
            '쥐색' => 'gra', '빨간색' => 'red', '파란색' => 'blu', '갈색' => 'brn', '베이지' => 'bge',
            '금색' => 'gld', '노란색' => 'ylw', '주황색' => 'orn', '녹색' => 'grn', '진한녹색' => 'd_grn',
            '연두색' => 'l_grn', '보라색' => 'prp', '분홍색' => 'pnk', '와인색' => 'vns',
            'white' => 'wht', 'black' => 'blk', 'silver' => 'slv', 'gray' => 'gra',
            'grey' => 'gra', 'red' => 'red', 'blue' => 'blu', 'brown' => 'brn',
        ];
        $driveMap = ['4x4' => '44', 'fwd' => 'fr', 'rwd' => 're'];

        $bodyLower = strtolower((string)($parsingRow['body_type'] ?? ''));
        $groupCode = $this->resolveGroup(
            $bodyLower,
            (string)($parsingRow['brand'] ?? ''),
            (string)($parsingRow['model'] ?? '')
        );

        $colorKey  = trim((string)($parsingRow['color'] ?? ''));
        $colorCode = $colorMap[$colorKey] ?? $colorKey;
        $vin = $this->resolveVin($parsingRow);

        $cols = [
            'gr' => $groupCode,
            'br' => $brandId,
            'mo' => $modelId,
            'br_nm' => $brName,
            'mo_nm' => $moName,
            'yr' => $parsingRow['year'] ?? '',
            'vin' => $vin,
            'vin_check_enabled' => $this->vinCheckEnabled($vin),
            'bt' => $bodyMap[$bodyLower] ?? '',
            'sts' => $parsingRow['seats'] ?? '',
            'mlg' => $parsingRow['km'] ?? '',
            'unit' => 'km',
            'vol' => $parsingRow['engine_volume'] ?? '',
            'hp' => $parsingRow['power_hp'] ?? '',
            'fl' => $fuelMap[strtolower((string)($parsingRow['fuel_type'] ?? ''))] ?? '',
            'tra' => $gearMap[strtolower((string)($parsingRow['gearbox'] ?? ''))] ?? '',
            'wd' => $driveMap[$parsingRow['drive_type'] ?? ''] ?? '',
            'clr' => $colorCode,
            'loc' => 1,
            'txt' => $parsingRow['description_ro'] ?? '',
            'prc' => $this->resolvePrice($parsingRow),
            'cur' => 'EUR',
            'soon' => 0,
            'n_a' => 0,
            'top' => 0,
            'tva' => 0,
            'gift' => 0,
            'is_at_client' => 0,
            'import_country_id' => $this->resolveImportCountryId($parsingRow),
            'catalog_type' => 'on_order',
            'delivery_time' => 60,
            'offer_timer' => '60:00:00:00',
            'offer_timer_end' => time() + 60 * 86400,
            'advance_amount' => null,
            // p_path MUST be the md5-hashed year/month (same as the upload handler)
            // — catalog/public pages build the photo URL from car_ctlg.p_path, so
            // it has to match the real photo folder.
            'p_path' => substr(md5(date('Y')), 0, 4) . '/' . substr(md5(date('m')), 0, 4),
            'date' => time(),
            // Real operator name (saved at enqueue), falling back to "Parser" for
            // the cron path where no one was logged in.
            'author' => trim((string)($parsingRow['published_by'] ?? '')) ?: 'Parser',
            'vis' => 1,
            'inf' => '',
            'telegram_published' => 0,
            'facebook_published' => 0,
            'parsing_id' => $parsingRow['id'],
            'parsing_source' => $parsingRow['source'],
        ];

        $columnList = implode('`,`', array_keys($cols));
        $placeholders = implode(',', array_map(fn($k) => ':' . $k, array_keys($cols)));
        $sql = 'INSERT INTO '.$this->prefix.'_car_ctlg (`'.$columnList.'`) VALUES ('.$placeholders.')';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($cols);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Download the source photos and store them EXACTLY like the sauto upload
     * handler (order_file_upload.php) so the public gallery finds them:
     *   _CAR_IMG/{p_path}/{carCtlgId}/{med,high}/car_{carCtlgId}_{pos}.jpg
     * and one car_pht row per photo (path={p_path}, name=car_{id}_{pos}, ff=jpg).
     */
    private function processPhotos(int $carCtlgId, array $parsingRow): void
    {
        $images = json_decode($parsingRow['images_local'] ?? '[]', true);
        if (!is_array($images) || empty($images)) return;

        $urls = [];
        foreach ($images as $img) {
            if (is_array($img) && !empty($img['url'])) $urls[] = $img['url'];
            elseif (is_string($img) && preg_match('#^https?://#i', $img)) $urls[] = $img;
            elseif (is_array($img) && !empty($img['path']) && !empty($img['name'])) {
                $urls[] = '/' . trim($img['path'], '/') . '/' . $img['name'];
            }
        }
        if (empty($urls)) return;

        $source = (string)($parsingRow['source'] ?? '');
        $cap = in_array($source, ['ecarstrade', 'openlane'], true) ? 10 : 30;
        $urls = array_slice($urls, 0, $cap);

        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
        // Use the car's OWN p_path so car_pht.path, the folder, and car_ctlg.p_path
        // always agree (even across a month boundary).
        $pPath = (string)$this->db->query('SELECT p_path FROM '.$this->prefix.'_car_ctlg WHERE id = '.(int)$carCtlgId)->fetchColumn();
        if ($pPath === '' || strpos($pPath, '/') === false) {
            $pPath = substr(md5(date('Y')), 0, 4) . '/' . substr(md5(date('m')), 0, 4);
        }
        $absBase = $docRoot . '/' . ltrim($carImg . '/' . $pPath . '/' . $carCtlgId, '/');
        $referer = $source === 'ecarstrade' ? 'https://www.e-carstrade.com/'
                 : ($source === 'openlane' ? 'https://www.openlane.eu/' : 'https://www.encar.com/');

        $tmpDir = $docRoot . '/tmp';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);

        $ins = $this->db->prepare('INSERT INTO '.$this->prefix.'_car_pht
            (`it_id`, `tp`, `path`, `name`, `ff`, `main`, `pos`)
            VALUES (:it_id, :tp, :path, :name, :ff, :main, :pos)');

        // Photoroom TEMPORARILY DISABLED — Encar photos now come clean from the CDN
        // (small "encar" watermark via encarHiResUrl). Flip this to true to re-enable
        // the cover background removal. The block below stays intact.
        $usePhotoroom = true;
        $photoroom = ($usePhotoroom && $source === 'encar') ? new PhotoroomService() : null;
        // Photoroom must run EXACTLY ONCE (the cover), no matter how many earlier
        // photos fail to download/encode. Tracking this with $pos was buggy:
        // $pos stays 0 until a photo is fully saved, so a failed first photo made
        // the next photo hit Photoroom again — burning extra API credits.
        $coverProcessed = false;

        $fetchUrls = [];
        foreach ($urls as $idx => $url) {
            $fetchUrls[$idx] = ($source === 'encar') ? $this->encarHiResUrl($url) : $url;
        }
        $fetched = $this->fetchImagesParallel($fetchUrls, $referer); // idx => bytes, in order

        // Encar: if a hi-res variant failed, retry that one with the original URL.
        if ($source === 'encar') {
            foreach ($urls as $idx => $url) {
                if (empty($fetched[$idx]) && $fetchUrls[$idx] !== $url) {
                    $b = $this->fetchImage($url, $referer);
                    if ($b !== null) $fetched[$idx] = $b;
                }
            }
            ksort($fetched);
        }

        $pos = 0;
        foreach ($fetched as $bytes) {
            if ($bytes === null || $bytes === '') continue;

            $tmpFile = $tmpDir . '/' . uniqid('pub_' . $carCtlgId . '_', true) . '.jpg';
            if (@file_put_contents($tmpFile, $bytes) === false) continue;
            $info = @getimagesize($tmpFile);
            if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_JPEG) {
                if (!$this->toJpeg($tmpFile)) { @unlink($tmpFile); continue; }
            }
            if (!$coverProcessed && $photoroom && $photoroom->isEnabled()) {
                $coverProcessed = true;
                $clean = $photoroom->removeBackgroundToWhiteJpeg((string)@file_get_contents($tmpFile));
                if ($clean !== null) {
                    @file_put_contents($tmpFile, $clean);
                    error_log('ParsingPublisher: Photoroom cover background removed for car ' . $carCtlgId);
                } else {
                    error_log('ParsingPublisher: Photoroom skipped/failed for car ' . $carCtlgId . ' (kept original)');
                }
            }

            $pos++;
            $name = 'car_' . $carCtlgId . '_' . $pos;

            // All sources, standard sizes (1600/600). See saveHighAndMed().
            $ok = $this->saveHighAndMed($tmpFile, $absBase, $name);

            @unlink($tmpFile);
            if (!$ok) { $pos--; continue; }
            $ins->execute([
                'it_id' => $carCtlgId, 'tp' => 'img', 'path' => $pPath,
                'name' => $name, 'ff' => 'jpg', 'main' => $pos === 1 ? 1 : 0, 'pos' => $pos,
            ]);
        }
    }

    private function fetchImagesParallel(array $urls, string $referer): array
    {
        if (empty($urls)) return [];
        $mh = curl_multi_init();
        $handles = [];
        foreach ($urls as $idx => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'Referer: ' . $referer,
                ],
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$idx] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running && $status === CURLM_OK);

        $out = [];
        foreach ($handles as $idx => $ch) {
            $body = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 200 && is_string($body) && strlen($body) >= 1000) {
                $out[$idx] = $body;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        ksort($out);
        return $out;
    }

    private function encarHiResUrl(string $url): string
    {
        if (strpos($url, 'ci.encar.com') === false) return $url;
        if (strpos($url, '?') !== false) return $url; // already parameterised
        return $url . '?impolicy=heightRate&cw=1200&rh=700&cg=Center&wtmk=https://ci.encar.com/wt_mark/w_mark_04.png';
    }

    private const HIGH_W = 1600;
    private const HIGH_Q = 88;
    private const MED_W  = 800;
    private const MED_Q  = 92;

    private function saveHighAndMed(string $tmpFile, string $absBase, string $name): bool
    {
        $highDir = $absBase . '/high';
        $medDir  = $absBase . '/med';
        if (!is_dir($highDir)) @mkdir($highDir, 0755, true);
        if (!is_dir($medDir))  @mkdir($medDir, 0755, true);

        $highPath = $highDir . '/' . $name . '.jpg';
        $medPath  = $medDir . '/' . $name . '.jpg';

        $info = @getimagesize($tmpFile);
        $srcW = $info[0] ?? 0;
        $src  = ($srcW > 0) ? @imagecreatefromjpeg($tmpFile) : false;

        // /high/: cap at HIGH_W (resize only if larger; else copy = zero loss).
        if ($src !== false && $srcW > self::HIGH_W) {
            $hi = @imagescale($src, self::HIGH_W);
            if ($hi !== false) { imagejpeg($hi, $highPath, self::HIGH_Q); imagedestroy($hi); }
            else { @copy($tmpFile, $highPath); }
        } else {
            @copy($tmpFile, $highPath);
        }

        // /med/: cap at MED_W.
        if ($src !== false && $srcW > self::MED_W) {
            $md = @imagescale($src, self::MED_W);
            if ($md !== false) { imagejpeg($md, $medPath, self::MED_Q); imagedestroy($md); }
            else { @copy($highPath, $medPath); }
        } else {
            @copy($highPath, $medPath);
        }

        if ($src !== false) imagedestroy($src);
        return is_file($highPath) && is_file($medPath);
    }

    // Download one image with a source-appropriate referer. Bytes or null.
    private function fetchImage(string $url, string $referer): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                'Referer: ' . $referer,
            ],
        ]);
        $bytes = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($bytes === false || $code !== 200 || strlen($bytes) < 1000) return null;
        return $bytes;
    }

    // Re-encode a non-JPEG temp file to JPEG in place (the sauto resizer reads JPEG only).
    private function toJpeg(string $tmpFile): bool
    {
        $im = @imagecreatefromstring(@file_get_contents($tmpFile));
        if (!$im) return false;
        $w = imagesx($im); $h = imagesy($im);
        $bg = imagecreatetruecolor($w, $h);
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagecopy($bg, $im, 0, 0, 0, 0, $w, $h);
        $ok = imagejpeg($bg, $tmpFile, 90);
        imagedestroy($im); imagedestroy($bg);
        return $ok;
    }

    // Real 17-char VIN, else masked VIN from report, else 17-zero placeholder.
    private function resolveVin(array $parsingRow): string
    {
        $vin = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/i', '', (string)($parsingRow['vin'] ?? '')));
        if (strlen($vin) !== 17) $vin = '';
        if ($vin === '' && !empty($parsingRow['report_data'])) {
            $rd = json_decode($parsingRow['report_data'], true);
            $rvin = $rd['inspection']['master']['detail']['vin'] ?? ($rd['record']['vin'] ?? '');
            $rvin = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/i', '', (string)$rvin));
            if (strlen($rvin) === 17) $vin = $rvin;
        }
        if ($vin === '' && in_array($parsingRow['source'] ?? '', ['encar', 'openlane', 'ecarstrade'], true)) {
            $vin = '00000000000000000';
        }
        return $vin;
    }

    private function vinCheckEnabled(string $vin): int
    {
        return (preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin) && $vin !== '00000000000000000') ? 1 : 0;
    }

    // Price = full MD landed cost (breakdown KR/EU) like the form; fallback to price.
    private function resolvePrice(array $parsingRow): int
    {
        $prc = !empty($parsingRow['price_final_eur']) ? (int)round($parsingRow['price_final_eur']) : 0;
        $src = $parsingRow['source'] ?? '';
        if (in_array($src, ['encar', 'openlane', 'ecarstrade'], true)) {
            $pricingFile = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/content/admin/page/parsing/parsing_pricing.php';
            if (is_file($pricingFile)) {
                require_once $pricingFile;
                $bdCar = [
                    'price_eur' => (float)($parsingRow['price_eur'] ?? 0),
                    'fuel'      => (string)($parsingRow['fuel_type'] ?? ''),
                    'capacity'  => (int)($parsingRow['engine_volume'] ?? 0),
                    'year'      => (int)($parsingRow['year'] ?? 0),
                ];
                try {
                    $bd = ($src === 'encar')
                        ? parsing_md_breakdown_kr($this->db, $this->prefix, $bdCar)
                        : parsing_md_breakdown_eu($this->db, $this->prefix, $bdCar);
                    if ($bd && !empty($bd['total'])) $prc = (int)round($bd['total']);
                } catch (\Throwable $e) { /* keep fallback */ }
            }
        }
        if ($prc <= 0) $prc = (int)round((float)($parsingRow['price_final_eur'] ?? $parsingRow['price_eur'] ?? 0));
        return $prc;
    }

    private function markPublishedOnSauto(int $parsingCarId, int $carCtlgId): void
    {
        $stmt = $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars
            SET status = "published",
                published_sauto = 1,
                car_ctlg_id = :car_id,
                published_at = NOW()
            WHERE id = :id');
        $stmt->execute(['id' => $parsingCarId, 'car_id' => $carCtlgId]);
    }

    private function propagatePublicationFlags(int $parsingCarId, ?int $carCtlgId): void
    {
        if (!$carCtlgId) return;

        $stmt = $this->db->prepare('SELECT telegram_published, facebook_published FROM '.$this->prefix.'_car_ctlg WHERE id = ?');
        $stmt->execute([$carCtlgId]);
        $flags = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars
            SET published_fb = :fb, published_tg = :tg
            WHERE id = :id');
        $stmt->execute([
            'id' => $parsingCarId,
            'fb' => $flags['facebook_published'] ?? 0,
            'tg' => $flags['telegram_published'] ?? 0,
        ]);
    }

    private function triggerAutoPublication(int $carCtlgId): void
    {
        try {
            $service = new AutoPublicationService($this->db, $this->prefix);
            $service->triggerAutoPublication($carCtlgId);
        } catch (Exception $e) {
            error_log('ParsingPublisher: AutoPublicationService failed for car ' . $carCtlgId . ' - ' . $e->getMessage());
        }
    }

    private function archive999Ad(string $ad999Id): void
    {
        try {
            $apiKeyId = 4;
            $service = new \App\Services\Api999Service($apiKeyId);
            $service->changeAccessPolicy(['999_id' => $ad999Id], 'private');
        } catch (Exception $e) {
            error_log('ParsingPublisher: 999.md archive failed for ad ' . $ad999Id . ' - ' . $e->getMessage());
        }
    }

    private function resolveBrandId(string $brandName): ?string
    {
        if ($brandName === '') return null;
        // Try exact match on br_nm, then on br code (lowercase with underscores).
        $stmt = $this->db->prepare('SELECT br FROM '.$this->prefix.'_car_list WHERE LOWER(br_nm) = LOWER(?) LIMIT 1');
        $stmt->execute([$brandName]);
        $val = $stmt->fetchColumn();
        if ($val !== false) return (string)$val;

        $brKey = strtolower(str_replace([' ', '-'], '_', $brandName));
        $stmt = $this->db->prepare('SELECT br FROM '.$this->prefix.'_car_list WHERE LOWER(br) = ? LIMIT 1');
        $stmt->execute([$brKey]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : null;
    }

    private function resolveModelId(string $modelName, ?string $brandId): ?string
    {
        if ($modelName === '' || !$brandId) return null;
        // Try exact match on mo_nm first.
        $stmt = $this->db->prepare('SELECT mo FROM '.$this->prefix.'_car_list WHERE br = ? AND LOWER(mo_nm) = LOWER(?) LIMIT 1');
        $stmt->execute([$brandId, $modelName]);
        $val = $stmt->fetchColumn();
        if ($val !== false) return (string)$val;

        // Fallback: match on mo code.
        $moKey = strtolower(str_replace([' ', '-'], '_', $modelName));
        $stmt = $this->db->prepare('SELECT mo FROM '.$this->prefix.'_car_list WHERE br = ? AND LOWER(mo) = ? LIMIT 1');
        $stmt->execute([$brandId, $moKey]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : null;
    }

    // Confirm a brand code exists in the catalog (returns it or null).
    private function validBrandCode(string $brCode): ?string
    {
        $stmt = $this->db->prepare('SELECT br FROM '.$this->prefix.'_car_list WHERE LOWER(br) = LOWER(?) LIMIT 1');
        $stmt->execute([$brCode]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : null;
    }

    // Confirm a model code exists for a brand (returns it or null).
    private function validModelCode(string $brandId, string $moCode): ?string
    {
        $stmt = $this->db->prepare('SELECT mo FROM '.$this->prefix.'_car_list WHERE br = ? AND LOWER(mo) = LOWER(?) LIMIT 1');
        $stmt->execute([$brandId, $moCode]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : null;
    }

    // Decide the sauto group: 'com' (AUTOCOMERCIALE) vs 'car' (AUTOTURISME).
    // A real van/minibus/truck/pickup → commercial ONLY when the brand or model
    // is actually commercial. This keeps genuine cargo vans (VW Transporter,
    // Ford Transit, Renault Master...) as 'com', while a passenger car the source
    // mis-tagged as "van" (e.g. a BMW 2 Series) stays 'car'. truck/pickup are
    // always commercial regardless of brand.
    private function resolveGroup(string $bodyLower, string $brand, string $model): string
    {
        if (in_array($bodyLower, ['truck', 'pickup'], true)) return 'com';

        $commercialBody = in_array($bodyLower, ['van', 'microbus', 'minibus', 'minivan'], true);
        if (!$commercialBody) return 'car';

        // Brands whose lineup is (mostly) commercial vans/trucks.
        $commercialBrands = [
            'iveco', 'man', 'isuzu', 'gaz', 'uaz', 'maxus', 'ldv',
        ];
        // Known commercial MODEL names across mixed brands (VW/Mercedes/Ford make
        // both cars and vans). Matched as a substring of the raw model.
        $commercialModels = [
            'transporter', 'transit', 'sprinter', 'crafter', 'vito', 'viano',
            'master', 'trafic', 'kangoo', 'expert', 'jumper', 'jumpy', 'boxer',
            'berlingo', 'partner', 'ducato', 'doblo', 'scudo', 'daily',
            'caddy', 'vivaro', 'movano', 'combo', 'nv200', 'nv300', 'nv400',
            'proace', 'connect', 'courier', 'tourneo',
        ];

        $b = mb_strtolower(trim($brand), 'UTF-8');
        $m = mb_strtolower(trim($model), 'UTF-8');
        if (in_array($b, $commercialBrands, true)) return 'com';
        foreach ($commercialModels as $cm) {
            if (strpos($m, $cm) !== false) return 'com';
        }
        // Commercial-looking body but a passenger brand/model → keep as car.
        return 'car';
    }

    // Official display names (br_nm / mo_nm) for the matched brand+model codes,
    // so the catalog card shows "BMW Seria 3" instead of the raw "BMW 316".
    private function catalogNames(string $brandId, string $modelId): array
    {
        $stmt = $this->db->prepare('SELECT br_nm, mo_nm FROM '.$this->prefix.'_car_list
            WHERE br = ? AND mo = ? LIMIT 1');
        $stmt->execute([$brandId, $modelId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['br_nm' => (string)($row['br_nm'] ?? ''), 'mo_nm' => (string)($row['mo_nm'] ?? '')];
    }

    // Per-source fallback + OpenLane's real country (CarCountryExtended) resolved
    // against the countries table, with generic "Europa" (EU) as last resort —
    // same as the autopublish form.
    private function resolveImportCountryId(array $parsingRow): int
    {
        $source = $parsingRow['source'] ?? '';
        $countryMap = ['encar' => 41, 'openlane' => 11, 'ecarstrade' => 2];
        $importCountryId = $countryMap[$source] ?? 39;

        if ($source === 'openlane') {
            $rawData = !empty($parsingRow['raw_data']) ? (json_decode($parsingRow['raw_data'], true) ?: []) : [];
            $olItem = $rawData;
            if (empty($olItem['CarCountryExtended']) && !empty($rawData['raw_data']) && is_array($rawData['raw_data'])) {
                $olItem = $rawData['raw_data'];
            }
            $cc = strtolower(trim((string)(
                $olItem['CarCountryExtended'] ?? $olItem['OriginCountryId'] ?? $olItem['CarEcadisCountryCountryId'] ?? ''
            )));
            try {
                $matched = false;
                if ($cc !== '') {
                    $cstmt = $this->db->prepare('SELECT id FROM countries WHERE LOWER(code) = ? LIMIT 1');
                    $cstmt->execute([$cc]);
                    $cid = $cstmt->fetchColumn();
                    if ($cid !== false) { $importCountryId = (int)$cid; $matched = true; }
                }
                if (!$matched) {
                    $eu = $this->db->query("SELECT id FROM countries WHERE code = 'EU' LIMIT 1")->fetchColumn();
                    if ($eu !== false) $importCountryId = (int)$eu;
                }
            } catch (\Throwable $e) { /* keep fallback */ }
        }
        return $importCountryId;
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM '.$this->prefix.'_parsing_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : $default;
    }
}
