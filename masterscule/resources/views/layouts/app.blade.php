<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title ?? 'MasterScule.ro - Scule si echipamente profesionale')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="topbar">
        <span>Livrare rapidă în România</span>
        <span>Branduri oficiale și garanție</span>
        <span>Pentru service auto și profesioniști</span>
    </div>

    <header class="site-header">
        <div class="mobile-header">
            <button class="icon-btn" data-open="mobile-menu" aria-label="Meniu">☰</button>
            <a href="{{ route('home') }}" class="mobile-logo"><img src="/images/brand/master-scule-logo.png" alt="MasterScule.ro"></a>
            <button class="icon-btn" data-open="search-overlay" aria-label="Caută">⌕</button>
            <a class="icon-btn" href="{{ route('cart.index') }}" aria-label="Coș">🛒<span>{{ $cartCount }}</span></a>
        </div>

        <div class="desktop-header shell">
            <a href="{{ route('home') }}" class="brand-mark">
                <img src="/images/brand/master-scule-logo.png" alt="MasterScule.ro">
                <span><strong>MasterScule.ro</strong><small>Scule și echipamente profesionale</small></span>
            </a>
            <form action="{{ route('catalog') }}" class="search-form">
                <input name="q" value="{{ request('q') }}" placeholder="Caută după produs, categorie sau brand...">
                <button aria-label="Caută">⌕</button>
            </form>
            <div class="header-actions">
                <a href="tel:0724123456"><strong>0724 123 456</strong><small>Luni - Vineri 08:00 - 17:00</small></a>
                <a href="{{ route('wishlist') }}">♡ Favorite</a>
                <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}">Cont</a>
                <a href="{{ route('cart.index') }}">Coș <b>{{ $cartCount }}</b></a>
            </div>
        </div>

        <nav class="main-nav">
            <div class="shell nav-inner">
                <a class="catalog-link" href="{{ route('catalog') }}" data-catalog-open>☰ Catalog produse</a>
                <a href="{{ route('brands') }}">Branduri</a>
                <a href="{{ route('catalog', 'echipamente-service') }}">Pentru service</a>
                <a href="{{ route('catalog', 'seturi-de-scule') }}">Garaj</a>
                <a class="orange" href="{{ route('promotions') }}">Promoții</a>
                <a href="{{ route('new') }}">Noutăți</a>
                <a href="{{ route('page', 'contacts') }}">Contact</a>
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
        <div><strong>Livrare în toată România</strong><span>Livrare rapidă prin curier oriunde în țară</span></div>
        <div><strong>Consultanță de specialitate</strong><span>Echipa noastră te ajută să alegi cele mai bune soluții</span></div>
        <div><strong>Garanție și calitate</strong><span>Produse originale, garanție conform producătorului</span></div>
        <div><strong>Plată securizată</strong><span>Plătești în siguranță cu cardul sau ramburs la livrare</span></div>
    </section>

    <footer class="site-footer">
        <div class="shell footer-grid">
            <div><h4>Pentru clienți</h4><a href="#">Cum cumpăr</a><a href="{{ route('page', 'delivery-payment') }}">Livrare și costuri</a><a href="{{ route('page', 'returns') }}">Retur și rambursare</a></div>
            <div><h4>Companie</h4><a href="{{ route('page', 'about') }}">Despre noi</a><a href="{{ route('page', 'terms') }}">Termeni și condiții</a><a href="{{ route('page', 'privacy-policy') }}">Politica de confidențialitate</a></div>
            <div><h4>Categorii</h4>@foreach($navCategories->take(5) as $category)<a href="{{ route('catalog', $category->slug) }}">{{ $category->name_ro }}</a>@endforeach</div>
            <div><h4>Contact</h4><span>0724 123 456</span><span>contact@masterscule.ro</span><span>Str. Fabricii nr. 12, Voluntari</span></div>
            <div><h4>Abonează-te la noutăți</h4><form class="newsletter"><input placeholder="Adresa ta de email"><button>Abonează-te</button></form></div>
        </div>
        <div class="footer-bottom shell">© 2026 MasterScule.ro · RON · VISA · Mastercard · Apple Pay · Google Pay</div>
    </footer>

    <div id="mobile-menu" class="drawer" hidden>
        <button data-close="mobile-menu" class="close-btn">×</button>
        <a href="{{ route('catalog') }}" data-catalog-open>Catalog produse</a>
        <a href="{{ route('brands') }}">Branduri</a>
        <a href="{{ route('promotions') }}">Promoții</a>
        <a href="{{ route('page', 'delivery-payment') }}">Livrare și plată</a>
        <a href="{{ route('page', 'warranty') }}">Garanție</a>
        <a href="{{ route('page', 'contacts') }}">Contact</a>
        <a href="{{ route('login') }}">Autentificare / Înregistrare</a>
    </div>

    <div id="search-overlay" class="search-overlay" hidden>
        <button data-close="search-overlay" class="close-btn">×</button>
        <form action="{{ route('catalog') }}" class="overlay-search">
            <label>Ce cauți?</label>
            <input name="q" placeholder="De exemplu: set de scule, pistol pneumatic, cric" autofocus>
            <button>Caută</button>
        </form>
        <div class="quick-chips">
            @foreach($navCategories->take(6) as $category)
                <a href="{{ route('catalog', $category->slug) }}">{{ $category->name_ro }}</a>
            @endforeach
            <a href="{{ route('ai.advisor') }}" data-ai-open>AI: descrie lucrarea</a>
        </div>
    </div>

    <div id="catalog-modal" class="catalog-modal" hidden>
        <div class="catalog-modal-backdrop" data-catalog-close></div>
        <section class="catalog-modal-panel" role="dialog" aria-modal="true" aria-labelledby="catalog-modal-title">
            <button class="catalog-modal-close" type="button" data-catalog-close aria-label="Inchide">×</button>
            <span class="catalog-kicker">Catalog MasterScule</span>
            <h2 id="catalog-modal-title">Alege categoria</h2>
            <p>Selecteaza o categorie si mergi direct la produsele potrivite.</p>
            <div class="catalog-modal-grid">
                <a class="catalog-modal-all" href="{{ route('catalog') }}">
                    <span>Toate</span>
                    <strong>Toate produsele</strong>
                    <small>Vezi catalogul complet</small>
                </a>
                @foreach($navCategories as $category)
                    <a href="{{ route('catalog', $category->slug) }}">
                        <span class="catalog-modal-icon">{{ mb_substr($category->name_ro, 0, 1) }}</span>
                        <strong>{{ $category->name_ro }}</strong>
                        <small>Categoria atelierului</small>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    <div id="ai-modal" class="ai-modal" hidden>
        <div class="ai-modal-backdrop" data-ai-close></div>
        <section class="ai-modal-panel" role="dialog" aria-modal="true" aria-labelledby="ai-modal-title">
            <button class="ai-modal-close" type="button" data-ai-close aria-label="Inchide">×</button>
            <span class="ai-panel-kicker">AI MasterScule</span>
            <h2 id="ai-modal-title">Consultant AI</h2>
            <p>Scrie ce cauti, ce buget ai sau ce vrei sa faci pe site. Raspunsul ramane in aceasta fereastra.</p>
            <form class="ai-modal-form" action="{{ route('ai.ask') }}" method="post">
                @csrf
                <textarea name="prompt" required placeholder="Ex: Am nevoie de un set King Tony pana la 2500 RON"></textarea>
                <div class="ai-prompts">
                    <button type="button" data-ai-prompt="Am nevoie de un set King Tony pentru garaj pana la 2500 RON">Set pentru garaj</button>
                    <button type="button" data-ai-prompt="Am nevoie de un pistol pneumatic M7 pentru service auto">Pneumatic M7</button>
                    <button type="button" data-ai-prompt="Cum adaug un produs in cos si finalizez comanda?">Finalizare comanda</button>
                </div>
                <button class="btn" type="submit">Intreaba AI</button>
            </form>
            <div class="ai-modal-state" hidden></div>
            <pre class="ai-response ai-modal-response" hidden></pre>
            <div class="ai-modal-products"></div>
        </section>
    </div>

    <a class="floating-ai" href="{{ route('ai.advisor') }}" aria-label="Consultant AI" data-ai-open>
        <span class="floating-ai-orb">AI</span>
        <span class="floating-ai-text"><strong>AI consultant</strong><small>Te ajut sa alegi scula</small></span>
    </a>

    <nav class="bottom-nav">
        <a href="{{ route('home') }}">Acasă</a>
        <a href="{{ route('catalog') }}" data-catalog-open>Catalog</a>
        <a class="ai" href="{{ route('ai.advisor') }}" data-ai-open>AI</a>
        <a href="{{ route('cart.index') }}">Coș</a>
        <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}">Cont</a>
    </nav>
</body>
</html>
