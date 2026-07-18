<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class TrisToolsEnrichmentService
{
    private static float $lastRequestAt = 0.0;

    public function __construct(private readonly ProductParserSettings $settings) {}

    public function enrich(string $sku, ?string $brand = null): array
    {
        if (! $this->settings->get('tristools.enabled', false)) {
            return $this->emptyResult('disabled');
        }

        $baseUrl = rtrim((string) $this->settings->get('tristools.base_url', 'https://tristool.md'), '/');
        if (! $this->safeBaseUrl($baseUrl)) {
            return $this->emptyResult('invalid_base_url');
        }

        $query = trim($sku);
        $searchUrl = $baseUrl.'/ru/search?searchword='.rawurlencode($query);
        $html = $this->get($searchUrl);
        if ($html === null) {
            return $this->emptyResult('search_failed');
        }

        $candidate = collect($this->cards($html, $baseUrl))
            ->map(function (array $card) use ($sku, $brand) {
                $exactSku = $this->normalizeSku($card['sku']) === $this->normalizeSku($sku);
                $imageSku = collect([$card['image'] ?? null, $card['image_full'] ?? null])
                    ->filter()
                    ->map(fn (string $url) => pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME))
                    ->contains(fn (string $filename) => $this->normalizeSku($filename) === $this->normalizeSku($sku));
                $brandMatch = ! filled($brand) || Str::contains(
                    Str::lower($card['title'].' '.$card['brand']),
                    Str::lower((string) $brand),
                );
                // Exact SKU is the product identity. TrisTool cards often omit
                // the brand text even when the product page is correct. Some
                // GYS cards expose a model code as the article while keeping the
                // supplier reference in the product image filename.
                $card['confidence'] = $exactSku
                    ? ($brandMatch ? 98 : 96)
                    : ($imageSku && $brandMatch ? 94 : 40);

                return $card;
            })
            ->sortByDesc('confidence')
            ->first();

        if (! $candidate) {
            return $this->emptyResult('not_found');
        }

        $minimum = (int) $this->settings->get('tristools.minimum_confidence', 70);
        if ($candidate['confidence'] < $minimum) {
            return $this->emptyResult('low_confidence', $candidate);
        }

        $pageRu = $this->productPage($this->localizedUrl($candidate['url'], 'ru'), $baseUrl);
        $pageRo = $this->productPage($this->localizedUrl($candidate['url'], 'ro'), $baseUrl);
        $images = collect($pageRu['images'] ?? [])
            ->merge($pageRo['images'] ?? [])
            ->push($candidate['image_full'] ?? null)
            ->push($this->fullImageUrl($candidate['image'] ?? null))
            ->filter()
            ->unique()
            ->take((int) $this->settings->get('max_images_per_product', 4))
            ->values()
            ->all();

        $ruUrl = $this->localizedUrl($candidate['url'], 'ru');
        $roUrl = $this->localizedUrl($candidate['url'], 'ro');

        return [
            'found' => true,
            'title' => $pageRu['title'] ?? $candidate['title'],
            'description' => $pageRu['description'] ?? null,
            'title_ru' => $pageRu['title'] ?? $candidate['title'],
            'description_ru' => $pageRu['description'] ?? null,
            'title_ro' => $pageRo['title'] ?? null,
            'description_ro' => $pageRo['description'] ?? null,
            'package_contents' => $pageRu['package_contents'] ?? ($pageRo['package_contents'] ?? []),
            'specs' => array_replace($pageRo['specs'] ?? [], $pageRu['specs'] ?? []),
            'breadcrumb' => $pageRu['breadcrumb'] ?? [],
            'breadcrumb_ro' => $pageRo['breadcrumb'] ?? [],
            'images' => $images,
            'sources' => [[
                'url' => $ruUrl,
                'domain' => parse_url($ruUrl, PHP_URL_HOST),
                'title' => $candidate['title'],
                'snippet' => 'TrisTool primary product card matched by exact SKU.',
                'source_type' => 'tristools_primary',
                'confidence_score' => $candidate['confidence'],
                'raw_data_json' => [
                    'sku' => $candidate['sku'],
                    'breadcrumb' => $pageRu['breadcrumb'] ?? [],
                    'breadcrumb_ro' => $pageRo['breadcrumb'] ?? [],
                    'ro_url' => $roUrl,
                ],
            ]],
            'source_urls' => array_values(array_unique(array_filter([$ruUrl, $roUrl]))),
            'source_url' => $ruUrl,
            'confidence' => $candidate['confidence'],
            'warnings' => [],
        ];
    }

    public function enrichUrl(string $url, string $sku, ?string $brand = null): array
    {
        $baseUrl = $this->baseUrlFromProductUrl($url);
        if (! $baseUrl || ! $this->safeBaseUrl($baseUrl)) {
            return $this->emptyResult('invalid_base_url');
        }

        $page = $this->productPage($url, $baseUrl);
        if ($page === []) {
            return $this->emptyResult('page_failed');
        }

        $haystack = $this->normalizeSku(($page['title'] ?? '').' '.($page['page_text'] ?? '').' '.implode(' ', $page['specs'] ?? []).' '.$url);
        if (! Str::contains($haystack, $this->normalizeSku($sku))) {
            return $this->emptyResult('sku_not_confirmed');
        }

        return [
            'found' => true,
            'title' => $page['title'] ?? null,
            'description' => $page['description'] ?? null,
            'package_contents' => $page['package_contents'] ?? [],
            'specs' => $page['specs'] ?? [],
            'breadcrumb' => $page['breadcrumb'] ?? [],
            'images' => $page['images'] ?? [],
            'sources' => [[
                'url' => $url,
                'domain' => parse_url($url, PHP_URL_HOST),
                'title' => $page['title'] ?? null,
                'snippet' => 'TrisTools product page refreshed by saved source URL.',
                'source_type' => 'tristools_source_refresh',
                'confidence_score' => 96,
                'raw_data_json' => ['sku' => $sku, 'brand' => $brand, 'breadcrumb' => $page['breadcrumb'] ?? []],
            ]],
            'source_urls' => [$url],
            'source_url' => $url,
            'confidence' => 96,
            'warnings' => [],
        ];
    }

    private function cards(string $html, string $baseUrl): array
    {
        [$document, $xpath] = $this->dom($html);
        $cards = [];

        foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' cl-item ')]") ?: [] as $node) {
            $link = $node instanceof \DOMElement && Str::lower($node->tagName) === 'a'
                ? $node
                : $xpath->query('.//a[@href]', $node)?->item(0);
            $image = $xpath->query('.//img[@src or @data-src]', $node)?->item(0);
            $title = $this->text($xpath->query('.//h6', $node)?->item(0));
            $sku = '';
            $skuNodes = $xpath->query(
                ".//*[contains(concat(' ', normalize-space(@class), ' '), ' article ')"
                ." or contains(concat(' ', normalize-space(@class), ' '), ' card-special-clamp-name ')]",
                $node,
            );
            foreach ($skuNodes ?: [] as $skuNode) {
                $sku = $this->text($skuNode);
                if ($sku !== '') {
                    break;
                }
            }
            $brand = $this->text($xpath->query('.//p', $node)?->item(0));

            if (! $link || $title === '' || $sku === '') {
                continue;
            }

            $url = $this->absoluteUrl($baseUrl, $link->getAttribute('href'));
            if (! $url || ! $this->sameHost($baseUrl, $url)) {
                continue;
            }

            $cards[] = [
                'url' => $url,
                'image' => $image ? $this->absoluteUrl($baseUrl, $image->getAttribute('data-src') ?: $image->getAttribute('src')) : null,
                'image_full' => $image ? $this->fullImageUrl($this->absoluteUrl($baseUrl, $this->bestImageAttribute($image))) : null,
                'title' => $title,
                'sku' => $sku,
                'brand' => $brand,
            ];
        }

        return $cards;
    }

    private function productPage(string $url, string $baseUrl): array
    {
        if (! $this->sameHost($baseUrl, $url) || ($html = $this->get($url)) === null) {
            return [];
        }

        [$document, $xpath] = $this->dom($html);
        $meta = function (string $name) use ($xpath): ?string {
            $node = $xpath->query("//meta[@name='{$name}' or @property='{$name}']")?->item(0);

            return $node ? $this->clean($node->getAttribute('content')) : null;
        };
        $specs = [];
        foreach ($xpath->query('//table//tr') ?: [] as $row) {
            $cells = $xpath->query('./th|./td', $row);
            if ($cells && $cells->length >= 2) {
                $key = $this->text($cells->item(0));
                $value = $this->text($cells->item(1));
                if ($key !== '' && $value !== '') {
                    $specs[$key] = $value;
                }
            }
        }

        $images = array_filter([
            $this->fullImageUrl($this->absoluteUrl($baseUrl, (string) $meta('og:image'))),
        ]);
        foreach ($xpath->query('//img[@src or @data-src]') ?: [] as $image) {
            $candidate = $this->fullImageUrl($this->absoluteUrl($baseUrl, $this->bestImageAttribute($image)));
            if ($candidate && $this->sameHost($baseUrl, $candidate) && ! Str::contains(Str::lower($candidate), ['logo', 'icon', 'placeholder', '/manufacturer/'])) {
                $images[] = $candidate;
            }
        }

        $content = $this->descriptionContent($xpath);

        $title = $this->productTitle(
            $meta('og:title')
                ?: $this->text($xpath->query('//h1')?->item(0))
                ?: $this->text($xpath->query("//div[contains(@class, 'product-info')]/preceding-sibling::h3[1]")?->item(0)),
        );
        $metaDescription = $meta('og:description') ?: $meta('description');

        return [
            'title' => $title,
            'description' => $content['description']
                ?: $this->productDescription($title, $specs, $metaDescription),
            'package_contents' => $content['package_contents'],
            'specs' => $specs,
            'breadcrumb' => collect($xpath->query("//*[contains(@class, 'breadcrumb') or contains(@class, 'breadcrumbs')]//a") ?: [])->map(fn ($node) => $this->text($node))->filter()->values()->all(),
            'images' => array_values(array_unique($images)),
            'page_text' => $this->text($xpath->query('//body')?->item(0)),
        ];
    }

    private function descriptionContent(DOMXPath $xpath): array
    {
        $container = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' container-desc ')]")?->item(0);
        if (! $container) {
            return ['description' => null, 'package_contents' => []];
        }

        $description = [];
        $package = [];
        $inPackage = false;

        foreach ($xpath->query('.//strong|.//li|.//p', $container) ?: [] as $node) {
            $text = $this->text($node);
            if ($text === '') {
                continue;
            }

            if (preg_match('/комплектац|complet|continut/iu', $text) === 1) {
                $inPackage = true;
                continue;
            }

            if ($node instanceof \DOMElement && Str::lower($node->tagName) === 'strong') {
                if (! $inPackage && ! preg_match('/описан/iu', $text)) {
                    $description[] = $text;
                }
                continue;
            }

            if ($inPackage) {
                $package[] = $text;
            } else {
                $description[] = $text;
            }
        }

        $description = array_values(array_unique(array_filter($description)));
        $package = array_values(array_unique(array_filter($package)));

        return [
            'description' => $description ? implode("\n", $description) : null,
            'package_contents' => $package,
        ];
    }

    private function get(string $url): ?string
    {
        $rateLimit = max(0, (int) $this->settings->get('tristools.rate_limit_ms', 1000));
        $elapsedMs = (microtime(true) - self::$lastRequestAt) * 1000;
        if ($elapsedMs < $rateLimit) {
            usleep((int) (($rateLimit - $elapsedMs) * 1000));
        }

        try {
            $response = Http::withOptions(['proxy' => ''])
                ->withHeaders(['User-Agent' => 'MasterSculeAdminParser/1.0', 'Accept' => 'text/html'])
                ->timeout((int) $this->settings->get('tristools.timeout', 15))
                ->get($url);
            self::$lastRequestAt = microtime(true);

            return $response->successful() ? $response->body() : null;
        } catch (Throwable) {
            self::$lastRequestAt = microtime(true);

            return null;
        }
    }

    private function dom(string $html): array
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        return [$document, new DOMXPath($document)];
    }

    private function text(?\DOMNode $node): string
    {
        return $node ? $this->clean($node->textContent) : '';
    }

    private function clean(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function normalizeSku(string $value): string
    {
        return Str::upper((string) preg_replace('/[^A-Z0-9]/i', '', $value));
    }

    private function safeBaseUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && filled($host)
            && filter_var($host, FILTER_VALIDATE_IP) === false
            && (Str::lower((string) $host) === 'tristool.md' || Str::endsWith(Str::lower((string) $host), '.tristool.md'))
            && ! in_array(Str::lower((string) $host), ['localhost', 'localhost.localdomain'], true);
    }

    private function sameHost(string $baseUrl, string $url): bool
    {
        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && Str::lower((string) parse_url($baseUrl, PHP_URL_HOST)) === Str::lower((string) parse_url($url, PHP_URL_HOST));
    }

    private function absoluteUrl(string $baseUrl, string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || Str::startsWith($url, ['data:', 'javascript:', '#'])) {
            return null;
        }
        if (Str::startsWith($url, '//')) {
            return 'https:'.$url;
        }
        if (Str::startsWith($url, ['https://', 'http://'])) {
            return $url;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($url, '/');
    }

    private function baseUrlFromProductUrl(string $url): ?string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        return $scheme && $host ? $scheme.'://'.$host : null;
    }

    private function localizedUrl(string $url, string $locale): string
    {
        return preg_replace('~/((?:ru|ro))/~i', '/'.$locale.'/', $url, 1) ?: $url;
    }

    private function bestImageAttribute(\DOMElement $image): string
    {
        foreach (['data-large_image', 'data-zoom-image', 'data-full', 'data-original', 'data-src'] as $attribute) {
            $value = trim($image->getAttribute($attribute));
            if ($value !== '') {
                return $value;
            }
        }

        $srcset = trim($image->getAttribute('srcset') ?: $image->getAttribute('data-srcset'));
        if ($srcset !== '') {
            $best = collect(explode(',', $srcset))
                ->map(function (string $candidate) {
                    $parts = preg_split('/\s+/', trim($candidate)) ?: [];
                    $url = $parts[0] ?? '';
                    $width = (int) preg_replace('/\D+/', '', $parts[1] ?? '0');

                    return ['url' => $url, 'width' => $width];
                })
                ->filter(fn (array $candidate) => $candidate['url'] !== '')
                ->sortByDesc('width')
                ->first();

            if ($best && ($best['url'] ?? '') !== '') {
                return $best['url'];
            }
        }

        return $image->getAttribute('src');
    }

    private function fullImageUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '');
        $path = preg_replace('~/+(?:thumbs?|thumbnail|thumbnails|preview|previews|small|resized)/+~i', '/', $path) ?: $path;
        $path = preg_replace('~/(?:thumb_|thumbnail_|small_)~i', '/', $path) ?: $path;
        $path = preg_replace('~([_-])\d{2,4}x\d{2,4}(?=\.(?:jpe?g|png|webp)$)~i', '', $path) ?: $path;
        $path = preg_replace('~-scaled(?=\.(?:jpe?g|png|webp)$)~i', '', $path) ?: $path;

        $query = '';
        if (isset($parts['query'])) {
            if (Str::contains(Str::lower($path), '/uploaded_files/')) {
                $query = '';
            } elseif (! str_contains((string) $parts['query'], '=')) {
                $query = '?'.$parts['query'];
            } else {
                parse_str($parts['query'], $parameters);
                foreach (['w', 'width', 'h', 'height', 'size', 'resize', 'thumb', 'thumbnail'] as $key) {
                    unset($parameters[$key]);
                }
                $query = $parameters ? '?'.http_build_query($parameters) : '';
            }
        }
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').$path.$query.$fragment;
    }

    private function productTitle(?string $title): ?string
    {
        $title = $this->clean((string) $title);
        $title = preg_replace('/^\s*tristool\.md\s*[-–—:|]\s*/iu', '', $title) ?: $title;

        return $title !== '' ? $title : null;
    }

    private function productDescription(?string $title, array $specs, ?string $metaDescription): ?string
    {
        $metaDescription = $this->clean((string) $metaDescription);
        if ($metaDescription !== '' && ! $this->isGenericMetaDescription($metaDescription)) {
            return $metaDescription;
        }

        $parts = [];
        if (filled($title)) {
            $parts[] = rtrim((string) $title, ". \t\n\r\0\x0B").'.';
        }

        $details = collect($specs)
            ->map(fn ($value, $key) => trim((string) $key).': '.trim((string) $value))
            ->filter(fn (string $value) => ! Str::endsWith($value, ':'))
            ->take(10)
            ->values()
            ->all();

        if ($details !== []) {
            $parts[] = implode('. ', $details).'.';
        }

        return $parts !== [] ? implode(' ', $parts) : null;
    }

    private function isGenericMetaDescription(string $description): bool
    {
        $description = Str::lower($description);

        return Str::contains($description, [
            'оборудование, инструмент и специнструмент для автосервиса',
        ]);
    }

    private function emptyResult(string $reason, ?array $candidate = null): array
    {
        return [
            'found' => false,
            'confidence' => (int) ($candidate['confidence'] ?? 0),
            'possible_match' => $candidate,
            'images' => [],
            'sources' => [],
            'source_urls' => [],
            'warnings' => [$reason],
        ];
    }
}
