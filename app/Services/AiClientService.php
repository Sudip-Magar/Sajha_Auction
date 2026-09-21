<?php

namespace App\Services;

use App\Exceptions\AiUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AiClientService
{
    /**
     * Statuses that mean "Google is overloaded or hiccuping", worth one more try.
     */
    private const RETRYABLE_STATUSES = [429, 500, 502, 503, 504];

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

        $response = null;
        $firstFailure = null;
        $firstStatus = 0;

        foreach ($this->modelsToTry() as $model) {
            try {
                $response = $this->send($model, $systemPrompt, $history);
            } catch (ConnectionException $exception) {
                $response = null;
                $firstFailure ??= $model.': connection problem: '.$exception->getMessage();

                continue;
            }

            if ($response->successful()) {
                break;
            }

            if ($firstFailure === null) {
                $firstFailure = $model.': HTTP '.$response->status().': '.$response->json('error.message', 'unknown error');
                $firstStatus = $response->status();
            }

            // A model that is gone or misconfigured (404/400/403) is skipped, not retried.
            // Anything else non-retryable stops here.
            if (! in_array($response->status(), array_merge(self::RETRYABLE_STATUSES, [400, 403, 404]), true)) {
                break;
            }
        }

        if (! $response || $response->failed()) {
            throw new AiUnavailableException('Gemini request failed with '.($firstFailure ?? 'no response'), $firstStatus);
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

    /**
     * The configured model first, then each fallback model (AI_FALLBACK_MODEL may
     * hold several, comma separated). With no fallback the same model is simply
     * tried a second time.
     *
     * @return array<int, string>
     */
    private function modelsToTry(): array
    {
        $primary = (string) config('services.ai.model');

        $fallbacks = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('services.ai.fallback_model'))
        )));

        $models = array_values(array_unique([$primary, ...$fallbacks]));

        if (count($models) === 1) {
            $models[] = $primary;
        }

        return array_slice($models, 0, 4);
    }

    /**
     * @param  array<int, array{role: string, body: string}>  $history
     */
    private function send(string $model, string $systemPrompt, array $history): Response
    {
        $url = rtrim((string) config('services.ai.base_url'), '/').'/models/'.$model.':generateContent';

        return Http::withHeaders(['x-goog-api-key' => (string) config('services.ai.api_key')])
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
    }
}
