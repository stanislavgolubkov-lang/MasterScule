<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ProductSearchService
{
    public function search(string $sku, ?string $brand = null, string $language = 'auto'): array
    {
        $sku = trim($sku);
        $brand = $brand && $brand !== 'auto' ? trim($brand) : null;

        if ($existing = Product::with(['brand', 'category'])->where('sku', $sku)->first()) {
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

    private function searchTristool(string $sku, ?string $brand): array
    {
        $query = trim(($brand ? $brand.' ' : '').$sku);
        $searchUrl = 'https://tristool.md/ru/search?searchword='.rawurlencode($query);
        $sources = [[
            'url' => $searchUrl,
            'domain' => 'tristool.md',
            'title' => 'TrisTool search: '.$query,
            'snippet' => 'Authorized distributor/e-commerce search by SKU.',
            'source_type' => 'authorized_distributor',
            'confidence_score' => 35,
            'raw_data_json' => ['query' => $query],
        ]];

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'MasterScule.md Product Parser/1.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ])->timeout(12)->retry(1, 350)->get($searchUrl);
        } catch (Throwable $e) {
            $sources[0]['snippet'] = 'TrisTool request failed: '.$e->getMessage();

            return ['found' => false, 'sources' => $sources];
        }

        if (! $response->successful()) {
            $sources[0]['snippet'] = 'TrisTool HTTP '.$response->status();

            return ['found' => false, 'sources' => $sources];
        }

        $cards = $this->parseTristoolCards($response->body());
        $normalizedSku = $this->normalizeSku($sku);
        $match = collect($cards)->first(fn ($card) => $this->normalizeSku($card['sku']) === $normalizedSku);

        if (! $match) {
            return ['found' => false, 'sources' => $sources];
        }

        $sourceUrl = $this->absoluteTristoolUrl($match['href']);
        $confidence = $this->confidence($sku, $brand, $match['sku'], $match['title']);
        $sources[0] = array_replace($sources[0], [
            'url' => $sourceUrl,
            'title' => $match['title'],
            'snippet' => 'SKU exact match from TrisTool search result.',
            'confidence_score' => $confidence,
            'raw_data_json' => $match,
        ]);

        $brandName = $brand ?: $this->brandFromTitle($match['title']);
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
            'images' => array_values(array_filter([$this->absoluteTristoolUrl($match['image'])])),
            'sources' => $sources,
            'source_urls' => [$sourceUrl],
            'confidence' => $confidence,
            'existing_product_id' => null,
            'category_id' => null,
            'warnings' => [],
        ];
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

    private function confidence(string $sku, ?string $brand, string $foundSku, string $title): int
    {
        $score = 40;

        if ($this->normalizeSku($sku) === $this->normalizeSku($foundSku)) {
            $score += 35;
        }

        if ($brand && Str::contains(Str::lower($title), Str::lower($brand))) {
            $score += 15;
        }

        if (preg_match('/\b(set|trusa|pistol|cheie|clichet|tubular|compresor|extractor)\b/iu', $title)) {
            $score += 5;
        }

        return min(96, $score);
    }

    private function brandFromTitle(string $title): ?string
    {
        $lower = Str::lower($title);

        return match (true) {
            Str::contains($lower, 'king tony') => 'King Tony',
            Str::contains($lower, ['mighty seven', 'm7']) => 'M7 / Mighty Seven',
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
}
