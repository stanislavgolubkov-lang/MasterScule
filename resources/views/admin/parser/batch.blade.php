@extends('layouts.admin')

@section('content')
@php
    $ru = app()->isLocale('ru');
    $activeFilter = request('status') ?: (request('needs_category') ? 'needs_category' : (request('no_images') ? 'no_images' : (request('exceptions') ? 'exceptions' : '')));
    $productRows = $batch->product_rows ?: $batch->sku_count;
    $draftPlan = $batch->planned_drafts ?: $batch->created_drafts;
    $autoRefresh = in_array($batch->status, ['pending', 'running', 'processing'], true);
    $canStartImport = in_array($batch->status, ['dry_run_completed', 'cancelled'], true);
@endphp

<section class="shell page-title">
    <p>{{ __('ui.admin') }} / <a href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a></p>
    <h1>{{ $batch->file_name ?: $batch->title }}</h1>
    <span>{{ __('ui.status') }}: {{ $batch->status }} / {{ $ru ? 'товарных строк' : 'randuri produse' }}: {{ $productRows }}</span>
</section>

<section class="shell panel parser-card parser-batch-summary">
    <div class="parser-batch-state">
        <span>{{ $ru ? 'Состояние импорта' : 'Stare import' }}</span>
        <strong>{{ $batch->status }}</strong>
        <small>{{ $autoRefresh ? ($ru ? 'Обработка идет, данные обновляются автоматически' : 'Procesarea ruleaza, datele se actualizeaza automat') : $batch->updated_at->format('d.m.Y H:i') }}</small>
    </div>
    <div class="parser-batch-metrics">
        <div><strong>{{ $batch->total_rows }}</strong><span>{{ $ru ? 'строк всего' : 'randuri total' }}</span></div>
        <div><strong>{{ $productRows }}</strong><span>{{ $ru ? 'товаров' : 'produse' }}</span></div>
        <div><strong>{{ $batch->new_sku_count }}</strong><span>{{ $ru ? 'новых SKU' : 'SKU noi' }}</span></div>
        <div><strong>{{ $batch->existing_sku_count ?: $batch->updated_existing }}</strong><span>{{ $ru ? 'существующих' : 'existente' }}</span></div>
        <div><strong>{{ $draftPlan }}</strong><span>{{ $ru ? 'draft' : 'drafturi' }}</span></div>
        <div><strong>{{ $batch->error_rows }}</strong><span>{{ $ru ? 'ошибок' : 'erori' }}</span></div>
    </div>
</section>

<section class="shell panel parser-card parser-progress-card">
    <div class="parser-progress-block">
        <div class="parser-progress-heading">
            <div>
                <span>{{ $ru ? 'Быстрый проход TrisTool' : 'Verificare rapida TrisTool' }}</span>
                <strong>{{ $filterCounts['fast_percent'] }}%</strong>
            </div>
            <small>
                {{ $ru ? 'Проверено' : 'Verificate' }}:
                {{ $filterCounts['fast_completed'] }} {{ $ru ? 'из' : 'din' }} {{ $filterCounts['fast_total'] }}
                · {{ $ru ? 'Осталось' : 'Ramase' }}: {{ $filterCounts['fast_pending'] }}
            </small>
        </div>
        <div
            class="parser-progress-track"
            role="progressbar"
            aria-label="{{ $ru ? 'Быстрая проверка TrisTool' : 'Verificare rapida TrisTool' }}"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $filterCounts['fast_percent'] }}"
        >
            <span style="width: {{ $filterCounts['fast_percent'] }}%"></span>
        </div>
        <div class="parser-progress-scale" aria-hidden="true">
            <span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span>
        </div>
    </div>

    <div class="parser-progress-block parser-progress-block-slow">
        <div class="parser-progress-heading">
            <div>
                <span>{{ $ru ? 'Сторонняя проверка отложенных товаров' : 'Verificarea externa a produselor amanate' }}</span>
                <strong>{{ $filterCounts['external_percent'] }}%</strong>
            </div>
            <small>
                {{ $ru ? 'Проверено' : 'Verificate' }}:
                {{ $filterCounts['external_completed'] }} {{ $ru ? 'из' : 'din' }} {{ $filterCounts['external_total'] }}
                · {{ $ru ? 'В очереди' : 'In asteptare' }}: {{ $filterCounts['external_pending'] }}
            </small>
        </div>
        <div
            class="parser-progress-track parser-progress-track-slow"
            role="progressbar"
            aria-label="{{ $ru ? 'Сторонняя проверка' : 'Verificare externa' }}"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $filterCounts['external_percent'] }}"
        >
            <span style="width: {{ $filterCounts['external_percent'] }}%"></span>
        </div>
        <div class="parser-progress-scale" aria-hidden="true">
            <span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span>
        </div>
    </div>
