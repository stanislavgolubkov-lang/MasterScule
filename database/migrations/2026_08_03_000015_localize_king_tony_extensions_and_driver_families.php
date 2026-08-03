<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $prefixes = ['8251-', '4251-', '4221-', '2221-', '6251-', '3221-', '2293-', '1450-', '1453-', '10F0-', '3641-', '3611-'];

        DB::table('products')->where(function ($query) use ($prefixes): void {
            foreach ($prefixes as $prefix) {
                $query->orWhere('sku', 'like', $prefix.'%');
            }
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
                    'translation_source_type' => 'verified_manual_translation',
                    'translation_reviewed_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    private function content(string $sku): ?array
    {
        $extensionDrives = [
            '8251-' => '1″', '4251-' => '1/2″', '4221-' => '1/2″', '2221-' => '1/4″',
            '6251-' => '3/4″', '3221-' => '3/8″', '2293-' => '1/4″',
        ];
        foreach ($extensionDrives as $prefix => $drive) {
            if (! str_starts_with($sku, $prefix)) {
                continue;
            }
            $length = (string) (int) preg_replace('/\D/', '', substr($sku, strlen($prefix)));
            $ball = $prefix === '2293-';
            $typeRu = $ball ? 'Удлинитель с шаровым окончанием' : 'Удлинитель';
            $typeRo = $ball ? 'Prelungitor cu capăt sferic' : 'Prelungitor';

            return $this->make($sku,
                "{$typeRu} King Tony {$drive}, {$length}″",
                "{$typeRo} King Tony {$drive}, {$length}″",
                "{$typeRu} King Tony, SKU {$sku}, присоединительный квадрат {$drive}, длина {$length} дюйм. Изготовлен из хромованадиевой стали; поверхность полированная, хромированная и фосфатированная. Предназначен для доступа к крепежу в углублениях и труднодоступных местах.",
                "{$typeRo} King Tony, SKU {$sku}, pătrat de antrenare {$drive}, lungime {$length} inch. Fabricat din oțel crom-vanadiu; suprafață lustruită, cromată și fosfatată. Este destinat accesului la elemente de fixare adâncite sau greu accesibile.",
                ['Тип' => $typeRu, 'Присоединительный квадрат' => $drive, 'Длина' => "{$length}″", 'Материал' => 'Хромованадиевая сталь', 'Покрытие' => 'Полированное, хромированное, фосфатированное']);
        }

        if (str_starts_with($sku, '1450-')) {
            return $this->make($sku,
                "Торцевая отвёртка King Tony {$sku}",
                "Șurubelniță tubulară King Tony {$sku}",
                "Торцевая отвёртка King Tony, SKU {$sku}, с рабочей длиной стержня 125 мм. Стержень изготовлен из хромованадиевой стали и имеет хромированное покрытие; двухкомпонентная рукоятка выполнена из PP и TPR.",
                "Șurubelniță tubulară King Tony, SKU {$sku}, cu lungimea utilă a tijei de 125 mm. Tija este fabricată din oțel crom-vanadiu și are finisaj cromat; mânerul bicomponent este realizat din PP și TPR.",
                ['Тип' => 'Торцевая отвёртка', 'Длина стержня' => '125 мм', 'Материал стержня' => 'Хромованадиевая сталь', 'Материал рукоятки' => 'PP + TPR', 'Покрытие' => 'Хромированное']);
        }

        if (str_starts_with($sku, '1453-')) {
            return $this->make($sku,
                "Гибкая торцевая отвёртка King Tony {$sku}",
                "Șurubelniță tubulară flexibilă King Tony {$sku}",
                "Гибкая торцевая отвёртка King Tony, SKU {$sku}. Стержень из хромованадиевой стали с хромированным покрытием позволяет работать с крепежом при ограниченном прямом доступе; рукоятка изготовлена из PP и TPR.",
                "Șurubelniță tubulară flexibilă King Tony, SKU {$sku}. Tija din oțel crom-vanadiu cu finisaj cromat permite lucrul la elemente de fixare cu acces direct limitat; mânerul este realizat din PP și TPR.",
                ['Тип' => 'Гибкая торцевая отвёртка', 'Материал стержня' => 'Хромованадиевая сталь', 'Материал рукоятки' => 'PP + TPR', 'Покрытие' => 'Хромированное']);
        }

        if (preg_match('/^10F0-(\d+)P$/', $sku, $match) === 1) {
            $size = (string) (int) $match[1];

            return $this->make($sku,
                "Силовой рожковый ключ King Tony {$size} мм",
                "Cheie fixă de forță King Tony {$size} mm",
                "Односторонний силовой рожковый ключ King Tony, SKU {$sku}, размер {$size} мм. Утолщённая рабочая головка рассчитана на высокий момент затяжки; рукоятка имеет отверстие для подвешивания. Хромованадиевая сталь, фосфатированное покрытие, стандарт DIN 894.",
                "Cheie fixă de forță cu un singur capăt King Tony, SKU {$sku}, dimensiune {$size} mm. Capul de lucru îngroșat este proiectat pentru cupluri mari; mânerul are orificiu de suspendare. Oțel crom-vanadiu, finisaj fosfatat, conform DIN 894.",
                ['Тип' => 'Односторонний силовой рожковый ключ', 'Размер' => "{$size} мм", 'Материал' => 'Хромованадиевая сталь', 'Покрытие' => 'Фосфатированное', 'Стандарт' => 'DIN 894']);
        }

        if (str_starts_with($sku, '3641-')) {
            return $this->make($sku,
                "Регулируемый шарнирный ключ King Tony {$sku}",
                "Cheie cu cârlig reglabilă King Tony {$sku}",
                "Регулируемый шарнирный ключ с крюком King Tony, SKU {$sku}. Изготовлен из хромованадиевой стали; шарнир оснащён пружинной шайбой для плавной и надёжной регулировки.",
                "Cheie cu cârlig reglabilă King Tony, SKU {$sku}. Fabricată din oțel crom-vanadiu; articulația este prevăzută cu o șaibă elastică pentru reglare lină și funcționare sigură.",
                ['Тип' => 'Регулируемый шарнирный ключ с крюком', 'Материал' => 'Хромованадиевая сталь', 'Механизм' => 'Шарнир с пружинной шайбой']);
        }

        if (preg_match('/^3611-(\d+)P$/', $sku, $match) === 1) {
            $length = (string) (int) $match[1];

            return $this->make($sku,
                "Разводной ключ King Tony {$length}″",
                "Cheie reglabilă King Tony {$length}″",
                "Разводной ключ японского типа King Tony, SKU {$sku}, длина {$length} дюйм. Изготовлен из хромованадиевой стали; поверхность полированная, фосфатированная и хромированная. Соответствует DIN 3117 и ANSI/ASME B107.8.",
                "Cheie reglabilă de tip japonez King Tony, SKU {$sku}, lungime {$length} inch. Fabricată din oțel crom-vanadiu; suprafață lustruită, fosfatată și cromată. Conform DIN 3117 și ANSI/ASME B107.8.",
                ['Тип' => 'Разводной ключ японского типа', 'Длина' => "{$length}″", 'Материал' => 'Хромованадиевая сталь', 'Покрытие' => 'Полированное, фосфатированное, хромированное', 'Стандарт' => 'DIN 3117 / ANSI/ASME B107.8']);
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

    public function down(): void
    {
        // Verified family localization is intentionally retained.
    }
};
