@extends('layouts.app')

@section('title', ($activeCategory->display_name ?? $activeBrand->name ?? __('ui.catalog_products')).' | '.config('store.domain_label'))

@section('content')
@php
    $sideItems = collect($sideNavigation['items'] ?? []);
    $showSide = $sideItems->isNotEmpty() || ($showProducts ?? true);
    $selectedBrands = $selectedBrands ?? [];
    $viewMode = $viewMode ?? 'grid';
    $drawerCategories = $sideItems->isNotEmpty() ? $sideItems : collect($categoryTiles ?? []);
@endphp

<section class="shell page-title catalog-title">
    <p class="catalog-breadcrumbs">
        <a href="{{ route('home') }}">{{ __('ui.home') }}</a>
        <span>/</span>
        <a href="{{ route('catalog') }}">{{ __('ui.catalog') }}</a>
        @foreach(($breadcrumbs ?? []) as $breadcrumb)
            <span>/</span>
            <a href="{{ route('catalog', $breadcrumb->slug) }}">{{ $breadcrumb->display_name }}</a>
        @endforeach
    </p>
    <h1>{{ $activeCategory->display_name ?? $activeBrand->name ?? __('ui.catalog_products') }}</h1>
    <span>
        @if(($categoryTiles ?? collect())->isNotEmpty() && ! ($showProducts ?? true))
            {{ __('ui.choose_next_category') }}
        @elseif($showProducts ?? true)
            {{ __('ui.products_count', ['count' => $products->total()]) }}
        @else
            {{ __('ui.choose_category') }}
        @endif
    </span>
</section>

@if($showProducts ?? true)
    <div id="mobile-filters" class="filter-drawer" hidden>
        <div class="filter-drawer-backdrop" data-close="mobile-filters"></div>
        <aside class="filter-drawer-panel" aria-label="{{ __('ui.filters') }}">
            <div class="filter-drawer-head">
                <h2>{{ __('ui.filters') }}</h2>
                <button type="button" data-close="mobile-filters" aria-label="{{ __('ui.close') }}">x</button>
            </div>
            @if($drawerCategories->isNotEmpty())
                <div class="filter-drawer-categories">
                    <strong>{{ __('ui.category') }}</strong>
                    @foreach($drawerCategories as $item)
                        <a class="{{ optional($activeCategory)->id === $item->id ? 'active' : '' }}" href="{{ route('catalog', $item->slug) }}">
                            {{ $item->display_name }}
                        </a>
                    @endforeach
                </div>
            @endif
            @include('shop.partials.catalog-filter-form')
        </aside>
    </div>
@endif

<section class="shell catalog-layout {{ $showSide ? '' : 'catalog-layout-wide' }}">
    @if($showSide)
        <aside class="filters catalog-rail">
            @if($sideItems->isNotEmpty())
                <div class="catalog-level-nav">
                    <span class="catalog-level-kicker">{{ __('ui.current_level') }}</span>
                    <h3>{{ $sideNavigation['title'] ?: __('ui.catalog') }}</h3>
                    @if(! empty($sideNavigation['back']))
                        <a class="catalog-back-link" href="{{ route('catalog', $sideNavigation['back']->slug) }}">{{ __('ui.back_to', ['name' => $sideNavigation['back']->display_name]) }}</a>
                    @elseif($activeCategory)
                        <a class="catalog-back-link" href="{{ route('catalog') }}">{{ __('ui.back_to', ['name' => __('ui.catalog')]) }}</a>
                    @endif
                    <nav class="catalog-level-list" aria-label="{{ __('ui.same_level_categories') }}">
                        @foreach($sideItems as $item)
                            <a class="{{ optional($activeCategory)->id === $item->id ? 'active' : '' }}" href="{{ route('catalog', $item->slug) }}">
                                {{ $item->display_name }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endif

            @if($showProducts ?? true)
                <div class="catalog-filter-box">
                    <h3>{{ __('ui.filters') }}</h3>
                    @include('shop.partials.catalog-filter-form')
                </div>
            @endif
        </aside>
    @endif

    <div class="catalog-main">
        @if(($categoryTiles ?? collect())->isNotEmpty())
            <div class="catalog-step-head">
                <span>{{ __('ui.next_step') }}</span>
                <h2>{{ $activeCategory ? __('ui.choose_next_category') : __('ui.choose_section') }}</h2>
            </div>
            <div class="catalog-category-grid">
                @foreach($categoryTiles as $categoryTile)
                    <a class="catalog-category-card" href="{{ route('catalog', $categoryTile->slug) }}">
                        <span class="catalog-category-image">
                            <img src="{{ $categoryTile->image ?: '/images/products/product-placeholder-toolbox.svg' }}" alt="{{ $categoryTile->display_name }}">
                        </span>
                        <strong>{{ $categoryTile->display_name }}</strong>
                    </a>
                @endforeach
            </div>
        @endif

        @if($showProducts ?? true)
            <div class="catalog-toolbar">
                <button type="button" class="filter-drawer-button" data-open="mobile-filters">{{ __('ui.filters') }}</button>
                <form class="sort-row" method="get">
                    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                    @foreach($selectedBrands as $brandSlug)<input type="hidden" name="brand[]" value="{{ $brandSlug }}">@endforeach
                    @if(request('price_min'))<input type="hidden" name="price_min" value="{{ request('price_min') }}">@endif
                    @if(request('price_max'))<input type="hidden" name="price_max" value="{{ request('price_max') }}">@endif
                    @if(request('in_stock'))<input type="hidden" name="in_stock" value="1">@endif
                    @if(request('discounted'))<input type="hidden" name="discounted" value="1">@endif
                    <input type="hidden" name="view" value="{{ $viewMode }}">
                    <label>{{ __('ui.sort') }}</label>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="">{{ __('ui.sort_relevance') }}</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>{{ __('ui.sort_price_asc') }}</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>{{ __('ui.sort_price_desc') }}</option>
                        <option value="new" @selected(request('sort') === 'new')>{{ __('ui.sort_new') }}</option>
                    </select>
                </form>
                <strong>{{ __('ui.products_count', ['count' => $products->total()]) }}</strong>
                <div class="view-toggle" aria-label="{{ __('ui.view_mode') }}">
                    <a class="{{ $viewMode === 'grid' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" title="{{ __('ui.grid_view') }}">
                        <span class="view-icon view-icon-grid" aria-hidden="true"></span>
                        <span class="sr-only">{{ __('ui.grid_view') }}</span>
                    </a>
                    <a class="{{ $viewMode === 'list' ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" title="{{ __('ui.list_view') }}">
                        <span class="view-icon view-icon-list" aria-hidden="true"></span>
                        <span class="sr-only">{{ __('ui.list_view') }}</span>
                    </a>
                </div>
            </div>
            <div class="product-grid catalog-grid {{ $viewMode === 'list' ? 'catalog-grid-list' : '' }}">
                @forelse($products as $product)
                    <x-product-card :product="$product" />
                @empty
                    <div class="empty">{{ __('ui.no_products') }}</div>
                    <x-consultation-cta compact class="catalog-empty-consultation" />
                @endforelse
            </div>
            {{ $products->links() }}
        @elseif(($categoryTiles ?? collect())->isEmpty())
            <div class="empty">{{ __('ui.choose_from_list') }}</div>
        @endif
    </div>
</section>
@endsection
