<?php

namespace App\Services\Parsing;

// Live foreign-exchange rates for source prices that are not quoted in EUR.
//
// BNM publishes CAD (Канадский Доллар) alongside EUR in its daily XML, both as
// MDL per unit, so CAD -> EUR is simply CAD_MDL / EUR_MDL. The rate is cached in
// parsing_settings for a day: a search imports hundreds of cars and must not hit
// bnm.md once per car, and the public price table already reads its EUR->MDL
// rate from the same source (parsing_pricing_eur_rate).
//
// Korea (KRW) is NOT here — BNM does not quote it, so it stays a fixed rate in
// ParsingPipeline.
class CurrencyRate
{
    private const CACHE_KEY = 'cad_eur_rate';
    private const CACHE_AT  = 'cad_eur_rate_at';
    private const TTL       = 86400;
    // Used only when BNM is unreachable AND nothing was ever cached.
    private const FALLBACK  = 0.62;

    private static ?float $memo = null;

    public static function cadToEur($db, string $prefix): float
    {
        if (self::$memo !== null) return self::$memo;

        $cached = self::readSetting($db, $prefix, self::CACHE_KEY);
        $cachedAt = (int)self::readSetting($db, $prefix, self::CACHE_AT);
        if ($cached > 0 && (time() - $cachedAt) < self::TTL) {
            return self::$memo = (float)$cached;
        }

        $fresh = self::fetchFromBnm();
        if ($fresh > 0) {
            self::writeSetting($db, $prefix, self::CACHE_KEY, (string)round($fresh, 6));
            self::writeSetting($db, $prefix, self::CACHE_AT, (string)time());
            return self::$memo = $fresh;
        }

        // BNM down: a stale rate is still far better than a made-up constant.
        return self::$memo = ($cached > 0 ? (float)$cached : self::FALLBACK);
    }

    // EUR -> CAD, for sending a price filter to a Canadian source.
    public static function eurToCad($db, string $prefix, float $eur): float
    {
        $rate = self::cadToEur($db, $prefix);
        return $rate > 0 ? $eur / $rate : $eur;
    }

    private static function fetchFromBnm(): float
    {
        try {
            $xml = @simplexml_load_file(
                'https://bnm.md/ru/official_exchange_rates?get_xml=1&date=' . date('d.m.Y'),
                'SimpleXMLElement',
                LIBXML_NOCDATA
            );
            if (!$xml) return 0.0;

            $cad = 0.0; $eur = 0.0;
            foreach ($xml->Valute as $v) {
                $code = (string)$v->CharCode;
                if ($code !== 'CAD' && $code !== 'EUR') continue;
                // Nominal is 1 for both, but honour it rather than assume.
                $nominal = max(1.0, (float)$v->Nominal);
                $value = (float)str_replace(',', '.', (string)$v->Value) / $nominal;
                if ($code === 'CAD') $cad = $value; else $eur = $value;
            }
            return ($cad > 0 && $eur > 0) ? $cad / $eur : 0.0;
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private static function readSetting($db, string $prefix, string $key): string
    {
        try {
            $stmt = $db->prepare('SELECT setting_value FROM '.$prefix.'_parsing_settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (string)$val : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function writeSetting($db, string $prefix, string $key, string $value): void
    {
        try {
            $db->prepare('INSERT INTO '.$prefix.'_parsing_settings (setting_key, setting_value)
                VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')
               ->execute([$key, $value]);
        } catch (\Throwable $e) {
            // Cache is an optimisation — a failed write just means we refetch.
        }
    }
}
