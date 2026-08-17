<?php defined('_DOIT') or die('Restricted access');

/**
 * Traffic counter for the /ordercars/{region} landing pages.
 *
 * Answers "which import region do visitors actually open" regardless of how they
 * got there (region buttons, home banner, search, direct link). Failures are
 * swallowed: a stats table must never break the catalog.
 */

if (!function_exists('region_view_visitor')) {
    /** Stable per-day pseudonym. No raw IP is stored. */
    function region_view_visitor(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (strpos($ip, ',') !== false) { $ip = trim(explode(',', $ip)[0]); }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ip . '|' . $ua . '|' . date('Y-m-d'));
    }
}

if (!function_exists('region_view_is_bot')) {
    function region_view_is_bot(): bool
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua === '') { return true; }
        foreach (['bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'preview',
                  'monitor', 'headless', 'python-requests', 'curl/', 'wget'] as $needle) {
            if (strpos($ua, $needle) !== false) { return true; }
        }
        return false;
    }
}

if (!function_exists('region_view_log')) {
    /**
     * Records one view. Repeated hits from the same visitor on the same region
     * within 30 minutes are ignored, so a reload does not inflate the numbers.
     */
    function region_view_log(string $region): void
    {
        global $db, $prefx;

        static $done = [];
        if (isset($done[$region])) { return; }
        $done[$region] = true;

        if (!in_array($region, ['korea', 'europe', 'canada', 'usa', 'china'], true)) { return; }
        if (!isset($db) || region_view_is_bot()) { return; }

        $visitor = region_view_visitor();

        try {
            $chk = $db->prepare('SELECT 1 FROM '.$prefx.'_region_views
                WHERE `region` = :r AND `visitor` = :v
                  AND `created_at` > DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1');
            $chk->execute([':r' => $region, ':v' => $visitor]);
            if ($chk->fetchColumn()) { return; }

            $db->prepare('INSERT INTO '.$prefx.'_region_views
                (`region`, `lang`, `visitor`, `created_at`) VALUES (:r, :l, :v, NOW())')
               ->execute([
                   ':r' => $region,
                   ':l' => substr((string)($_COOKIE['lang'] ?? ''), 0, 5),
                   ':v' => $visitor,
               ]);
        } catch (Throwable $e) {
            // Stats are best-effort.
        }
    }
}
