<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\ProductImageQualityGuard;
use Illuminate\Console\Command;

class ParserAuditImages extends Command
{
    protected $signature = 'masterscule:parser-audit-images {--mark-review}';

    protected $description = 'Audit parser product image files and provenance';

    public function handle(ProductImageQualityGuard $guard): int
    {
        $stats = ['parser_products' => 0, 'invalid_images' => 0, 'marked_for_review' => 0];
        Product::whereNotNull('source_import_batch_id')->orderBy('id')->chunkById(300, function ($products) use ($guard, &$stats) {
            foreach ($products as $product) {
                $stats['parser_products']++;
                if ($guard->evaluate($product)['allowed']) {
                    continue;
                }
                $stats['invalid_images']++;
                if ($this->option('mark-review') && ! $product->needs_image_review) {
                    $product->forceFill(['needs_image_review' => true])->save();
                    $stats['marked_for_review']++;
                }
            }
        });
        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());

        return self::SUCCESS;
    }
}
