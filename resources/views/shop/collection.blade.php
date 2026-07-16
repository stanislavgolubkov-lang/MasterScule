@extends('layouts.app')

@section('title', $title.' | '.config('store.domain_label'))

@section('content')
@if(($collectionHero ?? null) === 'new')
    @php
        $newHero = [
            'tone' => 'new',
            'breadcrumbs' => [
                ['label' => __('ui.home'), 'url' => route('home')],
                ['label' => $title],
            ],
            'kicker' => __('ui.new_hero_kicker'),
            'title' => $title,
            'text' => __('ui.new_hero_text'),
            'image' => '/images/new-arrivals-hero.webp',
            'image_alt' => __('ui.new_image_alt'),
            'badge_title' => __('ui.new_badge_title'),
            'badge_text' => __('ui.new_badge_text'),
            'actions' => [
                ['label' => __('ui.new_action_products'), 'url' => '#new-products'],
                ['label' => __('ui.new_action_catalog'), 'url' => route('catalog')],
            ],
            'points' => [
                __('ui.new_point_fresh'),
                __('ui.new_point_brands'),
                __('ui.new_point_stock'),
            ],
        ];
    @endphp

    @include('shop.partials.editorial-hero', ['hero' => $newHero])

    <section class="shell editorial-section-head" id="new-products">
        <span>{{ __('ui.new_section_kicker') }}</span>
        <div>
            <h2>{{ __('ui.new_section_title') }}</h2>
            <p>{{ __('ui.new_section_text') }}</p>
        </div>
    </section>
@elseif(($emptyState ?? null) !== 'promotions')
    <section class="shell page-title">
        <p>{{ __('ui.home') }} / {{ $title }}</p>
        <h1>{{ $title }}</h1>
        <span>{{ $subtitle }}</span>
    </section>
@endif

<section class="shell product-grid {{ $collectionClass ?? '' }} {{ ($emptyState ?? null) === 'promotions' && count($products) === 0 ? 'promotions-empty-layout' : '' }}">
    @forelse($products as $product)
        <x-product-card :product="$product" />
    @empty
        @if(($emptyState ?? null) === 'promotions')
            <div class="promotions-empty">
                <div class="promotions-empty-copy">
                    <span class="promotions-empty-kicker">{{ __('ui.promotions_empty_kicker') }}</span>
                    <h2>{{ __('ui.promotions_empty_title') }}</h2>
                    <p>{{ __('ui.promotions_empty_text') }}</p>

                    <div class="promotions-empty-actions">
                        <a class="btn orange-btn" href="{{ route('catalog') }}">{{ __('ui.promotions_browse_catalog') }}</a>
                        <a class="btn outline promotions-new-link" href="{{ route('new') }}">{{ __('ui.promotions_view_new') }}</a>
                    </div>

                    <div class="promotions-empty-points">
                        <span>{{ __('ui.promotions_point_prices') }}</span>
                        <span>{{ __('ui.promotions_point_offers') }}</span>
                        <span>{{ __('ui.promotions_point_stock') }}</span>
                    </div>
                </div>

                <div class="promotions-empty-media">
                    <img
                        src="/images/promotions-coming-soon.webp"
                        alt="{{ __('ui.promotions_image_alt') }}"
                        width="1712"
                        height="918"
                        fetchpriority="high"
                    >
                    <div class="promotions-empty-badge">
                        <strong>{{ __('ui.promotions_badge_title') }}</strong>
                        <span>{{ __('ui.promotions_badge_text') }}</span>
                    </div>
                </div>
            </div>

            <div class="promotions-followup">
                <div>
                    <span>{{ __('ui.promotions_followup_kicker') }}</span>
                    <h3>{{ __('ui.promotions_followup_title') }}</h3>
                    <p>{{ __('ui.promotions_followup_text') }}</p>
                </div>
                <a href="{{ route('page', 'contacts') }}">{{ __('ui.contact_us') }} <span aria-hidden="true">→</span></a>
            </div>
        @else
            <div class="empty">{{ __('ui.collection_empty') }}</div>
        @endif
    @endforelse
</section>

@if(method_exists($products, 'links'))
    <section class="shell pagination-wrap">{{ $products->links() }}</section>
@endif
@endsection
