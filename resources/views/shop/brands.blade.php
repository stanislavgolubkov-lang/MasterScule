@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>{{ __('ui.home') }} / {{ __('ui.brands') }}</p><h1>{{ __('ui.brands') }}</h1></section>
<section class="shell brand-grid">
    @foreach($brands as $brand)
        @php
            $logoPath = $brand->logo ? public_path(ltrim($brand->logo, '/')) : null;
            $hasLogo = $brand->logo && $logoPath && file_exists($logoPath);
        @endphp
        <a class="brand-card" href="{{ route('brand.show', $brand->slug) }}">
            <span class="brand-logo-box">
                @if($hasLogo)
                    <img src="{{ $brand->logo }}" alt="{{ $brand->name }}">
                @else
                    <span class="brand-logo-fallback">{{ $brand->name }}</span>
                @endif
            </span>
            <strong>{{ $brand->name }}</strong>
            <span>{{ __('ui.products_count', ['count' => $brand->products_count]) }}</span>
        </a>
    @endforeach
</section>
@endsection
