<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function home()
    {
        return view('shop.home', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->limit(9)->get(),
            'featuredProducts' => Product::with(['brand', 'category'])->where('is_active', true)->where('is_featured', true)->limit(12)->get(),
            'productsCount' => Product::where('is_active', true)->count(),
            'brands' => Brand::where('is_active', true)->orderByDesc('is_featured')->get(),
        ]);
    }

    public function catalog(Request $request, ?string $category = null)
    {
        $activeCategory = $category ? Category::where('slug', $category)->firstOrFail() : null;

        $products = Product::with(['brand', 'category'])
            ->where('is_active', true)
            ->when($activeCategory, fn ($query) => $query->where('category_id', $activeCategory->id))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('short_description', 'like', $term);
                });
            })
            ->when($request->filled('brand'), function ($query) use ($request) {
                $query->whereHas('brand', fn ($brand) => $brand->where('slug', $request->string('brand')));
            })
            ->when($request->boolean('in_stock'), fn ($query) => $query->where('stock_status', 'in_stock'))
            ->when($request->boolean('discounted'), fn ($query) => $query->where('is_discounted', true));

        match ($request->string('sort')->toString()) {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'new' => $products->orderByDesc('is_new')->orderByDesc('created_at'),
            default => $products->orderByDesc('is_bestseller')->orderByDesc('is_featured'),
        };

        return view('shop.catalog', [
            'products' => $products->paginate(12)->withQueryString(),
            'activeCategory' => $activeCategory,
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'brands' => Brand::where('is_active', true)->get(),
        ]);
    }

    public function product(string $slug)
    {
        $product = Product::with(['brand', 'category'])->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('shop.product', [
            'product' => $product,
            'similarProducts' => Product::with('brand')
                ->where('id', '!=', $product->id)
                ->where('category_id', $product->category_id)
                ->where('is_active', true)
                ->limit(4)
                ->get(),
            'brandProducts' => Product::with('brand')
                ->where('id', '!=', $product->id)
                ->where('brand_id', $product->brand_id)
                ->where('is_active', true)
                ->limit(4)
                ->get(),
        ]);
    }

    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('shop.page', compact('page'));
    }

    public function brands()
    {
        return view('shop.brands', [
            'brands' => Brand::withCount('products')->where('is_active', true)->get(),
        ]);
    }

    public function brand(string $slug)
    {
        $brand = Brand::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('shop.catalog', [
            'products' => Product::with(['brand', 'category'])->where('brand_id', $brand->id)->where('is_active', true)->paginate(12),
            'activeCategory' => null,
            'activeBrand' => $brand,
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'brands' => Brand::where('is_active', true)->get(),
        ]);
    }

    public function promotions()
    {
        return $this->productCollection('Promotii', Product::query()->where('is_discounted', true));
    }

    public function newProducts()
    {
        return $this->productCollection('Noutati', Product::query()->where('is_new', true)->orderByDesc('created_at'));
    }

    public function bestsellers()
    {
        return $this->productCollection('TOP vanzari', Product::query()->where('is_bestseller', true));
    }

    public function wishlist()
    {
        return view('shop.collection', [
            'title' => 'Favorite',
            'subtitle' => 'Produsele favorite vor aparea aici dupa activarea selectiei din cardurile de produs.',
            'products' => collect(),
        ]);
    }

    public function compare()
    {
        return view('shop.collection', [
            'title' => 'Comparare produse',
            'subtitle' => 'Lista de comparare va permite analizarea produselor selectate dupa specificatii si pret.',
            'products' => collect(),
        ]);
    }

    private function productCollection(string $title, $query)
    {
        return view('shop.collection', [
            'title' => $title,
            'subtitle' => 'Selectie actualizata din catalogul MasterScule.ro.',
            'products' => $query
                ->with(['brand', 'category'])
                ->where('is_active', true)
                ->orderByDesc('is_featured')
                ->paginate(12),
        ]);
    }
}
