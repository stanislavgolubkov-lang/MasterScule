<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$reviewColumns = [
    'needs_review',
    'needs_stock_review',
    'needs_image_review',
    'needs_category_review',
    'needs_translation_review',
    'needs_price_review',
    'needs_content_review',
    'needs_source_review',
];

$reviewFlags = [];
foreach ($reviewColumns as $column) {
    $reviewFlags[$column] = DB::table('products')->where($column, true)->count();
}

$textColumns = ['name', 'name_ru', 'name_ro', 'short_description', 'short_description_ru', 'short_description_ro', 'description', 'description_ru', 'description_ro', 'attributes'];
$maximumMentions = DB::table('products')->where(function ($query) use ($textColumns): void {
    foreach ($textColumns as $index => $column) {
        $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
        $query->{$method}("LOWER(COALESCE({$column}, '')) LIKE ?", ['%maximum%']);
    }
})->select('id', 'sku')->orderBy('sku')->get()->all();

$foreignMarketplacePatterns = [
    '%продаж%цен%',
    '%vânzare%preț%',
    '%vanzare%pret%',
    '%интернет-магазин%',
    '%magazin online%',
    '%подробная информация о товаре%',
    '%informații detaliate despre produs%',
    '%цена и условия поставки%',
    '%pret si conditii de livrare%',
    '%киев%',
    '%kiev%',
];
$foreignMarketplaceCopy = DB::table('products')->where(function ($query) use ($textColumns, $foreignMarketplacePatterns): void {
    foreach ($textColumns as $column) {
        foreach ($foreignMarketplacePatterns as $pattern) {
            $query->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$pattern]);
        }
    }
})->get(['id', 'sku', 'name_ru', 'name_ro'])->all();

$nameRows = DB::table('products as products')
    ->leftJoin('categories as categories', 'categories.id', '=', 'products.category_id')
    ->orderBy('products.sku')
    ->get(['products.id', 'products.sku', 'products.name_ru', 'products.name_ro', 'products.status', 'products.attributes', 'products.description_ru', 'categories.slug as category_slug']);
$nameAnomalies = [
    'very_short_ru' => $nameRows->filter(fn ($row): bool => mb_strlen(trim((string) $row->name_ru)) < 8)->values()->all(),
    'very_short_ro' => $nameRows->filter(fn ($row): bool => mb_strlen(trim((string) $row->name_ro)) < 8)->values()->all(),
    'leading_lowercase_ru' => $nameRows->filter(fn ($row): bool => preg_match('/^\p{Ll}/u', trim((string) $row->name_ru)) === 1)->values()->all(),
    'leading_lowercase_ro' => $nameRows->filter(fn ($row): bool => preg_match('/^\p{Ll}/u', trim((string) $row->name_ro)) === 1)->values()->all(),
];

