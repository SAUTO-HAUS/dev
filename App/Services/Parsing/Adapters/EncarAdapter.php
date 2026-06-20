<?php

namespace App\Services\Parsing\Adapters;

use App\Services\Parsing\AbstractAdapter;

// Encar (Korean used-car marketplace).
//
// Access mode: anonymous JSON endpoints used by the Encar mobile app.
// No login, no cookies, no Cloudflare friction. Two endpoints we rely on:
//   - GET https://api.encar.com/v1/readside/vehicle/{carid}
//     Full detail payload for a single listing (the one we hit on direct
//     link). Fields are Korean (or already-translated English in some
//     subfields), prices are in KRW.
//   - GET https://api.encar.com/search/car/list/general?q=...
//     Faceted search used by the filter flow.
//
// The detail endpoint occasionally returns HTML when it wants the client
// to re-authenticate; we treat any non-JSON body as a soft failure and
// log it so the user sees "no data" rather than a fatal error.
class EncarAdapter extends AbstractAdapter
{
    public int $lastOffset = 0;
    public int $lastTotalCount = 0;   // total cars Encar reports for the query

    private const SOURCE_CODE = 'encar';
    private const SOURCE_NAME = 'Encar (Coreea de Sud)';
    private const BASE_URL = 'https://www.encar.com';
    private const API_SEARCH = 'https://api.encar.com/search/car/list/general';
    private const API_DETAIL = 'https://api.encar.com/v1/readside/vehicle/';
    private const API_INSPECTION = 'https://api.encar.com/v1/readside/inspection/vehicle/';
    private const API_DIAGNOSIS = 'https://api.encar.com/v1/readside/diagnosis/vehicle/';
    private const API_RECORD = 'https://api.encar.com/v1/readside/record/vehicle/';
    private const IMG_HOST = 'https://ci.encar.com';
    private const DETAIL_WEB_URL = 'https://www.encar.com/dc/dc_cardetailview.do';

    public function getSourceCode(): string
    {
        return self::SOURCE_CODE;
    }

    public function getSourceName(): string
    {
        return self::SOURCE_NAME;
    }

    public function detectsUrl(string $url): bool
    {
        return stripos($url, 'encar.com') !== false || stripos($url, 'fem.encar.com') !== false;
    }

    public function searchByFilter(array $criteria): array
    {
        // A merged brand carries several Korean manufacturer codes joined by "|"
        // (e.g. Renault = "르노|르노코리아(삼성)" — the second is the ex-Samsung make
        // with SM3/SM5/QM... models). Encar's facet accepts only ONE manufacturer per
        // query, so run a separate search per code and merge the results (dedup by id).
        $brand = (string)($criteria['brand'] ?? '');
        if (strpos($brand, '|') !== false && empty($criteria['_single_brand'])) {
            $codes = array_filter(array_map('trim', explode('|', $brand)));
            $merged = [];
            $seen = [];
            $perCode = max(50, (int)(($criteria['_max_results'] ?? 200)));
            foreach ($codes as $code) {
                $sub = $criteria;
                $sub['brand'] = $code;
                $sub['_single_brand'] = true;       // prevent infinite recursion
                $sub['_max_results'] = $perCode;
                foreach ($this->searchByFilter($sub) as $car) {
                    $id = $car['source_id'] ?? null;
                    if ($id === null || isset($seen[$id])) continue;
                    $seen[$id] = true;
                    $merged[] = $car;
                }
            }
            return $merged;
        }

        $resolved = $this->resolveBrandModel($criteria['brand'] ?? null, $criteria['model'] ?? null);

        $variantCriteria = $criteria;
        if ($resolved) {
            $variantCriteria['brand'] = $resolved['brand'];
            $variantCriteria['model'] = $resolved['model'];
        }

        $carType = ($resolved && $resolved['car_type']) ? $resolved['car_type'] : 'A';
        $pageSize = 50;
        $maxResults = (int)($criteria['_max_results'] ?? 200);
        $defaultPages = min(80, max(20, (int)ceil($maxResults / $pageSize) * 3));
        $maxPages   = (int)($criteria['_max_pages'] ?? $defaultPages);
        $offset = (int)($criteria['_offset_start'] ?? 0);
        $seen = [];
        $results = [];
        $pagesFetched = 0;

        while (count($results) < $maxResults && $pagesFetched < $maxPages) {
            $query = $this->buildSearchQuery($variantCriteria, $carType, $offset, $pageSize);
            $url = self::API_SEARCH . '?' . $query;

            $response = $this->httpRequest($url, [
                'headers' => [
                    'Accept: application/json',
                    'Referer: https://www.encar.com/',
                ],
            ]);

            $tmpData = json_decode($response['body'] ?? '', true);
            $this->logError('Encar search', [
                'offset' => $offset,
                'status' => $response['status'],
                'body_length' => strlen($response['body'] ?? ''),
                'total_count' => $tmpData['Count'] ?? 'no',
                'results_count' => isset($tmpData['SearchResults']) ? count($tmpData['SearchResults']) : 'no',
                'url' => $url,
            ]);

            if ($response['status'] !== 200 || empty($response['body'])) break;
            $data = json_decode($response['body'], true);
            if (!is_array($data) || empty($data['SearchResults'])) break;
            // Total number of cars Encar has for this query (for progress display).
            if (isset($data['Count'])) $this->lastTotalCount = (int)$data['Count'];

            $rawRows = count($data['SearchResults']);
            $pageCount = 0;
            foreach ($data['SearchResults'] as $item) {
                $id = (string)($item['Id'] ?? '');
                if ($id === '' || isset($seen[$id])) continue;
                $seen[$id] = true;
                // Encar flags re-listings of the same physical car with
                // ServiceCopyCar = "DUPLICATION" (the genuine ad is "ORIGINAL").
                // Skip the duplicates — exactly like encar.com does — so the same
                // car doesn't get imported twice under different listing Ids.
                if (($item['ServiceCopyCar'] ?? '') === 'DUPLICATION') {
                    $pageCount++;   // still counts as a processed row for paging
                    continue;
                }
                $mapped = $this->mapListingItem($item);
                if (!empty($mapped['__skip'])) { $pageCount++; continue; } // e.g. Renault Samsung SM5/SM7
                $results[] = $this->normalizeCarData($mapped);
                $pageCount++;
                if (count($results) >= $maxResults) break 2;
            }

            $pagesFetched++;
            $offset += $pageSize;
            if ($rawRows === 0) break;
            if ($this->lastTotalCount > 0 && $offset >= $this->lastTotalCount) break;
        }
        // Remember the next offset to resume from on the following run.
        $this->lastOffset = $offset + $pageSize;
        return $results;
    }

