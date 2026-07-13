<?php

namespace App\Services;

use App\Support\ProductLocalizer;
use Illuminate\Support\Str;

class ProductParserContentBuilder
{
    public function build(string $sku, string $sourceName, ?string $brand = null, ?string $group = null): array
    {
        $sourceName = $this->clean($sourceName);
        $brand = trim((string) $brand);
        $nameRu = ProductLocalizer::russianName($sourceName, $brand, $sku);
        $nameRo = $this->romanianName($sourceName, $brand, $sku);
        $shortRu = "{$nameRu} — технический товар для профессионального использования в автосервисе, мастерской или гараже.";
        $shortRo = "{$nameRo} este un produs tehnic pentru utilizare profesionala in service auto, atelier sau garaj.";
        $scopeRu = $group ? ' Раздел прайс-листа: '.$this->clean($group).'.' : '';
        $scopeRo = $group ? ' Grupa din lista de preturi: '.$this->romanianText($this->clean($group)).'.' : '';

        return [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description_ru' => Str::limit($shortRu, 240, ''),
            'short_description_ro' => Str::limit($shortRo, 240, ''),
            'description_ru' => $shortRu.' Артикул: '.$sku.'.'.$scopeRu,
            'description_ro' => $shortRo.' Cod produs: '.$sku.'.'.$scopeRo,
        ];
    }

    public function mergeOfficialContent(array $content, ?string $officialTitle, ?string $officialDescription, string $sku, ?string $brand = null): array
    {
        $officialTitle = $this->clean((string) $officialTitle);
        $officialDescription = $this->clean((string) $officialDescription);

        if ($officialTitle !== '' && ! Str::contains(Str::lower($officialTitle), ['search', 'official product page'])) {
            $content['name_ru'] = ProductLocalizer::russianName($officialTitle, (string) $brand, $sku);
            $content['name_ro'] = $this->romanianName($officialTitle, (string) $brand, $sku);
        }
        if ($officialDescription !== '') {
            $content['description_ru'] = $this->russianText($officialDescription).' Артикул: '.$sku.'.';
            $content['description_ro'] = $this->romanianText($officialDescription).' Cod produs: '.$sku.'.';
            $content['short_description_ru'] = Str::limit($content['description_ru'], 240, '');
            $content['short_description_ro'] = Str::limit($content['description_ro'], 240, '');
        }

        return $content;
    }

    private function romanianName(string $name, string $brand, string $sku): string
    {
        return ProductLocalizer::name(Str::ucfirst($this->romanianText($name)), $brand, $sku);
    }

    private function romanianText(string $text): string
    {
        return $this->replaceTechnicalTerms($text, [
            'пневматический гайковерт' => 'cheie pneumatica de impact',
            'пневматический инструмент' => 'instrument pneumatic',
            'аккумуляторный инструмент' => 'instrument cu acumulator',
            'динамометрический ключ' => 'cheie dinamometrica',
            'торцевая головка' => 'cheie tubulara',
            'набор инструментов' => 'set de scule',
            'набор' => 'set',
            'отвертка' => 'surubelnita',
            'трещотка' => 'clichet',
            'съемник' => 'extractor',
            'домкрат' => 'cric',
            'тележка' => 'carucior',
            'шкаф' => 'dulap',
            'ящик' => 'cutie',
            'компрессор' => 'compresor',
            'шланг' => 'furtun',
            'гидравлический' => 'hidraulic',
            'головка' => 'cheie tubulara',
            'ключ' => 'cheie',
            'клещи' => 'cleste',
            'молоток' => 'ciocan',
            'сверло' => 'burghiu',
            'диск' => 'disc',
            'для' => 'pentru',
        ]);
    }

    private function russianText(string $text): string
    {
        if (preg_match('/\p{Cyrillic}/u', $text)) {
            return $this->clean($text);
        }

        return $this->replaceTechnicalTerms($text, [
            'air impact wrench' => 'пневматический гайковерт',
            'impact wrench' => 'ударный гайковерт',
            'torque wrench' => 'динамометрический ключ',
            'socket set' => 'набор торцевых головок',
            'tool set' => 'набор инструментов',
            'screwdriver' => 'отвертка',
            'puller' => 'съемник',
            'hydraulic jack' => 'гидравлический домкрат',
            'cordless' => 'аккумуляторный',
            'pneumatic' => 'пневматический',
        ]);
    }

    private function replaceTechnicalTerms(string $text, array $dictionary): string
    {
        $result = Str::lower($this->clean($text));
        uksort($dictionary, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return trim(preg_replace('/\s+/u', ' ', str_ireplace(array_keys($dictionary), array_values($dictionary), $result)) ?: $text);
    }

    private function clean(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?: '', " \t\n\r\0\x0B,.;");
    }
}
