<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $mode = 'verified_residual_catalog_cleanup_2026_08_03';

    public function up(): void
    {
        $groups = [
            'echipamente-service' => [
                '057555', '079878', '085916', 'TR6201CX', 'TR6360B', 'TR6800', 'TRT3001-1',
                'HT7G550', 'HT7G559', 'HT7G560', 'HT7G561', 'HT7G563', 'HT7G564',
            ],
            'aspiratoare-industriale' => ['058880', 'WG-202'],
            'prese-hidraulice' => ['PP-8', 'TY12002', 'TY20001', 'TY30002', 'TY50001', 'TY20001FP', 'TY30001FP'],
            'macarale-standuri-suporti-motor' => ['T31002X', 'T32002X', 'TEL05004S', 'TEL08011-sc', 'TRF40753'],
            'capre-auto-si-suporturi' => ['T410001C', 'T42001C', 'T43001C', 'T46001C', 'TRF3201', 'TRF3202'],
            'cricuri-hidraulice' => [
                'TR100001', 'TRA30-2AL', 'TRA40-2AL', 'TRQ35002', 'TRQ50002',
                'TQ20006D', 'TQ30007', 'TQ35001',
            ],
            'scule-pentru-roti-vulcanizare' => ['TRAD010'],
            'echipamente-depozitare-manipulare' => [
                'TRE8220B', 'TRE8315G', 'TRGA2502', 'TDP12003', 'TP05002',
                'HT7G555', 'HT7G556', 'HT7G557', 'HT7G558',
            ],
            'echipamente-schimb-ulei' => [
                'TRG2090', 'TRG2092', 'TRG2093', 'TRG2094', 'TRG6194',
                'HT8G940', 'HT8G947', 'HT8G948',
            ],
            'scule-speciale-auto' => ['TRHS-4029'],
            'extractoare-si-prese' => ['TRHS-4037', 'HT8G218'],
            'diagnoza-auto' => ['TRHS-A0021', 'TRHS-A1998B', 'HT8G370', 'HT8G413', 'HT8G414'],
            'scule-aer-conditionat-auto' => ['TRHS-C1051A', 'HT8G508'],
            'polizoare-si-slefuitoare-pneumatice' => ['051829'],
            'consumabile-pentru-scule-pneumatice' => ['052468'],
            'pistoale-pentru-silicon-si-gresare' => [
                '9BU112T', '9BV1420', 'SG-405', 'SG-407', 'SG-500', 'SG-501',
                'HT4R795', 'HT8G912', 'HT8G913',
            ],
            'foarfeci-ferastraie-si-debitare-pneumatice' => ['PN-150', 'PN-180'],
            'nituitoare-capsatoare-si-cuie-pneumatice' => ['SJ-1650T', 'SJ-1830F', 'SJ-1850F', 'SJ-CN55', 'SU-8016', 'SU-C1022J'],
            'dispozitive-pneumatice-service' => ['TRG5061'],
            'accesorii-pentru-sudura' => [
                '041059_1', '045354', '053366', '053458', '059269', '060753', '064485',
                '067875', '067899', '070646', '072527', '087033', '087194',
                '040168/1', '040175/1', '040212/1', '040236/1', '041592', 'HT3B650',
            ],
            'baterii-incarcatoare' => [
                '9TA34P01', '9TA45P01', '9TA92WA', '026025', '053106',
                'HT2E214', 'HT2E215', 'HT2E216', 'HT2E240-A40', 'HT2E241-A60', 'HT8G602',
            ],
            'chei-cu-acumulator' => ['DW-401A', 'DW-404A', 'DW-601A', 'HT2E253-B14IW', 'HT2E254-B16IW'],
            'testere-electrice-si-indicatoare' => ['024199', '025738', 'HT8G620'],
            'chei-si-surubelnite' => [
                '1710MR', '1C08MR', '1F06MRN', 'HT1R397', 'HT7G146', 'HT7G147', 'HT8G310', 'HT8G311',
            ],
            'tubulare-si-clichete' => ['1842MR', '4116PR', 'HT8G421'],
            'capete-tubulare-impact' => ['4406MX', '44904MP02'],
            'clesti-si-instrumente-taiere' => ['6518-10C', '74250', '74512', '74514'],
            'clesti-electrician-si-cabluri' => ['6AB31-85', '6AB31-85US'],
            'lipire-si-consumabile' => ['6BD22US'],
            'tinichigerie-si-richtuire' => ['057449', 'HT2C082', 'HT4R716', 'HT4R718'],
            'biti-insertii-adaptoare' => [
                '2128PR', 'HT1A770', 'HT1A771', 'HT1A772', 'HT1A773', 'HT1A774', 'HT1A775', 'HT4R323', 'HT4R326',
            ],
            'ochelari-protectie-fata' => ['042698', '042728', '043336', '082809'],
            'imbracaminte-lucru' => ['045224', 'HT5K494', 'HT5K495'],
            'accesorii-protectie' => ['050495', 'HT8G439'],
            'hidrometre-si-refractometre' => ['HT8G419', 'JTC-1039', 'JTC-1040', 'JTC-1041', 'JTC-1524'],
            'seringi-si-palnii' => ['9TB225', 'HT8G936', 'HT8G937'],
            'scule-pentru-tevi' => ['7916-12M', '7916-15M', '7916-21M', 'HT1P626', 'HT1P627'],
            'electroinstrumente' => ['HT2C551', 'HT2C552', 'HT2E105', 'HT2E106'],
            'accesorii-scule-electrice' => ['HT2E115', 'HT2E116'],
            'echipamente-spalare-piese' => ['HT2E400', 'HT2E418'],
            'taiere-pilire-prelucrare' => [
                'HT3B808', 'HT3B809', 'HT8G392', 'HT8G394', 'HT8G395', 'HT8G397', 'HT8G398', 'HT8G408', 'HT8G409',
            ],
            'instrumente-control-verificare' => ['HT4M218', 'HT4R502', 'HT4R504', 'HT8G331', 'HT8G427'],
            'sisteme-de-depozitare-si-transport' => [
                'HT4R512', 'HT4R519', 'HT4R520', 'HT4R521', 'HT4R523',
                'HT7G500', 'HT7G503', 'HT7G504', 'HT7G510', 'HT7G562',
            ],
            'furtunuri-cuple-accesorii' => [
                'HT4R801', 'HT4R802', 'HT4R803', 'HT4R804', 'HT4R812', 'HT4R813', 'HT4R821', 'HT4R876',
            ],
            'burghie-freze' => ['HT6D190'],
            'carucioare-de-scule' => ['HT7G045', 'HT7G048', 'HT7G049'],
            'mobilier-pentru-service' => ['HT7G540', 'HT7G541'],
            'scule-pentru-frane' => ['HT8G372', 'HT8G374'],
            'scule-pentru-motor' => ['HT8G420'],
            'scule-sistem-racire-auto' => ['HT8G426'],
            'accesorii-universale' => [
                'HT8G500', 'HT8G502', 'HT8G503', 'HT8G504', 'HT8G505', 'HT8G507',
                'HT8G509', 'HT8G510', 'HT8G511', 'HT8G512', 'HT8G515',
            ],
            'compresoare' => ['HT8G626'],
        ];

        $content = [
            'TRF40753' => [
                'ru' => 'Стойка трансмиссионная с педалью Torin TRF40753, 0,75 т, 1360–2030 мм',
                'ro' => 'Cric de transmisie cu pedală Torin TRF40753, 0,75 t, 1360–2030 mm',
            ],
            'TRF3201' => [
                'ru' => 'Складная автомобильная опора Torin TRF3201 Heavy Duty, 12 т, 456–710 мм',
                'ro' => 'Suport auto pliabil Torin TRF3201 Heavy Duty, 12 t, 456–710 mm',
            ],
            'TRF3202' => [
                'ru' => 'Складная автомобильная опора Torin TRF3202 Heavy Duty, 12 т, 710–1065 мм',
                'ro' => 'Suport auto pliabil Torin TRF3202 Heavy Duty, 12 t, 710–1065 mm',
            ],
            'T83502' => [
                'ru' => 'Подкатной гидравлический домкрат Torin T83502 с педалью, 3,5 т, 145–500 мм',
                'ro' => 'Cric hidraulic tip cărucior Torin T83502 cu pedală, 3,5 t, 145–500 mm',
            ],
            'TY12001' => [
                'ru' => 'Настольный гидравлический пресс Torin TY12001, 12 т',
                'ro' => 'Presă hidraulică de banc Torin TY12001, 12 t',
            ],
        ];

        DB::transaction(function () use ($groups, $content): void {
            $this->applyCategories($groups);

            foreach ($content as $sku => $copy) {
                $shortRu = $copy['ru'].'.';
                $shortRo = $copy['ro'].'.';
                DB::table('products')->where('sku', $sku)->update([
                    'name' => $copy['ru'],
                    'name_ru' => $copy['ru'],
                    'name_ro' => $copy['ro'],
                    'short_description' => $shortRu,
                    'short_description_ru' => $shortRu,
                    'short_description_ro' => $shortRo,
                    'meta_title' => $copy['ru'].' | MasterScule.md',
                    'meta_description' => $shortRu,
                    'updated_at' => now(),
                ]);
            }
        });
    }

    private function applyCategories(array $groups): void
    {
        $categories = DB::table('categories')->whereIn('slug', array_keys($groups))->pluck('id', 'slug');

        foreach ($groups as $slug => $skus) {
            $targetId = $categories[$slug] ?? null;
            if (! $targetId) {
                continue;
            }

            $products = DB::table('products')->whereIn('sku', $skus)->get(['id', 'sku', 'category_id']);
            foreach ($products as $product) {
                if ((int) $product->category_id === (int) $targetId) {
                    continue;
                }

                $now = now();
                DB::table('products')->where('id', $product->id)->update([
                    'category_id' => $targetId,
                    'needs_category_review' => false,
                    'updated_at' => $now,
                ]);
                DB::table('category_product')->where('product_id', $product->id)->delete();
                DB::table('category_product')->insert([
                    'product_id' => $product->id,
                    'category_id' => $targetId,
                    'is_primary' => true,
                    'source' => $this->mode,
                    'confidence' => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('product_category_decisions')->insert([
                    'product_id' => $product->id,
                    'previous_category_id' => $product->category_id,
                    'selected_category_id' => $targetId,
                    'taxonomy_version' => 'verified-2026-08-03',
                    'input_hash' => hash('sha256', $this->mode.'|'.$product->sku.'|'.$product->category_id.'|'.$targetId),
                    'mode' => $this->mode,
                    'status' => 'applied',
                    'classifier_confidence' => 1,
                    'verifier_confidence' => 1,
                    'evidence' => json_encode(['Exact product type matches the selected assignable catalog category.'], JSON_UNESCAPED_UNICODE),
                    'alternatives' => json_encode([]),
                    'validation_errors' => json_encode([]),
                    'applied_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Verified catalog corrections are intentionally irreversible.
    }
};
