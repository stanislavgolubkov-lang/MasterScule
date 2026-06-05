@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>Acasă / Contul meu</p><h1>Contul meu</h1><span>Bun venit, {{ $user->name }}</span></section>
<section class="shell account-layout">
    <aside class="account-nav">
        <a class="active" href="{{ route('account.dashboard') }}">Panou principal</a>
        <a href="#">Comenzile mele</a>
        <a href="#">Date personale</a>
        <a href="#">Adrese</a>
        <a href="{{ route('wishlist') }}">Favorite</a>
        <a href="{{ route('compare') }}">Comparate</a>
        @if($user->isAdmin())<a href="{{ route('admin.dashboard') }}">Admin</a>@endif
        <form method="post" action="{{ route('logout') }}">@csrf<button>Ieșire</button></form>
    </aside>
    <div class="account-main">
        <div class="stats">
            <div><strong>{{ $orders->where('status', 'new')->count() }}</strong><span>Comenzi active</span></div>
            <div><strong>{{ $orders->count() }}</strong><span>Comenzi totale</span></div>
            <div><strong>0</strong><span>Produse favorite</span></div>
            <div><strong>{{ $user->city ? 1 : 0 }}</strong><span>Adrese salvate</span></div>
        </div>
        <section class="panel"><h2>Comenzile recente</h2>
            <table><tr><th>Număr comandă</th><th>Data</th><th>Status</th><th>Total</th></tr>
                @forelse($orders as $order)<tr><td>{{ $order->order_number }}</td><td>{{ $order->created_at->format('d.m.Y') }}</td><td>{{ $order->status }}</td><td>{{ number_format((float) $order->total, 2, ',', '.') }} RON</td></tr>@empty<tr><td colspan="4">Nu ai comenzi încă.</td></tr>@endforelse
            </table>
        </section>
        <section class="panel"><h2>Date personale</h2><p>{{ $user->email }} · {{ $user->phone }} · {{ $user->company_name }}</p></section>
    </div>
</section>
@endsection
