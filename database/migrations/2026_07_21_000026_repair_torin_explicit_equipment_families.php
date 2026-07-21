<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-torin-sku-review';

    public function up(): void
    {
        $this->ensureIndustrialVacuumCategory();
        $records = $this->records();
        $categoryIds = DB::table('categories')
            ->whereIn('slug', collect($records)->pluck('category')->filter()->unique()->all())
            ->pluck('id', 'slug');

        DB::transaction(function () use ($records, $categoryIds): void {
            foreach ($records as $sku => $content) {
                $product = DB::table('products')->where('sku', $sku)->first();
                if (! $product) {
                    continue;
                }

                $categoryId = $content['category'] ? $categoryIds->get($content['category']) : null;
                $this->updateProduct($product, $content, $categoryId ? (int) $categoryId : null);
            }
        });
    }

    private function records(): array
    {
        return [
            'T412002C' => [
                'name_ru' => 'Автомобильные страховочные стойки Torin BIG RED T412002C, 12 т, 465–715 мм, 2 шт.',
                'name_ro' => 'Set capre auto Torin BIG RED T412002C, 12 t, 465–715 mm, 2 buc.',
                'description_ru' => 'Комплект Torin BIG RED T412002C состоит из двух страховочных стоек грузоподъёмностью 12 т. Храповой механизм обеспечивает быструю регулировку высоты от 465 до 715 мм, а стальная рукоятка выполняет функцию дополнительной самоблокировки. Стойки оснащены увеличенной опорной площадкой и зубчатой рейкой из ковкого чугуна.',
                'description_ro' => 'Setul Torin BIG RED T412002C conține două capre auto cu capacitatea de 12 t. Mecanismul cu clichet permite reglarea rapidă a înălțimii între 465 și 715 mm, iar mânerul din oțel asigură blocarea suplimentară. Caprele au șa mărită și cremalieră din fontă ductilă.',
                'attributes' => [
                    'Тип' => 'Автомобильные страховочные стойки',
                    'Количество предметов' => '2',
                    'Грузоподъёмность' => '12 t',
                    'Диапазон подъёма' => '465–715 mm',
                    'Механизм' => 'Храповой с двойной фиксацией',
                    'Масса нетто' => '28 kg',
                    'Масса брутто' => '29 kg',
                    'Размер упаковки' => '340 × 300 × 550 mm',
                ],
                'category' => null,
                'source_url' => 'https://en.tongrunjacks.com/products_details/83.html',
                'source_domain' => 'en.tongrunjacks.com',
                'source_type' => 'official_manufacturer',
                'needs_source_review' => false,
            ],
            'T84007' => [
                'name_ru' => 'Подкатной гидравлический домкрат Torin BIG RED T84007, 4 т, 100–585 мм',
                'name_ro' => 'Cric hidraulic tip cărucior Torin BIG RED T84007, 4 t, 100–585 mm',
                'description_ru' => 'Подкатной гидравлический домкрат Torin BIG RED T84007 поднимает груз массой до 4 т в диапазоне от 100 до 585 мм. Официальная спецификация указывает однопоршневой гидравлический насос, широкую стальную раму, предохранительный перепускной клапан и стальные колёса.',
                'description_ro' => 'Cricul hidraulic tip cărucior Torin BIG RED T84007 ridică sarcini de până la 4 t între 100 și 585 mm. Specificația oficială indică o pompă hidraulică cu un piston, cadru lat din oțel, supapă de siguranță la suprasarcină și roți din oțel.',
                'attributes' => [
                    'Тип' => 'Подкатной гидравлический домкрат',
                    'Грузоподъёмность' => '4 t',
                    'Диапазон подъёма' => '100–585 mm',
                    'Механизм' => 'Однопоршневой гидравлический насос',
                    'Габаритные размеры' => '846 × 398 × 166 mm',
                    'Масса нетто' => '52,3 kg',
                    'Масса брутто' => '55,8 kg',
                    'Размер упаковки' => '897 × 453 × 184 mm',
                ],
                'category' => null,
                'source_url' => 'https://en.tongrunjacks.com/product/134.html',
                'source_domain' => 'en.tongrunjacks.com',
                'source_type' => 'official_manufacturer',
                'needs_source_review' => false,
            ],
            'TRAD036' => [
                'name_ru' => 'Бустер для взрывной накачки шин Torin BIG RED TRAD036, 10 галлонов, 1,0 МПа',
                'name_ro' => 'Booster pentru umflarea rapidă a anvelopelor Torin BIG RED TRAD036, 10 galoane, 1,0 MPa',
                'description_ru' => 'Torin BIG RED TRAD036 — переносной бустер с ресивером 10 галлонов (около 38 л) для быстрой посадки борта бескамерной шины на диск. Рабочее давление составляет 0,6–0,8 МПа, максимальное — 1,0 МПа. Впуск и выпуск оснащены шаровыми клапанами, позволяющими выпустить запас воздуха одним быстрым движением.',
                'description_ro' => 'Torin BIG RED TRAD036 este un booster portabil cu rezervor de 10 galoane (aproximativ 38 l) pentru așezarea rapidă a talonului anvelopei fără cameră pe jantă. Presiunea de lucru este de 0,6–0,8 MPa, iar cea maximă de 1,0 MPa. Admisia și evacuarea folosesc robinete cu bilă pentru eliberarea rapidă a aerului.',
                'attributes' => [
                    'Тип' => 'Бустер для взрывной накачки шин',
                    'Объём' => '10 gal / ≈38 l',
                    'Рабочее давление' => '0,6–0,8 MPa',
                    'Максимальное давление' => '1,0 MPa / 10 bar',
                    'Масса нетто' => '15 kg',
                    'Масса брутто' => '17 kg',
                    'Размер упаковки' => '585 × 525 × 347 mm',
                ],
                'category' => null,
                'source_url' => 'https://en.tongrunjacks.com/products_details/608.html',
                'source_domain' => 'en.tongrunjacks.com',
                'source_type' => 'official_manufacturer',
                'needs_source_review' => false,
            ],
            'TRF70-3P' => [
                'name_ru' => 'Промышленный пылесос Torin BIG RED TRF70-3P для сухой и влажной уборки, 3 двигателя, 80 л',
                'name_ro' => 'Aspirator industrial Torin BIG RED TRF70-3P pentru curățare uscată și umedă, 3 motoare, 80 l',
                'description_ru' => 'Промышленный пылесос Torin BIG RED TRF70-3P предназначен для сухой и влажной уборки. Модель оснащена тремя двигателями, баком объёмом 80 л и рассчитана на однофазную сеть 220–240 В. Карточка требует дополнительного подтверждения полных характеристик по официальному источнику.',
                'description_ro' => 'Aspiratorul industrial Torin BIG RED TRF70-3P este destinat curățării uscate și umede. Modelul are trei motoare, rezervor de 80 l și alimentare monofazată la 220–240 V. Caracteristicile complete necesită confirmare suplimentară dintr-o sursă oficială.',
                'attributes' => [
                    'Тип' => 'Промышленный пылесос для сухой и влажной уборки',
                    'Применение' => 'Сухая и влажная уборка',
                    'Количество двигателей' => '3',
                    'Объём' => '80 l',
                    'Напряжение' => '220–240 V, 1 фаза',
                ],
                'category' => 'aspiratoare-industriale',
                'source_url' => 'https://www.mercadolibre.com.ec/aspiradora-torin-industrial-trf703p/up/MECU2677865537',
                'source_domain' => 'mercadolibre.com.ec',
                'source_type' => 'reviewed_distributor',
                'needs_source_review' => true,
            ],
            'TRHS-8781' => [
                'name_ru' => 'Мастер-набор съёмников дизельных форсунок Torin BIG RED TRHS-8781',
                'name_ro' => 'Set complet de extractoare pentru injectoare diesel Torin BIG RED TRHS-8781',
                'description_ru' => 'Torin BIG RED TRHS-8781 — мастер-набор съёмников для демонтажа дизельных форсунок, поставляемый в формованном кейсе. Точный состав и совместимость требуют подтверждения по официальной документации. Текущее изображение показывает комплект нужного типа, но содержит водяной знак стороннего бренда DATET и направлено на замену.',
                'description_ro' => 'Torin BIG RED TRHS-8781 este un set complet de extractoare pentru demontarea injectoarelor diesel, livrat într-o valiză profilată. Conținutul exact și compatibilitatea necesită confirmare din documentația oficială. Imaginea actuală prezintă tipul corect de set, dar conține filigranul mărcii terțe DATET și trebuie înlocuită.',
                'attributes' => [
                    'Тип' => 'Набор съёмников дизельных форсунок',
                    'Применение' => 'Дизельные форсунки',
                    'Комплектация' => 'Мастер-набор в кейсе',
                ],
                'category' => null,
                'source_url' => null,
                'source_domain' => null,
                'source_type' => 'reviewed_import',
                'needs_source_review' => true,
                'needs_image_review' => true,
            ],
            'TRHS-E3412' => [
                'name_ru' => 'Набор рихтовочных молотков и оправок Torin BIG RED TRHS-E3412, 7 предметов',
                'name_ro' => 'Set de ciocane și tasuri pentru tinichigerie Torin BIG RED TRHS-E3412, 7 piese',
                'description_ru' => 'Набор Torin BIG RED TRHS-E3412 предназначен для кузовных рихтовочных работ. В кейсе находятся три молотка с рукоятками и четыре стальные рихтовочные оправки, всего семь предметов.',
                'description_ro' => 'Setul Torin BIG RED TRHS-E3412 este destinat lucrărilor de îndreptare a caroseriei. Valiza conține trei ciocane cu mâner și patru tasuri din oțel, în total șapte piese.',
                'attributes' => [
                    'Тип' => 'Набор рихтовочных молотков и оправок',
                    'Количество предметов' => '7',
                    'Комплектация' => '3 молотка и 4 рихтовочные оправки в кейсе',
                    'Материал' => 'Сталь',
                ],
                'category' => null,
                'source_url' => 'https://toolmania.cl/desabolladura-y-pintura/juego-de-martillos-desabolladores-trhs-e3412-torin-mi-ton-049352-14125.html',
                'source_domain' => 'toolmania.cl',
                'source_type' => 'reviewed_distributor',
                'needs_source_review' => true,
            ],
            'TY30001' => [
                'name_ru' => 'Гидравлический пресс Torin BIG RED TY30001, 30 т, ход 160 мм',
                'name_ro' => 'Presă hidraulică Torin BIG RED TY30001, 30 t, cursă 160 mm',
                'description_ru' => 'Гидравлический пресс Torin BIG RED TY30001 с Н-образной стальной рамой развивает усилие 30 т. Ход штока составляет 160 мм, рабочий диапазон — 0–840 мм. Пресс предназначен для запрессовки и выпрессовки подшипников, втулок, уплотнений и других ремонтных операций.',
                'description_ro' => 'Presa hidraulică Torin BIG RED TY30001, cu cadru din oțel în formă de H, dezvoltă o forță de 30 t. Cursa pistonului este de 160 mm, iar intervalul de lucru de 0–840 mm. Presa este destinată montării și demontării rulmenților, bucșelor, garniturilor și altor operații de reparație.',
                'attributes' => [
                    'Тип' => 'Гидравлический пресс',
                    'Грузоподъёмность' => '30 t',
                    'Ход штока' => '160 mm',
                    'Рабочий диапазон' => '0–840 mm',
                    'Масса нетто' => '156 kg',
                    'Масса брутто' => '166,5 kg',
                    'Размер упаковки' => '1620 × 380 × 310 mm',
                ],
                'category' => null,
                'source_url' => 'https://en.tongrunjacks.com/product/868.html',
                'source_domain' => 'en.tongrunjacks.com',
                'source_type' => 'official_manufacturer',
                'needs_source_review' => false,
            ],
        ];
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $needsSourceReview = (bool) $content['needs_source_review'];
        $needsImageReview = (bool) ($content['needs_image_review'] ?? $product->needs_image_review);

        $updates = [
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
            'source_url' => $content['source_url'],
            'source_domain' => $content['source_domain'],
            'source_type' => $content['source_type'],
            'fallback_source_used' => $needsSourceReview && filled($content['source_url']),
            'needs_source_review' => $needsSourceReview,
            'needs_image_review' => $needsImageReview,
            'needs_content_review' => false,
            'generated_content' => false,
            'source_reviewed_at' => $needsSourceReview ? null : $now,
            'updated_at' => $now,
        ];

        if ($categoryId) {
            $updates['category_id'] = $categoryId;
            $updates['needs_category_review'] = false;
        }

        DB::table('products')->where('id', $product->id)->update($updates);

        if ($categoryId) {
            $this->syncCategory($product, $categoryId, $content['category'], $now);
        }

        if (! $product->source_parser_item_id) {
            return;
        }

        $parserUpdates = [
            'name_ru' => $content['name_ru'],
            'name_ro' => $content['name_ro'],
            'short_description_ru' => $content['description_ru'],
            'short_description_ro' => $content['description_ro'],
            'description_ru' => $content['description_ru'],
            'description_ro' => $content['description_ro'],
            'found_title' => $content['name_ru'],
            'found_description' => $content['description_ru'],
            'found_specs_json' => $attributes,
            'official_source_url' => $needsSourceReview ? null : $content['source_url'],
            'official_source_domain' => $needsSourceReview ? null : $content['source_domain'],
            'official_source_confidence' => $needsSourceReview ? null : 100,
            'fallback_source_url' => $needsSourceReview ? $content['source_url'] : null,
            'fallback_source_domain' => $needsSourceReview ? $content['source_domain'] : null,
            'fallback_source_used' => $needsSourceReview && filled($content['source_url']),
            'source_match_confidence' => $needsSourceReview ? null : 100,
            'needs_source_review' => $needsSourceReview,
            'needs_image_review' => $needsImageReview,
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => $content['source_type'],
            'source_reviewed_at' => $needsSourceReview ? null : $now,
            'updated_at' => $now,
        ];

        if ($needsImageReview) {
            $parserUpdates['image_reviewed_at'] = null;
        }

        if ($categoryId) {
            $parserUpdates = array_replace($parserUpdates, [
                'category_id' => $categoryId,
                'detected_category_id' => $categoryId,
                'detected_category_path' => $content['category'],
                'category_confidence_score' => 100,
                'category_detection_method' => $this->mode,
                'needs_category_review' => false,
            ]);
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update($parserUpdates);
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

        if ((int) $product->category_id === $categoryId) {
            return;
        }

        DB::table('product_category_decisions')->insert([
            'product_id' => $product->id,
            'previous_category_id' => $product->category_id,
            'selected_category_id' => $categoryId,
            'taxonomy_version' => 'verified-2026-07-21',
            'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$categoryId),
            'mode' => $this->mode,
            'status' => 'applied',
            'classifier_confidence' => 1,
            'verifier_confidence' => 1,
            'evidence' => json_encode(["Product type is explicit in SKU {$product->sku}; selected category {$categorySlug}."], JSON_UNESCAPED_UNICODE),
            'alternatives' => json_encode([]),
            'validation_errors' => json_encode([]),
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureIndustrialVacuumCategory(): void
    {
        if (DB::table('categories')->where('slug', 'aspiratoare-industriale')->exists()) {
            return;
        }

        $parent = DB::table('categories')->where('slug', 'echipamente-pentru-service')->first(['id']);
        if (! $parent) {
            return;
        }

        $now = now();
        DB::table('categories')->insert([
            'parent_id' => $parent->id,
            'name' => 'Промышленные пылесосы',
            'name_ro' => 'Aspiratoare industriale',
            'slug' => 'aspiratoare-industriale',
            'description' => 'Промышленные пылесосы для сухой и влажной уборки мастерских и автосервисов.',
            'description_ro' => 'Aspiratoare industriale pentru curățarea uscată și umedă a atelierelor și service-urilor auto.',
            'sort_order' => 49,
            'is_active' => true,
            'is_assignable' => true,
            'is_menu_visible' => true,
            'source' => 'curated',
            'taxonomy_version' => 'verified-2026-07-21',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated Torin SKU content and source-review decisions are intentionally retained.
    }
};
