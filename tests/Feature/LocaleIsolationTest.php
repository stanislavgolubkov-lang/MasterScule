<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Arr;
use Tests\TestCase;

class LocaleIsolationTest extends TestCase
{
    public function test_public_translation_dictionaries_are_complete_and_language_safe(): void
    {
        $uiRu = Arr::dot(include base_path('lang/ru/ui.php'));
        $uiRo = Arr::dot(include base_path('lang/ro/ui.php'));
        $catalogRu = (include base_path('lang/ru/catalog.php'))['categories'];
        $catalogRo = (include base_path('lang/ro/catalog.php'))['categories'];
        $pagesRu = Arr::dot(include base_path('lang/ru/pages.php'));
        $pagesRo = Arr::dot(include base_path('lang/ro/pages.php'));
        $jsonRu = json_decode(file_get_contents(base_path('lang/ru.json')), true, flags: JSON_THROW_ON_ERROR);
        $jsonRo = json_decode(file_get_contents(base_path('lang/ro.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(array_keys($uiRu), array_keys($uiRo));
        $this->assertSame(array_keys($catalogRu), array_keys($catalogRo));
        $this->assertSame(array_keys($pagesRu), array_keys($pagesRo));
        $this->assertSame(array_keys($jsonRu), array_keys($jsonRo));

        foreach (array_merge($uiRo, $catalogRo, $pagesRo, $jsonRo) as $value) {
            if (is_string($value)) {
                $this->assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', $value);
            }
        }

        foreach ($catalogRu as $value) {
            $this->assertMatchesRegularExpression('/\p{Cyrillic}/u', $value);
        }
    }

    public function test_category_name_fallback_never_uses_the_other_locale(): void
    {
        $category = new Category([
            'slug' => 'locale-isolation-test',
            'name' => 'Русское название',
            'name_ro' => 'Denumire română',
        ]);

        app()->setLocale('ru');
        $this->assertSame('Русское название', $category->display_name);

        app()->setLocale('ro');
        $this->assertSame('Denumire română', $category->display_name);

        $category->name_ro = null;
        $this->assertSame('locale-isolation-test', $category->display_name);
    }

    public function test_product_content_never_falls_back_to_the_other_locale(): void
    {
        $product = new Product([
            'sku' => 'LANG-1',
            'name' => 'Русское название',
            'name_ru' => 'Русское название',
            'name_ro' => 'Denumire română',
            'description_ru' => 'Описание товара на русском языке.',
            'description_ro' => 'Descrierea produsului în limba română.',
            'package_contents' => [
                'Draft parser preview',
                'Комплект крепежа',
                'renewal kits',
                'TORX T10',
            ],
        ]);

        app()->setLocale('ru');
        $this->assertSame('Русское название', $product->display_name);
        $this->assertSame('Описание товара на русском языке.', $product->display_description);
        $this->assertSame(['Комплект крепежа', 'TORX T10'], $product->display_package_contents);

        app()->setLocale('ro');
        $this->assertSame('Denumire română', $product->display_name);
        $this->assertSame('Descrierea produsului în limba română.', $product->display_description);
        $this->assertSame(['TORX T10'], $product->display_package_contents);

        $product->name_ro = null;
        $product->description_ro = null;
        $this->assertSame('Produs LANG-1', $product->display_name);
        $this->assertStringContainsString('produs profesional', $product->display_description);
        $this->assertStringNotContainsString('Русское', $product->display_description);
    }
}
