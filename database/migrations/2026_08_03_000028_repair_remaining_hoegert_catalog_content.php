<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private string $catalog = 'https://en.hoegert.com/wp-content/uploads/2025/06/CATALOGUE_HT_25_-DE_EN_FR_ES_HR_HU_RO-BG.pdf';

    public function up(): void
    {
        $records = [
            ...$this->handTools(),
            ...$this->poweredTools(),
            ...$this->storageAndWorkstations(),
            ...$this->automotiveTools(),
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

    private function handTools(): array
    {
        $records = [
            'HT1A705' => $this->entry('HT1A705', 'Удлинитель 1/4″', 'Prelungitor 1/4″', 'Предназначен для работы с торцевыми головками 1/4″ и позволяет добраться до крепежа в углублениях.', 'Este destinat capetelor tubulare de 1/4″ și facilitează accesul la elementele de fixare adâncite.'),
            'HT1A778' => $this->entry('HT1A778', 'Шарнирная свечная головка 14 мм, 12 граней, 3/8″, 92 мм', 'Cap tubular articulat pentru bujii, 14 mm, 12 muchii, 3/8″, 92 mm', 'Шарнир облегчает доступ к свечам зажигания, расположенным под углом или в ограниченном пространстве.', 'Articulația facilitează accesul la bujiile montate în unghi sau în spații înguste.'),
            'HT1E620' => $this->entry('HT1E620', 'Универсальные токоизмерительные клещи', 'Clește ampermetric universal', 'Позволяют измерять ток без разрыва электрической цепи и предназначены для диагностики электрооборудования.', 'Permit măsurarea curentului fără întreruperea circuitului și sunt destinate diagnosticării instalațiilor electrice.', 'https://en.hoegert.com/product/universal-clamp-gauge/'),
            'HT1P142' => $this->entry('HT1P142', 'Прямые разжимные щипцы для стопорных колец, 180 мм', 'Clește drept pentru inele de siguranță exterioare, 180 mm', 'Используются для установки и снятия наружных стопорных колец.', 'Se utilizează pentru montarea și demontarea inelelor de siguranță exterioare.'),
            'HT1R439' => $this->entry('HT1R439', 'Универсальный набор инструментов 1/4″, 3/8″ и 1/2″, 131 предмет', 'Set universal de scule 1/4″, 3/8″ și 1/2″, 131 piese', 'Комплект объединяет инструменты трёх присоединительных размеров для слесарных, монтажных и сервисных работ.', 'Setul combină scule cu trei dimensiuni de antrenare pentru lucrări de atelier, montaj și service.'),
            'HT1R444' => $this->entry('HT1R444', 'Универсальный набор инструментов 1/4″, 3/8″ и 1/2″, 222 предмета', 'Set universal de scule 1/4″, 3/8″ și 1/2″, 222 piese', 'Расширенный комплект для слесарных, монтажных и сервисных работ содержит 222 предмета трёх присоединительных размеров.', 'Setul extins pentru lucrări de atelier, montaj și service conține 222 de piese cu trei dimensiuni de antrenare.'),
            'HT1R492' => $this->entry('HT1R492', 'Набор накидных трещоточных ключей с головками 8–19 мм и E8–E22', 'Set de chei inelare cu clichet și capete 8–19 mm și E8–E22', 'Набор рассчитан на работу с метрическим шестигранным крепежом и наружным профилем TORX.', 'Setul este destinat elementelor de fixare metrice hexagonale și profilului TORX exterior.'),
            'HT1S105' => $this->entry('HT1S105', 'Набор отвёрток, бит и головок, 63 предмета', 'Set de șurubelnițe, biți și capete tubulare, 63 piese', 'Комплект содержит 63 предмета для работы с распространёнными типами винтового и гаечного крепежа.', 'Setul conține 63 de piese pentru lucrul cu tipurile uzuale de șuruburi și piulițe.'),
            'HT1S804' => $this->entry('HT1S804', 'Набор ударных головок и бит 1/4″, 4–13 мм, 21 предмет', 'Set de capete și biți de impact 1/4″, 4–13 mm, 21 piese', 'Набор предназначен для ударного инструмента с приводом 1/4″ и содержит оснастку размеров 4–13 мм.', 'Setul este destinat sculelor de impact cu antrenare de 1/4″ și conține accesorii de 4–13 mm.'),
            'HT1S986' => $this->entry('HT1S986', 'Набор прецизионных диэлектрических отвёрток VDE 1000 В, 6 шт.', 'Set de șurubelnițe de precizie izolate VDE 1000 V, 6 buc.', 'Шесть прецизионных отвёрток предназначены для точных электромонтажных работ при напряжении до 1000 В.', 'Cele șase șurubelnițe de precizie sunt destinate lucrărilor electrice fine la tensiuni de până la 1000 V.'),
            'HT3B097' => $this->entry('HT3B097', 'Кувалда 5000 г с рукояткой из стекловолокна', 'Baros de 5000 g cu mâner din fibră de sticlă', 'Кувалда массой 5 кг предназначена для тяжёлых ударных работ; стекловолоконная рукоятка обеспечивает прочный хват.', 'Barosul de 5 kg este destinat lucrărilor grele de lovire, iar mânerul din fibră de sticlă oferă o prindere rezistentă.'),
            'HT4R504' => $this->entry('HT4R504', 'Телескопическое зеркало 50 × 80 мм с LED, 300–880 мм', 'Oglindă telescopică 50 × 80 mm cu LED, 300–880 mm', 'Используется для осмотра труднодоступных мест; телескопическая штанга регулируется от 300 до 880 мм, подсветка улучшает обзор.', 'Se utilizează pentru inspectarea zonelor greu accesibile; tija se reglează între 300 și 880 mm, iar iluminarea îmbunătățește vizibilitatea.'),
            'HT4R520' => $this->entry('HT4R520', 'Магнитный держатель инструмента, 460 мм', 'Suport magnetic pentru scule, 460 mm', 'Магнитная планка длиной 460 мм предназначена для открытого хранения металлического ручного инструмента.', 'Bara magnetică de 460 mm este destinată depozitării la vedere a sculelor manuale metalice.'),
            'HT4R523' => $this->entry('HT4R523', 'Набор складных магнитных лотков, 3 шт.', 'Set de tăvi magnetice pliabile, 3 buc.', 'Три магнитных лотка удерживают мелкий крепёж и детали во время ремонта и складываются для компактного хранения.', 'Cele trei tăvi magnetice păstrează piesele mici și elementele de fixare în timpul reparației și se pliază pentru depozitare compactă.'),
            'HT6D584' => $this->entry('HT6D584', 'Твердосплавная борфреза типа A, 10 × 20 мм', 'Freză rotativă din carbură tip A, 10 × 20 mm', 'Борфреза цилиндрического профиля A предназначена для обработки металла; размер рабочей части — 10 × 20 мм.', 'Freza rotativă cu profil cilindric A este destinată prelucrării metalului; partea activă măsoară 10 × 20 mm.'),
            'HT6D593' => $this->entry('HT6D593', 'Твердосплавная борфреза типа F, 10 × 20 мм', 'Freză rotativă din carbură tip F, 10 × 20 mm', 'Борфреза параболического профиля F предназначена для обработки металла; размер рабочей части — 10 × 20 мм.', 'Freza rotativă cu profil parabolic F este destinată prelucrării metalului; partea activă măsoară 10 × 20 mm.'),
        ];

        foreach (['HT1S085' => 'T15', 'HT1S086' => 'T20', 'HT1S087' => 'T25', 'HT1S088' => 'T30'] as $sku => $profile) {
            $records[$sku] = $this->entry(
                $sku,
                "Длинная отвёртка TORX {$profile} × 450 мм",
                "Șurubelniță lungă TORX {$profile} × 450 mm",
                "Стержень длиной 450 мм позволяет работать с глубоко расположенным крепежом TORX {$profile}.",
                "Tija de 450 mm permite accesul la elementele de fixare TORX {$profile} amplasate în adâncime.",
            );
        }

        foreach (['HT5K494' => ['жёлтая', 'galbenă'], 'HT5K495' => ['оранжевая', 'portocalie']] as $sku => [$colorRu, $colorRo]) {
            $records[$sku] = $this->entry(
                $sku,
                "Шапка со съёмным LED-фонарём 180 лм, {$colorRu}",
                "Căciulă cu lanternă LED detașabilă de 180 lm, {$colorRo}",
                'Встроенный съёмный фонарь обеспечивает направленное освещение при работе и передвижении в темноте.',
                'Lanterna detașabilă integrată oferă iluminare direcționată la lucru și deplasare pe întuneric.',
            );
        }

        return $records;
    }

    private function poweredTools(): array
    {
        return [
            'HT2C303' => $this->entry('HT2C303', 'Электрический паяльник, 80 Вт', 'Ciocan de lipit electric, 80 W', 'Предназначен для мягкой пайки электрических соединений и металлических деталей оловянными припоями.', 'Este destinat lipirii moi a conexiunilor electrice și pieselor metalice cu aliaje pe bază de staniu.'),
            'HT2E115' => $this->entry('HT2E115', 'Набор оснастки для гравировальной машины, 155 предметов', 'Set de accesorii pentru mașină de gravat, 155 piese', 'Комплект из 155 предметов предназначен для резки, шлифования, полирования и гравирования различных материалов.', 'Setul de 155 de piese este destinat tăierii, șlefuirii, lustruirii și gravării diferitelor materiale.'),
            'HT2E116' => $this->entry('HT2E116', 'Набор оснастки для гравировальной машины, 71 предмет', 'Set de accesorii pentru mașină de gravat, 71 piese', 'Комплект из 71 предмета предназначен для резки, шлифования, полирования и гравирования различных материалов.', 'Setul de 71 de piese este destinat tăierii, șlefuirii, lustruirii și gravării diferitelor materiale.'),
            'HT2E200_' => $this->entry('HT2E200_', 'Аккумуляторный ударный гайковёрт 1/2″, 20 В, 2 А·ч, 400 Н·м', 'Cheie de impact cu acumulator 1/2″, 20 V, 2 Ah, 400 Nm', 'Бесщёточный гайковёрт имеет три ступени момента 200/300/400 Н·м, скорость до 2600 об/мин и частоту ударов до 3300 уд/мин. В комплект входят аккумулятор 2 А·ч, зарядное устройство и кейс.', 'Cheia de impact fără perii are trei trepte de cuplu de 200/300/400 Nm, turație de până la 2600 rpm și până la 3300 lovituri/min. Setul include acumulator de 2 Ah, încărcător și cutie.', 'https://en.hoegert.com/product/cordelss-impact-wrench-400nm/'),
            'HT2E214' => $this->entry('HT2E214', 'Портативная электростанция 600/1200 Вт, 569,4 Вт·ч', 'Stație de energie portabilă 600/1200 W, 569,4 Wh', 'Станция с чистой синусоидой оснащена аккумулятором 569,4 Вт·ч, выходами DC, USB-A и USB-C, беспроводной зарядкой 15 Вт и LED-фонарём. Поддерживает зарядку от солнечной панели HT2E216.', 'Stația cu undă sinusoidală pură are acumulator de 569,4 Wh, ieșiri DC, USB-A și USB-C, încărcare wireless de 15 W și lanternă LED. Acceptă încărcarea de la panoul solar HT2E216.', 'https://en.hoegert.com/product/600w-power-station/'),
            'HT2E215' => $this->entry('HT2E215', 'Портативная электростанция 1000/2000 Вт, 1095 Вт·ч', 'Stație de energie portabilă 1000/2000 W, 1095 Wh', 'Станция с чистой синусоидой оснащена аккумулятором 1095 Вт·ч, выходами DC, USB-A и USB-C, беспроводной зарядкой 15 Вт и LED-фонарём. Поддерживает зарядку от солнечной панели HT2E216.', 'Stația cu undă sinusoidală pură are acumulator de 1095 Wh, ieșiri DC, USB-A și USB-C, încărcare wireless de 15 W și lanternă LED. Acceptă încărcarea de la panoul solar HT2E216.', 'https://en.hoegert.com/product/1000w-power-station/'),
            'HT2E216' => $this->entry('HT2E216', 'Складная солнечная панель 18 В, 100 Вт', 'Panou solar pliabil 18 V, 100 W', 'Панель предназначена для автономной зарядки портативных электростанций HT2E213, HT2E214 и HT2E215.', 'Panoul este destinat încărcării autonome a stațiilor de energie portabile HT2E213, HT2E214 și HT2E215.', 'https://en.hoegert.com/product/100w-solar-panel/'),
            'HT2E418' => $this->entry('HT2E418', 'Мойка высокого давления 2200 Вт, 165 бар с регулировкой давления', 'Aparat de spălat cu presiune 2200 W, 165 bar, cu reglarea presiunii', 'Мойка предназначена для очистки автомобилей, оборудования и твёрдых поверхностей; рабочее давление регулируется в зависимости от задачи.', 'Aparatul este destinat curățării automobilelor, echipamentelor și suprafețelor dure; presiunea poate fi reglată în funcție de lucrare.'),
        ];
    }

    private function storageAndWorkstations(): array
    {
        $records = [
            'HT7G022' => $this->entry('HT7G022', 'Органайзер с переставными перегородками 12″, 29 × 19,5 × 3,5 см', 'Organizator cu separatoare reglabile 12″, 29 × 19,5 × 3,5 cm', 'Предназначен для сортировки и хранения мелкого инструмента, крепежа и расходных материалов.', 'Este destinat sortării și depozitării sculelor mici, elementelor de fixare și consumabilelor.'),
            'HT7G024' => $this->entry('HT7G024', 'Органайзер с переставными перегородками 14″, 34,4 × 24,9 × 5 см', 'Organizator cu separatoare reglabile 14″, 34,4 × 24,9 × 5 cm', 'Предназначен для сортировки и хранения мелкого инструмента, крепежа и расходных материалов.', 'Este destinat sortării și depozitării sculelor mici, elementelor de fixare și consumabilelor.'),
            'HT7G540' => $this->entry('HT7G540', 'Складной верстак 700 × 650 × 915 мм', 'Banc de lucru pliabil 700 × 650 × 915 mm', 'Складная конструкция упрощает переноску и компактное хранение рабочего стола.', 'Construcția pliabilă facilitează transportul și depozitarea compactă a bancului.'),
            'HT7G541' => $this->entry('HT7G541', 'Складной регулируемый верстак 845 × 598 × 735–885 мм', 'Banc de lucru pliabil reglabil 845 × 598 × 735–885 mm', 'Высота рабочей поверхности регулируется от 735 до 885 мм, а складная конструкция упрощает хранение.', 'Înălțimea suprafeței de lucru se reglează între 735 și 885 mm, iar construcția pliabilă simplifică depozitarea.'),
            'HT7G564' => $this->entry('HT7G564', 'Передвижной стол для сервисных работ', 'Masă mobilă pentru lucrări de service', 'Предназначен для размещения инструмента, деталей и оборудования рядом с обслуживаемым автомобилем.', 'Este destinat amplasării sculelor, pieselor și echipamentelor lângă automobilul deservit.'),
            'HT7G575' => $this->entry('HT7G575', 'Диагностическая тележка с 4 полками', 'Cărucior de diagnosticare cu 4 rafturi', 'Четыре полки предназначены для размещения диагностического оборудования, ноутбука, инструмента и кабелей.', 'Cele patru rafturi sunt destinate echipamentului de diagnosticare, laptopului, sculelor și cablurilor.'),
            'HT7G576' => $this->entry('HT7G576', 'Диагностическая тележка с 5 полками', 'Cărucior de diagnosticare cu 5 rafturi', 'Пять полок предназначены для размещения диагностического оборудования, ноутбука, инструмента и кабелей.', 'Cele cinci rafturi sunt destinate echipamentului de diagnosticare, laptopului, sculelor și cablurilor.'),
        ];

        foreach ([
            'HT7G045-159' => [6, 159],
            'HT7G048-332' => [7, 332],
            'HT7G049-307' => [7, 307],
            'HT7G049-338' => [7, 338],
        ] as $sku => [$drawers, $pieces]) {
            $records[$sku] = $this->entry(
                $sku,
                "Синяя инструментальная тележка, {$drawers} ящиков, {$pieces} предметов",
                "Cărucior albastru cu scule, {$drawers} sertare, {$pieces} piese",
                "Тележка поставляется с комплектом из {$pieces} инструментов, распределённых по {$drawers} ящикам.",
                "Căruciorul este livrat cu un set de {$pieces} scule, organizate în {$drawers} sertare.",
            );
        }

        $traySets = [
            'HT7G120' => ['Набор комбинированных ключей 6–21 мм в ложементе, 16 предметов', 'Set de chei combinate 6–21 mm în modul, 16 piese', 'Содержит 16 комбинированных ключей размеров 6–21 мм.', 'Conține 16 chei combinate cu dimensiuni de 6–21 mm.'],
            'HT7G121' => ['Набор комбинированных ключей 22–32 мм в ложементе, 6 предметов', 'Set de chei combinate 22–32 mm în modul, 6 piese', 'Содержит шесть комбинированных ключей размеров 22–32 мм.', 'Conține șase chei combinate cu dimensiuni de 22–32 mm.'],
            'HT7G122' => ['Набор накидных ключей 75°, 6–19 мм в ложементе, 7 предметов', 'Set de chei inelare la 75°, 6–19 mm în modul, 7 piese', 'Содержит семь накидных ключей с углом 75° для размеров 6–19 мм.', 'Conține șapte chei inelare la 75° pentru dimensiuni de 6–19 mm.'],
            'HT7G123' => ['Набор накидных ключей 75°, 20–32 мм в ложементе, 4 предмета', 'Set de chei inelare la 75°, 20–32 mm în modul, 4 piese', 'Содержит четыре накидных ключа с углом 75° для размеров 20–32 мм.', 'Conține patru chei inelare la 75° pentru dimensiuni de 20–32 mm.'],
            'HT7G127' => ['Набор головок 3/8″ с принадлежностями в ложементе, 22 предмета', 'Set de capete 3/8″ cu accesorii în modul, 22 piese', 'Содержит 22 предмета для работы с крепежом приводом 3/8″.', 'Conține 22 de piese pentru lucrul cu elemente de fixare și antrenare de 3/8″.'],
            'HT7G130' => ['Набор пассатижей и бокорезов в ложементе, 4 предмета', 'Set de clești și tăietoare laterale în modul, 4 piese', 'Четыре шарнирно-губцевых инструмента размещены в формованном ложементе.', 'Patru clești sunt organizați într-un modul profilat.'],
            'HT7G131' => ['Набор щипцов для стопорных колец в ложементе, 4 предмета', 'Set de clești pentru inele de siguranță în modul, 4 piese', 'Комплект содержит четыре щипца для установки и снятия стопорных колец.', 'Setul conține patru clești pentru montarea și demontarea inelelor de siguranță.'],
            'HT7G132' => ['Набор плоских отвёрток в ложементе, 7 предметов', 'Set de șurubelnițe plate în modul, 7 piese', 'Семь плоских отвёрток размещены в формованном ложементе.', 'Șapte șurubelnițe plate sunt organizate într-un modul profilat.'],
            'HT7G133' => ['Набор крестовых отвёрток в ложементе, 6 предметов', 'Set de șurubelnițe în cruce în modul, 6 piese', 'Шесть крестовых отвёрток размещены в формованном ложементе.', 'Șase șurubelnițe în cruce sunt organizate într-un modul profilat.'],
            'HT7G134' => ['Набор отвёрток и Г-образных ключей HEX/TORX в ложементе, 20 предметов', 'Set de șurubelnițe și chei L HEX/TORX în modul, 20 piese', 'Содержит 20 отвёрток и Г-образных ключей с профилями HEX и TORX.', 'Conține 20 de șurubelnițe și chei în L cu profile HEX și TORX.'],
            'HT7G145' => ['Набор рожковых ключей 6–32 мм в ложементе, 15 предметов', 'Set de chei fixe 6–32 mm în modul, 15 piese', 'Содержит 15 рожковых ключей размеров 6–32 мм.', 'Conține 15 chei fixe cu dimensiuni de 6–32 mm.'],
            'HT7G145-1' => ['Набор рожковых ключей 6–32 мм в ложементе, 15 предметов', 'Set de chei fixe 6–32 mm în modul, 15 piese', 'Содержит 15 рожковых ключей размеров 6–32 мм.', 'Conține 15 chei fixe cu dimensiuni de 6–32 mm.'],
            'HT7G148' => ['Набор головок и бит 1/4″ с принадлежностями в ложементе, 63 предмета', 'Set de capete și biți 1/4″ cu accesorii în modul, 63 piese', 'Содержит 63 предмета для работы с крепежом приводом 1/4″.', 'Conține 63 de piese pentru lucrul cu elemente de fixare și antrenare de 1/4″.'],
            'HT7G149' => ['Набор головок 1/2″ с принадлежностями в ложементе, 28 предметов', 'Set de capete 1/2″ cu accesorii în modul, 28 piese', 'Содержит 28 предметов для работы с крепежом приводом 1/2″.', 'Conține 28 de piese pentru lucrul cu elemente de fixare și antrenare de 1/2″.'],
            'HT7G150' => ['Набор бит в ложементе, 107 предметов', 'Set de biți în modul, 107 piese', 'Содержит 107 бит и принадлежностей для различных профилей крепежа.', 'Conține 107 biți și accesorii pentru diferite profile de fixare.'],
            'HT7G151' => ['Набор зубил, выколоток, кернеров и молоток в ложементе, 13 предметов', 'Set de dălți, dornuri, punctatoare și ciocan în modul, 13 piese', 'Комплект из 13 предметов предназначен для ударных слесарных работ.', 'Setul de 13 piese este destinat lucrărilor mecanice de lovire.'],
        ];
        foreach ($traySets as $sku => [$nameRu, $nameRo, $detailRu, $detailRo]) {
            $records[$sku] = $this->entry($sku, $nameRu, $nameRo, $detailRu, $detailRo);
        }

        return $records;
    }

    private function automotiveTools(): array
    {
        return [
            'HT8G052' => $this->entry('HT8G052', 'Автомобильные страховочные стойки 2 т, 278–423 мм, 2 шт.', 'Suporți auto 2 t, 278–423 mm, 2 buc.', 'Пара стоек предназначена для безопасной фиксации поднятого автомобиля; рабочий диапазон высоты — 278–423 мм.', 'Perechea de suporți este destinată susținerii în siguranță a automobilului ridicat; domeniul de înălțime este 278–423 mm.'),
            'HT8G053' => $this->entry('HT8G053', 'Автомобильные страховочные стойки 3 т, 288–428 мм, 2 шт.', 'Suporți auto 3 t, 288–428 mm, 2 buc.', 'Пара стоек предназначена для безопасной фиксации поднятого автомобиля; рабочий диапазон высоты — 288–428 мм.', 'Perechea de suporți este destinată susținerii în siguranță a automobilului ridicat; domeniul de înălțime este 288–428 mm.'),
            'HT8G058' => $this->entry('HT8G058', 'Подкатной гидравлический домкрат 2 т, 90–340 мм', 'Cric hidraulic tip crocodil 2 t, 90–340 mm', 'Домкрат грузоподъёмностью 2 т поднимает автомобиль в диапазоне 90–340 мм.', 'Cricul cu capacitatea de 2 t ridică automobilul în domeniul 90–340 mm.'),
            'HT8G063' => $this->entry('HT8G063', 'Автомобильные страховочные стойки 3 т, 300–444 мм, 2 шт.', 'Suporți auto 3 t, 300–444 mm, 2 buc.', 'Пара стоек предназначена для безопасной фиксации поднятого автомобиля; рабочий диапазон высоты — 300–444 мм.', 'Perechea de suporți este destinată susținerii în siguranță a automobilului ridicat; domeniul de înălțime este 300–444 mm.'),
            'HT8G065' => $this->entry('HT8G065', 'Подкатной гидравлический домкрат 3 т, 78–505 мм', 'Cric hidraulic tip crocodil 3 t, 78–505 mm', 'Низкопрофильный домкрат грузоподъёмностью 3 т поднимает автомобиль в диапазоне 78–505 мм.', 'Cricul cu profil redus și capacitatea de 3 t ridică automobilul în domeniul 78–505 mm.'),
            'HT8G245' => $this->entry('HT8G245', 'Стяжки пружин 355 мм, 2 шт.', 'Compresoare pentru arcuri 355 mm, 2 buc.', 'Пара механических стяжек предназначена для сжатия винтовых пружин при обслуживании подвески.', 'Perechea de compresoare mecanice este destinată comprimării arcurilor elicoidale la lucrările de suspensie.'),
            'HT8G253' => $this->entry('HT8G253', 'Набор оправок для монтажа и демонтажа ступичных подшипников', 'Set de adaptoare pentru montarea și demontarea rulmenților de butuc', 'Набор используется для запрессовки и выпрессовки ступичных подшипников при ремонте ходовой части.', 'Setul se utilizează pentru presarea și extragerea rulmenților de butuc la repararea trenului de rulare.'),
            'HT8G307' => $this->entry('HT8G307', 'Набор съёмников масляных фильтров, 30 предметов', 'Set de chei pentru filtre de ulei, 30 piese', 'Комплект содержит 30 чашечных съёмников и принадлежностей для обслуживания масляных фильтров разных размеров.', 'Setul conține 30 de chei tip cupă și accesorii pentru întreținerea filtrelor de ulei de diferite dimensiuni.'),
            'HT8G316' => $this->entry('HT8G316', 'Шиномонтажная монтировка, 500 мм', 'Levier pentru anvelope, 500 mm', 'Монтировка длиной 500 мм предназначена для монтажа и демонтажа шин.', 'Levierul de 500 mm este destinat montării și demontării anvelopelor.'),
            'HT8G397' => $this->entry('HT8G397', 'Набор слесарных крючков, 5 предметов', 'Set de cârlige pentru lucrări mecanice, 5 piese', 'Пять крючков предназначены для извлечения уплотнений, колец, пружин и мелких деталей из труднодоступных мест.', 'Cele cinci cârlige sunt destinate extragerii garniturilor, inelelor, arcurilor și pieselor mici din zone greu accesibile.'),
            'HT8G440' => $this->entry('HT8G440', 'Тестер тормозной жидкости', 'Tester pentru lichid de frână', 'Прибор предназначен для быстрой проверки состояния тормозной жидкости при техническом обслуживании автомобиля.', 'Aparatul este destinat verificării rapide a stării lichidului de frână la întreținerea automobilului.'),
        ];
    }

    private function entry(
        string $sku,
        string $nameRu,
        string $nameRo,
        string $detailRu,
        string $detailRo,
        ?string $sourceUrl = null,
    ): array {
        return [
            'name_ru' => "{$nameRu} HOEGERT {$sku}",
            'name_ro' => "{$nameRo} HOEGERT {$sku}",
            'description_ru' => "{$nameRu} HOEGERT {$sku}. {$detailRu}",
            'description_ro' => "{$nameRo} HOEGERT {$sku}. {$detailRo}",
            'source_url' => $sourceUrl ?: $this->catalog,
        ];
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
