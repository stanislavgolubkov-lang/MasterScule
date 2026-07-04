<?php

namespace App\Services;

use App\Support\ProductLocalizer;
use Illuminate\Support\Str;

class ProductParserContentBuilder
{
    public function build(string $sku, string $sourceName, ?string $brand = null, ?string $group = null): array
    {
        $sourceName = $this->cleanName($sourceName);
        $brand = trim((string) $brand);
        $nameRu = $this->nameRu($sourceName, $brand, $sku);
        $nameRo = $this->nameRo($sourceName, $brand, $sku);
        $usageRu = $this->usageRu($nameRu, $brand, $group);
        $usageRo = $this->usageRo($nameRo, $brand, $group);

        return [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description_ru' => Str::limit($usageRu, 180, ''),
            'short_description_ro' => Str::limit($usageRo, 180, ''),
            'description_ru' => $usageRu.' Код товара: '.$sku.'. Проверьте характеристики, категорию и изображения перед публикацией.',
            'description_ro' => $usageRo.' Cod produs: '.$sku.'. Verifica categoria, caracteristicile si imaginile inainte de publicare.',
        ];
    }

    private function cleanName(string $name): string
    {
        $name = html_entity_decode(strip_tags($name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = preg_replace('/\s+/u', ' ', $name) ?: '';

        return trim($name, " \t\n\r\0\x0B,.;");
    }

    private function nameRu(string $name, string $brand, string $sku): string
    {
        return ProductLocalizer::russianName($name, $brand, $sku);
    }

    private function nameRo(string $name, string $brand, string $sku): string
    {
        $lower = Str::lower($name);

        if (! preg_match('/[А-Яа-яЁё]/u', $name)) {
            return ProductLocalizer::name($name, $brand, $sku);
        }

        $replacements = [
            'пневматический' => 'pneumatic',
            'пневмогайковерт' => 'pistol pneumatic de impact',
            'пистолет' => 'pistol',
            'набор' => 'set',
            'комплект' => 'set',
            'ключ' => 'cheie',
            'головка' => 'cheie tubulara',
            'трещотка' => 'clichet',
            'отвертка' => 'surubelnita',
            'съемник' => 'extractor',
            'съёмник' => 'extractor',
            'шланг' => 'furtun',
            'муфта' => 'cupla',
            'смазочная' => 'gresare',
            'динамометрический' => 'dinamometric',
            'инструментов' => 'scule',
            'инструмент' => 'instrument',
        ];

        foreach ($replacements as $from => $to) {
            $lower = str_replace($from, $to, $lower);
        }

        $name = trim(preg_replace('/\s+/u', ' ', $lower) ?: $name);

        return ProductLocalizer::name(Str::ucfirst($name), $brand, $sku);
    }

    private function usageRu(string $name, string $brand, ?string $group): string
    {
        $scope = $group ? ' Раздел прайса: '.$group.'.' : '';

        return "{$name} - технический товар".($brand ? " {$brand}" : '')." для автосервиса, мастерской и гаража.{$scope}";
    }

    private function usageRo(string $name, string $brand, ?string $group): string
    {
        $scope = $group ? ' Sectiune pret: '.$group.'.' : '';

        return "{$name} este un produs tehnic".($brand ? " {$brand}" : '')." pentru service auto, atelier si garaj.{$scope}";
    }
}
