<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();

            $this->repairKnownTranslationDefects($now);
            $this->repairHeadingOnlyProducts($now);
            $this->repairMalformedRomanianTitles($now);
            $this->makeDuplicateTitlesProductSpecific($now);
            $this->repairVerifiedCategories($now);
        });
    }

    private function repairKnownTranslationDefects($now): void
    {
        $columns = [
            'name', 'name_ru', 'name_ro',
            'short_description', 'short_description_ru', 'short_description_ro',
            'description', 'description_ru', 'description_ro',
            'meta_title', 'meta_description',
        ];

        DB::table('products')->orderBy('id')->chunkById(250, function ($products) use ($columns, $now): void {
            foreach ($products as $product) {
                $updates = [];

                foreach ($columns as $column) {
                    $original = $product->{$column};
                    if (! is_string($original) || trim($original) === '') {
                        continue;
                    }

                    $value = $original;
                    if (str_ends_with($column, '_ro') || in_array($column, ['meta_title', 'meta_description'], true)) {
                        $value = preg_replace('/\bPriz(?:ă|a) adâncă de impact\b/iu', 'Cap tubular lung de impact', $value) ?? $value;
                        $value = preg_replace('/\bPriz(?:ă|a) de impact\b/iu', 'Cap tubular de impact', $value) ?? $value;
                        $value = preg_replace('/\bLiliac de impact\b/iu', 'Bit de impact', $value) ?? $value;
                        $value = preg_replace('/\b(?:Introduceți|Inserați)\s*\(bit\)/iu', 'Bit', $value) ?? $value;
                        $value = preg_replace('/\b(\d+)\s+(?:cereale|boabe)\b/iu', '$1 muchii', $value) ?? $value;
                        $value = preg_replace('/\bcapete\s+capete\b/iu', 'capete tubulare', $value) ?? $value;
                        $value = preg_replace('/\bindicator\s+indicator\b/iu', 'indicator', $value) ?? $value;
                        $value = preg_replace('/\bnituri\s+nituri\b/iu', 'nituri', $value) ?? $value;

                        if (preg_match('/filtr(?:u|e)(?:lui)? de ulei/iu', $value) === 1) {
                            $value = preg_replace('/\b(\d+)\s*g\./iu', '$1 muchii', $value) ?? $value;
                        }
                    }

                    if (in_array($column, ['name', 'name_ru', 'short_description', 'short_description_ru', 'description', 'description_ru'], true)) {
                        $value = preg_replace('/\bдля\s+для\b/iu', 'для', $value) ?? $value;
                        if (preg_match('/маслян(?:ого|ых|ый|ые)\s+фильтр/iu', $value) === 1) {
                            $value = preg_replace('/\b(\d+)\s*гр\./iu', '$1 граней', $value) ?? $value;
                        }
                    }

                    if ($value !== $original) {
                        $updates[$column] = trim(preg_replace('/\s{2,}/u', ' ', $value) ?? $value);
                    }
                }

                if ($updates !== []) {
                    $updates['updated_at'] = $now;
                    DB::table('products')->where('id', $product->id)->update($updates);
                }
            }
        });
    }

    private function repairHeadingOnlyProducts($now): void
    {
        $records = [
            '1213SRN' => [
                'name_ru' => 'Набор дюймовых комбинированных ключей KING TONY 1213SRN, 13 предметов, чехол Tetoron',
                'name_ro' => 'Set de chei combinate în inch KING TONY 1213SRN, 13 piese, husă Tetoron',
                'description_ru' => 'Набор KING TONY 1213SRN включает 13 комбинированных ключей дюймовых размеров. Ключи предназначены для монтажа и обслуживания резьбовых соединений, а чехол Tetoron обеспечивает компактное хранение и транспортировку комплекта.',
                'description_ro' => 'Setul KING TONY 1213SRN include 13 chei combinate în dimensiuni inch. Cheile sunt destinate montării și întreținerii îmbinărilor filetate, iar husa Tetoron permite depozitarea și transportul compact al setului.',
            ],
            '1216SRN' => [
                'name_ru' => 'Набор дюймовых комбинированных ключей KING TONY 1216SRN, 16 предметов, чехол Tetoron',
                'name_ro' => 'Set de chei combinate în inch KING TONY 1216SRN, 16 piese, husă Tetoron',
                'description_ru' => 'Набор KING TONY 1216SRN включает 16 комбинированных ключей дюймовых размеров. Комплект рассчитан на монтаж и обслуживание резьбовых соединений и поставляется в прочном чехле Tetoron.',
                'description_ro' => 'Setul KING TONY 1216SRN include 16 chei combinate în dimensiuni inch. Setul este destinat montării și întreținerii îmbinărilor filetate și este livrat într-o husă rezistentă Tetoron.',
            ],
            '1306MRN' => [
                'name_ru' => 'Набор разрезных ключей KING TONY 1306MRN, 8–32 мм, 6 предметов, чехол Tetoron',
                'name_ro' => 'Set de chei inelare despicate KING TONY 1306MRN, 8–32 mm, 6 piese, husă Tetoron',
                'description_ru' => 'Набор KING TONY 1306MRN состоит из 6 разрезных ключей размером 8–32 мм. Разрезной профиль облегчает работу с трубными соединениями и штуцерами, а чехол Tetoron удобен для хранения комплекта.',
                'description_ro' => 'Setul KING TONY 1306MRN conține 6 chei inelare despicate cu dimensiuni de 8–32 mm. Profilul despicat facilitează lucrul la racorduri și conducte, iar husa Tetoron este potrivită pentru depozitare.',
            ],
            '1108MR' => [
                'name_ru' => 'Набор рожковых ключей KING TONY 1108MR, 6–22 мм, 8 предметов',
                'name_ro' => 'Set de chei fixe KING TONY 1108MR, 6–22 mm, 8 piese',
                'description_ru' => 'Набор KING TONY 1108MR включает 8 рожковых ключей размером от 6 до 22 мм. Комплект предназначен для монтажа и обслуживания стандартных резьбовых соединений в мастерской и автосервисе.',
                'description_ro' => 'Setul KING TONY 1108MR include 8 chei fixe cu dimensiuni de la 6 la 22 mm. Setul este destinat montării și întreținerii îmbinărilor filetate uzuale în atelier și service auto.',
            ],
            '1110MR' => [
                'name_ru' => 'Набор рожковых ключей KING TONY 1110MR, 6–28 мм, 10 предметов',
                'name_ro' => 'Set de chei fixe KING TONY 1110MR, 6–28 mm, 10 piese',
                'description_ru' => 'Набор KING TONY 1110MR включает 10 рожковых ключей размером от 6 до 28 мм. Комплект предназначен для монтажа и обслуживания стандартных резьбовых соединений в мастерской и автосервисе.',
                'description_ro' => 'Setul KING TONY 1110MR include 10 chei fixe cu dimensiuni de la 6 la 28 mm. Setul este destinat montării și întreținerii îmbinărilor filetate uzuale în atelier și service auto.',
            ],
            '1112MRN' => [
                'name_ru' => 'Набор рожковых ключей KING TONY 1112MRN, 6–32 мм, 12 предметов, чехол Tetoron',
                'name_ro' => 'Set de chei fixe KING TONY 1112MRN, 6–32 mm, 12 piese, husă Tetoron',
                'description_ru' => 'Набор KING TONY 1112MRN включает 12 рожковых ключей размером от 6 до 32 мм. Комплект предназначен для обслуживания резьбовых соединений и поставляется в чехле Tetoron.',
                'description_ro' => 'Setul KING TONY 1112MRN include 12 chei fixe cu dimensiuni de la 6 la 32 mm. Setul este destinat întreținerii îmbinărilor filetate și este livrat într-o husă Tetoron.',
            ],
            '3812' => $this->adapterContent('3812', '3/8″', '1/4″'),
            '3814' => $this->adapterContent('3814', '3/8″', '1/2″'),
            '4813' => $this->adapterContent('4813', '1/2″', '3/8″'),
            '4816' => $this->adapterContent('4816', '1/2″', '3/4″'),
            '6814' => $this->adapterContent('6814', '3/4″', '1/2″'),
            '6818' => $this->adapterContent('6818', '3/4″', '1″'),
            '79815' => [
                'name_ru' => 'Светодиодный фонарь KING TONY 79815, 120 лм, 3 Вт, 3 × AAA',
                'name_ro' => 'Lanternă LED KING TONY 79815, 120 lm, 3 W, 3 × AAA',
                'description_ru' => 'Компактный светодиодный фонарь KING TONY 79815 обеспечивает световой поток 120 лм при мощности 3 Вт. Питание осуществляется от трёх батареек AAA, заявленное время работы составляет до 6,5 часа.',
                'description_ro' => 'Lanterna LED KING TONY 79815 oferă un flux luminos de 120 lm la o putere de 3 W. Este alimentată de trei baterii AAA, iar autonomia declarată este de până la 6,5 ore.',
            ],
        ];

        foreach ($records as $sku => $content) {
            $shortRu = $this->firstSentence($content['description_ru']);
            $shortRo = $this->firstSentence($content['description_ro']);
            DB::table('products')->where('sku', $sku)->update([
                'name' => $content['name_ru'],
                'name_ru' => $content['name_ru'],
                'name_ro' => $content['name_ro'],
                'short_description' => $shortRu,
                'short_description_ru' => $shortRu,
                'short_description_ro' => $shortRo,
                'description' => $content['description_ru'],
                'description_ru' => $content['description_ru'],
                'description_ro' => $content['description_ro'],
                'updated_at' => $now,
            ]);
        }
    }

    private function adapterContent(string $sku, string $from, string $to): array
    {
        return [
            'name_ru' => "Переходник KING TONY {$sku}, с {$from} на {$to}",
            'name_ro' => "Adaptor KING TONY {$sku}, de la {$from} la {$to}",
            'description_ru' => "Переходник KING TONY {$sku} преобразует привод {$from} в {$to}. Предназначен для совместного использования воротков, трещоток и торцевых головок разных присоединительных размеров.",
            'description_ro' => "Adaptorul KING TONY {$sku} transformă pătratul de antrenare de {$from} în {$to}. Este destinat utilizării mânerelor, clichetelor și capetelor tubulare cu dimensiuni de prindere diferite.",
        ];
    }

    private function repairMalformedRomanianTitles($now): void
    {
        $titles = [
            'QT-102' => 'Polizor pneumatic mini M7 QT-102, 7000 rot/min',
            'DG-501A' => 'Polizor unghiular cu acumulator M7 DG-501A, 18 V',
            'NC-4630Q' => 'Cheie pneumatică de impact mini M7 NC-4630Q, 1/2″, 610 Nm, 9000 rot/min',
            'NC-4130' => 'Cheie pneumatică de impact mini M7 NC-4130, 1/2″, 813 Nm, 9000 rot/min',
            'NC-4670Q' => 'Cheie pneumatică de impact mini M7 NC-4670Q, 1/2″, 884 Nm, 9000 rot/min',
            'NC-4650' => 'Cheie pneumatică de impact mini M7 NC-4650, 1/2″, 900 Nm, 9000 rot/min',
            'QG-102' => 'Foarfecă pneumatică dreaptă M7 QG-102, 2600 curse/min, oțel 1,2 mm / inox 1 mm',
            'NE-4901' => 'Clichet pneumatic mini M7 NE-4901, 1/2″, 68 Nm, 1100 rot/min',
            'NE-251' => 'Clichet pneumatic mini M7 NE-251, 1/4″, 30 Nm, 350 rot/min',
            'NE-352' => 'Clichet pneumatic mini M7 NE-352, 3/8″, 30 Nm, 350 rot/min',
            'NE-362' => 'Clichet pneumatic mini M7 NE-362, 3/8″, 50 Nm, 230 rot/min',
            'SY-210F' => 'Cuplă rapidă Europe M7 SY-210F, filet interior 1/4″',
            'SY-210M' => 'Cuplă rapidă Europe M7 SY-210M, filet exterior 1/4″',
            'SY-1413F' => 'Cuplă rapidă compozită Europe M7 SY-1413F, filet interior 1/2″',
            'SY-1213F' => 'Cuplă rapidă compozită Europe M7 SY-1213F, filet interior 1/4″',
            'SY-1313F' => 'Cuplă rapidă compozită Europe M7 SY-1313F, filet interior 3/8″',
            'SY-0413M' => 'Cuplă rapidă compozită Europe M7 SY-0413M, filet exterior 1/2″',
            'SY-0213M' => 'Cuplă rapidă compozită Europe M7 SY-0213M, filet exterior 1/4″',
            'SY-0313M' => 'Cuplă rapidă compozită Europe M7 SY-0313M, filet exterior 3/8″',
            'SY-211F' => 'Racord rapid Europe M7 SY-211F, filet interior 1/4″',
            'SY-211M' => 'Racord rapid Europe M7 SY-211M, filet exterior 1/4″',
            'SA-3215' => 'Tambur cu furtun pneumatic M7 SA-3215, 8/12 mm, lungime 15 m',
            'QB-49602' => 'Polizor orbital pneumatic M7 QB-49602, disc 152 mm (6″), 20000 rot/min, excentricitate 9,5 mm',
            'QB-9337M' => 'Talpă de schimb M7 QB-9337M, M14 × 2,0, 178 mm (7″), pentru QP-327',
            'QB-59642P39' => 'Talpă de schimb M7 QB-59642P39, 152 mm (6″), pentru QB-59642',
            'QB-48111' => 'Șlefuitor pneumatic cu vibrații M7 QB-48111, 100 × 100 mm, 11000 rot/min, excentricitate 2,5 mm',
            'QB-48112' => 'Șlefuitor pneumatic cu vibrații M7 QB-48112, 100 × 100 mm, 11000 rot/min, excentricitate 2,5 mm',
            'QB-53802' => 'Șlefuitor orbital pneumatic cu aspirare M7 QB-53802, 127 mm (5″), 10000 rot/min, excentricitate 5 mm',
            'QB-47612' => 'Șlefuitor orbital pneumatic cu aspirare M7 QB-47612, 152 mm (6″), 10000 rot/min, excentricitate 2,5 mm',
            'QB-47602' => 'Șlefuitor orbital pneumatic cu aspirare M7 QB-47602, 152 mm (6″), 10000 rot/min, excentricitate 5 mm',
            'QB-53902' => 'Șlefuitor orbital pneumatic cu aspirare M7 QB-53902, 152 mm (6″), 10000 rot/min, excentricitate 5 mm',
            'QB-51612' => 'Șlefuitor orbital pneumatic cu aspirare M7 QB-51612, 152 mm (6″), 12000 rot/min, excentricitate 2,5 mm',
            'QB-51602' => 'Șlefuitor orbital pneumatic cu aspirare M7 QB-51602, 152 mm (6″), 12000 rot/min, excentricitate 5 mm',
            'QB-46602' => 'Șlefuitor orbital pneumatic M7 QB-46602, 152 mm (6″), 10000 rot/min, excentricitate 5 mm',
            'JTC-4035' => 'Adaptor pentru rotirea arborelui cotit JTC-4035, VW/Audi, echivalent T40058',
            'JTC-4386' => 'Cap tubular pentru reglarea arborelui cu came JTC-4386, VW/Audi, echivalent T10352-2',
            'JTC-4844' => 'Cap tubular pentru reglarea arborelui cu came JTC-4844, VW/Audi, echivalent T40028',
            'JTC-4102' => 'Cap tubular pentru reglarea arborelui cu came JTC-4102, Audi A3 1.8 4V TFSI, echivalent T103252',
            'JTC-4094' => 'Set de capete tubulare de impact JTC-4094, profil SPLINE cu 12 muchii, 6 piese, pentru VW și Audi',
            'JTC-4456' => 'Set de capete pentru prezoane antifurt JTC-4456, 20 piese, pentru Audi după 2006',
            'JTC-4457' => 'Set de capete pentru prezoane antifurt JTC-4457, 20 piese, pentru VW după 2006',
            'JTC-4502' => 'Extractor pentru roțile arborilor de echilibrare JTC-4502, VAG T10394, pentru JTC-4883 și JTC-4893',
            'JTC-5594' => 'Cap tubular special JTC-5594, profil cu 11 muchii, 3/8″, pentru etrier KNORR BREMSE',
            'JTC-5463' => 'Extractor hidraulic pentru fuzete JTC-5463, 20 t, cursă 22 mm',
            'JTC-7874' => 'Extractor hidraulic pentru fuzete JTC-7874, 75 t, cursă 150 mm',
            'JTC-1425' => 'Set de capete pentru senzori de oxigen JTC-1425, 7 piese, 1/2″',
            'JTC-6869' => 'Set de capete pentru senzori de oxigen și injectoare JTC-6869, 10 piese, 1/2″',
            'JTC-4763' => 'Set de fixare a distribuției JTC-4763, pentru Ford Focus/C-Max 1.6 Ti și 2.0 TDCi',
            'JTC-5031' => 'Recipient gradat pentru lichide tehnice JTC-5031, 5 l, gură 180 mm',
            'JTC-5032' => 'Recipient gradat cu cioc pentru lichide tehnice JTC-5032, 5 l, 170 mm',
            'JTC-3820' => 'Clește cu vârf lung curbat la 45° JTC-3820, 280 mm (11″)',
            'JTC-3311' => 'Clește cu vârf lung curbat la 90° JTC-3311, 280 mm (11″)',
            'JTC-3328' => 'Daltă pentru ciocan pneumatic JTC-3328, 115 mm, tijă rotundă 12 mm',
            'JTC-3329' => 'Daltă pentru ciocan pneumatic JTC-3329, 127 mm, tijă rotundă 15 mm',
            'JTC-4885' => 'Piston hidraulic JTC-4885, 17 t, pentru JTC-1001 și JTC-1610A',
            '3513MR' => 'Set de capete tubulare cu 6 muchii KING TONY 3513MR, 7–19 mm, 3/8″',
            '9AT3-F01' => 'Comparator cu cadran tip micrometru KING TONY 9AT3-F01',
            '79711' => 'Pistol pentru nituri metalice KING TONY 79711, 3,2–6,4 mm, lungime 460 mm',
        ];

        foreach (['302D08', '302D09', '302D10', '302D15', '302D27', '302D30', '302D40', '302D45', '302D50', '302D55', '302D60'] as $sku) {
            $profile = ltrim(substr($sku, 4), '0');
            $titles[$sku] = "Cap cu bit IPR cu 5 muchii KING TONY {$sku}, IPR{$profile}, 3/8″";
        }

        foreach ($titles as $sku => $title) {
            $product = DB::table('products')->where('sku', $sku)->first(['id', 'name_ro', 'short_description_ro', 'description_ro']);
            if (! $product) {
                continue;
            }

            $updates = ['name_ro' => $title, 'updated_at' => $now];
            foreach (['short_description_ro', 'description_ro'] as $column) {
                $current = (string) $product->{$column};
                if ($current !== '' && (string) $product->name_ro !== '') {
                    $updates[$column] = str_replace((string) $product->name_ro, $title, $current);
                }
            }
            DB::table('products')->where('id', $product->id)->update($updates);
        }
    }

    private function makeDuplicateTitlesProductSpecific($now): void
    {
        foreach (['name_ru', 'name_ro'] as $column) {
            $duplicates = DB::table('products')
                ->where('status', 'published')
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->pluck($column);

            foreach ($duplicates as $duplicate) {
                $products = DB::table('products')
                    ->where('status', 'published')
                    ->where($column, $duplicate)
                    ->get(['id', 'sku']);

                foreach ($products as $product) {
                    if (mb_stripos((string) $duplicate, (string) $product->sku) !== false) {
                        continue;
                    }

                    $title = trim((string) $duplicate).' '.trim((string) $product->sku);
                    $updates = [$column => $title, 'updated_at' => $now];
                    if ($column === 'name_ru') {
                        $updates['name'] = $title;
                    }
                    DB::table('products')->where('id', $product->id)->update($updates);
                }
            }
        }
    }

    private function repairVerifiedCategories($now): void
    {
        $groups = [
            'carucioare-de-scule' => [
                '934-010MRV-B', '87SQ31-6B-BK', '87SQ31-7B-BK', '87SQ32-10B-BK',
                '87SQ32-9B-BK', '87SQ33-7B-BK', '87SQ33-8B-BK', '87SQ34-10B-BK',
            ],
            'sudura-richtuire-vopsire' => ['075139', '075160'],
            'scule-pentru-filtre-ulei' => ['320804', '320806', '9AE6-6514', 'HT8G306', 'HT8G307'],
        ];

        $categoryIds = DB::table('categories')->whereIn('slug', array_keys($groups))->pluck('id', 'slug');
        foreach ($groups as $slug => $skus) {
            $categoryId = $categoryIds[$slug] ?? null;
            if (! $categoryId) {
                continue;
            }

            $products = DB::table('products')->whereIn('sku', $skus)->get(['id']);
            foreach ($products as $product) {
                DB::table('products')->where('id', $product->id)->update([
                    'category_id' => $categoryId,
                    'needs_category_review' => false,
                    'updated_at' => $now,
                ]);
                DB::table('category_product')->where('product_id', $product->id)->delete();
                DB::table('category_product')->insert([
                    'product_id' => $product->id,
                    'category_id' => $categoryId,
                    'is_primary' => true,
                    'source' => 'verified_deep_audit',
                    'confidence' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function firstSentence(string $value): string
    {
        if (preg_match('/^(.+?[.!?])(?:\s|$)/u', $value, $match) === 1) {
            return $match[1];
        }

        return $value;
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally not reverted.
    }
};
