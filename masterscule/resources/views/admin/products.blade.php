@extends('layouts.app')

@section('content')
<section class="shell page-title"><p>Admin / Produse</p><h1>CRUD produse</h1></section>
<section class="shell admin-grid">
    <form method="post" action="{{ route('admin.products.store') }}" class="panel">
        @csrf
        <h2>Produs nou</h2>
        <label>Nume<input name="name" required></label>
        <label>SKU<input name="sku" required></label>
        <label>Brand<select name="brand_id">@foreach($brands as $brand)<option value="{{ $brand->id }}">{{ $brand->name }}</option>@endforeach</select></label>
        <label>Categorie<select name="category_id">@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name_ro }}</option>@endforeach</select></label>
        <label>Preț<input type="number" step="0.01" name="price" required></label>
        <label>Stoc<input type="number" name="stock_quantity" value="1" required></label>
        <label>Imagine<input name="main_image" placeholder="/images/products/produs.jpg"></label>
        <label>Descriere scurtă<textarea name="short_description"></textarea></label>
        <button class="btn">Salvează</button>
    </form>
    <div class="panel"><h2>Produse</h2><table><tr><th>SKU</th><th>Produs</th><th>Preț</th><th>Stoc</th><th>Status</th><th>Actiuni</th></tr>@foreach($products as $product)<tr><td>{{ $product->sku }}</td><td><form id="product-{{ $product->id }}" method="post" action="{{ route('admin.products.update', $product) }}">@csrf @method('PATCH')<input name="name_ro" value="{{ $product->display_name }}"></form></td><td><input form="product-{{ $product->id }}" type="number" step="0.01" name="price" value="{{ $product->price }}"></td><td><input form="product-{{ $product->id }}" type="number" name="stock_quantity" value="{{ $product->stock_quantity }}"></td><td><label><input form="product-{{ $product->id }}" type="checkbox" name="is_active" value="1" @checked($product->is_active)> Activ</label><label><input form="product-{{ $product->id }}" type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> Recomandat</label></td><td><button form="product-{{ $product->id }}" class="btn small">Salveaza</button><form method="post" action="{{ route('admin.products.destroy', $product) }}">@csrf @method('DELETE')<button class="delete">Sterge</button></form></td></tr>@endforeach</table>{{ $products->links() }}</div>
</section>
@endsection
