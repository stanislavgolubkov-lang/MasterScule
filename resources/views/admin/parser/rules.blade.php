@extends('layouts.admin')

@section('content')
@php($ru = app()->isLocale('ru'))
@php($rules = $settings['category_rules'] ?? [])
@php($keywords = collect($rules['keywords'] ?? [])->map(fn ($items, $slug) => $slug.'='.implode(',', (array) $items))->implode("\n"))
@php($skuPrefixes = collect($rules['sku_prefixes'] ?? [])->map(fn ($slug, $prefix) => $prefix.'='.$slug)->implode("\n"))
@php($groupMapping = collect($rules['group_mapping'] ?? [])->map(fn ($slug, $group) => $group.'='.$slug)->implode("\n"))

<section class="shell page-title">
    <p>{{ __('ui.admin') }} / <a href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a></p>
    <h1>{{ $ru ? 'Правила категорий' : 'Reguli categorii' }}</h1>
    <span>{{ $ru ? 'Настройка словарей, SKU-префиксов и mapping групп прайса.' : 'Configurare dictionare, prefixe SKU si mapping pentru grupele din liste.' }}</span>
</section>

<section class="shell parser-tabs">
    <a href="{{ route('admin.parser.index') }}">{{ $ru ? 'Импорт прайс-листа' : 'Import lista preturi' }}</a>
    <a href="{{ route('admin.parser.drafts') }}">{{ $ru ? 'Черновики из прайсов' : 'Drafturi din liste' }}</a>
    <a class="active" href="{{ route('admin.parser.rules') }}">{{ $ru ? 'Правила категорий' : 'Reguli categorii' }}</a>
</section>

<section class="shell parser-warning">
    <strong>{{ $ru ? 'Формат правил' : 'Format reguli' }}</strong>
    <span>{{ $ru ? 'Одна строка = одно правило. Формат: категория-slug=слово1,слово2 или SKU-префикс=категория-slug.' : 'Un rand = o regula. Format: categorie-slug=cuvant1,cuvant2 sau prefix-SKU=categorie-slug.' }}</span>
</section>

<section class="shell parser-grid parser-grid-wide">
    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ $ru ? 'Редактор правил' : 'Editor reguli' }}</span>
            <h2>{{ $ru ? 'Автоопределение категории' : 'Detectare automata categorie' }}</h2>
        </div>
        <form method="post" action="{{ route('admin.parser.rules.update') }}" class="admin-product-form">
            @csrf
            <label>{{ $ru ? 'Минимальный confidence для рекомендации' : 'Confidence minim pentru recomandare' }}
                <input type="number" name="min_confidence" value="{{ max(90, (int) ($rules['min_confidence'] ?? 90)) }}" min="90" max="100">
            </label>
            <label>{{ $ru ? 'Ключевые слова' : 'Cuvinte cheie' }}
                <textarea name="keywords" rows="10" placeholder="scule-pneumatice=пневмо,pneumatic,гайковерт">{{ $keywords }}</textarea>
            </label>
            <label>{{ $ru ? 'SKU-префиксы' : 'Prefixe SKU' }}
                <textarea name="sku_prefixes" rows="8" placeholder="NC-=pistoale-pneumatice-si-impact">{{ $skuPrefixes }}</textarea>
            </label>
            <label>{{ $ru ? 'Mapping групп прайса' : 'Mapping grupe lista' }}
                <textarea name="group_mapping" rows="8" placeholder="Авторемонтный Пневмоинструмент=scule-pneumatice">{{ $groupMapping }}</textarea>
            </label>
            <button class="btn">{{ __('ui.save_changes') }}</button>
        </form>
    </article>

    <aside class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.category') }}</span>
            <h2>{{ $ru ? 'Доступные slug категорий' : 'Sluguri categorii disponibile' }}</h2>
        </div>
        <div class="parser-mini-list">
            @foreach($categories as $category)
                <span>
                    <strong>{{ $category->slug }}</strong>
                    <small>{{ $category->display_name }}</small>
                </span>
            @endforeach
        </div>
    </aside>
</section>
@endsection
