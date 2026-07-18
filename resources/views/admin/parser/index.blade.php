@extends('layouts.admin')

@section('content')
@php
    $ru = app()->isLocale('ru');
    $activeBatches = $priceBatches->whereIn('status', ['pending', 'running', 'dry_run_completed'])->count();
    $draftCount = $draftItems->count();
@endphp

<section class="shell page-title">
    <p>{{ __('ui.admin') }} / {{ __('ui.parser_products') }}</p>
    <h1>{{ __('ui.parser_products') }}</h1>
    <span>{{ $ru ? 'Загрузите прайс, проверьте отчет и создайте черновики без лишних ручных шагов.' : 'Incarcati lista, verificati raportul si creati drafturi fara pasi inutili.' }}</span>
</section>

<section class="shell parser-tabs">
    <a class="active" href="{{ route('admin.parser.index') }}">{{ $ru ? 'Импорт прайса' : 'Import lista' }}</a>
    <a href="{{ route('admin.parser.drafts') }}">{{ $ru ? 'Черновики' : 'Drafturi' }}</a>
    <a href="{{ route('admin.parser.rules') }}">{{ $ru ? 'Правила категорий' : 'Reguli categorii' }}</a>
    <a href="#parser-settings">{{ $ru ? 'Настройки' : 'Setari' }}</a>
</section>

<section class="shell parser-console">
    <div class="parser-console-metrics">
        <span><strong>{{ $activeBatches }}</strong>{{ $ru ? 'в работе' : 'active' }}</span>
        <span><strong>{{ $draftCount }}</strong>{{ $ru ? 'draft' : 'drafturi' }}</span>
        <span><strong>{{ $priceBatches->count() }}</strong>{{ $ru ? 'последних импортов' : 'importuri recente' }}</span>
    </div>
</section>

<section class="shell parser-warning">
    <strong>{{ __('ui.parser_safety_title') }}</strong>
    <span>{{ __('ui.parser_safety_text') }} {{ $ru ? 'Перед публикацией проверьте право использования изображений и описаний.' : 'Inainte de publicare verificati dreptul de utilizare pentru imagini si descrieri.' }}</span>
</section>

