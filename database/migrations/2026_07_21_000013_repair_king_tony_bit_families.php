<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_bit_families_repair_2026_07_21';

    public function up(): void
    {
        $targetCategoryId = DB::table('categories')->where('slug', 'biti-insertii-adaptoare')->value('id');
        $brandIds = DB::table('brands')->where('name', 'like', '%King Tony%')->pluck('id');

        if (! $targetCategoryId || $brandIds->isEmpty()) {
            return;
        }

        $products = DB::table('products')
            ->whereIn('brand_id', $brandIds)
            ->whereIn('category_id', DB::table('categories')->whereIn('slug', [
                'biti-insertii-adaptoare',
                'scule-speciale-auto',
            ])->pluck('id'))
            ->select(['id', 'sku', 'name_ru', 'category_id', 'source_parser_item_id'])
            ->get();

        DB::transaction(function () use ($products, $targetCategoryId): void {
            foreach ($products as $product) {
                $content = $this->parse((string) $product->name_ru, trim((string) $product->sku));
                if (! $content) {
                    continue;
                }

                $this->updateProduct($product, $content, (int) $targetCategoryId);
            }
        });
    }

    private function parse(string $name, string $sku): ?array
    {
        if (preg_match('/^насадка\s+бита\s+(HEX|RIBE)\s+([HM]\d+(?:[.,]\d+)?)\s*(?:мм\.?)?,?\s+(1\/4|1\/2|3\/4)"\s+дл\.\s*(\d+)\s*мм/iu', $name, $matches) === 1) {
            $profile = strtoupper($matches[1]);
            $size = str_replace(',', '.', strtoupper($matches[2]));
            $drive = $matches[3];
            $length = (int) $matches[4];
            $compatibilityRu = $profile === 'RIBE' ? ' для автомобилей VW, Audi и Fiat' : '';
            $compatibilityRo = $profile === 'RIBE' ? ' pentru automobile VW, Audi și Fiat' : '';

            return [
                'name_ru' => "Торцевая насадка-бита KING TONY {$sku}, {$profile} {$size}, привод {$drive} дюйма, {$length} мм",
                'name_ro' => "Cap tubular cu bit KING TONY {$sku}, {$profile} {$size}, antrenare {$drive} inch, {$length} mm",
                'description_ru' => "Торцевая насадка-бита KING TONY {$sku} с рабочим профилем {$profile} {$size}, приводом {$drive} дюйма и длиной {$length} мм{$compatibilityRu}.",
                'description_ro' => "Capul tubular cu bit KING TONY {$sku} are profil de lucru {$profile} {$size}, antrenare de {$drive} inch și lungime de {$length} mm{$compatibilityRo}.",
                'attributes' => array_filter([
                    'Тип' => 'Торцевая насадка-бита',
                    'Рабочий профиль' => $profile,
                    'Размер' => $size,
                    'Посадочный квадрат' => $drive.' inch',
                    'Длина' => $length.' mm',
                    'Совместимость' => $profile === 'RIBE' ? 'VW, Audi, Fiat' : null,
                ]),
            ];
        }

        if (preg_match('/^Бита\s+ударная\s+(1\/4)"\s+(HEX|TORX|PH)\s+(.+?),?\s+(?:L\.|дл\.)\s*(\d+)\s*мм/iu', $name, $matches) === 1) {
            $profile = strtoupper($matches[2]);
            $size = $this->normalizeBitSize($profile, $matches[3]);
            $shank = $matches[1];
            $length = (int) $matches[4];

            return $this->impactBit($sku, $profile, $size, $shank, $length);
        }

        if (preg_match('/^Бита\s+ударная\s+(1\/4)"\s+шлиц(?:евая|овая)\s+([\d.]+)\s*\*\s*([\d.]+)\s+(?:L\.|дл\.)?\s*(\d+)\s*мм/iu', $name, $matches) === 1) {
            return $this->impactBit(
                $sku,
                'SL',
                $matches[2].' × '.$matches[3].' mm',
                $matches[1],
                (int) $matches[4],
            );
        }

        if (preg_match('/^вставка\s+\(бит\)\s+(5\/16)"\s+HEX\s+(H\d+(?:[.,]\d+)?)\s+L\.\s*(\d+)\s*мм\s+\(ударная\)/iu', $name, $matches) === 1) {
            $size = str_replace(',', '.', strtoupper($matches[2]));
            $length = (int) $matches[3];

            return [
                'name_ru' => "Ударная вставка-бита KING TONY {$sku}, HEX {$size}, хвостовик {$matches[1]} дюйма, {$length} мм",
                'name_ro' => "Inserție bit de impact KING TONY {$sku}, HEX {$size}, prindere {$matches[1]} inch, {$length} mm",
                'description_ru' => "Ударная вставка-бита KING TONY {$sku} имеет профиль HEX {$size}, хвостовик {$matches[1]} дюйма и длину {$length} мм.",
                'description_ro' => "Inserția bit de impact KING TONY {$sku} are profil HEX {$size}, prindere de {$matches[1]} inch și lungime de {$length} mm.",
                'attributes' => [
                    'Тип' => 'Ударная вставка-бита',
                    'Рабочий профиль' => 'HEX',
                    'Размер' => $size,
                    'Посадочное место' => $matches[1].' inch',
                    'Длина' => $length.' mm',
                ],
            ];
        }

        if (preg_match('/^держатель\s+бит\s+(1\/4)"\s+магн\.\s*с\s+фиксатором\s+дл\.\s*(\d+)\s*мм/iu', $name, $matches) === 1) {
            $length = (int) $matches[2];

            return [
                'name_ru' => "Магнитный держатель бит KING TONY {$sku}, 1/4 дюйма, {$length} мм, с фиксатором",
                'name_ro' => "Suport magnetic pentru biți KING TONY {$sku}, 1/4 inch, {$length} mm, cu fixare",
                'description_ru' => "Магнитный держатель KING TONY {$sku} предназначен для бит 1/4 дюйма, оснащён фиксатором и имеет длину {$length} мм.",
                'description_ro' => "Suportul magnetic KING TONY {$sku} este destinat biților de 1/4 inch, are sistem de fixare și lungime de {$length} mm.",
                'attributes' => [
                    'Тип' => 'Магнитный держатель бит',
                    'Размер для вставок' => '1/4 inch',
                    'Механизм' => 'С фиксатором',
                    'Длина' => $length.' mm',
                ],
            ];
        }

        return null;
    }

    private function normalizeBitSize(string $profile, string $raw): string
    {
        $size = trim(str_ireplace(['mm', '№'], '', $raw));
        $size = str_replace(',', '.', $size);

        return match ($profile) {
            'HEX' => 'H'.ltrim($size, 'Hh'),
            'TORX' => 'T'.ltrim($size, 'Tt'),
            'PH' => 'PH'.ltrim($size, 'PpHh'),
            default => strtoupper($size),
        };
    }

    private function impactBit(string $sku, string $profile, string $size, string $shank, int $length): array
    {
        return [
            'name_ru' => "Ударная бита KING TONY {$sku}, {$profile} {$size}, хвостовик {$shank} дюйма, {$length} мм",
            'name_ro' => "Bit de impact KING TONY {$sku}, {$profile} {$size}, prindere {$shank} inch, {$length} mm",
            'description_ru' => "Ударная бита KING TONY {$sku} имеет рабочий профиль {$profile} {$size}, хвостовик {$shank} дюйма и длину {$length} мм.",
            'description_ro' => "Bitul de impact KING TONY {$sku} are profil de lucru {$profile} {$size}, prindere de {$shank} inch și lungime de {$length} mm.",
            'attributes' => [
                'Тип' => 'Ударная бита',
                'Рабочий профиль' => $profile,
                'Размер' => $size,
                'Посадочное место' => $shank.' inch',
                'Длина' => $length.' mm',
            ],
        ];
    }

    private function updateProduct(object $product, array $content, int $targetCategoryId): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('products')->where('id', $product->id)->update([
            'category_id' => $targetCategoryId,
            'name' => $content['name_ru'],
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description' => $content['description_ru'],
            'short_description_ru' => $content['description_ru'],
            'short_description_ro' => $content['description_ro'],
            'description' => $content['description_ru'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'attributes' => $attributes,
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

        if ((int) $product->category_id !== $targetCategoryId) {
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
                'evidence' => json_encode(['Product name explicitly identifies a bit or bit holder and its dimensions.']),
                'alternatives' => json_encode([]),
                'validation_errors' => json_encode([]),
                'applied_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! $product->source_parser_item_id) {
            return;
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
            'category_id' => $targetCategoryId,
            'detected_category_id' => $targetCategoryId,
            'detected_category_path' => 'biti-insertii-adaptoare',
            'category_confidence_score' => 100,
            'category_detection_method' => $this->mode,
            'needs_category_review' => false,
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description_ru' => $content['description_ru'],
            'short_description_ro' => $content['description_ro'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'found_title' => $content['name_ru'],
            'found_description' => $content['description_ru'],
            'found_specs_json' => $attributes,
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated SKU-family content is intentionally retained.
    }
};
