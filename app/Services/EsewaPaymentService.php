<?php

namespace App\Services;

use App\Models\Order;
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
     * Build the signed form fields eSewa's payment page expects, and persist
     * a fresh transaction_uuid on the order so the callback can be matched
     * back to it.
     *
     * @return array<string, string>
     */
    public function buildPaymentForm(Order $order): array
    {
        $transactionUuid = (string) Str::uuid();
        $order->update(['deposit_transaction_uuid' => $transactionUuid]);

        $amount = number_format((float) $order->deposit_amount, 2, '.', '');

        $fields = [
            'amount' => $amount,
            'tax_amount' => '0',
            'product_service_charge' => '0',
            'product_delivery_charge' => '0',
            'total_amount' => $amount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => config('services.esewa.product_code'),
            'success_url' => route('payment.esewa.success'),
            'failure_url' => route('payment.esewa.failure', ['order' => $order->id]),
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
    public function checkStatus(Order $order): ?string
    {
        $response = Http::get(config('services.esewa.status_url'), [
            'product_code' => config('services.esewa.product_code'),
            'total_amount' => number_format((float) $order->deposit_amount, 2, '.', ''),
            'transaction_uuid' => $order->deposit_transaction_uuid,
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
