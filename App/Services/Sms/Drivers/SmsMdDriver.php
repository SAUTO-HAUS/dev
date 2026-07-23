<?php

namespace App\Services\Sms\Drivers;

use App\Services\B2b\B2bConfig;
use App\Services\Sms\SmsDriverInterface;

/**
 * Local HTTP SMS gateway (sms.md and compatible).
 *
 * Settings: b2b_sms_api_user, b2b_sms_api_pass, b2b_sms_sender and
 * b2b_sms_api_key (the gateway base URL). If the contracted provider expects
 * different field names, payload() is the only place to adjust.
 */
class SmsMdDriver implements SmsDriverInterface
{
    public function name(): string
    {
        return 'smsmd';
    }

    public function send(string $to, string $message): array
    {
        $endpoint = B2bConfig::get('b2b_sms_api_key');
        if ($endpoint === '' || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'Endpoint SMS neconfigurat (b2b_sms_api_key).'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($this->payload($to, $message)),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            B2bConfig::log('b2b_sms.log', 'smsmd CURL ERROR to=' . $to . ' err=' . $err);
            return ['ok' => false, 'error' => 'Eroare de rețea la trimiterea SMS-ului.'];
        }

        if ($code < 200 || $code >= 300) {
            B2bConfig::log('b2b_sms.log', 'smsmd HTTP ' . $code . ' to=' . $to . ' body=' . mb_substr((string)$body, 0, 300));
            return ['ok' => false, 'error' => 'Gateway-ul SMS a returnat HTTP ' . $code . '.'];
        }

        B2bConfig::log('b2b_sms.log', 'smsmd OK to=' . $to);
        return ['ok' => true, 'id' => (string)$code];
    }

    private function payload(string $to, string $message): array
    {
        return [
            'user'     => B2bConfig::get('b2b_sms_api_user'),
            'password' => B2bConfig::get('b2b_sms_api_pass'),
            'from'     => B2bConfig::get('b2b_sms_sender', 'SAUTO'),
            'to'       => $to,
            'text'     => $message,
        ];
    }
}
