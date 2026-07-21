<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $records = array_replace(
            $this->poweredToolRecords(),
            $this->sanderAndConsumableRecords(),
            $this->hoseAndHammerRecords(),
        );
        $products = DB::table('products')->whereIn('sku', array_keys($records))->get()->keyBy('sku');

        DB::transaction(function () use ($records, $products): void {
            foreach ($records as $sku => $content) {
                $product = $products->get($sku);
                if (! $product) {
                    continue;
                }

                $this->updateProduct($product, $content);
            }
        });
    }

    private function poweredToolRecords(): array
    {
        return [
            'DB-1850' => [
                'name_ru' => 'Аккумулятор LiHD M7 DB-1850, 18 В, 5 А·ч',
                'name_ro' => 'Acumulator LiHD M7 DB-1850, 18 V, 5 Ah',
                'description_ru' => 'Аккумулятор M7 DB-1850 технологии LiHD имеет напряжение 18 В и ёмкость 5 А·ч. Предназначен для совместимого аккумуляторного инструмента M7 платформы 18 В.',
                'description_ro' => 'Acumulatorul M7 DB-1850 cu tehnologie LiHD are tensiunea de 18 V și capacitatea de 5 Ah. Este destinat sculelor M7 compatibile din platforma de 18 V.',
                'attributes' => ['Тип' => 'Аккумулятор LiHD', 'Напряжение аккумулятора' => '18 V', 'Ёмкость аккумулятора' => '5 Ah', 'Совместимость' => 'M7 18 V'],
            ],
            'DG-585' => [
                'name_ru' => 'Аккумуляторная угловая шлифмашина M7 DG-585, 18 В, диск 5 дюймов, без аккумулятора',
                'name_ro' => 'Polizor unghiular cu acumulator M7 DG-585, 18 V, disc de 5 inch, fără acumulator',
                'description_ru' => 'Угловая шлифмашина M7 DG-585 рассчитана на диски 5 дюймов и платформу 18 В. Бесщёточный двигатель, регулировка скорости, двухпозиционная рукоятка, блокировка шпинделя и быстрорегулируемый кожух упрощают работу; аккумулятор и зарядное устройство в комплект не входят.',
                'description_ro' => 'Polizorul unghiular M7 DG-585 utilizează discuri de 5 inch și platforma de 18 V. Motorul fără perii, reglarea vitezei, mânerul cu două poziții, blocarea axului și apărătoarea cu reglare rapidă simplifică lucrul; acumulatorul și încărcătorul nu sunt incluse.',
                'attributes' => ['Тип' => 'Аккумуляторная угловая шлифмашина', 'Напряжение аккумулятора' => '18 V', 'Диаметр диска' => '5 inch', 'Тип двигателя' => 'Бесщёточный', 'Комплектация' => 'Без аккумулятора и зарядного устройства'],
            ],
            'DW-18502' => [
                'name_ru' => 'Аккумуляторный ударный гайковёрт M7 DW-18502, 1/2 дюйма, 1220 Н·м, комплект 18 В',
                'name_ro' => 'Cheie de impact cu acumulator M7 DW-18502, 1/2 inch, 1220 Nm, set 18 V',
                'description_ru' => 'Ударный гайковёрт M7 DW-18502 с приводом 1/2 дюйма развивает максимальный крутящий момент 1220 Н·м и рассчитан на крепёж M6–M24. Масса инструмента — 2,55 кг; комплект включает аккумулятор 18 В 5 А·ч и зарядное устройство.',
                'description_ro' => 'Cheia de impact M7 DW-18502, cu antrenare de 1/2 inch, dezvoltă un cuplu maxim de 1220 Nm și este destinată elementelor de fixare M6–M24. Masa sculei este de 2,55 kg; setul include un acumulator de 18 V și 5 Ah și încărcătorul.',
                'attributes' => ['Тип' => 'Аккумуляторный ударный гайковёрт', 'Посадочный квадрат' => '1/2 inch', 'Максимальный крутящий момент' => '1220 Nm', 'Диапазон крепежа' => 'M6–M24', 'Масса нетто' => '2.55 kg', 'Напряжение аккумулятора' => '18 V', 'Ёмкость аккумулятора' => '5 Ah', 'Комплектация' => 'Гайковёрт, аккумулятор 18 В 5 А·ч, зарядное устройство'],
            ],
            'NC-4650KIT' => [
                'name_ru' => 'Набор с пневматическим ударным гайковёртом M7 NC-4650KIT, 1/2 дюйма, 900 Н·м, 11 предметов',
                'name_ro' => 'Set cu cheie pneumatică de impact M7 NC-4650KIT, 1/2 inch, 900 Nm, 11 piese',
                'description_ru' => 'Комплект M7 NC-4650KIT состоит из пневматического ударного гайковёрта с приводом 1/2 дюйма и торцевых головок, всего 11 предметов. Максимальный крутящий момент гайковёрта — 900 Н·м.',
                'description_ro' => 'Setul M7 NC-4650KIT include o cheie pneumatică de impact cu antrenare de 1/2 inch și capete tubulare, în total 11 piese. Cuplul maxim al cheii este de 900 Nm.',
                'attributes' => ['Тип' => 'Набор с пневматическим ударным гайковёртом', 'Посадочный квадрат' => '1/2 inch', 'Максимальный крутящий момент' => '900 Nm', 'Количество предметов' => '11', 'Комплектация' => 'Гайковёрт и торцевые головки'],
            ],
            'NC-6215' => [
                'name_ru' => 'Пневматический ударный гайковёрт M7 NC-6215, 3/4 дюйма, 1220 Н·м, 6500 об/мин',
                'name_ro' => 'Cheie pneumatică de impact M7 NC-6215, 3/4 inch, 1220 Nm, 6500 rot/min',
                'description_ru' => 'Пневматический ударный гайковёрт M7 NC-6215 с приводом 3/4 дюйма развивает максимальный крутящий момент 1220 Н·м и скорость свободного вращения 6500 об/мин. Ударный механизм Twin Hammer повышает эффективность, а выпуск воздуха через рукоятку отводит поток от рабочей зоны.',
                'description_ro' => 'Cheia pneumatică de impact M7 NC-6215, cu antrenare de 3/4 inch, dezvoltă un cuplu maxim de 1220 Nm și o turație în gol de 6500 rot/min. Mecanismul Twin Hammer crește eficiența, iar evacuarea prin mâner îndepărtează aerul de zona de lucru.',
                'attributes' => ['Тип' => 'Пневматический ударный гайковёрт', 'Посадочный квадрат' => '3/4 inch', 'Максимальный крутящий момент' => '1220 Nm', 'Скорость свободного вращения' => '6500 об/мин', 'Механизм' => 'Twin Hammer'],
            ],
            'PN-150' => [
                'name_ru' => 'Пневматический дырокол-кромкогиб M7 PN-150, отверстие 5 мм, металл до 1,6 мм',
                'name_ro' => 'Perforator și fălțuitor pneumatic M7 PN-150, orificiu 5 mm, metal până la 1,6 mm',
                'description_ru' => 'Пневматический комбинированный инструмент M7 PN-150 предназначен для пробивки отверстий диаметром 5 мм и формирования кромки в листовом металле толщиной до 1,6 мм. Расход воздуха — 113 л/мин.',
                'description_ro' => 'Scula pneumatică combinată M7 PN-150 este destinată perforării orificiilor de 5 mm și fălțuirii tablei cu grosimea de până la 1,6 mm. Consumul de aer este de 113 l/min.',
                'attributes' => ['Тип' => 'Пневматический дырокол-кромкогиб', 'Диаметр отверстия' => '5 mm', 'Максимальная толщина металла' => '1.6 mm', 'Расход воздуха' => '113 л/мин'],
            ],
        ];
    }

    private function sanderAndConsumableRecords(): array
    {
        return [
            'QB-46502' => $this->orbitalSander('QB-46502', 127, 5),
            'QB-52602' => $this->orbitalSander('QB-52602', 152, 6),
            'QB-9403' => $this->polishingPad('QB-9403', 'Жёсткий полировальный диск', 'Disc de lustruire dur', 'Жёсткая', 'dură'),
            'QB-9413' => $this->polishingPad('QB-9413', 'Среднежёсткий полировальный диск', 'Disc de lustruire mediu', 'Средняя', 'medie'),
            'QD-924_1' => $this->sawBlade('QD-924_1', 24),
            'QD-932_1' => $this->sawBlade('QD-932_1', 32),
            'QE-98L' => [
                'name_ru' => 'Двухосевой пузырьковый уровень для позиционирования дрели M7 QE-98L',
                'name_ro' => 'Nivelă cu bulă pe două axe pentru poziționarea burghiului M7 QE-98L',
                'description_ru' => 'Пузырьковый уровень M7 QE-98L с двумя осями контроля помогает позиционировать дрель относительно горизонтальной и вертикальной плоскостей.',
                'description_ro' => 'Nivela cu bulă M7 QE-98L, cu două axe de control, ajută la poziționarea burghiului față de planurile orizontal și vertical.',
                'attributes' => ['Тип' => 'Двухосевой пузырьковый уровень', 'Количество осей' => '2', 'Применение' => 'Позиционирование дрели'],
            ],
            'QP-113' => [
                'name_ru' => 'Пневматическая пистолетная шлифмашина M7 QP-113, диск 76 мм, 15 000 об/мин',
                'name_ro' => 'Șlefuitor pneumatic tip pistol M7 QP-113, disc 76 mm, 15 000 rot/min',
                'description_ru' => 'Пневматическая пистолетная шлифмашина M7 QP-113 рассчитана на диск диаметром 76 мм (3 дюйма) и развивает скорость свободного вращения до 15 000 об/мин.',
                'description_ro' => 'Șlefuitorul pneumatic tip pistol M7 QP-113 utilizează un disc de 76 mm (3 inch) și atinge o turație în gol de până la 15 000 rot/min.',
                'attributes' => ['Тип' => 'Пневматическая пистолетная шлифмашина', 'Диаметр диска' => '76 mm', 'Скорость свободного вращения' => '15000 об/мин'],
            ],
        ];
    }

    private function orbitalSander(string $sku, int $diameter, int $inches): array
    {
        return [
            'name_ru' => "Пневматическая орбитальная шлифмашина M7 {$sku}, {$diameter} мм, 10 000 об/мин, ход 5 мм",
            'name_ro' => "Șlefuitor orbital pneumatic M7 {$sku}, {$diameter} mm, 10 000 rot/min, cursă 5 mm",
            'description_ru' => "Пневматическая орбитальная шлифмашина M7 {$sku} без системы пылеудаления оснащена рабочим диском {$diameter} мм ({$inches} дюймов), развивает 10 000 об/мин и имеет эксцентриситет 5 мм. Резиновая накладка позволяет работать одной рукой; предусмотрен регулятор подачи воздуха и скорости.",
            'description_ro' => "Șlefuitorul orbital pneumatic M7 {$sku}, fără sistem de extragere a prafului, utilizează un disc de {$diameter} mm ({$inches} inch), atinge 10 000 rot/min și are excentricitatea de 5 mm. Mânerul cauciucat permite lucrul cu o singură mână, iar debitul de aer și viteza pot fi reglate.",
            'attributes' => ['Тип' => 'Пневматическая орбитальная шлифмашина', 'Диаметр диска' => $diameter.' mm', 'Скорость свободного вращения' => '10000 об/мин', 'Эксцентриситет' => '5 mm', 'Пылеудаление' => 'Без пылеудаления'],
        ];
    }

    private function polishingPad(string $sku, string $typeRu, string $typeRo, string $hardnessRu, string $hardnessRo): array
    {
        return [
            'name_ru' => "{$typeRu} M7 {$sku}, 76 мм, для QP-113/QP-123",
            'name_ro' => "{$typeRo} M7 {$sku}, 76 mm, pentru QP-113/QP-123",
            'description_ru' => "Полировальный диск M7 {$sku} диаметром 76 мм изготовлен из губчатого материала, имеет {$hardnessRu} жёсткость и совместим с машинами M7 QP-113 и QP-123.",
            'description_ro' => "Discul de lustruire M7 {$sku}, cu diametrul de 76 mm, este realizat din material spongios, are duritate {$hardnessRo} și este compatibil cu mașinile M7 QP-113 și QP-123.",
            'attributes' => ['Тип' => $typeRu, 'Диаметр диска' => '76 mm', 'Жёсткость' => $hardnessRu, 'Совместимость' => 'M7 QP-113, QP-123'],
        ];
    }

    private function sawBlade(string $sku, int $teeth): array
    {
        return [
            'name_ru' => "Сменное биметаллическое полотно M7 {$sku}, {$teeth} TPI, толщина 0,025 дюйма",
            'name_ro' => "Pânză bimetalică de schimb M7 {$sku}, {$teeth} TPI, grosime 0,025 inch",
            'description_ru' => "Сменное биметаллическое полотно M7 {$sku} для пневматической ножовки имеет {$teeth} зуба на дюйм и толщину 0,025 дюйма.",
            'description_ro' => "Pânza bimetalică de schimb M7 {$sku} pentru ferăstrău pneumatic are {$teeth} dinți pe inch și grosimea de 0,025 inch.",
            'attributes' => ['Тип' => 'Сменное биметаллическое полотно', 'Материал' => 'Биметалл', 'Количество зубьев на дюйм' => (string) $teeth, 'Толщина' => '0.025 inch'],
        ];
    }

    private function hoseAndHammerRecords(): array
    {
        return [
            'SA-2015' => $this->hoseReel('SA-2015', 8, 12, 'Стальной', 'oțel'),
            'SA-3315' => $this->hoseReel('SA-3315', 10, 15, 'Ударопрочный пластик', 'plastic rezistent la impact'),
            'SC-0617C' => [
                'name_ru' => 'Набор с пневмомолотком M7 SC-0617C: SC-221, 5 зубил SC-425, фиксатор и кейс',
                'name_ro' => 'Set cu ciocan pneumatic M7 SC-0617C: SC-221, 5 dălți SC-425, retentor și valiză',
                'description_ru' => 'Комплект M7 SC-0617C включает пневмомолоток SC-221, пять шестигранных зубил SC-425, пружинный фиксатор и пластиковый кейс.',
                'description_ro' => 'Setul M7 SC-0617C include ciocanul pneumatic SC-221, cinci dălți hexagonale SC-425, un retentor cu arc și o valiză din plastic.',
                'attributes' => ['Тип' => 'Набор с пневмомолотком', 'Количество зубил' => '5', 'Форма хвостовика' => 'Шестигранный', 'Комплектация' => 'Пневмомолоток, 5 зубил, фиксатор, кейс'],
            ],
            'SC-2A' => $this->retainer('SC-2A', false),
            'SC-2B' => $this->retainer('SC-2B', true),
            'SC-331-KIT' => [
                'name_ru' => 'Набор с пневмомолотком M7 SC-331-KIT, 5 круглых зубил и кейс',
                'name_ro' => 'Set cu ciocan pneumatic M7 SC-331-KIT, 5 dălți rotunde și valiză',
                'description_ru' => 'Комплект M7 SC-331-KIT включает пневмомолоток, пять специальных зубил с круглым хвостовиком и пластиковый кейс для хранения и перевозки.',
                'description_ro' => 'Setul M7 SC-331-KIT include un ciocan pneumatic, cinci dălți speciale cu tijă rotundă și o valiză din plastic pentru depozitare și transport.',
                'attributes' => ['Тип' => 'Набор с пневмомолотком', 'Количество зубил' => '5', 'Форма хвостовика' => 'Круглый', 'Комплектация' => 'Пневмомолоток, 5 зубил, кейс'],
            ],
            'SY-2413P' => [
                'name_ru' => 'Быстроразъёмная пневматическая муфта M7 SY-2413P, европейский профиль, шланг 8 × 12 мм',
                'name_ro' => 'Cuplă pneumatică rapidă M7 SY-2413P, profil european, furtun 8 × 12 mm',
                'description_ru' => 'Композитная быстроразъёмная пневматическая муфта M7 SY-2413P европейского профиля предназначена для подключения шланга размером 8 × 12 мм.',
                'description_ro' => 'Cupla pneumatică rapidă M7 SY-2413P, realizată din compozit și cu profil european, este destinată conectării unui furtun de 8 × 12 mm.',
                'attributes' => ['Тип' => 'Быстроразъёмная пневматическая муфта', 'Стандарт соединения' => 'Европейский профиль', 'Размер шланга' => '8 × 12 mm', 'Материал' => 'Композит'],
            ],
        ];
    }

    private function hoseReel(string $sku, int $innerDiameter, int $outerDiameter, string $caseMaterialRu, string $caseMaterialRo): array
    {
        $caseMaterialDescriptionRu = $caseMaterialRu === 'Стальной'
            ? 'стали'
            : 'ударопрочного пластика';

        return [
            'name_ru' => "Катушка с воздушным шлангом M7 {$sku}, 15 м, {$innerDiameter} × {$outerDiameter} мм",
            'name_ro' => "Tambur cu furtun de aer M7 {$sku}, 15 m, {$innerDiameter} × {$outerDiameter} mm",
            'description_ru' => "Автоматическая катушка M7 {$sku} оснащена воздушным шлангом длиной 15 м с внутренним диаметром {$innerDiameter} мм и наружным диаметром {$outerDiameter} мм. Корпус выполнен из {$caseMaterialDescriptionRu}; пружинная система обеспечивает автоматическое сматывание.",
            'description_ro' => "Tamburul automat M7 {$sku} este echipat cu un furtun de aer de 15 m, cu diametrul interior de {$innerDiameter} mm și diametrul exterior de {$outerDiameter} mm. Carcasa este realizată din {$caseMaterialRo}, iar sistemul cu arc asigură retractarea automată.",
            'attributes' => ['Тип' => 'Катушка с воздушным шлангом', 'Длина шланга' => '15 m', 'Внутренний диаметр' => $innerDiameter.' mm', 'Наружный диаметр' => $outerDiameter.' mm', 'Материал корпуса' => $caseMaterialRu, 'Система сматывания' => 'Автоматическая пружинная'],
        ];
    }

    private function retainer(string $sku, bool $quickChange): array
    {
        $typeRu = $quickChange ? 'Быстросъёмный фиксатор зубила' : 'Фиксатор зубила';
        $typeRo = $quickChange ? 'Retentor rapid pentru daltă' : 'Retentor pentru daltă';
        $featureRu = $quickChange ? 'обеспечивает быструю замену зубила' : 'удерживает зубило во время работы';
        $featureRo = $quickChange ? 'permite schimbarea rapidă a dălții' : 'menține dalta în poziție în timpul lucrului';

        return [
            'name_ru' => "{$typeRu} M7 {$sku}, для серий SC-21, SC-22 и SC-34",
            'name_ro' => "{$typeRo} M7 {$sku}, pentru seriile SC-21, SC-22 și SC-34",
            'description_ru' => "{$typeRu} M7 {$sku} {$featureRu} и совместим с пневмомолотками серий SC-21, SC-22 и SC-34.",
            'description_ro' => "{$typeRo} M7 {$sku} {$featureRo} și este compatibil cu ciocanele pneumatice din seriile SC-21, SC-22 și SC-34.",
            'attributes' => ['Тип' => $typeRu, 'Совместимость' => 'SC-21, SC-22, SC-34'],
            'needs_image_review' => true,
        ];
    }

    private function updateProduct(object $product, array $content): void
    {
        $now = now();
        $attributes = json_encode($content['attributes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $needsImageReview = (bool) ($content['needs_image_review'] ?? false);

        DB::table('products')->where('id', $product->id)->update([
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
            'needs_image_review' => $needsImageReview,
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ]);

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
            'needs_image_review' => $needsImageReview,
            'needs_content_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ];
        if ($needsImageReview) {
            $parserUpdates['image_reviewed_at'] = null;
        }

        DB::table('product_parser_items')->where('id', $product->source_parser_item_id)->update($parserUpdates);
    }

    public function down(): void
    {
        // Curated M7 SKU-family content is intentionally retained.
    }
};
