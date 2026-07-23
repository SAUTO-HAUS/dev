<?php

namespace App\Services\Sms;

/**
 * SMS provider contract (spec 5: swapping providers must not touch the 2FA logic).
 */
interface SmsDriverInterface
{
    /**
     * @param string $to E.164 number (+373...)
     * @return array{ok: bool, error?: string, id?: string}
     */
    public function send(string $to, string $message): array;

    /** Driver id as stored in the settings. */
    public function name(): string;
}
