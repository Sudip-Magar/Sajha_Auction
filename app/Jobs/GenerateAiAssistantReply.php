<?php

namespace App\Jobs;

use App\Exceptions\AiUnavailableException;
use App\Models\AiChatMessage;
use App\Services\AiAssistantPromptService;
use App\Services\AiClientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAiAssistantReply implements ShouldQueue
{
    use Queueable;

    public const FALLBACK_MESSAGE = 'Sorry, I could not reach the assistant right now. Please try again in a moment, or check the FAQs and How It Works pages for answers.';

    public const BUSY_MESSAGE = 'The AI service is very busy right now. Please press Try again in a moment.';

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public int $messageId) {}

    public function handle(AiClientService $client, AiAssistantPromptService $prompt): void
    {
        $message = AiChatMessage::find($this->messageId);

        if (! $message || $message->role !== 'user') {
            return;
        }

        // The queue worker and the chat's own fallback may race for the same
        // message; only one of them is allowed to generate the reply.
        $lock = Cache::lock('ai-reply:'.$this->messageId, 90);

        if (! $lock->get()) {
            return;
        }

        try {
            if ($this->alreadyAnswered($message)) {
                return;
            }

            $history = AiChatMessage::query()
                ->sameOwnerAs($message)
                ->where('id', '<=', $message->id)
                ->where('is_error', false)
                ->latest('id')
                ->take((int) config('services.ai.history_limit', 10))
                ->get()
                ->reverse()
                ->map(fn (AiChatMessage $turn): array => ['role' => $turn->role, 'body' => $turn->body])
                ->values()
                ->all();

            $systemPrompt = $prompt->systemPrompt();

            // A first question with no earlier context (for example the suggestion
            // chips) has the same answer for everyone, so remember it for a while.
            // The key includes the prompt, so a change to the platform rules
            // automatically invalidates old answers.
            $cacheKey = count($history) === 1 && mb_strlen($message->body) <= 200
                ? 'ai-answer:'.md5($systemPrompt.'|'.mb_strtolower(trim($message->body)))
                : null;

            $reply = $cacheKey ? Cache::get($cacheKey) : null;

            if ($reply === null) {
                $reply = $client->generate($systemPrompt, $history);

                if ($cacheKey) {
                    Cache::put($cacheKey, $reply, now()->addHours(12));
                }
            }

            $this->storeReply($message, $reply, false);
        } catch (Throwable $exception) {
            $this->logFailure($exception);
            $this->storeReply($message, $exception instanceof AiUnavailableException && $exception->isBusy() ? self::BUSY_MESSAGE : self::FALLBACK_MESSAGE, true);
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $exception): void
    {
        $message = AiChatMessage::find($this->messageId);

        if ($message && $message->role === 'user' && ! $this->alreadyAnswered($message)) {
            $this->logFailure($exception);
            $this->storeReply($message, self::FALLBACK_MESSAGE, true);
        }
    }

    private function alreadyAnswered(AiChatMessage $message): bool
    {
        return AiChatMessage::query()
            ->sameOwnerAs($message)
            ->where('role', 'assistant')
            ->where('id', '>', $message->id)
            ->exists();
    }

    private function storeReply(AiChatMessage $message, string $body, bool $isError): void
    {
        AiChatMessage::create([
            'user_id' => $message->user_id,
            'session_id' => $message->session_id,
            'role' => 'assistant',
            'body' => $body,
            'is_error' => $isError,
        ]);
    }

    private function logFailure(Throwable $exception): void
    {
        Log::warning('AI assistant reply failed.', [
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
