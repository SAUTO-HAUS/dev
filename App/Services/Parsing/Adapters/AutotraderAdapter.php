<?php

namespace App\Services\Parsing\Adapters;

use App\Services\Parsing\AbstractAdapter;

// AutoTrader.ca — Canada's consumer marketplace (dealer + private listings).
// Cars are physically in Canada; sauto publishes them under the USA region.
//
// Access mode (verified live, anonymous):
//  - NO account, NO cookie, no Cloudflare/DataDome. Plain GETs answer 200.
//  - The site runs AutoScout24's search funnel (buildId "as24-search-funnel_*"),
//    so every page ships its data as JSON inside <script id="__NEXT_DATA__">.
//    We read props.pageProps — no HTML scraping.
//      list:   /cars[/{makeSlug}[/{modelSlug}]]?... → pageProps.listings[] (20/page)
//      detail: /offers/{guid}                      → pageProps.listingDetails
//    The id-only detail URL 308-redirects to the canonical slug URL, so the
//    listing GUID is all we need to store.
//  - Sold/removed listing: 301 to a SEARCH page (/cars/make/model) instead of
//    308 to /offers/... — that is how checkAvailability decides.
//
// Brand and model go in the PATH, not the query string (`make=13` is ignored).
// Year must be filtered with modelyearfrom/to: fregfrom/fregto filter on first
// registration, which most Canadian listings simply do not carry.
//
// NOT an auction: prices are dealer retail asking prices in CAD and, per the
// site's own disclaimer, exclude taxes and licensing. VIN is never exposed
// (like OpenLane anonymous / Auto1), so cars import without one.
class AutotraderAdapter extends AbstractAdapter
{
    public int $lastOffset = 0;       // next car offset to resume backfill from
    public int $lastTotalCount = 0;   // total results the site reports
    // Listings skipped because we already hold them. Without this the operator
    // sees a bare "0 imported" and cannot tell "nothing new on these pages" from
    // "something is broken" — they look identical.
    public int $lastKnownSkipped = 0;

    private const SOURCE_CODE = 'autotrader';
    private const SOURCE_NAME = 'AutoTrader.ca';
    private const BASE_URL    = 'https://www.autotrader.ca';
    // The site always returns 20 listings per page, whatever we ask for, and
    // stops at 200 pages (4 000 cars) per query — slice by make to go deeper.
    private const PAGE_SIZE   = 20;
    private const MAX_PAGES   = 200;
    // Our buyer's location (Laval, QC — 20 km from the port of Montreal). The
    // site resolves the postal code to lat/lon itself and filters by radius.
    // Radius 0 = no location filter (the whole country) and is the default: the
    // car ships from Montreal whatever province it sits in, so a radius only
    // hides stock. A radius is opt-in from the filter panel.
    private const DEFAULT_ZIP    = 'H7T2C9';
    private const DEFAULT_RADIUS = 0;
    // Listing images are served at the size baked into the URL path.
    private const IMG_FULL = '1280x960';
    // "Price on request" listings (~37% of the catalog) are not priced at 0 —
    // AutoTrader ships them with priceRaw = 1 and onRequestOnly = true. Anything
    // under this floor is that placeholder, never a real asking price.
    private const MIN_PRICE_CAD = 500;
    // Minimum real photos. A one-photo listing is usually a dealer banner or a
    // stock shot rather than the actual car ("in transit" stock, freshly posted
    // ads). Since we sort newest-first, a car skipped today for having no photos
    // yet is seen again on a later search once the dealer uploads them.
    private const MIN_IMAGES = 5;
    // Ceiling for the whole search. The web server drops the connection on its own
    // schedule (LiteSpeed, ~30s) whatever set_time_limit says, and a search cut off
    // mid-flight returns nothing at all — so stop early and keep what was found.
    private const SEARCH_TIME_BUDGET_WEB = 22.0;   // seconds
    private const SEARCH_TIME_BUDGET_CLI = 240.0;  // seconds

    // make id => ['name','slug','models'], read from autotrader_taxonomy.json.
    private ?array $taxonomyCache = null;

