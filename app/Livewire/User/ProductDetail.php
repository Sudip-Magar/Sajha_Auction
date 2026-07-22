<?php

namespace App\Livewire\User;

use App\Events\ChatMessageSent;
use App\Models\CartItem;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Wishlist;
use App\Notifications\NewChatMessageNotification;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class ProductDetail extends Component
{
    use Toast;

    public Product $product;

    public bool $isWishlisted = false;

    public bool $chatOpen = false;

    public ?int $conversationId = null;

    public string $chatMessage = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $chatMessages = [];

    public function mount(Product $product): void
    {
        abort_unless($product->is_approved && ($product->status === 'active' || $product->status === 'sold'), 404);

        $product->increment('views_count');

        $this->product = $product->refresh()->load([
            'auction.bids.bidder',
            'auction.traditionalAuction',
            'category',
            'images',
            'user',
            'timelines',
        ]);

        $this->checkWishlistStatus();
    }

    public function checkWishlistStatus(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->isWishlisted = Wishlist::where('user_id', $user->id)
                ->where('product_id', $this->product->id)
                ->exists();
        }
    }

    public function toggleWishlist(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->warning('Please sign in to add products to your wishlist.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $this->product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $this->isWishlisted = false;
            $this->success('Removed from wishlist.');
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $this->product->id,
            ]);
            $this->isWishlisted = true;
            $this->success('Added to wishlist!');
        }

        $this->dispatch('wishlistUpdated');
    }

    public function addToCart(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->warning('Please sign in to add items to cart.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        if ((int) $this->product->seller_id === (int) $user->id) {
            $this->error('You cannot add your own product to cart.');

            return;
        }

        if (! $this->product->isDirectSell()) {
            $this->error('Only direct sell items can be added to cart.');

            return;
        }

        $cartItem = CartItem::firstOrCreate(
            ['user_id' => $user->id, 'product_id' => $this->product->id],
            ['quantity' => 1]
        );

        if (! $cartItem->wasRecentlyCreated) {
            $cartItem->increment('quantity');
        }

        $this->success('Product added to your cart!');
        $this->dispatch('cartUpdated');
    }

    public function buyNow(): mixed
    {
        $user = Auth::user();
        if (! $user) {
            $this->warning('Please sign in to complete purchase.');

            return $this->redirect(route('user.login'), navigate: true);
        }

        if ((int) $this->product->seller_id === (int) $user->id) {
            $this->error('You cannot purchase your own product.');

            return null;
        }

        return $this->redirect(route('user.checkout', ['product' => $this->product->id]), navigate: true);
    }

    public function openChat(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->warning('Please sign in to chat with the seller.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        if ((int) $this->product->seller_id === (int) $user->id) {
            $this->error('You cannot chat with yourself on your own product listing.');

            return;
        }

        $conversation = Conversation::firstOrCreate([
            'product_id' => $this->product->id,
            'buyer_id' => $user->id,
            'seller_id' => $this->product->seller_id,
        ]);

        $this->conversationId = $conversation->id;
        $this->chatOpen = true;
        $this->loadChatMessages();
        $this->markIncomingMessagesAsRead();
        $this->dispatch('chatConversationChanged', conversationId: $this->conversationId);
    }

    public function openConversation(Conversation $conversation): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->warning('Please sign in to open this chat.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        abort_unless(
            (int) $conversation->product_id === (int) $this->product->id
            && $conversation->hasParticipant($user),
            403
        );

        $this->conversationId = $conversation->id;
        $this->chatOpen = true;
        $this->loadChatMessages();
        $this->markIncomingMessagesAsRead();
        $this->dispatch('chatConversationChanged', conversationId: $this->conversationId);
    }

    public function closeChat(): void
    {
        $this->chatOpen = false;
        $this->chatMessage = '';
    }

    public function sendChatMessage(): void
    {
        $user = Auth::user();
        if (! $user) {
            $this->warning('Please sign in to send messages.');
            $this->redirect(route('user.login'), navigate: true);

            return;
        }

        if (! $this->conversationId) {
            $this->openChat();
        }

        if (! $this->conversationId) {
            return;
        }

        $this->validate([
            'chatMessage' => 'required|string|max:1000',
        ]);

        if (trim($this->chatMessage) === '') {
            $this->addError('chatMessage', 'Please enter a message.');

            return;
        }

        $conversation = Conversation::query()->findOrFail($this->conversationId);
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
            Log::warning('Product chat broadcast failed.', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    #[On('chatMessageBroadcasted')]
    public function refreshChatFromBroadcast(?int $conversationId = null): void
    {
        if ($this->conversationId && (int) $conversationId === (int) $this->conversationId) {
            $this->loadChatMessages();
            $this->markIncomingMessagesAsRead();
            $this->dispatch('chatMessageRendered');
        } elseif (Auth::check() && (int) $this->product->seller_id === (int) Auth::id()) {
            $this->dispatch('$refresh');
        }
    }

    private function loadChatMessages(): void
    {
        if (! $this->conversationId) {
            $this->chatMessages = [];

            return;
        }

        $this->chatMessages = ChatMessage::query()
            ->with('sender')
            ->where('conversation_id', $this->conversationId)
            ->latest()
            ->take(50)
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
        if (! $user || ! $this->conversationId) {
            return;
        }

        ChatMessage::query()
            ->where('conversation_id', $this->conversationId)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render(): View
    {
        return view('livewire.user.product-detail', [
            'productConversations' => Auth::check() && (int) $this->product->seller_id === (int) Auth::id()
                ? Conversation::query()
                    ->with(['buyer', 'messages' => fn ($query) => $query->latest()->limit(1)])
                    ->where('product_id', $this->product->id)
                    ->where('seller_id', Auth::id())
                    ->latest('last_message_at')
                    ->get()
                : collect(),
            'similarProducts' => Product::with(['category', 'images'])
                ->where('is_approved', true)
                ->where('status', 'active')
                ->where('id', '!=', $this->product->id)
                ->where('sub_category_id', $this->product->sub_category_id)
                ->latest()
                ->take(4)
                ->get(),
        ]);
    }
}
