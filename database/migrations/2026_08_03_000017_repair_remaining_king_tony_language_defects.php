<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $skus = [
            '9BA33', '9BA31', '9BA32', '9AT2107E', '9AT3-F02', '9AT3-A02', '34862-2FG',
            '934-010MRV', '934-010MRV-G', '24110214', '14280204', '4452-24FR', '4311-16F',
            '7913A-63', '9TA261A', '19932427', '19911722', '3611-15HP', '19A03032',
            '19A02729', '19100810', '19A0-15', '6031-10', '45211PP', '33411-050',
            '33111US', '9BK73-14', '9BK73-22', '9AE53-80', '8260-10', '6260-13P',
            '3311-12', '4223-05R', '20317MR', '3611-18HP', '9TH45-XL', '9TH43-XXL',
            '9TH42-XXL', '9TH44-XXL',
        ];

        foreach ($skus as $sku) {
            $data = $this->content($sku);
            if ($data === null) {
                continue;
            }
            $shortRu = Str::limit($data['description_ru'], 220, '');
            $shortRo = Str::limit($data['description_ro'], 220, '');

            DB::table('products')->where('sku', $sku)->update([
                'name' => $data['name_ru'],
                'name_ru' => $data['name_ru'],
                'name_ro' => $data['name_ro'],
                'short_description' => $shortRu,
                'short_description_ru' => $shortRu,
                'short_description_ro' => $shortRo,
                'description' => $data['description_ru'],
                'description_ru' => $data['description_ru'],
                'description_ro' => $data['description_ro'],
                'meta_title' => Str::limit($data['name_ru'].' | MasterScule', 255, ''),
                'meta_description' => Str::limit($shortRu, 155, ''),
                'needs_translation_review' => false,
                'needs_content_review' => false,
                'generated_content' => false,
                'updated_at' => $now,
            ]);
            DB::table('product_parser_items')->where('sku', $sku)->update([
                'name_ru' => $data['name_ru'],
                'name_ro' => $data['name_ro'],
                'short_description_ru' => $shortRu,
                'short_description_ro' => $shortRo,
                'description_ru' => $data['description_ru'],
                'description_ro' => $data['description_ro'],
                'needs_translation_review' => false,
                'needs_content_review' => false,
                'generated_content' => false,
                'translation_source_type' => 'verified_manual_translation',
                'translation_reviewed_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function content(string $sku): ?array
    {
        $bearingSizes = ['9BA31' => ['малый', 'mică'], '9BA32' => ['средний', 'medie'], '9BA33' => ['большой', 'mare']];
        if (isset($bearingSizes[$sku])) {
            [$ru, $ro] = $bearingSizes[$sku];

            return $this->item(
                "Универсальный съёмник подшипников King Tony, {$ru}",
                "Extractor universal de rulmenți King Tony, dimensiune {$ro}",
                "Универсальный съёмник подшипников King Tony {$sku} предназначен для демонтажа внутренних и наружных подшипников. Захваты вводятся в подготовленные диаметрально противоположные отверстия и фиксируются поворотом на 90°, после чего подшипник выпрессовывается силовым винтом.",
                "Extractorul universal de rulmenți King Tony {$sku} este destinat demontării rulmenților interiori și exteriori. Ghearele se introduc în două orificii diametral opuse și se fixează prin rotire la 90°, după care rulmentul este extras cu șurubul de forță.");
        }

        if (preg_match('/^19A0(\d{2})(\d{2})$/', $sku, $match) === 1) {
            $size = (int) $match[1].' × '.(int) $match[2];

            return $this->item(
                "Двусторонний торцевой ключ King Tony {$size} мм",
                "Cheie tubulară dublă King Tony {$size} mm",
                "Двусторонний торцевой ключ King Tony {$sku}, размеры {$size} мм, изготовлен из хромованадиевой стали и имеет хромированное покрытие.",
                "Cheia tubulară dublă King Tony {$sku}, dimensiuni {$size} mm, este fabricată din oțel crom-vanadiu și are finisaj cromat.");
        }

        if (in_array($sku, ['3611-15HP', '3611-18HP'], true)) {
            return $this->item(
                "Разводной ключ King Tony {$sku}",
                "Cheie reglabilă King Tony {$sku}",
                "Разводной ключ японского типа King Tony {$sku} изготовлен из хромованадиевой стали. Полированная, фосфатированная и хромированная поверхность; стандарты DIN 3117 и ANSI/ASME B107.8.",
                "Cheia reglabilă de tip japonez King Tony {$sku} este fabricată din oțel crom-vanadiu. Suprafață lustruită, fosfatată și cromată; conform DIN 3117 și ANSI/ASME B107.8.");
        }

        if (in_array($sku, ['9BK73-14', '9BK73-22'], true)) {
            return $this->item(
                "Инструмент King Tony {$sku} для очистки ступиц",
                "Instrument King Tony {$sku} pentru curățarea butucilor",
                "Инструмент King Tony {$sku} предназначен для удаления коррозии со ступиц, в том числе вокруг основания колёсных шпилек.",
                "Instrumentul King Tony {$sku} este destinat îndepărtării coroziunii de pe butuci, inclusiv din jurul bazei prezoanelor de roată.");
        }

        $gloves = [
            '9TH45-XL' => ['Магнитные рабочие перчатки', 'Mănuși de lucru magnetice', 'XL', 'Прочная ладонь, магнитный указательный палец и магнит на тыльной стороне для временного хранения металлического крепежа. Эластичная манжета регулируется липучкой.', 'Palmă rezistentă, deget arătător magnetic și magnet pe partea dorsală pentru păstrarea temporară a elementelor metalice. Manșetă elastică reglabilă cu velcro.'],
            '9TH43-XXL' => ['Нескользящие рабочие перчатки', 'Mănuși de lucru antiderapante', 'XXL', 'Ладонь с противоскользящим силиконовым покрытием и вставкой EVA снижает вибрацию. Светоотражающая окантовка и регулируемая манжета повышают безопасность и удобство.', 'Palma cu strat de silicon antiderapant și inserție EVA reduce vibrațiile. Bordura reflectorizantă și manșeta reglabilă sporesc siguranța și confortul.'],
            '9TH42-XXL' => ['Антивибрационные рабочие перчатки', 'Mănuși de lucru antivibrații', 'XXL', 'Ладонь из козьей кожи с вставкой EVA обеспечивает комфорт и защиту от вибрации. Предусмотрены совместимые с сенсорным экраном пальцы, дышащая сетка и неопреновая манжета.', 'Palma din piele de capră cu inserție EVA oferă confort și protecție antivibrații. Degetele sunt compatibile cu ecranele tactile; materialul tip plasă este respirabil, iar manșeta este din neopren.'],
            '9TH44-XXL' => ['Утеплённые рабочие перчатки', 'Mănuși de lucru pentru protecție la frig', 'XXL', 'Дышащая ладонь из нубука, амортизирующая вставка, светоотражающая окантовка и утеплитель Thinsulate обеспечивают комфорт при работе в холоде.', 'Palma respirabilă din nubuc, inserția amortizantă, bordura reflectorizantă și căptușeala Thinsulate oferă confort la lucrul în condiții reci.'],
        ];
        if (isset($gloves[$sku])) {
            [$nameRu, $nameRo, $size, $descriptionRu, $descriptionRo] = $gloves[$sku];

            return $this->item("{$nameRu} King Tony, {$size}", "{$nameRo} King Tony, {$size}", $descriptionRu, $descriptionRo);
        }

        return match ($sku) {
            '9AT2107E' => $this->item(
                'Набор фиксаторов ГРМ King Tony 9AT2107E для BMW N47/N57',
                'Set de calare distribuție King Tony 9AT2107E pentru BMW N47/N57',
                'Набор King Tony 9AT2107E предназначен для установки и регулировки фаз ГРМ дизельных двигателей BMW N47, N47S и N57 объёмом 2,0 и 3,0 л. Совместим с соответствующими моделями BMW серий 1, 3, 5, 7, X1, X3, X5 и X6.',
                'Setul King Tony 9AT2107E este destinat calării și reglării distribuției motoarelor diesel BMW N47, N47S și N57 de 2,0 și 3,0 l. Compatibil cu modelele corespunzătoare din seriile BMW 1, 3, 5, 7, X1, X3, X5 și X6.'),
            '9AT3-F02' => $this->item(
                'Стойка King Tony 9AT3-F02 для индикатора положения поршня',
                'Suport King Tony 9AT3-F02 pentru comparatorul poziției pistonului',
                'Стойка King Tony 9AT3-F02 используется совместно с индикатором 9AT3-F01 для определения положения поршня в верхней мёртвой точке на двигателях VAG.',
                'Suportul King Tony 9AT3-F02 se utilizează împreună cu comparatorul 9AT3-F01 pentru determinarea poziției pistonului la punctul mort superior pe motoarele VAG.'),
            '9AT3-A02' => $this->item(
                'Универсальный фиксатор шкива распредвала King Tony 9AT3-A02',
                'Dispozitiv universal de blocare a fuliei arborelui cu came King Tony 9AT3-A02',
                'Фиксатор удерживает шкив распредвала при отворачивании и затяжке центрального болта. Четыре пары штифтов 7, 10, 14 и 16 мм и регулируемые поворотные губки обеспечивают диапазон 40–140 мм.',
                'Dispozitivul menține fulia arborelui cu came la slăbirea sau strângerea șurubului central. Patru perechi de pini de 7, 10, 14 și 16 mm și fălcile pivotante reglabile asigură un interval de 40–140 mm.'),
            '34862-2FG' => $this->item(
                'Динамометрический ключ King Tony 34862-2FG, привод 1″',
                'Cheie dinamometrică King Tony 34862-2FG, antrenare 1″',
                'Регулируемый динамометрический ключ повышенной мощности с приводом 1″ и двойной шкалой Н·м / ft·lb. Работает в обоих направлениях, допуск ±3%; соответствует DIN ISO 6789 и ASME B107.300-2010. Рекомендуется калибровка каждые 12 месяцев или 5000 циклов.',
                'Cheie dinamometrică reglabilă de mare capacitate cu antrenare de 1″ și scară dublă N·m / ft·lb. Funcționează în ambele sensuri, toleranță ±3%; conform DIN ISO 6789 și ASME B107.300-2010. Se recomandă calibrarea la 12 luni sau 5000 de cicluri.'),
            '934-010MRV', '934-010MRV-G' => $this->item(
                "Набор инструментов King Tony {$sku} с тележкой, 286 предметов",
                "Set de scule King Tony {$sku} cu cărucior, 286 piese",
                'Набор из 286 инструментов в семиящичной тележке King Tony 87434-7U предназначен для обслуживания автомобилей Volkswagen Group, Mercedes-Benz, Opel, BMW и японских марок. Формованные трёхцветные EVA-ложементы помогают быстро находить инструмент и контролировать комплектность.',
                'Setul de 286 de scule este livrat în căruciorul King Tony 87434-7U cu șapte sertare și este destinat automobilelor Volkswagen Group, Mercedes-Benz, Opel, BMW și mărcilor japoneze. Inserțiile EVA în trei culori facilitează identificarea și verificarea sculelor.'),
            '24110214' => $this->item(
                'Двусторонняя отвёртка King Tony 24110214',
                'Șurubelniță reversibilă King Tony 24110214',
                'Двусторонняя отвёртка King Tony 24110214 имеет шестигранный стержень из хромованадиевой стали с хромированным покрытием и рукоятку из PP. Рассчитана на работу при температуре до 60 °C.',
                'Șurubelnița reversibilă King Tony 24110214 are tijă hexagonală din oțel crom-vanadiu cu finisaj cromat și mâner din PP. Este destinată lucrului la temperaturi de până la 60 °C.'),
            '14280204' => $this->item(
                'Отвёртка Pozidriv King Tony 14280204',
                'Șurubelniță Pozidriv King Tony 14280204',
                'Отвёртка Pozidriv King Tony 14280204 имеет круглый стержень из стали SNCM+V, хромированное покрытие с чёрным наконечником и двухкомпонентную рукоятку PP+TPR. Соответствует DIN ISO 8764.',
                'Șurubelnița Pozidriv King Tony 14280204 are tijă rotundă din oțel SNCM+V, finisaj cromat cu vârf negru și mâner bicomponent PP+TPR. Conform DIN ISO 8764.'),
            '4452-24FR' => $this->item(
                'Шарнирный вороток King Tony 4452-24FR, 1/2″',
                'Antrenor articulat King Tony 4452-24FR, 1/2″',
                'Шарнирный вороток King Tony 4452-24FR с приводом 1/2″ изготовлен из хромованадиевой стали, отполирован и хромирован. Рабочая головка поворачивается на 180°; стандарт DIN 3122.',
                'Antrenorul articulat King Tony 4452-24FR cu pătrat de 1/2″ este fabricat din oțel crom-vanadiu, lustruit și cromat. Capul de lucru se rotește la 180°; conform DIN 3122.'),
            '4311-16F' => $this->item(
                'Коленчатый вороток King Tony 4311-16F, 1/2″',
                'Antrenor cotit King Tony 4311-16F, 1/2″',
                'Коленчатый скоростной вороток King Tony 4311-16F с приводом 1/2″ изготовлен из хромованадиевой стали, имеет полированное хромированное покрытие и соответствует DIN 3122.',
                'Antrenorul cotit rapid King Tony 4311-16F cu pătrat de 1/2″ este fabricat din oțel crom-vanadiu, are finisaj lustruit și cromat și corespunde DIN 3122.'),
            '7913A-63' => $this->item(
                'Труборез King Tony 7913A-63 для ПВХ, 6–63 мм',
                'Foarfecă pentru țevi PVC King Tony 7913A-63, 6–63 mm',
                'Труборез с трещоточным механизмом King Tony 7913A-63 предназначен для резки ПВХ-труб диаметром 6–63 мм. Компактный инструмент рассчитан на износостойкую профессиональную работу.',
                'Foarfeca cu mecanism cu clichet King Tony 7913A-63 este destinată tăierii țevilor din PVC cu diametrul de 6–63 mm. Unealta compactă este proiectată pentru utilizare profesională și rezistență la uzură.'),
            '9TA261A' => $this->item(
                'Светодиодный фонарь King Tony 9TA261A с магнитом, 600 лм',
                'Lampă LED King Tony 9TA261A cu magnet, 600 lm',
                'Тонкий аккумуляторный фонарь King Tony 9TA261A имеет основной свет до 600 лм, точечный свет 120 лм, пять фиксированных положений наклона в диапазоне 120° и магнитное основание. Аккумулятор 3,7 В / 2,6 А·ч обеспечивает 3–6 часов работы.',
                'Lampa reîncărcabilă subțire King Tony 9TA261A oferă lumină principală de până la 600 lm, lumină punctuală de 120 lm, cinci poziții fixe într-un interval de 120° și bază magnetică. Acumulatorul de 3,7 V / 2,6 Ah asigură 3–6 ore de funcționare.'),
            '19932427' => $this->item(
                'Крестовой баллонный ключ King Tony 19932427',
                'Cheie în cruce pentru roți King Tony 19932427',
                'Крестовой баллонный ключ King Tony 19932427 рассчитан на автомобильные колёсные гайки метрических размеров 24, 27, 30 и 33 мм. Изготовлен из хромованадиевой стали с полированным хромированным покрытием.',
                'Cheia în cruce pentru roți King Tony 19932427 este destinată piulițelor auto de 24, 27, 30 și 33 mm. Fabricată din oțel crom-vanadiu cu finisaj lustruit și cromat.'),
            '19911722' => $this->item(
                'Баллонный ключ King Tony 19911722, 17 × 22 мм',
                'Cheie pentru roți King Tony 19911722, 17 × 22 mm',
                'Баллонный ключ King Tony 19911722 предназначен для автомобильных колёсных гаек 17 и 22 мм. Хромованадиевая сталь, полированное хромированное покрытие; стандарты DIN 3119 и ISO 6788.',
                'Cheia pentru roți King Tony 19911722 este destinată piulițelor auto de 17 și 22 mm. Oțel crom-vanadiu, finisaj lustruit și cromat; conform DIN 3119 și ISO 6788.'),
            '19100810' => $this->item(
                'Шарнирный двусторонний торцевой ключ King Tony 19100810, 8 × 10 мм',
                'Cheie tubulară dublă articulată King Tony 19100810, 8 × 10 mm',
                'Двусторонний 12-гранный торцевой ключ King Tony 19100810 имеет тонкостенные головки 8 и 10 мм, поворачивающиеся на 180°. Изготовлен из хромованадиевой стали с хромированным покрытием.',
                'Cheia tubulară dublă cu 12 puncte King Tony 19100810 are capete cu pereți subțiri de 8 și 10 mm, rotative la 180°. Fabricată din oțel crom-vanadiu cu finisaj cromat.'),
            '19A0-15' => $this->item(
                'Двусторонний торцевой ключ King Tony 19A0-15',
                'Cheie tubulară dublă King Tony 19A0-15',
                'Двусторонний торцевой ключ King Tony 19A0-15 изготовлен из хромованадиевой стали и имеет износостойкое хромированное покрытие.',
                'Cheia tubulară dublă King Tony 19A0-15 este fabricată din oțel crom-vanadiu și are finisaj cromat rezistent la uzură.'),
            '6031-10' => $this->item(
                'Зажимные клещи King Tony 6031-10 с фиксатором, 10″',
                'Clește autoblocant King Tony 6031-10, 10″',
                'Зажимные клещи King Tony 6031-10 имеют прямые губки, регулировочный винт и рычаг освобождения. Губки изготовлены из пружинной легированной стали, рукоятка — из хромомолибденовой стали; стандарт ASME B107.500-2010.',
                'Cleștele autoblocant King Tony 6031-10 are fălci drepte, șurub de reglare și pârghie de eliberare. Fălcile sunt din oțel aliat pentru arcuri, iar mânerul din oțel crom-molibden; conform ASME B107.500-2010.'),
            '45211PP' => $this->item(
                'Набор клещей для стопорных колец King Tony 45211PP, 11 предметов',
                'Set de clești pentru siguranțe King Tony 45211PP, 11 piese',
                'Набор включает усиленные клещи длиной 406 мм для внутренних колец Ø78–155 мм и наружных Ø88–165 мм, храповой фиксатор и сменные наконечники Ø3 мм под углами 0°, 45° и 90°. Максимальное раскрытие 85 мм.',
                'Setul include clești ranforsați de 406 mm pentru siguranțe interioare Ø78–155 mm și exterioare Ø88–165 mm, blocare cu clichet și vârfuri interschimbabile Ø3 mm la 0°, 45° și 90°. Deschidere maximă 85 mm.'),
            '33411-050' => $this->item(
                'Пневматический ударный гайковёрт King Tony 33411-050, 1/2″',
                'Cheie pneumatică de impact King Tony 33411-050, 1/2″',
                'Пневматический гайковёрт с приводом 1/2″ имеет алюминиевый корпус, регулируемую скорость и глушитель выхлопа в рукоятке. Рекомендуемый крепёж до M19, шланг 3/8″, рабочее давление 6,2 бар.',
                'Cheia pneumatică de impact cu pătrat de 1/2″ are carcasă din aluminiu, viteză variabilă și evacuare amortizată în mâner. Pentru elemente de fixare până la M19, furtun de 3/8″ și presiune de lucru 6,2 bar.'),
            '33111US', '3311-12' => $this->item(
                "Гибкий удлинитель King Tony {$sku}, привод 3/8″",
                "Prelungitor flexibil King Tony {$sku}, antrenare 3/8″",
                'Гибкий удлинитель с приводом 3/8″ изготовлен из хромованадиевой стали, имеет полированное хромированное покрытие и соответствует DIN 3120.',
                'Prelungitorul flexibil cu antrenare de 3/8″ este fabricat din oțel crom-vanadiu, are finisaj lustruit și cromat și corespunde DIN 3120.'),
            '9AE53-80' => $this->item(
                'Двухзахватный съёмник масляного фильтра King Tony 9AE53-80',
                'Extractor de filtru de ulei cu două gheare King Tony 9AE53-80',
                'Съёмник предназначен для масляных фильтров диаметром 80–115 мм и используется с приводом 1/2″. Чёрное оксидное покрытие защищает инструмент от коррозии.',
                'Extractorul este destinat filtrelor de ulei cu diametrul de 80–115 mm și se utilizează cu antrenare de 1/2″. Finisajul cu oxid negru protejează unealta împotriva coroziunii.'),
            '8260-10' => $this->item(
                'Ударный удлинитель King Tony 8260-10, привод 1″',
                'Prelungitor de impact King Tony 8260-10, antrenare 1″',
                'Ударный удлинитель с приводом 1″ изготовлен из хромомолибденовой стали, имеет фосфатированное покрытие и соответствует DIN 3121.',
                'Prelungitorul de impact cu antrenare de 1″ este fabricat din oțel crom-molibden, are finisaj fosfatat și corespunde DIN 3121.'),
            '6260-13P' => $this->item(
                'Ударный удлинитель King Tony 6260-13P, привод 3/4″',
                'Prelungitor de impact King Tony 6260-13P, antrenare 3/4″',
                'Ударный удлинитель с приводом 3/4″ изготовлен из хромомолибденовой стали, имеет фосфатированное покрытие и соответствует DIN 3121.',
                'Prelungitorul de impact cu antrenare de 3/4″ este fabricat din oțel crom-molibden, are finisaj fosfatat și corespunde DIN 3121.'),
            '4223-05R' => $this->item(
                'Качающийся удлинитель King Tony 4223-05R, привод 1/2″',
                'Prelungitor oscilant King Tony 4223-05R, antrenare 1/2″',
                'Качающийся удлинитель King Tony 4223-05R с приводом 1/2″ изготовлен из хромованадиевой стали, отполирован и хромирован. Максимальный момент 320 Н·м; стандарт DIN 3123.',
                'Prelungitorul oscilant King Tony 4223-05R cu antrenare de 1/2″ este fabricat din oțel crom-vanadiu, lustruit și cromat. Cuplu maxim 320 N·m; conform DIN 3123.'),
            '20317MR' => $this->item(
                'Торцевая бита Phillips King Tony 20317MR, привод 1/4″',
                'Cap bit Phillips King Tony 20317MR, antrenare 1/4″',
                'Торцевая бита Phillips King Tony 20317MR с приводом 1/4″ изготовлена из кремнистой легированной стали S2, отполирована и хромирована. Соответствует DIN ISO 8764-1.',
                'Capul bit Phillips King Tony 20317MR cu antrenare de 1/4″ este fabricat din oțel aliat cu siliciu S2, lustruit și cromat. Conform DIN ISO 8764-1.'),
            default => null,
        };
    }

    private function item(string $nameRu, string $nameRo, string $descriptionRu, string $descriptionRo): array
    {
        return compact('nameRu', 'nameRo', 'descriptionRu', 'descriptionRo') + [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
        ];
    }

    public function down(): void
    {
        // Verified localization is intentionally retained.
    }
};
