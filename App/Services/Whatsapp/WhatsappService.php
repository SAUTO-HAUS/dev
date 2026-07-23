<?php

namespace App\Services\Whatsapp;

use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bCountries;

/**
 * WhatsApp notifications, two modes via the `b2b_whatsapp_driver` setting:
 *
 *   cloud_api - server-side send through the WhatsApp Business Cloud API.
 *               Needs a WABA, a verified number, a Phone Number ID and a
 *               permanent token; business-initiated messages outside the 24h
 *               window also need an approved template.
 *   walink    - zero-config fallback: a prefilled https://wa.me/ link for the
 *               frontend to open, the pattern already used across the CRM.
 */
class WhatsappService
{
    /** @return array{ok: bool, mode: string, link?: string, error?: string} */
    public static function notifySuperAdmin(string $message): array
    {
        $phone = B2bCountries::normalize(B2bConfig::get('b2b_superadmin_phone'));

        if ($phone === '') {
            return ['ok' => false, 'mode' => 'none', 'error' => 'Numărul Super Admin nu este configurat.'];
        }

        if (B2bConfig::get('b2b_whatsapp_driver', 'walink') === 'cloud_api') {
            $res = self::sendCloudApi($phone, $message);
            if ($res['ok']) {
                return ['ok' => true, 'mode' => 'cloud_api'];
            }
            // API unavailable: still hand back the link so the action can complete.
            return [
                'ok'    => true,
                'mode'  => 'walink',
                'link'  => self::waLink($phone, $message),
                'error' => $res['error'] ?? null,
            ];
        }

        return ['ok' => true, 'mode' => 'walink', 'link' => self::waLink($phone, $message)];
    }

    public static function waLink(string $phone, string $message): string
    {
        $normalized = B2bCountries::normalize($phone);
        $digits = preg_replace('/\D+/', '', $normalized !== '' ? $normalized : $phone);

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
    }

    /** @return array{ok: bool, error?: string} */
    private static function sendCloudApi(string $phone, string $message): array
    {
        $phoneId = B2bConfig::get('b2b_whatsapp_phone_id');
        $token   = B2bConfig::get('b2b_whatsapp_token');

        if ($phoneId === '' || $token === '') {
            return ['ok' => false, 'error' => 'Credențiale WhatsApp Cloud API incomplete.'];
        }

        $template = B2bConfig::get('b2b_whatsapp_template');
        $to = ltrim($phone, '+');

        if ($template !== '') {
            // Template messages are the only kind Meta accepts outside the 24h
            // window; the text goes in as body parameter {{1}}.
            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'template'          => [
                    'name'       => $template,
                    'language'   => ['code' => 'ro'],
                    'components' => [[
                        'type'       => 'body',
                        'parameters' => [['type' => 'text', 'text' => $message]],
                    ]],
                ],
            ];
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['preview_url' => true, 'body' => $message],
            ];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://graph.facebook.com/v22.0/' . rawurlencode($phoneId) . '/messages',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $err !== '') {
            B2bConfig::log('b2b_whatsapp.log', 'CURL ERROR to=' . $to . ' err=' . $err);
            return ['ok' => false, 'error' => 'Eroare de rețea către WhatsApp.'];
        }

        if ($code < 200 || $code >= 300) {
            B2bConfig::log('b2b_whatsapp.log', 'HTTP ' . $code . ' to=' . $to . ' body=' . mb_substr((string)$body, 0, 400));
            $json = json_decode((string)$body, true);
            $msg  = is_array($json) ? ($json['error']['message'] ?? '') : '';
            return ['ok' => false, 'error' => 'WhatsApp: ' . ($msg !== '' ? $msg : 'HTTP ' . $code)];
        }

        B2bConfig::log('b2b_whatsapp.log', 'OK to=' . $to);
        return ['ok' => true];
    }
}
