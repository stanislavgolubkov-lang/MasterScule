<?php

namespace App\Services\ProductSources\Adapters;

use App\Services\ProductSources\ProductSourceProductData;
use Illuminate\Support\Str;

class ThinkcarOfficialAdapter extends AbstractOfficialAdapter
{
    protected function brandKeys(): array
    {
        return ['THINKCAR'];
    }

    protected function searchUrls(string $sku): array
    {
        $queries = array_values(array_unique(array_filter([
            $sku,
            $this->humanizeSku($sku),
            $this->canonicalModel($sku),
        ])));

        return collect([
                'https://thinkcar.in/product/',
                'https://thinkcar.in/page-sitemap.xml',
                'https://thinktool.ru/sitemap.xml',
                'https://thinkcar.ua/content/export/thinkcar.ua/catalog-sitemap.xml',
            ])
            ->merge(collect($queries)->flatMap(fn (string $query) => [
                'https://mythinkcar.com/search?q='.rawurlencode($query),
                'https://thinkcarus.com/search?q='.rawurlencode($query),
            ]))
            ->unique()
            ->values()
            ->all();
    }

    public function fetchProductPage(\App\Services\ProductSources\ProductSourceSearchResult $result): ProductSourceProductData
    {
        $data = parent::fetchProductPage($result);
        if (filled($data->description) || ! filled($data->html)) {
            return $data;
        }

        return new ProductSourceProductData(
            search: $data->search,
            html: $data->html,
            title: $data->title,
            description: $this->descriptionFromOfficialPage((string) $data->html, $result->sku),
            images: $data->images,
            specifications: $data->specifications,
            breadcrumb: $data->breadcrumb,
            packageContents: $data->packageContents,
            raw: $data->raw,
        );
    }

    protected function isCandidateProductUrl(string $url): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));

        return match (true) {
            Str::endsWith($host, 'mythinkcar.com'), Str::endsWith($host, 'thinkcarus.com') => Str::startsWith($path, '/products/'),
            Str::endsWith($host, 'thinkcar.in') => Str::startsWith($path, '/product/') && $path !== '/product/',
            Str::endsWith($host, 'thinktool.ru') => Str::startsWith($path, '/catalog/') && $path !== '/catalog/',
            Str::endsWith($host, 'thinkcar.ua') => $path !== '' && $path !== '/' && ! Str::contains($path, ['/content/', '/blog/', '/news/']),
            default => false,
        };
    }

    protected function candidateMatchesSku(string $candidate, string $text, string $sku): bool
    {
        $haystack = $this->normalizeSku($candidate.' '.$text);

        return collect([
            $this->normalizeSku($sku),
            $this->normalizeSku($this->canonicalModel($sku)),
            $this->normalizeSku((string) preg_replace('/\([^)]*\)/u', '', $sku)),
        ])->filter(fn (string $needle) => strlen($needle) >= 4)
            ->unique()
            ->contains(fn (string $needle) => Str::contains($haystack, $needle));
    }

    protected function sourceTypeForCandidate(string $url): string
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        return Str::endsWith($host, ['thinkcar.in', 'thinktool.ru', 'thinkcar.ua'])
            ? 'official_distributor'
            : 'official_manufacturer';
    }

    protected function shouldCacheResponse(string $url): bool
    {
        return in_array($url, [
            'https://thinkcar.in/product/',
            'https://thinkcar.in/page-sitemap.xml',
            'https://thinktool.ru/sitemap.xml',
            'https://thinkcar.ua/content/export/thinkcar.ua/catalog-sitemap.xml',
        ], true);
    }

    private function humanizeSku(string $sku): string
    {
        $value = preg_replace('/\([^)]*\)/u', '', trim($sku)) ?: $sku;
        $value = preg_replace('/(?<=[a-z])(?=[A-Z])/u', ' ', $value) ?: $value;
        $value = preg_replace('/(?<=[A-Za-z])(?=\d)|(?<=\d)(?=[A-Za-z])/u', ' ', $value) ?: $value;

        return trim((string) preg_replace('/[._\-\/]+/', ' ', $value));
    }

    private function canonicalModel(string $sku): string
    {
        return (string) preg_replace('/^ThinkCarExpert(?=\d)/iu', 'THINKTOOL Expert ', trim($sku));
    }

    private function descriptionFromOfficialPage(string $html, string $sku): ?string
    {
        preg_match_all('/<(?:h2|p)\b[^>]*>([\s\S]*?)<\/(?:h2|p)>/iu', $html, $matches);
        $identities = collect([$sku, $this->canonicalModel($sku)])
            ->map(fn (string $value) => $this->normalizeSku($value))
            ->filter(fn (string $value) => strlen($value) >= 4)
            ->unique();

        $candidates = collect($matches[1] ?? [])
            ->map(fn (string $value) => $this->contentSanitizer->sanitize($value))
            ->filter(fn (string $value) => mb_strlen($value) >= 80)
            ->reject(fn (string $value) => Str::contains(Str::lower($value), [
                'please contact',
                'privacy policy',
                'all rights reserved',
                'where can i buy',
                'shipping policy',
                'return policy',
            ]))
            ->filter(function (string $value) use ($identities) {
                $normalized = $this->normalizeSku($value);

                return $identities->contains(fn (string $identity) => Str::contains($normalized, $identity));
            })
            ->unique()
            ->take(3)
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return Str::limit($candidates->implode("\n\n"), 3000, '');
    }
}
