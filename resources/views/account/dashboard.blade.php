@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>{{ __('ui.home') }} / {{ __('ui.account_title') }}</p><h1>{{ __('ui.account_title') }}</h1><span>{{ __('ui.welcome', ['name' => $user->name]) }}</span></section>
<section class="shell account-layout">
    <aside class="account-nav">
        <a class="active" href="{{ route('account.dashboard') }}">{{ __('ui.dashboard') }}</a>
        <a href="#">{{ __('ui.my_orders') }}</a>
        <a href="#">{{ __('ui.personal_data') }}</a>
        <a href="#">{{ __('ui.addresses') }}</a>
        @if(config('features.wishlist'))
            <a href="{{ route('wishlist') }}">{{ __('ui.favorites') }}</a>
        @endif
        @if(config('features.compare'))
            <a href="{{ route('compare') }}">{{ __('ui.compared') }}</a>
        @endif
        @if($user->isAdmin())<a href="{{ route('admin.dashboard') }}">{{ __('ui.admin') }}</a>@endif
        <form method="post" action="{{ route('logout') }}">@csrf<button>{{ __('ui.logout') }}</button></form>
    </aside>
    <div class="account-main">
        <div class="stats">
            <div><strong>{{ $orders->where('status', 'new')->count() }}</strong><span>{{ __('ui.active_orders') }}</span></div>
            <div><strong>{{ $orders->count() }}</strong><span>{{ __('ui.total_orders') }}</span></div>
            <div><strong>0</strong><span>{{ __('ui.favorite_products') }}</span></div>
            <div><strong>{{ $user->city ? 1 : 0 }}</strong><span>{{ __('ui.saved_addresses') }}</span></div>
        </div>
        <section class="panel"><h2>{{ __('ui.recent_orders') }}</h2>
            <table><tr><th>{{ __('ui.order_number') }}</th><th>{{ __('ui.date') }}</th><th>{{ __('ui.status') }}</th><th>Total</th></tr>
                @forelse($orders as $order)<tr><td>{{ $order->order_number }}</td><td>{{ $order->created_at->format('d.m.Y') }}</td><td>{{ $order->status }}</td><td>{{ money($order->total, $order->currency) }}</td></tr>@empty<tr><td colspan="4">{{ __('ui.no_orders') }}</td></tr>@endforelse
            </table>
        </section>
        <section class="panel"><h2>{{ __('ui.personal_data') }}</h2><p>{{ $user->email }} · {{ $user->phone }} · {{ $user->company_name }}</p></section>
    </div>
</section>
@endsection
