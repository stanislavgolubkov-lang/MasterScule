<?php

namespace App\Support;

use Illuminate\Support\Str;

class ProductLocalizer
{
    public static function name(string $title, string $brandName = '', ?string $sku = null): string
    {
        return self::ensureBrand(self::clean($title), self::brand($brandName), false, $sku);
    }

    public static function russianName(string $title, string $brandName = '', ?string $sku = null): string
    {
        $name = self::clean($title);

        if ($name === '') {
            $name = 'Профессиональный инструмент';
        } elseif (! preg_match('/\p{Cyrillic}/u', $name)) {
            $dictionary = [
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
                'set de scule' => 'набор инструментов',
                'set de tubulare' => 'набор торцевых головок',
                'cheie dinamometrica' => 'динамометрический ключ',
                'cheie tubulara' => 'торцевая головка',
                'pistol pneumatic' => 'пневматический инструмент',
                'compresor' => 'компрессор',
                'carucior' => 'тележка',
                'dulap' => 'шкаф',
                'cric' => 'домкрат',
                'extractor' => 'съемник',
            ];
            uksort($dictionary, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            $name = str_ireplace(array_keys($dictionary), array_values($dictionary), $name);
        }

        return self::ensureBrand(Str::ucfirst(self::clean($name)), self::brand($brandName), true, $sku);
    }

    public static function russianDescription(?string $description, string $displayName, string $brandName = '', ?string $sku = null): string
    {
        $description = self::clean((string) $description);
        if ($description !== '' && preg_match('/\p{Cyrillic}/u', $description)) {
            return $description;
        }

        $code = $sku ? " Артикул: {$sku}." : '';

        return "{$displayName} — технический товар для профессионального использования в автосервисе, мастерской или гараже.{$code}";
    }

    public static function shortDescription(string $displayName, string $brandName): string
    {
        return app()->isLocale('ru')
            ? "{$displayName}: профессиональный инструмент для автосервиса, мастерской и гаража."
            : "{$displayName}: produs profesional pentru service auto, atelier si garaj.";
    }

    public static function fullDescription(string $displayName, string $brandName, string $sku): string
    {
        return app()->isLocale('ru')
            ? "{$displayName}, артикул {$sku}. Технический товар для профессионального использования в автосервисе, мастерской или гараже."
            : "{$displayName}, cod {$sku}. Produs tehnic pentru utilizare profesionala in service auto, atelier sau garaj.";
    }

    private static function ensureBrand(string $name, string $brand, bool $russian, ?string $sku = null): string
    {
        $name = $name ?: ($russian ? 'Профессиональный инструмент' : 'Produs profesional');

        if ($brand !== '' && ! Str::contains(Str::lower($name), Str::lower($brand))) {
            $name .= ' '.$brand;
        }
        if ($sku && ! Str::contains(Str::lower($name), Str::lower($sku))) {
            $name .= ' '.$sku;
        }

        return Str::limit(self::clean($name), 200, '');
    }

    private static function clean(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?: '', " \t\n\r\0\x0B,.;-");
    }

    private static function brand(string $brandName): string
    {
        return Str::contains(Str::upper(trim($brandName)), 'M7') ? 'M7' : trim($brandName);
    }
}
