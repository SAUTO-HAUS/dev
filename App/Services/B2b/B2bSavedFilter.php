<?php

namespace App\Services\B2b;

use PDO;
use Throwable;

/**
 * Saved searches with an alert.
 *
 * A partner looks for, say, an Opel Astra, finds nothing, and saves the search.
 * From then on the cabinet tells them when matching cars are published.
 *
 * Two timestamps carry the whole behaviour:
 *   created_at   — the cut-off. Only cars published after it ever match, so a
 *                  new filter never lists stock that was already on the site.
 *   last_seen_at — when the partner last opened the section. Cars newer than
 *                  that are the "new" ones driving the dot; the list itself
 *                  still shows everything since created_at.
 *
 * Matching runs against gh3sp_car_ctlg and ALWAYS applies the partner's own
 * region and catalog permissions — being alerted about a car you are not allowed
 * to open would be worse than no alert at all.
 */
class B2bSavedFilter
{
    /** A partner cannot watch the whole catalog with a hundred filters. */
    public const MAX_PER_USER = 20;

    /** Criteria the form offers; anything else in the payload is dropped. */
    public const FIELDS = [
        'br', 'mo', 'region', 'fl', 'tra',
        'yr_from', 'yr_to', 'vol_from', 'vol_to', 'mlg_to', 'prc_from', 'prc_to',
    ];

    /** Catalog codes accepted for fuel and gearbox — closed sets, like region. */
    public const FUELS = ['gsl', 'gmn', 'gpn', 'hbd', 'dsl', 'pih', 'pid', 'elc', 'gas'];
    public const GEARBOXES = ['tpt', 'atm', 'mnl', 'rbt', 'vrr'];

    private static function table(): string
    {
        return B2bConfig::prefix() . '_b2b_saved_filters';
    }