    private function resolveBrandModel(?string $brand, ?string $model): ?array
    {
        if (!$brand) return null;
        $taxonomy = $this->loadTaxonomy();
        if (!$taxonomy) {
            // Fallback: just pass through as user typed.
            return ['brand' => $brand, 'model' => $model, 'car_type' => null];
        }

        // Merged makes arrive as "code1|code2"; resolve on the primary code.
        $brand = trim(explode('|', $brand)[0]);
        $needle = mb_strtolower($brand, 'UTF-8');

        // Find brand by matching against the key OR Korean label OR English name.
        $matched = null;
        foreach ($taxonomy['brands'] as $key => $info) {
            if (mb_strtolower($key, 'UTF-8') === $needle
                || mb_strtolower($info['name_kr'] ?? '', 'UTF-8') === $needle
                || mb_strtolower($info['eng_name'] ?? '', 'UTF-8') === $needle) {
                $matched = ['key' => $key, 'info' => $info];
                break;
            }
        }
        if (!$matched) {
            return ['brand' => $brand, 'model' => $model, 'car_type' => null];
        }

        $carType = $matched['info']['car_types'][0] ?? null;
        $modelKey = $model;
        if ($model) {
            $modelNeedle = mb_strtolower(trim($model), 'UTF-8');
            foreach ($matched['info']['models'] ?? [] as $mkey => $minfo) {
                if (mb_strtolower($mkey, 'UTF-8') === $modelNeedle
                    || mb_strtolower($minfo['name_kr'] ?? '', 'UTF-8') === $modelNeedle
                    || mb_strtolower($minfo['eng_name'] ?? '', 'UTF-8') === $modelNeedle) {
                    $modelKey = $mkey;
                    $carType = $minfo['car_type'] ?? $carType;
                    break;
                }
            }
        }

        return [
            'brand' => $matched['key'],
            'model' => $modelKey,
            'car_type' => $carType,
        ];
    }

    private function loadTaxonomy(): ?array
    {
        static $cache = null;
        if ($cache !== null) return $cache ?: null;
        $file = __DIR__ . '/encar_taxonomy.json';
        if (!file_exists($file)) {
            $cache = false;
            return null;
        }
        $json = json_decode(file_get_contents($file), true);
        $cache = is_array($json) ? $json : false;
        return $cache ?: null;
    }

    public function fetchByUrl(string $url): ?array
    {
        $sourceId = $this->extractIdFromUrl($url);
        if (!$sourceId) {
            $this->logError('Cannot extract source_id from URL', ['url' => $url]);
            return null;
        }
        return $this->fetchById($sourceId);
    }

    public function fetchById(string $sourceId): ?array
    {
        $response = $this->httpRequest(self::API_DETAIL . urlencode($sourceId), [
            'headers' => [
                'Accept: application/json',
                'Referer: https://www.encar.com/',
            ],
        ]);

        if ($response['status'] !== 200 || empty($response['body'])) {
            $this->logError('Encar fetchById HTTP failure', [
                'source_id' => $sourceId,
                'status' => $response['status'],
                'error' => $response['error'],
            ]);
            return null;
        }

        $data = json_decode($response['body'], true);
        if (!is_array($data)) {
            $this->logError('Encar fetchById non-JSON body', [
                'source_id' => $sourceId,
                'sample' => substr($response['body'], 0, 200),
            ]);
            return null;
        }

        $mapped = $this->mapDetailPayload($sourceId, $data);
        return $this->normalizeCarData($mapped);
    }

    public function checkAvailability(string $sourceId): bool
    {
        $response = $this->httpRequest(self::API_DETAIL . urlencode($sourceId));
        if ($response['status'] === 404) {
            return false;
        }
        if ($response['status'] !== 200) {
            return true;
        }
        // Encar marks sold listings either via a Korean status string or by
        // returning a payload with an explicit "sold" / "Y" flag.
        if (stripos($response['body'], '판매완료') !== false) {
            return false;
        }
        $data = json_decode($response['body'], true);
        if (is_array($data) && (($data['advertisement']['status'] ?? '') === 'SOLD' || ($data['sellType'] ?? '') === 'Sold')) {
            return false;
        }
        return true;
    }

