<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-gys-safety-equipment-2026-08-03';

    private const CATALOG_URL = 'https://www.toolteam.com/en-GB/downloads/dl/file/id/384/GYS_Katalog_Karrosserie_Schweisstechnik_Schweisstechnik_Schweissgeraet_Schutzgasschweissen.pdf';

    public function up(): void
    {
        $records = $this->records();
        $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');
        $categories = DB::table('categories')
            ->whereIn('slug', collect($records)->pluck('category')->unique())
            ->get()
            ->keyBy('slug');

        DB::transaction(function () use ($records, $products, $categories): void {
            foreach ($records as $sku => $content) {
                $product = $products->get($sku);
                $categoryId = $categories->get($content['category'])?->id;

                if ($product) {
                    $this->updateProduct($product, $content, $categoryId);
                }
            }
        });
    }

    private function records(): array
    {
        return [
            '042827' => $this->record(
                'Закрытые защитные очки GYS 042827 от искр и брызг, с защитой от запотевания',
                'Ochelari de protecție închiși GYS 042827 împotriva scânteilor și stropilor, anti-aburire',
                'Закрытые защитные очки GYS 042827 защищают глаза от искр и брызг при шлифовании и других работах в мастерской. Конструкция соответствует EN 166, а защита от запотевания помогает сохранять обзор во время работы.',
                'Ochelarii de protecție închiși GYS 042827 protejează ochii împotriva scânteilor și stropilor la șlefuire și la alte lucrări în atelier. Construcția respectă EN 166, iar protecția anti-aburire ajută la menținerea vizibilității în timpul lucrului.',
                ['Тип' => 'Закрытые защитные очки от брызг', 'Защита от запотевания' => 'Да', 'Стандарт' => 'EN 166', 'Применение' => 'Искры и брызги', 'Количество предметов' => '1'],
                'ochelari-protectie-fata',
                'https://tristool.md/ru/products/618/7522',
                'exact_sku_distributor'
            ),
            '042865' => $this->record(
                'Лицевой защитный щиток GYS 042865 для шлифования',
                'Vizieră de protecție facială GYS 042865 pentru șlefuire',
                'Лицевой щиток GYS 042865 предназначен для защиты лица от частиц и брызг при шлифовании. Прозрачный экран обеспечивает обзор рабочей зоны; средство индивидуальной защиты соответствует требованиям EN 166.',
                'Viziera facială GYS 042865 este destinată protejării feței împotriva particulelor și stropilor în timpul șlefuirii. Ecranul transparent păstrează vizibilitatea zonei de lucru, iar echipamentul individual de protecție respectă EN 166.',
                ['Тип' => 'Лицевой защитный щиток', 'Цвет' => 'Прозрачный', 'Стандарт' => 'EN 166', 'Применение' => 'Шлифование и защита от брызг', 'Количество предметов' => '1'],
                'ochelari-protectie-fata',
                'https://www.autodistribution.fr/accessoires-auto/protection-hygiene-securite/vetements-cuir-soudage-sablage/GYS/042865',
                'exact_sku_distributor'
            ),
            '042698' => $this->record(
                'Внутренние защитные экраны GYS 042698, 108 × 51 мм, 20 штук',
                'Ecrane interioare de protecție GYS 042698, 108 × 51 mm, 20 bucăți',
                'GYS 042698 — комплект из 20 прозрачных внутренних защитных экранов размером 108 × 51 мм для совместимых сварочных масок MASTER, VISION 11, WARLORD и FLIP FLAP. Экраны защищают светофильтр маски от загрязнений и повреждений.',
                'GYS 042698 este un set de 20 de ecrane interioare transparente, cu dimensiunea de 108 × 51 mm, pentru măștile de sudură compatibile MASTER, VISION 11, WARLORD și FLIP FLAP. Ecranele protejează filtrul măștii împotriva murdăriei și deteriorării.',
                ['Тип' => 'Внутреннее защитное стекло сварочной маски', 'Размер' => '108 × 51 mm', 'Совместимость' => 'MASTER / VISION 11 / WARLORD / FLIP FLAP', 'Количество предметов' => '20', 'Применение' => 'Защита светофильтра сварочной маски'],
                'echipament-protectie',
                'https://www.ach-shop.com/gys-20-innenscheiben-108x51-mm-master-vision-11-warlord-flip-fla-042698',
                'exact_sku_distributor',
                dimensions: '108 × 51 mm'
            ),
            '042728' => $this->record(
                'Внешние защитные экраны GYS 042728, 110 × 90 мм, 20 штук',
                'Ecrane exterioare de protecție GYS 042728, 110 × 90 mm, 20 bucăți',
                'GYS 042728 — комплект из 20 внешних защитных экранов размером 110 × 90 мм для сварочных масок MASTER LCD, VISION 11 и совместимых моделей. Экраны устанавливаются перед светофильтром и защищают его от брызг, царапин и загрязнений.',
                'GYS 042728 este un set de 20 de ecrane exterioare de protecție, cu dimensiunea de 110 × 90 mm, pentru măștile de sudură MASTER LCD, VISION 11 și modelele compatibile. Ecranele se montează în fața filtrului și îl protejează împotriva stropilor, zgârieturilor și murdăriei.',
                ['Тип' => 'Внешнее защитное стекло сварочной маски', 'Размер' => '110 × 90 mm', 'Совместимость' => 'MASTER LCD / VISION 11', 'Количество предметов' => '20', 'Применение' => 'Защита светофильтра сварочной маски'],
                'echipament-protectie',
                dimensions: '110 × 90 mm'
            ),
            '043336' => $this->record(
                'Сварочные стёкла GYS 043336 для Magic Zip, DIN 11, 90 × 110 мм, 20 штук',
                'Geamuri de sudură GYS 043336 pentru Magic Zip, DIN 11, 90 × 110 mm, 20 bucăți',
                'GYS 043336 — комплект из 20 затемнённых сварочных стёкол DIN 11 размером 90 × 110 мм для защитного щитка Magic Zip старого исполнения. Стёкла предназначены для замены изношенного защитного элемента.',
                'GYS 043336 este un set de 20 de geamuri de sudură întunecate DIN 11, cu dimensiunea de 90 × 110 mm, pentru viziera Magic Zip de generație veche. Geamurile sunt destinate înlocuirii elementului de protecție uzat.',
                ['Тип' => 'Затемнённое сварочное стекло', 'Степень затемнения' => 'DIN 11', 'Размер' => '90 × 110 mm', 'Совместимость' => 'Magic Zip (old)', 'Количество предметов' => '20'],
                'echipament-protectie',
                'https://www.ach-shop.com/gys-20-innenschutzglaeser-nr.11-90x110-mm-magic-zip-old-043336',
                'exact_sku_distributor',
                dimensions: '90 × 110 mm'
            ),
            '045224' => $this->record(
                'Огнестойкий защитный капюшон сварщика GYS 045224, размер XL',
                'Cagulă ignifugă de protecție pentru sudor GYS 045224, mărimea XL',
                'Защитный капюшон сварщика GYS 045224 изготовлен из огнестойкого хлопка плотностью 305 г/м² и закрывает голову, уши и шею. Размер XL регулируется застёжкой; изделие соответствует EN ISO 11611:2015, класс 1.',
                'Cagula de protecție pentru sudor GYS 045224 este fabricată din bumbac ignifug cu densitatea de 305 g/m² și acoperă capul, urechile și gâtul. Mărimea XL se reglează cu închidere, iar produsul respectă EN ISO 11611:2015, clasa 1.',
                ['Тип' => 'Защитный капюшон сварщика', 'Материал' => 'Огнестойкий хлопок', 'Плотность материала' => '305 g/m²', 'Размер' => 'XL', 'Стандарт' => 'EN ISO 11611:2015, class 1', 'Применение' => 'Защита головы, ушей и шеи при сварке'],
                'echipament-protectie',
                'https://www.comptoirdespros.com/media/MU_Gys_045224.pdf',
                'manufacturer_manual'
            ),
            '050495' => $this->record(
                'Термозащитное покрывало GYS 050495 PROTEC 550, 2 × 2 м',
                'Pătură termoprotectoare GYS 050495 PROTEC 550, 2 × 2 m',
                'Термозащитное покрывало GYS 050495 PROTEC 550 размером 2 × 2 м защищает кузов, сиденья и рабочую зону от искр, брызг и нагрева до 550 °C. Полотно плотностью 580 г/м² изготовлено из стеклоткани с полиуретановым покрытием, не содержит асбеста и имеет класс огнестойкости M0.',
                'Pătura termoprotectoare GYS 050495 PROTEC 550, cu dimensiunea de 2 × 2 m, protejează caroseria, scaunele și zona de lucru împotriva scânteilor, stropilor și temperaturilor de până la 550 °C. Materialul de 580 g/m² este o țesătură din fibră de sticlă cu acoperire din poliuretan, fără azbest, cu clasa de rezistență la foc M0.',
                ['Тип' => 'Термозащитное покрывало', 'Размер' => '2 × 2 m', 'Максимальная температура' => '550 °C', 'Плотность материала' => '580 g/m²', 'Материал' => 'Стеклоткань с полиуретановым покрытием', 'Класс огнестойкости' => 'M0', 'Применение' => 'Защита от искр, тепла и сварочных брызг'],
                'echipament-protectie',
                dimensions: '2 × 2 m'
            ),
            '082809' => $this->record(
                'Автоматическая сварочная маска GYS 082809 GYSMATIC AUTO PRO TRUE COLOR',
                'Mască automată de sudură GYS 082809 GYSMATIC AUTO PRO TRUE COLOR',
                'Автоматическая сварочная маска GYS GYSMATIC AUTO PRO TRUE COLOR (артикул 082809) защищает лицо и глаза при MMA, TIG и MIG/MAG сварке. Светофильтр оптического класса 1/1/1/1 имеет светлое состояние DIN 3, диапазоны затемнения DIN 5–9 и 9–13, четыре датчика и время срабатывания 0,08 мс. Размер обзорного окна — 100 × 93 мм; доступны регулировки чувствительности, задержки, затемнения и режим шлифования. Питание — солнечная батарея и две батарейки CR2032, масса — 540 г.',
                'Masca automată de sudură GYS GYSMATIC AUTO PRO TRUE COLOR (cod 082809) protejează fața și ochii în timpul sudării MMA, TIG și MIG/MAG. Filtrul cu clasa optică 1/1/1/1 are starea luminoasă DIN 3, intervale de întunecare DIN 5–9 și 9–13, patru senzori și un timp de reacție de 0,08 ms. Câmpul vizual măsoară 100 × 93 mm; sunt disponibile reglaje pentru sensibilitate, întârziere, nuanță și modul de șlefuire. Alimentarea este solară și cu două baterii CR2032, iar greutatea este de 540 g.',
                ['Тип' => 'Автоматическая сварочная маска', 'Оптический класс' => '1/1/1/1', 'Светлое состояние' => 'DIN 3', 'Степень затемнения' => 'DIN 5–9 / 9–13', 'Время срабатывания' => '0.08 ms', 'Время возврата в светлое состояние' => '0.15–0.8 s', 'Размер смотрового окна' => '100 × 93 mm', 'Количество датчиков' => '4', 'Источник питания' => 'Солнечная батарея + 2 × CR2032', 'Режим шлифования' => 'Да', 'Стандарт' => 'EN 175B / EN 166 / EN 379', 'Применение' => 'Сварка MMA / TIG / MIG-MAG', 'Вес' => '540 g'],
                'echipament-protectie',
                'https://www.agrizone.net/ressources/data/FT/GYS/82809.pdf',
                'manufacturer_datasheet',
                weight: '540 g'
            ),
            '042810' => $this->record(
                'Прозрачные защитные очки GYS 042810 от искр и брызг',
                'Ochelari de protecție transparenți GYS 042810 împotriva scânteilor și stropilor',
                'Прозрачные защитные очки GYS 042810 предназначены для защиты глаз от искр, частиц и брызг при шлифовании и других работах в мастерской. Очки соответствуют стандарту EN 166 и не имеют затемнения IR.',
                'Ochelarii de protecție transparenți GYS 042810 sunt destinați protejării ochilor împotriva scânteilor, particulelor și stropilor la șlefuire și la alte lucrări în atelier. Ochelarii respectă standardul EN 166 și nu au filtru IR întunecat.',
                ['Тип' => 'Прозрачные защитные очки', 'Цвет' => 'Прозрачный', 'Степень затемнения' => 'IR 0', 'Стандарт' => 'EN 166', 'Применение' => 'Искры и брызги', 'Количество предметов' => '1'],
                'ochelari-protectie-fata',
                'https://www.autodistribution.fr/accessoires-auto/protection-hygiene-securite/protection-des-yeux-lunettes-oculaire-monture/GYS/042810',
                'exact_sku_distributor'
            ),
            '042858' => $this->record(
                'Газосварочные очки GYS 042858 с откидными стёклами, DIN 5',
                'Ochelari pentru sudare cu gaz GYS 042858 cu lentile rabatabile, DIN 5',
                'Защитные очки GYS 042858 с откидными затемнёнными стёклами DIN 5 предназначены для газовой сварки и резки. Конструкция позволяет поднять тёмные фильтры для осмотра рабочей зоны; средство защиты соответствует EN 166 и EN 169.',
                'Ochelarii de protecție GYS 042858 cu lentile întunecate rabatabile DIN 5 sunt destinați sudării și tăierii cu gaz. Filtrele întunecate pot fi ridicate pentru inspectarea zonei de lucru, iar echipamentul respectă EN 166 și EN 169.',
                ['Тип' => 'Газосварочные очки с откидными стёклами', 'Степень затемнения' => 'DIN 5', 'Стандарт' => 'EN 166 / EN 169', 'Применение' => 'Газовая сварка', 'Количество предметов' => '1'],
                'ochelari-protectie-fata',
                'https://www.agrizone.net/ressources/data/notice/GYS/42858.pdf',
                'manufacturer_manual'
            ),
            '047778' => $this->record(
                'Прозрачные защитные очки GYS 047778 Premium',
                'Ochelari de protecție transparenți GYS 047778 Premium',
                'Защитные очки GYS 047778 Premium оснащены прозрачной поликарбонатной линзой, нейлоновыми дужками с мягкими вставками и дополнительным ремешком. Очки предназначены для защиты от брызг, соответствуют EN 166 и имеют оптический класс 1.',
                'Ochelarii de protecție GYS 047778 Premium au lentilă transparentă din policarbonat, brațe din nailon cu inserții moi și o curea suplimentară. Ochelarii protejează împotriva stropilor, respectă EN 166 și au clasa optică 1.',
                ['Тип' => 'Защитные очки Premium', 'Материал' => 'Поликарбонат', 'Материал дужек' => 'Нейлон / пеноматериал', 'Цвет' => 'Прозрачный', 'Оптический класс' => '1', 'Стандарт' => 'EN 166', 'Применение' => 'Искры и брызги'],
                'ochelari-protectie-fata',
                'https://www.peinturevoiture.fr/2082-lunette-securite-luxe.html',
                'exact_sku_distributor'
            ),
            '047792' => $this->record(
                'Жёлтые защитные очки GYS 047792 Premium, 30 г',
                'Ochelari de protecție galbeni GYS 047792 Premium, 30 g',
                'Защитные очки GYS 047792 Premium с жёлтыми поликарбонатными линзами защищают глаза от брызг и ультрафиолетового излучения. Лёгкая модель массой 30 г соответствует стандарту EN 166.',
                'Ochelarii de protecție GYS 047792 Premium cu lentile galbene din policarbonat protejează ochii împotriva stropilor și radiației ultraviolete. Modelul ușor, de 30 g, respectă standardul EN 166.',
                ['Тип' => 'Защитные очки Premium', 'Материал' => 'Поликарбонат', 'Цвет' => 'Жёлтый', 'Защита от УФ' => 'Да', 'Стандарт' => 'EN 166', 'Применение' => 'Защита глаз от брызг и УФ-излучения', 'Вес' => '30 g'],
                'ochelari-protectie-fata',
                'https://www.color-box.eu/art-1-paire-de-lunettes-de-protection-luxe-jaune-4825.htm',
                'exact_sku_distributor',
                weight: '30 g'
            ),
        ];
    }

    private function record(
        string $nameRu,
        string $nameRo,
        string $descriptionRu,
        string $descriptionRo,
        array $attributes,
        string $category,
        string $referenceUrl = self::CATALOG_URL,
        string $referenceType = 'manufacturer_catalog',
        ?string $weight = null,
        ?string $dimensions = null,
    ): array {
        return compact(
            'nameRu', 'nameRo', 'descriptionRu', 'descriptionRo', 'attributes',
            'category', 'referenceUrl', 'referenceType', 'weight', 'dimensions'
        );
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sourceUrls = $this->appendReferenceUrl($product->parser_source_urls ?? null, $content['referenceUrl']);

        $updates = [
            'name' => $content['nameRu'],
            'name_ru' => $content['nameRu'],
            'name_ro' => $content['nameRo'],
            'short_description' => $content['descriptionRu'],
            'short_description_ru' => $content['descriptionRu'],
            'short_description_ro' => $content['descriptionRo'],
            'description' => $content['descriptionRu'],
            'description_ru' => $content['descriptionRu'],
            'description_ro' => $content['descriptionRo'],
            'attributes' => $attributes,
            'weight' => $content['weight'],
            'dimensions' => $content['dimensions'],
            'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'needs_content_review' => false,
            'generated_content' => false,
            'meta_title' => $content['nameRu'].' | MasterScule.md',
            'meta_description' => mb_substr($content['descriptionRu'], 0, 250),
            'updated_at' => $now,
        ];

        if ($categoryId) {
            $updates['category_id'] = $categoryId;
            $updates['needs_category_review'] = false;
        }

        DB::table('products')->where('id', $product->id)->update($updates);

        if ($categoryId && (int) $product->category_id !== $categoryId) {
            $this->syncCategory($product, $categoryId, $content['category'], $now);
        }

        if (! $product->source_parser_item_id) {
            return;
        }

        $parserItem = DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->first();
        $parserSourceUrls = $this->appendReferenceUrl($parserItem?->source_urls_json, $content['referenceUrl']);

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
            'name_ru' => $content['nameRu'],
            'name_ro' => $content['nameRo'],
            'short_description_ru' => $content['descriptionRu'],
            'short_description_ro' => $content['descriptionRo'],
            'description_ru' => $content['descriptionRu'],
            'description_ro' => $content['descriptionRo'],
            'found_title' => $content['nameRu'],
            'found_description' => $content['descriptionRu'],
            'found_specs_json' => $attributes,
            'source_urls_json' => json_encode($parserSourceUrls, JSON_UNESCAPED_SLASHES),
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => $content['referenceType'],
            'translation_source_type' => 'curated_translation',
            'translation_reviewed_at' => $now,
            'category_id' => $categoryId,
            'detected_category_id' => $categoryId,
            'detected_category_path' => $content['category'],
            'category_confidence_score' => 100,
            'category_detection_method' => $this->mode,
            'needs_category_review' => false,
            'updated_at' => $now,
        ]);

        DB::table('product_parser_sources')->updateOrInsert(
            ['parser_item_id' => $product->source_parser_item_id, 'url' => $content['referenceUrl']],
            [
                'domain' => parse_url($content['referenceUrl'], PHP_URL_HOST),
                'title' => 'GYS reference — '.$product->sku,
                'snippet' => 'Manufacturer publication or exact-SKU product page matched to this GYS record.',
                'source_type' => $content['referenceType'],
                'confidence_score' => 95,
                'raw_data_json' => json_encode(['sku' => $product->sku, 'brand' => 'GYS'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function appendReferenceUrl(?string $json, string $referenceUrl): array
    {
        $urls = json_decode($json ?: '[]', true);

        if (! is_array($urls)) {
            $urls = [];
        }

        $urls[] = $referenceUrl;

        return array_values(array_unique(array_filter($urls)));
    }

    private function syncCategory(object $product, int $categoryId, string $categorySlug, object $now): void
    {
        DB::table('category_product')->where('product_id', $product->id)->delete();
        DB::table('category_product')->insert([
            'product_id' => $product->id,
            'category_id' => $categoryId,
            'is_primary' => true,
            'source' => $this->mode,
            'confidence' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_category_decisions')->insert([
            'product_id' => $product->id,
            'previous_category_id' => $product->category_id,
            'selected_category_id' => $categoryId,
            'taxonomy_version' => 'verified-2026-08-03',
            'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$categoryId),
            'mode' => $this->mode,
            'status' => 'applied',
            'classifier_confidence' => 1,
            'verifier_confidence' => 1,
            'evidence' => json_encode(["Exact GYS SKU {$product->sku} identifies category {$categorySlug}."], JSON_UNESCAPED_UNICODE),
            'alternatives' => json_encode([]),
            'validation_errors' => json_encode([]),
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated exact-SKU content and category decisions are intentionally retained.
    }
};
