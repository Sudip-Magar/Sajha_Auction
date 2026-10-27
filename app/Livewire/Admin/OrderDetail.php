<?php

namespace App\Livewire\Admin;

use App\Enums\ComplaintMessageSender;
use App\Enums\OrderComplaintStatus;
use App\Models\Order;
use App\Models\PayoutRequest;
use App\Notifications\ComplaintMessageReceivedNotification;
use App\Services\DamagePenaltyService;
use App\Services\OrderCancellationService;
use App\Services\OrderPaymentService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class OrderDetail extends Component
{
    use Toast;

    public Order $order;

    public string $verdictNote = '';

    public string $complaintMessage = '';

    public bool $showVerdictModal = false;

    public ?bool $confirmingVerdict = null;

    public bool $showRestoreAccessModal = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load([
            'buyer',
            'seller',
            'items.product',
            'auction',
            'transactions',
            'payoutRequests',
            'damagePenalty.strikes',
            'damagePenalty.seller',
            'complaintMessages',
        ]);
    }

    public function confirmRecordVerdict(bool $confirmedDamaged): void
    {
        $this->confirmingVerdict = $confirmedDamaged;
        $this->showVerdictModal = true;
    }

    public function runConfirmedVerdict(): void
    {
        $verdict = $this->confirmingVerdict;
        $this->showVerdictModal = false;
        $this->confirmingVerdict = null;

        if ($verdict !== null) {
            $this->recordVerdict($verdict);
        }
    }

    public function recordVerdict(bool $confirmedDamaged): void
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || $this->order->complaint_status !== OrderComplaintStatus::UNDER_REVIEW) {
            $this->error('This order has no complaint awaiting a verdict.');

            return;
        }

        OrderCancellationService::recordDamageVerdict($this->order, $admin, $confirmedDamaged, trim($this->verdictNote) ?: null);

        $this->verdictNote = '';
        $this->refreshOrder();

        $this->success($confirmedDamaged
            ? 'Verdict recorded: confirmed damaged. The buyer was refunded and the seller has been notified of the penalty.'
            : 'Verdict recorded: not damaged. The complaint was rejected.');
    }

    public function confirmRestoreSellerAccess(): void
    {
        $this->showRestoreAccessModal = true;
    }

    public function runConfirmedRestoreAccess(): void
    {
        $this->showRestoreAccessModal = false;
        $this->restoreSellerAccess();
    }

    public function restoreSellerAccess(): void
    {
        $admin = Auth::guard('admin')->user();
        $penalty = $this->order->damagePenalty;

        if (! $admin || ! $penalty) {
            return;
        }

        if (DamagePenaltyService::restoreAccess($penalty, $admin)) {
            $this->refreshOrder();
            $this->success("Access restored for {$penalty->seller->name}.");

            return;
        }

        $this->error($penalty->seller->is_permanently_banned
            ? 'This seller is permanently banned and cannot be restored this way.'
            : 'Access can only be restored after the penalty is paid.');
    }

    public function sendComplaintMessage(): void
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin || ! $this->order->complaint_status) {
            return;
        }

        $this->validate(['complaintMessage' => 'required|string|max:1000'], [
            'complaintMessage.required' => 'Please enter a message.',
        ]);

        $message = $this->order->complaintMessages()->create([
            'sender_role' => ComplaintMessageSender::ADMIN,
            'sender_id' => $admin->id,
            'body' => trim($this->complaintMessage),
        ]);

        $this->complaintMessage = '';
        $this->pollComplaintMessages();

        try {
            $this->order->buyer?->notify(new ComplaintMessageReceivedNotification($message));
        } catch (BroadcastException $exception) {
            Log::warning('Complaint message notification could not be delivered.', [
                'order_id' => $this->order->id,
                'message_id' => $message->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    public function pollComplaintMessages(): void
    {
        $this->order->load('complaintMessages');
    }

    /**
     * Admin has made the real eSewa transfer outside the app; record it here.
     * Only fires once the recipient has submitted their eSewa details
     * (payout_status PENDING) - see OrderPaymentService::markSent().
     */
    public function markPayoutSent(int $payoutId): void
    {
        $admin = Auth::guard('admin')->user();
        $payout = $this->order->payoutRequests->firstWhere('id', $payoutId);

        if (! $admin || ! $payout instanceof PayoutRequest) {
            return;
        }

        if (OrderPaymentService::markSent($payout, $admin)) {
            $this->refreshOrder();
            $this->success('Payout marked as sent.');

            return;
        }

        $this->error('This payout cannot be marked sent - it may not be awaiting a transfer.');
    }

    public function render(): View
    {
        return view('livewire.admin.order-detail');
    }

    private function refreshOrder(): void
    {
        $this->order->refresh()->load([
            'buyer', 'seller', 'items.product', 'auction', 'transactions', 'payoutRequests',
            'damagePenalty.strikes', 'damagePenalty.seller', 'complaintMessages',
        ]);
    }
}
