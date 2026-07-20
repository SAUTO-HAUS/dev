<?php

namespace App\Services\Parsing\Adapters;

use App\Services\Parsing\AbstractAdapter;

// Auto1.com merchant platform (Europe's largest B2B wholesale used-car auction).
// We import the "Instant Purchase" (channel "ip") cars — the fixed buy-now
// listings, the closest match to sauto's on-order buy-now flow (like eCarsTrade
// buy-now / OpenLane BuyNow).
//
// Access mode (all discovered live from the logged-in merchant SPA):
//  - Auth is a short-lived JWT Bearer (1h) injected into the merchant page HTML
//    as <app data-jwt="...">. It is NOT fetched from a token endpoint, so we
//    REFRESH it automatically: GET the merchant cars page with the session
//    cookie, then scrape data-jwt. The cookie (MPSESSID) has a sliding 1h expiry
//    and is the only thing we keep in .env — renewed rarely, like OpenLane.
//  - List:    POST /v1/car-search/cars/search/{userUuid}  body {filters,useAggregations}
//             → {totalHits, hits:[...]} — each hit already carries everything a
//             listing card needs (make/model/year/km/price/fuel/gearbox/hp/photos).
//  - Detail:  GET /en/app/merchant/car/{stockNumber} with X-Requested-With header
//             → JSON (response.details/gallery/equipmentItems/quality) — full
//             photo set + equipment + damage report.
//  - Filters: GET /v1/car-search/filters/{userUuid} → the taxonomy (123 makes
//             with models, fuel/gear/body types, channels) used for the UI.
//
// .env:
//   AUTO1_COOKIE  - full cookie request-header string copied from DevTools
//                   (must contain MPSESSID + the login cookies)
// When the cookie dies the page stops carrying data-jwt / the API answers 401;
// we surface a clear "renew cookie" message instead of a fatal error.
//
// Prices come back in MINOR UNITS (cents): mpPrice 344300 = 3443 EUR. VIN is not
// exposed by Auto1's API (like OpenLane anonymous), so cars import without a VIN.
class Auto1Adapter extends AbstractAdapter
{
    public int $lastOffset = 0;       // next page offset to resume backfill from
    public int $lastTotalCount = 0;   // total hits Auto1 reports for the query

    private const SOURCE_CODE = 'auto1';
    private const SOURCE_NAME = 'AUTO1.com';
    private const BASE_URL    = 'https://www.auto1.com';
    // Page that carries a fresh JWT in <app data-jwt="..."> and the user/merchant uuids.
    private const AUTH_PAGE   = 'https://www.auto1.com/en/app/merchant/cars';
    private const API_VERSION = 'v1';
    // Instant Purchase channel id (from the app's CHANNEL_INSTANT_PURCHASE=4).
    private const CHANNEL_IP  = 'ip';
    // Numeric channel on the detail payload: 4 = Instant Purchase (ours),
    // 2 = Customer Auction (DCB), 3 = 24h auction. A car can MOVE between these
    // after we imported it, so availability re-checks it (see checkAvailability).
    private const CHANNEL_ID_IP = 4;
    private const IMG_HOST    = 'img-pa.auto1.com';
    // Auto1 serves this when the seller uploaded no photos (typical for the
    // self-inspected Customer Auction cars). It must never reach a listing.
    private const PLACEHOLDER_IMG = 'placeholder-car-image';
    // Auto1 never returns more than 50 hits per page, whatever pageSize we ask.
    private const MAX_PAGE_SIZE = 50;

    // Cached auth resolved from the merchant page: [jwt, userUuid, merchantUuid].
    private ?array $auth = null;