    /**
     * Pull the full Encar inspection report for a listing: the technical
     * inspection (engine/transmission/brakes/body), the visual diagnosis
     * (per-panel condition) and the accident/ownership record. Each part is
     * best-effort — if one endpoint fails the others are still returned.
     *
     * Returns the raw Korean JSON as-is (translation/formatting happens later
     * at display time); null only when nothing could be fetched.
     */
    public function fetchInspectionReport(string $carid): ?array
    {
        $carid = trim($carid);
        if ($carid === '') return null;

        $headers = [
            'Accept: application/json',
            'Referer: https://fem.encar.com/',
        ];
        $get = function (string $url) use ($headers) {
            $resp = $this->httpRequest($url, ['headers' => $headers]);
            if (!$resp || ($resp['status'] ?? 0) !== 200 || empty($resp['body'])) return null;
            $data = json_decode($resp['body'], true);
            return is_array($data) ? $data : null;
        };

        // Re-registered ("dummy") listings carry a different URL carid than the
        // real vehicleId the inspection endpoints expect. Resolve the real id
        // from the detail payload (manage.dummy => use top-level vehicleId).
        $reportCarid = $carid;
        $detail = $get(self::API_DETAIL . urlencode($carid));
        if ($detail) {
            $realId = $detail['vehicleId'] ?? null;
            if (!empty($realId) && (string)$realId !== $carid) {
                $reportCarid = (string)$realId;
            }
        }

        $report = [
            'carid'       => $carid,
            'real_carid'  => $reportCarid,
            'inspection'  => $get(self::API_INSPECTION . urlencode($reportCarid)),
            'diagnosis'   => $get(self::API_DIAGNOSIS . urlencode($reportCarid)),
            'record'      => $get(self::API_RECORD . urlencode($reportCarid) . '/open'),
            // Equipment/option codes come straight from the detail payload we
            // already fetched (no extra request). Translated at display time.
            'options'     => $detail['options'] ?? null,
            '_fetched_at' => date('Y-m-d H:i:s'),
        ];

        // Nothing useful came back.
        if (empty($report['inspection']) && empty($report['diagnosis'])
            && empty($report['record']) && empty($report['options'])) {
            return null;
        }
        return $report;
    }

    private function buildSearchQuery(array $criteria, string $carType = 'A', int $offset = 0, int $pageSize = 50): string
    {
        // The brand key is already resolved to the exact Encar API spelling
        // (Korean for most brands, Latin uppercase for BMW/MINI etc.).
        //
        // IMPORTANT: Encar's search API only narrows correctly when ALL terms sit
        // at the SAME level inside the top-level And, joined by "._.". The older
        // nested "(C.CarType._.(C.Manufacturer._.ModelGroup.))" form matched the
        // brand/model but silently IGNORED the fuel/transmission/etc. facets (the
        // count stayed identical). A FLAT structure filters every facet correctly
        // (verified against api.encar.com). So we collect every term into one list
        // and join them all with "._.".
        $terms = [];
        $terms[] = 'CarType.' . $carType;
        // Only normal/standard sales: SellType.일반 excludes special listings
        // (auctions, wholesale, atypical offers). Sold cars are already gone from
        // the list via Hidden.N, so this just drops the non-standard ones.
        $terms[] = 'SellType.일반';
        if (!empty($criteria['brand'])) {
            // Merged makes carry several Korean codes joined by "|" (e.g. Chevrolet
            // = "쉐보레(GM대우)|쉐보레"). Encar's facet takes one manufacturer, so use
            // the first (primary) code, which holds the bulk of the listings.
            $brandCode = trim(explode('|', $criteria['brand'])[0]);
            $terms[] = 'Manufacturer.' . $brandCode;
            if (!empty($criteria['model'])) {
                $terms[] = 'ModelGroup.' . trim($criteria['model']);
                if (!empty($criteria['generation'])) {
                    $terms[] = 'Model.' . trim($criteria['generation']);
                }
                if (!empty($criteria['car_sub_model'])) {
                    $terms[] = 'Badge.' . trim($criteria['car_sub_model']);
                }
            }
        }

        // Remaining facets (km / price / year / fuel / transmission / category),
        // collected into the same flat list.
        $facets = [];

        if (!empty($criteria['km_min']) || !empty($criteria['km_max'])) {
            $kmFrom = !empty($criteria['km_min']) ? (int)$criteria['km_min'] : '';
            $kmTo   = !empty($criteria['km_max']) ? (int)$criteria['km_max'] : '';
            $facets[] = 'Mileage.range(' . $kmFrom . '..' . $kmTo . ')';
        }
        if (!empty($criteria['price_min']) || !empty($criteria['price_max'])) {
            // Encar prices are in "manwon" (1 manwon = 10,000 KRW).
            // 1 EUR ≈ 1753 KRW = 0.1753 manwon → EUR * 0.1753 = manwon.
            $fromManwon = !empty($criteria['price_min']) ? (int)round($criteria['price_min'] * 0.1753) : '';
            $toManwon   = !empty($criteria['price_max']) ? (int)round($criteria['price_max'] * 0.1753) : '';
            $facets[] = 'Price.range(' . $fromManwon . '..' . $toManwon . ')';
        }
        if (!empty($criteria['year_from']) || !empty($criteria['year_to'])) {
            // Encar's Year.range expects YYYYMM..YYYYMM (e.g. 201700..202599).
            // We filter on Year (제조년월 = build date) AND display the build year
            // (below) so the search result matches the chosen year — a "2024"
            // search returns cars whose displayed year is 2024.
            $from = !empty($criteria['year_from']) ? ((int)$criteria['year_from'] . '00') : '';
            $to   = !empty($criteria['year_to'])   ? ((int)$criteria['year_to']   . '99') : '';
            $facets[] = 'Year.range(' . $from . '..' . $to . ')';
        }
        if (!empty($criteria['fuel_type'])) {
            $fuelMap = [
                'benzina'       => '가솔린',
                'gasoline'      => '가솔린',
                'diesel'        => '디젤',
                'lpg'           => 'LPG(일반인 구입)',
                'gasoline_lpg'  => '가솔린+LPG',
                'gasoline_cng'  => '가솔린+CNG',
                'hybrid'        => '가솔린+전기',
                'plugin_hybrid' => '가솔린+전기',
                'diesel_hybrid' => '디젤+전기',
                'electric'      => '전기',
                'other'         => '기타',
            ];
            $fuelKr = $fuelMap[strtolower($criteria['fuel_type'])] ?? null;
            if ($fuelKr) {
                $facets[] = 'FuelType.' . $fuelKr;
            }
        }
        if (!empty($criteria['gearbox'])) {
            $gearMap = [
                'automat'   => '오토',
                'manual'    => '수동',
                'semi-auto' => '세미오토',
                'cvt'       => 'CVT',
            ];
            $gearKr = $gearMap[strtolower($criteria['gearbox'])] ?? null;
            if ($gearKr) {
                $facets[] = 'Transmission.' . $gearKr;
            }
        }
        if (!empty($criteria['category'])) {
            $facets[] = 'Category.' . trim($criteria['category']);
        }

        $all = array_merge($terms, $facets);
        $q = '(And.Hidden.N._.' . implode('._.', $all) . '.)';

        return 'count=true&q=' . rawurlencode($q) . '&sr=' . rawurlencode('|ModifiedDate|' . $offset . '|' . $pageSize);
    }

