<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_taxonomy_repair_2026_07_21';

    public function up(): void
    {
        $groups = [
            'seturi-de-scule' => [
                '9DT11-1', '9DT11-2', '9DT11-3', '9DT11-4', '9DT11-5', '9DT11-7',
                '9DT11-8', '9DT11-9', '9DT11-10', '9DT11-11', '9DT11-12',
            ],
            'carucioare-de-scule' => ['934-010MRV', '934-010MRV-G'],
            'taiere-pilire-prelucrare' => ['6131-14', '6131-18', '6131-24', '6131-30', '6131-36'],
            'biti-insertii-adaptoare' => ['2138PR'],
            'biti-si-capete' => ['4407MP', '4417PP', '4467PP', '4476MP'],
            'pistoale-pentru-silicon-si-gresare' => ['SG-400', 'SG-401'],
            'lipire-si-consumabile' => [
                '6BC23-1US', '6BC26-1US', '6BC210A', '6BC23AUS', '6BC24A', '6BC26A',
                '6BC141A', '6BC3007', '6BC200', '6BF11-17', '6BF11-17US',
            ],
            'clesti-electrician-si-cabluri' => [
                '6720-10', '6AB21-65', '6AB14-06', '6AB14-65', '42107GX', '6751-44',
                '67B2-05', '67F1-08US', '67A1-07US', '6742-06', '6756-05US',
            ],
            'clesti-si-instrumente-taiere' => [
                '6214-05', '6214-45', '6231-08', '6615-11', '6921-06A', '6031-10',
                '6625-18', '6511-13C', '6315-09', '6517-08C', '6114-05', '45211PP',
                '68HB-07L', '42124GP04', '42104GP',
            ],
            'cutite-lame-rezerve' => ['7978-07', '7971-07'],
            'testere-electrice-si-indicatoare' => ['6CB31'],
            'chei-si-surubelnite' => ['9-1216MR03', '1214MRN01', '1226MR'],
            'instrumente-izolate-vde' => ['083837', '066489'],
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
                    ->select(['id', 'sku', 'category_id'])
                    ->get();

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
                        'taxonomy_version' => 'verified-2026-07-21',
                        'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$targetCategoryId),
                        'mode' => $this->mode,
                        'status' => 'applied',
                        'classifier_confidence' => 1,
                        'verifier_confidence' => 1,
                        'evidence' => json_encode(['Exact product-type match reviewed in RU and RO catalog content.'], JSON_UNESCAPED_UNICODE),
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
                if (! $decision->previous_category_id) {
                    continue;
                }

                $product = DB::table('products')->where('id', $decision->product_id)->first();
                if (! $product || (int) $product->category_id !== (int) $decision->selected_category_id) {
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
