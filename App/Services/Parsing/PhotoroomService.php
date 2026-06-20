<?php

namespace App\Services\Parsing;

/**
 * Photoroom Remove Background API (v1/segment, Basic plan).
 *
 * Used at publish time for Encar (Korea) cars only: the cover photo is sent to
 * Photoroom, which returns the car cut out on a clean white background. The key
 * lives in .env as PHOTOROOM_API_KEY. If the key is missing or the call fails we
 * return null and the caller keeps the original photo (never blocks publishing).
 *
 * No shadow: the v2/edit AI shadow distorted the car and bills against the more
 * expensive "Plus" plan; a PHP-drawn shadow looked artificial (car "floating").
 * A clean white cut-out (one Basic-plan call) looks best and stays cheap.
 *
 * Docs: https://docs.photoroom.com/remove-background-api-basic-plan
 */
class PhotoroomService
{
    private const ENDPOINT = 'https://image-api.photoroom.com/v2/edit';
    private const SHADOW_MODEL_VERSION = '2026-04-15';
    private ?string $apiKey;

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
                'pr-ai-shadows-model-version: ' . self::SHADOW_MODEL_VERSION,
            ],
            CURLOPT_POSTFIELDS     => [
                'imageFile'                  => new \CURLFile($tmp, 'image/jpeg', 'car.jpg'),
                'background.color'           => 'FFFFFF',
                // 'auto' keeps the car's proportions (a fixed 3840x2160 stretched it).
                'outputSize'                 => 'auto',
                'padding'                    => '0.15',
                'shadow.mode'                => 'ai.auto-with-overrides',
                'shadow.softnessOverride'    => '0.3',
                'shadow.intensityOverride'   => '0.8',
                'shadow.spreadOverride'      => '45',
                'shadow.directionOverride'   => '45',
                'shadow.subjectPoseOverride' => '90',
                'export.format'              => 'jpeg',
            ],
        ]);
        $out  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        @unlink($tmp);

        if ($out === false || $code !== 200 || strlen((string)$out) < 1000) {
            return null;
        }
        // Sanity: the response must be a real JPEG image.
        $info = @getimagesizefromstring($out);
        if ($info === false || ($info[2] ?? 0) !== IMAGETYPE_JPEG) {
            return null;
        }
        return $out;
    }

    private function readApiKey(): ?string
    {
        $root = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/../../..');
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
