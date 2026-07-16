<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_contact_page_has_rich_contact_actions_and_generated_hero_image(): void
    {
        $this
            ->get('/contacts')
            ->assertOk()
            ->assertSee('/images/contact-workshop-consultation.webp', false)
            ->assertSee('tel:'.config('store.phone_href'), false)
            ->assertSee('mailto:'.config('store.email'), false)
            ->assertSee('contact-cards', false)
            ->assertSee('google.com/maps/search', false);

        $this->assertFileExists(public_path('images/contact-workshop-consultation.webp'));
    }
}
