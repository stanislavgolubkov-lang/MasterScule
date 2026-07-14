@extends('layouts.admin')

@section('content')
<section class="shell page-title admin-dashboard-title admin-page-head">
    <div>
        <p>{{ __('ui.admin') }}</p>
        <h1>{{ app()->isLocale('ru') ? 'Рабочий стол' : 'Panou de lucru' }}</h1>
        <span>{{ app()->isLocale('ru') ? 'Главное состояние магазина и быстрый доступ к ежедневным задачам.' : 'Starea magazinului si acces rapid la sarcinile zilnice.' }}</span>
    </div>
    <a class="btn small" href="{{ route('admin.products') }}">{{ app()->isLocale('ru') ? 'Открыть товары' : 'Deschide produse' }}</a>
</section>

<section class="shell admin-home">
    <section class="admin-home-metrics">
        <a class="admin-metric" href="{{ route('admin.orders') }}"><span>{{ app()->isLocale('ru') ? 'Заказы сегодня' : 'Comenzi azi' }}</span><strong>{{ $ordersToday }}</strong></a>
        <a class="admin-metric" href="{{ route('admin.orders') }}"><span>{{ app()->isLocale('ru') ? 'Заказы за 7 дней' : 'Comenzi 7 zile' }}</span><strong>{{ $ordersWeek }}</strong></a>
        <a class="admin-metric" href="{{ route('admin.products') }}"><span>{{ __('ui.products') }}</span><strong>{{ $productsCount }}</strong></a>
        <a class="admin-metric" href="{{ route('admin.users') }}"><span>{{ __('ui.users') }}</span><strong>{{ $usersCount }}</strong></a>
    </section>

    <section class="admin-attention-grid">
        <a class="admin-attention-card {{ $pendingPayments ? 'is-warning' : '' }}" href="{{ route('admin.payments') }}">
            <span>{{ __('ui.admin_pending_payments') }}</span>
            <strong>{{ $pendingPayments }}</strong>
            <small>{{ app()->isLocale('ru') ? 'Платежи, которые нужно проверить' : 'Plati de verificat' }}</small>
        </a>
        <a class="admin-attention-card {{ $productsWithoutPhoto ? 'is-warning' : '' }}" href="{{ route('admin.products', ['image_state' => 'missing']) }}">
            <span>{{ __('ui.admin_products_without_photo') }}</span>
            <strong>{{ $productsWithoutPhoto }}</strong>
            <small>{{ app()->isLocale('ru') ? 'Карточки без нормального изображения' : 'Produse fara imagine buna' }}</small>
        </a>
        <a class="admin-attention-card {{ $draftProducts ? 'is-info' : '' }}" href="{{ route('admin.parser.drafts') }}">
            <span>{{ __('ui.admin_new_drafts') }}</span>
            <strong>{{ $draftProducts }}</strong>
            <small>{{ app()->isLocale('ru') ? 'Черновики после импорта' : 'Drafturi dupa import' }}</small>
        </a>
        <a class="admin-attention-card {{ $parserErrors ? 'is-danger' : '' }}" href="{{ route('admin.parser.index') }}">
            <span>{{ __('ui.admin_parser_errors') }}</span>
            <strong>{{ $parserErrors }}</strong>
            <small>{{ app()->isLocale('ru') ? 'Ошибки, к которым вернемся в парсере' : 'Erori pentru etapa parserului' }}</small>
        </a>
    </section>

    <section class="admin-home-columns">
        <article class="panel admin-simple-panel">
            <div class="admin-panel-head">
                <span>{{ __('ui.orders') }}</span>
                <h2>{{ app()->isLocale('ru') ? 'Последние заказы' : 'Ultimele comenzi' }}</h2>
            </div>
            <div class="admin-compact-list">
                @forelse($orders as $order)
                    <a href="{{ route('admin.orders') }}">
                        <strong>{{ $order->order_number }}</strong>
                        <span>{{ $order->customer_name }} · {{ money($order->total, $order->currency) }} · {{ $order->status }}</span>
                    </a>
                @empty
                    <span>{{ app()->isLocale('ru') ? 'Заказов пока нет.' : 'Nu sunt comenzi.' }}</span>
                @endforelse
            </div>
        </article>

        <article class="panel admin-simple-panel">
            <div class="admin-panel-head">
                <span>{{ __('ui.admin_quick_actions') }}</span>
                <h2>{{ app()->isLocale('ru') ? 'Частые действия' : 'Actiuni rapide' }}</h2>
            </div>
            <div class="admin-action-list">
                <a class="btn small" href="{{ route('admin.products') }}">{{ app()->isLocale('ru') ? 'Найти или изменить товар' : 'Cauta sau editeaza produs' }}</a>
                <a class="btn small outline" href="{{ route('admin.orders') }}">{{ app()->isLocale('ru') ? 'Обработать заказы' : 'Proceseaza comenzi' }}</a>
                <a class="btn small outline" href="{{ route('admin.parser.index') }}">{{ app()->isLocale('ru') ? 'Перейти к парсеру' : 'Deschide parserul' }}</a>
            </div>
        </article>
    </section>
</section>
@endsection
