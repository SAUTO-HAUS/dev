<?php

namespace App\Services\B2b;

/**
 * Phone handling for the B2B module.
 *
 * Signup is Moldova-only: the client types 8 digits after a fixed +373 prefix.
 * The Super Admin contact number used for WhatsApp is admin-entered and may sit
 * abroad, so it goes through the lenient variant instead.
 */
final class B2bPhone
{
    public const MD_DIAL   = '+373';
    public const MD_DIGITS = 8;

    /**
     * Moldovan number -> +373XXXXXXXX, or '' when it is not one.
     *
     * Accepts what people actually type: "60123456", "060123456",
     * "+373 60 123 456", "00373...". Everything else is rejected, because a
     * foreign number cannot be registered here.
     */
    public static function normalizeMd(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '00373') === 0) {
            $digits = substr($digits, 5);
        } elseif (strpos($digits, '373') === 0 && strlen($digits) === 11) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) === 9 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        return strlen($digits) === self::MD_DIGITS ? self::MD_DIAL . $digits : '';
    }

    /**
     * Stored Moldovan number (+373XXXXXXXX) -> grouped local display form
     * "0XX XX XX XX". Anything that is not a +373 number is returned unchanged.
     */
    public static function local(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strpos($digits, '373') === 0 && strlen($digits) === 11) {
            $d = substr($digits, 3); // 8 local digits, e.g. 79975967
            return sprintf('0%s %s %s %s', substr($d, 0, 2), substr($d, 2, 2), substr($d, 4, 2), substr($d, 6, 2));
        }
        return $phone;
    }

    /**
     * Any number -> E.164, for admin-entered contacts. Returns '' when the input
     * cannot be a phone number at all.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '00') === 0) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9 && $digits[0] === '0') {
            $digits = '373' . substr($digits, 1);
        }
        if (strlen($digits) === self::MD_DIGITS) {
            $digits = '373' . $digits;
        }

        return (strlen($digits) < 10 || strlen($digits) > 15) ? '' : '+' . $digits;
    }
}
