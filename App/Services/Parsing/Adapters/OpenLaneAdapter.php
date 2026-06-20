<?php

namespace App\Services\Parsing\Adapters;

use App\Services\Parsing\AbstractAdapter;

// OpenLane Europe (B2B used-car auction marketplace).
//
// Access mode: the React front-end POSTs a JSON filter to
//   POST https://www.openlane.eu/en/findcarv6/search
// and gets back a paginated list of "Auctions" (cars). Unlike Encar this is
// NOT anonymous: the site sits behind Cloudflare, so every request needs the
// cookies a real logged-in browser produced (cf_clearance solves the
// Cloudflare challenge; ASP.NET_SessionId carries the login) plus the
// __requestverificationtoken anti-CSRF header.
//
// Those values can't be generated from PHP (cf_clearance needs the browser to
// run Cloudflare's JS challenge), so we read them from .env:
//   OPENLANE_COOKIE  - full cookie request-header string copied from DevTools
//   OPENLANE_RVT     - the __requestverificationtoken request-header value
// When they expire the API answers 403; we surface a clear "renew cookie"
// message instead of a fatal error.
//
// The search payload already carries everything we need for a listing card
// (make/model/year/km/price/fuel/gearbox/displacement/power/thumbnail), so the
// filter flow works straight from the list. Per-car detail (full photo set,
// VIN, description) is fetched in fetchById from the car detail endpoint.
class OpenLaneAdapter extends AbstractAdapter
{
    public int $lastOffset = 0;       // next car offset to resume backfill from
    public int $lastTotalCount = 0;   // total cars OpenLane reports for the query

    private const SOURCE_CODE = 'openlane';
    private const SOURCE_NAME = 'OPENLane Europe';
    private const BASE_URL    = 'https://www.openlane.eu';
    private const API_SEARCH  = 'https://www.openlane.eu/en/findcarv6/search';
    // Detail endpoint — keyed by AuctionId (NOT CarId), GET, returns JSON with
    // the full car payload: ChassisNumber (VIN), PictureList (all photos),
    // EtgOptionList (equipment), colour, doors, etc.
    private const API_DETAIL  = 'https://www.openlane.eu/en/carv6/auction/';
    // Public detail page shown to humans (used as source_url).
    private const DETAIL_WEB_URL = 'https://www.openlane.eu/en/car/info?auctionId=';
    private const IMG_HOST = 'https://images.openlane.eu';

    // Short delay between search pages: one manual search pulls the whole set
    // (a handful of pages) and the user is waiting on it, so the inherited 4–9s
    // pause made the import feel slow. We override randomDelay() below with a
    // sub-second jitter that still avoids hammering OpenLane.

