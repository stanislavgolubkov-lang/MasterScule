@extends('layouts.admin')

@section('content')
<section class="shell page-title admin-dashboard-title">
    <p>{{ __('ui.admin') }}</p>
    <h1>{{ __('ui.admin_panel') }}</h1>
</section>

<section class="shell admin-dashboard">
    <section class="admin-dashboard-group">
        <div class="admin-dashboard-heading">
            <span>{{ __('ui.admin_overview') }}</span>
        </div>
        <div class="admin-kpi-grid admin-kpi-grid-primary">
            <a class="admin-kpi-card" href="{{ route('admin.products') }}"><span>{{ __('ui.products') }}</span><strong>{{ $productsCount }}</strong></a>
            <a class="admin-kpi-card" href="{{ route('admin.orders') }}"><span>{{ __('ui.orders') }}</span><strong>{{ $ordersCount }}</strong></a>
            <a class="admin-kpi-card" href="{{ route('brands') }}"><span>{{ __('ui.brands') }}</span><strong>{{ $brandsCount }}</strong></a>
            <a class="admin-kpi-card" href="{{ route('admin.users') }}"><span>{{ __('ui.users') }}</span><strong>{{ $usersCount }}</strong></a>
        </div>
    </section>

    <div class="admin-dashboard-split">
        <section class="admin-dashboard-group">
            <div class="admin-dashboard-heading"><span>{{ __('ui.admin_sales') }}</span></div>
            <div class="admin-kpi-grid admin-kpi-grid-compact">
                <div class="admin-kpi-card"><span>{{ __('ui.admin_orders_today') }}</span><strong>{{ $ordersToday }}</strong></div>
                <div class="admin-kpi-card"><span>{{ __('ui.admin_orders_week') }}</span><strong>{{ $ordersWeek }}</strong></div>
                <div class="admin-kpi-card admin-kpi-wide"><span>{{ __('ui.admin_orders_sum') }}</span><strong>{{ money($ordersTotal) }}</strong></div>
                <a class="admin-kpi-card admin-kpi-alert" href="{{ route('admin.payments') }}"><span>{{ __('ui.admin_pending_payments') }}</span><strong>{{ $pendingPayments }}</strong></a>
            </div>
        </section>

        <section class="admin-dashboard-group">
            <div class="admin-dashboard-heading"><span>{{ __('ui.admin_catalog_quality') }}</span></div>
            <div class="admin-kpi-grid admin-kpi-grid-compact">
                <a class="admin-kpi-card" href="{{ route('admin.parser.drafts') }}"><span>{{ __('ui.admin_new_drafts') }}</span><strong>{{ $draftProducts }}</strong></a>
                <a class="admin-kpi-card admin-kpi-warning" href="{{ route('admin.products', ['image_state' => 'missing']) }}"><span>{{ __('ui.admin_products_without_photo') }}</span><strong>{{ $productsWithoutPhoto }}</strong></a>
                <a class="admin-kpi-card" href="{{ route('admin.products') }}"><span>{{ __('ui.admin_products_without_category') }}</span><strong>{{ $productsWithoutCategory }}</strong></a>
                <a class="admin-kpi-card {{ $parserErrors ? 'admin-kpi-danger' : '' }}" href="{{ route('admin.parser.index') }}"><span>{{ __('ui.admin_parser_errors') }}</span><strong>{{ $parserErrors }}</strong></a>
            </div>
        </section>
    </div>

    <section class="admin-quick-actions">
        <span>{{ __('ui.admin_quick_actions') }}</span>
        <div>
            <a class="btn small" href="{{ route('admin.products') }}">{{ __('ui.products') }}</a>
            <a class="btn small outline" href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a>
            <a class="btn small outline" href="{{ route('admin.orders') }}">{{ __('ui.orders') }}</a>
            <a class="btn small outline" href="{{ route('admin.users') }}">{{ __('ui.users') }}</a>
        </div>
    </section>
</section>
@endsection
