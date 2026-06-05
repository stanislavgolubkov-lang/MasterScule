@extends('layouts.app')

@section('title', $product->display_name.' | MasterScule.ro')

@section('content')
<section class="shell product-page">
    <div class="gallery">
        <img class="main-product-img" src="{{ $product->main_image }}" alt="{{ $product->display_name }}">
        <div class="mini-facts"><span>Garanție 24 luni</span><span>Consultanță</span><span>Livrare rapidă</span></div>
    </div>
    <div class="buy-box">
        <p>Acasă / Catalog / {{ $product->category->name_ro }}</p>
        <h1>{{ $product->display_name }}</h1>
        <div class="meta"><span>Brand: <a href="{{ route('brand.show', $product->brand->slug) }}">{{ $product->brand->name }}</a></span><span>Cod produs: {{ $product->sku }}</span><span class="stock">● În stoc</span></div>
        <div class="rating">★★★★★ <span>({{ $product->reviews_count }} recenzii)</span></div>
        <div class="product-price">{{ number_format((float) $product->price, 2, ',', '.') }} RON @if($product->old_price)<del>{{ number_format((float) $product->old_price, 2, ',', '.') }} RON</del>@endif</div>
        <form action="{{ route('cart.add', $product) }}" method="post" class="buy-actions">
            @csrf
            <input type="number" name="quantity" min="1" value="1">
            <button class="btn">Adaugă în coș</button>
            <button class="btn outline" formaction="{{ route('cart.add', $product) }}">Cumpără acum</button>
        </form>
        <div class="service-row"><span>Livrare rapidă</span><span>Garanție</span><span>Consultanță</span></div>
        <a class="ai-link" href="{{ route('ai.advisor') }}" data-ai-open data-ai-prefill="Spune-mi daca produsul {{ $product->display_name }} cu SKU {{ $product->sku }} este potrivit pentru lucrarea mea.">Întreabă consultantul AI despre acest produs</a>
    </div>
</section>

<section class="shell tabs-card">
    <div class="tabs"><b>Descriere</b><b>Specificații</b><b>Conținut</b><b>Livrare și plată</b><b>Garanție</b></div>
    <p>{{ $product->display_description }}</p>
    <table>
        @foreach($product->attributes ?? [] as $key => $value)
            <tr><th>{{ $key }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>
</section>

<section class="shell section-head"><h2>Produse similare</h2></section>
<section class="shell product-grid">
    @foreach($similarProducts->merge($brandProducts)->unique('id')->take(4) as $item)
        <x-product-card :product="$item" />
    @endforeach
</section>

<div class="sticky-buy"><strong>{{ number_format((float) $product->price, 2, ',', '.') }} RON</strong><form action="{{ route('cart.add', $product) }}" method="post">@csrf<button class="btn small">Adaugă în coș</button></form></div>
@endsection
