<?php

namespace App\Services\Parsing;

/**
 * Single source of truth for parsing's AI calls (spec enrichment, model matching,
 * Korean trim translation). Everything parsing-related goes through here so the
 * model / provider / endpoint is defined ONCE — change MODEL below and every
 * parsing AI call switches with it.
 *
 * Uses OpenAI (gpt-4.1-mini): reliable at high volume with no daily rate wall —
 * Groq's free tier kept 429-ing under load, publishing cars with empty HP/drive.
 */
class ParsingAI
{
    /** The one place the parsing AI model is defined. */
    public const MODEL = 'gpt-4.1-mini';

    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /** Resolve the OpenAI key from config or .env (CLI workers don't load config). */
    public static function apiKey(): string
    {
        $key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        if ($key === '') {
            $root = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/../../..');
            $envFile = rtrim((string) $root, '/\\') . '/.env';
            if (is_file($envFile)) {
                $env = file_get_contents($envFile);
                if (preg_match('/OPENAI_API_KEY=(.+)/', $env, $m)) $key = trim($m[1]);
            }
        }
        return $key;
    }

    /**
     * One chat completion. Returns the raw assistant message string (```-fences
     * stripped), or null on any failure (no key, non-200, empty body). Callers
     * json_decode it themselves — parsing AI always asks for JSON.
     */
    public static function chat(string $systemPrompt, string $userPrompt, int $maxTokens = 150, int $timeout = 30): ?string
    {
        $key = self::apiKey();
        if ($key === '') return null;

        $payload = json_encode([
            'model'       => self::MODEL,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature' => 0,
            'max_tokens'  => $maxTokens,
        ]);

        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$body) return null;
        $content = json_decode($body, true)['choices'][0]['message']['content'] ?? '';
        return trim(preg_replace('/^```(?:json)?|```$/m', '', (string) $content));
    }
}
