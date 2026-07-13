@extends('layouts.app')

@section('content')
@php($checkoutUser = auth()->user())
<section class="shell page-title"><p>{{ __('ui.cart') }} / {{ __('ui.checkout') }}</p><h1>{{ __('ui.checkout_title') }}</h1></section>
<section class="shell checkout-grid">
    <form method="post" action="{{ route('checkout.store') }}" class="checkout-form">
        @csrf
        <h2>{{ __('ui.contact_data') }}</h2>
        <label>{{ __('ui.name') }}<input name="customer_name" value="{{ old('customer_name', $checkoutUser?->name) }}" required></label>
        <label>{{ __('ui.email') }}<input type="email" name="customer_email" value="{{ old('customer_email', $checkoutUser?->email) }}" required></label>
        <label>{{ __('ui.phone') }}<input name="customer_phone" value="{{ old('customer_phone', $checkoutUser?->phone) }}" required></label>
        <label>{{ __('ui.company_name') }}<input name="company_name" value="{{ old('company_name', $checkoutUser?->company_name) }}"></label>
        <label>{{ __('ui.vat') }}<input name="vat_number" value="{{ old('vat_number', $checkoutUser?->vat_number) }}"></label>
        <h2>{{ __('ui.shipping_address') }}</h2>
        <label>{{ __('ui.city') }}<input name="shipping_city" value="{{ old('shipping_city', $checkoutUser?->city) }}" required></label>
        <label>{{ __('ui.address') }}<input name="shipping_address" value="{{ old('shipping_address') }}" required></label>
        <label>{{ __('ui.postcode') }}<input name="shipping_postcode" value="{{ old('shipping_postcode') }}"></label>
        <h2>{{ __('ui.shipping_payment') }}</h2>
        <label>{{ __('ui.shipping_method') }}<select name="shipping_method"><option value="courier">{{ __('ui.courier') }}</option><option value="pickup">{{ __('ui.pickup') }}</option><option value="individual">{{ __('ui.individual_delivery') }}</option></select></label>
        <label>{{ __('ui.payment_method') }}<select name="payment_method"><option value="cash_on_delivery">{{ __('ui.cash_on_delivery') }}</option><option value="bank_transfer">{{ __('ui.bank_transfer') }}</option><option value="online_card">{{ __('ui.online_card') }}</option></select></label>
        <label>{{ __('ui.comment') }}<textarea name="comment">{{ old('comment') }}</textarea></label>
        <label class="terms-check"><input type="checkbox" name="terms_accepted" value="1" required @checked(old('terms_accepted'))> <span>{{ __('ui.terms_accept') }}</span></label>
        <button class="btn orange-btn">{{ __('ui.send_order') }}</button>
    </form>
    <aside class="summary">
        <h2>{{ __('ui.order_check') }}</h2>
        @foreach($cart['items'] as $item)
            <p><span>{{ $item['product']->display_name }} × {{ $item['quantity'] }}</span><strong>{{ money($item['total']) }}</strong></p>
        @endforeach
        <hr>
        <p class="total"><span>{{ __('ui.total_to_pay') }}</span><strong>{{ money($cart['total']) }}</strong></p>
        <x-consultation-cta compact class="summary-consultation" />
    </aside>
</section>
@endsection
