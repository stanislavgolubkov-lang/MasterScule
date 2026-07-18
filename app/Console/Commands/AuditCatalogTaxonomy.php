<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditCatalogTaxonomy extends Command
{
    protected $signature = 'masterscule:audit-catalog-taxonomy {--json : Print machine-readable JSON only}';

    protected $description = 'Audit catalog taxonomy integrity, placement specificity and automation safety';

    public function handle(): int
    {
        $aliases = array_keys(config('catalog_taxonomy.aliases', []));
        $root = Category::where('slug', config('catalog_taxonomy.root'))->first();
        $rootIds = $root?->descendantsAndSelfIds() ?? [];
        $overrideMismatches = collect(config('catalog_taxonomy.sku_overrides', []))
            ->filter(function (string $slug, string $sku): bool {
                return Product::where('sku', $sku)->with('category:id,slug')->first()?->category?->slug !== $slug;
            })
            ->all();

        $metrics = [
            'products' => Product::count(),
            'published' => Product::where('status', 'published')->count(),
            'drafts' => Product::where('status', 'draft')->count(),
            'alias_products' => Product::whereHas('category', fn ($query) => $query->whereIn('slug', $aliases))->count(),
            'nonassignable_products' => Product::whereHas('category', fn ($query) => $query->where('is_assignable', false))->count(),
            'missing_category' => Product::whereDoesntHave('category')->count(),
            'orphan_parser_links' => Product::whereNotNull('source_parser_item_id')->whereDoesntHave('parserItem')->count(),
            'products_without_parser_link' => Product::whereNull('source_parser_item_id')->count(),
            'learning_rows' => DB::table('product_parser_category_learnings')->count(),
            'unverified_learning_rows' => DB::table('product_parser_category_learnings')
                ->whereNotIn('source', ['admin_verified', 'catalog_agent_verified'])->count(),
            'duplicate_or_missing_pivots' => (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate FROM (SELECT p.id FROM products p LEFT JOIN category_product cp ON cp.product_id = p.id GROUP BY p.id HAVING COUNT(cp.id) <> 1) x'
            )->aggregate,
            'primary_mismatches' => (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate FROM products p JOIN category_product cp ON cp.product_id = p.id AND cp.is_primary = 1 WHERE cp.category_id <> p.category_id'
            )->aggregate,
            'duplicate_skus' => (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate FROM (SELECT sku FROM products GROUP BY sku HAVING COUNT(*) > 1) x'
            )->aggregate,
            'orphan_category_parents' => (int) DB::selectOne(
                'SELECT COUNT(*) AS aggregate FROM categories c LEFT JOIN categories p ON p.id = c.parent_id WHERE c.parent_id IS NOT NULL AND p.id IS NULL'
            )->aggregate,
            'category_cycles' => $this->categoryCycles(),
            'products_outside_root' => $rootIds === [] ? Product::count() : Product::whereNotIn('category_id', $rootIds)->count(),
            'override_mismatches' => count($overrideMismatches),
            'nonleaf_products' => Product::whereHas('category.children', fn ($query) => $query->where('is_active', true)->where('is_assignable', true))->count(),
            'published_nonleaf_products' => Product::where('status', 'published')
                ->whereHas('category.children', fn ($query) => $query->where('is_active', true)->where('is_assignable', true))->count(),
            'empty_assignable_leaves' => Category::where('is_active', true)->where('is_assignable', true)
                ->whereDoesntHave('children', fn ($query) => $query->where('is_active', true)->where('is_assignable', true))
                ->whereDoesntHave('primaryProducts')->count(),
            'visible_categories_without_images' => Category::where('is_menu_visible', true)
                ->where(fn ($query) => $query->whereNull('image')->orWhere('image', ''))->count(),
            'polluted_group_vde_attributes' => Product::where('attributes', 'like', '%"Group":"VDE"%')->count(),
            'queued_jobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
        ];

        $broadCategories = Category::query()
            ->whereHas('children', fn ($query) => $query->where('is_active', true)->where('is_assignable', true))
            ->withCount('primaryProducts')
            ->get(['id', 'slug'])
            ->filter(fn (Category $category) => $category->primary_products_count > 0)
            ->sortByDesc('primary_products_count')
            ->take(10)
            ->values()
            ->map(fn (Category $category) => [
                'slug' => $category->slug,
                'products' => $category->primary_products_count,
            ])->all();

        $payload = compact('metrics', 'broadCategories', 'overrideMismatches');
        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Metric', 'Value'], collect($metrics)->map(fn ($value, $key) => [$key, $value])->values()->all());
            $this->table(['Broad category', 'Direct products'], collect($broadCategories)->map(fn ($row) => [$row['slug'], $row['products']])->all());
        }

        $critical = collect([
            'alias_products', 'nonassignable_products', 'missing_category', 'orphan_parser_links',
            'duplicate_or_missing_pivots', 'primary_mismatches', 'duplicate_skus', 'orphan_category_parents',
            'category_cycles', 'products_outside_root', 'override_mismatches', 'unverified_learning_rows',
        ])->sum(fn (string $key) => (int) $metrics[$key]);

        return $critical === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function categoryCycles(): int
    {
        $parents = Category::pluck('parent_id', 'id')->all();
        $cycles = 0;

        foreach (array_keys($parents) as $categoryId) {
            $seen = [];
            $current = $categoryId;
            while ($current !== null && array_key_exists($current, $parents)) {
                if (isset($seen[$current])) {
                    $cycles++;
                    break;
                }
                $seen[$current] = true;
                $current = $parents[$current];
            }
        }

        return $cycles;
    }
}
