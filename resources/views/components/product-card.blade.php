@php
    $productImage = trim((string) $product->main_image);
    $imageAvailable = app(\App\Services\Catalog\ProductImageAvailabilityService::class)->isAvailable($productImage);
    $missingProductImage = ! $imageAvailable;
    $inStock = $product->is_purchasable;
@endphp
<article class="product-card {{ $missingProductImage ? 'product-card-no-photo' : '' }}">
    <div class="product-image">
        @if(config('features.wishlist'))
            <button type="button" class="favorite-btn" aria-label="{{ __('ui.favorites') }}">&#9825;</button>
        @endif
        <a href="{{ route('product.show', $product->slug) }}" class="product-image-link {{ $missingProductImage ? 'product-image-missing' : '' }}">
            <span class="product-image-missing-content" @if(! $missingProductImage) hidden @endif>
                <span class="product-image-missing-mark" aria-hidden="true"></span>
                <strong>{{ __('ui.product_photo_pending_short') }}</strong>
                <small>SKU {{ $product->sku }}</small>
            </span>
            @if(! $missingProductImage)
                <img src="{{ $productImage }}" alt="{{ $product->display_name }}" loading="lazy" decoding="async" onerror="this.hidden=true;this.previousElementSibling.hidden=false;this.closest('.product-card').classList.add('product-card-no-photo');">
            @endif
        </a>
    </div>
    <div class="product-body">
        <div class="product-card-kicker">
            @if($product->badge)
                <span class="product-card-label {{ $product->is_discounted ? 'product-card-label-sale' : '' }}">{{ $product->badge }}</span>
            @endif
            <small class="product-brand">{{ $product->brand->name }}</small>
        </div>
        <h3><a href="{{ route('product.show', $product->slug) }}" title="{{ $product->display_name }}">{{ $product->display_name }}</a></h3>
        <div class="product-card-purchase">
            <div class="price-row">
                <strong>{{ money($product->price) }}</strong>
                @if($product->old_price)<del>{{ money($product->old_price) }}</del>@endif
            </div>
            <div class="stock {{ $inStock ? '' : 'stock-out' }}"><span aria-hidden="true">&#9679;</span> {{ $inStock ? __('ui.in_stock') : __('ui.out_of_stock') }}</div>
            <form action="{{ route('cart.add', $product) }}" method="post">
                @csrf
                <button class="btn small product-card-button" @disabled(! $inStock)>{{ $inStock ? __('ui.add_to_cart') : __('ui.out_of_stock') }}</button>
            </form>
        </div>
    </div>
</article>
