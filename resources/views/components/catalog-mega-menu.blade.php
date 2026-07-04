@props(['categories'])

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

    $flatten = function ($items) use (&$flatten) {
        return collect($items)->flatMap(function ($item) use (&$flatten) {
            return collect([$item])->merge($flatten($item->childrenRecursive ?? collect()));
        });
    };

    $categoryMap = $flatten($sections)->keyBy('slug');
    $activeSlug = optional($sections->first())->slug;
    $popularCategories = collect([
        'seturi-de-scule',
        'chei-dinamometrice',
        'tubulare-si-clichete',
        'compresoare',
        'instrumente-cu-acumulator',
    ])->map(fn ($slug) => $categoryMap->get($slug))->filter();
@endphp

<div id="catalog-modal" class="catalog-modal" hidden>
    <div class="catalog-modal-backdrop" data-catalog-close></div>
    <section class="catalog-mega-panel" role="dialog" aria-modal="true" aria-labelledby="catalog-mega-title">
        <div class="catalog-mega-main">
            <aside class="mega-left" aria-label="{{ __('ui.main_categories') }}">
                <div class="mega-title">
                    <span>{{ __('ui.catalog') }}</span>
                    <h2 id="catalog-mega-title">{{ __('ui.choose_category') }}</h2>
                </div>
                <nav class="mega-section-list" role="tablist" aria-label="{{ __('ui.main_categories') }}">
                    @foreach($sections as $section)
                        <a
                            class="mega-section-link {{ $section->slug === $activeSlug ? 'active' : '' }}"
                            id="mega-section-{{ $section->slug }}"
                            href="{{ route('catalog', $section->slug) }}"
                            data-mega-section="{{ $section->slug }}"
                            role="tab"
                            aria-selected="{{ $section->slug === $activeSlug ? 'true' : 'false' }}"
                            aria-controls="mega-panel-{{ $section->slug }}"
                        >
                            <img src="{{ $section->image ?: '/images/products/product-placeholder-toolbox.svg' }}" alt="{{ $section->display_name }}">
                            <strong>{{ $section->display_name }}</strong>
                            <span aria-hidden="true">›</span>
                        </a>
                    @endforeach
                </nav>
                <a class="mega-all-products" href="{{ route('catalog') }}">
                    <span class="mega-grid-icon" aria-hidden="true"></span>
                    {{ __('ui.all_products') }}
                </a>
            </aside>

            <div class="mega-center">
                @foreach($sections as $section)
                    @php($children = $section->childrenRecursive->isNotEmpty() ? $section->childrenRecursive : collect([$section]))
                    <div
                        id="mega-panel-{{ $section->slug }}"
                        class="mega-subcategory-panel {{ $section->slug === $activeSlug ? 'active' : '' }}"
                        data-mega-panel="{{ $section->slug }}"
                        role="tabpanel"
                        aria-labelledby="mega-section-{{ $section->slug }}"
                    >
                        <div class="mega-center-head clean">
                            <h3>{{ $section->display_name }}</h3>
                            <p>{{ __('ui.menu_choose_subcategory') }}</p>
                        </div>
                        <div class="mega-text-columns">
                            @foreach($children as $child)
                                <div class="mega-text-group">
                                    <a class="mega-text-parent" href="{{ route('catalog', $child->slug) }}">
                                        {{ $child->display_name }}
                                    </a>
                                    @foreach($child->childrenRecursive as $leaf)
                                        <a class="mega-text-leaf" href="{{ route('catalog', $leaf->slug) }}">
                                            {{ $leaf->display_name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        <a class="mega-view-category" href="{{ route('catalog', $section->slug) }}">
                            {{ __('ui.view_all_in_category') }}
                        </a>
                    </div>
                @endforeach
            </div>

            <aside class="mega-right" aria-label="{{ __('ui.popular_categories') }}">
                <div class="mega-side-block">
                    <h3>{{ __('ui.popular_categories') }}</h3>
                    <div class="mega-popular-links">
                        @foreach($popularCategories as $category)
                            <a href="{{ route('catalog', $category->slug) }}">{{ $category->display_name }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="mega-side-block mega-promo-block">
                    <span class="mega-promo-mark">%</span>
                    <h3>{{ __('ui.promotions') }}</h3>
                    <p>{{ __('ui.menu_promotions_text') }}</p>
                    <a class="mega-side-action" href="{{ route('promotions') }}">{{ __('ui.menu_promotions_action') }}</a>
                </div>
                <div class="mega-side-block mega-help-block">
                    <h3>{{ __('ui.need_help') }}</h3>
                    <p>{{ __('ui.help_text') }}</p>
                    <a class="mega-side-action outline" href="{{ route('page', 'contacts') }}">{{ __('ui.contact_us') }}</a>
                </div>
            </aside>
        </div>

        <div class="mega-benefits" aria-label="{{ __('ui.menu_benefits') }}">
            <div><span class="mega-benefit-icon brand"></span><strong>{{ __('ui.official_brands') }}</strong><small>{{ __('ui.official_brands_text') }}</small></div>
            <div><span class="mega-benefit-icon warranty"></span><strong>{{ __('ui.quality_guarantee') }}</strong><small>{{ __('ui.quality_guarantee_text') }}</small></div>
            <div><span class="mega-benefit-icon delivery"></span><strong>{{ __('ui.fast_delivery') }}</strong><small>{{ __('ui.fast_delivery_text') }}</small></div>
            <div><span class="mega-benefit-icon payment"></span><strong>{{ __('ui.convenient_payment') }}</strong><small>{{ __('ui.convenient_payment_text') }}</small></div>
        </div>
    </section>
</div>
