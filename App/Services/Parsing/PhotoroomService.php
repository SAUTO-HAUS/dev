<?php

namespace App\Services\Parsing;

/**
 * Photoroom Remove Background API (v1/segment, Basic plan).
 *
 * Used at publish time for AutoTrader (USA) cars only: the cover photo is sent to
 * Photoroom, which returns the car cut out on a clean white background. The key
 * lives in .env as PHOTOROOM_API_KEY. If the key is missing or the call fails we
 * return null and the caller keeps the original photo (never blocks publishing).
 *
 * No shadow, on purpose: the AI shadow distorted the car and bills against the more
 * expensive plan, and a PHP-drawn one looked artificial (the car "floating"). Asking
 * for nothing but the cut-out keeps it to one cheap call and looks best.
 *
 * Docs: https://docs.photoroom.com/remove-background-api-basic-plan
 */
class PhotoroomService
{
    // v1/segment is the Basic-plan "Remove Background" API: one credit per image.
    // The code used to call image-api.photoroom.com/v2/edit — the premium editing
    // API, which bills several credits for the same picture (5 per photo, measured
    // on the dashboard) because it prices the full editing pipeline, not a cut-out.
    private const ENDPOINT = 'https://sdk.photoroom.com/v1/segment';
    private ?string $apiKey;

    /** Why the last call failed, for the diagnostic script. Null when it worked. */
    public ?string $lastError = null;

    public function __construct()
    {
        $this->apiKey = $this->readApiKey();
    }

    public function isEnabled(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Remove the background from JPEG bytes and return JPEG bytes on a white
     * background. Returns null on any failure (missing key, API error, bad
     * response) so the caller can fall back to the original image.
     */
    public function removeBackgroundToWhiteJpeg(string $jpegBytes): ?string
    {
        if (empty($this->apiKey) || strlen($jpegBytes) < 1000) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'prm_') . '.jpg';
        if (@file_put_contents($tmp, $jpegBytes) === false) {
            return null;
        }

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'accept: image/jpeg',
                'x-api-key: ' . $this->apiKey,
            ],
            // Everything this API needs: the photo, a white background, JPEG out, full
            // resolution. No crop, so the car keeps the framing the dealer shot it in
            // and only the background changes. No effects — one credit per image.
            CURLOPT_POSTFIELDS     => [
                'image_file' => new \CURLFile($tmp, 'image/jpeg', 'car.jpg'),
                // v1 wants a CSS colour — "#FFFFFF" or "white". A bare "FFFFFF" is
                // rejected with wrong_key_value_combination.
                'bg_color'   => '#FFFFFF',
                'format'     => 'jpg',
                'size'       => 'full',
            ],
        ]);
        $out  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        @unlink($tmp);

        // Keep WHY it failed. Publishing still falls back to the original photo, but
        // "returns null" alone hides a rejected key or an unknown parameter, which is
        // exactly what has to be read from the API's own answer.
        if ($out === false || $code !== 200 || strlen((string)$out) < 1000) {
            $body = is_string($out) ? trim(substr($out, 0, 300)) : '';
            $this->lastError = 'HTTP ' . $code
                . ($err !== '' ? ' (curl: ' . $err . ')' : '')
                . ($body !== '' ? ' — ' . preg_replace('/\s+/', ' ', $body) : '');
            return null;
        }
        // Sanity: the response must be a real JPEG image.
        $info = @getimagesizefromstring($out);
        if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_JPEG) {
            $this->lastError = 'HTTP 200 but the body is not a JPEG (' . strlen($out) . ' bytes)';
            return null;
        }
        $this->lastError = null;
        return $out;
    }

    private function readApiKey(): ?string
    {
        // Under CLI, DOCUMENT_ROOT is set to an EMPTY STRING, not absent — so `??`
        // never fires and $root became ''. The cron looked for "/.env", found nothing,
        // and Photoroom silently reported itself disabled: every cover went up
        // untouched while the same code called from the browser worked perfectly.
        $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if (!is_string($root) || trim($root) === '') {
            $root = realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 3);
        }
        $envFile = rtrim((string)$root, '/\\') . '/.env';
        if (is_file($envFile)) {
            $env = (string)file_get_contents($envFile);
            if (preg_match('/^\s*PHOTOROOM_API_KEY\s*=\s*(.+)$/m', $env, $m)) {
                return trim($m[1]);
            }
        }
        $fromEnv = getenv('PHOTOROOM_API_KEY');
        return $fromEnv !== false && $fromEnv !== '' ? trim($fromEnv) : null;
    }
}
