<?php

namespace App\Services;

use App\Models\Product;
use App\Services\ProductSources\ProductSourceDiscoveryService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ProductSearchService
{
    private array $htmlCache = [];

    public function __construct(
        private readonly TrisToolsEnrichmentService $trisTools,
        private readonly ProductSourceDiscoveryService $sourceDiscovery,
    ) {}

    public function searchForParser(string $sku, ?string $brand = null, string $language = 'auto', bool $preferLocal = true): array
    {
        $sku = trim($sku);
        $brand = $brand && $brand !== 'auto' ? trim($brand) : null;

        if ($preferLocal && $existing = Product::with(['brand', 'category'])->where('sku', $sku)->first()) {
            return $this->fromExistingProduct($existing);
        }

        return $this->sourceDiscovery->search($sku, $brand);
    }

    public function searchFallbackForParser(string $sku, ?string $brand = null): array
    {
        return $this->sourceDiscovery->search(trim($sku), trim((string) $brand), forceFallback: true);
    }

    public function searchOfficialForParser(string $sku, ?string $brand = null): array
    {
        return $this->sourceDiscovery->search(trim($sku), trim((string) $brand), allowFallback: false);
    }

    public function search(string $sku, ?string $brand = null, string $language = 'auto', bool $preferLocal = true): array
    {
        $sku = trim($sku);
        $brand = $brand && $brand !== 'auto' ? trim($brand) : null;

        if ($preferLocal && $existing = Product::with(['brand', 'category'])->where('sku', $sku)->first()) {
            return $this->fromExistingProduct($existing);
        }

        return $this->searchOfficial($sku, $brand);
    }

    public function searchLoose(string $query, ?string $brand = null): array
    {
        preg_match_all('/[A-Z0-9][A-Z0-9\-\/]{2,}/iu', Str::upper($query), $matches);
        $sku = collect($matches[0] ?? [])
            ->map(fn ($value) => trim((string) $value, '-/'))
            ->first(fn ($value) => preg_match('/\d/', $value));

        return $sku ? $this->searchOfficial($sku, $brand) : $this->emptyResult();
    }

    private function fromExistingProduct(Product $product): array
    {
        $gallery = function_exists('productGallery')
            ? productGallery($product)
            : array_filter([$product->main_image, ...($product->gallery ?: [])]);
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
                'snippet' => 'Existing MasterScule catalog product.',
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

    private function searchOfficial(string $sku, ?string $brand): array
    {
        $sku = trim($sku);
        $domains = $this->officialDomains($brand);
        $isM7 = Str::contains(Str::lower((string) $brand), ['m7', 'mighty']);

        if ($sku === '' || $domains === []) {
            return $this->emptyResult();
        }

        $apiMatch = $this->officialApiMatch($sku, $brand);
        if ($isM7 && $apiMatch === []) {
            return $this->emptyResult();
        }

        $verifiedApiImages = $apiMatch['images'] ?? [];
        $images = array_values(array_unique(array_merge(
            $this->officialDirectImages($sku, $brand),
            $verifiedApiImages,
        )));
        $pages = $apiMatch['pages'] ?? [];

        if ($pages === []) {
            $pages = $this->officialApiPages($sku, $brand);
        }

        if ($pages === []) {
            foreach ($this->brandSearchPages($sku, $brand) as $searchUrl) {
                $pages = array_merge($pages, $this->officialLinksFromSearchPage($searchUrl, $sku, $domains));
            }
        }

        if ($pages === []) {
            $pages = array_merge($pages, $this->officialSearchEnginePages($sku, $brand, $domains));
        }

        $pages = collect($pages)
            ->filter(fn ($url) => $this->isOfficialUrl((string) $url, $domains))
            ->unique()
            ->take(8)
            ->values();
        $sources = [];
        $officialTitle = $apiMatch['title'] ?? null;
        $officialDescription = null;
        $officialSpecs = [];

        if ($apiMatch !== []) {
            foreach ($pages as $pageUrl) {
                $sources[] = [
                    'url' => $pageUrl,
                    'domain' => parse_url($pageUrl, PHP_URL_HOST),
                    'title' => 'Official product page: '.$sku,
                    'snippet' => 'Exact SKU matched in the official manufacturer API.',
                    'source_type' => 'official_brand',
                    'confidence_score' => 98,
                    'raw_data_json' => [
                        'sku' => $sku,
                        'brand' => $brand,
                        'images' => $verifiedApiImages,
                        'official_title' => $officialTitle,
                    ],
                ];
            }

            $pages = collect();
        }

        foreach ($pages as $pageUrl) {
            $pageImages = $this->imagesFromOfficialPage($pageUrl, $sku);
            $content = $this->contentFromOfficialPage($pageUrl, $sku);

            if ($pageImages === [] && $content === []) {
                continue;
            }

            $images = array_values(array_unique(array_merge($images, $pageImages)));
            $officialTitle ??= $content['title'] ?? null;
            $officialDescription ??= $content['description'] ?? null;
            $officialSpecs = array_merge($officialSpecs, $content['specs'] ?? []);
            $sources[] = [
                'url' => $pageUrl,
                'domain' => parse_url($pageUrl, PHP_URL_HOST),
                'title' => 'Official product page: '.$sku,
                'snippet' => 'Exact SKU verified on the official manufacturer website.',
                'source_type' => 'official_brand',
                'confidence_score' => 96,
                'raw_data_json' => [
                    'sku' => $sku,
                    'brand' => $brand,
                    'images' => $pageImages,
                    'official_title' => $content['title'] ?? null,
                ],
            ];

            if (count($images) >= 4) {
                break;
            }
        }

        $images = collect($images)
            ->filter()
            ->reject(fn ($url) => Str::contains(Str::lower((string) $url), ['no_pic', 'no-pic', 'no_image', 'no-image']))
            ->unique(fn ($url) => preg_replace('/^https?:\/\/(?:www\.)?/i', '', Str::lower((string) $url)))
            ->take(4)
            ->values()
            ->all();

        if ($isM7) {
            $images = collect($images)
                ->filter(fn ($url) => in_array($url, $verifiedApiImages, true)
                    || Str::contains($this->normalizeSku((string) $url), $this->normalizeSku($sku)))
                ->take(4)
                ->values()
                ->all();
        }

        if ($images === []) {
            return $this->emptyResult($sources);
        }

        if ($sources === []) {
            $sources[] = [
                'url' => $images[0],
                'domain' => parse_url($images[0], PHP_URL_HOST),
                'title' => 'Official direct SKU image: '.$sku,
                'snippet' => 'Exact SKU image served by the manufacturer.',
                'source_type' => 'official_brand',
                'confidence_score' => 94,
                'raw_data_json' => ['sku' => $sku, 'brand' => $brand, 'images' => $images],
            ];
        }

        return [
            'found' => true,
            'title' => $officialTitle ?: trim(implode(' ', array_filter([$brand, $sku]))),
            'description' => $officialDescription ?: trim(($brand ?: 'Product').' '.$sku.'. Official manufacturer media matched by exact SKU.'),
            'specs' => array_merge(
                array_filter(['Brand' => $brand, 'Cod produs' => $sku, 'Sursa' => 'Official manufacturer']),
                $officialSpecs,
            ),
            'images' => $images,
            'sources' => $sources,
            'source_urls' => collect($sources)->pluck('url')->merge($images)->unique()->values()->all(),
            'confidence' => 96,
            'official_content_found' => (bool) ($officialTitle || $officialDescription),
            'existing_product_id' => null,
            'category_id' => null,
            'warnings' => [],
        ];
    }

    private function officialDirectImages(string $sku, ?string $brand): array
    {
        if (! Str::contains(Str::lower((string) $brand), 'king')) {
            return [];
        }

        $variants = collect([
            $sku,
            Str::upper($sku),
            str_replace([' ', '/'], '-', $sku),
            str_replace(['-', ' ', '/'], '', $sku),
        ])->filter()->unique();
        $images = [];

        foreach ($variants as $variant) {
            foreach (['png', 'jpg', 'webp'] as $extension) {
                $url = 'https://www.kingtony.com/upload/products/'.rawurlencode((string) $variant).'.'.$extension;

                if ($this->remoteImageExists($url)) {
                    $images[] = $url;
                }
            }
        }

        return collect($images)->unique()->take(4)->values()->all();
    }

    private function officialDomains(?string $brand): array
    {
        $brand = Str::lower((string) $brand);

        return match (true) {
            Str::contains($brand, 'king') => ['kingtony.com'],
            Str::contains($brand, ['m7', 'mighty']) => ['mighty-seven.com'],
            Str::contains($brand, 'jtc') => ['jtc.com.tw', 'jtcautotools.com'],
            Str::contains($brand, ['hoegert', 'hogert', 'högert']) => ['hoegert.com'],
            Str::contains($brand, ['torin', 'big red', 'tongrun']) => ['torinjacks.com', 'torin-usa.com', 'tongrunjacks.com'],
            default => [],
        };
    }

    private function brandSearchPages(string $sku, ?string $brand): array
    {
        $brand = Str::lower((string) $brand);
        $encoded = rawurlencode($sku);

        return match (true) {
            Str::contains($brand, 'king') => ["https://www.kingtony.com/products_search.php?keywords={$encoded}"],
            Str::contains($brand, ['m7', 'mighty']) => ["https://www.mighty-seven.com/search_page?key={$encoded}"],
            Str::contains($brand, 'jtc') => ["https://eng.jtc.com.tw/product/index.php?keywords={$encoded}&mode=search"],
            Str::contains($brand, ['hoegert', 'hogert', 'högert']) => ["https://hoegert.com/?s={$encoded}&post_type=product"],
            Str::contains($brand, ['torin', 'big red', 'tongrun']) => [
                "https://torinjacks.com/search?q={$encoded}",
                "https://en.tongrunjacks.com/search?keyword={$encoded}",
            ],
            default => [],
        };
    }

    private function officialApiPages(string $sku, ?string $brand): array
    {
        $brand = Str::lower((string) $brand);

        if (Str::contains($brand, ['m7', 'mighty'])) {
            try {
                $response = $this->externalHttp()
                    ->asForm()
                    ->withHeaders(['User-Agent' => 'MasterSculeOfficialMedia/1.0'])
                    ->timeout(15)
                    ->retry(1, 250)
                    ->post('https://www.mighty-seven.com/api_v1/getprodut_list_search', [
                        'key' => $sku,
                        'type1' => '',
                        'type2' => '',
                        'type3' => '',
                        'type4' => '',
                    ]);
            } catch (Throwable) {
                return [];
            }

            return $response->successful()
                ? $this->productUrlsFromApiPayload($response->json() ?: $response->body(), 'https://www.mighty-seven.com')
                : [];
        }

        $endpoint = match (true) {
            Str::contains($brand, ['m7', 'mighty']) => 'https://tools.mighty-seven.com/wp-json/wp/v2/search?search='.rawurlencode($sku).'&per_page=6',
            Str::contains($brand, ['hoegert', 'hogert', 'högert']) => 'https://hoegert.com/wp-json/wp/v2/search?search='.rawurlencode($sku).'&per_page=6',
            default => null,
        };

        if (! $endpoint) {
            return [];
        }

        try {
            $response = $this->externalHttp()
                ->withHeaders(['User-Agent' => 'MasterSculeOfficialMedia/1.0'])
                ->timeout(12)
                ->retry(1, 250)
                ->get($endpoint);
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return collect($response->json() ?: [])
            ->filter(fn ($row) => ($row['subtype'] ?? null) === 'product')
            ->pluck('url')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function officialApiMatch(string $sku, ?string $brand): array
    {
        if (! Str::contains(Str::lower((string) $brand), ['m7', 'mighty'])) {
            return [];
        }

        try {
            $response = $this->externalHttp()
                ->asForm()
                ->withHeaders(['User-Agent' => 'MasterSculeOfficialMedia/1.0'])
                ->timeout(15)
                ->retry(1, 250)
                ->post('https://www.mighty-seven.com/api_v1/getprodut_list_search', [
                    'key' => $sku,
                    'type1' => '',
                    'type2' => '',
                    'type3' => '',
                    'type4' => '',
                ]);
        } catch (Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $payload = $response->json();
        $html = is_array($payload) ? (string) ($payload['data'] ?? '') : '';
        preg_match_all(
            '/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>[\s\S]*?<img\b[^>]*src=["\']([^"\']+)["\'][^>]*>[\s\S]*?<h3\b[^>]*>([\s\S]*?)<\/h3>[\s\S]*?<p\b[^>]*>([\s\S]*?)<\/p>[\s\S]*?<\/a>/iu',
            $html,
            $matches,
            PREG_SET_ORDER,
        );
        $needle = $this->normalizeSku($sku);

        foreach ($matches as $match) {
            if ($this->normalizeSku($this->cleanContent($match[4] ?? '') ?: '') !== $needle) {
                continue;
            }

            $page = $this->absoluteUrl('https://www.mighty-seven.com', $match[1]);
            $image = $this->absoluteUrl('https://www.mighty-seven.com', $match[2]);

            return [
                'pages' => array_values(array_filter([$page])),
                'images' => array_values(array_filter([$image])),
                'title' => $this->cleanContent($match[3] ?? ''),
            ];
        }

        return [];
    }

    private function productUrlsFromApiPayload(mixed $payload, string $baseUrl): array
    {
        $values = [];

        if (is_array($payload)) {
            array_walk_recursive($payload, function ($value) use (&$values) {
                if (is_string($value)) {
                    $values[] = $value;
                }
            });
        } elseif (is_string($payload)) {
            $values[] = $payload;
        }

        $urls = [];
        foreach ($values as $value) {
            preg_match_all("~(?:https?://[^\"'\\s<]+)?/product/\\d+~iu", html_entity_decode($value), $matches);
            foreach ($matches[0] ?? [] as $url) {
                $urls[] = $this->absoluteUrl($baseUrl, $url);
            }
        }

        return collect($urls)->filter()->unique()->take(8)->values()->all();
    }

    private function officialLinksFromSearchPage(string $searchUrl, string $sku, array $domains): array
    {
        $html = $this->fetchHtml($searchUrl);

        if ($html === null) {
            return [];
        }

        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>([\s\S]*?)<\/a>/iu', $html, $matches, PREG_SET_ORDER);
        $needle = $this->normalizeSku($sku);

        if (Str::contains(Str::lower((string) parse_url($searchUrl, PHP_URL_HOST)), 'jtc.com.tw')
            && $needle !== ''
            && Str::contains($this->normalizeSku(strip_tags($html)), $needle)) {
            $jtcLinks = collect($matches)
                ->pluck(1)
                ->filter(fn ($href) => Str::contains(Str::lower((string) $href), ['/product/?mode=data', '/product?mode=data']))
                ->map(fn ($href) => $this->absoluteUrl($searchUrl, html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8')))
                ->filter(fn ($url) => $url && $this->isOfficialUrl($url, $domains))
                ->unique()
                ->take(8)
                ->values()
                ->all();

            if ($jtcLinks !== []) {
                return $jtcLinks;
            }
        }

        return collect($matches)
            ->filter(function ($match) use ($needle) {
                $haystack = $this->normalizeSku(($match[1] ?? '').' '.strip_tags($match[2] ?? ''));

                return $needle !== '' && Str::contains($haystack, $needle);
            })
            ->map(fn ($match) => $this->absoluteUrl($searchUrl, html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')))
            ->filter(fn ($url) => $url && $this->isOfficialUrl($url, $domains))
            ->reject(fn ($url) => Str::contains(Str::lower($url), [
                'products_search',
                'index.php?url=',
                '/search?',
                '/search/',
            ]))
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    private function officialSearchEnginePages(string $sku, ?string $brand, array $domains): array
    {
        $brandLabel = trim((string) $brand);
        $pages = [];

        foreach ($domains as $domain) {
            $query = trim('"'.$sku.'" "'.$brandLabel.'" site:'.$domain);
            $html = $this->fetchHtml('https://html.duckduckgo.com/html/?q='.rawurlencode($query));

            if ($html === null) {
                continue;
            }

            preg_match_all('/class="result__a"\s+href="([^"]+)"/i', $html, $matches);

            foreach ($matches[1] ?? [] as $url) {
                $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $parameters);
                $url = isset($parameters['uddg']) ? urldecode($parameters['uddg']) : $url;

                if ($this->isOfficialUrl($url, $domains)) {
                    $pages[] = $url;
                }
            }

            if (count($pages) >= 8) {
                break;
            }
        }

        return array_values(array_unique($pages));
    }

    private function imagesFromOfficialPage(string $pageUrl, string $sku): array
    {
        $html = $this->fetchHtml($pageUrl);

        if ($html === null || ! Str::contains($this->normalizeSku($pageUrl.' '.strip_tags($html)), $this->normalizeSku($sku))) {
            return [];
        }

        preg_match_all('/<meta[^>]+(?:property|name)=["\'](?:og:image|twitter:image)["\'][^>]+content=["\']([^"\']+)["\']/iu', $html, $metaMatches);
        preg_match_all('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\'](?:og:image|twitter:image)["\']/iu', $html, $metaReverseMatches);
        preg_match_all('/(?:src|data-src|data-original|data-large_image|href|content)=["\']([^"\']+\.(?:jpe?g|png|webp)(?:\?[^"\']*)?)["\']/iu', $html, $imageMatches);

        $priority = collect($metaMatches[1] ?? [])->merge($metaReverseMatches[1] ?? [])->filter()->values();
        $all = $priority->merge($imageMatches[1] ?? []);

        return $all
            ->map(fn ($url) => html_entity_decode(str_replace('\\/', '/', (string) $url), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->map(fn ($url) => $this->absoluteUrl($pageUrl, $url))
            ->filter()
            ->reject(fn ($url) => Str::contains(Str::lower((string) $url), [
                'favicon', 'logo', 'icon', 'sprite', 'payment', 'banner', 'avatar', 'placeholder', 'qr-code', 'qrcode',
                'no_pic', 'no-pic', 'no_image', 'no-image',
                '/images/app/', '/img/kingtony', '144x144', '114x114', '72x72', 'apple-touch',
            ]))
            ->map(fn ($url, $index) => [
                'url' => $url,
                'score' => ($priority->contains(fn ($item) => Str::contains($url, trim((string) $item))) ? 100 : 30)
                    + (Str::contains($this->normalizeSku($url), $this->normalizeSku($sku)) ? 70 : 0)
                    + (Str::contains(Str::lower($url), ['product', 'upload', 'wp-content', 'cdn']) ? 15 : 0)
                    - min(20, $index),
            ])
            ->sortByDesc('score')
            ->pluck('url')
            ->unique(fn ($url) => preg_replace('/^https?:\/\/(?:www\.)?/i', '', Str::lower((string) $url)))
            ->take(4)
            ->values()
            ->all();
    }

    private function contentFromOfficialPage(string $pageUrl, string $sku): array
    {
        $html = $this->fetchHtml($pageUrl);

        if ($html === null || ! Str::contains($this->normalizeSku($pageUrl.' '.strip_tags($html)), $this->normalizeSku($sku))) {
            return [];
        }

        $meta = function (string $name) use ($html): ?string {
            $quoted = preg_quote($name, '/');
            if (preg_match('/<meta[^>]+(?:property|name)=["\']'.$quoted.'["\'][^>]+content=["\']([^"\']+)["\']/iu', $html, $match)
                || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']'.$quoted.'["\']/iu', $html, $match)) {
                return $this->cleanContent($match[1]);
            }

            return null;
        };

        $title = $meta('og:title') ?: $meta('twitter:title');
        if (! $title && preg_match('/<h1\b[^>]*>([\s\S]*?)<\/h1>/iu', $html, $match)) {
            $title = $this->cleanContent($match[1]);
        }

        $description = $meta('og:description') ?: $meta('description') ?: $meta('twitter:description');
        $specs = [];
        preg_match_all('/<tr\b[^>]*>[\s\S]*?<(?:th|td)\b[^>]*>([\s\S]*?)<\/(?:th|td)>[\s\S]*?<td\b[^>]*>([\s\S]*?)<\/td>[\s\S]*?<\/tr>/iu', $html, $rows, PREG_SET_ORDER);

        foreach (array_slice($rows, 0, 24) as $row) {
            $key = $this->cleanContent($row[1] ?? '');
            $value = $this->cleanContent($row[2] ?? '');
            if ($key && $value && mb_strlen($key) <= 100 && mb_strlen($value) <= 500) {
                $specs[$key] = $value;
            }
        }

        return array_filter([
            'title' => $title,
            'description' => $description,
            'specs' => $specs,
        ], fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    private function cleanContent(string $value): ?string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $value !== '' ? $value : null;
    }

    private function fetchHtml(string $url): ?string
    {
        if (array_key_exists($url, $this->htmlCache)) {
            return $this->htmlCache[$url];
        }

        try {
            $response = $this->externalHttp()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; MasterSculeOfficialMedia/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->timeout(15)
                ->retry(1, 300)
                ->get($url);

            return $this->htmlCache[$url] = $response->successful() && $response->body() !== '' ? $response->body() : null;
        } catch (Throwable) {
            return $this->htmlCache[$url] = null;
        }
    }

    private function remoteImageExists(string $url): bool
    {
        try {
            $response = $this->externalHttp()
                ->withHeaders(['User-Agent' => 'MasterSculeOfficialMedia/1.0'])
                ->timeout(6)
                ->head($url);

            return $response->successful()
                && Str::contains(Str::lower((string) $response->header('content-type')), ['image/jpeg', 'image/png', 'image/webp']);
        } catch (Throwable) {
            return false;
        }
    }

    private function isOfficialUrl(string $url, array $domains): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        return collect($domains)->contains(fn ($domain) => $host === $domain || Str::endsWith($host, '.'.$domain));
    }

    private function absoluteUrl(string $baseUrl, string $url): ?string
    {
        $url = trim($url);

        if ($url === '' || Str::startsWith($url, ['data:', 'javascript:', '#'])) {
            return null;
        }

        if (Str::startsWith($url, '//')) {
            return (parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https').':'.$url;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($baseUrl, PHP_URL_HOST);

        if (! $host) {
            return null;
        }

        if (Str::startsWith($url, '/')) {
            return $scheme.'://'.$host.$url;
        }

        $path = (string) parse_url($baseUrl, PHP_URL_PATH);

        return $scheme.'://'.$host.'/'.trim(dirname($path), './').'/'.$url;
    }

    private function normalizeSku(string $value): string
    {
        return Str::lower((string) preg_replace('/[^a-z0-9]/i', '', $value));
    }

    private function emptyResult(array $sources = []): array
    {
        return [
            'found' => false,
            'title' => null,
            'description' => null,
            'specs' => [],
            'images' => [],
            'sources' => $sources,
            'source_urls' => collect($sources)->pluck('url')->filter()->values()->all(),
            'confidence' => 0,
            'official_content_found' => false,
            'existing_product_id' => null,
            'category_id' => null,
            'warnings' => [__('ui.parser_no_exact_sku')],
        ];
    }

    private function externalHttp(): PendingRequest
    {
        return Http::withOptions(['proxy' => '']);
    }
}
