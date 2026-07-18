<?php

namespace App\Services;

use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;

class ProductParserItemPreparationService
{
    public function __construct(
        private readonly ProductImageProcessorService $imageProcessor,
        private readonly ProductParserSettings $settings,
    ) {}

    public function prepare(ProductParserItem $item, bool $processImages = true): bool
    {
        $item->imageAssets()->update(['is_selected' => false, 'is_main' => false]);
        $assets = $item->imageAssets()->orderByDesc('status')->orderBy('id')->get();

        if ($assets->isEmpty()) {
            $this->markUnready($item);

            return false;
        }

        if (! $processImages) {
            $asset = $assets->first();
            $asset->forceFill(['is_selected' => true, 'is_main' => true])->save();
            $item->forceFill([
                'selected_images_json' => [$asset->source_url],
                'processed_images_json' => [],
                'needs_image_review' => true,
            ])->save();

            return false;
        }

        foreach ($assets as $asset) {
            $asset->forceFill([
                'is_selected' => true,
                'is_main' => true,
                'status' => $asset->processed_path ? 'processed' : 'found',
                'error_message' => null,
            ])->save();

            if (! $this->assetReady($asset)) {
                $this->imageProcessor->processSelected($item->fresh(['imageAssets', 'batch']));
                $asset->refresh();
            }

            if ($this->assetReady($asset)) {
                $trusted = $this->sourceIsTrusted($item->fresh());
                $asset->forceFill(['needs_review' => ! $trusted])->save();
                $item->forceFill([
                    'selected_images_json' => [$asset->source_url],
                    'processed_images_json' => [$asset->processed_path],
                    'needs_image_review' => ! $trusted,
                    'needs_source_review' => ! $trusted,
                    'image_reviewed_at' => $trusted ? now() : null,
                    'source_reviewed_at' => $trusted ? now() : null,
                    'status' => $item->needs_category_review ? 'needs_category_review' : 'ready_for_review',
                    'error_message' => null,
                ])->save();

                return true;
            }

            $asset->forceFill(['is_selected' => false, 'is_main' => false, 'needs_review' => true])->save();
        }

        $this->markUnready($item);

        return false;
    }

    private function assetReady(ProductParserImageAsset $asset): bool
    {
        return $asset->status === 'processed'
            && filled($asset->processed_path)
            && filled($asset->preview_path)
            && filled($asset->thumb_path);
    }

    private function sourceIsTrusted(ProductParserItem $item): bool
    {
        if ($item->image_source_type === 'brand_logo_fallback'
            && $item->source_reviewed_at
            && $item->image_reviewed_at) {
            return true;
        }

        if (filled($item->tristools_url) && (int) $item->tristools_match_confidence >= 90) {
            return true;
        }

        if ($item->fallback_source_used) {
            return (bool) $this->settings->get('auto_approve_exact_fallback', true)
                && filled($item->fallback_source_url)
                && filled($item->fallback_source_domain)
                && (int) $item->source_match_confidence >= (int) $this->settings->get('min_fallback_confidence', 80);
        }

        $officialConfidence = (int) ($item->official_source_confidence ?? 0);
        if (filled($item->official_source_url)
            && filled($item->official_source_domain)
            && $officialConfidence >= (int) $this->settings->get('min_official_confidence', 90)) {
            return true;
        }

        return false;
    }

    private function markUnready(ProductParserItem $item): void
    {
        $errorMessage = trim((string) $item->error_message);
        if ($errorMessage === trim((string) __('ui.parser_images_failed'))) {
            $errorMessage = '';
        }

        $item->forceFill([
            'selected_images_json' => [],
            'processed_images_json' => [],
            'needs_image_review' => true,
            'image_reviewed_at' => null,
            'status' => $item->needs_category_review ? 'needs_category_review' : 'ready_for_review',
            'error_message' => $errorMessage !== '' ? $errorMessage : null,
        ])->save();
    }
}
