<?php

namespace App\Services\B2b;

use App\Core\Container;
use PDO;

/**
 * Shared access to PDO, the table prefix and the B2B settings.
 *
 * Settings live in the existing gh3sp_settings key-value store, same convention
 * as App\Services\PublicationService, so they are editable from the admin panel.
 */
class B2bConfig
{
    /** Regions that can be granted to a partner. */
    public const REGIONS = ['korea', 'europe', 'china', 'usa'];

    /** B2B session lifetime, seconds. */
    public const SESSION_TTL = 43200;

    /** OTP lifetime, seconds (spec: 5 minutes). */
    public const OTP_TTL = 300;

    /** Wrong attempts accepted per OTP before it is burned. */
    public const OTP_MAX_ATTEMPTS = 5;

    /** Failed logins before the account is locked temporarily. */
    public const LOGIN_MAX_ATTEMPTS = 5;

    /** Lockout duration after too many failed logins, seconds. */
    public const LOGIN_LOCK_TTL = 900;

    public const COOKIE_NAME = 'b2b_sess';

    private static ?array $cache = null;

    public static function db(): PDO
    {
        return Container::get('db');
    }

    public static function prefix(): string
    {
        return Container::get('prefix');
    }

    /** table('users') => gh3sp_b2b_users */
    public static function table(string $short): string
    {
        return self::prefix() . '_b2b_' . $short;
    }

    /** All b2b_* settings are loaded once per request. */
    public static function get(string $key, string $default = ''): string
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                $stmt = self::db()->query(
                    'SELECT `name`, `value` FROM ' . self::prefix() . '_settings WHERE `name` LIKE "b2b\_%"'
                );
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    self::$cache[$row['name']] = (string)$row['value'];
                }
            } catch (\Throwable $e) {
                // A missing settings table must not take the public site down.
            }
        }

        $val = self::$cache[$key] ?? '';
        return $val !== '' ? $val : $default;
    }

    public static function set(string $key, string $value): void
    {
        self::db()->prepare(
            'INSERT INTO ' . self::prefix() . '_settings (`name`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        )->execute([$key, $value]);

        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    /** Real client IP; trusts only the first valid X-Forwarded-For entry. */
    public static function clientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    public static function userAgent(): string
    {
        return mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    /** Best-effort write to /logs; never throws. */
    public static function log(string $file, string $message): void
    {
        try {
            $dir = ($_SERVER['DOCUMENT_ROOT'] ?? '.') . '/logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents(
                $dir . '/' . $file,
                date('Y-m-d H:i:s') . ' ' . $message . "\n",
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable $e) {
            // intentionally ignored
        }
    }
}
