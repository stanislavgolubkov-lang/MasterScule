@extends('layouts.app')

@section('title', __('ui.order_created_title').' | '.config('store.domain_label'))

@section('content')
<section class="shell page-title">
    <p>{{ __('ui.checkout_title') }}</p>
    <h1>{{ __('ui.order_created_title') }}</h1>
    <span>{{ __('ui.order_created_text') }}</span>
</section>

<section class="shell checkout-grid">
    <div class="panel order-confirmation">
        <h2>{{ __('ui.order_details') }}</h2>
        <p><span>{{ __('ui.order_number') }}</span><strong>{{ $order->order_number }}</strong></p>
        <p><span>{{ __('ui.status') }}</span><strong>{{ $order->status }}</strong></p>
        <p><span>{{ __('ui.payment_status') }}</span><strong>{{ $order->payment_status }}</strong></p>
        @if($order->payment_method === 'online_card' && $order->payment_status === 'pending')
            <p class="muted">{{ __('ui.maib_pending') }}</p>
        @endif
        <p class="total"><span>{{ __('ui.total_to_pay') }}</span><strong>{{ money($order->total, $order->currency) }}</strong></p>
        <div class="actions">
            <a class="btn" href="{{ route('catalog') }}">{{ __('ui.continue_shopping') }}</a>
            @auth
                <a class="btn outline" href="{{ route('account.dashboard') }}">{{ __('ui.account_title') }}</a>
            @endauth
        </div>
    </div>

    <aside class="summary">
        <h2>{{ __('ui.order_check') }}</h2>
        @foreach($order->items as $item)
            <p><span>{{ $item->product_name }} x {{ $item->quantity }}</span><strong>{{ money($item->total, $order->currency) }}</strong></p>
        @endforeach
        <hr>
        <p class="total"><span>{{ __('ui.total_to_pay') }}</span><strong>{{ money($order->total, $order->currency) }}</strong></p>
    </aside>
</section>
@endsection
