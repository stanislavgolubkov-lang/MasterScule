@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>Acasă / Coș</p><h1>Coșul tău</h1><span>Ai {{ $cart['count'] }} produse în coș</span></section>
<section class="shell cart-layout">
    <div class="cart-list">
        @forelse($cart['items'] as $item)
            <div class="cart-item">
                <img src="{{ $item['product']->main_image }}" alt="{{ $item['product']->display_name }}">
                <div><h3>{{ $item['product']->display_name }}</h3><small>Cod produs: {{ $item['product']->sku }}</small><span class="stock">● În stoc</span></div>
                <strong>{{ number_format((float) $item['product']->price, 2, ',', '.') }} RON</strong>
                <form action="{{ route('cart.update', $item['product']) }}" method="post" class="qty">@csrf @method('PATCH')<input type="number" min="0" name="quantity" value="{{ $item['quantity'] }}"><button>OK</button></form>
                <strong>{{ number_format($item['total'], 2, ',', '.') }} RON</strong>
                <form action="{{ route('cart.remove', $item['product']) }}" method="post">@csrf @method('DELETE')<button class="delete">Șterge</button></form>
            </div>
        @empty
            <div class="empty">Coșul este gol.</div>
        @endforelse
        <form class="promo"><label>Cod promoțional</label><input placeholder="Introdu codul promoțional"><button class="btn small">Aplică</button></form>
    </div>
    <aside class="summary">
        <h2>Sumar comandă</h2>
        <p><span>Subtotal</span><strong>{{ number_format($cart['subtotal'], 2, ',', '.') }} RON</strong></p>
        <p><span>Reducere</span><strong>- {{ number_format($cart['discount'], 2, ',', '.') }} RON</strong></p>
        <p><span>Livrare</span><strong>Gratuit</strong></p>
        <hr>
        <p class="total"><span>Total de plată</span><strong>{{ number_format($cart['total'], 2, ',', '.') }} RON</strong></p>
        <a class="btn outline" href="{{ route('catalog') }}">Continuă cumpărăturile</a>
        <a class="btn orange-btn" href="{{ route('checkout.show') }}">Finalizează comanda</a>
    </aside>
</section>
<div class="sticky-checkout"><strong>Total: {{ number_format($cart['total'], 2, ',', '.') }} RON</strong><a class="btn small orange-btn" href="{{ route('checkout.show') }}">Finalizează comanda</a></div>
@endsection
