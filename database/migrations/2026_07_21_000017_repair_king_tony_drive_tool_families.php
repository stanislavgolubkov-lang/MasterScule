<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $brandIds = DB::table('brands')->where('name', 'like', '%King Tony%')->pluck('id');
        $categoryId = DB::table('categories')->where('slug', 'tubulare-si-clichete')->value('id');

        if ($brandIds->isEmpty() || ! $categoryId) {
            return;
        }

        $products = DB::table('products')
            ->whereIn('brand_id', $brandIds)
            ->where('category_id', $categoryId)
            ->select(['id', 'sku', 'name_ru', 'source_parser_item_id'])
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
        if (preg_match('/^вороток\s+Т-образный\s+с\s+карданом,?\s+(1\/4|3\/8|1\/2)"\s+длина\s+(\d+)\s*мм/iu', $name, $matches) === 1) {
            return $this->handle(
                $sku,
                'Т-образный вороток с карданом',
                'Mâner în T cu articulație cardanică',
                $matches[1],
                (int) $matches[2],
                ['Механизм' => 'Карданный шарнир'],
            );
        }

        if (preg_match('/^вороток\s+с\s+шарниром,?\s+(1\/4|3\/8|1\/2)"\s+длина\s+(\d+)\s*мм/iu', $name, $matches) === 1) {
            return $this->handle($sku, 'Шарнирный вороток', 'Mâner articulat', $matches[1], (int) $matches[2], [
                'Механизм' => 'Шарнирная головка',
            ]);
        }

        if (preg_match('/^вороток\s+скользящий,?\s+(1\/4|3\/8|1\/2)"\s+длина\s+(\d+)\s*мм/iu', $name, $matches) === 1) {
            return $this->handle($sku, 'Скользящий вороток', 'Mâner culisant', $matches[1], (int) $matches[2]);
        }

        if (preg_match('/^Вороток\s+Т-образный\s+для\s+головок\s+и\s+вставок\s+\(бит\)\s+(1\/4)"/iu', $name, $matches) === 1) {
            return [
                'name_ru' => "Т-образный вороток KING TONY {$sku}, привод {$matches[1]} дюйма, для головок и бит",
                'name_ro' => "Mâner în T KING TONY {$sku}, antrenare {$matches[1]} inch, pentru capete și biți",
                'description_ru' => "Т-образный вороток KING TONY {$sku} с приводом {$matches[1]} дюйма предназначен для совместимых торцевых головок и бит.",
                'description_ro' => "Mânerul în T KING TONY {$sku}, cu antrenare de {$matches[1]} inch, este destinat capetelor tubulare și biților compatibili.",
                'attributes' => [
                    'Тип' => 'Т-образный вороток',
                    'Посадочный квадрат' => $matches[1].' inch',
                    'Применение' => 'Для торцевых головок и бит',
                ],
            ];
        }

        if (preg_match('/^трещотка\s+(\d+)\s*зубц(?:а|ев)?,?\s+(1\/4|3\/8|1\/2)"(?:\s+длина\s+(\d+)\s*мм)?(.*)$/iu', $name, $matches) === 1) {
            return $this->ratchet(
                $sku,
                (int) $matches[1],
                $matches[2],
                filled($matches[3] ?? null) ? (int) $matches[3] : null,
                (string) ($matches[4] ?? ''),
            );
        }

        if (preg_match('/^трещотка\s+вороток\s+для\s+головок\s+(1\/4)"\s+дисковая\s+(\d+)\s*зубц(?:а|ев)?/iu', $name, $matches) === 1) {
            return $this->specialRatchet(
                $sku,
                'Трещотка для торцевых головок',
                'Clichet pentru capete tubulare',
                $matches[1],
                (int) $matches[2],
                'Дисковый переключатель',
            );
        }

        if (preg_match('/^трещотка\s+вороток\s+для\s+вставок\s+\(бит\)\s+(1\/4)"\s+дисковая\s+бесшаговая/iu', $name, $matches) === 1) {
            return $this->specialRatchet(
                $sku,
                'Трещотка для бит',
                'Clichet pentru biți',
                $matches[1],
                null,
                'Бесшаговый',
            );
        }

        if (preg_match('/^(1\/4|3\/8|1\/2)[〞"]\s+ДР\.\s+(?:Universal Joint|Универсальный шарнир)-KING TONY/iu', $name, $matches) === 1) {
            return $this->joint($sku, 'Универсальный шарнир', 'Articulație universală', $matches[1]);
        }

        if (preg_match('/^(1)[〞"]\s+ДР\.\s+Карданный шарнир\s+-\s+с\s+шаром-KING TONY/iu', $name, $matches) === 1) {
            return $this->joint($sku, 'Универсальный шарнир', 'Articulație universală', $matches[1], true);
        }

        if (preg_match('/^карданный\s+шарнир\s+привода\s+ударный\s+(1\/2)"\s+с\s+шаром/iu', $name, $matches) === 1) {
            return $this->joint($sku, 'Ударный карданный шарнир', 'Articulație cardanică de impact', $matches[1], true);
        }

        if (preg_match('/^(1\/4)[〞"]\s+ДР\.\s+Адаптер\s+(1\/4)[〞"]F\s+x\s+(3\/8)[〞"]M-KING TONY/iu', $name, $matches) === 1) {
            return [
                'name_ru' => "Переходной адаптер KING TONY {$sku}, вход {$matches[2]} дюйма (F), выход {$matches[3]} дюйма (M)",
                'name_ro' => "Adaptor de trecere KING TONY {$sku}, intrare {$matches[2]} inch (F), ieșire {$matches[3]} inch (M)",
                'description_ru' => "Переходной адаптер KING TONY {$sku} соединяет входной квадрат {$matches[2]} дюйма с выходным квадратом {$matches[3]} дюйма.",
                'description_ro' => "Adaptorul de trecere KING TONY {$sku} conectează pătratul de intrare de {$matches[2]} inch la pătratul de ieșire de {$matches[3]} inch.",
                'attributes' => [
                    'Тип' => 'Переходной адаптер',
                    'Входной квадрат' => $matches[2].' inch (F)',
                    'Выходной квадрат' => $matches[3].' inch (M)',
                ],
            ];
        }

        if (preg_match('/^головка\s+свечная\s+(\d+(?:[.,]\d+)?)\s*мм\s+(6|12)\s*гран\.\s+(1\/4|3\/8|1\/2)"\s+шарниром\s+дл\.\s*(\d+)\s*мм/iu', $name, $matches) === 1) {
            $size = str_replace(',', '.', $matches[1]);
            $points = (int) $matches[2];
            $length = (int) $matches[4];

            return [
                'name_ru' => "Шарнирная свечная головка KING TONY {$sku}, {$size} мм, {$points}-гранная, привод {$matches[3]} дюйма, {$length} мм",
                'name_ro' => "Cap tubular articulat pentru bujii KING TONY {$sku}, {$size} mm, profil cu {$points} laturi, antrenare {$matches[3]} inch, {$length} mm",
                'description_ru' => "Шарнирная свечная головка KING TONY {$sku} размером {$size} мм имеет {$points}-гранный профиль, привод {$matches[3]} дюйма и длину {$length} мм.",
                'description_ro' => "Capul tubular articulat pentru bujii KING TONY {$sku}, de {$size} mm, are profil cu {$points} laturi, antrenare de {$matches[3]} inch și lungime de {$length} mm.",
                'attributes' => [
                    'Тип' => 'Шарнирная свечная головка',
                    'Размер' => $size.' mm',
                    'Количество граней' => (string) $points,
                    'Посадочный квадрат' => $matches[3].' inch',
                    'Механизм' => 'Карданный шарнир',
                    'Длина' => $length.' mm',
                ],
            ];
        }

        if (preg_match('/^удлинитель\s+(1\/4|3\/8|1\/2)"\s+длина\s+(\d+)\s*мм/iu', $name, $matches) === 1) {
            return [
                'name_ru' => "Удлинитель KING TONY {$sku}, привод {$matches[1]} дюйма, {$matches[2]} мм",
                'name_ro' => "Prelungitor KING TONY {$sku}, antrenare {$matches[1]} inch, {$matches[2]} mm",
                'description_ru' => "Удлинитель KING TONY {$sku} с приводом {$matches[1]} дюйма имеет длину {$matches[2]} мм.",
                'description_ro' => "Prelungitorul KING TONY {$sku}, cu antrenare de {$matches[1]} inch, are lungimea de {$matches[2]} mm.",
                'attributes' => [
                    'Тип' => 'Удлинитель',
                    'Посадочный квадрат' => $matches[1].' inch',
                    'Длина' => $matches[2].' mm',
                ],
            ];
        }

        return null;
    }

    private function handle(string $sku, string $typeRu, string $typeRo, string $drive, int $length, array $extra = []): array
    {
        return [
            'name_ru' => "{$typeRu} KING TONY {$sku}, привод {$drive} дюйма, {$length} мм",
            'name_ro' => "{$typeRo} KING TONY {$sku}, antrenare {$drive} inch, {$length} mm",
            'description_ru' => "{$typeRu} KING TONY {$sku} имеет привод {$drive} дюйма и длину {$length} мм.",
            'description_ro' => "{$typeRo} KING TONY {$sku} are antrenare de {$drive} inch și lungime de {$length} mm.",
            'attributes' => [
                'Тип' => $typeRu,
                'Посадочный квадрат' => $drive.' inch',
                'Длина' => $length.' mm',
                ...$extra,
            ],
        ];
    }

    private function ratchet(string $sku, int $teeth, string $drive, ?int $length, string $details): array
    {
        $attributes = [
            'Тип' => 'Трещотка',
            'Количество зубцов' => (string) $teeth,
            'Посадочный квадрат' => $drive.' inch',
        ];
        if ($length) {
            $attributes['Длина'] = $length.' mm';
        }

        $details = mb_strtolower($details);
        if (str_contains($details, 'автореверс')) {
            $attributes['Механизм'] = 'Автореверс';
        } elseif (str_contains($details, 'поворотн') || str_contains($details, 'шарнир')) {
            $attributes['Механизм'] = 'Шарнирная головка';
        } elseif (str_contains($details, 'дисков')) {
            $attributes['Механизм'] = 'Дисковый переключатель';
        }
        if (str_contains($details, 'кноп')) {
            $attributes['Фиксация'] = 'Кнопочная фиксация';
        }
        if (str_contains($details, 'резин')) {
            $attributes['Материал рукоятки'] = 'Резина';
        }

        $lengthRu = $length ? ", {$length} мм" : '';
        $lengthRo = $length ? ", {$length} mm" : '';

        return [
            'name_ru' => "Трещотка KING TONY {$sku}, {$teeth} зубцов, привод {$drive} дюйма{$lengthRu}",
            'name_ro' => "Clichet KING TONY {$sku}, {$teeth} dinți, antrenare {$drive} inch{$lengthRo}",
            'description_ru' => "Трещотка KING TONY {$sku} имеет механизм на {$teeth} зубцов и посадочный квадрат {$drive} дюйма".($length ? ", длина инструмента — {$length} мм." : '.'),
            'description_ro' => "Clichetul KING TONY {$sku} are un mecanism cu {$teeth} dinți și pătrat de antrenare de {$drive} inch".($length ? ", iar lungimea sculei este de {$length} mm." : '.'),
            'attributes' => $attributes,
        ];
    }

    private function specialRatchet(string $sku, string $typeRu, string $typeRo, string $drive, ?int $teeth, string $mechanism): array
    {
        $attributes = [
            'Тип' => $typeRu,
            'Посадочный квадрат' => $drive.' inch',
            'Механизм' => $mechanism,
        ];
        if ($teeth) {
            $attributes['Количество зубцов'] = (string) $teeth;
        }
        $teethRu = $teeth ? " с механизмом на {$teeth} зубцов" : '';
        $teethRo = $teeth ? " cu mecanism cu {$teeth} dinți" : '';

        return [
            'name_ru' => "{$typeRu} KING TONY {$sku}, привод {$drive} дюйма".($teeth ? ", {$teeth} зубцов" : ''),
            'name_ro' => "{$typeRo} KING TONY {$sku}, antrenare {$drive} inch".($teeth ? ", {$teeth} dinți" : ''),
            'description_ru' => "{$typeRu} KING TONY {$sku}{$teethRu} имеет привод {$drive} дюйма.",
            'description_ro' => "{$typeRo} KING TONY {$sku}{$teethRo} are antrenare de {$drive} inch.",
            'attributes' => $attributes,
        ];
    }

    private function joint(string $sku, string $typeRu, string $typeRo, string $drive, bool $withBall = false): array
    {
        $ballRu = $withBall ? ' и исполнение с шаром' : '';
        $ballRo = $withBall ? ' și construcție cu bilă' : '';

        return [
            'name_ru' => "{$typeRu} KING TONY {$sku}, привод {$drive} дюйма".($withBall ? ', с шаром' : ''),
            'name_ro' => "{$typeRo} KING TONY {$sku}, antrenare {$drive} inch".($withBall ? ', cu bilă' : ''),
            'description_ru' => "{$typeRu} KING TONY {$sku} имеет привод {$drive} дюйма{$ballRu} для передачи вращения под углом.",
            'description_ro' => "{$typeRo} KING TONY {$sku} are antrenare de {$drive} inch{$ballRo} pentru transmiterea rotației sub unghi.",
            'attributes' => array_filter([
                'Тип' => $typeRu,
                'Посадочный квадрат' => $drive.' inch',
                'Механизм' => $withBall ? 'С шаром' : 'Карданный шарнир',
            ]),
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
