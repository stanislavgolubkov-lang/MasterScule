<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParserDraftsPageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_parser_drafts_page_excludes_published_products(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $brand = Brand::firstOrFail();
        $category = Category::firstOrFail();

        $batch = ProductParserBatch::create([
            'user_id' => $admin->id,
            'title' => 'Draft page filtering test',
            'source_type' => 'price_list',
            'status' => 'completed',
        ]);

        $draft = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Visible parser draft',
            'name_ro' => 'Visible parser draft',
            'slug' => 'visible-parser-draft',
            'sku' => 'VISIBLE-PARSER-DRAFT',
            'price' => 100,
            'status' => 'draft',
            'approval_status' => 'pending_review',
            'is_active' => false,
        ]);

        $published = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Published parser product',
            'name_ro' => 'Published parser product',
            'slug' => 'published-parser-product',
            'sku' => 'PUBLISHED-PARSER-PRODUCT',
            'price' => 100,
            'status' => 'published',
            'approval_status' => 'approved',
            'is_active' => true,
        ]);

        ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => $draft->sku,
            'status' => 'draft_created',
            'created_product_id' => $draft->id,
        ]);

        ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => $published->sku,
            'status' => 'approved',
            'created_product_id' => $published->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.parser.drafts'))
            ->assertOk()
            ->assertSee('Всего черновиков: 1')
            ->assertSee('VISIBLE-PARSER-DRAFT')
            ->assertSee('Товар')
            ->assertSee('Парсер')
            ->assertDontSee('PUBLISHED-PARSER-PRODUCT');
    }
}