    // One manual search pulls a page the operator is waiting on — keep the
    // jitter sub-second like OpenLane/Auto1 instead of the parent's seconds.
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
        return stripos($url, 'autotrader.ca') !== false;
    }


    // ─────────────────────────── search ───────────────────────────

    public function searchByFilter(array $criteria): array
    {
        $maxResults = (int)($criteria['_max_results'] ?? 500);
        $maxPages   = (int)($criteria['_max_pages'] ?? 40);
        if ($maxPages > self::MAX_PAGES) $maxPages = self::MAX_PAGES;

        // _offset_start is a car offset (same contract as the other adapters).
        $offsetStart = (int)($criteria['_offset_start'] ?? 0);
        $page = $offsetStart > 0 ? (int)floor($offsetStart / self::PAGE_SIZE) + 1 : 1;

        // The filter panel works in EUR like the rest of the admin; the site
        // filters in CAD. Convert once here so the local guard below compares
        // CAD against CAD (price_source is the raw CAD asking price).
        $priceMin = !empty($criteria['price_min']) ? $this->eurToCad((float)$criteria['price_min']) : null;
        $priceMax = !empty($criteria['price_max']) ? $this->eurToCad((float)$criteria['price_max']) : null;
        $kmMax    = !empty($criteria['km_max']) ? (int)$criteria['km_max'] : null;
        $yearFrom = !empty($criteria['year_from']) ? (int)$criteria['year_from'] : null;
        $yearTo   = !empty($criteria['year_to']) ? (int)$criteria['year_to'] : null;

        $seen = [];
        $results = [];
        $pagesFetched = 0;

        // Listings we already hold, handed over by the orchestrator. Skipping them
        // here is what makes a repeated import productive: without it the run keeps
        // re-reading the cars we own and the newest pages yield nothing.
        $known = $criteria['_known_ids'] ?? [];
        if (!is_array($known)) $known = [];
        $this->lastKnownSkipped = 0;

        $onCli = (php_sapi_name() === 'cli');

        // Everything here comes from list pages. The CARFAX report lives on each
        // listing's own detail page — one request per car — which a search over
        // hundreds of results cannot pay for; ParsingPublisher checks it instead,
        // for the single car being published. Import wide, publish narrow.

        // Pages that are all cars we own yield nothing, so the loop keeps walking
        // to find fresh ones. Whatever is found by the deadline is returned and
        // the offset moves on, so the next run continues from there.
        $searchDeadline = microtime(true) + ($onCli ? self::SEARCH_TIME_BUDGET_CLI
                                                    : self::SEARCH_TIME_BUDGET_WEB);

        while (count($results) < $maxResults && $pagesFetched < $maxPages && $page <= self::MAX_PAGES) {
            if (microtime(true) > $searchDeadline) break;
            $data = $this->fetchNextData($this->buildSearchUrl($criteria, $page));
            if (!$data) break;

            $props = $data['props']['pageProps'] ?? [];
            $listings = $props['listings'] ?? [];
            if (isset($props['numberOfResults'])) {
                $this->lastTotalCount = (int)$props['numberOfResults'];
            }
            // The site tells us how many pages the result set has — trust that,
            // never the size of a page (see the stop conditions below).
            $totalPages = (int)($props['numberOfPages'] ?? 0);
            if (!$listings) break;

            $newThisPage = 0;
            foreach ($listings as $item) {
                $id = (string)($item['id'] ?? '');
                if ($id === '' || isset($seen[$id])) continue;
                $seen[$id] = true;
                // Already in our catalog — the orchestrator would drop it as a
                // duplicate anyway, so do not spend a detail request on it.
                if (isset($known[$id])) { $this->lastKnownSkipped++; continue; }

                // Too few photos → either an advert image instead of the car, or
                // a listing whose gallery is not up yet. Neither makes a usable
                // card, and both are common on the newest-first page.
                if (count($item['images'] ?? []) < self::MIN_IMAGES) continue;
                if ($this->isBannerGallery($item)) continue;

                $mapped = $this->mapListing($item);
                if ($mapped['price_source'] === null) continue;

                // The site filters server-side, but a listing whose price/km/year
                // is missing from its own filter index can still slip through.
                if ($priceMin !== null && $mapped['price_source'] < $priceMin) continue;
                if ($priceMax !== null && $mapped['price_source'] > $priceMax) continue;
                if ($kmMax !== null && $mapped['km'] !== null && $mapped['km'] > $kmMax) continue;
                if ($yearFrom !== null && $mapped['year'] !== null && $mapped['year'] < $yearFrom) continue;
                if ($yearTo !== null && $mapped['year'] !== null && $mapped['year'] > $yearTo) continue;

                $results[] = $this->normalizeCarData($mapped);
                $newThisPage++;
                if (count($results) >= $maxResults) break;
            }


            if (count($results) >= $maxResults) break;

            $pagesFetched++;
            $this->lastOffset = $page * self::PAGE_SIZE;
            // Stop on the page COUNT the site reports, never on a short page: a
            // page routinely comes back with 19 items instead of 20 (promoted
            // slots are pulled out of the organic list), and treating that as
            // "last page" stopped an Acura search on page 1 — 13 cars imported
            // out of 260. Pages that are genuinely empty still break above.
            if ($totalPages > 0 && $page >= $totalPages) break;
            // No page count in the payload → fall back to the result total, with
            // the same short-page allowance folded in.
            if ($totalPages === 0 && $this->lastTotalCount > 0
                && $page * self::PAGE_SIZE >= $this->lastTotalCount) break;
            $page++;
            // Pause only when the page produced work. Pages made entirely of cars we
            // already hold cost one request and nothing else; sleeping between them
            // just burns the run's budget — that is what an import over an
            // already-imported region spends all its time doing.
            if ($newThisPage > 0) $this->randomDelay();
        }


        return $results;
    }

    // Build a search URL. Make/model are the numeric taxonomy ids in mmvmk0/mmvmd0.
    //
    // They must NOT go in the path: /cars/toyota/rav-4 is an SEO landing page that
    // silently IGNORES every query parameter (verified — fuel/gear/zip changed
    // nothing and were not even echoed back), so a filtered search there would
    // import the make's whole catalog instead of the requested slice.
    private function buildSearchUrl(array $criteria, int $page): string
    {
        $makeId  = $this->resolveMakeId($criteria['brand'] ?? null);
        $modelId = $makeId ? $this->resolveModelId($makeId, $criteria['model'] ?? null) : null;

        $q = [
            'atype'            => 'C',
            'cy'               => 'CA',
            'offer'            => 'U',        // used only — new cars are not importable stock
            'damaged_listing'  => 'exclude',
            'sort'             => 'age',
            'desc'             => '1',
        ];

        if ($makeId)  $q['mmvmk0'] = $makeId;
        if ($modelId) $q['mmvmd0'] = $modelId;

        // Radius search around our buyer's postal code. radius = 0 / "all" drops
        // the location filter and searches the whole country.
        $zip = trim((string)($criteria['zip'] ?? self::DEFAULT_ZIP));
        $radius = $criteria['radius'] ?? self::DEFAULT_RADIUS;
        if ($zip !== '' && (int)$radius > 0) {
            $q['zip']  = $zip;
            $q['zipr'] = (int)$radius;
        }

        // All sellers by default (private included) — restricting to dealers cuts
        // roughly a fifth of the stock. Pass seller_type=dealer to narrow it.
        if ((string)($criteria['seller_type'] ?? 'all') === 'dealer') {
            $q['custtype'] = 'D';
        }

        // Year: modelyear*, NOT freg* (see class comment).
        if (!empty($criteria['year_from'])) $q['modelyearfrom'] = (int)$criteria['year_from'];
        if (!empty($criteria['year_to']))   $q['modelyearto']   = (int)$criteria['year_to'];
        if (!empty($criteria['km_min']))    $q['kmfrom']        = (int)$criteria['km_min'];
        if (!empty($criteria['km_max']))    $q['kmto']          = (int)$criteria['km_max'];
        // Price arrives in EUR (panel) and the site expects CAD.
        if (!empty($criteria['price_min'])) $q['pricefrom'] = (int)round($this->eurToCad((float)$criteria['price_min']));
        if (!empty($criteria['price_max'])) $q['priceto']   = (int)round($this->eurToCad((float)$criteria['price_max']));

        $fuel = $this->fuelFilterValue($criteria['fuel_type'] ?? null);
        if ($fuel !== null) $q['fuel'] = $fuel;
        $gear = $this->gearFilterValue($criteria['gearbox'] ?? null);
        if ($gear !== null) $q['gear'] = $gear;
        $body = $this->bodyFilterValue($criteria['body_type'] ?? null);
        if ($body !== null) $q['body'] = $body;

        if ($page > 1) $q['page'] = $page;

        return self::BASE_URL . '/cars?' . http_build_query($q);
    }

    // The generated taxonomy (make id → name/slug/models), loaded once.
    private function taxonomy(): array
    {
        if ($this->taxonomyCache === null) {
            $file = __DIR__ . '/autotrader_taxonomy.json';
            $data = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
            $this->taxonomyCache = is_array($data) ? ($data['makes'] ?? []) : [];
        }
        return $this->taxonomyCache;
    }

    // Accepts the numeric make id (what the filter UI sends), or a slug/name so a
    // filter saved before the ids existed — or a hand-typed brand — still works.
    private function resolveMakeId($value): ?int
    {
        $v = trim((string)$value);
        if ($v === '') return null;
        if (ctype_digit($v)) return (int)$v;

        $slug = $this->slugify($v);
        foreach ($this->taxonomy() as $id => $info) {
            if ($slug === ($info['slug'] ?? '') || $slug === $this->slugify((string)($info['name'] ?? ''))) {
                return (int)$id;
            }
        }
        $this->logError('AutoTrader: unknown make — not in autotrader_taxonomy.json', ['value' => $v]);
        return null;
    }

    // Same contract as resolveMakeId, scoped to one make's model list.
    private function resolveModelId(int $makeId, $value): ?int
    {
        $v = trim((string)$value);
        if ($v === '') return null;
        if (ctype_digit($v)) return (int)$v;

        $models = $this->taxonomy()[(string)$makeId]['models'] ?? [];
        $slug = $this->slugify($v);
        foreach ($models as $m) {
            if ($slug === $this->slugify((string)($m['label'] ?? '')) || $slug === (string)($m['slug'] ?? '')) {
                return isset($m['value']) ? (int)$m['value'] : null;
            }
        }
        $this->logError('AutoTrader: unknown model for make', ['make_id' => $makeId, 'value' => $v]);
        return null;
    }

    // ─────────────────────────── detail ───────────────────────────

    public function fetchByUrl(string $url): ?array
    {
        $guid = $this->guidFromUrl($url);
        if ($guid === null) {
            $this->logError('AutoTrader: cannot extract listing id from URL', ['url' => $url]);
            return null;
        }
        return $this->fetchById($guid);
    }

    public function fetchById(string $sourceId): ?array
    {
        $raw = $this->fetchDetailRaw($sourceId);
        if (!is_array($raw)) return null;
        return $this->normalizeCarData($this->mapDetail($sourceId, $raw));
    }

    // Raw listingDetails for one listing GUID. /offers/{guid} redirects to the
    // canonical slug URL and curl follows it (CURLOPT_FOLLOWLOCATION).
    public function fetchDetailRaw(string $guid): ?array
    {
        $guid = trim($guid);
        if ($guid === '') return null;

        $data = $this->fetchNextData(self::BASE_URL . '/offers/' . rawurlencode($guid));
        $details = $data['props']['pageProps']['listingDetails'] ?? null;
        if (!is_array($details)) {
            // A gone listing lands on a search page, which has no listingDetails.
            $this->logError('AutoTrader: no listingDetails (sold or removed?)', ['id' => $guid]);
            return null;
        }
        return $details;
    }

    /**
     * The CARFAX report for one listing, or null if it has none we can show.
     *
     * One detail request. Search does not call this — checking every listing is
     * what made an import time out — so the decision lands here instead, on the
     * single car being published.
     */
    public function carfaxFor(string $guid): ?array
    {
        $d = $this->fetchDetailRaw($guid);
        return is_array($d) ? $this->carfaxReport($d) : null;
    }

    // ─────────────────────────── availability ───────────────────────────

    // Live listings resolve to /offers/... (the canonical slug); sold or removed
    // ones bounce to a /cars/... search page. Network failure keeps the car
    // visible — never hide a car because of our own connectivity.
    public function checkAvailability(string $sourceId): bool
    {
        $sourceId = trim($sourceId);
        if ($sourceId === '') return true;

        $response = $this->httpRequest(self::BASE_URL . '/offers/' . rawurlencode($sourceId), [
            'headers' => ['Accept: text/html'],
        ]);
        $status = (int)($response['status'] ?? 0);
        if ($status === 0) return true;                 // curl/network error
        if ($status === 404 || $status === 410) return false;
        if ($status !== 200 || empty($response['body'])) return true;

        // 200 alone is not proof: the search page it redirects to is also a 200.
        return strpos($response['body'], '"listingDetails"') !== false;
    }

    // ─────────────────────────── taxonomy ───────────────────────────

    // All makes the site knows: [{label:"BMW", value:13}, ...]. One request.
    public function fetchMakes(): array
    {
        $data = $this->fetchNextData(self::BASE_URL . '/cars?atype=C&cy=CA&offer=U');
        $makes = $data['props']['pageProps']['taxonomy']['makesSorted'] ?? [];
        return is_array($makes) ? $makes : [];
    }

    // Models of one make. The site only ships a make's model list on that make's
    // own page, so this is one request per make (used by the taxonomy cron).
    public function fetchModelsForMake(string $makeSlug): array
    {
        $data = $this->fetchNextData(self::BASE_URL . '/cars/' . rawurlencode($makeSlug) . '?atype=C&cy=CA&offer=U');
        $models = $data['props']['pageProps']['taxonomy']['models'] ?? [];
        $out = [];
        foreach ((array)$models as $list) {
            foreach ((array)$list as $m) {
                $label = trim((string)($m['label'] ?? ''));
                // "Unspecified" is the site's catch-all bucket, not a model.
                if ($label === '' || strcasecmp($label, 'Unspecified') === 0) continue;
                // value = the numeric model id used by mmvmd0; the slug is kept
                // only so an older saved filter (which stored slugs) still resolves.
                $id = isset($m['value']) ? (int)$m['value'] : 0;
                if ($id === 0) continue;
                $out[] = [
                    'value' => $id,
                    'label' => $label,
                    'slug'  => $this->slugify($label),
                ];
            }
        }
        return $out;
    }

    // Build the full make→models taxonomy the /parsing filter UI reads from
    // autotrader_taxonomy.json. One request for the make list plus one per make.
    // Lives here (not in the cron script) so the console dump and any manual
    // re-generation always produce the same shape.
    //
    // @param callable|null $progress fn(int $done, int $total, string $make, int $models)
    public function buildTaxonomy(?callable $progress = null): array
    {
        $makes = $this->fetchMakes();
        $out = ['generated_at' => date('Y-m-d H:i:s'), 'makes' => []];
        $total = count($makes);
        $done = 0;

        foreach ($makes as $mk) {
            $name = trim((string)($mk['label'] ?? ''));
            $id   = (int)($mk['value'] ?? 0);
            $done++;
            if ($name === '' || $id === 0) continue;

            $slug = $this->slugify($name);
            $models = $this->fetchModelsForMake($slug);
            // A make whose page yields no models is one the site no longer
            // carries — keep it out of the dropdown rather than offering a
            // brand that can only ever return zero cars.
            if (!$models) {
                if ($progress) $progress($done, $total, $name, 0);
                continue;
            }

            $out['makes'][(string)$id] = [
                'name'   => $name,
                'slug'   => $slug,
                'models' => $models,
            ];
            if ($progress) $progress($done, $total, $name, count($models));
            $this->randomDelay();
        }

        return $out;
    }

    // Total results for a query without importing anything (count probe).
    public function countByFilter(array $criteria): int
    {
        $data = $this->fetchNextData($this->buildSearchUrl($criteria, 1));
        return (int)($data['props']['pageProps']['numberOfResults'] ?? 0);
    }

    // ─────────────────────────── mapping ───────────────────────────

    private function mapListing(array $item): array
    {
        $v = $item['vehicle'] ?? [];
        $guid = (string)($item['id'] ?? '');
        $brand = $this->cleanText($v['make'] ?? '');
        $model = $this->cleanText($v['modelGroup'] ?? $v['model'] ?? '');
        $trim  = $this->cleanText($v['modelVersionInput'] ?? '');
        $title = trim($brand . ' ' . $model . ($trim !== '' ? ' ' . $trim : ''));

        $images = $this->gallery($item['images'] ?? []);

        return [
            'source_id'  => $guid,
            'source_url' => (string)($item['url'] ?? (self::BASE_URL . '/offers/' . $guid)),
            'vin'        => null, // never exposed by AutoTrader
            'brand'      => $brand,
            'model'      => $model,
            'year'       => isset($v['modelYear']) ? (int)$v['modelYear'] : null,
            'km'         => $this->intFromLoose($v['mileageInKm'] ?? null),
            'fuel_type'  => $this->fuelFromTitle($title) ?? $this->normalizeFuel($v['fuel'] ?? null),
            'gearbox'    => $this->normalizeGearbox($v['transmission'] ?? null),
            // Only ~36% of listings carry cc here; enrichment fills the rest.
            'engine_volume' => $this->intFromLoose($v['engineDisplacementInCCM'] ?? null),
            'drive_type' => $this->inferDriveType($title),
            'price_source' => $this->realPrice($item['price'] ?? null),
            'price_source_currency' => 'CAD',
            'title'  => $title,
            'images' => $images,
            'raw_data' => $item,
        ];
    }

    private function mapDetail(string $guid, array $d): array
    {
        $v = $d['vehicle'] ?? [];
        $brand = $this->cleanText($v['make'] ?? '');
        $model = $this->cleanText($v['modelGroup'] ?? $v['model'] ?? '');
        $trim  = $this->cleanText($v['modelVersionInput'] ?? '');
        $title = trim($brand . ' ' . $model . ($trim !== '' ? ' ' . $trim : ''));

        $images = $this->gallery($d['images'] ?? []);

        // Equipment arrives grouped by category; the id IS the human label.
        $features = [];
        foreach (($v['equipment'] ?? []) as $group) {
            foreach ((array)$group as $eq) {
                $label = trim((string)($eq['id'] ?? ''));
                if ($label !== '') $features[] = $label;
            }
        }

        $year = isset($v['modelYear']) ? (int)$v['modelYear'] : null;
        if (!$year && !empty($v['firstRegistrationDateRaw'])) {
            $year = (int)substr((string)$v['firstRegistrationDateRaw'], 0, 4);
        }

        return [
            'source_id'  => $guid,
            'source_url' => (string)($d['webPage'] ?? (self::BASE_URL . '/offers/' . $guid)),
            'vin'        => null,
            'brand'      => $brand,
            'model'      => $model,
            'year'       => $year,
            'km'         => isset($v['mileageInKmRaw']) ? (int)$v['mileageInKmRaw'] : $this->intFromLoose($v['mileageInKm'] ?? null),
            'fuel_type'  => $this->fuelFromTitle($title) ?? $this->normalizeFuel($v['fuelCategory']['formatted'] ?? null),
            'gearbox'    => $this->normalizeGearbox($v['transmissionType'] ?? null),
            'engine_volume' => $this->intFromLoose(
                $v['rawDisplacementInCCM'] ?? $v['displacementInCCM'] ?? $v['rawCylinderCapacity'] ?? $v['cylinderCapacity'] ?? null
            ),
            'power_hp'   => isset($v['rawPowerInHp']) ? (int)$v['rawPowerInHp'] : $this->intFromLoose($v['powerInHp'] ?? null),
            'seats'      => isset($v['numberOfSeats']) ? (int)$v['numberOfSeats'] : null,
            'drive_type' => $this->normalizeDriveType($v['driveTrain'] ?? null) ?? $this->inferDriveType($title),
            'color'      => $this->normalizeColor($v['bodyColor'] ?? null),
            'body_type'  => $this->normalizeBodyType($v['bodyType'] ?? null),
            'price_source' => $this->realPrice($d['prices']['public'] ?? $d['price'] ?? null),
            'price_source_currency' => 'CAD',
            'title'       => $title,
            'description' => $this->cleanText($d['description'] ?? ''),
            'features'    => $features,
            'images'      => $images,
            'carfax'      => $this->carfaxReport($d),
            'raw_data'    => $d,
        ];
    }

    private function carfaxReport(array $d): ?array
    {
        $cf = $d['vehicleReport']['carfax'] ?? null;
        if (!is_array($cf)) return null;

        // Mode decides, not the URL. Read off 45 live listings, each mode matched
        // against the button AutoTrader actually renders:
        //   FreeCarFax          20%  "View report"          opens the report, no form
        //   ViewCarFaxWithLead  42%  "Request free report"  free, after giving Carfax
        //                                                   an email address
        //   BuyCarFax           33%  "Buy report"           an order page
        //   RequestCarFax        2%  a request form
        // Only FreeCarFax is a report we can put behind a button on our own page.
        // Note the name: "ViewCarFax..." is the LEAD-GATED one, so a rule built on
        // the word "View" — or on the vhr.carfax.ca host, which both share —
        // selects exactly the wrong listings.
        if ((string)($cf['carfaxMode'] ?? '') !== 'FreeCarFax') return null;

        $url = trim((string)($cf['reportUrlEn'] ?? ''));
        if ($url === '' || stripos($url, 'vhr.carfax.ca') === false) return null;

        return [
            'mode'   => (string)($cf['carfaxMode'] ?? ''),
            'url'    => $url,
            'url_fr' => trim((string)($cf['reportUrlFr'] ?? '')),
        ];
    }

    // ─────────────────────────── http / parsing ───────────────────────────

    // GET a page and return its __NEXT_DATA__ payload (the whole page state).
    private function fetchNextData(string $url): ?array
    {
        $response = $this->httpRequest($url, [
            'headers' => ['Accept: text/html', 'Accept-Language: en-CA,en;q=0.9'],
        ]);
        $status = (int)($response['status'] ?? 0);
        if ($status !== 200 || empty($response['body'])) {
            $this->logError('AutoTrader HTTP failure', ['status' => $status, 'url' => $url, 'error' => $response['error'] ?? '']);
            return null;
        }

        if (!preg_match('#<script id="__NEXT_DATA__"[^>]*>(.*?)</script>#s', $response['body'], $m)) {
            $this->logError('AutoTrader: __NEXT_DATA__ not found (page layout changed?)', ['url' => $url]);
            return null;
        }
        $data = json_decode($m[1], true);
        if (!is_array($data)) {
            $this->logError('AutoTrader: __NEXT_DATA__ is not valid JSON', ['url' => $url]);
            return null;
        }
        return $data;
    }

    // ─────────────────────────── helpers ───────────────────────────

    // "BMW" → "bmw", "Alfa Romeo" → "alfa-romeo", "300 ZX" → "300-zx". Values
    // that already look like a slug pass through untouched.
    private function slugify(string $s): string
    {
        $s = trim(mb_strtolower($s, 'UTF-8'));
        if ($s === '') return '';
        $s = str_replace(['ä', 'ö', 'ü', 'ß', 'é', 'è', 'ë', 'ç'], ['a', 'o', 'u', 'ss', 'e', 'e', 'e', 'c'], $s);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim((string)$s, '-');
    }

    // The asking price in CAD, or null when the seller hides it ("price on
    // request"). Those come through as priceRaw = 1 with onRequestOnly = true —
    // importing that literally would publish cars at 1 CAD. suggestedRetailPrice
    // is NOT used as a stand-in: it is the manufacturer's list price for a new
    // car, so on a used one it is far above what the dealer actually asks.
    private function realPrice(?array $price): ?float
    {
        if (!$price) return null;
        if (!empty($price['onRequestOnly'])) return null;
        $raw = $price['priceRaw'] ?? null;
        if ($raw === null || $raw === '') return null;
        $raw = (float)$raw;
        return $raw >= self::MIN_PRICE_CAD ? $raw : null;
    }

    // EUR → CAD at BNM's daily rate (the same one that converts the imported
    // price back to EUR, so the filter and the card can never disagree).
    private function eurToCad(float $eur): float
    {
        return \App\Services\Parsing\CurrencyRate::eurToCad($this->db, $this->prefix, $eur);
    }

    // "94,077 km" / "1,500 cc" → 94077 / 1500. Empty and "-" become null.
    private function intFromLoose($val): ?int
    {
        if ($val === null || $val === '') return null;
        if (is_int($val) || is_float($val)) return (int)$val;
        $digits = preg_replace('/[^0-9]/', '', (string)$val);
        return ($digits === '' ) ? null : (int)$digits;
    }

    /**
     * True when a listing's whole gallery is the dealer's "NEW ARRIVAL — PHOTOS
     * AVAILABLE SOON" banner repeated, instead of the car.
     *
     * The list payload flags only the COVER (tracking.imageContent =
     * "placeholder|<score>"), and that alone is not enough: some listings open
     * with a banner and continue with real photos — those are fine, since the
     * first photo is dropped anyway. So a flagged listing is verified: two of
     * its photos are HEAD-requested and their byte sizes compared. A repeated
     * banner is literally the same file (equal Content-Length); real photos
     * differ. Verified live: Honda Civic 5074/5074 and Jeep Wrangler 5848/5848
     * were all-banner, while Mazda 3 (4826/5108) and Lincoln Corsair
     * (6366/7998) had genuine galleries behind a banner cover.
     *
     * Only ~3% of listings are flagged, so this costs two small HEAD requests on
     * a handful of cars per search — not one per imported car.
     */
    private function isBannerGallery(array $item): bool
    {
        $marker = (string)(($item['tracking'] ?? [])['imageContent'] ?? '');
        if (strncmp($marker, 'placeholder', 11) !== 0) return false;

        $images = array_values(array_filter((array)($item['images'] ?? []), 'is_string'));
        // Flagged and nothing left to compare → treat as a banner.
        if (count($images) < 3) return true;

        $a = $this->imageByteSize($images[1]);
        $b = $this->imageByteSize($images[2]);
        // Either request failed → keep the car rather than drop it on a network hiccup.
        if ($a <= 0 || $b <= 0) return false;

        return $a === $b;
    }

    // Content-Length of an image, without downloading it.
    private function imageByteSize(string $url): int
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        curl_exec($ch);
        $len = (int)curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code === 200 && $len > 0) ? $len : 0;
    }

    // The whole gallery, first photo included. That frame often carries the dealer's
    // phone and address printed across it — Photoroom washes it off on the way to 999,
    // where text on the main image is a problem; on sauto it goes out as shot.
    private function gallery(array $urls): array
    {
        $clean = [];
        foreach ($urls as $img) {
            if (is_string($img) && $img !== '') $clean[] = $this->fullSizeImage($img);
        }
        return $clean;
    }

    // Ask the image CDN for the big rendition instead of the list thumbnail: the
    // size is the last path segment (.../hash.jpg/250x188.webp).
    private function fullSizeImage(string $url): string
    {
        return preg_replace('#/\d+x\d+\.(webp|jpg|jpeg|png)$#i', '/' . self::IMG_FULL . '.webp', $url) ?? $url;
    }

    private function cleanText(?string $s): string
    {
        if ($s === null) return '';
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/<br\s*\/?>/i', "\n", $s);
        $s = strip_tags((string)$s);
        return trim(preg_replace('/[ \t]+/', ' ', (string)$s));
    }

    // /offers/{slug}-{guid} or /offers/{guid} → guid.
    private function guidFromUrl(string $url): ?string
    {
        if (preg_match('/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $url, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }

    // ─────────── filter value mapping (internal code → site param) ───────────

    // fuel=: 2 hybrid, B gasoline, C natural gas, D diesel, E electric,
    // F flex fuel, L propane, O other.
    private function fuelFilterValue(?string $fuel): ?string
    {
        if (!$fuel) return null;
        $map = [
            'benzina'       => 'B',
            'diesel'        => 'D',
            'electric'      => 'E',
            'hybrid'        => '2',
            'hybrid_plugin' => '2',   // the site has one hybrid bucket
            'diesel_hybrid' => '2',
            'lpg'           => 'L',
            'cng'           => 'C',
        ];
        return $map[strtolower(trim($fuel))] ?? null;
    }

    private function gearFilterValue(?string $gear): ?string
    {
        if (!$gear) return null;
        $map = ['automat' => 'A', 'automatic' => 'A', 'manual' => 'M'];
        return $map[strtolower(trim($gear))] ?? null;
    }

    // body=: 1 hatchback, 2 convertible, 3 coupe, 5 wagon, 6 sedan, 12 minivan,
    // 14 SUV, 15 pick-up, 7 others.
    private function bodyFilterValue(?string $body): ?string
    {
        if (!$body) return null;
        $map = [
            'hatchback'   => '1',
            'convertible' => '2',
            'coupe'       => '3',
            'wagon'       => '5',
            'sedan'       => '6',
            'minivan'     => '12',
            'microbus'    => '12',
            'van'         => '12',
            'suv'         => '14',
            'pickup'      => '15',
        ];
        return $map[strtolower(trim($body))] ?? null;
    }

    // ─────────── source value → internal code ───────────

    private function normalizeFuel(?string $val): ?string
    {
        if (!$val) return null;
        $k = strtolower(trim($val));
        $map = [
            'gasoline'            => 'benzina',
            'gas'                 => 'benzina',
            'petrol'              => 'benzina',
            'diesel'              => 'diesel',
            'electric'            => 'electric',
            'gas/electric hybrid' => 'hybrid',
            'hybrid'              => 'hybrid',
            'propane (lpg)'       => 'lpg',
            'propane'             => 'lpg',
            'natural gas'         => 'gasoline_cng',
        ];
        if (isset($map[$k])) return $map[$k];
        // "Others"/"Flex Fuel" stay null on purpose: fuel drives the excise, so a
        // guess would produce a wrong MD price.
        if ($k !== 'others' && $k !== 'other' && $k !== 'flex fuel') {
            $this->logError('AutoTrader: unknown fuel — add it to normalizeFuel', ['value' => $val]);
        }
        return null;
    }

    private function normalizeGearbox(?string $val): ?string
    {
        if (!$val) return null;
        $k = strtolower(trim($val));
        $map = [
            'automatic' => 'automat',
            'auto'      => 'automat',
            'manual'    => 'manual',
            'cvt'       => 'cvt',
        ];
        if (isset($map[$k])) return $map[$k];
        $this->logError('AutoTrader: unknown transmission — add it to normalizeGearbox', ['value' => $val]);
        return null;
    }

    private function normalizeBodyType(?string $val): ?string
    {
        if (!$val) return null;
        $k = strtolower(trim($val));
        $map = [
            'hatchback'      => 'hatchback',
            'convertible'    => 'convertible',
            'coupe'          => 'coupe',
            'wagon'          => 'wagon',
            'sedan'          => 'sedan',
            'minivan'        => 'microbus',
            'suv'            => 'suv',
            'pick-up truck'  => 'pickup',
            'pickup'         => 'pickup',
            'van'            => 'van',
        ];
        if (isset($map[$k])) return $map[$k];
        // "Others" is the site's own catch-all (common on QC dealer listings) —
        // leave the field empty rather than inventing a body type.
        if ($k !== 'others' && $k !== 'other') {
            $this->logError('AutoTrader: unknown bodyType — add it to normalizeBodyType', ['value' => $val]);
        }
        return null;
    }

    // driveTrain → internal code. Publishing maps 4x4→44, fwd→fr, rwd→re.
    private function normalizeDriveType(?string $val): ?string
    {
        if (!$val) return null;
        $k = strtolower(trim($val));
        $map = [
            'all wheel drive'   => '4x4',
            '4x4'               => '4x4',
            'four wheel drive'  => '4x4',
            'front wheel drive' => 'fwd',
            'rear wheel drive'  => 'rwd',
        ];
        return $map[$k] ?? null;
    }

    private function normalizeColor(?string $val): ?string
    {
        if (!$val) return null;
        $map = [
            'black' => 'blk', 'white' => 'wht', 'silver' => 'slv',
            'grey' => 'gra', 'gray' => 'gra', 'brown' => 'brn',
            'bronze' => 'brn', 'gold' => 'gld', 'blue' => 'blu',
            'green' => 'grn', 'red' => 'red', 'orange' => 'orn',
            'yellow' => 'ylw', 'violet' => 'prp', 'purple' => 'prp',
            'beige' => 'bge',
        ];
        $k = strtolower(trim($val));
        return $map[$k] ?? 'wht';
    }

    // Guess 4x4/rwd from the trim string (same approach as Encar/OpenLane/Auto1).
    private function inferDriveType(?string $text): ?string
    {
        if (!$text) return null;
        $t = strtolower($text);
        if (preg_match('/\b(xdrive|quattro|4matic|4motion|allroad|all-?wheel|awd|4wd|sh-?awd|4x4|4dr awd)\b/i', $t)) return '4x4';
        if (preg_match('/\bsdrive\b|\brwd\b/i', $t)) return 'rwd';
        if (preg_match('/\bfwd\b/i', $t)) return 'fwd';
        return null;
    }

    // Fuel from the title/trim — catches hybrids the generic fuel field flattens
    // into one bucket. Ported from OpenLane/Auto1.
    private function fuelFromTitle(?string $title): ?string
    {
        if (!$title) return null;
        $t = mb_strtolower($title, 'UTF-8');
        $petrolHint = (strpos($t, 'petrol') !== false || strpos($t, 'gasolin') !== false
            || preg_match('/\btsi\b|\btfsi\b|\bgdi\b|\becoboost\b/', $t));
        $dieselHint = (strpos($t, 'diesel') !== false
            || preg_match('/\btdi\b|\bcdi\b|\bhdi\b|\bcrdi\b|\bduramax\b|\bpowerstroke\b|\bcummins\b|\bbluetec\b/', $t));
        $isPlugin = (bool)preg_match('/\bplug-?in\b|\bphev\b|\bprime\b/', $t);
        $isMild   = (bool)preg_match('/\bmild.?hybrid\b|\bmhev\b/', $t);
        $isFullHybrid = !$isMild && (strpos($t, 'hybrid') !== false || preg_match('/\bhev\b/', $t));

        if ($isMild) {
            if ($dieselHint) return 'diesel';
            if ($petrolHint) return 'benzina';
            return null;
        }
        if ($isPlugin) return $dieselHint ? 'diesel_hybrid' : 'hybrid_plugin';
        if ($isFullHybrid && $dieselHint) return null;
        if ($isFullHybrid) return 'hybrid';
        if (preg_match('/\bbev\b|\belectric\b/', $t)) return 'electric';
        if ($dieselHint) return 'diesel';
        return null;
    }
}
