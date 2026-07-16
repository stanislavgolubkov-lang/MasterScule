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

    private function utf8SemanticRules(): array
    {
        return [
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
            ['slug' => 'diagnoza-auto', 'keywords' => ['диагностик', 'diagnostic']],
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
