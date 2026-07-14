<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\ProductImageAvailabilityService;
use App\Services\Catalog\ProductPublicationGuard;
use Illuminate\Console\Command;

class AuditProductImages extends Command
{
    protected $signature = 'masterscule:audit-product-images {--fix} {--unpublish-invalid} {--force}';

    protected $description = 'Audit product image availability without changing data by default';

    public function handle(ProductImageAvailabilityService $images, ProductPublicationGuard $guard): int
    {
        $fix = (bool) $this->option('fix');
        $unpublish = (bool) $this->option('unpublish-invalid');

        if ($unpublish && ! $fix) {
            $this->error('--unpublish-invalid requires --fix.');

            return self::FAILURE;
        }

        if ($unpublish && ! $this->option('force') && ! $this->confirm('Move published products with invalid images to draft?')) {
            return self::FAILURE;
        }

        $stats = [
            'total_products' => 0,
            'missing_main_image' => 0,
            'main_image_file_missing' => 0,
            'placeholder_images' => 0,
            'invalid_image_files' => 0,
            'published_with_invalid_image' => 0,
            'draft_with_invalid_image' => 0,
            'marked_for_review' => 0,
            'unpublished' => 0,
        ];

        Product::orderBy('id')->chunkById(500, function ($products) use ($images, $guard, $fix, $unpublish, &$stats) {
            foreach ($products as $product) {
                $stats['total_products']++;
                $result = $images->inspect($product->main_image);
                if ($result['available']) {
                    continue;
                }

                $key = match ($result['code']) {
                    'missing' => 'missing_main_image',
                    'file_missing', 'remote_not_verified' => 'main_image_file_missing',
                    'placeholder' => 'placeholder_images',
                    default => 'invalid_image_files',
                };
                $stats[$key]++;

                $published = $product->status === 'published' || $product->is_active;
                $stats[$published ? 'published_with_invalid_image' : 'draft_with_invalid_image']++;

                if ($fix && ! $product->needs_image_review) {
                    $product->forceFill(['needs_image_review' => true])->save();
                    $stats['marked_for_review']++;
                }
                if ($fix && $unpublish && $published) {
                    $guard->unpublish($product);
                    $stats['unpublished']++;
                }
            }
        });

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());

        return self::SUCCESS;
    }
}
