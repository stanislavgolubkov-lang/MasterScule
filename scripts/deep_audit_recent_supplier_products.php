<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$batchIds = [26, 27, 28];
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--batches=')) {
        $batchIds = array_values(array_filter(array_map('intval', explode(',', substr($argument, 10)))));
    }
}

$products = DB::table('products as p')
    ->join('product_parser_items as i', 'i.id', '=', 'p.source_parser_item_id')
    ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
    ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
    ->leftJoin('product_parser_image_assets as a', function ($join): void {
        $join->on('a.parser_item_id', '=', 'i.id')->where('a.is_main', '=', 1);
    })
    ->whereIn('p.source_import_batch_id', $batchIds)
    ->orderBy('p.source_import_batch_id')
    ->orderBy('i.row_number')
    ->get([
        'p.id', 'p.source_import_batch_id as batch_id', 'p.sku', 'b.name as brand',
        'i.raw_name', 'i.parsed_price', 'i.parsed_stock', 'i.found_specs_json',
        'i.image_source_type as asset_source_type', 'i.source_match_confidence', 'i.tristools_url',
        'p.name_ru', 'p.name_ro', 'p.short_description_ru', 'p.short_description_ro',
        'p.description_ru', 'p.description_ro', 'p.price', 'p.stock_quantity',
        'p.stock_status', 'p.status', 'p.approval_status', 'p.is_active',
        'p.main_image', 'p.gallery', 'p.attributes', 'p.category_id',
        'c.name as category_ru', 'c.name_ro as category_ro', 'c.slug as category_slug',
        'p.needs_review', 'p.needs_image_review', 'p.needs_category_review',
        'p.needs_translation_review', 'p.needs_price_review', 'p.needs_content_review',
        'p.needs_source_review', 'p.generated_content', 'p.source_url', 'p.source_domain',
        'a.source_url as image_source_url', 'a.source_domain as image_source_domain',
        'a.has_watermark', 'a.needs_review as image_asset_needs_review',
    ]);

$issueDefinitions = [
    'missing_name_ru' => static fn ($p): bool => blank($p->name_ru),
    'missing_name_ro' => static fn ($p): bool => blank($p->name_ro),
    'missing_description_ru' => static fn ($p): bool => blank($p->description_ru),
    'missing_description_ro' => static fn ($p): bool => blank($p->description_ro),
    'romanian_contains_cyrillic' => static fn ($p): bool => preg_match('/\p{Cyrillic}/u', (string) $p->name_ro.' '.(string) $p->description_ro) === 1,
    'generic_description_ru' => static fn ($p): bool => Str::contains((string) $p->description_ru, ['товар бренда', 'Перед применением проверьте характеристики']),
    'generic_description_ro' => static fn ($p): bool => Str::contains((string) $p->description_ro, ['face parte din gama profesionala', 'compatibilitatea cu lucrarea planificata']),
    'short_description_ru' => static fn ($p): bool => mb_strlen(trim((string) $p->description_ru)) < 80,
    'short_description_ro' => static fn ($p): bool => mb_strlen(trim((string) $p->description_ro)) < 80,
    'price_mismatch' => static fn ($p): bool => $p->parsed_price !== null && abs((float) $p->price - (float) $p->parsed_price) > 0.01,
    'missing_supplier_price' => static fn ($p): bool => $p->parsed_price === null || (float) $p->price <= 0,
    'stock_mismatch' => static fn ($p): bool => $p->parsed_stock !== null && (int) $p->stock_quantity !== max(0, (int) $p->parsed_stock),
    'stock_status_mismatch' => static fn ($p): bool => ((int) $p->stock_quantity > 0) !== ($p->stock_status === 'in_stock'),
    'missing_category' => static fn ($p): bool => blank($p->category_id) || blank($p->category_slug),
    'missing_image' => static fn ($p): bool => blank($p->main_image) || str_starts_with((string) $p->main_image, '/images/brand/'),
    'missing_watermark' => static fn ($p): bool => ! (bool) $p->has_watermark,
    'image_family_or_equivalent' => static fn ($p): bool => Str::contains((string) $p->asset_source_type, ['family', 'equivalent']),
    'review_flags' => static fn ($p): bool => (bool) $p->needs_review
        || (bool) $p->needs_image_review
        || (bool) $p->needs_category_review
        || (bool) $p->needs_translation_review
        || (bool) $p->needs_price_review
        || (bool) $p->needs_content_review
        || (bool) $p->needs_source_review,
];

$report = [
    'generated_at' => now()->toIso8601String(),
    'batches' => [],
    'total_products' => $products->count(),
    'issues' => [],
    'issue_products' => [],
    'duplicate_skus' => DB::table('products')
        ->selectRaw('sku, COUNT(*) as total')
        ->whereNotNull('sku')
        ->groupBy('sku')
        ->havingRaw('COUNT(*) > 1')
        ->orderByDesc('total')
        ->get(),
];

foreach ($products->groupBy('batch_id') as $batchId => $batchProducts) {
    $report['batches'][(string) $batchId] = [
        'products' => $batchProducts->count(),
        'brands' => $batchProducts->countBy('brand')->all(),
        'statuses' => $batchProducts->countBy('status')->all(),
        'categories' => $batchProducts->countBy(static fn ($p): string => (string) ($p->category_slug ?: 'missing'))->all(),
        'image_source_types' => $batchProducts->countBy(static fn ($p): string => (string) ($p->asset_source_type ?: 'missing'))->all(),
    ];
}

foreach ($issueDefinitions as $issue => $predicate) {
    $affected = $products->filter($predicate)->values();
    $report['issues'][$issue] = $affected->count();
    $report['issue_products'][$issue] = $affected->map(static fn ($p): array => [
        'batch_id' => $p->batch_id,
        'id' => $p->id,
        'sku' => $p->sku,
        'brand' => $p->brand,
        'raw_name' => $p->raw_name,
        'name_ru' => $p->name_ru,
        'name_ro' => $p->name_ro,
        'category' => $p->category_slug,
        'image' => $p->main_image,
        'image_source_type' => $p->asset_source_type,
    ])->all();
}

$outputPath = storage_path('app/parser/deep-audit-recent-suppliers.json');
if (! is_dir(dirname($outputPath))) {
    mkdir(dirname($outputPath), 0775, true);
}
file_put_contents(
    $outputPath,
    json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL,
);

echo json_encode([
    'total_products' => $report['total_products'],
    'issues' => $report['issues'],
    'batches' => $report['batches'],
    'duplicate_skus' => $report['duplicate_skus']->count(),
    'report' => $outputPath,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;
