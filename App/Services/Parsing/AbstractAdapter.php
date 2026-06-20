<?php

namespace App\Services\Parsing;

use App\Core\Container;

abstract class AbstractAdapter implements AdapterInterface
{
    protected $db;
    protected $prefix;
    protected $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    protected $requestDelayMin = 3;
    protected $requestDelayMax = 8;
    protected $timeout = 30;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
    }

    abstract public function getSourceCode(): string;
    abstract public function getSourceName(): string;
    abstract public function searchByFilter(array $criteria): array;
    abstract public function fetchByUrl(string $url): ?array;
    abstract public function fetchById(string $sourceId): ?array;
    abstract public function checkAvailability(string $sourceId): bool;
    abstract public function detectsUrl(string $url): bool;

    protected function httpRequest(string $url, array $options = []): ?array
    {
        $ch = curl_init();

        $headers = $options['headers'] ?? [];
        $defaultHeaders = [
            'User-Agent: ' . $this->userAgent,
            'Accept: application/json, text/html, */*',
            'Accept-Language: en-US,en;q=0.9,ro;q=0.8',
        ];
        $headers = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $options['timeout'] ?? $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        if (!empty($options['post'])) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['post']);
        }

        if (!empty($options['cookies'])) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $options['cookies']);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $options['cookies']);
        }

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => $body,
            'error' => $error,
        ];
    }

    protected function randomDelay(): void
    {
        $microseconds = mt_rand($this->requestDelayMin * 1000000, $this->requestDelayMax * 1000000);
        usleep($microseconds);
    }

    protected function logError(string $message, array $context = []): void
    {
        $logFile = __DIR__ . '/../../../logs/parsing_' . $this->getSourceCode() . '.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $entry = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if (!empty($context)) {
            $entry .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        @file_put_contents($logFile, $entry . PHP_EOL, FILE_APPEND);
    }

    protected function normalizeCarData(array $raw): array
    {
        return [
            'source' => $this->getSourceCode(),
            'source_id' => $raw['source_id'] ?? null,
            'source_url' => $raw['source_url'] ?? null,
            'vin' => $raw['vin'] ?? null,
            'brand' => $raw['brand'] ?? null,
            'model' => $raw['model'] ?? null,
            'year' => isset($raw['year']) ? (int)$raw['year'] : null,
            'km' => isset($raw['km']) ? (int)$raw['km'] : null,
            'fuel_type' => $raw['fuel_type'] ?? null,
            'gearbox' => $raw['gearbox'] ?? null,
            'engine_volume' => isset($raw['engine_volume']) ? (int)$raw['engine_volume'] : null,
            'power_hp' => isset($raw['power_hp']) ? (int)$raw['power_hp'] : null,
            'seats' => isset($raw['seats']) ? (int)$raw['seats'] : null,
            'drive_type' => $raw['drive_type'] ?? null,
            'color' => $raw['color'] ?? null,
            'body_type' => $raw['body_type'] ?? null,
            'price_source' => isset($raw['price_source']) ? (float)$raw['price_source'] : null,
            'price_source_currency' => $raw['price_source_currency'] ?? null,
            'title' => $raw['title'] ?? null,
            'description' => $raw['description'] ?? null,
            'features' => $raw['features'] ?? [],
            'images' => $raw['images'] ?? [],
            'report' => $raw['report'] ?? null,
            'raw_data' => $raw,
        ];
    }
}
