@extends('layouts.app')

@section('title', ($activeCategory->display_name ?? $activeBrand->name ?? __('ui.catalog_products')).' | '.config('store.domain_label'))

@section('content')
@php
    $catalogTree = collect($catalogTree ?? []);
    $subcategories = collect($subcategories ?? []);
    $rootCatalogSections = collect($rootCatalogSections ?? $categoryTiles ?? []);
    $showRootTiles = ! $activeCategory && ! isset($activeBrand) && $rootCatalogSections->isNotEmpty();
    $showSide = $catalogTree->isNotEmpty() || ($showProducts ?? true);
    $selectedBrands = $selectedBrands ?? [];
    $viewMode = $viewMode ?? 'grid';
    $drawerCategories = $subcategories->isNotEmpty() ? $subcategories : $catalogTree;
    $sidebarCurrentLabel = $activeCategory->display_name ?? __('ui.all_categories');
    $editorialCatalogType = match(optional($activeCategory)->slug) {
        'echipamente-pentru-service' => 'service',
        'instrument-manual' => 'garage',
        default => null,
    };

    $catalogEditorialHero = $editorialCatalogType ? [
        'tone' => $editorialCatalogType,
        'breadcrumbs' => [
            ['label' => __('ui.home'), 'url' => route('home')],
            ['label' => __('ui.catalog'), 'url' => route('catalog')],
            ['label' => $activeCategory->display_name],
        ],
        'kicker' => __("ui.{$editorialCatalogType}_hero_kicker"),
        'title' => $activeCategory->display_name,
        'text' => __("ui.{$editorialCatalogType}_hero_text"),
        'image' => "/images/{$editorialCatalogType}-hero.webp",
        'image_alt' => __("ui.{$editorialCatalogType}_image_alt"),
        'badge_title' => __("ui.{$editorialCatalogType}_badge_title"),
        'badge_text' => __("ui.{$editorialCatalogType}_badge_text"),
        'actions' => [
            ['label' => __("ui.{$editorialCatalogType}_action_sections"), 'url' => '#catalog-content'],
            ['label' => __('ui.contact_us'), 'url' => route('page', 'contacts')],
        ],
        'points' => [
            __("ui.{$editorialCatalogType}_point_one"),
            __("ui.{$editorialCatalogType}_point_two"),
            __("ui.{$editorialCatalogType}_point_three"),
        ],
    ] : null;
@endphp

@if($catalogEditorialHero)
    @include('shop.partials.editorial-hero', ['hero' => $catalogEditorialHero])
@else
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
            @if(! $activeCategory && ! isset($activeBrand))
                {{ __('ui.catalog_intro') }}
            @elseif($showProducts ?? true)
                {{ __('ui.products_count', ['count' => $products->total()]) }}
            @else
                {{ __('ui.catalog_intro') }}
            @endif
        </span>
    </section>
@endif

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

