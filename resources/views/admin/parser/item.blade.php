@extends('layouts.admin')

@section('content')
@php($ru = app()->isLocale('ru'))
<section class="shell page-title">
    <p>{{ __('ui.admin') }} / <a href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a> / <a href="{{ route('admin.parser.batches.show', $item->batch) }}">{{ $item->batch->title }}</a></p>
    <h1>SKU {{ $item->sku }}</h1>
    <span>{{ __('ui.status') }}: {{ $item->status }} / {{ __('ui.parser_confidence') }}: {{ $item->category_confidence_score ?? $item->confidence_score ?? '-' }}%</span>
</section>

@if($errors->any())
    <div class="shell notice error">{{ $errors->first() }}</div>
@endif

<section class="shell parser-review-grid">
    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_product_data') }}</span>
            <h2>{{ $item->name_ru ?: $item->found_title ?: __('ui.parser_not_found') }}</h2>
        </div>
        <p>{{ $item->description_ru ?: $item->found_description ?: $item->error_message }}</p>
        <dl class="parser-specs">
            <div><dt>{{ $ru ? 'Название RO' : 'Denumire RO' }}</dt><dd>{{ $item->name_ro ?: '-' }}</dd></div>
            <div><dt>{{ $ru ? 'Retail цена из прайса' : 'Pret retail din lista' }}</dt><dd>{{ $item->parsed_price ?? '-' }}</dd></div>
            <div><dt>{{ $ru ? 'Остаток' : 'Stoc' }}</dt><dd>{{ $item->parsed_stock ?? '-' }} @if($item->needs_stock_review)<small>{{ $ru ? 'нужна проверка остатка' : 'necesita verificare stoc' }}</small>@endif</dd></div>
            <div><dt>{{ $ru ? 'Группа прайса' : 'Grupa lista' }}</dt><dd>{{ $item->detected_group ?: '-' }}</dd></div>
            @foreach(($item->found_specs_json ?: []) as $key => $value)
                <div><dt>{{ $key }}</dt><dd>{{ is_array($value) ? implode(', ', $value) : $value }}</dd></div>
            @endforeach
        </dl>
        @if($item->existingProduct)
            <div class="parser-existing">
                <strong>{{ __('ui.parser_existing_product') }}</strong>
                <a href="{{ route('admin.products', ['q' => $item->existingProduct->sku]) }}">{{ $item->existingProduct->display_name }}</a>
            </div>
        @endif
    </article>

    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_sources') }}</span>
            <h2>{{ __('ui.parser_found_sources') }}</h2>
        </div>
        <div class="parser-source-list">
            @forelse($item->sources as $source)
                <a href="{{ $source->url }}" target="_blank" rel="noopener">
                    <strong>{{ $source->title ?: $source->domain }}</strong>
                    <span>{{ $source->domain }} / {{ $source->source_type }} / {{ $source->confidence_score }}%</span>
                    <small>{{ $source->snippet }}</small>
                </a>
            @empty
                <p>{{ __('ui.parser_no_sources') }}</p>
            @endforelse
        </div>
    </article>
</section>

