@extends('layouts.admin')

@section('content')
<section class="shell page-title"><p>{{ __('ui.admin') }}</p><h1>{{ __('ui.admin_panel') }}</h1></section>
<section class="shell account-main">
    <div class="stats">
        <div><strong>{{ $productsCount }}</strong><span>{{ __('ui.products') }}</span></div>
        <div><strong>{{ $ordersCount }}</strong><span>{{ __('ui.orders') }}</span></div>
        <div><strong>{{ $brandsCount }}</strong><span>{{ __('ui.brands') }}</span></div>
        <div><strong>{{ $usersCount }}</strong><span>{{ __('ui.users') }}</span></div>
        <div><strong>{{ $ordersToday }}</strong><span>{{ __('ui.admin_orders_today') }}</span></div>
        <div><strong>{{ $ordersWeek }}</strong><span>{{ __('ui.admin_orders_week') }}</span></div>
        <div><strong>{{ money($ordersTotal) }}</strong><span>{{ __('ui.admin_orders_sum') }}</span></div>
        <div><strong>{{ $draftProducts }}</strong><span>{{ __('ui.admin_new_drafts') }}</span></div>
        <div><strong>{{ $productsWithoutPhoto }}</strong><span>{{ __('ui.admin_products_without_photo') }}</span></div>
        <div><strong>{{ $productsWithoutCategory }}</strong><span>{{ __('ui.admin_products_without_category') }}</span></div>
        <div><strong>{{ $parserErrors }}</strong><span>{{ __('ui.admin_parser_errors') }}</span></div>
        <div><strong>{{ $pendingPayments }}</strong><span>{{ __('ui.admin_pending_payments') }}</span></div>
    </div>
    <div class="admin-actions">
        <a class="btn" href="{{ route('admin.products') }}">CRUD {{ __('ui.products') }}</a>
        <a class="btn outline" href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a>
        <a class="btn outline" href="{{ route('admin.orders') }}">{{ __('ui.orders') }}</a>
        <a class="btn outline" href="{{ route('admin.users') }}">{{ __('ui.users') }}</a>
    </div>
</section>
@endsection
