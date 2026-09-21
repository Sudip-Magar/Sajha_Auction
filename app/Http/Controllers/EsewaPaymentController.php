<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Notifications\DepositPaidNotification;
use App\Services\EsewaPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EsewaPaymentController extends Controller
{
    public function __construct(private readonly EsewaPaymentService $esewa) {}

    public function initiate(Request $request, Order $order): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user || (int) $order->buyer_id !== (int) $user->id) {
            abort(403);
        }

        if (! $order->acceptsOnlinePayment()) {
            return redirect()->route('user.orders.show', $order)
                ->with('esewa_info', 'No online payment is pending for this order.');
        }

        $range = $this->esewa->allowedRange($order);
        $amount = $request->filled('amount') ? round((float) $request->query('amount'), 2) : $range['min'];

        if ($amount < $range['min']) {
            $message = $order->paidOnline() < $order->minimumDeposit()
                ? 'The minimum deposit for this order is Rs. '.number_format($range['min'], 2).' ('.config('services.esewa.deposit_percentage').'% of the winning bid).'
                : 'The amount must be at least Rs. '.number_format($range['min'], 2).'.';

            return redirect()->route('user.orders.show', $order)->with('esewa_error', $message);
        }

        if ($amount > $range['max']) {
            return redirect()->route('user.orders.show', $order)
                ->with('esewa_error', 'You cannot pay more than the remaining Rs. '.number_format($range['max'], 2).' on this order.');
        }

        return view('payment.esewa-redirect', [
            'gatewayUrl' => config('services.esewa.form_url'),
            'fields' => $this->esewa->buildPaymentForm($order, $amount),
        ]);
    }

    public function success(Request $request): RedirectResponse
    {
        $data = $request->query('data');
        $decoded = $data ? $this->esewa->decodeAndVerifyCallback($data) : null;

        if (! $decoded || ($decoded['status'] ?? null) !== 'COMPLETE') {
            return redirect()->route('user.orders')
                ->with('esewa_error', 'eSewa did not confirm the payment. Please try again.');
        }

        $uuid = (string) ($decoded['transaction_uuid'] ?? '');
        $transaction = PaymentTransaction::where('reference', $uuid)->first();

        // Payments started before per-payment logging existed only have the
        // uuid on the order; treat them as a single deposit payment.
        if (! $transaction) {
            $legacyOrder = Order::where('deposit_transaction_uuid', $uuid)->first();

            if ($legacyOrder && $legacyOrder->deposit_status === 'pending') {
                $transaction = PaymentTransaction::create([
                    'order_id' => $legacyOrder->id,
                    'type' => PaymentTransaction::TYPE_DEPOSIT_PAID,
                    'amount' => (float) $decoded['total_amount'],
                    'payment_method' => 'esewa',
                    'status' => 'pending',
                    'reference' => $uuid,
                    'party' => 'admin',
                ]);
            }
        }

        $order = $transaction?->order;

        if (! $transaction || ! $order || $transaction->status === 'completed') {
            return redirect()->route('user.orders')
                ->with('esewa_error', 'Could not match this payment to a pending order.');
        }

        // The signed callback must also match the amount we asked eSewa to charge.
        $paidAmount = round((float) str_replace(',', '', (string) ($decoded['total_amount'] ?? 0)), 2);

        if (abs($paidAmount - $transaction->amount) > 0.001) {
            Log::warning('eSewa callback amount did not match the pending payment.', [
                'order_id' => $order->id,
                'expected' => $transaction->amount,
                'received' => $paidAmount,
            ]);

            $transaction->update(['status' => 'failed', 'notes' => 'Amount mismatch on callback.']);

            return redirect()->route('user.orders.show', $order)
                ->with('esewa_error', 'The paid amount did not match this payment. Please contact support.');
        }

        // Cross-check via eSewa's status API before crediting the payment.
        $status = $this->esewa->checkStatus($uuid, $transaction->amount);

        if ($status !== 'COMPLETE') {
            Log::warning('eSewa callback signature verified but status API disagreed.', [
                'order_id' => $order->id,
                'status_api_response' => $status,
            ]);

            return redirect()->route('user.orders.show', $order)
                ->with('esewa_error', 'Payment could not be confirmed with eSewa. If money was deducted, contact support.');
        }

        DB::transaction(function () use ($transaction, $order, $decoded): void {
            $transaction->update([
                'status' => 'completed',
                'notes' => isset($decoded['transaction_code']) ? 'eSewa ref '.$decoded['transaction_code'] : null,
            ]);
            $order->update(['deposit_status' => 'paid']);
        });

        $order->load(['items.product', 'buyer', 'seller']);
        $remaining = $order->remainingAmount();

        $order->items->first()?->product?->logTimeline(
            'deposit_paid',
            "Online Payment via eSewa (#{$order->order_number})",
            'Buyer paid Rs. '.number_format($transaction->amount, 2).' online. '.($remaining > 0
                ? 'Balance of Rs. '.number_format($remaining, 2).' is due in cash at the meetup.'
                : 'The order is paid in full online; no cash is due at the meetup.'),
            $order->buyer
        );

        try {
            $order->seller?->notify(new DepositPaidNotification($order, $transaction->amount));
        } catch (\Throwable $exception) {
            Log::warning('Deposit-paid notification could not be delivered.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('user.orders.show', $order)
            ->with('esewa_success', 'Payment received! The seller has been notified.');
    }

    public function failure(Request $request): RedirectResponse
    {
        $orderId = $request->query('order');
        $order = $orderId ? Order::find($orderId) : null;

        return redirect($order ? route('user.orders.show', $order) : route('user.orders'))
            ->with('esewa_error', 'The eSewa payment was cancelled or failed. You can try again from the order page.');
    }
}
