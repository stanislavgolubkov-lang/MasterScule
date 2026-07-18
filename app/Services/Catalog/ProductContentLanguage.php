<?php

namespace App\Services\Catalog;

class ProductContentLanguage
{
    public function containsCyrillic(string $value): bool
    {
        return $value !== '' && preg_match('/\p{Cyrillic}/u', $value) === 1;
    }

    public function containsUkrainian(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (preg_match('/[іїєґІЇЄҐ]/u', $value) === 1) {
            return true;
        }

        return preg_match(
            '/\b(?:цей|ця|що|який|яка|які|можна|зручний|надійний|інструмент|обладнання|підйомник|вантажопідйомність|ціна|києві|україні|детальна|інформація|комплектація|виробник|країна|застосування)\b/iu',
            $value,
        ) === 1;
    }

    public function isRussian(string $value): bool
    {
        return $this->containsCyrillic($value) && ! $this->containsUkrainian($value);
    }

    public function isRomanian(string $value): bool
    {
        return $value !== ''
            && preg_match('/\p{Latin}/u', $value) === 1
            && ! $this->containsCyrillic($value);
    }
}
