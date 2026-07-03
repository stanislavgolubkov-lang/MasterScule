@extends('layouts.app')

@section('title', $title.' | '.config('store.domain_label'))

@section('content')
<section class="shell page-title">
    <p>{{ __('ui.home') }} / {{ $title }}</p>
    <h1>{{ $title }}</h1>
    <span>{{ $subtitle }}</span>
</section>

<section class="shell product-grid">
    @forelse($products as $product)
        <x-product-card :product="$product" />
    @empty
        <div class="empty">{{ __('ui.collection_empty') }}</div>
    @endforelse
</section>

@if(method_exists($products, 'links'))
    <section class="shell pagination-wrap">{{ $products->links() }}</section>
@endif
@endsection
