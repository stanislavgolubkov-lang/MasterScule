@extends('layouts.app')

@section('title', ($activeCategory->name_ro ?? $activeBrand->name ?? 'Catalog produse').' | MasterScule.ro')

@section('content')
<section class="shell page-title">
    <p>Acasă / Catalog</p>
    <h1>{{ $activeCategory->name_ro ?? $activeBrand->name ?? 'Catalog produse' }}</h1>
    <span>{{ $products->total() }} produse</span>
</section>

<section class="shell catalog-layout">
    <aside class="filters">
        <h3>Categorii</h3>
        @foreach($categories as $category)
            <a class="{{ optional($activeCategory)->id === $category->id ? 'active' : '' }}" href="{{ route('catalog', $category->slug) }}">{{ $category->name_ro }}</a>
        @endforeach
        <h3>Filtre</h3>
        <form>
            <label>Brand</label>
            <select name="brand"><option value="">Toate</option>@foreach($brands as $brand)<option value="{{ $brand->slug }}" @selected(request('brand')===$brand->slug)>{{ $brand->name }}</option>@endforeach</select>
            <label><input type="checkbox" name="in_stock" value="1" @checked(request('in_stock'))> În stoc</label>
            <label><input type="checkbox" name="discounted" value="1" @checked(request('discounted'))> Promoții</label>
            <button class="btn small">Afișează produsele</button>
        </form>
    </aside>
    <div>
        <form class="sort-row">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <select name="sort" onchange="this.form.submit()">
                <option value="">Relevanță</option>
                <option value="price_asc" @selected(request('sort')==='price_asc')>Preț crescător</option>
                <option value="price_desc" @selected(request('sort')==='price_desc')>Preț descrescător</option>
                <option value="new" @selected(request('sort')==='new')>Noutăți</option>
            </select>
        </form>
        <div class="product-grid catalog-grid">
            @forelse($products as $product)
                <x-product-card :product="$product" />
            @empty
                <div class="empty">Nu am găsit produse pentru filtrul ales.</div>
            @endforelse
        </div>
        {{ $products->links() }}
    </div>
</section>
@endsection
