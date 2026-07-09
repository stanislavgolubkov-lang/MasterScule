<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProductCatalogClassifier
{
    private ?Collection $categories = null;

    public function classify(Product $product): array
    {
        $this->loadCategories();

        $text = $this->text($product);
        $sku = Str::upper((string) $product->sku);
        $brand = $this->normalize((string) ($product->brand?->name.' '.$product->brand?->slug));
        $scores = [];
        $reasons = [];

        $add = function (string $slug, int $score, string $reason) use (&$scores, &$reasons): void {
            if (! $this->category($slug)) {
                return;
            }

            $scores[$slug] = ($scores[$slug] ?? 0) + $score;
            $reasons[$slug][] = $reason;
        };

        $has = fn (array $needles): bool => $this->containsAny($text, $needles);
        $skuStarts = fn (array $prefixes): bool => collect($prefixes)->contains(fn ($prefix) => Str::startsWith($sku, Str::upper($prefix)));

        if ($this->containsAny($brand, ['jtc'])) {
            $add('scule-speciale-auto', 70, 'brand JTC');
        }

        if ($this->containsAny($brand, ['torin', 'tongrun', 'big red'])) {
            $add('echipamente-pentru-service', 75, 'brand hydraulic/service equipment');
        }

        if ($this->containsAny($brand, ['hoegert', 'hogert', 'högert'])) {
            $add('instrument-manual', 45, 'brand Hoegert general hand tools');
        }

        $isAccumulator = $has(['аккумулятор', 'акумулятор', 'acumulator', 'cordless', 'battery', 'charger', 'зарядн', '20v', '18v'])
            || $skuStarts(['DS-', 'DW-', 'DB-', 'DC-']);

        if ($isAccumulator) {
            $add('instrumente-cu-acumulator', 80, 'battery/cordless wording or SKU');

            if ($has(['гайковерт', 'гайковёрт', 'impact', 'ударн', 'pistol impact']) || $skuStarts(['DW-', 'DS-'])) {
                $add('pistoale-impact-cu-acumulator', 88, 'cordless impact tool');
                $add('chei-cu-acumulator', 70, 'cordless wrench family');
            }

            if ($has(['аккумулятор', 'battery', 'charger', 'зарядн', 'incarcator'])) {
                $add('baterii-incarcatoare', 92, 'battery or charger');
            }
        }

        if ($has(['компрессор', 'compresor', 'compressor'])) {
            $add('compresoare', 110, 'compressor');
            $add('scule-pneumatice', 35, 'compressor belongs to pneumatic area');
        }

        $isPneumatic = $has(['пневмо', 'pneumatic', 'aer comprimat', 'air tool'])
            || ($this->containsAny($brand, ['m7', 'mighty seven']) && ! $isAccumulator)
            || $skuStarts(['NC-', 'QB-', 'QD-', 'QE-', 'QP-', 'QT-', 'RA-', 'SG-', 'SX-', 'SB-', 'SN-', 'NE-']);

        if ($isPneumatic) {
            $add('scule-pneumatice', 70, 'pneumatic brand/wording/SKU');

            if ($has(['гайковерт', 'гайковёрт', 'impact wrench', 'pistol impact']) || $skuStarts(['NC-'])) {
                $add('chei-pneumatice', 90, 'pneumatic impact wrench');
                $add('pistoale-pneumatice-si-impact', 75, 'legacy pneumatic impact section');
            }

            if ($has(['трещот', 'ratchet', 'clichet'])) {
                $add('clichete-pneumatice', 88, 'pneumatic ratchet');
            }

            if ($has(['молот', 'ciocan', 'hammer'])) {
                $add('ciocane-pneumatice', 88, 'pneumatic hammer');
            }

            if ($has(['дрель', 'gaurit', 'drill'])) {
                $add('burghie-pneumatice', 88, 'pneumatic drill');
            }

            if ($has(['шуруповерт', 'шуруповёрт', 'отвертк', 'screwdriver', 'surubelnita'])) {
                $add('surubelnite-pneumatice', 88, 'pneumatic screwdriver');
            }

            if ($has(['шлиф', 'полир', 'grinder', 'sander', 'polizor', 'orbital'])) {
                $add('polizoare-si-slefuitoare-pneumatice', 88, 'pneumatic grinding/sanding');
            }

            if ($has(['силикон', 'silicon', 'смаз', 'grease', 'gresare'])) {
                $add('pistoale-pentru-silicon-si-gresare', 105, 'silicone/grease pneumatic gun');
            }

            if ($has(['шланг', 'муфта', 'быстросъем', 'быстросъём', 'fitting', 'coupler', 'racord', 'hose']) || $skuStarts(['SG-', 'SX-', 'SB-'])) {
                $add('furtunuri-cuple-accesorii', 88, 'pneumatic hose/coupler/accessory');
                $add('accesorii-pneumatice', 55, 'legacy pneumatic accessories');
            }

            if ($has(['продув', 'пескостру', 'suflat', 'sablare', 'blow gun'])) {
                $add('pistoale-suflat-si-sablare', 88, 'blow/sandblasting pneumatic gun');
            }

            if ($has(['заклеп', 'степлер', 'гвозд', 'nituit', 'capsator'])) {
                $add('nituitoare-capsatoare-si-cuie-pneumatice', 88, 'riveter/stapler/nailer');
            }
        }

        if ($has(['домкрат', 'cric', 'jack', 'подъем', 'подъём', 'ridicare'])) {
            $add('cricuri-hidraulice', 92, 'jack/lifting wording');
            $add('cricuri-si-ridicare', 75, 'legacy lifting section');
            $add('echipamente-pentru-service', 55, 'lifting equipment');
        }

        if ($has(['гидравл', 'hydraulic', 'hidraulic'])) {
            $add('echipamente-pentru-service', 55, 'hydraulic wording');
        }

        if ($has(['пресс', 'press'])) {
            $add('prese-hidraulice', 92, 'press wording');
            $add('echipamente-pentru-service', 55, 'press equipment');
        }

        if ($has(['гидроцилиндр', 'гидравлический насос', 'цилиндр гидравл', 'hydraulic cylinder', 'hydraulic pump'])) {
            $add('pompe-si-cilindri-hidraulici', 84, 'pump/cylinder wording');
            $add('echipamente-pentru-service', 50, 'hydraulic component');
        }

        if ($has(['кран', 'стойка двигателя', 'опора двигателя', 'engine crane', 'engine stand', 'support motor'])) {
            $add('macarale-standuri-suporti-motor', 90, 'engine crane/stand/support');
            $add('echipamente-pentru-service', 50, 'service equipment');
        }

        if ($has(['шкаф', 'тележ', 'верстак', 'ящик', 'органайзер', 'полка', 'хранен', 'dulap', 'carucior', 'trolley', 'cabinet', 'tool chest', 'workbench', 'storage'])) {
            $add('dulapuri-si-organizare', 95, 'workshop storage/furniture wording');
            $add('mobilier-pentru-service', 75, 'workshop furniture parent');
        }

        if ($has(['съемник', 'съёмник', 'puller', 'extractor', 'сепаратор', 'сайлент', 'подшипник', 'шаровой'])) {
            $add('extractoare-si-prese', 90, 'puller/extractor/bearing wording');
            $add('extractoare-si-scule-speciale', 65, 'legacy extractors section');
            $add('scule-speciale-auto', 55, 'special auto tool');
        }

        if ($has(['масляный фильтр', 'масляных фильтров', 'oil filter', 'filtru ulei'])) {
            $add('scule-pentru-filtre-ulei', 92, 'oil filter tool');
            $add('scule-speciale-auto', 50, 'special auto tool');
        }

        if ($has(['тормоз', 'brake', 'frane', 'колод', 'суппорт'])) {
            $add('scule-pentru-frane', 90, 'brake wording');
            $add('scule-motor-frane-suspensie', 62, 'legacy engine/brake/suspension section');
            $add('scule-speciale-auto', 50, 'special auto tool');
        }

        if ($has(['подвес', 'амортиз', 'suspension', 'suspensie', 'стойка mcpherson'])) {
            $add('scule-pentru-suspensie', 90, 'suspension wording');
            $add('scule-motor-frane-suspensie', 62, 'legacy engine/brake/suspension section');
            $add('scule-speciale-auto', 50, 'special auto tool');
        }

        if ($has(['двигател', 'engine', 'timing', 'грм', 'распредвал', 'распределительн', 'коленвал', 'топливн', 'common rail'])) {
            $add('scule-pentru-motor', 90, 'engine/timing wording');
            $add('scule-motor-frane-suspensie', 62, 'legacy engine/brake/suspension section');
            $add('scule-speciale-auto', 50, 'special auto tool');
        }

        if ($has(['диагност', 'scanner', 'obd', 'тестер давления', 'tester presiune'])) {
            $add('diagnoza-auto', 86, 'diagnostics/tester wording');
            $add('scule-speciale-auto', 45, 'special auto tool');
        }

        if ($has(['шиномонтаж', 'колес', 'колёс', 'шина', 'шин', 'anvelope', 'roti', 'wheel', 'tire'])) {
            $add('scule-pentru-roti-vulcanizare', 88, 'wheel/tire wording');
            $add('vulcanizare', 70, 'tire service parent');
        }

        if ($has(['грузов', 'truck', 'camion', 'vehicule grele'])) {
            $add('scule-vehicule-grele', 86, 'heavy vehicle wording');
            $add('scule-speciale-auto', 45, 'special auto tool');
        }

        if ($has(['набор инструмент', 'trusa scule', 'set de scule', 'комплект инструмент', 'tool set'])) {
            $add('seturi-de-scule', 95, 'complete tool set');
            $add('instrument-manual', 25, 'hand tool set');
        } elseif ($has(['набор', 'комплект', 'set ', 'kit ', 'кейс'])) {
            $add('seturi-de-scule', 50, 'set/kit wording');
        }

        if ($has(['динамометр', 'torque', 'dinamometric'])) {
            $add('chei-dinamometrice', 120, 'torque/dynamometric wording');
            $add('instrumente-de-masurare', 20, 'measuring precision');
        }

        if ($has(['головк', 'торцев', 'трубчат', 'трещот', 'вороток', 'удлинител', 'кардан', 'socket', 'ratchet', 'tubular', 'clichet'])) {
            $add('tubulare-si-clichete', 88, 'sockets/ratchets wording');
            $add('instrument-manual', 20, 'manual socket/ratchet tool');
        }

        if ($has(['ударная головка', 'ударн головк', 'impact socket', 'cr-mo', 'crmo'])) {
            $add('capete-tubulare-impact', 92, 'impact socket');
            $add('tubulare-si-clichete', 50, 'socket family');
        }

        if ($has(['отвертк', 'отвёртк', 'screwdriver', 'surubelnita'])) {
            $add('surubelnite-si-biti', 90, 'screwdriver wording');
            $add('chei-si-surubelnite', 55, 'legacy wrench/screwdriver section');
            $add('instrument-manual', 20, 'manual screwdriver');
        }

        if ($has(['бита', 'бит ', 'insert', 'adaptor', 'адаптер', 'переходник'])) {
            $add('biti-insertii-adaptoare', 86, 'bits/inserts/adapters wording');
            $add('tubulare-si-clichete', 42, 'socket accessory');
        }

        if ($has(['ключ', 'рожков', 'накидн', 'комбинирован', 'wrench', 'spanner', 'cheie']) && ! $has(['гайковерт', 'гайковёрт'])) {
            $add('chei-si-surubelnite', 86, 'wrench wording');
            $add('instrument-manual', 20, 'manual wrench');
        }

        if ($has(['клещ', 'плоскогуб', 'кусач', 'ножниц', 'pliers', 'cleste', 'foarfeca'])) {
            $add('clesti-si-instrumente-taiere', 88, 'pliers/cutting wording');
            $add('instrument-manual', 20, 'manual pliers/cutter');
        }

        if ($has(['метчик', 'плашк', 'резьб', 'filet', 'tap ', 'die '])) {
            $add('tarozi-filiere-filetare', 88, 'threading wording');
            $add('instrument-manual', 20, 'manual threading tool');
        }

        if ($has(['напильник', 'пила', 'нож', 'лезвие', 'скреб', 'blade', 'cutit', 'lame'])) {
            $add('taiere-pilire-prelucrare', 84, 'cutting/filing wording');
            $add('instrument-manual', 20, 'manual cutting tool');
        }

        if ($has(['vde', '1000v', 'изолирован', 'диэлектр', 'электрик', 'кабель', 'клемм', 'обжим', 'crimp', 'electromontaj'])) {
            $add('instrumente-electromontaj', 88, 'electrician/VDE/cable wording');

            if ($has(['клещ', 'обжим', 'кабель', 'crimp', 'cleste'])) {
                $add('clesti-electrician-si-cabluri', 92, 'electrician pliers/cable tool');
            }

            if ($has(['тестер', 'индикатор', 'tester', 'indicator'])) {
                $add('testere-electrice-si-indicatoare', 88, 'electrical tester');
            }
        }

        if ($has(['штанген', 'микрометр', 'индикатор', 'subler', 'micrometru', 'comparator'])) {
            $add('sublere-micrometre-comparatoare', 92, 'caliper/micrometer/indicator');
            $add('instrumente-de-masurare', 70, 'measuring tool');
        }

        if ($has(['мультиметр', 'тестер', 'multimeter', 'tester'])) {
            $add('multimetre-testere', 88, 'multimeter/tester');
            $add('instrumente-de-masurare', 60, 'measuring tester');
        }

        if ($has(['рулетка', 'уровень', 'laser', 'лазер', 'nivele', 'ruleta'])) {
            $add('rulete-nivele', 90, 'tape/level/laser measuring');
            $add('instrumente-de-masurare', 70, 'measuring tool');
        }

        if ($has(['манометр', 'измерител', 'измерение', 'verificare', 'instrument control'])) {
            $add('instrumente-control-verificare', 74, 'control/verifying instrument');
            $add('instrumente-de-masurare', 55, 'measuring/checking');
        }

        if ($has(['сверло', 'сверл', 'фрез', 'burghiu', 'freza'])) {
            $add('burghie-freze', 90, 'drills/cutters consumables');
            $add('accesorii-si-consumabile', 55, 'consumable/accessory');
        }

        if ($has(['диск', 'щетка', 'щётка', 'абразив', 'perie', 'disc abraziv']) && ! $has(['трещотка дисков'])) {
            $add('discuri-perii-abrazive', 88, 'discs/brushes/abrasives');
            $add('accesorii-si-consumabile', 55, 'consumable/accessory');
        }

        if ($has(['насадка', 'бита', 'bit ', 'capete'])) {
            $add('biti-si-capete', 70, 'bits and heads accessory wording');
            $add('accesorii-si-consumabile', 42, 'accessory/consumable');
        }

        if ($has(['расходник', 'аксессуар', 'consumabil', 'rezerve'])) {
            $add('accesorii-si-consumabile', 78, 'accessory/consumable wording');
        }

        if ($has(['дрель', 'шуруповерт', 'шуруповёрт', 'болгарка', 'шлифмашина', 'polizor electric', 'bormasina']) && ! $isPneumatic && ! $isAccumulator) {
            $add('electroinstrumente', 76, 'corded power tool wording');

            if ($has(['дрель', 'шуруповерт', 'шуруповёрт', 'gaurit', 'insurubat'])) {
                $add('masini-gaurit-insurubat', 88, 'drilling/driving electric tool');
            }

            if ($has(['болгарка', 'шлиф', 'grinder', 'polizor'])) {
                $add('polizoare', 88, 'grinder/sander electric tool');
            }
        }

        if ($has(['свар', 'рихтов', 'покрас', 'краскопульт', 'tinichigerie', 'vopsire'])) {
            $add('sudura-richtuire-vopsire', 70, 'welding/bodywork/paint wording');
        }

        if ($scores === []) {
            $fallback = $product->category?->slug ?: 'instrument-manual';
            $add($fallback, 35, 'fallback to current category');
        }

        arsort($scores);

        $primarySlug = $this->bestPrimarySlug($scores, $product);
        $selectedSlugs = collect($scores)
            ->filter(fn (int $score, string $slug) => $score >= 55 || $slug === $primarySlug)
            ->keys()
            ->push($primarySlug)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $selectedSlugs = $this->withAncestors($selectedSlugs);

        return [
            'primary_slug' => $primarySlug,
            'category_slugs' => $selectedSlugs,
            'scores' => $scores,
            'reasons' => $reasons,
        ];
    }

    public function idsForSlugs(array $slugs): array
    {
        $this->loadCategories();

        return collect($slugs)
            ->map(fn (string $slug) => $this->category($slug)?->id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function confidenceById(array $scores): array
    {
        $this->loadCategories();
        $confidence = [];

        foreach ($scores as $slug => $score) {
            if ($category = $this->category($slug)) {
                $confidence[$category->id] = max(0, min(100, (int) $score));
            }
        }

        return $confidence;
    }

    private function bestPrimarySlug(array $scores, Product $product): string
    {
        $containers = [
            'instrumente-si-mobilier',
            'instrument-manual',
            'scule-pneumatice',
            'scule-speciale-auto',
            'echipamente-pentru-service',
            'mobilier-pentru-service',
            'electroinstrumente',
            'instrumente-cu-acumulator',
            'instrumente-electromontaj',
            'instrumente-de-masurare',
            'accesorii-si-consumabile',
            'sudura-richtuire-vopsire',
            'vulcanizare',
        ];
        $rank = [
            'instrumente-si-mobilier' => 0,
            'instrument-manual' => 10,
            'scule-pneumatice' => 10,
            'scule-speciale-auto' => 10,
            'echipamente-pentru-service' => 10,
            'mobilier-pentru-service' => 10,
            'electroinstrumente' => 10,
            'instrumente-cu-acumulator' => 10,
            'instrumente-electromontaj' => 10,
            'instrumente-de-masurare' => 10,
            'accesorii-si-consumabile' => 10,
        ];

        $bestSlug = null;
        $bestValue = -1;

        foreach ($scores as $slug => $score) {
            $category = $this->category($slug);
            if (! $category) {
                continue;
            }

            $specificity = in_array($slug, $containers, true)
                ? ($rank[$slug] ?? 5)
                : ($category->parent_id ? 150 : 5);
            $value = ($score * 10) + $specificity;

            if ($value > $bestValue) {
                $bestValue = $value;
                $bestSlug = $slug;
            }
        }

        return $bestSlug ?: ($product->category?->slug ?: 'instrument-manual');
    }

    private function withAncestors(array $slugs): array
    {
        $this->loadCategories();
        $result = collect($slugs);

        foreach ($slugs as $slug) {
            $category = $this->category($slug);

            while ($category?->parent) {
                $category = $category->parent;
                $result->push($category->slug);
            }
        }

        return $result
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function text(Product $product): string
    {
        return $this->normalize(implode(' ', array_filter([
            $product->sku,
            $product->name,
            $product->name_ro,
            $product->short_description,
            $product->description,
            $product->description_ro,
            $product->brand?->name,
            $product->brand?->slug,
            json_encode($product->attributes ?: [], JSON_UNESCAPED_UNICODE),
            json_encode($product->package_contents ?: [], JSON_UNESCAPED_UNICODE),
        ])));
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && Str::contains($text, $this->normalize($needle))) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = Str::lower($value);

        return preg_replace('/\s+/u', ' ', $value) ?: '';
    }

    private function category(string $slug): ?Category
    {
        $this->loadCategories();

        return $this->categories->get($slug);
    }

    private function loadCategories(): void
    {
        if ($this->categories !== null) {
            return;
        }

        $this->categories = Category::with('parent')->get()->keyBy('slug');
    }
}
