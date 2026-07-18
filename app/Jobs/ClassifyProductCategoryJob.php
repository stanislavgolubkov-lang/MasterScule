<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Catalog\AutomaticCategoryAgent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ClassifyProductCategoryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $productId) {}

    public function handle(AutomaticCategoryAgent $agent): void
    {
        $product = Product::find($this->productId);
        if (! $product) {
            return;
        }

        $decision = $agent->decide($product, true);
        if ($decision['can_apply'] && config('catalog_ai.auto_apply')) {
            if ($decision['changed']) {
                $agent->apply($product, $decision);
            } else {
                $agent->record($decision, 'confirmed');
            }

            return;
        }

        $agent->record($decision, 'rejected');
    }
}
