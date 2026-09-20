<?php

namespace App\Livewire\User;

use App\Jobs\GenerateAiAssistantReply;
use App\Models\AiChatMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * "How does this work?" assistant. Rendered as a full page at /ai-assistant
 * (mode "page") and as the sitewide slide-over drawer + floating widget
 * embedded in layouts/app.blade.php (mode "drawer") - same component, same backend.
 */
#[Layout('layouts.app')]
class AiAssistant extends Component
{
    private const PENDING_WINDOW_SECONDS = 45;

    private const MESSAGES_PER_MINUTE = 10;

    #[Locked]
    public string $mode = 'page';

    public string $message = '';

    // Drawer mode only loads the conversation once it has been opened.
    public bool $opened = false;

    public function mount(string $mode = 'page'): void
    {
        $this->mode = $mode === 'drawer' ? 'drawer' : 'page';
    }

    public function send(?string $preset = null): void
    {
        if ($preset !== null) {
            $this->message = $preset;
        }

        $this->validate(
            ['message' => 'required|string|max:500'],
            [
                'message.required' => 'Please type a question.',
                'message.max' => 'Please keep your question under 500 characters.',
            ]
        );

        $this->opened = true;

        if ($this->isPending()) {
            return;
        }

        $rateKey = 'ai-assistant:'.(Auth::id() ?? session()->getId()).':'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateKey, self::MESSAGES_PER_MINUTE)) {
            $this->addError('message', 'You are sending messages too quickly. Please wait a moment and try again.');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        $userMessage = AiChatMessage::create([
            'user_id' => Auth::id(),
            'session_id' => Auth::check() ? null : session()->getId(),
            'role' => 'user',
            'body' => trim($this->message),
        ]);

        $this->message = '';

        GenerateAiAssistantReply::dispatch($userMessage->id);
    }

    private function ownerQuery()
    {
        return AiChatMessage::query()->forOwner(Auth::id(), session()->getId());
    }

    private function isPending(): bool
    {
        $last = $this->ownerQuery()->latest('id')->first();

        return $last?->role === 'user'
            && $last->created_at->gt(now()->subSeconds(self::PENDING_WINDOW_SECONDS));
    }

    public function render(): View
    {
        $messages = ($this->mode === 'page' || $this->opened)
            ? $this->ownerQuery()->latest('id')->take(30)->get()->reverse()->values()
            : collect();

        $last = $messages->last();
        $awaitingReply = $last?->role === 'user';
        $pending = $awaitingReply && $last->created_at->gt(now()->subSeconds(self::PENDING_WINDOW_SECONDS));

        return view('livewire.user.ai-assistant', [
            'messages' => $messages,
            'pending' => $pending,
            'timedOut' => $awaitingReply && ! $pending,
        ]);
    }
}