    private function mapListingItem(array $item): array
    {
        $year = null;
        if (!empty($item['Year'])) {
            $year = (int)substr((string)$item['Year'], 0, 4);
        } elseif (!empty($item['FormYear'])) {
            $year = (int)$item['FormYear'];
        }

        $images = [];
        if (!empty($item['Photos']) && is_array($item['Photos'])) {
            $photos = $item['Photos'];
            usort($photos, fn($a, $b) => ($a['ordering'] ?? 0) <=> ($b['ordering'] ?? 0));
            foreach ($photos as $p) {
                if (!empty($p['location'])) {
                    $images[] = self::IMG_HOST . $p['location'];
                }
            }
        } elseif (!empty($item['Photo'])) {
            $images[] = self::IMG_HOST . $item['Photo'] . '001.jpg';
        }

        $fuelMap = [
            '가솔린'           => 'benzina',
            '디젤'             => 'diesel',
            'LPG(일반인 구입)' => 'lpg',
            '가솔린+전기'      => 'hybrid',
            '디젤+전기'        => 'diesel_hybrid',
            '가솔린+LPG'       => 'gasoline_lpg',
            '가솔린+CNG'       => 'gasoline_cng',
            '전기'             => 'electric',
            '기타'             => 'other',
        ];
        $fuelType = $item['FuelType'] ?? null;
        if ($fuelType && isset($fuelMap[$fuelType])) {
            $fuelType = $fuelMap[$fuelType];
        }

        $krBrand = $item['Manufacturer'] ?? null;
        $krModel = $item['Model'] ?? null;
        [$engBrand, $engModel] = $this->translateBrandModel($krBrand, $krModel);

        $badge = trim($item['Badge'] ?? '');
        $badgeClean = preg_match('/[\x{AC00}-\x{D7A3}\x{1100}-\x{11FF}\x{3130}-\x{318F}]/u', $badge) ? '' : $badge;

        $subModel = $this->extractTrim($badge, $item['BadgeDetail'] ?? '', (string)$krModel);

        if (stripos((string)$engBrand, 'renault') !== false && trim((string)$engModel) === '') {
            return ['__skip' => true];
        }

        $finalBrand = $this->cleanBrandName($engBrand ?: $krBrand);
        $finalModel = $this->cleanModelName($engModel ?: $krModel);
        $title = trim($finalBrand . ' ' . $finalModel . ($subModel !== '' ? ' ' . $subModel : ''));

        return [
            'source_id'  => (string)($item['Id'] ?? ''),
            'source_url' => self::DETAIL_WEB_URL . '?carid=' . ($item['Id'] ?? ''),
            'vin'        => null, // not in listing payload; only in detail
            'brand'      => $finalBrand,
            'model'      => $finalModel,
            'car_sub_model' => $subModel !== '' ? $subModel : null,
            'year'       => $year,
            'km'         => isset($item['Mileage']) ? (int)$item['Mileage'] : null,
            'fuel_type'  => $fuelType,
            'gearbox'    => $this->normalizeGearbox($item['Transmission'] ?? $item['Mission'] ?? null),
            'engine_volume' => $this->engineVolumeFromBadge($item['Badge'] ?? null),
            'price_source' => isset($item['Price']) ? (float)$item['Price'] * 10000 : null,
            'price_source_currency' => 'KRW',
            'title'  => $title,
            'images' => $images,
        ];
    }

