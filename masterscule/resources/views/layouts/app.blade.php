<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title ?? 'MasterScule.ro - Scule si echipamente profesionale')</title>
    <link rel="icon" href="/favicon.ico?v=20260606" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png?v=20260606">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png?v=20260606">
    <link rel="icon" type="image/png" sizes="64x64" href="/favicon.png?v=20260606">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=20260606">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="topbar">
        <span>Livrare rapida in Romania</span>
        <span>Branduri oficiale si garantie</span>
        <span>Pentru service auto si profesionisti</span>
    </div>

    <header class="site-header">
        <div class="mobile-header">
            <button class="icon-btn" data-open="mobile-menu" aria-label="Meniu">&#9776;</button>
            <a href="{{ route('home') }}" class="mobile-logo"><img src="/images/brand/master-scule-logo.png" alt="MasterScule.ro"></a>
            <button class="icon-btn" data-open="search-overlay" aria-label="Cauta">&#128269;</button>
        </div>

        <div class="desktop-header shell">
            <a href="{{ route('home') }}" class="brand-mark">
                <img src="/images/brand/master-scule-logo.png" alt="MasterScule.ro">
                <span><strong>MasterScule.ro</strong><small>Scule si echipamente profesionale</small></span>
            </a>
            <form action="{{ route('catalog') }}" class="search-form">
                <input name="q" value="{{ request('q') }}" placeholder="Cauta dupa produs, categorie sau brand...">
                <button aria-label="Cauta">Search</button>
            </form>
            <div class="header-actions">
                <a href="tel:0724123456"><strong>0724 123 456</strong><small>Luni - Vineri 08:00 - 17:00</small></a>
                <a href="{{ route('wishlist') }}">Favorite</a>
                <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}">Cont</a>
                <a href="{{ route('cart.index') }}">Cos <b>{{ $cartCount }}</b></a>
            </div>
        </div>

        <nav class="main-nav">
            <div class="shell nav-inner">
                <a class="catalog-link" href="{{ route('catalog') }}" data-catalog-open>Catalog produse</a>
                <a href="{{ route('brands') }}">Branduri</a>
                <a href="{{ route('catalog', 'echipamente-service') }}">Pentru service</a>
                <a href="{{ route('catalog', 'seturi-de-scule') }}">Garaj</a>
                <a class="orange" href="{{ route('promotions') }}">Promotii</a>
                <a href="{{ route('new') }}">Noutati</a>
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
        <div><strong>Livrare in toata Romania</strong><span>Livrare rapida prin curier oriunde in tara</span></div>
        <div><strong>Consultanta de specialitate</strong><span>Echipa noastra te ajuta sa alegi cele mai bune solutii</span></div>
        <div><strong>Garantie si calitate</strong><span>Produse originale, garantie conform producatorului</span></div>
        <div><strong>Plata securizata</strong><span>Platesti in siguranta cu cardul sau ramburs la livrare</span></div>
    </section>

    <footer class="site-footer">
        <div class="shell footer-grid">
            <div><h4>Pentru clienti</h4><a href="#">Cum cumpar</a><a href="{{ route('page', 'delivery-payment') }}">Livrare si costuri</a><a href="{{ route('page', 'returns') }}">Retur si rambursare</a></div>
            <div><h4>Companie</h4><a href="{{ route('page', 'about') }}">Despre noi</a><a href="{{ route('page', 'terms') }}">Termeni si conditii</a><a href="{{ route('page', 'privacy-policy') }}">Politica de confidentialitate</a></div>
            <div><h4>Categorii</h4>@foreach($navCategories->take(5) as $category)<a href="{{ route('catalog', $category->slug) }}">{{ $category->name_ro }}</a>@endforeach</div>
            <div><h4>Contact</h4><span>0724 123 456</span><span>contact@masterscule.ro</span><span>Str. Fabricii nr. 12, Voluntari</span></div>
            <div><h4>Aboneaza-te la noutati</h4><form class="newsletter"><input placeholder="Adresa ta de email"><button>Aboneaza-te</button></form></div>
        </div>
        <div class="footer-bottom shell">&copy; 2026 MasterScule.ro - RON - VISA - Mastercard - Apple Pay - Google Pay</div>
    </footer>

    <div id="mobile-menu" class="drawer" hidden>
        <button data-close="mobile-menu" class="close-btn">x</button>
        <a href="{{ route('catalog') }}" data-catalog-open>Catalog produse</a>
        <a href="{{ route('brands') }}">Branduri</a>
        <a href="{{ route('promotions') }}">Promotii</a>
        <a href="{{ route('page', 'delivery-payment') }}">Livrare si plata</a>
        <a href="{{ route('page', 'warranty') }}">Garantie</a>
        <a href="{{ route('page', 'contacts') }}">Contact</a>
        <a href="{{ route('login') }}">Autentificare / Inregistrare</a>
    </div>

    <div id="search-overlay" class="search-overlay" hidden>
        <button data-close="search-overlay" class="close-btn">x</button>
        <form action="{{ route('catalog') }}" class="overlay-search">
            <label>Ce cauti?</label>
            <input name="q" placeholder="De exemplu: set de scule, pistol pneumatic, cric" autofocus>
            <button>Cauta</button>
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
            <button class="catalog-modal-close" type="button" data-catalog-close aria-label="Inchide">x</button>
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
            <button class="ai-modal-close" type="button" data-ai-close aria-label="Inchide">x</button>
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
                    <button type="button" data-ai-prompt="Explica livrarea, garantia si returul">Livrare si garantie</button>
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
        <a href="{{ route('home') }}">Acasa</a>
        <a href="{{ route('catalog') }}" data-catalog-open>Catalog</a>
        <a class="ai" href="{{ route('ai.advisor') }}" data-ai-open>AI</a>
        <a href="{{ route('cart.index') }}">Cos</a>
        <a href="{{ auth()->check() ? route('account.dashboard') : route('login') }}">Cont</a>
    </nav>
</body>
</html>
