@extends('layouts.app')

@section('title', 'MasterScule.ro - Scule si echipamente profesionale')

@section('content')
<section class="hero hero-premium" data-hero-slider>
    <div class="hero-slide is-active hero-slide-service" data-hero-slide>
    <div class="hero-backdrop" aria-hidden="true"></div>
    <div class="hero-grid">
        <div class="hero-copy">
            <span class="hero-kicker">MasterScule.ro · RON · Livrare in Romania</span>
            <h1>Scule profesionale pentru service si garaj</h1>
            <p>Alege seturi, pneumatice, chei dinamometrice si echipamente de atelier pentru lucrari precise, rapide si sigure.</p>
            <div class="actions"><a class="btn" href="{{ route('catalog') }}" data-catalog-open>Vezi catalogul</a><a class="btn orange-btn" href="{{ route('promotions') }}">Promotii</a></div>
            <div class="hero-stats">
                <span><strong>{{ $productsCount }}+</strong> produse in catalog</span>
                <span><strong>King Tony</strong> brand principal</span>
                <span><strong>M7</strong> pneumatice</span>
            </div>
        </div>
        <div class="hero-panel">
            <span class="hero-panel-label">TOP atelier</span>
            <h2>Seturi, pneumatice si scule de precizie</h2>
            <p>Catalog pregatit pentru service-uri auto, garaje si clienti B2B.</p>
        </div>
    </div>
    </div>
    <div class="hero-slide hero-slide-king" data-hero-slide>
        <div class="hero-backdrop" aria-hidden="true"></div>
        <div class="hero-grid">
            <div class="hero-copy">
                <span class="hero-kicker">King Tony · scule pentru atelier</span>
                <h1>Seturi si tubulare pentru lucrari mecanice</h1>
                <p>Truse, chei, tubulare si accesorii King Tony pentru service-uri care lucreaza zilnic cu piese auto.</p>
                <div class="actions"><a class="btn" href="{{ route('brand.show', 'king-tony') }}">Vezi King Tony</a><a class="btn outline" href="{{ route('catalog', 'seturi-de-scule') }}">Seturi de scule</a></div>
                <div class="hero-stats">
                    <span><strong>100</strong> articole King Tony</span>
                    <span><strong>24 luni</strong> garantie</span>
                    <span><strong>RON</strong> preturi clare</span>
                </div>
            </div>
            <div class="hero-panel">
                <span class="hero-panel-label">King Tony</span>
                <h2>Truse complete pentru service</h2>
                <p>Produse potrivite pentru mecanica generala, garaj si ateliere profesionale.</p>
            </div>
        </div>
    </div>
    <div class="hero-slide hero-slide-m7" data-hero-slide>
        <div class="hero-backdrop" aria-hidden="true"></div>
        <div class="hero-grid">
            <div class="hero-copy">
                <span class="hero-kicker">M7 · pneumatice profesionale</span>
                <h1>Pistoale pneumatice si scule cu aer comprimat</h1>
                <p>Alege M7 pentru vulcanizare, service auto si lucrari rapide unde conteaza cuplul si fiabilitatea.</p>
                <div class="actions"><a class="btn" href="{{ route('brand.show', 'm7-mighty-seven') }}">Vezi M7</a><a class="btn outline" href="{{ route('catalog', 'scule-pneumatice') }}">Scule pneumatice</a></div>
                <div class="hero-stats">
                    <span><strong>50</strong> articole M7</span>
                    <span><strong>Service</strong> utilizare intensa</span>
                    <span><strong>Stoc</strong> produse demo</span>
                </div>
            </div>
            <div class="hero-panel">
                <span class="hero-panel-label">M7 pneumatice</span>
                <h2>Scule rapide pentru lucrari grele</h2>
                <p>Pistoale de impact, accesorii si echipamente pentru aer comprimat.</p>
            </div>
        </div>
    </div>
    <button class="hero-control hero-prev" type="button" data-hero-prev aria-label="Slide anterior">‹</button>
    <button class="hero-control hero-next" type="button" data-hero-next aria-label="Slide urmator">›</button>
    <div class="hero-dots" aria-label="Navigare bannere">
        <button class="is-active" type="button" data-hero-dot="0" aria-label="Banner 1"></button>
        <button type="button" data-hero-dot="1" aria-label="Banner 2"></button>
        <button type="button" data-hero-dot="2" aria-label="Banner 3"></button>
    </div>
