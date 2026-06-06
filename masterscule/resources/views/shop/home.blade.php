@extends('layouts.app')

@section('title', 'MasterScule.ro - Scule si echipamente profesionale')

@section('content')
<section class="hero hero-premium" data-hero-slider>
    <div class="hero-slide is-active hero-slide-service" data-hero-slide>
        <div class="hero-backdrop" aria-hidden="true"></div>
        <div class="hero-grid">
            <div class="hero-copy">
                <span class="hero-kicker">MasterScule.ro - atelier complet</span>
                <h1>Scule profesionale pentru service si garaj</h1>
                <p>Alege truse, pneumatice, chei dinamometrice si echipamente de atelier pentru lucrari precise, rapide si sigure.</p>
                <div class="actions"><a class="btn" href="{{ route('catalog') }}" data-catalog-open>Vezi catalogul</a><a class="btn orange-btn" href="{{ route('promotions') }}">Promotii</a></div>
                <div class="hero-stats">
                    <span><strong>{{ $productsCount }}+</strong> produse in catalog</span>
                    <span><strong>King Tony</strong> truse si chei</span>
                    <span><strong>M7</strong> pneumatice</span>
                </div>
            </div>
            <div class="hero-showcase" aria-hidden="true">
                <div class="hero-product-stage">
                    <span class="hero-product-badge">Service</span>
                    <img src="/images/products/king-tony-7596mr.jpg" alt="">
                </div>
                <div class="hero-product-row">
                    <div class="hero-product-mini"><img src="/images/products/m7-nc-4255q.jpg" alt=""></div>
                    <div class="hero-product-mini"><img src="/images/products/king-tony-34262-1dg.jpg" alt=""></div>
                </div>
                <div class="hero-panel">
                    <span class="hero-panel-label">TOP atelier</span>
                    <h2>Seturi, pneumatice si scule de precizie</h2>
                    <p>Catalog pregatit pentru service-uri auto, garaje si clienti B2B.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-slide hero-slide-king" data-hero-slide>
        <div class="hero-backdrop" aria-hidden="true"></div>
        <div class="hero-grid">
            <div class="hero-copy">
                <span class="hero-kicker">King Tony - truse pentru atelier</span>
                <h1>Seturi si tubulare pentru lucrari mecanice</h1>
                <p>Truse, chei, tubulare si accesorii King Tony pentru service-uri care lucreaza zilnic cu piese auto.</p>
                <div class="actions"><a class="btn" href="{{ route('brand.show', 'king-tony') }}">Vezi King Tony</a><a class="btn outline" href="{{ route('catalog', 'seturi-de-scule') }}">Seturi de scule</a></div>
                <div class="hero-stats">
                    <span><strong>200</strong> articole King Tony</span>
                    <span><strong>24 luni</strong> garantie</span>
                    <span><strong>RON</strong> preturi clare</span>
                </div>
            </div>
            <div class="hero-showcase" aria-hidden="true">
                <div class="hero-product-stage">
                    <span class="hero-product-badge">King Tony</span>
                    <img src="/images/products/king-tony-7596mr.jpg" alt="">
                </div>
                <div class="hero-product-row">
                    <div class="hero-product-mini"><img src="/images/products/king-tony-11311mq02.jpg" alt=""></div>
                    <div class="hero-product-mini"><img src="/images/products/king-tony-7k09mp.jpg" alt=""></div>
                </div>
                <div class="hero-panel">
                    <span class="hero-panel-label">King Tony</span>
                    <h2>Truse complete pentru service</h2>
                    <p>Produse potrivite pentru mecanica generala, garaj si ateliere profesionale.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-slide hero-slide-m7" data-hero-slide>
        <div class="hero-backdrop" aria-hidden="true"></div>
        <div class="hero-grid">
            <div class="hero-copy">
                <span class="hero-kicker">M7 - pneumatice profesionale</span>
                <h1>Pistoale pneumatice si scule cu aer comprimat</h1>
                <p>Alege M7 pentru vulcanizare, service auto si lucrari rapide unde conteaza cuplul si fiabilitatea.</p>
                <div class="actions"><a class="btn" href="{{ route('brand.show', 'm7-mighty-seven') }}">Vezi M7</a><a class="btn outline" href="{{ route('catalog', 'scule-pneumatice') }}">Scule pneumatice</a></div>
                <div class="hero-stats">
                    <span><strong>100</strong> articole M7</span>
                    <span><strong>Service</strong> utilizare intensa</span>
                    <span><strong>Stoc</strong> produse demo</span>
                </div>
            </div>
            <div class="hero-showcase" aria-hidden="true">
                <div class="hero-product-stage hero-product-stage-orange">
                    <span class="hero-product-badge">M7</span>
                    <img src="/images/products/m7-nc-4255q.jpg" alt="">
                </div>
                <div class="hero-product-row">
                    <div class="hero-product-mini"><img src="/images/products/m7-dw-406.jpg" alt=""></div>
                    <div class="hero-product-mini"><img src="/images/products/m7-qt-102.jpg" alt=""></div>
                </div>
                <div class="hero-panel">
                    <span class="hero-panel-label">M7 pneumatice</span>
                    <h2>Scule rapide pentru lucrari grele</h2>
                    <p>Pistoale de impact, accesorii si echipamente pentru aer comprimat.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-slide hero-slide-equipment" data-hero-slide>
        <div class="hero-backdrop" aria-hidden="true"></div>
        <div class="hero-grid">
            <div class="hero-copy">
                <span class="hero-kicker">Echipamente service - organizare si control</span>
                <h1>Diagnostic, organizare si intretinere in atelier</h1>
                <p>Completeaza zona de lucru cu scule pentru diagnoza, dulapuri mobile si accesorii utile pentru service.</p>
                <div class="actions"><a class="btn" href="{{ route('catalog', 'echipamente-service') }}">Echipamente service</a><a class="btn outline" href="{{ route('catalog', 'dulapuri-si-organizare') }}">Organizare atelier</a></div>
                <div class="hero-stats">
                    <span><strong>Service</strong> flux organizat</span>
                    <span><strong>Control</strong> lucrari precise</span>
                    <span><strong>Stoc</strong> selectie rapida</span>
                </div>
            </div>
            <div class="hero-showcase" aria-hidden="true">
                <div class="hero-product-stage">
                    <span class="hero-product-badge">Atelier</span>
                    <img src="/images/products/m7-sm-0503.jpg" alt="">
                </div>
                <div class="hero-product-row">
                    <div class="hero-product-mini"><img src="/images/products/king-tony-34262-1dg.jpg" alt=""></div>
                    <div class="hero-product-mini"><img src="/images/products/product-placeholder-toolbox.svg" alt=""></div>
                </div>
                <div class="hero-panel">
                    <span class="hero-panel-label">Echipamente</span>
                    <h2>Atelier pregatit pentru comenzi rapide</h2>
                    <p>Produse grupate pe zone de lucru pentru service si garaj.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-dots" aria-label="Navigare bannere">
        <button class="is-active" type="button" data-hero-dot="0" aria-label="Banner 1"></button>
        <button type="button" data-hero-dot="1" aria-label="Banner 2"></button>
        <button type="button" data-hero-dot="2" aria-label="Banner 3"></button>
        <button type="button" data-hero-dot="3" aria-label="Banner 4"></button>
    </div>
