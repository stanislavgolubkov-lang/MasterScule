<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $isPlaceholder = static fn ($value): bool => preg_match(
            '/draft parser preview|lorem ipsum|unknown product|\btodo\b|\btbd\b/i',
            trim((string) $value),
        ) === 1;

        DB::table('products')
            ->select(['id', 'package_contents'])
            ->whereNotNull('package_contents')
            ->orderBy('id')
            ->chunkById(250, function ($products) use ($isPlaceholder): void {
                foreach ($products as $product) {
                    $items = json_decode((string) $product->package_contents, true);
                    if (! is_array($items)) {
                        continue;
                    }

                    $clean = array_values(array_filter($items, fn ($value) => ! $isPlaceholder($value)));
                    if ($clean !== array_values($items)) {
                        DB::table('products')->where('id', $product->id)->update([
                            'package_contents' => $clean === [] ? null : json_encode($clean, JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        DB::table('product_parser_items')
            ->select(['id', 'found_specs_json'])
            ->whereNotNull('found_specs_json')
            ->orderBy('id')
            ->chunkById(250, function ($items) use ($isPlaceholder): void {
                foreach ($items as $item) {
                    $specs = json_decode((string) $item->found_specs_json, true);
                    if (! is_array($specs) || ! is_array($specs['_package_contents'] ?? null)) {
                        continue;
                    }

                    $original = array_values($specs['_package_contents']);
                    $specs['_package_contents'] = array_values(array_filter(
                        $original,
                        fn ($value) => ! $isPlaceholder($value),
                    ));
                    if ($specs['_package_contents'] !== $original) {
                        DB::table('product_parser_items')->where('id', $item->id)->update([
                            'found_specs_json' => json_encode($specs, JSON_UNESCAPED_UNICODE),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        $romanianFixes = [
            '4116PR' => [
                'short_description_ro' => 'Set de biți tubulari RIBE 1/2 inch, lungime 100 mm, mărimi M9, M10, M12, M13, M14 și M16, cu suport pentru 6 biți.',
                'description_ro' => 'Setul include biți tubulari RIBE cu antrenare de 1/2 inch și lungime de 100 mm, în mărimile M9, M10, M12, M13, M14 și M16. Suportul inclus are 6 poziții și lungimea de 180 mm.',
            ],
            '9CF230' => [
                'name_ro' => 'Tas de tinichigerie HEEL',
            ],
            '9BA11' => [
                'replace' => ['Выколотка' => 'Dorn de impact'],
            ],
            '1010CMR' => ['replace' => ['С' => 'C']],
            '34467-1AG-1' => ['replace' => ['С' => 'C']],
            '34367-2AG-1' => ['replace' => ['С' => 'C']],
            '9TA54' => ['replace' => ['С' => 'C']],
        ];

        foreach ($romanianFixes as $sku => $fix) {
            $product = DB::table('products')->where('sku', $sku)->first([
                'id', 'name_ro', 'short_description_ro', 'description_ro',
            ]);
            if (! $product) {
                continue;
            }

            $updates = array_intersect_key($fix, array_flip([
                'name_ro', 'short_description_ro', 'description_ro',
            ]));
            foreach (($fix['replace'] ?? []) as $search => $replace) {
                foreach (['name_ro', 'short_description_ro', 'description_ro'] as $column) {
                    $updates[$column] = str_replace($search, $replace, (string) $product->{$column});
                }
            }
            $updates['needs_translation_review'] = false;
            $updates['updated_at'] = now();
            DB::table('products')->where('id', $product->id)->update($updates);

            $parserUpdates = array_intersect_key($updates, array_flip([
                'name_ro', 'short_description_ro', 'description_ro',
                'needs_translation_review', 'updated_at',
            ]));
            DB::table('product_parser_items')->where('sku', $sku)->update($parserUpdates);
        }
    }

    public function down(): void
    {
        // Placeholder and invalid locale artifacts are intentionally not restored.
    }
};
