@props(['categories', 'cartCount' => 0])

@php
    $catalogRoot = $categories->firstWhere('slug', 'instrumente-si-mobilier');
    $menuOrder = [
        'mobilier-pentru-service',
        'scule-speciale-auto',
        'instrument-manual',
        'scule-pneumatice',
        'electroinstrumente',
        'instrumente-cu-acumulator',
        'instrumente-electromontaj',
        'instrumente-de-masurare',
        'accesorii-si-consumabile',
    ];

    $sections = collect($catalogRoot?->childrenRecursive ?? [])
        ->filter(fn ($category) => in_array($category->slug, $menuOrder, true))
        ->sortBy(fn ($category) => array_search($category->slug, $menuOrder, true))
        ->values();
@endphp

<div id="mobile-menu" class="mobile-catalog-drawer" hidden>
    <div class="mobile-catalog-backdrop" data-close="mobile-menu"></div>
    <aside class="mobile-catalog-panel" role="dialog" aria-modal="true" aria-label="{{ __('ui.catalog_products') }}">
        <div class="mobile-catalog-head">
            <a href="{{ route('home') }}" class="mobile-catalog-logo">
                <img src="/images/brand/master-scule-logo.png" alt="{{ config('store.domain_label') }}">
                <span>{{ config('store.domain_label') }}</span>
            </a>
            <button type="button" data-close="mobile-menu" aria-label="{{ __('ui.close') }}">x</button>
        </div>

        <form action="{{ route('catalog') }}" class="mobile-catalog-search">
            <input name="q" placeholder="{{ __('ui.search_placeholder') }}">
            <button aria-label="{{ __('ui.search_button') }}">{{ __('ui.search_button') }}</button>
        </form>

        <div class="mobile-catalog-accordion">
            @foreach($sections as $section)
                <section class="mobile-catalog-item">
                    <button
                        type="button"
                        data-mobile-catalog-toggle
                        aria-expanded="false"
                        aria-controls="mobile-catalog-panel-{{ $section->slug }}"
                    >
                        <img src="{{ $section->image ?: '/images/products/product-placeholder-toolbox.svg' }}" alt="{{ $section->display_name }}">
                        <strong>{{ $section->display_name }}</strong>
                        <span aria-hidden="true">›</span>
                    </button>
                    <div id="mobile-catalog-panel-{{ $section->slug }}" class="mobile-catalog-sublist" hidden>
                        <a class="mobile-catalog-view-all" href="{{ route('catalog', $section->slug) }}">{{ __('ui.view_catalog') }}</a>
                        @foreach($section->childrenRecursive as $child)
                            <a href="{{ route('catalog', $child->slug) }}">{{ $child->display_name }}</a>
                            @foreach($child->childrenRecursive as $leaf)
                                <a class="mobile-catalog-leaf" href="{{ route('catalog', $leaf->slug) }}">{{ $leaf->display_name }}</a>
                            @endforeach
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mobile-catalog-links">
            <a href="{{ route('catalog') }}">{{ __('ui.all_products') }}</a>
            <a href="{{ route('promotions') }}">{{ __('ui.promotions') }}</a>
            <a href="{{ route('new') }}">{{ __('ui.new_items') }}</a>
            <a href="{{ route('brands') }}">{{ __('ui.brands') }}</a>
            <a href="{{ route('page', 'contacts') }}">{{ __('ui.contact') }}</a>
            <a href="{{ route('cart.index') }}">{{ __('ui.cart') }} <b>{{ $cartCount }}</b></a>
        </div>

        <div class="mobile-catalog-help">
            <strong>{{ __('ui.need_help') }}</strong>
            <p>{{ __('ui.help_text') }}</p>
            <a class="btn small" href="{{ route('page', 'contacts') }}">{{ __('ui.contact_us') }}</a>
        </div>

        <div class="language-switch mobile-language">
            <a class="{{ app()->isLocale('ru') ? 'active' : '' }}" href="{{ route('language.switch', 'ru') }}">{{ __('ui.ru') }}</a>
            <a class="{{ app()->isLocale('ro') ? 'active' : '' }}" href="{{ route('language.switch', 'ro') }}">{{ __('ui.ro') }}</a>
        </div>
    </aside>
</div>
