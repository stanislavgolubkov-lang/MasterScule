<?php

namespace App\Support;

use Illuminate\Support\Str;

class ProductLocalizer
{
    public static function name(string $title, string $brandName = '', ?string $sku = null): string
    {
        return self::ensureBrand($title, self::brand($brandName));
    }

    public static function russianName(string $title, string $brandName = '', ?string $sku = null): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $title));

        if ($name === '') {
            return trim('Профессиональный инструмент '.self::brand($brandName).' '.($sku ?? ''));
        }

        if (preg_match('/[А-Яа-яЁё]/u', $name)) {
            return self::ensureBrand($name, self::brand($brandName));
        }

        $replacements = [
            '/\bSet de scule\b/iu' => 'Набор инструментов',
            '/\bSet scule\b/iu' => 'Набор инструментов',
            '/\bSet de tubulare\b/iu' => 'Набор головок',
            '/\bSet tubulare\b/iu' => 'Набор головок',
            '/\bSet reparatie filet\b/iu' => 'Набор для восстановления резьбы',
            '/\bSet profesional\b/iu' => 'Профессиональный набор',
            '/\bCheie dinamometrica\b/iu' => 'Динамометрический ключ',
            '/\bCheie tubulara\b/iu' => 'Торцевая головка',
            '/\bChei combinate\b/iu' => 'Комбинированные ключи',
            '/\bChei si surubelnite\b/iu' => 'Ключи и отвертки',
            '/\bTubulare si clichete\b/iu' => 'Головки и трещотки',
            '/\bPistol pneumatic\b/iu' => 'Пневмогайковерт',
            '/\bPistoale pneumatice\b/iu' => 'Пневмопистолеты',
            '/\bCompresor\b/iu' => 'Компрессор',
            '/\bCarucior scule\b/iu' => 'Тележка для инструментов',
            '/\bDulap\b/iu' => 'Шкаф',
            '/\bCric\b/iu' => 'Домкрат',
            '/\bCricuri\b/iu' => 'Домкраты',
            '/\bExtractor\b/iu' => 'Съемник',
            '/\bExtractoare\b/iu' => 'Съемники',
            '/\bSurubelnite\b/iu' => 'Отвертки',
            '/\bSurubelnita\b/iu' => 'Отвертка',
            '/\bClichet\b/iu' => 'Трещотка',
            '/\bChei\b/iu' => 'Ключи',
            '/\bCheie\b/iu' => 'Ключ',
            '/\bbit\b/iu' => 'бит',
            '/\bbiti\b/iu' => 'биты',
            '/\bantifurt\b/iu' => 'для секреток',
            '/\bprofesional\b/iu' => 'профессиональный',
            '/\bpneumatic\b/iu' => 'пневматический',
            '/\bpneumatice\b/iu' => 'пневматические',
            '/\bmanual\b/iu' => 'ручной',
            '/\bpiese\b/iu' => 'предметов',
            '/\bmm\b/iu' => 'мм',
            '/\bpana la\b/iu' => 'до',
            '/\bpentru\b/iu' => 'для',
            '/\bcu\b/iu' => 'с',
            '/\bsi\b/iu' => 'и',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $name = preg_replace($pattern, $replacement, $name);
        }

        $name = preg_replace('/\s+/u', ' ', $name);
        $name = trim($name, " \t\n\r\0\x0B,.-");

        return self::ensureBrand($name, self::brand($brandName));
    }

    public static function russianDescription(?string $description, string $displayName, string $brandName = '', ?string $sku = null): string
    {
        $description = trim((string) $description);

        if ($description !== '' && preg_match('/[А-Яа-яЁё]/u', $description)) {
            return $description;
        }

        $brand = self::brand($brandName);
        $code = $sku ? " Код товара: {$sku}." : '';
        $brandText = Str::contains(Str::lower($displayName), Str::lower($brand)) ? '' : " {$brand}";

        return "{$displayName}{$brandText} подходит для автосервиса, мастерской и гаража. Карточка содержит цену в MDL, наличие, изображение, гарантию и основные технические характеристики.{$code}";
    }

    public static function shortDescription(string $displayName, string $brandName): string
    {
        $brand = self::brand($brandName);

        if (app()->isLocale('ru')) {
            return "{$displayName}: товар {$brand} для автосервиса, мастерской и гаража.";
        }

        return "{$displayName}: produs {$brand} pentru service auto, atelier si garaj.";
    }

    public static function fullDescription(string $displayName, string $brandName, string $sku): string
    {
        $brand = self::brand($brandName);

        if (app()->isLocale('ru')) {
            return "{$displayName}, код {$sku}, товар {$brand} для автосервиса, мастерской и гаража. Карточка содержит код, цену в MDL, изображение, наличие, гарантию и основные характеристики.";
        }

        return "{$displayName}, cod {$sku}, este un produs {$brand} pregatit pentru utilizare in service auto, atelier si garaj.";
    }

    private static function ensureBrand(string $name, string $brand): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        if ($name === '') {
            $name = app()->isLocale('ru') ? 'Профессиональный инструмент' : 'Produs profesional';
        }

        if ($brand !== '' && ! Str::contains(Str::lower($name), Str::lower($brand))) {
            $name .= ' '.$brand;
        }

        return Str::limit($name, 130, '');
    }

    private static function brand(string $brandName): string
    {
        return str_contains($brandName, 'M7') ? 'M7' : (trim($brandName) ?: 'King Tony');
    }
}
