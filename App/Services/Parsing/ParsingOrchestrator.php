<?php

namespace App\Services\Parsing;

use App\Core\Container;
use Exception;
use PDO;

class ParsingOrchestrator
{
    private $db;
    private $prefix;
    private $pipeline;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
        $this->pipeline = new ParsingPipeline();
    }

    public function runFilter(int $filterId, bool $fast = false, string $runType = 'cron'): array
    {
        $filter = $this->loadFilter($filterId);
        if (!$filter) {
            return ['error' => 'Filter not found or inactive'];
        }

        // Per-filter publish cap reached → the filter goes fully idle: no import, no
        // publish. Saves Encar requests on a filter that's already "full". Raising the
        // cap later lets it resume (import continues from last_offset, not from zero).
        // Cap of 0 = unlimited, so this never triggers for uncapped filters.
        $publishLimit = (int)($filter['publish_limit'] ?? 0);
        if ($publishLimit > 0 && $this->countAutoPublished($filterId) >= $publishLimit) {
            // Stamp last_run_at so the cron's "due" rotation doesn't reselect this idle
            // filter every single run (it would waste one of the 15 slots/run).
            $this->touchFilter($filterId, 0, 0);
            return ['_capped' => true];
        }

        $sources = array_filter(array_map('trim', explode(',', $filter['sources'] ?? '')));
        $summary = [];

        foreach ($sources as $sourceCode) {
            $adapter = AdapterFactory::create($sourceCode);
            if (!$adapter) {
                $summary[$sourceCode] = ['error' => 'Unknown adapter'];
                continue;
            }

            $runId = $this->openRun($filterId, $sourceCode, $runType);
            $found = 0; $imported = 0; $skipped = 0; $error = null;

            try {
                $baseCriteria = $this->filterToCriteria($filter);

                $haveCount = 0;
                try {
                    $hc = $this->db->prepare('SELECT COUNT(*) FROM '.$this->prefix.'_parsing_cars WHERE filter_id = ?');
                    $hc->execute([(int)$filter['id']]);
                    $haveCount = (int)$hc->fetchColumn();
                } catch (\Throwable $e) { /* non-fatal */ }

                $backfillStart = (int)($filter['last_offset'] ?? 0);
                $passes = [
                    ['_offset_start' => 0,              'backfill' => false],
                    ['_offset_start' => $backfillStart, 'backfill' => true],
                ];

                $duplicates = 0;   // already-have cars skipped by dedup
                $totalCount = 0;   // Encar's total for this query (progress denominator)
                $newLastOffset = $backfillStart;
                foreach ($passes as $pass) {
                    if ($pass['backfill'] && $pass['_offset_start'] === 0) {
                        $encarTotal = property_exists($adapter, 'lastTotalCount') ? (int)$adapter->lastTotalCount : 0;
                        $moreToFetch = $encarTotal > 0 && $haveCount < $encarTotal;
                        if (!$moreToFetch) continue;
       
                        $pass['_offset_start'] = max(0, $haveCount - 50);
                    }

                    $criteria = $baseCriteria;
                    $criteria['_offset_start'] = $pass['_offset_start'];
                    $cars = $adapter->searchByFilter($criteria);
                    $found += count($cars);
                    if (property_exists($adapter, 'lastTotalCount')) {
                        $totalCount = max($totalCount, (int)$adapter->lastTotalCount);
                    }

                    foreach ($cars as $raw) {
                        $result = $this->ingestCar($raw, (int)$filter['id'], $fast);
                        if ($result === 'imported')       $imported++;
                        elseif ($result === 'duplicate')  $duplicates++;
                        else                              $skipped++;
                    }

                    if ($pass['backfill']) {
                        $adv = property_exists($adapter, 'lastOffset') ? (int)$adapter->lastOffset : 0;
                        $encarTotal = $totalCount;
                        $importedAll = $encarTotal > 0 && ($haveCount + $imported) >= $encarTotal;
                        if ($importedAll) {
                            $newLastOffset = 0;
                        } elseif ($adv > 0) {
                            $newLastOffset = $adv;
                        }
                        // else: keep the previous offset (don't reset on a transient 0).
                    }
                }

                $this->db->prepare('UPDATE '.$this->prefix.'_parsing_filters SET last_offset = ? WHERE id = ?')
                    ->execute([$newLastOffset, (int)$filter['id']]);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }

            $this->closeRun($runId, $found, $imported, $skipped, $error);
            $summary[$sourceCode] = compact('found', 'imported', 'skipped', 'error')
                + ['duplicates' => $duplicates ?? 0,
                   'total_count' => $totalCount ?? 0,
                   'catalog_offset' => $newLastOffset];
        }

        $this->touchFilter($filterId, array_sum(array_column($summary, 'found')), array_sum(array_column($summary, 'imported')));

        // After import, enrich newly-added cars (gearbox + full images from detail API).
        // Skipped in fast (AJAX) mode to keep responses snappy.
        $totalImported = array_sum(array_column($summary, 'imported'));
        if (!$fast) {
            if ($totalImported > 0) {
                $this->enrichRecent(min($totalImported, 300), 120);
            }
        }

        if (!$fast) {
            // Claim orphan cars already in the catalog that match this filter (manual
            // imports, leftovers from a deleted filter) BEFORE auto-publishing, so they
            // get published too instead of being stuck unattached forever.
            $adopted = $this->adoptOrphanCars($filter);
            if ($adopted > 0) {
                $summary['_adopted'] = $adopted;
            }
            $this->autoPublishFilterCars((int)$filter['id']);
        }

        return $summary;
    }

    // Cap per call so a filter with a big proposed backlog can't flood the queue
    // in one run; the rest are picked up next run (oldest first).
    private const AUTO_PUBLISH_LIMIT = 100;

    private function autoPublishFilterCars(int $filterId): void
    {
        try {
            // Per-filter publish cap (publish_limit, 0/NULL = unlimited): once this
            // filter has auto-published `publish_limit` cars, it stops enqueuing more.
            // This only gates AUTO-publish — manual publishing goes straight through
            // ParsingPublisher and ignores the cap entirely. Cars over the cap stay
            // "proposed" in the catalog. The per-run LIMIT 100 below still applies on
            // top, so a big filter never floods the queue in one run.
            $batchLimit = self::AUTO_PUBLISH_LIMIT;
            $limit = $this->filterPublishLimit($filterId);
            if ($limit > 0) {
                $already = $this->countAutoPublished($filterId);
                $remaining = $limit - $already;
                if ($remaining <= 0) return;            // cap reached → stop
                $batchLimit = min($batchLimit, $remaining);
            }

            // Skip cars auto-publish already gave up on (brand/model not mapped,
            // etc.) so they aren't retried every run forever. They stay in the
            // failures panel; a manual retry clears the flag.
            $sql = 'SELECT id FROM '.$this->prefix.'_parsing_cars
                WHERE filter_id = ? AND status = "proposed"
                  AND COALESCE(autopublish_failed, 0) = 0
                ORDER BY id ASC LIMIT '.(int)$batchLimit;
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$filterId]);
            } catch (\Throwable $colErr) {
                // Column not created yet on this install — fall back without it.
                $stmt = $this->db->prepare('SELECT id FROM '.$this->prefix.'_parsing_cars
                    WHERE filter_id = ? AND status = "proposed"
                    ORDER BY id ASC LIMIT '.(int)$batchLimit);
                $stmt->execute([$filterId]);
            }
            $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            if (!$ids) return;

            $queue = new PublishQueue();
            foreach ($ids as $carId) {
                $queue->enqueue((int)$carId, 'sauto');
            }
        } catch (\Throwable $e) {

        }
    }

    // Per-filter auto-publish cap. 0 = unlimited. Column is self-created elsewhere;
    // a missing column is treated as unlimited (no cap), preserving old behavior.
    private function filterPublishLimit(int $filterId): int
    {
        try {
            $stmt = $this->db->prepare('SELECT publish_limit FROM '.$this->prefix.'_parsing_filters WHERE id = ?');
            $stmt->execute([$filterId]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0; // column missing → unlimited
        }
    }

    // How many cars this filter has already put on the site. Counts both still-live
    // (published) and sold/gone (unavailable) ones, so a filter doesn't keep topping
    // up forever as cars sell — the cap means "publish this many total, then stop".
    private function countAutoPublished(int $filterId): int
    {
        try {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM '.$this->prefix.'_parsing_cars
                WHERE filter_id = ? AND status IN ("published", "unavailable")');
            $stmt->execute([$filterId]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function runByUrl(string $url): array
    {
        $adapter = AdapterFactory::detectFromUrl($url);
        if (!$adapter) {
            return ['success' => false, 'error' => 'No adapter matches this URL'];
        }

        try {
            $raw = $adapter->fetchByUrl($url);
            if (!$raw) {
                return ['success' => false, 'error' => 'Source returned no data'];
            }
            // skipImages=true: keep the remote Encar image URLs (like search does)
            // instead of downloading them locally. The form's image_proxy fetches
            // them live, which is what already works for filter/search imports.
            $result = $this->ingestCar($raw, null, true);

            // Tell the UI WHERE the car is (parsing catalog / favorite / published
            // sauto ad) so it can jump + highlight it — both when it already existed
            // (duplicate) and when we just added it (imported), since a freshly added
            // car gets mixed into the price-sorted catalog and is hard to find.
            if ($result === 'duplicate' || $result === 'imported') {
                $existing = $this->findExistingCar($raw['source'] ?? '', $raw['source_id'] ?? '', $raw['vin'] ?? null);
                return ['success' => true, 'result' => $result, 'existing' => $existing];
            }
            return ['success' => true, 'result' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function ingestCarPublic(array $raw, ?int $filterId, bool $skipImages = false): string
    {
        return $this->ingestCar($raw, $filterId, $skipImages);
    }

    /**
     * Enrich a single car from its detail page (full image gallery, gearbox,
     * seats, color, etc.). Used on-demand when the user opens a car to edit/
     * publish — search-imported cars only carry the few listing photos; the
     * full gallery lives on the detail page.
     */
    public function enrichOnePublic(int $carId): bool
    {
        $stmt = $this->db->prepare('SELECT source, source_id FROM '.$this->prefix.'_parsing_cars WHERE id = ? LIMIT 1');
        $stmt->execute([$carId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return false;
        $this->enrichFromDetail($row['source'], $row['source_id']);
        return true;
    }

    public function enrichRecent(int $limit = 50, int $timeLimitSec = 25): int
    {
        // Encar + OpenLane both enrich from a detail endpoint (full photos, VIN,
        // specs). The "images_local LIKE %url%" clause catches rows that still
        // hold only the remote listing thumbnail (search saved {"url":...}) and
        // need the full gallery pulled from detail.
        // engine_volume is in the list because the catalog card cannot show the MD
        // landed price without it (the excise is charged per cm3, so a missing cc
        // would silently drop the biggest line). Encar's list payload carries no
        // displacement at all — the adapter can only guess it from the Badge text,
        // which fails on roughly half the ads — so detail is the only source. A car
        // that already has vin/gearbox/seats/images but no cc used to match nothing
        // here and was never re-enriched, leaving its card polling on every view.
        $stmt = $this->db->prepare('SELECT id, source, source_id FROM '.$this->prefix.'_parsing_cars
            WHERE source IN ("encar", "openlane", "ecarstrade", "auto1")
              AND (vin IS NULL OR vin = "" OR gearbox IS NULL OR seats IS NULL OR engine_volume IS NULL OR engine_volume = 0 OR images_local IS NULL OR images_local = "[]" OR images_local LIKE \'%"url"%\')
            ORDER BY found_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) return 0;

        $start = time();
        $enriched = 0;
        foreach ($rows as $row) {
            if (time() - $start >= $timeLimitSec) break;
            $this->enrichFromDetail($row['source'], $row['source_id']);
            $enriched++;
        }
        return $enriched;
    }

    private function ingestCar(array $raw, ?int $filterId, bool $skipImages = false): string
    {
        $source = $raw['source'] ?? null;
        $sourceId = $raw['source_id'] ?? null;
        $logFile = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/logs/parsing_insert.log';

        if (!$source || !$sourceId) {
            @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] FAIL no source/id\n", FILE_APPEND);
            return 'failed';
        }

        if ($this->isDuplicate($source, $sourceId, $raw)) {
            return 'duplicate';
        }

        $processed = $this->pipeline->process($raw, $skipImages);
        if (!$processed) {
            @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] FAIL pipeline source_id={$sourceId}\n", FILE_APPEND);
            return 'failed';
        }

        // Manual import (filter_id NULL): "adopt" the car into a saved filter whose
        // parameters it matches (same source + brand + model + year in range), so it
        // gets auto-published just like the cron-imported cars instead of sitting
        // unpublished forever.
        if ($filterId === null) {
            $filterId = $this->findMatchingFilterId($processed);
        }

        if (!$this->insertCar($processed, $filterId)) {
            @file_put_contents($logFile, '['.date('Y-m-d H:i:s')."] FAIL insert source_id={$sourceId}\n", FILE_APPEND);
            return 'failed';
        }

        // Enrich happens in bulk after all inserts (runFilter / search_now).

        return 'imported';
    }

    private function enrichFromDetail(string $source, string $sourceId): void
    {
        try {
            $adapter = AdapterFactory::create($source);
            if (!$adapter) return;

            $detail = $adapter->fetchById($sourceId);
            if (!$detail) return;

            // EV fallback: electric cars have a single-speed gearbox and no displacement.
            // Encar's spec.transmissionName / spec.displacement are blank for EVs.
            // For electric cars: force gearbox=automat and engine_volume=1 (placeholder
            // so sauto's mandatory-field check passes). Encar's "displacement" for EVs
            // is a battery code, not a real cc value.
            $isElectric = ($detail['fuel_type'] ?? '') === 'electric';
            $gearboxVal = $detail['gearbox'] ?? null;
            if ($isElectric) $gearboxVal = 'automat';
            $engineVol = $detail['engine_volume'] ?? null;
            if ($isElectric) $engineVol = 1;

            $update = array_filter([
                'gearbox'        => $gearboxVal,
                'engine_volume'  => $engineVol,
                'color'          => $detail['color'] ?? null,
                'body_type'      => $detail['body_type'] ?? null,
                'vin'            => $detail['vin'] ?? null,
                'power_hp'       => $detail['power_hp'] ?? null,
                'seats'          => $detail['seats'] ?? null,
                'drive_type'     => $detail['drive_type'] ?? null,
                // Detail page resolves the hybrid sub-type (PHEV/HEV/diesel) that the
                // listing item lacks, so refresh fuel_type from it too.
                'fuel_type'      => $detail['fuel_type'] ?? null,
            ], fn($v) => $v !== null && $v !== '');

            // Update images_local with full image list from detail (preserves ordering).
            $detailImages = $detail['images'] ?? [];
            if (!empty($detailImages)) {
                $imagesLocal = array_map(fn($u) => ['url' => $u], $detailImages);
                $update['images_local'] = json_encode($imagesLocal, JSON_UNESCAPED_UNICODE);
            }

            if (empty($update)) return;

            $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($update)));
            $update['_source']    = $source;
            $update['_source_id'] = $sourceId;
            $this->db->prepare(
                'UPDATE ' . $this->prefix . '_parsing_cars SET ' . $sets .
                ' WHERE source = :_source AND source_id = :_source_id'
            )->execute($update);
        } catch (\Throwable $e) {
            // Non-critical — listing data already saved, detail is a bonus.
        }
    }

    private function isDuplicate(string $source, string $sourceId, ?array $raw = null): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM '.$this->prefix.'_parsing_cars WHERE source = ? AND source_id = ? LIMIT 1');
        $stmt->execute([$source, $sourceId]);
        if ($stmt->fetchColumn()) return true;

        // Same physical car re-listed with a NEW source_id: only treat as a
        // duplicate when we can match on a real VIN (a unique car identifier).
        // The old brand+model+year+km+price match produced FALSE POSITIVES —
        // two different cars of a popular model often share the exact same
        // year/mileage/price, so genuine new listings were silently dropped.
        // VINs are unique, so this is safe; cars without a VIN fall back to the
        // source_id check above (which already ran).
        if ($raw) {
            $vin = trim((string)($raw['vin'] ?? ''));
            // A valid VIN is 17 chars; anything shorter (or empty) is unreliable.
            if (strlen($vin) === 17) {
                $stmt2 = $this->db->prepare('SELECT 1 FROM '.$this->prefix.'_parsing_cars
                    WHERE source = ? AND vin = ? LIMIT 1');
                $stmt2->execute([$source, $vin]);
                if ($stmt2->fetchColumn()) return true;
            }
        }
        return false;
    }

    /**
     * Locate a car already in the catalog (matched the same way isDuplicate does:
     * source+source_id, or source+VIN) and describe where it lives so the UI can
     * link straight to it: parsing catalog, favorites, or the published sauto ad.
     *
     * @return array{id:int,status:string,car_ctlg_id:?int,is_favorite:bool,title:string}|null
     */
    private function findExistingCar(string $source, string $sourceId, ?string $vin = null): ?array
    {
        $cols = 'id, status, car_ctlg_id, brand, model, year';
        $stmt = $this->db->prepare('SELECT '.$cols.'
            FROM '.$this->prefix.'_parsing_cars WHERE source = ? AND source_id = ? LIMIT 1');
        $stmt->execute([$source, $sourceId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row && $vin !== null && strlen(trim($vin)) === 17) {
            $stmt = $this->db->prepare('SELECT '.$cols.'
                FROM '.$this->prefix.'_parsing_cars WHERE source = ? AND vin = ? LIMIT 1');
            $stmt->execute([$source, trim($vin)]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        if (!$row) return null;

        // Favorite is stored as status = 'favorite' (no separate flag column).
        return [
            'id'          => (int)$row['id'],
            'status'      => (string)$row['status'],
            'car_ctlg_id' => $row['car_ctlg_id'] !== null ? (int)$row['car_ctlg_id'] : null,
            'is_favorite' => ($row['status'] === 'favorite'),
            'title'       => trim(($row['brand'] ?? '').' '.($row['model'] ?? '').' '.($row['year'] ?? '')),
        ];
    }

    // Lazily-built ParsingPublisher, reused across inserts in one run so the model
    // resolver/creator isn't re-instantiated per car.
    private $publisherInstance = null;
    private function publisher(): ParsingPublisher
    {
        if ($this->publisherInstance === null) {
            $this->publisherInstance = new ParsingPublisher();
        }
        return $this->publisherInstance;
    }

    // Make sure parsing_cars has the sauto mapping columns (created on first import
    // in installs that predate them). Runs once per process.
    private function ensureSautoMapColumns(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;
        try {
            $has = $this->db->query("SHOW COLUMNS FROM {$this->prefix}_parsing_cars LIKE 'sauto_br'");
            if ($has && $has->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->prefix}_parsing_cars
                    ADD COLUMN `sauto_br` VARCHAR(50) DEFAULT NULL,
                    ADD COLUMN `sauto_mo` VARCHAR(50) DEFAULT NULL");
            }
        } catch (\Throwable $e) { /* best-effort */ }
    }

    private function insertCar(array $row, ?int $filterId): bool
    {
        $row['filter_id'] = $filterId;
        $row['status'] = 'proposed';

        // Map to the single canonical sauto model (car_list) at insert time, so the
        // /parsing filter — which reads brand/model from car_list via sauto_br/mo —
        // stays a single deduplicated list. Raw brand/model are kept untouched for
        // reference. Missing models are created in car_list so the list is complete.
        // Best-effort: a failure here must not block the import.
        try {
            $this->ensureSautoMapColumns();
            $canon = $this->publisher()->resolveCanonicalNames(
                (string)($row['brand'] ?? ''), (string)($row['model'] ?? ''), true
            );
            if ($canon) { $row['sauto_br'] = $canon['br']; $row['sauto_mo'] = $canon['mo']; }
        } catch (\Throwable $e) { /* import proceeds with raw names */ }

        $jsonCols = ['price_breakdown', 'features_ro', 'report_data', 'images_local', 'raw_data'];
        foreach ($jsonCols as $c) {
            if (isset($row[$c]) && !is_string($row[$c])) {
                $row[$c] = json_encode($row[$c], JSON_UNESCAPED_UNICODE);
            }
        }

        $columns = array_keys($row);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = 'INSERT INTO '.$this->prefix.'_parsing_cars (`'.implode('`,`', $columns).'`)
                VALUES ('.implode(',', $placeholders).')';
        $logFile = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/logs/parsing_insert.log';
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($row);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // Duplicate key = already exists, not a real error.
            if (stripos($msg, 'Duplicate') !== false) {
                return false;
            }
            @file_put_contents($logFile,
                '['.date('Y-m-d H:i:s').'] INSERT FAIL: '.$msg.
                ' | cols: '.implode(',', $columns).
                ' | source_id: '.($row['source_id'] ?? 'null')."\n",
                FILE_APPEND);
            return false;
        }
    }

    // Find an active saved filter a manually-imported car belongs to, so it can be
    // "adopted" (gets a filter_id → auto-publishes) instead of sitting unpublished.
    //
    // Strict match: the car must satisfy EVERY criterion the filter has set —
    // brand, model, year range, fuel, gearbox, body type, max km, max price. A
    // filter only adopts cars it would have imported itself. Filters are read
    // fresh each time, so edits take effect immediately.
    //
    // Brand/model can't be compared directly: the filter stores the source's
    // taxonomy keys (Korean for Encar, sauto codes elsewhere) while the car holds
    // a normalized display name. So we anchor candidates on cars the filter has
    // ALREADY imported (same normalized brand/model), then check the rest in PHP.
    // Most-specific (model-pinned) then newest filter wins. Null = no match.
    private function findMatchingFilterId(array $car): ?int
    {
        $source = (string)($car['source'] ?? '');
        $brand  = trim((string)($car['brand'] ?? ''));
        $model  = trim((string)($car['model'] ?? ''));
        if ($source === '' || $brand === '') return null;

        // Match against the filter's OWN criteria (brand + optional model), not
        // against cars it already imported. The old EXISTS check required the filter
        // to already hold a matching car — so a brand-new filter that imported nothing
        // (e.g. a cron run that found 0) could never adopt manual imports, leaving them
        // unpublished forever. Now an empty filter can adopt too. carMatchesFilter()
        // below still enforces year/km/price/fuel/gearbox/body_type.
        try {
            $stmt = $this->db->prepare('SELECT f.*
                FROM '.$this->prefix.'_parsing_filters f
                WHERE f.active = 1
                  AND FIND_IN_SET(?, REPLACE(f.sources, " ", "")) > 0
                  AND (f.brand IS NULL OR f.brand = "" OR f.brand = ?)
                  AND (f.model IS NULL OR f.model = "" OR f.model = ?)
                ORDER BY (f.model IS NOT NULL AND f.model <> "") DESC,
                         (f.brand IS NOT NULL AND f.brand <> "") DESC,
                         f.id DESC');
            $stmt->execute([$source, $brand, $model]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($rows as $f) {
            if ($this->carMatchesFilter($car, $f)) return (int)$f['id'];
        }
        return null;
    }

    // True if a car satisfies every criterion a saved filter has set. Unset filter
    // fields (NULL / empty / 0) don't constrain. Used to adopt manual imports.
    private function carMatchesFilter(array $car, array $f): bool
    {
        $year = (int)($car['year'] ?? 0);
        $yf = (int)($f['year_from'] ?? 0);
        $yt = (int)($f['year_to'] ?? 0);
        if ($yf > 0 && $year > 0 && $year < $yf) return false;
        if ($yt > 0 && $year > 0 && $year > $yt) return false;

        // Max km / max price (price compared in EUR, like the filter's price_max).
        $km = (int)($car['km'] ?? 0);
        if (!empty($f['km_max']) && $km > 0 && $km > (int)$f['km_max']) return false;
        $priceEur = (float)($car['price_eur'] ?? 0);
        if (!empty($f['price_max']) && $priceEur > 0 && $priceEur > (float)$f['price_max']) return false;

        // For the spec fields below, a value the car simply doesn't have yet (null/
        // empty — common for listing-only imports like eCarsTrade body_type) does
        // NOT exclude it; only a value that's present AND different does.

        // Fuel: filter stores a CSV of codes (e.g. "benzina,diesel"); if the car
        // has a fuel, it must be one of them.
        if (!empty($f['fuel_type'])) {
            $carFuel = strtolower(trim((string)($car['fuel_type'] ?? '')));
            if ($carFuel !== '') {
                $fuels = array_map('strtolower', array_filter(array_map('trim', explode(',', (string)$f['fuel_type']))));
                if (!in_array($carFuel, $fuels, true)) return false;
            }
        }

        // Gearbox.
        if (!empty($f['gearbox'])) {
            $carGear = trim((string)($car['gearbox'] ?? ''));
            if ($carGear !== '' && strcasecmp(trim((string)$f['gearbox']), $carGear) !== 0) return false;
        }

        // Body type lives in criteria_extra (JSON).
        $extra = [];
        if (!empty($f['criteria_extra'])) {
            $decoded = json_decode((string)$f['criteria_extra'], true);
            if (is_array($decoded)) $extra = $decoded;
        }
        if (!empty($extra['body_type'])) {
            $carBody = trim((string)($car['body_type'] ?? ''));
            if ($carBody !== '' && strcasecmp(trim((string)$extra['body_type']), $carBody) !== 0) return false;
        }

        return true;
    }

    // STRICT match for adoption: a car is adopted only if it satisfies EVERY criterion
    // the filter sets, with NO benefit of the doubt. Unlike carMatchesFilter() (used at
    // import, where listing-only data is incomplete and a missing value is tolerated),
    // here a criterion the filter requires but the car can't confirm (year 0, km 0,
    // empty fuel/gearbox/body) is a REJECT. We only publish 100%-confirmed matches.
    // Filter fields the user left blank still don't constrain.
    private function carMatchesFilterStrict(array $car, array $f): bool
    {
        // Year: if the filter pins a range, the car MUST have a year inside it.
        $yf = (int)($f['year_from'] ?? 0);
        $yt = (int)($f['year_to'] ?? 0);
        if ($yf > 0 || $yt > 0) {
            $year = (int)($car['year'] ?? 0);
            if ($year <= 0) return false;                 // unknown year → reject
            if ($yf > 0 && $year < $yf) return false;
            if ($yt > 0 && $year > $yt) return false;
        }

        // Max km: car MUST have a known km <= limit.
        if (!empty($f['km_max'])) {
            $km = (int)($car['km'] ?? 0);
            if ($km <= 0) return false;                   // unknown km → reject
            if ($km > (int)$f['km_max']) return false;
        }

        // Max price (EUR): car MUST have a known price <= limit.
        if (!empty($f['price_max'])) {
            $priceEur = (float)($car['price_eur'] ?? 0);
            if ($priceEur <= 0) return false;             // unknown price → reject
            if ($priceEur > (float)$f['price_max']) return false;
        }

        // Fuel: filter stores a CSV of codes; car MUST have a fuel and it MUST be one.
        if (!empty($f['fuel_type'])) {
            $carFuel = strtolower(trim((string)($car['fuel_type'] ?? '')));
            if ($carFuel === '') return false;            // unknown fuel → reject
            $fuels = array_map('strtolower', array_filter(array_map('trim', explode(',', (string)$f['fuel_type']))));
            if (!in_array($carFuel, $fuels, true)) return false;
        }

        // Gearbox: car MUST have it and it MUST match.
        if (!empty($f['gearbox'])) {
            $carGear = trim((string)($car['gearbox'] ?? ''));
            if ($carGear === '') return false;            // unknown gearbox → reject
            if (strcasecmp(trim((string)$f['gearbox']), $carGear) !== 0) return false;
        }

        // Drive type (criteria_extra or column): car MUST have it and match, if set.
        $extra = [];
        if (!empty($f['criteria_extra'])) {
            $decoded = json_decode((string)$f['criteria_extra'], true);
            if (is_array($decoded)) $extra = $decoded;
        }
        $driveReq = trim((string)($f['drive_type'] ?? ''));
        if ($driveReq !== '') {
            $carDrive = trim((string)($car['drive_type'] ?? ''));
            if ($carDrive === '') return false;
            if (strcasecmp($driveReq, $carDrive) !== 0) return false;
        }

        // Body type (criteria_extra): car MUST have it and match, if set.
        if (!empty($extra['body_type'])) {
            $carBody = trim((string)($car['body_type'] ?? ''));
            if ($carBody === '') return false;            // unknown body → reject
            if (strcasecmp(trim((string)$extra['body_type']), $carBody) !== 0) return false;
        }

        return true;
    }

    // Adopt orphan cars (filter_id NULL/0) already sitting in the catalog that match
    // this filter, so they get auto-published instead of waiting for a fresh import
    // that dedup would skip. Runs every filter run → self-heals manual imports and
    // cars left over from a previous, now-deleted filter.
    //
    // Only proposed/favorite are claimed (published/unavailable are left untouched —
    // they're already on the site or gone). Brand/model can't be matched in SQL
    // because the filter stores Encar's Korean keys while cars hold the English
    // display name, so we translate the filter's keys first, then match in PHP.
    private function adoptOrphanCars(array $filter): int
    {
        $source = trim((string)($filter['sources'] ?? ''));
        // Single-source filters only (all current filters are); skip multi-source.
        if ($source === '' || strpos($source, ',') !== false) return 0;

        // Translate the filter's stored brand/model into the normalized English the
        // cars hold. For Encar this maps 아우디→Audi, A6→A6; other sources already
        // store English-ish keys, so translation is a no-op fallback.
        [$engBrand, $engModel] = $this->translateFilterBrandModel($source, $filter);
        if ($engBrand === '') return 0;

        // Candidate orphans: same source + normalized brand (+ model if the filter
        // pins one), still waiting to be published.
        $sql = 'SELECT * FROM '.$this->prefix.'_parsing_cars
            WHERE (filter_id IS NULL OR filter_id = 0)
              AND status IN ("proposed", "favorite")
              AND source = ?
              AND brand = ?';
        $params = [$source, $engBrand];
        if ($engModel !== '') { $sql .= ' AND model = ?'; $params[] = $engModel; }
        $sql .= ' LIMIT 2000';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $cands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return 0;
        }
        if (!$cands) return 0;

        $upd = $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars
            SET filter_id = ? WHERE id = ? AND (filter_id IS NULL OR filter_id = 0)');

        $adopted = 0;
        foreach ($cands as $car) {
            // STRICT: only adopt cars that satisfy 100% of the filter's criteria. The
            // row's keys already line up (year, km, price_eur, fuel_type, gearbox,
            // drive_type, body_type).
            if (!$this->carMatchesFilterStrict($car, $filter)) continue;
            $upd->execute([(int)$filter['id'], (int)$car['id']]);
            $adopted += $upd->rowCount();
        }
        return $adopted;
    }

    // Translate a filter's stored brand/model keys to the normalized English the
    // imported cars hold. Encar stores Korean keys (아우디 / A6); the taxonomy dump
    // maps them to eng_name. Returns ['', ''] when brand can't be resolved.
    private function translateFilterBrandModel(string $source, array $filter): array
    {
        $brand = trim((string)($filter['brand'] ?? ''));
        $model = trim((string)($filter['model'] ?? ''));
        if ($brand === '') return ['', ''];

        if ($source === 'auto1') {
            // Auto1 filters store the brand as a NUMERIC make code ("860"), but the
            // imported cars hold the manufacturer NAME ("Toyota"). Translate the code
            // to the name via the Auto1 taxonomy so orphan adoption can match cars —
            // otherwise auto1 cars never get a filter_id and never auto-publish.
            $name = $this->auto1BrandName($brand);
            return [$name !== '' ? $name : $brand, $model];
        }

        if ($source !== 'encar') {
            // Other non-Encar sources already store display-ish names; use as-is.
            return [$brand, $model];
        }

        $tax = $this->loadEncarTaxonomy();
        if (!$tax) return [$brand, $model];

        $brandInfo = $tax[$brand] ?? null;
        $engBrand = ($brandInfo['eng_name'] ?? null) ?: $brand;

        $engModel = $model;
        if ($model !== '' && $brandInfo && !empty($brandInfo['models'])) {
            $minfo = $brandInfo['models'][$model] ?? null;
            if (!$minfo) {
                // Trimmed-key fallback (taxonomy keys sometimes have stray spaces).
                foreach ($brandInfo['models'] as $k => $v) {
                    if (trim((string)$k) === $model) { $minfo = $v; break; }
                }
            }
            if ($minfo && !empty($minfo['eng_name'])) $engModel = $minfo['eng_name'];
        }
        return [$engBrand, $engModel];
    }

    // Auto1 make code ("860") → manufacturer name ("Toyota"), from auto1_taxonomy.json.
    // Cars store the name, filters store the code, so adoption must bridge the two.
    private static $auto1MakesCache = null;
    private function auto1BrandName(string $code): string
    {
        if (self::$auto1MakesCache === null) {
            $file = __DIR__ . '/Adapters/auto1_taxonomy.json';
            $json = is_file($file) ? json_decode((string)@file_get_contents($file), true) : null;
            self::$auto1MakesCache = (is_array($json) && !empty($json['makes'])) ? $json['makes'] : [];
        }
        $entry = self::$auto1MakesCache[$code] ?? null;
        return $entry ? trim((string)($entry['name'] ?? '')) : '';
    }

    private static $encarTaxCache = null;
    private function loadEncarTaxonomy(): ?array
    {
        if (self::$encarTaxCache !== null) {
            return self::$encarTaxCache ?: null;
        }
        $file = __DIR__ . '/Adapters/encar_taxonomy.json';
        if (!is_file($file)) { self::$encarTaxCache = false; return null; }
        $json = json_decode((string)@file_get_contents($file), true);
        $brands = is_array($json) ? ($json['brands'] ?? null) : null;
        self::$encarTaxCache = is_array($brands) ? $brands : false;
        return self::$encarTaxCache ?: null;
    }

    private function loadFilter(int $filterId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM '.$this->prefix.'_parsing_filters WHERE id = ? AND active = 1');
        $stmt->execute([$filterId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function filterToCriteria(array $filter): array
    {
        // The adapters read individual criteria fields (km_min, price_min, body_type,
        // car_sub_model, category, premium_offer, …). Several of those are stored
        // inside criteria_extra, so we MUST flatten them out here — otherwise the cron
        // search differs from the manual "Search" (which sends them as flat fields) and
        // a filter can find 0 cars on cron yet find them manually. Keep these in sync
        // with parsing_search_now() in content/admin/ajax/parsing/ajax.php.
        $extra = !empty($filter['criteria_extra'])
            ? (json_decode($filter['criteria_extra'], true) ?: [])
            : [];
        return [
            'brand'          => $filter['brand'] ?? null,
            'model'          => $filter['model'] ?? null,
            'generation'     => $extra['generation'] ?? null,
            'engine'         => $extra['engine'] ?? null,
            'year_from'      => $filter['year_from'] ?? null,
            'year_to'        => $filter['year_to'] ?? null,
            'km_min'         => $extra['km_min'] ?? null,
            'km_max'         => $filter['km_max'] ?? null,
            'price_min'      => $extra['price_min'] ?? null,
            'price_max'      => $filter['price_max'] ?? null,
            'fuel_type'      => $filter['fuel_type'] ?? null,
            'gearbox'        => $filter['gearbox'] ?? null,
            'drive_type'     => $filter['drive_type'] ?? null,
            'body_type'      => $extra['body_type'] ?? null,
            'category'       => $extra['category'] ?? null,
            'seats'          => $extra['seats'] ?? null,
            'engine_volume'  => $extra['engine_volume'] ?? null,
            'country_origin' => $extra['country_origin'] ?? null,
            'car_sub_model'  => $extra['car_sub_model'] ?? null,
            'premium_offer'  => $extra['premium_offer'] ?? null,
            // Encar colour facets (body + interior).
            'color'          => $extra['color'] ?? null,
            'interior_color' => $extra['interior_color'] ?? null,
            'criteria_extra' => $extra,
        ];
    }

    private function openRun(int $filterId, string $source, string $runType = 'cron'): int
    {
        $stmt = $this->db->prepare('INSERT INTO '.$this->prefix.'_parsing_runs (filter_id, source, run_type, status) VALUES (?, ?, ?, "running")');
        $stmt->execute([$filterId, $source, $runType]);
        return (int)$this->db->lastInsertId();
    }

    private function closeRun(int $runId, int $found, int $imported, int $skipped, ?string $error): void
    {
        $status = $error ? 'failed' : ($imported > 0 ? 'success' : 'partial');
        $stmt = $this->db->prepare('UPDATE '.$this->prefix.'_parsing_runs
            SET finished_at = NOW(), status = ?, found_count = ?, imported_count = ?, skipped_count = ?, error_message = ?
            WHERE id = ?');
        $stmt->execute([$status, $found, $imported, $skipped, $error, $runId]);
    }

    private function touchFilter(int $filterId, int $found, int $imported): void
    {
        // Accumulate totals across all runs of this filter (manual + cron).
        $stmt = $this->db->prepare('UPDATE '.$this->prefix.'_parsing_filters
            SET last_run_at = NOW(),
                total_found = total_found + ?,
                total_imported = total_imported + ?
            WHERE id = ?');
        $stmt->execute([$found, $imported, $filterId]);

        // Adaptive backoff bookkeeping: a run that imported nothing new bumps
        // idle_runs (cron then queries this filter less often, up to a 60-min cap);
        // a run that imported something resets it to 0 (back to normal frequency).
        // Best-effort: if the column doesn't exist, the cron just uses flat frequency.
        try {
            if ($imported > 0) {
                $this->db->prepare('UPDATE '.$this->prefix.'_parsing_filters
                    SET idle_runs = 0 WHERE id = ?')->execute([$filterId]);
            } else {
                // Don't grow unbounded — 5 is enough to reach the 60-min cap.
                $this->db->prepare('UPDATE '.$this->prefix.'_parsing_filters
                    SET idle_runs = LEAST(COALESCE(idle_runs, 0) + 1, 5) WHERE id = ?')->execute([$filterId]);
            }
        } catch (\Throwable $e) { /* column missing → backoff disabled, harmless */ }
    }
}
