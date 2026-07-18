<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductParserItem;
use Illuminate\Support\Str;

class ProductImageQualityGuard
{
    public function __construct(private readonly ProductImageAvailabilityService $availability) {}

    public function evaluate(Product $product): array
    {
        $errors = [];
        $main = $this->availability->inspect($product->main_image);
        if (! $main['available']) {
            $errors['image_'.$main['code']] = 'Main product image is not a valid local image.';
        }

        if ($product->source_import_batch_id) {
            if (! filled($product->source_url) || ! filled($product->source_domain)) {
                $errors['image_missing_source'] = 'Parser product image has no source URL or domain.';
            }
            $sourcePath = Str::lower((string) parse_url((string) $product->source_url, PHP_URL_PATH));
            if (Str::contains($sourcePath, ['/search', 'site_search'])) {
                $errors['image_search_page_source'] = 'A search or listing page cannot be used as a product source.';
            }

            $item = $product->source_parser_item_id ? ProductParserItem::find($product->source_parser_item_id) : null;
            $approvedBrandFallback = $item?->image_source_type === 'brand_logo_fallback'
                && $item->source_reviewed_at
                && $item->image_reviewed_at;
            $assets = $item?->imageAssets()->where('is_selected', true)->get() ?: collect();
            if ($assets->isEmpty()) {
                $errors['image_missing_asset'] = 'Parser product has no selected image asset.';
            }
            foreach ($assets as $asset) {
                $assetUrl = Str::lower((string) $asset->source_url);
                if (! filled($asset->source_url) || ! filled($asset->source_domain)) {
                    $errors['image_asset_missing_source'] = 'Selected image has no source URL or domain.';
                }
                if (! $approvedBrandFallback && Str::contains($assetUrl, ['logo', 'tce300x300'])) {
                    $errors['image_asset_non_product'] = 'A logo or brand graphic cannot be used as a product image.';
                }
                if ($asset->needs_review) {
                    $errors['image_asset_needs_review'] = 'Selected image requires manual review.';
                }
                if (! $asset->processed_path || ! $this->availability->inspect($asset->processed_path)['available']) {
                    $errors['image_processed_missing'] = 'Processed image file is missing.';
                }
                if ($asset->is_main && (! $asset->thumb_path || ! $this->availability->inspect($asset->thumb_path)['available'])) {
                    $errors['image_thumb_missing'] = 'Image thumbnail is missing.';
                }
                if (! $asset->preview_path || ! $this->availability->inspect($asset->preview_path)['available']) {
                    $errors['image_preview_missing'] = 'Image preview is missing.';
                }
                if (! $approvedBrandFallback && config('product_parser.watermark.enabled', true) && ! $asset->has_watermark) {
                    $errors['image_watermark_missing'] = 'Required watermark was not applied.';
                }
            }
        }

        return ['allowed' => $errors === [], 'errors' => $errors, 'main' => $main];
    }
}
