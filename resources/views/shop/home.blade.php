@extends('layouts.app')

@section('title', __('ui.site_title'))

@section('content')
@php
    $copy = app()->isLocale('ru')
        ? [
            ['kicker' => config('store.domain_label').' - полный комплект для сервиса', 'title' => 'Профессиональные инструменты для сервиса и гаража', 'text' => 'Выбирайте наборы, пневмоинструмент, динамометрические ключи и оборудование для точной, быстрой и безопасной работы.', 'primary' => 'Открыть каталог', 'secondary' => 'Акции', 'panel' => 'Наборы, пневматика и точный инструмент', 'panel_text' => 'Каталог для автосервисов, гаражей и B2B-клиентов.', 'badge' => 'Сервис'],
            ['kicker' => 'King Tony - наборы для мастерской', 'title' => 'Наборы и головки для механических работ', 'text' => 'Наборы, ключи, головки и аксессуары King Tony для сервисов, которые каждый день работают с авто.', 'primary' => 'Смотреть King Tony', 'secondary' => 'Наборы инструментов', 'panel' => 'Полные наборы для сервиса', 'panel_text' => 'Подходит для общей механики, гаража и профессиональных мастерских.', 'badge' => 'King Tony'],
            ['kicker' => 'M7 - профессиональная пневматика', 'title' => 'Пневмогайковерты и инструмент на сжатом воздухе', 'text' => 'Выбирайте M7 для шиномонтажа, автосервиса и задач, где важны момент и надежность.', 'primary' => 'Смотреть M7', 'secondary' => 'Пневмоинструмент', 'panel' => 'Быстрый инструмент для тяжелых задач', 'panel_text' => 'Ударные гайковерты, аксессуары и оборудование для сжатого воздуха.', 'badge' => 'M7'],
            ['kicker' => 'Оборудование сервиса - порядок и контроль', 'title' => 'Диагностика, организация и обслуживание в мастерской', 'text' => 'Дополните рабочую зону диагностикой, мобильными шкафами и полезными аксессуарами для сервиса.', 'primary' => 'Оборудование сервиса', 'secondary' => 'Организация мастерской', 'panel' => 'Мастерская готова к быстрым заказам', 'panel_text' => 'Товары сгруппированы по зонам работы для сервиса и гаража.', 'badge' => 'Мастерская'],
        ]
        : [
            ['kicker' => config('store.domain_label').' - atelier complet', 'title' => 'Scule profesionale pentru service si garaj', 'text' => 'Alege truse, pneumatice, chei dinamometrice si echipamente de atelier pentru lucrari precise, rapide si sigure.', 'primary' => 'Vezi catalogul', 'secondary' => 'Promotii', 'panel' => 'Seturi, pneumatice si scule de precizie', 'panel_text' => 'Catalog pregatit pentru service-uri auto, garaje si clienti B2B.', 'badge' => 'Service'],
            ['kicker' => 'King Tony - truse pentru atelier', 'title' => 'Seturi si tubulare pentru lucrari mecanice', 'text' => 'Truse, chei, tubulare si accesorii King Tony pentru service-uri care lucreaza zilnic cu piese auto.', 'primary' => 'Vezi King Tony', 'secondary' => 'Seturi de scule', 'panel' => 'Truse complete pentru service', 'panel_text' => 'Produse potrivite pentru mecanica generala, garaj si ateliere profesionale.', 'badge' => 'King Tony'],
            ['kicker' => 'M7 - pneumatice profesionale', 'title' => 'Pistoale pneumatice si scule cu aer comprimat', 'text' => 'Alege M7 pentru vulcanizare, service auto si lucrari rapide unde conteaza cuplul si fiabilitatea.', 'primary' => 'Vezi M7', 'secondary' => 'Scule pneumatice', 'panel' => 'Scule rapide pentru lucrari grele', 'panel_text' => 'Pistoale de impact, accesorii si echipamente pentru aer comprimat.', 'badge' => 'M7'],
            ['kicker' => 'Echipamente service - organizare si control', 'title' => 'Diagnostic, organizare si intretinere in atelier', 'text' => 'Completeaza zona de lucru cu scule pentru diagnoza, dulapuri mobile si accesorii utile pentru service.', 'primary' => 'Echipamente service', 'secondary' => 'Organizare atelier', 'panel' => 'Atelier pregatit pentru comenzi rapide', 'panel_text' => 'Produse grupate pe zone de lucru pentru service si garaj.', 'badge' => 'Atelier'],
        ];

    $taskCards = [
        ['key' => 'garage', 'href' => route('catalog', 'instrument-manual'), 'tone' => 'blue', 'image' => '/images/tasks/for-garage.png'],
        ['key' => 'service', 'href' => route('catalog', 'echipamente-pentru-service'), 'tone' => 'dark', 'image' => '/images/tasks/for-service.png'],
        ['key' => 'tires', 'href' => route('catalog', 'vulcanizare'), 'tone' => 'orange', 'image' => '/images/tasks/for-tires.png'],
        ['key' => 'pneumatic', 'href' => route('catalog', 'scule-pneumatice'), 'tone' => 'orange', 'image' => '/images/tasks/for-pneumatic.png'],
        ['key' => 'brakes', 'href' => route('catalog', 'scule-motor-frane-suspensie'), 'tone' => 'blue', 'image' => '/images/tasks/for-brakes-suspension.png'],
        ['key' => 'engine', 'href' => route('catalog', 'scule-motor-frane-suspensie'), 'tone' => 'dark', 'image' => '/images/tasks/for-engine.png'],
        ['key' => 'electric', 'href' => route('catalog', 'instrumente-electromontaj'), 'tone' => 'blue', 'image' => '/images/tasks/for-electric.png'],
        ['key' => 'workshop', 'href' => route('catalog', 'dulapuri-si-organizare'), 'tone' => 'dark', 'image' => '/images/tasks/for-workshop.png'],
    ];