    private function extractTrim(string $badge, string $badgeDetail = '', string $krModel = ''): string
    {
        static $map = [
            '노블레스 스페셜' => 'Noblesse Special',
            '롱 레인지'      => 'Long Range',
            '롱레인지'       => 'Long Range',
            '스탠다드 레인지'=> 'Standard Range',
            '퍼포먼스'       => 'Performance',
            '듀얼 모터'      => 'Dual Motor',
            '하이리무진'     => 'High Limousine',
            '시그니처'       => 'Signature',
            '노블레스'       => 'Noblesse',
            '프레스티지'     => 'Prestige',
            '그래비티'       => 'Gravity',
            '캘리그래피'     => 'Calligraphy',
            '인스퍼레이션'   => 'Inspiration',
            '익스클루시브'   => 'Exclusive',
            '셀러브리티'     => 'Celebrity',
            '엑스라인'       => 'X-Line',
            '리무진'         => 'Limousine',
            '럭셔리'         => 'Luxury',
            '프리미엄'       => 'Premium',
            '스마트'         => 'Smart',
            '아웃도어'       => 'Outdoor',
            '트렌디'         => 'Trendy',
            '디럭스'         => 'Deluxe',
            '스페셜'         => 'Special',
            '모던'           => 'Modern',
            '스타일'         => 'Style',
        ];
        $hay = $badge . ' ' . $badgeDetail;
        foreach ($map as $kr => $latin) {
            if (mb_strpos($hay, $kr) !== false) {
                return $latin;
            }
        }
        // BadgeDetail sometimes already holds a Latin trim (e.g. "VIP").
        $bd = trim($badgeDetail);
        if ($bd !== '' && !preg_match('/[\x{AC00}-\x{D7A3}]/u', $bd)) {
            return $bd;
        }

        // Clean the badge: drop seat count, fuel, drivetrain and the model's own
        // words (e.g. for "그랜드 카니발 GLX" remove "그랜드 카니발" so only "GLX" stays).
        $raw = trim($badge);
        if ($raw === '') return '';
        $raw = preg_replace('/\d+\s*인승/u', '', $raw);                          // "9인승" seats
        $raw = preg_replace('/(가솔린|디젤|하이브리드|전기|LPG)/u', '', $raw);       // fuel
        $raw = preg_replace('/(\b\d\.\d[tT]?\b|2WD|4WD|AWD)/u', '', $raw);        // engine / drive
        if ($krModel !== '') {
            foreach (preg_split('/\s+/u', $krModel) as $w) {
                $w = trim($w);
                if ($w !== '' && mb_strlen($w) > 1) {
                    $raw = str_replace($w, '', $raw);
                }
            }
        }
        $raw = trim(preg_replace('/\s+/u', ' ', $raw));
        if ($raw === '') return '';

        // If a Latin trim token survived (GLX, VIP, ...), that IS the trim — use it.
        if (preg_match('/[A-Za-z][A-Za-z0-9+\-]*/u', $raw, $m)
            && !preg_match('/[\x{AC00}-\x{D7A3}]/u', $m[0])) {
            return $m[0];
        }
        // Otherwise keep the remaining Korean trim for the batch Groq translation.
        if (preg_match('/[\x{AC00}-\x{D7A3}]/u', $raw)) {
            return $raw;
        }
        return $raw;
    }

    // Extract engine displacement (in cc) from an Encar Badge string. The badge
    // carries it as litres, e.g. "2.5T 가솔린 AWD" -> 2500, "디젤 2.2 4WD" -> 2200.
    // Returns null when no plausible litre value (1.0–8.9) is present, so detail
    // enrichment can still fill it in later.
    private function engineVolumeFromBadge(?string $badge): ?int
    {
        if (!$badge) return null;
        if (preg_match('/(?<![\d.])([1-8]\.\d)(?![\d.])/u', $badge, $m)) {
            return (int) round(((float) $m[1]) * 1000);
        }
        return null;
    }

    // Map Korean body type names from Encar to our internal English codes
    // (which the sauto form then maps to its own short codes).
    // Covers both spec.bodyName (specific) and category.* (broader buckets).
    private function normalizeBodyType(?string $val): ?string
    {
        if (!$val) return null;
        $map = [
            // Specific shapes
            '세단'        => 'sedan',
            'SUV'         => 'suv',
            '대형SUV'     => 'suv',
            '소형SUV'     => 'suv',
            '준중형SUV'   => 'suv',
            '중형SUV'     => 'suv',
            '해치백'      => 'hatchback',
            '왜건'        => 'wagon',
            '쿠페'        => 'coupe',
            '컨버터블'    => 'convertible',
            '오픈카'      => 'convertible',
            '미니밴'      => 'minivan',
            '픽업'        => 'pickup',
            '픽업트럭'    => 'pickup',
            '밴'          => 'van',
            '스포츠카'    => 'coupe',
            // Encar category buckets (size-based)
            '경차'        => 'hatchback',   // Kei / very small car
            '소형차'      => 'hatchback',   // small car
            '준중형차'    => 'sedan',       // compact sedan
            '중형차'      => 'sedan',       // mid sedan
            '대형차'      => 'sedan',       // large sedan
            'RV'          => 'minivan',     // recreational vehicle
            '경승합차'    => 'microbus',
            '승합차'      => 'minivan',
            '화물차'      => 'pickup',
            '기타'        => 'sedan',        // misc — neutral default
        ];
        return $map[trim($val)] ?? 'sedan';
    }

    // Map Korean colour names from Encar to sauto colour codes.
    // Two-tone variants and metallic/light/dark shades collapse to the base sauto code.
    private function normalizeColor(?string $val): ?string
    {
        if (!$val) return null;
        $map = [
            // Black family
            '검정색' => 'blk', '검정투톤' => 'blk',
            // White / pearl family
            '흰색' => 'wht', '흰색투톤' => 'wht', '진주색' => 'wht', '진주투톤' => 'wht',
            // Silver family
            '은색' => 'slv', '은색투톤' => 'slv', '은하색' => 'slv', '명은색' => 'slv',
            // Gray family
            '쥐색' => 'gra', '은회색' => 'gra', '회색' => 'gra',
            // Brown family
            '갈색' => 'brn', '갈색투톤' => 'brn', '갈대색' => 'brn',
            // Gold family
            '금색' => 'gld', '금색투톤' => 'gld', '연금색' => 'gld',
            // Blue family
            '청색' => 'blu', '파란색' => 'blu',
            '하늘색' => 'azr', '청옥색' => 'azr',
            // Green family
            '녹색' => 'grn',
            '담녹색' => 'd_grn', '진한녹색' => 'd_grn',
            '연두색' => 'l_grn',
            // Red / orange / yellow
            '빨간색' => 'red',
            '주황색' => 'orn',
            '노란색' => 'ylw',
            // Purple family
            '자주색' => 'vns', '보라색' => 'prp', '분홍색' => 'pnk', '와인색' => 'vns',
            // Beige
            '베이지' => 'bge',
        ];
        // Unknown Encar colours fall back to "wht" so sauto auto-publish never
        // fails on a missing colour — user can still edit it manually.
        return $map[trim($val)] ?? 'wht';
    }

