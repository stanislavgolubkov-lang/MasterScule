<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParserItem;
use App\Models\User;
use App\Services\Catalog\ProductPublicationGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'ordersToday' => Order::whereDate('created_at', today())->count(),
            'ordersWeek' => Order::where('created_at', '>=', now()->subDays(7))->count(),
            'ordersTotal' => (float) Order::sum('total'),
            'draftProducts' => Product::where('status', 'draft')->count(),
            'productsWithoutPhoto' => Product::where(function ($query) {
                $query
                    ->whereNull('main_image')
                    ->orWhere('main_image', '')
                    ->orWhere('main_image', 'like', '%placeholder%');
            })->count(),
            'productsWithoutCategory' => Product::whereNull('category_id')->count(),
            'parserErrors' => ProductParserItem::whereIn('status', ['failed', 'not_found'])->count(),
            'pendingPayments' => PaymentTransaction::whereIn('status', ['created', 'waiting_for_payment'])->count(),
            'orders' => Order::latest()->limit(8)->get(),
        ]);
    }

    public function products(ProductPublicationGuard $publicationGuard)
    {
        $this->guard();

        $products = Product::with(['brand', 'category', 'categories'])
            ->when(request('q'), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('name_ro', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when(request('brand_id'), fn ($query, $brandId) => $query->where('brand_id', $brandId))
            ->when(request('category_id'), function ($query, $categoryId) {
                $category = Category::with('childrenRecursive')->find($categoryId);

                $query->inCatalogCategories($category?->descendantsAndSelfIds() ?: [(int) $categoryId]);
            })
            ->when(request('image_state') === 'missing', function ($query) {
                $query->where(function ($images) {
                    $images
                        ->whereNull('main_image')
                        ->orWhere('main_image', '')
                        ->orWhere('main_image', 'like', '%placeholder%');
                });
            })
            ->when(request('image_state') === 'ready', function ($query) {
                $query
                    ->whereNotNull('main_image')
                    ->where('main_image', '!=', '')
                    ->where('main_image', 'not like', '%placeholder%');
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.products', [
            'products' => $products,
            'publicationChecks' => $products->getCollection()->mapWithKeys(fn (Product $product) => [
                $product->id => $publicationGuard->evaluate($product, true),
            ]),
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('sort_order')->orderBy('name_ro')->get(),
        ]);
    }

    public function storeProduct(Request $request, ProductPublicationGuard $publicationGuard)
    {
        $this->guard();
        $publishRequested = $request->boolean('is_active');
        $product = Product::create($this->productData($request) + [
            'status' => 'draft',
            'approval_status' => 'pending_review',
            'needs_review' => true,
            'is_active' => false,
        ]);
        $this->syncProductImages($product);
        $this->syncProductCategories($request, $product);

        if ($publishRequested) {
            $result = $publicationGuard->publish($product->refresh(), true);
            if (! $result['allowed']) {
                return back()
                    ->with('warning', app()->isLocale('ru') ? 'Товар сохранён как черновик: публикация заблокирована.' : 'Produsul a fost salvat ca draft: publicarea este blocata.')
                    ->with('publication_errors', $result['errors']);
            }
        }

        return back()->with('success', app()->isLocale('ru') ? 'Товар сохранён.' : 'Produsul a fost salvat.');
    }

    public function updateProduct(Request $request, Product $product, ProductPublicationGuard $publicationGuard)
    {
        $this->guard();
        $publishRequested = $request->boolean('is_active');

        $product->forceFill($this->productData($request, $product) + [
            'status' => 'draft',
            'approval_status' => 'pending_review',
            'needs_review' => true,
            'is_active' => false,
        ])->save();
        $this->syncProductImages($product);
        $this->syncProductCategories($request, $product);

        if ($publishRequested) {
            $result = $publicationGuard->publish($product->refresh(), true);
            if (! $result['allowed']) {
                return back()
                    ->with('warning', app()->isLocale('ru') ? 'Изменения сохранены, но товар переведён в черновик.' : 'Modificarile au fost salvate, dar produsul a ramas draft.')
                    ->with('publication_errors', $result['errors']);
            }
        }

        return back()->with('success', app()->isLocale('ru') ? 'Товар обновлён.' : 'Produsul a fost actualizat.');
    }

    public function orders()
    {
        $this->guard();

        return view('admin.orders', [
            'orders' => Order::with(['user', 'items', 'paymentTransactions'])->latest()->paginate(20),
            'orderStatuses' => ['new', 'pending_payment', 'processing', 'paid', 'stock_conflict', 'payment_failed', 'shipped', 'completed', 'canceled'],
            'paymentStatuses' => ['pending', 'paid', 'failed', 'refunded'],
        ]);
    }

    public function payments()
    {
        $this->guard();

        return view('admin.payments', [
            'transactions' => PaymentTransaction::with('order')->latest()->paginate(25),
        ]);
    }

    public function updateOrder(Request $request, Order $order)
    {
        $this->guard();

        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'pending_payment', 'processing', 'paid', 'stock_conflict', 'payment_failed', 'shipped', 'completed', 'canceled'])],
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->forceFill($data + [
            'paid_at' => $data['payment_status'] === 'paid' ? ($order->paid_at ?: now()) : $order->paid_at,
        ])->save();

        return back()->with('success', app()->isLocale('ru') ? 'Заказ обновлен.' : 'Comanda a fost actualizata.');
    }

    public function users()
    {
        $this->guard();

        return view('admin.users', ['users' => User::latest()->paginate(20)]);
    }

    private function productData(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_ro' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($product)],
            'price' => ['required', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'main_image' => ['nullable', 'string', 'max:255'],
            'main_image_file' => ['nullable', 'image', 'max:4096'],
            'gallery_text' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string'],
            'short_description_ru' => ['nullable', 'string'],
            'short_description_ro' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'description_ro' => ['nullable', 'string'],
            'attributes_text' => ['nullable', 'string'],
            'package_contents_text' => ['nullable', 'string'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'reviews_count' => ['nullable', 'integer', 'min:0'],
            'warranty' => ['nullable', 'string', 'max:80'],
            'weight' => ['nullable', 'string', 'max:80'],
            'dimensions' => ['nullable', 'string', 'max:120'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_new' => ['nullable', 'boolean'],
            'is_bestseller' => ['nullable', 'boolean'],
            'is_discounted' => ['nullable', 'boolean'],
            'needs_image_review' => ['nullable', 'boolean'],
            'needs_category_review' => ['nullable', 'boolean'],
            'needs_translation_review' => ['nullable', 'boolean'],
            'needs_price_review' => ['nullable', 'boolean'],
            'needs_stock_review' => ['nullable', 'boolean'],
        ]);

        $nameRu = ($data['name_ru'] ?? null) ?: $data['name'];
        $nameRo = ($data['name_ro'] ?? null) ?: null;
        $mainImage = ($data['main_image'] ?? null) ?: $product?->main_image;

        if ($request->hasFile('main_image_file')) {
            $this->deleteUploadedImage($product?->main_image);
            $mainImage = $this->storeUploadedImage($request, $data['sku']);
        }

        $mainImage = $mainImage ?: '/images/products/product-placeholder-toolbox.svg';
        $gallery = $this->lines($data['gallery_text'] ?? '');
        array_unshift($gallery, $mainImage);
        $gallery = array_values(array_unique(array_filter($gallery)));

        return [
            'brand_id' => $data['brand_id'],
            'category_id' => $data['category_id'],
            'name' => $nameRu,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'slug' => $product?->slug ?: $this->uniqueProductSlug($nameRo ?: $nameRu, $data['sku'], $product),
            'sku' => $data['sku'],
            'short_description' => ($data['short_description_ru'] ?? null) ?: (($data['short_description'] ?? null) ?: null),
            'short_description_ru' => ($data['short_description_ru'] ?? null) ?: (($data['short_description'] ?? null) ?: null),
            'short_description_ro' => ($data['short_description_ro'] ?? null) ?: null,
            'description' => ($data['description_ru'] ?? null) ?: (($data['description'] ?? null) ?: null),
            'description_ru' => ($data['description_ru'] ?? null) ?: (($data['description'] ?? null) ?: null),
            'description_ro' => ($data['description_ro'] ?? null) ?: null,
            'price' => $data['price'],
            'old_price' => ($data['old_price'] ?? null) ?: null,
            'currency' => config('store.currency', 'MDL'),
            'stock_quantity' => $data['stock_quantity'],
            'stock_status' => ((int) $data['stock_quantity']) > 0 ? 'in_stock' : 'out_of_stock',
            'main_image' => $mainImage,
            'gallery' => $gallery,
            'attributes' => $this->keyValueLines($data['attributes_text'] ?? ''),
            'package_contents' => $this->lines($data['package_contents_text'] ?? ''),
            'rating' => ($data['rating'] ?? null) ?: 5,
            'reviews_count' => ($data['reviews_count'] ?? null) ?: 0,
            'is_featured' => $request->boolean('is_featured'),
            'is_new' => $request->boolean('is_new'),
            'is_bestseller' => $request->boolean('is_bestseller'),
            'is_discounted' => $request->boolean('is_discounted') || (float) ($data['old_price'] ?? 0) > (float) $data['price'],
            'needs_image_review' => $request->boolean('needs_image_review') || Str::contains(Str::lower($mainImage), ['placeholder', 'fallback']),
            'needs_category_review' => $request->boolean('needs_category_review'),
            'needs_translation_review' => $request->boolean('needs_translation_review'),
            'needs_price_review' => $request->boolean('needs_price_review'),
            'needs_stock_review' => $request->boolean('needs_stock_review'),
            'warranty' => ($data['warranty'] ?? null) ?: '12 luni',
            'weight' => ($data['weight'] ?? null) ?: null,
            'dimensions' => ($data['dimensions'] ?? null) ?: null,
            'meta_title' => ($data['meta_title'] ?? null) ?: ($nameRo ?: $nameRu).' | '.config('store.domain_label'),
            'meta_description' => ($data['meta_description'] ?? null) ?: Str::limit(($data['short_description_ro'] ?? null) ?: ($data['description_ro'] ?? null) ?: ($data['short_description_ru'] ?? null) ?: ($data['description_ru'] ?? null) ?: $nameRo ?: $nameRu, 150),
        ];
    }

    private function storeUploadedImage(Request $request, string $sku): string
    {
        $file = $request->file('main_image_file');
        $directory = public_path('images/products/admin');
        File::ensureDirectoryExists($directory);

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::slug($sku).'-'.now()->format('YmdHis').'.'.$extension;
        $file->move($directory, $filename);

        return '/images/products/admin/'.$filename;
    }

    private function deleteUploadedImage(?string $path): void
    {
        if (! $path || ! Str::startsWith($path, '/images/products/admin/')) {
            return;
        }

        $fullPath = public_path(ltrim($path, '/'));
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }

    private function syncProductImages(Product $product): void
    {
        $gallery = $product->gallery ?: [$product->main_image];
        ProductImage::where('product_id', $product->id)->delete();

        foreach (array_values(array_filter($gallery)) as $index => $path) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'alt' => $product->display_name,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function syncProductCategories(Request $request, Product $product): void
    {
        $categoryIds = collect((array) $request->input('category_ids', []))
            ->push($product->category_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $product->syncCategoryLinks($categoryIds, (int) $product->category_id, 'admin');
    }

    private function uniqueProductSlug(string $name, string $sku, ?Product $product = null): string
    {
        $base = Str::slug($name.'-'.$sku) ?: Str::slug('produs-'.$sku);
        $slug = $base;
        $index = 2;

        while (Product::where('slug', $slug)->when($product, fn ($query) => $query->whereKeyNot($product->id))->exists()) {
            $slug = $base.'-'.$index++;
        }

        return $slug;
    }

    private function lines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function keyValueLines(?string $value): array
    {
        $attributes = [];

        foreach ($this->lines($value) as $line) {
            [$key, $attributeValue] = array_pad(preg_split('/\s*[:=]\s*/', $line, 2), 2, null);
            if ($key && $attributeValue) {
                $attributes[$key] = $attributeValue;
            }
        }

        return $attributes;
    }
}
