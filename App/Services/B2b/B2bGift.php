<?php

namespace App\Services\B2b;

use PDO;
use Throwable;

/**
 * Gifts granted to a B2B partner (free polishing, dry cleaning, ...).
 *
 * A gift is an EVENT, not a property of the account: the Super Admin grants one
 * from the client list, the same client may receive several over time, and each
 * keeps its own date. That is why nothing here overwrites a previous gift.
 *
 * Two states drive the whole UI:
 *   - seen_at IS NULL    -> "new": the header shows a dot and the cabinet puts
 *                           the gift on top as a highlighted banner;
 *   - revoked_at IS NULL -> still granted. Withdrawing is a soft delete, so a
 *                           mistake disappears for the partner without erasing
 *                           the record that it happened.
 *
 * The service catalogue is a constant rather than free text so the wording is
 * identical everywhere, translatable into the three site languages, and later
 * reportable ("how many polishes did we give away?"). The free-text note covers
 * anything outside the list.
 */
class B2bGift
{
    /** Grantable services. Extend the list here and in labels(). */
    public const SERVICES = ['polish', 'dry_clean'];

    /** Note length must match the column, or MySQL would silently truncate. */
    public const NOTE_MAX = 500;

    private static function table(): string
    {
        return B2bConfig::prefix() . '_b2b_gifts';
    }

    /** Service code => label, in one of the three site languages. */
    public static function labels(string $lang): array
    {
        $l = [
            'ro' => ['polish' => 'Polish', 'dry_clean' => 'Curățare chimică'],
            'ru' => ['polish' => 'Полировка', 'dry_clean' => 'Химчистка'],
            'en' => ['polish' => 'Polishing', 'dry_clean' => 'Dry cleaning'],
        ];
        return $l[$lang] ?? $l['ro'];
    }

    /**
     * Human list of what a gift contains, e.g. "Polish, Curățare chimică".
     * Unknown codes are dropped rather than printed raw: the catalogue may
     * shrink, and a partner should never see an internal code.
     */
    public static function describe(array $gift, string $lang): string
    {
        $labels = self::labels($lang);
        $out    = [];
        foreach (self::itemsOf($gift) as $code) {
            if (isset($labels[$code])) {
                $out[] = $labels[$code];
            }
        }
        return implode(', ', $out);
    }

    /** @return string[] service codes stored on a gift row */
    public static function itemsOf(array $gift): array
    {
        $raw = (string)($gift['items'] ?? '');
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw)), 'strlen'));
    }

    /**
     * Grants a gift. Returns the new id, or 0 when nothing was granted.
     *
     * At least one service or a note is required — an empty gift would show the
     * partner a notification with no content.
     *
     * @param string[] $items service codes; anything outside SERVICES is ignored
     */
    public static function send(int $userId, array $items, string $note, int $adminId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        $items = array_values(array_intersect(
            array_map('strval', $items),
            self::SERVICES
        ));
        $note = mb_substr(trim($note), 0, self::NOTE_MAX);

        if (!$items && $note === '') {
            return 0;
        }

        try {
            $db = B2bConfig::db();
            $db->prepare(
                'INSERT INTO ' . self::table() . ' (`b2b_user_id`, `items`, `note`, `created_by`)
                 VALUES (:uid, :items, :note, :by)'
            )->execute([
                ':uid'   => $userId,
                ':items' => implode(',', $items),
                ':note'  => $note,
                ':by'    => $adminId > 0 ? $adminId : null,
            ]);
            return (int)$db->lastInsertId();
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'gift send err=' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Withdraws a gift (soft delete). Returns the owner's id so the caller can
     * audit it, or 0 when the gift does not exist or was already withdrawn.
     */
    public static function revoke(int $giftId, int $adminId): int
    {
        if ($giftId <= 0) {
            return 0;
        }

        try {
            $db   = B2bConfig::db();
            $stmt = $db->prepare('SELECT b2b_user_id FROM ' . self::table()
                . ' WHERE id = :id AND revoked_at IS NULL LIMIT 1');
            $stmt->execute([':id' => $giftId]);
            $ownerId = (int)($stmt->fetchColumn() ?: 0);
            if ($ownerId <= 0) {
                return 0;
            }

            $db->prepare('UPDATE ' . self::table()
                . ' SET revoked_at = NOW(), revoked_by = :by WHERE id = :id')
               ->execute([':by' => $adminId > 0 ? $adminId : null, ':id' => $giftId]);

            return $ownerId;
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'gift revoke err=' . $e->getMessage());
            return 0;
        }
    }

    /**
     * A partner's active gifts, newest first. Withdrawn ones are never returned:
     * for the partner they simply no longer exist.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forUser(int $userId, int $limit = 50): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . self::table() . '
                  WHERE b2b_user_id = :uid AND revoked_at IS NULL
                  ORDER BY created_at DESC, id DESC
                  LIMIT ' . max(1, (int)$limit)
            );
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /** Every gift of a client including withdrawn ones — admin view only. */
    public static function historyFor(int $userId, int $limit = 50): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . self::table() . '
                  WHERE b2b_user_id = :uid
                  ORDER BY created_at DESC, id DESC
                  LIMIT ' . max(1, (int)$limit)
            );
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /** Active gifts a partner has not opened yet — drives the header dot. */
    public static function unseenCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT COUNT(*) FROM ' . self::table()
                . ' WHERE b2b_user_id = :uid AND seen_at IS NULL AND revoked_at IS NULL'
            );
            $stmt->execute([':uid' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            // Missing table (not migrated): behave as "no gifts" rather than
            // breaking the header on every page.
            return 0;
        }
    }

    /** Active gifts in total — the count on the cabinet nav card. */
    public static function countForUser(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT COUNT(*) FROM ' . self::table()
                . ' WHERE b2b_user_id = :uid AND revoked_at IS NULL'
            );
            $stmt->execute([':uid' => $userId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Marks everything the partner currently has as seen. Called when the Gifts
     * tab is rendered — AFTER the rows were read, so the page the partner is
     * looking at still highlights what was new.
     */
    public static function markAllSeen(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . self::table()
                . ' SET seen_at = NOW() WHERE b2b_user_id = :uid AND seen_at IS NULL AND revoked_at IS NULL'
            )->execute([':uid' => $userId]);
        } catch (Throwable $e) {
            // Not fatal: the dot simply stays until the next successful load.
        }
    }
}
