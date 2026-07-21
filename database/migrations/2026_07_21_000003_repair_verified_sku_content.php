<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $verified = [
            '37335-030' => [
                'short_description_ru' => 'Пневматическая трещотка KING TONY 37335-030 с квадратом 3/8 дюйма для работы в ограниченном пространстве.',
                'short_description_ro' => 'Clichet pneumatic KING TONY 37335-030 cu antrenare de 3/8 inch, conceput pentru lucrul în spații înguste.',
                'description_ru' => 'Пневматическая трещотка KING TONY 37335-030 с квадратом 3/8 дюйма предназначена для работы в тесном пространстве вокруг двигателей, машин и оборудования. Частота свободного вращения — 230 об/мин, максимальный крутящий момент — 41 Н·м. Длина инструмента — 155 мм, рабочее давление — 6,2 бар, рекомендуемый внутренний диаметр шланга — 10 мм. Масса — 0,49 кг.',
                'description_ro' => 'Clichetul pneumatic KING TONY 37335-030 cu antrenare de 3/8 inch este destinat lucrului în spații înguste din jurul motoarelor, utilajelor și echipamentelor. Turația în gol este de 230 rot/min, iar cuplul maxim este de 41 N·m. Lungimea sculei este de 155 mm, presiunea de lucru este de 6,2 bar, iar diametrul interior recomandat al furtunului este de 10 mm. Greutate: 0,49 kg.',
                'source_url' => 'https://www.kingtony.com/product_detail.php?Key=2914&cID=208&uID=75',
                'air_consumption' => '71 л/мин',
            ],
            '9AL12' => [
                'short_description_ru' => 'Ключ KING TONY 9AL12 для фиксации поликлиновых шкивов диаметром 40–140 мм.',
                'short_description_ro' => 'Cheie KING TONY 9AL12 pentru fixarea fuliilor Poly-V cu diametrul de 40–140 mm.',
                'description_ru' => 'Ключ для фиксации поликлиновых шкивов KING TONY 9AL12 удерживает шкив при снятии или установке крепёжного болта. Ремень типа Poly-V рассчитан на шкивы диаметром 40–140 мм. Длина инструмента — 365 мм, масса — 540 г. Подходит, в частности, для шкивов компрессора, водяного насоса и генератора, а также для снятия и установки масляных фильтров.',
                'description_ro' => 'Cheia KING TONY 9AL12 pentru fixarea fuliilor Poly-V menține fulia blocată în timpul demontării sau montării șurubului de fixare. Cureaua de tip Poly-V este destinată fuliilor cu diametrul de 40–140 mm. Lungimea sculei este de 365 mm, iar greutatea este de 540 g. Poate fi utilizată, între altele, la fuliile compresorului, pompei de apă și alternatorului, precum și la demontarea și montarea filtrelor de ulei.',
                'source_url' => 'https://www.kingtony.com/product_detail.php?Key=982&cID=661&uID=61',
            ],
        ];

        foreach ($verified as $sku => $content) {
            $product = DB::table('products')->where('sku', $sku)->first(['id', 'attributes']);
            if (! $product) {
                continue;
            }

            $productUpdates = [
                'short_description' => $content['short_description_ru'],
                'short_description_ru' => $content['short_description_ru'],
                'short_description_ro' => $content['short_description_ro'],
                'description' => $content['description_ru'],
                'description_ru' => $content['description_ru'],
                'description_ro' => $content['description_ro'],
                'source_url' => $content['source_url'],
                'source_domain' => 'kingtony.com',
                'source_type' => 'official_manufacturer',
                'fallback_source_used' => false,
                'needs_source_review' => false,
                'needs_content_review' => false,
                'generated_content' => false,
                'source_reviewed_at' => now(),
                'updated_at' => now(),
            ];

            if (isset($content['air_consumption'])) {
                $attributes = json_decode((string) $product->attributes, true);
                if (is_array($attributes)) {
                    $attributes['Среднее потребление воздуха'] = $content['air_consumption'];
                    $productUpdates['attributes'] = json_encode(
                        $attributes,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    );
                }
            }

            DB::table('products')->where('id', $product->id)->update($productUpdates);

            $parser = DB::table('product_parser_items')->where('sku', $sku)->first(['id', 'found_specs_json']);
            if (! $parser) {
                continue;
            }

            $parserUpdates = [
                'short_description_ru' => $content['short_description_ru'],
                'short_description_ro' => $content['short_description_ro'],
                'description_ru' => $content['description_ru'],
                'description_ro' => $content['description_ro'],
                'official_source_url' => $content['source_url'],
                'official_source_domain' => 'kingtony.com',
                'official_source_confidence' => 1,
                'fallback_source_used' => false,
                'needs_source_review' => false,
                'needs_content_review' => false,
                'generated_content' => false,
                'content_source_type' => 'official_manufacturer',
                'source_reviewed_at' => now(),
                'updated_at' => now(),
            ];

            if (isset($content['air_consumption'])) {
                $specs = json_decode((string) $parser->found_specs_json, true);
                if (is_array($specs)) {
                    $specs['Среднее потребление воздуха'] = $content['air_consumption'];
                    $parserUpdates['found_specs_json'] = json_encode(
                        $specs,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    );
                }
            }

            DB::table('product_parser_items')->where('id', $parser->id)->update($parserUpdates);
        }

        foreach (['name_ro', 'short_description_ro', 'description_ro'] as $column) {
            DB::table('products')
                ->select(['id', $column])
                ->whereNotNull($column)
                ->whereRaw("LOWER({$column}) LIKE ?", ['%regele tony%'])
                ->orderBy('id')
                ->chunkById(100, function ($products) use ($column): void {
                    foreach ($products as $product) {
                        DB::table('products')->where('id', $product->id)->update([
                            $column => str_ireplace('regele tony', 'KING TONY', (string) $product->{$column}),
                            'updated_at' => now(),
                        ]);
                    }
                });

            DB::table('product_parser_items')
                ->select(['id', $column])
                ->whereNotNull($column)
                ->whereRaw("LOWER({$column}) LIKE ?", ['%regele tony%'])
                ->orderBy('id')
                ->chunkById(100, function ($items) use ($column): void {
                    foreach ($items as $item) {
                        DB::table('product_parser_items')->where('id', $item->id)->update([
                            $column => str_ireplace('regele tony', 'KING TONY', (string) $item->{$column}),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }

        DB::table('products')->where('sku', 'P7941-08')->update([
            'name_ro' => 'Cuțit pliabil cu blocare a lamei, ediție aniversară KING TONY 35 de ani',
            'updated_at' => now(),
        ]);
        DB::table('product_parser_items')->where('sku', 'P7941-08')->update([
            'name_ro' => 'Cuțit pliabil cu blocare a lamei, ediție aniversară KING TONY 35 de ani',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally not reverted to known-bad content.
    }
};
