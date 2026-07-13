<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Services\MaibHostedCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function show()
    {
        $cart = $this->cart();

        if ($cart['items']->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => app()->isLocale('ru') ? 'Корзина пуста.' : 'Cosul este gol.']);
        }

        return view('shop.checkout', ['cart' => $cart]);
    }

    public function store(Request $request, MaibHostedCheckout $maib)
    {
        $cart = $this->cart();
        if ($cart['items']->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => app()->isLocale('ru') ? 'Корзина пуста.' : 'Cosul este gol.']);
        }

        if (Auth::check() && ! $request->filled('customer_email')) {
            $request->merge(['customer_email' => Auth::user()->email]);
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_postcode' => ['nullable', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:60'],
            'payment_method' => ['required', 'in:cash_on_delivery,bank_transfer,online_card'],
            'shipping_method' => ['required', 'in:courier,pickup,individual'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'terms_accepted' => ['accepted'],
        ]);

        $order = DB::transaction(function () use ($cart, $data) {
            $isOnlineCard = $data['payment_method'] === 'online_card';

            $order = Order::create($data + [
                'user_id' => Auth::id(),
                'order_number' => 'MSR-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'status' => $isOnlineCard ? 'pending_payment' : 'new',
                'payment_status' => 'pending',
                'subtotal' => $cart['subtotal'],
                'discount_total' => $cart['discount'],
                'shipping_total' => $cart['shipping'],
                'total' => $cart['total'],
                'currency' => config('store.currency', 'MDL'),
                'shipping_country' => config('store.country', 'Moldova'),
            ]);

            foreach ($cart['items'] as $item) {
                $product = Product::whereKey($item['product']->id)->lockForUpdate()->firstOrFail();

                if (! $product->is_active || $product->stock_status !== 'in_stock' || $product->stock_quantity < $item['quantity']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cart' => (app()->isLocale('ru') ? 'Недостаточно товара на складе: ' : 'Stoc insuficient pentru ').$item['product']->display_name.'.',
                    ]);
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->display_name,
                    'sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $item['total'],
                ]);

                if (! $isOnlineCard) {
                    $this->decrementProductStock($product, (int) $item['quantity']);
                }
            }

            if (! $isOnlineCard) {
                $order->forceFill(['stock_deducted_at' => now()])->save();
            }

            return $order;
        });

        if ($order->payment_method === 'online_card') {
            $payment = $maib->create($order);

            if (($payment['url'] ?? null)) {
                $order->forceFill([
                    'payment_reference' => $payment['reference'] ?? null,
                    'payment_url' => $payment['url'],
                    'payment_status' => 'pending',
                ])->save();

                session()->forget('cart');

                return redirect()->away($payment['url']);
            }

            $order->forceFill(['payment_status' => 'pending'])->save();
        }

        session()->forget('cart');

        return redirect()
            ->route('checkout.thank-you', $order->order_number)
            ->with('success', (app()->isLocale('ru') ? 'Заказ создан: ' : 'Comanda a fost creata: ').$order->order_number);
    }

    public function thankYou(Order $order)
    {
        return view('shop.order-confirmation', ['order' => $order->load('items')]);
    }

    public function maibCallback(Request $request, MaibHostedCheckout $maib)
    {
        abort_unless($maib->verifyCallback($request), 403);

        $reference = $this->callbackReference($request);

        $order = Order::query()
            ->where('order_number', $reference)
            ->orWhere('payment_reference', $reference)
            ->first();

        if (! $order) {
            return response()->json(['ok' => false], 404);
        }

        $status = $this->callbackStatus($request);
        $providerTransactionId = $this->callbackProviderTransactionId($request, (string) $reference);

        $result = DB::transaction(function () use ($order, $request, $status, $providerTransactionId) {
            $lockedOrder = Order::with('items')->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $transaction = $this->recordPaymentCallback($lockedOrder, $request, $providerTransactionId);
            $transactionStatus = $status;
            $stockCaptured = false;

            if ($status === 'completed') {
                $stockCaptured = $lockedOrder->stock_deducted_at !== null || $this->deductOrderStock($lockedOrder);

                $lockedOrder->forceFill([
                    'payment_status' => 'paid',
                    'status' => $stockCaptured ? 'paid' : 'stock_conflict',
                    'paid_at' => $lockedOrder->paid_at ?: now(),
                ])->save();

                $transactionStatus = $stockCaptured ? 'completed' : 'failed';
            } elseif (in_array($status, ['failed', 'cancelled'], true)) {
                if ($lockedOrder->payment_status !== 'paid') {
                    $lockedOrder->forceFill([
                        'payment_status' => 'failed',
                        'status' => 'payment_failed',
                    ])->save();
                }

            } elseif ($status === 'refunded') {
                $lockedOrder->forceFill([
                    'payment_status' => 'refunded',
                    'status' => 'canceled',
                ])->save();
            }

            $transaction->forceFill([
                'status' => $transactionStatus,
                'processed_at' => now(),
            ])->save();

            return [
                'status' => $transactionStatus,
                'stock_captured' => $stockCaptured,
            ];
        });

        return response()->json(['ok' => true] + $result);
    }

    private function callbackReference(Request $request): ?string
    {
        return $request->input('orderId')
            ?: $request->input('order_number')
            ?: $request->input('paymentReference')
            ?: $request->input('payment_reference')
            ?: $request->input('checkoutId')
            ?: $request->input('checkout_id')
            ?: $request->input('id')
            ?: $request->input('payId')
            ?: $request->input('paymentId')
            ?: $request->input('transactionId');
    }

    private function callbackStatus(Request $request): string
    {
        $rawStatus = strtolower(str_replace([' ', '-', '_'], '', (string) (
            $request->input('status')
            ?: $request->input('paymentStatus')
            ?: $request->input('checkoutStatus')
            ?: $request->input('result.status')
            ?: 'waiting_for_payment'
        )));

        return match ($rawStatus) {
            'completed', 'paid', 'success', 'approved', 'captured' => 'completed',
            'created', 'new' => 'created',
            'waitingforpayment', 'pending', 'processing' => 'waiting_for_payment',
            'cancelled', 'canceled', 'declined' => 'cancelled',
            'refunded', 'refund' => 'refunded',
            'failed', 'error', 'expired' => 'failed',
            default => 'waiting_for_payment',
        };
    }

    private function callbackProviderTransactionId(Request $request, string $fallback): string
    {
        return (string) (
            $request->input('checkoutId')
            ?: $request->input('checkout_id')
            ?: $request->input('transactionId')
            ?: $request->input('paymentId')
            ?: $request->input('payId')
            ?: $request->input('id')
            ?: $request->input('paymentReference')
            ?: $request->input('payment_reference')
            ?: $fallback
        );
    }

    private function recordPaymentCallback(Order $order, Request $request, string $providerTransactionId): PaymentTransaction
    {
        $knownReferences = array_values(array_unique(array_filter([
            $providerTransactionId,
            $order->payment_reference,
            $order->order_number,
        ])));

        $transaction = PaymentTransaction::query()
            ->where('order_id', $order->id)
            ->where('provider', 'maib')
            ->whereIn('provider_transaction_id', $knownReferences)
            ->latest()
            ->first();

        if (! $transaction) {
            $transaction = PaymentTransaction::create([
                'order_id' => $order->id,
                'provider' => 'maib',
                'provider_transaction_id' => $providerTransactionId,
                'status' => 'waiting_for_payment',
                'amount' => $order->total,
                'currency' => $order->currency,
            ]);
        }

        $transaction->forceFill([
            'provider_transaction_id' => $transaction->provider_transaction_id ?: $providerTransactionId,
            'callback_payload_json' => $request->all(),
            'callback_signature' => $request->header('X-Signature') ?: $request->input('signature'),
        ])->save();

        return $transaction;
    }

    private function deductOrderStock(Order $order): bool
    {
        if ($order->stock_deducted_at) {
            return true;
        }

        $order->loadMissing('items');
        $productIds = $order->items->pluck('product_id')->filter()->values();

        if ($productIds->isEmpty()) {
            return false;
        }

        $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($order->items as $item) {
            $product = $products->get($item->product_id);

            if (! $product || ! $product->is_active || $product->stock_status !== 'in_stock' || $product->stock_quantity < $item->quantity) {
                return false;
            }
        }

        foreach ($order->items as $item) {
            $this->decrementProductStock($products->get($item->product_id), (int) $item->quantity);
        }

        $order->forceFill(['stock_deducted_at' => now()])->save();

        return true;
    }

    private function decrementProductStock(Product $product, int $quantity): void
    {
        $newStock = max(0, (int) $product->stock_quantity - $quantity);

        $product->forceFill([
            'stock_quantity' => $newStock,
            'stock_status' => $newStock > 0 ? 'in_stock' : 'out_of_stock',
            'is_active' => $newStock > 0,
        ])->save();
    }

    private function cart(): array
    {
        $cart = collect(session('cart', []));
        $products = Product::with('brand')
            ->whereIn('id', $cart->keys())
            ->availableForSale()
            ->get()
            ->keyBy('id');

        $items = $cart
            ->map(function ($quantity, $productId) use ($products) {
                $product = $products->get((int) $productId);

                if (! $product || $product->stock_status !== 'in_stock') {
                    return null;
                }

                $quantity = min((int) $quantity, $product->stock_quantity);

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'total' => (float) $product->price * $quantity,
                ];
            })
            ->filter()
            ->values();

        return [
            'items' => $items,
            'subtotal' => $items->sum('total'),
            'discount' => 0,
            'shipping' => 0,
            'total' => $items->sum('total'),
            'count' => $items->sum('quantity'),
        ];
    }
}
