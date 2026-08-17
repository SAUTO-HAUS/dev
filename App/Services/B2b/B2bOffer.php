<?php

namespace App\Services\B2b;

use PDO;
use Throwable;

/**
 * Expiry ("offer valid until") for the B2B price tables.
 *
 * One deadline per pricing scope, stored in gh3sp_b2b_price_expiry with the same
 * NULL-means-global convention as the four price tables:
 *   - b2b_user_id IS NULL -> the general offer, shared by every partner;
 *   - b2b_user_id = X     -> that client's own offer, set on their pricing tab.
 *
 * On expiry the partner does not lose everything at once, they fall one step
 * down the same ladder the prices themselves use:
 *
 *   client's own offer expired  ->  the GLOBAL B2B prices apply
 *   global offer expired        ->  the PUBLIC (guest) prices apply
 *
 * A missing row, or expires_at NULL, means "no time limit" for that scope.
 */
class B2bOffer
{
    /** Resolved deadlines per scope key, so one request hits the table once. */
    private static array $cache = [];

    private static function table(): string
    {
        return B2bConfig::prefix() . '_b2b_price_expiry';
    }

    /**
     * Deadline for one scope as a unix timestamp, or null when that scope has no
     * limit. $userId null = the general offer.
     */
    public static function deadline(?int $userId): ?int
    {
        $key = $userId > 0 ? (string)$userId : 'global';
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $ts = null;
        try {
            $where = $userId > 0 ? 'b2b_user_id = :uid' : 'b2b_user_id IS NULL';
            $stmt  = B2bConfig::db()->prepare(
                'SELECT expires_at FROM ' . self::table() . ' WHERE ' . $where . ' LIMIT 1'
            );
            $stmt->execute($userId > 0 ? [':uid' => $userId] : []);
            $val = $stmt->fetchColumn();
            if ($val) {
                $t = strtotime((string)$val);
                if ($t !== false) {
                    $ts = $t;
                }
            }
        } catch (Throwable $e) {
            // Table not migrated yet: behave as "no limit" rather than cutting
            // every partner off from their prices.
        }

        self::$cache[$key] = $ts;
        return $ts;
    }

    /** True when the scope has a deadline and it has passed. */
    public static function isExpired(?int $userId): bool
    {
        $ts = self::deadline($userId);
        return $ts !== null && $ts <= time();
    }

    /**
     * The pricing scope a partner is actually entitled to right now, after the
     * cascade. This is the single place that decides it; the public pricing
     * helpers and the countdown both read from here so they cannot disagree.
     *
     * @return array{0: bool, 1: int|null, 2: int|null}
     *         [0] use the B2B tables at all
     *         [1] user id whose own tables to use (null = the global B2B set)
     *         [2] deadline that is now counting down (null = no limit)
     */
    /**
     * Master switch (/adminsauto/b2b/users): when on, every partner's own deadline
     * is ignored and the general offer decides for all of them again. Since a
     * personal deadline outranks the general one, without this there is no single
     * place left to end the preferential prices for everybody at once.
     *
     * A setting, not a bulk edit of the dates: switching it back off restores each
     * partner's own deadline exactly as it was.
     */
    public static function personalDeadlinesIgnored(): bool
    {
        return B2bConfig::get('b2b_ignore_personal_deadlines', '0') === '1';
    }

    public static function scopeFor(int $userId): array
    {
        $globalTs = self::deadline(null);
        $ownTs    = self::personalDeadlinesIgnored()
            ? null                                          // the switch is on: general offer only
            : ($userId > 0 ? self::deadline($userId) : null);

        // A partner's OWN deadline outranks the general one, in both directions:
        // it keeps him on his own prices after the general offer has lapsed, and it
        // cuts him off while the general one still runs. The general offer used to
        // gate everything, so one lapsed date silently put every partner back on
        // public prices — personal tables, personal deadline and countdown included.
        if ($ownTs !== null) {
            if ($ownTs > time()) {
                return [true, $userId, $ownTs];
            }
            // His own offer ran out: he drops to the general one, if it still runs.
            return ($globalTs !== null && $globalTs <= time())
                ? [false, null, null]
                : [true, null, $globalTs];
        }

        // No deadline of his own: the general offer decides for him.
        if ($globalTs !== null && $globalTs <= time()) {
            return [false, null, null];
        }

        return [true, $userId > 0 ? $userId : null, $globalTs];
    }

