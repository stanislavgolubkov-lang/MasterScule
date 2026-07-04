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

        return array_replace_recursive(config('product_parser'), $stored);
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