@endphp

<section class="hero hero-premium" data-hero-slider>
    @foreach([
        ['class' => 'hero-slide-service is-active', 'primary' => route('catalog'), 'secondary' => route('promotions')],
        ['class' => 'hero-slide-king', 'primary' => route('brand.show', 'king-tony'), 'secondary' => route('catalog', 'seturi-de-scule')],
        ['class' => 'hero-slide-m7', 'primary' => route('brand.show', 'm7-mighty-seven'), 'secondary' => route('catalog', 'scule-pneumatice')],
        ['class' => 'hero-slide-equipment', 'primary' => route('catalog', 'echipamente-service'), 'secondary' => route('catalog', 'dulapuri-si-organizare')],
    ] as $index => $slide)
        <div class="hero-slide {{ $slide['class'] }}" data-hero-slide>
            <div class="hero-backdrop" aria-hidden="true"></div>
            <div class="hero-grid">
                <div class="hero-copy">
                    <span class="hero-kicker">{{ $copy[$index]['kicker'] }}</span>
                    <h1>{{ $copy[$index]['title'] }}</h1>
                    <p>{{ $copy[$index]['text'] }}</p>
                    <div class="actions"><a class="btn" href="{{ $slide['primary'] }}" @if($index === 0) data-catalog-open @endif>{{ $copy[$index]['primary'] }}</a><a class="btn {{ $index === 0 ? 'orange-btn' : 'outline' }}" href="{{ $slide['secondary'] }}">{{ $copy[$index]['secondary'] }}</a></div>
                    <div class="hero-stats">
                        <span><strong>{{ $productsCount }}+</strong> {{ __('ui.products') }}</span>
                        <span><strong>King Tony</strong> {{ app()->isLocale('ru') ? 'наборы и ключи' : 'truse si chei' }}</span>
                        <span><strong>M7</strong> {{ app()->isLocale('ru') ? 'пневматика' : 'pneumatice' }}</span>
                    </div>
                </div>
                <div class="hero-showcase" aria-hidden="true">
                    <div class="hero-panel">
                        <span class="hero-panel-label">{{ $copy[$index]['badge'] }}</span>
                        <h2>{{ $copy[$index]['panel'] }}</h2>
                        <p>{{ $copy[$index]['panel_text'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div class="hero-dots" aria-label="Hero slider">
        <button class="is-active" type="button" data-hero-dot="0" aria-label="Banner 1"></button>
        <button type="button" data-hero-dot="1" aria-label="Banner 2"></button>
        <button type="button" data-hero-dot="2" aria-label="Banner 3"></button>
        <button type="button" data-hero-dot="3" aria-label="Banner 4"></button>
    </div>
</section>

<section class="quick-categories-shell">
    <div class="quick-categories-head">
        <span>{{ __('ui.main_categories') }}</span>
        <h2>{{ __('ui.quick_work_area') }}</h2>
    </div>
    <div class="quick-categories">
    @foreach($categories as $category)
        <a href="{{ route('catalog', $category->slug) }}">
            <span class="category-visual category-photo category-{{ $category->slug }}" aria-hidden="true">
                <img src="{{ $category->image ?: '/images/products/product-placeholder-toolbox.svg' }}" alt="">
                <span class="category-photo-glow"></span>
            </span>
            <span class="category-title">{{ $category->display_name }}</span>
        </a>
    @endforeach
    </div>
</section>

<section class="shell task-selector">
    <div class="task-selector-head">
        <span>{{ __('ui.task_selector_badge') }}</span>
        <h2>{{ __('ui.task_selector_title') }}</h2>
    </div>
    <div class="task-grid">
        @foreach($taskCards as $task)
            <a class="task-card task-card-{{ $task['tone'] }}" href="{{ $task['href'] }}">
                <span class="task-card-icon task-icon-{{ $task['key'] }}" aria-hidden="true">
                    <img src="{{ $task['image'] }}" alt="">
                </span>
                <strong>{{ __('ui.task_'.$task['key']) }}</strong>
                <small>{{ __('ui.task_'.$task['key'].'_text') }}</small>
            </a>
        @endforeach
    </div>
</section>

<x-consultation-cta class="shell home-consultation" />

<section class="shell section-head">
    <h2>{{ __('ui.recommended_products') }}</h2>
    <a href="{{ route('catalog') }}">{{ __('ui.view_all_products') }}</a>
</section>
<section class="shell product-grid">
    @foreach($featuredProducts as $product)
        <x-product-card :product="$product" />
    @endforeach
</section>

@endsection
