<?php

namespace App\Services;

/**
 * Minimal S3-compatible client for Cloudflare R2, signing requests with AWS
 * SigV4 by hand. Deliberately no AWS SDK: it drags in tens of MB of vendor code
 * for the four calls we make (PUT / DELETE / HEAD / LIST).
 *
 * Credentials come from /.env:
 *   R2_ACCOUNT_ID, R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET
 *   R2_ENDPOINT (optional — derived from the account id when absent)
 */
final class R2Client
{
    private const REGION  = 'auto';   // R2 ignores the region but SigV4 requires one
    private const SERVICE = 's3';

    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $host;
    private string $scheme = 'https';

    public function __construct(string $accessKey, string $secretKey, string $bucket, string $endpoint)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket    = $bucket;
        $this->host      = (string)parse_url($endpoint, PHP_URL_HOST);
    }

    /** Build from .env, or null when not configured (callers stay optional-safe). */
    public static function fromEnv(): ?self
    {
        // Under CLI DOCUMENT_ROOT is an empty string rather than unset, so check the
        // value instead of just the key.
        $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($root === '' || !is_dir($root)) $root = dirname(__DIR__, 2);
        $envFile = rtrim($root, '/') . '/.env';
        if (!is_file($envFile)) return null;

        $env = (string)@file_get_contents($envFile);
        $get = function (string $key) use ($env): string {
            return preg_match('/^\s*' . preg_quote($key, '/') . '\s*=\s*(.+)$/m', $env, $m)
                ? trim($m[1]) : '';
        };

        $account = $get('R2_ACCOUNT_ID');
        $ak      = $get('R2_ACCESS_KEY_ID');
        $sk      = $get('R2_SECRET_ACCESS_KEY');
        $bucket  = $get('R2_BUCKET');
        $ep      = $get('R2_ENDPOINT') ?: ($account !== '' ? "https://{$account}.r2.cloudflarestorage.com" : '');

        if ($ak === '' || $sk === '' || $bucket === '' || $ep === '') return null;
        return new self($ak, $sk, $bucket, $ep);
    }

    // ── public API ──────────────────────────────────────────────────────────

    /** Upload a local file. Returns true on 2xx. */
    public function putFile(string $key, string $path, string $contentType = 'image/jpeg'): bool
    {
        $body = @file_get_contents($path);
        if ($body === false) return false;
        return $this->putObject($key, $body, $contentType);
    }

    public function putObject(string $key, string $body, string $contentType = 'image/jpeg'): bool
    {
        $ch = $this->buildRequest('PUT', $key, $body, $contentType);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    /**
     * Upload many files at once. $items is a list of [key, path] or
     * [key, path, contentType] — a folder mixes jpg with pdf/html.
     * Returns ['ok' => n, 'failed' => [key => httpCode|error], 'bytes' => n].
     * Parallelism is what turns a ~64h sequential migration into ~7h.
     */
    public function putFilesParallel(array $items, int $concurrency = 10, string $contentType = 'image/jpeg'): array
    {
        $ok = 0; $failed = []; $bytes = 0;
        foreach (array_chunk($items, max(1, $concurrency)) as $chunk) {
            $res = $this->uploadChunk($chunk, $contentType);
            $ok += $res['ok']; $bytes += $res['bytes'];

            // R2 throws the odd 500/502 under load. One retry clears them —
            // without it every hiccup becomes a file to hunt down before the
            // local copy can be deleted.
            $retry = [];
            foreach ($res['failed'] as $key => $why) {
                if (!self::worthRetrying($why)) { $failed[$key] = $why; continue; }
                foreach ($chunk as $it) { if ($it[0] === $key) { $retry[] = $it; break; } }
            }
            if ($retry) {
                usleep(500000); // these errors cluster; a short pause helps
                $res2 = $this->uploadChunk($retry, $contentType);
                $ok += $res2['ok']; $bytes += $res2['bytes'];
                foreach ($res2['failed'] as $key => $why) $failed[$key] = $why;
            }
        }
        return ['ok' => $ok, 'failed' => $failed, 'bytes' => $bytes];
    }

    /** 5xx and transport errors are worth another go; 4xx and unreadable files are not. */
    private static function worthRetrying($why): bool
    {
        if ($why === 'unreadable') return false;
        if (is_int($why)) return $why === 0 || $why >= 500;
        return true; // curl transport error
    }

    /** One parallel batch. Same return shape as putFilesParallel(). */
    private function uploadChunk(array $chunk, string $contentType): array
    {
        $ok = 0; $failed = []; $bytes = 0;

        $mh = curl_multi_init();
        $handles = [];
        foreach ($chunk as $item) {
            $key  = $item[0];
            $path = $item[1];
            $type = $item[2] ?? $contentType;
            $body = @file_get_contents($path);
            if ($body === false) { $failed[$key] = 'unreadable'; continue; }
            $ch = $this->buildRequest('PUT', $key, $body, $type);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = [$ch, strlen($body)];
        }
        if (!$handles) {
            curl_multi_close($mh);
            return ['ok' => 0, 'failed' => $failed, 'bytes' => 0];
        }

        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $key => [$ch, $len]) {
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            if ($code >= 200 && $code < 300) { $ok++; $bytes += $len; }
            else { $failed[$key] = $err !== '' ? $err : $code; }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return ['ok' => $ok, 'failed' => $failed, 'bytes' => $bytes];
    }

    /** Object bytes, or null when missing / on error. */
    public function getObject(string $key): ?string
    {
        $ch = $this->buildRequest('GET', $key, '');
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code === 200 && is_string($body)) ? $body : null;
    }

    /** True when the object exists. */
    public function exists(string $key): bool
    {
        $ch = $this->buildRequest('HEAD', $key, '');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }

    /** Delete one object. R2 returns 204 whether or not it existed. */
    public function deleteObject(string $key): bool
    {
        $ch = $this->buildRequest('DELETE', $key, '');
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }

    /**
     * Delete every object under a prefix (a car folder). Lists then deletes,
     * paging until exhausted. Returns the number deleted.
     */
    public function deletePrefix(string $prefix): int
    {
        $deleted = 0; $token = null;
        do {
            [$keys, $token] = $this->listObjects($prefix, $token);
            $deleted += $this->deleteObjects(array_keys($keys));
        } while ($token !== null);
        return $deleted;
    }

    /**
     * Delete many keys in ONE request (S3 DeleteObjects), up to 1000 per call.
     *
     * A car folder holds ~50 objects (every photo in two sizes) and deleting them
     * one by one meant 50 signed round trips to Cloudflare — several seconds of
     * staring at a spinner after pressing "delete". This does it in one.
     */
    public function deleteObjects(array $keys): int
    {
        $keys = array_values(array_filter($keys, fn($k) => is_string($k) && $k !== ''));
        if (!$keys) return 0;

        $deleted = 0;
        foreach (array_chunk($keys, 1000) as $chunk) {
            $body = '<?xml version="1.0" encoding="UTF-8"?><Delete><Quiet>true</Quiet>';
            foreach ($chunk as $k) {
                $body .= '<Object><Key>' . htmlspecialchars($k, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Key></Object>';
            }
            $body .= '</Delete>';

            $ch = $this->buildRequest('POST', '', $body, 'application/xml', ['delete' => ''], [
                // Required by the DeleteObjects API, on top of the SigV4 payload hash.
                'content-md5' => base64_encode(md5($body, true)),
            ]);
            $out  = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code >= 200 && $code < 300) {
                // Quiet mode answers with errors only, so a clean body means all gone.
                $failed = substr_count((string)$out, '<Error>');
                $deleted += max(0, count($chunk) - $failed);
            } else {
                // Batch refused (older gateway, odd key) — fall back to one by one so
                // a delete never silently leaves the whole folder behind.
                foreach ($chunk as $k) { if ($this->deleteObject($k)) $deleted++; }
            }
        }
        return $deleted;
    }

    /**
     * One page under a prefix. Returns [[key => size], continuationToken|null].
     * Sizes come along because verification has to catch truncated uploads, not
     * just missing ones.
     */
    public function listObjects(string $prefix, ?string $token = null, int $max = 1000): array
    {
        $query = [
            'list-type'  => '2',
            'max-keys'   => (string)$max,
            'prefix'     => $prefix,
        ];
        if ($token !== null) $query['continuation-token'] = $token;
        ksort($query); // SigV4 requires the canonical query string sorted by key

        $ch = $this->buildRequest('GET', '', '', null, $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !is_string($body)) return [[], null];

        $xml = @simplexml_load_string($body);
        if ($xml === false) return [[], null];

        $keys = [];
        foreach ($xml->Contents ?? [] as $c) $keys[(string)$c->Key] = (int)$c->Size;
        $next = ((string)($xml->IsTruncated ?? 'false') === 'true')
            ? (string)($xml->NextContinuationToken ?? '') : '';

        return [$keys, $next !== '' ? $next : null];
    }

    /** Total object count under a prefix ('' = whole bucket). For verification. */
    public function countObjects(string $prefix = ''): int
    {
        $n = 0; $token = null;
        do {
            [$keys, $token] = $this->listObjects($prefix, $token);
            $n += count($keys);
        } while ($token !== null);
        return $n;
    }

    // ── signing ─────────────────────────────────────────────────────────────

    /**
     * Build a signed cURL handle. $key '' addresses the bucket itself (LIST).
     * $contentType null omits the header entirely (GET/DELETE/HEAD).
     */
    private function buildRequest(string $method, string $key, string $body, ?string $contentType = null, array $query = [], array $extraHeaders = [])
    {
        $amzDate   = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $payloadHash = hash('sha256', $body);

        // Each path segment is encoded separately so the slashes stay literal.
        $canonicalUri = '/' . $this->bucket;
        if ($key !== '') {
            $canonicalUri .= '/' . implode('/', array_map('rawurlencode', explode('/', $key)));
        }

        $canonicalQuery = '';
        if ($query) {
            $parts = [];
            foreach ($query as $k => $v) $parts[] = rawurlencode($k) . '=' . rawurlencode($v);
            $canonicalQuery = implode('&', $parts);
        }

        $headers = [
            'host'                 => $this->host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $amzDate,
        ];
        if ($contentType !== null) $headers['content-type'] = $contentType;
        // Extra headers have to be SIGNED, not just sent — S3 rejects the request
        // otherwise. DeleteObjects needs Content-MD5, hence this.
        foreach ($extraHeaders as $h => $v) $headers[strtolower($h)] = $v;
        ksort($headers);

        $canonicalHeaders = '';
        foreach ($headers as $h => $v) $canonicalHeaders .= $h . ':' . $v . "\n";
        $signedHeaders = implode(';', array_keys($headers));

        $canonicalRequest = implode("\n", [
            $method, $canonicalUri, $canonicalQuery,
            $canonicalHeaders, $signedHeaders, $payloadHash,
        ]);

        $scope = $dateStamp . '/' . self::REGION . '/' . self::SERVICE . '/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256', $amzDate, $scope, hash('sha256', $canonicalRequest),
        ]);

        $kDate    = hash_hmac('sha256', $dateStamp, 'AWS4' . $this->secretKey, true);
        $kRegion  = hash_hmac('sha256', self::REGION, $kDate, true);
        $kService = hash_hmac('sha256', self::SERVICE, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = 'AWS4-HMAC-SHA256 '
            . 'Credential=' . $this->accessKey . '/' . $scope . ', '
            . 'SignedHeaders=' . $signedHeaders . ', '
            . 'Signature=' . $signature;

        $url = $this->scheme . '://' . $this->host . $canonicalUri
             . ($canonicalQuery !== '' ? '?' . $canonicalQuery : '');

        $curlHeaders = ['Authorization: ' . $authorization];
        foreach ($headers as $h => $v) {
            if ($h === 'host') continue; // cURL sets Host itself
            $curlHeaders[] = $h . ': ' . $v;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        if ($method === 'PUT' || $method === 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        return $ch;
    }
}
