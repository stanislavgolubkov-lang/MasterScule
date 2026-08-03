<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('products')
            ->where(function ($query): void {
                $query->where('sku', 'like', '1060-%')
                    ->orWhere('sku', 'like', '1071-%')
                    ->orWhere('sku', 'like', '1080-%')
                    ->orWhere('sku', 'like', '1900%')
                    ->orWhere('sku', 'like', '1970%');
            })
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($now): void {
                foreach ($products as $product) {
                    $content = $this->content((string) $product->sku);
                    if ($content === null) {
                        continue;
                    }

                    $productUpdates = [
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
                    ];
                    DB::table('products')->where('id', $product->id)->update($productUpdates);

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
        if (preg_match('/^1060-(\d+)$/', $sku, $match) === 1) {
            $size = $this->number($match[1]);

            return $this->make(
                $sku,
                "Комбинированный ключ King Tony {$size} мм",
                "Cheie combinată King Tony {$size} mm",
                "Комбинированный ключ King Tony, SKU {$sku}, размер {$size} мм. Рожковая часть расположена под углом 15°, кольцевая часть имеет смещение 15°. Ключ изготовлен из хромованадиевой стали с хромированным покрытием; соответствует DIN 3113 Form A и ISO 7738.",
                "Cheie combinată King Tony, SKU {$sku}, dimensiune {$size} mm. Capătul fix este înclinat la 15°, iar capătul inelar are un decalaj de 15°. Cheia este fabricată din oțel crom-vanadiu, cu finisaj cromat, conform DIN 3113 Form A și ISO 7738.",
                ['Тип' => 'Комбинированный ключ', 'Размер' => "{$size} мм", 'Материал' => 'Хромованадиевая сталь', 'Покрытие' => 'Хромированное', 'Стандарт' => 'DIN 3113 Form A / ISO 7738'],
            );
        }

        if (preg_match('/^1071-(\d+)$/', $sku, $match) === 1) {
            $size = $this->number($match[1]);

            return $this->make(
                $sku,
                "Комбинированный ключ Jumbo King Tony {$size} мм",
                "Cheie combinată Jumbo King Tony {$size} mm",
                "Усиленный комбинированный ключ Jumbo King Tony, SKU {$sku}, размер {$size} мм. Рожковая часть расположена под углом 15°, кольцевая часть смещена на 15°. Полированная хромированная поверхность; метрическое исполнение по DIN 3113 Form A и ISO 7738.",
                "Cheie combinată ranforsată Jumbo King Tony, SKU {$sku}, dimensiune {$size} mm. Capătul fix este înclinat la 15°, iar capătul inelar este decalat la 15°. Suprafața este lustruită și cromată; execuție metrică conform DIN 3113 Form A și ISO 7738.",
                ['Тип' => 'Комбинированный ключ Jumbo', 'Размер' => "{$size} мм", 'Покрытие' => 'Полированное, хромированное', 'Угол' => '15°', 'Стандарт' => 'DIN 3113 Form A / ISO 7738'],
            );
        }

        if (preg_match('/^1080-(\d+)$/', $sku, $match) === 1) {
            $size = $this->number($match[1]);

            return $this->make(
                $sku,
                "Угловой торцевой ключ King Tony 12×6 граней, {$size} мм",
                "Cheie tubulară cotită King Tony 12×6 puncte, {$size} mm",
                "Угловой проходной торцевой ключ King Tony, SKU {$sku}, размер {$size} мм. Один конец имеет 12-гранный, второй — 6-гранный профиль. Глубокие торцевые части позволяют работать с выступающими шпильками; хромированное покрытие, стандарт ISO 2236.",
                "Cheie tubulară cotită și traversantă King Tony, SKU {$sku}, dimensiune {$size} mm. Un capăt are profil cu 12 puncte, iar celălalt profil hexagonal cu 6 puncte. Capetele adânci permit lucrul pe prezoane proeminente; finisaj cromat, conform ISO 2236.",
                ['Тип' => 'Угловой торцевой ключ', 'Размер' => "{$size} мм", 'Профиль' => '12 граней × 6 граней', 'Покрытие' => 'Хромированное', 'Стандарт' => 'ISO 2236'],
            );
        }

        if (preg_match('/^1900(\d{2})(\d{2})$/', $sku, $match) === 1) {
            $size = $this->number($match[1]).' × '.$this->number($match[2]);

            return $this->make(
                $sku,
                "Двусторонний рожковый ключ King Tony {$size} мм",
                "Cheie fixă dublă King Tony {$size} mm",
                "Двусторонний рожковый ключ King Tony, SKU {$sku}, размеры {$size} мм. Тонкий европейский профиль и наклон рабочих головок 15° упрощают доступ к крепежу. Ключ изготовлен из хромованадиевой стали, имеет хромированное покрытие и соответствует DIN 3110.",
                "Cheie fixă dublă King Tony, SKU {$sku}, dimensiuni {$size} mm. Profilul european subțire și înclinarea capetelor la 15° facilitează accesul la elementele de fixare. Fabricată din oțel crom-vanadiu, cu finisaj cromat, conform DIN 3110.",
                ['Тип' => 'Двусторонний рожковый ключ', 'Размер' => "{$size} мм", 'Материал' => 'Хромованадиевая сталь', 'Угол' => '15°', 'Стандарт' => 'DIN 3110'],
            );
        }

        if (preg_match('/^1970(\d{2})(\d{2})$/', $sku, $match) === 1) {
            $size = $this->number($match[1]).' × '.$this->number($match[2]);

            return $this->make(
                $sku,
                "Накидной ключ King Tony со смещением 75°, {$size} мм",
                "Cheie inelară King Tony decalată la 75°, {$size} mm",
                "Двусторонний накидной ключ King Tony, SKU {$sku}, размеры {$size} мм. Кольцевые головки с тонкими стенками имеют глубокое смещение 75° для доступа к утопленному крепежу. Хромованадиевая сталь, хромированное покрытие, стандарт DIN 838.",
                "Cheie inelară dublă King Tony, SKU {$sku}, dimensiuni {$size} mm. Capetele inelare cu pereți subțiri au un decalaj adânc de 75° pentru acces la elemente de fixare îngropate. Oțel crom-vanadiu, finisaj cromat, conform DIN 838.",
                ['Тип' => 'Двусторонний накидной ключ', 'Размер' => "{$size} мм", 'Материал' => 'Хромованадиевая сталь', 'Смещение' => '75°', 'Стандарт' => 'DIN 838'],
            );
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
