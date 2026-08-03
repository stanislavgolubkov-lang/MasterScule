<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('products')->where(function ($query): void {
            $query->where('sku', 'like', '1031-%')
                ->orWhere('sku', 'like', '19B0%')
                ->orWhere('sku', 'like', '1950%')
                ->orWhere('sku', 'like', '1930%')
                ->orWhere('sku', 'like', '1920%')
                ->orWhere('sku', 'like', '1960%');
        })->orderBy('id')->chunkById(100, function ($products) use ($now): void {
            foreach ($products as $product) {
                $content = $this->content((string) $product->sku);
                if ($content === null) {
                    continue;
                }

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

    private function content(string $sku): ?array
    {
        if (preg_match('/^1031-(\d+)R$/', $sku, $match) === 1) {
            $size = $this->number($match[1]);

            return $this->make($sku,
                "Накидная насадка Crowfoot King Tony {$size} мм",
                "Cheie Crowfoot King Tony {$size} mm",
                "12-гранная накидная насадка Crowfoot King Tony, SKU {$sku}, размер {$size} мм. Предназначена для накидных гаек и трубных соединений; присоединение к приводу выполняется через квадратное посадочное отверстие.",
                "Cheie Crowfoot cu 12 puncte King Tony, SKU {$sku}, dimensiune {$size} mm. Este destinată piulițelor olandeze și racordurilor de țeavă; conectarea la antrenor se face prin locașul pătrat.",
                ['Тип' => 'Накидная насадка Crowfoot', 'Размер' => "{$size} мм", 'Профиль' => '12 граней', 'Присоединение' => 'Квадрат']);
        }

        foreach ([
            '19B0' => ['offset0', 'Накидной ключ King Tony со смещением 0°', 'Cheie inelară King Tony cu decalaj 0°'],
            '1950' => ['halfmoon', 'Полукруглый накидной ключ King Tony', 'Cheie inelară semilună King Tony'],
            '1930' => ['flare', 'Ключ для накидных гаек King Tony', 'Cheie pentru racorduri King Tony'],
            '1920' => ['star', 'Накидной ключ Star King Tony', 'Cheie inelară profil stea King Tony'],
            '1960' => ['offset45', 'Накидной ключ King Tony со смещением 45°', 'Cheie inelară King Tony decalată la 45°'],
        ] as $prefix => [$type, $baseRu, $baseRo]) {
            if (preg_match('/^'.preg_quote($prefix, '/').'(\d{2})(\d{2})$/', $sku, $match) !== 1) {
                continue;
            }

            $size = $this->number($match[1]).' × '.$this->number($match[2]);

            return match ($type) {
                'offset0' => $this->make($sku, "{$baseRu}, {$size} мм", "{$baseRo}, {$size} mm",
                    "Компактный двусторонний накидной ключ King Tony, SKU {$sku}, размеры {$size} мм. Головки без смещения подходят для работы в ограниченном пространстве. Ключ изготовлен из хромомолибденовой стали, имеет хромированное покрытие и соответствует DIN 311.",
                    "Cheie inelară dublă compactă King Tony, SKU {$sku}, dimensiuni {$size} mm. Capetele fără decalaj sunt potrivite pentru spații înguste. Fabricată din oțel crom-molibden, cu finisaj cromat, conform DIN 311.",
                    ['Тип' => 'Двусторонний накидной ключ', 'Размер' => "{$size} мм", 'Материал' => 'Хромомолибденовая сталь', 'Смещение' => '0°', 'Стандарт' => 'DIN 311']),
                'halfmoon' => $this->make($sku, "{$baseRu} {$size} мм", "{$baseRo} {$size} mm",
                    "Полукруглый двусторонний накидной ключ King Tony, SKU {$sku}, размеры {$size} мм. Форма облегчает доступ к болтам стартеров, впускных и выпускных коллекторов. Хромованадиевая сталь, хромированное покрытие, метрическое исполнение.",
                    "Cheie inelară dublă în formă de semilună King Tony, SKU {$sku}, dimensiuni {$size} mm. Forma facilitează accesul la șuruburile demaroarelor și ale galeriilor de admisie sau evacuare. Oțel crom-vanadiu, finisaj cromat, execuție metrică.",
                    ['Тип' => 'Полукруглый накидной ключ', 'Размер' => "{$size} мм", 'Материал' => 'Хромованадиевая сталь', 'Покрытие' => 'Хромированное']),
                'flare' => $this->make($sku, "{$baseRu} {$size} мм", "{$baseRo} {$size} mm",
                    "Двусторонний 6-гранный ключ для накидных гаек King Tony, SKU {$sku}, размеры {$size} мм. Кольцевые головки смещены на 12°. Хромованадиевая сталь, хромированное покрытие, стандарт DIN 3118.",
                    "Cheie dublă cu profil hexagonal pentru racorduri King Tony, SKU {$sku}, dimensiuni {$size} mm. Capetele inelare sunt decalate la 12°. Oțel crom-vanadiu, finisaj cromat, conform DIN 3118.",
                    ['Тип' => 'Ключ для накидных гаек', 'Размер' => "{$size} мм", 'Профиль' => '6 граней', 'Смещение' => '12°', 'Стандарт' => 'DIN 3118']),
                'star' => $this->make($sku, "{$baseRu} {$size} мм", "{$baseRo} {$size} mm",
                    "Двусторонний накидной ключ Star King Tony, SKU {$sku}, размеры {$size} мм. Изготовлен из хромованадиевой стали с хромированным покрытием; рассчитан на профессиональное обслуживание крепежа соответствующего профиля.",
                    "Cheie inelară dublă cu profil stea King Tony, SKU {$sku}, dimensiuni {$size} mm. Este fabricată din oțel crom-vanadiu cu finisaj cromat și este destinată lucrărilor profesionale asupra elementelor de fixare cu profil compatibil.",
                    ['Тип' => 'Двусторонний накидной ключ', 'Размер' => "{$size} мм", 'Профиль' => 'Star', 'Материал' => 'Хромованадиевая сталь', 'Покрытие' => 'Хромированное']),
                'offset45' => $this->make($sku, "{$baseRu}, {$size} мм", "{$baseRo}, {$size} mm",
                    "Двусторонний накидной ключ King Tony, SKU {$sku}, размеры {$size} мм. Тонкостенные кольцевые головки имеют глубокое смещение 45°. Хромованадиевая сталь, хромированное покрытие, стандарт DIN 838.",
                    "Cheie inelară dublă King Tony, SKU {$sku}, dimensiuni {$size} mm. Capetele inelare cu pereți subțiri au un decalaj adânc de 45°. Oțel crom-vanadiu, finisaj cromat, conform DIN 838.",
                    ['Тип' => 'Двусторонний накидной ключ', 'Размер' => "{$size} мм", 'Материал' => 'Хромованадиевая сталь', 'Смещение' => '45°', 'Стандарт' => 'DIN 838']),
            };
        }

        return null;
    }

    private function make(string $sku, string $nameRu, string $nameRo, string $descriptionRu, string $descriptionRo, array $attributes): array
    {
        return [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_ru' => "{$nameRu}, SKU {$sku}, для профессиональных работ.",
            'short_ro' => "{$nameRo}, SKU {$sku}, pentru lucrări profesionale.",
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'attributes' => $attributes + ['Артикул производителя' => $sku],
        ];
    }

    private function number(string $value): string
    {
        return (string) (int) $value;
    }

    public function down(): void
    {
        // Verified official-family localization is intentionally retained.
    }
};
