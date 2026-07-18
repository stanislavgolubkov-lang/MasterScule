<?php

namespace App\Services;

use App\Services\Catalog\ProductContentLanguage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProductTranslationService
{
    public function __construct(
        private ProductParserSettings $settings,
        private ProductContentLanguage $language,
    ) {}

    public function bilingual(array $source): array
    {
        $title = $this->clean((string) ($source['title'] ?? ''));
        $description = $this->clean((string) ($source['description'] ?? ''));
        $titleRu = $this->clean((string) ($source['title_ru'] ?? ''));
        $titleRo = $this->clean((string) ($source['title_ro'] ?? ''));
        $descriptionRu = $this->clean((string) ($source['description_ru'] ?? ''));
        $descriptionRo = $this->clean((string) ($source['description_ro'] ?? ''));

        // A Cyrillic string is not automatically Russian. Never allow source
        // Ukrainian content to occupy the public RU fields.
        if ($this->language->containsUkrainian($titleRu)) {
            $titleRu = '';
        }
        if ($this->language->containsUkrainian($descriptionRu)) {
            $descriptionRu = '';
        }

        // Some TrisTool /ro/ pages return the Russian card unchanged. A locale
        // URL is not proof that the text is actually Romanian.
        if ($this->isRussian($titleRo)) {
            $titleRo = '';
        }
        if ($this->isRussian($descriptionRo)) {
            $descriptionRo = '';
        }

        if ($titleRu === '' && $title !== '' && $this->isRussian($title)) {
            $titleRu = $title;
        }
        if ($descriptionRu === '' && $description !== '' && $this->isRussian($description)) {
            $descriptionRu = $description;
        }
        if ($titleRo === '' && $title !== '' && $this->isRomanian($title)) {
            $titleRo = $title;
        }
        if ($descriptionRo === '' && $description !== '' && $this->isRomanian($description)) {
            $descriptionRo = $description;
        }

        $baseTitle = $titleRu ?: $titleRo ?: $title;
        $baseDescription = $descriptionRu ?: $descriptionRo ?: $description;

        $titleRu = $titleRu ?: $this->translate($baseTitle, 'ru');
        $titleRo = $titleRo ?: $this->translate($baseTitle, 'ro');
        $descriptionRu = $descriptionRu ?: $this->translate($baseDescription, 'ru');
        $descriptionRo = $descriptionRo ?: $this->translate($baseDescription, 'ro');

        $complete = $titleRu !== ''
            && $titleRo !== ''
            && $descriptionRu !== ''
            && $descriptionRo !== ''
            && $this->isRussian($titleRu.' '.$descriptionRu)
            && $this->isRomanian($titleRo.' '.$descriptionRo)
            && ! $this->language->containsUkrainian($titleRu.' '.$titleRo.' '.$descriptionRu.' '.$descriptionRo);

        return [
            'name_ru' => $titleRu ?: null,
            'name_ro' => $titleRo ?: null,
            'short_description_ru' => $descriptionRu !== '' ? mb_strimwidth($descriptionRu, 0, 240, '') : null,
            'short_description_ro' => $descriptionRo !== '' ? mb_strimwidth($descriptionRo, 0, 240, '') : null,
            'description_ru' => $descriptionRu ?: null,
            'description_ro' => $descriptionRo ?: null,
            'complete' => $complete,
            'translation_source_type' => $this->hasBilingualSource($source)
                ? 'source_bilingual'
                : ($complete ? 'machine_translation' : 'translation_failed'),
        ];
    }

    public function translate(string $text, string $target): string
    {
        $text = $this->clean($text);
        if ($text === '' || ! in_array($target, ['ru', 'ro'], true)) {
            return '';
        }

        if (($target === 'ru' && $this->isRussian($text))
            || ($target === 'ro' && $this->isRomanian($text))) {
            return $text;
        }

        if (! $this->settings->get('translation.enabled', true)) {
            return '';
        }

        $key = 'parser-translation:'.sha1($target.'|'.$text);
        $cached = Cache::get($key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $translated = $this->translateWithGoogle($text, $target);
        if ($translated === '') {
            $translated = $this->translateWithFallback($text, $target);
        }

        // Never cache a failed translation. A temporary provider outage must
        // remain recoverable on the next automatic parser pass.
        if ($translated !== '') {
            Cache::put($key, $translated, now()->addDays(90));
        }

        return $translated;
    }

    private function translateWithGoogle(string $text, string $target): string
    {
        try {
            $response = Http::withOptions(['proxy' => ''])
                ->timeout((int) $this->settings->get('translation.timeout', 15))
                ->retry(2, 350)
                ->get((string) $this->settings->get(
                    'translation.base_url',
                    'https://translate.googleapis.com/translate_a/single',
                ), [
                    'client' => 'gtx',
                    'sl' => 'auto',
                    'tl' => $target,
                    'dt' => 't',
                    'q' => $text,
                ]);

            if (! $response->successful()) {
                return '';
            }

            return $this->clean(collect($response->json()[0] ?? [])
                ->map(fn ($segment) => is_array($segment) ? ($segment[0] ?? '') : '')
                ->implode(''));
        } catch (Throwable) {
            return '';
        }
    }

    private function translateWithFallback(string $text, string $target): string
    {
        $source = $this->language->containsUkrainian($text)
            ? 'uk'
            : ($this->isRussian($text) ? 'ru' : ($this->isRomanian($text) ? 'ro' : 'en'));
        if ($source === $target) {
            return $text;
        }

        try {
            $response = Http::withOptions(['proxy' => ''])
                ->timeout((int) $this->settings->get('translation.timeout', 15))
                ->retry(2, 350)
                ->get((string) $this->settings->get(
                    'translation.fallback_url',
                    'https://api.mymemory.translated.net/get',
                ), [
                    'q' => $text,
                    'langpair' => $source.'|'.$target,
                ]);

            if (! $response->successful()) {
                return '';
            }

            return $this->clean((string) data_get($response->json(), 'responseData.translatedText', ''));
        } catch (Throwable) {
            return '';
        }
    }

    private function hasBilingualSource(array $source): bool
    {
        return filled($source['title_ru'] ?? null)
            && filled($source['title_ro'] ?? null)
            && filled($source['description_ru'] ?? null)
            && filled($source['description_ro'] ?? null)
            && ! $this->language->containsUkrainian(implode(' ', [
                (string) $source['title_ru'],
                (string) $source['title_ro'],
                (string) $source['description_ru'],
                (string) $source['description_ro'],
            ]))
            && $this->isRussian((string) $source['title_ru'].' '.(string) $source['description_ru'])
            && ! $this->isRussian((string) $source['title_ro'])
            && ! $this->isRussian((string) $source['description_ro'])
            && $this->isRomanian((string) $source['title_ro'].' '.(string) $source['description_ro']);
    }

    private function isRussian(string $value): bool
    {
        return $this->language->isRussian($value);
    }

    private function isRomanian(string $value): bool
    {
        return $this->language->isRomanian($value);
    }

    private function clean(string $value): string
    {
        return trim((string) preg_replace(
            '/[ \t]+/u',
            ' ',
            html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ));
    }
}
