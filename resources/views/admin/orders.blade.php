@extends('layouts.app')

@section('content')
<section class="shell page-title">
    <p>{{ __('ui.admin') }} / {{ __('ui.orders') }}</p>
    <h1>{{ __('ui.admin_orders') }}</h1>
</section>

<section class="shell admin-order-list">
    @foreach($orders as $order)
        <article class="panel admin-order-card">
            <div class="admin-order-head">
                <div>
                    <strong>{{ $order->order_number }}</strong>
                    <span>{{ $order->created_at->format('d.m.Y H:i') }}</span>
                </div>
                <div>
                    <strong>{{ money($order->total, $order->currency) }}</strong>
                    <span>{{ $order->payment_method }}</span>
                </div>
            </div>

            <div class="admin-order-grid">
                <div>
                    <h3>{{ __('ui.client') }}</h3>
                    <p>{{ $order->customer_name }}</p>
                    <p>{{ $order->customer_email }}</p>
                    <p>{{ $order->customer_phone }}</p>
                    <p>{{ $order->shipping_city }}, {{ $order->shipping_address }}</p>
                </div>

                <div>
                    <h3>{{ __('ui.products') }}</h3>
                    @foreach($order->items as $item)
                        <p>{{ $item->product_name }} x {{ $item->quantity }}</p>
                    @endforeach
                </div>

                <div>
                    <h3>{{ __('ui.payment_status') }}</h3>
                    <p>{{ $order->payment_status }} / {{ $order->status }}</p>
                    @forelse($order->paymentTransactions as $transaction)
                        <p>
                            {{ $transaction->provider }}:
                            {{ $transaction->status }}
                            @if($transaction->provider_transaction_id)
                                <small>{{ $transaction->provider_transaction_id }}</small>
                            @endif
                        </p>
                    @empty
                        <p>-</p>
                    @endforelse
                </div>

                <form method="post" action="{{ route('admin.orders.update', $order) }}">
                    @csrf
                    @method('patch')
                    <label>{{ __('ui.status') }}
                        <select name="status">
                            @foreach($orderStatuses as $status)
                                <option value="{{ $status }}" @selected($order->status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>{{ __('ui.payment_status') }}
                        <select name="payment_status">
                            @foreach($paymentStatuses as $status)
                                <option value="{{ $status }}" @selected($order->payment_status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>{{ __('ui.admin_note') }}
                        <textarea name="admin_note">{{ old('admin_note', $order->admin_note) }}</textarea>
                    </label>
                    <button class="btn small">{{ __('ui.update_order') }}</button>
                </form>
            </div>
        </article>
    @endforeach

    {{ $orders->links() }}
</section>
@endsection
