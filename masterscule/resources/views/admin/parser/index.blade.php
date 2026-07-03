@extends('layouts.app')

@section('content')
<section class="shell page-title">
    <p>{{ __('ui.admin') }} / {{ __('ui.parser_products') }}</p>
    <h1>{{ __('ui.parser_products') }}</h1>
    <span>{{ __('ui.parser_intro') }}</span>
</section>

<section class="shell parser-warning">
    <strong>{{ __('ui.parser_safety_title') }}</strong>
    <span>{{ __('ui.parser_safety_text') }}</span>
</section>

<section class="shell parser-grid">
    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_single') }}</span>
            <h2>{{ __('ui.parser_find_one') }}</h2>
        </div>
        <form method="post" action="{{ route('admin.parser.single') }}" class="admin-product-form">
            @csrf
            <label>SKU
                <input name="sku" required placeholder="7596MR">
            </label>
            <div class="admin-two-cols">
                <label>{{ __('ui.brand') }}
                    <select name="brand">
                        <option value="auto">Auto</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->name }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>{{ __('ui.category') }}
                    <select name="category_id">
                        <option value="">{{ __('ui.all_categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->display_name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="admin-two-cols">
                <label>{{ __('ui.parser_language') }}
                    <select name="language">
                        <option value="auto">AUTO</option>
                        <option value="ru">RU</option>
                        <option value="ro">RO</option>
                        <option value="en">EN</option>
                    </select>
                </label>
                <label>{{ __('ui.parser_photo_count') }}
                    <input type="number" name="image_limit" min="1" max="4" value="{{ $settings['max_images_per_product'] ?? 4 }}">
                </label>
            </div>
            <button class="btn" type="submit">{{ __('ui.parser_find_product') }}</button>
        </form>
    </article>

    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_batch') }}</span>
            <h2>{{ __('ui.parser_batch_import') }}</h2>
        </div>
        <form method="post" action="{{ route('admin.parser.batch') }}" enctype="multipart/form-data" class="admin-product-form">
            @csrf
            <label>{{ __('ui.parser_batch_title') }}
                <input name="title" placeholder="{{ __('ui.parser_batch_title_placeholder') }}">
            </label>
            <label>{{ __('ui.parser_sku_list') }}
                <textarea name="sku_text" placeholder="7596MR&#10;NC-4233&#10;34262-1DG"></textarea>
            </label>
            <label>{{ __('ui.parser_upload_file') }}
                <input type="file" name="sku_file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
            </label>
            <div class="admin-two-cols">
                <label>{{ __('ui.brand') }}
                    <select name="brand">
                        <option value="auto">Auto</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->name }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>{{ __('ui.parser_mode') }}
                    <select name="mode">
                        <option value="find_only">{{ __('ui.parser_mode_find') }}</option>
                        <option value="find_prepare_photos">{{ __('ui.parser_mode_photos') }}</option>
                        <option value="create_drafts">{{ __('ui.parser_mode_drafts') }}</option>
                    </select>
                </label>
            </div>
            <input type="hidden" name="language" value="auto">
            <button class="btn" type="submit">{{ __('ui.parser_start_batch') }}</button>
        </form>
    </article>
</section>

<section class="shell parser-grid parser-grid-wide">
    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_history') }}</span>
            <h2>{{ __('ui.parser_task_history') }}</h2>
        </div>
        <div class="parser-table-wrap">
            <table class="parser-table">
                <thead>
                    <tr>
                        <th>{{ __('ui.date') }}</th>
                        <th>{{ __('ui.parser_batch_title') }}</th>
                        <th>SKU</th>
                        <th>{{ __('ui.status') }}</th>
                        <th>{{ __('ui.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->created_at->format('d.m.Y H:i') }}</td>
                            <td>{{ $batch->title }}</td>
                            <td>{{ $batch->items_count }} / {{ $batch->sku_count }}</td>
                            <td><span class="parser-status parser-status-{{ $batch->status }}">{{ $batch->status }}</span></td>
                            <td><a class="btn small" href="{{ route('admin.parser.batches.show', $batch) }}">{{ __('ui.open') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('ui.parser_no_batches') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $batches->links() }}
    </article>

    <article class="panel parser-card">
        <div class="admin-panel-head">
            <span>{{ __('ui.parser_settings') }}</span>
            <h2>{{ __('ui.parser_settings_title') }}</h2>
        </div>
        <form method="post" action="{{ route('admin.parser.settings.update') }}" class="admin-product-form">
            @csrf
            <label><input type="checkbox" name="enabled" value="1" @checked($settings['enabled'] ?? true)> {{ __('ui.parser_enabled') }}</label>
            <div class="admin-two-cols">
                <label>{{ __('ui.parser_max_sku') }}<input type="number" name="max_sku_per_batch" value="{{ $settings['max_sku_per_batch'] ?? 100 }}" min="1" max="500"></label>
                <label>{{ __('ui.parser_max_images') }}<input type="number" name="max_images_per_product" value="{{ $settings['max_images_per_product'] ?? 4 }}" min="1" max="4"></label>
            </div>
            <div class="admin-three-cols">
                <label>{{ __('ui.parser_min_confidence') }}<input type="number" name="min_confidence_score" value="{{ $settings['min_confidence_score'] ?? 70 }}" min="0" max="100"></label>
                <label>{{ __('ui.parser_image_size') }}<input type="number" name="image_size" value="{{ $settings['image_size'] ?? 1200 }}" min="600" max="2000"></label>
                <label>{{ __('ui.parser_thumb_size') }}<input type="number" name="thumb_size" value="{{ $settings['thumb_size'] ?? 300 }}" min="150" max="800"></label>
            </div>
            <label>{{ __('ui.parser_webp_quality') }}<input type="number" name="webp_quality" value="{{ $settings['webp_quality'] ?? 88 }}" min="70" max="95"></label>
            <label>{{ __('ui.parser_allowed_domains') }}<textarea name="allowed_domains">{{ implode("\n", $settings['allowed_domains'] ?? []) }}</textarea></label>
            <label>{{ __('ui.parser_blocked_domains') }}<textarea name="blocked_domains">{{ implode("\n", $settings['blocked_domains'] ?? []) }}</textarea></label>
            <details class="admin-details" open>
                <summary>{{ __('ui.parser_watermark') }}</summary>
                <label><input type="checkbox" name="watermark_enabled" value="1" @checked($settings['watermark']['enabled'] ?? true)> {{ __('ui.parser_watermark_enabled') }}</label>
                <label>{{ __('ui.parser_watermark_file') }}<input name="watermark_file" value="{{ $settings['watermark']['file'] ?? '/images/brand/master-scule-logo.png' }}"></label>
                <div class="admin-three-cols">
                    <label>{{ __('ui.parser_watermark_position') }}
                        <select name="watermark_position">
                            @foreach(['bottom_right', 'bottom_left', 'center'] as $position)
                                <option value="{{ $position }}" @selected(($settings['watermark']['position'] ?? 'bottom_right') === $position)>{{ $position }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>{{ __('ui.parser_watermark_opacity') }}<input type="number" name="watermark_opacity" value="{{ $settings['watermark']['opacity'] ?? 14 }}" min="8" max="20"></label>
                    <label>{{ __('ui.parser_watermark_size') }}<input type="number" name="watermark_size_percent" value="{{ $settings['watermark']['size_percent'] ?? 18 }}" min="12" max="25"></label>
                </div>
            </details>
            <button class="btn" type="submit">{{ __('ui.save_changes') }}</button>
        </form>
    </article>
</section>
@endsection