</section>

<section class="quick-categories-shell">
    <div class="quick-categories-head">
        <span>Categorii principale</span>
        <h2>Alege rapid zona de lucru</h2>
    </div>
    <div class="quick-categories">
    @foreach($categories as $category)
        <a href="{{ route('catalog', $category->slug) }}">
            <span class="category-visual category-{{ $category->slug }}" aria-hidden="true">
                @switch($category->slug)
                    @case('seturi-de-scule')
                        <svg viewBox="0 0 96 64"><path class="icon-fill" d="M19 22h58a5 5 0 0 1 5 5v25a5 5 0 0 1-5 5H19a5 5 0 0 1-5-5V27a5 5 0 0 1 5-5Z"/><path d="M37 22v-7h22v7M14 35h68M28 46h8m8 0h8m8 0h8"/></svg>
                        @break
                    @case('tubulare-si-clichete')
                        <svg viewBox="0 0 96 64"><path class="icon-fill" d="M18 44h38l23-23 9 9-23 23H18Z"/><circle cx="76" cy="20" r="8"/><path d="M25 49h31M59 40l-9-9"/></svg>
                        @break
                    @case('chei-si-surubelnite')
                        <svg viewBox="0 0 96 64"><path d="M18 51l30-30 8 8-30 30Z"/><path class="icon-accent" d="M62 14l20 20M55 21l20 20M27 16l52 36"/><path d="M46 23l7 7"/></svg>
                        @break
                    @case('scule-pneumatice')
                        <svg viewBox="0 0 96 64"><path class="icon-fill" d="M14 24h45l15 10v10H47l-7 13H28l5-13H14Z"/><path d="M59 24v-8h14M73 34h10M31 44l-5 13M48 24v20"/></svg>
                        @break
                    @case('chei-dinamometrice')
                        <svg viewBox="0 0 96 64"><path class="icon-fill" d="M14 38h57l11-11 6 6-11 11H14Z"/><circle cx="25" cy="38" r="7"/><path d="M42 32v12M52 32v12M62 32v12M72 27l6 6"/></svg>
                        @break
                    @case('cricuri-si-ridicare')
                        <svg viewBox="0 0 96 64"><path class="icon-fill" d="M22 50h52l-9-24H39Z"/><path d="M15 50h66M31 50l13-33h17l13 33M38 38h28"/><circle cx="31" cy="53" r="4"/><circle cx="70" cy="53" r="4"/></svg>
                        @break
                    @case('dulapuri-si-organizare')
                        <svg viewBox="0 0 96 64"><rect class="icon-fill" x="25" y="10" width="46" height="45" rx="5"/><path d="M25 23h46M25 36h46M37 17h22M37 30h22M37 43h22"/><circle cx="33" cy="58" r="3"/><circle cx="63" cy="58" r="3"/></svg>
                        @break
                    @case('compresoare')
                        <svg viewBox="0 0 96 64"><rect class="icon-fill" x="15" y="35" width="58" height="16" rx="8"/><path d="M33 35V19h25v16M38 19h15M68 29h13M73 29v6"/><circle cx="28" cy="54" r="5"/><circle cx="63" cy="54" r="5"/></svg>
                        @break
                    @default
                        <svg viewBox="0 0 96 64"><rect class="icon-fill" x="23" y="18" width="50" height="35" rx="5"/><path d="M36 18v-7h24v7M34 33h28M38 44h20"/></svg>
                @endswitch
            </span>
            <span class="category-title">{{ $category->name_ro }}</span>
        </a>
    @endforeach
    </div>
</section>

<section class="shell ai-strip">
    <div><h2>Consultant AI pentru scule</h2><p>Spune ce lucrare ai, bugetul și nivelul de utilizare. Recomandările sunt făcute doar din produse existente în catalog.</p></div>
    <a class="btn" href="{{ route('ai.advisor') }}" data-ai-open>Alege scula potrivită</a>
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
