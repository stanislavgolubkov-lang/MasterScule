@extends('layouts.app')

@section('content')
<section class="shell page-title">
    <p>{{ __('ui.admin') }} / <a href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a></p>
    <h1>{{ $batch->title }}</h1>
    <span>{{ __('ui.status') }}: {{ $batch->status }} / SKU: {{ $batch->sku_count }}</span>
</section>

<section class="shell parser-toolbar panel">
    <div class="parser-filters">
        @foreach(['' => __('ui.all'), 'ready_for_review' => __('ui.parser_ready'), 'not_found' => __('ui.parser_not_found'), 'failed' => __('ui.parser_failed'), 'approved' => __('ui.parser_approved')] as $status => $label)
            <a class="{{ request('status') === $status ? 'active' : '' }}" href="{{ route('admin.parser.batches.show', $batch) }}{{ $status ? '?status='.$status : '' }}">{{ $label }}</a>
        @endforeach
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
                    <th>SKU</th>
                    <th>{{ __('ui.brand') }}</th>
                    <th>{{ __('ui.status') }}</th>
                    <th>{{ __('ui.parser_confidence') }}</th>
                    <th>{{ __('ui.parser_photo_count') }}</th>
                    <th>{{ __('ui.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td><strong>{{ $item->sku }}</strong></td>
                        <td>{{ $item->brand ?: 'Auto' }}</td>
                        <td><span class="parser-status parser-status-{{ $item->status }}">{{ $item->status }}</span></td>
                        <td>{{ $item->confidence_score ? $item->confidence_score.'%' : '-' }}</td>
                        <td>{{ $item->imageAssets->count() }}</td>
                        <td><a class="btn small" href="{{ route('admin.parser.items.show', $item) }}">{{ __('ui.open') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ __('ui.collection_empty') }}</td></tr>
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
        <p><strong>{{ $log['at'] ?? '' }}</strong> {{ $log['message'] ?? '' }}</p>
    @endforeach
</section>
@endif
@endsection
