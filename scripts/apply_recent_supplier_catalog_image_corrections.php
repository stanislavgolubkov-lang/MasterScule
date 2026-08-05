<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$spinCatalogue = 'https://www.spinsrl.it/wp-content/uploads/2026/06/MARCOTOOLS-2026-web.pdf';
$spinMainCatalogue = 'https://www.spinsrl.it/wp-content/uploads/2026/04/Catalogo-SPIN-2026-it-web.pdf';
$telwinCatalogue = 'https://www.telwin.com/it/doc/Cataloghi/BUSINESSCATALOGUE.pdf';

$corrections = [
    'WT000023L' => [$spinCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WT000036L' => [$spinCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WBIAT207A' => [$spinCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WT000014P' => [$spinCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WT000102R' => [$spinCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WT000102S' => [$spinCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WTEC02895' => [$spinCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WCFHL2907' => [$spinMainCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WCFHL26D2' => [$spinMainCatalogue, 'www.spinsrl.it', 'official_manufacturer_pdf', true],
    'WTSPURF10' => ['https://www.spinsrl.it/en/product/brake-mate-mk2/', 'www.spinsrl.it', 'official_manufacturer_product', true],
    '801043' => ['https://www.telwin.com/intl/en/products/repair-systems/823234-digital-car-spotter-5500-400v-automatic', 'www.telwin.com', 'official_manufacturer_product', true],
    '804150' => ['https://tristool.md/ru/products/600/7351', 'tristool.md', 'tristools_exact', false],
    '125182' => ['https://lincos.hu/hu/product/59582357-125182', 'lincos.hu', 'verified_exact_title', false],
    '169999' => [$telwinCatalogue, 'www.telwin.com', 'official_manufacturer_pdf', true],
    '802295' => [$telwinCatalogue, 'www.telwin.com', 'official_manufacturer_pdf', true],
    'WZRIC0667' => ['https://tristool.md/ru/products/418/6993', 'tristool.md', 'tristools_exact', false],
    'WZRIC0668' => ['https://tristool.md/ru/products/418/6994', 'tristool.md', 'tristools_exact', false],
    'WZRIC0669' => ['https://tristool.md/ru/products/418/6995', 'tristool.md', 'tristools_exact', false],
    'WZRIC0670' => ['https://tristool.md/ru/products/418/6996', 'tristool.md', 'tristools_exact', false],
    'WT15CR005M12' => ['https://tristool.md/ru/products/566/7847', 'tristool.md', 'tristools_exact', false],
    'WT15CR005R' => ['https://tristool.md/ru/products/566/7748', 'tristool.md', 'tristools_exact', false],
    'WT15CR005B' => ['https://tristool.md/ru/products/566/7749', 'tristool.md', 'tristools_exact', false],
    '41О2' => ['https://tristool.md/ru/products/143/8581', 'tristool.md', 'tristools_exact', false],
    '31О2' => ['https://tristool.md/ru/products/143/8576', 'tristool.md', 'tristools_exact', false],
    'Универсалисп.1/2' => ['https://tristool.md/ru/products/146/8644', 'tristool.md', 'tristools_exact', false],
    'МС1970x1000х400' => ['https://tristool.md/ru/products/146/7907', 'tristool.md', 'tristools_exact', false],
    'СТ-4/4M600' => ['https://tristool.md/ru/products/146/7296', 'tristool.md', 'tristools_exact', false],
    'СК2,0/1000х300' => ['https://tristool.md/ru/products/146/7011', 'tristool.md', 'tristools_exact', false],
    'СТ-4/2Муп_' => ['https://tristool.md/ru/products/146/7007', 'tristool.md', 'tristools_exact', false],
    '31' => ['https://tristool.md/ru/products/143/8581', 'tristool.md', 'tristools_family_detail', false],
    'ВТ-210Г' => ['https://tristool.md/ru/products/143/8581', 'tristool.md', 'tristools_family_detail', false],
    '15Г' => ['https://tristool.md/ru/products/143/8581', 'tristool.md', 'tristools_family_detail', false],
    '18Г' => ['https://tristool.md/ru/products/143/8581', 'tristool.md', 'tristools_family_detail', false],
    '636' => ['https://tristool.md/ru/products/143/8581', 'tristool.md', 'tristools_family_detail', false],
    '636-1,08' => ['https://tristool.md/ru/products/143/8581', 'tristool.md', 'tristools_family_detail', false],
];

$requestedSkus = [];
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--skus=')) {
        $requestedSkus = array_values(array_filter(array_map(
            static fn (string $value): string => strtoupper(trim($value)),
            explode(',', substr($argument, 7)),
        )));
    }
}

if ($requestedSkus !== []) {
    $corrections = array_filter(
        $corrections,
        static fn (string $sku): bool => in_array(strtoupper($sku), $requestedSkus, true),
        ARRAY_FILTER_USE_KEY,
    );
}

$updated = [];

DB::transaction(function () use ($corrections, &$updated): void {
    foreach ($corrections as $sku => [$sourceUrl, $sourceDomain, $sourceType, $isOfficial]) {
        $item = DB::table('product_parser_items as i')
            ->join('products as p', 'p.source_parser_item_id', '=', 'i.id')
            ->whereIn('i.batch_id', [26, 27, 28])
            ->where('i.sku', $sku)
            ->first(['i.id as item_id', 'p.id as product_id', 'p.main_image']);

        if (! $item) {
            throw new RuntimeException("Parser item not found for {$sku}");
        }

        $absolutePath = public_path(ltrim((string) $item->main_image, '/'));
        if (! is_file($absolutePath)) {
            throw new RuntimeException("Corrected image not found for {$sku}: {$absolutePath}");
        }

        $imageSize = getimagesize($absolutePath);
        if (! $imageSize) {
            throw new RuntimeException("Corrected image is invalid for {$sku}");
        }

        DB::table('product_parser_items')->where('id', $item->item_id)->update([
            'image_source_type' => $sourceType,
            'official_source_url' => $isOfficial ? $sourceUrl : DB::raw('official_source_url'),
            'official_source_domain' => $isOfficial ? $sourceDomain : DB::raw('official_source_domain'),
            'official_source_confidence' => $isOfficial ? 100 : DB::raw('official_source_confidence'),
            'fallback_source_url' => $isOfficial ? null : $sourceUrl,
            'fallback_source_domain' => $isOfficial ? null : $sourceDomain,
            'fallback_source_used' => ! $isOfficial,
            'source_match_confidence' => 100,
            'needs_image_review' => false,
            'image_reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->where('id', $item->product_id)->update([
            'needs_image_review' => false,
            'updated_at' => now(),
        ]);

        DB::table('product_parser_image_assets')
            ->where('parser_item_id', $item->item_id)
            ->where('is_main', true)
            ->update([
                'source_url' => $sourceUrl,
                'source_domain' => $sourceDomain,
                'width' => $imageSize[0],
                'height' => $imageSize[1],
                'mime_type' => 'image/webp',
                'status' => 'processed',
                'has_watermark' => false,
                'needs_review' => false,
                'error_message' => null,
                'updated_at' => now(),
            ]);

        DB::table('product_images')
            ->where('product_id', $item->product_id)
            ->where('path', $item->main_image)
            ->update([
                'source_url' => $sourceUrl,
                'source_page_url' => $sourceUrl,
                'source_domain' => $sourceDomain,
                'is_official' => $isOfficial,
                'mime_type' => 'image/webp',
                'width' => $imageSize[0],
                'height' => $imageSize[1],
                'file_size' => filesize($absolutePath),
                'updated_at' => now(),
            ]);

        $updated[] = ['sku' => $sku, 'source_type' => $sourceType, 'source' => $sourceUrl];
    }
});

echo json_encode([
    'updated' => count($updated),
    'products' => $updated,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;