<section class="shell parser-grid parser-grid-wide">
    <article class="panel parser-card parser-card-primary parser-import-card">
        <div class="admin-panel-head">
            <span>{{ $ru ? 'Основной сценарий' : 'Scenariu principal' }}</span>
            <h2>{{ $ru ? 'Импорт прайс-листа поставщика' : 'Import lista de preturi furnizor' }}</h2>
        </div>
        <form method="post" action="{{ route('admin.parser.price-list') }}" enctype="multipart/form-data" class="admin-product-form">
            @csrf
            <div class="admin-two-cols">
                <label>{{ $ru ? 'Поставщик / источник' : 'Furnizor / sursa' }}
                    <input name="supplier_name" value="{{ old('supplier_name') }}" placeholder="Tristool, King Tony, M7">
                </label>
                <label>{{ $ru ? 'Файл прайса (.xls, .xlsx, .csv)' : 'Fisier lista (.xls, .xlsx, .csv)' }}
                    <input type="file" name="price_file" required accept=".xls,.xlsx,.csv,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                </label>
            </div>
            <div class="admin-two-cols">
                <label>{{ $ru ? 'Бренд по умолчанию' : 'Brand implicit' }}
                    <select name="brand_default">
                        <option value="auto">Auto</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->name }}" @selected(old('brand_default') === $brand->name)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>{{ $ru ? 'Категория по умолчанию' : 'Categorie implicita' }}
                    <select name="category_default_id">
                        <option value="">{{ __('ui.all_categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_default_id') === (string) $category->id)>{{ $category->display_name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="admin-two-cols">
                <label>{{ $ru ? 'Тип цены' : 'Tip pret' }}
                    <select name="price_type">
                        <option value="retail_price">{{ $ru ? 'Отпускная цена = retail price' : 'Pret furnizor = retail price' }}</option>
                    </select>
                </label>
                <label>{{ $ru ? 'Режим импорта' : 'Mod import' }}
                    <select name="import_mode">
                        <option value="dry_run">{{ $ru ? 'Dry-run: сначала отчет, без товаров' : 'Dry-run: raport intai, fara produse' }}</option>
                        <option value="create_drafts">{{ $ru ? 'После dry-run создать черновики автоматически' : 'Dupa dry-run creeaza drafturi automat' }}</option>
                        <option value="review_only">{{ $ru ? 'Только анализ и проверка' : 'Doar analiza si verificare' }}</option>
                    </select>
                </label>
            </div>

            <details class="parser-advanced-options">
                <summary>{{ $ru ? 'Дополнительные правила импорта' : 'Reguli suplimentare import' }}</summary>
                <div class="parser-check-grid">
                    <label><input type="checkbox" name="search_images" value="1" checked> {{ $ru ? 'Искать изображения' : 'Cauta imagini' }}</label>
                    <label><input type="checkbox" name="translate_descriptions" value="1" checked> {{ $ru ? 'Готовить RU/RO описания' : 'Pregateste descrieri RU/RO' }}</label>
                    <label><input type="checkbox" name="create_drafts_automatically" value="1" checked> {{ $ru ? 'Создавать draft при уверенной категории' : 'Creeaza draft daca categoria este sigura' }}</label>
                    <label><input type="checkbox" name="add_photos_to_existing" value="1" checked> {{ $ru ? 'Готовить фото для существующих SKU' : 'Pregateste imagini pentru SKU existente' }}</label>
                    <label><input type="checkbox" name="update_existing_products" value="1"> {{ $ru ? 'Не менять существующие товары без ручного действия' : 'Nu modifica produse existente fara actiune manuala' }}</label>
                    <label><input type="checkbox" name="replace_existing_photos" value="1"> {{ $ru ? 'Разрешить замену фото только после подтверждения' : 'Permite inlocuirea fotografiilor doar dupa confirmare' }}</label>
                </div>
            </details>

            <button class="btn" type="submit">{{ $ru ? 'Запустить проверку прайса' : 'Porneste verificarea listei' }}</button>
        </form>
    </article>

    <aside class="parser-side-stack">
        <article class="panel parser-card">
            <div class="admin-panel-head">
                <span>{{ $ru ? 'Последние draft' : 'Ultimele drafturi' }}</span>
                <h2>{{ $ru ? 'Готово к ручной проверке' : 'Gata pentru verificare' }}</h2>
            </div>
            <div class="parser-mini-list">
                @forelse($draftItems as $item)
                    <a href="{{ route('admin.parser.items.show', $item) }}">
                        <strong>{{ $item->sku }} · {{ $item->brand ?: 'Auto' }}</strong>
                        <span>{{ $item->name_ru ?: $item->found_title }}</span>
                        <small>{{ $item->createdProduct?->status }} / {{ $item->category?->display_name }}</small>
                    </a>
                @empty
                    <p>{{ $ru ? 'Черновиков из прайсов пока нет.' : 'Nu exista drafturi din importuri.' }}</p>
                @endforelse
            </div>
        </article>
    </aside>
</section>

<section class="shell parser-utility-grid">
    <details class="panel parser-card parser-collapsible">
        <summary>
            <span>{{ __('ui.parser_single') }}</span>
            <strong>{{ __('ui.parser_find_one') }}</strong>
        </summary>
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
    </details>

    <details class="panel parser-card parser-collapsible">
        <summary>
            <span>{{ __('ui.parser_batch') }}</span>
            <strong>{{ __('ui.parser_batch_import') }}</strong>
        </summary>
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
    </details>
</section>

<section class="shell panel parser-card parser-history-panel" id="parser-history">
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
                        <th>{{ $ru ? 'Новые / существующие' : 'Noi / existente' }}</th>
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
                            <td>{{ $batch->new_sku_count }} / {{ $batch->existing_sku_count }}</td>
                            <td><span class="parser-status parser-status-{{ $batch->status }}">{{ $batch->status }}</span></td>
                            <td><a class="btn small" href="{{ route('admin.parser.batches.show', $batch) }}">{{ __('ui.open') }} <span aria-hidden="true">→</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">{{ __('ui.parser_no_batches') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $batches->links() }}
</section>

<section class="shell">
    <details class="panel parser-card parser-collapsible parser-settings-panel" id="parser-settings">
        <summary>
            <span>{{ __('ui.parser_settings') }}</span>
            <strong>{{ __('ui.parser_settings_title') }}</strong>
        </summary>
        <form method="post" action="{{ route('admin.parser.settings.update') }}" class="admin-product-form">
            @csrf
            <label><input type="checkbox" name="enabled" value="1" @checked($settings['enabled'] ?? true)> {{ __('ui.parser_enabled') }}</label>
            <div class="admin-two-cols">
                <label>{{ __('ui.parser_max_sku') }}<input type="number" name="max_sku_per_batch" value="{{ $settings['max_sku_per_batch'] ?? 20000 }}" min="1" max="20000"></label>
                <label>{{ $ru ? 'Максимальный размер файла, KB' : 'Dimensiune maxima fisier, KB' }}<input type="number" name="max_file_size_kb" value="{{ $settings['max_file_size_kb'] ?? 20480 }}" min="512" max="51200"></label>
            </div>
            <div class="admin-two-cols">
                <label>{{ __('ui.parser_max_images') }}<input type="number" name="max_images_per_product" value="{{ $settings['max_images_per_product'] ?? 4 }}" min="1" max="4"></label>
                <label>Preview<input type="number" name="preview_size" value="{{ $settings['preview_size'] ?? 600 }}" min="300" max="1200"></label>
            </div>
            <div class="admin-three-cols">
                <label>{{ __('ui.parser_min_confidence') }}<input type="number" name="min_confidence_score" value="{{ max(90, (int) ($settings['min_confidence_score'] ?? 90)) }}" min="90" max="100"></label>
                <label>{{ __('ui.parser_image_size') }}<input type="number" name="image_size" value="{{ $settings['image_size'] ?? 1200 }}" min="600" max="2000"></label>
                <label>{{ __('ui.parser_thumb_size') }}<input type="number" name="thumb_size" value="{{ $settings['thumb_size'] ?? 300 }}" min="150" max="800"></label>
            </div>
            <label>{{ __('ui.parser_webp_quality') }}<input type="number" name="webp_quality" value="{{ $settings['webp_quality'] ?? 88 }}" min="70" max="95"></label>
            <div class="parser-check-grid">
                <label><input type="checkbox" name="official_sources_enabled" value="1" @checked($settings['official_sources_enabled'] ?? true)> {{ $ru ? 'Официальные источники включены' : 'Surse oficiale active' }}</label>
                <label><input type="checkbox" name="tristools_fallback_enabled" value="1" @checked($settings['tristools_fallback_enabled'] ?? false)> {{ $ru ? 'Использовать TrisTool только если официальный источник неполный' : 'Foloseste TrisTool doar daca sursa oficiala este incompleta' }}</label>
                <label><input type="checkbox" name="auto_approve_exact_fallback" value="1" @checked($settings['auto_approve_exact_fallback'] ?? false)> {{ $ru ? 'Автопроверка точного fallback по SKU' : 'Aproba automat fallback exact dupa SKU' }}</label>
                <label><input type="checkbox" name="allow_marketplace_sources" value="1" @checked($settings['allow_marketplace_sources'] ?? false)> {{ $ru ? 'Разрешить marketplace-источники' : 'Permite surse marketplace' }}</label>
                <label><input type="checkbox" name="search_images" value="1" @checked($settings['search_images'] ?? true)> {{ $ru ? 'Искать изображения' : 'Cauta imagini' }}</label>
                <label><input type="checkbox" name="translate_descriptions" value="1" @checked($settings['translate_descriptions'] ?? true)> {{ $ru ? 'Готовить RU/RO описания' : 'Pregateste descrieri RU/RO' }}</label>
                <label><input type="checkbox" name="create_drafts_automatically" value="1" @checked($settings['create_drafts_automatically'] ?? true)> {{ $ru ? 'Создавать draft автоматически' : 'Creeaza draft automat' }}</label>
                <label><input type="checkbox" name="update_existing_prices" value="1" @checked($settings['update_existing_prices'] ?? false)> {{ $ru ? 'Разрешить ручное обновление цен' : 'Permite actualizare manuala preturi' }}</label>
                <label><input type="checkbox" name="update_existing_stock" value="1" @checked($settings['update_existing_stock'] ?? false)> {{ $ru ? 'Разрешить ручное обновление остатков' : 'Permite actualizare manuala stoc' }}</label>
            </div>
            <div class="admin-three-cols">
                <label>{{ $ru ? 'Минимум official confidence' : 'Incredere oficiala minima' }}<input type="number" name="min_official_confidence" value="{{ $settings['min_official_confidence'] ?? 90 }}" min="70" max="100"></label>
                <label>{{ $ru ? 'Минимум fallback confidence' : 'Incredere fallback minima' }}<input type="number" name="min_fallback_confidence" value="{{ $settings['min_fallback_confidence'] ?? 80 }}" min="70" max="100"></label>
                <label>{{ $ru ? 'Фото для ready' : 'Imagini pentru ready' }}<input type="number" name="required_images_for_ready" value="{{ $settings['required_images_for_ready'] ?? 1 }}" min="1" max="4"></label>
            </div>
            <label>{{ $ru ? 'Остаток по умолчанию, если в прайсе отсутствует' : 'Stoc implicit dacă lipsește din listă' }}<input type="number" name="missing_stock_quantity" value="{{ $settings['missing_stock_quantity'] ?? 0 }}" min="0" max="9999"></label>
            <label>{{ __('ui.parser_allowed_domains') }}<textarea name="allowed_domains">{{ implode("\n", $settings['allowed_domains'] ?? []) }}</textarea></label>
            <label>{{ __('ui.parser_blocked_domains') }}<textarea name="blocked_domains">{{ implode("\n", $settings['blocked_domains'] ?? []) }}</textarea></label>
            <details class="admin-details">
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
    </details>
</section>
@endsection
