<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialLandingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_brands_page_uses_the_editorial_hero(): void
    {
        $this
            ->get('/brands')
            ->assertOk()
            ->assertSee('editorial-hero-brands', false)
            ->assertSee('/images/brands-hero.webp', false)
            ->assertSee('brands-assurance', false)
            ->assertSee('brands-section-head', false);

        $this->assertFileExists(public_path('images/brands-hero.webp'));
    }

    public function test_service_and_garage_catalog_pages_use_their_editorial_heroes(): void
    {
        Category::firstOrCreate(['slug' => 'echipamente-pentru-service'], [
            'name' => 'Оборудование для сервиса',
            'name_ro' => 'Echipamente pentru service',
            'is_active' => true,
        ]);

        Category::firstOrCreate(['slug' => 'instrument-manual'], [
            'name' => 'Ручной инструмент',
            'name_ro' => 'Instrument manual',
            'is_active' => true,
        ]);

        $this
            ->get('/catalog/echipamente-pentru-service')
            ->assertOk()
            ->assertSee('editorial-hero-service', false)
            ->assertSee('/images/service-hero.webp', false);

        $this
            ->get('/catalog/instrument-manual')
            ->assertOk()
            ->assertSee('editorial-hero-garage', false)
            ->assertSee('/images/garage-hero.webp', false);

        $this->assertFileExists(public_path('images/service-hero.webp'));
        $this->assertFileExists(public_path('images/garage-hero.webp'));
    }
}
