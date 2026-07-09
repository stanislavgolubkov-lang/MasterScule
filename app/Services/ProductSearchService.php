<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ProductSearchService
{
    public function search(string $sku, ?string $brand = null, string $language = 'auto', bool $preferLocal = true): array
    {
        $sku = trim($sku);
        $brand = $brand && $brand !== 'auto' ? trim($brand) : null;

        if ($preferLocal && $existing = Product::with(['brand', 'category'])->where('sku', $sku)->first()) {
            return $this->fromExistingProduct($existing);
        }

        $tristool = $this->searchTristool($sku, $brand);
        if ($tristool['found']) {
            return $tristool;
        }

        return [
            'found' => false,
            'title' => null,
            'description' => null,
            'specs' => [],
            'images' => [],
            'sources' => $tristool['sources'],
            'source_urls' => collect($tristool['sources'])->pluck('url')->filter()->values()->all(),
            'confidence' => 0,
            'existing_product_id' => null,
            'category_id' => null,
            'warnings' => [__('ui.parser_no_exact_sku')],
        ];
    }

    public function searchLoose(string $query, ?string $brand = null): array
    {
        $query = trim($query);
        $brand = $brand && $brand !== 'auto' ? trim($brand) : null;

        if ($query === '') {
            return [
                'found' => false,
                'title' => null,
                'description' => null,
                'specs' => [],
                'images' => [],
                'sources' => [],
                'source_urls' => [],
                'confidence' => 0,
                'existing_product_id' => null,
                'category_id' => null,
                'warnings' => [__('ui.parser_no_exact_sku')],
            ];
        }

        $tristool = $this->searchTristool($query, $brand, false);
        if ($tristool['found']) {
            return $tristool;
        }

        return [
            'found' => false,
            'title' => null,
            'description' => null,
            'specs' => [],
            'images' => [],
            'sources' => $tristool['sources'],
            'source_urls' => collect($tristool['sources'])->pluck('url')->filter()->values()->all(),
            'confidence' => 0,
            'existing_product_id' => null,
            'category_id' => null,
            'warnings' => [__('ui.parser_no_exact_sku')],
        ];
    }

    private function fromExistingProduct(Product $product): array
    {
        $gallery = function_exists('productGallery') ? productGallery($product) : array_filter([$product->main_image, ...($product->gallery ?: [])]);
        $url = route('product.show', $product->slug);

        return [
            'found' => true,
            'title' => $product->name_ro ?: $product->name,
            'description' => $product->description_ro ?: $product->description ?: $product->short_description,
            'specs' => $product->attributes ?: [],
            'images' => array_values(array_unique(array_filter($gallery))),
            'sources' => [[
                'url' => $url,
                'domain' => parse_url($url, PHP_URL_HOST) ?: config('store.domain_label'),
                'title' => $product->display_name,
                'snippet' => 'Existing MasterScule catalog product. Parser will not overwrite it automatically.',
                'source_type' => 'local_catalog',
                'confidence_score' => 98,
                'raw_data_json' => ['product_id' => $product->id],
            ]],
            'source_urls' => [$url],
            'confidence' => 98,
            'existing_product_id' => $product->id,
            'category_id' => $product->category_id,
            'warnings' => [__('ui.parser_existing_warning')],
        ];
    }

    private function searchTristool(string $sku, ?string $brand, bool $requireSkuMatch = true): array
    {
        $sources = [];

        foreach ($this->tristoolQueries($sku, $brand) as $query) {
            $searchUrl = 'https://tristool.md/ru/search?searchword='.rawurlencode($query);
            $source = [
                'url' => $searchUrl,
                'domain' => 'tristool.md',
                'title' => 'TrisTool search: '.$query,
                'snippet' => 'Authorized distributor/e-commerce search by SKU.',
                'source_type' => 'authorized_distributor',
                'confidence_score' => 35,
                'raw_data_json' => ['query' => $query],
            ];

            try {
                $response = $this->externalHttp()->withHeaders([
                    'User-Agent' => 'MasterScule.md Product Parser/1.0',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])->timeout(12)->retry(1, 350)->get($searchUrl);
            } catch (Throwable $e) {
                $source['snippet'] = 'TrisTool request failed: '.$e->getMessage();
                $sources[] = $source;

                continue;
            }

            if (! $response->successful()) {
                $source['snippet'] = 'TrisTool HTTP '.$response->status();
                $sources[] = $source;

                continue;
            }

            $cards = collect($this->parseTristoolCards($response->body()))
                ->map(fn ($card) => array_merge($card, ['sku_score' => $this->skuMatchScore($sku, $card['sku'])]));
            $match = $requireSkuMatch
                ? $cards->filter(fn ($card) => $card['sku_score'] > 0)->sortByDesc('sku_score')->first()
                : $cards->first();

            if (! $match) {
                $source['snippet'] = 'No usable SKU match in TrisTool search results.';
                $source['raw_data_json'] = ['query' => $query, 'cards_found' => $cards->count()];
                $sources[] = $source;

                continue;
            }

            $sourceUrl = $this->absoluteTristoolUrl($match['href']);
            $confidence = $requireSkuMatch ? $this->confidence($sku, $brand, $match['sku'], $match['title']) : 55;
            $source = array_replace($source, [
                'url' => $sourceUrl,
                'title' => $match['title'],
                'snippet' => ! $requireSkuMatch
                    ? 'Loose product-name match from TrisTool search result.'
                    : ($match['sku_score'] >= 100
                        ? 'SKU exact match from TrisTool search result.'
                        : 'SKU compatible match from TrisTool search result.'),
                'confidence_score' => $confidence,
                'raw_data_json' => $match,
            ]);
            $sources[] = $source;

            $brandName = $brand ?: $this->brandFromTitle($match['title']);
            $images = $this->tristoolProductImages($sourceUrl, $match['image']);
            $description = trim(($brandName ?: 'Product').' '.$match['sku'].'. Technical product data prepared for admin review. Verify source rights before publication.');

            return [
                'found' => true,
                'title' => $match['title'],
                'description' => $description,
                'specs' => array_filter([
                    'Brand' => $brandName,
                    'Cod produs' => $match['sku'],
                    'Sursa' => 'TrisTool.md',
                ]),
                'images' => $images,
                'sources' => $sources,
                'source_urls' => [$sourceUrl],
                'confidence' => $confidence,
                'existing_product_id' => null,
                'category_id' => null,
                'warnings' => [],
            ];
        }

        return ['found' => false, 'sources' => $sources];
    }

    private function parseTristoolCards(string $html): array
    {
        preg_match_all(
            '/<a class="cl-item[\s\S]*?href="(?<href>[^"]+)"[\s\S]*?<img[^>]+src="(?<img>[^"]+)"[\s\S]*?<h6[^>]*>(?<title>[\s\S]*?)<\/h6>[\s\S]*?<span class="article"[^>]*>(?<sku>[\s\S]*?)<\/span>/i',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        return array_map(fn ($match) => [
            'href' => $match['href'],
            'image' => $match['img'],
            'title' => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($match['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
            'sku' => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($match['sku']), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
        ], $matches);
    }

    private function tristoolProductImages(string $productUrl, string $fallbackImage): array
    {
        $images = [$this->absoluteTristoolUrl($fallbackImage)];

        try {
            $response = $this->externalHttp()->withHeaders([
                'User-Agent' => 'MasterScule.md Product Parser/1.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(12)->retry(1, 350)->get($productUrl);
        } catch (Throwable) {
            return array_values(array_unique(array_filter($images)));
        }

        if (! $response->successful()) {
            return array_values(array_unique(array_filter($images)));
        }

        preg_match_all('/(?:src|data-src|data-large_image|href)="([^"]+\.(?:jpe?g|png|webp)(?:\?[^"]*)?)"/i', $response->body(), $matches);

        foreach ($matches[1] ?? [] as $candidate) {
            $lower = Str::lower($candidate);

            if (
                Str::contains($lower, ['logo', 'icon', 'sprite', 'payment', 'banner', 'brand', 'placeholder'])
                || ! Str::contains($lower, ['virtuemart', 'product', 'images'])
            ) {
                continue;
            }

            $images[] = $this->absoluteTristoolUrl(html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return collect($images)
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->all();
    }

    private function confidence(string $sku, ?string $brand, string $foundSku, string $title): int
    {
        $score = 25;
        $skuScore = $this->skuMatchScore($sku, $foundSku);
        $score += match (true) {
            $skuScore >= 100 => 50,
            $skuScore >= 90 => 45,
            $skuScore > 0 => 35,
            default => 0,
        };

        if ($brand && Str::contains(Str::lower($title), Str::lower($brand))) {
            $score += 15;
        }

        if (preg_match('/\b(set|trusa|pistol|cheie|clichet|tubular|compresor|extractor)\b/iu', $title)) {
            $score += 5;
        }

        return min(96, $score);
    }

    private function tristoolQueries(string $sku, ?string $brand): array
    {
        $brand = trim((string) $brand);
        $brands = array_filter(array_unique([
            $brand,
            $this->compactBrand($brand),
        ]));

        return collect($brands)
            ->map(fn ($brandName) => trim($brandName.' '.$sku))
            ->push($sku)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function compactBrand(string $brand): string
    {
        $lower = Str::lower($brand);

        return match (true) {
            Str::contains($lower, 'king') => 'King Tony',
            Str::contains($lower, ['m7', 'mighty']) => 'M7',
            Str::contains($lower, 'jtc') => 'JTC',
            Str::contains($lower, ['hoegert', 'högert', 'hogert']) => 'Hoegert',
            Str::contains($lower, ['torin', 'tongrun', 'big red']) => 'Torin',
            default => $brand,
        };
    }

    private function skuMatchScore(string $sku, string $foundSku): int
    {
        $needle = $this->normalizeSku($sku);
        $found = $this->normalizeSku($foundSku);

        if ($needle === '' || $found === '') {
            return 0;
        }

        if ($needle === $found) {
            return 100;
        }

        if ('sc'.$needle === $found) {
            return 94;
        }

        if (Str::endsWith($found, $needle)) {
            return 88;
        }

        return Str::contains($found, $needle) ? 82 : 0;
    }

    private function brandFromTitle(string $title): ?string
    {
        $lower = Str::lower($title);

        return match (true) {
            Str::contains($lower, 'king tony') => 'King Tony',
            Str::contains($lower, ['mighty seven', 'm7']) => 'M7 / Mighty Seven',
            Str::contains($lower, 'jtc') => 'JTC',
            Str::contains($lower, ['hoegert', 'högert', 'hogert']) => 'Hoegert',
            Str::contains($lower, ['torin', 'tongrun', 'big red']) => 'Torin BIG RED',
            default => null,
        };
    }

    private function normalizeSku(string $sku): string
    {
        return Str::lower(preg_replace('/[^a-z0-9]/i', '', $sku));
    }

    private function absoluteTristoolUrl(string $url): string
    {
        $absolute = Str::startsWith($url, ['http://', 'https://'])
            ? $url
            : 'https://tristool.md/'.ltrim($url, '/');

        return $this->encodeUrlPath($absolute);
    }

    private function encodeUrlPath(string $url): string
    {
        $parts = parse_url($url);

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $path = implode('/', array_map(
            fn ($segment) => rawurlencode(rawurldecode($segment)),
            explode('/', $parts['path'] ?? '')
        ));
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port.$path.$query.$fragment;
    }

    private function externalHttp(): PendingRequest
    {
        return Http::withOptions([
            'proxy' => '',
            'verify' => false,
        ]);
    }
}
