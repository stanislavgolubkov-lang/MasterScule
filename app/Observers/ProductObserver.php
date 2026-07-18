<?php

namespace App\Observers;

use App\Jobs\ClassifyProductCategoryJob;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductObserver
{
    private const CLASSIFICATION_FIELDS = [
        'sku', 'brand_id', 'name', 'name_ru', 'name_ro', 'description', 'description_ru', 'description_ro',
        'attributes', 'vehicle_application', 'source_parser_item_id',
    ];

    public function created(Product $product): void
    {
        $this->dispatch($product);
    }

    public function updated(Product $product): void
    {
        if ($product->wasChanged(self::CLASSIFICATION_FIELDS)) {
            $this->dispatch($product);
        }
    }

    private function dispatch(Product $product): void
    {
        if (! config('catalog_ai.enabled') || app()->runningUnitTests()) {
            return;
        }

        if (filled(config('catalog_ai.api_key'))) {
            ClassifyProductCategoryJob::dispatch($product->id)->afterCommit();

            return;
        }

        $productId = $product->id;
        DB::afterCommit(fn () => ClassifyProductCategoryJob::dispatchSync($productId));
    }
}
