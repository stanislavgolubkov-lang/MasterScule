@extends('layouts.app')

@section('title', $title.' | MasterScule.ro')

@section('content')
<section class="shell page-title">
    <p>Acasa / {{ $title }}</p>
    <h1>{{ $title }}</h1>
    <span>{{ $subtitle }}</span>
</section>

<section class="shell product-grid">
    @forelse($products as $product)
        <x-product-card :product="$product" />
    @empty
        <div class="empty">Nu exista produse in aceasta lista momentan.</div>
    @endforelse
</section>

@if(method_exists($products, 'links'))
    <section class="shell pagination-wrap">{{ $products->links() }}</section>
@endif
@endsection
