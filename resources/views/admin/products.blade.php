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
    <aside class="panel admin-product-create">
        <div class="admin-panel-head">
            <span>{{ __('ui.new_product') }}</span>
            <h2>{{ __('ui.add_to_catalog') }}</h2>
        </div>

        <form method="post" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="admin-product-form">
            @csrf

            <label>{{ __('ui.product_name') }}
                <input name="name" value="{{ old('name') }}" required placeholder="{{ app()->isLocale('ru') ? 'Например: набор King Tony 7596MR' : 'Ex: Set de scule King Tony 7596MR' }}">
            </label>

            <label>{{ __('ui.site_name') }}
                <input name="name_ro" value="{{ old('name_ro') }}" placeholder="{{ app()->isLocale('ru') ? 'Опционально' : 'Optional' }}">
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

            <label>{{ __('ui.short_description') }}
                <textarea name="short_description" placeholder="{{ app()->isLocale('ru') ? 'Короткий текст для карточки товара' : 'Text scurt pentru cardul produsului' }}">{{ old('short_description') }}</textarea>
            </label>

            <label>{{ __('ui.product_description') }}
                <textarea name="description_ro" placeholder="{{ app()->isLocale('ru') ? 'Полное описание для страницы товара' : 'Descriere completa pentru pagina produsului' }}">{{ old('description_ro') }}</textarea>
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
                        <input name="warranty" value="{{ old('warranty', '24 luni') }}">
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
                <label><input type="checkbox" name="is_active" value="1" checked> {{ __('ui.active') }}</label>
                <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> {{ __('ui.featured') }}</label>
                <label><input type="checkbox" name="is_new" value="1" @checked(old('is_new'))> {{ __('ui.new') }}</label>
                <label><input type="checkbox" name="is_bestseller" value="1" @checked(old('is_bestseller'))> {{ __('ui.top') }}</label>
                <label><input type="checkbox" name="is_discounted" value="1" @checked(old('is_discounted'))> {{ __('ui.discounted') }}</label>
            </div>

            <button class="btn" type="submit">{{ __('ui.add_product') }}</button>
        </form>
    </aside>

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
                @endphp

                <article class="admin-product-card">
                    <form method="post" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="admin-product-edit">
                        @csrf
                        @method('PATCH')

                        <div class="admin-product-media">
                            <img src="{{ $product->main_image }}" alt="{{ $product->display_name }}">
                            <div>
                                <strong>{{ $product->sku }}</strong>
                                <span>{{ $product->brand?->name }} / {{ $categoryList ?: $product->category?->display_name }}</span>
                            </div>
                            @if($product->badge)
                                <em>{{ $product->badge }}</em>
                            @endif
                        </div>

                        <div class="admin-product-fields">
                            <label>{{ app()->isLocale('ru') ? 'Внутреннее название' : 'Nume intern' }}
                                <input name="name" value="{{ old('name', $product->name) }}" required>
                            </label>
                            <label>{{ __('ui.site_name') }}
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
                            <label>{{ __('ui.short_description') }}
                                <textarea name="short_description">{{ old('short_description', $product->short_description) }}</textarea>
                            </label>
                            <label>{{ __('ui.product_description') }}
                                <textarea name="description_ro">{{ old('description_ro', $product->description_ro) }}</textarea>
                            </label>

                            <details class="admin-details">
                                <summary>{{ app()->isLocale('ru') ? 'Описания, галерея и SEO' : 'Descrieri, galerie si SEO' }}</summary>
                                <label>{{ app()->isLocale('ru') ? 'Альтернативное техническое описание' : 'Descriere tehnica alternativa' }}
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
                                <label><input type="checkbox" name="is_active" value="1" @checked($product->is_active)> {{ __('ui.active') }}</label>
                                <label><input type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> {{ __('ui.featured') }}</label>
                                <label><input type="checkbox" name="is_new" value="1" @checked($product->is_new)> {{ __('ui.new') }}</label>
                                <label><input type="checkbox" name="is_bestseller" value="1" @checked($product->is_bestseller)> {{ __('ui.top') }}</label>
                                <label><input type="checkbox" name="is_discounted" value="1" @checked($product->is_discounted)> {{ __('ui.discounted') }}</label>
                            </div>

                            <div class="admin-product-actions">
                                <button class="btn" type="submit">{{ __('ui.save_changes') }}</button>
                                <a class="btn outline" href="{{ route('product.show', $product->slug) }}" target="_blank">{{ __('ui.view_product') }}</a>
                            </div>
                        </div>
                    </form>

                    <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="admin-delete-form" onsubmit="return confirm('{{ __('ui.delete_confirm') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="delete" type="submit">{{ __('ui.delete_product') }}</button>
                    </form>
                </article>
            @empty
                <div class="empty">{{ __('ui.no_selected_products') }}</div>
            @endforelse
        </div>

        {{ $products->links() }}
    </div>
</section>
@endsection
