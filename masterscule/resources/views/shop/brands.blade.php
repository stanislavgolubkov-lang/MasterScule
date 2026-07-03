@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>{{ __('ui.home') }} / {{ __('ui.brands') }}</p><h1>{{ __('ui.brands') }}</h1></section>
<section class="shell brand-grid">@foreach($brands as $brand)<a class="brand-card" href="{{ route('brand.show', $brand->slug) }}"><img src="{{ $brand->logo }}" alt="{{ $brand->name }}"><strong>{{ $brand->name }}</strong><span>{{ __('ui.products_count', ['count' => $brand->products_count]) }}</span></a>@endforeach</section>
@endsection