    // Guess drive type from grade/badge strings since Encar API doesn't return it.
    private function inferDriveType(?string $text): ?string
    {
        if (!$text) return null;
        $t = strtolower($text);
        // 4x4 / AWD signals
        if (preg_match('/\b(xdrive|quattro|4matic|4motion|allroad|all-?wheel|awd|4wd|sh-?awd|symmetrical|t-?awd)\b/i', $t)) return '4x4';
        if (preg_match('/\b(s-?awc)\b/i', $t)) return '4x4';
        if (preg_match('/\bsdrive\b/i', $t)) return 'rwd';
        return null;
    }

    private function normalizeDriveType(?string $val): ?string
    {
        if (!$val) return null;
        $v = trim($val);
        // Encar uses Korean labels; map to our internal codes.
        $map = [
            '4WD' => '4x4', '4wd' => '4x4', '사륜구동' => '4x4', '상시4륜' => '4x4', '풀타임4륜' => '4x4',
            'FF' => 'fwd', '전륜구동' => 'fwd', '앞바퀴굴림' => 'fwd',
            'FR' => 'rwd', '후륜구동' => 'rwd', '뒷바퀴굴림' => 'rwd',
            'AWD' => '4x4', 'awd' => '4x4',
        ];
        return $map[$v] ?? strtolower($v);
    }

    private function normalizeGearbox(?string $val): ?string
    {
        if (!$val) return null;
        $map = [
            '오토' => 'automat', '자동' => 'automat',
            '수동' => 'manual',
            '세미오토' => 'semi-auto',
            'CVT' => 'cvt',
            '기타' => 'other',
        ];
        return $map[$val] ?? $val;
    }

    // Public wrapper so maintenance scripts can re-translate already-imported
    // rows (e.g. fixing Korean model names left over from earlier imports).
    public function translateBrandModelPublic(?string $krBrand, ?string $krModel): array
    {
        return $this->translateBrandModel($krBrand, $krModel);
    }

    // Translate Korean brand/model API keys to English using taxonomy eng_name.
    // Returns [$engBrand, $engModel] — falls back to Korean value if taxonomy missing or key not found.
    private function translateBrandModel(?string $krBrand, ?string $krModel): array
    {
        if (!$krBrand) return ['', ''];

        $taxonomy = $this->loadTaxonomy();
        if (!$taxonomy || empty($taxonomy['brands'])) {
            return [$krBrand, $krModel ?? ''];
        }

        $brandInfo = $taxonomy['brands'][$krBrand] ?? null;
        $engBrand = ($brandInfo['eng_name'] ?? null) ?: $krBrand;

        $engModel = $krModel ?? '';
        if ($krModel && $brandInfo) {
            $models = $brandInfo['models'] ?? [];

            // Strip known Korean marketing prefixes before lookup.
            // e.g. "더 뉴 팰리세이드" → "팰리세이드", "올 뉴 쏘렌토" → "쏘렌토"
            $strippedModel = trim(preg_replace('/^(더 뉴 |올 뉴 |더 |뉴 )/u', '', $krModel));
            $krModelTrim   = trim($krModel);

            // Build a trimmed-key lookup once — the taxonomy dump sometimes has
            // stray leading/trailing spaces in keys (e.g. "씨라이언 7 "), which
            // broke exact and prefix matching and left the model untranslated.
            $modelsTrimmed = [];
            foreach ($models as $k => $v) { $modelsTrimmed[trim((string)$k)] = $v; }

            // 1. Exact key (trimmed both sides).
            $modelInfo = $modelsTrimmed[$krModelTrim] ?? null;
            // 2. Stripped prefix key.
            if (!$modelInfo && $strippedModel !== $krModelTrim) {
                $modelInfo = $modelsTrimmed[$strippedModel] ?? null;
            }
            // 3. First token of stripped key (removes generation suffix e.g. "4세대").
            if (!$modelInfo) {
                $baseKey = explode(' ', $strippedModel)[0];
                $modelInfo = $modelsTrimmed[$baseKey] ?? null;
            }
            // 4. Either string is a prefix of the other (handles partial/extra
            //    tokens). Compare trimmed strings both ways.
            if (!$modelInfo) {
                foreach ($modelsTrimmed as $key => $info) {
                    if ($key === '') continue;
                    if (strpos($strippedModel, $key) === 0 || strpos($key, $strippedModel) === 0) {
                        $modelInfo = $info;
                        break;
                    }
                }
            }

            $engModel = ($modelInfo['eng_name'] ?? null) ?: null;

            if (!$engModel) {
                $bestKey = '';
                $bestInfo = null;
                foreach ($modelsTrimmed as $key => $info) {
                    if (mb_strlen($key, 'UTF-8') < 2) continue;
                    if (mb_strpos($krModelTrim, $key, 0, 'UTF-8') !== false
                        && mb_strlen($key, 'UTF-8') > mb_strlen($bestKey, 'UTF-8')) {
                        $bestKey = $key;
                        $bestInfo = $info;
                    }
                }
                if ($bestInfo) {
                    $engModel = ($bestInfo['eng_name'] ?? null) ?: $bestKey;
                    $pos = mb_strpos($krModelTrim, $bestKey, 0, 'UTF-8');
                    $prefix = trim(mb_substr($krModelTrim, 0, $pos, 'UTF-8'));
                    if ($prefix !== '') {
                        $words = [];
                        foreach (explode(' ', $prefix) as $w) {
                            $tw = $this->translateModelWord($w);
                            if ($tw !== '') $words[] = $tw;
                        }
                        if ($words) $engModel = implode(' ', $words) . ' ' . $engModel;
                    }
                }
            }

            $engModel = $engModel ?: $krModel;
            $engModel = $this->fixEngName(trim($engModel));
        }


        [$engBrand, $engModel] = $this->mapRenaultSamsung($engBrand, $engModel);

        return [$engBrand, $engModel];
    }

