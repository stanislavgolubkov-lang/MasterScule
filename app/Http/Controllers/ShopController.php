<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    private const CATALOG_ROOT_SLUG = 'instrumente-si-mobilier';
    private const NEW_PRODUCTS_PER_BRAND = 4;

    private const MAIN_CATALOG_SLUGS = [
        'mobilier-pentru-service',
        'scule-speciale-auto',
        'instrument-manual',
        'scule-pneumatice',
        'electroinstrumente',
        'instrumente-cu-acumulator',
        'instrumente-electromontaj',
        'instrumente-de-masurare',
        'accesorii-si-consumabile',
    ];

    public function home()
    {
        $instrumentRoot = Category::where('slug', self::CATALOG_ROOT_SLUG)->first();

        return view('shop.home', [
            'categories' => Category::with('children')
                ->where('is_active', true)
                ->when($instrumentRoot, fn ($query) => $query->where('parent_id', $instrumentRoot->id))
                ->when(! $instrumentRoot, fn ($query) => $query->whereNull('parent_id'))
                ->orderBy('sort_order')
                ->limit(9)
                ->get(),
            'featuredProducts' => Product::with(['brand', 'category'])
                ->availableForSale()
                ->where('is_featured', true)
                ->where('main_image', 'not like', '%product-placeholder%')
                ->orderByDesc('is_bestseller')
                ->orderByDesc('id')
                ->limit(12)
                ->get(),
            'productsCount' => Product::availableForSale()->count(),
            'brands' => Brand::where('is_active', true)->orderByDesc('is_featured')->get(),
        ]);
    }

    public function catalog(Request $request, ?string $category = null)
    {
        $activeCategory = $category
            ? Category::with(['parent.parent', 'childrenRecursive'])->where('slug', $category)->firstOrFail()
            : null;
        $categoryIds = $activeCategory?->descendantsAndSelfIds();
        $showProducts = $this->shouldShowProducts($request, $activeCategory);

        $products = Product::with(['brand', 'category', 'categories'])
            ->availableForSale()
            ->when(! $showProducts, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($activeCategory, fn ($query) => $query->inCatalogCategories($categoryIds))
            ->when($request->filled('q'), function ($query) use ($request) {
                $terms = $this->searchTerms($request->string('q')->toString());

                $query->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $like = '%'.$term.'%';

                        $query->orWhere('name', 'like', $like)
                            ->orWhere('name_ro', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhere('short_description', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('description_ro', 'like', $like)
                            ->orWhere('attributes', 'like', $like)
                            ->orWhere('package_contents', 'like', $like)
                            ->orWhereHas('brand', function ($brand) use ($like) {
                                $brand->where('name', 'like', $like)
                                    ->orWhere('slug', 'like', $like);
                            })
                            ->orWhereHas('category', function ($category) use ($like) {
                                $category->where('name', 'like', $like)
                                    ->orWhere('name_ro', 'like', $like)
                                    ->orWhere('slug', 'like', $like);
                            })
                            ->orWhereHas('categories', function ($category) use ($like) {
                                $category->where('name', 'like', $like)
                                    ->orWhere('name_ro', 'like', $like)
                                    ->orWhere('slug', 'like', $like);
                            });
                    }
                });
            })
            ->when($request->filled('brand'), function ($query) use ($request) {
                $brands = collect((array) $request->input('brand'))->filter()->values();
                $query->whereHas('brand', fn ($brand) => $brand->whereIn('slug', $brands));
            })
            ->when($request->filled('price_min'), fn ($query) => $query->where('price', '>=', max(0, (float) $request->input('price_min'))))
            ->when($request->filled('price_max'), fn ($query) => $query->where('price', '<=', max(0, (float) $request->input('price_max'))))
            ->when($request->boolean('in_stock'), fn ($query) => $query->where('stock_status', 'in_stock'))
            ->when($request->boolean('discounted'), fn ($query) => $query->where('is_discounted', true))
            ->when($request->filled('attributes'), function ($query) use ($request) {
                foreach ((array) $request->input('attributes') as $key => $value) {
                    if ($key && $value !== null && $value !== '') {
                        $query->where('attributes', 'like', '%"'.$key.'":"'.$value.'"%');
                    }
                }
            });

        match ($request->string('sort')->toString()) {
            'price_asc' => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'new' => $products->orderByDesc('is_new')->orderByDesc('created_at'),
            default => $products->orderByDesc('is_bestseller')->orderByDesc('is_featured'),
        };

        return view('shop.catalog', [
            'products' => $products->paginate(12)->withQueryString(),
            'activeCategory' => $activeCategory,
            'activePathIds' => $this->categoryPathIds($activeCategory),
            'breadcrumbs' => $this->categoryBreadcrumbs($activeCategory),
            'rootCatalogSections' => $this->catalogMainSections(),
            'subcategories' => $this->subcategories($activeCategory),
            'catalogTree' => $this->catalogMainSections(),
            'categoryTiles' => $activeCategory ? collect() : $this->catalogMainSections(),
            'sideNavigation' => ['title' => '', 'items' => collect(), 'back' => null],
            'showProducts' => $showProducts,
            'brands' => Brand::where('is_active', true)->get(),
            'selectedBrands' => collect((array) $request->input('brand'))->filter()->values()->all(),
            'priceBounds' => [
                'min' => (int) floor(Product::availableForSale()->min('price') ?? 0),
                'max' => (int) ceil(Product::availableForSale()->max('price') ?? 0),
            ],
            'viewMode' => $request->string('view')->toString() === 'list' ? 'list' : 'grid',
        ]);
    }

    public function product(string $slug)
    {
        $product = Product::with(['brand', 'category', 'categories'])->where('slug', $slug)->availableForSale()->firstOrFail();
        $similarCategoryIds = $product->categories
            ->pluck('id')
            ->push($product->category_id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return view('shop.product', [
            'product' => $product,
            'similarProducts' => Product::with('brand')
                ->where('id', '!=', $product->id)
                ->inCatalogCategories($similarCategoryIds)
                ->availableForSale()
                ->limit(4)
                ->get(),
            'brandProducts' => Product::with('brand')
                ->where('id', '!=', $product->id)
                ->where('brand_id', $product->brand_id)
                ->availableForSale()
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
            'brands' => Brand::withCount([
                'products' => fn ($products) => $products->availableForSale(),
            ])->where('is_active', true)->get(),
        ]);
    }

    public function brand(string $slug)
    {
        $brand = Brand::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('shop.catalog', [
            'products' => Product::with(['brand', 'category', 'categories'])->where('brand_id', $brand->id)->availableForSale()->paginate(12),
            'activeCategory' => null,
            'activePathIds' => [],
            'breadcrumbs' => [],
            'activeBrand' => $brand,
            'rootCatalogSections' => $this->catalogMainSections(),
            'subcategories' => collect(),
            'catalogTree' => $this->catalogMainSections(),
            'categoryTiles' => collect(),
            'sideNavigation' => ['title' => 'Brand', 'items' => collect(), 'back' => null],
            'showProducts' => true,
            'brands' => Brand::where('is_active', true)->get(),
            'selectedBrands' => [$brand->slug],
            'priceBounds' => [
                'min' => (int) floor(Product::availableForSale()->min('price') ?? 0),
                'max' => (int) ceil(Product::availableForSale()->max('price') ?? 0),
            ],
            'viewMode' => 'grid',
        ]);
    }

    public function promotions()
    {
        return $this->productCollection(__('ui.promotions'), Product::query()->where('is_discounted', true));
    }

    public function newProducts()
    {
        $products = Brand::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($products) => $products->purchasable())
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get()
            ->flatMap(fn (Brand $brand) => Product::with(['brand', 'category', 'categories'])
                ->purchasable()
                ->where('brand_id', $brand->id)
                ->orderByDesc('is_new')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::NEW_PRODUCTS_PER_BRAND)
                ->get())
            ->values();

        return view('shop.collection', [
            'title' => __('ui.new_items'),
            'subtitle' => __('ui.collection_text'),
            'products' => $products,
            'collectionClass' => 'product-grid-compact',
        ]);
    }

    public function bestsellers()
    {
        return $this->productCollection(__('ui.bestsellers'), Product::query()->where('is_bestseller', true));
    }

    public function wishlist()
    {
        return view('shop.collection', [
            'title' => __('ui.wishlist_title'),
            'subtitle' => __('ui.wishlist_text'),
            'products' => collect(),
        ]);
    }

    public function compare()
    {
        return view('shop.collection', [
            'title' => __('ui.compare_title'),
            'subtitle' => __('ui.compare_text'),
            'products' => collect(),
        ]);
    }

    private function productCollection(string $title, $query)
    {
        return view('shop.collection', [
            'title' => $title,
            'subtitle' => __('ui.collection_text'),
            'products' => $query
                ->with(['brand', 'category', 'categories'])
                ->availableForSale()
                ->orderByDesc('is_featured')
                ->paginate(12),
        ]);
    }

    private function searchTerms(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        $normalized = mb_strtolower($value);
        $terms = [$value, $normalized];

        $groups = [
            ['гайковерт', 'гайковёрт', 'pistol impact', 'pistol pneumatic', 'pneumatic impact', 'impact', 'nc-'],
            ['набор', 'set', 'trusă', 'trusa', 'truse', 'set de scule'],
            ['головки', 'головка', 'tubulare', 'tubulara'],
            ['трещотка', 'clichet', 'clichete'],
            ['домкрат', 'cric', 'ridicare'],
            ['компрессор', 'compresor', 'aer comprimat'],
            ['динамометрический', 'динамометрическая', 'dinamometric', 'cheie dinamometrica'],
            ['гараж', 'garaj', 'atelier'],
            ['шиномонтаж', 'vulcanizare', 'anvelope', 'roti'],
            ['тормоз', 'тормоза', 'frane', 'suspensie'],
            ['двигатель', 'motor'],
            ['электрика', 'electric', 'electromontaj'],
            ['организация', 'organizare', 'dulapuri', 'carucior'],
        ];

        foreach ($groups as $group) {
            foreach ($group as $candidate) {
                if (mb_stripos($normalized, mb_strtolower($candidate)) !== false) {
                    $terms = array_merge($terms, $group);
                    break;
                }
            }
        }

        return collect($terms)
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->unique(fn ($term) => mb_strtolower($term))
            ->values()
            ->all();
    }

    private function catalogTree()
    {
        return $this->catalogMainSections();
    }

    private function catalogMainSections()
    {
        $catalogRoot = Category::where('slug', self::CATALOG_ROOT_SLUG)->first();

        return Category::with('childrenRecursive')
            ->where('is_active', true)
            ->when($catalogRoot, fn ($query) => $query->where('parent_id', $catalogRoot->id))
            ->when(! $catalogRoot, fn ($query) => $query->whereNull('parent_id'))
            ->whereIn('slug', self::MAIN_CATALOG_SLUGS)
            ->get()
            ->sortBy(fn ($category) => array_search($category->slug, self::MAIN_CATALOG_SLUGS, true))
            ->values();
    }

    private function subcategories(?Category $category)
    {
        if (! $category) {
            return collect();
        }

        return collect($category->childrenRecursive ?? [])
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->values();
    }

    private function categoryPathIds(?Category $category): array
    {
        $ids = [];

        while ($category) {
            if ($category->slug !== self::CATALOG_ROOT_SLUG) {
                $ids[] = $category->id;
            }
            $category = $category->parent;
        }

        return $ids;
    }

    private function categoryTiles(?Category $category)
    {
        return $category ? collect() : $this->catalogMainSections();
    }

    private function shouldShowProducts(Request $request, ?Category $category): bool
    {
        $hasProductIntent = $request->filled('q')
            || $request->filled('brand')
            || $request->boolean('in_stock')
            || $request->boolean('discounted')
            || $request->filled('sort');

        if ($hasProductIntent) {
            return true;
        }

        if (! $category) {
            return true;
        }

        return true;
    }

    private function categoryBreadcrumbs(?Category $category): array
    {
        $items = [];

        while ($category) {
            if ($category->slug !== self::CATALOG_ROOT_SLUG) {
                array_unshift($items, $category);
            }
            $category = $category->parent;
        }

        return $items;
    }

    private function sideNavigation(?Category $category): array
    {
        if (! $category) {
            return ['title' => '', 'items' => collect(), 'back' => null];
        }

        $parent = $category->parent;

        return [
            'title' => $parent?->display_name ?? __('ui.catalog'),
            'items' => $parent ? $parent->childrenRecursive : $this->catalogTree(),
            'back' => $parent,
        ];
    }
}
