<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_wrench_socket_families_repair_2026_07_21';

    public function up(): void
    {
        $brandIds = DB::table('brands')->where('name', 'like', '%King Tony%')->pluck('id');
        $categoryIds = DB::table('categories')->whereIn('slug', [
            'tubulare-si-clichete',
            'chei-si-surubelnite',
            'capete-tubulare-impact',
        ])->pluck('id');

        if ($brandIds->isEmpty() || $categoryIds->isEmpty()) {
            return;
        }

        $products = DB::table('products')
            ->whereIn('brand_id', $brandIds)
            ->whereIn('category_id', $categoryIds)
            ->select(['id', 'sku', 'name_ru', 'category_id', 'source_parser_item_id'])
            ->get();

        DB::transaction(function () use ($products): void {
            foreach ($products as $product) {
                $content = $this->parse((string) $product->name_ru, trim((string) $product->sku));
                if (! $content) {
                    continue;
                }

                $this->updateProduct($product, $content);
            }
        });
    }

    private function parse(string $name, string $sku): ?array
    {
        if (preg_match('/^головка\s+торцевая\s+с\s+карданн(?:ом|ым)\s+(\d+(?:[.,]\d+)?)\s*мм\s+(6|12)\s*гран\.?\s+(1\/4|3\/8|1\/2|3\/4)"/iu', $name, $matches) === 1) {
            $size = str_replace(',', '.', $matches[1]);
            $points = (int) $matches[2];
            $drive = $matches[3];

            return [
                'name_ru' => "Карданная торцевая головка KING TONY {$sku}, {$size} мм, {$points}-гранная, привод {$drive} дюйма",
                'name_ro' => "Cap tubular articulat KING TONY {$sku}, {$size} mm, profil cu {$points} laturi, antrenare {$drive} inch",
                'description_ru' => "Карданная торцевая головка KING TONY {$sku} размером {$size} мм имеет {$points}-гранный рабочий профиль, привод {$drive} дюйма и шарнир для работы под углом.",
                'description_ro' => "Capul tubular articulat KING TONY {$sku}, de {$size} mm, are profil cu {$points} laturi, antrenare de {$drive} inch și articulație pentru lucrul sub unghi.",
                'attributes' => [
                    'Тип' => 'Карданная торцевая головка',
                    'Размер' => $size.' mm',
                    'Количество граней' => (string) $points,
                    'Посадочный квадрат' => $drive.' inch',
                    'Механизм' => 'Карданный шарнир',
                ],
            ];
        }

        if (preg_match('/^ключ\s+комбинированный\s+(шарнирный\s+)?с\s+трещоткой\s+(\d+)\s*(?:мм|mm)/iu', $name, $matches) === 1) {
            $articulated = trim((string) ($matches[1] ?? '')) !== '';
            $size = (string) ((int) $matches[2]);
            $typeRu = $articulated
                ? 'Комбинированный шарнирный ключ с трещоткой'
                : 'Комбинированный ключ с трещоткой';
            $typeRo = $articulated
                ? 'Cheie combinată articulată cu clichet'
                : 'Cheie combinată cu clichet';

            return [
                'name_ru' => "{$typeRu} KING TONY {$sku}, {$size} мм",
                'name_ro' => "{$typeRo} KING TONY {$sku}, {$size} mm",
                'description_ru' => "{$typeRu} KING TONY {$sku} размером {$size} мм предназначен для монтажа и демонтажа резьбового крепежа без постоянной перестановки ключа.",
                'description_ro' => "{$typeRo} KING TONY {$sku}, de {$size} mm, este destinat montării și demontării elementelor de fixare filetate fără repoziționarea repetată a cheii.",
                'attributes' => [
                    'Тип' => $typeRu,
                    'Размер' => $size.' mm',
                    'Механизм' => 'Храповый',
                ],
            ];
        }

        if (preg_match('/^головка\s+торцевая\s+ударная\s+(3\/4|1\/2)"\s+(\d+(?:[.,]\d+)?)\s*мм/iu', $name, $matches) === 1) {
            return $this->impactSocket(
                $sku,
                'Ударная торцевая головка',
                'Cap tubular de impact',
                str_replace(',', '.', $matches[2]),
                $matches[1],
            );
        }

        if (preg_match('/^головка\s+торцевая\s+ударная\s+глубокая\s+(?:(тонкостенная)\s+)?(?:(12)\s*гран\.\s+)?(1\/2|3\/4)"\s+(\d+(?:[.,]\d+)?)\s*мм(?:\s+(тонкостенная))?/iu', $name, $matches) === 1) {
            $thinWall = filled($matches[1] ?? null) || filled($matches[5] ?? null);
            $typeRu = $thinWall
                ? 'Глубокая тонкостенная ударная головка'
                : 'Глубокая ударная торцевая головка';
            $typeRo = $thinWall
                ? 'Cap tubular de impact lung cu perete subțire'
                : 'Cap tubular de impact lung';

            return $this->impactSocket(
                $sku,
                $typeRu,
                $typeRo,
                str_replace(',', '.', $matches[4]),
                $matches[3],
                filled($matches[2] ?? null) ? (int) $matches[2] : null,
            );
        }

        if (preg_match('/^Головка\s+ударная\s+2-х\s+сторон(?:ея|няя)\s+(1\/2)"\s+(\d+)\s*\*\s*(\d+)\s*(?:мм|MM)/iu', $name, $matches) === 1) {
            $sizes = $matches[2].' × '.$matches[3];

            return [
                'name_ru' => "Двусторонняя ударная головка KING TONY {$sku}, {$sizes} мм, привод {$matches[1]} дюйма",
                'name_ro' => "Cap tubular de impact cu două capete KING TONY {$sku}, {$sizes} mm, antrenare {$matches[1]} inch",
                'description_ru' => "Двусторонняя ударная головка KING TONY {$sku} имеет рабочие размеры {$sizes} мм и привод {$matches[1]} дюйма.",
                'description_ro' => "Capul tubular de impact cu două capete KING TONY {$sku} are dimensiunile de lucru {$sizes} mm și antrenare de {$matches[1]} inch.",
                'attributes' => [
                    'Тип' => 'Двусторонняя ударная головка',
                    'Размер' => $sizes.' mm',
                    'Посадочный квадрат' => $matches[1].' inch',
                ],
            ];
        }

        return null;
    }

    private function impactSocket(
        string $sku,
        string $typeRu,
        string $typeRo,
        string $size,
        string $drive,
        ?int $points = null,
    ): array {
        $pointsRu = $points ? ", {$points}-гранная" : '';
        $pointsRo = $points ? ", profil cu {$points} laturi" : '';
        $attributes = [
            'Тип' => $typeRu,
            'Размер' => $size.' mm',
            'Посадочный квадрат' => $drive.' inch',
        ];
        if ($points) {
            $attributes['Количество граней'] = (string) $points;
        }

        return [
            'name_ru' => "{$typeRu} KING TONY {$sku}, {$size} мм{$pointsRu}, привод {$drive} дюйма",
            'name_ro' => "{$typeRo} KING TONY {$sku}, {$size} mm{$pointsRo}, antrenare {$drive} inch",
            'description_ru' => "{$typeRu} KING TONY {$sku} размером {$size} мм{$pointsRu} имеет привод {$drive} дюйма и предназначена для работы с ударным инструментом.",
            'description_ro' => "{$typeRo} KING TONY {$sku}, de {$size} mm{$pointsRo}, are antrenare de {$drive} inch și este destinat utilizării cu scule de impact.",
            'attributes' => $attributes,
        ];
    }

    private function updateProduct(object $product, array $content): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('products')->where('id', $product->id)->update([
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
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ]);

        if (! $product->source_parser_item_id) {
            return;
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
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
