@extends('layouts.app')

@section('title', $product->display_name.' | '.config('store.domain_label'))

@section('content')
<section class="shell product-page">
    <div class="gallery">
        <img class="main-product-img" src="{{ $product->main_image }}" alt="{{ $product->display_name }}">
        <div class="mini-facts"><span>{{ __('ui.warranty') }} 24 {{ app()->isLocale('ru') ? 'мес.' : 'luni' }}</span><span>{{ __('ui.consultation') }}</span><span>{{ __('ui.fast_delivery') }}</span></div>
    </div>
    <div class="buy-box">
        <p>{{ __('ui.home') }} / {{ __('ui.catalog') }} / {{ $product->category->display_name }}</p>
        <h1>{{ $product->display_name }}</h1>
        <div class="meta"><span>{{ __('ui.brand') }}: <a href="{{ route('brand.show', $product->brand->slug) }}">{{ $product->brand->name }}</a></span><span>{{ __('ui.product_code') }}: {{ $product->sku }}</span><span class="stock">● {{ __('ui.in_stock') }}</span></div>
        <div class="rating">★★★★★ <span>({{ $product->reviews_count }} {{ __('ui.reviews') }})</span></div>
        <div class="product-price">{{ money($product->price) }} @if($product->old_price)<del>{{ money($product->old_price) }}</del>@endif</div>
        <form action="{{ route('cart.add', $product) }}" method="post" class="buy-actions">
            @csrf
            <input type="number" name="quantity" min="1" value="1">
            <button class="btn">{{ __('ui.add_to_cart') }}</button>
            <button class="btn outline" formaction="{{ route('cart.add', $product) }}">{{ __('ui.buy_now') }}</button>
        </form>
        <div class="service-row"><span>{{ __('ui.fast_delivery') }}</span><span>{{ __('ui.warranty') }}</span><span>{{ __('ui.consultation') }}</span></div>
        <x-consultation-cta compact class="product-consultation" />
        @if(config('features.ai_assistant'))
            <a class="ai-link" href="{{ route('ai.advisor') }}" data-ai-open data-ai-prefill="{{ app()->isLocale('ru') ? 'Подскажи, подходит ли товар '.$product->display_name.' с кодом '.$product->sku.' для моей задачи.' : 'Spune-mi daca produsul '.$product->display_name.' cu SKU '.$product->sku.' este potrivit pentru lucrarea mea.' }}">{{ __('ui.ask_ai_about_product') }}</a>
        @endif
    </div>
</section>

<section class="shell tabs-card">
    <div class="tabs"><b>{{ __('ui.description') }}</b><b>{{ __('ui.specifications') }}</b><b>{{ __('ui.contents') }}</b><b>{{ __('ui.delivery_and_payment') }}</b><b>{{ __('ui.warranty') }}</b></div>
    <p>{{ $product->display_description }}</p>
    <table>
        @foreach($product->display_attributes as $key => $value)
            <tr><th>{{ $key }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>
</section>

<section class="shell section-head"><h2>{{ __('ui.similar_products') }}</h2></section>
<section class="shell product-grid">
    @foreach($similarProducts->merge($brandProducts)->unique('id')->take(4) as $item)
        <x-product-card :product="$item" />
    @endforeach
</section>

<div class="sticky-buy"><strong>{{ money($product->price) }}</strong><form action="{{ route('cart.add', $product) }}" method="post">@csrf<button class="btn small">{{ __('ui.add_to_cart') }}</button></form></div>
@endsection
