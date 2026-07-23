<?php

namespace App\Services\B2b;

use PDO;

/**
 * Region access control (spec 3.2 / 4.2).
 *
 * Region -> country mapping follows the existing public `ic` filter in
 * content/site/include/order_functions.php: korea=KR, usa=US, china=CN,
 * europe = everything else. Deny by default: no row means no access.
 */
class B2bRegions
{
    /** Codes that are NOT "europe"; used to build the europe clause. */
    private const NON_EUROPE_CODES = ['KR', 'US', 'CN'];

    private static array $cache = [];

    /** @return string[] subset of B2bConfig::REGIONS */
    public static function allowed(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        if (isset(self::$cache[$userId])) {
            return self::$cache[$userId];
        }

        $out = [];
        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT region FROM ' . B2bConfig::table('permissions')
                . ' WHERE b2b_user_id = :uid AND is_allowed = 1'
            );
            $stmt->execute([':uid' => $userId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $region) {
                if (in_array($region, B2bConfig::REGIONS, true)) {
                    $out[] = $region;
                }
            }
        } catch (\Throwable $e) {
            // Fail closed: a DB error denies access, it never grants it.
        }

        return self::$cache[$userId] = $out;
    }

    public static function isAllowed(int $userId, string $region): bool
    {
        return in_array($region, self::allowed($userId), true);
    }

    /** Replaces the partner's whole permission set (admin panel). */
    public static function save(int $userId, array $regions): void
    {
        $db  = B2bConfig::db();
        $tbl = B2bConfig::table('permissions');

        $db->prepare('DELETE FROM ' . $tbl . ' WHERE b2b_user_id = :uid')->execute([':uid' => $userId]);

        $ins = $db->prepare(
            'INSERT INTO ' . $tbl . ' (b2b_user_id, region, is_allowed) VALUES (:uid, :region, 1)'
        );
        foreach (B2bConfig::REGIONS as $region) {
            if (in_array($region, $regions, true)) {
                $ins->execute([':uid' => $userId, ':region' => $region]);
            }
        }

        unset(self::$cache[$userId]);
    }

    /** @return string one of B2bConfig::REGIONS */
    public static function regionFromCountryCode(?string $code): string
    {
        switch (strtoupper(trim((string)$code))) {
            case 'KR': return 'korea';
            case 'US': return 'usa';
            case 'CN': return 'china';
            default:   return 'europe';
        }
    }

    /** Region of a catalog car, or null if the car does not exist. */
    public static function regionForCar(int $carId): ?string
    {
        if ($carId <= 0) {
            return null;
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT c.code FROM ' . B2bConfig::prefix() . '_car_ctlg AS cc
                 LEFT JOIN countries AS c ON c.id = cc.import_country_id
                 WHERE cc.id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $carId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            // No import country falls into "europe", same as the public ic filter.
            return self::regionFromCountryCode($row['code'] ?? null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * SQL fragment restricting a gh3sp_car_ctlg query to the allowed regions.
     * Empty string when the partner may see everything; an impossible condition
     * when they may see nothing.
     *
     * @param string $alias column prefix, e.g. '' or 'cc.'
     */
    public static function sqlRestriction(int $userId, string $alias = ''): string
    {
        $allowed = self::allowed($userId);

        if (count($allowed) === count(B2bConfig::REGIONS)) {
            return '';
        }
        if (!$allowed) {
            return ' AND 1=0';
        }

        $col    = $alias . '`import_country_id`';
        $in     = [];
        $europe = false;

        foreach ($allowed as $region) {
            switch ($region) {
                case 'korea':  $in[] = 'KR'; break;
                case 'usa':    $in[] = 'US'; break;
                case 'china':  $in[] = 'CN'; break;
                case 'europe': $europe = true; break;
            }
        }

        $parts = [];
        if ($in) {
            $parts[] = $col . ' IN (SELECT id FROM countries WHERE code IN (\'' . implode("','", $in) . '\'))';
        }
        if ($europe) {
            // Cars without an import country belong here, as in the public ic filter.
            $parts[] = '(' . $col . ' IS NULL OR ' . $col . ' IN (SELECT id FROM countries WHERE code NOT IN (\''
                     . implode("','", self::NON_EUROPE_CODES) . '\')))';
        }

        return ' AND (' . implode(' OR ', $parts) . ')';
    }
}
