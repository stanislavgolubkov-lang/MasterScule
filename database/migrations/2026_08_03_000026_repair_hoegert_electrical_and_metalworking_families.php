<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $records = [
            'HT1E414' => [
                'name_ru' => 'Светодиодный фонарь HOEGERT HT1E414, 1000 лм, 2000 мА·ч, IPX7',
                'name_ro' => 'Lanternă LED HOEGERT HT1E414, 1000 lm, 2000 mAh, IPX7',
                'description_ru' => 'Лёгкий алюминиевый фонарь HOEGERT HT1E414 с максимальным световым потоком 1000 лм и регулировкой фокуса ZOOM. Имеет четыре режима освещения, влагозащиту IPX7 и литий-ионный аккумулятор 2000 мА·ч с зарядкой через USB-C.',
                'description_ro' => 'Lanterna ușoară din aluminiu HOEGERT HT1E414 oferă un flux luminos maxim de 1000 lm și focalizare reglabilă ZOOM. Dispune de patru moduri de iluminare, protecție IPX7 și acumulator Li-Ion de 2000 mAh, încărcat prin USB-C.',
                'source_url' => 'https://en.hoegert.com/product/flashlight-1000-lm/',
            ],
            'HT1E415' => [
                'name_ru' => 'Светодиодный фонарь HOEGERT HT1E415, 2000 лм, 4500 мА·ч, IPX7',
                'name_ro' => 'Lanternă LED HOEGERT HT1E415, 2000 lm, 4500 mAh, IPX7',
                'description_ru' => 'Алюминиевый фонарь HOEGERT HT1E415 создаёт световой поток до 2000 лм и имеет регулируемый фокус ZOOM. Предусмотрены четыре режима, защита IPX7, функция power bank и аккумулятор Li-Ion 4500 мА·ч с зарядкой USB-C; кабель USB-C/USB-A входит в комплект.',
                'description_ro' => 'Lanterna din aluminiu HOEGERT HT1E415 oferă până la 2000 lm și focalizare reglabilă ZOOM. Are patru moduri, protecție IPX7, funcție power bank și acumulator Li-Ion de 4500 mAh cu încărcare USB-C; cablul USB-C/USB-A este inclus.',
                'source_url' => 'https://en.hoegert.com/product/flashlight-2000-lm/',
            ],
            'HT1E416' => [
                'name_ru' => 'Светодиодный фонарь HOEGERT HT1E416, 3100 лм, 4500 мА·ч, IPX7',
                'name_ro' => 'Lanternă LED HOEGERT HT1E416, 3100 lm, 4500 mAh, IPX7',
                'description_ru' => 'Мощный алюминиевый фонарь HOEGERT HT1E416 выдаёт до 3100 лм и позволяет регулировать световой пучок функцией ZOOM. Имеет четыре режима, влагозащиту IPX7, функцию power bank и аккумулятор Li-Ion 4500 мА·ч с зарядкой USB-C.',
                'description_ro' => 'Lanterna puternică din aluminiu HOEGERT HT1E416 oferă până la 3100 lm și permite reglarea fasciculului prin funcția ZOOM. Are patru moduri, protecție IPX7, funcție power bank și acumulator Li-Ion de 4500 mAh cu încărcare USB-C.',
                'source_url' => 'https://en.hoegert.com/product/flashlight-3100-lm/',
            ],
            'HT2C081' => [
                'name_ru' => 'Аккумуляторный аппарат для ремонта пластика HOEGERT HT2C081, 4 В, 2000 мА·ч',
                'name_ro' => 'Aparat cu acumulator pentru repararea plasticului HOEGERT HT2C081, 4 V, 2000 mAh',
                'description_ru' => 'Аккумуляторный аппарат HOEGERT HT2C081 предназначен для ремонта и усиления пластиковых деталей горячими скобами. Работает от Li-Ion аккумулятора 4 В ёмкостью 2000 мА·ч, нагревается до 550 °C и заряжается через USB-C. В кейсе поставляются режущая и паяльная насадки, зарядное устройство и 300 скоб шести типов.',
                'description_ro' => 'Aparatul cu acumulator HOEGERT HT2C081 este destinat reparării și consolidării pieselor din plastic cu capse fierbinți. Funcționează cu un acumulator Li-Ion de 4 V și 2000 mAh, atinge 550 °C și se încarcă prin USB-C. Setul în cutie include cap de tăiere, vârf de lipit, încărcător și 300 de capse în șase forme.',
                'source_url' => 'https://en.hoegert.com/product/plastic-welding-machine-cordless/',
            ],
            'HT2C082' => [
                'name_ru' => 'Набор скоб для ремонта пластика HOEGERT HT2C082, 300 шт.',
                'name_ro' => 'Set de capse pentru repararea plasticului HOEGERT HT2C082, 300 buc.',
                'description_ru' => 'Набор HOEGERT HT2C082 предназначен для аккумуляторного аппарата HT2C081 и содержит 300 скоб шести типов. В комплект входят большие и малые волнистые скобы толщиной 0,6 и 0,8 мм, а также скобы типов M и V — по 50 штук каждого вида.',
                'description_ro' => 'Setul HOEGERT HT2C082 este destinat aparatului cu acumulator HT2C081 și conține 300 de capse în șase variante. Include capse ondulate mari și mici de 0,6 și 0,8 mm, precum și capse de tip M și V, câte 50 de bucăți din fiecare tip.',
                'source_url' => 'https://en.hoegert.com/product/staple-set-for-plastic-welding-machine-300-pcs/',
            ],
            'HT2C306' => [
                'name_ru' => 'Электрический паяльник HOEGERT HT2C306, 60 Вт',
                'name_ro' => 'Ciocan de lipit electric HOEGERT HT2C306, 60 W',
                'description_ru' => 'Электрический паяльник HOEGERT HT2C306 мощностью 60 Вт предназначен для пайки электрических и электронных компонентов. Оснащён латунным жалом в направляющей из нержавеющей стали, нагревается за 2–3 минуты и имеет двухкомпонентную нескользящую рукоятку. Металлическая подставка входит в комплект.',
                'description_ro' => 'Ciocanul de lipit electric HOEGERT HT2C306 de 60 W este destinat lipirii componentelor electrice și electronice. Are vârf din alamă într-un ghidaj din oțel inoxidabil, se încălzește în 2–3 minute și dispune de mâner bicomponent antiderapant. Suportul metalic este inclus.',
                'source_url' => 'https://en.hoegert.com/product/flask-soldering-iron-60w/',
            ],
            'HT2C312' => [
                'name_ru' => 'Цифровая паяльная станция HOEGERT HT2C312, 60 Вт, 100–500 °C',
                'name_ro' => 'Stație de lipit digitală HOEGERT HT2C312, 60 W, 100–500 °C',
                'description_ru' => 'Паяльная станция HOEGERT HT2C312 мощностью 60 Вт поддерживает цифровую регулировку температуры 100–500 °C с шагом 1 °C и отображает параметры на LCD. Предусмотрены режимы 200/300/400 °C, Auto Sleep через 10 минут, Auto OFF через 30 минут и защита ESD Safe. В комплект входят пять сменных жал и припой.',
                'description_ro' => 'Stația de lipit HOEGERT HT2C312 de 60 W permite reglarea digitală a temperaturii între 100 și 500 °C, cu pas de 1 °C, iar valorile sunt afișate pe LCD. Are presetări la 200/300/400 °C, Auto Sleep după 10 minute, Auto OFF după 30 de minute și protecție ESD Safe. Sunt incluse cinci vârfuri și aliaj de lipit.',
                'source_url' => 'https://en.hoegert.com/product/soldering-kit-with-digital-soldering-station-60w/',
            ],
            'HT2C313' => [
                'name_ru' => 'Паяльная станция 2-в-1 HOEGERT HT2C313, паяльник 75 Вт и термофен 750 Вт',
                'name_ro' => 'Stație de lipit 2-în-1 HOEGERT HT2C313, ciocan 75 W și aer cald 750 W',
                'description_ru' => 'Станция HOEGERT HT2C313 объединяет паяльник 75 Вт и термофен 750 Вт с раздельными LED-дисплеями. Температура паяльника регулируется в диапазоне 200–480 °C, термофена — 100–480 °C; воздушный поток достигает 120 л/мин. Конструкция ESD Safe, в комплекте четыре сопла термофена.',
                'description_ro' => 'Stația HOEGERT HT2C313 combină un ciocan de lipit de 75 W cu un pistol cu aer cald de 750 W și afișaje LED separate. Temperatura ciocanului se reglează între 200–480 °C, iar a aerului între 100–480 °C; debitul ajunge la 120 l/min. Construcția este ESD Safe și include patru duze.',
                'source_url' => 'https://en.hoegert.com/product/2-in-1-hot-air-soldering-station-750w/',
            ],
            'HT3B609' => [
                'name_ru' => 'Моделистские тиски HOEGERT HT3B609, губки 60 мм',
                'name_ro' => 'Menghină pentru modelism HOEGERT HT3B609, fălci de 60 mm',
                'description_ru' => 'Компактные моделистские тиски HOEGERT HT3B609 предназначены для точных работ. Корпус изготовлен из высокопрочного чугуна, закалённые губки шириной 60 мм надёжно удерживают деталь. Тиски крепятся к столу встроенной струбциной.',
                'description_ro' => 'Menghina compactă pentru modelism HOEGERT HT3B609 este destinată lucrărilor de precizie. Corpul este realizat din fontă ductilă, iar fălcile călite de 60 mm fixează sigur piesa. Menghina se montează pe banc cu clema integrată.',
                'source_url' => 'https://en.hoegert.com/product/modelling-vise-60-mm/',
            ],
            'HT3B612' => [
                'name_ru' => 'Поворотные слесарные тиски HOEGERT HT3B612, губки 150 мм',
                'name_ro' => 'Menghină rotativă de banc HOEGERT HT3B612, fălci de 150 mm',
                'description_ru' => 'Слесарные тиски HOEGERT HT3B612 с губками 150 мм поворачиваются на 360° и фиксируются в выбранном положении. Корпус выполнен из высокопрочного чугуна, губки и резьба зажимного механизма закалены, металлические элементы защищены хромированием. Основание крепится четырьмя винтами.',
                'description_ro' => 'Menghina de banc HOEGERT HT3B612 cu fălci de 150 mm se rotește la 360° și se blochează în poziția aleasă. Corpul este din fontă ductilă, fălcile și filetul mecanismului sunt călite, iar elementele metalice sunt cromate. Baza se fixează cu patru șuruburi.',
                'source_url' => 'https://en.hoegert.com/product/swivel-vise-150-mm/',
            ],
            'HT3B617' => [
                'name_ru' => 'Поворотные слесарные тиски HOEGERT HT3B617, губки 125 мм',
                'name_ro' => 'Menghină rotativă de banc HOEGERT HT3B617, fălci de 125 mm',
                'description_ru' => 'Поворотные тиски HOEGERT HT3B617 с губками 125 мм предназначены для слесарных, ремонтных и строительных работ. Прочный чугунный корпус снабжён наковальней, закалённой резьбой и стальными губками с перекрёстной насечкой. Основание поворачивается на 360° и фиксируется двумя болтами.',
                'description_ro' => 'Menghina rotativă HOEGERT HT3B617 cu fălci de 125 mm este destinată lucrărilor de atelier, reparații și construcții. Corpul robust din fontă are nicovală, filet călit și fălci din oțel cu striații încrucișate. Baza se rotește la 360° și se fixează cu două șuruburi.',
                'source_url' => 'https://en.hoegert.com/wp-content/uploads/2021/09/HT3B617-619_EN.pdf',
            ],
            'HT3B619' => [
                'name_ru' => 'Поворотные слесарные тиски HOEGERT HT3B619, губки 200 мм',
                'name_ro' => 'Menghină rotativă de banc HOEGERT HT3B619, fălci de 200 mm',
                'description_ru' => 'Поворотные тиски HOEGERT HT3B619 с губками 200 мм рассчитаны на тяжёлые слесарные, ремонтные и строительные работы. Чугунный корпус снабжён наковальней, закалённой резьбой и стальными губками с перекрёстной насечкой. Основание поворачивается на 360° и фиксируется двумя болтами.',
                'description_ro' => 'Menghina rotativă HOEGERT HT3B619 cu fălci de 200 mm este destinată lucrărilor grele de atelier, reparații și construcții. Corpul din fontă are nicovală, filet călit și fălci din oțel cu striații încrucișate. Baza se rotește la 360° și se fixează cu două șuruburi.',
                'source_url' => 'https://en.hoegert.com/product/swivel-bench-vise-200-mm/',
            ],
            'HT3B802' => [
                'name_ru' => 'Круглый напильник по металлу HOEGERT HT3B802, 200 мм, насечка №2',
                'name_ro' => 'Pilă rotundă pentru metal HOEGERT HT3B802, 200 mm, tăietură nr. 2',
                'description_ru' => 'Круглый напильник HOEGERT HT3B802 длиной 200 мм предназначен для ручной обработки металла и твёрдых синтетических материалов. Средняя насечка №2 подходит для обработки криволинейных поверхностей и крупных выемок в промышленности и мастерских.',
                'description_ro' => 'Pila rotundă HOEGERT HT3B802 de 200 mm este destinată prelucrării manuale a metalului și a materialelor sintetice dure. Tăietura medie nr. 2 este potrivită pentru suprafețe curbe și degajări mari, în industrie și ateliere.',
                'source_url' => 'https://en.hoegert.com/product/round-metal-file/',
            ],
            'HT6D190' => [
                'name_ru' => 'Набор зенкеров по металлу HOEGERT HT6D190, HSS 4241, 6 шт.',
                'name_ro' => 'Set de teșitoare pentru metal HOEGERT HT6D190, HSS 4241, 6 buc.',
                'description_ru' => 'Набор HOEGERT HT6D190 содержит шесть зенкеров из стали HSS 4241 с титановым покрытием: 6,3; 8,3; 10,4; 12,4; 16,5 и 20,5 мм. Угол резания 45° и три лезвия обеспечивают аккуратное снятие фасок, удаление заусенцев и выравнивание кромок при работе дрелью или шуруповёртом.',
                'description_ro' => 'Setul HOEGERT HT6D190 conține șase teșitoare din oțel HSS 4241 cu acoperire din titan: 6,3; 8,3; 10,4; 12,4; 16,5 și 20,5 mm. Unghiul de 45° și cele trei tăișuri permit teșirea, debavurarea și uniformizarea precisă a marginilor cu mașina de găurit sau înșurubat.',
                'source_url' => 'https://hoegert.com/wp-content/uploads/2024/01/HT6D190_EN.pdf',
            ],
            'HT6D322' => [
                'name_ru' => 'Ступенчатое сверло HOEGERT HT6D322, 4–20 мм, HSS 4241, TiN',
                'name_ro' => 'Burghiu în trepte HOEGERT HT6D322, 4–20 mm, HSS 4241, TiN',
                'description_ru' => 'Ступенчатое сверло HOEGERT HT6D322 из закалённой стали HSS 4241 с покрытием нитридом титана выполняет отверстия диаметром 4–20 мм. Девять ступеней, две прямые режущие канавки, угол 118° и функция автоматического снятия заусенцев обеспечивают точное сверление материалов толщиной до 4 мм. Хвостовик — 1/4″ HEX, стандарт DIN 1412C.',
                'description_ro' => 'Burghiul în trepte HOEGERT HT6D322 din oțel HSS 4241 călit, acoperit cu nitrură de titan, execută găuri de 4–20 mm. Cele nouă trepte, două canale drepte, unghiul de 118° și debavurarea automată asigură găurirea precisă a materialelor de până la 4 mm. Prindere 1/4″ HEX, conform DIN 1412C.',
                'source_url' => 'https://en.hoegert.com/product/step-drill-4-20-mm/',
            ],
            'HT6D579' => [
                'name_ru' => 'Набор твердосплавных борфрез по металлу HOEGERT HT6D579, 8 шт.',
                'name_ro' => 'Set de freze rotative din carbură HOEGERT HT6D579, 8 buc.',
                'description_ru' => 'Набор HOEGERT HT6D579 включает восемь борфрез из карбида вольфрама для обработки стали, алюминия, чугуна, пластика, камня и дерева. Рабочие профили B, F, E, D, A, C, D и G имеют размеры 10×9–20 мм, хвостовик 6 мм. Допустимая частота вращения — до 19 000 об/мин.',
                'description_ro' => 'Setul HOEGERT HT6D579 include opt freze rotative din carbură de tungsten pentru oțel, aluminiu, fontă, plastic, piatră și lemn. Profilele B, F, E, D, A, C, D și G au părți active de 10×9–20 mm și tijă de 6 mm. Turația admisă este de până la 19.000 rpm.',
                'source_url' => 'https://ru.hoegert.com/wp-content/uploads/2025/05/HT6D579_EN.pdf',
            ],
            'HT6D580' => [
                'name_ru' => 'Набор твердосплавных борфрез по металлу HOEGERT HT6D580, 3 шт.',
                'name_ro' => 'Set de freze rotative din carbură HOEGERT HT6D580, 3 buc.',
                'description_ru' => 'Набор HOEGERT HT6D580 включает три борфрезы из карбида вольфрама для обработки стали, алюминия, чугуна, пластика, камня и дерева. В комплект входят профили B, C и F с рабочей частью 12×25 мм и хвостовиком 6 мм. Допустимая частота вращения — до 19 000 об/мин.',
                'description_ro' => 'Setul HOEGERT HT6D580 include trei freze rotative din carbură de tungsten pentru oțel, aluminiu, fontă, plastic, piatră și lemn. Sunt incluse profilele B, C și F cu partea activă de 12×25 mm și tijă de 6 mm. Turația admisă este de până la 19.000 rpm.',
                'source_url' => 'https://en.hoegert.com/wp-content/uploads/2025/05/HT6D580_EN.pdf',
            ],
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
            'source_type' => 'official_manufacturer',
            'parser_confidence' => 100,
            'fallback_source_used' => false,
            'source_reviewed_at' => $now,
        ]);

        $parser = $common + [
            'found_title' => $content['name_ru'],
            'found_description' => $content['description_ru'],
            'official_source_url' => $content['source_url'],
            'official_source_domain' => $domain,
            'official_source_confidence' => 100,
            'fallback_source_url' => null,
            'fallback_source_domain' => null,
            'fallback_source_used' => false,
            'source_match_confidence' => 100,
            'content_source_type' => 'official_source',
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
