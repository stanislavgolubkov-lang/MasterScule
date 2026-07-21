<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_socket_family_repair_2026_07_21';

    public function up(): void
    {
        $sourceCategoryId = DB::table('categories')->where('slug', 'surubelnite-si-biti')->value('id');
        $targetCategoryId = DB::table('categories')->where('slug', 'tubulare-si-clichete')->value('id');
        $brandIds = DB::table('brands')->where('name', 'like', '%King Tony%')->pluck('id');

        if (! $sourceCategoryId || ! $targetCategoryId || $brandIds->isEmpty()) {
            return;
        }

        $pattern = '/головка\s+торцевая\s+(\d+(?:[.,]\d+)?)\s*мм\s+(6|12)\s*гран(?:\.|ная|н\.)?\s+(1\/4|3\/8|1\/2|3\/4)(?:["〞″])?/iu';
        $products = DB::table('products')
            ->whereIn('brand_id', $brandIds)
            ->where('category_id', $sourceCategoryId)
            ->select(['id', 'sku', 'name_ru', 'category_id', 'source_parser_item_id'])
            ->get();

        DB::transaction(function () use ($products, $pattern, $targetCategoryId): void {
            foreach ($products as $product) {
                if (preg_match($pattern, (string) $product->name_ru, $matches) !== 1) {
                    continue;
                }

                $size = str_replace(',', '.', $matches[1]);
                $points = (int) $matches[2];
                $drive = $matches[3].' inch';
                $sku = trim((string) $product->sku);
                $nameRu = "Торцевая головка {$sku}, {$size} мм, {$points}-гранная, привод {$matches[3]} дюйма";
                $nameRo = "Cap tubular {$sku}, {$size} mm, profil cu {$points} laturi, antrenare {$matches[3]} inch";
                $descriptionRu = "Стандартная торцевая головка {$sku} размером {$size} мм имеет {$points}-гранный рабочий профиль и посадочный квадрат {$matches[3]} дюйма.";
                $descriptionRo = "Capul tubular standard {$sku}, de {$size} mm, are profil de lucru cu {$points} laturi și pătrat de antrenare de {$matches[3]} inch.";
                $attributes = [
                    'Тип' => 'Стандартная торцевая головка',
                    'Размер' => $size.' mm',
                    'Количество граней' => (string) $points,
                    'Посадочный квадрат' => $drive,
                ];
                $now = now();

                DB::table('products')->where('id', $product->id)->update([
                    'category_id' => $targetCategoryId,
                    'name' => $nameRu,
                    'name_ru' => $nameRu,
                    'name_ro' => $nameRo,
                    'short_description' => $descriptionRu,
                    'short_description_ru' => $descriptionRu,
                    'short_description_ro' => $descriptionRo,
                    'description' => $descriptionRu,
                    'description_ru' => $descriptionRu,
                    'description_ro' => $descriptionRo,
                    'attributes' => json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'needs_category_review' => false,
                    'needs_content_review' => false,
                    'generated_content' => false,
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
                    'input_hash' => hash('sha256', $this->mode.'|'.$sku.'|'.$product->category_id.'|'.$targetCategoryId),
                    'mode' => $this->mode,
                    'status' => 'applied',
                    'classifier_confidence' => 1,
                    'verifier_confidence' => 1,
                    'evidence' => json_encode(['Product name explicitly contains socket size, point count, and drive size.'], JSON_UNESCAPED_UNICODE),
                    'alternatives' => json_encode([]),
                    'validation_errors' => json_encode([]),
                    'applied_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if (! $product->source_parser_item_id) {
                    continue;
                }

                DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
                    'category_id' => $targetCategoryId,
                    'detected_category_id' => $targetCategoryId,
                    'detected_category_path' => 'tubulare-si-clichete',
                    'category_confidence_score' => 100,
                    'category_detection_method' => $this->mode,
                    'needs_category_review' => false,
                    'name_ru' => $nameRu,
                    'name_ro' => $nameRo,
                    'short_description_ru' => $descriptionRu,
                    'short_description_ro' => $descriptionRo,
                    'description_ru' => $descriptionRu,
                    'description_ro' => $descriptionRo,
                    'found_title' => $nameRu,
                    'found_description' => $descriptionRu,
                    'found_specs_json' => json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'needs_content_review' => false,
                    'generated_content' => false,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Curated content is not reverted to generic imported names and subgroup metadata.
    }
};
