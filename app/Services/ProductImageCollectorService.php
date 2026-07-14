<?php

namespace App\Services;

use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use Illuminate\Support\Str;

class ProductImageCollectorService
{
    public function __construct(private ProductParserSettings $settings) {}

    public function collect(ProductParserItem $item, array $imageUrls): void
    {
        $max = max(1, (int) $this->settings->get('max_images_per_product', 4));
        $required = max(1, (int) $this->settings->get('required_images_for_ready', 3));
        $urls = collect($imageUrls)
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->unique()
            ->take(max($max, 4))
            ->values();

        ProductParserImageAsset::where('parser_item_id', $item->id)->delete();

        foreach ($urls as $index => $url) {
            $fallbackHost = Str::lower((string) parse_url(
                (string) $this->settings->get('tristools.base_url', 'https://tristool.md'),
                PHP_URL_HOST,
            ));
            $sourceHost = Str::lower((string) parse_url($url, PHP_URL_HOST));
            $isFallback = $fallbackHost !== ''
                && ($sourceHost === $fallbackHost || Str::endsWith($sourceHost, '.'.$fallbackHost));

            ProductParserImageAsset::create([
                'parser_item_id' => $item->id,
                'source_url' => $url,
                'source_domain' => parse_url($url, PHP_URL_HOST),
                'status' => 'found',
                'is_selected' => $index < $max,
                'is_main' => $index === 0,
                'needs_review' => $urls->count() < $required || $isFallback,
            ]);
        }
    }
}