    // Sub-second jitter between search pages (300–700ms). The parent uses whole
    // seconds; here the user is actively waiting, so we keep it light.
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
        return stripos($url, 'openlane.eu') !== false || stripos($url, 'openlane.com') !== false;
    }

    // Read the browser-copied auth (cookie + anti-CSRF token) from .env.
    // Returns [cookie, rvt]; cookie is null when not configured.
    private function loadAuth(): array
    {
        static $auth = null;
        if ($auth !== null) return $auth;

        $cookie = null;
        $rvt = null;
        $root = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/../../../..');
        $envFile = rtrim((string)$root, '/\\') . '/.env';
        if (is_file($envFile)) {
            $env = file_get_contents($envFile);
            if (preg_match('/OPENLANE_COOKIE=(.+)/', $env, $m)) $cookie = trim($m[1]);
            if (preg_match('/OPENLANE_RVT=(.+)/', $env, $m))    $rvt    = trim($m[1]);
        }
        $auth = [$cookie, $rvt];
        return $auth;
    }

    // Common headers that make the POST look like the React app's request.
    private function searchHeaders(string $pageUrl): array
    {
        [$cookie, $rvt] = $this->loadAuth();
        $headers = [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: en-US,en;q=0.9,ru;q=0.8,ro;q=0.7',
            'Content-Type: application/json',
            'Origin: ' . self::BASE_URL,
            'Referer: ' . $pageUrl,
            'X-Requested-With: XMLHttpRequest',
            'x-cotw-app-webview: false',
        ];
        if ($cookie) $headers[] = 'Cookie: ' . $cookie;
        if ($rvt)    $headers[] = '__requestverificationtoken: ' . $rvt;
        return $headers;
    }

    public function searchByFilter(array $criteria): array
    {
        [$cookie] = $this->loadAuth();
        if (!$cookie) {
            $this->logError('OpenLane: no OPENLANE_COOKIE in .env — cannot authenticate');
            return [];
        }

        // OpenLane result sets are small (tens–hundreds). Its UserPreferences
        // sort is NOT stable across pages — paginating with size 20 made a few
        // cars repeat/skip between pages (107 of 111). Fix: request a LARGE page
        // so the whole set comes back in ONE call — no pagination, nothing can be
        // reshuffled out. We still loop as a safety net for sets bigger than 250.
        $pageSize   = 250;
        $maxResults = (int)($criteria['_max_results'] ?? 2000);
        $maxPages   = (int)($criteria['_max_pages']   ?? 200);
        $page = 1; // always from the top — one search = all cars

        $pageUrl = $this->buildPageUrl($criteria);
        // ONE search log id for the whole pagination run. Regenerating it per
        // page made OpenLane treat each page as a fresh search, reshuffling the
        // (non-deterministic) order so some cars repeated across pages and others
        // were never reached — that's why ~8 of 111 went missing.
        $searchLogId = $this->uuid4();
        $seen = [];
        $results = [];
        $pagesFetched = 0;

        while (count($results) < $maxResults && $pagesFetched < $maxPages) {
            $payload = $this->buildSearchPayload($criteria, $page, $pageSize, $pageUrl, $searchLogId);

            $response = $this->httpRequest(self::API_SEARCH, [
                'post'    => $payload,
                'headers' => $this->searchHeaders($pageUrl),
            ]);

            $status = $response['status'] ?? 0;
            if ($status === 403) {
                $this->logError('OpenLane 403 — cookie/token expired, renew OPENLANE_COOKIE/OPENLANE_RVT in .env', [
                    'page' => $page,
                ]);
                break;
            }
            if ($status !== 200 || empty($response['body'])) {
                $this->logError('OpenLane search HTTP failure', [
                    'status' => $status,
                    'page'   => $page,
                    'error'  => $response['error'] ?? '',
                ]);
                break;
            }

            $data = json_decode($response['body'], true);
            if (!is_array($data) || empty($data['Auctions'])) {
                // Empty page = end of results (or wrong payload). Stop quietly.
                break;
            }
            if (isset($data['Count'])) {
                $this->lastTotalCount = (int)$data['Count'];
            }

            // Price bounds (EUR) the user asked for. OpenLane filters its own
            // PriceRange on the LIVE bid (CurrentPrice), but we import/show only
            // the BUY price (BuyNow/Requested). So re-check the buy price here so
            // a car never slips in above the requested max (or below the min).
            $priceMin = !empty($criteria['price_min']) ? (float)$criteria['price_min'] : null;
            $priceMax = !empty($criteria['price_max']) ? (float)$criteria['price_max'] : null;

            $pageCount = 0;
            foreach ($data['Auctions'] as $item) {
                $id = (string)($item['CarId'] ?? '');
                if ($id === '' || isset($seen[$id])) { $pageCount++; continue; }
                $seen[$id] = true;
                // Import only undamaged cars. The server filter Damages:[1] does
                // this already; this is a harmless backup (skips if a damage flag
                // is present on the listing — absent flags = not skipped).
                if (!empty($item['HasDamages']) || !empty($item['HasTechnicalDamage'])
                    || !empty($item['HasReportDamage'])) { $pageCount++; continue; }
                $mapped = $this->mapAuction($item);
                $p = $mapped['price_source'] ?? null;
                // Skip pure-auction cars with NO buy price (no BuyNow, no
                // Requested) — we only import cars that have a real purchase
                // price ("Купить сразу" / "Ориентировочная цена").
                if ($p === null) { $pageCount++; continue; }
                // Enforce the requested buy-price range on our side (OpenLane
                // filters its PriceRange on the live bid, not the buy price).
                if (($priceMin !== null && $p < $priceMin)
                    || ($priceMax !== null && $p > $priceMax)) {
                    $pageCount++;
                    continue;
                }
                $results[] = $this->normalizeCarData($mapped);
                $pageCount++;
                if (count($results) >= $maxResults) break 2;
            }

            $pagesFetched++;
            // Fewer items than a full page → no more results.
            if ($pageCount < $pageSize) break;
            $page++;
            $this->randomDelay();
        }

        // We always pull the whole set from page 1, so there is no progressive
        // offset to remember — keep it at 0 so the next search re-scans from the
        // top (picks up newly listed cars; dedup skips the ones we already have).
        $this->lastOffset = 0;
        return $results;
    }

    // Fetch the search facets (the option lists + counts the advanced filter
    // shows): makes, models, fuel types, gearboxes, body types, premium offers,
    // dealer countries. Pass $criteria to scope them (e.g. models for a make).
    // Returns the raw Facets array from OpenLane, or null on failure. Cached by
    // the caller — this hits OpenLane, so don't call it per keystroke.
    public function fetchFacets(array $criteria = []): ?array
    {
        [$cookie] = $this->loadAuth();
        if (!$cookie) {
            $this->logError('OpenLane fetchFacets: no OPENLANE_COOKIE in .env');
            return null;
        }

        $pageUrl = $this->buildPageUrl($criteria);
        // ItemsPerPage 1: we only want the Facets block, not the cars.
        $payload = $this->buildSearchPayload($criteria, 1, 1, $pageUrl, $this->uuid4());

        $response = $this->httpRequest(self::API_SEARCH, [
            'post'    => $payload,
            'headers' => $this->searchHeaders($pageUrl),
        ]);

        $status = $response['status'] ?? 0;
        if ($status !== 200 || empty($response['body'])) {
            $this->logError('OpenLane fetchFacets HTTP failure', ['status' => $status]);
            return null;
        }
        $data = json_decode($response['body'], true);
        if (!is_array($data) || empty($data['Facets'])) {
            return null;
        }
        return $data['Facets'];
    }

    // Build the human page URL (also sent as PageUrl in the payload and Referer).
    private function buildPageUrl(array $criteria): string
    {
        $brand = trim((string)($criteria['brand'] ?? ''));
        $model = trim((string)($criteria['model'] ?? ''));
        if ($brand === '') {
            return self::BASE_URL . '/en/findcar';
        }
        $mm = $model !== '' ? ($brand . ',' . $model) : $brand;
        return self::BASE_URL . '/en/findcar?makesModels=' . rawurlencode($mm);
    }

    // Build the JSON body the findcarv6/search endpoint expects. Mirrors the
    // React app's payload shape exactly (verified live).
    private function buildSearchPayload(array $criteria, int $page, int $pageSize, string $pageUrl, ?string $searchLogId = null): string
    {
        $brand = $this->normalizeMakeName(trim((string)($criteria['brand'] ?? '')));
        $model = $this->normalizeModelName(trim((string)($criteria['model'] ?? '')));

        $makeModels = [];
        if ($brand !== '') {
            // Always include Models (empty array when no model chosen) — matches
            // the site's payload exactly. With Make set + Models:[], OpenLane
            // narrows the CleanModel facet to that make's models.
            $entry = ['Make' => $brand, 'Models' => $model !== '' ? [$model] : []];
            $makeModels[] = $entry;
        }

        $query = [];
        if ($makeModels) $query['MakeModels'] = $makeModels;

        // Field names verified from a real filtered request:
        //   RegistrationYearRange / MilageRange (sic — their typo) / PriceRange
        //   {From,To}; FuelTypeIds (string ids); Transmissions; BodyTypes.
        if (!empty($criteria['year_from']) || !empty($criteria['year_to'])) {
            $query['RegistrationYearRange'] = [
                'From' => !empty($criteria['year_from']) ? (int)$criteria['year_from'] : null,
                'To'   => !empty($criteria['year_to'])   ? (int)$criteria['year_to']   : null,
            ];
        }
        if (!empty($criteria['km_min']) || !empty($criteria['km_max'])) {
            $query['MilageRange'] = [
                'From' => !empty($criteria['km_min']) ? (int)$criteria['km_min'] : null,
                'To'   => !empty($criteria['km_max']) ? (int)$criteria['km_max'] : null,
            ];
        }
        if (!empty($criteria['price_min']) || !empty($criteria['price_max'])) {
            $query['PriceRange'] = [
                'From' => !empty($criteria['price_min']) ? (int)$criteria['price_min'] : null,
                'To'   => !empty($criteria['price_max']) ? (int)$criteria['price_max'] : null,
            ];
        }
        // Fuel: internal code → OpenLane FuelTypeId(s), as STRINGS ("9", "100003").
        $fuelIds = $this->fuelTypeIds($criteria['fuel_type'] ?? null);
        if ($fuelIds) {
            $query['FuelTypeIds'] = array_map('strval', $fuelIds);
        }
        // Gearbox → Transmissions ("Automatic"/"Manual").
        $gear = $this->gearboxGroup($criteria['gearbox'] ?? null);
        if ($gear) {
            $query['Transmissions'] = [$gear];
        }
        // Body type → BodyTypes (OpenLane uses "Break", "SUV", ... — our internal
        // code may differ; passed best-effort).
        if (!empty($criteria['body_type'])) {
            $bt = $this->bodyTypeName($criteria['body_type']);
            if ($bt) $query['BodyTypes'] = [$bt];
        }
        // PremiumOffer ("Focus"): describedCorrectly / 247StockSale / highChance /
        // fastRelease / exclusiveAtAdesa / almostNew.
        if (!empty($criteria['premium_offer'])) {
            $query['PremiumOffers'] = [trim($criteria['premium_offer'])];
        }
        // Always import only undamaged cars. OpenLane's damage filter field is
        // "Damages": 1 = no damage, 2 = body, 3 = technical, 4 = both. Verified
        // live: Damages:[1] drops Audi A4 from 111 → 103 (matches the site).
        $query['Damages'] = [1];

        $payload = [
            'query'             => $query ?: new \stdClass(),
            'FacetRequest'      => ['CleanMake', 'FuelTypeId', 'GearboxGroupSearch', 'PremiumOffer', 'CleanModel', 'CountryCodeDealer', 'CommunityId'],
            'PageUrl'           => $pageUrl,
            'Paging'            => ['PageNumber' => $page, 'ItemsPerPage' => $pageSize],
            'SavedSearchId'     => null,
            // Keep the verified working sort. The missing-cars fix is the STABLE
            // UniqueSearchLogId below (same id for every page of one run) — a new
            // id per page made OpenLane reshuffle the order between pages.
            'Sort'              => ['Field' => 'UserPreference', 'Direction' => 'ascending', 'SortType' => 'UserPreferences'],
            'UniqueSearchLogId' => $searchLogId ?: $this->uuid4(),
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    // Reconcile sauto's brand display name with the spelling OpenLane expects
    // in the Make field (seen in its CleanMake facet: "BMW", "Audi",
    // "Alfa Romeo", "Mercedes-Benz", ...). Only the known mismatches are
    // remapped; everything else passes through unchanged.
    private function normalizeMakeName(string $make): string
    {
        if ($make === '') return '';
        // Map common spellings to the EXACT name OpenLane uses in its CleanMake
        // facet. Note: OpenLane keeps diacritics ("Citroën") — sending "Citroen"
        // returns 0. When the name already comes from our taxonomy (built from
        // the facet) it's correct and passes through unchanged.
        $map = [
            'mercedes'       => 'Mercedes-Benz',
            'mercedes benz'  => 'Mercedes-Benz',
            'mercedes-benz'  => 'Mercedes-Benz',
            'vw'             => 'Volkswagen',
            'volkswagen'     => 'Volkswagen',
            'alfa'           => 'Alfa Romeo',
            'alfa romeo'     => 'Alfa Romeo',
            'land rover'     => 'Land Rover',
            'range rover'    => 'Land Rover',
            'mini'           => 'Mini',
            'citroen'        => 'Citroën',
            'citroën'        => 'Citroën',
        ];
        $key = mb_strtolower($make, 'UTF-8');
        return $map[$key] ?? $make;
    }

    // Model names now come from openlane_taxonomy.json (the real OpenLane model
    // strings), so they're already exactly what the search API expects — pass
    // through unchanged. (The old sauto→OpenLane remap could only corrupt them.)
    private function normalizeModelName(string $model): string
    {
        return trim($model);
    }

    // OpenLane sometimes returns a placeholder model ("Other"), not a real one.
    private function isUselessModel(string $model): bool
    {
        $m = mb_strtolower(trim($model), 'UTF-8');
        return $m === '' || in_array($m, ['other', 'others', 'autre', 'другое', 'n/a', '-'], true);
    }

    // Derive a usable model/trim code from the car title (+ engine/trim) when the
    // structured model is useless. Picks an engine/series code (116, 320d, X5, Q3,
    // A4, GLC) which match_model maps to the proper series. Skips brand/year/body.
    private function modelFromTitle(string $brand, string $title): string
    {
        $clean = preg_replace('/\b\d+\s*(?:hp|kw|ps)\b/iu', ' ', $title);
        $clean = preg_replace('/\([^)]*\)/u', ' ', $clean);
        $tokens = preg_split('/\s+/', trim($clean)) ?: [];
        $bodyFuel = ['hatch','suv','coupe','combi','sedan','diesel','petrol','hybrid',
                     'auto','cabrio','break','electric','estate','saloon','5d','3d','4d','2d'];
        foreach ($tokens as $tok) {
            if (preg_match('/^(19|20)\d{2}$/', $tok)) continue;          // year
            if (preg_match('/^\d{2,3}[a-z]?$/iu', $tok)) return $tok;             // 116, 320, 520d
            if (preg_match('/^[A-Za-z]{1,2}\d{1,3}[A-Za-z]?$/u', $tok)) return $tok; // X5, Q3, A4, GLC300
        }
        $firstBrandWord = $brand !== '' ? (preg_split('/[\s-]+/', $brand)[0] ?? '') : '';
        foreach ($tokens as $tok) {
            $low = mb_strtolower($tok, 'UTF-8');
            if ($low === mb_strtolower($brand, 'UTF-8') || $low === mb_strtolower($firstBrandWord, 'UTF-8')) continue;
            if (preg_match('/^(19|20)\d{2}$/', $tok)) continue;
            if (in_array($low, $bodyFuel, true)) continue;
            return $tok;
        }
        return '';
    }

    // Our internal fuel code → OpenLane FuelTypeId list (from its fuelTypes
    // facet): 100001=Petrol, 100003=Diesel, 8=MHEV petrol, 9=MHEV diesel,
    // 100013=HEV (full hybrid petrol), 100023=PHEV (plug-in), 100002=LPG,
    // 100008=Electric. "hybrid" spans every hybrid flavour OpenLane lists.
    private function fuelTypeIds(?string $fuel): array
    {
        if (!$fuel) return [];
        $fuel = trim($fuel);

        // The filter now sends OpenLane's exact FuelTypeId directly (verified
        // codes from its advanced filter). If it's one of those, use it as-is.
        $valid = ['100001','100003','100004','8','9','100013','100022',
                  '100023','100024','107003','100006','100008'];
        if (in_array($fuel, $valid, true)) return [$fuel];

        // Fallback: our internal labels (e.g. from enrichment) → OpenLane codes.
        $map = [
            'benzina'       => [100001, 8],          // petrol + mild-hybrid petrol
            'gasoline'      => [100001, 8],
            'diesel'        => [100003, 9],          // diesel + mild-hybrid diesel
            'lpg'           => [100006],
            'gasoline_cng'  => [107003],
            'hybrid'        => [100013, 100023, 8, 9], // HEV/PHEV/MHEV
            'diesel_hybrid' => [9, 100022, 100024],
            'electric'      => [100004],
            'gasoline_lpg'  => [100006, 100001],
            'other'         => [],
        ];
        return $map[strtolower($fuel)] ?? [];
    }

    // Our gearbox code → OpenLane Transmissions value.
    private function gearboxGroup(?string $gear): ?string
    {
        if (!$gear) return null;
        $g = strtolower($gear);
        if (in_array($g, ['automat', 'automatic', 'auto', 'semi-auto', 'cvt'], true)) return 'Automatic';
        if (in_array($g, ['manual'], true)) return 'Manual';
        return null;
    }

    // OpenLane BodyTypes values (verified from its advanced filter):
    //   SUV, Truck, Cabriolet, Compact, Coupé, Lighttruck, Minibus, MPV,
    //   Pickup, Berline (=sedan), Break (=wagon), Hatchback.
    // The filter sends these exact strings, so they pass through unchanged. We
    // also accept our older internal codes as a fallback.
    private function bodyTypeName(?string $body): ?string
    {
        if (!$body) return null;
        $valid = ['SUV','Truck','Cabriolet','Compact','Coupé','Lighttruck',
                  'Minibus','MPV','Pickup','Berline','Break','Hatchback'];
        if (in_array($body, $valid, true)) return $body; // already an OpenLane value
        // Legacy internal codes → OpenLane value.
        $map = [
            'sedan'       => 'Berline',
            'suv'         => 'SUV',
            'hatchback'   => 'Hatchback',
            'wagon'       => 'Break',
            'coupe'       => 'Coupé',
            'convertible' => 'Cabriolet',
            'minivan'     => 'MPV',
            'van'         => 'Lighttruck',
            'pickup'      => 'Pickup',
        ];
        return $map[strtolower($body)] ?? null;
    }

    private function uuid4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }

    // Map one Auctions[] entry from the search response to our normalized shape.
    private function mapAuction(array $item): array
    {
        $ident = $item['CarIdentification'] ?? [];

        $brand = $this->cleanText($item['CleanMake'] ?? $ident['Make'] ?? '');
        $model = $this->cleanText($ident['ModelDisplay'] ?? $ident['Model'] ?? '');
        // Guard: if OpenLane gives a useless model ("Other"/blank), derive it from
        // the car name / engine+trim (same idea as eCarsTrade's "BMW Other").
        if ($this->isUselessModel($model)) {
            $model = $this->modelFromTitle(
                $brand,
                (string)($item['CarNameEn'] ?? '') . ' '
                    . (string)($ident['EngineDescription'] ?? '') . ' '
                    . (string)($ident['TrimLine'] ?? '')
            ) ?: $model;
        }

        // Year from first registration date (YYYY-...).
        $year = null;
        if (!empty($item['DateFirstRegistration'])) {
            $year = (int)substr((string)$item['DateFirstRegistration'], 0, 4) ?: null;
        }

        // Price: use the BUY price, never the live bid. BuyNowPrice = "Купить
        // сразу" (fixed buy-now); RequestedSalesPrice = "Ориентировочная цена"
        // (indicative). We deliberately DO NOT use CurrentPrice ("Текущая ставка"
        // = current auction bid), which isn't a sale price.
        $price = null;
        foreach (['BuyNowPrice', 'RequestedSalesPrice'] as $pk) {
            if (!empty($item[$pk]) && (float)$item[$pk] > 0) { $price = (float)$item[$pk]; break; }
        }

        // Engine title (e.g. "25d", "45e") used to build a readable trim line.
        $engineDesc = trim((string)($ident['EngineDescription'] ?? ''));
        $trim       = trim((string)($ident['TrimLine'] ?? ''));
        $titleParts = array_filter([$brand, $model, $engineDesc, $trim]);
        $title = $item['CarNameEn'] ?? implode(' ', $titleParts);

        // Drive type: explicit flag + name hints (xDrive/quattro/4MATIC...).
        if (!empty($ident['IsFourWheelDrive'])) {
            $drive = '4x4';
        } else {
            $drive = $this->inferDriveType(($item['CarNameEn'] ?? '') . ' ' . $engineDesc . ' ' . $trim);
        }

        // The detail endpoint is keyed by AuctionId, so the public/detail URL
        // points at the auction, not the CarId.
        $auctionId = (string)($item['AuctionId'] ?? '');

        return [
            'source_id'  => (string)($item['CarId'] ?? ''),
            'source_url' => $auctionId !== '' ? self::DETAIL_WEB_URL . $auctionId : self::BASE_URL . '/en/findcar',
            'vin'        => $this->validVin($item['ChassisNumber'] ?? null),
            'brand'      => $brand,
            'model'      => $model,
            'year'       => $year,
            'km'         => isset($item['Mileage']) ? (int)$item['Mileage'] : null,
            // Title-first fuel (catches diesel-hybrid the list mislabels as petrol
            // via generic "Hybride"/FuelTypeId=0), fall back to the id/label logic.
            'fuel_type'  => $this->fuelFromTitle((string)$title) ?? $this->normalizeFuel(
                $ident['FuelGroup'] ?? null,
                $item['FuelTypeId'] ?? $ident['FuelTypeId'] ?? null,
                trim(($ident['EngineDescription'] ?? '') . ' ' . ($ident['TrimLine'] ?? '') . ' ' . ($ident['TypeName'] ?? ''))
            ),
            'gearbox'    => $this->normalizeGearbox($ident['GearboxGroup'] ?? null),
            'engine_volume' => isset($item['CylinderCapacity']) ? (int)$item['CylinderCapacity'] : null,
            'power_hp'   => isset($item['Hp']) ? (int)$item['Hp'] : (isset($item['Pk']) ? (int)$item['Pk'] : null),
            'seats'      => isset($item['Places']) ? (int)$item['Places'] : null,
            'drive_type' => $drive,
            'body_type'  => $this->normalizeBodyType($item['Size'] ?? null),
            'price_source' => $price,
            'price_source_currency' => $item['CurrencyCodeId'] ?? 'EUR',
            'title'  => trim((string)$title),
            'images' => $this->collectThumbnail($item),
            'raw_data' => $item,
        ];
    }

    // The search payload only carries one thumbnail; full gallery comes from
    // fetchById. Return the thumbnail so the card has at least one image.
    private function collectThumbnail(array $item): array
    {
        $url = $item['ThumbnailUrl'] ?? null;
        return $url ? [$url] : [];
    }

    public function fetchByUrl(string $url): ?array
    {
        // Detail URLs carry the AuctionId (car/info?auctionId=...).
        if (preg_match('/auctionId=(\d+)/i', $url, $m)) {
            return $this->fetchByAuctionId($m[1]);
        }
        $id = $this->extractIdFromUrl($url);
        if (!$id) {
            $this->logError('OpenLane: cannot extract id from URL', ['url' => $url]);
            return null;
        }
        return $this->fetchById($id);
    }

    // fetchById receives our source_id (= CarId). The detail endpoint, though,
    // is keyed by AuctionId, which we stored in raw_data at import time. Resolve
    // it from the parsing_cars row and delegate to fetchByAuctionId.
    public function fetchById(string $sourceId): ?array
    {
        $sourceId = trim($sourceId);
        if ($sourceId === '') return null;

        $auctionId = $this->resolveAuctionId($sourceId);
        if (!$auctionId) {
            $this->logError('OpenLane fetchById: no AuctionId for CarId', ['source_id' => $sourceId]);
            return null;
        }
        return $this->fetchByAuctionId($auctionId);
    }

    // Look up the AuctionId we saved in parsing_cars.raw_data for this CarId.
    private function resolveAuctionId(string $carId): ?string
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT raw_data FROM ' . $this->prefix . '_parsing_cars
                 WHERE source = ? AND source_id = ? ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute([self::SOURCE_CODE, $carId]);
            $raw = $stmt->fetchColumn();
            if ($raw) {
                $data = json_decode($raw, true);
                $aid = $data['AuctionId'] ?? ($data['raw_data']['AuctionId'] ?? null);
                if ($aid) return (string)$aid;
            }
        } catch (\Throwable $e) {
            $this->logError('OpenLane resolveAuctionId failed', ['err' => $e->getMessage()]);
        }
        return null;
    }

    // Fetch the RAW detail JSON for one auction (carv6/auction/{id}). Carries
    // EtgOptionList (equipment) and Damage (condition) that the normalized shape
    // drops — used by the report. Returns the decoded array or null.
    public function fetchDetailRaw(string $auctionId): ?array
    {
        $auctionId = trim($auctionId);
        if ($auctionId === '') return null;

        [$cookie] = $this->loadAuth();
        if (!$cookie) {
            $this->logError('OpenLane fetchDetailRaw: no OPENLANE_COOKIE in .env');
            return null;
        }

        $url = self::API_DETAIL . rawurlencode($auctionId);
        $response = $this->httpRequest($url, [
            'headers' => $this->searchHeaders(self::BASE_URL . '/en/car/info?auctionId=' . $auctionId),
        ]);

        $status = $response['status'] ?? 0;
        if ($status === 403) {
            $this->logError('OpenLane detail 403 — renew OPENLANE_COOKIE/OPENLANE_RVT in .env', ['auction_id' => $auctionId]);
            return null;
        }
        if ($status !== 200 || empty($response['body'])) {
            $this->logError('OpenLane detail HTTP failure', ['auction_id' => $auctionId, 'status' => $status]);
            return null;
        }

        $d = json_decode($response['body'], true);
        if (!is_array($d) || empty($d['CarId'])) {
            $this->logError('OpenLane detail non-JSON / empty', ['auction_id' => $auctionId]);
            return null;
        }
        return $d;
    }

    // Fetch + map the full detail payload for one auction. This is where VIN,
    // the complete photo gallery and the equipment list come from.
    public function fetchByAuctionId(string $auctionId): ?array
    {
        $d = $this->fetchDetailRaw($auctionId);
        if (!is_array($d)) return null;
        return $this->normalizeCarData($this->mapDetail($d));
    }

    // Map the car/info detail payload to our normalized shape. Richer than the
    // listing: full VIN, every photo, the equipment list and colour.
    private function mapDetail(array $d): array
    {
        $ident = $d['CarIdentification'] ?? [];

        $brand = $this->cleanText($d['CleanMake'] ?? $ident['Make'] ?? $d['Make'] ?? '');
        $model = $this->cleanText($d['CleanModel'] ?? $ident['ModelDisplay'] ?? $ident['Model'] ?? '');
        // Guard: useless model ("Other"/blank) → derive from the title/engine/trim.
        if ($this->isUselessModel($model)) {
            $model = $this->modelFromTitle(
                $brand,
                (string)($d['CarTitleList']['en'] ?? $d['CarNameEn'] ?? '') . ' '
                    . (string)($d['EngineDescription'] ?? '') . ' '
                    . (string)($d['Trimline'] ?? '')
            ) ?: $model;
        }

        $year = null;
        if (!empty($d['YearFirstRegistration'])) {
            $year = (int)$d['YearFirstRegistration'] ?: null;
        } elseif (!empty($d['DateFirstRegistration'])) {
            $year = (int)substr((string)$d['DateFirstRegistration'], 0, 4) ?: null;
        }

        // Buy price only — never the live bid (CurrentPrice = "Текущая ставка").
        // BuyNowPrice ("Купить сразу") → SpecialPrice/RequestedSalesPrice
        // ("Ориентировочная цена").
        $price = null;
        foreach (['BuyNowPrice', 'SpecialPrice', 'RequestedSalesPrice'] as $pk) {
            if (!empty($d[$pk]) && (float)$d[$pk] > 0) { $price = (float)$d[$pk]; break; }
        }

        // All photos: PictureList (fall back to PicturesList), each {Url,...}.
        $images = [];
        $picList = $d['PictureList'] ?? $d['PicturesList'] ?? [];
        foreach ($picList as $p) {
            if (!empty($p['Url'])) $images[] = $p['Url'];
        }
        if (!$images && !empty($d['ThumbnailUrl'])) $images[] = $d['ThumbnailUrl'];

        // Equipment list — names are already English ("Navigation system", ...).
        $features = [];
        foreach (($d['EtgOptionList'] ?? []) as $opt) {
            if (!empty($opt['Name'])) $features[] = $opt['Name'];
        }

        // Drive type: explicit flag (FourWheelDrive / CarIdentification) + hints.
        if (!empty($d['FourWheelDrive']) || !empty($ident['IsFourWheelDrive'])) {
            $drive = '4x4';
        } else {
            $drive = $this->inferDriveType(
                ($d['EngineDescription'] ?? '') . ' ' . ($d['Trimline'] ?? '') . ' ' . ($ident['TypeName'] ?? '')
            );
        }

        $auctionId = (string)($d['AuctionId'] ?? '');

        return [
            'source_id'  => (string)($d['CarId'] ?? ''),
            'source_url' => $auctionId !== '' ? self::DETAIL_WEB_URL . $auctionId : self::BASE_URL . '/en/findcar',
            'vin'        => $this->validVin($d['ChassisNumber'] ?? null),
            'brand'      => $brand,
            'model'      => $model,
            'year'       => $year,
            'km'         => isset($d['Mileage']) ? (int)$d['Mileage'] : null,
            // Title-first fuel (the detail title "120d ... Hybrid" tells diesel-HEV
            // apart, which the generic FuelGroup "Hybride" loses), else id/label.
            'fuel_type'  => $this->fuelFromTitle((string)($d['CarTitleList']['en'] ?? ''))
                ?? $this->normalizeFuel(
                $ident['FuelGroup'] ?? $d['FuelGroupSearch'] ?? null,
                $ident['FuelTypeId'] ?? $d['FuelTypeId'] ?? null,
                trim(($d['EngineDescription'] ?? '') . ' ' . ($d['Trimline'] ?? $ident['TrimLine'] ?? '') . ' ' . ($ident['TypeName'] ?? ''))
            ),
            'gearbox'    => $this->normalizeGearbox($ident['GearboxGroup'] ?? $d['GearboxGroup'] ?? null),
            'engine_volume' => isset($d['CylinderCapacity']) ? (int)$d['CylinderCapacity'] : null,
            'power_hp'   => isset($d['Hp']) ? (int)$d['Hp'] : (isset($d['Pk']) ? (int)$d['Pk'] : null),
            'seats'      => isset($d['Places']) ? (int)$d['Places'] : null,
            'drive_type' => $drive,
            'color'      => $this->normalizeColor($d['BodyColorGroup'] ?? null),
            // Detail Size is a label ("carsize.SUV"); strip the prefix.
            'body_type'  => $this->normalizeBodyType($this->stripLabelPrefix($d['Size'] ?? $d['SizeName'] ?? null)),
            'price_source' => $price,
            'price_source_currency' => $d['CurrencyCodeId'] ?? $d['CurrencyCode'] ?? 'EUR',
            'title'  => trim((string)($d['CarTitleList']['en'] ?? ($brand . ' ' . $model))),
            'features' => $features,
            'images' => $images,
            'raw_data' => $d,
        ];
    }

    // "carsize.SUV" -> "SUV", "carcolor.grey" -> "grey".
    private function stripLabelPrefix(?string $val): ?string
    {
        if (!$val) return null;
        $pos = strrpos($val, '.');
        return $pos !== false ? substr($val, $pos + 1) : $val;
    }

    public function checkAvailability(string $sourceId): bool
    {
        $auctionId = $this->resolveAuctionId($sourceId);
        if (!$auctionId) return true; // can't confirm → keep visible

        [$cookie] = $this->loadAuth();
        if (!$cookie) return true;

        $url = self::API_DETAIL . rawurlencode($auctionId);
        $response = $this->httpRequest($url, [
            'headers' => $this->searchHeaders(self::BASE_URL . '/en/car/info?auctionId=' . $auctionId),
        ]);
        $status = $response['status'] ?? 0;
        if ($status === 404) return false;
        if ($status !== 200 || empty($response['body'])) return true; // network hiccup → keep
        $d = json_decode($response['body'], true);
        if (!is_array($d)) return true;
        // Sold → no longer available.
        if (!empty($d['IsSold'])) return false;
        // Auction finished (batch end date in the past) → no longer biddable.
        $end = $d['BatchEndDate'] ?? $d['EndDate'] ?? $d['BatchEndDateTime'] ?? '';
        if ($end) {
            $ts = strtotime((string)$end);
            if ($ts && $ts < time()) return false;
        }
        return true;
    }

    private function extractIdFromUrl(string $url): ?string
    {
        if (preg_match('/auctionId=(\d+)/i', $url, $m)) return $m[1];
        if (preg_match('/details\/(\d+)/i', $url, $m)) return $m[1];
        if (preg_match('/[?&]carid=(\d+)/i', $url, $m)) return $m[1];
        if (preg_match('/\/(\d{5,})(?:[?\/]|$)/', $url, $m)) return $m[1];
        return null;
    }

    private function validVin($vin): ?string
    {
        $vin = strtoupper(trim((string)$vin));
        $vin = preg_replace('/[^A-HJ-NPR-Z0-9]/', '', $vin);
        return strlen($vin) === 17 ? $vin : null;
    }

    private function cleanText(?string $s): string
    {
        return trim((string)$s);
    }

    // Re-derive the internal fuel code from an already-stored raw_data payload,
    // without re-fetching from OpenLane. Used to back-fill existing cars after the
    // hybrid sub-type logic changed (e.g. PHEV that was saved as plain "hybrid").
    // Returns the internal code, or null if nothing usable is found.
    public function renormalizeFuelFromRaw(array $raw): ?string
    {
        // raw_data may be the listing item, the detail payload, or wrapped.
        $item = $raw;
        if (isset($raw['raw_data']) && is_array($raw['raw_data'])) $item = $raw['raw_data'];
        $ident = $item['CarIdentification'] ?? [];
        $label = $ident['FuelGroup'] ?? $item['FuelGroup'] ?? $item['FuelGroupSearch'] ?? null;
        $id    = $item['FuelTypeId'] ?? $ident['FuelTypeId'] ?? null;
        $hint  = trim(($ident['EngineDescription'] ?? $item['EngineDescription'] ?? '') . ' '
                    . ($ident['TrimLine'] ?? $item['Trimline'] ?? '') . ' '
                    . ($ident['TypeName'] ?? ''));
        return $this->normalizeFuel($label, $id, $hint);
    }

    // Derive the fuel code from the car TITLE, which is more reliable than the
    // search list's generic "Hybride"/FuelTypeId=0. Catches the diesel-hybrid case
    // the list mislabels as petrol hybrid, e.g. "BMW 120d ... Hybrid" (diesel HEV)
    // or "Audi Q7 e-tron 3.0 TDI ... Hybrid" (diesel PHEV). Returns null when the
    // title has no fuel hint (caller falls back to normalizeFuel). Order matters.
    private function fuelFromTitle(?string $title): ?string
    {
        if (!$title) return null;
        $t = mb_strtolower($title, 'UTF-8');
        // Petrol engine markers (Renault TCe, VW TSI/TFSI, etc.) — needed to keep a
        // "TCe ... mild hybrid" as petrol, not let the source mislabel it diesel.
        $petrolHint = (strpos($t, 'petrol') !== false || strpos($t, 'gasolin') !== false
            || preg_match('/\btce\b|\btsi\b|\btfsi\b|\bgdi\b|\bvti\b|\bpuretech\b|\bmpi\b|\bibrid benzina\b/', $t));
        $dieselHint = (strpos($t, 'diesel') !== false
            || preg_match('/\btdi\b|\bcdi\b|\bhdi\b|\bdci\b|\bcrdi\b|\bbluehdi\b/', $t)
            || preg_match('/\d{2,3}d(?![a-z])/', $t)        // 120d, 320d, sDrive16d
            || preg_match('/\d{3}de\b/', $t)               // Mercedes 300de/400de = diesel PHEV
            || preg_match('/\bd\d{3}\b/', $t)              // Land Rover/Jaguar D200/D300 = diesel
            || preg_match('/\b[est]d4\b/', $t));            // Land Rover eD4/SD4/TD4 = diesel
        $isPlugin = (bool)preg_match('/\bplug-?in\b|\bphev\b|e-?tron|\d{3}de\b/', $t);
        $isMild   = (bool)preg_match('/\bmild.?hybrid\b|\bmhev\b/', $t);
        // Full hybrid: "hybrid"/"hev"/"hyb", or Toyota/Lexus "NNNh" badge (250h,
        // 350h = petrol self-charging hybrid). Not when it's a mild hybrid.
        $isFullHybrid = !$isMild && (strpos($t, 'hybrid') !== false
            || preg_match('/\bhev\b|\bhyb\b|\b\d{3}h\b/', $t));

        // Mild-hybrid (48V) is taxed as its BASE engine, not as a hybrid. Resolve to
        // diesel/benzina when the title says which; else null → spec decides.
        if ($isMild) {
            if ($dieselHint) return 'diesel';
            if ($petrolHint) return 'benzina';
            return null;
        }

        // Explicit plug-in → PHEV (diesel or petrol).
        if ($isPlugin) return $dieselHint ? 'diesel_hybrid' : 'hybrid_plugin';

        // A bare full "Hybrid" on a DIESEL trim is AMBIGUOUS (HEV vs PHEV); the title
        // can't tell, and it skews the excise — return null, let the spec decide.
        if ($isFullHybrid && $dieselHint) return null;

        // Petrol full hybrid is safe from the title ("1.8 Hybrid", "1,5 Hyb").
        if ($isFullHybrid) return 'hybrid';

        if (strpos($t, 'electric') !== false || preg_match('/\bev\b|\bbev\b/', $t)) return 'electric';
        if ($dieselHint) return 'diesel';
        if (strpos($t, 'petrol') !== false || strpos($t, 'gasolin') !== false
            || preg_match('/\btsi\b|\btfsi\b|\bgdi\b|\bvti\b/', $t)) return 'benzina';
        if (preg_match('/\blpg\b/', $t)) return 'lpg';
        return null;
    }

    // OpenLane fuel -> internal fuel code. Prefers the precise FuelTypeId (which
    // tells HEV apart from PHEV/MHEV) and falls back to the generic FuelGroup
    // label. The hybrid sub-type matters for customs (plug-in = bigger excise
    // discount), so we keep it: 100013=HEV→hybrid, 100023=PHEV→hybrid_plugin,
    // 8=MHEV petrol→benzina, 9=MHEV diesel→diesel, 100022/100024=diesel PHEV.
    private function normalizeFuel(?string $val, $fuelTypeId = null, ?string $hint = null): ?string
    {
        // 1) Precise id mapping first (distinguishes hybrid flavours). Note: search
        // items often carry FuelTypeId = 0 (useless) and FuelGroup = "Hybride"
        // (generic), so this only helps when a real id is present.
        $id = $fuelTypeId !== null ? trim((string)$fuelTypeId) : '';
        if ($id !== '' && $id !== '0') {
            $byId = [
                '100001' => 'benzina', '8' => 'benzina',           // petrol, MHEV petrol
                '100003' => 'diesel',  '9' => 'diesel',            // diesel, MHEV diesel
                '100013' => 'hybrid',                              // HEV (full hybrid petrol)
                '100023' => 'hybrid_plugin',                       // PHEV (plug-in petrol)
                '100022' => 'diesel_hybrid', '100024' => 'diesel_hybrid', // diesel hybrids
                '100006' => 'lpg', '107003' => 'gasoline_cng',
                '100004' => 'electric', '100008' => 'electric',
            ];
            if (isset($byId[$id])) return $byId[$id];
        }

        // 2) Fallback: parse the human label by KEYWORD (substring), because
        // OpenLane writes compound strings like "Petrol/Elektro-PlugIn (PHEV)",
        // "Diesel/Elektro (HEV)", "Petrol/Elektro (MHEV)". When the label is just
        // the generic "Hybride", fold in the engine/type text ($hint, e.g.
        // EngineDescription "T8 PlugIn") so we can still tell PHEV/diesel apart.
        if (!$val && !$hint) return null;
        $k = strtolower(trim((string)$val) . ' ' . strtolower(trim((string)$hint)));

        $hasElectro = (strpos($k, 'elektro') !== false || strpos($k, 'electro') !== false
                       || strpos($k, 'hybrid') !== false || strpos($k, 'hev') !== false);
        $isPlugin   = (strpos($k, 'plugin') !== false || strpos($k, 'plug-in') !== false
                       || strpos($k, 'phev') !== false);
        $isDiesel   = (strpos($k, 'diesel') !== false);

        if ($hasElectro) {
            if ($isPlugin) return $isDiesel ? 'diesel_hybrid' : 'hybrid_plugin';
            // MHEV / HEV (non plug-in): treat as full hybrid (diesel or petrol).
            return $isDiesel ? 'diesel_hybrid' : 'hybrid';
        }
        if ($isDiesel) return 'diesel';
        if (strpos($k, 'petrol') !== false || strpos($k, 'gasolin') !== false
            || strpos($k, 'benzin') !== false) return 'benzina';
        if (strpos($k, 'electric') !== false || $k === 'ev') return 'electric';
        if (strpos($k, 'lpg') !== false) return 'lpg';
        if (strpos($k, 'cng') !== false) return 'gasoline_cng';
        return $k;
    }

    private function normalizeGearbox(?string $val): ?string
    {
        if (!$val) return null;
        $map = [
            'automatic'      => 'automat',
            'automatik'      => 'automat',
            'auto'           => 'automat',
            'manual'         => 'manual',
            'semi-automatic' => 'semi-auto',
            'cvt'            => 'cvt',
        ];
        $k = strtolower(trim($val));
        return $map[$k] ?? strtolower($val);
    }

    // OpenLane "Size" (SUV, Sedan, ...) -> internal body codes.
    private function normalizeBodyType(?string $val): ?string
    {
        if (!$val) return null;
        // Normalise: lowercase, strip accents (Coupé→coupe) and spaces/dashes.
        $k = strtolower(trim($val));
        $k = strtr($k, ['é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a','ï'=>'i','î'=>'i','ô'=>'o','û'=>'u','ü'=>'u','ç'=>'c']);
        $k = str_replace([' ', '-', '_'], '', $k);
        $map = [
            'suv'         => 'suv',
            'sedan'       => 'sedan',
            'saloon'      => 'sedan',
            'berline'     => 'sedan',     // OpenLane (FR)
            'limousine'   => 'sedan',
            'hatchback'   => 'hatchback',
            'compact'     => 'hatchback', // OpenLane
            'estate'      => 'wagon',
            'wagon'       => 'wagon',
            'station'     => 'wagon',
            'stationwagon'=> 'wagon',
            'break'       => 'wagon',     // OpenLane (FR)
            'touring'     => 'wagon',
            'coupe'       => 'coupe',
            'cabrio'      => 'convertible',
            'cabriolet'   => 'convertible', // OpenLane
            'convertible' => 'convertible',
            'roadster'    => 'convertible',
            'mpv'         => 'minivan',
            'minivan'     => 'minivan',
            'minibus'     => 'van',        // OpenLane
            'van'         => 'van',
            'lighttruck'  => 'van',        // OpenLane
            'pickup'      => 'pickup',
            'truck'       => 'pickup',
        ];
        return $map[$k] ?? null;
    }

    // OpenLane BodyColorGroup (English) -> sauto colour codes. Unknown colours
    // fall back to "wht" so auto-publish never fails on a missing colour.
    private function normalizeColor(?string $val): ?string
    {
        if (!$val) return null;
        $map = [
            'black'  => 'blk',
            'white'  => 'wht',
            'silver' => 'slv',
            'grey'   => 'gra',
            'gray'   => 'gra',
            'brown'  => 'brn',
            'gold'   => 'gld',
            'blue'   => 'blu',
            'green'  => 'grn',
            'red'    => 'red',
            'orange' => 'orn',
            'yellow' => 'ylw',
            'purple' => 'prp',
            'violet' => 'prp',
            'pink'   => 'pnk',
            'beige'  => 'bge',
        ];
        $k = strtolower(trim($val));
        return $map[$k] ?? 'wht';
    }

    // Guess 4x4/rwd from grade/name strings (same approach as Encar).
    private function inferDriveType(?string $text): ?string
    {
        if (!$text) return null;
        $t = strtolower($text);
        if (preg_match('/\b(xdrive|quattro|4matic|4motion|allroad|all-?wheel|awd|4wd|sh-?awd|4x4)\b/i', $t)) return '4x4';
        if (preg_match('/\bsdrive\b/i', $t)) return 'rwd';
        return null;
    }
}