</section>

@if(!$canStartImport)
<section class="shell parser-bulk-panel">
    <div class="admin-panel-head">
        <span>{{ $ru ? 'Быстрый путь для больших прайсов' : 'Flux rapid pentru liste mari' }}</span>
        <h2>{{ $ru ? 'Массовая обработка без просмотра каждого товара' : 'Procesare in masa fara verificare pe fiecare produs' }}</h2>
    </div>
    <div class="parser-bulk-grid">
        <form method="post" action="{{ route('admin.parser.batches.bulk-action', $batch) }}">
            @csrf
            <input type="hidden" name="action" value="create_safe_drafts">
            <strong>{{ $bulkStats['safe_new'] }}</strong>
            <span>{{ $ru ? 'новых строк готовы к draft' : 'randuri noi gata pentru draft' }}</span>
            <label>{{ $ru ? 'Лимит' : 'Limita' }}<input type="number" name="limit" value="20000" min="1" max="20000"></label>
            <button class="btn small" @disabled($bulkStats['safe_new'] === 0)>{{ $ru ? 'Создать draft массово' : 'Creeaza drafturi in masa' }}</button>
        </form>
        <form method="post" action="{{ route('admin.parser.batches.bulk-action', $batch) }}">
            @csrf
            <input type="hidden" name="action" value="publish_drafts">
            <strong>{{ $bulkStats['drafts'] }}</strong>
            <span>{{ $ru ? 'черновиков на проверку и публикацию' : 'drafturi pentru verificare si publicare' }}</span>
            <label>{{ $ru ? 'Лимит' : 'Limita' }}<input type="number" name="limit" value="20000" min="1" max="20000"></label>
            <button class="btn small" @disabled($bulkStats['drafts'] === 0)>{{ $ru ? 'Опубликовать массово' : 'Publica in masa' }}</button>
        </form>
        <form method="post" action="{{ route('admin.parser.batches.bulk-action', $batch) }}">
            @csrf
            <input type="hidden" name="action" value="update_existing_price_stock">
            <strong>{{ $bulkStats['existing'] }}</strong>
            <span>{{ $ru ? 'существующих SKU можно обновить' : 'SKU existente pot fi actualizate' }}</span>
            <label>{{ $ru ? 'Лимит' : 'Limita' }}<input type="number" name="limit" value="20000" min="1" max="20000"></label>
            <button class="btn outline small" @disabled($bulkStats['existing'] === 0)>{{ $ru ? 'Обновить цену и остаток' : 'Actualizeaza pret si stoc' }}</button>
        </form>
        <a class="parser-bulk-exceptions" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'exceptions' => 1]) }}">
            <strong>{{ $bulkStats['exceptions'] }}</strong>
            <span>{{ $ru ? 'окончательных исключений после автопроверки' : 'exceptii finale dupa verificarea automata' }}</span>
        </a>
    </div>
</section>
@endif

