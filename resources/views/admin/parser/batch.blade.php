@extends('layouts.app')

@section('content')
@php($ru = app()->isLocale('ru'))
@php($activeFilter = request('status') ?: (request('needs_category') ? 'needs_category' : (request('no_images') ? 'no_images' : '')))
<section class="shell page-title">
    <p>{{ __('ui.admin') }} / <a href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a></p>
    <h1>{{ $batch->file_name ?: $batch->title }}</h1>
    <span>{{ __('ui.status') }}: {{ $batch->status }} / {{ $ru ? 'товарных строк' : 'randuri produse' }}: {{ $batch->product_rows ?: $batch->sku_count }}</span>
</section>

<section class="shell stats parser-stats">
    <div><strong>{{ $batch->total_rows }}</strong><span>{{ $ru ? 'строк всего' : 'randuri total' }}</span></div>
    <div><strong>{{ $batch->product_rows ?: $batch->sku_count }}</strong><span>{{ $ru ? 'товаров' : 'produse' }}</span></div>
    <div><strong>{{ $batch->created_drafts }}</strong><span>{{ $ru ? 'draft создано' : 'drafturi create' }}</span></div>
    <div><strong>{{ $batch->updated_existing }}</strong><span>{{ $ru ? 'существующих SKU' : 'SKU existente' }}</span></div>
    <div><strong>{{ $batch->error_rows }}</strong><span>{{ $ru ? 'ошибок' : 'erori' }}</span></div>
</section>

<section class="shell parser-toolbar panel">
    <div class="parser-filters">
        <a class="{{ $activeFilter === '' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', $batch) }}">{{ __('ui.all') }}</a>
        <a class="{{ $activeFilter === 'ready_for_review' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'ready_for_review']) }}">{{ __('ui.parser_ready') }}</a>
        <a class="{{ $activeFilter === 'existing_product_found' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'existing_product_found']) }}">{{ $ru ? 'Существующие' : 'Existente' }}</a>
        <a class="{{ $activeFilter === 'needs_category' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'needs_category' => 1]) }}">{{ $ru ? 'Нужна категория' : 'Necesita categorie' }}</a>
        <a class="{{ $activeFilter === 'no_images' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'no_images' => 1]) }}">{{ $ru ? 'Нет фото' : 'Fara imagini' }}</a>
        <a class="{{ $activeFilter === 'failed' ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'failed']) }}">{{ __('ui.parser_failed') }}</a>
    </div>
    <div class="parser-actions">
        <form method="post" action="{{ route('admin.parser.batches.cancel', $batch) }}">@csrf<button class="btn outline small">{{ __('ui.parser_cancel_batch') }}</button></form>
        <form method="post" action="{{ route('admin.parser.batches.destroy', $batch) }}" onsubmit="return confirm('{{ __('ui.parser_delete_confirm') }}')">@csrf @method('DELETE')<button class="delete">{{ __('ui.parser_delete_report') }}</button></form>
    </div>
</section>

<section class="shell panel">
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
                        <td>{{ $item->detected_category_path ?: $item->category?->display_name ?: '-' }}</td>
                        <td><span class="parser-status parser-status-{{ $item->status }}">{{ $item->status }}</span></td>
                        <td>{{ $item->category_confidence_score ?? $item->confidence_score ?? '-' }}%</td>
                        <td>{{ $item->imageAssets->count() }}</td>
                        <td><a class="btn small" href="{{ route('admin.parser.items.show', $item) }}">{{ __('ui.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="11">{{ __('ui.collection_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $items->links() }}
</section>

@if($batch->log_json)
<section class="shell panel parser-log">
    <h2>{{ __('ui.parser_logs') }}</h2>
    @foreach(array_reverse($batch->log_json) as $log)
        <p><strong>{{ $log['at'] ?? '' }}</strong> {{ $log['message'] ?? '' }} @if(!empty($log['context']))<small>{{ json_encode($log['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</small>@endif</p>
    @endforeach
</section>
@endif
@endsection
