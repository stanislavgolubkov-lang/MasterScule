<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductParserItem;
use Illuminate\Support\Str;
use RuntimeException;

class ProductDraftService
{
    public function createDraft(ProductParserItem $item): Product
    {
        if ($existing = Product::where('sku', $item->sku)->first()) {
            $item->forceFill(['existing_product_id' => $existing->id])->save();
            throw new RuntimeException('SKU already exists. Use safe update actions instead of creating a duplicate.');
        }

        $brand = $this->brand($item->brand ?: ($item->found_specs_json['Brand'] ?? 'Unknown brand'));
        $category = $item->category ?: ($item->category_id ? Category::find($item->category_id) : null);

        if (! $category || $item->needs_category_review) {
            throw new RuntimeException('Category must be reviewed before creating a product draft.');
        }

        $images = $this->imagePaths($item);
        $mainImage = $images[0] ?? '/images/products/product-placeholder-toolbox.svg';
        $titleRu = $item->name_ru ?: $item->found_title ?: ('Draft '.$item->sku);
        $titleRo = $item->name_ro ?: $item->found_title ?: $titleRu;
        $descriptionRu = $item->description_ru ?: $item->found_description ?: null;
        $descriptionRo = $item->description_ro ?: $descriptionRu;
        $price = $item->parsed_price !== null ? (float) $item->parsed_price : 0;
        $stock = $item->parsed_stock !== null ? max(0, (int) $item->parsed_stock) : 0;

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => $titleRu,
            'name_ro' => $titleRo,
            'slug' => $this->uniqueProductSlug($titleRu, $item->sku),
            'sku' => $item->sku,
            'short_description' => $item->short_description_ru ?: Str::limit((string) $descriptionRu, 180),
            'description' => $descriptionRu,
            'description_ro' => $descriptionRo,
            'price' => $price,
            'old_price' => null,
            'currency' => config('store.currency', 'MDL'),
            'stock_quantity' => $stock,
            'stock_status' => $stock > 0 ? 'in_stock' : 'out_of_stock',
            'status' => 'draft',
            'approval_status' => 'pending_review',
            'needs_review' => true,
            'needs_stock_review' => (bool) $item->needs_stock_review,
            'needs_image_review' => (bool) $item->needs_image_review,
            'source_import_batch_id' => $item->batch_id,
            'source_parser_item_id' => $item->id,
            'parser_confidence' => $item->confidence_score,
            'parser_source_urls' => $item->source_urls_json ?: [],
            'main_image' => $mainImage,
            'gallery' => $images,
            'attributes' => $item->found_specs_json ?: [],
            'package_contents' => ['Draft parser preview'],
            'rating' => 5,
            'reviews_count' => 0,
            'is_active' => false,
            'is_featured' => false,
            'is_bestseller' => false,
            'is_new' => false,
            'is_discounted' => false,
            'warranty' => '24 luni',
            'meta_title' => $titleRu.' | '.config('store.domain_label'),
            'meta_description' => Str::limit((string) $descriptionRu, 150),
        ]);

        $this->syncImages($product, $images);

        $item->forceFill([
            'status' => 'draft_created',
            'created_product_id' => $product->id,
            'approval_status' => 'pending_review',
        ])->save();
        $item->batch?->addLog('Created draft product', ['sku' => $item->sku, 'product_id' => $product->id]);

        return $product;
    }

    public function updateExisting(ProductParserItem $item, string $action, bool $replaceConfirmed = false): Product
    {
        $product = $item->existingProduct ?: Product::where('sku', $item->sku)->first();

        if (! $product) {
            throw new RuntimeException('Existing product was not found.');
        }

        $images = $this->imagePaths($item);

        if ($action === 'replace_photos') {
            if (! $replaceConfirmed) {
                throw new RuntimeException('Replacing photos requires explicit confirmation.');
            }

            if ($images) {
                $product->forceFill([
                    'main_image' => $images[0],
                    'gallery' => $images,
                    'parser_confidence' => $item->confidence_score,
                    'parser_source_urls' => $item->source_urls_json ?: [],
                ])->save();
            }
        } elseif ($action === 'update_description') {
            $product->forceFill([
                'name' => $item->name_ru ?: $item->found_title ?: $product->name,
                'name_ro' => $item->name_ro ?: $item->found_title ?: $product->name_ro,
                'description' => $item->description_ru ?: $item->found_description ?: $product->description,
                'description_ro' => $item->description_ro ?: $item->found_description ?: $product->description_ro,
                'attributes' => $item->found_specs_json ?: $product->attributes,
                'parser_confidence' => $item->confidence_score,
                'parser_source_urls' => $item->source_urls_json ?: [],
            ])->save();
        } elseif ($action === 'update_price') {
            if ($item->parsed_price !== null) {
                $product->forceFill(['price' => $item->parsed_price])->save();
            }
        } elseif ($action === 'update_stock') {
            if ($item->parsed_stock !== null) {
                $product->forceFill([
                    'stock_quantity' => max(0, (int) $item->parsed_stock),
                    'stock_status' => (int) $item->parsed_stock > 0 ? 'in_stock' : 'out_of_stock',
                ])->save();
            }
        } elseif ($action === 'update_price_stock') {
            $updates = [];
            if ($item->parsed_price !== null) {
                $updates['price'] = $item->parsed_price;
            }
            if ($item->parsed_stock !== null) {
                $updates['stock_quantity'] = max(0, (int) $item->parsed_stock);
                $updates['stock_status'] = (int) $item->parsed_stock > 0 ? 'in_stock' : 'out_of_stock';
            }
            if ($updates) {
                $product->forceFill($updates)->save();
            }
        } else {
            $gallery = array_values(array_unique(array_filter(array_merge($product->gallery ?: [], $images))));
            $product->forceFill([
                'gallery' => $gallery,
                'parser_confidence' => $item->confidence_score,
                'parser_source_urls' => $item->source_urls_json ?: [],
            ])->save();
        }

        $this->syncImages($product, $product->gallery ?: [$product->main_image]);
        $item->forceFill(['status' => 'approved', 'approval_status' => 'approved', 'existing_product_id' => $product->id])->save();
        $item->batch?->addLog('Updated existing product safely', ['sku' => $item->sku, 'product_id' => $product->id, 'action' => $action]);

        return $product;
    }

    private function imagePaths(ProductParserItem $item): array
    {
        $processed = $item->processed_images_json ?: [];

        if ($processed) {
            return array_values(array_filter($processed));
        }

        return $item->imageAssets()
            ->where('is_selected', true)
            ->pluck('processed_path')
            ->filter()
            ->values()
            ->all();
    }

    private function brand(string $name): Brand
    {
        $name = trim($name) ?: 'Unknown brand';
        $slug = Str::slug($name) ?: 'unknown-brand';

        return Brand::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => 'Brand added from Product Parser preview.',
                'is_active' => true,
            ]
        );
    }

    private function uniqueProductSlug(string $title, string $sku): string
    {
        $base = Str::slug(Str::limit($title, 70, '').'-'.$sku) ?: Str::slug('draft-'.$sku);
        $slug = $base;
        $index = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$index++;
        }

        return $slug;
    }

    private function syncImages(Product $product, array $images): void
    {
        ProductImage::where('product_id', $product->id)->delete();

        foreach (array_values(array_filter($images)) as $index => $path) {
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'alt' => $product->display_name,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
