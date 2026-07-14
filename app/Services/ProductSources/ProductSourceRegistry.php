<?php

namespace App\Services\ProductSources;

use App\Services\ProductParserSettings;
use Illuminate\Support\Str;

class ProductSourceRegistry
{
    public function __construct(private readonly ProductParserSettings $settings) {}

    public function forBrand(string $brand): array
    {
        $key = $this->brandKey($brand);

        return collect($this->settings->get('source_registry', []))
            ->filter(fn (array $source) => ($source['enabled'] ?? true) && in_array($key, $source['brands'] ?? [], true))
            ->sortByDesc('priority')
            ->values()
            ->all();
    }

    public function fallback(): array
    {
        return collect($this->settings->get('source_registry', []))
            ->filter(fn (array $source) => ($source['enabled'] ?? true) && ($source['fallback_only'] ?? false))
            ->sortByDesc('priority')
            ->values()
            ->all();
    }

    public function isOfficialDomain(string $domain, string $brand): bool
    {
        $domain = Str::lower(preg_replace('/^www\./i', '', trim($domain)) ?: '');

        return collect($this->forBrand($brand))->contains(function (array $source) use ($domain) {
            $allowed = Str::lower((string) ($source['domain'] ?? ''));

            return $allowed !== '' && ($domain === $allowed || Str::endsWith($domain, '.'.$allowed));
        });
    }

    public function brandKey(string $brand): string
    {
        $brand = Str::upper(Str::ascii(trim($brand)));

        return match (true) {
            Str::contains($brand, 'KING') => 'KING_TONY',
            Str::contains($brand, ['M7', 'MIGHTY']) => 'M7',
            Str::contains($brand, 'JTC') => 'JTC',
            Str::contains($brand, ['HOEGERT', 'HOGERT']) => 'HOEGERT',
            Str::contains($brand, ['TORIN', 'BIG RED']) => 'TORIN',
            Str::contains($brand, 'TONGRUN') => 'TONGRUN',
            default => preg_replace('/[^A-Z0-9]+/', '_', $brand) ?: 'UNKNOWN',
        };
    }
}