    // One manual search pulls a page the user is waiting on, so keep the jitter
    // sub-second (the parent's whole-second pause felt slow), same as OpenLane.
    protected function randomDelay(): void
    {
        usleep(mt_rand(300000, 700000));
    }

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
        return stripos($url, 'auto1.com') !== false;
    }

    // Read the browser-copied session cookie from .env (AUTO1_COOKIE).
    private function loadCookie(): string
    {
        static $cookie = null;
        if ($cookie !== null) return $cookie;
        $cookie = '';
        $root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 4);
        $envFile = rtrim((string)$root, '/\\') . '/.env';
        if (is_file($envFile)) {
            $env = file_get_contents($envFile);
            if (preg_match('/AUTO1_COOKIE=(.+)/', $env, $m)) $cookie = trim($m[1]);
        }
        return $cookie;
    }

    // Fetch a FRESH Bearer JWT (+ the user/merchant uuids) by loading the merchant
    // page with the session cookie and scraping <app data-jwt=... data-user-uuid=...
    // data-merchant-uuid=...>. Cached per request. Returns null when the cookie is
    // missing/expired (page no longer carries a data-jwt).
    private function loadAuth(): ?array
    {
        if ($this->auth !== null) return $this->auth ?: null;

        $cookie = $this->loadCookie();
        if ($cookie === '') {
            $this->logError('Auto1: no AUTO1_COOKIE in .env — cannot authenticate');
            $this->auth = [];
            return null;
        }

        $response = $this->httpRequest(self::AUTH_PAGE, [
            'headers' => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,ru;q=0.8,ro;q=0.7',
                'Cookie: ' . $cookie,
            ],
        ]);
        $html = (string)($response['body'] ?? '');
        if (($response['status'] ?? 0) !== 200 || $html === '') {
            $this->logError('Auto1 auth page HTTP failure — renew AUTO1_COOKIE in .env', ['status' => $response['status'] ?? 0]);
            $this->auth = [];
            return null;
        }

        // data-jwt is unquoted in the markup: <app\n data-jwt=eyJ... data-merchant-uuid="...">
        if (!preg_match('/data-jwt=([A-Za-z0-9._-]+)/', $html, $m)) {
            $this->logError('Auto1 auth page has no data-jwt — cookie expired, renew AUTO1_COOKIE in .env');
            $this->auth = [];
            return null;
        }
        $jwt = $m[1];
        $userUuid = '';
        $merchantUuid = '';
        if (preg_match('/data-user-uuid="([^"]+)"/', $html, $mm))     $userUuid = $mm[1];
        if (preg_match('/data-merchant-uuid="([^"]+)"/', $html, $mm)) $merchantUuid = $mm[1];
        // Fallback: pull the user uuid straight from the JWT payload if the tag was absent.
        if ($userUuid === '') {
            $userUuid = $this->userUuidFromJwt($jwt) ?? '';
        }

        $this->auth = [$jwt, $userUuid, $merchantUuid];
        return $this->auth;
    }

    // Decode the JWT payload (no verification — we only need user_uuid) as a fallback.
    private function userUuidFromJwt(string $jwt): ?string
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) return null;
        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        if ($payload === false) return null;
        $data = json_decode($payload, true);
        return is_array($data) ? ($data['user_uuid'] ?? null) : null;
    }

    // Authenticated JSON-API headers (Bearer + cookie + XHR marker).
    private function apiHeaders(): array
    {
        return [
            'Accept: application/json',
            'Accept-Language: en-US,en;q=0.9,ru;q=0.8,ro;q=0.7',
            'Content-Type: application/json',
            'Origin: ' . self::BASE_URL,
            'Referer: ' . self::AUTH_PAGE . '?channel=ip',
            'X-Requested-With: XMLHttpRequest',
            'Authorization: Bearer ' . ($this->auth[0] ?? ''),
            'Cookie: ' . $this->loadCookie(),
        ];
    }

    public function searchByFilter(array $criteria): array
    {
        $auth = $this->loadAuth();
        if (!$auth) return [];
        [$jwt, $userUuid] = $auth;
        if ($userUuid === '') {
            $this->logError('Auto1 search: no user uuid resolved');
            return [];
        }

        // Auto1 HARD-CAPS a page at 50 hits: asking for 100/200 still returns 50
        // (verified live). Requesting more than the cap silently truncates, which
        // made the "short page = last page" check below fire on the very FIRST
        // page — a BMW search imported 50 cars out of 3525. Always page by 50.
        // Paging itself is clean: pages 1/2/3 return disjoint sets.
        $pageSize   = self::MAX_PAGE_SIZE;
        $maxResults = (int)($criteria['_max_results'] ?? 500);
        $maxPages   = (int)($criteria['_max_pages']   ?? 40);
        // _offset_start is a car offset (like the other adapters). Convert to a
        // 1-based page number for Auto1's paging.
        $offsetStart = (int)($criteria['_offset_start'] ?? 0);
        $page = $offsetStart > 0 ? (int)floor($offsetStart / $pageSize) + 1 : 1;

        $priceMin = !empty($criteria['price_min']) ? (float)$criteria['price_min'] : null;
        $priceMax = !empty($criteria['price_max']) ? (float)$criteria['price_max'] : null;

        $url = self::BASE_URL . '/' . self::API_VERSION . '/car-search/cars/search/' . rawurlencode($userUuid);
        $seen = [];
        $results = [];
        $pagesFetched = 0;

        while (count($results) < $maxResults && $pagesFetched < $maxPages) {
            $payload = $this->buildSearchPayload($criteria, $page, $pageSize);
            $response = $this->httpRequest($url, [
                'post'    => $payload,
                'headers' => $this->apiHeaders(),
            ]);

            $status = (int)($response['status'] ?? 0);
            if ($status === 401 || $status === 403) {
                $this->logError('Auto1 search ' . $status . ' — token/cookie expired, renew AUTO1_COOKIE in .env', ['page' => $page]);
                break;
            }
            if ($status !== 200 || empty($response['body'])) {
                $this->logError('Auto1 search HTTP failure', ['status' => $status, 'page' => $page, 'error' => $response['error'] ?? '']);
                break;
            }

            $data = json_decode($response['body'], true);
            if (!is_array($data) || !isset($data['hits'])) {
                break;
            }
            if (isset($data['totalHits'])) {
                $this->lastTotalCount = (int)$data['totalHits'];
            }
            $hits = $data['hits'];
            if (!$hits) break;

            $pageCount = 0;
            foreach ($hits as $item) {
                $pageCount++;
                $id = (string)($item['stockNumber'] ?? $item['id'] ?? '');
                if ($id === '' || isset($seen[$id])) continue;
                $seen[$id] = true;

                // Only Instant Purchase — never a Customer Auction (DCB) or a 24h
                // lot. The channel in the payload already scopes the search to IP,
                // so this is a cheap belt-and-braces check on the car itself.
                $aType = strtoupper((string)($item['auctionType'] ?? ''));
                if ($aType !== '' && $aType !== 'INSTANTPURCHASE') continue;
                // No real photos (Auto1 serves a placeholder instead) → skip: a card
                // without a picture is worthless and it signals a self-inspected lot.
                if (isset($item['hasPhotos']) && !$item['hasPhotos']) continue;

                $mapped = $this->mapHit($item);
                if (empty($mapped['images'])) continue; // placeholder-only gallery
                $p = $mapped['price_source'] ?? null;
                // Import only cars that have a real buy-now price.
                if ($p === null) continue;
                // Enforce the requested price range on our side (Auto1's filter is
                // applied server-side but we keep this as a guard, like OpenLane).
                if (($priceMin !== null && $p < $priceMin) || ($priceMax !== null && $p > $priceMax)) {
                    continue;
                }
                $results[] = $this->normalizeCarData($mapped);
                if (count($results) >= $maxResults) break 2;
            }

            $pagesFetched++;
            $this->lastOffset = $page * $pageSize;
            // Stop when we have walked the whole result set Auto1 reports, or the
            // page came back short (real last page — pageSize is the API's own cap,
            // so a short page now genuinely means the end).
            if ($this->lastTotalCount > 0 && $page * $pageSize >= $this->lastTotalCount) break;
            if ($pageCount < $pageSize) break;
            $page++;
            $this->randomDelay();
        }

        return $results;
    }

    // Fetch the search filters/taxonomy (makes+models, fuel/gear/body types,
    // channels, ranges). Returns the raw "filters" block, or null on failure.
    // Cached by the caller — this hits Auto1, don't call it per keystroke.
    public function fetchFilters(): ?array
    {
        $auth = $this->loadAuth();
        if (!$auth) return null;
        [$jwt, $userUuid] = $auth;
        if ($userUuid === '') return null;

        $url = self::BASE_URL . '/' . self::API_VERSION . '/car-search/filters/' . rawurlencode($userUuid);
        $response = $this->httpRequest($url, ['headers' => $this->apiHeaders()]);
        $status = (int)($response['status'] ?? 0);
        if ($status !== 200 || empty($response['body'])) {
            $this->logError('Auto1 fetchFilters HTTP failure', ['status' => $status]);
            return null;
        }
        $data = json_decode($response['body'], true);
        if (!is_array($data) || empty($data['filters'])) return null;
        return $data;
    }

    // Fetch the make → model → engine tree with live counts.
    //
    // The /filters taxonomy carries makes and models but every mainType comes
    // back with an EMPTY subTypes[] — Auto1's own UI lazy-loads engines from the
    // search response instead. So the only source for engines is the
    // "compositeCarKey" aggregation, shaped:
    //   { "285": { c: 1972, i: { "Focus": { c: 433, i: { "1.5 TDCi": 26, ... } } } } }
    // where c = count and i = children. It covers the WHOLE result set (its
    // counts sum to totalHits), not just the page, so one pageSize=1 call is
    // enough. Counts honour the import policy filters, so a model/engine listed
    // here always has at least one importable car.
    // Returns null on failure — callers keep the previous taxonomy.
    public function fetchCompositeTree(): ?array
    {
        $auth = $this->loadAuth();
        if (!$auth) return null;
        [$jwt, $userUuid] = $auth;
        if ($userUuid === '') return null;

        $url = self::BASE_URL . '/' . self::API_VERSION . '/car-search/cars/search/' . rawurlencode($userUuid);
        $response = $this->httpRequest($url, [
            'post'    => $this->buildSearchPayload([], 1, 1),
            'headers' => $this->apiHeaders(),
        ]);
        $status = (int)($response['status'] ?? 0);
        if ($status !== 200 || empty($response['body'])) {
            $this->logError('Auto1 fetchCompositeTree HTTP failure', ['status' => $status]);
            return null;
        }
        $data = json_decode($response['body'], true);
        $tree = $data['aggregations']['compositeCarKey'] ?? null;
        return is_array($tree) && $tree ? $tree : null;
    }

    // Build the JSON body the cars/search endpoint expects. Mirrors the app's
    // shape: {filters:{page,pageSize,sort{channel:"ip",...},carFilters,ranges},
    // useAggregations}. Verified live (totalHits > 0).
    private function buildSearchPayload(array $criteria, int $page, int $pageSize): string
    {
        // carFilters: [{value: <manufacturerCode>, mainTypes:[{value:<model>, subTypes:[]}]}].
        // Brand comes as Auto1's numeric manufacturer code (from the taxonomy);
        // model is the mainType label.
        $carFilters = [];
        $brand  = trim((string)($criteria['brand'] ?? ''));
        $model  = trim((string)($criteria['model'] ?? ''));
        // Engines are multi-select (OR'd by Auto1: 1.5 TDCi + 1.0 EcoBoost returns
        // the sum of both). Never split a string on "," — two real engine values
        // contain one ("electric drive 12,6 kW").
        $engines = $criteria['engine'] ?? null;
        if (!is_array($engines)) $engines = ($engines !== null && trim((string)$engines) !== '') ? [(string)$engines] : [];
        $engines = array_values(array_filter(array_map(fn($e) => trim((string)$e), $engines), fn($e) => $e !== ''));
        if ($brand !== '') {
            $entry = ['value' => $brand];
            if ($model !== '') {
                // subTypes = engine variants ("1.5 TDCi"). They are plain strings:
                // wrapping them as {value:...} like mainTypes makes the API reject
                // the whole query (no totalHits). Engines only narrow a chosen
                // model — they are nested under mainTypes, so it needs one.
                $entry['mainTypes'] = [['value' => $model, 'subTypes' => $engines]];
            }
            $carFilters[] = $entry;
        }

        $filters = [
            'page'              => $page,
            'pageSize'          => $pageSize,
            'supportedFeatures' => ['dealer_a'],
            'sort'              => [
                'channel'   => self::CHANNEL_IP,
                'sorting'   => $criteria['_sort'] ?? 'relevanceSorting',
                'direction' => $criteria['_dir'] ?? 'asc',
            ],
            'carFilters' => $carFilters,
            'powerRange' => ['unit' => 'hp'],
            // Import policy, applied server-side so photo-less / faulty lots never
            // eat a page slot. Both are *values* of their list — a top-level
            // "hasPhotos" key is silently ignored by the API.
            'carCondition'      => ['hasPhotos'],
            // "none" = no fault found on the test drive. Auto1 splits every car
            // into none / some-finding, so a car that was never test-driven still
            // lands in "none" and is kept.
            'testDriveFindings' => ['none'],
            // Accident-free only. The list also offers "N/A", but that bucket is
            // empty — every car is classified true/false — so nothing is lost.
            'accidents'         => ['false'],
        ];

        // Year (firstRegistrationRange {from,to} as strings).
        if (!empty($criteria['year_from']) || !empty($criteria['year_to'])) {
            $filters['firstRegistrationRange'] = array_filter([
                'from' => !empty($criteria['year_from']) ? (string)(int)$criteria['year_from'] : null,
                'to'   => !empty($criteria['year_to'])   ? (string)(int)$criteria['year_to']   : null,
            ], fn($v) => $v !== null);
        }
        // Mileage (mileageRange {from,to}).
        if (!empty($criteria['km_min']) || !empty($criteria['km_max'])) {
            $filters['mileageRange'] = array_filter([
                'from' => !empty($criteria['km_min']) ? (int)$criteria['km_min'] : null,
                'to'   => !empty($criteria['km_max']) ? (int)$criteria['km_max'] : null,
            ], fn($v) => $v !== null);
        }
        // Price (priceRange {from,to}) — in EUR (Auto1 filters accept whole euros).
        if (!empty($criteria['price_min']) || !empty($criteria['price_max'])) {
            $filters['priceRange'] = array_filter([
                'from' => !empty($criteria['price_min']) ? (int)$criteria['price_min'] : null,
                'to'   => !empty($criteria['price_max']) ? (int)$criteria['price_max'] : null,
            ], fn($v) => $v !== null);
        }
        // Fuel → fuelTypes (petrol/diesel/gas/hybrid/electro/other).
        $fuel = $this->fuelFilterValue($criteria['fuel_type'] ?? null);
        if ($fuel) $filters['fuelTypes'] = [$fuel];
        // Gearbox → gearTypes (manual/automatic).
        $gear = $this->gearFilterValue($criteria['gearbox'] ?? null);
        if ($gear) $filters['gearTypes'] = [$gear];
        // Body → bodyTypes (cabrio/coupe/combi/limo/suv/...).
        $body = $this->bodyFilterValue($criteria['body_type'] ?? null);
        if ($body) $filters['bodyTypes'] = [$body];
        // Import country → branchCountries: where the car physically IS (the branch
        // you collect it from). NOT countryOfOrigin, which is where it was first
        // registered — a DE-origin car can sit in NL, and it's the location that
        // decides the import country we publish with (see resolveImportCountryId).
        // CSV so several countries can be picked at once.
        $countries = [];
        foreach (explode(',', (string)($criteria['country_origin'] ?? '')) as $cc) {
            $cc = trim($cc);
            if ($cc !== '') $countries[] = strtoupper($cc);
        }
        if ($countries) $filters['branchCountries'] = array_values(array_unique($countries));

        return json_encode(['filters' => $filters, 'useAggregations' => true], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    // Map one hit from the cars/search response to our normalized shape. The hit
    // already carries everything a card needs, so no per-car detail call is
    // required at import time.
    private function mapHit(array $item): array
    {
        $stock = (string)($item['stockNumber'] ?? '');
        $brand = $this->cleanText($item['manufacturerName'] ?? '');
        // Model: prefer the concrete mainType, else the human modelDescription.
        $model = $this->cleanText($item['mainType'] ?? '');
        if ($model === '') {
            $model = $this->cleanText($item['modelDescription'] ?? '');
        }
        $title = trim($brand . ' ' . (string)($item['modelDescription'] ?? $model));

        // firstRegistrationDate is a millisecond timestamp.
        $year = $this->yearFromMs($item['firstRegistrationDate'] ?? null);

        // Price: mpPrice / searchPrice / minimumBid — all the same buy-now value,
        // in MINOR UNITS (cents). Convert to whole EUR.
        $price = $this->priceFromMinor($item['mpPrice'] ?? $item['searchPrice'] ?? $item['minimumBid'] ?? $item['auctionStartPrice'] ?? null);

        // Drive type: explicit flag + engine/name hints (xDrive/quattro/4MATIC).
        if (!empty($item['allWheelDrive'])) {
            $drive = '4x4';
        } else {
            $drive = $this->inferDriveType((string)($item['modelDescription'] ?? '') . ' ' . $model);
        }

        // Photos already in the hit: images[].fullUrl (full-size, img-pa CDN).
        // Skip Auto1's "no photos" placeholder so a card never shows it.
        $images = [];
        foreach (($item['images'] ?? []) as $img) {
            $u = $img['fullUrl'] ?? $img['url'] ?? null;
            if ($u && !$this->isPlaceholderImage((string)$u)) $images[] = $this->absImage($u);
        }

        $country = strtoupper((string)($item['sourceCountry'] ?? $item['countryCode'] ?? $item['owningCountry'] ?? ''));

        return [
            'source_id'  => $stock,
            'source_url' => self::BASE_URL . '/en/app/merchant/car/' . rawurlencode($stock),
            'vin'        => null, // Auto1 does not expose VIN in its API
            'brand'      => $brand,
            'model'      => $model,
            'year'       => $year,
            'km'         => isset($item['km']) ? (int)$item['km'] : null,
            'fuel_type'  => $this->fuelFromTitle((string)($item['modelDescription'] ?? '')) ?? $this->normalizeFuel($item['fuelType'] ?? null),
            'gearbox'    => $this->normalizeGearbox($item['gearType'] ?? null),
            'power_hp'   => isset($item['hp']) ? (int)$item['hp'] : null,
            // The listing hit carries doors, not seats; seats come from the detail
            // payload during enrichment. Leave null here.
            'seats'      => null,
            'drive_type' => $drive,
            'color'      => $this->normalizeColor($item['colour'] ?? null),
            'body_type'  => $this->normalizeBodyType($item['bodyType'] ?? null),
            'price_source' => $price,
            'price_source_currency' => 'EUR',
            'title'  => $title,
            'images' => $images,
            'raw_data' => $item,
        ];
    }

    public function fetchByUrl(string $url): ?array
    {
        // Detail URL carries the stock number: /en/app/merchant/car/AA00051
        if (preg_match('#/car/([A-Z0-9]+)#i', $url, $m)) {
            return $this->fetchById($m[1]);
        }
        $this->logError('Auto1: cannot extract stock number from URL', ['url' => $url]);
        return null;
    }

    // Fetch + map the full detail for one stock number. This is where the full
    // photo gallery and the equipment list come from.
    public function fetchById(string $sourceId): ?array
    {
        $d = $this->fetchDetailRaw($sourceId);
        if (!is_array($d)) return null;
        return $this->normalizeCarData($this->mapDetail($sourceId, $d));
    }

    // Fetch the RAW detail JSON for one stock number. GET /en/app/merchant/car/{stock}
    // with the XHR header returns JSON (without it you get the SPA HTML shell).
    // Carries response.{details,gallery,equipmentItems,datItems,quality} — the
    // gallery + equipment + damage report the normalized shape trims.
    public function fetchDetailRaw(string $stock): ?array
    {
        $stock = trim($stock);
        if ($stock === '') return null;
        $auth = $this->loadAuth();
        if (!$auth) return null;

        $url = self::BASE_URL . '/en/app/merchant/car/' . rawurlencode($stock);
        $response = $this->httpRequest($url, [
            'headers' => [
                'Accept: */*',
                'Accept-Language: en-US,en;q=0.9,ru;q=0.8,ro;q=0.7',
                'Referer: ' . $url,
                'X-Requested-With: XMLHttpRequest',
                'Authorization: Bearer ' . ($this->auth[0] ?? ''),
                'Cookie: ' . $this->loadCookie(),
            ],
        ]);
        $status = (int)($response['status'] ?? 0);
        if ($status === 401 || $status === 403) {
            $this->logError('Auto1 detail ' . $status . ' — renew AUTO1_COOKIE in .env', ['stock' => $stock]);
            return null;
        }
        if ($status === 404) return null; // car gone
        if ($status !== 200 || empty($response['body'])) {
            $this->logError('Auto1 detail HTTP failure', ['stock' => $stock, 'status' => $status]);
            return null;
        }
        $d = json_decode($response['body'], true);
        if (!is_array($d) || empty($d['response'])) {
            $this->logError('Auto1 detail non-JSON / empty', ['stock' => $stock]);
            return null;
        }
        return $d['response'];
    }

    // Everything the vehicle report needs, gathered from the TWO endpoints that
    // each hold half of it:
    //   car-details-view → meta.damages (the structured, translation-keyed damage
    //                      list) + meta.accidents
    //   merchant/car     → response.paint (hood + both front doors; the site's
    //                      widget shows more, from an endpoint we don't have) and
    //                      the equipment groups
    // Returns ['damages'=>[], 'accidents'=>[], 'paint'=>[], 'equipment'=>[]], or
    // null when neither half could be fetched. Renders via parsing_auto1_report_html().
    public function fetchReportRaw(string $stock): ?array
    {
        $stock = trim($stock);
        if ($stock === '') return null;
        $auth = $this->loadAuth();
        if (!$auth) return null;
        [$jwt, $userUuid] = $auth;

        $out = ['damages' => [], 'accidents' => [], 'paint' => [], 'equipment' => []];
        $got = false;

        // 1) Damages + accident flag.
        if ($userUuid !== '') {
            $url = self::BASE_URL . '/' . self::API_VERSION . '/car-details-view/'
                . rawurlencode($stock) . '/' . rawurlencode($userUuid) . '?useNewFeesApi=1';
            $r = $this->httpRequest($url, ['headers' => $this->apiHeaders()]);
            $status = (int)($r['status'] ?? 0);
            if ($status === 200 && !empty($r['body'])) {
                $cdv = json_decode($r['body'], true);
                if (is_array($cdv)) {
                    $out['damages']   = $cdv['meta']['damages']   ?? [];
                    $out['accidents'] = $cdv['meta']['accidents'] ?? [];
                    $got = true;
                }
            } elseif ($status === 401 || $status === 403) {
                $this->logError('Auto1 report ' . $status . ' — renew AUTO1_COOKIE in .env', ['stock' => $stock]);
            }
        }

        // 2) Paint thickness + equipment (best-effort: a report without them is
        //    still worth showing).
        $detail = $this->fetchDetailRaw($stock);
        if (is_array($detail)) {
            $out['paint'] = $detail['paint'] ?? [];
            $out['equipment'] = array_merge(
                $detail['equipmentItems'] ?? [],
                $detail['datItems'] ?? []
            );
            $got = true;
        }

        return $got ? $out : null;
    }

    // Map the car detail payload (response.*) to our normalized shape. Richer than
    // the listing hit: the complete photo gallery and the equipment list.
    private function mapDetail(string $stock, array $r): array
    {
        $det = $r['details'] ?? [];

        $brand = $this->cleanText($det['manufacturer'] ?? '');
        // modelDescription is the full trim string ("X5 xDrive 45e xLine"); keep the
        // first token(s) as a model, mirroring how the listing mainType reads.
        $modelDesc = $this->cleanText($det['modelDescription'] ?? '');
        $model = $modelDesc !== '' ? (preg_split('/\s+/', $modelDesc)[0] ?? $modelDesc) : '';

        // firstRegistrationDate here is "MM/YYYY"; builtYear is a plain year.
        $year = null;
        if (!empty($det['firstRegistrationDate']) && preg_match('#(\d{4})#', (string)$det['firstRegistrationDate'], $ym)) {
            $year = (int)$ym[1];
        } elseif (!empty($det['builtYear'])) {
            $year = (int)$det['builtYear'];
        }

        // Price: bidding/purchase.price is in WHOLE EUR here (not minor units).
        $price = null;
        foreach ([$r['purchase']['price'] ?? null, $r['bidding']['price'] ?? null, $det['buyNowPrice'] ?? null] as $p) {
            if ($p !== null && (float)$p > 0) { $price = (float)$p; break; }
        }

        // All photos: gallery.galleryImages[].url (full-size), else the main image.
        // The placeholder is filtered out — this matters most HERE: enrichment feeds
        // this gallery back into images_local, so a car that moved to a Customer
        // Auction (photos replaced by the placeholder) would otherwise overwrite the
        // real photos we imported. Returning no images leaves the good ones in place.
        $images = [];
        $gallery = $r['gallery'] ?? [];
        foreach (($gallery['galleryImages'] ?? []) as $img) {
            $u = $img['url'] ?? null;
            if ($u && !$this->isPlaceholderImage((string)$u)) $images[] = $this->absImage($u);
        }
        if (!$images && !empty($gallery['mainImage']['url'])
            && !$this->isPlaceholderImage((string)$gallery['mainImage']['url'])) {
            $images[] = $this->absImage($gallery['mainImage']['url']);
        }

        // Equipment: equipmentItems[].items[].description (English). datItems adds
        // the extended DAT list; we merge both, de-duplicated.
        $features = [];
        foreach (['equipmentItems', 'datItems'] as $key) {
            foreach (($r[$key] ?? []) as $grp) {
                foreach (($grp['items'] ?? []) as $it) {
                    $name = trim((string)($it['description'] ?? ''));
                    if ($name !== '') $features[$name] = true;
                }
            }
        }
        $features = array_keys($features);

        // Drive type: all-wheel-drive equipment flag + name hints.
        $awd = false;
        foreach (($r['equipmentItems'] ?? []) as $grp) {
            if (stripos((string)($grp['group'] ?? ''), 'all wheel') !== false) { $awd = true; break; }
        }
        $drive = $awd ? '4x4' : $this->inferDriveType($modelDesc);

        $country = strtoupper((string)($det['sourceCountryCode'] ?? $det['countryOfRegistration'] ?? ''));

        return [
            'source_id'  => $stock,
            'source_url' => self::BASE_URL . '/en/app/merchant/car/' . rawurlencode($stock),
            'vin'        => null, // Auto1 does not expose VIN
            'brand'      => $brand,
            'model'      => $model,
            'year'       => $year,
            'km'         => $this->intFromLoose($det['km'] ?? null),
            'fuel_type'  => $this->fuelFromTitle($modelDesc) ?? $this->normalizeFuel($det['fuelType'] ?? null),
            'gearbox'    => $this->normalizeGearbox($det['gearType'] ?? null),
            'engine_volume' => $this->intFromLoose($det['ccm'] ?? null),
            'power_hp'   => isset($det['horsepower']) ? (int)$det['horsepower'] : null,
            'seats'      => isset($det['seats']) ? (int)$det['seats'] : null,
            'drive_type' => $drive,
            'color'      => $this->normalizeColor($det['outsideColour'] ?? null),
            'body_type'  => $this->normalizeBodyType($det['bodyType'] ?? null),
            'price_source' => $price,
            'price_source_currency' => 'EUR',
            'title'  => trim($brand . ' ' . $modelDesc),
            'features' => $features,
            'images' => $images,
            'raw_data' => $r,
        ];
    }

    // Is the car still buyable on Auto1? Sold/withdrawn cars do NOT 404 — Auto1
    // answers 403 with {"success":false,"errors":{"__form__":["Sorry, but this car
    // is not available anymore"]}}. Reading 403 as "auth problem" (as a cookie
    // check would) meant a sold car stayed live on sauto forever, so the body is
    // what decides: an expired session answers 403 WITHOUT that marker.
    public function checkAvailability(string $sourceId): bool
    {
        $auth = $this->loadAuth();
        if (!$auth) return true; // can't confirm → keep visible

        $url = self::BASE_URL . '/en/app/merchant/car/' . rawurlencode(trim($sourceId));
        $response = $this->httpRequest($url, [
            'headers' => [
                'Accept: */*',
                'Referer: ' . $url,
                'X-Requested-With: XMLHttpRequest',
                'Authorization: Bearer ' . ($this->auth[0] ?? ''),
                'Cookie: ' . $this->loadCookie(),
            ],
        ]);
        $status = (int)($response['status'] ?? 0);
        $body   = (string)($response['body'] ?? '');

        if ($status === 404) return false;

        // "not available anymore" → gone, whatever status carries it.
        if ($body !== '' && stripos($body, 'not available anymore') !== false) return false;
        $d = json_decode($body, true);
        if (is_array($d) && ($d['success'] ?? null) === false) {
            $errs = json_encode($d['errors'] ?? [], JSON_UNESCAPED_UNICODE);
            if (stripos((string)$errs, 'not available') !== false
                || stripos((string)$errs, 'not found') !== false) return false;
        }

        if ($status !== 200 || $body === '') return true;   // hiccup / auth → keep
        if (!is_array($d) || empty($d['response'])) return true;

        $car = $d['response']['car'] ?? [];
        if (!empty($car['isAlreadySold'])) return false;

        // Still Instant Purchase? Auto1 MOVES cars between channels after we
        // imported them — an IP car can become a Customer Auction (channel 2), and
        // then our frozen buy-now price is meaningless (it is a bidding lot now)
        // and its photos are replaced by the placeholder. We only ever sell Instant
        // Purchase, so anything that left channel 4 counts as gone.
        $ch = $car['channel'] ?? ($d['response']['details']['channel'] ?? null);
        if ($ch !== null && (int)$ch !== self::CHANNEL_ID_IP) {
            $this->logError('Auto1: car left Instant Purchase — marking unavailable', [
                'stock' => $sourceId, 'channel' => (int)$ch,
            ]);
            return false;
        }
        return true;
    }

    // Verify the .env cookie still carries a logged-in session. The real signal is
    // whether the merchant page still hands out a data-jwt AND a search returns
    // 200. Returns ['logged_in'=>bool, 'reason'=>string].
    public function checkSession(): array
    {
        $cookie = $this->loadCookie();
        if ($cookie === '') {
            return ['logged_in' => false, 'reason' => 'no_cookie'];
        }
        $auth = $this->loadAuth();
        if (!$auth || ($auth[1] ?? '') === '') {
            return ['logged_in' => false, 'reason' => 'no_jwt'];
        }
        // Prove the token works against the API with a cheap 1-item search.
        $url = self::BASE_URL . '/' . self::API_VERSION . '/car-search/cars/search/' . rawurlencode($auth[1]);
        $response = $this->httpRequest($url, [
            'post'    => $this->buildSearchPayload([], 1, 1),
            'headers' => $this->apiHeaders(),
        ]);
        $status = (int)($response['status'] ?? 0);
        if ($status === 401 || $status === 403) {
            return ['logged_in' => false, 'reason' => 'http_' . $status];
        }
        if ($status !== 200 || empty($response['body'])) {
            return ['logged_in' => false, 'reason' => 'search_http_' . $status];
        }
        $data = json_decode($response['body'], true);
        if (!is_array($data) || !array_key_exists('hits', $data)) {
            return ['logged_in' => false, 'reason' => 'non_json'];
        }
        return ['logged_in' => true, 'reason' => 'ok'];
    }

    // -------- helpers (value parsing) --------

    // Auto1 numbers arrive as display strings ("200,042", "2,998"). Strip commas.
    private function intFromLoose($val): ?int
    {
        if ($val === null || $val === '') return null;
        $n = preg_replace('/[^\d]/', '', (string)$val);
        return $n === '' ? null : (int)$n;
    }

    private function priceFromMinor($minor): ?float
    {
        if ($minor === null || $minor === '' || !is_numeric($minor)) return null;
        $v = (float)$minor / 100.0;
        return $v > 0 ? $v : null;
    }

    private function yearFromMs($ms): ?int
    {
        if ($ms === null || !is_numeric($ms)) return null;
        $sec = (int)((int)$ms / 1000);
        if ($sec <= 0) return null;
        return (int)date('Y', $sec) ?: null;
    }

    // Make an image URL absolute (Auto1 damage thumbs are protocol-relative //host/...).
    private function absImage(string $u): string
    {
        if (strpos($u, '//') === 0) return 'https:' . $u;
        return $u;
    }

    // Auto1's stand-in for "the seller uploaded no photos"
    // (https://img-pa.auto1.com/placeholder-car-image.png). Common on the
    // self-inspected Customer Auction cars; must never end up on a listing.
    private function isPlaceholderImage(string $u): bool
    {
        return stripos($u, self::PLACEHOLDER_IMG) !== false;
    }

    private function cleanText(?string $s): string
    {
        return trim((string)$s);
    }

    // -------- filter value mapping (internal → Auto1 filter values) --------

    // Internal fuel code (or an Auto1 value passed through) → Auto1 fuelTypes value.
    private function fuelFilterValue(?string $fuel): ?string
    {
        if (!$fuel) return null;
        $f = strtolower(trim($fuel));
        $valid = ['petrol', 'diesel', 'gas', 'hybrid', 'electro', 'other'];
        if (in_array($f, $valid, true)) return $f;
        $map = [
            'benzina' => 'petrol', 'gasoline' => 'petrol',
            'diesel' => 'diesel',
            'hybrid' => 'hybrid', 'hybrid_plugin' => 'hybrid', 'diesel_hybrid' => 'hybrid',
            'electric' => 'electro',
            'lpg' => 'gas', 'gasoline_lpg' => 'gas', 'gasoline_cng' => 'gas',
        ];
        return $map[$f] ?? null;
    }

    private function gearFilterValue(?string $gear): ?string
    {
        if (!$gear) return null;
        $g = strtolower(trim($gear));
        if (in_array($g, ['automat', 'automatic', 'auto', 'semi-auto', 'cvt'], true)) return 'automatic';
        if ($g === 'manual') return 'manual';
        return null;
    }

    // Internal body code (or an Auto1 value) → Auto1 bodyTypes value
    // (cabrio/coupe/combi/limo/suv/van/...). Best-effort.
    private function bodyFilterValue(?string $body): ?string
    {
        if (!$body) return null;
        // Auto1 bodyTypes values (from its filters taxonomy):
        // cabrio, coupe, combi, limo, suv, van, truck, commercial, smallCar.
        $valid = ['cabrio', 'coupe', 'combi', 'limo', 'suv', 'van', 'truck', 'commercial', 'smallcar'];
        $b = strtolower(trim($body));
        if (in_array($b, $valid, true)) {
            return $b === 'smallcar' ? 'smallCar' : $b; // preserve Auto1's camelCase
        }
        $map = [
            'convertible' => 'cabrio',
            'wagon' => 'combi', 'estate' => 'combi',
            'sedan' => 'limo', 'saloon' => 'limo',
            'hatchback' => 'smallCar',
            'minivan' => 'van', 'mpv' => 'van',
            'pickup' => 'truck',
        ];
        return $map[$b] ?? null;
    }

    // -------- normalizers (Auto1 value → sauto value) --------

    // Auto1 fuelType (petrol/diesel/gas/hybrid/electro/other) → internal code.
    // "other" stays null on purpose: fuel drives the customs/excise base, so a
    // guess would produce a wrong MD price — better an empty field the operator
    // fixes than a confidently wrong one.
    private function normalizeFuel(?string $val): ?string
    {
        if (!$val) return null;
        $k = strtolower(trim($val));
        $map = [
            'petrol' => 'benzina', 'gasoline' => 'benzina',
            'diesel' => 'diesel',
            'hybrid' => 'hybrid',
            'electro' => 'electric', 'electric' => 'electric',
            'gas' => 'lpg', 'lpg' => 'lpg',
        ];
        if (isset($map[$k])) return $map[$k];
        if ($k !== 'other') {
            $this->logError('Auto1: unknown fuelType — add it to normalizeFuel', ['value' => $val]);
        }
        return null;
    }

    // Auto1 gearType → internal code. Its vocabulary (sampled over 300 live cars):
    // manual, automatic, duplex, semi-automatic. "duplex" is Auto1's name for a
    // dual-clutch box (Doppelkupplung — DSG/DCT) and is ~15% of the catalog; it
    // drives like an automatic, so it maps to "automat" (sauto atm) rather than
    // Tiptronic/Robot. An unmapped value used to fall through as raw text and got
    // published as an EMPTY gearbox, so unknowns now return null and are logged.
    private function normalizeGearbox(?string $val): ?string
    {
        if (!$val) return null;
        $map = [
            'automatic'      => 'automat',
            'auto'           => 'automat',
            'duplex'         => 'automat',   // dual-clutch (DSG/DCT)
            'manual'         => 'manual',
            'semi-automatic' => 'semi-auto',
            'cvt'            => 'cvt',
        ];
        $k = strtolower(trim($val));
        if (isset($map[$k])) return $map[$k];
        $this->logError('Auto1: unknown gearType — add it to normalizeGearbox', ['value' => $val]);
        return null;
    }

    // Auto1 bodyType → internal body code. Full vocabulary seen live: cabrio,
    // coupe, combi, limo, suv, van, truck, commercial, smallCar, bus. "commercial"
    // is ~13% of the catalog and "bus" also occurs, and both used to fall through
    // as null → published with an EMPTY body type. Unknowns return null + a log
    // line so a new value surfaces instead of silently blanking the field.
    private function normalizeBodyType(?string $val): ?string
    {
        if (!$val) return null;
        $k = strtolower(trim($val));
        $map = [
            'suv'         => 'suv',
            'limo'        => 'sedan',
            'limousine'   => 'sedan',
            'sedan'       => 'sedan',
            'combi'       => 'wagon',
            'kombi'       => 'wagon',
            'estate'      => 'wagon',
            'smallcar'    => 'hatchback',
            'hatchback'   => 'hatchback',
            'coupe'       => 'coupe',
            'cabrio'      => 'convertible',
            'convertible' => 'convertible',
            'van'         => 'van',
            'transporter' => 'van',
            'commercial'  => 'van',      // utility/panel van
            'truck'       => 'van',      // light truck — sauto has no truck body
            'bus'         => 'microbus',
            'minibus'     => 'microbus',
            'pickup'      => 'pickup',
        ];
        if (isset($map[$k])) return $map[$k];
        $this->logError('Auto1: unknown bodyType — add it to normalizeBodyType', ['value' => $val]);
        return null;
    }

    // Auto1 colour (english, lowercase) → sauto colour codes. Unknown → "wht" so
    // auto-publish never fails on a missing colour (same policy as OpenLane).
    private function normalizeColor(?string $val): ?string
    {
        if (!$val) return null;
        $map = [
            'black' => 'blk', 'white' => 'wht', 'silver' => 'slv',
            'grey' => 'gra', 'gray' => 'gra', 'brown' => 'brn',
            'gold' => 'gld', 'blue' => 'blu', 'green' => 'grn',
            'red' => 'red', 'orange' => 'orn', 'yellow' => 'ylw',
            'purple' => 'prp', 'violet' => 'prp', 'pink' => 'pnk',
            'beige' => 'bge',
        ];
        $k = strtolower(trim($val));
        return $map[$k] ?? 'wht';
    }

    // Guess 4x4/rwd from grade/name strings (same approach as Encar/OpenLane).
    private function inferDriveType(?string $text): ?string
    {
        if (!$text) return null;
        $t = strtolower($text);
        if (preg_match('/\b(xdrive|quattro|4matic|4motion|allroad|all-?wheel|awd|4wd|sh-?awd|4x4)\b/i', $t)) return '4x4';
        if (preg_match('/\bsdrive\b/i', $t)) return 'rwd';
        return null;
    }

    // Derive the fuel code from the model description/title — more reliable than
    // the generic list fuelType for hybrids. Ported from OpenLane; catches
    // diesel-hybrid the source mislabels. Returns null when no hint (caller falls
    // back to normalizeFuel).
    private function fuelFromTitle(?string $title): ?string
    {
        if (!$title) return null;
        $t = mb_strtolower($title, 'UTF-8');
        $petrolHint = (strpos($t, 'petrol') !== false || strpos($t, 'gasolin') !== false
            || preg_match('/\btce\b|\btsi\b|\btfsi\b|\bgdi\b|\bvti\b|\bpuretech\b|\bmpi\b/', $t));
        $dieselHint = (strpos($t, 'diesel') !== false
            || preg_match('/\btdi\b|\bcdi\b|\bhdi\b|\bdci\b|\bcrdi\b|\bbluehdi\b/', $t)
            || preg_match('/\d{2,3}d(?![a-z])/', $t)
            || preg_match('/\d{3}de\b/', $t));
        $isPlugin = (bool)preg_match('/\bplug-?in\b|\bphev\b|e-?tron|\d{3}de\b/', $t);
        $isMild   = (bool)preg_match('/\bmild.?hybrid\b|\bmhev\b/', $t);
        $isFullHybrid = !$isMild && (strpos($t, 'hybrid') !== false
            || preg_match('/\bhev\b|\bhyb\b|\b\d{3}h\b/', $t));

        if ($isMild) {
            if ($dieselHint) return 'diesel';
            if ($petrolHint) return 'benzina';
            return null;
        }
        if ($isPlugin) return $dieselHint ? 'diesel_hybrid' : 'hybrid_plugin';
        if ($isFullHybrid && $dieselHint) return null;
        if ($isFullHybrid) return 'hybrid';
        if (strpos($t, 'electric') !== false || preg_match('/\bev\b|\bbev\b/', $t)) return 'electric';
        if ($dieselHint) return 'diesel';
        if ($petrolHint) return 'benzina';
        if (preg_match('/\blpg\b/', $t)) return 'lpg';
        return null;
    }
}
