<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_homepage_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)->assertSee('MasterScule.ro');
    }

    public function test_catalog_and_product_pages_are_available(): void
    {
        $this->get('/catalog')->assertStatus(200)->assertSee('Catalog produse');
        $this->get('/product/king-tony-7596mr')->assertStatus(200)->assertSee('Set de scule King Tony 7596MR');
    }

    public function test_product_can_be_added_to_cart(): void
    {
        $this->post('/cart/add/1', ['quantity' => 2])->assertRedirect();
        $this->withSession(['cart' => [1 => 2]])->get('/cart')->assertStatus(200)->assertSee('Ai 2 produse');
    }

    public function test_seeded_admin_can_open_users_page(): void
    {
        $admin = User::where('email', 'admin@masterscule.ro')->firstOrFail();

        $this->actingAs($admin)->get('/admin/users')->assertStatus(200)->assertSee('admin@masterscule.ro');
    }

    public function test_checkout_creates_order_and_decrements_stock(): void
    {
        $user = User::where('email', 'andrei.popescu@example.com')->firstOrFail();
        $product = Product::firstOrFail();
        $stock = $product->stock_quantity;

        $this
            ->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', [
                'customer_name' => $user->name,
                'customer_phone' => '0724 123 456',
                'shipping_city' => 'Voluntari',
                'shipping_address' => 'Str. Fabricii nr. 12',
                'shipping_postcode' => '077190',
                'payment_method' => 'cash_on_delivery',
                'shipping_method' => 'courier',
            ])
            ->assertRedirect('/account');

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame($stock - 1, $product->fresh()->stock_quantity);
    }

    public function test_ai_json_response_is_romanian(): void
    {
        $response = $this->postJson('/ai/tool-advisor', [
            'prompt' => 'Am nevoie de un set King Tony pana la 2500 RON',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['response', 'products']);

        $this->assertStringContainsString('Produse potrivite', $response->json('response'));
        $this->assertDoesNotMatchRegularExpression('/[А-Яа-яЁё]/u', $response->json('response'));
    }

    public function test_regular_user_cannot_open_admin_products(): void
    {
        $user = User::where('email', 'andrei.popescu@example.com')->firstOrFail();

        $this->actingAs($user)->get('/admin/products')->assertForbidden();
    }
}
