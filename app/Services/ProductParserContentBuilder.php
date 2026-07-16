<?php

namespace App\Services;

class ProductParserContentBuilder
{
    public function build(string $sku, string $sourceName, ?string $brand = null, ?string $group = null, array $category = []): array
    {
        $sourceName = $this->clean($sourceName);
        $fallback = trim(implode(' ', array_filter([$brand, $sku])));
        $sourceName = $sourceName !== '' ? $sourceName : $fallback;
        $isCyrillic = $this->containsCyrillic($sourceName);
        $brandLabel = $this->brandLabel($brand);
        $labels = $this->categoryLabels((string) ($category['category_slug'] ?? ''));
        $categoryRu = $labels['ru'] ?: $this->clean((string) ($category['category_name_ru'] ?? '')) ?: 'Профессиональный инструмент';
        $categoryRo = $labels['ro'] ?: $this->clean((string) ($category['category_name_ro'] ?? '')) ?: 'Instrument profesional';
        $nameRu = $isCyrillic ? $sourceName : trim($categoryRu.' '.$brandLabel.' '.$sku);
        $nameRo = $isCyrillic || ! $this->looksRomanian($sourceName)
            ? trim($categoryRo.' '.$brandLabel.' '.$sku)
            : $sourceName;
        $shortRu = trim($nameRu.'. Бренд '.$brandLabel.', артикул '.$sku.'.');
        $shortRo = trim($nameRo.'. '.$brandLabel.', articol '.$sku.'.');
        $descriptionRu = $nameRu.' — товар бренда '.$brandLabel.' из категории «'.$categoryRu.'». '
            .'Артикул производителя: '.$sku.'. Подходит для профессионального использования в мастерской и автосервисе. '
            .'Перед применением проверьте характеристики и совместимость с вашей задачей.';
        $descriptionRo = $nameRo.' este un produs '.$brandLabel.' din categoria „'.$categoryRo.'”. '
            .'Cod producator: '.$sku.'. Este destinat utilizarii profesionale in atelier si service auto. '
            .'Inainte de utilizare, verificati caracteristicile si compatibilitatea cu lucrarea planificata.';
        $descriptionRo = $nameRo.'. '.$brandLabel.' '.$sku.' este recomandat pentru lucrari profesionale in atelier, service auto sau zona tehnica. '
            .'Categoria: '.$categoryRo.'. Verificati caracteristicile, dimensiunile si compatibilitatea cu lucrarea planificata inainte de utilizare.';

        return [
            'name_ru' => $nameRu,
            'name_ro' => $nameRo,
            'short_description_ru' => $shortRu,
            'short_description_ro' => $shortRo,
            'description_ru' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'needs_translation_review' => true,
            'needs_content_review' => true,
            'generated_content' => true,
            'translation_source_type' => 'generated_pending_review',
        ];
    }

    public function mergeOfficialContent(array $content, ?string $officialTitle, ?string $officialDescription, string $sku, ?string $brand = null): array
    {
        $title = $this->clean((string) $officialTitle);
        $description = $this->clean((string) $officialDescription);
        $usedOfficialTranslation = false;

        if ($title !== '') {
            if ($this->containsCyrillic($title)) {
                $content['name_ru'] = $title;
                $usedOfficialTranslation = true;
            } elseif ($this->looksRomanian($title)) {
                $content['name_ro'] = $title;
                $usedOfficialTranslation = true;
            }
        }

        if ($description !== '') {
            if ($this->containsCyrillic($description)) {
                $content['description_ru'] = $description;
                $content['short_description_ru'] = mb_strimwidth($description, 0, 240, '');
                $usedOfficialTranslation = true;
            } elseif ($this->looksRomanian($description)) {
                $content['description_ro'] = $description;
                $content['short_description_ro'] = mb_strimwidth($description, 0, 240, '');
                $usedOfficialTranslation = true;
            }
        }

        $content['needs_translation_review'] = ! filled($content['name_ru'] ?? null)
            || ! filled($content['name_ro'] ?? null)
            || ! filled($content['description_ru'] ?? null)
            || ! filled($content['description_ro'] ?? null)
            || $this->containsCyrillic((string) ($content['name_ro'] ?? '').' '.(string) ($content['description_ro'] ?? ''));

        if ($usedOfficialTranslation) {
            $content['needs_content_review'] = $description === '';
        }

        return $this->ensureComplete($content, $sku, $officialTitle ?: ($content['name_ru'] ?? $content['name_ro'] ?? $sku), $brand);
    }

