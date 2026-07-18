@extends('layouts.app')

@section('content')
@php
    $brandsHero = [
        'tone' => 'brands',
        'breadcrumbs' => [
            ['label' => __('ui.home'), 'url' => route('home')],
            ['label' => __('ui.brands')],
        ],
        'kicker' => __('ui.brands_hero_kicker'),
        'title' => __('ui.brands'),
        'text' => __('ui.brands_hero_text'),
        'image' => '/images/brands-hero.webp',
        'image_alt' => __('ui.brands_image_alt'),
        'badge_title' => __('ui.brands_badge_title'),
        'badge_text' => __('ui.brands_badge_text'),
        'actions' => [
            ['label' => __('ui.brands_action_catalog'), 'url' => route('catalog')],
            ['label' => __('ui.contact_us'), 'url' => route('page', 'contacts')],
        ],
        'points' => [
            __('ui.brands_point_official'),
            __('ui.brands_point_warranty'),
            __('ui.brands_point_support'),
        ],
    ];
@endphp

@include('shop.partials.editorial-hero', ['hero' => $brandsHero])

<section class="shell editorial-section-head brands-section-head">
    <span>{{ __('ui.brands_section_kicker') }}</span>
    <h2>{{ __('ui.brands_section_title') }}</h2>
    <p>{{ __('ui.brands_section_text', [
        'brands' => number_format($brands->count(), 0, ',', ' '),
        'products' => number_format($brands->sum('products_count'), 0, ',', ' '),
    ]) }}</p>
</section>

<section class="shell brand-grid brands-page-grid">
    @foreach($brands as $brand)
        @php
            $logoPath = $brand->logo ? public_path(ltrim($brand->logo, '/')) : null;
            $hasLogo = $brand->logo && $logoPath && file_exists($logoPath);
            $brandDisplayNames = [
                'king-tony' => 'King Tony',
                'm7-mighty-seven' => 'M7 Mighty Seven',
                'jtc' => 'JTC',
                'hoegert' => 'Högert Technik',
                'torin-big-red' => 'Torin BIG RED',
                'gys' => 'GYS',
            ];
            $brandDisplayName = $brandDisplayNames[$brand->slug] ?? $brand->name;
        @endphp
        <a class="brand-card brand-card-{{ $brand->slug }} {{ $brand->products_count === 0 ? 'is-empty' : '' }}" href="{{ route('brand.show', $brand->slug) }}">
            <span class="brand-logo-box">
                @if($hasLogo)
                    <img src="{{ $brand->logo }}" alt="{{ $brandDisplayName }}">
                @else
                    <span class="brand-logo-fallback">{{ $brandDisplayName }}</span>
                @endif
            </span>
            <strong>{{ $brandDisplayName }}</strong>
            <span class="brand-card-meta">
                {{ $brand->products_count > 0
                    ? __('ui.products_count', ['count' => number_format($brand->products_count, 0, ',', ' ')])
                    : __('ui.brand_products_coming')
                }}
            </span>
            <span class="brand-card-arrow" aria-hidden="true">→</span>
        </a>
    @endforeach
</section>

<section class="shell brands-assurance">
    <article>
        <span>01</span>
        <div>
            <strong>{{ __('ui.brands_assurance_original_title') }}</strong>
            <p>{{ __('ui.brands_assurance_original_text') }}</p>
        </div>
    </article>
    <article>
        <span>02</span>
        <div>
            <strong>{{ __('ui.brands_assurance_choice_title') }}</strong>
            <p>{{ __('ui.brands_assurance_choice_text') }}</p>
        </div>
    </article>
    <article>
        <span>03</span>
        <div>
            <strong>{{ __('ui.brands_assurance_help_title') }}</strong>
            <p>{{ __('ui.brands_assurance_help_text') }}</p>
        </div>
    </article>
</section>
@endsection
