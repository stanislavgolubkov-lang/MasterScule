<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private string $catalog = 'https://en.hoegert.com/wp-content/uploads/2025/06/CATALOGUE_HT_25_-DE_EN_FR_ES_HR_HU_RO-BG.pdf';

    public function up(): void
    {
        $records = [
            ...$this->impactTools(),
            ...$this->protectiveEquipment(),
        ];
        $brandId = DB::table('brands')->where('name', 'Hoegert')->value('id');
        if (! $brandId) {
            return;
        }

        DB::transaction(function () use ($records, $brandId): void {
            foreach ($records as $sku => $content) {
                $product = DB::table('products')
                    ->where('brand_id', $brandId)
                    ->where('sku', $sku)
                    ->first();
                if ($product) {
                    $this->updateProduct($product, $content);
                }
            }
        });
    }

    private function impactTools(): array
    {
        $records = [];
        foreach (['HT4R033' => 'T70', 'HT4R034' => 'T60', 'HT4R035' => 'T55'] as $sku => $tip) {
            $records[$sku] = [
                'name_ru' => "Ударная торцевая насадка TORX HOEGERT {$sku}, {$tip}, 1/2\", CrMo",
                'name_ro' => "Cheie tubulară de impact TORX HOEGERT {$sku}, {$tip}, 1/2\", CrMo",
                'description_ru' => "Ударная насадка TORX HOEGERT {$sku} с профилем {$tip} и квадратом 1/2\" изготовлена холодной ковкой из хромомолибденовой стали. Оксидированная чёрная поверхность защищает инструмент, конструкция рассчитана на ударные нагрузки.",
                'description_ro' => "Cheia tubulară de impact TORX HOEGERT {$sku}, cu profil {$tip} și pătrat de 1/2\", este forjată la rece din oțel crom-molibden. Suprafața neagră oxidată protejează scula, iar construcția este destinată sarcinilor de impact.",
                'source_url' => $this->catalog,
            ];
        }

        foreach (['HT4R114' => 27, 'HT4R120' => 36, 'HT4R122' => 41] as $sku => $size) {
            $records[$sku] = [
                'name_ru' => "Глубокая ударная головка HOEGERT {$sku}, 3/4\", {$size} мм, CrMo, DIN 3121",
                'name_ro' => "Cheie tubulară lungă de impact HOEGERT {$sku}, 3/4\", {$size} mm, CrMo, DIN 3121",
                'description_ru' => "Глубокая шестигранная ударная головка HOEGERT {$sku} размером {$size} мм имеет присоединительный квадрат 3/4\". Изготовлена ковкой из хромомолибденовой стали CrMo и соответствует стандарту DIN 3121.",
                'description_ro' => "Cheia tubulară lungă de impact HOEGERT {$sku}, de {$size} mm, are profil hexagonal și pătrat de antrenare de 3/4\". Este forjată din oțel crom-molibden CrMo și respectă standardul DIN 3121.",
                'source_url' => $this->catalog,
            ];
        }

        foreach (['HT4R220' => 27, 'HT4R221' => 30, 'HT4R222' => 32, 'HT4R223' => 33, 'HT4R226' => 36] as $sku => $size) {
            $records[$sku] = [
                'name_ru' => "Ударная головка HOEGERT {$sku}, 1\", {$size} мм, CrMo, DIN 3121",
                'name_ro' => "Cheie tubulară de impact HOEGERT {$sku}, 1\", {$size} mm, CrMo, DIN 3121",
                'description_ru' => "Шестигранная ударная головка HOEGERT {$sku} размером {$size} мм имеет присоединительный квадрат 1\". Изготовлена ковкой из хромомолибденовой стали CrMo, рассчитана на работу с ударным инструментом и соответствует DIN 3121.",
                'description_ro' => "Cheia tubulară hexagonală de impact HOEGERT {$sku}, de {$size} mm, are pătrat de antrenare de 1\". Este forjată din oțel crom-molibden CrMo, destinată sculelor de impact și respectă standardul DIN 3121.",
                'source_url' => 'https://en.hoegert.com/wp-content/uploads/2022/12/22-12-08_Regulamin_gwarancji_Hoegert_EN_aktualizacja_gwar_25lat.pdf',
            ];
        }

        $records['HT4R326'] = [
            'name_ru' => 'Ударный переходник HOEGERT HT4R326, F3/4" × M1", CrMo',
            'name_ro' => 'Adaptor de impact HOEGERT HT4R326, F3/4" × M1", CrMo',
            'description_ru' => 'Ударный переходник HOEGERT HT4R326 соединяет инструмент с внутренним квадратом 3/4" и оснастку с наружным квадратом 1". Изготовлен ковкой из хромомолибденовой стали CrMo и предназначен для электрического, аккумуляторного и пневматического ударного инструмента.',
            'description_ro' => 'Adaptorul de impact HOEGERT HT4R326 conectează o sculă cu pătrat interior de 3/4" la un accesoriu cu pătrat exterior de 1". Este forjat din oțel crom-molibden CrMo și destinat sculelor electrice, cu acumulator și pneumatice de impact.',
            'source_url' => 'https://en.hoegert.com/product/impact-adapter-3-4-f-x-1-m-crmo/',
        ];

        return $records;
    }

    private function protectiveEquipment(): array
    {
        $records = [];
        foreach ([
            'HT5K005' => ['прозрачные', 'transparenți', 'https://en.hoegert.com/product/mainz-protective-spectacles-transparent-one-size/'],
            'HT5K006' => ['жёлтые', 'galbeni', 'https://en.hoegert.com/product/mainz-protective-spectacles-yellow-one-size/'],
            'HT5K007' => ['затемнённые', 'fumurii', 'https://en.hoegert.com/product/mainz-protective-spectacles-tinted-one-size/'],
        ] as $sku => [$colorRu, $colorRo, $source]) {
            $records[$sku] = [
                'name_ru' => "Защитные очки MAINZ HOEGERT {$sku}, {$colorRu} линзы, универсальный размер",
                'name_ro' => "Ochelari de protecție MAINZ HOEGERT {$sku}, lentile {$colorRo}, mărime universală",
                'description_ru' => "Защитные очки MAINZ HOEGERT {$sku} с линзами из профилированного поликарбоната защищают глаза спереди и сбоку от частиц с низкой энергией удара. Оптический класс 1, механическая стойкость F; дужки регулируются. Соответствуют EN 166:2001, EN 170 и EN 172.",
                'description_ro' => "Ochelarii de protecție MAINZ HOEGERT {$sku}, cu lentile profilate din policarbonat, protejează ochii frontal și lateral împotriva particulelor cu energie redusă. Clasa optică este 1, rezistența mecanică F, iar brațele sunt reglabile. Respectă EN 166:2001, EN 170 și EN 172.",
                'source_url' => $source,
            ];
        }

        foreach (['HT5K774-S' => 'S', 'HT5K774-M' => 'M', 'HT5K774-L' => 'L', 'HT5K774-XL' => 'XL'] as $sku => $size) {
            $records[$sku] = [
                'name_ru' => "Одноразовые нитриловые перчатки WUSSOW HOEGERT {$sku}, размер {$size}, 100 шт.",
                'name_ro' => "Mănuși de unică folosință din nitril WUSSOW HOEGERT {$sku}, mărimea {$size}, 100 buc.",
                'description_ru' => "Одноразовые перчатки WUSSOW HOEGERT {$sku} размера {$size} изготовлены из нитрила, не содержат пудры и имеют алмазную текстуру для надёжного захвата во влажной и масляной среде. Упаковка — 100 штук. Соответствуют EN ISO 21420, EN ISO 374-1 тип B и EN ISO 374-5; категория СИЗ III.",
                'description_ro' => "Mănușile de unică folosință WUSSOW HOEGERT {$sku}, mărimea {$size}, sunt fabricate din nitril, fără pudră, și au textură diamantată pentru priză sigură în medii umede și uleioase. Ambalajul conține 100 de bucăți. Respectă EN ISO 21420, EN ISO 374-1 tip B și EN ISO 374-5; categoria EIP III.",
                'source_url' => 'https://en.hoegert.com/wp-content/uploads/2024/12/HT5K774_EN.pdf',
            ];
        }

        return $records;
    }

    private function updateProduct(object $product, array $content): void
    {
        $now = now();
        $domain = (string) parse_url($content['source_url'], PHP_URL_HOST);
        $shortRu = Str::limit($content['description_ru'], 240, '');
        $shortRo = Str::limit($content['description_ro'], 240, '');
        $common = [
            'name_ru' => $content['name_ru'], 'name_ro' => $content['name_ro'],
            'short_description_ru' => $shortRu, 'short_description_ro' => $shortRo,
            'description_ru' => $content['description_ru'], 'description_ro' => $content['description_ro'],
            'needs_source_review' => false, 'needs_content_review' => false,
            'needs_translation_review' => false, 'generated_content' => false,
            'updated_at' => $now,
        ];
        DB::table('products')->where('id', $product->id)->update($common + [
            'name' => $content['name_ru'], 'short_description' => $shortRu,
            'description' => $content['description_ru'],
            'meta_description' => Str::limit($content['description_ru'], 150, ''),
            'source_url' => $content['source_url'], 'source_domain' => $domain,
            'source_type' => 'official_manufacturer', 'parser_confidence' => 100,
            'fallback_source_used' => false, 'source_reviewed_at' => $now,
        ]);
        $parser = $common + [
            'found_title' => $content['name_ru'], 'found_description' => $content['description_ru'],
            'official_source_url' => $content['source_url'], 'official_source_domain' => $domain,
            'official_source_confidence' => 100, 'fallback_source_url' => null,
            'fallback_source_domain' => null, 'fallback_source_used' => false,
            'source_match_confidence' => 100, 'content_source_type' => 'official_source',
            'source_reviewed_at' => $now,
        ];
        $query = DB::table('product_parser_items');
        $product->source_parser_item_id
            ? $query->where('id', $product->source_parser_item_id)->update($parser)
            : $query->where('sku', $product->sku)->update($parser);
    }

    public function down(): void
    {
        // Verified exact-SKU bilingual content is intentionally retained.
    }
};