    /** @return array<int, array<string, mixed>> newest first */
    public static function forUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . self::table() . ' WHERE b2b_user_id = :uid ORDER BY id DESC'
            );
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function countForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT COUNT(*) FROM ' . self::table() . ' WHERE b2b_user_id = :uid'
            );
            $stmt->execute([':uid' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Normalises a raw form payload into the stored criteria.
     * Empty values are dropped, so an unset field never narrows the search.
     *
     * @return array<string, string|int>
     */
    public static function sanitize(array $raw): array
    {
        $out = [];

        // br/mo are the catalog codes used for matching; br_nm/mo_nm are the
        // display names, stored alongside so describe() does not have to look
        // them up (and still reads correctly if the car is later removed).
        foreach (['br', 'mo', 'br_nm', 'mo_nm'] as $k) {
            $v = trim((string)($raw[$k] ?? ''));
            if ($v !== '') {
                $out[$k] = mb_substr($v, 0, 60);
            }
        }
        // Closed set: anything else is dropped, so a tampered value cannot widen
        // (or corrupt) the match.
        // Criteria saved before the USA → Canada rename still carry the old key.
        $region = B2bRegions::normalizeRegion((string)($raw['region'] ?? ''));
        if (in_array($region, B2bConfig::REGIONS, true)) {
            $out['region'] = $region;
        }

        foreach (['fl' => self::FUELS, 'tra' => self::GEARBOXES] as $k => $allowed) {
            $v = (string)($raw[$k] ?? '');
            if (in_array($v, $allowed, true)) {
                $out[$k] = $v;
            }
        }

        foreach (['yr_from', 'yr_to', 'vol_from', 'vol_to', 'mlg_to', 'prc_from', 'prc_to'] as $k) {
            $v = (int)($raw[$k] ?? 0);
            if ($v > 0) {
                $out[$k] = $v;
            }
        }

        // A model without its brand would match that model name across brands.
        if (isset($out['mo']) && !isset($out['br'])) {
            unset($out['mo'], $out['mo_nm']);
        }
        if (!isset($out['br'])) {
            unset($out['br_nm']);
        }

        // Display names alone are not criteria: without br/mo the filter would
        // look specific in the list while matching the whole catalog.
        // Display names alone are not criteria, so check the real ones.
        $real = array_diff_key($out, array_flip(['br_nm', 'mo_nm']));
        if (!$real) {
            return [];
        }

        return $out;
    }

    /** @return array<string, mixed> stored criteria of one row */
    public static function criteriaOf(array $filter): array
    {
        $c = json_decode((string)($filter['criteria'] ?? ''), true);
        return is_array($c) ? $c : [];
    }

    /**
     * Creates a filter. Returns the new id, or 0 when it was rejected (empty
     * criteria, or the per-user limit reached).
     */
    public static function create(int $userId, array $rawCriteria, string $name = ''): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $criteria = self::sanitize($rawCriteria);
        if (!$criteria) {
            return 0; // a filter matching everything is an alert on every car
        }
        if (self::countForUser($userId) >= self::MAX_PER_USER) {
            return 0;
        }

        try {
            $db = B2bConfig::db();
            $db->prepare(
                'INSERT INTO ' . self::table() . ' (`b2b_user_id`, `name`, `criteria`)
                 VALUES (:uid, :name, :criteria)'
            )->execute([
                ':uid'      => $userId,
                ':name'     => mb_substr(trim($name), 0, 120),
                ':criteria' => json_encode($criteria, JSON_UNESCAPED_UNICODE),
            ]);
            return (int)$db->lastInsertId();
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'saved filter create err=' . $e->getMessage());
            return 0;
        }
    }

    /** One of the partner's own filters, or null. */
    public static function find(int $userId, int $filterId): ?array
    {
        if ($userId <= 0 || $filterId <= 0) {
            return null;
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . self::table() . ' WHERE id = :id AND b2b_user_id = :uid'
            );
            $stmt->execute([':id' => $filterId, ':uid' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Rewrites the criteria of one of the partner's own filters.
     *
     * created_at stays untouched on purpose: it is the cut-off, so an edited
     * filter keeps listing everything published since it was first saved
     * instead of starting over from an empty list.
     */
    public static function update(int $userId, int $filterId, array $rawCriteria, string $name = ''): bool
    {
        if ($userId <= 0 || $filterId <= 0) {
            return false;
        }

        $criteria = self::sanitize($rawCriteria);
        if (!$criteria) {
            return false; // a filter matching everything is an alert on every car
        }

        try {
            // The user id is part of the WHERE, so one partner cannot rewrite
            // another's filter by guessing an id. rowCount() is not the test:
            // re-saving identical criteria touches no row and is still a
            // successful edit — the caller checks existence with find().
            $stmt = B2bConfig::db()->prepare(
                'UPDATE ' . self::table() . ' SET `name` = :name, `criteria` = :criteria
                  WHERE id = :id AND b2b_user_id = :uid'
            );
            return $stmt->execute([
                ':name'     => mb_substr(trim($name), 0, 120),
                ':criteria' => json_encode($criteria, JSON_UNESCAPED_UNICODE),
                ':id'       => $filterId,
                ':uid'      => $userId,
            ]);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'saved filter update err=' . $e->getMessage());
            return false;
        }
    }

    /** Deletes one of the partner's own filters. */
    public static function delete(int $userId, int $filterId): bool
    {
        if ($userId <= 0 || $filterId <= 0) {
            return false;
        }

        try {
            // The user id is part of the WHERE, so one partner cannot delete
            // another's filter by guessing an id.
            $stmt = B2bConfig::db()->prepare(
                'DELETE FROM ' . self::table() . ' WHERE id = :id AND b2b_user_id = :uid'
            );
            $stmt->execute([':id' => $filterId, ':uid' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * SQL fragment + bindings for one filter's criteria.
     *
     * @param string $since the filter's DATETIME cut-off, converted in SQL
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function where(array $criteria, string $since, string $prefix): array
    {
        $sql  = ' AND `date` > UNIX_TIMESTAMP(:' . $prefix . 'since)';
        $args = [':' . $prefix . 'since' => $since];

        if (isset($criteria['br'])) {
            $sql .= ' AND `br` = :' . $prefix . 'br';
            $args[':' . $prefix . 'br'] = $criteria['br'];
        }
        if (isset($criteria['mo'])) {
            $sql .= ' AND `mo` = :' . $prefix . 'mo';
            $args[':' . $prefix . 'mo'] = $criteria['mo'];
        }
        if (isset($criteria['region'])) {
            // Same country mapping the permission filter uses, so a saved search
            // and the catalog can never disagree about what "Korea" means.
            $sql .= B2bRegions::sqlForRegion((string)$criteria['region']);
        }
        foreach (['fl', 'tra'] as $k) {
            if (isset($criteria[$k])) {
                $sql .= ' AND `' . $k . '` = :' . $prefix . $k;
                $args[':' . $prefix . $k] = $criteria[$k];
            }
        }
        // Every numeric bound, as [criteria key, column, comparison]. One loop
        // instead of a block each: adding a range later is one line.
        foreach ([
            ['yr_from',   'yr',  '>='], ['yr_to',   'yr',  '<='],
            ['vol_from',  'vol', '>='], ['vol_to',  'vol', '<='],
            ['mlg_to',    'mlg', '<='],
            ['prc_from',  'prc', '>='], ['prc_to',  'prc', '<='],
        ] as [$key, $col, $op]) {
            if (isset($criteria[$key])) {
                $sql .= ' AND `' . $col . '` ' . $op . ' :' . $prefix . $key;
                $args[':' . $prefix . $key] = (int)$criteria[$key];
            }
        }

        return [$sql, $args];
    }

    /**
     * Car ids matching a filter, newest first.
     *
     * @param bool $onlyUnseen count from last_seen_at instead of created_at —
     *                         that is the "what is new since I last looked" set
     * @return int[]
     */
    public static function matchIds(array $filter, bool $onlyUnseen = false, int $limit = 60): array
    {
        $since = (string)$filter['created_at'];
        if ($onlyUnseen && !empty($filter['last_seen_at'])) {
            $since = (string)$filter['last_seen_at'];
        }

        return self::matchIdsSince($filter, $since, $limit);
    }

    /**
     * Same match, from an explicit cut-off — what the email notifier needs, as
     * it counts from its own watermark (notified_at) rather than from the two
     * timestamps the cabinet uses.
     *
     * @return int[]
     */
    public static function matchIdsSince(array $filter, string $since, int $limit = 60): array
    {
        $criteria = self::criteriaOf($filter);
        if (!$criteria || $since === '') {
            return [];
        }

        [$where, $args] = self::where($criteria, $since, 'f');
        $guard = self::ownerGuard($filter);

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT id FROM ' . B2bConfig::prefix() . '_car_ctlg
                  WHERE `vis` = "1" AND `act` = "1"' . $where . $guard . '
                  ORDER BY `date` DESC, id DESC
                  LIMIT ' . max(1, (int)$limit)
            );
            $stmt->execute($args);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Region + catalog restriction of the filter's OWNER.
     *
     * Read from the filter row, not from the session: the notifier cron runs
     * with nobody logged in, and being alerted about a car your plan hides
     * would be worse than no alert at all. In the cabinet the owner IS the
     * logged-in partner, so the guard is the same one the catalog applies.
     */
    private static function ownerGuard(array $filter): string
    {
        $userId = (int)($filter['b2b_user_id'] ?? 0);
        if ($userId <= 0) {
            return '';
        }
        // The cabinet matches every filter twice (all / new), and the cron walks
        // whole users, so the same guard is asked for over and over.
        static $cache = [];
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $guard = B2bRegions::sqlRestriction($userId);

        $user = B2bAuth::findById($userId);
        if (!$user) {
            return $cache[$userId] = ' AND 1=0'; // account gone: match nothing, not everything
        }
        $inStock = (int)($user['allow_in_stock'] ?? 1) === 1;
        $onOrder = (int)($user['allow_on_order'] ?? 1) === 1;

        if (!$inStock && !$onOrder) {
            $guard .= ' AND 1=0';
        } elseif (!$onOrder) {
            // Legacy rows with no catalog_type count as in-stock, as in the
            // public listings (content/site/include/functions.php).
            $guard .= ' AND (`catalog_type` = "in_stock" OR `catalog_type` IS NULL)';
        } elseif (!$inStock) {
            $guard .= ' AND `catalog_type` = "on_order"';
        }

        return $cache[$userId] = $guard;
    }

    /** How many cars appeared since the partner last opened this filter. */
    public static function unseenCount(array $filter): int
    {
        return count(self::matchIds($filter, true, 200));
    }

    /** Unseen matches across every filter — drives the header dot. */
    public static function unseenTotal(int $userId): int
    {
        $n = 0;
        foreach (self::forUser($userId) as $f) {
            $n += self::unseenCount($f);
        }
        return $n;
    }

    /**
     * Watermark of the email notifier: everything published after it has
     * already been mailed. Separate from last_seen_at on purpose — opening the
     * cabinet must not cancel an email that was never sent, and an email must
     * not clear the "new" highlight the partner has not looked at yet.
     */
    public static function markNotified(array $filterIds): void
    {
        $ids = array_values(array_filter(array_map('intval', $filterIds)));
        if (!$ids) {
            return;
        }

        try {
            B2bConfig::db()->exec(
                'UPDATE ' . self::table() . ' SET notified_at = NOW()
                  WHERE id IN (' . implode(',', $ids) . ')'
            );
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'saved filter markNotified err=' . $e->getMessage());
        }
    }

    /**
     * Adds notified_at to installations created before the email alerts.
     * Cheap enough to call from the cron on every run.
     */
    public static function ensureSchema(): void
    {
        try {
            $db  = B2bConfig::db();
            $has = $db->query('SHOW COLUMNS FROM ' . self::table() . ' LIKE "notified_at"')->fetch();
            if (!$has) {
                $db->exec('ALTER TABLE ' . self::table() . ' ADD COLUMN `notified_at` DATETIME DEFAULT NULL
                            COMMENT "last email alert; NULL = never mailed"');
                // Filters that predate the alerts start their clock now. Without
                // this the first run would mail months of catalog as "new".
                $db->exec('UPDATE ' . self::table() . ' SET notified_at = NOW()');
            }
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'saved filter schema err=' . $e->getMessage());
        }
    }

    /**
     * Marks every filter as seen. Called after the section is rendered, so the
     * page the partner is looking at still highlights what was new.
     */
    public static function markAllSeen(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . self::table() . ' SET last_seen_at = NOW() WHERE b2b_user_id = :uid'
            )->execute([':uid' => $userId]);
        } catch (Throwable $e) {
            // Not fatal: the dot simply stays until the next successful load.
        }
    }

    /**
     * Readable summary of a filter, e.g. "Opel Astra · 2018+ · până la 15000 €".
     * Falls back to the stored name when the partner gave one.
     */
    public static function describe(array $filter, array $labels): string
    {
        $name = trim((string)($filter['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $c    = self::criteriaOf($filter);
        $bits = [];

        $brmo = trim(($c['br_nm'] ?? $c['br'] ?? '') . ' ' . ($c['mo_nm'] ?? $c['mo'] ?? ''));
        if ($brmo !== '') {
            $bits[] = ucwords(str_replace('_', ' ', $brmo));
        }
        if (isset($c['region']) && isset($labels['rg_' . $c['region']])) {
            $bits[] = $labels['rg_' . $c['region']];
        }
        // Fuel and gearbox read from the catalog's own translated labels, passed
        // in by the caller, so the filter is described in the site's wording.
        foreach (['fl', 'tra'] as $k) {
            if (isset($c[$k]) && isset($labels[$k][$c[$k]])) {
                $bits[] = $labels[$k][$c[$k]];
            }
        }
        if (isset($c['yr_from']) && isset($c['yr_to'])) {
            $bits[] = $c['yr_from'] . '-' . $c['yr_to'];
        } elseif (isset($c['yr_from'])) {
            $bits[] = $c['yr_from'] . '+';
        } elseif (isset($c['yr_to'])) {
            $bits[] = '≤ ' . $c['yr_to'];
        }
        if (isset($c['vol_from']) || isset($c['vol_to'])) {
            $bits[] = ($c['vol_from'] ?? 0) . '-' . ($c['vol_to'] ?? '') . ' ' . self::unitLabel($labels['cm3'] ?? null, 'cm3');
        }
        if (isset($c['mlg_to'])) {
            $bits[] = '≤ ' . number_format((float)$c['mlg_to'], 0, '.', ' ') . ' ' . self::unitLabel($labels['km'] ?? null, 'km');
        }
        if (isset($c['prc_from'])) {
            $bits[] = ($labels['from'] ?? 'de la') . ' ' . number_format((float)$c['prc_from'], 0, '.', ' ') . ' €';
        }
        if (isset($c['prc_to'])) {
            $bits[] = ($labels['to'] ?? 'până la') . ' ' . number_format((float)$c['prc_to'], 0, '.', ' ') . ' €';
        }

        return $bits ? implode(' · ', $bits) : ($labels['any'] ?? '—');
    }

    private static function unitLabel(?string $label, string $fallback): string
    {
        $label = trim((string)$label);
        if ($label === '') {
            return $fallback;
        }
        $label = preg_replace('~<sup>\s*2\s*</sup>~iu', '²', $label);
        $label = preg_replace('~<sup>\s*3\s*</sup>~iu', '³', $label);

        return trim(strip_tags($label));
    }
}
