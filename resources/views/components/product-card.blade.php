@php
    $productImage = trim((string) $product->main_image);
    $missingProductImage = $productImage === '' || \Illuminate\Support\Str::contains(
        \Illuminate\Support\Str::lower($productImage),
        ['placeholder', 'product-placeholder']
    );
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
                <img src="{{ $productImage }}" alt="{{ $product->display_name }}" onerror="this.hidden=true;this.previousElementSibling.hidden=false;this.closest('.product-card').classList.add('product-card-no-photo');">
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
            <div class="stock"><span aria-hidden="true">&#9679;</span> {{ __('ui.in_stock') }}</div>
            <form action="{{ route('cart.add', $product) }}" method="post">
                @csrf
                <button class="btn small product-card-button" @disabled($product->stock_status !== 'in_stock' || $product->stock_quantity < 1)>{{ __('ui.add_to_cart') }}</button>
            </form>
        </div>
    </div>
</article>
