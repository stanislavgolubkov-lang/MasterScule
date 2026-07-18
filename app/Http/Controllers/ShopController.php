<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Services\Catalog\CatalogStorefrontNavigation;
use App\Services\Catalog\ProductImageAvailabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ShopController extends Controller
{
    public function __construct(
        private CatalogStorefrontNavigation $catalogNavigation,
        private ProductImageAvailabilityService $productImages,
    ) {}

    private const CATALOG_ROOT_SLUG = 'instrumente-si-mobilier';

    private const HOME_RECOMMENDED_LIMIT = 50;

    private const NEW_PRODUCTS_PER_PAGE = 50;

    private const RELATED_PRODUCTS_LIMIT = 20;

    private const HOME_HIDDEN_QUICK_CATEGORY_SLUGS = [
        'echipamente-pentru-service',
        'sudura-richtuire-vopsire',
        'vulcanizare',
        'echipament-protectie',
    ];

    private const TASK_CATALOG_RULES = [
        'garage' => [
            'categories' => ['instrument-manual'],
        ],
        'service' => [
            'categories' => [
                'echipamente-pentru-service',
                'scule-speciale-auto',
                'scule-pneumatice',
                'instrumente-cu-acumulator',
                'instrumente-de-masurare',
            ],
            'relevance_keywords' => ['сервисная установка', 'технических жидкостей', 'прокачки', 'домкрат', 'подъемник', 'подъёмник', 'пресс', 'гидравл', 'cric', 'ridicare', 'hidraulic'],
        ],
        'tires' => [
            'categories' => [
                'vulcanizare',
                'scule-pentru-roti-vulcanizare',
                'chei-pneumatice',
                'cricuri-hidraulice',
                'scule-pentru-suspensie',
            ],
            'keywords' => ['шиномонтаж', 'подкачки шин', 'шины', 'anvelope', 'vulcanizare', 'wheel service', 'tire'],
            'relevance_keywords' => ['подкачки шин', 'шиномонтаж', 'гайковерт', 'гайковёрт', 'anvelope', 'vulcanizare', 'tire'],
        ],
        'pneumatic' => [
            'categories' => ['scule-pneumatice'],
            'relevance_keywords' => ['гайковерт', 'гайковёрт', 'impact wrench'],
        ],
        'brakes' => [
            'categories' => [
                'scule-pentru-frane',
                'scule-pentru-suspensie',
                'extractoare-si-prese',
                'dispozitive-pneumatice-service',
            ],
            'keywords' => ['тормоз', 'суппорт', 'подвеск', 'frane', 'suspensie', 'brake'],
        ],
        'engine' => [
            'categories' => [
                'grup-motor',
                'scule-pentru-motor',
                'scule-pentru-filtre-ulei',
                'diagnoza-auto',
                'macarale-standuri-suporti-motor',
            ],
            'keywords' => ['двигател', 'грм', 'распредвал', 'коленвал', 'motor', 'engine', 'timing'],
        ],
        'auto-repair' => [
            'categories' => [
                'grup-motor',
                'scule-pentru-motor',
                'scule-pentru-filtre-ulei',
                'scule-pentru-frane',
                'scule-pentru-suspensie',
                'extractoare-si-prese',
                'diagnoza-auto',
                'dispozitive-pneumatice-service',
            ],
            'keywords' => ['двигател', 'тормоз', 'суппорт', 'подвеск', 'motor', 'engine', 'frane', 'suspensie', 'brake'],
        ],
        'electric' => [
            'categories' => ['instrumente-electromontaj'],
        ],
        'workshop' => [
            'categories' => ['mobilier-pentru-service', 'dulapuri-si-organizare'],
            'keywords' => [
                'Тележк',
                'тележк',
                'Шкаф',
                'шкаф',
                'Верстак',
                'верстак',
                'Органайзер',
                'органайзер',
                'Ящик для инструмент',
                'ящик для инструмент',
                'Стол для сервис',
                'стол для сервис',
                'Держатель инструмент',
                'держатель инструмент',
                'Поддон магнит',
                'поддон магнит',
                'carucior',
                'dulap',
                'organizator',
                'banc de lucru',
                'tool chest',
                'workbench',
            ],
        ],
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
                ->whereIn('slug', config('catalog_taxonomy.main_sections', []))
                ->whereNotIn('slug', self::HOME_HIDDEN_QUICK_CATEGORY_SLUGS)
                ->get(),
            'featuredProducts' => $this->recommendedProducts(),
            'productsCount' => Product::availableForSale()->count(),
            'brands' => Brand::where('is_active', true)->orderByDesc('is_featured')->get(),
        ]);
    }

    public function catalog(Request $request, ?string $category = null)
    {
        $activeCategory = $category
            ? Category::with(['parent.parent', 'childrenRecursive'])->where('slug', $category)->firstOrFail()
            : null;

        $isEditorialMainSection = $activeCategory
            && in_array($activeCategory->slug, config('catalog_taxonomy.main_sections', []), true);

        if ($activeCategory
            && ! $isEditorialMainSection
            && ! $request->filled('task')
            && ! $this->catalogNavigation->isVisible($activeCategory)) {
            $fallback = $this->catalogNavigation->nearestVisibleAncestor($activeCategory->parent);

            return $fallback
                ? redirect()->route('catalog', $fallback->slug)
                : redirect()->route('catalog');
        }
        $categoryIds = $activeCategory?->descendantsAndSelfIds();
        $requestedTask = $request->string('task')->toString();
        $taskKey = array_key_exists($requestedTask, self::TASK_CATALOG_RULES) ? $requestedTask : null;
        $taskRule = $taskKey ? self::TASK_CATALOG_RULES[$taskKey] : null;

        $taskCategoryIds = $taskRule
            ? $this->categoryIdsForSlugs($taskRule['categories'] ?? [])
            : [];

        $showProducts = $this->shouldShowProducts($request, $activeCategory);

        $products = Product::with(['brand', 'category', 'categories'])
            ->availableForSale()
            ->when(! $showProducts, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($activeCategory && ! $taskRule, fn ($query) => $query->inCatalogCategories($categoryIds ?? []))
            ->when($taskRule, function ($query) use ($taskRule, $taskCategoryIds) {
                $query->where(function ($taskQuery) use ($taskRule, $taskCategoryIds) {
                    if ($taskCategoryIds !== []) {
                        $taskQuery->where(fn ($categoryQuery) => $categoryQuery->inCatalogCategories($taskCategoryIds));
                    } else {
                        $taskQuery->whereRaw('1 = 0');
                    }

                    foreach ($taskRule['keywords'] ?? [] as $keyword) {
                        $like = '%'.$keyword.'%';

                        $taskQuery->orWhere(function ($keywordQuery) use ($like) {
                            $keywordQuery
                                ->where('name', 'like', $like)
                                ->orWhere('name_ru', 'like', $like)
                                ->orWhere('name_ro', 'like', $like)
                                ->orWhere('short_description', 'like', $like)
                                ->orWhere('short_description_ru', 'like', $like)
                                ->orWhere('short_description_ro', 'like', $like)
                                ->orWhere('description', 'like', $like)
                                ->orWhere('description_ru', 'like', $like)
                                ->orWhere('description_ro', 'like', $like)
                                ->orWhere('attributes', 'like', $like);
                        });
                    }
                });
            })
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

        $sort = $request->string('sort')->toString();

        if ($sort === '' && $taskRule) {
            $this->orderByKeywordRelevance(
                $products,
                $taskRule['relevance_keywords'] ?? $taskRule['keywords'] ?? []
            );
        }

        match ($sort) {
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

        $similarProducts = Product::with('brand')
            ->where('id', '!=', $product->id)
            ->inCatalogCategories($similarCategoryIds)
            ->availableForSale()
            ->limit(self::RELATED_PRODUCTS_LIMIT)
            ->get();

        $remainingSlots = self::RELATED_PRODUCTS_LIMIT - $similarProducts->count();
        $brandProducts = collect();

        if ($remainingSlots > 0) {
            $brandProducts = Product::with('brand')
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $similarProducts->pluck('id'))
                ->where('brand_id', $product->brand_id)
                ->availableForSale()
                ->limit($remainingSlots)
                ->get();
        }

        return view('shop.product', [
            'product' => $product,
            'relatedProducts' => $similarProducts
                ->concat($brandProducts)
                ->values(),
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
        return view('shop.collection', [
            'title' => __('ui.promotions'),
            'subtitle' => __('ui.promotions_page_subtitle'),
            'products' => Product::with(['brand', 'category', 'categories'])
                ->where('is_discounted', true)
                ->availableForSale()
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->paginate(20),
            'collectionClass' => 'product-grid-compact promotions-product-grid',
            'emptyState' => 'promotions',
        ]);
    }

    public function newProducts()
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($products) => $products->purchasable())
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        $brandCount = $brands->count();
        $baseQuota = $brandCount > 0 ? intdiv(self::NEW_PRODUCTS_PER_PAGE, $brandCount) : 0;
        $extraSlots = $brandCount > 0 ? self::NEW_PRODUCTS_PER_PAGE % $brandCount : 0;

        $products = $brands
            ->flatMap(fn (Brand $brand, int $index) => Product::with(['brand', 'category', 'categories'])
                ->purchasable()
                ->where('brand_id', $brand->id)
                ->orderByDesc('is_new')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($baseQuota + ($index < $extraSlots ? 1 : 0))
                ->get())
            ->values();

        $remainingSlots = self::NEW_PRODUCTS_PER_PAGE - $products->count();

        if ($remainingSlots > 0 && $brands->isNotEmpty()) {
            $backfillProducts = Product::with(['brand', 'category', 'categories'])
                ->purchasable()
                ->whereIn('brand_id', $brands->pluck('id'))
                ->whereNotIn('id', $products->pluck('id'))
                ->orderByDesc('is_new')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit($remainingSlots)
                ->get();

            $products = $products->concat($backfillProducts)->values();
        }

        return view('shop.collection', [
            'title' => __('ui.new_items'),
            'subtitle' => __('ui.collection_text'),
            'products' => $products,
            'collectionClass' => 'product-grid-compact new-arrivals-grid',
            'collectionHero' => 'new',
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

    private function recommendedProducts()
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($products) => $products->availableForSale())
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        $brandCount = $brands->count();
        $baseQuota = $brandCount > 0 ? intdiv(self::HOME_RECOMMENDED_LIMIT, $brandCount) : 0;
        $extraSlots = $brandCount > 0 ? self::HOME_RECOMMENDED_LIMIT % $brandCount : 0;

        $products = $brands
            ->flatMap(fn (Brand $brand, int $index) => $this->productsWithAvailableImages(
                Product::with(['brand', 'category'])
                    ->availableForSale()
                    ->where('brand_id', $brand->id)
                    ->orderByDesc('is_featured')
                    ->orderByDesc('is_bestseller')
                    ->orderByDesc('is_new')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id'),
                $baseQuota + ($index < $extraSlots ? 1 : 0),
            ))
            ->values();

        $remainingSlots = self::HOME_RECOMMENDED_LIMIT - $products->count();

        if ($remainingSlots > 0 && $brands->isNotEmpty()) {
            $backfillProducts = $this->productsWithAvailableImages(
                Product::with(['brand', 'category'])
                    ->availableForSale()
                    ->whereIn('brand_id', $brands->pluck('id'))
                    ->whereNotIn('id', $products->pluck('id'))
                    ->orderByDesc('is_featured')
                    ->orderByDesc('is_bestseller')
                    ->orderByDesc('is_new')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id'),
                $remainingSlots,
            );

            $products = $products->concat($backfillProducts)->values();
        }

        return $products;
    }

    private function productsWithAvailableImages(Builder $query, int $limit): Collection
    {
        if ($limit <= 0) {
            return collect();
        }

        $products = collect();
        $offset = 0;
        $batchSize = max(30, $limit * 3);

        $query
            ->whereNotNull('main_image')
            ->where('main_image', '!=', '')
            ->where('main_image', 'not like', '%placeholder%')
            ->where('main_image', 'not like', '%no-image%')
            ->where('main_image', 'not like', '%no_image%')
            ->where('main_image', 'not like', '%missing-image%')
            ->where('main_image', 'not like', 'http%');

        do {
            $batch = (clone $query)
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            foreach ($batch as $product) {
                if ($this->productImages->isAvailable($product->main_image)) {
                    $products->push($product);
                }

                if ($products->count() >= $limit) {
                    break 2;
                }
            }

            $offset += $batch->count();
        } while ($batch->count() === $batchSize);

        return $products->take($limit)->values();
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

    private function categoryIdsForSlugs(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return Category::with('childrenRecursive')
            ->where('is_active', true)
            ->whereIn('slug', $slugs)
            ->get()
            ->flatMap(fn (Category $category) => $category->descendantsAndSelfIds())
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function orderByKeywordRelevance($query, array $keywords): void
    {
        $keywords = collect($keywords)
            ->map(fn ($keyword) => trim((string) $keyword))
            ->filter()
            ->unique()
            ->values();

        if ($keywords->isEmpty()) {
            return;
        }

        $columns = ['name', 'name_ru', 'name_ro', 'short_description', 'short_description_ru', 'short_description_ro'];
        $conditions = [];
        $bindings = [];

        foreach ($keywords as $keyword) {
            foreach ($columns as $column) {
                $conditions[] = "{$column} like ?";
                $bindings[] = '%'.$keyword.'%';
            }
        }

        $query->orderByRaw('case when '.implode(' or ', $conditions).' then 0 else 1 end', $bindings);
    }

    private function catalogMainSections()
    {
        return $this->catalogNavigation->mainSections();
    }

    private function subcategories(?Category $category)
    {
        if (! $category) {
            return collect();
        }

        return $this->catalogNavigation->children($category);
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
