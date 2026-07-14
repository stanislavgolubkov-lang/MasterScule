<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductPublicationGuard
{
    public function __construct(
        private readonly ProductImageAvailabilityService $images,
        private readonly ProductLanguageQualityGuard $languages,
        private readonly ProductImageQualityGuard $imageQuality,
    ) {}

    public function evaluate(Product $product, bool $approveGeneralReview = false, array $approvedReviewFlags = []): array
    {
        $errors = [];
        $warnings = [];
        $approvedReviewFlags = array_fill_keys($approvedReviewFlags, true);

        $add = static function (string $code, string $message) use (&$errors): void {
            $errors[$code] = $message;
        };

        if (! filled($product->sku)) {
            $add('missing_sku', $this->message('Не указан SKU.', 'SKU lipseste.'));
        }
        $brandExists = $product->relationLoaded('brand')
            ? $product->brand !== null
            : ($product->brand_id && Brand::whereKey($product->brand_id)->exists());
        if (! $brandExists) {
            $add('missing_brand', $this->message('Не указан существующий бренд.', 'Marca valida lipseste.'));
        }
        $categoryExists = $product->relationLoaded('category')
            ? $product->category !== null
            : ($product->category_id && Category::whereKey($product->category_id)->exists());
        if (! $categoryExists) {
            $add('missing_category', $this->message('Не указана существующая категория.', 'Categoria valida lipseste.'));
        }
        if ((float) $product->price <= 0) {
            $add('invalid_price', $this->message('Цена должна быть больше нуля.', 'Pretul trebuie sa fie mai mare decat zero.'));
        }

        $expectedCurrency = Str::upper((string) config('store.currency', 'MDL'));
        if (Str::upper((string) $product->currency) !== $expectedCurrency) {
            $add('invalid_currency', $this->message("Валюта должна быть {$expectedCurrency}.", "Moneda trebuie sa fie {$expectedCurrency}."));
        }

        $image = $this->images->inspect($product->main_image);
        if (! $image['available']) {
            $messages = [
                'missing' => ['Нет главного фото товара.', 'Imaginea principala lipseste.'],
                'placeholder' => ['Вместо фото указана заглушка.', 'Imaginea principala este un placeholder.'],
                'file_missing' => ['Файл главного фото не найден.', 'Fisierul imaginii principale nu exista.'],
                'unsafe_path' => ['Путь главного фото небезопасен.', 'Calea imaginii principale nu este sigura.'],
                'remote_not_verified' => ['Внешнее фото не сохранено и не проверено локально.', 'Imaginea externa nu este salvata si verificata local.'],
                'not_an_image' => ['Файл главного фото не является изображением.', 'Fisierul principal nu este o imagine valida.'],
            ];
            [$ru, $ro] = $messages[$image['code']] ?? ['Главное фото недоступно.', 'Imaginea principala nu este disponibila.'];
            $add('invalid_image_'.$image['code'], $this->message($ru, $ro));
        }

        $flags = [
            'needs_image_review' => ['Требуется проверка фото.', 'Este necesara verificarea imaginii.'],
            'needs_category_review' => ['Требуется проверка категории.', 'Este necesara verificarea categoriei.'],
            'needs_translation_review' => ['Требуется проверка перевода.', 'Este necesara verificarea traducerii.'],
            'needs_price_review' => ['Требуется проверка цены.', 'Este necesara verificarea pretului.'],
            'needs_stock_review' => ['Требуется проверка остатка.', 'Este necesara verificarea stocului.'],
        ];

        foreach ($flags as $field => [$ru, $ro]) {
            if ((bool) $product->{$field} && ! isset($approvedReviewFlags[$field])) {
                $add($field, $this->message($ru, $ro));
            }
        }

        if ((bool) $product->needs_content_review && ! isset($approvedReviewFlags['needs_content_review'])) {
            $add('needs_content_review', 'Product content requires review.');
        }
        if ((bool) $product->needs_source_review && ! isset($approvedReviewFlags['needs_source_review'])) {
            $add('needs_source_review', 'Product source requires review.');
        }

        if (! $approveGeneralReview && (bool) $product->needs_review) {
            $add('needs_review', $this->message('Товар не прошёл общую модерацию.', 'Produsul nu a trecut moderarea generala.'));
        }

        $nameRu = trim((string) ($product->name_ru ?: $product->name));
        $nameRo = trim((string) $product->name_ro);
        $descriptionRu = trim((string) ($product->short_description_ru ?: $product->short_description ?: $product->description_ru ?: $product->description));
        $descriptionRo = trim((string) ($product->short_description_ro ?: $product->description_ro));

        if ($nameRu === '' || $this->isMachinePlaceholder($nameRu)) {
            $add('missing_name_ru', $this->message('Нет нормального названия на русском.', 'Denumirea rusa valida lipseste.'));
        }
        if ($nameRo === '') {
            $add('missing_name_ro', $this->message('Нет названия на румынском.', 'Denumirea romana lipseste.'));
        }
        if ($descriptionRu === '' || $this->isMachinePlaceholder($descriptionRu)) {
            $add('missing_description_ru', $this->message('Нет нормального описания на русском.', 'Descrierea rusa valida lipseste.'));
        }
        if ($descriptionRo === '') {
            $add('missing_description_ro', $this->message('Нет описания на румынском.', 'Descrierea romana lipseste.'));
        }
        $allRomanianText = implode(' ', array_filter([
            $product->name_ro,
            $product->short_description_ro,
            $product->description_ro,
        ]));
        if ($this->containsCyrillic($allRomanianText)) {
            $add('ro_contains_cyrillic', $this->message('RO-текст содержит кириллицу.', 'Textul RO contine caractere chirilice.'));
        }

        foreach ($this->languages->evaluate($product)['errors'] as $code => $message) {
            $add($code, $message);
        }
        foreach ($this->imageQuality->evaluate($product)['errors'] as $code => $message) {
            $add($code, $message);
        }

        if ($product->source_import_batch_id && ! $product->source_reviewed_at) {
            $minimum = (int) config('product_parser.min_official_confidence', 90);
            if ((int) $product->parser_confidence < $minimum) {
                $add('source_confidence_low', "Source confidence must be at least {$minimum}% or manually approved.");
            }
            if ($product->fallback_source_used) {
                $add('fallback_not_approved', 'Fallback source requires manual approval.');
            }
        }

        if (! filled($product->stock_status)) {
            $warnings[] = $this->message('Не указан статус остатка.', 'Starea stocului lipseste.');
        }

        return [
            'allowed' => $errors === [],
            'errors' => array_values($errors),
            'error_codes' => array_keys($errors),
            'warnings' => $warnings,
            'image' => $image,
        ];
    }

    public function publish(Product $product, bool $approveGeneralReview = true, array $approvedReviewFlags = []): array
    {
        $result = $this->evaluate($product, $approveGeneralReview, $approvedReviewFlags);

        if (! $result['allowed']) {
            return $result;
        }

        $approvedFlags = array_fill_keys(array_intersect($approvedReviewFlags, [
            'needs_category_review',
            'needs_translation_review',
            'needs_content_review',
            'needs_price_review',
            'needs_stock_review',
        ]), false);

        $product->forceFill(array_merge($approvedFlags, [
            'status' => 'published',
            'approval_status' => 'approved',
            'needs_review' => false,
            'is_active' => true,
        ]))->save();

        return $result;
    }

    public function unpublish(Product $product): void
    {
        $product->forceFill([
            'status' => 'draft',
            'approval_status' => 'pending_review',
            'needs_review' => true,
            'is_active' => false,
        ])->save();
    }

    private function containsCyrillic(string $value): bool
    {
        return $value !== '' && preg_match('/\p{Cyrillic}/u', $value) === 1;
    }

    private function isMachinePlaceholder(string $value): bool
    {
        $value = Str::lower(trim($value));

        return $value === '' || Str::contains($value, [
            'draft product',
            'draft parser',
            'lorem ipsum',
            'todo',
            'tbd',
            'unknown product',
            'тестовый товар',
            'описание будет добавлено',
        ]);
    }

    private function message(string $ru, string $ro): string
    {
        return app()->isLocale('ru') ? $ru : $ro;
    }
}
