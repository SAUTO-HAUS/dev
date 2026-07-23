<?php

namespace App\Services\B2b;

use PDO;

/**
 * B2B activity log (spec 3.3 / 4.3).
 * Logging is best-effort: a write failure must never interrupt navigation.
 */
class B2bAudit
{
    public const LOGIN               = 'login';
    public const LOGIN_FAILED        = 'login_failed';
    public const LOGOUT              = 'logout';
    public const PAGE_VIEW           = 'page_view';
    public const GENERATE_INVOICE    = 'generate_invoice';
    public const SEND_TO_ADMIN       = 'send_to_admin';
    public const REGION_DENIED       = 'region_denied';
    public const SAVE_CAR            = 'save_car';
    public const UNSAVE_CAR          = 'unsave_car';
    public const REGISTER            = 'register';
    public const OTP_SENT            = 'otp_sent';
    public const OTP_FAILED          = 'otp_failed';
    public const PASSWORD_RESET      = 'password_reset';
    public const STATUS_CHANGED      = 'status_changed';
    public const PERMISSIONS_CHANGED = 'permissions_changed';

    public static function log(int $userId, string $action, array $details = [], ?int $targetId = null): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            B2bConfig::db()->prepare(
                'INSERT INTO ' . B2bConfig::table('activity_logs')
                . ' (b2b_user_id, action_type, details, target_id, ip_address, user_agent)
                   VALUES (:uid, :action, :details, :target, :ip, :ua)'
            )->execute([
                ':uid'     => $userId,
                ':action'  => mb_substr($action, 0, 50),
                ':details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                ':target'  => $targetId,
                ':ip'      => B2bConfig::clientIp(),
                ':ua'      => B2bConfig::userAgent(),
            ]);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'audit uid=' . $userId . ' action=' . $action . ' err=' . $e->getMessage());
        }
    }

    /** Car view, deduplicated over 30 minutes so refreshes do not flood the log. */
    public static function logCarView(int $userId, int $carId): void
    {
        if ($userId <= 0 || $carId <= 0) {
            return;
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT id FROM ' . B2bConfig::table('activity_logs')
                . ' WHERE b2b_user_id = :uid AND action_type = :action AND target_id = :car
                    AND created_at > (NOW() - INTERVAL 30 MINUTE) LIMIT 1'
            );
            $stmt->execute([':uid' => $userId, ':action' => self::PAGE_VIEW, ':car' => $carId]);
            if ($stmt->fetchColumn()) {
                return;
            }
        } catch (\Throwable $e) {
            // On failure log anyway: a duplicate row beats a missing audit entry.
        }

        self::log($userId, self::PAGE_VIEW, ['car_id' => $carId], $carId);
    }

    /**
     * Car ids the partner recently looked at, newest first.
     * Feeds cabinet tab 1, which per spec 2.3 lists both viewed and saved cars.
     *
     * @return int[]
     */
    public static function recentlyViewedCarIds(int $userId, int $limit = 50): array
    {
        if ($userId <= 0) {
            return [];
        }

        $limit = max(1, min(200, $limit)); // inlined below: no placeholder in LIMIT

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT target_id FROM ' . B2bConfig::table('activity_logs')
                . ' WHERE b2b_user_id = :uid AND action_type = :action AND target_id IS NOT NULL
                    GROUP BY target_id
                    ORDER BY MAX(created_at) DESC
                    LIMIT ' . $limit
            );
            $stmt->execute([':uid' => $userId, ':action' => self::PAGE_VIEW]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 200, int $offset = 0, string $action = ''): array
    {
        $limit  = max(1, min(1000, $limit));
        $offset = max(0, $offset);

        $sql  = 'SELECT * FROM ' . B2bConfig::table('activity_logs') . ' WHERE b2b_user_id = :uid';
        $args = [':uid' => $userId];

        if ($action !== '') {
            $sql .= ' AND action_type = :action';
            $args[':action'] = $action;
        }

        // LIMIT/OFFSET inlined as ints (clamped above): placeholders are not
        // allowed there with native prepares.
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

        try {
            $stmt = B2bConfig::db()->prepare($sql);
            $stmt->execute($args);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function countForUser(int $userId, string $action = ''): int
    {
        $sql  = 'SELECT COUNT(*) FROM ' . B2bConfig::table('activity_logs') . ' WHERE b2b_user_id = :uid';
        $args = [':uid' => $userId];

        if ($action !== '') {
            $sql .= ' AND action_type = :action';
            $args[':action'] = $action;
        }

        try {
            $stmt = B2bConfig::db()->prepare($sql);
            $stmt->execute($args);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
