<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Notifications\DepositPaidNotification;
use App\Services\EsewaPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EsewaPaymentController extends Controller
{
    public function __construct(private readonly EsewaPaymentService $esewa) {}

    public function initiate(Order $order): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user || (int) $order->buyer_id !== (int) $user->id) {
            abort(403);
        }

        if (! $order->needsDeposit()) {
            return redirect()->route('user.orders.show', $order)
                ->with('esewa_info', 'No deposit is pending for this order.');
        }

        return view('payment.esewa-redirect', [
            'gatewayUrl' => config('services.esewa.form_url'),
            'fields' => $this->esewa->buildPaymentForm($order),
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

        $order = Order::where('deposit_transaction_uuid', $decoded['transaction_uuid'] ?? null)->first();

        if (! $order || $order->deposit_status === 'paid') {
            return redirect()->route('user.orders')
                ->with('esewa_error', 'Could not match this payment to a pending order.');
        }

        // Trust our own verified signature over the network for the happy
        // path, but also cross-check via eSewa's status API before writing
        // deposit_status=paid, per eSewa's recommended verification flow.
        $status = $this->esewa->checkStatus($order);

        if ($status !== 'COMPLETE') {
            Log::warning('eSewa callback signature verified but status API disagreed.', [
                'order_id' => $order->id,
                'status_api_response' => $status,
            ]);

            return redirect()->route('user.orders.show', $order)
                ->with('esewa_error', 'Payment could not be confirmed with eSewa. If money was deducted, contact support.');
        }

        $order->update(['deposit_status' => 'paid']);
        $order->load(['items.product', 'buyer', 'seller']);

        $order->items->first()?->product?->logTimeline(
            'deposit_paid',
            "Deposit Paid via eSewa (#{$order->order_number})",
            'Buyer paid a deposit of Rs. '.number_format((float) $order->deposit_amount, 2).' to secure the auction win. Balance due in cash at meetup.',
            $order->buyer
        );

        try {
            $order->seller?->notify(new DepositPaidNotification($order));
        } catch (\Throwable $exception) {
            Log::warning('Deposit-paid notification could not be delivered.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('user.orders.show', $order)
            ->with('esewa_success', 'Deposit paid successfully! The seller has been notified.');
    }

    public function failure(Request $request): RedirectResponse
    {
        $orderId = $request->query('order');
        $order = $orderId ? Order::find($orderId) : null;

        return redirect($order ? route('user.orders.show', $order) : route('user.orders'))
            ->with('esewa_error', 'The eSewa payment was cancelled or failed. You can try again from the order page.');
    }
}
