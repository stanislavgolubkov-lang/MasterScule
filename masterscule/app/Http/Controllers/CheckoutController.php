<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function show()
    {
        if (! Auth::check()) {
            return view('shop.checkout-auth');
        }

        return view('shop.checkout', ['cart' => $this->cart()]);
    }

    public function store(Request $request)
    {
        abort_unless(Auth::check(), 403);

        $cart = $this->cart();
        if ($cart['items']->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Coșul este gol.']);
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_postcode' => ['nullable', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:60'],
            'payment_method' => ['required', 'in:cash_on_delivery,bank_transfer,online_card'],
            'shipping_method' => ['required', 'in:courier,pickup,individual'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($cart, $data) {
            $order = Order::create($data + [
                'user_id' => Auth::id(),
                'order_number' => 'MSR-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'status' => 'new',
                'subtotal' => $cart['subtotal'],
                'discount_total' => $cart['discount'],
                'shipping_total' => $cart['shipping'],
                'total' => $cart['total'],
                'customer_email' => Auth::user()->email,
                'shipping_country' => 'Romania',
            ]);

            foreach ($cart['items'] as $item) {
                $product = Product::whereKey($item['product']->id)->lockForUpdate()->firstOrFail();

                if (! $product->is_active || $product->stock_status !== 'in_stock' || $product->stock_quantity < $item['quantity']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cart' => 'Stoc insuficient pentru '.$item['product']->display_name.'.',
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

                $product->decrement('stock_quantity', $item['quantity']);
                if ($product->stock_quantity - $item['quantity'] <= 0) {
                    $product->forceFill(['stock_status' => 'out_of_stock'])->save();
                }
            }

            return $order;
        });

        session()->forget('cart');

        return redirect()->route('account.dashboard')->with('success', 'Comanda a fost creata: '.$order->order_number);
    }

    private function cart(): array
    {
        $cart = collect(session('cart', []));
        $products = Product::with('brand')
            ->whereIn('id', $cart->keys())
            ->where('is_active', true)
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
