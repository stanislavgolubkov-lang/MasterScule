@extends('layouts.app')

@section('content')
<section class="shell page-title">
    <p>{{ __('ui.admin') }} / <a href="{{ route('admin.parser.index') }}">{{ __('ui.parser_products') }}</a> / <a href="{{ route('admin.parser.batches.show', $item->batch) }}">{{ $item->batch->title }}</a></p>
    <h1>SKU {{ $item->sku }}</h1>
    <span>{{ __('ui.status') }}: {{ $item->status }} / {{ __('ui.parser_confidence') }}: {{ $item->confidence_score ? $item->confidence_score.'%' : '-' }}</span>
</section>

@if($errors->any())
    <div class="shell notice error">{{ $errors->first() }}</div>
@endif

<section class="shell parser-review-grid">
    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_product_data') }}</span>
            <h2>{{ $item->found_title ?: __('ui.parser_not_found') }}</h2>
        </div>
        <p>{{ $item->found_description ?: $item->error_message }}</p>
        <dl class="parser-specs">
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
        <span>{{ __('ui.parser_images') }}</span>
        <h2>{{ __('ui.parser_select_images') }}</h2>
    </div>
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
            <form method="post" action="{{ route('admin.parser.items.update-existing', $item) }}" class="admin-product-form">
                @csrf
                <label>{{ __('ui.parser_update_action') }}
                    <select name="action">
                        <option value="add_photos">{{ __('ui.parser_add_photos') }}</option>
                        <option value="update_description">{{ __('ui.parser_update_description') }}</option>
                        <option value="replace_photos">{{ __('ui.parser_replace_photos') }}</option>
                    </select>
                </label>
                <label><input type="checkbox" name="replace_confirmed" value="1"> {{ __('ui.parser_replace_confirm') }}</label>
                <button class="btn" type="submit">{{ __('ui.parser_update_existing') }}</button>
            </form>
        @else
            <form method="post" action="{{ route('admin.parser.items.draft', $item) }}">
                @csrf
                <button class="btn" type="submit" @disabled($item->status === 'not_found' || $item->status === 'failed')>{{ __('ui.parser_create_draft') }}</button>
            </form>
            <p>{{ __('ui.parser_draft_note') }}</p>
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
