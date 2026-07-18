<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $version = '2026-07-17.3';

    public function up(): void
    {
        $categories = DB::table('categories')->pluck('id', 'slug');
        $electrical = $categories['electroinstrumente'] ?? null;
        $cordless = $categories['instrumente-cu-acumulator'] ?? null;
        $drills = $categories['masini-gaurit-insurubat'] ?? null;
        $grinders = $categories['polizoare'] ?? null;
        $wrenches = $categories['chei-cu-acumulator'] ?? null;
        $batteries = $categories['baterii-incarcatoare'] ?? null;
        $duplicateImpact = $categories['pistoale-impact-cu-acumulator'] ?? null;

        if (! $electrical || ! $cordless || ! $drills || ! $grinders || ! $wrenches || ! $batteries) {
            return;
        }

        DB::table('categories')->whereIn('id', array_filter([$wrenches, $batteries, $duplicateImpact]))->update([
            'parent_id' => $electrical,
            'taxonomy_version' => $this->version,
        ]);
        DB::table('categories')->where('id', $cordless)->update([
            'is_assignable' => false,
            'is_menu_visible' => false,
            'taxonomy_version' => $this->version,
        ]);
        if ($duplicateImpact) {
            DB::table('categories')->where('id', $duplicateImpact)->update([
                'is_assignable' => false,
                'is_menu_visible' => false,
                'taxonomy_version' => $this->version,
            ]);
            $this->move(DB::table('products')->where('category_id', $duplicateImpact), $wrenches, 'duplicate cordless impact branch');
        }

        $this->move(DB::table('products')->where('category_id', $cordless)->where(function (Builder $query): void {
            $query->where('sku', 'like', 'DB-%')
                ->orWhere('sku', 'like', 'DC-%')
                ->orWhere('name_ru', 'like', 'АКБ %')
                ->orWhere('name_ru', 'like', 'Зарядное устройство%');
        }), $batteries, 'battery or charger');

        $this->move(DB::table('products')->where('category_id', $cordless)->where(function (Builder $query): void {
            $query->where('name_ru', 'like', '%дрел%')->orWhere('name_ru', 'like', '%шуруповерт%');
        }), $drills, 'drill or screwdriver by product title');

        $this->move(DB::table('products')->where('category_id', $cordless)->where(function (Builder $query): void {
            $query->where('name_ru', 'like', '%болгарк%')->orWhere('name_ru', 'like', '%шлифмаш%');
        }), $grinders, 'grinder by product title');

        $this->move(DB::table('products')->where('category_id', $cordless)->where(function (Builder $query): void {
            $query->where('name_ru', 'like', '%гайковерт%')
                ->orWhere('name_ru', 'like', '%гайковёрт%')
                ->orWhere('name_ru', 'like', '%импакт%');
        }), $wrenches, 'cordless impact wrench by product title');

        $this->move(DB::table('products')->where('category_id', $cordless), $electrical, 'other electric or cordless tool');

        $pneumaticCutting = $categories['foarfeci-ferastraie-si-debitare-pneumatice'] ?? null;
        if ($pneumaticCutting) {
            $this->move(DB::table('products')->where('category_id', $pneumaticCutting)->whereIn('sku', ['DRS-102', 'DRS-102A']), $electrical, 'cordless saw outside pneumatic tools');

            $electricalPliers = $categories['clesti-electrician-si-cabluri'] ?? null;
            if ($electricalPliers) {
                $this->move(DB::table('products')->where('category_id', $pneumaticCutting)->where('status', 'draft')->where('name_ru', 'like', '%Ножницы электрика%'), $electricalPliers, 'electrician scissors outside pneumatic tools');
            }

            $manualCutting = $categories['taiere-pilire-prelucrare'] ?? null;
            if ($manualCutting) {
                $this->move(DB::table('products')->where('category_id', $pneumaticCutting)->where('status', 'draft')->where(function (Builder $query): void {
                    foreach (['%Выколотк%', '%Кернер%', '%Киянк%', '%Нож %', '%Ножниц%'] as $pattern) {
                        $query->orWhere('name_ru', 'like', $pattern);
                    }
                }), $manualCutting, 'manual cutting or striking tool outside pneumatic tools');
            }

            $manual = $categories['instrument-manual'] ?? null;
            if ($manual) {
                $this->move(DB::table('products')->where('category_id', $pneumaticCutting)->where('status', 'draft')->where('name_ru', 'like', '%Тиски%'), $manual, 'bench vice outside pneumatic tools');
            }
        }
    }

    public function down(): void
    {
        $decisions = DB::table('product_category_decisions')
            ->where('mode', 'storefront_simplification')
            ->where('taxonomy_version', $this->version)
            ->orderByDesc('id')
            ->get();

        foreach ($decisions as $decision) {
            if (! $decision->previous_category_id) {
                continue;
            }
            DB::table('products')->where('id', $decision->product_id)->update(['category_id' => $decision->previous_category_id]);
            DB::table('category_product')->where('product_id', $decision->product_id)->delete();
            DB::table('category_product')->insert([
                'product_id' => $decision->product_id,
                'category_id' => $decision->previous_category_id,
                'is_primary' => true,
                'source' => 'migration_rollback',
                'confidence' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('product_category_decisions')->where('mode', 'storefront_simplification')->where('taxonomy_version', $this->version)->delete();

        $categories = DB::table('categories')->pluck('id', 'slug');
        $cordless = $categories['instrumente-cu-acumulator'] ?? null;
        if ($cordless) {
            DB::table('categories')->whereIn('id', array_filter([
                $categories['chei-cu-acumulator'] ?? null,
                $categories['baterii-incarcatoare'] ?? null,
                $categories['pistoale-impact-cu-acumulator'] ?? null,
            ]))->update(['parent_id' => $cordless]);
            DB::table('categories')->where('id', $cordless)->update(['is_assignable' => true, 'is_menu_visible' => true]);
        }
    }

    private function move(Builder $query, int $targetCategoryId, string $evidence): void
    {
        $products = $query->select(['id', 'category_id'])->get();
        $now = now();

        foreach ($products as $product) {
            if ((int) $product->category_id === $targetCategoryId) {
                continue;
            }

            DB::table('products')->where('id', $product->id)->update(['category_id' => $targetCategoryId]);
            DB::table('category_product')->where('product_id', $product->id)->delete();
            DB::table('category_product')->insert([
                'product_id' => $product->id,
                'category_id' => $targetCategoryId,
                'is_primary' => true,
                'source' => 'storefront_simplification',
                'confidence' => 100,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('product_category_decisions')->insert([
                'product_id' => $product->id,
                'previous_category_id' => $product->category_id,
                'selected_category_id' => $targetCategoryId,
                'taxonomy_version' => $this->version,
                'input_hash' => hash('sha256', 'storefront-simplification|'.$product->id.'|'.$product->category_id.'|'.$targetCategoryId),
                'mode' => 'storefront_simplification',
                'status' => 'applied',
                'classifier_confidence' => 1,
                'verifier_confidence' => 1,
                'evidence' => json_encode([$evidence], JSON_UNESCAPED_UNICODE),
                'alternatives' => json_encode([]),
                'validation_errors' => json_encode([]),
                'applied_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
