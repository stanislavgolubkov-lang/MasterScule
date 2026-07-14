<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

class MaibHostedCheckout
{
    public function isConfigured(): bool
    {
        return filled(config('services.maib.base_url'))
            && filled(config('services.maib.project_id'))
            && filled(config('services.maib.secret'));
    }

    public function create(Order $order): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $payload = [
            'projectId' => config('services.maib.project_id'),
            'orderId' => $order->order_number,
            'amount' => (int) round((float) $order->total * 100),
            'currency' => $order->currency,
            'description' => config('store.domain_label').' '.$order->order_number,
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
            'successUrl' => $this->signedOrderUrl($order),
            'failUrl' => $this->signedOrderUrl($order),
            'callbackUrl' => route('payment.maib.callback'),
        ];

        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'provider' => 'maib',
            'status' => 'created',
            'amount' => $order->total,
            'currency' => $order->currency,
            'request_payload_json' => $payload,
        ]);

        try {
            $response = Http::acceptJson()
                ->withToken(config('services.maib.secret'))
                ->post($this->endpoint(), $payload);
        } catch (Throwable $exception) {
            $transaction->forceFill([
                'status' => 'failed',
                'response_payload_json' => ['message' => $exception->getMessage()],
                'processed_at' => now(),
            ])->save();

            Log::warning('MAIB checkout request failed', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            $transaction->forceFill([
                'status' => 'failed',
                'response_payload_json' => $response->json() ?: ['body' => $response->body()],
                'processed_at' => now(),
            ])->save();

            Log::warning('MAIB checkout rejected request', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $data = $response->json() ?: [];
        $reference = $this->referenceFromResponse($data);
        $checkoutUrl = $this->checkoutUrlFromResponse($data);

        $transaction->forceFill([
            'provider_transaction_id' => $reference,
            'status' => $checkoutUrl ? 'waiting_for_payment' : 'created',
            'response_payload_json' => $data,
        ])->save();

        return [
            'reference' => $reference,
            'url' => $checkoutUrl,
            'raw' => $data,
            'transaction' => $transaction,
        ];
    }

    public function verifyCallback(Request $request): bool
    {
        $secret = config('services.maib.signature_secret');

        if (! filled($secret)) {
            return false;
        }

        $signature = $request->header('X-Maib-Signature')
            ?: $request->header('X-Callback-Signature')
            ?: $request->header('X-Signature')
            ?: $request->input('signature');

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent() ?: json_encode($request->all(), JSON_UNESCAPED_SLASHES);
        $expected = hash_hmac('sha256', (string) $payload, (string) $secret);

        return hash_equals($expected, (string) $signature);
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.maib.base_url'), '/').'/'.ltrim((string) config('services.maib.create_payment_path'), '/');
    }

    private function signedOrderUrl(Order $order): string
    {
        return URL::temporarySignedRoute('checkout.thank-you', now()->addDays(7), [
            'order' => $order->order_number,
        ]);
    }

    private function referenceFromResponse(array $data): ?string
    {
        return $data['checkoutId']
            ?? $data['checkout_id']
            ?? $data['id']
            ?? $data['payId']
            ?? $data['paymentId']
            ?? $data['transactionId']
            ?? data_get($data, 'result.checkoutId')
            ?? data_get($data, 'result.id');
    }

    private function checkoutUrlFromResponse(array $data): ?string
    {
        return $data['checkoutUrl']
            ?? $data['checkout_url']
            ?? $data['redirectUrl']
            ?? $data['payUrl']
            ?? $data['url']
            ?? data_get($data, 'result.checkoutUrl')
            ?? data_get($data, 'result.redirectUrl');
    }
}