    /**
     * Sets (or clears) a scope's deadline. Delete + insert, the same "full
     * replace within the scope" idiom the pricing saves use.
     *
     * @param string $localDateTime 'Y-m-d H:i' from the admin form; '' clears it
     */
    public static function save(?int $userId, string $localDateTime, int $adminId): void
    {
        $db    = B2bConfig::db();
        $where = $userId > 0 ? 'b2b_user_id = :uid' : 'b2b_user_id IS NULL';

        $expiresAt = null;
        $trimmed   = trim($localDateTime);
        if ($trimmed !== '') {
            $ts = strtotime(str_replace('T', ' ', $trimmed));
            if ($ts === false) {
                throw new \RuntimeException('invalid datetime');
            }
            $expiresAt = date('Y-m-d H:i:s', $ts);
        }

        $del = $db->prepare('DELETE FROM ' . self::table() . ' WHERE ' . $where);
        $del->execute($userId > 0 ? [':uid' => $userId] : []);

        // No deadline is stored as the absence of a row, so "no limit" and "never
        // configured" cannot drift apart.
        if ($expiresAt !== null) {
            $db->prepare(
                'INSERT INTO ' . self::table() . ' (`b2b_user_id`, `expires_at`, `updated_by`)
                 VALUES (:uid, :exp, :by)'
            )->execute([
                ':uid' => $userId > 0 ? $userId : null,
                ':exp' => $expiresAt,
                ':by'  => $adminId > 0 ? $adminId : null,
            ]);
        }

        self::$cache = [];
    }

    /** Value for the admin's datetime-local input ('' when no deadline). */
    public static function inputValue(?int $userId): string
    {
        $ts = self::deadline($userId);
        return $ts === null ? '' : date('Y-m-d\TH:i', $ts);
    }

    // ---- Wording -------------------------------------------------------------
    // Kept here rather than in the view so the countdown the admin sees and the
    // one the partner sees are produced by the same code.

    /**
     * "price offer expires in" label.
     *
     * Deliberately NOT the plain "Oferta expiră peste:" used by the per-car offer
     * clock on the car page — both can be on screen at once and they mean
     * different things: that one is about the car, this one about the partner's
     * price reverting to the standard one.
     */
    public static function label(string $lang): string
    {
        $l = [
            'ro' => 'Oferta de preț expiră peste:',
            'ru' => 'Ценовое предложение истекает через:',
            'en' => 'Price offer expires in:',
        ];
        return $l[$lang] ?? $l['ro'];
    }

    /**
     * Plural forms per unit: [days, hours, minutes], three forms each
     * (ro: 1 / 2-19 / 20+, ru: 1 / 2-4 / 5+, en: 1 / many / many).
     *
     * @return array{0: string[], 1: string[], 2: string[]}
     */
    public static function unitForms(string $lang): array
    {
        $f = [
            'ro' => [['zi', 'zile', 'de zile'], ['oră', 'ore', 'de ore'], ['minut', 'minute', 'de minute']],
            'ru' => [['день', 'дня', 'дней'], ['час', 'часа', 'часов'], ['минута', 'минуты', 'минут']],
            'en' => [['day', 'days', 'days'], ['hour', 'hours', 'hours'], ['minute', 'minutes', 'minutes']],
        ];
        return $f[$lang] ?? $f['ro'];
    }

    /** Plural form index for $n; mirrored by pluralIndex() in sitescripts.js. */
    public static function pluralIndex(int $n, string $lang): int
    {
        if ($lang === 'ru') {
            $m10 = $n % 10;
            $m100 = $n % 100;
            if ($m10 === 1 && $m100 !== 11) return 0;
            if ($m10 >= 2 && $m10 <= 4 && ($m100 < 12 || $m100 > 14)) return 1;
            return 2;
        }
        if ($lang === 'en') {
            return $n === 1 ? 0 : 1;
        }
        // Romanian takes "de" from 20 upwards (20 de zile, but 101 zile).
        if ($n === 1) return 0;
        $m100 = $n % 100;
        return ($m100 >= 1 && $m100 <= 19) ? 1 : 2;
    }

    /**
     * "3 zile" / "18 ore" / "42 de minute": days, then hours under a day, then
     * minutes under an hour. Never returns "0 minute" — a live offer always has
     * at least a minute left to show.
     */
    public static function humanize(int $seconds, string $lang): string
    {
        [$d, $h, $m] = self::unitForms($lang);

        if ($seconds >= 86400) {
            $n = (int)floor($seconds / 86400);
            return $n.' '.$d[self::pluralIndex($n, $lang)];
        }
        if ($seconds >= 3600) {
            $n = (int)floor($seconds / 3600);
            return $n.' '.$h[self::pluralIndex($n, $lang)];
        }
        $n = max(1, (int)floor($seconds / 60));
        return $n.' '.$m[self::pluralIndex($n, $lang)];
    }
}
