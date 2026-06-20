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

                // Encar sorts by ModifiedDate desc, so the freshest listings are
                // always at offset 0. Each run does two passes:
                //   1) NEW pass  — always offset 0, catches cars listed since
                //      the last run (dedup skips the ones we already have).
                //   2) BACKFILL  — continues from last_offset to keep pulling the
                //      older part of the catalog until we've got it all.
                $passes = [
                    ['_offset_start' => 0,                                   'backfill' => false],
                    ['_offset_start' => (int)($filter['last_offset'] ?? 0),  'backfill' => true],
                ];

                $duplicates = 0;   // already-have cars skipped by dedup
                $totalCount = 0;   // Encar's total for this query (progress denominator)
                $newLastOffset = (int)($filter['last_offset'] ?? 0);
                foreach ($passes as $pass) {
                    // Skip the backfill pass if it would just repeat the NEW pass.
                    if ($pass['backfill'] && $pass['_offset_start'] === 0) continue;

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
                        // Wrap back to 0 when the catalog end is reached.
                        $newLastOffset = (count($cars) === 0) ? 0 : $adv;
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
        if (!$fast) {
            $totalImported = array_sum(array_column($summary, 'imported'));
            if ($totalImported > 0) {
                $this->enrichRecent(min($totalImported, 100), 120);
            }
        }

        return $summary;
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
        $stmt = $this->db->prepare('SELECT id, source, source_id FROM '.$this->prefix.'_parsing_cars
            WHERE source IN ("encar", "openlane", "ecarstrade")
              AND (vin IS NULL OR vin = "" OR gearbox IS NULL OR seats IS NULL OR images_local IS NULL OR images_local = "[]" OR images_local LIKE \'%"url"%\')
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

    private function insertCar(array $row, ?int $filterId): bool
    {
        $row['filter_id'] = $filterId;
        $row['status'] = 'proposed';

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

    private function loadFilter(int $filterId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM '.$this->prefix.'_parsing_filters WHERE id = ? AND active = 1');
        $stmt->execute([$filterId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function filterToCriteria(array $filter): array
    {
        $extra = !empty($filter['criteria_extra'])
            ? (json_decode($filter['criteria_extra'], true) ?: [])
            : [];
        return [
            'brand' => $filter['brand'] ?? null,
            'model' => $filter['model'] ?? null,
            'generation' => $extra['generation'] ?? null,
            'year_from' => $filter['year_from'] ?? null,
            'year_to' => $filter['year_to'] ?? null,
            'km_max' => $filter['km_max'] ?? null,
            'price_max' => $filter['price_max'] ?? null,
            'fuel_type' => $filter['fuel_type'] ?? null,
            'gearbox' => $filter['gearbox'] ?? null,
            'drive_type' => $filter['drive_type'] ?? null,
            'criteria_extra' => json_decode($filter['criteria_extra'] ?? '{}', true),
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
    }
}
