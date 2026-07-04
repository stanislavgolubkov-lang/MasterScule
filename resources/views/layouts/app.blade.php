<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title ?? __('ui.site_title'))</title>
    <link rel="icon" href="/favicon.ico?v=20260606" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png?v=20260606">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png?v=20260606">
    <link rel="icon" type="image/png" sizes="64x64" href="/favicon.png?v=20260606">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=20260606">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @php
        $aiEnabled = (bool) config('features.ai_assistant');
        $wishlistEnabled = (bool) config('features.wishlist');
        $newsletterEnabled = (bool) config('features.newsletter');
    @endphp
    <div class="topbar">
        <span>{{ __('ui.topbar_delivery') }}</span>
        <span>{{ __('ui.topbar_brands') }}</span>
        <span>{{ __('ui.topbar_service') }}</span>
    </div>

    <header class="site-header">
        <div class="mobile-header">
            <button class="icon-btn" data-open="mobile-menu" aria-label="{{ __('ui.menu') }}" aria-controls="mobile-menu" aria-expanded="false">&#9776;</button>
            <a href="{{ route('home') }}" class="mobile-logo"><img src="/images/brand/master-scule-logo.png" alt="{{ config('store.domain_label') }}"></a>
            <button class="icon-btn" data-open="search-overlay" aria-label="{{ __('ui.search_button') }}">&#128269;</button>
        </div>

        <div class="desktop-header shell">
            <a href="{{ route('home') }}" class="brand-mark">
                <img src="/images/brand/master-scule-logo.png" alt="{{ config('store.domain_label') }}">
                <span><strong>{{ config('store.domain_label') }}</strong><small>{{ __('ui.tagline') }}</small></span>
            </a>
            <form action="{{ route('catalog') }}" class="search-form">
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('ui.search_placeholder') }}">
                <button aria-label="{{ __('ui.search_button') }}">{{ __('ui.search_button') }}</button>
            </form>
            <div class="header-actions">
                <a href="tel:{{ config('store.phone_href') }}"><strong>{{ config('store.phone') }}</strong><small>{{ config('store.working_hours.'.app()->getLocale()) }}</small></a>
                @if($wishlistEnabled)
                    <a href="{{ route('wishlist') }}">{{ __('ui.favorites') }}</a>
                @endif
                <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}">{{ __('ui.account') }}</a>
                <a href="{{ route('cart.index') }}">{{ __('ui.cart') }} <b>{{ $cartCount }}</b></a>
                <div class="language-switch" aria-label="{{ __('ui.language') }}">
                    <a class="{{ app()->isLocale('ru') ? 'active' : '' }}" href="{{ route('language.switch', 'ru') }}">{{ __('ui.ru') }}</a>
                    <a class="{{ app()->isLocale('ro') ? 'active' : '' }}" href="{{ route('language.switch', 'ro') }}">{{ __('ui.ro') }}</a>
                </div>
            </div>
        </div>

        <nav class="main-nav">
            <div class="shell nav-inner">
                <a class="catalog-link" href="{{ route('catalog') }}" data-catalog-open aria-expanded="false" aria-controls="catalog-modal">{{ __('ui.catalog_products') }}</a>
                <a href="{{ route('brands') }}">{{ __('ui.brands') }}</a>
                <a href="{{ route('catalog', 'echipamente-pentru-service') }}">{{ __('ui.for_service') }}</a>
                <a href="{{ route('catalog', 'instrument-manual') }}">{{ __('ui.garage') }}</a>
                <a class="orange" href="{{ route('promotions') }}">{{ __('ui.promotions') }}</a>
                <a href="{{ route('new') }}">{{ __('ui.new_items') }}</a>
                <a href="{{ route('page', 'contacts') }}">{{ __('ui.contact') }}</a>
            </div>
        </nav>
    </header>

    @if (session('success'))
        <div class="notice shell">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice error shell">{{ $errors->first() }}</div>
    @endif

    <main>
        @yield('content')
    </main>

    <section class="trust shell">
        <div><strong>{{ __('ui.trust_delivery_title') }}</strong><span>{{ __('ui.trust_delivery_text') }}</span></div>
        <div><strong>{{ __('ui.trust_consult_title') }}</strong><span>{{ __('ui.trust_consult_text') }}</span></div>
        <div><strong>{{ __('ui.trust_warranty_title') }}</strong><span>{{ __('ui.trust_warranty_text') }}</span></div>
        <div><strong>{{ __('ui.trust_payment_title') }}</strong><span>{{ __('ui.trust_payment_text') }}</span></div>
    </section>

    <footer class="site-footer">
        <div class="shell footer-grid">
            <div><h4>{{ __('ui.footer_clients') }}</h4><a href="#">{{ __('ui.how_to_buy') }}</a><a href="{{ route('page', 'delivery-payment') }}">{{ __('ui.delivery_payment') }}</a><a href="{{ route('page', 'returns') }}">{{ __('ui.returns') }}</a></div>
            <div><h4>{{ __('ui.company') }}</h4><a href="{{ route('page', 'about') }}">{{ __('ui.about_us') }}</a><a href="{{ route('page', 'terms') }}">{{ __('ui.terms') }}</a><a href="{{ route('page', 'privacy-policy') }}">{{ __('ui.privacy') }}</a></div>
            <div><h4>{{ __('ui.categories') }}</h4>@foreach($navCategories->take(5) as $category)<a href="{{ route('catalog', $category->slug) }}">{{ $category->display_name }}</a>@endforeach</div>
            <div><h4>{{ __('ui.contact') }}</h4><span>{{ config('store.phone') }}</span><span>{{ config('store.email') }}</span><span>{{ config('store.address') }}</span></div>
            @if($newsletterEnabled)
                <div><h4>{{ __('ui.newsletter') }}</h4><p>{{ __('ui.newsletter_text') }}</p><form class="newsletter"><input placeholder="{{ __('ui.email_placeholder') }}"><button>{{ __('ui.subscribe') }}</button></form></div>
            @else
                <div><h4>{{ __('ui.contact') }}</h4><span>{{ config('store.working_hours.'.app()->getLocale()) }}</span><span>{{ config('store.legal_name') }}</span><span>{{ config('store.country') }}</span></div>
            @endif
        </div>
        <div class="footer-bottom shell">{{ __('ui.copyright') }}</div>
    </footer>

    <x-mobile-catalog-drawer :categories="$navCategories" :cart-count="$cartCount" />

    <div id="search-overlay" class="search-overlay" hidden>
        <button data-close="search-overlay" class="close-btn">{{ __('ui.close') }}</button>
        <form action="{{ route('catalog') }}" class="overlay-search">
            <label>{{ __('ui.search_question') }}</label>
            <input name="q" placeholder="{{ __('ui.search_example') }}" autofocus>
            <button>{{ __('ui.search_button') }}</button>
        </form>
        <div class="quick-chips">
            @foreach($navCategories->take(6) as $category)
                <a href="{{ route('catalog', $category->slug) }}">{{ $category->display_name }}</a>
            @endforeach
            @if($aiEnabled)
                <a href="{{ route('ai.advisor') }}" data-ai-open>{{ __('ui.ai_describe_work') }}</a>
            @endif
        </div>
    </div>

    <x-catalog-mega-menu :categories="$navCategories" />

    @if($aiEnabled)
    <div id="ai-modal" class="ai-modal" hidden>
        <div class="ai-modal-backdrop" data-ai-close></div>
        <section class="ai-modal-panel" role="dialog" aria-modal="true" aria-labelledby="ai-modal-title">
            <button class="ai-modal-close" type="button" data-ai-close aria-label="{{ __('ui.close') }}">x</button>
            <span class="ai-panel-kicker">{{ __('ui.ai_kicker') }}</span>
            <h2 id="ai-modal-title">{{ __('ui.ai_title') }}</h2>
            <p>{{ __('ui.ai_intro') }}</p>
            <form class="ai-modal-form" action="{{ route('ai.ask') }}" method="post">
                @csrf
                <textarea name="prompt" required placeholder="{{ __('ui.ai_placeholder') }}"></textarea>
                <div class="ai-prompts">
                    <button type="button" data-ai-prompt="{{ __('ui.ai_placeholder') }}">{{ __('ui.ai_prompt_garage') }}</button>
                    <button type="button" data-ai-prompt="{{ app()->isLocale('ru') ? 'Нужен пневмоинструмент M7 для автосервиса' : 'Am nevoie de un pistol pneumatic M7 pentru service auto' }}">{{ __('ui.ai_prompt_m7') }}</button>
                    <button type="button" data-ai-prompt="{{ app()->isLocale('ru') ? 'Как добавить товар в корзину и оформить заказ?' : 'Cum adaug un produs in cos si finalizez comanda?' }}">{{ __('ui.ai_prompt_checkout') }}</button>
                    <button type="button" data-ai-prompt="{{ app()->isLocale('ru') ? 'Объясни доставку, гарантию и возврат' : 'Explica livrarea, garantia si returul' }}">{{ __('ui.ai_prompt_delivery') }}</button>
                </div>
                <button class="btn" type="submit">{{ __('ui.ask_ai') }}</button>
            </form>
            <div class="ai-modal-state" hidden></div>
            <pre class="ai-response ai-modal-response" hidden></pre>
            <div class="ai-modal-products"></div>
        </section>
    </div>

    <a class="floating-ai" href="{{ route('ai.advisor') }}" aria-label="{{ __('ui.ai_title') }}" data-ai-open>
        <span class="floating-ai-orb">AI</span>
        <span class="floating-ai-text"><strong>{{ __('ui.ai_float_title') }}</strong><small>{{ __('ui.ai_float_text') }}</small></span>
    </a>
    @endif

    <nav class="bottom-nav {{ $aiEnabled ? 'has-ai' : '' }}">
        <a href="{{ route('home') }}">{{ __('ui.bottom_home') }}</a>
        <a href="{{ route('catalog') }}" data-catalog-open aria-expanded="false" aria-controls="catalog-modal">{{ __('ui.bottom_catalog') }}</a>
        @if($aiEnabled)
            <a class="ai" href="{{ route('ai.advisor') }}" data-ai-open>{{ __('ui.bottom_ai') }}</a>
        @endif
        <a href="{{ route('cart.index') }}">{{ __('ui.bottom_cart') }}</a>
        <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}">{{ __('ui.bottom_account') }}</a>
    </nav>
</body>
</html>
