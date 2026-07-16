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
    $inStock = $product->is_purchasable;
    $productDescription = $product->display_description;
    $productAttributes = $product->display_attributes;
    $productContents = $product->display_package_contents;
    $detailTabs = [
        'description' => __('ui.description'),
        'specifications' => __('ui.specifications'),
        'contents' => __('ui.contents'),
        'delivery' => __('ui.delivery_and_payment'),
        'warranty' => __('ui.warranty'),
    ];
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
        <div class="meta"><span>{{ __('ui.brand') }}: <a href="{{ route('brand.show', $product->brand->slug) }}">{{ $product->brand->name }}</a></span><span>{{ __('ui.product_code') }}: {{ $product->sku }}</span><span class="stock {{ $inStock ? '' : 'stock-out' }}">● {{ $inStock ? __('ui.in_stock') : __('ui.out_of_stock') }}</span></div>
        <div class="rating">★★★★★ <span>({{ $product->reviews_count }} {{ __('ui.reviews') }})</span></div>
        <div class="product-price">{{ money($product->price) }} @if($product->old_price)<del>{{ money($product->old_price) }}</del>@endif</div>
        @if($inStock)
            <form action="{{ route('cart.add', $product) }}" method="post" class="buy-actions">
                @csrf
                <input type="number" name="quantity" min="1" value="1">
                <button class="btn">{{ __('ui.add_to_cart') }}</button>
                <button class="btn outline" formaction="{{ route('cart.add', $product) }}">{{ __('ui.buy_now') }}</button>
            </form>
        @else
            <div class="buy-actions">
                <button class="btn" disabled>{{ __('ui.out_of_stock') }}</button>
            </div>
        @endif
        <div class="service-row"><span>{{ __('ui.fast_delivery') }}</span><span>{{ __('ui.warranty') }}</span><span>{{ __('ui.consultation') }}</span></div>
        <x-consultation-cta compact class="product-consultation" />
    </div>
</section>

<section class="shell tabs-card">
    <div class="tabs" role="tablist" aria-label="{{ $product->display_name }}" data-product-tabs>
        @foreach($detailTabs as $tabId => $tabLabel)
            <button
                type="button"
                role="tab"
                id="product-tab-{{ $tabId }}"
                class="{{ $loop->first ? 'active' : '' }}"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                aria-controls="product-panel-{{ $tabId }}"
                data-product-tab="{{ $tabId }}"
            >
                {{ $tabLabel }}
            </button>
        @endforeach
    </div>

    <div
        id="product-panel-description"
        class="product-tab-panel active"
        role="tabpanel"
        tabindex="0"
        aria-labelledby="product-tab-description"
        data-product-panel="description"
    >
        <p>{!! nl2br(e($productDescription)) !!}</p>
    </div>

    <div
        id="product-panel-specifications"
        class="product-tab-panel"
        role="tabpanel"
        tabindex="0"
        aria-labelledby="product-tab-specifications"
        data-product-panel="specifications"
        hidden
    >
        @if($productAttributes)
            <table>
                @foreach($productAttributes as $key => $value)
                    <tr><th>{{ $key }}</th><td>{{ $value }}</td></tr>
                @endforeach
            </table>
        @else
            <p>{{ app()->isLocale('ru') ? 'Характеристики уточняются менеджером.' : 'Specificatiile sunt in curs de verificare.' }}</p>
        @endif
    </div>

    <div
        id="product-panel-contents"
        class="product-tab-panel"
        role="tabpanel"
        tabindex="0"
        aria-labelledby="product-tab-contents"
        data-product-panel="contents"
        hidden
    >
        @if($productContents)
            <ul class="product-tab-list">
                @foreach($productContents as $contentItem)
                    <li>{{ $contentItem }}</li>
                @endforeach
            </ul>
        @else
            <p>{{ app()->isLocale('ru') ? 'Комплектация уточняется перед подтверждением заказа.' : 'Continutul se confirma inainte de validarea comenzii.' }}</p>
        @endif
    </div>

    <div
        id="product-panel-delivery"
        class="product-tab-panel"
        role="tabpanel"
        tabindex="0"
        aria-labelledby="product-tab-delivery"
        data-product-panel="delivery"
        hidden
    >
        <p>{{ app()->isLocale('ru') ? 'Доставляем по Молдове курьером или согласованным способом. Оплата доступна наличными, переводом или онлайн-картой при оформлении заказа.' : 'Livram in Moldova prin curier sau prin metoda agreata. Plata este disponibila numerar, prin transfer sau online cu cardul la finalizarea comenzii.' }}</p>
    </div>

    <div
        id="product-panel-warranty"
        class="product-tab-panel"
        role="tabpanel"
        tabindex="0"
        aria-labelledby="product-tab-warranty"
        data-product-panel="warranty"
        hidden
    >
        <p>{{ app()->isLocale('ru') ? 'Гарантия на товар: '.$product->display_warranty.'. Перед отправкой товар проверяется, а по вопросам подбора и обслуживания помогает менеджер.' : 'Garantia produsului: '.$product->display_warranty.'. Produsul este verificat inainte de expediere, iar managerul te ajuta cu alegerea si service-ul.' }}</p>
    </div>
</section>

<section class="shell section-head"><h2>{{ __('ui.similar_products') }}</h2></section>
<section class="shell product-grid">
    @foreach($similarProducts->merge($brandProducts)->unique('id')->take(4) as $item)
        <x-product-card :product="$item" />
    @endforeach
</section>

<div class="sticky-buy"><strong>{{ money($product->price) }}</strong>@if($inStock)<form action="{{ route('cart.add', $product) }}" method="post">@csrf<button class="btn small">{{ __('ui.add_to_cart') }}</button></form>@else<span class="stock stock-out">{{ __('ui.out_of_stock') }}</span>@endif</div>
@endsection
