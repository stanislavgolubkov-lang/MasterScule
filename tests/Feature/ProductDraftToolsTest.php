<?php

namespace Tests\Feature;

use App\Jobs\RefreshDraftProductBySkuJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductDraftToolsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_admin_can_queue_repeat_sku_search_for_a_product_draft(): void
    {
        Queue::fake();
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $product = $this->draftProduct('DRAFT-RETRY-1');

        $this->actingAs($admin)
            ->post(route('admin.products.repeat-search', $product))
            ->assertRedirect()
            ->assertSessionHas('success');

        $item = ProductParserItem::where('sku', $product->sku)->latest('id')->firstOrFail();
        $batch = ProductParserBatch::findOrFail($item->batch_id);

        $this->assertSame('single', $batch->source_type);
        $this->assertSame('refresh_draft', $batch->options_json['mode']);
        $this->assertSame(['tristool', 'external'], $batch->options_json['search_priority']);
        $this->assertSame($product->id, $item->created_product_id);
        Queue::assertPushed(
            RefreshDraftProductBySkuJob::class,
            fn (RefreshDraftProductBySkuJob $job) => $job->productId === $product->id && $job->itemId === $item->id,
        );
    }

    public function test_admin_can_upload_multiple_photos_and_choose_the_first_as_main(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $product = $this->draftProduct('DRAFT-PHOTOS-1');
        $folder = public_path('images/products/admin/'.Str::slug($product->sku));

        try {
            $this->actingAs($admin)
                ->post(route('admin.products.images.upload', $product), [
                    'photos' => [
                        UploadedFile::fake()->image('front.jpg', 800, 600),
                        UploadedFile::fake()->image('side.png', 700, 700),
                    ],
                    'set_first_as_main' => '1',
                ])
                ->assertRedirect()
                ->assertSessionHas('success');

            $product->refresh();

            $this->assertFalse($product->needs_image_review);
            $this->assertStringStartsWith('/images/products/admin/draft-photos-1/', $product->main_image);
            $this->assertCount(2, $product->gallery);
            $this->assertFileExists(public_path(ltrim($product->main_image, '/')));
            $this->assertDatabaseCount('product_images', 2);
            $this->assertDatabaseHas('product_images', [
                'product_id' => $product->id,
                'path' => $product->main_image,
                'width' => 800,
                'height' => 600,
                'sort_order' => 1,
            ]);
        } finally {
            File::deleteDirectory($folder);
        }
    }

    public function test_draft_product_page_shows_search_and_upload_controls(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $product = $this->draftProduct('DRAFT-TOOLS-UI');

        $response = $this->actingAs($admin)
            ->get(route('admin.products', ['q' => $product->sku]));

        $response
            ->assertOk()
            ->assertSee('Повторный поиск по SKU')
            ->assertSee('Загрузить свои фото');

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML((string) $response->getContent());
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $this->assertSame(1, $xpath->query("//form[contains(concat(' ', normalize-space(@class), ' '), ' admin-product-edit ')]/div[contains(concat(' ', normalize-space(@class), ' '), ' admin-product-fields ')]/div[contains(concat(' ', normalize-space(@class), ' '), ' admin-publication-warning ')]")->length);
        $this->assertSame(0, $xpath->query("//form[contains(concat(' ', normalize-space(@class), ' '), ' admin-product-edit ')]/div[contains(concat(' ', normalize-space(@class), ' '), ' admin-publication-warning ')]")->length);
    }

    public function test_admin_can_confirm_a_real_product_photo_from_the_publication_warning(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $product = $this->draftProduct('DRAFT-CONFIRM-PHOTO');
        $folder = public_path('images/products/admin/draft-confirm-photo');
        $imagePath = '/images/products/admin/draft-confirm-photo/approved.jpg';

        File::ensureDirectoryExists($folder);
        UploadedFile::fake()->image('approved.jpg', 800, 600)->move($folder, 'approved.jpg');

        try {
            $product->forceFill([
                'main_image' => $imagePath,
                'gallery' => [$imagePath],
                'needs_image_review' => true,
                'needs_stock_review' => true,
            ])->save();

            $this->actingAs($admin)
                ->get(route('admin.products', ['q' => $product->sku]))
                ->assertOk()
                ->assertSee('Подтвердить фото')
                ->assertSee('Подтвердить остаток');

            $this->actingAs($admin)
                ->patch(route('admin.products.update', $product), [
                    'brand_id' => $product->brand_id,
                    'category_id' => $product->category_id,
                    'name' => $product->name,
                    'name_ro' => $product->name_ro,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'stock_quantity' => $product->stock_quantity,
                    'main_image' => $imagePath,
                    'needs_image_review' => '1',
                    'needs_stock_review' => '1',
                    'confirm_review' => 'image',
                ])
                ->assertRedirect()
                ->assertSessionHas('success', 'Фото подтверждено.');

            $product->refresh();
            $this->assertFalse($product->needs_image_review);
            $this->assertTrue($product->needs_stock_review);
        } finally {
            File::deleteDirectory($folder);
        }
    }

    private function draftProduct(string $sku): Product
    {
        $brand = Brand::firstOrFail();
        $category = Category::where('is_assignable', true)->first() ?: Category::firstOrFail();

        return Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Черновик '.$sku,
            'name_ru' => 'Черновик '.$sku,
            'name_ro' => 'Produs '.$sku,
            'slug' => Str::slug('draft-'.$sku),
            'sku' => $sku,
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 1,
            'stock_status' => 'in_stock',
            'status' => 'draft',
            'approval_status' => 'pending_review',
            'is_active' => false,
            'needs_review' => true,
            'needs_image_review' => true,
            'main_image' => '/images/products/product-placeholder-toolbox.svg',
            'gallery' => ['/images/products/product-placeholder-toolbox.svg'],
        ]);
    }
}
