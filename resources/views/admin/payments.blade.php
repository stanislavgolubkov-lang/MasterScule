@extends('layouts.admin')

@section('content')
<section class="shell page-title">
    <p>{{ __('ui.admin') }} / {{ __('ui.admin_payments') }}</p>
    <h1>{{ __('ui.admin_payments') }}</h1>
    <span>{{ __('ui.admin_payments_text') }}</span>
</section>

<section class="shell panel">
    <div class="parser-table-wrap">
        <table class="parser-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Payment ID</th>
                    <th>Provider</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Status</th>
                    <th>Callback</th>
                    <th>Created</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>
                            @if($transaction->order)
                                <a href="{{ route('admin.orders') }}">{{ $transaction->order->order_number }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $transaction->provider_transaction_id ?: '-' }}</td>
                        <td>{{ $transaction->provider }}</td>
                        <td>{{ money($transaction->amount, $transaction->currency) }}</td>
                        <td>{{ $transaction->currency }}</td>
                        <td><span class="parser-status parser-status-{{ $transaction->status }}">{{ $transaction->status }}</span></td>
                        <td>{{ $transaction->callback_payload_json ? __('ui.yes') : __('ui.no') }}</td>
                        <td>{{ $transaction->created_at?->format('d.m.Y H:i') }}</td>
                        <td>{{ $transaction->updated_at?->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">{{ __('ui.admin_no_payments') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $transactions->links() }}
</section>
@endsection
