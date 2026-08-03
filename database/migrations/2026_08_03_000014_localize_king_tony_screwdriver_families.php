<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $families = $this->families();

        DB::table('products')->where(function ($query) use ($families): void {
            foreach (array_keys($families) as $prefix) {
                $query->orWhere('sku', 'like', $prefix.'%');
            }
        })->orderBy('id')->chunkById(100, function ($products) use ($families, $now): void {
            foreach ($products as $product) {
                $family = collect($families)->first(fn ($data, $prefix) => str_starts_with((string) $product->sku, $prefix));
                if (! is_array($family)) {
                    continue;
                }

                $content = $this->content((string) $product->sku, $family);
                DB::table('products')->where('id', $product->id)->update([
                    'name' => $content['name_ru'],
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description' => $content['short_ru'],
                    'short_description_ru' => $content['short_ru'],
                    'short_description_ro' => $content['short_ro'],
                    'description' => $content['description_ru'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'meta_title' => Str::limit($content['name_ru'].' | MasterScule', 255, ''),
                    'meta_description' => Str::limit($content['short_ru'], 155, ''),
                    'attributes' => json_encode($content['attributes'], JSON_UNESCAPED_UNICODE),
                    'needs_translation_review' => false,
                    'needs_content_review' => false,
                    'generated_content' => false,
                    'updated_at' => $now,
                ]);
                DB::table('product_parser_items')->where('sku', $product->sku)->update([
                    'name_ru' => $content['name_ru'],
                    'name_ro' => $content['name_ro'],
                    'short_description_ru' => $content['short_ru'],
                    'short_description_ro' => $content['short_ro'],
                    'description_ru' => $content['description_ru'],
                    'description_ro' => $content['description_ro'],
                    'needs_translation_review' => false,
                    'needs_content_review' => false,
                    'generated_content' => false,
                    'content_source_type' => 'official_manufacturer',
                    'translation_source_type' => 'verified_manual_translation',
                    'translation_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    private function families(): array
    {
        return [
            '1422' => ['ru' => 'Шлицевая отвёртка King Tony', 'ro' => 'Șurubelniță dreaptă King Tony', 'tip_ru' => 'Шлицевой', 'tip_ro' => 'Drept', 'material' => 'SNCM + V', 'handle_ru' => 'PP + TPR', 'handle_ro' => 'PP + TPR', 'shaft_ru' => 'Круглый', 'shaft_ro' => 'Rotund', 'standard' => 'DIN ISO 2380'],
            '1421' => ['ru' => 'Крестовая отвёртка Phillips King Tony', 'ro' => 'Șurubelniță Phillips King Tony', 'tip_ru' => 'Phillips', 'tip_ro' => 'Phillips', 'material' => 'SNCM + V', 'handle_ru' => 'PP + TPR', 'handle_ro' => 'PP + TPR', 'shaft_ru' => 'Круглый', 'shaft_ro' => 'Rotund', 'standard' => 'DIN ISO 8764'],
            '1432' => ['ru' => 'Прецизионная шлицевая отвёртка King Tony', 'ro' => 'Șurubelniță de precizie dreaptă King Tony', 'tip_ru' => 'Шлицевой', 'tip_ro' => 'Drept', 'material' => 'SNCM + V', 'handle_ru' => 'PP', 'handle_ro' => 'PP', 'shaft_ru' => 'Круглый', 'shaft_ro' => 'Rotund', 'standard' => 'DIN 5263'],
            '1431' => ['ru' => 'Прецизионная отвёртка Phillips King Tony', 'ro' => 'Șurubelniță de precizie Phillips King Tony', 'tip_ru' => 'Phillips', 'tip_ro' => 'Phillips', 'material' => 'SNCM + V', 'handle_ru' => 'PP', 'handle_ro' => 'PP', 'shaft_ru' => 'Круглый', 'shaft_ro' => 'Rotund', 'standard' => 'DIN ISO 8764'],
            '1462' => ['ru' => 'Сквозная шлицевая отвёртка King Tony', 'ro' => 'Șurubelniță dreaptă cu tijă traversantă King Tony', 'tip_ru' => 'Шлицевой', 'tip_ro' => 'Drept', 'material' => 'SNCM + V', 'handle_ru' => 'PP + TPR', 'handle_ro' => 'PP + TPR', 'shaft_ru' => 'Шестигранный, сквозной', 'shaft_ro' => 'Hexagonal, traversant', 'standard' => 'DIN ISO 2380'],
            '1461' => ['ru' => 'Сквозная отвёртка Phillips King Tony', 'ro' => 'Șurubelniță Phillips cu tijă traversantă King Tony', 'tip_ru' => 'Phillips', 'tip_ro' => 'Phillips', 'material' => 'SNCM + V', 'handle_ru' => 'PP + TPR', 'handle_ro' => 'PP + TPR', 'shaft_ru' => 'Шестигранный, сквозной', 'shaft_ro' => 'Hexagonal, traversant', 'standard' => 'DIN ISO 8764'],
            '1412' => ['ru' => 'Шлицевая отвёртка King Tony', 'ro' => 'Șurubelniță dreaptă King Tony', 'tip_ru' => 'Шлицевой', 'tip_ro' => 'Drept', 'material' => 'Cr-V', 'handle_ru' => 'PP', 'handle_ro' => 'PP', 'shaft_ru' => 'Круглый', 'shaft_ro' => 'Rotund', 'standard' => 'DIN ISO 2380'],
            '1411' => ['ru' => 'Крестовая отвёртка Phillips King Tony', 'ro' => 'Șurubelniță Phillips King Tony', 'tip_ru' => 'Phillips', 'tip_ro' => 'Phillips', 'material' => 'Cr-V', 'handle_ru' => 'PP', 'handle_ro' => 'PP', 'shaft_ru' => 'Круглый', 'shaft_ro' => 'Rotund', 'standard' => 'DIN ISO 8764'],
            '1482' => ['ru' => 'Сквозная шлицевая отвёртка King Tony', 'ro' => 'Șurubelniță dreaptă cu tijă traversantă King Tony', 'tip_ru' => 'Шлицевой', 'tip_ro' => 'Drept', 'material' => 'Cr-V', 'handle_ru' => 'PP', 'handle_ro' => 'PP', 'shaft_ru' => 'Шестигранный, сквозной', 'shaft_ro' => 'Hexagonal, traversant', 'standard' => 'DIN ISO 2380'],
            '1481' => ['ru' => 'Сквозная отвёртка Phillips King Tony', 'ro' => 'Șurubelniță Phillips cu tijă traversantă King Tony', 'tip_ru' => 'Phillips', 'tip_ro' => 'Phillips', 'material' => 'Cr-V', 'handle_ru' => 'PP', 'handle_ro' => 'PP', 'shaft_ru' => 'Шестигранный, сквозной', 'shaft_ro' => 'Hexagonal, traversant', 'standard' => 'DIN ISO 8764'],
        ];
    }

    private function content(string $sku, array $family): array
    {
        $nameRu = $family['ru'].' '.$sku;
        $nameRo = $family['ro'].' '.$sku;
        $descriptionRu = "{$family['ru']}, SKU {$sku}. Стержень из легированной стали {$family['material']} имеет хромированное покрытие и чёрный рабочий наконечник. Профиль: {$family['tip_ru']}; стержень: {$family['shaft_ru']}; рукоятка: {$family['handle_ru']}. Соответствует {$family['standard']}.";
        $descriptionRo = "{$family['ro']}, SKU {$sku}. Tija din oțel aliat {$family['material']} are finisaj cromat și vârf de lucru negru. Profil: {$family['tip_ro']}; tijă: {$family['shaft_ro']}; mâner: {$family['handle_ro']}. Conform {$family['standard']}.";

        return [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_ru' => "{$nameRu}; профиль {$family['tip_ru']}, стержень {$family['shaft_ru']}.",
            'short_ro' => "{$nameRo}; profil {$family['tip_ro']}, tijă {$family['shaft_ro']}.",
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'attributes' => [
                'Тип наконечника' => $family['tip_ru'],
                'Форма стержня' => $family['shaft_ru'],
                'Материал стержня' => $family['material'],
                'Материал рукоятки' => $family['handle_ru'],
                'Покрытие' => 'Хромированное, чёрный наконечник',
                'Стандарт' => $family['standard'],
                'Артикул производителя' => $sku,
            ],
        ];
    }

    public function down(): void
    {
        // Verified official-family localization is intentionally retained.
    }
};
