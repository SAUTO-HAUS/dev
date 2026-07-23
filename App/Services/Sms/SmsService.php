<?php

namespace App\Services\Sms;

use App\Services\B2b\B2bConfig;
use App\Services\Sms\Drivers\LogDriver;
use App\Services\Sms\Drivers\SmsMdDriver;
use App\Services\Sms\Drivers\TwilioDriver;

/**
 * Single entry point for sending SMS. The active driver comes from the
 * `b2b_sms_driver` setting; callers never know which provider is configured.
 */
class SmsService
{
    public static function driver(): SmsDriverInterface
    {
        switch (B2bConfig::get('b2b_sms_driver', 'log')) {
            case 'smsmd':  return new SmsMdDriver();
            case 'twilio': return new TwilioDriver();
            default:       return new LogDriver();
        }
    }

    /** @return array{ok: bool, error?: string, id?: string} */
    public static function send(string $phone, string $message): array
    {
        $to = self::normalize($phone);
        if ($to === '') {
            return ['ok' => false, 'error' => 'Număr de telefon invalid.'];
        }

        try {
            return self::driver()->send($to, $message);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_sms.log', 'send EXCEPTION to=' . $to . ' err=' . $e->getMessage());
            return ['ok' => false, 'error' => 'Serviciul SMS este temporar indisponibil.'];
        }
    }

    /**
     * Normalises to E.164. Moldovan local numbers (8 digits, or 9 with a leading
     * zero) get the +373 prefix. Returns '' when the result is not plausible.
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return '';
        }

        if (strpos($digits, '00') === 0) {           // 00373... -> 373...
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9 && $digits[0] === '0') {  // 0XXXXXXXX -> 373XXXXXXXX
            $digits = '373' . substr($digits, 1);
        }
        if (strlen($digits) === 8) {                 // XXXXXXXX -> 373XXXXXXXX
            $digits = '373' . $digits;
        }

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return '';
        }

        return '+' . $digits;
    }
}
