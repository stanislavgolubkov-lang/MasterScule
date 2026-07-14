<?php

namespace App\Services\ProductSources;

use Illuminate\Support\Str;

class ReviewedCatalogSourceService
{
    public function find(string $sku, ?string $brand, ?string $name = null): ?array
    {
        $normalizedSku = $this->normalizeSku($sku);
        $normalizedBrand = Str::lower(trim((string) $brand));

        foreach (config('product_parser.reviewed_catalogs', []) as $catalog) {
            if (! $this->supportsBrand($normalizedBrand, $catalog['brand_keys'] ?? [])) {
                continue;
            }

            $assets = $catalog['assets'] ?? [];
            $filename = $assets[$normalizedSku] ?? null;
            if (! is_string($filename) || $filename === '') {
                continue;
            }

            $directory = trim((string) ($catalog['directory'] ?? ''), '/\\');
            $relativePath = $directory.'/'.$filename;
            $absolutePath = public_path($relativePath);
            $dimensions = is_file($absolutePath) ? @getimagesize($absolutePath) : false;

            if (! is_array($dimensions) || $dimensions[0] < 220 || $dimensions[1] < 220) {
                continue;
            }

            $sourceUrl = (string) ($catalog['source_url'] ?? '');
            $sourceDomain = Str::lower((string) parse_url($sourceUrl, PHP_URL_HOST));
            if (! filter_var($sourceUrl, FILTER_VALIDATE_URL) || $sourceDomain === '') {
                continue;
            }

            $publicPath = '/'.str_replace('\\', '/', $relativePath);
            $confidence = max(90, min(100, (int) ($catalog['confidence'] ?? 95)));

            return [
                'found' => true,
                'title' => trim((string) $name) ?: trim($sku),
                'description' => null,
                'specs' => [],
                'images' => [$publicPath],
                'sources' => [[
                    'url' => $sourceUrl,
                    'domain' => $sourceDomain,
                    'title' => (string) ($catalog['source_name'] ?? 'Reviewed manufacturer catalog'),
                    'snippet' => 'Exact SKU image from a reviewed manufacturer catalog.',
                    'source_type' => 'official_manufacturer_catalog',
                    'confidence_score' => $confidence,
                    'raw_data_json' => ['sku' => $sku, 'brand' => $brand, 'asset' => $publicPath],
                ]],
                'source_urls' => [$sourceUrl, $publicPath],
                'confidence' => $confidence,
                'source_match_confidence' => $confidence,
                'official_source_url' => $sourceUrl,
                'official_source_domain' => $sourceDomain,
                'official_source_confidence' => $confidence,
                'fallback_source_url' => null,
                'fallback_source_domain' => null,
                'fallback_source_used' => false,
                'needs_source_review' => false,
                'content_source_type' => 'generated_pending_review',
                'image_source_type' => 'official_manufacturer_catalog',
                'translation_source_type' => 'generated_pending_review',
                'existing_product_id' => null,
                'category_id' => null,
                'warnings' => [],
            ];
        }

        return null;
    }

    private function supportsBrand(string $brand, array $brandKeys): bool
    {
        if ($brand === '') {
            return false;
        }

        return collect($brandKeys)->contains(
            fn ($key) => Str::contains($brand, Str::lower(trim((string) $key)))
        );
    }

    private function normalizeSku(string $sku): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', '', $sku) ?: ''));
    }
}
