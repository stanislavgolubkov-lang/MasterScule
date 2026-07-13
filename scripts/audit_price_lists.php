<?php

declare(strict_types=1);

use App\Models\Product;
use App\Services\ProductPriceListReader;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$base = 'C:/Users/Ghost/Desktop/MasterScule.Ro/Price/First';
$files = [
    'Torin BIG RED' => $base.'/TONGRUN (Torin BIG Red -Гидравлика) - 20%.xls',
    'Hoegert' => $base.'/HOEGERT (инструмент) - 20% 22.06.2026.xls',
    'JTC' => $base.'/JTC (автомобильный инструмент) - 20%.xls',
    'King Tony' => $base.'/KING TONY (инструмент) - 20%.xls',
    'M7 / Mighty Seven' => $base.'/M7 (пневматический и аккумуляторный инструмент)- 20% 14.05.2026.xls',
];

function priceValue(mixed $value): ?float
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $value = str_replace(["\xc2\xa0", ' '], '', $value);
    $value = preg_replace('/[^0-9,.\-]/', '', $value) ?: '';

    if (substr_count($value, ',') === 1 && substr_count($value, '.') === 0) {
        $value = str_replace(',', '.', $value);
    } elseif (str_contains($value, ',') && str_contains($value, '.')) {
        $value = str_replace(',', '', $value);
    }

    return is_numeric($value) ? round((float) $value, 2) : null;
}

function stockValue(mixed $value): ?int
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $value = preg_replace('/[^0-9\-]/', '', $value) ?: '';

    return is_numeric($value) ? max(0, (int) $value) : null;
}

function normalizedSku(mixed $value): string
{
    return Str::lower(preg_replace('/[^a-z0-9]/i', '', trim((string) $value)) ?: '');
}

function isProductRow(array $row): bool
{
    $sku = trim((string) ($row['sku'] ?? ''));
    $name = trim((string) ($row['name'] ?? ''));
    $hasPriceOrStock = trim((string) ($row['price'] ?? '')) !== ''
        || trim((string) ($row['stock'] ?? '')) !== '';

    return $sku !== ''
        && $name !== ''
        && $hasPriceOrStock
        && (preg_match('/[0-9]/', $sku) || preg_match('/^[A-Z]{2,}[A-Z0-9]*[-_\/]?[A-Z0-9]+$/i', $sku));
}

$reader = app(ProductPriceListReader::class);
$products = Product::with('brand:id,name')->get()->keyBy(fn (Product $product) => normalizedSku($product->sku));
$report = [];
$global = [
    'price_rows' => 0,
    'unique_skus' => 0,
    'matched_products' => 0,
    'missing_products' => 0,
    'missing_prices' => 0,
    'price_mismatches' => 0,
    'currency_mismatches' => 0,
    'discount_state_mismatches' => 0,
    'stock_mismatches' => 0,
];

foreach ($files as $brand => $path) {
    $fileReport = [
        'file' => basename($path),
        'exists' => is_file($path),
        'price_rows' => 0,
        'unique_skus' => 0,
        'matched_products' => 0,
        'missing_products' => 0,
        'missing_prices' => 0,
        'price_mismatches' => 0,
        'currency_mismatches' => 0,
        'discount_state_mismatches' => 0,
        'stock_mismatches' => 0,
        'samples' => [],
    ];

    if (! is_file($path)) {
        $report[$brand] = $fileReport;
        continue;
    }

    $parsed = $reader->read($path, 'xls');
    $bestRows = [];

    foreach ($parsed['rows'] as $row) {
        if (! isProductRow($row)) {
            continue;
        }

        $fileReport['price_rows']++;
        $key = normalizedSku($row['sku']);
        if ($key === '') {
            continue;
        }

        $candidate = [
            'sku' => trim((string) $row['sku']),
            'name' => trim((string) $row['name']),
            'price' => priceValue($row['price'] ?? null),
            'stock' => stockValue($row['stock'] ?? null),
            'row_number' => $row['row_number'] ?? null,
        ];

        if (! isset($bestRows[$key]) || ($bestRows[$key]['stock'] === null && $candidate['stock'] !== null)) {
            $bestRows[$key] = $candidate;
        }
    }

    $fileReport['unique_skus'] = count($bestRows);

    foreach ($bestRows as $key => $row) {
        $product = $products->get($key);
        if (! $product || $product->brand?->name !== $brand) {
            $fileReport['missing_products']++;
            if (count($fileReport['samples']) < 25) {
                $fileReport['samples'][] = ['type' => 'missing_product', 'sku' => $row['sku'], 'row' => $row['row_number']];
            }
            continue;
        }

        $fileReport['matched_products']++;

        if ($row['price'] === null) {
            $fileReport['missing_prices']++;
        } elseif (abs((float) $product->price - $row['price']) >= 0.01) {
            $fileReport['price_mismatches']++;
            if (count($fileReport['samples']) < 25) {
                $fileReport['samples'][] = [
                    'type' => 'price_mismatch',
                    'sku' => $row['sku'],
                    'price_list' => $row['price'],
                    'database' => (float) $product->price,
                    'row' => $row['row_number'],
                ];
            }
        }

        if ($product->currency !== 'MDL') {
            $fileReport['currency_mismatches']++;
        }

        if ($product->old_price !== null || $product->is_discounted) {
            $fileReport['discount_state_mismatches']++;
        }

        if ($row['stock'] !== null && (int) $product->stock_quantity !== $row['stock']) {
            $fileReport['stock_mismatches']++;
            if (count($fileReport['samples']) < 25) {
                $fileReport['samples'][] = [
                    'type' => 'stock_mismatch',
                    'sku' => $row['sku'],
                    'stock_list' => $row['stock'],
                    'database' => (int) $product->stock_quantity,
                    'row' => $row['row_number'],
                ];
            }
        }
    }

    foreach (array_keys($global) as $key) {
        $global[$key] += $fileReport[$key];
    }

    $report[$brand] = $fileReport;
}

echo json_encode([
    'generated_at' => now()->toIso8601String(),
    'summary' => $global,
    'files' => $report,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
