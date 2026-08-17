<?php

namespace App\Services\Parsing;

/**
 * Optional outbound proxy for a parsing source.
 *
 * Encar's CDN started answering 407 to every request from this server's IP, while the
 * same request succeeds from anywhere else — an address block, not a code problem. The
 * only real cure is to leave through a different address, so each source can be given
 * its own proxy without touching the others.
 *
 * Configure in .env (any of these, most specific first):
 *   PARSING_PROXY_ENCAR=http://user:pass@host:port
 *   PARSING_PROXY=http://user:pass@host:port      (fallback for every source)
 *
 * Nothing set → nothing changes: requests go out directly, as before.
 */
class OutboundProxy
{
    private static ?array $env = null;

    /** Proxy URL for a source code ('encar', 'autotrader', …), or null. */
    public static function for(string $source): ?string
    {
        $env = self::env();
        $key = 'PARSING_PROXY_' . strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', $source));

        foreach ([$key, 'PARSING_PROXY'] as $name) {
            if (!empty($env[$name])) return $env[$name];
            $fromEnv = getenv($name);
            if ($fromEnv !== false && $fromEnv !== '') return trim($fromEnv);
        }
        return null;
    }

    /** Apply it to a cURL handle. Returns true when a proxy was set. */
    public static function apply($ch, string $source): bool
    {
        $proxy = self::for($source);
        if ($proxy === null || $ch === null) return false;

        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
        // Credentials may travel inside the URL (user:pass@host); cURL reads them, but
        // some proxies only accept them sent separately.
        $parts = parse_url($proxy);
        if (!empty($parts['user'])) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $parts['user'] . ':' . ($parts['pass'] ?? ''));
        }
        return true;
    }

    private static function env(): array
    {
        if (self::$env !== null) return self::$env;

        self::$env = [];
        // Under CLI $_SERVER['DOCUMENT_ROOT'] is an empty string, not missing, so the
        // path has to be resolved from this file instead.
        $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($root === '' || !is_dir($root)) $root = dirname(__DIR__, 3);
        $file = rtrim($root, '/\\') . '/.env';
        if (!is_file($file)) return self::$env;

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            self::$env[trim($k)] = trim($v, " \t\"'");
        }
        return self::$env;
    }
}
