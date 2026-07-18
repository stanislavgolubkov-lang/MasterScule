@php
    $heroTone = $hero['tone'] ?? 'blue';
    $heroActions = collect($hero['actions'] ?? []);
    $heroPoints = collect($hero['points'] ?? []);
    $heroBreadcrumbs = collect($hero['breadcrumbs'] ?? []);
@endphp

<section class="shell editorial-hero editorial-hero-{{ $heroTone }} page-banner">
    <div class="editorial-hero-copy">
        @if($heroBreadcrumbs->isNotEmpty())
            <nav class="editorial-hero-breadcrumbs" aria-label="{{ __('ui.catalog') }}">
                @foreach($heroBreadcrumbs as $breadcrumb)
                    @if(! $loop->first)<span>/</span>@endif
                    @if(! empty($breadcrumb['url']))
                        <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                    @else
                        <span>{{ $breadcrumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <span class="editorial-hero-kicker">{{ $hero['kicker'] }}</span>
        <h1>{{ $hero['title'] }}</h1>
        <p>{{ $hero['text'] }}</p>

        @if($heroActions->isNotEmpty())
            <div class="editorial-hero-actions">
                @foreach($heroActions as $action)
                    <a class="btn {{ $loop->first ? 'editorial-primary-action' : 'editorial-secondary-action' }}" href="{{ $action['url'] }}">
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        @if($heroPoints->isNotEmpty())
            <div class="editorial-hero-points">
                @foreach($heroPoints as $point)
                    <span>{{ $point }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="editorial-hero-media">
        <img
            src="{{ $hero['image'] }}"
            alt="{{ $hero['image_alt'] }}"
            width="1712"
            height="918"
            fetchpriority="high"
        >

        @if(! empty($hero['badge_title']))
            <div class="editorial-hero-badge">
                <strong>{{ $hero['badge_title'] }}</strong>
                @if(! empty($hero['badge_text']))
                    <span>{{ $hero['badge_text'] }}</span>
                @endif
            </div>
        @endif
    </div>
</section>