<section class="shell panel parser-card">
    <div class="admin-panel-head">
        <span>{{ $ru ? 'Категория товара' : 'Categoria produsului' }}</span>
        <h2>{{ $item->detected_category_path ?: ($ru ? 'Категория не определена уверенно' : 'Categoria nu este determinata sigur') }}</h2>
    </div>
    <div class="parser-category-review">
        <div>
            <strong>{{ $ru ? 'Уверенность' : 'Incredere' }}: {{ $item->category_confidence_score ?? $item->confidence_score ?? 0 }}%</strong>
            <span>{{ $item->needs_category_review ? ($ru ? 'Нужна ручная проверка категории.' : 'Categoria necesita verificare manuala.') : ($ru ? 'Категория может быть использована для draft.' : 'Categoria poate fi folosita pentru draft.') }}</span>
        </div>
        <ul>
            @forelse(($item->category_detection_notes_json ?: []) as $note)
                <li>{{ $note }}</li>
            @empty
                <li>{{ $ru ? 'Нет достаточных совпадений по правилам.' : 'Nu exista potriviri suficiente dupa reguli.' }}</li>
            @endforelse
        </ul>
        <form method="post" action="{{ route('admin.parser.items.category', $item) }}" class="admin-product-form parser-category-form">
            @csrf
            <label>{{ $ru ? 'Изменить категорию' : 'Schimba categoria' }}
                <select name="category_id" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) $item->category_id === (int) $category->id)>{{ $category->display_name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn small">{{ $ru ? 'Сохранить категорию' : 'Salveaza categoria' }}</button>
        </form>
    </div>
</section>

<section class="shell panel parser-card">
    <div class="admin-panel-head">
        <span>{{ __('ui.parser_images') }}</span>
        <h2>{{ __('ui.parser_select_images') }}</h2>
    </div>
    <p class="parser-legal-note">{{ $ru ? 'Перед публикацией проверьте право использования изображений. Парсер сохраняет источник каждого фото.' : 'Inainte de publicare verificati dreptul de utilizare pentru imagini. Parserul salveaza sursa fiecarei fotografii.' }}</p>
    <form method="post" action="{{ route('admin.parser.items.select-images', $item) }}">
        @csrf
        <div class="parser-image-grid">
            @foreach($item->imageAssets as $asset)
                <label class="parser-image-option">
                    <input type="checkbox" name="images[]" value="{{ $asset->id }}" @checked($asset->is_selected)>
                    <img src="{{ $asset->processed_path ?: $asset->source_url }}" alt="">
                    <span>{{ $asset->status }} {{ $asset->is_main ? '/ main' : '' }}</span>
                </label>
            @endforeach
        </div>
        <div class="parser-actions">
            <button class="btn small" type="submit">{{ __('ui.parser_save_selection') }}</button>
        </div>
    </form>
    <div class="parser-actions">
        <form method="post" action="{{ route('admin.parser.items.process-images', $item) }}">@csrf<button class="btn small">{{ __('ui.parser_process_images') }}</button></form>
        <form method="post" action="{{ route('admin.parser.items.retry', $item) }}">@csrf<button class="btn outline small">{{ __('ui.parser_retry') }}</button></form>
        <form method="post" action="{{ route('admin.parser.items.reject', $item) }}">@csrf<button class="delete">{{ __('ui.parser_reject') }}</button></form>
    </div>
</section>

<section class="shell parser-review-grid">
    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_compare') }}</span>
            <h2>{{ __('ui.parser_safe_actions') }}</h2>
        </div>
        @if($item->existingProduct)
            <div class="parser-table-wrap parser-compare-wrap">
                <table class="parser-table parser-compare-table">
                    <thead>
                        <tr>
                            <th>{{ $ru ? 'Поле' : 'Camp' }}</th>
                            <th>{{ $ru ? 'Текущее значение' : 'Valoare curenta' }}</th>
                            <th>{{ $ru ? 'Новое из прайса' : 'Nou din lista' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>SKU</td><td>{{ $item->existingProduct->sku }}</td><td>{{ $item->sku }}</td></tr>
                        <tr><td>{{ $ru ? 'Название RU' : 'Denumire RU' }}</td><td>{{ $item->existingProduct->name }}</td><td>{{ $item->name_ru ?: $item->found_title }}</td></tr>
                        <tr><td>{{ $ru ? 'Название RO' : 'Denumire RO' }}</td><td>{{ $item->existingProduct->name_ro }}</td><td>{{ $item->name_ro }}</td></tr>
                        <tr><td>{{ $ru ? 'Цена' : 'Pret' }}</td><td>{{ $item->existingProduct->price }}</td><td>{{ $item->parsed_price ?? '-' }}</td></tr>
                        <tr><td>{{ $ru ? 'Остаток' : 'Stoc' }}</td><td>{{ $item->existingProduct->stock_quantity }}</td><td>{{ $item->parsed_stock ?? '-' }}</td></tr>
                        <tr><td>{{ __('ui.category') }}</td><td>{{ $item->existingProduct->category?->display_name }}</td><td>{{ $item->detected_category_path ?: $item->category?->display_name }}</td></tr>
                    </tbody>
                </table>
            </div>
            <form method="post" action="{{ route('admin.parser.items.update-existing', $item) }}" class="admin-product-form">
                @csrf
                <label>{{ __('ui.parser_update_action') }}
                    <select name="action">
                        <option value="update_stock">{{ $ru ? 'Обновить остаток' : 'Actualizeaza stoc' }}</option>
                        <option value="update_price">{{ $ru ? 'Обновить цену' : 'Actualizeaza pret' }}</option>
                        <option value="update_price_stock">{{ $ru ? 'Обновить цену и остаток' : 'Actualizeaza pret si stoc' }}</option>
                        <option value="add_photos">{{ __('ui.parser_add_photos') }}</option>
                        <option value="update_description">{{ __('ui.parser_update_description') }}</option>
                        <option value="replace_photos">{{ __('ui.parser_replace_photos') }}</option>
                    </select>
                </label>
                <label><input type="checkbox" name="replace_confirmed" value="1"> {{ __('ui.parser_replace_confirm') }}</label>
                <button class="btn" type="submit">{{ __('ui.parser_update_existing') }}</button>
            </form>
        @else
            @if($item->createdProduct)
                <div class="parser-existing">
                    <strong>{{ $ru ? 'Draft уже создан' : 'Draftul exista deja' }}</strong>
                    <a href="{{ route('admin.products', ['q' => $item->createdProduct->sku]) }}">{{ $item->createdProduct->display_name }}</a>
                </div>
                <form method="post" action="{{ route('admin.parser.items.publish', $item) }}">
                    @csrf
                    <button class="btn" type="submit">{{ $ru ? 'Утвердить и опубликовать' : 'Aproba si publica' }}</button>
                </form>
            @else
            <form method="post" action="{{ route('admin.parser.items.draft', $item) }}">
                @csrf
                <button class="btn" type="submit" @disabled($item->status === 'not_found' || $item->status === 'failed' || $item->needs_category_review)>{{ __('ui.parser_create_draft') }}</button>
            </form>
            <p>{{ __('ui.parser_draft_note') }}</p>
            @endif
        @endif
    </article>

    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_processed_images') }}</span>
            <h2>{{ __('ui.parser_site_standard') }}</h2>
        </div>
        <div class="parser-processed-list">
            @forelse($item->imageAssets->where('status', 'processed') as $asset)
                <a href="{{ $asset->processed_path }}" target="_blank">
                    <img src="{{ $asset->thumb_path ?: $asset->processed_path }}" alt="">
                    <span>1200x1200 WebP / watermark: {{ $asset->has_watermark ? 'yes' : 'no' }}</span>
                </a>
            @empty
                <p>{{ __('ui.parser_no_processed_images') }}</p>
            @endforelse
        </div>
    </article>
</section>
@endsection
