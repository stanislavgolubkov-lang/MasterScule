@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>Acasă / Branduri</p><h1>Branduri</h1></section>
<section class="shell brand-grid">@foreach($brands as $brand)<a class="brand-card" href="{{ route('brand.show', $brand->slug) }}"><img src="{{ $brand->logo }}" alt="{{ $brand->name }}"><strong>{{ $brand->name }}</strong><span>{{ $brand->products_count }} produse</span></a>@endforeach</section>
@endsection
