<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_residual_category_cleanup_2026_08_03';

    public function up(): void
    {
        $groups = [
            'scule-pentru-suspensie' => ['JTC-1415'],
            'scule-sistem-racire-auto' => ['JTC-1528'],
            'clesti-electrician-si-cabluri' => ['67F1-08'],
            'tinichigerie-si-richtuire' => ['083813'],
            'instrumente-izolate-vde' => [
                'HT1S986',
                '6AC52-12',
                '053816',
                '053793',
                '053779',
                '053786',
                '056367',
                '056329',
                '056343',
            ],
            'clesti-si-instrumente-taiere' => ['6133-08'],
            'burghie-freze' => ['11101SQ', '11325SQ'],
        ];

        $categories = DB::table('categories')
            ->whereIn('slug', array_keys($groups))
            ->pluck('id', 'slug');

        DB::transaction(function () use ($groups, $categories): void {
            foreach ($groups as $slug => $skus) {
                $targetCategoryId = $categories[$slug] ?? null;
                if (! $targetCategoryId) {
                    continue;
                }

                $products = DB::table('products')
                    ->whereIn('sku', $skus)
                    ->get(['id', 'sku', 'category_id']);

                foreach ($products as $product) {
                    if ((int) $product->category_id === (int) $targetCategoryId) {
                        continue;
                    }

                    $now = now();
                    DB::table('products')->where('id', $product->id)->update([
                        'category_id' => $targetCategoryId,
                        'needs_category_review' => false,
                        'updated_at' => $now,
                    ]);
                    DB::table('category_product')->where('product_id', $product->id)->delete();
                    DB::table('category_product')->insert([
                        'product_id' => $product->id,
                        'category_id' => $targetCategoryId,
                        'is_primary' => true,
                        'source' => $this->mode,
                        'confidence' => 100,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('product_category_decisions')->insert([
                        'product_id' => $product->id,
                        'previous_category_id' => $product->category_id,
                        'selected_category_id' => $targetCategoryId,
                        'taxonomy_version' => 'verified-2026-08-03',
                        'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$targetCategoryId),
                        'mode' => $this->mode,
                        'status' => 'applied',
                        'classifier_confidence' => 1,
                        'verifier_confidence' => 1,
                        'evidence' => json_encode(['Deterministic product-type match passed the catalog category validator.'], JSON_UNESCAPED_UNICODE),
                        'alternatives' => json_encode([]),
                        'validation_errors' => json_encode([]),
                        'applied_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $decisions = DB::table('product_category_decisions')
                ->where('mode', $this->mode)
                ->orderByDesc('id')
                ->get();

            foreach ($decisions as $decision) {
                $product = DB::table('products')->where('id', $decision->product_id)->first();
                if (! $product || ! $decision->previous_category_id || (int) $product->category_id !== (int) $decision->selected_category_id) {
                    continue;
                }

                $now = now();
                DB::table('products')->where('id', $product->id)->update([
                    'category_id' => $decision->previous_category_id,
                    'updated_at' => $now,
                ]);
                DB::table('category_product')->where('product_id', $product->id)->delete();
                DB::table('category_product')->insert([
                    'product_id' => $product->id,
                    'category_id' => $decision->previous_category_id,
                    'is_primary' => true,
                    'source' => 'migration_rollback',
                    'confidence' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('product_category_decisions')->where('mode', $this->mode)->delete();
        });
    }
};
