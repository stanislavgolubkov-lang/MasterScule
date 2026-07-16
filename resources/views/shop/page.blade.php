@extends('layouts.app')

@php
    $translatedTitle = __("pages.{$page->slug}.title");
    $translatedContent = __("pages.{$page->slug}.content");
    $title = $translatedTitle === "pages.{$page->slug}.title" ? $page->title : $translatedTitle;
    $content = $translatedContent === "pages.{$page->slug}.content" ? $page->content : $translatedContent;
@endphp

@section('title', $title.' | '.config('store.domain_label'))

@section('content')
@if($page->slug === 'contacts')
    @php
        $isRussian = app()->isLocale('ru');
        $mapUrl = 'https://www.google.com/maps/search/?api=1&query='.urlencode(config('store.address'));
    @endphp

    <section class="shell contact-hero">
        <div class="contact-hero-copy">
            <span class="contact-eyebrow">{{ $isRussian ? 'Всегда на связи' : 'Suntem aproape' }}</span>
            <h1>{{ $title }}</h1>
            <p>{{ $isRussian
                ? 'Поможем подобрать инструмент, уточнить наличие, оформить заказ и организовать доставку по Молдове.'
                : 'Te ajutăm să alegi sculele potrivite, să verifici stocul, să plasezi comanda și să organizezi livrarea în Moldova.' }}</p>

            <div class="contact-hero-actions">
                <a class="btn contact-primary-action" href="tel:{{ config('store.phone_href') }}">
                    {{ $isRussian ? 'Позвонить менеджеру' : 'Sună managerul' }}
                </a>
                <a class="btn outline contact-secondary-action" href="mailto:{{ config('store.email') }}">
                    {{ $isRussian ? 'Написать на email' : 'Scrie-ne pe email' }}
                </a>
            </div>

            <div class="contact-hero-note">
                <span aria-hidden="true"></span>
                {{ $isRussian ? 'Отвечаем в рабочее время' : 'Răspundem în timpul programului' }}
            </div>
        </div>

        <div class="contact-hero-media">
            <img
                src="/images/contact-workshop-consultation.webp"
                alt="{{ $isRussian ? 'Консультация специалиста в магазине профессионального инструмента' : 'Consultație într-un magazin de scule profesionale' }}"
                width="1712"
                height="918"
                fetchpriority="high"
            >
            <div class="contact-hero-badge">
                <strong>{{ $isRussian ? 'Экспертный подбор' : 'Alegere asistată' }}</strong>
                <span>{{ $isRussian ? 'Для сервиса, мастерской и гаража' : 'Pentru service, atelier și garaj' }}</span>
            </div>
        </div>
    </section>

    <section class="shell contact-cards" aria-label="{{ $isRussian ? 'Контактная информация' : 'Date de contact' }}">
        <article class="contact-card contact-card-phone">
            <span class="contact-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M6.6 2.8 9.4 2l2.1 5-2 1.5a15 15 0 0 0 6 6l1.5-2 5 2.1-.8 2.8c-.4 1.5-1.8 2.6-3.4 2.6C10.2 20 4 13.8 4 6.2c0-1.6 1.1-3 2.6-3.4Z"/></svg>
            </span>
            <div>
                <small>{{ $isRussian ? 'Телефон' : 'Telefon' }}</small>
                <a href="tel:{{ config('store.phone_href') }}">{{ config('store.phone') }}</a>
                <p>{{ $isRussian ? 'Консультация и оформление заказа' : 'Consultanță și plasarea comenzii' }}</p>
            </div>
        </article>

        <article class="contact-card contact-card-email">
            <span class="contact-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M3 5h18v14H3V5Zm1.5 1.5L12 12l7.5-5.5M4.5 17.5l5.4-5M19.5 17.5l-5.4-5"/></svg>
            </span>
            <div>
                <small>Email</small>
                <a href="mailto:{{ config('store.email') }}">{{ config('store.email') }}</a>
                <p>{{ $isRussian ? 'Запросы, счета и предложения' : 'Solicitări, facturi și oferte' }}</p>
            </div>
        </article>

        <article class="contact-card contact-card-address">
            <span class="contact-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Zm0-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
            </span>
            <div>
                <small>{{ $isRussian ? 'Адрес' : 'Adresă' }}</small>
                <a href="{{ $mapUrl }}" target="_blank" rel="noopener">{{ config('store.address') }}</a>
                <p>{{ $isRussian ? 'Открыть маршрут в Google Maps' : 'Deschide ruta în Google Maps' }}</p>
            </div>
        </article>

        <article class="contact-card contact-card-hours">
            <span class="contact-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13v5l3.5 2"/></svg>
            </span>
            <div>
                <small>{{ $isRussian ? 'График работы' : 'Program' }}</small>
                <strong>{{ config('store.working_hours.'.app()->getLocale()) }}</strong>
                <p>{{ $isRussian ? 'Суббота и воскресенье — выходные' : 'Sâmbătă și duminică — închis' }}</p>
            </div>
        </article>
    </section>

    <section class="shell contact-support">
        <div class="contact-support-copy">
            <span>{{ $isRussian ? 'Не знаете, что выбрать?' : 'Nu știi ce să alegi?' }}</span>
            <h2>{{ $isRussian ? 'Опишите задачу — подберём подходящий инструмент' : 'Descrie lucrarea — îți recomandăm scula potrivită' }}</h2>
            <p>{{ $isRussian
                ? 'Сообщите марку автомобиля, тип работ или артикул. Менеджер проверит совместимость, наличие и предложит подходящие варианты.'
                : 'Spune-ne marca automobilului, tipul lucrării sau codul produsului. Managerul verifică compatibilitatea, stocul și îți propune variante potrivite.' }}</p>
        </div>
        <div class="contact-support-actions">
            <a class="btn orange-btn" href="tel:{{ config('store.phone_href') }}">{{ $isRussian ? 'Получить консультацию' : 'Cere o consultație' }}</a>
            <a class="contact-catalog-link" href="{{ route('catalog') }}">{{ $isRussian ? 'Перейти в каталог' : 'Vezi catalogul' }} <span aria-hidden="true">→</span></a>
        </div>
    </section>
@else
    <section class="shell page-title"><p>{{ config('store.domain_label') }}</p><h1>{{ $title }}</h1></section>
    <section class="shell panel legal"><p>{{ $content }}</p></section>
@endif
@endsection
