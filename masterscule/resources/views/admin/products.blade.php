@extends('layouts.app')

@section('content')
<section class="shell page-title">
    <p>Admin / Produse</p>
    <h1>Administrare produse</h1>
    <span>Adauga, incarca imagini, editeaza descrieri, preturi, stocuri si sterge produse din catalog.</span>
</section>

@if(session('success'))
    <div class="shell notice">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="shell notice error">
        <strong>Verifica formularul.</strong>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<section class="shell admin-products-layout">
    <aside class="panel admin-product-create">
        <div class="admin-panel-head">
            <span>Produs nou</span>
            <h2>Adauga in catalog</h2>
        </div>

        <form method="post" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="admin-product-form">
            @csrf

            <label>Nume produs
                <input name="name" value="{{ old('name') }}" required placeholder="Ex: Set de scule King Tony 7596MR">
            </label>

            <label>Nume afisat pe site
                <input name="name_ro" value="{{ old('name_ro') }}" placeholder="Optional">
            </label>

            <div class="admin-two-cols">
                <label>SKU
                    <input name="sku" value="{{ old('sku') }}" required placeholder="7596MR">
                </label>
                <label>Stoc
                    <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 1) }}" min="0" required>
                </label>
            </div>

            <div class="admin-two-cols">
                <label>Brand
                    <select name="brand_id" required>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Categorie
                    <select name="category_id" required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name_ro }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="admin-two-cols">
                <label>Pret RON
                    <input type="number" step="0.01" name="price" value="{{ old('price') }}" min="0" required>
                </label>
                <label>Pret vechi
                    <input type="number" step="0.01" name="old_price" value="{{ old('old_price') }}" min="0">
                </label>
            </div>

            <label>Incarca imagine principala
                <input type="file" name="main_image_file" accept="image/*">
            </label>

            <label>Sau cale imagine
                <input name="main_image" value="{{ old('main_image') }}" placeholder="/images/products/produs.jpg">
            </label>

            <label>Descriere scurta
                <textarea name="short_description" placeholder="Text scurt pentru cardul produsului">{{ old('short_description') }}</textarea>
            </label>

            <label>Descriere produs
                <textarea name="description_ro" placeholder="Descriere completa pentru pagina produsului">{{ old('description_ro') }}</textarea>
            </label>

            <details class="admin-details">
                <summary>Campuri avansate</summary>
                <label>Specificatii, cate una pe linie
                    <textarea name="attributes_text" placeholder="Material: Otel crom-vanadiu&#10;Numar piese: 96">{{ old('attributes_text') }}</textarea>
                </label>
                <label>Continut pachet, cate una pe linie
                    <textarea name="package_contents_text" placeholder="Chei tubulare 1/4&#10;Clichet reversibil">{{ old('package_contents_text') }}</textarea>
                </label>
                <label>Galerie imagini, cate o cale pe linie
                    <textarea name="gallery_text" placeholder="/images/products/produs-2.jpg">{{ old('gallery_text') }}</textarea>
                </label>
                <div class="admin-two-cols">
                    <label>Garantie
                        <input name="warranty" value="{{ old('warranty', '24 luni') }}">
                    </label>
                    <label>Greutate
                        <input name="weight" value="{{ old('weight') }}" placeholder="7,40 kg">
                    </label>
                </div>
                <label>Dimensiuni
                    <input name="dimensions" value="{{ old('dimensions') }}" placeholder="580 x 380 x 120 mm">
                </label>
                <div class="admin-two-cols">
                    <label>Rating
                        <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', 5) }}">
                    </label>
                    <label>Recenzii
                        <input type="number" min="0" name="reviews_count" value="{{ old('reviews_count', 0) }}">
                    </label>
                </div>
                <label>Meta titlu
                    <input name="meta_title" value="{{ old('meta_title') }}">
                </label>
                <label>Meta descriere
                    <textarea name="meta_description">{{ old('meta_description') }}</textarea>
                </label>
            </details>

            <div class="admin-product-flags">
                <label><input type="checkbox" name="is_active" value="1" checked> Activ</label>
                <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> Recomandat</label>
                <label><input type="checkbox" name="is_new" value="1" @checked(old('is_new'))> Nou</label>
                <label><input type="checkbox" name="is_bestseller" value="1" @checked(old('is_bestseller'))> Top</label>
                <label><input type="checkbox" name="is_discounted" value="1" @checked(old('is_discounted'))> Promotie</label>
            </div>

            <button class="btn" type="submit">Adauga produsul</button>
        </form>
    </aside>

    <div class="admin-products-workspace">
        <form method="get" action="{{ route('admin.products') }}" class="panel admin-product-toolbar">
            <label>Cauta
                <input name="q" value="{{ request('q') }}" placeholder="Nume, SKU sau brand">
            </label>
            <label>Brand
                <select name="brand_id">
                    <option value="">Toate brandurile</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Categorie
                <select name="category_id">
                    <option value="">Toate categoriile</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name_ro }}</option>
                    @endforeach
                </select>
            </label>
            <div class="admin-toolbar-actions">
                <button class="btn" type="submit">Filtreaza</button>
                <a class="btn outline" href="{{ route('admin.products') }}">Reseteaza</a>
            </div>
        </form>

        <div class="admin-products-summary">
            <strong>{{ $products->total() }} produse</strong>
            <span>Editeaza cardul, pagina produsului, imaginile, stocul si statusurile.</span>
        </div>

        <div class="admin-product-list">
            @forelse($products as $product)
                @php
                    $galleryText = implode("\n", array_filter($product->gallery ?? []));
                    $attributesText = collect($product->attributes ?? [])->map(fn ($value, $key) => $key.': '.$value)->implode("\n");
                    $packageText = implode("\n", array_filter($product->package_contents ?? []));
                @endphp

                <article class="admin-product-card">
                    <form method="post" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="admin-product-edit">
                        @csrf
                        @method('PATCH')

                        <div class="admin-product-media">
                            <img src="{{ $product->main_image }}" alt="{{ $product->display_name }}">
                            <div>
                                <strong>{{ $product->sku }}</strong>
                                <span>{{ $product->brand?->name }} / {{ $product->category?->name_ro }}</span>
                            </div>
                            @if($product->badge)
                                <em>{{ $product->badge }}</em>
                            @endif
                        </div>

                        <div class="admin-product-fields">
                            <label>Nume intern
                                <input name="name" value="{{ old('name', $product->name) }}" required>
                            </label>
                            <label>Nume pe site
                                <input name="name_ro" value="{{ old('name_ro', $product->name_ro) }}">
                            </label>
                            <div class="admin-three-cols">
                                <label>SKU
                                    <input name="sku" value="{{ old('sku', $product->sku) }}" required>
                                </label>
                                <label>Brand
                                    <select name="brand_id" required>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Categorie
                                    <select name="category_id" required>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name_ro }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <div class="admin-three-cols">
                                <label>Pret
                                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" required>
                                </label>
                                <label>Pret vechi
                                    <input type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $product->old_price) }}">
                                </label>
                                <label>Stoc
                                    <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required>
                                </label>
                            </div>
                            <div class="admin-two-cols">
                                <label>Imagine principala
                                    <input name="main_image" value="{{ old('main_image', $product->main_image) }}">
                                </label>
                                <label>Inlocuieste imaginea
                                    <input type="file" name="main_image_file" accept="image/*">
                                </label>
                            </div>
                            <label>Descriere scurta pentru card
                                <textarea name="short_description">{{ old('short_description', $product->short_description) }}</textarea>
                            </label>
                            <label>Descriere pagina produsului
                                <textarea name="description_ro">{{ old('description_ro', $product->description_ro) }}</textarea>
                            </label>

                            <details class="admin-details">
                                <summary>Descrieri, galerie si SEO</summary>
                                <label>Descriere tehnica alternativa
                                    <textarea name="description">{{ old('description', $product->description) }}</textarea>
                                </label>
                                <label>Specificatii
                                    <textarea name="attributes_text">{{ old('attributes_text', $attributesText) }}</textarea>
                                </label>
                                <label>Continut pachet
                                    <textarea name="package_contents_text">{{ old('package_contents_text', $packageText) }}</textarea>
                                </label>
                                <label>Galerie imagini
                                    <textarea name="gallery_text">{{ old('gallery_text', $galleryText) }}</textarea>
                                </label>
                                <div class="admin-three-cols">
                                    <label>Garantie
                                        <input name="warranty" value="{{ old('warranty', $product->warranty) }}">
                                    </label>
                                    <label>Greutate
                                        <input name="weight" value="{{ old('weight', $product->weight) }}">
                                    </label>
                                    <label>Dimensiuni
                                        <input name="dimensions" value="{{ old('dimensions', $product->dimensions) }}">
                                    </label>
                                </div>
                                <div class="admin-two-cols">
                                    <label>Rating
                                        <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $product->rating) }}">
                                    </label>
                                    <label>Recenzii
                                        <input type="number" min="0" name="reviews_count" value="{{ old('reviews_count', $product->reviews_count) }}">
                                    </label>
                                </div>
                                <label>Meta titlu
                                    <input name="meta_title" value="{{ old('meta_title', $product->meta_title) }}">
                                </label>
                                <label>Meta descriere
                                    <textarea name="meta_description">{{ old('meta_description', $product->meta_description) }}</textarea>
                                </label>
                            </details>

                            <div class="admin-product-flags">
                                <label><input type="checkbox" name="is_active" value="1" @checked($product->is_active)> Activ</label>
                                <label><input type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> Recomandat</label>
                                <label><input type="checkbox" name="is_new" value="1" @checked($product->is_new)> Nou</label>
                                <label><input type="checkbox" name="is_bestseller" value="1" @checked($product->is_bestseller)> Top</label>
                                <label><input type="checkbox" name="is_discounted" value="1" @checked($product->is_discounted)> Promotie</label>
                            </div>

                            <div class="admin-product-actions">
                                <button class="btn" type="submit">Salveaza modificarile</button>
                                <a class="btn outline" href="{{ route('product.show', $product->slug) }}" target="_blank">Vezi produsul</a>
                            </div>
                        </div>
                    </form>

                    <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="admin-delete-form" onsubmit="return confirm('Stergi definitiv acest produs?')">
                        @csrf
                        @method('DELETE')
                        <button class="delete" type="submit">Sterge produsul</button>
                    </form>
                </article>
            @empty
                <div class="empty">Nu exista produse pentru filtrele selectate.</div>
            @endforelse
        </div>

        {{ $products->links() }}
    </div>
</section>
@endsection
