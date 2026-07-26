<?php

namespace App\Services\B2b;

use PDO;

/**
 * Currency conversion for B2B documents.
 *
 * The "cont de plată" is always issued in MDL (the VictoriaBank MDL account),
 * so a car priced in EUR/USD is converted at the official BNM rate — the same
 * source the public price calculator uses (parsing_pricing_eur_rate). No manual
 * currency choice: the client sees the MDL amount and can only adjust that.
 */
class B2bMoney
{
    /** Convert $amount, expressed in $cur, to MDL at the BNM rate. */
    public static function toMdl(float $amount, string $cur): float
    {
        $cur = strtoupper(trim($cur));
        if ($cur === '' || $cur === 'MDL' || $cur === 'LEI') {
            return round($amount, 2);
        }

        return round($amount * self::rate($cur), 2);
    }

    /** MDL per 1 unit of $cur — live BNM, DB-cached fallback, then a sane default. */
    public static function rate(string $cur): float
    {
        $cur = strtoupper(trim($cur));
        if ($cur === '' || $cur === 'MDL' || $cur === 'LEI') {
            return 1.0;
        }

        // Reuse the site-wide EUR rate so B2B matches the public calculator.
        if ($cur === 'EUR' && function_exists('parsing_pricing_eur_rate')) {
            $r = (float)parsing_pricing_eur_rate(B2bConfig::db(), B2bConfig::prefix());
            if ($r > 0) {
                return $r;
            }
        }

        // Live BNM (same feed as the calculator), for any currency.
        try {
            $prev = libxml_use_internal_errors(true);
            $ctx  = stream_context_create(['http' => ['timeout' => 3], 'https' => ['timeout' => 3]]);
            $body = @file_get_contents(
                'https://bnm.md/ru/official_exchange_rates?get_xml=1&date=' . date('d.m.Y'),
                false,
                $ctx
            );
            $xml = $body ? @simplexml_load_string($body) : null;
            libxml_use_internal_errors($prev);

            if ($xml && isset($xml->Valute)) {
                foreach ($xml->Valute as $v) {
                    if ((string)$v->CharCode === $cur) {
                        $val = floatval(str_replace(',', '.', (string)$v->Value));
                        $nom = (int)$v->Nominal ?: 1;
                        if ($val > 0) {
                            return $val / $nom;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // fall through to the DB cache
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT `value` FROM ' . B2bConfig::prefix() . '_exchange WHERE `name` = :n LIMIT 1'
            );
            $stmt->execute([':n' => $cur]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && floatval($row['value']) > 0) {
                return floatval($row['value']);
            }
        } catch (\Throwable $e) {
            // fall through to defaults
        }

        return $cur === 'USD' ? 17.5 : ($cur === 'EUR' ? 19.5 : 1.0);
    }
}
