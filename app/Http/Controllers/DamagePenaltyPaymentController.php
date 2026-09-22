<?php

namespace App\Http\Controllers;

use App\Enums\DamagePenaltyStatus;
use App\Models\DamagePenalty;
use App\Services\DamagePenaltyService;
use App\Services\EsewaPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * A seller paying their 30% damage penalty directly to the admin - the
 * reverse direction of EsewaPaymentController's buyer-pays-admin deposit
 * flow, but the same eSewa ePay v2 signing, verification and double-check.
 */
class DamagePenaltyPaymentController extends Controller
{
    public function __construct(private readonly EsewaPaymentService $esewa) {}

    public function initiate(DamagePenalty $penalty): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user || (int) $penalty->seller_id !== (int) $user->id) {
            abort(403);
        }

        if ($penalty->status !== DamagePenaltyStatus::PENDING) {
            return redirect()->route('dashboard')
                ->with('esewa_info', 'This penalty is no longer awaiting payment.');
        }

        return view('payment.esewa-redirect', [
            'gatewayUrl' => config('services.esewa.form_url'),
            'fields' => $this->esewa->buildPenaltyPaymentForm($penalty),
        ]);
    }

    public function success(Request $request): RedirectResponse
    {
        $data = $request->query('data');
        $decoded = $data ? $this->esewa->decodeAndVerifyCallback($data) : null;

        if (! $decoded || ($decoded['status'] ?? null) !== 'COMPLETE') {
            return redirect()->route('dashboard')
                ->with('esewa_error', 'eSewa did not confirm the payment. Please try again.');
        }

        $uuid = (string) ($decoded['transaction_uuid'] ?? '');
        $penalty = DamagePenalty::where('transaction_uuid', $uuid)->first();

        if (! $penalty || $penalty->status !== DamagePenaltyStatus::PENDING) {
            return redirect()->route('dashboard')
                ->with('esewa_error', 'Could not match this payment to a pending penalty.');
        }

        $paidAmount = round((float) str_replace(',', '', (string) ($decoded['total_amount'] ?? 0)), 2);

        if (abs($paidAmount - $penalty->amount) > 0.001) {
            Log::warning('eSewa damage-penalty callback amount did not match.', [
                'damage_penalty_id' => $penalty->id,
                'expected' => $penalty->amount,
                'received' => $paidAmount,
            ]);

            return redirect()->route('dashboard')
                ->with('esewa_error', 'The paid amount did not match this penalty. Please contact support.');
        }

        $status = $this->esewa->checkStatus($uuid, $penalty->amount);

        if ($status !== 'COMPLETE') {
            Log::warning('eSewa damage-penalty callback signature verified but status API disagreed.', [
                'damage_penalty_id' => $penalty->id,
                'status_api_response' => $status,
            ]);

            return redirect()->route('dashboard')
                ->with('esewa_error', 'Payment could not be confirmed with eSewa. If money was deducted, contact support.');
        }

        DamagePenaltyService::recordPayment($penalty);

        return redirect()->route('dashboard')
            ->with('esewa_success', 'Penalty payment received. An admin will review and restore your access.');
    }

    public function failure(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard')
            ->with('esewa_error', 'The eSewa payment was cancelled or failed. You can try again from your dashboard.');
    }
}
