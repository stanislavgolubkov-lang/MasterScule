<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-gys-bodyshop-accessories-2026-08-03';

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
            '041318' => $this->record(
                'Защита автомобильной электроники GYS 041318 Anti-Arc, 12 В',
                'Protecție pentru electronica auto GYS 041318 Anti-Arc, 12 V',
                'GYS 041318 Anti-Arc подключается к клеммам 12-вольтовой аккумуляторной батареи и поглощает импульсные перенапряжения при сварке или обслуживании автомобиля. Зелёный индикатор подтверждает активную защиту; красный индикатор и зуммер сообщают, что устройство сработало и требует замены.',
                'GYS 041318 Anti-Arc se conectează la bornele bateriei de 12 V și absoarbe vârfurile de supratensiune în timpul sudării sau intervențiilor asupra vehiculului. Indicatorul verde confirmă protecția activă, iar indicatorul roșu și semnalul sonor arată că dispozitivul a intervenit și trebuie înlocuit.',
                ['Тип' => 'Защита автомобильной электроники от перенапряжения', 'Напряжение' => '12 V', 'Применение' => 'Защита электроники автомобиля при сварке', 'Комплектация' => 'Устройство с кабелями и зажимами'],
                'baterii-incarcatoare',
                'https://lakkspesialisten.no/content/uploads/2026/01/GYS%20041318.pdf',
                'manufacturer_datasheet'
            ),
            '042803' => $this->record(
                'Защитные очки GYS 042803 для газовой сварки, затемнение DIN 5',
                'Ochelari de protecție GYS 042803 pentru sudare cu gaz, nuanță DIN 5',
                'Защитные очки GYS 042803 с затемнением DIN 5 предназначены для газовой сварки и резки, защищая глаза от яркого излучения и искр.',
                'Ochelarii de protecție GYS 042803 cu nuanță DIN 5 sunt destinați sudării și tăierii cu gaz, protejând ochii împotriva radiației luminoase și a scânteilor.',
                ['Тип' => 'Защитные очки для газовой сварки', 'Степень затемнения' => 'DIN 5', 'Применение' => 'Газовая сварка и резка', 'Количество предметов' => '1'],
                'ochelari-protectie-fata'
            ),
            '048232' => $this->record(
                'Самопробивные стальные заклёпки GYS 048232, Ø 3,3 × 3,5 мм, 200 штук',
                'Nituri autoperforante din oțel GYS 048232, Ø 3,3 × 3,5 mm, 200 bucăți',
                'GYS 048232 — комплект из 200 самопробивных стальных заклёпок диаметром 3,3 мм и длиной 3,5 мм для соединения кузовных панелей без предварительного сверления.',
                'GYS 048232 este un set de 200 de nituri autoperforante din oțel, cu diametrul de 3,3 mm și lungimea de 3,5 mm, pentru îmbinarea panourilor de caroserie fără găurire prealabilă.',
                ['Тип' => 'Самопробивные стальные заклёпки', 'Диаметр' => '3.3 mm', 'Длина' => '3.5 mm', 'Материал' => 'Сталь', 'Количество предметов' => '200', 'Применение' => 'Кузовной ремонт'],
                'tinichigerie-si-richtuire'
            ),
            '048249' => $this->record(
                'Самопробивные стальные заклёпки GYS 048249, Ø 3,3 × 4 мм, 200 штук',
                'Nituri autoperforante din oțel GYS 048249, Ø 3,3 × 4 mm, 200 bucăți',
                'GYS 048249 — комплект из 200 самопробивных стальных заклёпок диаметром 3,3 мм и длиной 4 мм для соединения кузовных панелей без предварительного сверления.',
                'GYS 048249 este un set de 200 de nituri autoperforante din oțel, cu diametrul de 3,3 mm și lungimea de 4 mm, pentru îmbinarea panourilor de caroserie fără găurire prealabilă.',
                ['Тип' => 'Самопробивные стальные заклёпки', 'Диаметр' => '3.3 mm', 'Длина' => '4 mm', 'Материал' => 'Сталь', 'Количество предметов' => '200', 'Применение' => 'Кузовной ремонт'],
                'tinichigerie-si-richtuire'
            ),
            '048706' => $this->record(
                'Набор самопробивных заклёпок GYS 048706, Ø 3,3 и 5,3 мм, 300 штук',
                'Set de nituri autoperforante GYS 048706, Ø 3,3 și 5,3 mm, 300 bucăți',
                'GYS 048706 содержит шесть групп по 50 стальных заклёпок: 3,3 × 3,5; 3,3 × 4; 5,3 × 4; 5,3 × 5; 5,3 × 6 и 5,3 × 8 мм. Набор предназначен для кузовных соединений методом самопробивного клепания.',
                'GYS 048706 conține șase grupe a câte 50 de nituri din oțel: 3,3 × 3,5; 3,3 × 4; 5,3 × 4; 5,3 × 5; 5,3 × 6 și 5,3 × 8 mm. Setul este destinat îmbinărilor de caroserie prin nituire autoperforantă.',
                ['Тип' => 'Набор самопробивных стальных заклёпок', 'Размер' => '3.3 × 3.5 / 3.3 × 4 / 5.3 × 4 / 5.3 × 5 / 5.3 × 6 / 5.3 × 8 mm', 'Материал' => 'Сталь', 'Количество предметов' => '300', 'Комплектация' => '6 размеров × 50 штук'],
                'tinichigerie-si-richtuire'
            ),
            '049468' => $this->record(
                'Углеродные электроды GYS 049468, Ø 10 × 300 мм, 5 штук',
                'Electrozi din carbon GYS 049468, Ø 10 × 300 mm, 5 bucăți',
                'GYS 049468 — комплект из пяти углеродных электродов диаметром 10 мм и длиной 300 мм для локального нагрева и усадки листового металла при кузовном ремонте.',
                'GYS 049468 este un set de cinci electrozi din carbon cu diametrul de 10 mm și lungimea de 300 mm, pentru încălzirea locală și contractarea tablei în reparațiile de caroserie.',
                ['Тип' => 'Углеродные электроды для нагрева металла', 'Диаметр' => '10 mm', 'Длина' => '300 mm', 'Количество предметов' => '5', 'Применение' => 'Нагрев и усадка листового металла'],
                'tinichigerie-si-richtuire'
            ),
            '049987' => $this->record(
                'Электродные колпачки GYS 049987 типа A, Ø 13 мм, 6 штук',
                'Capace pentru electrozi GYS 049987 tip A, Ø 13 mm, 6 bucăți',
                'GYS 049987 — комплект из шести сменных электродных колпачков типа A диаметром 13 мм для совместимых клещей точечной сварки GYS.',
                'GYS 049987 este un set de șase capace de electrod tip A, cu diametrul de 13 mm, pentru cleștii compatibili de sudare prin puncte GYS.',
                ['Тип' => 'Электродные колпачки', 'Исполнение' => 'Тип A', 'Диаметр' => '13 mm', 'Количество предметов' => '6', 'Применение' => 'Точечная сварка'],
                'tinichigerie-si-richtuire'
            ),
            '050020' => $this->record(
                'Набор расходников GYS 050020 ALU BOX STANDARD, Ø 4 мм',
                'Set de consumabile GYS 050020 ALU BOX STANDARD, Ø 4 mm',
                'GYS 050020 ALU BOX STANDARD включает 200 винтов AlMg3 M4 × 12 мм, 200 винтов AlSi M4 × 12 мм, пять вытяжных колец M4 и одну тягу длиной 180 мм в кейсе. Комплект предназначен для ремонта алюминиевых кузовных панелей.',
                'GYS 050020 ALU BOX STANDARD include 200 de șuruburi AlMg3 M4 × 12 mm, 200 de șuruburi AlSi M4 × 12 mm, cinci inele de tragere M4 și o tijă de 180 mm într-o cutie. Setul este destinat reparării panourilor de caroserie din aluminiu.',
                ['Тип' => 'Набор расходников для алюминиевого споттера', 'Диаметр' => '4 mm', 'Резьба' => 'M4', 'Материал' => 'AlMg3 / AlSi', 'Количество предметов' => '406', 'Комплектация' => '050273 × 200 / 050280 × 200 / 050297 × 5 / 180 mm × 1'],
                'tinichigerie-si-richtuire'
            ),
            '050822' => $this->record(
                'Электроды GYS 050822 для Manuspot, 3 штуки',
                'Electrozi GYS 050822 pentru Manuspot, 3 bucăți',
                'GYS 050822 — комплект из трёх сменных электродов для рукояток Manuspot, применяемых при локальном вытягивании кузовных вмятин.',
                'GYS 050822 este un set de trei electrozi de schimb pentru mânerele Manuspot, utilizați la tragerea locală a deformărilor caroseriei.',
                ['Тип' => 'Электроды для Manuspot', 'Совместимость' => 'Manuspot', 'Количество предметов' => '3', 'Применение' => 'Вытягивание вмятин'],
                'tinichigerie-si-richtuire',
                'https://lakkspesialisten.no/content/uploads/2026/01/GYS%20035058_1.pdf',
                'manufacturer_manual'
            ),
            '050839' => $this->record(
                'Медные вытяжные электроды GYS 050839, 5 штук',
                'Electrozi de tragere din cupru GYS 050839, 5 bucăți',
                'GYS 050839 — комплект из пяти сменных медных электродов для локального вытягивания вмятин кузовным споттером.',
                'GYS 050839 este un set de cinci electrozi de schimb din cupru pentru tragerea locală a deformărilor cu un spotter de caroserie.',
                ['Тип' => 'Медные электроды для вытягивания', 'Материал' => 'Медь', 'Количество предметов' => '5', 'Применение' => 'Вытягивание вмятин'],
                'tinichigerie-si-richtuire'
            ),
            '052291' => $this->record(
                'Магниты GYS 052291 для термозащитного полотна, Ø 50 мм, 2 штуки',
                'Magneți GYS 052291 pentru pătură termoprotectoare, Ø 50 mm, 2 bucăți',
                'GYS 052291 — комплект из двух магнитов диаметром 50 мм для фиксации термозащитного полотна на кузовной панели во время сварочных работ.',
                'GYS 052291 este un set de doi magneți cu diametrul de 50 mm pentru fixarea păturii termoprotectoare pe panoul caroseriei în timpul sudării.',
                ['Тип' => 'Магниты для защитного полотна', 'Диаметр' => '50 mm', 'Количество предметов' => '2', 'Применение' => 'Фиксация термозащитного полотна'],
                'tinichigerie-si-richtuire'
            ),
            '052413' => $this->record(
                'Набор скоб GYS 052413 для ремонта пластика, Ø 0,7 мм, 250 штук',
                'Set de capse GYS 052413 pentru repararea plasticului, Ø 0,7 mm, 250 bucăți',
                'GYS 052413 содержит по 50 нагреваемых скоб пяти форм — V, U, N, W и M — диаметром 0,7 мм. Набор предназначен для армирования трещин в пластиковых бамперах и других термопластичных кузовных деталях.',
                'GYS 052413 conține câte 50 de capse încălzite din cinci forme — V, U, N, W și M — cu diametrul de 0,7 mm. Setul este destinat armării fisurilor din barele de protecție din plastic și alte piese termoplastice ale caroseriei.',
                ['Тип' => 'Набор скоб для ремонта пластика', 'Диаметр' => '0.7 mm', 'Форма' => 'V / U / N / W / M', 'Количество предметов' => '250', 'Комплектация' => '5 форм × 50 штук', 'Применение' => 'Термический ремонт пластика'],
                'tinichigerie-si-richtuire',
                'https://optimotive.co.uk/pdfs/datasheets/052925.pdf',
                'manufacturer_datasheet'
            ),
            '059429' => $this->record(
                'Набор алюминиевых вытяжных колец GYS 059429, 1,5 мм, 210 штук',
                'Set de inele de tragere din aluminiu GYS 059429, 1,5 mm, 210 bucăți',
                'GYS 059429 — набор алюминиевых вытяжных колец толщиной 1,5 мм трёх сплавов: Al-Special, AlMg3 и AlMgSi. Для каждого сплава предусмотрено 50 прямых и 20 скрученных колец, всего 210 штук.',
                'GYS 059429 este un set de inele de tragere din aluminiu cu grosimea de 1,5 mm, din trei aliaje: Al-Special, AlMg3 și AlMgSi. Pentru fiecare aliaj sunt incluse 50 de inele drepte și 20 răsucite, în total 210 bucăți.',
                ['Тип' => 'Набор алюминиевых вытяжных колец', 'Толщина' => '1.5 mm', 'Материал' => 'Al-Special / AlMg3 / AlMgSi', 'Исполнение' => 'Прямые / скрученные', 'Количество предметов' => '210', 'Применение' => 'Ремонт алюминиевых панелей'],
                'tinichigerie-si-richtuire'
            ),
            '059610' => $this->record(
                'Держатель алюминиевых вытяжных колец GYS 059610',
                'Suport pentru inele de tragere din aluminiu GYS 059610',
                'Держатель GYS 059610 предназначен для установки и приварки алюминиевых вытяжных колец при работе с совместимыми системами GYSPOT ARC PULL.',
                'Suportul GYS 059610 este destinat montării și sudării inelelor de tragere din aluminiu cu sistemele compatibile GYSPOT ARC PULL.',
                ['Тип' => 'Держатель вытяжных колец', 'Совместимость' => 'GYSPOT ARC PULL', 'Количество предметов' => '1', 'Применение' => 'Алюминиевые вытяжные кольца'],
                'tinichigerie-si-richtuire'
            ),
            '059825' => $this->record(
                'Мобильная стойка GYS 059825 для 8 бамперов',
                'Suport mobil GYS 059825 pentru 8 bare de protecție',
                'Двусторонняя мобильная стойка GYS 059825 вмещает до восьми бамперов. Размер конструкции — 104,5 × 108,8 см, масса — 8,1 кг; колёса упрощают перемещение деталей по кузовной мастерской.',
                'Suportul mobil bilateral GYS 059825 poate găzdui până la opt bare de protecție. Dimensiunile structurii sunt de 104,5 × 108,8 cm, greutatea este de 8,1 kg, iar roțile facilitează deplasarea pieselor în atelierul de caroserie.',
                ['Тип' => 'Мобильная стойка для бамперов', 'Вместимость' => '8 бамперов', 'Исполнение' => 'Стойка на колёсах', 'Габаритные размеры' => '104.5 × 108.8 cm', 'Вес' => '8.1 kg'],
                'tinichigerie-si-richtuire',
                weight: '8.1 kg',
                dimensions: '104.5 × 108.8 cm'
            ),
            '059856' => $this->record(
                'Толщиномер лакокрасочного покрытия GYS 059856, 0–1,80 мм',
                'Aparat pentru măsurarea grosimii vopselei GYS 059856, 0–1,80 mm',
                'GYS 059856 измеряет толщину немагнитных покрытий на стальных и алюминиевых основаниях в диапазоне 0–1,80 мм. Разрешение составляет 0,01 мм, погрешность — ±0,03 мм; питание обеспечивают две батарейки AAA 1,5 В.',
                'GYS 059856 măsoară grosimea acoperirilor nemagnetice pe suporturi din oțel și aluminiu în intervalul 0–1,80 mm. Rezoluția este de 0,01 mm, precizia de ±0,03 mm, iar alimentarea este asigurată de două baterii AAA de 1,5 V.',
                ['Тип' => 'Толщиномер лакокрасочного покрытия', 'Диапазон измерения' => '0–1.80 mm', 'Разрешение' => '0.01 mm', 'Точность' => '±0.03 mm', 'Материал' => 'Сталь / алюминий', 'Источник питания' => '2 × AAA 1.5 V', 'Габаритные размеры' => '62 × 30.5 × 105 mm'],
                'instrumente-control-verificare',
                dimensions: '62 × 30.5 × 105 mm'
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
                'snippet' => 'Manufacturer publication matched by exact GYS SKU.',
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
