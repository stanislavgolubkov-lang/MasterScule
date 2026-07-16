<?php

namespace App\Services;

use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use Illuminate\Support\Str;

class ProductImageCollectorService
{
    public function __construct(private ProductParserSettings $settings) {}

    public function collect(ProductParserItem $item, array $imageUrls, ?string $sourceDomain = null): void
    {
        $max = max(1, (int) $this->settings->get('max_images_per_product', 4));
        $urls = collect($imageUrls)
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->reject(fn ($url) => $this->isBlockedBrandImage($item, $url))
            ->unique()
            ->take(max($max, 4))
            ->values();

        ProductParserImageAsset::where('parser_item_id', $item->id)->delete();

        foreach ($urls as $index => $url) {
            $fallbackHost = Str::lower((string) parse_url(
                (string) $this->settings->get('tristools.base_url', 'https://tristool.md'),
                PHP_URL_HOST,
            ));
            $sourceHost = Str::lower((string) (parse_url($url, PHP_URL_HOST) ?: $sourceDomain));
            $isFallback = $fallbackHost !== ''
                && ($sourceHost === $fallbackHost || Str::endsWith($sourceHost, '.'.$fallbackHost));

            ProductParserImageAsset::create([
                'parser_item_id' => $item->id,
                'source_url' => $url,
                'source_domain' => $sourceHost ?: null,
                'status' => 'found',
                'is_selected' => $index === 0,
                'is_main' => $index === 0,
                'needs_review' => $isFallback,
            ]);
        }
    }

    private function isBlockedBrandImage(ProductParserItem $item, string $url): bool
    {
        $brand = Str::lower((string) $item->brand);
        $lower = Str::lower($url);

        if (Str::contains($lower, [
            'logo', 'brand', 'favicon', 'icon', 'banner', 'collection', 'category',
            'avatar', 'social', 'facebook', 'instagram', 'youtube', 'placeholder',
            'messenger', 'no-image', 'no_image', 'no-pic', 'no_pic',
        ])) {
            return true;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $fileName = pathinfo($path, PATHINFO_FILENAME);
        $normalizedBrand = $this->normalizeSku($brand);
        $normalizedFileName = $this->normalizeSku($fileName);

        if ($normalizedBrand !== '' && $normalizedFileName === $normalizedBrand) {
            return true;
        }

        if (! Str::contains($brand, 'jtc')) {
            return false;
        }

        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || (! Str::endsWith($host, 'jtc.com.tw') && ! Str::endsWith($host, 'jtcautotools.com'))) {
            return false;
        }

        $sku = $this->normalizeSku((string) $item->sku);
        $skuCore = preg_replace('/^JTC/', '', $sku) ?: $sku;
        $normalizedUrl = $this->normalizeSku($url);

        return $sku === ''
            || (! Str::contains($normalizedUrl, $sku)
                && (strlen($skuCore) < 3 || ! Str::contains($normalizedUrl, $skuCore)));
    }

    private function normalizeSku(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', Str::upper(Str::ascii($value))) ?: '';
    }
}
