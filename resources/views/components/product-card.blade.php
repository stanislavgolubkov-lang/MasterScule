<article class="product-card">
    <div class="product-image">
        @if($product->badge)<span class="badge {{ $product->is_discounted ? 'orange-badge' : '' }}">{{ $product->badge }}</span>@endif
        @if(config('features.wishlist'))
            <button type="button" class="favorite-btn" aria-label="{{ __('ui.favorites') }}">&#9825;</button>
        @endif
        <a href="{{ route('product.show', $product->slug) }}" class="product-image-link">
            <img src="{{ $product->main_image }}" alt="{{ $product->display_name }}">
        </a>
    </div>
    <div class="product-body">
        <small>{{ $product->brand->name }}</small>
        <h3><a href="{{ route('product.show', $product->slug) }}">{{ $product->display_name }}</a></h3>
        <div class="rating"><span aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span> <span>({{ $product->reviews_count }})</span></div>
        <div class="price-row">
            <strong>{{ money($product->price) }}</strong>
            @if($product->old_price)<del>{{ money($product->old_price) }}</del>@endif
        </div>
        <div class="stock"><span aria-hidden="true">&#9679;</span> {{ __('ui.in_stock') }}</div>
        <form action="{{ route('cart.add', $product) }}" method="post">
            @csrf
            <button class="btn small" @disabled($product->stock_status !== 'in_stock' || $product->stock_quantity < 1)>{{ __('ui.add_to_cart') }}</button>
        </form>
    </div>
</article>
