<?php

namespace App\Support;

use Illuminate\Support\Str;

class ProductLocalizer
{
    public static function name(string $title, string $brandName = '', ?string $sku = null): string
    {
        $brand = str_contains($brandName, 'M7') ? 'M7' : 'King Tony';
        $title = trim((string) preg_replace('/\s+/u', ' ', $title));

        if (! preg_match('/[А-Яа-яЁё]/u', $title)) {
            return self::ensureBrand($title, $brand);
        }

        $lower = mb_strtolower($title, 'UTF-8');
        $type = self::typeFromTitle($lower);
        $specs = self::specsFromTitle($title);
        $suffix = $specs !== '' ? $specs : ($sku ?: '');

        return self::ensureBrand(trim($type.' '.$suffix), $brand);
    }

    public static function shortDescription(string $displayName, string $brandName): string
    {
        $brand = str_contains($brandName, 'M7') ? 'M7 / Mighty Seven' : 'King Tony';

        return "{$displayName}: produs {$brand} pentru service auto, atelier si garaj.";
    }

    public static function fullDescription(string $displayName, string $brandName, string $sku): string
    {
        $brand = str_contains($brandName, 'M7') ? 'M7 / Mighty Seven' : 'King Tony';

        return "{$displayName}, cod {$sku}, este un produs {$brand} pregatit pentru utilizare in service auto, atelier si garaj. Cardul include cod produs, pret in RON, imagine, stoc disponibil, garantie si caracteristici tehnice de baza.";
    }

    private static function typeFromTitle(string $lower): string
    {
        return match (true) {
            str_contains($lower, 'шлифмаш') && str_contains($lower, 'вакуум') => 'Masina de slefuit orbitala cu aspiratie',
            str_contains($lower, 'шлифмаш') && str_contains($lower, 'ленточ') => 'Masina de slefuit cu banda',
            str_contains($lower, 'шлифмаш') => 'Masina de slefuit orbitala',
            str_contains($lower, 'полировоч') => 'Masina de polisat',
            str_contains($lower, 'сменная подошва') => 'Talpa de schimb pentru masina orbitala',
            str_contains($lower, 'диск полиров') => 'Disc de polisare',
            str_contains($lower, 'форсун') => 'Extractor injectoare diesel',
            str_contains($lower, 'зачистная машина') => 'Aparat pneumatic cu ace',
            str_contains($lower, 'лента абразив') => 'Banda abraziva',
            str_contains($lower, 'пескостру') => 'Pistol de sablare',
            str_contains($lower, 'пневмопылесос') => 'Aspirator pneumatic',
            str_contains($lower, 'шприц пневмат') => 'Pistol pneumatic pentru vaselina',
            str_contains($lower, 'аккумуляторная болгарка') => 'Polizor unghiular cu acumulator',
            str_contains($lower, 'аккумуляторная дрель') => 'Masina de gaurit cu percutie',
            str_contains($lower, 'сабельная пила') => 'Fierastrau sabie cu acumulator',
            str_contains($lower, 'гайков') => 'Cheie de impact cu acumulator',
            str_contains($lower, 'импакт') => 'Surubelnita de impact',
            str_contains($lower, 'трещотка') => 'Clichet',
            str_contains($lower, 'рукоятка отверт') => 'Maner pentru surubelnita',
            str_contains($lower, 'отверт') => 'Surubelnita',
            str_contains($lower, 'наконечник') => 'Varf dublu pentru surubelnita',
            str_contains($lower, 'намагнич') => 'Magnetizator si demagnetizator pentru surubelnite',
            str_contains($lower, 'головка свеч') => 'Cheie tubulara pentru bujii',
            str_contains($lower, 'головка') => 'Cheie tubulara',
            str_contains($lower, 'насадка') || str_contains($lower, 'бита') => 'Bit special',
            str_contains($lower, 'набор') => 'Set scule',
            str_contains($lower, 'масля') => 'Extractor filtru ulei',
            str_contains($lower, 'съемник') || str_contains($lower, 'съёмник') => self::extractorType($lower),
            str_contains($lower, 'фиксатор') => 'Dispozitiv de blocare',
            str_contains($lower, 'оправка') => 'Compresor pentru inele piston',
            str_contains($lower, 'ключ') => 'Cheie speciala',
            str_contains($lower, 'инструмент') => 'Unealta speciala',
            str_contains($lower, 'шабер') => 'Racleta plata',
            str_contains($lower, 'сверло') => 'Burghiu HSS',
            str_contains($lower, 'молоток') => 'Ciocan pentru tinichigerie',
            str_contains($lower, 'тестер') => 'Tester circuite',
            str_contains($lower, 'глубиномер') => 'Adancimetru electronic pentru profil anvelope',
            str_contains($lower, 'клещи') => 'Cleste special',
            str_contains($lower, 'зажим') => 'Cleme pentru furtun',
            default => 'Scula profesionala',
        };
    }

    private static function extractorType(string $lower): string
    {
        return match (true) {
            str_contains($lower, 'масля') => 'Extractor filtru ulei',
            str_contains($lower, 'шаровых') => 'Extractor articulatii sferice',
            str_contains($lower, 'подшип') => 'Extractor rulmenti',
            str_contains($lower, 'шкива') => 'Extractor fulie',
            default => 'Extractor profesional',
        };
    }

    private static function specsFromTitle(string $title): string
    {
        preg_match_all('/(?:\d+[,.]?\d*\s*(?:мм|mm|Nm|Нм|В|V|л|L|об\/мин|шт\.?|предм\.?|отв\.?)|\d+\/\d+"|\d+"\s*-\s*\d+\s*мм|M\d+\*?P?\.?\d*|#[0-9]+|[0-9]+x[0-9]+(?:\/\s*[0-9]+x[0-9]+)*)/iu', $title, $matches);

        $specs = collect($matches[0] ?? [])
            ->map(fn ($value) => str_replace(
                ['мм', 'Нм', 'В', 'об/мин', 'шт.', 'предм.', 'отв.'],
                ['mm', 'Nm', 'V', 'rpm', 'buc.', 'piese', 'gauri'],
                $value
            ))
            ->unique()
            ->take(5)
            ->implode(', ');

        return trim($specs);
    }

    private static function ensureBrand(string $name, string $brand): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));
        $name = preg_replace('/[А-Яа-яЁё]+/u', '', $name);
        $name = trim((string) preg_replace('/\s+/u', ' ', $name), " \t\n\r\0\x0B,.-");

        if ($name === '') {
            $name = 'Produs profesional';
        }

        if (! Str::contains(Str::lower($name), Str::lower($brand))) {
            $name .= ' '.$brand;
        }

        return Str::limit($name, 130, '');
    }
}
