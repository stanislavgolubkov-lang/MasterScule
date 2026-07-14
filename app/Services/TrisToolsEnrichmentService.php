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
                $brandMatch = ! filled($brand) || Str::contains(
                    Str::lower($card['title'].' '.$card['brand']),
                    Str::lower((string) $brand),
                );
                $card['confidence'] = $exactSku ? ($brandMatch ? 96 : 84) : 40;

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

        $page = $this->productPage($candidate['url'], $baseUrl);
        $images = collect($page['images'] ?? [])
            ->push($candidate['image'])
            ->filter()
            ->unique()
            ->take((int) $this->settings->get('max_images_per_product', 4))
            ->values()
            ->all();

        return [
            'found' => true,
            'title' => $page['title'] ?? $candidate['title'],
            'description' => $page['description'] ?? null,
            'specs' => $page['specs'] ?? [],
            'breadcrumb' => $page['breadcrumb'] ?? [],
            'images' => $images,
            'sources' => [[
                'url' => $candidate['url'],
                'domain' => parse_url($candidate['url'], PHP_URL_HOST),
                'title' => $candidate['title'],
                'snippet' => 'TrisTools parser enrichment candidate matched by SKU.',
                'source_type' => 'tristools_enrichment',
                'confidence_score' => $candidate['confidence'],
                'raw_data_json' => ['sku' => $candidate['sku'], 'breadcrumb' => $page['breadcrumb'] ?? []],
            ]],
            'source_urls' => [$candidate['url']],
            'source_url' => $candidate['url'],
            'confidence' => $candidate['confidence'],
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
            $sku = $this->text($xpath->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' article ')]", $node)?->item(0));

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
                'title' => $title,
                'sku' => $sku,
                'brand' => '',
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
            $this->absoluteUrl($baseUrl, (string) $meta('og:image')),
        ]);
        foreach ($xpath->query('//img[@src or @data-src]') ?: [] as $image) {
            $candidate = $this->absoluteUrl($baseUrl, $image->getAttribute('data-src') ?: $image->getAttribute('src'));
            if ($candidate && $this->sameHost($baseUrl, $candidate) && ! Str::contains(Str::lower($candidate), ['logo', 'icon', 'placeholder', '/manufacturer/'])) {
                $images[] = $candidate;
            }
        }

        return [
            'title' => $meta('og:title') ?: $this->text($xpath->query('//h1')?->item(0)),
            'description' => $meta('og:description') ?: $meta('description'),
            'specs' => $specs,
            'breadcrumb' => collect($xpath->query("//*[contains(@class, 'breadcrumb')]//a") ?: [])->map(fn ($node) => $this->text($node))->filter()->values()->all(),
            'images' => array_values(array_unique($images)),
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
