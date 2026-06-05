<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    private function guard(): void
    {
        abort_unless(auth()->check() && auth()->user()->isAdmin(), 403);
    }

    public function dashboard()
    {
        $this->guard();

        return view('admin.dashboard', [
            'productsCount' => Product::count(),
            'ordersCount' => Order::count(),
            'brandsCount' => Brand::count(),
            'categoriesCount' => Category::count(),
            'usersCount' => User::count(),
            'orders' => Order::latest()->limit(8)->get(),
        ]);
    }

    public function products()
    {
        $this->guard();

        return view('admin.products', [
            'products' => Product::with(['brand', 'category'])->latest()->paginate(20),
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function storeProduct(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'main_image' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
        ]);

        $data['main_image'] = $data['main_image'] ?: '/images/products/product-placeholder-toolbox.svg';

        Product::create($data + [
            'slug' => Str::slug($data['name'].'-'.$data['sku']),
            'stock_status' => $data['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock',
            'currency' => 'RON',
            'is_active' => true,
        ]);

        return back()->with('success', 'Produsul a fost creat.');
    }

    public function updateProduct(Request $request, Product $product)
    {
        $this->guard();

        $data = $request->validate([
            'name_ro' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'is_bestseller' => ['nullable', 'boolean'],
            'is_discounted' => ['nullable', 'boolean'],
        ]);

        $product->forceFill($data + [
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'is_new' => $request->boolean('is_new'),
            'is_bestseller' => $request->boolean('is_bestseller'),
            'is_discounted' => $request->boolean('is_discounted'),
            'stock_status' => ((int) $data['stock_quantity']) > 0 ? 'in_stock' : 'out_of_stock',
        ])->save();

        return back()->with('success', 'Produsul a fost actualizat.');
    }

    public function destroyProduct(Product $product)
    {
        $this->guard();
        $product->delete();

        return back()->with('success', 'Produsul a fost sters.');
    }

    public function orders()
    {
        $this->guard();

        return view('admin.orders', ['orders' => Order::with('user')->latest()->paginate(20)]);
    }

    public function users()
    {
        $this->guard();

        return view('admin.users', ['users' => User::latest()->paginate(20)]);
    }
}