</section>

<section class="quick-categories-shell">
    <div class="quick-categories-head">
        <span>Categorii principale</span>
        <h2>Alege rapid zona de lucru</h2>
    </div>
    <div class="quick-categories">
    @php
        $categoryImages = [
            'seturi-de-scule' => '/images/categories/seturi-scule.svg',
            'tubulare-si-clichete' => '/images/categories/tubulare-clichete.svg',
            'chei-si-surubelnite' => '/images/categories/chei-surubelnite.svg',
            'scule-pneumatice' => '/images/categories/scule-pneumatice.svg',
            'chei-dinamometrice' => '/images/categories/cheie-dinamometrica.svg',
            'cricuri-si-ridicare' => '/images/categories/cric-ridicare.svg',
            'dulapuri-si-organizare' => '/images/categories/dulapuri-organizare.svg',
            'compresoare' => '/images/categories/compresor-atelier.svg',
            'echipamente-service' => '/images/categories/echipamente-service.svg',
        ];
    @endphp
    @foreach($categories as $category)
        <a href="{{ route('catalog', $category->slug) }}">
            <span class="category-visual category-photo category-{{ $category->slug }}" aria-hidden="true">
                <img src="{{ $categoryImages[$category->slug] ?? '/images/products/product-placeholder-toolbox.svg' }}" alt="">
                <span class="category-photo-glow"></span>
            </span>
            <span class="category-title">{{ $category->name_ro }}</span>
        </a>
    @endforeach
    </div>
</section>

<section class="shell section-head">
    <h2>Produse recomandate</h2>
    <a href="{{ route('catalog') }}">Vezi toate produsele</a>
</section>
<section class="shell product-grid">
    @foreach($featuredProducts as $product)
        <x-product-card :product="$product" />
    @endforeach
</section>

<section class="shell brands-row">
    <h2>Branduri populare</h2>
    @foreach($brands as $brand)
        <a href="{{ route('brand.show', $brand->slug) }}"><img src="{{ $brand->logo }}" alt="{{ $brand->name }}"></a>
    @endforeach
</section>
@endsection
