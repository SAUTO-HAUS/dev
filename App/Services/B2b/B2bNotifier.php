<?php

namespace App\Services\B2b;

use PDO;

/**
 * Super Admin notifications for B2B actions (spec 2.3.3).
 *
 * Channels: the existing admin bell (gh3sp_crm_notifications, rendered by
 * content/admin/head.php) and WhatsApp. The caller has already persisted the
 * request, then the bell entry is written, and WhatsApp is attempted last, so a
 * Meta outage cannot lose the request.
 *
 * NOTE: the bell message is rendered with innerHTML in admin/head.php, so every
 * client-supplied value must be escaped here, where the message is built.
 */
class B2bNotifier
{
    /** Admin panel roles that receive B2B notifications. */
    private const SUPER_ADMIN_ROLES = ['gordon'];

    /**
     * A new payment invoice / reservation request: written to the admin bell only.
     * (WhatsApp to the Super Admin was removed together with the approval flow.)
     */
    public static function notifyRequest(array $user, array $car, ?array $invoice, string $comment, int $requestId): void
    {
        $lang    = $_COOKIE['lang'] ?? 'ro';
        $reqUrl  = '/' . $lang . '/adminsauto/b2b/requests?id=' . $requestId;
        $carName = trim((string)($car['title'] ?? ''));
        $carName = $carName !== '' ? $carName : ('#' . (int)$car['id']);

        self::notifyAdmins(
            '<b>Cont de plată nou</b> de la ' . self::esc(B2bAuth::displayName($user))
            . '<br>Mașină: ' . self::esc($carName)
            . ($invoice ? '<br>Proformă: ' . self::esc((string)$invoice['invoice_no']) : '')
            . ($comment !== '' ? '<br>Comentariu: ' . self::esc(mb_substr($comment, 0, 200)) : '')
            . '<br><a href="' . self::esc($reqUrl) . '" style="color:#E61E2D;">Deschide</a>'
        );
    }

    /** $message is HTML already escaped by the caller. */
    private static function notifyAdmins(string $message): void
    {
        foreach (self::superAdminIds() as $adminId) {
            try {
                B2bConfig::db()->prepare(
                    'INSERT INTO ' . B2bConfig::prefix() . '_crm_notifications (user_id, type, message)
                     VALUES (:uid, :type, :msg)'
                )->execute([':uid' => $adminId, ':type' => 'b2b', ':msg' => $message]);
            } catch (\Throwable $e) {
                B2bConfig::log('b2b_error.log', 'notifyAdmins uid=' . $adminId . ' err=' . $e->getMessage());
            }
        }
    }

    /** @return int[] */
    private static function superAdminIds(): array
    {
        try {
            $in = "'" . implode("','", self::SUPER_ADMIN_ROLES) . "'";
            $stmt = B2bConfig::db()->query(
                'SELECT id FROM ' . B2bConfig::prefix() . '_adm_usr
                  WHERE `act` = "1" AND (`role` IN (' . $in . ') OR `type` IN (' . $in . '))'
            );
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function siteUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'www.sauto.md');
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
