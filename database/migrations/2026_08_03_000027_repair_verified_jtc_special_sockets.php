<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $records = [
            'JTC-1338' => [
                'name_ru' => 'Специальная головка для верхней гайки амортизатора Mercedes-Benz W220 JTC-1338',
                'name_ro' => 'Cap tubular special pentru piulița amortizorului Mercedes-Benz W220 JTC-1338',
                'description_ru' => 'Специальная головка JTC-1338 применяется для снятия и установки верхней гайки крепления амортизатора на автомобилях Mercedes-Benz W220.',
                'description_ro' => 'Capul tubular special JTC-1338 este destinat demontării și montării piuliței superioare de fixare a amortizorului la automobilele Mercedes-Benz W220.',
                'source_url' => 'https://tristool.md/ru/products/425/4842',
            ],
            'JTC-1339' => [
                'name_ru' => 'Специальная головка для верхней гайки амортизатора Mercedes-Benz W203 JTC-1339',
                'name_ro' => 'Cap tubular special pentru piulița amortizorului Mercedes-Benz W203 JTC-1339',
                'description_ru' => 'Специальная головка JTC-1339 применяется для снятия и установки верхней гайки крепления амортизатора на автомобилях Mercedes-Benz W203.',
                'description_ro' => 'Capul tubular special JTC-1339 este destinat demontării și montării piuliței superioare de fixare a amortizorului la automobilele Mercedes-Benz W203.',
                'source_url' => 'https://tristool.md/ru/products/425/4843',
            ],
            'JTC-1415' => [
                'name_ru' => 'Головка 3/4″ для шаровых опор Mercedes-Benz ML/R-Class JTC-1415',
                'name_ro' => 'Cap tubular 3/4″ pentru articulații Mercedes-Benz ML/R-Class JTC-1415',
                'description_ru' => 'Головка JTC-1415 с приводом 3/4″ предназначена для демонтажа стопорных колец шаровых соединений Mercedes-Benz. Применяется на передних и задних осях моделей W163 и W164 ML-Class, а также W251 R-Class; момент затяжки стопорного кольца — 300 Н·м.',
                'description_ro' => 'Capul tubular JTC-1415 cu antrenare de 3/4″ este destinat demontării inelelor de fixare ale articulațiilor Mercedes-Benz. Se utilizează pe punțile față și spate ale modelelor W163 și W164 ML-Class, precum și W251 R-Class; cuplul inelului de fixare este de 300 Nm.',
                'source_url' => 'https://tristool.md/ru/products/425/4849',
            ],
            'JTC-1615' => [
                'name_ru' => 'Четырёхзубая головка для фланца редуктора Mercedes-Benz JTC-1615',
                'name_ro' => 'Cap tubular cu patru dinți pentru flanșa diferențialului Mercedes-Benz JTC-1615',
                'description_ru' => 'Четырёхзубая головка JTC-1615 используется с приводом 1/2″ для снятия фланца редуктора автомобилей Mercedes-Benz серий W107, W114, W115 и W116.',
                'description_ro' => 'Capul tubular cu patru dinți JTC-1615 se utilizează cu antrenare de 1/2″ pentru demontarea flanșei diferențialului la automobilele Mercedes-Benz din seriile W107, W114, W115 și W116.',
                'source_url' => 'https://tristool.md/ru/products/425/4835',
            ],
            'JTC-4902' => [
                'name_ru' => 'Головка TORX T80H 1/2″ для механизма стеклоочистителя BMW JTC-4902',
                'name_ro' => 'Cap tubular TORX T80H 1/2″ pentru mecanismul ștergătoarelor BMW JTC-4902',
                'description_ru' => 'Головка JTC-4902 размером 1/2″ × T80H и длиной 78 мм предназначена для снятия и установки механизма стеклоочистителей BMW. Используется совместно с JTC-4910 и подходит для серий E12, E21, E23, E24, E28, E30, E31, E32, E34, E36, E38, E39, E46, E52, E53, E60, E65–E67, E85 и других.',
                'description_ro' => 'Capul tubular JTC-4902 de 1/2″ × T80H, cu lungimea de 78 mm, este destinat demontării și montării mecanismului ștergătoarelor BMW. Se utilizează împreună cu JTC-4910 și este compatibil cu seriile E12, E21, E23, E24, E28, E30, E31, E32, E34, E36, E38, E39, E46, E52, E53, E60, E65–E67, E85 și altele.',
                'source_url' => 'https://tristool.md/ru/products/425/4870',
            ],
            'JTC-4924' => [
                'name_ru' => 'Набор головок HEX H7/H9 для замены тормозных колодок JTC-4924',
                'name_ro' => 'Set de capete tubulare HEX H7/H9 pentru înlocuirea plăcuțelor de frână JTC-4924',
                'description_ru' => 'Набор JTC-4924 предназначен для замены тормозных колодок и включает головки 3/8″ × H7 длиной 36 мм и 3/8″ × H9 длиной 92 мм. Подходит для автомобилей Mercedes-Benz, BMW, Volkswagen, Audi, Ford, Mazda и других марок.',
                'description_ro' => 'Setul JTC-4924 este destinat înlocuirii plăcuțelor de frână și include capete de 3/8″ × H7 cu lungimea de 36 mm și 3/8″ × H9 cu lungimea de 92 mm. Este compatibil cu automobile Mercedes-Benz, BMW, Volkswagen, Audi, Ford, Mazda și alte mărci.',
                'source_url' => 'https://tristool.md/ru/products/425/4838',
            ],
            'JTC-6760' => [
                'name_ru' => 'Головка 17 мм × T55 для контроля трансмиссионной жидкости BMW MINI JTC-6760',
                'name_ro' => 'Cap tubular 17 mm × T55 pentru verificarea lichidului de transmisie BMW MINI JTC-6760',
                'description_ru' => 'Специальная головка JTC-6760 размером 17 мм × T55 предназначена для установки и снятия заливной пробки при проверке, замене или доливке трансмиссионной жидкости на автомобилях BMW MINI. Низкопрофильная конструкция удобна для работы в ограниченном пространстве.',
                'description_ro' => 'Capul tubular special JTC-6760 de 17 mm × T55 este destinat montării și demontării bușonului de umplere la verificarea, înlocuirea sau completarea lichidului de transmisie la automobilele BMW MINI. Profilul redus permite lucrul în spații înguste.',
                'source_url' => 'https://tristool.md/ru/products/425/6755',
            ],
            'JTC-6845' => [
                'name_ru' => 'Головка 1/2″ × 22 мм для регулятора VANOS BMW JTC-6845',
                'name_ro' => 'Cap tubular 1/2″ × 22 mm pentru regulatorul VANOS BMW JTC-6845',
                'description_ru' => 'Специальная 16-гранная головка JTC-6845 размером 1/2″ × 22 мм предназначена для установки и снятия регулятора VANOS на двигателях BMW B38, B48 и B58. Оригинальный номер инструмента BMW: 2450487.',
                'description_ro' => 'Capul tubular special cu 16 muchii JTC-6845, de 1/2″ × 22 mm, este destinat montării și demontării regulatorului VANOS la motoarele BMW B38, B48 și B58. Numărul original al sculei BMW este 2450487.',
                'source_url' => 'https://tristool.md/ru/products/425/6804',
            ],
            'JTC-6885' => [
                'name_ru' => 'Головка 27 мм 3PT для регулировочного винта рулевой рейки JTC-6885',
                'name_ro' => 'Cap tubular 27 mm 3PT pentru șurubul de reglare al casetei de direcție JTC-6885',
                'description_ru' => 'Головка JTC-6885 размером 27 мм с профилем 3PT разработана для снятия и установки регулировочного винта рулевой рейки с электроусилителем. Применяется на BMW F20–F23, F25, F26, F30–F36, F48, F49 и Mercedes-Benz W205, W222, V222, X222.',
                'description_ro' => 'Capul tubular JTC-6885 de 27 mm, cu profil 3PT, este conceput pentru demontarea și montarea șurubului de reglare al casetei de direcție asistate electric. Se utilizează la BMW F20–F23, F25, F26, F30–F36, F48, F49 și Mercedes-Benz W205, W222, V222, X222.',
                'source_url' => 'https://tristool.md/ru/products/425/8316',
            ],
            'JTC-6892' => [
                'name_ru' => 'Низкопрофильная головка E12 1/4″ для стеклоподъёмника BMW JTC-6892',
                'name_ro' => 'Cap tubular cu profil redus E12 1/4″ pentru macara geam BMW JTC-6892',
                'description_ru' => 'Низкопрофильная головка JTC-6892 размером 1/4″ × E12 предназначена для крепежа, недоступного стандартным головкам E-TORX. Применяется при обслуживании стеклоподъёмников BMW 5-й и 7-й серий.',
                'description_ro' => 'Capul tubular cu profil redus JTC-6892, de 1/4″ × E12, este destinat elementelor de fixare inaccesibile capetelor E-TORX standard. Se utilizează la întreținerea mecanismelor de ridicare a geamurilor BMW Seria 5 și Seria 7.',
                'source_url' => 'https://tristool.md/ru/products/425/6851',
            ],
        ];

        $brandId = DB::table('brands')->where('name', 'JTC')->value('id');
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

    private function updateProduct(object $product, array $content): void
    {
        $now = now();
        $domain = (string) parse_url($content['source_url'], PHP_URL_HOST);
        $shortRu = Str::limit($content['description_ru'], 240, '');
        $shortRo = Str::limit($content['description_ro'], 240, '');
        $common = [
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description_ru' => $shortRu,
            'short_description_ro' => $shortRo,
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'needs_source_review' => false,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ];

        DB::table('products')->where('id', $product->id)->update($common + [
            'name' => $content['name_ru'],
            'short_description' => $shortRu,
            'description' => $content['description_ru'],
            'meta_description' => Str::limit($content['description_ru'], 150, ''),
            'source_url' => $content['source_url'],
            'source_domain' => $domain,
            'source_type' => 'verified_primary_catalog',
            'parser_confidence' => 98,
            'fallback_source_used' => false,
            'source_reviewed_at' => $now,
        ]);

        $parser = $common + [
            'found_title' => $content['name_ru'],
            'found_description' => $content['description_ru'],
            'official_source_url' => null,
            'official_source_domain' => null,
            'official_source_confidence' => null,
            'fallback_source_url' => $content['source_url'],
            'fallback_source_domain' => $domain,
            'fallback_source_used' => false,
            'source_match_confidence' => 98,
            'content_source_type' => 'tristools_primary',
            'translation_source_type' => 'reviewed_bilingual_content',
            'source_reviewed_at' => $now,
            'translation_reviewed_at' => $now,
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
