<?php

declare(strict_types=1);

use App\Models\Product;
use App\Services\Catalog\ProductPublicationGuard;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$guard = $app->make(ProductPublicationGuard::class);
$details = in_array('--details', $argv, true);

Product::query()
    ->with(['brand', 'category'])
    ->orderBy('id')
    ->chunkById(300, function ($products) use ($guard, $details): void {
        foreach ($products as $product) {
            $published = $product->status === 'published' || $product->is_active;
            $result = $guard->evaluate($product, ! $published);

            if ($result['allowed']) {
                continue;
            }

            $summary = implode('|', [
                $product->sku,
                $product->brand?->name ?? '',
                $product->status,
                implode(',', $result['error_codes']),
            ]);

            if (! $details) {
                echo $summary.PHP_EOL;

                continue;
            }

            echo json_encode([
                'summary' => $summary,
                'name_ru' => $product->name_ru,
                'name_ro' => $product->name_ro,
                'source_url' => $product->source_url,
                'source_domain' => $product->source_domain,
                'main_image' => $product->main_image,
                'attributes' => $product->attributes,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
        }
    });