@if($canStartImport)
<section class="shell panel parser-warning parser-import-ready">
    <div>
        <strong>{{ $batch->status === 'cancelled' ? ($ru ? 'Импорт остановлен' : 'Import oprit') : ($ru ? 'Dry-run завершен' : 'Dry-run finalizat') }}</strong>
        <span>{{ $batch->status === 'cancelled'
            ? ($ru ? 'Найденные изображения и созданные черновики сохранены. Повторный запуск проверит прайс заново и продолжит подготовку.' : 'Imaginile gasite si drafturile create sunt pastrate. Repornirea verifica din nou lista si continua pregatirea.')
            : ($ru ? 'Товары и изображения еще не создавались. Следующий этап найдет фотографии, обработает их и подготовит черновики.' : 'Produsele si imaginile nu au fost create. Urmatoarea etapa cauta fotografii, le proceseaza si pregateste drafturile.') }}</span>
    </div>
    <form method="post" action="{{ route('admin.parser.batches.run-import', $batch) }}" class="parser-run-form">
        @csrf
        <input type="hidden" name="import_mode" value="create_drafts">
        <label>{{ $ru ? 'Тестовый лимит' : 'Limita test' }}
            <input type="number" name="row_limit" min="1" max="5000" placeholder="{{ $ru ? 'пусто = весь прайс' : 'gol = tot fisierul' }}">
        </label>
        <button class="btn" type="submit">{{ $batch->status === 'cancelled' ? ($ru ? 'Продолжить поиск изображений' : 'Continua cautarea imaginilor') : ($ru ? 'Запустить импорт и поиск изображений' : 'Porneste importul si cautarea imaginilor') }}</button>
    </form>
</section>
@endif

<section class="shell parser-toolbar panel parser-card">
    <div class="parser-filters">
        <a class="{{ $activeFilter === '' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', $batch) }}">{{ __('ui.all') }} <span>{{ $filterCounts['all'] }}</span></a>
        <a class="{{ $activeFilter === 'tristool_queue' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'tristool_queue']) }}">{{ $ru ? 'Очередь TrisTool' : 'Coada TrisTool' }} <span>{{ $filterCounts['fast_pending'] }}</span></a>
        <a class="{{ $activeFilter === 'tristool_found' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'tristool_found']) }}">{{ $ru ? 'Найдено TrisTool' : 'Gasite TrisTool' }} <span>{{ $filterCounts['tristool_ready'] }}</span></a>
        <a class="{{ $activeFilter === 'external_check' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'external_check']) }}">{{ $ru ? 'Сторонняя проверка' : 'Verificare externa' }} <span>{{ $filterCounts['external_total'] }}</span></a>
        <a class="{{ $activeFilter === 'processing_auto' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'processing_auto']) }}">{{ $ru ? 'Сейчас обрабатывается' : 'Se proceseaza acum' }} <span>{{ $filterCounts['processing'] }}</span></a>
        <a class="{{ $activeFilter === 'ready_for_review' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'ready_for_review']) }}">{{ __('ui.parser_ready') }} <span>{{ $filterCounts['ready_for_review'] }}</span></a>
        <a class="{{ $activeFilter === 'dry_run_ready' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'dry_run_ready']) }}">{{ $ru ? 'Dry-run готово' : 'Dry-run gata' }} <span>{{ $filterCounts['dry_run_ready'] }}</span></a>
        <a class="{{ $activeFilter === 'existing_product_found' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'existing_product_found']) }}">{{ $ru ? 'Существующие' : 'Existente' }} <span>{{ $filterCounts['existing_product_found'] }}</span></a>
        <a class="{{ $activeFilter === 'needs_category' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'needs_category' => 1]) }}">{{ $ru ? 'Нужна категория' : 'Necesita categorie' }} <span>{{ $filterCounts['needs_category'] }}</span></a>
        <a class="{{ $activeFilter === 'no_images' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'no_images' => 1]) }}">{{ $ru ? 'Нет фото' : 'Fara imagini' }} <span>{{ $filterCounts['no_images'] }}</span></a>
        <a class="{{ $activeFilter === 'failed' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'failed']) }}">{{ __('ui.parser_failed') }} <span>{{ $filterCounts['failed'] }}</span></a>
    </div>
    <div class="parser-actions">
        @if($filterCounts['deferred_retryable'] > 0)
            <form method="post" action="{{ route('admin.parser.batches.retry-deferred', $batch) }}">
                @csrf
                <input type="hidden" name="mode" value="tristool">
                <button class="btn outline small">{{ $ru ? 'Перепроверить TrisTool' : 'Reverifica TrisTool' }}</button>
            </form>
            <form method="post" action="{{ route('admin.parser.batches.retry-deferred', $batch) }}">
                @csrf
                <input type="hidden" name="mode" value="external">
                <button class="btn outline small">{{ $ru ? 'Повторить все источники' : 'Repeta toate sursele' }}</button>
            </form>
        @endif
        @if($autoRefresh)
            <form method="post" action="{{ route('admin.parser.batches.cancel', $batch) }}" onsubmit="return confirm('{{ $ru ? 'Остановить текущую обработку?' : 'Opriti procesarea curenta?' }}')">@csrf<button class="btn outline small">{{ __('ui.parser_cancel_batch') }}</button></form>
        @endif
        @if(!$autoRefresh)
            <form method="post" action="{{ route('admin.parser.batches.destroy', $batch) }}" onsubmit="return confirm('{{ __('ui.parser_delete_confirm') }}')">@csrf @method('DELETE')<button class="delete">{{ __('ui.parser_delete_report') }}</button></form>
        @endif
    </div>
