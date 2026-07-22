<?php

namespace App\Livewire\User;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Notifications\NewChatMessageNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class Messages extends Component
{
    use Toast;

    public ?int $selectedConversationId = null;

    public string $chatMessage = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $chatMessages = [];

    public function mount(?Conversation $conversation = null): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        if ($conversation && $conversation->exists && $conversation->hasParticipant($user)) {
            $this->selectedConversationId = $conversation->id;
        } else {
            $latest = Conversation::query()
                ->where(function ($query) use ($user): void {
                    $query->where('buyer_id', $user->id)
                        ->orWhere('seller_id', $user->id);
                })
                ->latest('last_message_at')
                ->first();

            $this->selectedConversationId = $latest?->id;
        }

        if ($this->selectedConversationId) {
            $this->loadChatMessages();
            $this->markIncomingMessagesAsRead();
        }
    }

    public function selectConversation(int $conversationId): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $conversation = Conversation::query()->findOrFail($conversationId);
        abort_unless($conversation->hasParticipant($user), 403);

        $this->selectedConversationId = $conversation->id;
        $this->chatMessage = '';
        $this->loadChatMessages();
        $this->markIncomingMessagesAsRead();
        $this->dispatch('chatConversationChanged', conversationId: $this->selectedConversationId);
        $this->dispatch('chatMessageRendered');
    }

    public function sendChatMessage(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->selectedConversationId) {
            return;
        }

        $this->validate([
            'chatMessage' => 'required|string|max:1000',
        ]);

        if (trim($this->chatMessage) === '') {
            $this->addError('chatMessage', 'Please enter a message.');

            return;
        }

        $conversation = Conversation::query()->findOrFail($this->selectedConversationId);
        abort_unless($conversation->hasParticipant($user), 403);

        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => trim($this->chatMessage),
        ]);

        $conversation->update([
            'last_message_at' => now(),
        ]);

        $this->chatMessage = '';
        $this->loadChatMessages();
        $this->dispatch('chatMessageRendered');

        try {
            broadcast(new ChatMessageSent($message));

            $recipient = (int) $conversation->buyer_id === (int) $user->id
                ? $conversation->seller
                : $conversation->buyer;

            if ($recipient) {
                $recipient->notify(new NewChatMessageNotification($message));
            }
        } catch (BroadcastException $exception) {
            Log::warning('Messages page chat broadcast failed.', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    #[On('chatMessageBroadcasted')]
    public function refreshChatFromBroadcast(?int $conversationId = null): void
    {
        if ($this->selectedConversationId && (int) $conversationId === (int) $this->selectedConversationId) {
            $this->loadChatMessages();
            $this->markIncomingMessagesAsRead();
            $this->dispatch('chatMessageRendered');
        } else {
            $this->dispatch('$refresh');
        }
    }

    private function loadChatMessages(): void
    {
        if (! $this->selectedConversationId) {
            $this->chatMessages = [];

            return;
        }

        $this->chatMessages = ChatMessage::query()
            ->with('sender')
            ->where('conversation_id', $this->selectedConversationId)
            ->latest()
            ->take(60)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $message): array => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name ?? 'User',
                'body' => $message->body,
                'created_at' => $message->created_at?->format('M d, h:i A'),
            ])
            ->all();
    }

    private function markIncomingMessagesAsRead(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->selectedConversationId) {
            return;
        }

        ChatMessage::query()
            ->where('conversation_id', $this->selectedConversationId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render(): View
    {
        $user = Auth::user();

        /** @var Collection<int, Conversation> $conversations */
        $conversations = $user
            ? Conversation::query()
                ->with(['product.images', 'buyer', 'seller', 'messages' => fn ($q) => $q->latest()->limit(1)])
                ->where(function ($query) use ($user): void {
                    $query->where('buyer_id', $user->id)
                        ->orWhere('seller_id', $user->id);
                })
                ->latest('last_message_at')
                ->get()
            : collect();

        $selectedConversation = $this->selectedConversationId
            ? $conversations->firstWhere('id', $this->selectedConversationId)
            : null;

        return view('livewire.user.messages', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
        ]);
    }
}
