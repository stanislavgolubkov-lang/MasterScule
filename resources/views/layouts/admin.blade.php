<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('ui.admin_panel').' | '.config('store.domain_label'))</title>
    <link rel="icon" href="/favicon.ico?v=20260606" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png?v=20260606">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
    @php
        $adminNav = [
            ['label' => __('ui.dashboard'), 'route' => 'admin.dashboard'],
            ['label' => __('ui.products'), 'route' => 'admin.products'],
            ['label' => __('ui.orders'), 'route' => 'admin.orders'],
            ['label' => __('ui.admin_payments'), 'route' => 'admin.payments'],
            ['label' => __('ui.users'), 'route' => 'admin.users'],
            ['label' => __('ui.parser_products'), 'route' => 'admin.parser.index'],
            ['label' => __('ui.parser_drafts'), 'route' => 'admin.parser.drafts'],
            ['label' => __('ui.parser_category_rules'), 'route' => 'admin.parser.rules'],
        ];
    @endphp

    <div class="admin-shell">
        <aside class="admin-sidebar" aria-label="{{ __('ui.admin_panel') }}">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                <img src="/images/brand/master-scule-logo.png" alt="{{ config('store.domain_label') }}">
                <span><strong>{{ config('store.domain_label') }}</strong><small>{{ __('ui.admin_panel') }}</small></span>
            </a>

            <nav class="admin-nav">
                @foreach($adminNav as $item)
                    <a class="{{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <a class="admin-store-link" href="{{ route('home') }}">{{ __('ui.back_to_home') }}</a>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <strong>{{ __('ui.admin_panel') }}</strong>
                    <span>{{ auth()->user()?->email }}</span>
                </div>
                <div class="admin-topbar-actions">
                    <div class="language-switch" aria-label="{{ __('ui.language') }}">
                        <a class="{{ app()->isLocale('ru') ? 'active' : '' }}" href="{{ route('language.switch', 'ru') }}">{{ __('ui.ru') }}</a>
                        <a class="{{ app()->isLocale('ro') ? 'active' : '' }}" href="{{ route('language.switch', 'ro') }}">{{ __('ui.ro') }}</a>
                    </div>
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">{{ __('ui.logout') }}</button>
                    </form>
                </div>
            </header>

            @if (session('success'))
                <div class="notice">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="notice error">{{ $errors->first() }}</div>
            @endif

            <main class="admin-content">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
