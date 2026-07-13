@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>{{ __('ui.home') }} / {{ __('ui.cart') }}</p><h1>{{ __('ui.cart_title') }}</h1><span>{{ __('ui.cart_count', ['count' => $cart['count']]) }}</span></section>
<section class="shell cart-layout">
    <div class="cart-list">
        @forelse($cart['items'] as $item)
            <div class="cart-item">
                <img
                    src="{{ $item['product']->main_image ?: '/images/products/product-placeholder-toolbox.svg' }}"
                    alt="{{ $item['product']->display_name }}"
                    onerror="this.onerror=null;this.src='/images/products/product-placeholder-toolbox.svg';"
                >
                <div><h3>{{ $item['product']->display_name }}</h3><small>{{ __('ui.product_code') }}: {{ $item['product']->sku }}</small><span class="stock">● {{ __('ui.in_stock') }}</span></div>
                <strong>{{ money($item['product']->price) }}</strong>
                <form action="{{ route('cart.update', $item['product']) }}" method="post" class="qty">@csrf @method('PATCH')<input type="number" min="0" name="quantity" value="{{ $item['quantity'] }}"><button>OK</button></form>
                <strong>{{ money($item['total']) }}</strong>
                <form action="{{ route('cart.remove', $item['product']) }}" method="post">@csrf @method('DELETE')<button class="delete">{{ __('ui.remove') }}</button></form>
            </div>
        @empty
            <div class="empty">{{ __('ui.empty_cart') }}</div>
        @endforelse
        <form class="promo"><label>{{ __('ui.promo_code') }}</label><input placeholder="{{ __('ui.promo_placeholder') }}"><button class="btn small">{{ __('ui.apply') }}</button></form>
    </div>
    <aside class="summary">
        <h2>{{ __('ui.order_summary') }}</h2>
        <p><span>{{ __('ui.subtotal') }}</span><strong>{{ money($cart['subtotal']) }}</strong></p>
        <p><span>{{ __('ui.discount') }}</span><strong>- {{ money($cart['discount']) }}</strong></p>
        <p><span>{{ __('ui.delivery') }}</span><strong>{{ __('ui.free') }}</strong></p>
        <hr>
        <p class="total"><span>{{ __('ui.total_to_pay') }}</span><strong>{{ money($cart['total']) }}</strong></p>
        <a class="btn outline" href="{{ route('catalog') }}">{{ __('ui.continue_shopping') }}</a>
        <a class="btn orange-btn" href="{{ route('checkout.show') }}">{{ __('ui.checkout') }}</a>
        <x-consultation-cta compact class="summary-consultation" />
    </aside>
</section>
<div class="sticky-checkout"><strong>{{ __('ui.total_to_pay') }}: {{ money($cart['total']) }}</strong><a class="btn small orange-btn" href="{{ route('checkout.show') }}">{{ __('ui.checkout') }}</a></div>
@endsection
