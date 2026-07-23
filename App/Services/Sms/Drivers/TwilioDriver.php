<?php

namespace App\Services\Sms\Drivers;

use App\Services\B2b\B2bConfig;
use App\Services\Sms\SmsDriverInterface;

/**
 * Twilio REST API (2010-04-01) over cURL, no SDK.
 *
 * Settings: b2b_sms_api_user = Account SID, b2b_sms_api_pass = Auth Token,
 * b2b_sms_sender = sender number or Messaging Service SID.
 */
class TwilioDriver implements SmsDriverInterface
{
    public function name(): string
    {
        return 'twilio';
    }

    public function send(string $to, string $message): array
    {
        $sid   = B2bConfig::get('b2b_sms_api_user');
        $token = B2bConfig::get('b2b_sms_api_pass');
        $from  = B2bConfig::get('b2b_sms_sender');

        if ($sid === '' || $token === '' || $from === '') {
            return ['ok' => false, 'error' => 'Credențiale Twilio incomplete.'];
        }

        $fields = ['To' => $to, 'Body' => $message];
        // A Messaging Service SID starts with MG; anything else is a sender number.
        if (strpos($from, 'MG') === 0) {
            $fields['MessagingServiceSid'] = $from;
        } else {
            $fields['From'] = $from;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_USERPWD        => $sid . ':' . $token,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            B2bConfig::log('b2b_sms.log', 'twilio CURL ERROR to=' . $to . ' err=' . $err);
            return ['ok' => false, 'error' => 'Eroare de rețea la trimiterea SMS-ului.'];
        }

        $json = json_decode((string)$body, true);

        if ($code < 200 || $code >= 300) {
            $msg = is_array($json) ? ($json['message'] ?? '') : '';
            B2bConfig::log('b2b_sms.log', 'twilio HTTP ' . $code . ' to=' . $to . ' msg=' . $msg);
            return ['ok' => false, 'error' => 'Twilio: ' . ($msg !== '' ? $msg : 'HTTP ' . $code)];
        }

        B2bConfig::log('b2b_sms.log', 'twilio OK to=' . $to . ' sid=' . ($json['sid'] ?? ''));
        return ['ok' => true, 'id' => (string)($json['sid'] ?? '')];
    }
}
