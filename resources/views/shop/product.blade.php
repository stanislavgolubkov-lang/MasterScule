@extends('layouts.app')

@section('title', $product->display_name.' | '.config('store.domain_label'))

@section('content')
@php
    $productGallery = collect([$product->main_image])
        ->merge($product->gallery ?? [])
        ->filter()
        ->reject(fn ($image) => \Illuminate\Support\Str::contains(
            \Illuminate\Support\Str::lower((string) $image),
            ['placeholder', 'product-placeholder']
        ))
        ->unique()
        ->values();
    $mainProductImage = $productGallery->first();
@endphp
<section class="shell product-page">
    <div class="gallery">
        <div class="product-main-stage">
            @if($mainProductImage)
                <img
                    class="main-product-img"
                    src="{{ $mainProductImage }}"
                    alt="{{ $product->display_name }}"
                    data-product-main-image
                    onerror="this.hidden=true;this.nextElementSibling.hidden=false;"
                >
            @endif
            <div class="product-main-missing" @if($mainProductImage) hidden @endif>
                <span class="product-image-missing-mark" aria-hidden="true"></span>
                <strong>{{ __('ui.product_photo_pending') }}</strong>
                <small>SKU {{ $product->sku }}</small>
            </div>
        </div>
        @if($productGallery->count() > 1)
            <div class="product-thumbnails" aria-label="{{ $product->display_name }}">
                @foreach($productGallery as $galleryImage)
                    <button
                        type="button"
                        class="product-thumbnail {{ $loop->first ? 'active' : '' }}"
                        data-product-gallery-src="{{ $galleryImage }}"
                        aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                        aria-label="{{ $product->display_name }} {{ $loop->iteration }}"
                    >
                        <img src="{{ $galleryImage }}" alt="" onerror="this.closest('button').hidden=true;">
                    </button>
                @endforeach
            </div>
        @endif
        <div class="mini-facts"><span>{{ __('ui.warranty') }} 24 {{ app()->isLocale('ru') ? 'мес.' : 'luni' }}</span><span>{{ __('ui.consultation') }}</span><span>{{ __('ui.fast_delivery') }}</span></div>
    </div>
    <div class="buy-box">
        <nav class="product-breadcrumbs" aria-label="{{ __('ui.catalog') }}">
            <a href="{{ route('home') }}">{{ __('ui.home') }}</a>
            <span>/</span>
            <a href="{{ route('catalog') }}">{{ __('ui.catalog') }}</a>
            <span>/</span>
            <a href="{{ route('catalog', $product->category->slug) }}">{{ $product->category->display_name }}</a>
        </nav>
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
