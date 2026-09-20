<?php

namespace App\Jobs;

use App\Models\AiChatMessage;
use App\Services\AiAssistantPromptService;
use App\Services\AiClientService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAiAssistantReply implements ShouldQueue
{
    use Queueable;

    public const FALLBACK_MESSAGE = 'Sorry, I could not reach the assistant right now. Please try again in a moment, or check the FAQs and How It Works pages for answers.';

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(public int $messageId) {}

    public function handle(AiClientService $client, AiAssistantPromptService $prompt): void
    {
        $message = AiChatMessage::find($this->messageId);

        if (! $message || $message->role !== 'user' || $this->alreadyAnswered($message)) {
            return;
        }

        try {
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

            $reply = $client->generate($prompt->systemPrompt(), $history);

            $this->storeReply($message, $reply, false);
        } catch (Throwable $exception) {
            $this->logFailure($exception);
            $this->storeReply($message, self::FALLBACK_MESSAGE, true);
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
