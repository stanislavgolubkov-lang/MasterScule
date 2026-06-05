@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>Coș / Checkout</p><h1>Finalizează comanda</h1></section>
<section class="shell checkout-grid">
    <form method="post" action="{{ route('checkout.store') }}" class="checkout-form">
        @csrf
        <h2>1. Date de contact</h2>
        <label>Nume<input name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required></label>
        <label>Telefon<input name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone) }}" required></label>
        <label>Companie<input name="company_name" value="{{ old('company_name', auth()->user()->company_name) }}"></label>
        <label>CUI / TVA<input name="vat_number" value="{{ old('vat_number', auth()->user()->vat_number) }}"></label>
        <h2>2. Adresă de livrare</h2>
        <label>Oraș<input name="shipping_city" value="{{ old('shipping_city', auth()->user()->city) }}" required></label>
        <label>Adresă<input name="shipping_address" required></label>
        <label>Cod poștal<input name="shipping_postcode"></label>
        <h2>3. Livrare și plată</h2>
        <label>Metodă de livrare<select name="shipping_method"><option value="courier">Curier în România</option><option value="pickup">Ridicare personală</option><option value="individual">Livrare individuală pentru echipamente mari</option></select></label>
        <label>Metodă de plată<select name="payment_method"><option value="cash_on_delivery">Plată la livrare</option><option value="bank_transfer">Transfer bancar</option><option value="online_card">Plată online</option></select></label>
        <label>Comentariu<textarea name="comment"></textarea></label>
        <button class="btn orange-btn">Trimite comanda</button>
    </form>
    <aside class="summary">
        <h2>Verificare comandă</h2>
        @foreach($cart['items'] as $item)
            <p><span>{{ $item['product']->display_name }} × {{ $item['quantity'] }}</span><strong>{{ number_format($item['total'], 2, ',', '.') }} RON</strong></p>
        @endforeach
        <hr>
        <p class="total"><span>Total</span><strong>{{ number_format($cart['total'], 2, ',', '.') }} RON</strong></p>
    </aside>
</section>
@endsection