    public function ensureComplete(array $content, string $sku, string $sourceName, ?string $brand = null, ?string $group = null, array $category = []): array
    {
        $fallback = $this->build($sku, $sourceName, $brand, $group, $category);
        $usedFallback = false;

        foreach ([
            'name_ru',
            'name_ro',
            'short_description_ru',
            'short_description_ro',
            'description_ru',
            'description_ro',
        ] as $key) {
            if (! filled($content[$key] ?? null)) {
                $content[$key] = $fallback[$key];
                $usedFallback = true;
            }
        }

        if (! filled($content['short_description_ru'] ?? null) && filled($content['description_ru'] ?? null)) {
            $content['short_description_ru'] = mb_strimwidth((string) $content['description_ru'], 0, 240, '');
            $usedFallback = true;
        }

        if (! filled($content['short_description_ro'] ?? null) && filled($content['description_ro'] ?? null)) {
            $content['short_description_ro'] = mb_strimwidth((string) $content['description_ro'], 0, 240, '');
            $usedFallback = true;
        }

        $hasMissing = ! filled($content['name_ru'] ?? null)
            || ! filled($content['name_ro'] ?? null)
            || ! filled($content['description_ru'] ?? null)
            || ! filled($content['description_ro'] ?? null);
        $roContainsCyrillic = $this->containsCyrillic((string) ($content['name_ro'] ?? '').' '.(string) ($content['description_ro'] ?? ''));

        $content['generated_content'] = (bool) ($content['generated_content'] ?? false) || $usedFallback;
        $content['needs_translation_review'] = (bool) ($content['needs_translation_review'] ?? false)
            || $usedFallback
            || $hasMissing
            || $roContainsCyrillic;
        $content['needs_content_review'] = (bool) ($content['needs_content_review'] ?? false)
            || $usedFallback
            || $hasMissing
            || (bool) $content['generated_content'];
        $content['translation_source_type'] = $content['translation_source_type']
            ?? ($usedFallback ? 'generated_pending_review' : 'official_or_imported');

        return $content;
    }

    private function containsCyrillic(string $value): bool
    {
        return $value !== '' && preg_match('/\p{Cyrillic}/u', $value) === 1;
    }

    private function looksRomanian(string $value): bool
    {
        return preg_match('/[ăâîșşțţ]/iu', $value) === 1
            || preg_match('/\b(pentru|produs|instrument|scule|utilizare|atelier|profesional)\b/iu', $value) === 1;
    }

    private function brandLabel(?string $brand): string
    {
        $brand = $this->clean((string) $brand);
        $normalized = mb_strtolower($brand, 'UTF-8');

        if (str_contains($normalized, 'mighty') || preg_match('/(^|\W)m7(\W|$)/iu', $brand) === 1) {
            return 'M7';
        }

        return $brand !== '' ? $brand : 'MasterScule';
    }

    private function categoryLabels(string $slug): array
    {
        return match ($slug) {
            'furtunuri-cuple-accesorii' => ['ru' => 'Пневматическая муфта или аксессуар', 'ro' => 'Cupla sau accesoriu pneumatic'],
            'consumabile-pentru-scule-pneumatice' => ['ru' => 'Расходный материал для пневмоинструмента', 'ro' => 'Consumabil pentru scule pneumatice'],
            'polizoare-si-slefuitoare-pneumatice' => ['ru' => 'Пневматическая шлифовальная машина', 'ro' => 'Masina pneumatica de slefuit'],
            'pistoale-suflat-si-sablare' => ['ru' => 'Пневматический пистолет', 'ro' => 'Pistol pneumatic'],
            'pistoale-pentru-silicon-si-gresare' => ['ru' => 'Пневматический пистолет для смазки', 'ro' => 'Pistol pneumatic pentru gresare'],
            'chei-pneumatice' => ['ru' => 'Пневматический гайковерт', 'ro' => 'Cheie pneumatica'],
            'clichete-pneumatice' => ['ru' => 'Пневматическая трещотка', 'ro' => 'Clichet pneumatic'],
            'ciocane-pneumatice' => ['ru' => 'Пневматический молоток', 'ro' => 'Ciocan pneumatic'],
            'burghie-pneumatice' => ['ru' => 'Пневматическая дрель', 'ro' => 'Masina pneumatica de gaurit'],
            'surubelnite-pneumatice' => ['ru' => 'Пневматическая отвертка', 'ro' => 'Surubelnita pneumatica'],
            'foarfeci-ferastraie-si-debitare-pneumatice' => ['ru' => 'Пневматический режущий инструмент', 'ro' => 'Instrument pneumatic de taiere'],
            'nituitoare-capsatoare-si-cuie-pneumatice' => ['ru' => 'Пневматический заклепочник', 'ro' => 'Nituitor pneumatic'],
            'extractoare-si-prese' => ['ru' => 'Автомобильный съемник', 'ro' => 'Extractor auto'],
            'scule-pentru-roti-vulcanizare' => ['ru' => 'Инструмент для колес и шиномонтажа', 'ro' => 'Instrument pentru roti si vulcanizare'],
            'accesorii-pneumatice' => ['ru' => 'Аксессуар для пневмоинструмента', 'ro' => 'Accesoriu pentru scule pneumatice'],
            'scule-pneumatice' => ['ru' => 'Пневматический инструмент', 'ro' => 'Instrument pneumatic'],
            default => ['ru' => '', 'ro' => ''],
        };
    }

    private function clean(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/^\s*(?:https?:\/\/)?(?:www\.)?tristool\.md\s*(?:[-–—:|]\s*)?/iu', '', $value) ?: $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?: '', " \t\n\r\0\x0B,.;");
    }
}
