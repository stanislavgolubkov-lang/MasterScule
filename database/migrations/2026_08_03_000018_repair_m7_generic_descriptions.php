<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->skus() as $sku) {
            $product = DB::table('products')->where('sku', $sku)->first(['id', 'attributes']);
            if (! $product) {
                continue;
            }
            $attributes = json_decode((string) $product->attributes, true) ?: [];
            $data = $this->content($sku, $attributes);
            if ($data === null) {
                continue;
            }
            $shortRu = Str::limit($data['description_ru'], 220, '');
            $shortRo = Str::limit($data['description_ro'], 220, '');

            DB::table('products')->where('id', $product->id)->update([
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

    private function skus(): array
    {
        return [
            'NC-0208M', 'JC-507A', 'ZB-812XL', 'ZB-812XXL', 'ZB-814XL', 'ZB-814XXL', 'ZB-814M',
            'QB-91110', 'QB-91106', 'QB-91108', 'QB-91210', 'QB-91208', 'QB-7114', 'QB-7115M',
            'QP-327M', 'QB-9315', 'QB-9305', 'QB-9306', 'QB-49602P08', 'QB-51602P21',
        ];
    }

    private function content(string $sku, array $attributes): ?array
    {
        if ($sku === 'NC-0208M') {
            return $this->item(
                'Пневматический гайковёрт M7 NC-0208M для свечей накаливания, 1/4″',
                'Cheie pneumatică M7 NC-0208M pentru bujii incandescente, 1/4″',
                'Компактный пневматический гайковёрт M7 NC-0208M с приводом 1/4″ предназначен для работы со свечами накаливания. Момент 10–40 Н·м, скорость 11 000 об/мин, расход воздуха 150 л/мин, рабочее давление 6,3 бар, масса 0,8 кг.',
                'Cheia pneumatică compactă M7 NC-0208M cu antrenare de 1/4″ este destinată lucrului la bujiile incandescente. Cuplu 10–40 N·m, viteză 11 000 rpm, consum de aer 150 l/min, presiune 6,3 bar, masă 0,8 kg.');
        }
        if ($sku === 'JC-507A') {
            return $this->item(
                'Пневматический продувочный пистолет M7 JC-507A',
                'Pistol pneumatic de suflare M7 JC-507A',
                'Продувочный пистолет M7 JC-507A работает при давлении 5,3–8,5 бар и расходует 398 л/мин. Сопло регулируется по длине 178–305 мм, воздушный штуцер 1/4″, масса 0,22 кг.',
                'Pistolul pneumatic de suflare M7 JC-507A funcționează la 5,3–8,5 bar și consumă 398 l/min. Duza are lungime reglabilă 178–305 mm, racord de aer 1/4″, masă 0,22 kg.');
        }

        if (preg_match('/^ZB-812(XL|XXL)$/', $sku, $match) === 1) {
            return $this->item(
                "Антивибрационные перчатки M7 {$sku}, размер {$match[1]}",
                "Mănuși antivibrații M7 {$sku}, mărimea {$match[1]}",
                "Защитные перчатки M7 {$sku} размера {$match[1]} предназначены для снижения вибрационной нагрузки при работе с ручным и пневматическим инструментом.",
                "Mănușile de protecție M7 {$sku}, mărimea {$match[1]}, sunt destinate reducerii vibrațiilor la utilizarea sculelor manuale și pneumatice.");
        }
        if (preg_match('/^ZB-814(XL|XXL|M)$/', $sku, $match) === 1) {
            return $this->item(
                "Антивибрационные перчатки без пальцев M7 {$sku}, размер {$match[1]}",
                "Mănuși antivibrații fără degete M7 {$sku}, mărimea {$match[1]}",
                "Перчатки без пальцев M7 {$sku} размера {$match[1]} обеспечивают защиту от вибрации при сохранении точности захвата. Уровень защиты указан производителем как EN 388 Grade 2.",
                "Mănușile fără degete M7 {$sku}, mărimea {$match[1]}, reduc vibrațiile și păstrează precizia prizei. Nivelul de protecție indicat de producător este EN 388 Grade 2.");
        }

        if (preg_match('/^QB-91(1|2)(10|08|06)$/', $sku) === 1) {
            $size = (string) ($attributes['Размер шлиф.ленты'] ?? '');
            $sizeRo = $this->roTechnical($size);
            $grit = (string) ($attributes['Зерно абразива'] ?? '');
            $nameRu = "Абразивные ленты M7 {$sku}, {$size}, зернистость {$grit}, 10 шт.";
            $nameRo = "Benzi abrazive M7 {$sku}, {$sizeRo}, granulație {$grit}, 10 buc.";

            return $this->item($nameRu, $nameRo,
                "Комплект из 10 абразивных лент M7 {$sku} размером {$size} и зернистостью {$grit} предназначен для ленточных пневматических шлифмашин совместимого размера.",
                "Setul conține 10 benzi abrazive M7 {$sku}, dimensiune {$sizeRo}, granulație {$grit}, pentru mașini pneumatice de șlefuit cu bandă compatibile.");
        }

        if (in_array($sku, ['QB-7114', 'QB-7115M'], true)) {
            $diameter = (string) ($attributes['Диаметр диска'] ?? '');
            $mount = (string) ($attributes['Посадочное место'] ?? '');
            $diameterRo = $this->roTechnical($diameter);
            $mountRo = $this->roTechnical($mount);
            $nameRu = "Пневматическая угловая шлифмашина M7 {$sku}, диск {$diameter}";
            $nameRo = "Polizor unghiular pneumatic M7 {$sku}, disc {$diameterRo}";

            return $this->item($nameRu, $nameRo,
                "Пневматическая угловая шлифмашина M7 {$sku} рассчитана на диск {$diameter}, посадка {$mount}. Скорость 11 000 об/мин, расход воздуха 119 л/мин, рабочее давление 6,3 бар, масса 1,81 кг.",
                "Polizorul unghiular pneumatic M7 {$sku} utilizează disc {$diameterRo}, prindere {$mountRo}. Viteză 11 000 rpm, consum de aer 119 l/min, presiune 6,3 bar, masă 1,81 kg.");
        }
        if ($sku === 'QP-327M') {
            return $this->item(
                'Пневматическая полировальная машина M7 QP-327M, 178 мм',
                'Mașină pneumatică de polisat M7 QP-327M, 178 mm',
                'Полировальная машина M7 QP-327M оснащена подошвой 178 мм и развивает 2500 об/мин. Расход воздуха 169 л/мин, рабочее давление 6,3 бар, мощность 0,40 л.с., масса 2,04 кг.',
                'Mașina pneumatică de polisat M7 QP-327M are taler de 178 mm și viteză de 2500 rpm. Consum de aer 169 l/min, presiune 6,3 bar, putere 0,40 CP, masă 2,04 kg.');
        }

        $pads = [
            'QB-9315' => ['127 мм', '6', 'cu 6 orificii'],
            'QB-9305' => ['127 мм', '0', 'fără orificii'],
            'QB-9306' => ['152 мм', '0', 'fără orificii'],
            'QB-49602P08' => ['152 мм', '0', 'fără orificii'],
            'QB-51602P21' => ['152 мм', '15', 'cu 15 orificii'],
        ];
        if (isset($pads[$sku])) {
            [$diameter, $holes, $holesRo] = $pads[$sku];
            $diameterRo = $this->roTechnical($diameter);
            $holesRu = $holes === '0' ? 'без отверстий' : "с {$holes} отверстиями";

            return $this->item(
                "Сменная шлифовальная подошва M7 {$sku}, {$diameter}, {$holesRu}",
                "Taler de șlefuit M7 {$sku}, {$diameterRo}, {$holesRo}",
                "Сменная подошва M7 {$sku} диаметром {$diameter}, {$holesRu}, крепится к совместимой шлифмашине резьбой M6. Масса 0,13 кг.",
                "Talerul de schimb M7 {$sku}, diametru {$diameterRo}, {$holesRo}, se fixează pe mașina compatibilă prin filet M6. Masă 0,13 kg.");
        }

        return null;
    }

    private function item(string $nameRu, string $nameRo, string $descriptionRu, string $descriptionRo): array
    {
        return [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
        ];
    }

    private function roTechnical(string $value): string
    {
        return strtr($value, ['мм' => 'mm', 'М' => 'M', 'Р' => 'P', 'х' => '×']);
    }

    public function down(): void
    {
        // Verified SKU descriptions are intentionally retained.
    }
};
