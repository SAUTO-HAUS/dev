<?php

namespace App\Services\Parsing\Adapters;

use App\Services\Parsing\AbstractAdapter;

/**
 * e-CarsTrade adapter.
 *
 * Data source: GET /future_api.php?request_type=cars&start=..&perpage=..&...
 * The endpoint streams a JSON object PER CAR: {status, car_id, result} where
 * `result` is the BASE64-encoded HTML of one car card. We decode each card and
 * scrape the fields (title, year, km, fuel, gearbox, hp, buy-now price, photos,
 * country, auction end) from it — eCarsTrade has no clean JSON car API.
 *
 * Auth: a logged-in session cookie copied from the browser, kept in .env:
 *   ECARSTRADE_COOKIE  - full cookie request-header string
 * (We only import "Купить" / buy-now fixed-price cars, like OpenLane.)
 */
class EcarsTradeAdapter extends AbstractAdapter
{
    private const SOURCE_CODE = 'ecarstrade';
    private const SOURCE_NAME = 'e-CarsTrade (Belgia)';
    private const BASE_URL = 'https://ru.ecarstrade.com';
    private const API_SEARCH = self::BASE_URL . '/future_api.php';
    private const CAR_WEB_URL = self::BASE_URL . '/cars/';

    // Faster than the 3-8s parent default; eCarsTrade is a plain PHP API.
    protected $requestDelayMin = 1;
    protected $requestDelayMax = 2;

    // Brand name → eCarsTrade mark id (from /search's <select name="mark[]">).
    // Only marks with real stock are listed; names match our display spelling.
    private const MARK_IDS = [
        'Alfa Romeo'=>2, 'Alpine'=>167, 'Audi'=>5, 'Bentley'=>6, 'BMW'=>7, 'BYD'=>99,
        'Citroën'=>14, 'Citroen'=>14, 'Cupra'=>98, 'Dacia'=>15, 'DAF'=>17, 'Dodge'=>20,
        'DS Automobiles'=>96, 'Fiat'=>23, 'Ford'=>24, 'Honda'=>27, 'Hyundai'=>29,
        'Isuzu'=>32, 'Iveco'=>91, 'Jaguar'=>33, 'Jeep'=>34, 'Kia'=>35, 'Lancia'=>37,
        'Land Rover'=>38, 'Lexus'=>39, 'Lotus'=>41, 'Lynk&Co'=>127, 'MAN'=>89,
        'Maserati'=>42, 'Maxus'=>154, 'Mazda'=>44, 'Mercedes-Benz'=>45, 'Mercedes'=>45,
        'MG'=>130, 'Mini'=>47, 'Mitsubishi'=>48, 'NIO'=>133, 'Nissan'=>49, 'Opel'=>50,
        'Peugeot'=>51, 'Polestar'=>97, 'Porsche'=>54, 'Renault'=>55, 'Saab'=>58,
        'Seat'=>60, 'Skoda'=>61, 'Smart'=>62, 'SsangYong'=>63, 'Suzuki'=>65, 'Tesla'=>84,
        'Toyota'=>66, 'VinFast'=>175, 'Volkswagen'=>67, 'Volvo'=>68, 'XPENG'=>176,
    ];

    // Resolve a display brand name to its eCarsTrade mark id (or null).
    private function markId(string $brand): ?int
    {
        $brand = trim($brand);
        if (isset(self::MARK_IDS[$brand])) return self::MARK_IDS[$brand];
        // Case-insensitive fallback.
        foreach (self::MARK_IDS as $name => $id) {
            if (strcasecmp($name, $brand) === 0) return $id;
        }
        return null;
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
        return stripos($url, 'ecarstrade.com') !== false || stripos($url, 'e-carstrade.com') !== false;
    }

    // -- Auth -------------------------------------------------------------

