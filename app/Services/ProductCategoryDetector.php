<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductCategoryDetector
{
    public function __construct(
        private ProductParserSettings $settings,
        private ProductCategoryLearningService $learning,
    ) {}

    public function detect(string $sku, string $name, ?string $brand = null, ?string $group = null, ?string $subgroup = null, ?string $vehicleApplication = null): array
    {
        if ($supplierCategory = $this->detectSpinTelwinCategory($sku, $name, $brand, $group, $subgroup)) {
            return $supplierCategory;
        }

        if ($uhlMash = $this->detectUhlMashCategory($sku, $name, $brand, $group, $subgroup)) {
            return $uhlMash;
        }

        $productText = $this->normalize($sku.' '.$name.' '.(string) $brand);
        if (Str::contains($productText, ['катушка с воздуш', 'катушка для воздушного шланга', 'air hose reel'])) {
            $category = Category::where('slug', 'furtunuri-cuple-accesorii')->first();
            if ($category) {
                return $this->categoryResult($category, 99, 'air_hose_reel_family', [
                    "Air hose reel: {$sku} -> furtunuri-cuple-accesorii",
                ]);
            }
        }

        if (Str::startsWith(Str::upper(trim($sku)), 'V7001') || Str::contains($productText, [
            'снятия секреток', 'снятие секреток', 'колесных шпилек', 'колесных гаек',
            'rim lock', 'wheel stud', 'wheel nut',
        ])) {
            $category = Category::where('slug', 'scule-pentru-roti-vulcanizare')->first();
            if ($category) {
                return $this->categoryResult($category, 99, 'wheel_service_family', [
                    "Wheel service tool: {$sku} -> scule-pentru-roti-vulcanizare",
                ]);
            }
        }

        if (Str::contains($productText, ['vde', 'диэлектрическ', 'изолированн'])) {
            $category = Category::where('slug', 'instrumente-izolate-vde')->first();
            if ($category) {
                return $this->categoryResult($category, 99, 'electrical_safety_family', [
                    "VDE insulated tool: {$sku} -> instrumente-izolate-vde",
                ]);
            }
        }

        if ($thinkcar = $this->detectThinkcarCategory($sku, $name, $brand, $group, $subgroup, $vehicleApplication)) {
            return $thinkcar;
        }

        if ($learned = $this->learning->resolve($sku, $brand, $group, $subgroup)) {
            return $this->categoryResult(
                $learned['category'],
                $learned['confidence'],
                $learned['method'],
                [$learned['note']],
            );
        }

        $rules = $this->settings->get('category_rules', config('product_parser.category_rules', []));
        $productText = $this->normalize(implode(' ', array_filter([$sku, $name, $brand, $subgroup, $vehicleApplication])));
        $groupText = $this->normalize((string) $group);
        $scores = [];
        $notes = [];

        foreach (($rules['group_mapping'] ?? []) as $needle => $slug) {
            if ($this->contains($groupText, $needle)) {
                $scores[$slug] = ($scores[$slug] ?? 0) + 18;
                $notes[] = "group: {$needle} -> {$slug}";
                break;
            }
        }

        foreach (($rules['sku_prefixes'] ?? []) as $pattern => $slug) {
            if ($this->skuMatches($sku, $pattern)) {
                $scores[$slug] = ($scores[$slug] ?? 0) + 45;
                $notes[] = "sku: {$pattern} -> {$slug}";
            }
        }

        foreach ($this->subgroupRules() as $needle => $slug) {
            if ($this->contains((string) $subgroup, $needle)) {
                $scores[$slug] = ($scores[$slug] ?? 0) + 95;
                $notes[] = "subgroup: {$needle} -> {$slug}";
                break;
            }
        }

        if ($this->isGysCatalog($brand, $group)) {
            foreach ($this->gysSubgroupRules() as $needle => $slug) {
                if ($this->contains($this->normalize((string) $subgroup), $needle)) {
                    $scores[$slug] = ($scores[$slug] ?? 0) + 98;
                    $notes[] = "GYS subgroup: {$needle} -> {$slug}";
                    break;
                }
            }

            if ($this->contains($this->normalize((string) $subgroup), 'общие')) {
                foreach ($this->gysGeneralRules() as $slug => $keywords) {
                    if (collect($keywords)->contains(fn (string $keyword) => $this->contains($productText, $keyword))) {
                        $scores[$slug] = ($scores[$slug] ?? 0) + 98;
                        $notes[] = "GYS general item -> {$slug}";
                        break;
                    }
                }
            }
        }

        foreach (($rules['keywords'] ?? []) as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->contains($productText, $keyword)) {
                    $scores[$slug] = ($scores[$slug] ?? 0) + 55;
                    $notes[] = "keyword: {$keyword} -> {$slug}";
                }
            }
        }

        foreach ($this->utf8SemanticRules() as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->contains($productText, $keyword)) {
                    $scores[$slug] = ($scores[$slug] ?? 0) + 120;
                    $notes[] = "product: {$keyword} -> {$slug}";
                    break;
                }
            }
        }

        if ($similar = $this->similarProduct($sku, $brand)) {
            $slug = $similar->category?->slug;
            if ($slug) {
                $scores[$slug] = ($scores[$slug] ?? 0) + 28;
                $notes[] = "similar SKU {$similar->sku} -> {$slug}";
            }
        }

        if ($brand && Str::contains(Str::lower($brand), ['m7', 'mighty seven'])) {
            $scores['scule-pneumatice'] = ($scores['scule-pneumatice'] ?? 0) + 10;
            $notes[] = 'brand: M7 gives pneumatic hint';
        }

        if ($brand && Str::contains(Str::lower($brand), ['jtc'])) {
            $scores['scule-speciale-auto'] = ($scores['scule-speciale-auto'] ?? 0) + 35;
            $notes[] = 'brand: JTC gives special auto tools hint';
        }

        if ($brand && Str::contains(Str::lower($brand), ['torin', 'tongrun', 'big red'])) {
            $scores['echipamente-pentru-service'] = ($scores['echipamente-pentru-service'] ?? 0) + 35;
            $notes[] = 'brand: Torin/TONGRUN gives service equipment hint';
        }

        if ($brand && Str::contains(Str::lower($brand), ['hoegert', 'högert', 'hogert'])) {
            $scores['instrument-manual'] = ($scores['instrument-manual'] ?? 0) + 25;
            $notes[] = 'brand: Hoegert gives manual tools hint';
        }

        arsort($scores);
        $slug = array_key_first($scores);
        $score = $slug ? min(98, (int) $scores[$slug]) : 0;
        $category = $slug ? Category::where('slug', $slug)->first() : null;
        $min = $this->minimumConfidence($rules);

        return [
            'category_id' => $score >= $min ? $category?->id : null,
            'detected_category_id' => $category?->id,
            'detected_category_path' => $category ? $this->path($category) : null,
            'category_slug' => $category?->slug,
            'category_name_ru' => $category?->name,
            'category_name_ro' => $category?->name_ro,
            'confidence' => $score,
            'method' => $notes ? 'rules' : 'none',
            'notes' => $notes,
            'needs_review' => ! $category || $score < $min,
        ];
    }

    public function detectFromTrisTools(
        string $sku,
        string $name,
        ?string $brand,
        array $breadcrumb,
        ?string $description = null,
        array $specifications = [],
    ): array {
        if ($supplierCategory = $this->detectSpinTelwinCategory(
            $sku,
            trim($name.' '.(string) $description.' '.implode(' ', $specifications)),
            $brand,
            implode(' ', $breadcrumb),
            collect($breadcrumb)->last(),
        )) {
            return $supplierCategory;
        }

        if ($uhlMash = $this->detectUhlMashCategory(
            $sku,
            trim($name.' '.(string) $description.' '.implode(' ', $specifications)),
            $brand,
            implode(' ', $breadcrumb),
            collect($breadcrumb)->last(),
        )) {
            return $uhlMash;
        }

        if ($thinkcar = $this->detectThinkcarCategory(
            $sku,
            trim($name.' '.(string) $description.' '.implode(' ', $specifications)),
            $brand,
            implode(' ', $breadcrumb),
            collect($breadcrumb)->last(),
        )) {
            return $thinkcar;
        }

        if ($learned = $this->learning->resolveBreadcrumb($breadcrumb, $brand)) {
            return $this->categoryResult(
                $learned['category'],
                $learned['confidence'],
                $learned['method'],
                [$learned['note']],
            );
        }

        $sourceText = $this->normalize(implode(' ', array_filter([
            implode(' ', $breadcrumb),
            $name,
            $description,
            implode(' ', array_keys($specifications)),
            implode(' ', array_values($specifications)),
        ])));

        foreach ($this->trisToolsCategoryRules() as $rule) {
            if (! collect($rule['keywords'])->contains(fn (string $keyword) => $this->contains($sourceText, $keyword))) {
                continue;
            }

            $category = Category::where('slug', $rule['slug'])->first();
            if ($category) {
                return $this->categoryResult(
                    $category,
                    (int) ($rule['confidence'] ?? 96),
                    'tristools_category',
                    ['TrisTool category: '.implode(' > ', $breadcrumb).' -> '.$category->slug],
                );
            }
        }

        return $this->detect(
            $sku,
            trim($name.' '.$sourceText),
            $brand,
            implode(' ', $breadcrumb),
            collect($breadcrumb)->last(),
        );
    }

    private function similarProduct(string $sku, ?string $brand): ?Product
    {
        $family = preg_replace('/\d{1,3}[a-z]*$/iu', '', trim($sku));
        $family = $family && mb_strlen($family) >= 3 ? $family : mb_substr($sku, 0, 4);

        return Product::with(['category', 'brand'])
            ->where('sku', '!=', $sku)
            ->whereNull('source_import_batch_id')
            ->where('sku', 'like', $family.'%')
            ->when($brand, fn ($query) => $query->whereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', '%'.$brand.'%')))
            ->first();
    }

    /**
     * SPIN and TELWIN price lists already contain sufficiently descriptive
     * Russian product names. Deterministic supplier-family rules are faster
     * and more reliable here than waiting for external catalogue searches.
     */
    private function detectSpinTelwinCategory(
        string $sku,
        string $name,
        ?string $brand,
        ?string $group,
        ?string $subgroup,
    ): ?array {
        $brandKey = Str::upper(trim((string) $brand));
        if (! Str::contains($brandKey, ['SPIN', 'TELWIN'])) {
            return null;
        }

        $text = $this->normalize(implode(' ', array_filter([$sku, $name, $group, $subgroup])));

        if (Str::contains($brandKey, 'SPIN')) {
            $rules = [
                'scule-aer-conditionat-auto' => ['кондицион', 'фреон', 'r134', 'r1234', 'хладагент'],
                'scule-sistem-racire-auto' => ['системы охлаждения', 'радиатор', 'антифриз'],
                'diagnoza-auto' => ['диагност', 'детектор', 'проверки давления', 'обнаружения утечек', 'дымогенератор'],
                'tinichigerie-si-richtuire' => ['кузов', 'рихтов', 'покрас', 'инфракрасная сушка'],
                'echipamente-pentru-service' => ['вытяжка выхлопных', 'мойка деталей', 'ультразвуковой ванн', 'регулировки света фар'],
                'mobilier-pentru-service' => ['инструментальной мебели', 'тележк'],
            ];
            $defaultSlug = 'scule-speciale-auto';
        } else {
            $rules = [
                'baterii-incarcatoare' => ['пуско-заряд', 'пусковое устройство', 'зарядное устройство', 'startzilla', 'drive pro', 'sprinter', 'dynamic', 'energy'],
                'tinichigerie-si-richtuire' => ['spotter', 'споттер', 'кузовн', 'рихтов', 'грейфер', 'правки кузова'],
                'accesorii-pentru-sudura' => ['аксессуар', 'кабель', 'горелк', 'электрод', 'зажим', 'клещи', 'пистолет', 'тележка'],
            ];
            $defaultSlug = 'sudura-richtuire-vopsire';
        }

        $slug = $defaultSlug;
        foreach ($rules as $candidate => $keywords) {
            if (collect($keywords)->contains(fn (string $keyword) => $this->contains($text, $keyword))) {
                $slug = $candidate;
                break;
            }
        }

        $category = Category::where('slug', $slug)->first();

        return $category
            ? $this->categoryResult($category, 96, 'supplier_family', ["{$brandKey} product family -> {$slug}"])
            : null;
    }

    /**
     * UHL-MASH supplier headings are frequently shifted by one or more rows,
     * while generic keyword rules confuse key cabinets with wrenches and
     * wardrobe legs with replacement blades. Product-family rules are more
     * reliable than those inherited headings and than stale learned mappings.
     */
    private function detectUhlMashCategory(
        string $sku,
        string $name,
        ?string $brand,
        ?string $group,
        ?string $subgroup,
    ): ?array {
        $identity = $this->normalize(implode(' ', array_filter([$brand, $group])));
        if (! Str::contains($identity, ['ухл-маш', 'ухл маш', 'uhl-mash', 'uhl mash'])) {
            return null;
        }

        $text = $this->normalize(implode(' ', array_filter([$sku, $name])));
        $slug = match (true) {
            Str::contains($text, ['шлифовальный станок', 'точило']) => 'polizoare',
            Str::contains($text, ['тиски слесарн', 'тиски поворотн']) => 'menghine-si-cleme',
            Str::contains($text, ['тележка инструментальн', 'tележка инструментальн', 'инструментальная тележка']) => 'carucioare-de-scule',
            Str::contains($text, ['тележка', 'tележка', 'тележки', 'платформенная', 'передвижная разборная']) => 'carucioare-pentru-rafturi',
            Str::contains($text, [
                'стеллаж', 'стелажи', 'кювет', 'контейнер',
                'полкокомплект стеллажа',
            ]) => 'sisteme-de-depozitare-si-transport',
            Str::contains($text, [
                'панель', 'надставк', 'крючок', 'опора', 'подставка', 'полкокомплект',
                'столешниц', 'тумба верстач', 'блок электромонтажн', 'комплект освещения',
                'замок встраиваемый', 'ножки для', 'полка шириной',
            ]) => 'accesorii-pentru-bancuri-de-lucru',
            Str::contains($text, [
                'кешбокс', 'ключниц', 'аптечк', 'шкаф', 'шкафы', 'локер',
            ]) => 'dulapuri-si-organizare',
            default => 'mobilier-pentru-service',
        };

        $category = Category::where('slug', $slug)->first()
            ?: Category::where('slug', 'mobilier-pentru-service')->first();

        return $category
            ? $this->categoryResult($category, 99, 'uhl_mash_family', ["UHL-MASH family: {$sku} -> {$category->slug}"])
            : null;
    }

    /**
     * THINKCAR price lists use supplier groups that are too broad to be useful
     * (for example, scanners and lifts can both be placed under adapters).
     * Product-family rules are deterministic and deliberately run before
     * learned breadcrumb mappings so a bad supplier breadcrumb is not memorised.
     */
    private function detectThinkcarCategory(
        string $sku,
        string $name,
        ?string $brand,
        ?string $group,
        ?string $subgroup,
        ?string $vehicleApplication = null,
    ): ?array {
        $identity = $this->normalize($sku.' '.(string) $brand);
        $skuUpper = Str::upper(trim($sku));
        $knownPrefix = collect([
            'THINK', 'VENU', 'T-WAND', 'PPS', 'TJS', 'TBT', 'TWB', 'TFJ', 'TWA',
            'TCR', 'PLD', 'TTE', 'GDI', 'TES', 'ES-', 'MCU', 'EVC', 'TVL', 'EVP',
            'TPC', 'TKD', 'DML-',
        ])->contains(fn (string $prefix) => Str::startsWith($skuUpper, $prefix));

        if (! Str::contains($identity, ['thinkcar', 'thinckar', 'thinсkar']) && ! $knownPrefix) {
            return null;
        }

        $productText = $this->normalize(implode(' ', array_filter([$sku, $name, $brand])));
        $text = $this->normalize(implode(' ', array_filter([
            $sku,
            $name,
            $brand,
            $group,
            $subgroup,
            $vehicleApplication,
        ])));

        $slug = match (true) {
            Str::startsWith($skuUpper, ['TBT', 'BATTERYTESTER'])
                || Str::contains($productText, ['тестер аккумуляторных батарей', 'тестер акб', 'battery tester']) => 'multimetre-testere',
            Str::startsWith($skuUpper, ['PPS', 'TJS', 'EVP'])
                || Str::contains($productText, ['зарядное устройство', 'пусковое устройство', 'зарядки/разрядки акб', 'модуль-эквалайзер для акб']) => 'baterii-incarcatoare',
            Str::contains($productText, ['tpms', 'датчик давления в шинах', 'датчиков давления']) => 'sisteme-tpms',
            Str::contains($skuUpper, ['28127_', '2004780_']),
            Str::contains($text, ['сход-развал', 'развал схожд', 'wheel alignment']) => 'scule-pentru-roti-vulcanizare',
            Str::startsWith($skuUpper, 'TWB')
                || Str::contains($productText, ['балансировочный станок', 'балансировочный стенд']) => 'scule-pentru-roti-vulcanizare',
            Str::startsWith($skuUpper, 'TCR-333')
                || Str::contains($productText, ['катушка с электрическим кабелем', 'электрический кабель на катушке']) => 'prelungitoare-si-tamburi-cablu',
            Str::startsWith($skuUpper, 'DML-'),
            Str::contains($text, ['кондиционер', 'ac100', 'filter drier', 'фильтр-осушитель']) => 'scule-aer-conditionat-auto',
            Str::contains($text, ['стойки автомобильные', 'подставка под автомобиль']) => 'capre-auto-si-suporturi',
            Str::startsWith($skuUpper, 'TFJ') || Str::contains($text, ['домкрат']) => 'cricuri-hidraulice',
            Str::startsWith($skuUpper, 'TVL') || Str::contains($text, ['подъёмник', 'подъемник', 'vehicle lift']) => 'elevatoare-auto',
            Str::startsWith($skuUpper, 'TTE') || Str::contains($text, ['замены масла в акпп', 'обмена масла акпп']) => 'echipamente-schimb-ulei',
            Str::startsWith($skuUpper, 'GDI') || Str::contains($text, ['форсунк']) => 'scule-pentru-motor',
            Str::startsWith($skuUpper, 'TPC') || Str::contains($text, ['тележка диагностическая']) => 'carucioare-pentru-rafturi',
            Str::startsWith($skuUpper, ['PLD', 'TES', 'ES-', 'MCU', 'EVC', 'TKD'])
                || Str::contains($text, [
                    'сканер автомобильный', 'диагности', 'дымогенератор', 'видеоэндоскоп',
                    'программатор', 'thinktool', 'thinkdiag', 'осциллограф', 'тепловизор',
                    'тестер изоляции',
                ]) => 'diagnoza-auto',
            default => null,
        };

        if (! $slug) {
            return null;
        }

        $category = Category::where('slug', $slug)->first();

        return $category
            ? $this->categoryResult($category, 99, 'thinkcar_family', ["THINKCAR family: {$sku} -> {$slug}"])
            : null;
    }

    private function subgroupRules(): array
    {
        return [
            'гайковёрты' => 'chei-pneumatice',
            'гайковерты' => 'chei-pneumatice',
            'дрели пневматические' => 'burghie-pneumatice',
            'заклёпочники' => 'nituitoare-capsatoare-si-cuie-pneumatice',
            'заклепочники' => 'nituitoare-capsatoare-si-cuie-pneumatice',
            'измерительный инструмент' => 'chei-dinamometrice',
            'молотки пневматические' => 'ciocane-pneumatice',
            'отвёртки пневматические' => 'surubelnite-pneumatice',
            'отвертки пневматические' => 'surubelnite-pneumatice',
            'продувочные пистолеты' => 'pistoale-suflat-si-sablare',
            'режущий инструмент' => 'foarfeci-ferastraie-si-debitare-pneumatice',
            'спец. одежда' => 'echipament-protectie',
            'шланги и разъёмы' => 'furtunuri-cuple-accesorii',
            'шланги и разъемы' => 'furtunuri-cuple-accesorii',
            'шлифмашинки ленточные' => 'polizoare-si-slefuitoare-pneumatice',
            'шлифмашинки орбитальные' => 'polizoare-si-slefuitoare-pneumatice',
        ];
    }

    private function isGysCatalog(?string $brand, ?string $group): bool
    {
        return preg_match('/(^|\W)gys(\W|$)/iu', trim((string) $brand).' '.trim((string) $group)) === 1;
    }

    private function gysSubgroupRules(): array
    {
        return [
            'аксессуары для mig' => 'sudura-richtuire-vopsire',
            'расходник для mig' => 'sudura-richtuire-vopsire',
            'аксессуары mma' => 'sudura-richtuire-vopsire',
            'запчасти mma' => 'sudura-richtuire-vopsire',
            'расходник для tig' => 'sudura-richtuire-vopsire',
            'аксесcуары для tig' => 'sudura-richtuire-vopsire',
            'аксессуары для tig' => 'sudura-richtuire-vopsire',
            'индукционные аппараты' => 'sudura-richtuire-vopsire',
            'pac аппараты плазменной резки' => 'sudura-richtuire-vopsire',
            'инструменты для куз.ремонта' => 'tinichigerie-si-richtuire',
            'технология pdr' => 'tinichigerie-si-richtuire',
            'расходник для куз.ремонта' => 'tinichigerie-si-richtuire',
            'запчасти к.р.' => 'tinichigerie-si-richtuire',
            'маски и защита' => 'echipament-protectie',
            'автономные пусковые устройства' => 'baterii-incarcatoare',
            'запчасти з.у.' => 'baterii-incarcatoare',
            'кабеля и акссесуары' => 'baterii-incarcatoare',
            'тестеры' => 'multimetre-testere',
        ];
    }

    private function gysGeneralRules(): array
    {
        return [
            'instrumente-electromontaj' => ['vde', 'изолированных инструмент'],
            'compresoare' => ['компрессор'],
            'baterii-incarcatoare' => ['зарядное устройство'],
            'sudura-richtuire-vopsire' => [
                'охлаждающая жидкость для сварочных',
                'паста против сварочных',
                'спрей против сварочных',
                'контактной смазкой',
            ],
            'echipamente-pentru-service' => ['пылесос', 'стул автослесаря', 'мешков для пыли'],
            'accesorii-universale' => ['фонарь', 'лампа диодная', 'прожектор'],
        ];
    }

    private function utf8SemanticRules(): array
    {
        return [
            'diagnoza-auto' => ['видеоэндоскоп', 'видео эндоскоп', 'videoendoscop', 'video endoscope'],
            'furtunuri-cuple-accesorii' => ['смазочная муфта', 'быстросъём', 'быстросъем', 'быстроразъём europe', 'быстроразъем europe', 'быстроразъём композит', 'быстроразъем композит', 'пневмошланг', 'воздушный шланг', 'воздушным шлангом', 'катушка с воздушным', 'шланг полиуретановый', 'ниппель', 'фитинг', 'фильтр-редуктор', 'наконечник europe'],
            'consumabile-pentru-scule-pneumatice' => ['точильный камень', 'точильных камней', 'зачистной диск', 'диск зачистной', 'круг отрезной', 'диск наждачный', 'диск полировочный', 'лента абразивная', 'лента образивная', 'сменная подошва', 'набор зубил', 'набор напильников', 'пила сменная', 'пилы сменные', 'патрон зажимной', 'патрон быстро-зажимной', 'сверло с титановым', 'прицел с пузырьковым уровнем', 'быстроразъём для фиксатора', 'фиксатор для зубил'],
            'polizoare-si-slefuitoare-pneumatice' => ['шлифовальная машин', 'шлифмашинка', 'пневмошлиф', 'турбинка', 'полировальная машин', 'полировочная машин', 'углошлифовальная', 'зачистная машина', 'удаления ржавчины', 'фрезер', 'фрейзер'],
            'pistoale-suflat-si-sablare' => ['пистолет моечный', 'пистолет очиститель', 'пистолет продувочный', 'пескоструй', 'подкачки шин', 'пенообразователь', 'tornador', 'распылитель'],
            'pistoale-pentru-silicon-si-gresare' => ['пистолет для смазки', 'шприц смазочный', 'пистолет для силикона'],
            'chei-pneumatice' => ['пневмогайковерт', 'пневмогайковёрт', 'гайковёрт', 'гайковерт'],
            'clichete-pneumatice' => ['пневмотрещот'],
            'ciocane-pneumatice' => ['пневмомолот', 'молоток пневматический'],
            'burghie-pneumatice' => ['пневмодрель', 'дрель пневматическая', 'дрель для сверления', 'дрель прямой ручкой'],
            'surubelnite-pneumatice' => ['пневмоотверт', 'пневмоотвёрт', 'отвёртка пневмат', 'отвертка пневмат'],
            'foarfeci-ferastraie-si-debitare-pneumatice' => ['пневмопила', 'пневмоножовка', 'ножовка пневм', 'сабельная пила', 'машинка отрезная', 'ножницы пневматические'],
            'nituitoare-capsatoare-si-cuie-pneumatice' => ['пневмозаклепочник', 'пневмозаклёпочник', 'пневмагидравлический заклёпочник', 'пневмагидравлический заклепочник'],
            'chei-dinamometrice' => ['динамометрический ключ'],
            'extractoare-si-prese' => ['съемник', 'съёмник', 'шаровых опор'],
            'scule-pentru-roti-vulcanizare' => ['вентиль шин', 'ремонт шин', 'шиномонтаж'],
            'dispozitive-pneumatice-service' => ['прокачки тормоз', 'прокачки привода тормоз', 'извлечения технических жидкостей', 'вакуумный экстрактор', 'пневматический домкрат'],
            'manusi' => ['перчатки'],
            'accesorii-universale' => ['сумка для инструментов', 'накидка защитная'],
        ];
    }

    private function semanticRules(): array
    {
        return [
            'furtunuri-cuple-accesorii' => ['смазочная муфта', 'быстросъем', 'быстросъём', 'пневмошланг', 'воздушный шланг', 'ниппель', 'фитинг'],
            'consumabile-pentru-scule-pneumatice' => ['точильных камней', 'зачистной диск', 'диск зачистной', 'иглы для пневмо', 'щетка для пневмо', 'щётка для пневмо'],
            'polizoare-si-slefuitoare-pneumatice' => ['шлифовальная машин', 'пневмошлиф', 'турбинка', 'полировальная машин', 'удаления ржавчины', 'фрезер', 'фрейзер'],
            'pistoale-suflat-si-sablare' => ['пистолет моечный', 'пистолет очиститель', 'пескоструй', 'подкачки шин', 'продувочный пистолет', 'пенообразователь', 'tornador', 'распылитель'],
            'pistoale-pentru-silicon-si-gresare' => ['пистолет для смазки', 'шприц смазочный', 'пистолет для силикона'],
            'chei-pneumatice' => ['пневмогайковерт', 'гайковерт пневматический'],
            'clichete-pneumatice' => ['пневмотрещот'],
            'ciocane-pneumatice' => ['пневмомолот', 'молоток пневматический'],
            'burghie-pneumatice' => ['пневмодрель', 'дрель пневматическая'],
            'surubelnite-pneumatice' => ['пневмоотверт', 'пневмоотвёрт'],
            'foarfeci-ferastraie-si-debitare-pneumatice' => ['пневмопила', 'пневмоножовка', 'ножницы пневматические'],
            'nituitoare-capsatoare-si-cuie-pneumatice' => ['пневмозаклепочник', 'пневмозаклёпочник', 'заклепочник пневматический'],
            'extractoare-si-prese' => ['съемник', 'съёмник', 'шаровых опор'],
            'scule-pentru-roti-vulcanizare' => ['вентиль шин', 'ремонт шин', 'шиномонтаж'],
            'dispozitive-pneumatice-service' => ['прокачки тормоз', 'вакуумный экстрактор', 'пневматический домкрат'],
        ];
    }

    private function trisToolsCategoryRules(): array
    {
        return [
            ['slug' => 'clesti-electrician-si-cabluri', 'keywords' => [
                'каблерез',
                'кабелерез',
                'ножницы для кабеля',
                'режущих губок',
                'cable cutter',
                'ratcheting cable',
                'wire & cable tools',
                'стрипперы - для очистки изоляции',
            ], 'confidence' => 98],
            ['slug' => 'instrumente-electromontaj', 'keywords' => ['vde', 'диэлектр', 'изолирован', 'электромонтаж']],
            ['slug' => 'capete-tubulare-impact', 'keywords' => ['ударные головки', 'головка ударная', 'impact socket']],
            ['slug' => 'scule-pentru-filtre-ulei', 'keywords' => ['масляных фильтр', 'масляный фильтр']],
            ['slug' => 'scule-pentru-frane', 'keywords' => ['тормоз', 'brake']],
            ['slug' => 'scule-pentru-suspensie', 'keywords' => ['подвеск', 'амортизатор', 'пружин']],
            ['slug' => 'scule-pentru-motor', 'keywords' => ['двигател', 'грм', 'распредвал', 'коленвал']],
            ['slug' => 'scule-pentru-roti-vulcanizare', 'keywords' => ['колес', 'шиномонтаж', 'вулканизац']],
            ['slug' => 'diagnoza-auto', 'keywords' => ['диагностик', 'diagnostic', 'видеоэндоскоп', 'видео эндоскоп', 'videoendoscop', 'video endoscope']],
            ['slug' => 'tinichigerie-si-richtuire', 'keywords' => ['правка кузова', 'рихтов', 'покраск', 'tinichigerie']],
            ['slug' => 'chei-dinamometrice', 'keywords' => ['динамометр', 'torque']],
            ['slug' => 'sublere-micrometre-comparatoare', 'keywords' => ['штангенциркул', 'микрометр', 'индикатор часового']],
            ['slug' => 'multimetre-testere', 'keywords' => ['мультиметр', 'тестер электр']],
            ['slug' => 'instrumente-de-masurare', 'keywords' => ['измерительный инструмент', 'измерение']],
            ['slug' => 'manusi', 'keywords' => ['перчатк']],
            ['slug' => 'ochelari-protectie-fata', 'keywords' => ['защитные очки', 'защита лица']],
            ['slug' => 'echipament-protectie', 'keywords' => ['средства защиты', 'спецодежд', 'рабочая одежда']],
            ['slug' => 'seturi-de-scule', 'keywords' => ['инструменты в ложементах', 'универсальные в наборах', 'ключи в наборах', 'отвертки в наборах']],
            ['slug' => 'tubulare-si-clichete', 'keywords' => ['головки торцевые', 'воротки и трещотки', 'удлинители', 'карданы', 'трещотк']],
            ['slug' => 'biti-insertii-adaptoare', 'keywords' => ['биты - вставки', 'биты, вставки', 'держатели бит', 'ударные биты', 'hex, torx', 'насадки бит']],
            ['slug' => 'surubelnite-si-biti', 'keywords' => ['отвертк', 'отвёртк', 'screwdriver']],
            ['slug' => 'clesti-si-instrumente-taiere', 'keywords' => ['пассатиж', 'плоскогуб', 'бокорез', 'кусач', 'клещи', 'зажимы и захваты']],
            ['slug' => 'chei-si-surubelnite', 'keywords' => ['ключи', 'ключ гаечн', 'ключ комбинирован']],
            ['slug' => 'tarozi-filiere-filetare', 'keywords' => ['плашки и метчики', 'метчик', 'плашк', 'резьб']],
            ['slug' => 'taiere-pilire-prelucrare', 'keywords' => ['ударный и режущий', 'напильник', 'ножовк', 'зубил', 'молоток']],
            ['slug' => 'accesorii-universale', 'keywords' => ['фонари', 'фонарь', 'squad', 'разное']],
            ['slug' => 'chei-pneumatice', 'keywords' => ['пневмогайковерт']],
            ['slug' => 'clichete-pneumatice', 'keywords' => ['пневмотрещот']],
            ['slug' => 'ciocane-pneumatice', 'keywords' => ['пневмомолот']],
            ['slug' => 'burghie-pneumatice', 'keywords' => ['пневмодрел']],
            ['slug' => 'surubelnite-pneumatice', 'keywords' => ['пневмоотверт', 'пневмоотвёрт']],
            ['slug' => 'polizoare-si-slefuitoare-pneumatice', 'keywords' => ['пневмошлиф', 'пневматическая шлиф', 'турбинк']],
            ['slug' => 'pistoale-suflat-si-sablare', 'keywords' => ['продувочн', 'пескостру', 'tornador']],
            ['slug' => 'scule-pneumatice', 'keywords' => ['пневматический инструмент', 'пневмоинструмент']],
        ];
    }

    private function categoryResult(Category $category, int $confidence, string $method, array $notes): array
    {
        $confidence = min(100, $confidence);
        $needsReview = $confidence < $this->minimumConfidence();

        return [
            'category_id' => $needsReview ? null : $category->id,
            'detected_category_id' => $category->id,
            'detected_category_path' => $this->path($category),
            'category_slug' => $category->slug,
            'category_name_ru' => $category->name,
            'category_name_ro' => $category->name_ro,
            'confidence' => $confidence,
            'method' => $method,
            'notes' => $notes,
            'needs_review' => $needsReview,
        ];
    }

    private function minimumConfidence(?array $rules = null): int
    {
        $rules ??= $this->settings->get('category_rules', config('product_parser.category_rules', []));

        return max(
            90,
            (int) ($rules['min_confidence'] ?? 0),
            (int) $this->settings->get('min_confidence_score', 90),
        );
    }

    private function path(Category $category): string
    {
        $parts = [];
        $current = $category;

        while ($current) {
            array_unshift($parts, $current->display_name);
            $current = $current->parent;
        }

        return implode(' > ', $parts);
    }

    private function skuMatches(string $sku, string $pattern): bool
    {
        $sku = Str::upper(trim($sku));
        $pattern = Str::upper(trim($pattern));

        if (Str::startsWith($pattern, '*')) {
            return Str::endsWith($sku, ltrim($pattern, '*'));
        }

        if (Str::endsWith($pattern, '*')) {
            return Str::startsWith($sku, rtrim($pattern, '*'));
        }

        return Str::startsWith($sku, $pattern);
    }

    private function contains(string $text, string $needle): bool
    {
        return Str::contains($text, $this->normalize($needle));
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        return preg_replace('/\s+/u', ' ', $value) ?: '';
    }
}
