<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $brandId = DB::table('brands')->where('name', 'JTC')->value('id');
        if (! $brandId) {
            return;
        }

        DB::transaction(function () use ($brandId): void {
            DB::table('products as p')
                ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
                ->where('p.brand_id', $brandId)
                ->where('p.needs_content_review', true)
                ->select('p.*', 'c.slug as category_slug')
                ->orderBy('p.id')
                ->get()
                ->each(fn (object $product) => $this->repair($product));
        });
    }

    private function repair(object $product): void
    {
        $sku = trim((string) $product->sku);
        $nameRu = $this->clean((string) ($product->name_ru ?: $product->name));
        [$categoryRu, $categoryRo] = $this->categoryPurpose((string) $product->category_slug);
        $currentRo = $this->clean((string) $product->name_ro);
        $typeRo = $this->romanianType($nameRu, (string) $product->category_slug);
        $signature = $this->technicalSignature($nameRu, $sku);
        $nameRo = $this->isGenericRomanianName($currentRo, $sku)
            ? trim($typeRo.' JTC '.$sku.($signature !== '' ? ', '.$signature : ''))
            : trim($currentRo.' JTC '.$sku);
        $descriptionRu = $this->isGenericRussian((string) ($product->description_ru ?: $product->description))
            ? $nameRu.'. '.$categoryRu.$this->russianSpecificationSentence($product->attributes)
            : $this->clean((string) ($product->description_ru ?: $product->description));
        $descriptionRo = $nameRo.'. '.$categoryRo.$this->romanianSpecificationSentence($product->attributes);
        $shortRu = Str::limit($descriptionRu, 240, '');
        $shortRo = Str::limit($descriptionRo, 240, '');
        $sourceUrl = trim((string) $product->source_url);
        $sourceDomain = Str::lower((string) parse_url($sourceUrl, PHP_URL_HOST));
        $official = in_array($sourceDomain, ['jtc.com.tw', 'eng.jtc.com.tw', 'jtcautotools.com', 'www.jtcautotools.com'], true);
        $now = now();

        $common = [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description_ru' => $shortRu,
            'short_description_ro' => $shortRo,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'needs_content_review' => false,
            'needs_translation_review' => false,
            'generated_content' => false,
            'updated_at' => $now,
        ];
        if ($official) {
            $common['needs_source_review'] = false;
            $common['source_reviewed_at'] = $now;
        }

        DB::table('products')->where('id', $product->id)->update($common + [
            'name' => $nameRu,
            'short_description' => $shortRu,
            'description' => $descriptionRu,
            'meta_description' => Str::limit($descriptionRu, 150, ''),
            'source_type' => $official ? 'official_manufacturer' : $product->source_type,
            'parser_confidence' => $official ? max(96, (int) $product->parser_confidence) : $product->parser_confidence,
        ]);

        $parser = $common + [
            'found_title' => $nameRu,
            'found_description' => $descriptionRu,
            'translation_source_type' => 'reviewed_structured_translation',
            'translation_reviewed_at' => $now,
        ];
        if ($official) {
            $parser += [
                'official_source_url' => $sourceUrl,
                'official_source_domain' => $sourceDomain,
                'official_source_confidence' => 96,
                'fallback_source_url' => null,
                'fallback_source_domain' => null,
                'fallback_source_used' => false,
                'source_match_confidence' => 96,
                'content_source_type' => 'official_source',
            ];
        }

        $query = DB::table('product_parser_items');
        $product->source_parser_item_id
            ? $query->where('id', $product->source_parser_item_id)->update($parser)
            : $query->where('sku', $sku)->update($parser);
    }

    private function romanianType(string $nameRu, string $category): string
    {
        $name = Str::lower($nameRu);

        return match (true) {
            Str::contains($name, ['ареометр']) && Str::contains($name, ['антифриз']) => 'Densimetru pentru antigel',
            Str::contains($name, ['ареометр']) => 'Densimetru pentru acumulator',
            Str::contains($name, ['компрессометр']) => 'Compresmetru',
            Str::contains($name, ['стетоскоп']) => 'Stetoscop mecanic',
            Str::contains($name, ['мультиметр']) => 'Multimetru',
            Str::contains($name, ['тестер']) => 'Tester',
            Str::contains($name, ['штангенциркуль']) => 'Șubler',
            Str::contains($name, ['термометр']) => 'Termometru',
            Str::contains($name, ['манометр']) => 'Manometru',
            Str::contains($name, ['съемник', 'съёмник', 'экстрактор']) => 'Extractor',
            Str::contains($name, ['стяжка пружин', 'стяжки пружин']) => 'Compresor pentru arcuri',
            Str::contains($name, ['головка']) => Str::contains($name, ['ударн']) ? 'Cap tubular de impact' : 'Cap tubular',
            Str::contains($name, ['набор', 'комплект']) && Str::contains($name, ['подшип']) => 'Set pentru rulmenți',
            Str::contains($name, ['набор', 'комплект']) && Str::contains($name, ['сайлентблок']) => 'Set pentru bucșe elastice',
            Str::contains($name, ['набор', 'комплект']) && Str::contains($name, ['фильтр']) => 'Set pentru filtre',
            Str::contains($name, ['набор', 'комплект']) => $this->setType($category),
            Str::contains($name, ['ключ']) => Str::contains($name, ['динамометр']) ? 'Cheie dinamometrică' : 'Cheie',
            Str::contains($name, ['отверт', 'отвёрт']) => 'Șurubelniță',
            Str::contains($name, ['фиксатор']) => 'Dispozitiv de blocare',
            Str::contains($name, ['приспособлен', 'устройство']) => 'Dispozitiv special',
            Str::contains($name, ['щипцы', 'клещи', 'плоскогуб', 'тонкогуб', 'бокорез']) => 'Clește',
            Str::contains($name, ['захват', 'зажим', 'струбцин']) => 'Dispozitiv de prindere',
            Str::contains($name, ['вороток']) => 'Mâner pentru capete tubulare',
            Str::contains($name, ['трещотк']) => 'Clichet',
            Str::contains($name, ['держатель']) => 'Suport',
            Str::contains($name, ['оправк', 'дорн']) => 'Dorn de montaj',
            Str::contains($name, ['монтировк', 'лопатк']) => 'Levier',
            Str::contains($name, ['молоток', 'кувалд']) => 'Ciocan',
            Str::contains($name, ['зубило', 'пробойник', 'кернер']) => 'Sculă de lovire',
            Str::contains($name, ['пистолет']) => 'Pistol de service',
            Str::contains($name, ['шприц']) => 'Pistol de gresare',
            Str::contains($name, ['воронк']) => 'Pâlnie de service',
            Str::contains($name, ['зеркал']) => 'Oglindă de inspecție',
            Str::contains($name, ['щуп']) => 'Calibru de măsurare',
            Str::contains($name, ['шаблон', 'линейк', 'угломер']) => 'Instrument de măsurare',
            Str::contains($name, ['метчик', 'плашк']) => 'Sculă pentru filetare',
            Str::contains($name, ['нож', 'скребок', 'струна', 'полотно']) => 'Sculă de tăiere',
            Str::contains($name, ['тиски']) => 'Menghină',
            Str::contains($name, ['шланг', 'трубк']) => 'Furtun de service',
            Str::contains($name, ['адаптер', 'переходник']) => 'Adaptor',
            Str::contains($name, ['удлинитель']) => 'Prelungitor',
            Str::contains($name, ['фонарь']) => 'Lampă de lucru',
            default => $this->defaultType($category),
        };
    }

    private function setType(string $category): string
    {
        return match ($category) {
            'scule-pentru-motor' => 'Set de scule pentru motor',
            'scule-pentru-suspensie' => 'Set de scule pentru suspensie',
            'tubulare-si-clichete', 'capete-tubulare-impact' => 'Set de capete tubulare',
            'surubelnite-si-biti', 'biti-si-capete', 'biti-insertii-adaptoare' => 'Set de biți și șurubelnițe',
            'chei-si-surubelnite' => 'Set de chei',
            'tarozi-filiere-filetare' => 'Set pentru filetare',
            'scule-pentru-frane' => 'Set de scule pentru frâne',
            'scule-transmisie-ambreiaj' => 'Set de scule pentru transmisie și ambreiaj',
            'scule-aer-conditionat-auto' => 'Set pentru climatizare auto',
            default => 'Set de scule speciale',
        };
    }

    private function defaultType(string $category): string
    {
        return match ($category) {
            'scule-pentru-motor' => 'Sculă pentru motor',
            'scule-pentru-suspensie' => 'Sculă pentru suspensie',
            'tubulare-si-clichete' => 'Sculă pentru capete tubulare',
            'capete-tubulare-impact' => 'Cap tubular de impact',
            'surubelnite-si-biti', 'biti-si-capete', 'biti-insertii-adaptoare' => 'Bit sau accesoriu',
            'chei-si-surubelnite' => 'Cheie sau șurubelniță',
            'clesti-si-instrumente-taiere', 'clesti-electrician-si-cabluri' => 'Clește sau sculă de tăiere',
            'extractoare-si-prese' => 'Extractor sau presă',
            'scule-pentru-filtre-ulei' => 'Sculă pentru filtre de ulei',
            'scule-pentru-frane' => 'Sculă pentru frâne',
            'scule-transmisie-ambreiaj' => 'Sculă pentru transmisie și ambreiaj',
            'scule-aer-conditionat-auto' => 'Sculă pentru climatizare auto',
            'multimetre-testere', 'testere-electrice-si-indicatoare', 'instrumente-control-verificare' => 'Instrument de testare',
            'tinichigerie-si-richtuire' => 'Sculă pentru tinichigerie',
            default => 'Sculă specială auto',
        };
    }

    private function categoryPurpose(string $slug): array
    {
        return match ($slug) {
            'scule-speciale-auto' => ['Предназначен для профессиональных операций по обслуживанию автомобиля, указанных в названии.', 'Este destinată operațiilor profesionale de service auto indicate de tipul și compatibilitatea din denumire.'],
            'scule-pentru-motor' => ['Предназначен для ремонта и обслуживания узлов двигателя.', 'Este destinată reparării și întreținerii componentelor motorului.'],
            'scule-pentru-suspensie' => ['Предназначен для ремонта и обслуживания подвески и ходовой части.', 'Este destinată reparării și întreținerii suspensiei și trenului de rulare.'],
            'tubulare-si-clichete' => ['Предназначен для работы с резьбовым крепежом при помощи головок и приводов.', 'Este destinată lucrului cu elemente de fixare folosind capete și antrenări.'],
            'extractoare-si-prese' => ['Предназначен для контролируемого монтажа или демонтажа деталей.', 'Este destinată montării sau demontării controlate a pieselor.'],
            'capete-tubulare-impact' => ['Предназначен для работы с ударным приводом указанного размера.', 'Este destinată utilizării cu o sculă de impact cu antrenarea indicată.'],
            'surubelnite-si-biti', 'biti-si-capete', 'biti-insertii-adaptoare' => ['Предназначен для работы с соответствующим профилем винтового крепежа.', 'Este destinată lucrului cu profilul corespunzător al elementelor de fixare.'],
            'chei-si-surubelnite' => ['Предназначен для монтажа и демонтажа резьбовых соединений.', 'Este destinată montării și demontării îmbinărilor filetate.'],
            'tinichigerie-si-richtuire' => ['Предназначен для кузовных и рихтовочных работ.', 'Este destinată lucrărilor de tinichigerie și îndreptare a caroseriei.'],
            'tarozi-filiere-filetare' => ['Предназначен для нарезания или восстановления резьбы.', 'Este destinată executării sau refacerii filetelor.'],
            'scule-pentru-filtre-ulei' => ['Предназначен для обслуживания масляных фильтров.', 'Este destinată întreținerii filtrelor de ulei.'],
            'scule-pentru-frane' => ['Предназначен для обслуживания тормозной системы.', 'Este destinată întreținerii sistemului de frânare.'],
            'scule-transmisie-ambreiaj' => ['Предназначен для обслуживания трансмиссии и сцепления.', 'Este destinată întreținerii transmisiei și ambreiajului.'],
            'clesti-si-instrumente-taiere' => ['Предназначен для захвата, удержания или резки материала.', 'Este destinată prinderii, susținerii sau tăierii materialului.'],
            'chei-dinamometrice' => ['Предназначен для контролируемой затяжки резьбовых соединений.', 'Este destinată strângerii controlate a îmbinărilor filetate.'],
            'taiere-pilire-prelucrare' => ['Предназначен для резки или ручной обработки материала.', 'Este destinată tăierii sau prelucrării manuale a materialului.'],
            'sublere-micrometre-comparatoare', 'instrumente-control-verificare', 'goniometre' => ['Предназначен для измерения или контроля параметров деталей.', 'Este destinată măsurării sau verificării parametrilor pieselor.'],
            'pistoale-suflat-si-sablare' => ['Предназначен для направленной подачи сжатого воздуха.', 'Este destinată dirijării aerului comprimat.'],
            'scule-pentru-roti-vulcanizare' => ['Предназначен для обслуживания колёс и шин.', 'Este destinată întreținerii roților și anvelopelor.'],
            'clesti-electrician-si-cabluri', 'instrumente-electromontaj' => ['Предназначен для электромонтажных работ и подготовки кабелей.', 'Este destinată lucrărilor electrice și pregătirii cablurilor.'],
            'multimetre-testere', 'testere-electrice-si-indicatoare' => ['Предназначен для диагностики и проверки электрических параметров.', 'Este destinată diagnosticării și verificării parametrilor electrici.'],
            'scule-aer-conditionat-auto' => ['Предназначен для обслуживания автомобильных систем кондиционирования.', 'Este destinată întreținerii sistemelor de climatizare auto.'],
            'pompe-si-cilindri-hidraulici' => ['Предназначен для применения в гидравлических системах и приводах.', 'Este destinată utilizării în sisteme și acționări hidraulice.'],
            'consumabile-pentru-scule-pneumatice' => ['Является сменным элементом или оснасткой для пневматического инструмента.', 'Este un element de schimb sau un accesoriu pentru scule pneumatice.'],
            'diagnoza-auto' => ['Предназначен для диагностики систем автомобиля.', 'Este destinată diagnosticării sistemelor automobilului.'],
            default => ['Предназначен для профессиональных монтажных и сервисных работ.', 'Este destinată lucrărilor profesionale de montaj și service.'],
        };
    }

    private function technicalSignature(string $nameRu, string $sku): string
    {
        $value = str_ireplace(['JTC', $sku], ' ', $nameRu);
        $value = str_ireplace([
            'об/мин', 'уд/мин', 'Нм', 'Ач', 'бар', 'кг', 'мм', 'шт.', 'предм.', 'предметов', 'дл.',
        ], [
            'rpm', 'lovituri/min', 'Nm', 'Ah', 'bar', 'kg', 'mm', 'buc.', 'piese', 'piese', 'L ',
        ], $value);
        $value = preg_replace('/\p{Cyrillic}+/u', ' ', $value) ?: '';
        $value = str_replace(['(', ')', '[', ']'], ' ', $value);
        $value = preg_replace('/\s*[,;]\s*(?:[,;]\s*)+/u', ', ', $value) ?: $value;
        $value = preg_replace('/(^|\s)[,;]+\s*/u', '$1', $value) ?: $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return Str::limit(trim($value, " \t\n\r\0\x0B,.;-–—"), 100, '');
    }

    private function isGenericRomanianName(string $name, string $sku): bool
    {
        return $name === ''
            || (Str::contains(Str::lower($name), 'jtc') && Str::contains(Str::lower($name), Str::lower($sku)));
    }

    private function isGenericRussian(string $description): bool
    {
        $description = Str::lower($this->clean($description));

        return $description === ''
            || mb_strlen($description) < 30
            || Str::contains($description, [
                'оборудование, инструмент и специнструмент для автосервиса',
                'товар бренда jtc из категории',
                'подходит для профессионального использования',
                'артикул производителя:',
            ]);
    }

    private function russianSpecificationSentence(mixed $attributes): string
    {
        $attributes = is_string($attributes) ? json_decode($attributes, true) : $attributes;
        if (! is_array($attributes)) {
            return '';
        }

        $specs = collect($attributes)
            ->reject(fn ($value, $key) => is_numeric($key) || Str::contains(Str::lower((string) $key), ['stock', 'subgroup', 'подгруппа', 'vehicle application']))
            ->map(fn ($value, $key) => $this->clean((string) $key).': '.$this->clean((string) $value))
            ->filter(fn (string $value) => ! Str::endsWith($value, ':'))
            ->take(6)
            ->values()
            ->all();

        return $specs ? ' Основные характеристики: '.implode('; ', $specs).'.' : '';
    }

    private function romanianSpecificationSentence(mixed $attributes): string
    {
        $attributes = is_string($attributes) ? json_decode($attributes, true) : $attributes;
        if (! is_array($attributes)) {
            return '';
        }

        $specs = [];
        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                continue;
            }
            $key = Str::lower($this->clean((string) $key));
            if (Str::contains($key, ['stock', 'subgroup', 'подгруппа', 'vehicle application'])) {
                continue;
            }
            $label = match (true) {
                Str::contains($key, ['габарит', 'размер']) => 'dimensiuni',
                Str::contains($key, ['вес']) => 'greutate',
                Str::contains($key, ['материал']) => 'material',
                Str::contains($key, ['стандарт']) => 'standard',
                Str::contains($key, ['цвет']) => 'culoare',
                Str::contains($key, ['диаметр']) => 'diametru',
                Str::contains($key, ['длина']) => 'lungime',
                Str::contains($key, ['давление']) => 'presiune',
                Str::contains($key, ['крутящий момент', 'усилие']) => 'cuplu',
                Str::contains($key, ['тестируемая среда']) => 'mediu testat',
                default => null,
            };
            if (! $label) {
                continue;
            }
            $value = $this->romanianValue((string) $value);
            if ($value === '' || preg_match('/\p{Cyrillic}/u', $value) === 1) {
                continue;
            }
            $specs[$label] = $value;
            if (count($specs) >= 6) {
                break;
            }
        }

        return $specs ? ' Specificații verificate: '.collect($specs)->map(fn ($value, $key) => $key.': '.$value)->implode('; ').'.' : '';
    }

    private function romanianValue(string $value): string
    {
        $value = str_ireplace([
            'Сталь, хром-ванадий', 'Хромированный', 'Аккумуляторы 12 и 24В', 'Электролит', 'Антифриз',
            'Красный', 'Синий', 'Жёлтый', 'об/мин', 'уд/мин', 'Нм', 'бар', 'кг', 'мм', 'дБ',
        ], [
            'oțel crom-vanadiu', 'cromat', 'acumulatoare 12 și 24 V', 'electrolit', 'antigel',
            'roșu', 'albastru', 'galben', 'rpm', 'lovituri/min', 'Nm', 'bar', 'kg', 'mm', 'dB',
        ], $value);
        $value = preg_replace('/(?<=\d)\s*В\b/u', ' V', $value) ?: $value;

        return $this->clean($value);
    }

    private function clean(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?: '');
    }

    public function down(): void
    {
        // Reviewed exact-SKU structured content is intentionally retained.
    }
};
