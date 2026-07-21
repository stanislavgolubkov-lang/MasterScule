<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'curated-king-tony-sku-review';

    public function up(): void
    {
        $this->ensurePipeToolsCategory();
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
            '1009GPN' => $this->record(
                'Набор пробойников King Tony 1009GPN, 9 предметов, Ø2–14 мм, с защитными рукоятками',
                'Set de dornuri King Tony 1009GPN, 9 piese, Ø2–14 mm, cu mânere de protecție',
                'Набор из девяти пробойников King Tony 1009GPN диаметром 2–14 мм с защитными нескользящими рукоятками и чехлом.',
                'Set de nouă dornuri King Tony 1009GPN cu diametre de 2–14 mm, mânere antiderapante de protecție și husă.',
                'Пробойники серии 764-G изготовлены из хром-ванадиевой стали. В набор входят размеры Ø2 × 140, Ø3 × 150, Ø4 × 180, Ø5 × 200, Ø6 × 210, Ø8 × 220, Ø10 × 230, Ø12 × 250 и Ø14 × 290 мм. Защитные рукоятки имеют нескользящую поверхность и торцевой колпачок; комплект поставляется в чехле из теторона.',
                'Dornurile din seria 764-G sunt fabricate din oțel crom-vanadiu. Setul include dimensiunile Ø2 × 140, Ø3 × 150, Ø4 × 180, Ø5 × 200, Ø6 × 210, Ø8 × 220, Ø10 × 230, Ø12 × 250 și Ø14 × 290 mm. Mânerele de protecție au suprafață antiderapantă și capăt protejat; setul este livrat într-o husă din tetoron.',
                [
                    'Тип' => 'Набор пробойников',
                    'Количество предметов' => '9',
                    'Размеры пробойников' => 'Ø2 × 140 / Ø3 × 150 / Ø4 × 180 / Ø5 × 200 / Ø6 × 210 / Ø8 × 220 / Ø10 × 230 / Ø12 × 250 / Ø14 × 290 mm',
                    'Материал' => 'Хром-ванадиевая сталь',
                    'Исполнение' => 'Защитные нескользящие рукоятки',
                    'Комплектация' => 'Чехол из теторона',
                ],
                'taiere-pilire-prelucrare',
                'https://www.kingtony.com/product/9-PC-Sheathed-Pin-Punch-Set-1009GPN'
            ),
            '1009PRN' => $this->record(
                'Набор пробойников King Tony 1009PRN, 9 предметов, Ø2–14 мм',
                'Set de dornuri King Tony 1009PRN, 9 piese, Ø2–14 mm',
                'Набор из девяти пробойников King Tony 1009PRN диаметром 2–14 мм с восьмигранным хвостовиком и нейлоновым чехлом.',
                'Set de nouă dornuri King Tony 1009PRN cu diametre de 2–14 mm, tijă octogonală și husă din nailon.',
                'Пробойники серии 764 изготовлены из хром-ванадиевой стали и имеют восьмигранный хвостовик. Рабочие размеры: Ø2 × 30, Ø3 × 40, Ø4 × 50, Ø5 × 50, Ø6 × 50, Ø8 × 50, Ø10 × 65, Ø12 × 70 и Ø14 × 75 мм. Комплект поставляется в нейлоновом чехле.',
                'Dornurile din seria 764 sunt fabricate din oțel crom-vanadiu și au tijă octogonală. Dimensiunile de lucru sunt Ø2 × 30, Ø3 × 40, Ø4 × 50, Ø5 × 50, Ø6 × 50, Ø8 × 50, Ø10 × 65, Ø12 × 70 și Ø14 × 75 mm. Setul este livrat într-o husă din nailon.',
                [
                    'Тип' => 'Набор пробойников',
                    'Количество предметов' => '9',
                    'Размеры пробойников' => 'Ø2 × 30 / Ø3 × 40 / Ø4 × 50 / Ø5 × 50 / Ø6 × 50 / Ø8 × 50 / Ø10 × 65 / Ø12 × 70 / Ø14 × 75 mm',
                    'Материал' => 'Хром-ванадиевая сталь',
                    'Форма хвостовика' => 'Восьмигранная',
                    'Комплектация' => 'Нейлоновый чехол',
                ],
                'taiere-pilire-prelucrare',
                'https://www.kingtony.com/product/9-PC-Pin-Punch-Set-1009PR'
            ),
            '1015GQ' => $this->record(
                'Набор напильников King Tony 1015GQ, 5 предметов, 10″',
                'Set de pile King Tony 1015GQ, 5 piese, 10″',
                'Набор King Tony 1015GQ из пяти напильников длиной 10″ с личной насечкой и рукоятками.',
                'Set King Tony 1015GQ cu cinci pile de 10″, tăietură medie și mânere.',
                'Комплект состоит из плоского 75102-10G, полукруглого 75202-10G, круглого 75302-10G, треугольного 75402-10G и квадратного 75502-10G напильников. Все пять инструментов имеют длину 10″, личную насечку T2 и рукоятки.',
                'Setul conține pila plată 75102-10G, semicirculară 75202-10G, rotundă 75302-10G, triunghiulară 75402-10G și pătrată 75502-10G. Toate cele cinci scule au lungimea de 10″, tăietură medie T2 și mânere.',
                [
                    'Тип' => 'Набор напильников',
                    'Количество предметов' => '5',
                    'Длина' => '10 inch',
                    'Тип насечки' => 'Личная, T2',
                    'Состав набора' => 'Плоский / полукруглый / круглый / треугольный / квадратный',
                    'Вес' => '1,4 kg',
                ],
                'taiere-pilire-prelucrare',
                'https://www.kingtony.com/product/5-PC-Second-Cut-File-1015GQ',
                weight: '1,4 kg'
            ),
            '42104GP' => $this->record(
                'Набор шарнирно-губцевого инструмента King Tony 42104GP, 4 предмета',
                'Set de clești King Tony 42104GP, 4 piese',
                'Набор King Tony 42104GP из четырёх шарнирно-губцевых инструментов для слесарных и монтажных работ.',
                'Set King Tony 42104GP cu patru clești pentru lucrări de atelier și montaj.',
                'В комплект входят комбинированные пассатижи 6111-06, диагональные кусачки 6211-06, длинногубцы 6311-08 и переставные клещи 6511-10. Масса набора — 1,17 кг.',
                'Setul include cleștele combinat 6111-06, cleștele tăietor diagonal 6211-06, cleștele cu fălci lungi 6311-08 și cleștele reglabil 6511-10. Greutatea setului este de 1,17 kg.',
                [
                    'Тип' => 'Набор шарнирно-губцевого инструмента',
                    'Количество предметов' => '4',
                    'Состав набора' => '6111-06 / 6211-06 / 6311-08 / 6511-10',
                    'Вес' => '1,17 kg',
                ],
                'clesti-si-instrumente-taiere',
                'https://www.kingtony.com/product/4-PC-Pliers-Set-42104GP',
                weight: '1,17 kg'
            ),
            '42124GP04' => $this->record(
                'Набор шарнирно-губцевого инструмента King Tony 42124GP04, 4 предмета',
                'Set de clești King Tony 42124GP04, 4 piese',
                'Набор King Tony 42124GP04 из четырёх шарнирно-губцевых инструментов для слесарных и монтажных работ.',
                'Set King Tony 42124GP04 cu patru clești pentru lucrări de atelier și montaj.',
                'В комплект входят комбинированные пассатижи 6111-08, усиленные диагональные кусачки 6231-08, длинногубцы 6311-08 и переставные клещи 6511-10C. Масса набора — 1,48 кг.',
                'Setul include cleștele combinat 6111-08, cleștele tăietor diagonal ranforsat 6231-08, cleștele cu fălci lungi 6311-08 și cleștele reglabil 6511-10C. Greutatea setului este de 1,48 kg.',
                [
                    'Тип' => 'Набор шарнирно-губцевого инструмента',
                    'Количество предметов' => '4',
                    'Состав набора' => '6111-08 / 6231-08 / 6311-08 / 6511-10C',
                    'Вес' => '1,48 kg',
                ],
                'clesti-si-instrumente-taiere',
                'https://www.kingtony.com/product/4-PC-Pliers-Set-42124GP',
                weight: '1,48 kg'
            ),
            '6114-05' => $this->record(
                'Мини-пассатижи King Tony 6114-05, 5″, 122 мм',
                'Clește combinat mini King Tony 6114-05, 5″, 122 mm',
                'Компактные комбинированные пассатижи King Tony 6114-05 длиной 122 мм с двухкомпонентными рукоятками.',
                'Clește combinat compact King Tony 6114-05, lungime 122 mm, cu mânere bicomponente.',
                'Мини-пассатижи соответствуют DIN ISO 5746. Рукоятки из PP+TPR обеспечивают устойчивый хват. Номинальная длина инструмента — 5″, фактическая — 122 мм, масса — 87 г.',
                'Cleștele mini respectă standardul DIN ISO 5746. Mânerele din PP+TPR asigură o priză stabilă. Lungimea nominală este de 5″, lungimea totală de 122 mm, iar greutatea de 87 g.',
                [
                    'Тип' => 'Мини-пассатижи',
                    'Длина' => '5 inch / 122 mm',
                    'Материал рукоятки' => 'PP+TPR',
                    'Стандарт' => 'DIN ISO 5746',
                    'Вес' => '87 g',
                ],
                'clesti-si-instrumente-taiere',
                'https://www.kingtony.com/product/Mini-Combination-Pliers-6114',
                weight: '0,087 kg'
            ),
            '6231-08' => $this->record(
                'Усиленные диагональные кусачки King Tony 6231-08, 8″, 200 мм',
                'Clește tăietor diagonal ranforsat King Tony 6231-08, 8″, 200 mm',
                'Усиленные диагональные кусачки King Tony 6231-08 длиной 200 мм с полированной головкой и рукоятками PP+TPR.',
                'Clește tăietor diagonal ranforsat King Tony 6231-08, lungime 200 mm, cu cap polisat și mânere PP+TPR.',
                'Инструмент предназначен для интенсивных режущих работ и соответствует DIN ISO 5749. Полированная рабочая головка дополнена двухкомпонентными рукоятками PP+TPR. Длина — 8″ / 200 мм, масса — 313 г.',
                'Scula este destinată lucrărilor intensive de tăiere și respectă DIN ISO 5749. Capul de lucru polisat este completat de mânere bicomponente PP+TPR. Lungimea este de 8″ / 200 mm, iar greutatea de 313 g.',
                [
                    'Тип' => 'Усиленные диагональные кусачки',
                    'Длина' => '8 inch / 200 mm',
                    'Исполнение' => 'Полированная головка',
                    'Материал рукоятки' => 'PP+TPR',
                    'Стандарт' => 'DIN ISO 5749',
                    'Вес' => '313 g',
                ],
                'clesti-si-instrumente-taiere',
                'https://www.kingtony.com/product/Heavy-Duty-Diagonal-Cutting-Pliers-6231',
                weight: '0,313 kg'
            ),
            '6411MP' => $this->record(
                'Набор ударных шестигранных головок King Tony 6411MP, 3/4″, 11 предметов',
                'Set de capete de impact hexagonale King Tony 6411MP, 3/4″, 11 piese',
                'Набор King Tony 6411MP из 11 ударных шестигранных головок и держателей с приводом 3/4″ в металлическом кейсе.',
                'Set King Tony 6411MP cu 11 capete hexagonale de impact și suporturi, antrenare 3/4″, în cutie metalică.',
                'Набор изготовлен из хром-молибденовой стали с чёрным фосфатным покрытием. Комплектация: держатели 609616M и 609622M, а также шестигранные насадки H10, H12, H14, H17, H19, H22, H24, H27 и H32. Размер металлического кейса — 270 × 100 × 49 мм, масса комплекта — 2,77 кг.',
                'Setul este fabricat din oțel crom-molibden cu acoperire fosfatată neagră. Conținut: suporturile 609616M și 609622M, plus biții hexagonali H10, H12, H14, H17, H19, H22, H24, H27 și H32. Cutia metalică măsoară 270 × 100 × 49 mm, iar setul cântărește 2,77 kg.',
                [
                    'Тип' => 'Набор ударных шестигранных головок',
                    'Количество предметов' => '11',
                    'Привод' => '3/4 inch',
                    'Рабочий профиль' => 'HEX H10 / H12 / H14 / H17 / H19 / H22 / H24 / H27 / H32',
                    'Материал' => 'Хром-молибденовая сталь',
                    'Покрытие' => 'Чёрное фосфатное',
                    'Состав набора' => '609616M / 609622M / H10–H32',
                    'Размер кейса' => '270 × 100 × 49 mm',
                    'Вес' => '2,77 kg',
                ],
                'capete-tubulare-impact',
                'https://www.kingtony.com/product/11-PC-Impact-Bit-Socket-Set-6411MP',
                imageUrl: 'https://www.kingtony.com/upload/products/6411MP.png',
                weight: '2,77 kg',
                dimensions: '270 × 100 × 49 mm',
                clearImage: true
            ),
            '6511-13C' => $this->record(
                'Переставные клещи King Tony 6511-13C, 13″, 325 мм',
                'Clește reglabil King Tony 6511-13C, 13″, 325 mm',
                'Переставные клещи King Tony 6511-13C длиной 325 мм с семью положениями и рукоятками из PVC.',
                'Clește reglabil King Tony 6511-13C, lungime 325 mm, cu șapte poziții și mânere din PVC.',
                'Клещи имеют семь положений регулировки и максимальное раскрытие 65 мм. Исполнение с рукоятками из PVC соответствует DIN ISO 8976. Длина — 13″ / 325 мм, масса — 808 г.',
                'Cleștele are șapte poziții de reglare și o deschidere maximă de 65 mm. Varianta cu mânere din PVC respectă DIN ISO 8976. Lungimea este de 13″ / 325 mm, iar greutatea de 808 g.',
                [
                    'Тип' => 'Переставные клещи',
                    'Количество положений' => '7',
                    'Максимальное раскрытие' => '65 mm',
                    'Длина' => '13 inch / 325 mm',
                    'Материал рукоятки' => 'PVC',
                    'Стандарт' => 'DIN ISO 8976',
                    'Вес' => '808 g',
                ],
                'clesti-si-instrumente-taiere',
                'https://www.kingtony.com/productlist/Pliers-Set/Groove-Joint-Pliers-6511C',
                weight: '0,808 kg'
            ),
            '68HB-07L' => $this->record(
                'Удлинённые щипцы King Tony 68HB-07L для внутренних стопорных колец, 90°, 12–28 мм',
                'Clește lung King Tony 68HB-07L pentru siguranțe interioare, 90°, 12–28 mm',
                'Удлинённые угловые щипцы King Tony 68HB-07L для внутренних стопорных колец диаметром 12–28 мм.',
                'Clește lung unghiular King Tony 68HB-07L pentru siguranțe interioare cu diametrul de 12–28 mm.',
                'Рабочие наконечники изогнуты под углом 90°. Диаметр наконечников — 1,2 мм, их длина — 64 мм. Общая длина инструмента — 7″ / 180 мм, масса — 222 г.',
                'Vârfurile de lucru sunt înclinate la 90°. Diametrul vârfurilor este de 1,2 mm, iar lungimea lor de 64 mm. Lungimea totală a sculei este de 7″ / 180 mm, iar greutatea de 222 g.',
                [
                    'Тип' => 'Щипцы для внутренних стопорных колец',
                    'Угол губок' => '90°',
                    'Диапазон стопорных колец' => '12–28 mm',
                    'Диаметр наконечника' => '1,2 mm',
                    'Длина наконечника' => '64 mm',
                    'Длина' => '7 inch / 180 mm',
                    'Вес' => '222 g',
                ],
                'clesti-si-instrumente-taiere',
                'https://www.kingtony.com/product/Long-Arms-Internal-Angled-Circlip-Pliers-68HB-07L',
                weight: '0,222 kg'
            ),
            '6742-06' => $this->record(
                'Клещи для зачистки проводов King Tony 6742-06, 0,5–2,6 мм',
                'Clește pentru dezizolat conductoare King Tony 6742-06, 0,5–2,6 mm',
                'Клещи King Tony 6742-06 для резки и зачистки проводов диаметром 0,5–2,6 мм.',
                'Clește King Tony 6742-06 pentru tăierea și dezizolarea conductoarelor cu diametrul de 0,5–2,6 mm.',
                'Инструмент работает с проводами AWG 24–10 и сечением 0,2–5,5 мм². Встроенный резак рассчитан на провод диаметром до 6 мм. Номинальная длина — 6″, масса — 103 г.',
                'Scula lucrează cu conductoare AWG 24–10 și secțiuni de 0,2–5,5 mm². Tăietorul integrat acceptă conductoare cu diametrul de până la 6 mm. Lungimea nominală este de 6″, iar greutatea de 103 g.',
                [
                    'Тип' => 'Клещи для зачистки проводов',
                    'Диапазон зачистки' => 'Ø0,5–2,6 mm',
                    'Сечение кабеля' => '0,2–5,5 mm²',
                    'Диапазон AWG' => 'AWG 24–10',
                    'Максимальный диаметр провода' => '6 mm',
                    'Длина' => '6 inch',
                    'Вес' => '103 g',
                ],
                'clesti-electrician-si-cabluri',
                'https://www.kingtony.com/product/Wire-Stripper-6742-06',
                weight: '0,103 kg'
            ),
            '6756-05US' => $this->record(
                'Стриппер для коаксиального кабеля King Tony 6756-05US, Ø2,6–8 мм',
                'Dezizolator pentru cablu coaxial King Tony 6756-05US, Ø2,6–8 mm',
                'Двухлезвийный стриппер King Tony 6756-05US для коаксиальных кабелей диаметром 2,6–8 мм.',
                'Dezizolator King Tony 6756-05US cu două lame pentru cabluri coaxiale cu diametrul de 2,6–8 mm.',
                'Корпус инструмента изготовлен из ABS. Стриппер совместим с кабелями RG-174, RG-58, RG-59, RG-62, RG-6, а также 3C, 4C и 5C. Длина — 5″ / 127 мм, масса — 68 г.',
                'Corpul sculei este fabricat din ABS. Dezizolatorul este compatibil cu cablurile RG-174, RG-58, RG-59, RG-62, RG-6, precum și 3C, 4C și 5C. Lungimea este de 5″ / 127 mm, iar greutatea de 68 g.',
                [
                    'Тип' => 'Стриппер для коаксиального кабеля',
                    'Количество лезвий' => '2',
                    'Диаметр кабеля' => '2,6–8,0 mm',
                    'Совместимые кабели' => 'RG-174 / RG-58 / RG-59 / RG-62 / RG-6 / 3C / 4C / 5C',
                    'Материал корпуса' => 'ABS',
                    'Длина' => '5 inch / 127 mm',
                    'Вес' => '68 g',
                ],
                'clesti-electrician-si-cabluri',
                'https://www.kingtony.com/product/Coax-Cable-Stripper-6756-05',
                weight: '0,068 kg'
            ),
            '67A1-07US' => $this->record(
                'Клещи для зачистки проводов King Tony 67A1-07US, 0,05–3,5 мм²',
                'Clește pentru dezizolat conductoare King Tony 67A1-07US, 0,05–3,5 mm²',
                'Клещи King Tony 67A1-07US для зачистки проводов сечением 0,05–3,5 мм² и резки провода.',
                'Clește King Tony 67A1-07US pentru dezizolarea conductoarelor de 0,05–3,5 mm² și tăierea sârmei.',
                'Рабочий диапазон инструмента соответствует AWG 30–12. Встроенный резак рассчитан на провод диаметром до 5 мм. Масса клещей — 209 г.',
                'Intervalul de lucru al sculei corespunde AWG 30–12. Tăietorul integrat acceptă conductoare cu diametrul de până la 5 mm. Greutatea cleștelui este de 209 g.',
                [
                    'Тип' => 'Клещи для зачистки проводов',
                    'Сечение кабеля' => '0,05–3,5 mm²',
                    'Диапазон AWG' => 'AWG 30–12',
                    'Максимальный диаметр провода' => '5 mm',
                    'Вес' => '209 g',
                ],
                'clesti-electrician-si-cabluri',
                'https://www.kingtony.com/product/Wire-Stripper-67A1-07',
                weight: '0,209 kg'
            ),
            '67F1-08US' => $this->record(
                'Кримпер для модульных разъёмов King Tony 67F1-08US, 6P/8P, 8″',
                'Clește de sertizat conectori modulari King Tony 67F1-08US, 6P/8P, 8″',
                'Многофункциональный кримпер King Tony 67F1-08US для резки, зачистки и обжима модульных разъёмов 6P и 8P.',
                'Clește multifuncțional King Tony 67F1-08US pentru tăiere, dezizolare și sertizarea conectorilor modulari 6P și 8P.',
                'Инструмент объединяет резак, стриппер и обжимные матрицы для модульных разъёмов 6P и 8P. Рукоятки выполнены из PP+TPR. Длина — 8″ / 203 мм, масса — 370 г.',
                'Scula combină funcțiile de tăiere, dezizolare și sertizare pentru conectori modulari 6P și 8P. Mânerele sunt fabricate din PP+TPR. Lungimea este de 8″ / 203 mm, iar greutatea de 370 g.',
                [
                    'Тип' => 'Кримпер для модульных разъёмов',
                    'Совместимые разъёмы' => '6P / 8P',
                    'Функции' => 'Резка / зачистка / обжим',
                    'Материал рукоятки' => 'PP+TPR',
                    'Длина' => '8 inch / 203 mm',
                    'Вес' => '370 g',
                ],
                'clesti-electrician-si-cabluri',
                'https://www.kingtony.com/product/Modular-Crimping-Tool-67F1-08',
                weight: '0,370 kg'
            ),
            '74010' => $this->record(
                'Левые авиационные ножницы King Tony 74010, 248 мм',
                'Foarfecă de tablă pentru tăiere la stânga King Tony 74010, 248 mm',
                'Рычажные авиационные ножницы King Tony 74010 для левого реза листовой стали и нержавеющей стали.',
                'Foarfecă de tablă cu pârghie King Tony 74010 pentru tăiere la stânga a oțelului și inoxului.',
                'Кованые лезвия из хром-молибденовой стали имеют зубчатые режущие кромки твёрдостью 59–62 HRC. Максимальная толщина прокатной стали — 1,2 мм, нержавеющей стали — 0,7 мм. Общая длина — 248 мм, длина лезвия — 35 мм, масса — 375 г.',
                'Lamele forjate din oțel crom-molibden au muchii zimțate cu duritatea de 59–62 HRC. Grosimea maximă este de 1,2 mm pentru oțel laminat și 0,7 mm pentru inox. Lungimea totală este de 248 mm, lama are 35 mm, iar greutatea este de 375 g.',
                [
                    'Тип' => 'Авиационные ножницы',
                    'Направление реза' => 'Левое',
                    'Материал' => 'Хром-молибденовая сталь',
                    'Максимальная толщина стали' => '1,2 mm',
                    'Максимальная толщина нержавеющей стали' => '0,7 mm',
                    'Твёрдость режущих кромок' => '59–62 HRC',
                    'Длина' => '248 mm',
                    'Длина лезвия' => '35 mm',
                    'Вес' => '375 g',
                ],
                'taiere-pilire-prelucrare',
                'https://www.kingtony.com/product/Aviation-Tin-Snips-Left-Cut-74010',
                weight: '0,375 kg'
            ),
            '74020' => $this->record(
                'Правые авиационные ножницы King Tony 74020, 248 мм',
                'Foarfecă de tablă pentru tăiere la dreapta King Tony 74020, 248 mm',
                'Рычажные авиационные ножницы King Tony 74020 для правого реза листовой стали и нержавеющей стали.',
                'Foarfecă de tablă cu pârghie King Tony 74020 pentru tăiere la dreapta a oțelului și inoxului.',
                'Кованые лезвия из хром-молибденовой стали имеют зубчатые режущие кромки твёрдостью 59–62 HRC. Максимальная толщина прокатной стали — 1,2 мм, нержавеющей стали — 0,7 мм. Общая длина — 248 мм, длина лезвия — 35 мм, масса — 375 г.',
                'Lamele forjate din oțel crom-molibden au muchii zimțate cu duritatea de 59–62 HRC. Grosimea maximă este de 1,2 mm pentru oțel laminat și 0,7 mm pentru inox. Lungimea totală este de 248 mm, lama are 35 mm, iar greutatea este de 375 g.',
                [
                    'Тип' => 'Авиационные ножницы',
                    'Направление реза' => 'Правое',
                    'Материал' => 'Хром-молибденовая сталь',
                    'Максимальная толщина стали' => '1,2 mm',
                    'Максимальная толщина нержавеющей стали' => '0,7 mm',
                    'Твёрдость режущих кромок' => '59–62 HRC',
                    'Длина' => '248 mm',
                    'Длина лезвия' => '35 mm',
                    'Вес' => '375 g',
                ],
                'taiere-pilire-prelucrare',
                'https://www.kingtony.com/product/Aviation-Tin-Snips-Right-Cut-74020',
                weight: '0,375 kg'
            ),
            '7912-23' => $this->record(
                'Труборез с храповым механизмом King Tony 7912-23, 28–67 мм',
                'Tăietor de țevi cu clichet King Tony 7912-23, 28–67 mm',
                'Труборез King Tony 7912-23 с храповым механизмом для труб диаметром 28–67 мм и работы в ограниченном пространстве.',
                'Tăietor de țevi King Tony 7912-23 cu mecanism cu clichet pentru diametre de 28–67 mm și lucru în spații înguste.',
                'Храповый механизм позволяет резать трубу без полного оборота инструмента. Труборез подходит для нержавеющей стали, стальных трубопроводов, меди и алюминия; в комплект входит запасное лезвие 7912-2311. Масса — 1122,6 г.',
                'Mecanismul cu clichet permite tăierea fără rotirea completă a sculei. Tăietorul este potrivit pentru inox, conducte din oțel, cupru și aluminiu; este inclusă lama de rezervă 7912-2311. Greutatea este de 1122,6 g.',
                [
                    'Тип' => 'Труборез с храповым механизмом',
                    'Диапазон труб' => '28–67 mm',
                    'Механизм' => 'Храповый',
                    'Поддерживаемые материалы' => 'Нержавеющая сталь / сталь / медь / алюминий',
                    'Запасное лезвие' => '7912-2311',
                    'Вес' => '1122,6 g',
                ],
                'scule-pentru-tevi',
                'https://www.kingtony.com/product/Ratchet-Tubing-Cutter-for-Stainless-Steel-28~67mm-7912-23',
                weight: '1,1226 kg'
            ),
            '7CA15-10M' => $this->record(
                'Ручной трубогиб King Tony 7CA15-10M, 90°, 5/6/8/10 мм',
                'Dispozitiv manual de îndoit țevi King Tony 7CA15-10M, 90°, 5/6/8/10 mm',
                'Четырёхразмерный ручной трубогиб King Tony 7CA15-10M для гибки труб из латуни, меди и алюминия под углом до 90°.',
                'Dispozitiv manual King Tony 7CA15-10M, cu patru dimensiuni, pentru îndoirea la 90° a țevilor din alamă, cupru și aluminiu.',
                'Конструкция 4-в-1 рассчитана на наружные диаметры 3/16″, 1/4″, 5/16″ и 3/8″, соответствующие 5, 6, 8 и 10 мм. Максимальная толщина стенки трубы — 0,9 мм. Длина инструмента — 10-7/8″ / 275 мм, масса — 958 г.',
                'Construcția 4-în-1 acceptă diametre exterioare de 3/16″, 1/4″, 5/16″ și 3/8″, respectiv 5, 6, 8 și 10 mm. Grosimea maximă a peretelui este de 0,9 mm. Lungimea sculei este de 10-7/8″ / 275 mm, iar greutatea de 958 g.',
                [
                    'Тип' => 'Ручной трубогиб',
                    'Угол гибки' => '90°',
                    'Размеры труб' => '3/16 / 1/4 / 5/16 / 3/8 inch; 5 / 6 / 8 / 10 mm',
                    'Поддерживаемые материалы' => 'Латунь / медь / алюминий',
                    'Максимальная толщина стенки' => '0,9 mm',
                    'Длина' => '10-7/8 inch / 275 mm',
                    'Вес' => '958 g',
                ],
                'scule-pentru-tevi',
                'https://www.kingtony.com/product/90˚-Tube-Bender-7CA15-10M',
                weight: '0,958 kg'
            ),
        ];
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
        ?string $imageUrl = null,
        ?string $weight = null,
        ?string $dimensions = null,
        bool $clearImage = false
    ): array {
        return compact(
            'nameRu', 'nameRo', 'shortRu', 'shortRo', 'descriptionRu', 'descriptionRo',
            'attributes', 'category', 'sourceUrl', 'imageUrl', 'weight', 'dimensions', 'clearImage'
        );
    }

    private function updateProduct(object $product, array $content, ?int $categoryId): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sourceDomain = parse_url($content['sourceUrl'], PHP_URL_HOST);
        $existingImageUrls = (! $content['clearImage'] && $product->source_parser_item_id)
            ? DB::table('product_parser_image_assets')
                ->where('parser_item_id', $product->source_parser_item_id)
                ->pluck('source_url')
                ->all()
            : [];
        $sourceUrls = array_values(array_unique(array_filter([
            $content['sourceUrl'],
            $content['imageUrl'],
            ...$existingImageUrls,
        ])));
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

        if ($content['clearImage']) {
            $updates = array_replace($updates, [
                'status' => 'draft',
                'main_image' => null,
                'gallery' => json_encode([]),
                'needs_image_review' => true,
                'needs_review' => true,
            ]);
        }

        DB::table('products')->where('id', $product->id)->update($updates);

        if ($categoryId) {
            $this->syncCategory($product, $categoryId, $content['category'], $now);
        }

        if (! $product->source_parser_item_id) {
            return;
        }

        $parserUpdates = [
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
            'updated_at' => $now,
        ];

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

        if ($content['clearImage']) {
            $parserUpdates = array_replace($parserUpdates, [
                'found_images_json' => json_encode([$content['imageUrl']], JSON_UNESCAPED_SLASHES),
                'selected_images_json' => json_encode([$content['imageUrl']], JSON_UNESCAPED_SLASHES),
                'processed_images_json' => json_encode([], JSON_UNESCAPED_SLASHES),
                'image_source_type' => 'official_manufacturer',
                'needs_image_review' => true,
                'image_reviewed_at' => null,
            ]);
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update($parserUpdates);
        $this->syncOfficialSource((int) $product->source_parser_item_id, $product->sku, $content, $now);

        if ($content['clearImage'] && $content['imageUrl']) {
            $this->replaceWrongImage((int) $product->source_parser_item_id, $content['imageUrl'], $now);
        }
    }

    private function syncOfficialSource(int $parserItemId, string $sku, array $content, object $now): void
    {
        if ($content['clearImage']) {
            DB::table('product_parser_sources')
                ->where('parser_item_id', $parserItemId)
                ->where('url', '!=', $content['sourceUrl'])
                ->delete();
        }

        DB::table('product_parser_sources')->updateOrInsert(
            ['parser_item_id' => $parserItemId, 'url' => $content['sourceUrl']],
            [
                'domain' => parse_url($content['sourceUrl'], PHP_URL_HOST),
                'title' => $content['nameRo'],
                'snippet' => 'Official manufacturer source verified by exact SKU.',
                'source_type' => 'official_manufacturer',
                'confidence_score' => 100,
                'raw_data_json' => json_encode(['sku' => $sku, 'brand' => 'King Tony'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function replaceWrongImage(int $parserItemId, string $imageUrl, object $now): void
    {
        DB::table('product_parser_image_assets')
            ->where('parser_item_id', $parserItemId)
            ->update([
                'is_selected' => false,
                'is_main' => false,
                'needs_review' => true,
                'updated_at' => $now,
            ]);

        DB::table('product_parser_image_assets')->updateOrInsert(
            ['parser_item_id' => $parserItemId, 'source_url' => $imageUrl],
            [
                'source_domain' => parse_url($imageUrl, PHP_URL_HOST),
                'original_path' => null,
                'processed_path' => null,
                'preview_path' => null,
                'thumb_path' => null,
                'width' => null,
                'height' => null,
                'mime_type' => null,
                'status' => 'found',
                'is_selected' => true,
                'is_main' => true,
                'has_watermark' => false,
                'background_removed' => false,
                'background_removal_failed' => false,
                'needs_review' => false,
                'error_message' => null,
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
            'taxonomy_version' => 'verified-2026-07-21',
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

    private function ensurePipeToolsCategory(): void
    {
        $parentId = DB::table('categories')->where('slug', 'taiere-pilire-prelucrare')->value('id');
        if (! $parentId) {
            return;
        }

        $now = now();
        $values = [
            'parent_id' => $parentId,
            'name' => 'Инструменты для труб',
            'name_ro' => 'Scule pentru țevi',
            'description' => 'Ручные инструменты для резки, гибки и обработки металлических труб.',
            'description_ro' => 'Scule manuale pentru tăierea, îndoirea și prelucrarea țevilor metalice.',
            'is_active' => true,
            'is_assignable' => true,
            'is_menu_visible' => true,
            'source' => 'curated',
            'taxonomy_version' => 'verified-2026-07-21',
            'updated_at' => $now,
        ];

        if (DB::table('categories')->where('slug', 'scule-pentru-tevi')->exists()) {
            DB::table('categories')->where('slug', 'scule-pentru-tevi')->update($values);

            return;
        }

        DB::table('categories')->insert($values + [
            'slug' => 'scule-pentru-tevi',
            'sort_order' => 45,
            'created_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Curated SKU content, source evidence, and category decisions are intentionally retained.
    }
};
