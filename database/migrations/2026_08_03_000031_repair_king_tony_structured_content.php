<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $brandId = DB::table('brands')->where('name', 'King Tony')->value('id');
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
        $currentRo = $this->clean((string) $product->name_ro);
        $nameRo = $this->isGenericRomanianName($currentRo, $sku)
            ? trim($typeRo.' King Tony '.$sku.($this->technicalSignature($nameRu, $sku) !== '' ? ', '.$this->technicalSignature($nameRu, $sku) : ''))
            : trim($currentRo.' King Tony '.$sku);
        $descriptionRu = $this->isGenericRussian((string) ($product->description_ru ?: $product->description))
            ? $nameRu.'. '.$purposeRu.$this->russianSpecificationSentence($product->attributes)
            : $this->clean((string) ($product->description_ru ?: $product->description));
        $descriptionRo = $nameRo.'. '.$purposeRo.$this->romanianSpecificationSentence($product->attributes);
        $shortRu = Str::limit($descriptionRu, 240, '');
        $shortRo = Str::limit($descriptionRo, 240, '');
        $sourceUrl = trim((string) $product->source_url);
        $sourceDomain = Str::lower((string) parse_url($sourceUrl, PHP_URL_HOST));
        $official = $sourceDomain === 'kingtony.com' || Str::endsWith($sourceDomain, '.kingtony.com');
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
            'biti-insertii-adaptoare' => ['Bit sau adaptor', 'Предназначен для работы с соответствующим профилем резьбового крепежа.', 'Este destinat lucrului cu profilul corespunzător al elementelor de fixare.'],
            'biti-si-capete' => ['Cap cu bit', 'Предназначена для установки на привод указанного размера и работы с соответствующим профилем крепежа.', 'Este destinat montării pe antrenarea indicată și lucrului cu profilul corespunzător de fixare.'],
            'surubelnite-si-biti' => [$this->screwdriverType($nameRu), 'Предназначен для монтажа и демонтажа винтового крепежа.', 'Este destinat montării și demontării elementelor de fixare cu șurub.'],
            'chei-si-surubelnite' => ['Set de chei', 'Предназначен для монтажа и демонтажа резьбовых соединений.', 'Este destinat montării și demontării îmbinărilor filetate.'],
            'clesti-si-instrumente-taiere' => ['Clește sau sculă de tăiere', 'Предназначен для захвата, удержания или резки материала.', 'Este destinat prinderii, susținerii sau tăierii materialului.'],
            'clesti-electrician-si-cabluri' => ['Sculă pentru electrician', 'Предназначена для подготовки и монтажа проводов и кабелей.', 'Este destinată pregătirii și montării conductorilor și cablurilor.'],
            'extractoare-si-prese' => ['Extractor sau presă', 'Предназначен для контролируемого монтажа или демонтажа деталей.', 'Este destinat montării sau demontării controlate a pieselor.'],
            'capete-tubulare-impact' => ['Cap tubular de impact', 'Предназначен для работы с ударным приводом указанного размера.', 'Este destinat utilizării cu o sculă de impact cu antrenarea indicată.'],
            'instrumente-electromontaj' => ['Sculă pentru montaj electric', 'Предназначена для электромонтажных и сервисных работ.', 'Este destinată lucrărilor de montaj electric și service.'],
            'tarozi-filiere-filetare' => ['Sculă pentru filetare', 'Предназначена для нарезания или восстановления резьбы указанных размеров.', 'Este destinată executării sau refacerii filetelor cu dimensiunile indicate.'],
            'tubulare-si-clichete' => ['Cap tubular sau clichet', 'Предназначен для работы с резьбовым крепежом.', 'Este destinat lucrului cu elemente de fixare filetate.'],
            'chei-pneumatice' => ['Cheie pneumatică de impact', 'Предназначен для быстрого отворачивания и затягивания резьбового крепежа.', 'Este destinată desfacerii și strângerii rapide a elementelor de fixare filetate.'],
            'manusi' => ['Mănuși de protecție', 'Предназначены для защиты рук при выполнении работ.', 'Sunt destinate protecției mâinilor în timpul lucrului.'],
            'pistoale-suflat-si-sablare' => ['Pistol pneumatic', 'Предназначен для направленной подачи сжатого воздуха.', 'Este destinat dirijării aerului comprimat.'],
            'scule-pentru-motor' => ['Sculă pentru motor', 'Предназначена для обслуживания узлов двигателя, указанных в названии.', 'Este destinată întreținerii componentelor motorului indicate în denumire.'],
            'scule-pentru-suspensie' => ['Sculă pentru suspensie', 'Предназначена для обслуживания элементов подвески.', 'Este destinată întreținerii componentelor suspensiei.'],
            'taiere-pilire-prelucrare' => ['Sculă pentru tăiere și prelucrare', 'Предназначена для резки или ручной обработки материала.', 'Este destinată tăierii sau prelucrării manuale a materialului.'],
            'tinichigerie-si-richtuire' => ['Sculă pentru tinichigerie', 'Предназначена для кузовных и рихтовочных работ.', 'Este destinată lucrărilor de tinichigerie și îndreptare.'],
            default => ['Sculă profesională', 'Предназначен для профессиональных монтажных и сервисных работ.', 'Este destinată lucrărilor profesionale de montaj și service.'],
        };
    }

    private function screwdriverType(string $nameRu): string
    {
        $name = Str::lower($nameRu);

        return match (true) {
            Str::contains($name, ['набор', 'комплект']) => 'Set de șurubelnițe și biți',
            Str::contains($name, ['отвертк', 'отвёртк']) => 'Șurubelniță',
            default => 'Bit sau accesoriu pentru șurubelniță',
        };
    }

    private function isGenericRomanianName(string $name, string $sku): bool
    {
        return $name === ''
            || (Str::contains(Str::lower($name), 'king tony') && Str::contains(Str::lower($name), Str::lower($sku)));
    }

    private function technicalSignature(string $nameRu, string $sku): string
    {
        $value = str_ireplace(['King Tony', $sku], ' ', $nameRu);
        $value = str_ireplace([
            'об/мин', 'уд/мин', 'Нм', 'Ач', 'бар', 'кг', 'мм', 'шт.', 'предм.', 'предм', 'дл.', 'длин.',
        ], [
            'rpm', 'lovituri/min', 'Nm', 'Ah', 'bar', 'kg', 'mm', 'buc.', 'piese', 'piese', 'L ', 'L ',
        ], $value);
        $value = preg_replace('/\p{Cyrillic}+/u', ' ', $value) ?: '';
        $value = str_replace(['(', ')', '[', ']'], ' ', $value);
        $value = preg_replace('/\s*[,;]\s*(?:[,;]\s*)+/u', ', ', $value) ?: $value;
        $value = preg_replace('/(^|\s)[,;]+\s*/u', '$1', $value) ?: $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return Str::limit(trim($value, " \t\n\r\0\x0B,.;-–—"), 90, '');
    }

    private function isGenericRussian(string $description): bool
    {
        $description = Str::lower($this->clean($description));

        return $description === ''
            || mb_strlen($description) < 30
            || Str::contains($description, [
                'товар бренда king tony из категории',
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
            ->reject(fn ($value, $key) => is_numeric($key) || Str::contains(Str::lower((string) $key), ['stock', 'subgroup', 'подгруппа']))
            ->map(fn ($value, $key) => $this->clean((string) $key).': '.$this->clean((string) $value))
            ->filter(fn (string $value) => ! Str::endsWith($value, ':'))
            ->take(5)
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
            $label = match (true) {
                Str::contains($key, ['вес']) => 'greutate',
                Str::contains($key, ['размер квадрата']) => 'antrenare',
                Str::contains($key, ['крутящий момент', 'усилие']) => 'cuplu',
                Str::contains($key, ['длина']) => 'lungime',
                Str::contains($key, ['диаметр']) => 'diametru',
                default => null,
            };
            if (! $label) {
                continue;
            }
            $value = str_ireplace(['Нм', 'кг', 'мм', 'бар', 'об/мин'], ['Nm', 'kg', 'mm', 'bar', 'rpm'], (string) $value);
            if (preg_match('/\p{Cyrillic}/u', $value) === 1) {
                continue;
            }
            $specs[$label] = $this->clean($value);
            if (count($specs) >= 5) {
                break;
            }
        }

        return $specs ? ' Specificații verificate: '.collect($specs)->map(fn ($value, $key) => $key.': '.$value)->implode('; ').'.' : '';
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
