<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATALOG_URL = 'https://www.shop.niteh.com/media/Pages_from_GYS_Carbody_HD_1_1.pdf';

    public function up(): void
    {
        $records = $this->records();
        $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');

        DB::transaction(function () use ($records, $products): void {
            foreach ($records as $sku => $content) {
                $product = $products->get($sku);

                if ($product) {
                    $this->updateProduct($product, $content);
                }
            }
        });
    }

    private function records(): array
    {
        return [
            '041561' => $this->record(
                'Приварные кузовные гвозди GYS 041561, Ø 2 мм, 100 штук',
                'Cuie sudabile pentru caroserie GYS 041561, Ø 2 mm, 100 bucăți',
                'GYS 041561 — комплект из 100 кузовных гвоздей диаметром 2 мм. Гвозди привариваются к стальной панели в зоне вмятины и служат точками захвата при вытягивании обратным молотком.',
                'GYS 041561 este un set de 100 de cuie pentru caroserie cu diametrul de 2 mm. Cuiele se sudează pe panoul din oțel în zona deformării și formează puncte de prindere pentru tragerea cu ciocanul culisant.',
                ['Тип' => 'Приварные кузовные гвозди', 'Диаметр' => '2 mm', 'Количество предметов' => '100', 'Применение' => 'Вытягивание вмятин на стальных панелях']
            ),
            '048126' => $this->record(
                'Алюминиево-кремниевые шпильки GYS 048126 AlSi12, Ø 5 × 12 мм, 100 штук',
                'Știfturi din aluminiu-siliciu GYS 048126 AlSi12, Ø 5 × 12 mm, 100 bucăți',
                'GYS 048126 — комплект из 100 приварных шпилек AlSi12 диаметром 5 мм и длиной 12 мм для рихтовки алюминиевых кузовных панелей.',
                'GYS 048126 este un set de 100 de știfturi sudabile AlSi12, cu diametrul de 5 mm și lungimea de 12 mm, pentru îndreptarea panourilor de caroserie din aluminiu.',
                ['Тип' => 'Приварные шпильки', 'Материал' => 'AlSi12', 'Диаметр' => '5 mm', 'Длина' => '12 mm', 'Количество предметов' => '100', 'Применение' => 'Ремонт алюминиевых кузовных панелей']
            ),
            '048140' => $this->record(
                'Алюминиево-магниевые шпильки GYS 048140 AlMg3, Ø 5 × 12 мм, 100 штук',
                'Știfturi din aluminiu-magneziu GYS 048140 AlMg3, Ø 5 × 12 mm, 100 bucăți',
                'GYS 048140 — комплект из 100 приварных шпилек AlMg3 диаметром 5 мм и длиной 12 мм для рихтовки алюминиевых кузовных панелей.',
                'GYS 048140 este un set de 100 de știfturi sudabile AlMg3, cu diametrul de 5 mm și lungimea de 12 mm, pentru îndreptarea panourilor de caroserie din aluminiu.',
                ['Тип' => 'Приварные шпильки', 'Материал' => 'AlMg3', 'Диаметр' => '5 mm', 'Длина' => '12 mm', 'Количество предметов' => '100', 'Применение' => 'Ремонт алюминиевых кузовных панелей']
            ),
            '048157' => $this->record(
                'Патрон для алюминиевых шпилек GYS 048157, Ø 5 мм',
                'Mandrină pentru știfturi din aluminiu GYS 048157, Ø 5 mm',
                'Патрон GYS 048157 предназначен для удержания алюминиевых приварных шпилек диаметром 5 мм в пистолете для кузовного ремонта.',
                'Mandrina GYS 048157 este destinată fixării știfturilor sudabile din aluminiu cu diametrul de 5 mm în pistolul pentru reparații de caroserie.',
                ['Тип' => 'Патрон для шпилек', 'Диаметр' => '5 mm', 'Количество предметов' => '1', 'Применение' => 'Пистолет для ремонта алюминия']
            ),
            '049277' => $this->record(
                'Электрод GYS 049277 для гвоздей Ø 2 мм и шпилек M4',
                'Electrod GYS 049277 pentru cuie Ø 2 mm și știfturi M4',
                'Электрод GYS 049277 используется со споттером для приварки кузовных гвоздей диаметром 2 мм и резьбовых шпилек M4.',
                'Electrodul GYS 049277 se utilizează cu un spotter pentru sudarea cuielor de caroserie cu diametrul de 2 mm și a știfturilor filetate M4.',
                ['Тип' => 'Электрод для споттера', 'Совместимость' => 'Гвозди Ø 2 mm / шпильки M4', 'Количество предметов' => '1', 'Применение' => 'Кузовной ремонт стали']
            ),
            '049383' => $this->record(
                'Резьбовые шпильки GYS 049383 M5 × 18 мм, 100 штук',
                'Știfturi filetate GYS 049383 M5 × 18 mm, 100 bucăți',
                'GYS 049383 — комплект из 100 приварных резьбовых шпилек M5 длиной 18 мм для вытягивания и ремонта стальных кузовных панелей.',
                'GYS 049383 este un set de 100 de știfturi sudabile filetate M5, cu lungimea de 18 mm, pentru tragerea și repararea panourilor de caroserie din oțel.',
                ['Тип' => 'Приварные резьбовые шпильки', 'Резьба' => 'M5', 'Длина' => '18 mm', 'Количество предметов' => '100', 'Применение' => 'Кузовной ремонт стали']
            ),
            '049413' => $this->record(
                'Самонарезающие шпильки GYS 049413, Ø 5 × 12 мм, 100 штук',
                'Știfturi autofiletante GYS 049413, Ø 5 × 12 mm, 100 bucăți',
                'GYS 049413 — комплект из 100 приварных самонарезающих шпилек диаметром 5 мм и длиной 12 мм для ремонта стальных кузовных панелей.',
                'GYS 049413 este un set de 100 de știfturi sudabile autofiletante, cu diametrul de 5 mm și lungimea de 12 mm, pentru repararea panourilor de caroserie din oțel.',
                ['Тип' => 'Приварные самонарезающие шпильки', 'Диаметр' => '5 mm', 'Длина' => '12 mm', 'Количество предметов' => '100', 'Применение' => 'Кузовной ремонт стали']
            ),
            '049420' => $this->record(
                'Самонарезающие шпильки GYS 049420, Ø 5 × 18 мм, 100 штук',
                'Știfturi autofiletante GYS 049420, Ø 5 × 18 mm, 100 bucăți',
                'GYS 049420 — комплект из 100 приварных самонарезающих шпилек диаметром 5 мм и длиной 18 мм для ремонта стальных кузовных панелей.',
                'GYS 049420 este un set de 100 de știfturi sudabile autofiletante, cu diametrul de 5 mm și lungimea de 18 mm, pentru repararea panourilor de caroserie din oțel.',
                ['Тип' => 'Приварные самонарезающие шпильки', 'Диаметр' => '5 mm', 'Длина' => '18 mm', 'Количество предметов' => '100', 'Применение' => 'Кузовной ремонт стали']
            ),
            '049444' => $this->record(
                'Омеднённые кольца GYS 049444, Ø 8 × 16 мм, 100 штук',
                'Inele cuprate GYS 049444, Ø 8 × 16 mm, 100 bucăți',
                'GYS 049444 — комплект из 100 омеднённых колец размером 8 × 16 мм. Кольца привариваются к стальной панели и используются как точки захвата при вытягивании вмятин.',
                'GYS 049444 este un set de 100 de inele cuprate de 8 × 16 mm. Inelele se sudează pe panoul din oțel și sunt utilizate ca puncte de prindere la tragerea deformărilor.',
                ['Тип' => 'Приварные кольца', 'Размер' => '8 × 16 mm', 'Покрытие' => 'Медь', 'Количество предметов' => '100', 'Применение' => 'Вытягивание вмятин']
            ),
            '049482' => $this->record(
                'Патрон для кузовных звёзд GYS 049482 к обратному молотку',
                'Mandrină pentru stele de tinichigerie GYS 049482, pentru ciocan culisant',
                'Патрон GYS 049482 устанавливается на обратный молоток и удерживает приварные кузовные звёзды при вытягивании вмятин.',
                'Mandrina GYS 049482 se montează pe ciocanul culisant și fixează stelele sudabile pentru tragerea deformărilor caroseriei.',
                ['Тип' => 'Патрон для обратного молотка', 'Совместимость' => 'Приварные кузовные звёзды', 'Количество предметов' => '1', 'Применение' => 'Вытягивание вмятин']
            ),
            '049574' => $this->record(
                'Электрод для колец GYS 049574, Ø 16 мм, кольца 8 × 16 мм',
                'Electrod pentru inele GYS 049574, Ø 16 mm, inele 8 × 16 mm',
                'Электрод GYS 049574 диаметром 16 мм предназначен для приварки кузовных колец размером 8 × 16 мм с помощью споттера.',
                'Electrodul GYS 049574 cu diametrul de 16 mm este destinat sudării inelelor de caroserie de 8 × 16 mm cu ajutorul unui spotter.',
                ['Тип' => 'Электрод для споттера', 'Диаметр' => '16 mm', 'Размер' => 'Кольца 8 × 16 mm', 'Количество предметов' => '1', 'Применение' => 'Приварка кузовных колец']
            ),
            '049598' => $this->record(
                'Электрод GYS 049598 для шпилек Ø 5 мм, M5–M6',
                'Electrod GYS 049598 pentru știfturi Ø 5 mm, M5–M6',
                'Электрод GYS 049598 предназначен для приварки резьбовых кузовных шпилек диаметром 5 мм с резьбой M5 или M6.',
                'Electrodul GYS 049598 este destinat sudării știfturilor filetate pentru caroserie cu diametrul de 5 mm și filet M5 sau M6.',
                ['Тип' => 'Электрод для споттера', 'Диаметр' => '5 mm', 'Резьба' => 'M5 / M6', 'Количество предметов' => '1', 'Применение' => 'Приварка резьбовых шпилек']
            ),
            '049666' => $this->record(
                'Магнитная масса GYS 049666 для кузовного споттера',
                'Masă magnetică GYS 049666 pentru spotter de caroserie',
                'Магнитная масса GYS 049666 обеспечивает быстрое подключение обратного кабеля к стальной кузовной панели без обычного зажима.',
                'Masa magnetică GYS 049666 permite conectarea rapidă a cablului de retur la panoul de caroserie din oțel, fără utilizarea unei cleme obișnuite.',
                ['Тип' => 'Магнитная масса', 'Количество предметов' => '1', 'Применение' => 'Подключение массы кузовного споттера', 'Материал' => 'Сталь']
            ),
            '049727' => $this->record(
                'Омеднённая волнистая проволока GYS 049727, 50 штук',
                'Sârmă ondulată cuprată GYS 049727, 50 bucăți',
                'GYS 049727 — комплект из 50 отрезков омеднённой волнистой проволоки для приварки к стальной панели и распределённого вытягивания вмятин гребёнкой или обратным молотком.',
                'GYS 049727 este un set de 50 de segmente de sârmă ondulată cuprată, destinate sudării pe panoul din oțel și tragerii distribuite a deformărilor cu pieptenele sau ciocanul culisant.',
                ['Тип' => 'Волнистая проволока для споттера', 'Покрытие' => 'Медь', 'Количество предметов' => '50', 'Применение' => 'Вытягивание вмятин на стальных панелях']
            ),
            '049789' => $this->record(
                'Электрод GYS 049789 для приварки волнистой проволоки',
                'Electrod GYS 049789 pentru sudarea sârmei ondulate',
                'Электрод GYS 049789 применяется со споттером для приварки волнистой проволоки к стальным кузовным панелям.',
                'Electrodul GYS 049789 se utilizează cu un spotter pentru sudarea sârmei ondulate pe panourile de caroserie din oțel.',
                ['Тип' => 'Электрод для споттера', 'Совместимость' => 'Волнистая проволока', 'Количество предметов' => '1', 'Применение' => 'Кузовной ремонт стали']
            ),
            '050013' => $this->record(
                'Электрод для магнитной массы GYS 050013',
                'Electrod pentru masa magnetică GYS 050013',
                'Электрод GYS 050013 является сменной рабочей частью магнитной массы кузовного споттера.',
                'Electrodul GYS 050013 este piesa de lucru înlocuibilă a masei magnetice pentru spotterul de caroserie.',
                ['Тип' => 'Электрод для магнитной массы', 'Количество предметов' => '1', 'Применение' => 'Магнитная масса кузовного споттера']
            ),
            '050273' => $this->record(
                'Алюминиево-магниевые винты GYS 050273 AlMg, M4 × 12 мм, 200 штук',
                'Șuruburi din aluminiu-magneziu GYS 050273 AlMg, M4 × 12 mm, 200 bucăți',
                'GYS 050273 — комплект из 200 приварных винтов AlMg с резьбой M4 и длиной 12 мм для ремонта алюминиевых кузовных панелей.',
                'GYS 050273 este un set de 200 de șuruburi sudabile AlMg cu filet M4 și lungimea de 12 mm, pentru repararea panourilor de caroserie din aluminiu.',
                ['Тип' => 'Приварные винты', 'Материал' => 'AlMg', 'Резьба' => 'M4', 'Длина' => '12 mm', 'Количество предметов' => '200', 'Применение' => 'Ремонт алюминиевых кузовных панелей']
            ),
            '050280' => $this->record(
                'Алюминиево-кремниевые винты GYS 050280 AlSi, M4 × 12 мм, 200 штук',
                'Șuruburi din aluminiu-siliciu GYS 050280 AlSi, M4 × 12 mm, 200 bucăți',
                'GYS 050280 — комплект из 200 приварных винтов AlSi с резьбой M4 и длиной 12 мм для ремонта алюминиевых кузовных панелей.',
                'GYS 050280 este un set de 200 de șuruburi sudabile AlSi cu filet M4 și lungimea de 12 mm, pentru repararea panourilor de caroserie din aluminiu.',
                ['Тип' => 'Приварные винты', 'Материал' => 'AlSi', 'Резьба' => 'M4', 'Длина' => '12 mm', 'Количество предметов' => '200', 'Применение' => 'Ремонт алюминиевых кузовных панелей']
            ),
            '050297' => $this->record(
                'Резьбовые кольца GYS 050297 для алюминиевых винтов M4, 5 штук',
                'Inele filetate GYS 050297 pentru șuruburi din aluminiu M4, 5 bucăți',
                'GYS 050297 — комплект из пяти многоразовых резьбовых колец диаметром 4 мм для установки на приварные алюминиевые винты M4 и последующего вытягивания панели.',
                'GYS 050297 este un set de cinci inele filetate reutilizabile, cu diametrul de 4 mm, pentru montarea pe șuruburi sudabile din aluminiu M4 și tragerea ulterioară a panoului.',
                ['Тип' => 'Резьбовые вытяжные кольца', 'Диаметр' => '4 mm', 'Резьба' => 'M4', 'Количество предметов' => '5', 'Применение' => 'Ремонт алюминиевых кузовных панелей']
            ),
            '050310' => $this->record(
                'Роликовый электрод GYS 050310 для шовной сварки, Ø 16 мм',
                'Electrod cu rolă GYS 050310 pentru sudare în cusătură, Ø 16 mm',
                'Роликовый электрод GYS 050310 диаметром 16 мм предназначен для шовной сварки с совместимым кузовным споттером.',
                'Electrodul cu rolă GYS 050310, cu diametrul de 16 mm, este destinat sudării în cusătură cu un spotter de caroserie compatibil.',
                ['Тип' => 'Роликовый электрод', 'Диаметр' => '16 mm', 'Количество предметов' => '1', 'Применение' => 'Шовная сварка']
            ),
            '050631' => $this->record(
                'Скрученные вытяжные кольца GYS 050631, 50 штук',
                'Inele răsucite pentru tragere GYS 050631, 50 bucăți',
                'GYS 050631 — комплект из 50 скрученных приварных колец для вытягивания угловых участков и рёбер жёсткости стальных кузовных панелей.',
                'GYS 050631 este un set de 50 de inele sudabile răsucite pentru tragerea zonelor de colț și a nervurilor panourilor de caroserie din oțel.',
                ['Тип' => 'Скрученные приварные кольца', 'Исполнение' => 'Скрученное', 'Количество предметов' => '50', 'Применение' => 'Вытягивание углов и рёбер кузовных панелей']
            ),
            '050648' => $this->record(
                'Прямые вытяжные кольца GYS 050648, 100 штук',
                'Inele drepte pentru tragere GYS 050648, 100 bucăți',
                'GYS 050648 — комплект из 100 прямых приварных колец для вытягивания вмятин на стальных кузовных панелях.',
                'GYS 050648 este un set de 100 de inele sudabile drepte pentru tragerea deformărilor panourilor de caroserie din oțel.',
                ['Тип' => 'Прямые приварные кольца', 'Исполнение' => 'Прямое', 'Количество предметов' => '100', 'Применение' => 'Вытягивание вмятин на стальных панелях']
            ),
            '050655' => $this->record(
                'Электрод для вытяжных колец GYS 050655',
                'Electrod pentru inele de tragere GYS 050655',
                'Электрод GYS 050655 применяется со споттером для приварки прямых и скрученных вытяжных колец к стальным кузовным панелям.',
                'Electrodul GYS 050655 se utilizează cu un spotter pentru sudarea inelelor de tragere drepte și răsucite pe panourile de caroserie din oțel.',
                ['Тип' => 'Электрод для споттера', 'Совместимость' => 'Прямые и скрученные вытяжные кольца', 'Количество предметов' => '1', 'Применение' => 'Кузовной ремонт стали']
            ),
            '050792' => $this->record(
                'Набор вытяжных тяг GYS 050792, 180 / 300 / 500 мм, 3 штуки',
                'Set de tije de tragere GYS 050792, 180 / 300 / 500 mm, 3 bucăți',
                'GYS 050792 — комплект из трёх вытяжных тяг длиной 180, 300 и 500 мм для локальной рихтовки кузовных панелей.',
                'GYS 050792 este un set de trei tije de tragere cu lungimile de 180, 300 și 500 mm, pentru îndreptarea locală a panourilor de caroserie.',
                ['Тип' => 'Вытяжные тяги', 'Количество предметов' => '3', 'Длина' => '180 / 300 / 500 mm', 'Применение' => 'Рихтовка кузовных панелей']
            ),
            '052239' => $this->record(
                'Омеднённые кузовные звёзды GYS 052239, 20 штук',
                'Stele cuprate pentru caroserie GYS 052239, 20 bucăți',
                'GYS 052239 — комплект из 20 омеднённых звёзд, которые привариваются к стальной панели и используются с обратным молотком для вытягивания вмятин.',
                'GYS 052239 este un set de 20 de stele cuprate care se sudează pe panoul din oțel și se utilizează cu ciocanul culisant pentru tragerea deformărilor.',
                ['Тип' => 'Приварные кузовные звёзды', 'Покрытие' => 'Медь', 'Количество предметов' => '20', 'Применение' => 'Вытягивание вмятин']
            ),
            '055438' => $this->record(
                'Резьбовые наконечники GYS 055438 для магнитной массы, 5 штук',
                'Vârfuri filetate GYS 055438 pentru masa magnetică, 5 bucăți',
                'GYS 055438 — комплект из пяти сменных резьбовых наконечников для магнитной массы кузовного споттера.',
                'GYS 055438 este un set de cinci vârfuri filetate de schimb pentru masa magnetică a spotterului de caroserie.',
                ['Тип' => 'Резьбовые наконечники', 'Количество предметов' => '5', 'Совместимость' => 'Магнитная масса GYS', 'Применение' => 'Кузовной споттер']
            ),
        ];
    }

    private function record(string $nameRu, string $nameRo, string $descriptionRu, string $descriptionRo, array $attributes): array
    {
        return compact('nameRu', 'nameRo', 'descriptionRu', 'descriptionRo', 'attributes');
    }

    private function updateProduct(object $product, array $content): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sourceUrls = $this->appendCatalogUrl($product->parser_source_urls ?? null);

        DB::table('products')->where('id', $product->id)->update([
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
            'parser_source_urls' => json_encode($sourceUrls, JSON_UNESCAPED_SLASHES),
            'needs_content_review' => false,
            'generated_content' => false,
            'meta_title' => $content['nameRu'].' | MasterScule.md',
            'meta_description' => mb_substr($content['descriptionRu'], 0, 250),
            'updated_at' => $now,
        ]);

        if (! $product->source_parser_item_id) {
            return;
        }

        $parserItem = DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->first();
        $parserSourceUrls = $this->appendCatalogUrl($parserItem?->source_urls_json);

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
            'content_source_type' => 'manufacturer_catalog',
            'translation_source_type' => 'curated_translation',
            'translation_reviewed_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_parser_sources')->updateOrInsert(
            ['parser_item_id' => $product->source_parser_item_id, 'url' => self::CATALOG_URL],
            [
                'domain' => 'www.shop.niteh.com',
                'title' => 'GYS Car Body catalog — '.$product->sku,
                'snippet' => 'Manufacturer catalog table matched by exact GYS SKU.',
                'source_type' => 'manufacturer_catalog',
                'confidence_score' => 95,
                'raw_data_json' => json_encode(['sku' => $product->sku, 'brand' => 'GYS'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function appendCatalogUrl(?string $json): array
    {
        $urls = json_decode($json ?: '[]', true);

        if (! is_array($urls)) {
            $urls = [];
        }

        $urls[] = self::CATALOG_URL;

        return array_values(array_unique(array_filter($urls)));
    }

    public function down(): void
    {
        // Curated exact-SKU catalog content and source evidence are intentionally retained.
    }
};