</section>

<section class="shell panel parser-card parser-table-panel">
    <div class="parser-table-wrap">
        <table class="parser-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>{{ __('ui.brand') }}</th>
                    <th>{{ $ru ? 'Название' : 'Denumire' }}</th>
                    <th>{{ $ru ? 'Цена' : 'Pret' }}</th>
                    <th>{{ $ru ? 'Остаток' : 'Stoc' }}</th>
                    <th>{{ $ru ? 'Авто' : 'Auto' }}</th>
                    <th>{{ __('ui.category') }}</th>
                    <th>{{ __('ui.status') }}</th>
                    <th>{{ __('ui.parser_confidence') }}</th>
                    <th>{{ __('ui.parser_photo_count') }}</th>
                    <th>{{ __('ui.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->row_number ?: $item->id }}</td>
                        <td><strong>{{ $item->sku }}</strong></td>
                        <td>{{ $item->brand ?: 'Auto' }}</td>
                        <td>{{ $item->name_ru ?: $item->parsed_name ?: $item->found_title }}</td>
                        <td>{{ $item->parsed_price ?? '-' }}</td>
                        <td>{{ $item->parsed_stock ?? '-' }}</td>
                        <td>{{ $item->vehicle_application ?: '-' }}</td>
                        <td>{{ $item->detected_category_path ?: $item->category?->display_name ?: '-' }}</td>
                        <td><span class="parser-status parser-status-{{ $item->status }}">{{ $item->status }}</span></td>
                        <td>{{ $item->category_confidence_score ?? $item->confidence_score ?? '-' }}%</td>
                        <td>{{ $item->imageAssets->count() }}</td>
                        <td><a class="btn small" href="{{ route('admin.parser.items.show', $item) }}">{{ __('ui.open') }} <span aria-hidden="true">→</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="12">{{ __('ui.collection_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</section>

@if($batch->log_json)
<section class="shell">
    <details class="panel parser-card parser-log parser-collapsible">
        <summary>
            <span>{{ __('ui.parser_logs') }}</span>
            <strong>{{ $ru ? 'Технический журнал' : 'Jurnal tehnic' }}</strong>
        </summary>
        @foreach(array_reverse($batch->log_json) as $log)
            <p><strong>{{ $log['at'] ?? '' }}</strong> {{ $log['message'] ?? '' }} @if(!empty($log['context']))<small>{{ json_encode($log['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</small>@endif</p>
        @endforeach
    </details>
</section>
@endif
@if($autoRefresh)
<script>
    window.setTimeout(() => window.location.reload(), 10000);
</script>
@endif
@endsection
