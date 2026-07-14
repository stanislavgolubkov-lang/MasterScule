<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Catalog\ProductImageAvailabilityService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly ProductImageAvailabilityService $images) {}

    public function index()
    {
        return view('shop.cart', ['cart' => $this->cart()]);
    }

    public function add(Request $request, Product $product)
    {
        $quantity = max(1, (int) $request->input('quantity', 1));

        $available = Product::whereKey($product->id)->availableForSale()->exists();
        if (! $available || ! $this->images->isAvailable($product->main_image)) {
            return back()->withErrors(['cart' => app()->isLocale('ru') ? 'Товар недоступен на складе.' : 'Produsul nu este disponibil in stoc.']);
        }

        $cart = session('cart', []);
        $nextQuantity = min(($cart[$product->id] ?? 0) + $quantity, $product->stock_quantity);
        $cart[$product->id] = $nextQuantity;
        session(['cart' => $cart]);

        return back()->with('success', app()->isLocale('ru') ? 'Товар добавлен в корзину.' : 'Produsul a fost adaugat in cos.');
    }

    public function update(Request $request, Product $product)
    {
        $quantity = max(0, (int) $request->input('quantity', 1));
        $cart = session('cart', []);

        if ($quantity === 0) {
            unset($cart[$product->id]);
        } else {
            if (! Product::whereKey($product->id)->availableForSale()->exists() || ! $this->images->isAvailable($product->main_image)) {
                unset($cart[$product->id]);
            } else {
                $cart[$product->id] = min($quantity, $product->stock_quantity);
            }
        }

        session(['cart' => $cart]);

        return back();
    }

    public function remove(Product $product)
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);

        return back();
    }

    private function cart(): array
    {
        $items = collect(session('cart', []))
            ->map(function ($quantity, $productId) {
                $product = Product::with('brand')->availableForSale()->find($productId);

                if (! $product) {
                    return null;
                }
                if (! $this->images->isAvailable($product->main_image)) {
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
