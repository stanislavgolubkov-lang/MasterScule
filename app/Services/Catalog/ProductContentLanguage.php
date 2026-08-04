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

    public function isLikelyEnglish(string $value): bool
    {
        $value = mb_strtolower(trim($value));
        if ($value === '' || $this->containsCyrillic($value)) {
            return false;
        }

        if (preg_match(
            '/\b(?:used|designed|suitable)\s+(?:to|for|in)\b|\b(?:air blow gun|air consumption|air pressure|combination wrench|drawer divider|flexible handle|grip material|long nose pliers|ring slogging|socket rail|cutting pliers|c-clamp grip|polished heads|rail length|steel nozzle|work gloves|mesh fabric|pu leather|with moving jaw|finished with|do not cut)\b/u',
            $value,
        ) === 1) {
            return true;
        }

        $tokens = preg_split('/[^\p{L}]+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $markers = array_unique(array_intersect($tokens, [
            'the', 'with', 'for', 'and', 'used', 'design', 'designed', 'suitable',
            'chrome', 'plated', 'steel', 'size', 'sizes', 'tool', 'tools', 'wrench',
            'drive', 'socket', 'heavy', 'duty', 'adjustable', 'application',
            'applications', 'especially', 'while', 'easy', 'quality', 'direction',
            'must', 'ensure', 'ring', 'moon', 'pliers', 'hammer', 'screwdriver',
            'trolley', 'cabinet', 'gauge', 'puller', 'cutter', 'cutting', 'drill', 'extension',
            'action', 'base', 'clamp', 'clip', 'combine', 'divider', 'drawer', 'finished',
            'flexible', 'grip', 'groove', 'handle', 'jaw', 'joint', 'long', 'mini',
            'miniature', 'nose', 'piece', 'pieces', 'plastic', 'polished', 'quick',
            'rail', 'slogging',
            'activation', 'capabilities', 'diagnostic', 'intelligent', 'learning',
            'management', 'pressure', 'programming', 'provides', 'sensor', 'sensors',
            'tire', 'vehicle',
            'air', 'blow', 'breathability', 'cleaning', 'consumption', 'fabric',
            'gloves', 'gun', 'leather', 'lightweight', 'mesh', 'nozzle', 'pressure',
            'work',
        ]));

        $romanianMarkers = array_unique(array_intersect($tokens, [
            'acțiune', 'articulat', 'cheie', 'clește', 'clești', 'clichet', 'cu',
            'de', 'din', 'este', 'falcă', 'flexibil', 'inelară', 'mâner', 'pentru',
            'piese', 'prindere', 'separatoare', 'suport', 'tăiere', 'tubulare',
        ]));

        return count($markers) >= 2 && count($romanianMarkers) === 0;
    }
}
