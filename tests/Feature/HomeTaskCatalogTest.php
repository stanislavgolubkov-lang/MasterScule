<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTaskCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_cards_open_filled_collections_even_when_landing_category_is_empty(): void
    {
        $brand = Brand::create([
            'name' => 'Task brand',
            'slug' => 'task-brand',
            'is_active' => true,
        ]);

        $tiresLanding = Category::firstOrCreate(['slug' => 'vulcanizare'], [
            'name' => 'Vulcanizare',
            'name_ro' => 'Vulcanizare',
            'is_active' => true,
        ]);
        $tiresProducts = Category::firstOrCreate(['slug' => 'scule-pentru-roti-vulcanizare'], [
            'name' => 'Инструмент для колес',
            'name_ro' => 'Scule pentru roti',
            'is_active' => true,
        ]);
        $workshopLanding = Category::firstOrCreate(['slug' => 'dulapuri-si-organizare'], [
            'name' => 'Организация мастерской',
            'name_ro' => 'Organizare atelier',
            'is_active' => true,
        ]);
        $manualTools = Category::firstOrCreate(['slug' => 'instrument-manual'], [
            'name' => 'Ручной инструмент',
            'name_ro' => 'Instrument manual',
            'is_active' => true,
        ]);

        $tireProduct = $this->createProduct($brand, $tiresProducts, 'Wheel service product', 'TASK-TIRE-1');
        $workshopProduct = $this->createProduct($brand, $manualTools, 'Тележка с инструментом для мастерской', 'TASK-WORKSHOP-1');

        $this
            ->get(route('catalog', ['category' => $tiresLanding->slug]))
            ->assertOk()
            ->assertSee($tireProduct->display_name)
            ->assertSee('name="task" value="tires"', false);

        $this
            ->get(route('catalog', ['category' => $tiresLanding->slug, 'task' => 'tires']))
            ->assertOk()
            ->assertSee($tireProduct->display_name)
            ->assertSee('name="task" value="tires"', false);

        $this
            ->get(route('catalog', ['category' => $workshopLanding->slug, 'task' => 'workshop']))
            ->assertOk()
            ->assertSee($workshopProduct->display_name)
            ->assertSee('name="task" value="workshop"', false);
    }

    public function test_home_task_links_include_the_collection_key(): void
    {
        $this
            ->get('/')
            ->assertOk()
            ->assertSee(route('catalog', ['category' => 'echipamente-pentru-service', 'task' => 'service']))
            ->assertSee(route('catalog', ['category' => 'vulcanizare', 'task' => 'tires']))
            ->assertSee(route('catalog', ['category' => 'scule-motor-frane-suspensie', 'task' => 'brakes']))
            ->assertSee(route('catalog', ['category' => 'scule-motor-frane-suspensie', 'task' => 'engine']))
            ->assertSee(route('catalog', ['category' => 'dulapuri-si-organizare', 'task' => 'workshop']));
    }

    public function test_empty_visible_subcategory_uses_products_from_its_nearest_filled_parent(): void
    {
        $brand = Brand::create([
            'name' => 'Fallback brand',
            'slug' => 'fallback-brand',
            'is_active' => true,
        ]);
        $parent = Category::create([
            'name' => 'Filled parent',
            'name_ro' => 'Părinte cu produse',
            'slug' => 'filled-parent',
            'is_active' => true,
        ]);
        $emptyChild = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Empty child',
            'name_ro' => 'Subcategorie goală',
            'slug' => 'empty-child',
            'is_active' => true,
        ]);
        $product = $this->createProduct($brand, $parent, 'Related parent product', 'FALLBACK-1');

        $this
            ->get(route('catalog', $emptyChild->slug))
            ->assertOk()
            ->assertSee($product->display_name);
    }

    private function createProduct(Brand $brand, Category $category, string $name, string $sku): Product
    {
        return Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => $name,
            'name_ru' => $name,
            'name_ro' => $name,
            'slug' => strtolower($sku),
            'sku' => $sku,
            'price' => 500,
            'currency' => 'MDL',
            'stock_quantity' => 5,
            'stock_status' => 'in_stock',
            'status' => 'published',
            'approval_status' => 'approved',
            'needs_review' => false,
            'needs_stock_review' => false,
            'needs_image_review' => false,
            'needs_category_review' => false,
            'needs_translation_review' => false,
            'needs_price_review' => false,
            'is_active' => true,
            'main_image' => '/images/parser-catalog/m7/sc-9337r.png',
        ]);
    }
}
