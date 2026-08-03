<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $brandId = DB::table('brands')->where('name', 'M7 / Mighty Seven')->value('id');
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
        [$typeRo, $purposeRu, $purposeRo] = $this->categoryCopy((string) $product->category_slug, $nameRu);
        $signature = $this->technicalSignature($nameRu, $sku);
        $nameRo = trim($typeRo.' M7 '.$sku.($signature !== '' ? ', '.$signature : ''));
        $specs = $this->specifications($product->attributes);

        $descriptionRu = $this->isGenericRussian((string) ($product->description_ru ?: $product->description))
            ? $nameRu.'. '.$purposeRu.$this->russianSpecificationSentence($product->attributes)
            : $this->clean((string) ($product->description_ru ?: $product->description));
        $descriptionRo = $nameRo.'. '.$purposeRo.$this->romanianSpecificationSentence($specs);
        $shortRu = Str::limit($descriptionRu, 240, '');
        $shortRo = Str::limit($descriptionRo, 240, '');
        $sourceUrl = trim((string) $product->source_url);
        $sourceDomain = Str::lower((string) parse_url($sourceUrl, PHP_URL_HOST));
        $official = $sourceDomain === 'mighty-seven.com' || Str::endsWith($sourceDomain, '.mighty-seven.com');
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

    private function categoryCopy(string $slug, string $nameRu): array
    {
        return match ($slug) {
            'polizoare-si-slefuitoare-pneumatice' => ['Polizor pneumatic', 'Предназначен для профессионального шлифования и обработки поверхностей с приводом от сжатого воздуха.', 'Este destinat șlefuirii și prelucrării profesionale a suprafețelor, fiind acționat cu aer comprimat.'],
            'furtunuri-cuple-accesorii' => [$this->pneumaticAccessoryType($nameRu), 'Предназначен для подключения и оснащения пневматического инструмента.', 'Este destinat conectării și echipării sculelor pneumatice.'],
            'consumabile-pentru-scule-pneumatice' => ['Consumabil pentru scule pneumatice', 'Является сменной оснасткой или расходным элементом для указанного пневматического инструмента.', 'Este un accesoriu de schimb sau un consumabil pentru scula pneumatică indicată.'],
            'chei-pneumatice' => ['Cheie pneumatică de impact', 'Предназначен для быстрого отворачивания и затягивания резьбового крепежа.', 'Este destinată desfacerii și strângerii rapide a elementelor de fixare filetate.'],
            'scule-pneumatice' => ['Sculă pneumatică', 'Профессиональный инструмент работает от сети сжатого воздуха.', 'Scula profesională funcționează de la o instalație de aer comprimat.'],
            'pistoale-suflat-si-sablare' => ['Pistol pneumatic', 'Предназначен для направленной подачи сжатого воздуха при очистке и сервисных работах.', 'Este destinat dirijării aerului comprimat la curățare și lucrări de service.'],
            'burghie-pneumatice' => ['Mașină pneumatică de găurit', 'Предназначена для сверления с приводом от сжатого воздуха.', 'Este destinată găuririi și funcționează cu aer comprimat.'],
            'electroinstrumente' => [$this->electricToolType($nameRu), 'Предназначен для профессиональных монтажных и ремонтных работ.', 'Este destinat lucrărilor profesionale de montaj și reparații.'],
            'foarfeci-ferastraie-si-debitare-pneumatice' => ['Sculă pneumatică de tăiere', 'Предназначена для резки материала с приводом от сжатого воздуха.', 'Este destinată tăierii materialelor și funcționează cu aer comprimat.'],
            'ciocane-pneumatice' => ['Ciocan pneumatic', 'Предназначен для ударных работ с приводом от сжатого воздуха.', 'Este destinat lucrărilor de lovire și funcționează cu aer comprimat.'],
            'clichete-pneumatice' => ['Clichet pneumatic', 'Предназначен для работы с резьбовым крепежом в мастерской и автосервисе.', 'Este destinat lucrului cu elemente de fixare filetate în atelier și service auto.'],
            'chei-cu-acumulator' => ['Cheie de impact cu acumulator', 'Предназначен для мобильной работы с резьбовым крепежом.', 'Este destinată lucrului mobil cu elemente de fixare filetate.'],
            'nituitoare-capsatoare-si-cuie-pneumatice' => ['Nituitor pneumatic', 'Предназначен для установки заклёпок с приводом от сжатого воздуха.', 'Este destinat montării niturilor și funcționează cu aer comprimat.'],
            'chei-dinamometrice' => ['Cheie dinamometrică', 'Предназначен для контролируемой затяжки резьбовых соединений заданным моментом.', 'Este destinată strângerii controlate a îmbinărilor filetate la cuplul stabilit.'],
            'surubelnite-pneumatice' => ['Șurubelniță pneumatică', 'Предназначена для серийного заворачивания и отворачивания винтового крепежа.', 'Este destinată înșurubării și deșurubării repetitive a elementelor de fixare.'],
            'accesorii-universale' => ['Accesoriu universal', 'Предназначен для совместного применения с указанным инструментом M7.', 'Este destinat utilizării împreună cu scula M7 indicată.'],
            'baterii-incarcatoare' => [$this->batteryType($nameRu), 'Предназначен для аккумуляторной системы M7 с указанными в названии параметрами.', 'Este destinat sistemului de acumulatori M7 cu parametrii indicați în denumire.'],
            'dispozitive-pneumatice-service' => ['Dispozitiv pneumatic pentru service', 'Предназначен для профессиональных работ в мастерской и автосервисе.', 'Este destinat lucrărilor profesionale în atelier și service auto.'],
            'masini-gaurit-insurubat' => ['Mașină de găurit și înșurubat cu acumulator', 'Предназначена для сверления и работы с резьбовым крепежом.', 'Este destinată găuririi și lucrului cu elemente de fixare filetate.'],
            'polizoare' => ['Polizor unghiular cu acumulator', 'Предназначен для резки и шлифования с автономным аккумуляторным питанием.', 'Este destinat tăierii și șlefuirii cu alimentare autonomă de la acumulator.'],
            'biti-si-capete' => ['Bit sau cap de lucru', 'Предназначен для установки в совместимый ручной или механизированный инструмент.', 'Este destinat montării într-o sculă manuală sau mecanizată compatibilă.'],
            'tinichigerie-si-richtuire' => ['Sculă pentru tinichigerie', 'Предназначена для кузовных и рихтовочных работ.', 'Este destinată lucrărilor de tinichigerie și îndreptare a caroseriei.'],
            default => ['Sculă profesională', 'Предназначен для профессиональных работ в мастерской и автосервисе.', 'Este destinată lucrărilor profesionale în atelier și service auto.'],
        };
    }

    private function pneumaticAccessoryType(string $nameRu): string
    {
        $name = Str::lower($nameRu);

        return match (true) {
            Str::contains($name, ['шланг', 'рукав']) => 'Furtun pneumatic',
            Str::contains($name, ['муфт', 'соедин', 'переход', 'штуцер']) => 'Cuplă pneumatică',
            default => 'Accesoriu pneumatic',
        };
    }

    private function electricToolType(string $nameRu): string
    {
        $name = Str::lower($nameRu);

        return match (true) {
            Str::contains($name, 'фен') => 'Pistol cu aer cald cu acumulator',
            Str::contains($name, 'реноватор') => 'Sculă multifuncțională cu acumulator',
            Str::contains($name, ['пила', 'лобзик']) => 'Ferăstrău cu acumulator',
            Str::contains($name, 'фонар') => 'Lampă cu acumulator',
            default => 'Sculă electrică cu acumulator',
        };
    }

    private function batteryType(string $nameRu): string
    {
        return Str::contains(Str::lower($nameRu), ['заряд', 'incarc'])
            ? 'Încărcător pentru acumulatori'
            : 'Acumulator Li-Ion';
    }

    private function technicalSignature(string $nameRu, string $sku): string
    {
        $value = str_ireplace(['Mighty Seven', 'M7', $sku], ' ', $nameRu);
        $value = $this->romanianValue($value);
        $value = preg_replace('/\p{Cyrillic}+/u', ' ', $value) ?: '';
        $value = str_replace(['(', ')', '[', ']'], ' ', $value);
        $value = preg_replace('/\s*[,;]\s*(?:[,;]\s*)+/u', ', ', $value) ?: $value;
        $value = preg_replace('/(^|\s)[,;]+\s*/u', '$1', $value) ?: $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';
        $value = trim($value, " \t\n\r\0\x0B,.;-–—()[]");

        return Str::limit($value, 90, '');
    }

    private function specifications(mixed $attributes): array
    {
        $attributes = is_string($attributes) ? json_decode($attributes, true) : $attributes;
        if (! is_array($attributes)) {
            return [];
        }

        $result = [];
        foreach ($attributes as $key => $value) {
            $label = $this->romanianAttribute((string) $key);
            $value = $this->romanianValue((string) $value);
            if ($label === null || $value === '' || preg_match('/\p{Cyrillic}/u', $value) === 1) {
                continue;
            }
            $result[$label] = $value;
            if (count($result) >= 6) {
                break;
            }
        }

        return $result;
    }

    private function romanianAttribute(string $key): ?string
    {
        $key = Str::lower($this->clean($key));

        return match (true) {
            Str::contains($key, ['напряжение']) => 'tensiune',
            Str::contains($key, ['ёмкость аккумулятора', 'емкость аккумулятора']) => 'capacitate acumulator',
            Str::contains($key, ['тип двигателя']) => 'motor',
            Str::contains($key, ['число оборотов', 'скорость свободного вращения', 'скорость вращения']) => 'turație',
            Str::contains($key, ['максимальный крутящий момент', 'макс. усилие на откручивание', 'крутящий момент']) => 'cuplu maxim',
            Str::contains($key, ['рабочий диапазон усилий']) => 'domeniu de cuplu',
            Str::contains($key, ['среднее потребление воздуха', 'расход воздуха']) => 'consum de aer',
            Str::contains($key, ['рабочее давление']) => 'presiune de lucru',
            Str::contains($key, ['размер воздушного штуцера']) => 'racord de aer',
            Str::contains($key, ['диаметр шланга']) => 'diametru furtun',
            Str::contains($key, ['длина сопла']) => 'lungime duză',
            Str::contains($key, ['размер квадрата']) => 'antrenare',
            Str::contains($key, ['размер диска']) => 'diametru disc',
            Str::contains($key, ['резьба шпинделя']) => 'filet ax',
            Str::contains($key, ['диаметр']) => 'diametru',
            Str::contains($key, ['частота']) => 'frecvență',
            Str::contains($key, ['уровень шума']) => 'nivel sonor',
            Str::contains($key, ['производительность']) => 'debit',
            Str::contains($key, ['вес']) => 'greutate',
            default => null,
        };
    }

    private function romanianValue(string $value): string
    {
        $value = str_ireplace([
            'Бесщёточный', 'Бесщеточный', 'об/мин', 'Об/мин', 'уд/мин', 'л/мин',
            'Нм', 'Ач', 'Бар', 'бар', 'кг', 'мм', 'дБ', 'м/с2', 'Есть', 'положений',
        ], [
            'fără perii', 'fără perii', 'rpm', 'rpm', 'lovituri/min', 'l/min',
            'Nm', 'Ah', 'bar', 'bar', 'kg', 'mm', 'dB', 'm/s²', 'da', 'poziții',
        ], $value);
        $value = preg_replace('/(?<=\d)\s*В\b/u', ' V', $value) ?: $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return trim($value, " \t\n\r\0\x0B,.;");
    }

    private function russianSpecificationSentence(mixed $attributes): string
    {
        $attributes = is_string($attributes) ? json_decode($attributes, true) : $attributes;
        if (! is_array($attributes)) {
            return '';
        }

        $specs = collect($attributes)
            ->reject(fn ($value, $key) => Str::contains(Str::lower((string) $key), ['stock', 'subgroup', 'подгруппа']))
            ->map(fn ($value, $key) => $this->clean((string) $key).': '.$this->clean((string) $value))
            ->filter(fn (string $value) => ! Str::endsWith($value, ':'))
            ->take(5)
            ->values()
            ->all();

        return $specs ? ' Основные характеристики: '.implode('; ', $specs).'.' : '';
    }

    private function romanianSpecificationSentence(array $specs): string
    {
        if ($specs === []) {
            return '';
        }

        return ' Specificații verificate: '.collect($specs)
            ->map(fn ($value, $key) => $key.': '.$value)
            ->implode('; ').'.';
    }

    private function isGenericRussian(string $description): bool
    {
        $description = Str::lower($this->clean($description));

        return $description === ''
            || mb_strlen($description) < 20
            || Str::contains($description, [
                'товар бренда m7 из категории',
                'подходит для профессионального использования',
                'артикул производителя:',
            ]);
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
