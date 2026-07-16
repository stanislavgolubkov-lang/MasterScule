<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceAdapterInterface;
use App\Services\ProductSources\ProductSourceProductData;
use App\Services\ProductSources\ProductSourceRegistry;
use App\Services\ProductSources\ProductSourceSearchResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

abstract class AbstractOfficialAdapter implements ProductSourceAdapterInterface
{
    private static array $lastRequestAt = [];

    public function __construct(protected readonly ProductSourceRegistry $registry) {}

    abstract protected function brandKeys(): array;

    abstract protected function searchUrls(string $sku): array;

    public function supportsBrand(string $brand): bool
    {
        return in_array($this->registry->brandKey($brand), $this->brandKeys(), true);
    }

    public function searchBySku(string $sku, string $brand, ?string $name = null): ProductSourceSearchResult
    {
        $sku = trim($sku);
        $needle = $this->normalizeSku($sku);
        $directImage = null;

        foreach ($this->searchUrls($sku) as $url) {
            $html = $this->get($url, $brand);
            if (! $html) {
                continue;
            }

            preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>([\s\S]*?)<\/a>/iu', $html, $links, PREG_SET_ORDER);
            foreach ($links as $link) {
                $candidate = html_entity_decode((string) ($link[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = strip_tags((string) ($link[2] ?? ''));
                if ($needle === '' || ! Str::contains($this->normalizeSku($candidate.' '.$text), $needle)) {
                    continue;
                }

                $candidate = $this->absoluteUrl($url, $candidate);
                $domain = (string) parse_url($candidate, PHP_URL_HOST);
                if (! $this->registry->isOfficialDomain($domain, $brand)) {
                    continue;
                }

                if ($this->isDocumentUrl($candidate)) {
                    continue;
                }

                if ($this->isDirectImageUrl($candidate)) {
                    $directImage ??= new ProductSourceSearchResult(
                        true,
                        $sku,
                        $brand,
                        $candidate,
                        $domain,
                        trim($text),
                        true,
                        priority: 100,
                        payload: ['direct_image' => $candidate],
                    );

                    continue;
                }

                return new ProductSourceSearchResult(true, $sku, $brand, $candidate, $domain, trim($text), true, priority: 100);
            }
        }

        return $directImage ?: ProductSourceSearchResult::notFound($sku, $brand);
    }

    public function fetchProductPage(ProductSourceSearchResult $result): ProductSourceProductData
    {
        if ($directImage = $result->payload['direct_image'] ?? null) {
            return new ProductSourceProductData(
                $result,
                title: $result->title,
                images: [$directImage],
                raw: $result->payload,
            );
        }

        $html = $result->url ? $this->get($result->url, $result->brand) : null;
        if (! $html) {
            return new ProductSourceProductData($result, raw: $result->payload);
        }

        $title = $this->meta($html, ['og:title']) ?: $this->firstText($html, 'h1') ?: $result->title;
        $description = $this->meta($html, ['description', 'og:description']);
        $data = new ProductSourceProductData($result, $html, $title, $description, raw: $result->payload);

        return new ProductSourceProductData(
            search: $result,
            html: $html,
            title: $title,
            description: $description,
            images: $this->extractImages($data),
            specifications: $this->extractSpecifications($data),
            breadcrumb: $this->extractBreadcrumb($data),
            raw: $result->payload,
        );
    }

    public function extractImages(ProductSourceProductData $data): array
    {
        $html = (string) $data->html;
        preg_match_all('/(?:src|data-src|data-original|data-large_image|content)=["\']([^"\']+\.(?:jpe?g|png|webp)(?:\?[^"\']*)?)["\']/iu', $html, $matches);
        preg_match_all('/srcset=["\']([^"\']+)["\']/iu', $html, $srcsets);
        $srcsetUrls = collect($srcsets[1] ?? [])
            ->flatMap(fn (string $srcset) => array_map(
                fn (string $candidate) => trim((string) preg_split('/\s+/', trim($candidate))[0]),
                explode(',', $srcset),
            ))
            ->filter(fn (string $url) => preg_match('/\.(?:jpe?g|png|webp)(?:\?[^#]*)?$/i', $url));

        return collect($matches[1] ?? [])
            ->merge($srcsetUrls)
            ->map(fn ($url) => $this->absoluteUrl((string) $data->search->url, html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')))
            ->filter(fn ($url) => parse_url($url, PHP_URL_SCHEME) === 'https')
            ->filter(fn ($url) => $this->registry->isOfficialDomain((string) parse_url($url, PHP_URL_HOST), $data->search->brand))
            ->reject(fn ($url) => Str::contains(Str::lower($url), ['logo', 'icon', 'banner', 'category', 'no-image', 'no_pic']))
            ->unique()
            ->take(4)
            ->values()
            ->all();
    }

    public function extractDescription(ProductSourceProductData $data): ?string
    {
        return $data->description;
    }

    public function extractSpecifications(ProductSourceProductData $data): array
    {
        preg_match_all('/<tr[^>]*>[\s\S]*?<t[hd][^>]*>([\s\S]*?)<\/t[hd]>[\s\S]*?<t[hd][^>]*>([\s\S]*?)<\/t[hd]>[\s\S]*?<\/tr>/iu', (string) $data->html, $rows, PREG_SET_ORDER);
        $specs = [];
        foreach ($rows as $row) {
            $key = trim(strip_tags($row[1] ?? ''));
            $value = trim(strip_tags($row[2] ?? ''));
            if ($key !== '' && $value !== '') {
                $specs[$key] = $value;
            }
        }

        return array_slice($specs, 0, 40, true);
    }

    public function extractBreadcrumb(ProductSourceProductData $data): array
    {
        preg_match_all('/<(?:nav|ol|ul)[^>]*(?:breadcrumb|breadcrumbs)[^>]*>([\s\S]*?)<\/(?:nav|ol|ul)>/iu', (string) $data->html, $blocks);
        preg_match_all('/<a\b[^>]*>([\s\S]*?)<\/a>/iu', implode(' ', $blocks[1] ?? []), $links);

        return collect($links[1] ?? [])->map(fn ($value) => trim(strip_tags($value)))->filter()->unique()->values()->all();
    }

    protected function get(string $url, string $brand): ?string
    {
        $domain = (string) parse_url($url, PHP_URL_HOST);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! $this->registry->isOfficialDomain($domain, $brand)) {
            return null;
        }

        $this->throttle($domain, $brand);
        try {
            $response = $this->request()->get($url);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    protected function request(): PendingRequest
    {
        return Http::withOptions(['proxy' => ''])
            ->withHeaders(['User-Agent' => 'MasterSculeOfficialSource/1.0', 'Accept' => 'text/html,application/json'])
            ->timeout(15)
            ->retry(1, 250);
    }

    protected function normalizeSku(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', Str::upper(Str::ascii($value))) ?: '';
    }

    protected function absoluteUrl(string $base, string $url): string
    {
        if (Str::startsWith($url, '//')) {
            return (parse_url($base, PHP_URL_SCHEME) ?: 'https').':'.$url;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }
        $parts = parse_url($base);
        $root = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        return $root.'/'.ltrim($url, '/');
    }

    private function isDirectImageUrl(string $url): bool
    {
        return (bool) preg_match('/\.(?:jpe?g|png|webp)(?:\?[^#]*)?$/i', $url);
    }

    private function isDocumentUrl(string $url): bool
    {
        return (bool) preg_match('/\.(?:pdf|docx?|xlsx?|csv|zip)(?:\?[^#]*)?$/i', $url);
    }

    private function throttle(string $domain, string $brand): void
    {
        $source = collect($this->registry->forBrand($brand))->firstWhere('domain', preg_replace('/^www\./i', '', $domain));
        $rate = (int) ($source['rate_limit_per_minute'] ?? 60);
        $interval = $rate > 0 ? 60 / $rate : 0;
        $elapsed = microtime(true) - (self::$lastRequestAt[$domain] ?? 0);
        if ($interval > $elapsed) {
            usleep((int) (($interval - $elapsed) * 1_000_000));
        }
        self::$lastRequestAt[$domain] = microtime(true);
    }

    private function meta(string $html, array $names): ?string
    {
        foreach ($names as $name) {
            $quoted = preg_quote($name, '/');
            if (preg_match('/<meta[^>]+(?:name|property)=["\']'.$quoted.'["\'][^>]+content=["\']([^"\']+)["\']/iu', $html, $match)
                || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:name|property)=["\']'.$quoted.'["\']/iu', $html, $match)) {
                return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        return null;
    }

    private function firstText(string $html, string $tag): ?string
    {
        return preg_match('/<'.$tag.'\b[^>]*>([\s\S]*?)<\/'.$tag.'>/iu', $html, $match)
            ? trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            : null;
    }
}
