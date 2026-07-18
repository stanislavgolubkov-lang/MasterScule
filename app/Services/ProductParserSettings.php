<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Arr;

class ProductParserSettings
{
    public function all(): array
    {
        $stored = Setting::where('key', 'product_parser')->value('value');
        $stored = $stored ? (json_decode($stored, true) ?: []) : [];
        $defaults = config('product_parser');
        $settings = array_replace_recursive($defaults, $stored);

        if (isset($defaults['source_registry'])) {
            $sourceRegistry = collect($defaults['source_registry'])
                ->keyBy('domain')
                ->all();

            foreach ($stored['source_registry'] ?? [] as $source) {
                $domain = $source['domain'] ?? null;
                if ($domain) {
                    $sourceRegistry[$domain] = array_replace($sourceRegistry[$domain] ?? [], $source);
                }
            }

            $settings['source_registry'] = array_values($sourceRegistry);
        }

        if (isset($defaults['allowed_domains'])) {
            $settings['allowed_domains'] = array_values(array_unique(array_merge(
                $defaults['allowed_domains'],
                $stored['allowed_domains'] ?? [],
            )));
        }

        return $settings;
    }

    public function update(array $settings): array
    {
        $current = $this->all();
        $merged = array_replace_recursive($current, $settings);

        Setting::updateOrCreate(
            ['key' => 'product_parser'],
            ['value' => json_encode($merged, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]
        );

        return $merged;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->all(), $key, $default);
    }
}
