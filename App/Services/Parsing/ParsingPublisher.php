<?php

namespace App\Services\Parsing;

use App\Core\Container;
use App\Services\AutoPublicationService;
use App\Services\CarEraser;
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
            SpecEnricher::enrich($this->db, $this->prefix, $parsingCarId);
            $row = $this->loadParsingCar($parsingCarId) ?: $row;
        } catch (\Throwable $e) { /* publish with what we have */ }

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

        if (in_array($target, ['sauto', 'all'], true) && $carCtlgId) {
            $this->processPhotos($carCtlgId, $row);
            $this->bakeOpenlaneReport($parsingCarId, $row);
            $this->bakeAuto1Report($parsingCarId, $row);
        }

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

    // Same idea for Auto1: its report needs a live API call (and a session), which
    // is too slow/fragile for the public page, so render all three languages ONCE
    // at publish time and store them in parsing_cars.report_data. ordercars.php
    // then just prints the baked HTML.
    private function bakeAuto1Report(int $parsingCarId, array $row): void
    {
        try {
            if (($row['source'] ?? '') !== 'auto1') return;
            if (!empty($row['report_data']) && strpos((string)$row['report_data'], 'auto1_report') !== false) return;

            $stock = (string)($row['source_id'] ?? '');
            if ($stock === '') return;

            $adapter = AdapterFactory::create('auto1');
            if (!$adapter || !method_exists($adapter, 'fetchReportRaw')) return;
            $rep = $adapter->fetchReportRaw($stock);
            if (!is_array($rep)) return;
            // Nothing worth showing (no damages AND no equipment) → don't store an
            // empty block that the public page would render as a bare heading.
            if (empty($rep['damages']) && empty($rep['equipment'])) return;

            $helper = dirname(__DIR__, 3) . '/content/admin/page/parsing/parsing_auto1_report.php';
            if (!function_exists('parsing_auto1_report_html') && is_file($helper)) {
                require_once $helper;
            }
            if (!function_exists('parsing_auto1_report_html')) return;

            // PUBLIC variant: equipment ONLY. The damage report / diagram / paint
            // stay internal (admin modal renders them live) — product decision.
            $byLang = [];
            foreach (['ro', 'ru', 'en'] as $rl) {
                $byLang[$rl] = parsing_auto1_report_html($rep, $rl, true);
            }
            $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars SET report_data = ? WHERE id = ?')
                     ->execute([json_encode(['auto1_report' => $byLang], JSON_UNESCAPED_UNICODE), $parsingCarId]);
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

        // Set below only when the car actually has a catalog row.
        $photoDir = ''; $photoPPath = ''; $ctlgId = 0;

        try {
            $this->db->beginTransaction();

            if (!empty($row['car_ctlg_id'])) {
                $ctlgId = (int)$row['car_ctlg_id'];

                // Read p_path while the row still exists — it's the only way to
                // locate the photo folder. Deleted after commit, not here: a
                // rollback would otherwise wipe photos of a car that still lives.
                $st = $this->db->prepare('SELECT p_path FROM '.$this->prefix.'_car_ctlg WHERE id = ?');
                $st->execute([$ctlgId]);
                $photoPPath = (string)$st->fetchColumn();
                $photoDir   = $this->carPhotoDir($ctlgId, $photoPPath);

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

        // Car row is gone for good — now drop its photos. Skipping this is what
        // left ~18k orphan folders on disk (nothing else ever cleans them up).
        if ($photoDir !== '' && is_dir($photoDir)) {
            CarEraser::rmdirRecursive($photoDir);
        }
        if ($photoPPath !== '' && !empty($ctlgId)) {
            try { \App\Services\CarPhotoR2::deleteCar($photoPPath, $ctlgId); }
            catch (\Throwable $e) { /* non-fatal — a stale object only costs storage */ }
        }

        if (!empty($row['ad_999_id'])) {
            $this->archive999Ad($row['ad_999_id']);
        }

        return ['success' => true];
    }

    /** Absolute photo folder for a car, or '' when p_path is missing. */
    private function carPhotoDir(int $carCtlgId, string $pPath): string
    {
        if ($pPath === '') return '';
        $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
        if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) {
            $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/').'/'.ltrim($carImg, '/');
        }
        return rtrim($carImg, '/').'/'.trim($pPath, '/').'/'.$carCtlgId;
    }

    private function loadParsingCar(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM '.$this->prefix.'_parsing_cars WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Resolve a raw source brand/model to the SAUTO canonical display names
    // (car_list br_nm/mo_nm) — the exact names publishing would assign — WITHOUT
    // creating anything. Returns ['br_nm'=>..., 'mo_nm'=>...] or null if the model
    // can't be matched to an existing catalog entry. Used to align parsing_cars so
    // the /parsing filter reads identical to sauto. $createMissing=false keeps this
    // read-safe for audits.
    public function resolveCanonicalNames(string $brand, string $model, bool $createMissing = false): ?array
    {
        $brandId = $this->resolveBrandId($brand);
        if (!$brandId) return null;
        $modelName = $this->canonicalModelName($brandId, $this->stripModelPrefix($model));
        // Try to match an existing model only (steps 1-3 of resolveModelId).
        $modelId = $this->matchExistingModel($brandId, $modelName);
        if (!$modelId && $createMissing) $modelId = $this->resolveModelId($model, $brandId);
        if (!$modelId) return null;
        $names = $this->catalogNames($brandId, $modelId);
        return [
            'br'    => $brandId,
            'mo'    => $modelId,
            'br_nm' => $names['br_nm'] ?: $brand,
            'mo_nm' => $names['mo_nm'] ?: $model,
        ];
    }

    // Match a model name to an existing car_list entry under a brand (no creation).
    // Mirrors resolveModelId steps 1-3 (exact mo_nm, mo slug, fuzzy prefix).
    private function matchExistingModel(string $brandId, string $modelName): ?string
    {
        if ($modelName === '') return null;
        $stmt = $this->db->prepare('SELECT mo FROM '.$this->prefix.'_car_list WHERE br = ? AND LOWER(mo_nm) = LOWER(?) LIMIT 1');
        $stmt->execute([$brandId, $modelName]);
        $val = $stmt->fetchColumn();
        if ($val !== false) return (string)$val;

        $moKey = strtolower(str_replace([' ', '-'], '_', $modelName));
        $stmt = $this->db->prepare('SELECT mo FROM '.$this->prefix.'_car_list WHERE br = ? AND LOWER(mo) = ? LIMIT 1');
        $stmt->execute([$brandId, $moKey]);
        $val = $stmt->fetchColumn();
        if ($val !== false) return (string)$val;

        // Fuzzy: reuse an existing model the raw name extends by a NON-alphabetic
        // tail only (so "RAV4"→"RAV 4", "CR-V"→"CRV"), but NOT when the extra tail is
        // a whole word — "C3 Aircross" must stay its own model, not collapse to "C3".
        $normKey = fn($s) => preg_replace('/[^a-z0-9]+/', '', mb_strtolower(trim((string)$s), 'UTF-8'));
        $rawN = $normKey($modelName);
        if ($rawN === '' || mb_strlen($rawN) < 2) return null;
        // The last token of the raw name; if it's an alphabetic word AND longer than
        // a trim letter, the raw name is a distinct sub-model → don't fuzzy-collapse.
        $rawTokens = preg_split('/[\s\-]+/', trim(mb_strtolower($modelName, 'UTF-8'))) ?: [];
        $lastTok = end($rawTokens) ?: '';
        $lastIsWord = (mb_strlen($lastTok) >= 3 && preg_match('/^[a-z]+$/', $lastTok));
        $all = $this->db->prepare('SELECT mo, mo_nm FROM '.$this->prefix.'_car_list WHERE br = ?');
        $all->execute([$brandId]);
        $best = null; $bestLen = 0;
        foreach ($all as $row) {
            $optN = $normKey($row['mo_nm']);
            if ($optN === '' || mb_strlen($optN) < 2) continue;
            // raw starts with opt: only if the extra tail isn't a distinct word.
            $rawExtendsOpt = (strpos($rawN, $optN) === 0 && !($lastIsWord && $optN !== $rawN));
            $optExtendsRaw = (strpos($optN, $rawN) === 0);
            if (($rawExtendsOpt || $optExtendsRaw) && mb_strlen($optN) > $bestLen) {
                $best = $row['mo']; $bestLen = mb_strlen($optN);
            }
        }
        return $best !== null ? (string)$best : null;
    }

    private function insertIntoCarCtlg(array $parsingRow): int
    {
        $importUserId = $this->getSetting('import_user_id', '1');

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

        $names = $this->catalogNames($brandId, $modelId);
        $brName = $names['br_nm'] ?: ($parsingRow['brand'] ?? '');
        $moName = $names['mo_nm'] ?: ($parsingRow['model'] ?? '');

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
            'yr' => (int)($parsingRow['year'] ?? 0),
            'vin' => $vin,
            'vin_check_enabled' => $this->vinCheckEnabled($vin),
            'bt' => $bodyMap[$bodyLower] ?? '',
            'sts' => (int)($parsingRow['seats'] ?? 0),
            'mlg' => (int)($parsingRow['km'] ?? 0),
            'unit' => 'km',
            'vol' => (int)($parsingRow['engine_volume'] ?? 0),
            'hp' => (int)($parsingRow['power_hp'] ?? 0),
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
            'p_path' => substr(md5(date('Y')), 0, 4) . '/' . substr(md5(date('m')), 0, 4),
            'date' => time(),
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
        // Photo cap on sauto: Encar 30, the auction sources 20 — their galleries
        // run to dozens of near-identical frames. Keep in step with the other
        // three copies of this rule (order_add_new.php and two in order_car.php);
        // changing only one has no visible effect, because manual publishing goes
        // through the JS in the form and never reaches this file.
        // 999 is separate and stays at 10 for every parsing car — see
        // buildImagesFeature14() in console/sauto_personal_cron.php.
        $cap = in_array($source, ['ecarstrade', 'openlane', 'auto1'], true) ? 20 : 30;
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

        $usePhotoroom = true;
        $photoroom = ($usePhotoroom && $source === 'encar') ? new PhotoroomService() : null;
        
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
        $written = []; // local files to mirror into R2 in one batch at the end
        foreach ($fetched as $bytes) {
            if ($bytes === null || $bytes === '') continue;

            $tmpFile = $tmpDir . '/' . uniqid('pub_' . $carCtlgId . '_', true) . '.jpg';
            if (@file_put_contents($tmpFile, $bytes) === false) { @unlink($tmpFile); continue; }
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
            $written[] = $absBase . '/high/' . $name . '.jpg';
            $written[] = $absBase . '/med/'  . $name . '.jpg';
        }

        // One parallel batch for the whole car. Sequentially this was two round
        // trips per photo — the single biggest cost in a publish job.
        if ($written) {
            $r2 = \App\Services\R2Client::fromEnv();
            if ($r2) {
                $items = [];
                foreach ($written as $p) {
                    $k = \App\Services\CarPhotoR2::keyFor($p);
                    if ($k !== '' && is_file($p)) $items[] = [$k, $p, 'image/jpeg'];
                }
                if ($items) {
                    $res = $r2->putFilesParallel($items, 10);
                    if (!empty($res['failed'])) {
                        // Non-fatal: the file is on disk and the Worker falls back
                        // to origin, so the ad still shows. The nightly cleanup
                        // will refuse to delete it locally until R2 has it.
                        error_log('ParsingPublisher: ' . count($res['failed'])
                            . ' photo(s) failed to reach R2 for car ' . $carCtlgId);
                    }
                }
            }
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
        // The R2 copy is NOT made here. Pushing two objects per photo, one after
        // the other, meant 100 sequential round trips for a 50-photo car and was
        // the reason publishing slowed to a crawl. processPhotos() collects the
        // written files and uploads them in one parallel batch instead.
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
        if ($vin === '' && in_array($parsingRow['source'] ?? '', ['encar', 'openlane', 'ecarstrade', 'auto1'], true)) {
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
        if (in_array($src, ['encar', 'openlane', 'ecarstrade', 'auto1'], true)) {
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

        // Strip Encar marketing prefixes ("NEW QM3", "The New Sorento", "All New
        // Niro") so they map to the base model instead of creating a bogus new one.
        $modelName = $this->stripModelPrefix($modelName);
        if ($modelName === '') return null;

        // Fold known body/trim/engine variants into their canonical base model so
        // sources never re-create fragments ("C Break"→"C Class", "A6 Avant"→"A6",
        // "NX Series"→"NX"). Same rules the mergemodels cleanup used. Keeps genuinely
        // distinct models (GLC Coupe, CX-30, ID.4, Range Rover Sport) untouched.
        $modelName = $this->canonicalModelName($brandId, $modelName);

        // 1. Exact match on mo_nm.
        $stmt = $this->db->prepare('SELECT mo FROM '.$this->prefix.'_car_list WHERE br = ? AND LOWER(mo_nm) = LOWER(?) LIMIT 1');
        $stmt->execute([$brandId, $modelName]);
        $val = $stmt->fetchColumn();
        if ($val !== false) return (string)$val;

        // 2. Match on mo code (slug).
        $moKey = strtolower(str_replace([' ', '-'], '_', $modelName));
        $stmt = $this->db->prepare('SELECT mo FROM '.$this->prefix.'_car_list WHERE br = ? AND LOWER(mo) = ? LIMIT 1');
        $stmt->execute([$brandId, $moKey]);
        $val = $stmt->fetchColumn();
        if ($val !== false) return (string)$val;

        // 3. Fuzzy: normalized key (no spaces/dashes/case). Reuse an existing model
        //    the raw name starts with, or that starts with the raw name — so "RAV4"
        //    matches "RAV 4", "NEW QM3"→"QM3", "C 200"→"C Class". Longest wins. Mirrors
        //    order_add_new.php's duplicate defence so auto-publish maps like manual Edit.
        $normKey = fn($s) => preg_replace('/[^a-z0-9]+/', '', mb_strtolower(trim((string)$s), 'UTF-8'));
        $rawN = $normKey($modelName);
        if ($rawN !== '' && mb_strlen($rawN) >= 2) {
            $all = $this->db->prepare('SELECT mo, mo_nm FROM '.$this->prefix.'_car_list WHERE br = ?');
            $all->execute([$brandId]);
            $best = null; $bestLen = 0;
            foreach ($all as $row) {
                $optN = $normKey($row['mo_nm']);
                if ($optN === '' || mb_strlen($optN) < 2) continue;
                if ((strpos($rawN, $optN) === 0 || strpos($optN, $rawN) === 0) && mb_strlen($optN) > $bestLen) {
                    $best = $row['mo']; $bestLen = mb_strlen($optN);
                }
            }
            if ($best !== null) return (string)$best;
        }

        // 4. Not in catalog at all → create it (same as manual Edit does), so
        //    auto-publish no longer stalls on models the catalog simply lacks.
        return $this->createModel($brandId, $modelName);
    }

    // Remove Encar's marketing prefixes from a model name so it maps to the base
    // model. Handles English ("NEW", "THE NEW", "ALL NEW") and Korean ("뉴", "더 뉴",
    // "올 뉴") variants. Returns the trimmed name (never empties a real name).
    private function stripModelPrefix(string $model): string
    {
        $m = trim($model);
        $m = preg_replace('/^(the\s+new|all\s+new|new)\s+/i', '', $m);
        $m = preg_replace('/^(더\s*뉴|올\s*뉴|뉴)\s+/u', '', $m);
        return trim($m);
    }

    // Fold body/trim/engine variants into their canonical base model, keyed by
    // brand code. The map value is the canonical DISPLAY name (mo_nm) that step-1
    // exact match then resolves. Only fold what is truly the SAME car — never a
    // distinct model (different number, or a body 999 lists separately). This is the
    // live-parsing twin of the mergemodels RULES, so fragments can't come back.
    private function canonicalModelName(string $brandId, string $modelName): string
    {
        // Judgment calls a rule can't make: "Mokka X" IS a Mokka, but "Model X" is
        // NOT a Model. Everything mechanical is handled by the rules further down,
        // so this list stays short no matter how many brands a source carries.
        static $map = [
            'mercedes_benz' => [
                'a' => 'A Class', 'a amg' => 'A Class',
                'b' => 'B Class',
                'c' => 'C Class', 'c amg' => 'C Class', 'c break' => 'C Class',
                'e' => 'E Class', 'e break' => 'E Class',
                'g amg' => 'G Class',
                's long' => 'S Class',
                'v' => 'V Class', 'v l3' => 'V Class',
            ],
            'citroen'    => ['c4 picasso' => 'C4'],
            'cupra'      => ['formentor vz' => 'Formentor', 'leon vz' => 'Leon',
                             'leon sportstourer vz' => 'Leon'],
            'dacia'      => ['logan mcv' => 'Logan', 'logan van' => 'Logan', 'dokker van' => 'Dokker'],
            'ford'       => ['focus rs' => 'Focus', 'focus wagon' => 'Focus'],
            'honda'      => ['civic hibrid' => 'Civic'],
            'lexus'      => ['nx series' => 'NX', 'ux 250h' => 'UX'],
            'nissan'     => ['qashqai 2' => 'Qashqai', 'qashqai+2' => 'Qashqai'],
            // Generation/marketing suffixes the maker itself dropped. A single
            // trailing letter can't be a rule — "Mokka X"→Mokka but "Aygo X" is its
            // own model — so these stay explicit.
            'opel'       => [
                'astra k' => 'Astra',
                'mokka x' => 'Mokka',
                'grandland x' => 'Grandland', 'crossland x' => 'Crossland',
            ],
            'renault'    => ['megane e-tech' => 'Megane', 'megane e tech' => 'Megane'],
            'toyota'     => ['prius+' => 'Prius', 'prius plus' => 'Prius', 'prius c' => 'Prius'],
            // Golf I/V keep a single-letter numeral the generic rule won't touch;
            // e-Golf is the electric Golf and sauto lists one "Golf".
            'volkswagen' => [
                'passat cc' => 'Passat', 'passat alltrack' => 'Passat',
                'golf i' => 'Golf', 'golf v' => 'Golf',
                'e golf' => 'Golf', 'e golf vii' => 'Golf',
            ],
        ];

        $brandId = strtolower($brandId);
        // Key form: lowercase, dashes/underscores → spaces, collapsed whitespace.
        // "C-Break"/"C_Break"/"C  Break" → "c break".
        $key = mb_strtolower(trim($modelName), 'UTF-8');
        $key = preg_replace('/[_\-]+/u', ' ', $key);
        $key = preg_replace('/\s+/u', ' ', $key);

        // 1) An explicit fold wins over any rule.
        if (isset($map[$brandId][$key])) return $map[$brandId][$key];

        // 2) Mechanical rules — these cover EVERY brand a source may list, so a
        // feed using German-market names needs no per-model table.

        // BMW badge: "5er" → "5 Series".
        if ($brandId === 'bmw' && preg_match('/^([1-8])er$/', $key, $m)) {
            return $m[1] . ' Series';
        }
        // Mercedes badge: "E-Klasse" → "E Class"; multi-letter keeps the bare code
        // ("GLC-Klasse" → "GLC"). Trailing words fold in too ("A-Klasse Limousine").
        if ($brandId === 'mercedes_benz' && preg_match('/^([a-z]{1,3}) klasse\b/u', $key, $m)) {
            $code = mb_strtoupper($m[1], 'UTF-8');
            return mb_strlen($code, 'UTF-8') === 1 ? $code . ' Class' : $code;
        }

        $out = trim($modelName);
        // Generation numeral: "Golf VII" → "Golf". Multi-character only — a bare
        // I/V/X is a real model suffix (Tesla "Model X", Toyota "Aygo X", "C4 X").
        $out = preg_replace('/\s+(?:II|III|IV|VI|VII|VIII|IX)$/u', '', $out);
        // Body/trim shell of the same car: "A3 Sportback" → "A3", "Astra GTC" → "Astra".
        $out = preg_replace('/\s+(?:Sportback|Avant|Allroad|All[\s-]*Terrain|Limousine|'
            . 'Sportstourer|Sports\s*Tourer|Shooting\s*Brake|Grand\s*Sport|Country\s*Tourer|'
            . 'Tourer|Variant|Touring|GTC)$/iu', '', $out);
        $out = trim($out);

        return $out !== '' ? $out : $modelName;
    }

    // Insert a new model under a brand and return its code. Mirrors the INSERT in
    // content/admin/ajax/ordercars/order_add_new.php so auto-publish and manual Edit
    // create catalog entries the same way.
    private function createModel(string $brandId, string $modelName): ?string
    {
        try {
            $moSlug = trim(preg_replace('/[^a-z0-9]+/u', '_',
                mb_strtolower($modelName, 'UTF-8')), '_');
            if ($moSlug === '') $moSlug = 'model';

            // Guard against a race / pre-existing slug: reuse if already present.
            $chk = $this->db->prepare('SELECT mo FROM '.$this->prefix.'_car_list WHERE br = ? AND mo = ? LIMIT 1');
            $chk->execute([$brandId, $moSlug]);
            $existing = $chk->fetchColumn();
            if ($existing !== false) return (string)$existing;

            $bnm = $this->db->prepare('SELECT br_nm FROM '.$this->prefix.'_car_list WHERE br = ? LIMIT 1');
            $bnm->execute([$brandId]);
            $brandDisplay = $bnm->fetchColumn() ?: $brandId;

            $ins = $this->db->prepare('INSERT INTO '.$this->prefix.'_car_list (br, mo, br_nm, mo_nm) VALUES (?, ?, ?, ?)');
            $ins->execute([$brandId, $moSlug, $brandDisplay, $modelName]);
            return $moSlug;
        } catch (\Throwable $e) {
            return null;
        }
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

    // Passenger pickups the source labels as truck/pickup (so they'd become commercial),
    // but that has no matching category on sauto/999 — publish them as a normal car.
    // Matched on brand+model, trimmed + case-insensitive. Add pairs here as they come up.
    private const PASSENGER_PICKUPS = [
        'tesla|cybertruck',
        'ford|ranger',
    ];

    private function resolveGroup(string $bodyLower, string $brand, string $model): string
    {
        $bm = mb_strtolower(trim($brand), 'UTF-8') . '|' . mb_strtolower(trim($model), 'UTF-8');
        if (in_array($bm, self::PASSENGER_PICKUPS, true)) return 'car';

        if (in_array($bodyLower, ['truck', 'pickup'], true)) return 'com';

        $commercialBody = in_array($bodyLower, ['van', 'microbus', 'minibus', 'minivan'], true);
        if (!$commercialBody) return 'car';

        // Brands whose lineup is (mostly) commercial vans/trucks.
        $commercialBrands = [
            'iveco', 'man', 'isuzu', 'gaz', 'uaz', 'maxus', 'ldv',
        ];

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

    private function catalogNames(string $brandId, string $modelId): array
    {
        $stmt = $this->db->prepare('SELECT br_nm, mo_nm FROM '.$this->prefix.'_car_list
            WHERE br = ? AND mo = ? LIMIT 1');
        $stmt->execute([$brandId, $modelId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['br_nm' => (string)($row['br_nm'] ?? ''), 'mo_nm' => (string)($row['mo_nm'] ?? '')];
    }

    private function resolveImportCountryId(array $parsingRow): int
    {
        $source = $parsingRow['source'] ?? '';
        $countryMap = ['encar' => 41, 'openlane' => 11, 'ecarstrade' => 2, 'auto1' => 11];
        $importCountryId = $countryMap[$source] ?? 39;

        // Auto1 cars come from many EU countries; the hit carries the real one in
        // sourceCountry/countryCode (IT, BE, DE...). Resolve it like OpenLane below.
        if ($source === 'auto1') {
            $rawData = !empty($parsingRow['raw_data']) ? (json_decode($parsingRow['raw_data'], true) ?: []) : [];
            $a1 = $rawData['raw_data'] ?? $rawData;
            $cc = strtolower(trim((string)(
                $a1['sourceCountry'] ?? $a1['countryCode'] ?? $a1['owningCountry'] ?? ''
            )));
            // Detail payload keeps it under details.sourceCountryCode.
            if ($cc === '' && !empty($a1['details']['sourceCountryCode'])) {
                $cc = strtolower(trim((string)$a1['details']['sourceCountryCode']));
            }
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
