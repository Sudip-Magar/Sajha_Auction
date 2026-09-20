<?php

namespace App\Services;

use App\Exceptions\AiUnavailableException;
use Illuminate\Support\Facades\Http;

class AiClientService
{
    public function isConfigured(): bool
    {
        return config('services.ai.provider') === 'gemini' && filled(config('services.ai.api_key'));
    }

    /**
     * @param  array<int, array{role: string, body: string}>  $history  Oldest first; roles are 'user' or 'assistant'.
     *
     * @throws AiUnavailableException
     */
    public function generate(string $systemPrompt, array $history): string
    {
        if (! $this->isConfigured()) {
            throw new AiUnavailableException('AI provider is not configured.');
        }

        // Gemini expects the conversation to open with a user turn.
        while ($history !== [] && $history[0]['role'] !== 'user') {
            array_shift($history);
        }

        if ($history === []) {
            throw new AiUnavailableException('No user message to answer.');
        }

        $url = rtrim((string) config('services.ai.base_url'), '/').'/models/'.config('services.ai.model').':generateContent';

        $response = Http::withHeaders(['x-goog-api-key' => (string) config('services.ai.api_key')])
            ->timeout((int) config('services.ai.timeout'))
            ->acceptJson()
            ->asJson()
            ->post($url, [
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => array_map(fn (array $turn): array => [
                    'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $turn['body']]],
                ], $history),
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 800,
                ],
            ]);

        if ($response->failed()) {
            throw new AiUnavailableException('Gemini request failed with HTTP '.$response->status().': '.$response->json('error.message', 'unknown error'));
        }

        $text = collect($response->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode('');

        if (trim($text) === '') {
            throw new AiUnavailableException('Gemini returned no text (finish reason: '.$response->json('candidates.0.finishReason', 'none').').');
        }

        return trim($text);
    }
}
