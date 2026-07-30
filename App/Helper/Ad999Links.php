<?php

namespace App\Helper;

use PDO;

/**
 * The "Detalii despre automobil / Toate automobilele modelului|mărcii" block that opens
 * every 999.md description (feature 13).
 *
 * It lives here because FIVE different paths push feature 13 to 999 — the admin 999 form,
 * the parsing auto-publish cron, the daily price resync, a car edit that changes the price,
 * and the schedule renewals — and the ones that reused a stored payload used to send it
 * without the block, silently wiping the links off a live ad.
 */
class Ad999Links
{
    private const RE = '/Detalii despre automobil:\s*\n'
        . 'https?:\/\/\S+\s*\n'
        . 'Toate automobilele modelului[^\n]*\n'
        . 'https?:\/\/\S+\s*\n'
        . 'Toate automobilele mărcii[^\n]*\n'
        . 'https?:\/\/\S+\s*/u';

    public static function strip(string $text): string
    {
        return trim((string)preg_replace(self::RE, '', $text));
    }

    public static function has(string $text): bool
    {
        return (bool)preg_match(self::RE, $text);
    }

    /** on-order cars live under /ordercars, the stock catalogue under /cars. */
    public static function section(?string $catalogType): string
    {
        return ($catalogType === 'on_order') ? 'ordercars' : 'cars';
    }

    /**
     * Build the block for one car, or null when the brand/model can't be named (no
     * car_list row) — the same condition under which the ad has always gone out
     * without links.
     */
    public static function block(PDO $db, string $prefix, int $carId, string $br, string $mo, string $section): ?string
    {
        if ($carId <= 0 || $br === '' || $mo === '') return null;

        $st = $db->prepare("SELECT br_nm, mo_nm FROM {$prefix}_car_list WHERE br = ? AND mo = ? LIMIT 1");
        $st->execute([$br, $mo]);
        $cl = $st->fetch(PDO::FETCH_ASSOC);
        if (!$cl || empty($cl['br_nm']) || empty($cl['mo_nm'])) return null;

        $brandSlug = strtolower(str_replace('_', '-', $br));
        $modelSlug = strtolower(str_replace('_', '-', $mo));
        $base = "https://www.sauto.md/ro/{$section}";

        return "Detalii despre automobil:\n{$base}/{$carId}\n"
             . "Toate automobilele modelului {$cl['mo_nm']}:\n{$base}/{$brandSlug}/{$modelSlug}\n"
             . "Toate automobilele mărcii {$cl['br_nm']}:\n{$base}/{$brandSlug}";
    }

    /**
     * Put the block on top of feature 13 of a features payload, replacing an older copy
     * of it. Returns the payload untouched when the block can't be built.
     *
     * $car needs id, br, mo and — unless $section is passed — catalog_type.
     */
    public static function apply(PDO $db, string $prefix, array $car, array $features, ?string $section = null): array
    {
        $block = self::block(
            $db,
            $prefix,
            (int)($car['id'] ?? 0),
            (string)($car['br'] ?? ''),
            (string)($car['mo'] ?? ''),
            $section ?? self::section($car['catalog_type'] ?? null)
        );
        if ($block === null) return $features;

        foreach ($features as $i => $f) {
            if ((string)($f['id'] ?? '') === '13') {
                $body = self::strip((string)($f['value'] ?? ''));
                $features[$i]['value'] = $block . ($body !== '' ? "\n\n" . $body : '');
                return $features;
            }
        }
        $features[] = ['id' => '13', 'value' => $block];

        return $features;
    }
}