    private function mapRenaultSamsung(?string $brand, ?string $model): array
    {
        $b = trim((string)$brand);
        $m = trim((string)$model);
        // Only act on Renault (the cleaned brand) with a Samsung-coded model.
        if (stripos($b, 'renault') === false && stripos($m, 'samsung') === false) {
            return [$brand, $model];
        }

        $code = strtoupper(trim(preg_replace('/^\s*samsung\s+/i', '', $m)));
        if ($code === '') {
            return [($b !== '' ? $b : $brand), $model];
        }

        return ['Renault Samsung', $code];
    }

    private function translateModelWord(string $word): string
    {
        static $map = [
            '그랜드' => 'Grand',
            '뉴'     => 'New',
            '올'     => 'All',
            '신형'   => '',   
        ];
        $w = trim($word);
        if ($w === '') return '';
        // ASCII words (already English, e.g. "All New XJ" leftovers) pass through.
        if (preg_match('/^[A-Za-z0-9\-]+$/', $w)) return $w;
        return $map[$w] ?? '';
    }

    // Correct known typos/casing errors in Encar's own eng_name values.
    // Safe to extend — corrections survive taxonomy regeneration.
    private function fixEngName(string $name): string
    {
        static $fixes = [
            // Typos
            'Canival'        => 'Carnival',
            'Santafe'        => 'Santa Fe',
            'Traget XG'      => 'Trajet XG',
            'Town&Contry'    => 'Town & Country',
            'PT Crusier'     => 'PT Cruiser',
            'SpeedSter'      => 'Speedster',
            'LeSable'        => 'LeSabre',
            'Del sol'        => 'Del Sol',
            'GranTurismo'    => 'GranTurismo',
            'MCPura'         => 'MC Pura',
            'HiAce'          => 'HiAce',
            'MarkX'          => 'Mark X',
            'C4 SpaceTourer' => 'C4 SpaceTourer',
            'Grand AM'       => 'Grand Am',
            'Trans AM'       => 'Trans Am',
            'BlueOn'         => 'Blue On',
            // Casing fixes
            'morning'        => 'Morning',
            'pride'          => 'Pride',
            'damas'          => 'Damas',
            'labo'           => 'Labo',
            // Korean not translated
            '볼트 EUV'       => 'Bolt EUV',
            // Trailing spaces
            'QM5 '           => 'QM5',
            'SM5 '           => 'SM5',
        ];

        $name = trim($name);
        return $fixes[$name] ?? $name;
    }

    // Clean composite brand names from Encar taxonomy.
    // Generic rule: keep the first English word/segment when brand is a
    // concatenation like "ChevroletGMDaewoo" or hyphenated like "Citroen-DS".
    private function cleanBrandName(?string $name): ?string
    {
        if (!$name) return $name;
        $name = trim($name);

        // Known Encar brand spellings that are glued / lower-cased / abbreviated.
        // Checked case-insensitively against the whole name (after trimming).
        static $brandFixes = [
            'astonmartin'   => 'Aston Martin',
            'landrover'     => 'Land Rover',
            'rollsroyce'    => 'Rolls-Royce',
            'alfaromeo'     => 'Alfa Romeo',
            'mercedesbenz'  => 'Mercedes Benz',
            'mercedes'      => 'Mercedes Benz',
            'benz'          => 'Mercedes Benz',
            'mini'          => 'Mini',
            'vw'            => 'Volkswagen',
            'volkswagen'    => 'Volkswagen',
            'gmc'           => 'GMC',
            'ds'            => 'DS',
            'renaultsamsung' => 'Renault Samsung',
        ];
        $key = mb_strtolower(preg_replace('/[\s\-_]+/', '', $name), 'UTF-8');
        if (isset($brandFixes[$key])) return $brandFixes[$key];

        // Hyphen-joined: take first part (e.g. "Citroen-DS" -> "Citroen").
        if (strpos($name, '-') !== false) {
            $name = trim(explode('-', $name)[0]);
        }

        // CamelCase concatenation: split at uppercase boundaries and keep
        // the first recognizable word (e.g. "ChevroletGMDaewoo" -> "Chevrolet",
        // "RenaultSamsung" -> "Renault").
        if (preg_match('/^[A-Z][a-z]+(?=[A-Z])/', $name, $m)) {
            $name = $m[0];
        }
        return $name;
    }

    // Strip common Korean marketing prefixes left in model names.
    private function cleanModelName(?string $name): ?string
    {
        if (!$name) return $name;
        $name = trim($name);
        // Korean marketing prefixes used in model names.
        // Add new ones here as you spot them; the regex matches at the start.
        $name = preg_replace('/^(더 넥스트 |올 뉴 |더 뉴 |더 |뉴 |올 |신형 )/u', '', $name);
        return trim($name);
    }

    private function buildDetailImages(array $photosRaw): array
    {
        $keepTypes = ['THUMBNAIL', 'OUTER', 'INNER', 'OPTION'];
        $photos = array_values(array_filter($photosRaw, function ($p) use ($keepTypes) {
            return in_array($p['type'] ?? '', $keepTypes, true);
        }));

        // Derive the canonical hero path "<prefix>_001.<ext>" from any photo.
        $forcedHero = null;
        foreach ($photos as $p) {
            if (preg_match('#^(.*)_\d+\.(\w+)$#', $p['path'] ?? '', $m)) {
                $forcedHero = $m[1] . '_001.' . $m[2];
                break;
            }
        }

        usort($photos, function ($a, $b) {
            $na = preg_match('/_(\d+)\.\w+$/', $a['path'] ?? '', $ma) ? (int)$ma[1] : 999;
            $nb = preg_match('/_(\d+)\.\w+$/', $b['path'] ?? '', $mb) ? (int)$mb[1] : 999;
            return $na <=> $nb;
        });

        $images = [];
        $seen = [];
        if ($forcedHero) {
            $images[] = self::IMG_HOST . $forcedHero;
            $seen[$forcedHero] = true;
        }
        foreach ($photos as $p) {
            $path = $p['path'] ?? '';
            if ($path !== '' && !isset($seen[$path])) {
                $seen[$path] = true;
                $images[] = self::IMG_HOST . $path;
            }
        }
        return $images;
    }

