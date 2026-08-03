<?php

use App\Models\Product;
use App\Services\Catalog\ProductContentQualityGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $products = Product::query()->orderBy('id')->get();
        $contentGuard = app(ProductContentQualityGuard::class);

        foreach ($products as $product) {
            $nameRu = trim((string) ($product->name_ru ?: $product->name));
            $nameRo = trim((string) $product->name_ro);
            if ($this->isBalanced($nameRu) && $this->isBalanced($nameRo) && $product->sku !== 'DG-501A') {
                continue;
            }

            if ($product->sku === 'JTC-1228') {
                $nameRu = 'Мультиметр цифровой автомобильный с сохранением данных';
            } else {
                $nameRu = $this->repair($nameRu, $product->sku !== 'JTC-4414');
            }
            $nameRo = $this->repair($nameRo, true);

            DB::table('products')->where('id', $product->id)->update([
                'name' => $nameRu,
                'name_ru' => $nameRu,
                'name_ro' => $nameRo,
                'updated_at' => $now,
            ]);
            DB::table('product_parser_items')->where('sku', $product->sku)->update([
                'name_ru' => $nameRu,
                'name_ro' => $nameRo,
                'updated_at' => $now,
            ]);

            $product->forceFill([
                'name' => $nameRu,
                'name_ru' => $nameRu,
                'name_ro' => $nameRo,
            ]);
            if ($contentGuard->evaluate($product)['allowed']) {
                DB::table('products')->where('id', $product->id)->update(['needs_content_review' => false]);
                DB::table('product_parser_items')->where('sku', $product->sku)->update(['needs_content_review' => false]);
            }
        }
    }

    private function repair(string $value, bool $restoreInchMark): string
    {
        if ($value === '') {
            return $value;
        }

        if ($restoreInchMark && preg_match('/\([\d.,\/-]+$/u', $value) === 1) {
            $value .= '″';
        }

        $missing = substr_count($value, '(') - substr_count($value, ')');
        if ($missing > 0) {
            $value .= str_repeat(')', $missing);
        }

        return $value;
    }

    private function isBalanced(string $value): bool
    {
        return substr_count($value, '(') === substr_count($value, ')')
            && substr_count($value, '[') === substr_count($value, ']');
    }

    public function down(): void
    {
        // Truncated catalog names are intentionally not restored.
    }
};
