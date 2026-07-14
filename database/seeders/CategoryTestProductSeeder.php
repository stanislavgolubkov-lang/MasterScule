<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryTestProductSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::firstOrCreate(
            ['slug' => 'test-catalog'],
            [
                'name' => 'Test Catalog',
                'description' => 'Temporary products for checking catalog categories.',
                'is_featured' => false,
            ],
        );
        $image = '/images/tasks/for-workshop.png';

        Category::query()->orderBy('id')->each(function (Category $category) use ($brand, $image) {
            $sku = 'TEST-CAT-'.str_pad((string) $category->id, 3, '0', STR_PAD_LEFT);
            $nameRu = 'Тестовый товар: '.$category->name;
            $nameRo = 'Produs test: '.($category->name_ro ?: $category->name);
            $descriptionRu = 'Тестовый товар для проверки категории «'.$category->name.'». Не предназначен для реальной продажи.';
            $descriptionRo = 'Produs de test pentru verificarea categoriei „'.($category->name_ro ?: $category->name).'”. Nu este destinat vanzarii reale.';

            $product = Product::create([
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'name' => $nameRu,
                'name_ru' => $nameRu,
                'name_ro' => $nameRo,
                'slug' => 'test-product-'.$category->slug,
                'sku' => $sku,
                'short_description' => $descriptionRu,
                'short_description_ru' => $descriptionRu,
                'short_description_ro' => $descriptionRo,
                'description' => $descriptionRu,
                'description_ru' => $descriptionRu,
                'description_ro' => $descriptionRo,
                'price' => 100 + $category->id,
                'old_price' => null,
                'currency' => config('store.currency', 'MDL'),
                'stock_quantity' => 10,
                'stock_status' => 'in_stock',
                'status' => 'published',
                'approval_status' => 'approved',
                'needs_review' => false,
                'needs_stock_review' => false,
                'needs_image_review' => false,
                'needs_category_review' => false,
                'needs_translation_review' => false,
                'needs_price_review' => false,
                'main_image' => $image,
                'gallery' => [$image],
                'attributes' => [
                    'Tip' => 'Produs test',
                    'Categorie' => $category->name_ro ?: $category->name,
                ],
                'package_contents' => ['Produs test'],
                'rating' => 5,
                'reviews_count' => 0,
                'is_active' => true,
                'is_featured' => false,
                'is_bestseller' => false,
                'is_new' => true,
                'is_discounted' => false,
                'warranty' => 'Produs test',
                'meta_title' => Str::limit($nameRu.' | '.config('store.domain_label'), 255, ''),
                'meta_description' => Str::limit($descriptionRu, 150),
            ]);

            $product->syncCategoryLinks([$category->id], $category->id, 'test_seed');
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $image,
                'alt' => $nameRo,
                'sort_order' => 1,
            ]);
        });
    }
}
