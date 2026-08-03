<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $records = [
            '014664' => $this->entry('Сварочный инвертор MMA GYSARC 160, 10–160 А', 'Invertor de sudură MMA GYSARC 160, 10–160 A', 'Работает от сети 230 В, поддерживает электроды диаметром 1,6–4,0 мм; сварочный ток при ПВ 60% составляет 85 А.', 'Funcționează la 230 V și acceptă electrozi de 1,6–4,0 mm; curentul de sudare la ciclu de 60% este de 85 A.'),
            '048089' => $this->entry('Конические пластиковые кольца, жёлтые, 6 × 22 × 156 мм, 3 шт.', 'Inele conice din plastic, galbene, 6 × 22 × 156 mm, 3 buc.', 'Комплект из трёх колец предназначен для работ по технологии беспокрасочного ремонта вмятин PDR.', 'Setul de trei inele este destinat lucrărilor de îndreptare fără vopsire PDR.'),
            '051331.2' => $this->entry('Тележка для споттера UNIVERSAL 800 с держателем кабелей', 'Cărucior pentru spotter UNIVERSAL 800 cu suport pentru cabluri', 'Предназначена для размещения и перемещения споттера UNIVERSAL 800 и его кабелей.', 'Este destinat amplasării și deplasării spotterului UNIVERSAL 800 și a cablurilor acestuia.'),
            '052284' => $this->entry('Держатель кабелей для кузовного правочного стола', 'Suport pentru cabluri destinat mesei de redresare a caroseriei', 'Позволяет организовать кабели на рабочем месте при кузовном ремонте.', 'Permite organizarea cablurilor la postul de lucru pentru reparații de caroserie.'),
            '053533' => $this->entry('Тележка для споттеров GYSPOT 2700 и GYSPOT 3902', 'Cărucior pentru spotterele GYSPOT 2700 și GYSPOT 3902', 'Предназначена для размещения и перемещения совместимых споттеров и принадлежностей.', 'Este destinat amplasării și deplasării spotterelor compatibile și accesoriilor.'),
            '053694' => $this->entry('Комплект стандартных матриц для пресса 10 т', 'Set de matrițe standard pentru presă de 10 t', 'Набор матриц предназначен для совместимого кузовного пресса усилием 10 т.', 'Setul de matrițe este destinat unei prese compatibile pentru caroserie cu forța de 10 t.'),
            '058644' => $this->entry('Удлинитель рычага для клепального инструмента 8/10 т XT11/XT21', 'Prelungitor de braț pentru scula de nituire 8/10 t XT11/XT21', 'Совместим с клепальными инструментами HR1S, HR110, HR2 и HR210.', 'Este compatibil cu sculele de nituire HR1S, HR110, HR2 și HR210.'),
            '058880' => $this->entry('Мешки для пыли ATEX, 5 шт.', 'Saci de praf ATEX, 5 buc.', 'Комплект содержит пять сменных мешков для совместимой системы пылеудаления ATEX.', 'Setul conține cinci saci de schimb pentru un sistem compatibil de aspirare ATEX.'),
            '077065' => $this->entry('Заклёпочник GYSPRESS 10T Premium Push-Pull', 'Nituitor GYSPRESS 10T Premium Push-Pull', 'Профессиональный инструмент развивает усилие до 10 т и предназначен для клепальных работ при кузовном ремонте.', 'Scula profesională dezvoltă o forță de până la 10 t și este destinată nituirii în reparațiile de caroserie.'),
            '096000' => $this->entry('Запасное колесо для устройства перемещения автомобиля 053243', 'Roată de schimb pentru dispozitivul de deplasare auto 053243', 'Предназначено для замены колеса на совместимом устройстве GYS 053243.', 'Este destinată înlocuirii roții la dispozitivul compatibil GYS 053243.'),
            '51914ind1' => $this->entry('Синие и жёлтые кнопки управления для GYSMI свыше 200 А', 'Butoane de comandă albastre și galbene pentru GYSMI peste 200 A', 'Комплект является запасной частью для совместимых сварочных аппаратов GYSMI.', 'Setul este o piesă de schimb pentru aparatele de sudură GYSMI compatibile.'),
            '51938ind1' => $this->entry('Кнопки управления для GYSPOT 230/400 В', 'Butoane de comandă pentru GYSPOT 230/400 V', 'Комплект является запасной частью для совместимых аппаратов GYSPOT.', 'Setul este o piesă de schimb pentru aparatele GYSPOT compatibile.'),
            '53539' => $this->entry('Зарядное устройство 6 В для лампы 058002', 'Încărcător de 6 V pentru lampa 058002', 'Предназначено для зарядки совместимой лампы GYS 058002.', 'Este destinat încărcării lămpii GYS compatibile 058002.'),
            '53608' => $this->entry('Кабель с разъёмом для PBT 600', 'Cablu cu conector pentru PBT 600', 'Является запасным соединительным кабелем для совместимого прибора PBT 600.', 'Este un cablu de conectare de schimb pentru aparatul compatibil PBT 600.'),
            '97039C' => $this->entry('Электронная плата, запасная часть', 'Placă electronică, piesă de schimb', 'Предназначена для ремонта совместимого оборудования GYS; совместимость необходимо определять по артикулу 97039C.', 'Este destinată reparării echipamentelor GYS compatibile; compatibilitatea se stabilește după codul 97039C.'),
            '97290' => $this->entry('Силовая плата SMI без радиатора для GYSMI 135/130P', 'Placă de putere SMI fără radiator pentru GYSMI 135/130P', 'Является запасной частью для указанных моделей сварочного оборудования.', 'Este o piesă de schimb pentru modelele de echipamente de sudură indicate.'),
            '97300' => $this->entry('Силовая плата SMI без радиатора для GYSMI 160P/160DC', 'Placă de putere SMI fără radiator pentru GYSMI 160P/160DC', 'Является запасной частью для указанных моделей сварочного оборудования.', 'Este o piesă de schimb pentru modelele de echipamente de sudură indicate.'),
            '97380' => $this->entry('Силовая плата SMI без радиатора для GYSMI 200P/220P', 'Placă de putere SMI fără radiator pentru GYSMI 200P/220P', 'Является запасной частью для указанных моделей сварочного оборудования.', 'Este o piesă de schimb pentru modelele de echipamente de sudură indicate.'),
            '97443C' => $this->entry('Электронная плата для GYSFLASH 6 Heritage', 'Placă electronică pentru GYSFLASH 6 Heritage', 'Является запасной частью для зарядного устройства GYSFLASH 6 Heritage.', 'Este o piesă de schimb pentru încărcătorul GYSFLASH 6 Heritage.'),
        ];

        $gysId = DB::table('brands')->where('name', 'GYS')->value('id');
        if ($gysId) {
            $this->updateBrandRecords((int) $gysId, 'GYS', $records);
        }

        $torinId = DB::table('brands')->where('name', 'Torin BIG RED')->value('id');
        if ($torinId) {
            $this->updateBrandRecords((int) $torinId, 'Torin BIG RED', [
                'T950011' => $this->entry('Гидравлический бутылочный домкрат 50 т, 280 + 170 мм', 'Cric hidraulic tip butelie 50 t, 280 + 170 mm', 'Домкрат грузоподъёмностью 50 т имеет исходную высоту 280 мм и ход подъёма 170 мм.', 'Cricul cu capacitatea de 50 t are înălțimea inițială de 280 mm și cursa de ridicare de 170 mm.'),
                'TRE8315' => $this->entry('Ручной гидравлический штабелёр 1,5 т, 330–740 мм', 'Stivuitor hidraulic manual 1,5 t, 330–740 mm', 'Предназначен для подъёма и перемещения грузов массой до 1,5 т в диапазоне высоты 330–740 мм.', 'Este destinat ridicării și deplasării sarcinilor de până la 1,5 t, în domeniul de înălțime 330–740 mm.'),
                'TRHS-A0031_A1016' => $this->entry('Компрессометр для бензиновых двигателей', 'Compresmetru pentru motoare pe benzină', 'Предназначен для измерения компрессии в цилиндрах бензиновых двигателей при диагностике.', 'Este destinat măsurării compresiei în cilindrii motoarelor pe benzină în timpul diagnosticării.'),
            ]);
        }

        $this->repairThreadKit();
    }

    private function repairThreadKit(): void
    {
        $product = DB::table('products')->where('sku', '11311MQ')->first();
        $categoryId = DB::table('categories')->where('slug', 'tarozi-filiere-filetare')->value('id');
        if (! $product || ! $categoryId) {
            return;
        }

        $content = $this->entry(
            'Набор для восстановления резьбы M5 × 0,8, M6 × 1,0, M8 × 1,25, M10 × 1,5',
            'Set pentru refacerea filetelor M5 × 0,8, M6 × 1,0, M8 × 1,25, M10 × 1,5',
            'Предназначен для ремонта повреждённой метрической резьбы четырёх указанных размеров.',
            'Este destinat reparării filetelor metrice deteriorate în cele patru dimensiuni indicate.',
        );
        $this->updateProduct($product, 'King Tony', $content, (int) $categoryId);

        DB::table('category_product')->where('product_id', $product->id)->delete();
        DB::table('category_product')->insert([
            'product_id' => $product->id,
            'category_id' => $categoryId,
            'is_primary' => true,
            'source' => 'verified_sku_content',
            'confidence' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function updateBrandRecords(int $brandId, string $brand, array $records): void
    {
        DB::transaction(function () use ($brandId, $brand, $records): void {
            foreach ($records as $sku => $content) {
                $product = DB::table('products')->where('brand_id', $brandId)->where('sku', $sku)->first();
                if ($product) {
                    $this->updateProduct($product, $brand, $content);
                }
            }
        });
    }

    private function entry(string $nameRu, string $nameRo, string $detailRu, string $detailRo): array
    {
        return compact('nameRu', 'nameRo', 'detailRu', 'detailRo');
    }

    private function updateProduct(object $product, string $brand, array $content, ?int $categoryId = null): void
    {
        $sku = (string) $product->sku;
        $nameRu = $content['nameRu'].' '.$brand.' '.$sku;
        $nameRo = $content['nameRo'].' '.$brand.' '.$sku;
        $descriptionRu = $nameRu.'. '.$content['detailRu'];
        $descriptionRo = $nameRo.'. '.$content['detailRo'];
        $shortRu = Str::limit($descriptionRu, 240, '');
        $shortRo = Str::limit($descriptionRo, 240, '');
        $now = now();
        $updates = [
            'name' => $nameRu,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description' => $shortRu,
            'short_description_ru' => $shortRu,
            'short_description_ro' => $shortRo,
            'description' => $descriptionRu,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'meta_description' => Str::limit($descriptionRu, 150, ''),
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ];
        if ($categoryId) {
            $updates['category_id'] = $categoryId;
        }
        DB::table('products')->where('id', $product->id)->update($updates);

        $parser = [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description_ru' => $shortRu,
            'short_description_ro' => $shortRo,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'found_title' => $nameRu,
            'found_description' => $descriptionRu,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'generated_content' => false,
            'translation_source_type' => 'reviewed_bilingual_content',
            'translation_reviewed_at' => $now,
            'updated_at' => $now,
        ];
        if ($categoryId) {
            $parser['category_id'] = $categoryId;
            $parser['detected_category_id'] = $categoryId;
            $parser['needs_category_review'] = false;
        }
        $query = DB::table('product_parser_items');
        $product->source_parser_item_id
            ? $query->where('id', $product->source_parser_item_id)->update($parser)
            : $query->where('sku', $sku)->update($parser);
    }

    public function down(): void
    {
        // Reviewed bilingual content and corrected category are intentionally retained.
    }
};
