@extends('layouts.admin')

@section('content')
<section class="shell page-title">
    <p>{{ __('ui.admin') }} / {{ __('ui.products') }}</p>
    <h1>{{ __('ui.admin_products') }}</h1>
    <span>{{ __('ui.admin_products_text') }}</span>
</section>

@if(session('success'))
    <div class="shell notice">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="shell notice error">
        <strong>{{ app()->isLocale('ru') ? 'Проверьте форму.' : 'Verifica formularul.' }}</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<section class="shell admin-products-layout">
    <details class="panel admin-product-create" @if($errors->any()) open @endif>
        <summary class="admin-create-summary">
            <span>
                <strong>{{ __('ui.add_to_catalog') }}</strong>
                <small>{{ app()->isLocale('ru') ? 'Откройте только когда нужно создать товар вручную.' : 'Deschide doar cand creezi manual un produs.' }}</small>
            </span>
        </summary>

        <form method="post" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="admin-product-form">
            @csrf

            <label>{{ app()->isLocale('ru') ? 'Название RU' : 'Denumire RU' }}
                <input name="name" value="{{ old('name') }}" required placeholder="{{ app()->isLocale('ru') ? 'Например: набор King Tony 7596MR' : 'Ex: Set de scule King Tony 7596MR' }}">
            </label>

            <label>{{ app()->isLocale('ru') ? 'Название RO' : 'Denumire RO' }}
                <input name="name_ro" value="{{ old('name_ro') }}">
            </label>

            <div class="admin-two-cols">
                <label>SKU
                    <input name="sku" value="{{ old('sku') }}" required placeholder="7596MR">
                </label>
                <label>{{ __('ui.stock') }}
                    <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 1) }}" min="0" required>
                </label>
            </div>

            <div class="admin-two-cols">
                <label>{{ __('ui.brand') }}
                    <select name="brand_id" required>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>{{ __('ui.category') }}
                    <select name="category_id" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->display_name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label>{{ app()->isLocale('ru') ? 'Дополнительные категории' : 'Categorii suplimentare' }}
                <select name="category_ids[]" multiple size="6">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(in_array($category->id, (array) old('category_ids', [])))>{{ $category->display_name }}</option>
                    @endforeach
                </select>
            </label>

            <div class="admin-two-cols">
                <label>{{ __('ui.price_ron') }}
                    <input type="number" step="0.01" name="price" value="{{ old('price') }}" min="0" required>
                </label>
                <label>{{ __('ui.old_price') }}
                    <input type="number" step="0.01" name="old_price" value="{{ old('old_price') }}" min="0">
                </label>
            </div>

            <label>{{ __('ui.upload_main_image') }}
                <input type="file" name="main_image_file" accept="image/*">
            </label>

            <label>{{ __('ui.image_path') }}
                <input name="main_image" value="{{ old('main_image') }}" placeholder="/images/products/produs.jpg">
            </label>

            <label>{{ app()->isLocale('ru') ? 'Короткое описание RU' : 'Descriere scurta RU' }}
                <textarea name="short_description" placeholder="{{ app()->isLocale('ru') ? 'Короткий текст для карточки товара' : 'Text scurt pentru cardul produsului' }}">{{ old('short_description') }}</textarea>
            </label>

            <label>{{ app()->isLocale('ru') ? 'Короткое описание RO' : 'Descriere scurta RO' }}
                <textarea name="short_description_ro">{{ old('short_description_ro') }}</textarea>
            </label>

            <label>{{ app()->isLocale('ru') ? 'Полное описание RU' : 'Descriere completa RU' }}
                <textarea name="description">{{ old('description') }}</textarea>
            </label>

            <label>{{ app()->isLocale('ru') ? 'Полное описание RO' : 'Descriere completa RO' }}
                <textarea name="description_ro">{{ old('description_ro') }}</textarea>
            </label>

            <details class="admin-details">
                <summary>{{ __('ui.advanced_fields') }}</summary>
                <label>{{ __('ui.specifications') }}
                    <textarea name="attributes_text" placeholder="Material: Otel crom-vanadiu&#10;Numar piese: 96">{{ old('attributes_text') }}</textarea>
                </label>
                <label>{{ __('ui.contents') }}
                    <textarea name="package_contents_text" placeholder="Chei tubulare 1/4&#10;Clichet reversibil">{{ old('package_contents_text') }}</textarea>
                </label>
                <label>{{ app()->isLocale('ru') ? 'Галерея изображений, по одному пути в строке' : 'Galerie imagini, cate o cale pe linie' }}
                    <textarea name="gallery_text" placeholder="/images/products/produs-2.jpg">{{ old('gallery_text') }}</textarea>
                </label>
                <div class="admin-two-cols">
                    <label>{{ __('ui.warranty') }}
                        <input name="warranty" value="{{ old('warranty', '12 luni') }}">
                    </label>
                    <label>{{ app()->isLocale('ru') ? 'Вес' : 'Greutate' }}
                        <input name="weight" value="{{ old('weight') }}" placeholder="7,40 kg">
                    </label>
                </div>
                <label>{{ app()->isLocale('ru') ? 'Размеры' : 'Dimensiuni' }}
                    <input name="dimensions" value="{{ old('dimensions') }}" placeholder="580 x 380 x 120 mm">
                </label>
                <div class="admin-two-cols">
                    <label>Rating
                        <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', 5) }}">
                    </label>
                    <label>{{ __('ui.reviews') }}
                        <input type="number" min="0" name="reviews_count" value="{{ old('reviews_count', 0) }}">
                    </label>
                </div>
                <label>Meta title
                    <input name="meta_title" value="{{ old('meta_title') }}">
                </label>
                <label>Meta description
                    <textarea name="meta_description">{{ old('meta_description') }}</textarea>
                </label>
            </details>

            <div class="admin-product-flags">
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active'))> {{ app()->isLocale('ru') ? 'Проверить и опубликовать' : 'Verifica si publica' }}</label>
                <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> {{ __('ui.featured') }}</label>
                <label><input type="checkbox" name="is_new" value="1" @checked(old('is_new'))> {{ __('ui.new') }}</label>
                <label><input type="checkbox" name="is_bestseller" value="1" @checked(old('is_bestseller'))> {{ __('ui.top') }}</label>
                <label><input type="checkbox" name="is_discounted" value="1" @checked(old('is_discounted'))> {{ __('ui.discounted') }}</label>
            </div>

            <button class="btn" type="submit">{{ __('ui.add_product') }}</button>
        </form>
    </details>

    <div class="admin-products-workspace">
        <form method="get" action="{{ route('admin.products') }}" class="panel admin-product-toolbar">
            <label>{{ __('ui.search') }}
                <input name="q" value="{{ request('q') }}" placeholder="{{ app()->isLocale('ru') ? 'Название, SKU или бренд' : 'Nume, SKU sau brand' }}">
            </label>
            <label>{{ __('ui.brand') }}
                <select name="brand_id">
                    <option value="">{{ __('ui.all_brands') }}</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>{{ __('ui.category') }}
                <select name="category_id">
                    <option value="">{{ __('ui.all_categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->display_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>{{ __('ui.admin_image_state') }}
                <select name="image_state">
                    <option value="">{{ __('ui.admin_all_images') }}</option>
                    <option value="ready" @selected(request('image_state') === 'ready')>{{ __('ui.admin_with_photo') }}</option>
                    <option value="missing" @selected(request('image_state') === 'missing')>{{ __('ui.admin_without_photo') }}</option>
                </select>
            </label>
            <div class="admin-toolbar-actions">
                <button class="btn" type="submit">{{ __('ui.filter') }}</button>
                <a class="btn outline" href="{{ route('admin.products') }}">{{ __('ui.reset') }}</a>
            </div>
        </form>

        <div class="admin-products-summary">
            <strong>{{ __('ui.products_count', ['count' => $products->total()]) }}</strong>
            <span>{{ app()->isLocale('ru') ? 'Редактируйте карточку, страницу товара, изображения, остаток и статусы.' : 'Editeaza cardul, pagina produsului, imaginile, stocul si statusurile.' }}</span>
        </div>

        <div class="admin-product-list">
            @forelse($products as $product)
                @php
                    $galleryText = implode("\n", array_filter($product->gallery ?? []));
                    $attributesText = collect($product->attributes ?? [])->map(fn ($value, $key) => $key.': '.$value)->implode("\n");
                    $packageText = implode("\n", array_filter($product->package_contents ?? []));
                    $linkedCategoryIds = $product->categories->pluck('id')->push($product->category_id)->filter()->unique()->values()->all();
                    $categoryList = $product->categories->pluck('display_name')->push($product->category?->display_name)->filter()->unique()->implode(', ');
                    $publicationCheck = $publicationChecks[$product->id] ?? ['allowed' => false, 'errors' => []];
                @endphp

                <details class="admin-product-card">
                    <summary class="admin-product-row">
                        <img
                            src="{{ $product->main_image ?: '/images/products/product-placeholder-toolbox.svg' }}"
                            alt="{{ $product->display_name }}"
                            onerror="this.onerror=null;this.src='/images/products/product-placeholder-toolbox.svg';"
                        >
                        <span class="admin-product-row-main">
                            <strong>{{ $product->display_name }}</strong>
                            <small>{{ $product->sku }} · {{ $product->brand?->name }} · {{ $categoryList ?: $product->category?->display_name }}</small>
                        </span>
                        <span class="admin-product-row-meta">
                            <strong>{{ money($product->price, $product->currency) }}</strong>
                            <small>{{ $product->stock_quantity }} {{ __('ui.stock') }}</small>
                        </span>
                        <span class="admin-product-row-state {{ $publicationCheck['allowed'] && $product->is_active ? 'is-live' : 'is-muted' }}">
                            {{ $publicationCheck['allowed'] && $product->is_active ? __('ui.active') : count($publicationCheck['errors']).' '.(app()->isLocale('ru') ? 'проблем' : 'probleme') }}
                        </span>
                    </summary>

                    <form method="post" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="admin-product-edit">
                        @csrf
                        @method('PATCH')

                        <div class="admin-product-media">
                            <img
                                src="{{ $product->main_image ?: '/images/products/product-placeholder-toolbox.svg' }}"
                                alt="{{ $product->display_name }}"
                                onerror="this.onerror=null;this.src='/images/products/product-placeholder-toolbox.svg';"
                            >
                            <div>
                                <strong>{{ $product->sku }}</strong>
                                <span>{{ $product->brand?->name }} / {{ $categoryList ?: $product->category?->display_name }}</span>
                            </div>
                            @if($product->badge)
                                <em>{{ $product->badge }}</em>
                            @endif
                        </div>

                        @if(!$publicationCheck['allowed'])
                            <div class="admin-publication-warning">
                                <strong>{{ app()->isLocale('ru') ? 'Публикация заблокирована' : 'Publicarea este blocata' }}</strong>
                                <ul>
                                    @foreach($publicationCheck['errors'] as $publicationError)
                                        <li>{{ $publicationError }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="admin-product-fields">
                            <label>{{ app()->isLocale('ru') ? 'Название RU' : 'Denumire RU' }}
                                <input name="name" value="{{ old('name', $product->name) }}" required>
                            </label>
                            <label>{{ app()->isLocale('ru') ? 'Название RO' : 'Denumire RO' }}
                                <input name="name_ro" value="{{ old('name_ro', $product->name_ro) }}">
                            </label>
                            <div class="admin-three-cols">
                                <label>SKU
                                    <input name="sku" value="{{ old('sku', $product->sku) }}" required>
                                </label>
                                <label>{{ __('ui.brand') }}
                                    <select name="brand_id" required>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>{{ __('ui.category') }}
                                    <select name="category_id" required>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->display_name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <div class="admin-three-cols">
                                <label>{{ __('ui.price_ron') }}
                                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" required>
                                </label>
                                <label>{{ __('ui.old_price') }}
                                    <input type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $product->old_price) }}">
                                </label>
                                <label>{{ __('ui.stock') }}
                                    <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required>
                                </label>
                            </div>
                            <div class="admin-two-cols">
                                <label>{{ __('ui.upload_main_image') }}
                                    <input name="main_image" value="{{ old('main_image', $product->main_image) }}">
                                </label>
                                <label>{{ app()->isLocale('ru') ? 'Заменить изображение' : 'Inlocuieste imaginea' }}
                                    <input type="file" name="main_image_file" accept="image/*">
                                </label>
                            </div>
                            <label>{{ app()->isLocale('ru') ? 'Короткое описание RU' : 'Descriere scurta RU' }}
                                <textarea name="short_description">{{ old('short_description', $product->short_description) }}</textarea>
                            </label>
                            <label>{{ app()->isLocale('ru') ? 'Короткое описание RO' : 'Descriere scurta RO' }}
                                <textarea name="short_description_ro">{{ old('short_description_ro', $product->short_description_ro) }}</textarea>
                            </label>
                            <label>{{ app()->isLocale('ru') ? 'Полное описание RO' : 'Descriere completa RO' }}
                                <textarea name="description_ro">{{ old('description_ro', $product->description_ro) }}</textarea>
                            </label>

                            <details class="admin-details">
                                <summary>{{ app()->isLocale('ru') ? 'Описания, галерея и SEO' : 'Descrieri, galerie si SEO' }}</summary>
                                <label>{{ app()->isLocale('ru') ? 'Полное описание RU' : 'Descriere completa RU' }}
                                    <textarea name="description">{{ old('description', $product->description) }}</textarea>
                                </label>
                                <label>{{ __('ui.specifications') }}
                                    <textarea name="attributes_text">{{ old('attributes_text', $attributesText) }}</textarea>
                                </label>
                                <label>{{ __('ui.contents') }}
                                    <textarea name="package_contents_text">{{ old('package_contents_text', $packageText) }}</textarea>
                                </label>
                                <label>{{ app()->isLocale('ru') ? 'Галерея изображений' : 'Galerie imagini' }}
                                    <textarea name="gallery_text">{{ old('gallery_text', $galleryText) }}</textarea>
                                </label>
                                <label>{{ app()->isLocale('ru') ? 'Дополнительные категории' : 'Categorii suplimentare' }}
                                    <select name="category_ids[]" multiple size="6">
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected(in_array($category->id, (array) old('category_ids', $linkedCategoryIds)))>{{ $category->display_name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="admin-three-cols">
                                    <label>{{ __('ui.warranty') }}
                                        <input name="warranty" value="{{ old('warranty', $product->warranty) }}">
                                    </label>
                                    <label>{{ app()->isLocale('ru') ? 'Вес' : 'Greutate' }}
                                        <input name="weight" value="{{ old('weight', $product->weight) }}">
                                    </label>
                                    <label>{{ app()->isLocale('ru') ? 'Размеры' : 'Dimensiuni' }}
                                        <input name="dimensions" value="{{ old('dimensions', $product->dimensions) }}">
                                    </label>
                                </div>
                                <div class="admin-two-cols">
                                    <label>Rating
                                        <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $product->rating) }}">
                                    </label>
                                    <label>{{ __('ui.reviews') }}
                                        <input type="number" min="0" name="reviews_count" value="{{ old('reviews_count', $product->reviews_count) }}">
                                    </label>
                                </div>
                                <label>Meta title
                                    <input name="meta_title" value="{{ old('meta_title', $product->meta_title) }}">
                                </label>
                                <label>Meta description
                                    <textarea name="meta_description">{{ old('meta_description', $product->meta_description) }}</textarea>
                                </label>
                            </details>

                            <div class="admin-product-flags">
                                <label><input type="checkbox" name="is_active" value="1" @checked($product->is_active)> {{ app()->isLocale('ru') ? 'Проверить и опубликовать' : 'Verifica si publica' }}</label>
                                <label><input type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> {{ __('ui.featured') }}</label>
                                <label><input type="checkbox" name="is_new" value="1" @checked($product->is_new)> {{ __('ui.new') }}</label>
                                <label><input type="checkbox" name="is_bestseller" value="1" @checked($product->is_bestseller)> {{ __('ui.top') }}</label>
                                <label><input type="checkbox" name="is_discounted" value="1" @checked($product->is_discounted)> {{ __('ui.discounted') }}</label>
                            </div>

                            <details class="admin-details">
                                <summary>{{ app()->isLocale('ru') ? 'Флаги ручной проверки' : 'Marcaje verificare manuala' }}</summary>
                                <div class="admin-product-flags">
                                    <label><input type="checkbox" name="needs_image_review" value="1" @checked($product->needs_image_review)> {{ app()->isLocale('ru') ? 'Фото' : 'Imagine' }}</label>
                                    <label><input type="checkbox" name="needs_category_review" value="1" @checked($product->needs_category_review)> {{ app()->isLocale('ru') ? 'Категория' : 'Categorie' }}</label>
                                    <label><input type="checkbox" name="needs_translation_review" value="1" @checked($product->needs_translation_review)> {{ app()->isLocale('ru') ? 'Перевод' : 'Traducere' }}</label>
                                    <label><input type="checkbox" name="needs_price_review" value="1" @checked($product->needs_price_review)> {{ app()->isLocale('ru') ? 'Цена' : 'Pret' }}</label>
                                    <label><input type="checkbox" name="needs_stock_review" value="1" @checked($product->needs_stock_review)> {{ app()->isLocale('ru') ? 'Остаток' : 'Stoc' }}</label>
                                </div>
                            </details>

                            <div class="admin-product-actions">
                                <button class="btn" type="submit">{{ __('ui.save_changes') }}</button>
                                <a class="btn outline" href="{{ route('product.show', $product->slug) }}" target="_blank">{{ __('ui.view_product') }}</a>
                            </div>
                        </div>
                    </form>

                </details>
            @empty
                <div class="empty">{{ __('ui.no_selected_products') }}</div>
            @endforelse
        </div>

        {{ $products->links() }}
    </div>
</section>
@endsection
