<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sets = [
            '2509SR' => ['socket', '1/4″'],
            '2510SR' => ['socket', '1/4″'],
            '2526SR' => ['socket', '1/4″'],
            '2539MR-AM' => ['socket', '1/4″'],
            '2547MR-EB' => ['socket', '1/4″'],
            '3034MR' => ['socket', '3/8″'],
            '3527MR' => ['socket', '3/8″'],
            '3533MRV' => ['socket', '3/8″'],
            '4406MX' => ['impact', '1/2″'],
            '4410MP01' => ['impact', '1/2″'],
            '4414MP' => ['impact', '1/2″'],
            '4415MP' => ['impact', '1/2″'],
            '4415MP03' => ['impact', '1/2″'],
            '4419MP' => ['impact', '1/2″'],
            '44904MP02' => ['impact-wheel', '1/2″'],
            '4501MR' => ['socket', '1/2″'],
            '4510SR' => ['socket', '1/2″'],
            '4526SR03' => ['socket', '1/2″'],
            '6016SR' => ['socket', '3/4″'],
            '6409MP03' => ['impact', '3/4″'],
            '6410MP' => ['impact', '3/4″'],
            '6410SP' => ['socket', '3/4″'],
            '6414MP' => ['impact', '3/4″'],
            '7126PR' => ['torx', '1/4″ и 1/2″'],
            '8015SR' => ['socket', '1″'],
            '8409MP' => ['impact-deep', '1″'],
            '8410MP' => ['impact-deep', '1″'],
            '9-2565MRV' => ['socket', '1/4″'],
            '9-4419MPV' => ['impact-deep', '1/2″'],
            '9-4427MP' => ['impact', '1/2″'],
            '9-4435MP' => ['impact-deep', '1/2″'],
            '9-4537MRV' => ['socket', '1/2″'],
            '9-4827MP' => ['impact', '1/2″'],
            '9-5575MRV02' => ['socket', '1/4″ и 3/8″'],
            '9-6314MRV' => ['socket', '3/4″'],
            '9-6414MPV' => ['impact-deep', '3/4″'],
            '9-7540MR' => ['socket', '1/4″ и 1/2″'],
            'ST4028SR' => ['socket', '1/2″'],
            'ST4528SR' => ['socket', '1/2″'],
        ];

        DB::transaction(function () use ($sets): void {
            DB::table('products')->orderBy('id')->chunkById(500, function ($products): void {
                foreach ($products as $product) {
                    $updates = [];
                    $nameRu = trim((string) $product->name_ru);
                    $nameRo = trim((string) $product->name_ro);
                    $fixedRu = $this->capitalize($nameRu);
                    $fixedRo = $this->capitalize($nameRo);

                    if ($fixedRu !== $nameRu) {
                        $updates['name'] = $fixedRu;
                        $updates['name_ru'] = $fixedRu;
                        $updates['meta_title'] = $fixedRu.' | MasterScule.md';
                    }
                    if ($fixedRo !== $nameRo) {
                        $updates['name_ro'] = $fixedRo;
                    }
                    if ($updates !== []) {
                        $updates['updated_at'] = now();
                        DB::table('products')->where('id', $product->id)->update($updates);
                    }
                }
            });

            foreach ($sets as $sku => [$kind, $drive]) {
                [$nameRu, $nameRo] = $this->setNames($sku, $kind, $drive);
                DB::table('products')->where('sku', $sku)->update([
                    'name' => $nameRu,
                    'name_ru' => $nameRu,
                    'name_ro' => $nameRo,
                    'meta_title' => $nameRu.' | MasterScule.md',
                    'updated_at' => now(),
                ]);
            }

            $this->updateJtcPliers();
            $this->moveProducts(
                ['4410MP01', '4414MP', '4415MP', '4415MP03', '4419MP'],
                'capete-tubulare-impact',
            );
            $this->moveProducts(['9-5575MRV02', '9-6314MRV'], 'tubulare-si-clichete');

            DB::table('products')->where('sku', '4406MX')->update([
                'attributes' => json_encode([
                    'Тип' => 'Набор ударных торцевых головок',
                    'Привод' => '1/2″',
                    'Материал' => 'Хромомолибденовая сталь',
                    'Кейс' => 'Металлический',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
            DB::table('products')->where('sku', '44904MP02')->update([
                'attributes' => json_encode([
                    'Тип' => 'Набор глубоких тонкостенных ударных головок',
                    'Привод' => '1/2″',
                    'Материал' => 'Хромомолибденовая сталь',
                    'Применение' => 'Колёсные диски',
                    'Защитная втулка' => 'Съёмная пластиковая',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        });
    }

    private function setNames(string $sku, string $kind, string $drive): array
    {
        return match ($kind) {
            'impact' => [
                'Набор ударных торцевых головок King Tony '.$sku.', привод '.$drive,
                'Set de capete tubulare de impact King Tony '.$sku.', antrenare '.$this->romanianDrive($drive),
            ],
            'impact-deep' => [
                'Набор глубоких ударных головок King Tony '.$sku.', привод '.$drive,
                'Set de capete tubulare lungi de impact King Tony '.$sku.', antrenare '.$this->romanianDrive($drive),
            ],
            'impact-wheel' => [
                'Набор тонкостенных ударных головок для колёс King Tony '.$sku.', привод '.$drive,
                'Set de capete de impact cu pereți subțiri pentru roți King Tony '.$sku.', antrenare '.$this->romanianDrive($drive),
            ],
            'torx' => [
                'Набор торцевых головок TORX King Tony '.$sku.', приводы '.$drive,
                'Set de capete tubulare TORX King Tony '.$sku.', antrenări '.$this->romanianDrive($drive),
            ],
            default => [
                'Набор торцевых головок и принадлежностей King Tony '.$sku.', привод '.$drive,
                'Set de capete tubulare și accesorii King Tony '.$sku.', antrenare '.$this->romanianDrive($drive),
            ],
        };
    }

    private function updateJtcPliers(): void
    {
        $nameRu = 'Щипцы для стопорных колец JTC-3316 для Volkswagen и Audi';
        $nameRo = 'Clește pentru inele de siguranță JTC-3316 pentru Volkswagen și Audi';
        DB::table('products')->where('sku', 'JTC-3316')->update([
            'name' => $nameRu,
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description' => 'Щипцы предназначены для снятия и установки стопорных колец на автомобилях Volkswagen и Audi.',
            'short_description_ru' => 'Щипцы предназначены для снятия и установки стопорных колец на автомобилях Volkswagen и Audi.',
            'short_description_ro' => 'Cleștele este destinat demontării și montării inelelor de siguranță la automobilele Volkswagen și Audi.',
            'description' => 'Щипцы предназначены для снятия и установки стопорных колец на автомобилях Volkswagen и Audi. Размер — 135 × 20 × 210 мм; масса — 0,169 кг.',
            'description_ru' => 'Щипцы предназначены для снятия и установки стопорных колец на автомобилях Volkswagen и Audi. Размер — 135 × 20 × 210 мм; масса — 0,169 кг.',
            'description_ro' => 'Cleștele este destinat demontării și montării inelelor de siguranță la automobilele Volkswagen și Audi. Dimensiuni — 135 × 20 × 210 mm; greutate — 0,169 kg.',
            'meta_title' => $nameRu.' | MasterScule.md',
            'meta_description' => 'Щипцы JTC-3316 для стопорных колец Volkswagen и Audi, размер 135 × 20 × 210 мм.',
            'updated_at' => now(),
        ]);
    }

    private function moveProducts(array $skus, string $slug): void
    {
        $categoryId = DB::table('categories')->where('slug', $slug)->value('id');
        if (! $categoryId) {
            return;
        }

        foreach (DB::table('products')->whereIn('sku', $skus)->get(['id', 'category_id']) as $product) {
            DB::table('products')->where('id', $product->id)->update(['category_id' => $categoryId, 'updated_at' => now()]);
            DB::table('category_product')->where('product_id', $product->id)->delete();
            DB::table('category_product')->insert([
                'product_id' => $product->id,
                'category_id' => $categoryId,
                'is_primary' => true,
                'source' => 'verified_generic_set_name_cleanup_2026_08_03',
                'confidence' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function capitalize(string $value): string
    {
        if ($value === '' || preg_match('/^\p{Ll}/u', $value) !== 1) {
            return $value;
        }

        return mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
    }

    private function romanianDrive(string $drive): string
    {
        return str_replace(' и ', ' și ', $drive);
    }

    public function down(): void
    {
        // Verified naming and category corrections are intentionally irreversible.
    }
};
