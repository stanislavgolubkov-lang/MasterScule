<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_socket_families_repair_2026_07_21';

    public function up(): void
    {
        $sourceCategoryId = DB::table('categories')->where('slug', 'surubelnite-si-biti')->value('id');
        $targetCategoryId = DB::table('categories')->where('slug', 'tubulare-si-clichete')->value('id');
        $brandIds = DB::table('brands')->where('name', 'like', '%King Tony%')->pluck('id');

        if (! $sourceCategoryId || ! $targetCategoryId || $brandIds->isEmpty()) {
            return;
        }

        $products = DB::table('products')
            ->whereIn('brand_id', $brandIds)
            ->where('category_id', $sourceCategoryId)
            ->select(['id', 'sku', 'name_ru', 'category_id', 'source_parser_item_id'])
            ->get();

        DB::transaction(function () use ($products, $targetCategoryId): void {
            foreach ($products as $product) {
                $parsed = $this->parseSocket((string) $product->name_ru);
                if (! $parsed) {
                    continue;
                }

                $this->updateProduct($product, $parsed, (int) $targetCategoryId);
            }
        });
    }

    private function parseSocket(string $name): ?array
    {
        $drive = '(1\/4|3\/8|1\/2|3\/4|1)';
        $quote = '["〞″]?';

        if (preg_match('/головка\s+торцевая\s+экстра\s*глубокая\s+(\d+(?:[.,]\d+)?)\s*мм,?\s*(6|12)\s*гран(?:\.|ная|н\.)?\s+'.$drive.$quote.'(?:\s+длина\s+(\d+)\s*мм)?/iu', $name, $matches) === 1) {
            return $this->metricSocket('extra_deep', $matches[1], $matches[2], $matches[3], $matches[4] ?? null);
        }

        if (preg_match('/головка\s+торцевая\s+глубокая\s+E(\d+)\s+'.$drive.$quote.'/iu', $name, $matches) === 1) {
            return $this->profileSocket('deep', 'E'.$matches[1], $matches[2]);
        }

        if (preg_match('/головка\s+торцевая\s+глубокая\s+(\d+(?:[.,]\d+)?)\s*мм,?\s*(6|12)\s*гран(?:\.|ная|н\.)?\s+'.$drive.$quote.'/iu', $name, $matches) === 1) {
            return $this->metricSocket('deep', $matches[1], $matches[2], $matches[3]);
        }

        if (preg_match('/головка\s+торцевая\s+E(\d+)\s+'.$drive.$quote.'/iu', $name, $matches) === 1) {
            return $this->profileSocket('standard', 'E'.$matches[1], $matches[2]);
        }

        if (preg_match('/головка\s+торцевая\s+(\d+(?:[.,]\d+)?)\s*мм\s+(6|12)\s*гран(?:\.|ная|н\.)?\s+'.$drive.$quote.'/iu', $name, $matches) === 1) {
            return $this->metricSocket('standard', $matches[1], $matches[2], $matches[3]);
        }

        return null;
    }

    private function metricSocket(string $variant, string $size, string $points, string $drive, ?string $length = null): array
    {
        $typeRu = match ($variant) {
            'extra_deep' => 'Экстраглубокая торцевая головка',
            'deep' => 'Глубокая торцевая головка',
            default => 'Стандартная торцевая головка',
        };
        $typeRo = match ($variant) {
            'extra_deep' => 'Cap tubular extra-lung',
            'deep' => 'Cap tubular lung',
            default => 'Cap tubular standard',
        };

        return [
            'type_ru' => $typeRu,
            'type_ro' => $typeRo,
            'size' => str_replace(',', '.', $size),
            'points' => (int) $points,
            'drive' => $drive,
            'length' => $length ? (int) $length : null,
        ];
    }

    private function profileSocket(string $variant, string $profile, string $drive): array
    {
        return [
            'type_ru' => $variant === 'deep' ? 'Глубокая торцевая головка' : 'Стандартная торцевая головка',
            'type_ro' => $variant === 'deep' ? 'Cap tubular lung' : 'Cap tubular standard',
            'profile' => $profile,
            'drive' => $drive,
            'length' => null,
        ];
    }

    private function updateProduct(object $product, array $parsed, int $targetCategoryId): void
    {
        $sku = trim((string) $product->sku);
        $driveRu = $parsed['drive'].' дюйма';
        $driveRo = $parsed['drive'].' inch';
        $attributes = ['Тип' => $parsed['type_ru']];

        if (isset($parsed['size'])) {
            $size = $parsed['size'];
            $points = $parsed['points'];
            $nameRu = "{$parsed['type_ru']} {$sku}, {$size} мм, {$points}-гранная, привод {$driveRu}";
            $nameRo = "{$parsed['type_ro']} {$sku}, {$size} mm, profil cu {$points} laturi, antrenare {$driveRo}";
            $descriptionRu = "{$parsed['type_ru']} {$sku} размером {$size} мм имеет {$points}-гранный рабочий профиль и посадочный квадрат {$driveRu}.";
            $descriptionRo = "{$parsed['type_ro']} {$sku}, de {$size} mm, are profil de lucru cu {$points} laturi și pătrat de antrenare de {$driveRo}.";
            $attributes['Размер'] = $size.' mm';
            $attributes['Количество граней'] = (string) $points;
        } else {
            $profile = $parsed['profile'];
            $nameRu = "{$parsed['type_ru']} {$sku}, профиль {$profile}, привод {$driveRu}";
            $nameRo = "{$parsed['type_ro']} {$sku}, profil {$profile}, antrenare {$driveRo}";
            $descriptionRu = "{$parsed['type_ru']} {$sku} имеет рабочий профиль {$profile} и посадочный квадрат {$driveRu}.";
            $descriptionRo = "{$parsed['type_ro']} {$sku} are profil de lucru {$profile} și pătrat de antrenare de {$driveRo}.";
            $attributes['Рабочий профиль'] = $profile.' (External Torx)';
        }

        $attributes['Посадочный квадрат'] = $parsed['drive'].' inch';
        if ($parsed['length']) {
            $nameRu .= ", длина {$parsed['length']} мм";
            $nameRo .= ", lungime {$parsed['length']} mm";
            $descriptionRu = rtrim($descriptionRu, '.')." Общая длина составляет {$parsed['length']} мм.";
            $descriptionRo = rtrim($descriptionRo, '.')." Lungimea totală este de {$parsed['length']} mm.";
            $attributes['Общая длина'] = $parsed['length'].' mm';
        }

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
            'evidence' => json_encode(['Product name explicitly contains socket variant, profile or size, and drive size.'], JSON_UNESCAPED_UNICODE),
            'alternatives' => json_encode([]),
            'validation_errors' => json_encode([]),
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (! $product->source_parser_item_id) {
            return;
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

    public function down(): void
    {
        // Curated content is not reverted to generic imported names and subgroup metadata.
    }
};
