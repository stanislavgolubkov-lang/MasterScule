<article class="product-card">
    <a href="{{ route('product.show', $product->slug) }}" class="product-image">
        @if($product->badge)<span class="badge {{ $product->is_discounted ? 'orange-badge' : '' }}">{{ $product->badge }}</span>@endif
        <img src="{{ $product->main_image }}" alt="{{ $product->display_name }}">
    </a>
    <div class="product-body">
        <small>{{ $product->brand->name }}</small>
        <h3><a href="{{ route('product.show', $product->slug) }}">{{ $product->display_name }}</a></h3>
        <div class="rating">★★★★★ <span>({{ $product->reviews_count }})</span></div>
        <div class="price-row">
            <strong>{{ number_format((float) $product->price, 2, ',', '.') }} RON</strong>
            @if($product->old_price)<del>{{ number_format((float) $product->old_price, 2, ',', '.') }} RON</del>@endif
        </div>
        <div class="stock">● În stoc</div>
        <form action="{{ route('cart.add', $product) }}" method="post">
            @csrf
            <button class="btn small">Adaugă în coș</button>
        </form>
    </div>
</article>
