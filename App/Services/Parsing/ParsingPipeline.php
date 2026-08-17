<?php

namespace App\Services\Parsing;

use App\Core\Container;
use Exception;

class ParsingPipeline
{
    private $db;
    private $prefix;
    private $priceCalculator;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
        $this->priceCalculator = new PriceCalculator();
    }

    public function process(array $raw, bool $skipImages = false): ?array
    {
        $source = $raw['source'] ?? null;
        $sourceId = $raw['source_id'] ?? null;
        if (!$source || !$sourceId) {
            return null;
        }

        $row = $this->baseRow($raw);

        if ($skipImages) {
            // Fast path for search: don't download, just keep remote URLs so
            // the proposed list can render covers directly from the source CDN.
            $row['images_local'] = array_map(fn($u) => ['url' => $u], $raw['images'] ?? []);
        } else {
            $row['images_local'] = $this->downloadImages($raw['images'] ?? [], $source, $sourceId);
        }
        $row['price_eur'] = $this->convertToEur($raw['price_source'] ?? null, $raw['price_source_currency'] ?? null);
        // Display the source price directly — no customs/delivery calculation.
        $row['price_final_eur'] = $row['price_eur'];

        $translated = $this->translateText($raw['title'] ?? null, $raw['description'] ?? null, $raw['features'] ?? []);
        $row['title_ro'] = $translated['title'];
        $row['description_ro'] = $translated['description'];
        $row['features_ro'] = $translated['features'];

        if (!empty($raw['report'])) {
            $row['report_data'] = $raw['report'];
        }

        $row['raw_data'] = $raw['raw_data'] ?? $raw;
        return $row;
    }

    private function baseRow(array $raw): array
    {
        $keep = [
            'source', 'source_id', 'source_url', 'vin', 'brand', 'model',
            'car_sub_model',
            'year', 'km', 'fuel_type', 'gearbox', 'engine_volume', 'power_hp',
            'seats', 'drive_type',
            'color', 'body_type', 'price_source', 'price_source_currency',
        ];
        $row = [];
        foreach ($keep as $k) {
            if (array_key_exists($k, $raw)) $row[$k] = $raw[$k];
        }
        return $row;
    }

    public function downloadImagesPublic(array $urls, string $source, string $sourceId): array
    {
        return $this->downloadImages($urls, $source, $sourceId);
    }

    private function downloadImages(array $urls, string $source, string $sourceId): array
    {
        if (empty($urls)) {
            return [];
        }

        $baseDir = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/uploads/parsing/' . $source . '/' . $sourceId;
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }

        $logFile = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/logs/parsing_images.log';
        @mkdir(dirname($logFile), 0755, true);

        $maxImages = 50;
        $saved = [];
        $position = 0;
        $attempted = 0;

        foreach ($urls as $url) {
            if (count($saved) >= $maxImages) break;
            if (!is_string($url) || $url === '') continue;
            $attempted++;
            $position = count($saved) + 1;

            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }
            $filename = $position . '.' . $ext;
            $filepath = $baseDir . '/' . $filename;

            if (file_exists($filepath) && filesize($filepath) > 1000) {
                $saved[] = [
                    'path' => 'uploads/parsing/' . $source . '/' . $sourceId,
                    'name' => $filename,
                    'ff'   => $ext,
                ];
                continue;
            }

            $referer = 'https://www.encar.com/';
            if ($source === 'ecarstrade') $referer = 'https://www.e-carstrade.com/';
            if ($source === 'openlane')   $referer = 'https://www.openlane.eu/';

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER     => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'Referer: ' . $referer,
                ],
            ]);
            $bytes = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($bytes === false || $httpCode !== 200 || strlen($bytes) < 1000) {
                @file_put_contents($logFile,
                    '['.date('Y-m-d H:i:s')."] FAIL {$source}/{$sourceId} url={$url} status={$httpCode} bytes=".strlen((string)$bytes)." err={$err}\n",
                    FILE_APPEND);
                continue;
            }

            file_put_contents($filepath, $bytes);
            $saved[] = [
                'path' => 'uploads/parsing/' . $source . '/' . $sourceId,
                'name' => $filename,
                'ff'   => $ext,
            ];
        }

        @file_put_contents($logFile,
            '['.date('Y-m-d H:i:s')."] DONE {$source}/{$sourceId} urls=".count($urls)." attempted={$attempted} saved=".count($saved)."\n",
            FILE_APPEND);

        return $saved;
    }

    private function convertToEur(?float $amount, ?string $currency): ?float
    {
        if ($amount === null || !$currency) return null;
        $currency = strtoupper($currency);
        if ($currency === 'EUR') return round($amount, 2);

        // Approximate fixed rates — good enough for display/filtering.
        // Update periodically if rates drift significantly.
        // CAD is quoted by BNM (like EUR), so it uses the live daily rate.
        if ($currency === 'CAD') {
            return round($amount * CurrencyRate::cadToEur($this->db, $this->prefix), 2);
        }

        // KRW is NOT published by BNM — it stays a fixed rate.
        $rates = [
            'KRW' => 0.000570, // 1 KRW ≈ 0.000570 EUR (1 EUR ≈ 1753 KRW, updated 2026-05-27)
        ];

        if (!isset($rates[$currency])) return null;
        return round($amount * $rates[$currency], 2);
    }

    private function translateText(?string $title, ?string $description, array $features): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'features' => $features,
        ];
    }

    private function countryCodeForSource(string $source): string
    {
        return [
            'encar' => 'KR',
            'ecarstrade' => 'BE',
            'openlane' => 'DE',
            'autotrader' => 'CA',
        ][$source] ?? 'DE';
    }
}