    // Read the logged-in cookie from .env (ECARSTRADE_COOKIE).
    private function loadCookie(): string
    {
        static $cookie = null;
        if ($cookie !== null) return $cookie;
        $cookie = '';
        $root = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 4);
        $envFile = rtrim((string)$root, '/\\') . '/.env';
        if (is_file($envFile)) {
            $env = file_get_contents($envFile);
            if (preg_match('/ECARSTRADE_COOKIE=(.+)/', $env, $m)) $cookie = trim($m[1]);
        }
        return $cookie;
    }

    private function searchHeaders(string $referer): array
    {
        return [
            'Accept: */*',
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $referer,
            'Cookie: ' . $this->loadCookie(),
        ];
    }

    // -- Search -----------------------------------------------------------

    public function searchByFilter(array $criteria): array
    {
        $cookie = $this->loadCookie();
        if ($cookie === '') {
            $this->logError('eCarsTrade: no ECARSTRADE_COOKIE in .env — cannot authenticate');
            return [];
        }

        // eCarsTrade honours perpage (10/20/50/100 — verified). Page through with
        // start += actual returned count, stopping when a page brings no new ids.
        $perPage   = 100;       // max the server allows per page
        $maxPages  = 200;       // safety cap (≈20000 cars)
        $referer   = self::BASE_URL . '/search';
        // No auction_type filter: a buy-now ("Купить сразу") price can exist on
        // ANY type (stock / bid / open / fix / bid_or_fix). We instead keep only
        // cars that actually expose a buy-now price (checked at mapping below) —
        // restricting to fix+bid_or_fix wrongly dropped most buyable cars.
        $filterQs  = $this->buildFilterQuery($criteria);

        // Client-side filters for fields the URL API doesn't reliably take.
        // model (no model-id) + gearbox come from the card, so filter on them.
        // body_type isn't in the card (only the detail page), so we can't filter
        // it at search time — it's left to the caller after enrichment.
        $wantModel = mb_strtolower(trim((string)($criteria['model'] ?? '')), 'UTF-8');
        $wantGear  = trim((string)($criteria['gearbox'] ?? ''));
        // eCarsTrade ignores km/price/year URL params (verified — they don't
        // narrow results), so we enforce these ranges OUR side from the card data.
        $kmMin    = !empty($criteria['km_min'])    ? (int)$criteria['km_min']    : null;
        $kmMax    = !empty($criteria['km_max'])    ? (int)$criteria['km_max']    : null;
        $priceMin = !empty($criteria['price_min']) ? (float)$criteria['price_min'] : null;
        $priceMax = !empty($criteria['price_max']) ? (float)$criteria['price_max'] : null;
        $yearMin  = !empty($criteria['year_from']) ? (int)$criteria['year_from'] : null;
        $yearMax  = !empty($criteria['year_to'])   ? (int)$criteria['year_to']   : null;

        // The slow part is the network: each page (~3MB base64) takes ~2s and a
        // big brand has 8+ pages. Fetch pages in PARALLEL batches (curl_multi):
        // request a batch of consecutive offsets at once, process, and if the
        // last page was still full, fetch the next batch.
        $batchSize = 8;          // pages fetched simultaneously
        $results = [];
        $seen = [];
        $start = 0;
        $done = false;
        for ($round = 0; $round < $maxPages && !$done; $round += $batchSize) {
            // Build this batch of page URLs.
            $urls = [];
            for ($b = 0; $b < $batchSize; $b++) {
                $offset = $start + $b * $perPage;
                $params = http_build_query([
                    'request_type' => 'cars', 'start' => $offset, 'perpage' => $perPage,
                    'sort' => 'on_site.desc', 'only_next_available_car' => 'false',
                ]);
                $urls[$offset] = self::API_SEARCH . '?' . $params . '&' . $filterQs;
            }
            $bodies = $this->multiGet($urls, $referer);

            $batchHadFull = false;
            foreach (array_keys($urls) as $offset) {
                $body = $bodies[$offset] ?? '';
                if ($body === '') continue;
                $cards = $this->splitStreamedJson($body);
                if (!$cards) { $done = true; continue; }
                if (count($cards) >= $perPage) $batchHadFull = true;

                foreach ($cards as $obj) {
                    $carId = (string)($obj['car_id'] ?? '');
                    if ($carId === '' || isset($seen[$carId])) continue;
                    $seen[$carId] = true;
                    $html = $this->decodeResult($obj['result'] ?? '');
                    if ($html === '') continue;
                    $mapped = $this->parseCarCard($html, $carId);
                    if (!$mapped) continue;
                    if (($mapped['price_source'] ?? null) === null) continue; // buy-now only
                    if ($wantModel !== '') {
                        $hay = mb_strtolower(($mapped['model'] ?? '') . ' ' . ($mapped['title'] ?? ''), 'UTF-8');
                        if (mb_strpos($hay, $wantModel) === false) continue;
                    }
                    if ($wantGear !== '' && ($mapped['gearbox'] ?? '') !== $wantGear) continue;
                    $km = (int)($mapped['km'] ?? 0);
                    if ($kmMin !== null && $km > 0 && $km < $kmMin) continue;
                    if ($kmMax !== null && $km > 0 && $km > $kmMax) continue;
                    $pr = (float)($mapped['price_source'] ?? 0);
                    if ($priceMin !== null && $pr > 0 && $pr < $priceMin) continue;
                    if ($priceMax !== null && $pr > 0 && $pr > $priceMax) continue;
                    $yr = (int)($mapped['year'] ?? 0);
                    if ($yearMin !== null && $yr > 0 && $yr < $yearMin) continue;
                    if ($yearMax !== null && $yr > 0 && $yr > $yearMax) continue;
                    $results[] = $this->normalizeCarData($mapped);
                }
            }

            // Stop once a batch wasn't completely full (we've reached the end).
            if (!$batchHadFull) $done = true;
            $start += $batchSize * $perPage;
        }

        return $results;
    }

    // Fetch several URLs in parallel (curl_multi). Returns [key => body].
    private function multiGet(array $urls, string $referer): array
    {
        $mh = curl_multi_init();
        $handles = [];
        $headers = $this->searchHeaders($referer);
        foreach ($urls as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_TIMEOUT        => 40,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER     => $headers,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running && $status === CURLM_OK);

        $out = [];
        foreach ($handles as $key => $ch) {
            $out[$key] = (string)curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        return $out;
    }

    // Returns ['alive'=>bool, 'status'=>int, 'reason'=>string].
    public function pingSession(): array
    {
        $cookie = $this->loadCookie();
        if ($cookie === '') {
            return ['alive' => false, 'status' => 0, 'reason' => 'no_cookie'];
        }

        // The cheapest authenticated call: the search endpoint with perpage=1.
        // Same shape parsing already uses, so the server treats it as ordinary
        // logged-in activity (it touches/extends the PHP session like any page).
        $params = http_build_query([
            'request_type' => 'cars', 'start' => 0, 'perpage' => 1,
            'sort' => 'on_site.desc', 'only_next_available_car' => 'false',
        ]);
        $url = self::API_SEARCH . '?' . $params;
        $response = $this->httpRequest($url, [
            'headers' => $this->searchHeaders(self::BASE_URL . '/search'),
        ]);
        $status = (int)($response['status'] ?? 0);
        $body   = (string)($response['body'] ?? '');

        // A logged-in session returns the streamed car JSON. An expired/guest
        // session typically answers non-200, empty, or an HTML login page.
        if ($status !== 200 || $body === '') {
            return ['alive' => false, 'status' => $status, 'reason' => 'http_' . $status];
        }
        // Look for the streamed-card shape ({"car_id":...) — proof the API
        // answered as for a logged-in user, not a login/HTML redirect.
        $looksLikeCards = (stripos($body, '"car_id"') !== false)
                          || (stripos($body, '"status"') !== false && stripos($body, '"result"') !== false);
        if (!$looksLikeCards) {
            return ['alive' => false, 'status' => $status, 'reason' => 'non_card_body'];
        }
        return ['alive' => true, 'status' => $status, 'reason' => 'ok'];
    }

    public function collectModelsForBrand(string $brand): array
    {
        $cookie = $this->loadCookie();
        if ($cookie === '') return [];
        $markId = $this->markId($brand);
        if (!$markId) return [];

        $perPage = 100;
        $referer = self::BASE_URL . '/search';
        $models = [];
        $seen = [];
        $start = 0;
        for ($page = 0; $page < 200; $page++) {
            $params = http_build_query([
                'request_type' => 'cars', 'start' => $start, 'perpage' => $perPage,
                'sort' => 'on_site.desc', 'only_next_available_car' => 'false',
            ]);
            // No price/model/auction filter — we want every car of this mark.
            $url = self::API_SEARCH . '?' . $params . '&mark%5B%5D=' . $markId . '&power_value=kw';
            $response = $this->httpRequest($url, ['headers' => $this->searchHeaders($referer)]);
            if (($response['status'] ?? 0) !== 200 || empty($response['body'])) break;

            $cards = $this->splitStreamedJson($response['body']);
            if (!$cards) break;
            $newOnPage = 0;
            foreach ($cards as $obj) {
                $carId = (string)($obj['car_id'] ?? '');
                if ($carId === '' || isset($seen[$carId])) continue;
                $seen[$carId] = true;
                $newOnPage++;
                $html = $this->decodeResult($obj['result'] ?? '');
                if ($html === '') continue;
                $mapped = $this->parseCarCard($html, $carId);
                $m = trim((string)($mapped['model'] ?? ''));
                if ($m !== '') $models[$m] = ($models[$m] ?? 0) + 1;
            }
            if ($newOnPage === 0) break;
            $start += count($cards);
            $this->randomDelay();
        }
        ksort($models, SORT_NATURAL | SORT_FLAG_CASE);
        return $models;
    }

    // Translate our internal criteria to eCarsTrade query params. Brand/model
    // use eCarsTrade mark/model IDs (from marksData.php); for now we pass the
    // text search where IDs are unknown. Numeric ranges map directly.
    private function buildFilterQuery(array $criteria): string
    {
        $parts = [];
        // Mark: prefer an explicit id, else resolve the brand name → eCarsTrade id.
        $markId = !empty($criteria['mark_id']) ? (int)$criteria['mark_id']
            : ($this->markId((string)($criteria['brand'] ?? '')) ?? 0);
        if ($markId > 0) {
            $parts[] = 'mark%5B%5D=' . $markId;
        } elseif (!empty($criteria['brand'])) {
            // Unknown brand → free-text search fallback.
            $parts[] = 'search=' . rawurlencode(trim($criteria['brand'] . ' ' . ($criteria['model'] ?? '')));
        }
        // Model: eCarsTrade has no model-id, but its free-text "search" narrows
        // server-side — so when a model is chosen we ALSO pass search=Model. That
        // avoids downloading the whole brand (547 Audi) to find one model; we
        // still re-filter by title our side (the text search can be broad).
        if ($markId > 0 && !empty($criteria['model'])) {
            $parts[] = 'search=' . rawurlencode(trim((string)$criteria['model']));
        }
        // Year → regist/regist_to ; Km → kilom/kilom_to ; Price → price/price_to.
        // Field names verified from the /search form. (km were silently ignored
        // before because we sent "mileage_*" — the real name is "kilom".)
        if (!empty($criteria['year_from'])) $parts[] = 'regist=' . (int)$criteria['year_from'];
        if (!empty($criteria['year_to']))   $parts[] = 'regist_to=' . (int)$criteria['year_to'];
        if (!empty($criteria['km_min']))    $parts[] = 'kilom=' . (int)$criteria['km_min'];
        if (!empty($criteria['km_max']))    $parts[] = 'kilom_to=' . (int)$criteria['km_max'];
        if (!empty($criteria['price_min'])) $parts[] = 'price=' . (int)$criteria['price_min'];
        if (!empty($criteria['price_max'])) $parts[] = 'price_to=' . (int)$criteria['price_max'];
        // Fuel: one or more internal codes (CSV) → eCarsTrade values, each as its
        // own fuel[] param (Diesel/Petrol/Electric...). The API ORs them together.
        foreach ($this->splitCodes($criteria['fuel_type'] ?? null) as $code) {
            $fuelVal = $this->fuelFilterValue($code);
            if ($fuelVal) $parts[] = 'fuel%5B%5D=' . rawurlencode($fuelVal);
        }
        // Gearbox: gearbox[]=Automatic|Manual|Semi-automatic (eCarsTrade has no CVT).
        $gearVal = $this->gearboxFilterValue($criteria['gearbox'] ?? null);
        if ($gearVal) $parts[] = 'gearbox%5B%5D=' . rawurlencode($gearVal);
        // Body: category[]=Saloon|Estate Car|SUV/... (eCarsTrade's own values).
        $catVal = $this->categoryFilterValue($criteria['body_type'] ?? null);
        if ($catVal) $parts[] = 'category%5B%5D=' . rawurlencode($catVal);
        $parts[] = 'power_value=kw';
        return implode('&', $parts);
    }

    // Split a CSV fuel_type ("benzina,diesel") into a clean list of codes.
    private function splitCodes(?string $csv): array
    {
        if (!$csv) return [];
        return array_values(array_filter(array_map('trim', explode(',', $csv)), fn($c) => $c !== ''));
    }

    // Internal fuel code → eCarsTrade filter value (from /search checkboxes).
    private function fuelFilterValue(?string $code): ?string
    {
        if (!$code) return null;
        $map = [
            'diesel' => 'Diesel', 'benzina' => 'Petrol', 'electric' => 'Electric',
            'hybrid' => 'Hybrid (petrol/electric)', 'diesel_hybrid' => 'Hybrid (diesel/electric)',
            'lpg' => 'LPG', 'gasoline_cng' => 'Natural Gas',
        ];
        return $map[$code] ?? null;
    }

    // Internal gearbox code → eCarsTrade value. eCarsTrade has no CVT (it's an
    // automatic variant), so CVT maps to Automatic.
    private function gearboxFilterValue(?string $code): ?string
    {
        if (!$code) return null;
        $map = [
            'automat' => 'Automatic', 'manual' => 'Manual',
            'semi-auto' => 'Semi-automatic', 'cvt' => 'Automatic',
        ];
        return $map[$code] ?? null;
    }

    // Internal body code → eCarsTrade category[] value (verified from /search).
    private function categoryFilterValue(?string $code): ?string
    {
        if (!$code) return null;
        $map = [
            'sedan'       => 'Saloon',
            'wagon'       => 'Estate Car',
            'suv'         => 'SUV/Off-road Vehicle/Pickup Truck',
            'pickup'      => 'SUV/Off-road Vehicle/Pickup Truck',
            'hatchback'   => 'Small Car',
            'coupe'       => 'Sports Car/Coupe',
            'convertible' => 'Convertible/Roadster',
            'minivan'     => 'Van/Minibus',
            'van'         => 'Van/Minibus',
        ];
        return $map[$code] ?? null;
    }

    // The endpoint concatenates JSON objects (sometimes prefixed with "{}").
    // Split them into decoded arrays by scanning balanced braces.
    private function splitStreamedJson(string $body): array
    {
        $out = [];
        $len = strlen($body);
        $depth = 0; $start = -1; $inStr = false; $esc = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $body[$i];
            if ($inStr) {
                if ($esc) { $esc = false; }
                elseif ($ch === '\\') { $esc = true; }
                elseif ($ch === '"') { $inStr = false; }
                continue;
            }
            if ($ch === '"') { $inStr = true; continue; }
            if ($ch === '{') { if ($depth === 0) $start = $i; $depth++; }
            elseif ($ch === '}') {
                $depth--;
                if ($depth === 0 && $start >= 0) {
                    $json = substr($body, $start, $i - $start + 1);
                    $obj = json_decode($json, true);
                    // Skip the empty "{}" separators; keep real car objects.
                    if (is_array($obj) && !empty($obj['car_id'])) $out[] = $obj;
                    $start = -1;
                }
            }
        }
        return $out;
    }

    // The card HTML arrives base64-encoded (URL-safe variants tolerated).
    private function decodeResult(string $b64): string
    {
        $b64 = trim($b64);
        if ($b64 === '') return '';
        $b64 = strtr($b64, '-_', '+/');
        $decoded = base64_decode($b64, false);
        return $decoded !== false ? $decoded : '';
    }

    // -- Card parsing -----------------------------------------------------

    // Extract one car's fields from its decoded card HTML.
    private function parseCarCard(string $html, string $carId): ?array
    {
        $title = $this->matchOne('/<a href="\/cars\/' . preg_quote($carId, '/') . '"[^>]*>\s*<span>(.*?)<\/span>/s', $html);
        if ($title === '') {
            $title = $this->matchOne('/<div class="item-title[^"]*"[^>]*>.*?<span>(.*?)<\/span>/s', $html);
        }
        $title = $this->cleanText($title);

        // Brand/model from the "#id - Make Model" subtitle line.
        $sub = $this->cleanText($this->matchOne('/#' . preg_quote($carId, '/') . '\s*-\s*([^<]+)</s', $html));
        [$brand, $model] = $this->splitBrandModel($sub, $title);
        // Subtitle model is sometimes useless ("Other") — fall back to the title.
        if ($model === '' || $this->isUselessModel($model)) {
            $fromTitle = $this->modelFromTitle($brand, $title);
            if ($fromTitle !== '') $model = $fromTitle;
        }

        // Feature blocks: date, gearbox, mileage, fuel, hp.
        $regDate = $this->matchOne('/fa-calendar-alt.*?feature-value">\s*([0-9]{2}\/[0-9]{4})/s', $html);
        $year = ($regDate && preg_match('#/(\d{4})#', $regDate, $m)) ? (int)$m[1] : null;

        $kmRaw = $this->matchOne('/fa-tachometer-alt.*?feature-value">\s*([0-9\s\x{00a0}.,]+)\s*(?:км|km)/su', $html);
        $km = $kmRaw !== '' ? (int)preg_replace('/\D/', '', $kmRaw) : null;

        $hpRaw = $this->matchOne('/fa-bolt.*?feature-value">\s*([0-9]+)\s*Hp/s', $html);
        $hp = $hpRaw !== '' ? (int)$hpRaw : null;

        $fuelRaw = $this->cleanText($this->matchOne('/fa-gas-pump.*?<span[^>]*>\s*([^<,]+?)\s*<\/span>/s', $html));
        $gearRaw = $this->cleanText($this->matchOne('/fa-cog.*?feature-value">\s*([^<]+?)\s*<\/div>/s', $html));

        // Buy-now price = the "Купить сразу" button. It exists on several auction
        // types (stock/bid/open/fix), so match the buy button by its data-price
        // (the class carries "fix" only on fixed-price listings). The buy button
        // always has data-price + "fix" in its class chain OR contains "Купить".
        $price = $this->extractBuyNowPrice($html);

        // Country of origin (flag image): /images/flags/h40/be.png
        $country = strtolower($this->matchOne('#/images/flags/h40/([a-z]{2})\.png#i', $html));

        // Photos: data-src="/thumbnails/.../photo_000/260x0__r.jpg" → full size.
        // Pass carId so recommended-listing photos of other cars are filtered out.
        $images = $this->collectPhotos($html, $carId);

        // Auction end (UTC): data-utc-time="2026-06-09T13:25:00Z".
        $auctionEnd = $this->matchOne('/data-utc-time="([^"]+)"/', $html);

        // The card has no engine-volume feature block (only detail does), but the
        // title often carries the litres ("... 2.0 TDI", "1.5 dCi", "1998cc"). Pull
        // it from there so cards have a cc without needing the detail page; null
        // when the title has no clear litrage (then it's filled later at enrich).
        $engineVol = $this->engineVolumeFromTitle($title);

        return [
            'source_id'  => $carId,
            'source_url' => self::CAR_WEB_URL . $carId,
            'vin'        => null, // not in the card; comes from detail
            'brand'      => $brand,
            'model'      => $model,
            'year'       => $year,
            'km'         => $km,
            'fuel_type'  => $this->fuelFromTitle($title) ?? $this->normalizeFuel($fuelRaw),
            'gearbox'    => $this->normalizeGearbox($gearRaw),
            'power_hp'   => $hp,
            'engine_volume' => $engineVol,
            'drive_type' => $this->inferDriveFromTitle($title),
            'body_type'  => null,
            'price_source' => $price,
            'price_source_currency' => 'EUR',
            'title'      => $title,
            'images'     => $images,
            'raw_data'   => [
                'car_id'      => $carId,
                'title'       => $title,
                'country'     => $country,
                'auction_end' => $auctionEnd,
                'reg_date'    => $regDate,
            ],
        ];
    }

    // Collect distinct photo URLs from the card, upgraded to a large size.
    // Collect distinct full-size photo URLs. The page references thumbnails
    //   /thumbnails/carsphotos/{range}/{car}/photo_000/260x0__r.jpg
    // but the original (134KB vs 31KB) lives at
    //   /carsphotos/{range}/{car}/photo_000.jpg   ← we use this for top quality.
    //
    // IMPORTANT: the HTML (card + detail page) also embeds photos of OTHER cars
    // (similar/recommended listings), so a blind match leaks ~5 foreign cars' pics
    // into the gallery. When the car id is known we keep ONLY photos whose {car}
    // path segment equals it.
    private function collectPhotos(string $html, ?string $carId = null): array
    {
        if (!preg_match_all('#/thumbnails/carsphotos/([^"/]+)/(\d+)/(photo_\d+)/\d+x\d+__r\.jpg#i', $html, $m, PREG_SET_ORDER)) {
            return [];
        }
        $carId = $carId !== null ? trim($carId) : null;
        $seen = []; $out = [];
        foreach ($m as $row) {
            [, $range, $car, $photo] = $row;
            // Drop photos that belong to a different car (recommended listings).
            if ($carId !== null && $carId !== '' && (string)$car !== $carId) continue;
            $orig = "/carsphotos/{$range}/{$car}/{$photo}.jpg";
            if (isset($seen[$orig])) continue;
            $seen[$orig] = true;
            $out[] = self::BASE_URL . $orig;
        }
        return $out;
    }

    // Extract the buy-now ("Купить сразу") price from a card, across all auction
    // types. The buy button is an <a class="item-bid-button fix ..."> carrying
    // data-price="NNNN" and the text "Купить ... <span>NNNN€</span>". We read the
    // data-price of the FIX buy button; fall back to the "Купить ... N€" text.
    private function extractBuyNowPrice(string $html): ?float
    {
        // 1) The buy button: <a ... class="...item-bid-button fix..." data-price="11550">
        if (preg_match('/<a\b[^>]*\bclass="[^"]*\bfix\b[^"]*item-bid-button[^"]*"[^>]*\bdata-price="([0-9]+)"/i', $html, $m)
         || preg_match('/<a\b[^>]*\bclass="[^"]*item-bid-button[^"]*\bfix\b[^"]*"[^>]*\bdata-price="([0-9]+)"/i', $html, $m)
         || preg_match('/<a\b[^>]*\bdata-price="([0-9]+)"[^>]*\bclass="[^"]*item-bid-button[^"]*\bfix\b/i', $html, $m)) {
            $p = (float)$m[1];
            return $p > 0 ? $p : null;
        }
        // 2) Fallback: a "Купить ... 11550€" buy button text.
        if (preg_match('/item-bid-button[^>]*\bfix\b[^>]*>.*?([0-9][0-9\s.\x{00a0}]*)\s*(?:&euro;|€)/su', $html, $m)) {
            $p = (float)preg_replace('/\D/', '', $m[1]);
            return $p > 0 ? $p : null;
        }
        return null; // no buy-now price → pure auction, skip
    }

    private function matchOne(string $re, string $html): string
    {
        if (preg_match($re, $html, $m)) {
            for ($i = 1; $i < count($m); $i++) {
                if (isset($m[$i]) && $m[$i] !== '') return $m[$i];
            }
        }
        return '';
    }

    private function cleanText(?string $s): string
    {
        $s = html_entity_decode((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = str_replace("\xc2\xa0", ' ', $s); // nbsp
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    // "BMW 216 Gran Tourer" → ['BMW', '216 Gran Tourer'].
    private function splitBrandModel(string $sub, string $title): array
    {
        $src = $sub !== '' ? $sub : $title;
        $src = trim($src);
        if ($src === '') return ['', ''];
        $parts = preg_split('/\s+/', $src, 2);
        $brand = $parts[0] ?? '';
        $model = $parts[1] ?? '';
        return [$this->normalizeMakeName($brand), trim($model)];
    }

    // eCarsTrade's spec model is sometimes a placeholder, not a real model.
    private function isUselessModel(string $model): bool
    {
        $m = mb_strtolower(trim($model), 'UTF-8');
        return $m === '' || in_array($m, ['other', 'others', 'autre', 'другое', 'n/a', '-'], true);
    }

    // Pull a usable model code from the H1 title when the spec model is useless.
    // Title example: "BMW 1 HATCH DIESEL - 2019 116 d 116hp (EU6AP) 5d". We want
    // the engine/trim code ("116") which match_model maps to the series (Seria 1).
    private function modelFromTitle(string $brand, string $title): string
    {
        $t = trim($title);
        if ($t === '') return '';
        // Drop the "...hp" power tail and parenthised emission codes so they don't
        // get picked as the model.
        $clean = preg_replace('/\b\d+\s*hp\b/iu', ' ', $t);
        $clean = preg_replace('/\([^)]*\)/u', ' ', $clean);
        $tokens = preg_split('/\s+/', trim($clean));

        $bodyFuel = ['hatch','suv','coupe','combi','sedan','diesel','petrol','hybrid',
                     'auto','cabrio','break','electric','5d','3d','4d','2019','d'];
        // Prefer an engine/trim code like 116/320d/X5/Q3/A4/GLC — best for
        // match_model. Scan the WHOLE title (the real code "116" often sits after
        // the year, e.g. "... - 2019 116 d ..."), skip a bare year (1990-2099).
        foreach ($tokens as $tok) {
            if (preg_match('/^(19|20)\d{2}$/', $tok)) continue;     // year
            if (preg_match('/^\d{2,3}[a-z]?$/iu', $tok)) return $tok;            // 116, 320, 520d
            if (preg_match('/^[A-Za-z]{1,2}\d{1,3}[A-Za-z]?$/u', $tok)) return $tok; // X5, Q3, A4, GLC300
        }
        // Fallback: first token after the brand that isn't a body/fuel/year word.
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

    // -- Detail / availability -------------------------------------------

    public function fetchByUrl(string $url): ?array
    {
        if (preg_match('#/cars/(\d+)#', $url, $m)) {
            return $this->fetchById($m[1]);
        }
        return null;
    }

    // Fetch the detail page /cars/{id} to enrich a card with VIN + full gallery
    // (the card has ~5 photos, the detail page ~20). Returns a normalized array
    // with at least vin + images; null on failure.
    public function fetchById(string $sourceId): ?array
    {
        $sourceId = preg_replace('/\D/', '', (string)$sourceId);
        if ($sourceId === '') return null;
        $cookie = $this->loadCookie();
        if ($cookie === '') {
            $this->logError('eCarsTrade fetchById: no ECARSTRADE_COOKIE in .env');
            return null;
        }

        $url = self::CAR_WEB_URL . $sourceId;
        $response = $this->httpRequest($url, [
            'headers' => ['Accept: text/html,*/*', 'Cookie: ' . $cookie],
        ]);
        $status = $response['status'] ?? 0;
        if ($status !== 200 || empty($response['body'])) {
            $this->logError('eCarsTrade detail HTTP failure', ['id' => $sourceId, 'status' => $status]);
            return null;
        }
        $body = $response['body'];

        // Two data sources on the detail page:
        //  1) JSON-LD <script type="application/ld+json"> — clean structured data
        //     (fuelType, bodyType, vehicleTransmission, mileage, brand).
        //  2) item_description_item rows: VIN, colour, hp, engine, seats, drive.
        $ld = $this->extractJsonLd($body);

        // VIN from the spec row: <i fa-barcode></i>VIN</span> <strong>VIN</strong>.
        $vin = $this->detailField($body, 'VIN');
        if ($vin && preg_match('/([A-HJ-NPR-Z0-9]{17})/i', $vin, $mm)) $vin = strtoupper($mm[1]);
        else $vin = null;

        $fuel = $ld['fuelType'] ?? $this->detailField($body, 'Тип топлива');
        // "Тип коробки передач" = type (Автоматическая); "Коробка передач" alone
        // is the gear COUNT ("7"), so prefer JSON-LD / the typed row.
        $gear = $ld['vehicleTransmission'] ?? $this->detailField($body, 'Тип коробки передач');
        $color = $this->detailField($body, 'Цвет');
        // Prefer the Категория row (Внедорожник/Седан...) — it maps cleanly; fall
        // back to JSON-LD bodyType ("Personenauto" is too generic).
        $category = $this->detailField($body, 'Категория');
        $body_type = $category !== '' ? $category : ($ld['bodyType'] ?? '');
        $seatsRaw = $this->detailField($body, 'Мест');
        $hpRaw = $this->detailField($body, 'Мощность'); // "218 Hp 160 kW"
        $engineRaw = $this->detailField($body, 'Объем двигателя'); // "1998 CC" (no ё)
        // Drive isn't a row; infer from category ("Внедорожник" = SUV → 4x4).
        $driveRaw = $this->detailField($body, 'Категория');

        $hp = ($hpRaw && preg_match('/(\d+)\s*Hp/i', $hpRaw, $m2)) ? (int)$m2[1] : null;
        $engineVol = $this->parseEngineVolume($engineRaw);
        $seats = ($seatsRaw && preg_match('/(\d+)/', $seatsRaw, $m3)) ? (int)$m3[1] : null;

        // Filter to THIS car's photos only (the detail page also embeds similar
        // listings' pictures, which would otherwise leak into the gallery).
        $images = $this->collectPhotos($body, $sourceId);

        // Identity (needed for direct-link import, where there's no card):
        // brand/model from "Марка и модель", title + brand + mileage + reg from
        // JSON-LD, price from the buy-now button.
        $markModel = $this->detailField($body, 'Марка и модель'); // "BMW X1"
        $title = $this->cleanText($ld['name'] ?? $markModel);
        [$brand, $model] = $this->splitBrandModel($markModel, $title);
        if ($brand === '' && !empty($ld['brand']['name'])) {
            $brand = $this->normalizeMakeName($this->cleanText($ld['brand']['name']));
        }
        // eCarsTrade's "Марка и модель" spec is sometimes useless ("BMW Other"):
        // the real model lives in the H1 title (e.g. "BMW 1 HATCH ... 116 d ...").
        // When the spec model is empty/"Other", pull the model from the title.
        if ($model === '' || $this->isUselessModel($model)) {
            $fromTitle = $this->modelFromTitle($brand, $title);
            if ($fromTitle !== '') $model = $fromTitle;
        }
        $km = null;
        if (!empty($ld['mileageFromOdometer']['value'])) {
            $km = (int)preg_replace('/\D/', '', (string)$ld['mileageFromOdometer']['value']);
        }
        $year = null;
        $reg = $ld['dateVehicleFirstRegistered'] ?? $this->detailField($body, 'Дата первой регистрации');
        if ($reg && preg_match('#(\d{4})#', $reg, $ym)) $year = (int)$ym[1];
        $country = strtolower($this->matchOne('#/images/flags/h40/([a-z]{2})\.png#i', $body));
        // Drive type: SUV category → 4x4; else infer from title (xDrive/quattro/
        // 4MATIC → 4x4, sDrive → rwd). Unknown stays null (no front-wheel guess).
        $drive = $this->normalizeDrive($driveRaw);
        if (!$drive) $drive = $this->inferDriveFromTitle($title . ' ' . $model);
        // Buy-now (fix) price on the detail page.
        $price = null;
        if (preg_match('/item-bid-button[^>]*fix[^>]*>.*?([0-9][0-9\s.,]*)\s*(?:&euro;|€)/su', $body, $pm)
            || preg_match('/data-price="([0-9]+)"[^>]*item-bid-button[^>]*fix/su', $body, $pm)) {
            $price = (float)preg_replace('/[^\d.]/', '', str_replace([' ', ','], '', $pm[1]));
        }

        return $this->normalizeCarData([
            'source_id'  => $sourceId,
            'source_url' => $url,
            'vin'        => $vin,
            'brand'      => $brand,
            'model'      => $model,
            'year'       => $year,
            'km'         => $km,
            'title'      => $title,
            'fuel_type'  => $this->fuelFromTitle($title) ?? $this->normalizeFuel($fuel),
            'gearbox'    => $this->normalizeGearbox($gear),
            'color'      => $this->normalizeColor($color),
            'body_type'  => $this->normalizeBodyType($body_type),
            'power_hp'   => $hp,
            'engine_volume' => $engineVol,
            'seats'      => $seats,
            'drive_type' => $drive,
            'price_source' => $price,
            'price_source_currency' => 'EUR',
            'images'     => $images,
            'raw_data'   => ['car_id' => $sourceId, 'vin' => $vin, 'country' => $country, 'ld' => $ld],
        ]);
    }

    // Pull the first JSON-LD Car object from the page.
    private function extractJsonLd(string $html): array
    {
        if (preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m)) {
            foreach ($m[1] as $json) {
                $data = json_decode(trim($json), true);
                if (is_array($data) && (($data['@type'] ?? '') === 'Car' || isset($data['vehicleTransmission']))) {
                    return $data;
                }
            }
        }
        return [];
    }

    // Read a spec row value. The icon before the label may be <i> or
    // <span><img alt="LABEL"></span> — and that img's alt repeats the label, so
    // we anchor on the label being the TEXT right before </span><strong>:
    //   ...>LABEL</span> <strong>VALUE</strong>
    // ([^<>"]* before the label lets the icon markup precede it; the label must
    // sit directly before the closing </span>, never inside an attribute.)
    private function detailField(string $html, string $label): string
    {
        $q = preg_quote($label, '#');
        $re = '#' . $q . '\s*</span>\s*<strong[^>]*>(.*?)</strong>#su';
        if (preg_match($re, $html, $m)) {
            $val = $this->cleanText($m[1]);
            // eCarsTrade shows "n/a" when a field is unknown — treat as empty.
            if (strcasecmp($val, 'n/a') === 0 || $val === '-') return '';
            return $val;
        }
        return '';
    }

    private function engineVolumeFromTitle(?string $title): ?int
    {
        if (!$title) return null;
        
        if (preg_match('/\b([0-8][.,]\d)\s*(?:l\b|litre|liter|tdi|tsi|tfsi|fsi|gdi|dci|hdi|cdi|crdi|bluehdi|tce|thp|vti|mpi|ecoboost|dCi)/iu', $title, $m)) {
            $l = (float)str_replace(',', '.', $m[1]);
            if ($l >= 0.6 && $l <= 8.0) return (int)round($l * 1000);
        }
        // Explicit cc / cm3 (900–8000).
        if (preg_match('/\b(\d{3,4})\s*(?:cc|cm3|cm³|см3|см³)\b/iu', $title, $m)) {
            $cc = (int)$m[1];
            if ($cc >= 600 && $cc <= 8000) return $cc;
        }
        return null;
    }

    // "1998 CC" / "1499 см³" / "1.5 L" / "1498" → cc int (or null).
    private function parseEngineVolume(string $raw): ?int
    {
        if ($raw === '') return null;
        $raw = trim(str_replace("\xc2\xa0", ' ', $raw));
        if (preg_match('/(\d[\d\s]*)\s*(?:cc|см|cm)/iu', $raw, $m)) {
            return (int)preg_replace('/\D/', '', $m[1]);
        }
        if (preg_match('/(\d+[.,]\d+)\s*l/iu', $raw, $m)) {
            return (int)round((float)str_replace(',', '.', $m[1]) * 1000);
        }
        if (preg_match('/(\d{3,5})/', str_replace(' ', '', $raw), $m)) return (int)$m[1];
        return null;
    }

    public function checkAvailability(string $sourceId): bool
    {
        $cookie = $this->loadCookie();
        if ($cookie === '') return true;
        $url = self::CAR_WEB_URL . rawurlencode($sourceId);
        $response = $this->httpRequest($url, ['headers' => ['Cookie: ' . $cookie]]);
        $status = $response['status'] ?? 0;
        if ($status === 404) return false;
        if ($status !== 200 || empty($response['body'])) return true; // hiccup → keep
        // Sold / closed cars show a clear marker on the detail page.
        $body = $response['body'];
        if (stripos($body, 'data-visible-type="close"') !== false
            && stripos($body, 'item-bid-button') === false) {
            return false;
        }
        // Auction finished (end time in the past) → no longer biddable. The
        // data-utc-time="...Z" value is already correct UTC — compare as-is.
        if (preg_match('/data-utc-time="([^"]+)"/', $body, $m)) {
            $ts = strtotime($m[1]);
            if ($ts && $ts < time()) return false;
        }
        return true;
    }

    // Verify the .env cookie still carries a logged-in session — WITHOUT relying
    // on a VIN. The old check called fetchById and treated "VIN != 17 chars" as
    // "cookie expired", which gave a false "expired" whenever the newest car had
    // no published VIN or was already sold (404). Those are not auth problems.
    //
    // What actually proves we're logged in: an authenticated detail page exposes
    // the buyer-only bits a guest never sees — the VIN spec row and the buy/bid
    // button. A guest page instead redirects/links to login. So we look for those
    // logged-in markers in the HTML, not for a 17-char VIN.
    //
    // We try several recent cars (not just the newest) so one sold/removed car
    // (404) can't masquerade as an expired cookie. Returns:
    //   ['logged_in'=>bool, 'vin'=>?string, 'reason'=>string]
    public function checkSession(array $sourceIds): array
    {
        $cookie = $this->loadCookie();
        if ($cookie === '') {
            return ['logged_in' => false, 'vin' => null, 'reason' => 'no_cookie'];
        }

        $sawCar = false;     // at least one car page loaded (HTTP 200 with body)
        $allGone = true;     // every tested car answered 404 (sold/removed)
        foreach ($sourceIds as $sid) {
            $sid = preg_replace('/\D/', '', (string)$sid);
            if ($sid === '') continue;

            $url = self::CAR_WEB_URL . $sid;
            $response = $this->httpRequest($url, [
                'headers' => [
                    'Accept: text/html,*/*',
                    'Referer: ' . self::BASE_URL . '/search',
                    'Cookie: ' . $cookie,
                ],
            ]);
            $status = (int)($response['status'] ?? 0);
            $body   = (string)($response['body'] ?? '');

            // 404 = this car is gone, not a cookie problem. Try the next one.
            if ($status === 404) { continue; }
            $allGone = false;
            // Network hiccup / non-200 with no body: inconclusive, try the next.
            if ($status !== 200 || $body === '') { continue; }
            $sawCar = true;

            // Logged-in markers: the buy/bid button and the VIN spec row only
            // render for an authenticated buyer; a guest sees a login prompt.
            $hasBuyButton = stripos($body, 'item-bid-button') !== false;
            $hasVinRow    = (bool)preg_match('/>\s*VIN\s*<\/span>/i', $body)
                            || stripos($body, 'fa-barcode') !== false;
            // Guest/expired markers: the page sends us to log in.
            $isGuest = stripos($body, '/login') !== false
                       && !$hasBuyButton && !$hasVinRow;

            if ($hasBuyButton || $hasVinRow) {
                // Pull the VIN as a bonus (may legitimately be absent for this car).
                $vin = $this->detailField($body, 'VIN');
                if ($vin && preg_match('/([A-HJ-NPR-Z0-9]{17})/i', $vin, $mm)) {
                    $vin = strtoupper($mm[1]);
                } else {
                    $vin = null;
                }
                return ['logged_in' => true, 'vin' => $vin, 'reason' => 'ok'];
            }
            if ($isGuest) {
                return ['logged_in' => false, 'vin' => null, 'reason' => 'guest_page'];
            }
            // 200 but no clear marker either way — keep trying other cars.
        }

        // No car confirmed a logged-in marker → treat the cookie as NOT valid.
        // The old code reported "valid" when every tested car was 404 (sold) or when
        // no car loaded — a false positive that let imports run without VINs on an
        // expired cookie. We now require POSITIVE proof (buy button / VIN row) to
        // call it valid; anything short of that is "not authenticated".
        $reason = $allGone ? 'all_404' : ($sawCar ? 'no_marker' : 'inconclusive');
        return ['logged_in' => false, 'vin' => null, 'reason' => $reason];
    }

    // Fetch the equipment list ("Комплектация") from the detail page. Each option
    // is a <span ...option-name...>NAME</span>; high-value ones are preceded by a
    // star icon (fa-star). Some names carry a popover with a long data-content
    // description, so we must take ONLY the visible text (after the final ">"),
    // never the attributes. Returns ['items' => [['label','top'], ...]] ★ first.
    public function fetchEquipment(string $sourceId): ?array
    {
        $cookie = $this->loadCookie();
        $url = self::CAR_WEB_URL . rawurlencode($sourceId);
        // Send the same headers the browser does (Referer + XHR cookie). Without a
        // Referer the car page sometimes answers 403/redirect even with a valid
        // cookie, which surfaced as a misleading "cookie expired?" error.
        $headers = [
            'Accept: text/html,application/xhtml+xml',
            'Referer: ' . self::BASE_URL . '/search',
            'Cookie: ' . $cookie,
        ];
        $response = $this->httpRequest($url, ['headers' => $headers]);
        $status = (int)($response['status'] ?? 0);
        if ($status !== 200 || empty($response['body'])) {
            // 404 = car removed/sold on eCarsTrade (not a cookie issue); log the
            // real status so the cause is clear instead of always blaming cookies.
            $this->logError('eCarsTrade fetchEquipment failed', [
                'source_id' => $sourceId,
                'status'    => $status,
                'body_len'  => strlen($response['body'] ?? ''),
            ]);
            return null;
        }
        $body = $response['body'];

        // Narrow to the equipment section so unrelated spans aren't picked up.
        $start = mb_strpos($body, 'Комплектация');
        $scope = ($start !== false) ? mb_substr($body, $start) : $body;

        $items = [];
        $seen  = [];
        // Match each option-name span WITH its byte offset, so we can look at the
        // HTML right before it to decide whether a star icon precedes it.
        // NOTE: popover spans carry a long data-content="...<img ...>..." that has
        // RAW ">" chars, so [^>]* stops early and the capture leaks attribute text.
        // We defend against that below by keeping only the text after the last ">".
        $re = '/<span\b[^>]*class="[^"]*option-name[^"]*"[^>]*>(.*?)<\/span>/su';
        if (preg_match_all($re, $scope, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $i => $cap) {
                $raw = $cap[0];
                // If attribute text leaked in (a stray '">' from data-content), the
                // real visible label is whatever follows the LAST '>' in the capture.
                if (($gt = strrpos($raw, '>')) !== false) {
                    $raw = substr($raw, $gt + 1);
                }
                // Visible text only: strip any inner tags, decode entities.
                $label = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES, 'UTF-8'));
                $label = preg_replace('/\s+/u', ' ', $label);
                if ($label === '' || $label === ',') continue;
                $key = mb_strtolower($label, 'UTF-8');
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                // Star detection: scan the ~300 chars of HTML just before the span.
                $spanStart = $matches[0][$i][1];
                $ctxFrom = max(0, $spanStart - 300);
                $ctx = substr($scope, $ctxFrom, $spanStart - $ctxFrom);
                $top = (stripos($ctx, 'fa-star') !== false);
                $items[] = ['label' => $label, 'top' => $top];
            }
        }

        // Plain alphabetical — clean to scan (no high-value priority).
        usort($items, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        return ['items' => $items];
    }

    // -- Normalisation ----------------------------------------------------

    private function normalizeMakeName(string $brand): string
    {
        $brand = trim($brand);
        $map = [
            'MERCEDES' => 'Mercedes-Benz', 'MERCEDES-BENZ' => 'Mercedes-Benz',
            'VW' => 'Volkswagen', 'VOLKSWAGEN' => 'Volkswagen',
            'BMW' => 'BMW', 'AUDI' => 'Audi', 'CITROEN' => 'Citroën',
        ];
        $u = mb_strtoupper($brand, 'UTF-8');
        if (isset($map[$u])) return $map[$u];
        // Title-case fallback (BMW stays BMW via map; others → "Audi").
        return ucwords(mb_strtolower($brand, 'UTF-8'));
    }

    // Derive the fuel code from the car TITLE, more reliable than the source's spec
    // category (which mislabels e.g. a petrol "TCe ... mild hybrid" as diesel, or a
    // petrol HEV as "Дизель/Электрический"). Mild-hybrid (48V) resolves to its base
    // engine (taxed as diesel/petrol, not hybrid). Returns null when the title has
    // no fuel hint (caller falls back to normalizeFuel). Order matters.
    private function fuelFromTitle(?string $title): ?string
    {
        if (!$title) return null;
        $t = mb_strtolower($title, 'UTF-8');
        $petrolHint = (strpos($t, 'petrol') !== false || strpos($t, 'gasolin') !== false
            || preg_match('/\btce\b|\btsi\b|\btfsi\b|\bgdi\b|\bvti\b|\bpuretech\b|\bmpi\b/', $t));
        $dieselHint = (strpos($t, 'diesel') !== false
            || preg_match('/\btdi\b|\bcdi\b|\bhdi\b|\bdci\b|\bcrdi\b|\bbluehdi\b/', $t)
            || preg_match('/\d{2,3}d(?![a-z])/', $t)        // 120d, 320d, sDrive16d
            || preg_match('/\d{3}de\b/', $t)               // Mercedes 300de/400de = diesel PHEV
            || preg_match('/\bd\d{3}\b/', $t)              // Land Rover/Jaguar D200/D300 = diesel
            || preg_match('/\b[est]d4\b/', $t));            // Land Rover eD4/SD4/TD4 = diesel
        $isPlugin = (bool)preg_match('/\bplug-?in\b|\bphev\b|e-?tron|\d{3}de\b/', $t);
        $isMild   = (bool)preg_match('/\bmild.?hybrid\b|\bmhev\b/', $t);
        // Full hybrid: "hybrid"/"hev"/"hyb", or Toyota/Lexus "NNNh" (250h/350h).
        $isFullHybrid = !$isMild && (strpos($t, 'hybrid') !== false
            || preg_match('/\bhev\b|\bhyb\b|\b\d{3}h\b/', $t));

        // Mild-hybrid (48V) is taxed as its BASE engine, not as a hybrid.
        if ($isMild) {
            if ($dieselHint) return 'diesel';
            if ($petrolHint) return 'benzina';
            return null;
        }
        // Explicit plug-in → PHEV (diesel or petrol).
        if ($isPlugin) return $dieselHint ? 'diesel_hybrid' : 'hybrid_plugin';
        // Bare full "Hybrid" on a diesel trim is ambiguous (HEV/PHEV) → spec decides.
        if ($isFullHybrid && $dieselHint) return null;
        if ($isFullHybrid) return 'hybrid';
        if (strpos($t, 'electric') !== false || preg_match('/\bev\b|\bbev\b/', $t)) return 'electric';
        if ($dieselHint) return 'diesel';
        if ($petrolHint) return 'benzina';
        if (preg_match('/\blpg\b/', $t)) return 'lpg';
        return null;
    }

    // eCarsTrade shows fuel in the UI language (here RU). Map to internal codes.
    private function normalizeFuel(?string $val): ?string
    {
        if (!$val) return null;
        $k = mb_strtolower(trim($val), 'UTF-8');
        $map = [
            'дизель' => 'diesel', 'diesel' => 'diesel',
            'бензин' => 'benzina', 'petrol' => 'benzina', 'gasoline' => 'benzina',
            'гибрид' => 'hybrid', 'hybrid' => 'hybrid',
            'hybrid (petrol/electric)' => 'hybrid', 'гибрид (бензин / электрический)' => 'hybrid',
            'hybrid (diesel/electric)' => 'diesel_hybrid', 'гибрид (дизель / электрический)' => 'diesel_hybrid',
            // Plug-in hybrids (PHEV) — petrol base, bigger excise discount.
            'plug-in hybrid' => 'hybrid_plugin', 'plug-in' => 'hybrid_plugin', 'phev' => 'hybrid_plugin',
            'подключаемый гибрид' => 'hybrid_plugin', 'плагин-гибрид' => 'hybrid_plugin',
            'plug-in hybrid (petrol/electric)' => 'hybrid_plugin',
            'электро' => 'electric', 'электрический' => 'electric', 'electric' => 'electric',
            'газ' => 'lpg', 'газ(гбо)' => 'lpg', 'lpg' => 'lpg',
            'сжиженный нефтяной газ' => 'lpg', 'natural gas' => 'gasoline_cng', 'cng' => 'gasoline_cng',
            // "Other" → null on purpose: ambiguous, let Groq infer at enrichment.
        ];
        return $map[$k] ?? null;
    }

    private function normalizeGearbox(?string $val): ?string
    {
        if (!$val) return null;
        $k = mb_strtolower(trim($val), 'UTF-8');
        if (strpos($k, 'автомат') !== false || strpos($k, 'automat') !== false) return 'automat';
        if (strpos($k, 'механ') !== false || strpos($k, 'manual') !== false) return 'manual';
        return null;
    }

    // Colour (RU/EN/NL/FR/DE) → sauto colour code. Real values often carry a
    // finish/shade suffix ("Тёмно-серый металлик", "Pearl White"), so we match by
    // KEYWORD (substring), not the whole string. Order matters: specific shades
    // (silver/dark) are checked before the broad base colour. Unknown → null.
    private function normalizeColor(?string $val): ?string
    {
        if (!$val) return null;
        $k = mb_strtolower(trim($val), 'UTF-8');
        $kw = [
            // silver/grey BEFORE generic, so "серебристо-серый" → silver
            'серебрист'=>'slv','серебрян'=>'slv','silver'=>'slv','zilver'=>'slv','argent'=>'slv','silber'=>'slv',
            'антрацит'=>'gra','antraciet'=>'gra','anthracite'=>'gra',
            'серый'=>'gra','сер.'=>'gra','gray'=>'gra','grey'=>'gra','grijs'=>'gra','gris'=>'gra','grau'=>'gra',
            'чёрн'=>'blk','черн'=>'blk','black'=>'blk','zwart'=>'blk','noir'=>'blk','schwarz'=>'blk',
            'бел'=>'wht','white'=>'wht','wit'=>'wht','blanc'=>'wht','weiss'=>'wht','weiß'=>'wht',
            'син'=>'blu','голуб'=>'blu','blue'=>'blu','blauw'=>'blu','bleu'=>'blu','blau'=>'blu',
            'бордов'=>'red','красн'=>'red','red'=>'red','rood'=>'red','rouge'=>'red','rot'=>'red',
            'зелён'=>'grn','зелен'=>'grn','green'=>'grn','groen'=>'grn','vert'=>'grn','grün'=>'grn',
            'коричнев'=>'brn','brown'=>'brn','bruin'=>'brn','brun'=>'brn','braun'=>'brn',
            'беж'=>'bge','beige'=>'bge',
            'жёлт'=>'ylw','желт'=>'ylw','yellow'=>'ylw','geel'=>'ylw','jaune'=>'ylw','gelb'=>'ylw',
            'оранж'=>'orn','orange'=>'orn','oranje'=>'orn',
            'золот'=>'gld','gold'=>'gld','goud'=>'gld',
            'фиолет'=>'prp','purple'=>'prp','paars'=>'prp',
        ];
        foreach ($kw as $needle => $code) {
            if (mb_strpos($k, $needle) !== false) return $code;
        }
        return null;
    }

    // eCarsTrade category/bodyType → internal code. Values can be compound
    // ("Внедорожник/внедорожник", "Минивэн / микроавтобус", "SUV/Off-road…"),
    // so we match by KEYWORD (substring) rather than the whole string.
    private function normalizeBodyType(?string $val): ?string
    {
        if (!$val) return null;
        $k = mb_strtolower(trim($val), 'UTF-8');
        $k = strtr($k, ['é'=>'e','ë'=>'e']);
        // Keyword → code, checked in order (first hit wins). SUV before others
        // since "off-road vehicle" contains generic words.
        $kw = [
            'внедорожник'=>'suv','кроссовер'=>'suv','suv'=>'suv','off-road'=>'suv','offroad'=>'suv',
            'terreinwagen'=>'suv','gelände'=>'suv','gelande'=>'suv',
            'пикап'=>'pickup','pickup'=>'pickup','pick-up'=>'pickup',
            'минивэн'=>'minivan','микроавтобус'=>'van','minibus'=>'van','mpv'=>'minivan','monovolume'=>'minivan',
            'фургон'=>'van','van'=>'van','bestelwagen'=>'van','lichte vracht'=>'van',
            'универсал'=>'wagon','break'=>'wagon','station'=>'wagon','estate'=>'wagon','kombi'=>'wagon','touring'=>'wagon',
            'хэтчбек'=>'hatchback','хетчбэк'=>'hatchback','hatchback'=>'hatchback','compact'=>'hatchback',
            'кабриолет'=>'convertible','cabrio'=>'convertible','convertible'=>'convertible','roadster'=>'convertible',
            'купе'=>'coupe','coupe'=>'coupe',
            'седан'=>'sedan','лимузин'=>'sedan','sedan'=>'sedan','berline'=>'sedan','limousine'=>'sedan','saloon'=>'sedan',
            'personenauto'=>'sedan', // generic passenger car → sedan
        ];
        foreach ($kw as $needle => $code) {
            if (mb_strpos($k, $needle) !== false) return $code;
        }
        return null;
    }

    private function normalizeDrive(?string $val): ?string
    {
        if (!$val) return null;
        $k = mb_strtolower(trim($val), 'UTF-8');
        if (strpos($k,'4x4')!==false || strpos($k,'awd')!==false || strpos($k,'4wd')!==false
            || strpos($k,'полн')!==false || strpos($k,'integral')!==false
            || strpos($k,'внедорожник')!==false) return '4x4'; // SUV category → AWD
        if (strpos($k,'перед')!==false || strpos($k,'fwd')!==false || strpos($k,'front')!==false) return 'front';
        if (strpos($k,'задн')!==false || strpos($k,'rwd')!==false || strpos($k,'rear')!==false) return 'rear';
        return null;
    }

    // Infer drive from a car title — like OpenLane. AWD trims (xDrive/quattro/
    // 4MATIC/4Motion/AWD/4x4) → 4x4; BMW sDrive → rwd. Unknown → null (we never
    // guess front-wheel, since eCarsTrade gives no explicit drive field).
    private function inferDriveFromTitle(?string $text): ?string
    {
        if (!$text) return null;
        $t = mb_strtolower($text, 'UTF-8');
        if (preg_match('/\b(xdrive|quattro|4matic|4motion|allroad|all-?wheel|awd|4wd|sh-?awd|4x4|4\s*motion)\b/i', $t)) return '4x4';
        if (preg_match('/\bsdrive\b/i', $t)) return 'rear';
        return null;
    }
}
