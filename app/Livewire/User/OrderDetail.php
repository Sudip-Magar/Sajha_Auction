<?php

namespace App\Livewire\User;

use App\Enums\ComplaintMessageSender;
use App\Enums\OrderCancellationReason;
use App\Models\Admin;
use App\Models\Order;
use App\Notifications\ComplaintMessageReceivedNotification;
use App\Notifications\MeetupScheduledNotification;
use App\Services\OrderCancellationService;
use App\Services\OrderPaymentService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class OrderDetail extends Component
{
    use Toast;

    public Order $order;

    public bool $showCancelForm = false;

    public bool $showCompleteConfirm = false;

    /**
     * Kept as a raw string, not the enum, deliberately: this is hydrated
     * directly from client input on every request via wire:model, and
     * Livewire's enum coercion throws an uncaught ValueError on a bad value
     * (a tampered/replayed request) instead of failing gracefully. Validated
     * and converted to OrderCancellationReason in confirmCancel() instead.
     */
    public string $cancelReasonCategory = OrderCancellationReason::CHANGED_MIND->value;

    public string $cancelNote = '';

    public bool $showMeetupForm = false;

    public string $meetup_location = '';

    public string $meetup_date_np = '';

    public string $meetup_date_en = '';

    public string $meetup_time_of_day = '';

    public string $complaintMessage = '';

    public function mount(Order $order): void
    {
        $user = Auth::user();
        if (! $user || ((int) $order->buyer_id !== (int) $user->id && (int) $order->seller_id !== (int) $user->id)) {
            abort(403);
        }

        $this->order = $order->load([
            'buyer',
            'seller',
            'items.product.images',
            'items.product.timelines',
            'complaintMessages',
        ]);

        if (session()->has('esewa_success')) {
            $this->success(session('esewa_success'));
        } elseif (session()->has('esewa_error')) {
            $this->error(session('esewa_error'));
        } elseif (session()->has('esewa_info')) {
            $this->info(session('esewa_info'));
        }
    }

    /**
     * Which party is allowed to move the order into each status. Completing
     * an order decrements stock and marks the product sold, so that step is
     * restricted to the seller — the same party who alone can confirm it —
     * rather than left open to either side as it was before.
     *
     * @var array<string, string>
     */
    private const TRANSITION_OWNER = [
        'confirmed' => 'seller',
        'completed' => 'seller',
    ];

    public function toggleCancelForm(): void
    {
        $this->showCancelForm = ! $this->showCancelForm;
    }

    public function confirmCancel(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $isSeller = (int) $this->order->seller_id === (int) $user->id;
        $isBuyer = (int) $this->order->buyer_id === (int) $user->id;

        if (! $isSeller && ! $isBuyer) {
            $this->error('You are not allowed to make this change to the order.');

            return;
        }

        if (in_array($this->order->status, ['cancelled', 'completed'], true)) {
            $this->error('This order can no longer be cancelled.');

            return;
        }

        $reason = OrderCancellationReason::tryFrom($this->cancelReasonCategory);

        if ($isBuyer && $reason === null) {
            $this->error('Please choose a valid reason for cancelling.');

            return;
        }

        OrderCancellationService::cancel(
            $this->order,
            $user,
            $isBuyer ? $reason : null,
            trim($this->cancelNote) ?: null
        );

        $this->order->refresh();
        $this->showCancelForm = false;
        $this->cancelNote = '';
        $this->success('Order cancelled.');
    }

    public function confirmMarkCompleted(): void
    {
        $this->showCompleteConfirm = false;
        $this->updateOrderStatus('completed');
    }

    public function updateOrderStatus(string $status): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        if (! array_key_exists($status, self::TRANSITION_OWNER)) {
            $this->error('Invalid order status.');

            return;
        }

        $isSeller = (int) $this->order->seller_id === (int) $user->id;
        $isBuyer = (int) $this->order->buyer_id === (int) $user->id;
        $owner = self::TRANSITION_OWNER[$status];

        $allowed = $owner === 'seller' ? $isSeller : $isBuyer;

        if (! $allowed) {
            $this->error('You are not allowed to make this change to the order.');

            return;
        }

        if (in_array($this->order->status, ['cancelled', 'completed'], true)) {
            $this->error('This order can no longer be changed.');

            return;
        }

        if ($status === 'completed') {
            OrderPaymentService::complete($this->order, $user);
        } elseif ($status === 'confirmed') {
            $this->order->update(['status' => 'confirmed']);

            foreach ($this->order->items as $item) {
                $item->product?->logTimeline(
                    'meetup_scheduled',
                    "Order Confirmed by Seller (#{$this->order->order_number})",
                    'Seller confirmed order. Agreed meetup location: '.($this->order->meetup_location ?: 'Seller Location').'.',
                    $user
                );
            }
        }

        $this->order->refresh();
        $this->success("Order status updated to '".str($status)->replace('_', ' ')->title()."'.");
    }

    /**
     * Buyer-only: pick (or change) the meetup location/date/time. The only
     * place this ever happens for an auction win, since that order is
     * created automatically with no checkout step to collect it.
     */
    public function toggleMeetupForm(): void
    {
        $user = Auth::user();
        if (! $user || (int) $this->order->buyer_id !== (int) $user->id) {
            return;
        }

        if (in_array($this->order->status, ['cancelled', 'completed'], true)) {
            return;
        }

        $this->showMeetupForm = ! $this->showMeetupForm;

        if ($this->showMeetupForm) {
            $this->meetup_location = $this->order->meetup_location ?: '';
            $this->meetup_date_en = $this->order->meetup_time?->format('Y-m-d') ?? '';
            $this->meetup_date_np = $this->order->meetup_time_np ? str($this->order->meetup_time_np)->before(' ')->toString() : '';
            $this->meetup_time_of_day = $this->order->meetup_time?->format('H:i') ?? '';
            $this->dispatch('init-nepali-date-pickers');
        }
    }

    public function saveMeetupDetails(): void
    {
        $user = Auth::user();
        if (! $user || (int) $this->order->buyer_id !== (int) $user->id) {
            $this->error('You are not allowed to make this change to the order.');

            return;
        }

        if (in_array($this->order->status, ['cancelled', 'completed'], true)) {
            $this->error('This order can no longer be changed.');

            return;
        }

        $this->validate([
            'meetup_location' => 'required|string|max:255',
            'meetup_date_np' => 'required|string|max:20',
            'meetup_date_en' => 'required|date|after_or_equal:today',
            'meetup_time_of_day' => 'required|date_format:H:i',
        ], [
            'meetup_date_np.required' => 'Please pick a meetup date.',
            'meetup_date_en.required' => 'Please pick a valid meetup date.',
            'meetup_date_en.after_or_equal' => 'The meetup date cannot be in the past.',
            'meetup_time_of_day.required' => 'Please pick a meetup time.',
            'meetup_time_of_day.date_format' => 'Please pick a valid meetup time.',
        ]);

        $wasScheduled = $this->order->meetup_time !== null;

        $this->order->update([
            'meetup_location' => $this->meetup_location,
            'meetup_time' => Carbon::parse("{$this->meetup_date_en} {$this->meetup_time_of_day}"),
            'meetup_time_np' => "{$this->meetup_date_np} {$this->meetup_time_of_day}",
        ]);

        foreach ($this->order->items as $item) {
            $item->product?->logTimeline(
                'meetup_scheduled',
                ($wasScheduled ? 'Meetup Rescheduled' : 'Meetup Scheduled')." (#{$this->order->order_number})",
                "Buyer set the meetup: {$this->meetup_location} on {$this->meetup_date_en} at {$this->meetup_time_of_day}.",
                $user
            );
        }

        try {
            $this->order->seller?->notify(new MeetupScheduledNotification($this->order));
        } catch (BroadcastException $exception) {
            Log::warning('Meetup-scheduled notification could not be delivered.', [
                'order_id' => $this->order->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        $this->order->refresh();
        $this->showMeetupForm = false;
        $this->success($wasScheduled ? 'Meetup details updated.' : 'Meetup scheduled! The seller has been notified.');
    }

    /**
     * Buyer-only: message the admin while a complaint is open. Scoped to
     * complaints so there is no general-purpose buyer-to-admin inbox.
     */
    public function sendComplaintMessage(): void
    {
        $user = Auth::user();
        if (! $user || (int) $this->order->buyer_id !== (int) $user->id || ! $this->order->complaint_status) {
            return;
        }

        $this->validate(['complaintMessage' => 'required|string|max:1000'], [
            'complaintMessage.required' => 'Please enter a message.',
        ]);

        $message = $this->order->complaintMessages()->create([
            'sender_role' => ComplaintMessageSender::BUYER,
            'sender_id' => $user->id,
            'body' => trim($this->complaintMessage),
        ]);

        $this->complaintMessage = '';
        $this->pollComplaintMessages();

        // Each admin notified independently: one broadcast failure must not
        // abort the loop and silently skip the remaining admins.
        foreach (Admin::all() as $admin) {
            try {
                $admin->notify(new ComplaintMessageReceivedNotification($message));
            } catch (BroadcastException $exception) {
                Log::warning('Complaint message notification could not be delivered.', [
                    'order_id' => $this->order->id,
                    'message_id' => $message->id,
                    'admin_id' => $admin->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function pollComplaintMessages(): void
    {
        $this->order->load('complaintMessages');
    }

    public function render(): View
    {
        return view('livewire.user.order-detail');
    }
}
