<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-king-tony-official-set-review';

    public function up(): void
    {
        $records = $this->records();
        $categoryIds = DB::table('categories')
            ->whereIn('slug', collect($records)->pluck('category')->unique()->all())
            ->pluck('id', 'slug');

        DB::transaction(function () use ($records, $categoryIds): void {
            foreach ($records as $sku => $content) {
                $product = DB::table('products')->where('sku', $sku)->first();
                if (! $product) {
                    continue;
                }

                $categoryId = $categoryIds->get($content['category']);
                $this->updateProduct($product, $content, $categoryId ? (int) $categoryId : null);
            }
        });
    }

    private function records(): array
    {
        return [
            '1004A6HQ' => $this->record(
                'Набор силовых шестигранных бит King Tony 1004A6HQ, 1/4″, 6 предметов',
                'Set de biți de putere HEX King Tony 1004A6HQ, 1/4″, 6 piese',
                'Набор из шести силовых шестигранных бит King Tony 1004A6HQ длиной 50 мм с хвостовиком 1/4″.',
                'Set de șase biți de putere HEX King Tony 1004A6HQ, lungime 50 mm, cu tijă de 1/4″.',
                'В набор входят биты H2, H2.5, H3, H4, H5 и H6. Короткая шейка имеет длину 9,5 мм. Биты изготовлены из стали S2, имеют фосфатированное покрытие с антикоррозионным маслом и магнитный шестигранный хвостовик 1/4″. Соответствуют DIN ISO 2351-3.',
                'Setul conține biții H2, H2.5, H3, H4, H5 și H6. Gâtul scurt are 9,5 mm. Biții sunt fabricați din oțel S2, au finisaj fosfatat cu ulei anticoroziv și tijă hexagonală magnetică de 1/4″. Respectă DIN ISO 2351-3.',
                [
                    'Тип' => 'Набор силовых бит',
                    'Количество предметов' => '6',
                    'Рабочий профиль' => 'HEX',
                    'Размеры битов' => 'H2 / H2.5 / H3 / H4 / H5 / H6',
                    'Хвостовик' => '1/4 inch HEX',
                    'Длина' => '50 mm',
                    'Длина шейки' => '9,5 mm',
                    'Материал' => 'Сталь S2',
                    'Покрытие' => 'Фосфатирование с антикоррозионным маслом',
                    'Магнитный хвостовик' => 'Да',
                    'Стандарт' => 'DIN ISO 2351-3',
                    'Вес' => '0,09 kg',
                ],
                'biti-insertii-adaptoare',
                'https://www.kingtony.com/product/6-PC-Power-Bit-Set-HEXAGON-head-1004A6HQ',
                weight: '0,09 kg'
            ),
            '1004A6TQ' => $this->record(
                'Набор силовых бит TORX King Tony 1004A6TQ, 1/4″, 6 предметов',
                'Set de biți de putere TORX King Tony 1004A6TQ, 1/4″, 6 piese',
                'Набор из шести силовых бит TORX King Tony 1004A6TQ длиной 50 мм с хвостовиком 1/4″.',
                'Set de șase biți de putere TORX King Tony 1004A6TQ, lungime 50 mm, cu tijă de 1/4″.',
                'В набор входят биты T15, T20, T25, T27, T30 и T40. Короткая шейка имеет длину 9,5 мм. Биты изготовлены из стали S2, имеют фосфатированное покрытие с антикоррозионным маслом и магнитный шестигранный хвостовик 1/4″.',
                'Setul conține biții T15, T20, T25, T27, T30 și T40. Gâtul scurt are 9,5 mm. Biții sunt fabricați din oțel S2, au finisaj fosfatat cu ulei anticoroziv și tijă hexagonală magnetică de 1/4″.',
                [
                    'Тип' => 'Набор силовых бит',
                    'Количество предметов' => '6',
                    'Рабочий профиль' => 'TORX',
                    'Размеры битов' => 'T15 / T20 / T25 / T27 / T30 / T40',
                    'Хвостовик' => '1/4 inch HEX',
                    'Длина' => '50 mm',
                    'Длина шейки' => '9,5 mm',
                    'Материал' => 'Сталь S2',
                    'Покрытие' => 'Фосфатирование с антикоррозионным маслом',
                    'Магнитный хвостовик' => 'Да',
                    'Вес' => '0,09 kg',
                ],
                'biti-insertii-adaptoare',
                'https://www.kingtony.com/product/6-PC-Power-Bit-Set-TORX-head-1004A6TQ',
                weight: '0,09 kg'
            ),
            '1004A6UQ' => $this->record(
                'Набор антивандальных силовых бит TORX King Tony 1004A6UQ, 1/4″, 6 предметов',
                'Set de biți de putere TORX antiefracție King Tony 1004A6UQ, 1/4″, 6 piese',
                'Набор из шести антивандальных силовых бит TORX King Tony 1004A6UQ длиной 50 мм с хвостовиком 1/4″.',
                'Set de șase biți de putere TORX antiefracție King Tony 1004A6UQ, lungime 50 mm, cu tijă de 1/4″.',
                'В набор входят биты T10H, T15H, T20H, T25H, T27H и T30H с отверстием. Короткая шейка имеет длину 9,5 мм. Биты изготовлены из стали S2, имеют фосфатированное покрытие с антикоррозионным маслом и магнитный шестигранный хвостовик 1/4″.',
                'Setul conține biții cu orificiu T10H, T15H, T20H, T25H, T27H și T30H. Gâtul scurt are 9,5 mm. Biții sunt fabricați din oțel S2, au finisaj fosfatat cu ulei anticoroziv și tijă hexagonală magnetică de 1/4″.',
                [
                    'Тип' => 'Набор силовых бит',
                    'Количество предметов' => '6',
                    'Рабочий профиль' => 'Tamper-resistant TORX',
                    'Размеры битов' => 'T10H / T15H / T20H / T25H / T27H / T30H',
                    'Хвостовик' => '1/4 inch HEX',
                    'Длина' => '50 mm',
                    'Длина шейки' => '9,5 mm',
                    'Материал' => 'Сталь S2',
                    'Покрытие' => 'Фосфатирование с антикоррозионным маслом',
                    'Магнитный хвостовик' => 'Да',
                    'Вес' => '0,09 kg',
                ],
                'biti-insertii-adaptoare',
                'https://www.kingtony.com/product/6-PC-Power-Bit-Set-TORX-head-1004A6UQ',
                weight: '0,09 kg'
            ),
            '4421MP1' => $this->record(
                'Набор ударных головок King Tony 4421MP1, 1/2″, 10–32 мм, 11 предметов',
                'Set de capete de impact King Tony 4421MP1, 1/2″, 10–32 mm, 11 piese',
                'Набор из 11 шестигранных ударных головок King Tony 4421MP1 с приводом 1/2″ на металлической планке.',
                'Set de 11 capete hexagonale de impact King Tony 4421MP1 cu antrenare de 1/2″, pe șină metalică.',
                'В набор входят шестигранные головки 10, 11, 13, 17, 19, 22, 24, 27, 29, 30 и 32 мм. Головки изготовлены из хром-молибденовой стали и имеют фосфатированное покрытие. Длина планки — 400 мм, масса комплекта — 1,69 кг.',
                'Setul include capete hexagonale de 10, 11, 13, 17, 19, 22, 24, 27, 29, 30 și 32 mm. Capetele sunt fabricate din oțel crom-molibden și au finisaj fosfatat. Șina are 400 mm, iar setul cântărește 1,69 kg.',
                [
                    'Тип' => 'Набор ударных торцевых головок',
                    'Количество предметов' => '11',
                    'Привод' => '1/2 inch',
                    'Количество граней' => '6',
                    'Размеры головок' => '10 / 11 / 13 / 17 / 19 / 22 / 24 / 27 / 29 / 30 / 32 mm',
                    'Материал' => 'Хром-молибденовая сталь',
                    'Покрытие' => 'Фосфатированное',
                    'Длина планки' => '400 mm',
                    'Вес' => '1,69 kg',
                ],
                'capete-tubulare-impact',
                'https://www.kingtony.com/product/11-PC-6-Point-Impact-Socket-Rail-Set-4421MP1',
                weight: '1,69 kg'
            ),
            '6424MP' => $this->record(
                'Набор ударных головок King Tony 6424MP, 3/4″, 24–65 мм, 14 предметов',
                'Set de capete de impact King Tony 6424MP, 3/4″, 24–65 mm, 14 piese',
                'Набор из 14 шестигранных ударных головок King Tony 6424MP с приводом 3/4″ в кейсе.',
                'Set de 14 capete hexagonale de impact King Tony 6424MP cu antrenare de 3/4″, în cutie.',
                'В набор входят головки 24, 27, 30, 32, 33, 34, 36, 38, 41, 46, 50, 55, 60 и 65 мм. Они изготовлены из хром-молибденовой стали и имеют фосфатированное покрытие. Размер кейса — 422 × 360 × 90 мм.',
                'Setul include capete de 24, 27, 30, 32, 33, 34, 36, 38, 41, 46, 50, 55, 60 și 65 mm. Sunt fabricate din oțel crom-molibden și au finisaj fosfatat. Cutia măsoară 422 × 360 × 90 mm.',
                [
                    'Тип' => 'Набор ударных торцевых головок',
                    'Количество предметов' => '14',
                    'Привод' => '3/4 inch',
                    'Количество граней' => '6',
                    'Размеры головок' => '24 / 27 / 30 / 32 / 33 / 34 / 36 / 38 / 41 / 46 / 50 / 55 / 60 / 65 mm',
                    'Материал' => 'Хром-молибденовая сталь',
                    'Покрытие' => 'Фосфатированное',
                    'Размер кейса' => '422 × 360 × 90 mm',
                ],
                'capete-tubulare-impact',
                'https://www.kingtony.com/product/14-PC-6-Point-Impact-Socket-Set-6424MP',
                dimensions: '422 × 360 × 90 mm'
            ),
            '1226MR' => $this->record(
                'Набор комбинированных ключей King Tony 1226MR, 6–32 мм, 26 предметов',
                'Set de chei combinate King Tony 1226MR, 6–32 mm, 26 piese',
                'Набор из 26 комбинированных ключей King Tony 1226MR метрических размеров от 6 до 32 мм.',
                'Set de 26 chei combinate King Tony 1226MR, dimensiuni metrice de la 6 la 32 mm.',
                'Комплект серии 1060 включает ключи 6–30 мм с шагом 1 мм и ключ 32 мм. Ключи изготовлены из хром-ванадиевой стали; набор поставляется в нейлоновом чехле. Масса — 5,67 кг.',
                'Setul din seria 1060 include chei de 6–30 mm în trepte de 1 mm și cheia de 32 mm. Cheile sunt fabricate din oțel crom-vanadiu, iar setul este livrat într-o husă din nailon. Greutatea este de 5,67 kg.',
                [
                    'Тип' => 'Набор комбинированных ключей',
                    'Количество предметов' => '26',
                    'Размеры ключей' => '6 / 7 / 8 / 9 / 10 / 11 / 12 / 13 / 14 / 15 / 16 / 17 / 18 / 19 / 20 / 21 / 22 / 23 / 24 / 25 / 26 / 27 / 28 / 29 / 30 / 32 mm',
                    'Серия инструмента' => '1060',
                    'Материал' => 'Хром-ванадиевая сталь',
                    'Комплектация' => 'Нейлоновый чехол',
                    'Вес' => '5,67 kg',
                ],
                'chei-si-surubelnite',
                'https://www.kingtony.com/product/26-PC-Combination-Wrench-Set-1226MR',
                weight: '5,67 kg'
            ),
            '9-1216MR03' => $this->record(
                'Набор комбинированных ключей King Tony 9-1216MR03, 6–32 мм, 16 предметов',
                'Set de chei combinate King Tony 9-1216MR03, 6–32 mm, 16 piese',
                'Набор из 16 комбинированных ключей King Tony 9-1216MR03 в ложементе для инструментальной тележки.',
                'Set de 16 chei combinate King Tony 9-1216MR03 în tavă pentru dulap sau cărucior de scule.',
                'В комплект входят ключи 6, 8, 10, 12, 13, 14, 16, 17, 19, 21, 22, 24, 26, 27, 29 и 32 мм. Размер ложемента категории №4 — 375 × 265 мм. Масса комплекта — 3,20 кг.',
                'Setul include chei de 6, 8, 10, 12, 13, 14, 16, 17, 19, 21, 22, 24, 26, 27, 29 și 32 mm. Tava din categoria nr. 4 măsoară 375 × 265 mm. Greutatea setului este de 3,20 kg.',
                [
                    'Тип' => 'Набор комбинированных ключей',
                    'Количество предметов' => '16',
                    'Размеры ключей' => '6 / 8 / 10 / 12 / 13 / 14 / 16 / 17 / 19 / 21 / 22 / 24 / 26 / 27 / 29 / 32 mm',
                    'Серия инструмента' => '1060',
                    'Размер ложемента' => '375 × 265 mm',
                    'Вес' => '3,20 kg',
                ],
                'chei-si-surubelnite',
                'https://www.kingtony.com/product/16-PC-Combination-Wrench-Set-Metric-for-Tool-Chest-Trolley-9-1216MR',
                weight: '3,20 kg',
                dimensions: '375 × 265 mm'
            ),
            '1406PR' => $this->record(
                'Набор накидных ключей E-TORX King Tony 1406PR, 6 предметов',
                'Set de chei inelare E-TORX King Tony 1406PR, 6 piese',
                'Набор из шести двусторонних накидных ключей E-TORX King Tony 1406PR в металлическом кейсе.',
                'Set de șase chei inelare duble E-TORX King Tony 1406PR în cutie metalică.',
                'Комплект включает размеры E6×E8, E7×E11, E10×E12, E14×E18, E16×E22 и E20×E24. Ключи изготовлены из хром-ванадиевой стали, имеют хромированное покрытие и соответствуют ANSI/ASME B107.6. Размер кейса — 280 × 130 × 41 мм, масса — 0,46 кг.',
                'Setul include dimensiunile E6×E8, E7×E11, E10×E12, E14×E18, E16×E22 și E20×E24. Cheile sunt fabricate din oțel crom-vanadiu, au finisaj cromat și respectă ANSI/ASME B107.6. Cutia măsoară 280 × 130 × 41 mm, iar greutatea este de 0,46 kg.',
                [
                    'Тип' => 'Набор накидных ключей E-TORX',
                    'Количество предметов' => '6',
                    'Размеры ключей' => 'E6×E8 / E7×E11 / E10×E12 / E14×E18 / E16×E22 / E20×E24',
                    'Материал' => 'Хром-ванадиевая сталь',
                    'Покрытие' => 'Хромированное',
                    'Стандарт' => 'ANSI/ASME B107.6',
                    'Размер кейса' => '280 × 130 × 41 mm',
                    'Вес' => '0,46 kg',
                ],
                'chei-si-surubelnite',
                'https://www.kingtony.com/product/Star-Box-End-Wrench-Set-1406PR',
                weight: '0,46 kg',
                dimensions: '280 × 130 × 41 mm'
            ),
            '6BF11-17US' => $this->record(
                'Паяльная проволока King Tony 6BF11-17US, Sn60/Pb40, Ø1 мм',
                'Sârmă de lipit King Tony 6BF11-17US, Sn60/Pb40, Ø1 mm',
                'Паяльная проволока King Tony 6BF11-17US диаметром 1 мм, состав Sn60/Pb40.',
                'Sârmă de lipit King Tony 6BF11-17US cu diametrul de 1 mm și compoziția Sn60/Pb40.',
                'Припой содержит 60% олова и 40% свинца. Диаметр проволоки — 1,0 мм, масса катушки — 27 г. Предназначен для пайки электронных и электротехнических соединений.',
                'Aliajul conține 60% staniu și 40% plumb. Diametrul sârmei este de 1,0 mm, iar masa bobinei de 27 g. Este destinat lipirii conexiunilor electronice și electrice.',
                [
                    'Тип' => 'Паяльная проволока',
                    'Содержание олова' => '60%',
                    'Содержание свинца' => '40%',
                    'Диаметр' => '1,0 mm',
                    'Вес' => '27 g',
                ],
                'lipire-si-consumabile',
                'https://www.kingtony.com/product/Soldering-Wire-6BF11-17',
                weight: '0,027 kg'
            ),
            '9CJ7416' => $this->record(
                'Скребок с твердосплавным лезвием King Tony 9CJ7416, 16 мм',
                'Racletă cu lamă din carbură King Tony 9CJ7416, 16 mm',
                'Скребок King Tony 9CJ7416 с лезвием из карбида вольфрама шириной 16 мм.',
                'Racletă King Tony 9CJ7416 cu lamă din carbură de tungsten, lățime 16 mm.',
                'Твердосплавное лезвие рассчитано на удаление нагара, ржавчины и остатков силикона с поддонов, выпускных коллекторов, тормозных суппортов и других поверхностей. Лезвие закрывается защитным колпачком; в рукоятке предусмотрено отверстие для подвешивания. Общая длина — 210 мм, масса — 136 г.',
                'Lama din carbură este destinată îndepărtării depunerilor de carbon, ruginii și reziduurilor de silicon de pe băi de ulei, colectoare de evacuare, etriere și alte suprafețe. Lama are capac de protecție, iar mânerul este prevăzut cu orificiu de agățare. Lungimea totală este de 210 mm, iar greutatea de 136 g.',
                [
                    'Тип' => 'Скребок с твердосплавным лезвием',
                    'Рабочая ширина' => '16 mm',
                    'Материал лезвия' => 'Карбид вольфрама',
                    'Длина' => '210 mm',
                    'Комплектация' => 'Защитный колпачок лезвия',
                    'Вес' => '136 g',
                ],
                'taiere-pilire-prelucrare',
                'https://www.kingtony.com/product/16mm-Carbide-Scraper-9CJ7416',
                weight: '0,136 kg'
            ),
            '2549MR-EB' => $this->record(
                'Набор торцевых головок и бит King Tony 2549MR-EB, 1/4″, 48 предметов',
                'Set de capete tubulare și biți King Tony 2549MR-EB, 1/4″, 48 piese',
                'Компактный набор King Tony 2549MR-EB из 48 торцевых головок, бит и принадлежностей с приводом 1/4″.',
                'Set compact King Tony 2549MR-EB cu 48 de capete tubulare, biți și accesorii, antrenare de 1/4″.',
                'Комплект включает стандартные и глубокие головки 4–14 мм, головки E4–E8, биты PH1/PH2, шлицевые 4/5,5 мм и HEX H3–H8, а также удлинитель 55 мм. Складной кейс изготовлен из гибкого полипропилена и оснащён двусторонней застёжкой. Масса набора — 1,28 кг.',
                'Setul include capete standard și lungi de 4–14 mm, capete E4–E8, biți PH1/PH2, drepți de 4/5,5 mm și HEX H3–H8, plus un prelungitor de 55 mm. Cutia pliabilă este realizată din polipropilenă flexibilă și are închidere bidirecțională. Greutatea setului este de 1,28 kg.',
                [
                    'Тип' => 'Набор торцевых головок и бит',
                    'Количество предметов' => '48',
                    'Привод' => '1/4 inch',
                    'Размеры головок' => '4–14 mm; E4–E8',
                    'Размеры битов' => 'PH1 / PH2 / SL4 / SL5.5 / H3 / H4 / H5 / H6 / H8',
                    'Комплектация' => 'Стандартные и глубокие головки / биты / удлинитель 55 mm',
                    'Материал кейса' => 'Полипропилен',
                    'Вес' => '1,28 kg',
                ],
                'tubulare-si-clichete',
                'https://www.kingtony.com/product/48-PC-Socket-Set-2549MR-EB',
                weight: '1,28 kg'
            ),
            '2883' => $this->insertAdapter('2883', '1/4 inch F', '3/8 inch M', '90 N·m', '18 g', '0,018 kg'),
            '3884' => $this->insertAdapter('3884', '3/8 inch F', '1/2 inch M', '300 N·m', '32 g', '0,032 kg'),
            '4886' => $this->insertAdapter('4886', '1/2 inch F', '3/4 inch M', '760 N·m', '90 g', '0,090 kg'),
            '3033MRV' => $this->record(
                'Набор двенадцатигранных головок King Tony 3033MRV, 3/8″, 33 предмета',
                'Set de capete bihexagonale King Tony 3033MRV, 3/8″, 33 piese',
                'Набор King Tony 3033MRV из 33 двенадцатигранных торцевых головок и принадлежностей с приводом 3/8″.',
                'Set King Tony 3033MRV cu 33 de capete tubulare bihexagonale și accesorii, antrenare de 3/8″.',
                'Комплект содержит стандартные головки 8–22 мм, глубокие головки 7–24 мм и многофункциональные удлинители 3″ и 6″. Инструменты размещены в ложементе категории №8 размером 375 × 187 мм; размер кейса — 389 × 185 × 66 мм. Масса — 3,40 кг.',
                'Setul conține capete standard de 8–22 mm, capete lungi de 7–24 mm și prelungitoare multifuncționale de 3″ și 6″. Sculele sunt dispuse într-o tavă categoria nr. 8 de 375 × 187 mm; cutia măsoară 389 × 185 × 66 mm. Greutatea este de 3,40 kg.',
                [
                    'Тип' => 'Набор торцевых головок',
                    'Количество предметов' => '33',
                    'Привод' => '3/8 inch',
                    'Количество граней' => '12',
                    'Размеры головок' => 'Стандартные 8–22 mm / глубокие 7–24 mm',
                    'Комплектация' => 'Головки / удлинители 3 и 6 inch / ложемент',
                    'Размер ложемента' => '375 × 187 mm',
                    'Размер кейса' => '389 × 185 × 66 mm',
                    'Вес' => '3,40 kg',
                ],
                'tubulare-si-clichete',
                'https://www.kingtony.com/product/33-PC-12-Point-Socket-Wrench-Set-3033MRV',
                weight: '3,40 kg',
                dimensions: '389 × 185 × 66 mm'
            ),
            '78106' => $this->adapterSet(false),
            '786P06' => $this->adapterSet(true),
            '9103PR' => $this->record(
                'Набор адаптеров для силовых бит King Tony 9103PR, 1/4″–1/2″, 3 предмета',
                'Set de adaptoare pentru biți de putere King Tony 9103PR, 1/4″–1/2″, 3 piese',
                'Набор из трёх адаптеров King Tony 9103PR для соединения силовых бит 1/4″ с квадратами 1/4″, 3/8″ и 1/2″.',
                'Set de trei adaptoare King Tony 9103PR pentru conectarea biților de putere de 1/4″ la pătrate de 1/4″, 3/8″ și 1/2″.',
                'Комплект состоит из адаптеров 7702-50, 7703-50 и 7704-50. Каждый адаптер имеет длину 50 мм и шариковый фиксатор. Соответствует DIN 7428. Масса набора — 0,09 кг.',
                'Setul conține adaptoarele 7702-50, 7703-50 și 7704-50. Fiecare adaptor are lungimea de 50 mm și fixare cu bilă. Respectă DIN 7428. Greutatea setului este de 0,09 kg.',
                [
                    'Тип' => 'Набор адаптеров для силовых бит',
                    'Количество предметов' => '3',
                    'Хвостовик' => '1/4 inch HEX',
                    'Выходной квадрат' => '1/4 / 3/8 / 1/2 inch',
                    'Состав набора' => '7702-50 / 7703-50 / 7704-50',
                    'Длина' => '50 mm',
                    'Фиксация' => 'Шариковый фиксатор',
                    'Стандарт' => 'DIN 7428',
                    'Вес' => '0,09 kg',
                ],
                'biti-insertii-adaptoare',
                'https://www.kingtony.com/product/3-PC-Power-Bit-Socket-Adapter-Set-9103PR',
                weight: '0,09 kg'
            ),
        ];
    }

    private function insertAdapter(string $sku, string $input, string $output, string $torque, string $weightAttribute, string $weight): array
    {
        return $this->record(
            "Вставной магнитный адаптер King Tony {$sku}, {$input} × {$output}",
            "Adaptor magnetic compact King Tony {$sku}, {$input} × {$output}",
            "Компактный вставной адаптер King Tony {$sku} с магнитной фиксацией для работы в ограниченном пространстве.",
            "Adaptor compact King Tony {$sku} cu fixare magnetică pentru lucru în spații înguste.",
            "Адаптер преобразует {$input} в {$output}. Дополнительные магниты удерживают адаптер в головке. Предназначен только для ручного инструмента; максимальный крутящий момент — {$torque}.",
            "Adaptorul transformă {$input} în {$output}. Magneții suplimentari îl mențin în capul tubular. Este destinat exclusiv sculelor manuale; cuplul maxim este de {$torque}.",
            [
                'Тип' => 'Вставной магнитный адаптер',
                'Входной квадрат' => $input,
                'Выходной квадрат' => $output,
                'Максимальный крутящий момент' => $torque,
                'Применение' => 'Для ручного инструмента',
                'Вес' => $weightAttribute,
            ],
            'tubulare-si-clichete',
            "https://www.kingtony.com/product/Insert-Adapter-KING%20TONY-{$sku}",
            weight: $weight
        );
    }

    private function adapterSet(bool $impact): array
    {
        $sku = $impact ? '786P06' : '78106';
        $typeRu = $impact ? 'Набор ударных адаптеров' : 'Набор ручных адаптеров';
        $typeRo = $impact ? 'Set de adaptoare de impact' : 'Set de adaptoare manuale';
        $category = $impact ? 'capete-tubulare-impact' : 'tubulare-si-clichete';
        $source = $impact
            ? 'https://www.kingtony.com/product/6-PC-Impact-Adapter-Set-786P06'
            : 'https://www.kingtony.com/product/6-PC-Adapter-Set-78106';
        $standard = $impact ? 'DIN 3121 / ISO 1174' : 'DIN 3123 / ISO 3316; DIN 3120 / ISO 1174';
        $usageRu = $impact ? 'Для механизированного инструмента' : 'Для ручного инструмента';
        $usageRo = $impact ? 'pentru utilizare mecanizată' : 'pentru utilizare manuală';

        return $this->record(
            "{$typeRu} King Tony {$sku}, 1/4″–3/4″, 6 предметов",
            "{$typeRo} King Tony {$sku}, 1/4″–3/4″, 6 piese",
            "Набор из шести адаптеров King Tony {$sku} для перехода между приводами 1/4″, 3/8″, 1/2″ и 3/4″.",
            "Set de șase adaptoare King Tony {$sku} pentru conversia între antrenările de 1/4″, 3/8″, 1/2″ și 3/4″.",
            "Комплект содержит переходы 1/4″→3/8″, 3/8″→1/4″, 3/8″→1/2″, 1/2″→3/8″, 1/2″→3/4″ и 3/4″→1/2″. Шариковая фиксация соответствует {$standard}. Набор предназначен {$usageRu}; масса — 1,31 кг.",
            "Setul conține adaptoare 1/4″→3/8″, 3/8″→1/4″, 3/8″→1/2″, 1/2″→3/8″, 1/2″→3/4″ și 3/4″→1/2″. Fixarea cu bilă respectă {$standard}. Setul este destinat {$usageRo}; greutatea este de 1,31 kg.",
            [
                'Тип' => $typeRu,
                'Количество предметов' => '6',
                'Диапазон приводов' => '1/4 / 3/8 / 1/2 / 3/4 inch',
                'Состав набора' => '1/4→3/8 / 3/8→1/4 / 3/8→1/2 / 1/2→3/8 / 1/2→3/4 / 3/4→1/2 inch',
                'Фиксация' => 'Шариковый фиксатор',
                'Стандарт' => $standard,
                'Применение' => $usageRu,
                'Вес' => '1,31 kg',
            ],
            $category,
            $source,
            weight: '1,31 kg'
        );
    }

    private function record(
        string $nameRu,
        string $nameRo,
        string $shortRu,
        string $shortRo,
        string $descriptionRu,
        string $descriptionRo,
        array $attributes,
        string $category,
        string $sourceUrl,
        ?string $weight = null,
        ?string $dimensions = null
    ): array {
        return compact(
            'nameRu', 'nameRo', 'shortRu', 'shortRo', 'descriptionRu', 'descriptionRo',
            'attributes', 'category', 'sourceUrl', 'weight', 'dimensions'
        );
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sourceDomain = parse_url($content['sourceUrl'], PHP_URL_HOST);
        $imageUrls = $product->source_parser_item_id
            ? DB::table('product_parser_image_assets')
                ->where('parser_item_id', $product->source_parser_item_id)
                ->pluck('source_url')
                ->all()
            : [];
        $sourceUrls = array_values(array_unique(array_filter([$content['sourceUrl'], ...$imageUrls])));

        $updates = [
            'name' => $content['nameRu'],
            'name_ru' => $content['nameRu'],
            'name_ro' => $content['nameRo'],
            'short_description' => $content['shortRu'],
            'short_description_ru' => $content['shortRu'],
            'short_description_ro' => $content['shortRo'],
            'description' => $content['descriptionRu'],
            'description_ru' => $content['descriptionRu'],
            'description_ro' => $content['descriptionRo'],
            'attributes' => $attributes,
            'weight' => $content['weight'],
            'dimensions' => $content['dimensions'],
            'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'source_url' => $content['sourceUrl'],
            'source_domain' => $sourceDomain,
            'source_type' => 'official_manufacturer',
            'fallback_source_used' => false,
            'needs_source_review' => false,
            'needs_content_review' => false,
            'generated_content' => false,
            'source_reviewed_at' => $now,
            'meta_title' => $content['nameRu'].' | MasterScule.md',
            'meta_description' => mb_substr($content['shortRu'], 0, 250),
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

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update([
            'name_ru' => $content['nameRu'],
            'name_ro' => $content['nameRo'],
            'short_description_ru' => $content['shortRu'],
            'short_description_ro' => $content['shortRo'],
            'description_ru' => $content['descriptionRu'],
            'description_ro' => $content['descriptionRo'],
            'found_title' => $content['nameRu'],
            'found_description' => $content['descriptionRu'],
            'found_specs_json' => $attributes,
            'source_urls_json' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'official_source_url' => $content['sourceUrl'],
            'official_source_domain' => $sourceDomain,
            'official_source_confidence' => 100,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'source_match_confidence' => 100,
            'needs_source_review' => false,
            'needs_content_review' => false,
            'generated_content' => false,
            'content_source_type' => 'official_source',
            'translation_source_type' => 'curated_translation',
            'source_reviewed_at' => $now,
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
            ['parser_item_id' => $product->source_parser_item_id, 'url' => $content['sourceUrl']],
            [
                'domain' => $sourceDomain,
                'title' => $content['nameRo'],
                'snippet' => 'Official manufacturer source verified by exact SKU.',
                'source_type' => 'official_manufacturer',
                'confidence_score' => 100,
                'raw_data_json' => json_encode(['sku' => $product->sku, 'brand' => 'King Tony'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
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
            'taxonomy_version' => 'verified-2026-08-03',
            'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$categoryId),
            'mode' => $this->mode,
            'status' => 'applied',
            'classifier_confidence' => 1,
            'verifier_confidence' => 1,
            'evidence' => json_encode(["Official King Tony data identifies SKU {$product->sku}; selected category {$categorySlug}."], JSON_UNESCAPED_UNICODE),
            'alternatives' => json_encode([]),
            'validation_errors' => json_encode([]),
            'applied_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated SKU content, source evidence, and category decisions are intentionally retained.
    }
};
