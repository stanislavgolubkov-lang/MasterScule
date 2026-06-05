@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>Admin</p><h1>Panou administrare</h1></section>
<section class="shell account-main">
    <div class="stats">
        <div><strong>{{ $productsCount }}</strong><span>Produse</span></div>
        <div><strong>{{ $ordersCount }}</strong><span>Comenzi</span></div>
        <div><strong>{{ $brandsCount }}</strong><span>Branduri</span></div>
        <div><strong>{{ $usersCount }}</strong><span>Utilizatori</span></div>
    </div>
    <div class="admin-actions"><a class="btn" href="{{ route('admin.products') }}">CRUD produse</a><a class="btn outline" href="{{ route('admin.orders') }}">Comenzi</a><a class="btn outline" href="{{ route('admin.users') }}">Utilizatori</a><a class="btn outline" href="{{ route('ai.advisor') }}" data-ai-open data-ai-prefill="Ce poate face administratorul in MasterScule.ro?">AI Tools</a></div>
</section>
@endsection
