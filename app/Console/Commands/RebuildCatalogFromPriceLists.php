<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParserBatch;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Models\ProductParserSource;
use App\Services\ProductCatalogClassifier;
use App\Services\ProductDraftService;
use App\Services\ProductPriceListImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RebuildCatalogFromPriceLists extends Command
{
    protected $signature = 'masterscule:rebuild-catalog-from-prices
        {paths* : Supplier XLS/XLSX/CSV files}
        {--execute : Confirm deletion of all current products and product media}';

    protected $description = 'Safely purge only catalog products/media and recreate the catalog from supplier price lists';

    public function handle(
        ProductPriceListImportService $importer,
        ProductDraftService $drafts,
        ProductCatalogClassifier $classifier,
    ): int {
        if (! $this->option('execute')) {
            $this->error('Destructive rebuild requires --execute.');

            return self::FAILURE;
        }

        $paths = collect($this->argument('paths'))->map(fn ($path) => (string) $path)->values();
        $missing = $paths->reject(fn ($path) => is_file($path));

        if ($paths->isEmpty() || $missing->isNotEmpty()) {
            $missing->each(fn ($path) => $this->error('Price list not found: '.$path));

            return self::FAILURE;
        }

        $run = now()->format('Ymd-His');
        $categoryMap = $this->categoryMap();
        $snapshotPath = 'catalog-rebuild/'.$run.'/category-map.json';
        Storage::disk('local')->put($snapshotPath, json_encode($categoryMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $backup = $this->backupDatabase($run);

        $this->info('Saved category map for '.count($categoryMap).' products.');
        $this->purgeCatalog();
        $this->info('Old products and product media were removed.');

        $batchIds = [];

        foreach ($paths as $path) {
            $batch = $this->createBatch($path, $run);
            $batchIds[] = $batch->id;
            $this->line('Importing '.basename($path).'...');
            $importer->import($batch);
            $batch->refresh();
            $this->line("batch={$batch->id} products={$batch->product_rows} drafts={$batch->created_drafts} errors={$batch->error_rows}");
            $this->createMissingDrafts($batch, $categoryMap, $drafts);
        }

        $priceConflicts = $this->reconcileDuplicateSkuRows($batchIds);
        $this->restoreCategoryLinks($categoryMap, $classifier);
        $this->publishInStockProducts();
        $audit = $this->auditPrices($batchIds);
        $this->vacuumDatabase();

        $report = [
            'products' => Product::count(),
            'active_in_stock' => Product::availableForSale()->count(),
            'out_of_stock_hidden' => Product::where('stock_quantity', '<=', 0)->where('is_active', false)->count(),
            'products_with_multiple_categories' => Product::has('categories', '>=', 2)->count(),
            'price_rows_checked' => $audit['checked'],
            'price_mismatches' => $audit['mismatches'],
            'source_price_conflicts' => $priceConflicts,
            'batch_ids' => $batchIds,
            'category_snapshot' => Storage::disk('local')->path($snapshotPath),
            'database_backup' => $backup,
        ];

        Storage::disk('local')->put('catalog-rebuild/'.$run.'/report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $audit['mismatches'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function categoryMap(): array
    {
        $map = [];

        Product::with(['brand:id,name', 'categories:id'])->orderBy('id')->chunkById(500, function ($products) use (&$map) {
            foreach ($products as $product) {
                $ids = $product->categories->pluck('id')->push($product->category_id)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
                $map[$this->key($product->sku)] = [
                    'primary' => (int) $product->category_id,
                    'categories' => $ids,
                ];
            }
        });

        return $map;
    }

    private function backupDatabase(string $run): ?string
    {
        if (config('database.default') !== 'sqlite') {
            return null;
        }

        $source = config('database.connections.sqlite.database');
        $isAbsoluteWindowsPath = $source && preg_match('/^[A-Za-z]:[\\\\\/]/', $source) === 1;
        $source = $source && ! $isAbsoluteWindowsPath && ! Str::startsWith($source, ['/', '\\'])
            ? base_path($source)
            : $source;

        if (! $source || ! is_file($source)) {
            return null;
        }

        $directory = storage_path('app/private/catalog-rebuild/'.$run);
        File::ensureDirectoryExists($directory);
        $target = $directory.'/database-before-rebuild.sqlite';
        File::copy($source, $target);

        return $target;
    }

    private function purgeCatalog(): void
    {
        $placeholder = public_path('images/products/product-placeholder-toolbox.svg');
        $placeholderBytes = is_file($placeholder) ? file_get_contents($placeholder) : null;
        $targets = [
            public_path('images/products'),
            storage_path('app/public/products'),
            storage_path('app/public/parser/imports'),
        ];
        $roots = [public_path(), storage_path('app/public')];

        foreach ($targets as $target) {
            $normalized = str_replace('\\', '/', $target);
            $allowed = collect($roots)->contains(fn ($root) => Str::startsWith($normalized, rtrim(str_replace('\\', '/', $root), '/').'/'));

            if (! $allowed) {
                throw new \RuntimeException('Refusing to delete outside product media roots: '.$target);
            }

            if (File::isDirectory($target)) {
                File::deleteDirectory($target);
            }
        }

        if ($placeholderBytes !== null) {
            File::ensureDirectoryExists(dirname($placeholder));
            File::put($placeholder, $placeholderBytes);
        }

        DB::transaction(function () {
            // Explicit child-table deletes are considerably faster than thousands of
            // repeated cascade checks in SQLite while preserving the same data boundary.
            ProductParserImageAsset::query()->delete();
            ProductParserSource::query()->delete();
            ProductParserItem::query()->delete();
            ProductParserBatch::query()->delete();
            ProductImage::query()->delete();
            DB::table('category_product')->delete();
            DB::table('wishlist_items')->delete();
            DB::table('comparison_items')->delete();
            DB::table('order_items')->whereNotNull('product_id')->update(['product_id' => null]);
            Product::query()->delete();
        });
    }

    private function createBatch(string $path, string $run): ProductParserBatch
    {
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $safeName = Str::slug(pathinfo($path, PATHINFO_FILENAME)) ?: uniqid('price-', true);
        $storedPath = "parser/imports/catalog-rebuild/{$run}/{$safeName}.{$extension}";
        Storage::disk('local')->put($storedPath, file_get_contents($path));

        return ProductParserBatch::create([
            'title' => 'Catalog rebuild - '.basename($path),
            'source_type' => 'price_list',
            'supplier_name' => pathinfo($path, PATHINFO_FILENAME),
            'file_name' => basename($path),
            'file_path' => $storedPath,
            'file_type' => $extension,
            'price_type' => 'retail_price',
            'import_mode' => 'create_drafts',
            'status' => 'pending',
            'options_json' => [
                'search_images' => false,
                'process_images' => false,
                'create_drafts_automatically' => true,
                'add_photos_to_existing' => false,
                'replace_existing_photos' => false,
                'catalog_rebuild' => true,
            ],
        ]);
    }

    private function createMissingDrafts(ProductParserBatch $batch, array $map, ProductDraftService $drafts): void
    {
        $fallback = Category::where('slug', 'instrument-manual')->value('id') ?: Category::value('id');

        $batch->items()->whereNull('created_product_id')->whereNull('existing_product_id')->where('status', '!=', 'skipped')->orderBy('id')->chunkById(300, function ($items) use ($map, $drafts, $fallback) {
            foreach ($items as $item) {
                if (Product::where('sku', $item->sku)->exists()) {
                    continue;
                }

                $snapshot = $map[$this->key($item->sku)] ?? null;
                $categoryId = $snapshot['primary'] ?? $item->detected_category_id ?? $fallback;
                $item->forceFill([
                    'category_id' => $categoryId,
                    'needs_category_review' => false,
                    'status' => 'ready_for_review',
                ])->save();

                try {
                    $drafts->createDraft($item->fresh(['category', 'batch']));
                } catch (Throwable $e) {
                    $item->forceFill(['status' => 'failed', 'error_message' => $e->getMessage()])->save();
                }
            }
        });
    }

    private function restoreCategoryLinks(array $map, ProductCatalogClassifier $classifier): void
    {
        Product::with('brand:id,name')->orderBy('id')->chunkById(300, function ($products) use ($map, $classifier) {
            foreach ($products as $product) {
                $snapshot = $map[$this->key($product->sku)] ?? null;

                if ($snapshot) {
                    $validIds = Category::whereIn('id', $snapshot['categories'])->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $primary = Category::whereKey($snapshot['primary'])->exists() ? (int) $snapshot['primary'] : ($validIds[0] ?? $product->category_id);
                    $product->forceFill(['category_id' => $primary])->save();
                    $product->syncCategoryLinks($validIds, $primary, 'catalog_rebuild_snapshot');
                    continue;
                }

                $result = $classifier->classify($product);
                $ids = $classifier->idsForSlugs($result['category_slugs']);
                $primary = Category::where('slug', $result['primary_slug'])->value('id') ?: $product->category_id;
                $product->forceFill(['category_id' => $primary])->save();
                $product->syncCategoryLinks($ids, $primary, 'catalog_rebuild_classifier', $classifier->confidenceById($result['scores']));
            }
        });
    }

    private function publishInStockProducts(): void
    {
        Product::orderBy('id')->chunkById(500, function ($products) {
            foreach ($products as $product) {
                $inStock = (int) $product->stock_quantity > 0;
                $product->forceFill([
                    'stock_status' => $inStock ? 'in_stock' : 'out_of_stock',
                    'status' => $inStock ? 'published' : 'draft',
                    'approval_status' => $inStock ? 'approved' : 'pending_review',
                    'is_active' => $inStock,
                    'needs_review' => false,
                    'needs_image_review' => true,
                ])->save();
            }
        });
    }

    private function reconcileDuplicateSkuRows(array $batchIds): int
    {
        $conflicts = 0;
        $items = ProductParserItem::whereIn('batch_id', $batchIds)
            ->whereNotNull('parsed_price')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (ProductParserItem $item) => $this->key($item->sku));

        foreach ($items as $rows) {
            if ($rows->count() < 2) {
                continue;
            }

            $withStock = $rows->filter(fn (ProductParserItem $item) => $item->parsed_stock !== null);
            $canonical = ($withStock->isNotEmpty() ? $withStock : $rows)->last();
            $prices = $rows->pluck('parsed_price')->map(fn ($price) => (float) $price)->unique()->values();
            $conflicts += $prices->count() > 1 ? 1 : 0;
            $stock = $prices->count() === 1
                ? $withStock->pluck('parsed_stock')->filter(fn ($value) => $value !== null)->sum()
                : (int) ($canonical->parsed_stock ?? 0);

            Product::where('sku', $canonical->sku)->update([
                'price' => (float) $canonical->parsed_price,
                'stock_quantity' => $stock,
            ]);
        }

        return $conflicts;
    }

    private function auditPrices(array $batchIds): array
    {
        $checked = 0;
        $mismatches = 0;
        $items = ProductParserItem::whereIn('batch_id', $batchIds)
            ->whereNotNull('parsed_price')
            ->get()
            ->groupBy(fn (ProductParserItem $item) => $this->key($item->sku));

        foreach ($items as $rows) {
            $product = Product::where('sku', $rows->first()->sku)->first();

            if (! $product) {
                continue;
            }

            $checked++;
            $acceptedPrices = $rows->pluck('parsed_price')->map(fn ($price) => round((float) $price, 2))->unique();
            if (! $acceptedPrices->contains(round((float) $product->price, 2)) || $product->currency !== 'MDL') {
                $mismatches++;
            }
        }

        return compact('checked', 'mismatches');
    }

    private function vacuumDatabase(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('VACUUM');
        }
    }

    private function key(string $sku): string
    {
        return Str::lower((string) preg_replace('/[^a-z0-9]/i', '', $sku));
    }
}
