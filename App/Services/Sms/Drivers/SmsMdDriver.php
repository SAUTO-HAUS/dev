<?php

namespace App\Services\Sms\Drivers;

use App\Services\B2b\B2bConfig;
use App\Services\Sms\SmsDriverInterface;

/**
 * SMS.md REST API.
 *
 *   POST https://api.sms.md/v1/send
 *   Authorization: Bearer <API_KEY>
 *   Content-Type: application/json
 *   {"to": "+373...", "from": "SenderId", "message": "..."}
 *
 * A 2xx returns {"id", "receiver", "status", "message", "dateCreate"}, where
 * status is "queued" — the message is accepted, not yet delivered.
 *
 * Settings: b2b_sms_api_key (Bearer token), b2b_sms_sender (approved sender ID),
 * b2b_sms_api_url (optional, only to point at a staging endpoint).
 */
class SmsMdDriver implements SmsDriverInterface
{
    private const ENDPOINT = 'https://api.sms.md/v1/send';

    public function name(): string
    {
        return 'smsmd';
    }

    public function send(string $to, string $message): array
    {
        $key = B2bConfig::get('b2b_sms_api_key');
        if ($key === '') {
            return ['ok' => false, 'error' => 'Cheia API SMS.md nu este configurată.'];
        }

        $from = B2bConfig::get('b2b_sms_sender', 'SAUTO');

        $endpoint = B2bConfig::get('b2b_sms_api_url');
        if ($endpoint === '' || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
            $endpoint = self::ENDPOINT;
        }

        $payload = json_encode([
            'to'      => $to,
            'from'    => $from,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
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

        $json = json_decode((string)$body, true);

        if ($code < 200 || $code >= 300) {
            // The API reports the reason in "message"; keep it for the log, but
            // show the client something generic.
            $reason = is_array($json) ? ($json['message'] ?? $json['error'] ?? '') : '';
            B2bConfig::log('b2b_sms.log',
                'smsmd HTTP ' . $code . ' to=' . $to . ' reason=' . $reason
                . ' body=' . mb_substr((string)$body, 0, 300));

            if ($code === 401 || $code === 403) {
                return ['ok' => false, 'error' => 'Cheia API SMS.md este invalidă sau expirată.'];
            }
            if ($code === 402) {
                return ['ok' => false, 'error' => 'Credit SMS insuficient.'];
            }
            return ['ok' => false, 'error' => 'SMS.md: ' . ($reason !== '' ? $reason : 'HTTP ' . $code)];
        }

        // 2xx with an explicit failure status: treat it as a failure, not a send.
        $status = is_array($json) ? (string)($json['status'] ?? '') : '';
        if ($status !== '' && in_array(strtolower($status), ['failed', 'rejected', 'error'], true)) {
            B2bConfig::log('b2b_sms.log', 'smsmd REJECTED to=' . $to . ' status=' . $status);
            return ['ok' => false, 'error' => 'SMS-ul a fost respins de operator.'];
        }

        $id = is_array($json) ? (string)($json['id'] ?? '') : '';
        B2bConfig::log('b2b_sms.log', 'smsmd OK to=' . $to . ' id=' . $id . ' status=' . $status);

        return ['ok' => true, 'id' => $id];
    }
}