<section id="catalog-content" class="shell catalog-layout {{ $showSide ? '' : 'catalog-layout-wide' }} {{ $catalogEditorialHero ? 'catalog-editorial-layout' : '' }}">
    @if($showSide)
        <aside class="catalog-rail">
            @if($catalogTree->isNotEmpty())
                <section class="catalog-sidebar">
                    <button
                        type="button"
                        class="catalog-sidebar__mobile-toggle"
                        data-catalog-sidebar-mobile-toggle
                        data-label-open="{{ __('ui.show_sections') }}"
                        data-label-close="{{ __('ui.hide_sections') }}"
                        aria-expanded="false"
                        aria-controls="catalog-sidebar-nav"
                        aria-label="{{ __('ui.show_sections') }}"
                    >
                        <span>{{ __('ui.catalog_sections') }}</span>
                        <small>{{ $sidebarCurrentLabel }}</small>
                        <span class="catalog-sidebar__mobile-chevron" aria-hidden="true"></span>
                    </button>

                    <nav id="catalog-sidebar-nav" class="catalog-sidebar__nav is-collapsed" aria-label="{{ __('ui.catalog_sections') }}">
                        <h2 class="catalog-sidebar__title">{{ __('ui.catalog_sections') }}</h2>
                        <ul class="catalog-sidebar__list">
                            <li class="catalog-sidebar__item {{ ! $activeCategory && ! isset($activeBrand) ? 'is-active' : '' }}">
                                <div class="catalog-sidebar__linkrow">
                                    <a
                                        class="catalog-sidebar__link"
                                        href="{{ route('catalog') }}"
                                        @if(! $activeCategory && ! isset($activeBrand)) aria-current="page" @endif
                                    >
                                        {{ __('ui.all_categories') }}
                                    </a>
                                </div>
                            </li>

                            @foreach($catalogTree as $section)
                                @php
                                    $sectionActive = in_array($section->id, $activePathIds ?? [], true);
                                    $sectionExact = optional($activeCategory)->id === $section->id;
                                    $sectionOpen = $sectionActive;
                                    $sectionChildren = $section->childrenRecursive;
                                    $sectionPanelId = 'catalog-sidebar-sublist-'.$section->id;
                                @endphp
                                <li class="catalog-sidebar__item {{ $sectionActive ? 'is-active' : '' }} {{ $sectionOpen ? 'is-open' : '' }}">
                                    <div class="catalog-sidebar__linkrow">
                                        <a
                                            class="catalog-sidebar__link"
                                            href="{{ route('catalog', $section->slug) }}"
                                            @if($sectionExact) aria-current="page" @endif
                                        >
                                            {{ $section->display_name }}
                                        </a>
                                        @if($sectionChildren->isNotEmpty())
                                            <button
                                                type="button"
                                                class="catalog-sidebar__chevron"
                                                data-catalog-sidebar-toggle
                                                aria-expanded="{{ $sectionOpen ? 'true' : 'false' }}"
                                                aria-controls="{{ $sectionPanelId }}"
                                                aria-label="{{ $section->display_name }}"
                                            >
                                                <span aria-hidden="true"></span>
                                            </button>
                                        @endif
                                    </div>

                                    @if($sectionChildren->isNotEmpty())
                                        <ul id="{{ $sectionPanelId }}" class="catalog-sidebar__sublist" @if(! $sectionOpen) hidden @endif>
                                            @foreach($sectionChildren as $child)
                                                @php
                                                    $childActive = in_array($child->id, $activePathIds ?? [], true);
                                                    $childExact = optional($activeCategory)->id === $child->id;
                                                @endphp
                                                <li>
                                                    <a
                                                        class="catalog-sidebar__sublink {{ $childActive ? 'is-active' : '' }}"
                                                        href="{{ route('catalog', $child->slug) }}"
                                                        @if($childExact) aria-current="page" @endif
                                                    >
                                                        {{ $child->display_name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </section>
            @endif

            @if($showProducts ?? true)
                <div class="filters catalog-filter-box">
                    <h3>{{ __('ui.filters') }}</h3>
                    @include('shop.partials.catalog-filter-form')
                </div>
            @endif
        </aside>
    @endif

    <div class="catalog-main">
        @if($showRootTiles)
            <div class="catalog-category-grid">
                @foreach($rootCatalogSections as $categoryTile)
                    <a class="catalog-category-card" href="{{ route('catalog', $categoryTile->slug) }}">
                        <span class="catalog-category-image">
                            <img src="{{ $categoryTile->image ?: '/images/products/product-placeholder-toolbox.svg' }}" alt="{{ $categoryTile->display_name }}">
                        </span>
                        <strong>{{ $categoryTile->display_name }}</strong>
                    </a>
                @endforeach
            </div>
        @endif

        @if($subcategories->isNotEmpty())
            <section class="subcategory-strip">
                <div class="subcategory-strip-head">
                    <h2>{{ __('ui.subcategories') }}</h2>
                    <p>{{ __('ui.subcategories_help') }}</p>
                </div>
                <div class="subcategory-links">
                    @foreach($subcategories as $subcategory)
                        <a href="{{ route('catalog', $subcategory->slug) }}">{{ $subcategory->display_name }}</a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($showProducts ?? true)
            <div class="catalog-toolbar">
                <button type="button" class="filter-drawer-button" data-open="mobile-filters">{{ __('ui.filters') }}</button>
                <form class="sort-row" method="get">
                    @if(request('task'))<input type="hidden" name="task" value="{{ request('task') }}">@endif
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
        @elseif(! $showRootTiles && $subcategories->isEmpty())
            <div class="empty">{{ __('ui.choose_from_list') }}</div>
        @endif
    </div>
</section>
@endsection
