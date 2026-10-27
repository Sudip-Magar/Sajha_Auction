<?php

namespace App\Services;

use App\Enums\PaymentTransactionMethod;
use App\Enums\PaymentTransactionParty;
use App\Enums\PaymentTransactionStatus;
use App\Enums\PaymentTransactionType;
use App\Models\DamagePenalty;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * eSewa ePay v2 integration. Runs against eSewa's sandbox (rc-epay.esewa.com.np)
 * by default via config/services.php; swap the esewa.* env vars for live
 * merchant credentials to go to production, no code change needed.
 *
 * Docs: https://developer.esewa.com.np/pages/Epay
 */
class EsewaPaymentService
{
    /**
     * Validate a buyer-chosen online payment. The first payments must reach
     * the minimum deposit (the configured percentage of the winning bid); the
     * maximum is always what is still owed on the order.
     *
     * @return array{min: float, max: float}
     */
    public function allowedRange(Order $order): array
    {
        $max = $order->remainingAmount();
        $stillToReachMinimum = round($order->minimumDeposit() - $order->paidOnline(), 2);

        return [
            'min' => min($max, $stillToReachMinimum > 0 ? $stillToReachMinimum : 1.0),
            'max' => $max,
        ];
    }

    /**
     * Build the signed form fields eSewa's payment page expects. Each click
     * logs its own pending transaction (keyed by a fresh transaction_uuid) so
     * the callback can be matched back to exactly this payment.
     *
     * @return array<string, string>
     */
    public function buildPaymentForm(Order $order, ?float $chosenAmount = null): array
    {
        $transactionUuid = (string) Str::uuid();
        $order->update(['deposit_transaction_uuid' => $transactionUuid]);

        $chosenAmount ??= $this->allowedRange($order)['min'];

        PaymentTransaction::create([
            'order_id' => $order->id,
            'type' => PaymentTransactionType::DEPOSIT_PAID,
            'amount' => $chosenAmount,
            'payment_method' => PaymentTransactionMethod::ESEWA,
            'status' => PaymentTransactionStatus::PENDING,
            'reference' => $transactionUuid,
            'party' => PaymentTransactionParty::ADMIN,
        ]);

        return $this->buildSignedForm(
            $transactionUuid,
            $chosenAmount,
            route('payment.esewa.success'),
            route('payment.esewa.failure', ['order' => $order->id]),
        );
    }

    /**
     * The reverse direction: a seller paying a damage penalty to the admin,
     * not a buyer paying into an order. Same signing and verification flow,
     * a different pair of callback routes.
     *
     * @return array<string, string>
     */
    public function buildPenaltyPaymentForm(DamagePenalty $penalty): array
    {
        $transactionUuid = (string) Str::uuid();
        $penalty->update(['transaction_uuid' => $transactionUuid]);

        return $this->buildSignedForm(
            $transactionUuid,
            $penalty->amount,
            route('payment.esewa.penalty-success'),
            route('payment.esewa.penalty-failure', ['penalty' => $penalty->id]),
        );
    }

    /**
     * @return array<string, string>
     */
    private function buildSignedForm(string $transactionUuid, float $amount, string $successUrl, string $failureUrl): array
    {
        $formattedAmount = number_format($amount, 2, '.', '');

        $fields = [
            'amount' => $formattedAmount,
            'tax_amount' => '0',
            'product_service_charge' => '0',
            'product_delivery_charge' => '0',
            'total_amount' => $formattedAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => config('services.esewa.product_code'),
            'success_url' => $successUrl,
            'failure_url' => $failureUrl,
            'signed_field_names' => 'total_amount,transaction_uuid,product_code',
        ];

        $fields['signature'] = $this->sign([
            'total_amount' => $fields['total_amount'],
            'transaction_uuid' => $fields['transaction_uuid'],
            'product_code' => $fields['product_code'],
        ]);

        return $fields;
    }

    /**
     * Verify the signature on the base64-decoded callback payload eSewa
     * appends to success_url as ?data=. Returns null if it doesn't check out.
     *
     * @return array<string, mixed>|null
     */
    public function decodeAndVerifyCallback(string $encodedData): ?array
    {
        $decoded = json_decode(base64_decode($encodedData), true);

        if (! is_array($decoded) || empty($decoded['signed_field_names']) || empty($decoded['signature'])) {
            return null;
        }

        $signedFields = explode(',', (string) $decoded['signed_field_names']);
        $toSign = [];
        foreach ($signedFields as $field) {
            if (! array_key_exists($field, $decoded)) {
                return null;
            }
            $toSign[$field] = $decoded[$field];
        }

        if (! hash_equals($this->sign($toSign), (string) $decoded['signature'])) {
            return null;
        }

        return $decoded;
    }

    /**
     * Independent server-to-server confirmation, per eSewa's recommended
     * flow, rather than trusting the browser-redirected callback alone.
     */
    public function checkStatus(string $transactionUuid, float $amount): ?string
    {
        $response = Http::get(config('services.esewa.status_url'), [
            'product_code' => config('services.esewa.product_code'),
            'total_amount' => number_format($amount, 2, '.', ''),
            'transaction_uuid' => $transactionUuid,
        ]);

        if (! $response->ok()) {
            return null;
        }

        return $response->json('status');
    }

    /**
     * @param  array<string, string>  $fields  in the exact order they must be signed
     */
    private function sign(array $fields): string
    {
        $message = collect($fields)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode(',');

        return base64_encode(hash_hmac('sha256', $message, (string) config('services.esewa.secret_key'), true));
    }
}