    // Pull the fields the rest of the pipeline expects out of the detail
    // payload. Encar's detail JSON groups data into sections (category,
    // spec, advertisement, photos), so we cherry-pick from each.
    private function mapDetailPayload(string $sourceId, array $d): array
    {
        $category = $d['category'] ?? [];
        $spec = $d['spec'] ?? [];
        $advertisement = $d['advertisement'] ?? [];
        $images = $this->buildDetailImages($d['photos'] ?? []);

        $title = trim(
            ($category['manufacturerEnglishName'] ?? $category['manufacturerName'] ?? '') . ' ' .
            ($category['modelGroupEnglishName'] ?? $category['modelEnglishName'] ?? $category['modelName'] ?? '') . ' ' .
            ($category['gradeEnglishName'] ?? $category['gradeName'] ?? '')
        );

        $fuelMap = [
            '가솔린'           => 'benzina',
            '디젤'             => 'diesel',
            'LPG(일반인 구입)' => 'lpg',
            '가솔린+전기'      => 'hybrid',
            '디젤+전기'        => 'diesel_hybrid',
            '가솔린+LPG'       => 'gasoline_lpg',
            '가솔린+CNG'       => 'gasoline_cng',
            '전기'             => 'electric',
            '기타'             => 'other',
        ];
        $gearMap = [
            '오토' => 'automat', '자동' => 'automat',
            '수동' => 'manual', '세미오토' => 'semi-auto',
        ];
        $rawFuel = $spec['fuelName'] ?? null;
        $rawGear = $spec['transmissionName'] ?? null;
        $fuelType = ($rawFuel && isset($fuelMap[$rawFuel])) ? $fuelMap[$rawFuel] : $rawFuel;
        $gearbox  = ($rawGear && isset($gearMap[$rawGear])) ? $gearMap[$rawGear] : $rawGear;

        // English names live in category.* — prefer them, fall back to taxonomy translate.
        // modelGroupEnglishName (e.g. "X5") beats modelEnglishName (which sometimes carries the generation).
        $engBrand = $category['manufacturerEnglishName'] ?? null;
        $engModel = $category['modelGroupEnglishName']
                 ?? $category['modelEnglishName']
                 ?? null;
        if (!$engBrand || !$engModel) {
            [$txBrand, $txModel] = $this->translateBrandModel(
                $category['manufacturerName'] ?? null,
                $category['modelGroupName'] ?? $category['modelName'] ?? null
            );
            $engBrand = $engBrand ?: $txBrand;
            $engModel = $engModel ?: $txModel;
        }

        $detailVin = strtoupper(trim((string)($d['vin'] ?? '')));
        $detailVin = preg_replace('/[^A-HJ-NPR-Z0-9]/', '', $detailVin);
        if (strlen($detailVin) !== 17) $detailVin = null;

        return [
            'source_id' => $sourceId,
            'source_url' => self::DETAIL_WEB_URL . '?carid=' . $sourceId,
            'vin' => $detailVin,
            'brand' => $this->cleanBrandName($engBrand),
            'model' => $this->cleanModelName($engModel),
            // Build year (yearMonth) first, to match the listing display + filter.
            'year' => !empty($category['yearMonth'])
                ? (int)substr((string)$category['yearMonth'], 0, 4)
                : (!empty($category['formYear']) ? (int)$category['formYear'] : null),
            'km' => isset($spec['mileage']) ? (int)$spec['mileage'] : null,
            'fuel_type' => $fuelType,
            'gearbox' => $gearbox,
            'engine_volume' => isset($spec['displacement']) ? (int)$spec['displacement'] : null,
            'power_hp' => isset($spec['horsePower']) ? (int)$spec['horsePower']
                       : (isset($spec['maxPower']) ? (int)$spec['maxPower']
                       : (isset($spec['enginePower']) ? (int)$spec['enginePower'] : null)),
            'seats' => isset($spec['seatCount']) ? (int)$spec['seatCount']
                     : (isset($spec['seats']) ? (int)$spec['seats']
                     : (isset($spec['passengerCapacity']) ? (int)$spec['passengerCapacity'] : null)),
            // Encar's public API doesn't expose drive type — infer from grade name
            // (BMW xDrive, Audi quattro, Mercedes 4MATIC, etc.).
            'drive_type' => $this->inferDriveType(
                ($category['gradeName'] ?? '') . ' ' . ($category['gradeEnglishName'] ?? '')
            ),
            'color' => $this->normalizeColor($spec['colorName'] ?? null),
            'body_type' => $this->normalizeBodyType($spec['bodyName'] ?? null),
            // Encar quotes prices in "manwon" (1 unit = 10,000 KRW). Multiply
            // here so downstream code only ever sees plain KRW values.
            'price_source' => isset($advertisement['price']) ? (float)$advertisement['price'] * 10000 : null,
            'price_source_currency' => 'KRW',
            'title' => $title,
            'description' => $advertisement['description'] ?? '',
            'features' => $d['options'] ?? [],
            'images' => $images,
            'report' => $d['inspection'] ?? null,
        ];
    }

    private function extractIdFromUrl(string $url): ?string
    {
        if (preg_match('/carid=(\d+)/i', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/\/(\d{6,})(?:[?\/]|$)/', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