$payload = [
    'review_flags' => $reviewFlags,
    'stock_and_price' => [
        'non_positive_price' => DB::table('products')->where('price', '<=', 0)->count(),
        'old_price_not_above_price' => DB::table('products')->whereNotNull('old_price')->whereColumn('old_price', '<=', 'price')->count(),
        'in_stock_with_zero_quantity' => DB::table('products')->where('stock_status', 'in_stock')->where('stock_quantity', '<=', 0)->count(),
        'positive_quantity_not_in_stock' => DB::table('products')->where('stock_quantity', '>', 0)->where('stock_status', '!=', 'in_stock')->count(),
        'published_out_of_stock' => DB::table('products')->where('status', 'published')->where(fn ($query) => $query->where('stock_status', '!=', 'in_stock')->orWhere('stock_quantity', '<=', 0))->count(),
        'unexpected_currency' => DB::table('products')->where('currency', '!=', 'MDL')->count(),
    ],
    'maximum_mentions' => $maximumMentions,
    'foreign_marketplace_copy' => $foreignMarketplaceCopy,
    'name_anomalies' => $nameAnomalies,
    'maximum_provenance' => [
        'products' => DB::table('products')->where(fn ($query) => $query->whereRaw("LOWER(COALESCE(source_url,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(parser_source_urls,'')) LIKE '%maximum%'"))->count(),
        'parser_items' => DB::table('product_parser_items')->where(fn ($query) => $query->whereRaw("LOWER(COALESCE(official_source_url,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(fallback_source_url,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(source_urls_json,'')) LIKE '%maximum%'"))->count(),
        'parser_sources' => DB::table('product_parser_sources')->where(fn ($query) => $query->whereRaw("LOWER(COALESCE(domain,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(url,'')) LIKE '%maximum%'"))->count(),
        'product_images' => DB::table('product_images')->where(fn ($query) => $query->whereRaw("LOWER(COALESCE(source_url,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(source_page_url,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(source_domain,'')) LIKE '%maximum%'"))->count(),
        'parser_image_assets' => DB::table('product_parser_image_assets')->where(fn ($query) => $query->whereRaw("LOWER(COALESCE(source_url,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(source_domain,'')) LIKE '%maximum%'"))->count(),
        'product_rows' => DB::table('products')->where(fn ($query) => $query->whereRaw("LOWER(COALESCE(source_url,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(parser_source_urls,'')) LIKE '%maximum%'"))->get(['id', 'sku', 'source_url', 'parser_source_urls'])->all(),
        'parser_source_rows' => DB::table('product_parser_sources as sources')->leftJoin('product_parser_items as items', 'items.id', '=', 'sources.parser_item_id')->where(fn ($query) => $query->whereRaw("LOWER(COALESCE(sources.domain,'')) LIKE '%maximum%'")->orWhereRaw("LOWER(COALESCE(sources.url,'')) LIKE '%maximum%'"))->get(['sources.id', 'items.sku', 'sources.url', 'sources.domain'])->all(),
    ],
    'placeholder_path_audit' => [
        'published_count' => DB::table('products')->where('status', 'published')->where(function ($query): void {
            $query->whereRaw("LOWER(COALESCE(main_image,'')) LIKE '%placeholder%'")
                ->orWhereRaw("LOWER(COALESCE(main_image,'')) LIKE '%no-image%'")
                ->orWhereRaw("LOWER(COALESCE(main_image,'')) LIKE '%no_image%'")
                ->orWhereRaw("LOWER(COALESCE(main_image,'')) LIKE '%gys-product.svg%'");
        })->count(),
        'samples' => DB::table('products')->where('status', 'published')->where(function ($query): void {
            $query->whereRaw("LOWER(COALESCE(main_image,'')) LIKE '%placeholder%'")
                ->orWhereRaw("LOWER(COALESCE(main_image,'')) LIKE '%no-image%'")
                ->orWhereRaw("LOWER(COALESCE(main_image,'')) LIKE '%no_image%'")
                ->orWhereRaw("LOWER(COALESCE(main_image,'')) LIKE '%gys-product.svg%'");
        })->orderBy('sku')->limit(40)->get(['id', 'sku', 'name_ru', 'main_image', 'needs_image_review'])->all(),
    ],
    'primary_mismatches' => DB::select(
        'SELECT p.id,p.sku,p.category_id AS product_category_id,c.slug AS product_category,cp.category_id AS pivot_category_id,pc.slug AS pivot_category FROM products p JOIN categories c ON c.id=p.category_id JOIN category_product cp ON cp.product_id=p.id AND cp.is_primary=1 JOIN categories pc ON pc.id=cp.category_id WHERE cp.category_id<>p.category_id'
    ),
    'visible_categories_without_images' => DB::table('categories as categories')
        ->leftJoin('categories as parents', 'parents.id', '=', 'categories.parent_id')
        ->where('categories.is_menu_visible', true)
        ->where(fn ($query) => $query->whereNull('categories.image')->orWhere('categories.image', ''))
        ->orderBy('categories.id')
        ->get([
            'categories.id', 'categories.slug', 'categories.name', 'categories.name_ro', 'categories.parent_id',
            'categories.icon', 'categories.is_assignable', 'parents.slug as parent_slug', 'parents.image as parent_image',
        ])->all(),
    'empty_assignable_leaf_categories' => DB::select(
        'SELECT c.id,c.slug,c.name,c.name_ro,c.is_menu_visible,c.is_active,c.image FROM categories c LEFT JOIN categories child ON child.parent_id=c.id LEFT JOIN products p ON p.category_id=c.id WHERE c.is_assignable=1 GROUP BY c.id,c.slug,c.name,c.name_ro,c.is_menu_visible,c.is_active,c.image HAVING COUNT(DISTINCT child.id)=0 AND COUNT(DISTINCT p.id)=0 ORDER BY c.id'
    ),
    'parser_image_review' => DB::select(
        'SELECT i.id,i.sku,i.batch_id,i.created_product_id,i.status,p.status AS product_status,p.main_image FROM product_parser_items i LEFT JOIN products p ON p.id=i.created_product_id WHERE i.needs_image_review=1 ORDER BY i.sku,i.id'
    ),
    'parser_status_counts' => DB::table('product_parser_items')->select('status', DB::raw('COUNT(*) AS count'))->groupBy('status')->orderBy('status')->get()->all(),
    'parser_nonterminal_rows' => DB::table('product_parser_items as items')->leftJoin('products as products', 'products.sku', '=', 'items.sku')->whereIn('items.status', ['queued', 'searching', 'tristool_queued', 'tristool_searching', 'ready_for_review', 'needs_manual_review'])->orderBy('items.status')->orderBy('items.sku')->get(['items.id', 'items.sku', 'items.status', 'items.processing_stage', 'items.batch_id', 'items.created_product_id', 'items.existing_product_id', 'products.id as matching_product_id', 'products.status as matching_product_status'])->all(),
    'duplicate_parser_skus' => DB::select(
        "SELECT sku,COUNT(*) AS occurrences,GROUP_CONCAT(id) AS item_ids,GROUP_CONCAT(COALESCE(status,'')) AS statuses,GROUP_CONCAT(COALESCE(created_product_id,'')) AS product_ids FROM product_parser_items GROUP BY sku HAVING COUNT(*)>1 ORDER BY sku"
    ),
    'duplicate_product_descriptions' => DB::select(
        "SELECT COUNT(*) AS groups_count FROM (SELECT description_ru FROM products WHERE COALESCE(description_ru,'')<>'' GROUP BY description_ru HAVING COUNT(DISTINCT sku)>1) duplicated"
    )[0]->groups_count ?? 0,
    'attribute_cardinality' => [
        'without_attributes' => DB::select("SELECT COUNT(*) AS aggregate FROM products p WHERE (SELECT COUNT(*) FROM json_each(COALESCE(NULLIF(p.attributes,''),'{}')))=0")[0]->aggregate ?? 0,
        'one_attribute' => DB::select("SELECT COUNT(*) AS aggregate FROM products p WHERE (SELECT COUNT(*) FROM json_each(COALESCE(NULLIF(p.attributes,''),'{}')))=1")[0]->aggregate ?? 0,
        'published_without_attributes' => DB::select("SELECT COUNT(*) AS aggregate FROM products p WHERE p.status='published' AND (SELECT COUNT(*) FROM json_each(COALESCE(NULLIF(p.attributes,''),'{}')))=0")[0]->aggregate ?? 0,
        'published_one_attribute' => DB::select("SELECT COUNT(*) AS aggregate FROM products p WHERE p.status='published' AND (SELECT COUNT(*) FROM json_each(COALESCE(NULLIF(p.attributes,''),'{}')))=1")[0]->aggregate ?? 0,
        'samples_without_attributes' => DB::select("SELECT id,sku,name_ru,status FROM products p WHERE (SELECT COUNT(*) FROM json_each(COALESCE(NULLIF(p.attributes,''),'{}')))=0 ORDER BY sku LIMIT 100"),
        'samples_one_attribute' => DB::select("SELECT id,sku,name_ru,status,attributes FROM products p WHERE (SELECT COUNT(*) FROM json_each(COALESCE(NULLIF(p.attributes,''),'{}')))=1 ORDER BY sku LIMIT 100"),
    ],
    'incomplete_section_descriptions' => DB::table('products')->where(function ($query): void {
        $query->whereIn('description_ru', ["Назначение:\nОбласть применения:", "Назначение:\r\nОбласть применения:"])
            ->orWhereIn('description_ro', ["Scop:\nDomeniul de aplicare:", "Scop:\r\nDomeniul de aplicare:"]);
    })->orderBy('sku')->get(['id', 'sku', 'name_ru', 'name_ro', 'description_ru', 'description_ro', 'attributes', 'status'])->all(),
    'jobs' => [
        'queued' => DB::table('jobs')->count(),
        'queued_rows' => DB::table('jobs')->orderBy('id')->get(['id', 'queue', 'attempts', 'available_at', 'created_at', 'payload'])->map(function ($row): array {
            $payload = json_decode((string) $row->payload, true);
            preg_match('/itemId";i:(\d+)/', (string) ($payload['data']['command'] ?? ''), $itemMatch);
            $itemId = isset($itemMatch[1]) ? (int) $itemMatch[1] : null;
            $item = $itemId ? DB::table('product_parser_items')->where('id', $itemId)->first(['sku', 'status', 'batch_id']) : null;

            return ['id' => $row->id, 'queue' => $row->queue, 'attempts' => $row->attempts, 'available_at' => $row->available_at, 'created_at' => $row->created_at, 'job' => $payload['displayName'] ?? null, 'item_id' => $itemId, 'sku' => $item?->sku, 'item_status' => $item?->status, 'batch_id' => $item?->batch_id];
        })->all(),
        'failed' => DB::table('failed_jobs')->count(),
        'failed_recent' => DB::table('failed_jobs')->orderByDesc('id')->limit(50)->get(['id', 'uuid', 'queue', 'failed_at', 'exception', 'payload'])->map(function ($row): array {
            $firstLine = strtok((string) $row->exception, "\r\n") ?: '';
            $payload = json_decode((string) $row->payload, true);
            preg_match('/itemId";i:(\d+)/', (string) ($payload['data']['command'] ?? ''), $itemMatch);
            $itemId = isset($itemMatch[1]) ? (int) $itemMatch[1] : null;
            $item = $itemId ? DB::table('product_parser_items')->where('id', $itemId)->first(['sku', 'status', 'created_product_id']) : null;

            return [
                'id' => $row->id,
                'uuid' => $row->uuid,
                'queue' => $row->queue,
                'failed_at' => $row->failed_at,
                'job' => $payload['displayName'] ?? null,
                'item_id' => $itemId,
                'sku' => $item?->sku,
                'item_status' => $item?->status,
                'product_id' => $item?->created_product_id,
                'error' => $firstLine,
            ];
        })->all(),
    ],
];

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
