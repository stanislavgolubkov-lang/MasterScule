<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\AutomaticCategoryAgent;
use App\Services\Catalog\CategoryCandidateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomaticCategoryAgentTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_noise_level_does_not_classify_a_pneumatic_grinder_as_a_level(): void
    {
        $pneumatic = $this->category('scule-pneumatice');
        $grinders = $this->category('polizoare-si-slefuitoare-pneumatice', $pneumatic);
        $measuring = $this->category('instrumente-de-masurare');
        $levels = $this->category('rulete-nivele', $measuring);
        $product = $this->product($levels, 'Пневматическая шлифовальная машина M7', 'QB-123');
        $product->update(['attributes' => ['Уровень шума' => '88 dB', 'Диаметр диска' => '125 mm']]);

        $result = app(CategoryCandidateService::class)->rank($product->fresh());

        $this->assertSame($grinders->slug, $result['selected_slug']);
        $this->assertGreaterThanOrEqual(0.97, $result['confidence']);
    }

    public function test_high_confidence_decision_updates_primary_category_and_records_audit(): void
    {
        $pneumatic = $this->category('scule-pneumatice');
        $grinders = $this->category('polizoare-si-slefuitoare-pneumatice', $pneumatic);
        $wrong = $this->category('rulete-nivele', $this->category('instrumente-de-masurare'));
        $product = $this->product($wrong, 'Пневматическая шлифовальная машина M7', 'QB-124');
        $agent = app(AutomaticCategoryAgent::class);

        $decision = $agent->decide($product, false);
        $this->assertTrue($decision['can_apply']);
        $this->assertTrue($decision['changed']);

        $audit = $agent->apply($product, $decision);

        $this->assertSame($grinders->id, $product->fresh()->category_id);
        $this->assertSame('applied', $audit->status);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $grinders->id,
            'is_primary' => true,
            'source' => 'catalog_agent',
        ]);
    }

    public function test_syncing_primary_category_keeps_product_and_pivot_in_lockstep(): void
    {
        $original = $this->category('instrument-manual');
        $target = $this->category('instrumente-electromontaj');
        $product = $this->product($original, 'Тестовый инструмент', 'SYNC-PRIMARY-1');

        $product->syncCategoryLinks([$target->id], $target->id, 'integrity_test');

        $this->assertSame($target->id, $product->fresh()->category_id);
        $this->assertDatabaseHas('category_product', [
            'product_id' => $product->id,
            'category_id' => $target->id,
            'is_primary' => true,
            'source' => 'integrity_test',
        ]);
        $this->assertDatabaseMissing('category_product', [
            'product_id' => $product->id,
            'category_id' => $original->id,
        ]);
    }

    public function test_morphological_automotive_signal_moves_an_oil_filter_puller_to_its_leaf(): void
    {
        $auto = $this->category('scule-speciale-auto');
        $oilFilters = $this->category('scule-pentru-filtre-ulei', $auto);
        $product = $this->product($auto, 'Съемник масляных фильтров чашка 76 мм', 'FILTER-PULLER-1');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertSame($oilFilters->slug, $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
        $this->assertGreaterThanOrEqual(0.985, $decision['classifier_confidence']);
    }

    public function test_insulated_screwdriver_uses_the_vde_leaf_instead_of_the_electromontage_parent(): void
    {
        $electrical = $this->category('instrumente-electromontaj');
        $vde = $this->category('instrumente-izolate-vde', $electrical);
        $product = $this->product($electrical, 'Отвертка диэлектрическая VDE 1000 V', 'VDE-SCREWDRIVER-1');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertSame($vde->slug, $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
    }

    public function test_impact_socket_leaves_the_broad_automotive_parent(): void
    {
        $auto = $this->category('scule-speciale-auto');
        $impactSockets = $this->category('capete-tubulare-impact', $this->category('instrument-manual'));
        $product = $this->product($auto, 'Головка торцевая ударная глубокая 22 мм 1/2', 'AUTO-SOCKET-1');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertSame($impactSockets->slug, $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
    }

    public function test_clutch_tool_uses_the_transmission_leaf(): void
    {
        $auto = $this->category('scule-speciale-auto');
        $transmission = $this->category('scule-transmisie-ambreiaj', $auto);
        $product = $this->product($auto, 'Набор для замены сцепления КПП', 'CLUTCH-TOOL-1');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertSame($transmission->slug, $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
    }

    public function test_generic_clamp_leaves_the_electromontage_parent(): void
    {
        $electrical = $this->category('instrumente-electromontaj');
        $clamps = $this->category('menghine-si-cleme', $this->category('instrument-manual'));
        $product = $this->product($electrical, 'Зажим с фиксатором С-образный 280 мм', 'CLAMP-1');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertSame($clamps->slug, $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
    }

    public function test_vde_set_for_a_trolley_is_not_mistaken_for_the_trolley_itself(): void
    {
        $electrical = $this->category('instrumente-electromontaj');
        $vde = $this->category('instrumente-izolate-vde', $electrical);
        $product = $this->product($electrical, 'Набор изолированных инструментов VDE 1000 В в ложементах под тележку', 'VDE-FOAM-1');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertSame($vde->slug, $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
    }

    public function test_low_confidence_product_is_not_authorized_for_automatic_move(): void
    {
        $current = $this->category('instrument-manual');
        $this->category('scule-pneumatice');
        $product = $this->product($current, 'Набор профессиональный', 'UNKNOWN-1');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertFalse($decision['can_apply']);
        $this->assertLessThan(0.97, $decision['classifier_confidence']);
    }

    public function test_dielectric_pliers_cannot_leave_the_electromontaj_branch(): void
    {
        $electrical = $this->category('instrumente-electromontaj');
        $manual = $this->category('instrument-manual');
        $this->category('clesti-si-instrumente-taiere', $manual);
        $this->category('clesti-electrician-si-cabluri', $electrical);
        $product = $this->product($electrical, 'Плоскогубцы комбинированные диэлектрические 185мм', 'VDE-1');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertSame('clesti-electrician-si-cabluri', $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
        $this->assertSame([], $decision['validation_errors']);
    }

    public function test_polluted_group_attribute_does_not_turn_ordinary_pliers_into_vde_tools(): void
    {
        $manual = $this->category('instrument-manual');
        $manualPliers = $this->category('clesti-si-instrumente-taiere', $manual);
        $electrical = $this->category('instrumente-electromontaj');
        $this->category('clesti-electrician-si-cabluri', $electrical);
        $product = $this->product($electrical, 'Плоскогубцы комбинированные 185 мм', 'PLIER-1');
        $product->update(['attributes' => ['Group' => 'VDE', 'Brand' => 'Test']]);

        $decision = app(AutomaticCategoryAgent::class)->decide($product->fresh(), false);

        $this->assertSame($manualPliers->slug, $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
        $this->assertSame([], $decision['validation_errors']);
    }

    public function test_verified_sku_override_wins_over_generic_product_wording(): void
    {
        $auto = $this->category('scule-speciale-auto');
        $suspension = $this->category('scule-pentru-suspensie', $auto);
        $wrong = $this->category('echipamente-schimb-ulei');
        $product = $this->product($wrong, 'Комплект приспособлений', 'JTC-1412');

        $decision = app(AutomaticCategoryAgent::class)->decide($product, false);

        $this->assertSame($suspension->slug, $decision['selected_slug']);
        $this->assertTrue($decision['can_apply']);
        $this->assertContains('verified SKU override: JTC-1412', $decision['evidence']);
    }

    private function category(string $slug, ?Category $parent = null): Category
    {
        return Category::firstOrCreate(['slug' => $slug], [
            'parent_id' => $parent?->id,
            'name' => $slug,
            'name_ro' => $slug,
            'is_active' => true,
            'is_assignable' => true,
        ]);
    }

    private function product(Category $category, string $name, string $sku): Product
    {
        $brand = Brand::firstOrCreate(['slug' => 'm7-test'], [
            'name' => 'M7 Test',
            'is_active' => true,
        ]);

        return Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => $name,
            'name_ru' => $name,
            'name_ro' => $name,
            'slug' => strtolower($sku),
            'sku' => $sku,
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 1,
            'stock_status' => 'in_stock',
            'status' => 'published',
            'approval_status' => 'approved',
            'is_active' => true,
        ]);
    }
}
