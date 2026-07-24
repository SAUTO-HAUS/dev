<?php

namespace App\Services\Whatsapp;

use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bPhone;

/**
 * WhatsApp notifications for the Super Admin.
 *
 * One channel: a prefilled https://wa.me/ link the frontend opens, so the Super
 * Admin receives the message with a single tap. No Meta Business onboarding, no
 * server-side send. The admin bell is the reliable channel; this is convenience.
 */
class WhatsappService
{
    /** @return array{ok: bool, links?: string[], error?: string} */
    public static function notifySuperAdmin(string $message): array
    {
        $phones = self::superAdminPhones();

        if (!$phones) {
            return ['ok' => false, 'error' => 'Numărul Super Admin nu este configurat.'];
        }

        return ['ok' => true, 'links' => self::waLinks($phones, $message)];
    }

    /**
     * The Super Admin numbers, from the two dedicated settings (the second is
     * optional). Normalised and de-duplicated, so the same message never goes to
     * one person twice.
     *
     * @return string[]
     */
    private static function superAdminPhones(): array
    {
        $phones = [];
        foreach (['b2b_superadmin_phone', 'b2b_superadmin_phone_2'] as $key) {
            $phone = B2bPhone::normalize(trim((string)B2bConfig::get($key)));
            if ($phone !== '' && !in_array($phone, $phones, true)) {
                $phones[] = $phone;
            }
        }
        return $phones;
    }

    /**
     * @param string[] $phones
     * @return string[]
     */
    private static function waLinks(array $phones, string $message): array
    {
        return array_map(
            static fn (string $phone): string => self::waLink($phone, $message),
            $phones
        );
    }

    public static function waLink(string $phone, string $message): string
    {
        return 'https://wa.me/' . preg_replace('/\D+/', '', $phone) . '?text=' . rawurlencode($message);
    }
}
