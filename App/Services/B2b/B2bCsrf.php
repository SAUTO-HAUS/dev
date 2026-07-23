<?php

namespace App\Services\B2b;

/**
 * CSRF protection for the B2B forms.
 *
 * The public frontend has no CSRF mechanism; it is introduced here because the
 * B2B flow performs cookie-authenticated writes (invoices, requests, saved cars).
 * The token lives in the PHP session, already started by index.php / ajax.php.
 */
class B2bCsrf
{
    private const KEY = 'b2b_csrf_token';

    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // No session means no token; check() then rejects every request.
            return '';
        }

        if (empty($_SESSION[self::KEY]) || !is_string($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::KEY];
    }

    public static function check(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $expected = $_SESSION[self::KEY] ?? '';
        if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '" />';
    }
}
