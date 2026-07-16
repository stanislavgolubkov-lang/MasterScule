<?php

namespace Tests\Feature;

use App\Jobs\ParsePriceListJob;
use App\Jobs\ParseSkuBatchJob;
use App\Jobs\ProcessExternalPriceListRowJob;
use App\Jobs\ProcessPriceListRowJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductParserBatch;
use App\Models\ProductParserCategoryLearning;
use App\Models\ProductParserImageAsset;
use App\Models\ProductParserItem;
use App\Models\User;
use App\Services\ProductImageProcessorService;
use App\Services\ProductPriceListImportService;
use App\Services\ProductPriceListReader;
use App\Services\TrisToolsEnrichmentService;
use Database\Seeders\CatalogStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        UploadedFile::fake()->image('seed-product.png', 80, 80)->storeAs('products', 'seed-product.png', 'public');

        Product::query()->each(function (Product $product) {
            $product->forceFill([
                'name_ru' => $product->sku === '7596MR' ? 'Набор инструментов King Tony 7596MR' : ($product->name_ru ?: $product->name),
                'short_description_ru' => $product->short_description_ru ?: $product->short_description,
                'short_description_ro' => $product->short_description_ro ?: $product->short_description,
                'description_ru' => $product->description_ru ?: $product->description,
                'main_image' => '/storage/products/seed-product.png',
                'gallery' => ['/storage/products/seed-product.png'],
                'status' => 'published',
                'approval_status' => 'approved',
                'needs_review' => false,
                'needs_image_review' => false,
                'needs_category_review' => false,
                'needs_translation_review' => false,
                'needs_price_review' => false,
                'needs_stock_review' => false,
                'is_active' => true,
            ])->save();
        });
    }

    public function test_homepage_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)->assertSee('MasterScule.md');
    }

    public function test_catalog_and_product_pages_are_available(): void
    {
        $this->get('/catalog')->assertStatus(200)->assertSee('Каталог товаров');
        $this->get('/product/king-tony-7596mr')->assertStatus(200)->assertSee('Набор инструментов King Tony 7596MR');
    }

    public function test_out_of_stock_products_are_visible_but_not_purchasable(): void
    {
        $product = Product::create([
            'brand_id' => Brand::firstOrFail()->id,
            'category_id' => Category::firstOrFail()->id,
            'name' => 'Visible out of stock product',
            'name_ru' => 'Visible out of stock product',
            'name_ro' => 'Visible out of stock product',
            'slug' => 'visible-out-of-stock-product',
            'sku' => 'OOS-1',
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock',
            'status' => 'published',
            'approval_status' => 'approved',
            'needs_review' => false,
            'needs_image_review' => false,
            'needs_category_review' => false,
            'needs_translation_review' => false,
            'needs_price_review' => false,
            'is_active' => true,
            'main_image' => '/storage/products/seed-product.png',
            'gallery' => ['/storage/products/seed-product.png'],
        ]);

        $this->get('/catalog?q=OOS-1')->assertOk()->assertSee('Visible out of stock product');
        $this->get('/product/'.$product->slug)->assertOk()->assertSee(__('ui.out_of_stock'));

        $this
            ->from('/product/'.$product->slug)
            ->post('/cart/add/'.$product->id, ['quantity' => 1])
            ->assertRedirect('/product/'.$product->slug)
            ->assertSessionHasErrors('cart');
    }

    public function test_product_detail_tabs_are_rendered_as_interactive_controls(): void
    {
        $this
            ->get('/product/king-tony-7596mr')
            ->assertOk()
            ->assertSee('data-product-tab="description"', false)
            ->assertSee('data-product-tab="specifications"', false)
            ->assertSee('data-product-tab="contents"', false)
            ->assertSee('role="tabpanel"', false);
    }

    public function test_products_with_missing_image_files_still_render_cards_and_detail_page(): void
    {
        $product = Product::create([
            'brand_id' => Brand::firstOrFail()->id,
            'category_id' => Category::firstOrFail()->id,
            'name' => 'Missing image visible product',
            'name_ru' => 'Missing image visible product',
            'name_ro' => 'Missing image visible product',
            'slug' => 'missing-image-visible-product',
            'sku' => 'MISS-IMG-1',
            'description_ru' => 'Visible product description.',
            'description_ro' => 'Descriere produs vizibil.',
            'price' => 100,
            'currency' => 'MDL',
            'stock_quantity' => 2,
            'stock_status' => 'in_stock',
            'status' => 'published',
            'approval_status' => 'approved',
            'needs_review' => false,
            'needs_image_review' => false,
            'needs_category_review' => false,
            'needs_translation_review' => false,
            'needs_price_review' => false,
            'is_active' => true,
            'main_image' => '/storage/products/not-uploaded-yet.png',
            'gallery' => ['/storage/products/not-uploaded-yet.png'],
        ]);

        $this
            ->get('/catalog?q=MISS-IMG-1')
            ->assertOk()
            ->assertSee('Missing image visible product')
            ->assertSee(__('ui.product_photo_pending_short'));

        $this
            ->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('Visible product description.');
    }

    public function test_product_can_be_added_to_cart(): void
    {
        $this->post('/cart/add/1', ['quantity' => 2])->assertRedirect();
        $this->withSession(['cart' => [1 => 2]])->get('/cart')->assertStatus(200)->assertSee('В корзине товаров: 2');
    }

    public function test_seeded_admin_can_open_users_page(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();

        $this->actingAs($admin)->get('/admin/users')->assertStatus(200)->assertSee('admin@masterscule.md');
    }

    public function test_seeded_admin_can_open_payments_page(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();

        $this
            ->actingAs($admin)
            ->get('/admin/payments')
            ->assertOk()
            ->assertSee(__('ui.admin_payments'));
    }

    public function test_admin_entry_shows_admin_login_for_guests(): void
    {
        $this
            ->get('/admin')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('/admin', false);
    }

    public function test_guest_admin_subroute_redirects_to_admin_entry(): void
    {
        $this->get('/admin/products')->assertRedirect(route('admin.dashboard'));
    }

    public function test_seeded_admin_can_login_only_at_admin_entry(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();

        $this
            ->post('/admin', [
                'email' => 'admin@masterscule.md',
                'password' => 'MasterScule2026!',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_login_through_customer_login(): void
    {
        $this
            ->post('/login', [
                'email' => 'admin@masterscule.md',
                'password' => 'MasterScule2026!',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertGuest();
    }

    public function test_admin_login_is_rate_limited_after_repeated_failures(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this
                ->post('/admin', [
                    'email' => 'admin@masterscule.md',
                    'password' => 'wrong-password',
                ])
                ->assertSessionHasErrors('email');
        }

        $this
            ->post('/admin', [
                'email' => 'admin@masterscule.md',
                'password' => 'wrong-password',
            ])
            ->assertTooManyRequests();
    }

    public function test_admin_can_manage_product_cards(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $brand = Brand::firstOrFail();
        $category = Category::firstOrFail();

        $this
            ->actingAs($admin)
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Управление товарами')
            ->assertSee('Загрузить главное изображение')
            ->assertDontSee('Удалить товар');

        $this
            ->actingAs($admin)
            ->post('/admin/products', [
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'name' => 'Produs test admin',
                'name_ro' => 'Produs test admin',
                'sku' => 'ADMIN-TEST-1',
                'price' => 123.45,
                'old_price' => 150,
                'stock_quantity' => 5,
                'main_image' => '/images/products/product-placeholder-toolbox.svg',
                'short_description' => 'Card produs editabil din admin.',
                'description_ro' => 'Descriere completa editata din panoul admin.',
                'attributes_text' => "Material: Otel\nUtilizare: Service",
                'package_contents_text' => "Produs test\nManual",
                'gallery_text' => '/images/products/product-placeholder-toolbox.svg',
                'is_active' => '1',
                'is_featured' => '1',
            ])
            ->assertRedirect();

        $product = Product::where('sku', 'ADMIN-TEST-1')->firstOrFail();
        $this->assertSame('Otel', $product->attributes['Material']);
        $this->assertDatabaseHas('product_images', ['product_id' => $product->id]);

        $this
            ->actingAs($admin)
            ->patch('/admin/products/'.$product->id, [
                'brand_id' => $brand->id,
                'category_id' => $category->id,
                'name' => 'Produs test admin actualizat',
                'name_ro' => 'Produs test admin actualizat',
                'sku' => 'ADMIN-TEST-1',
                'price' => 99,
                'stock_quantity' => 0,
                'main_image' => $product->main_image,
                'description_ro' => 'Descriere actualizata.',
                'attributes_text' => 'Status: Actualizat',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'sku' => 'ADMIN-TEST-1',
            'price' => 99,
            'stock_status' => 'out_of_stock',
        ]);

        $this
            ->actingAs($admin)
            ->delete('/admin/products/'.$product->id)
            ->assertMethodNotAllowed();

        $this->assertDatabaseHas('products', ['sku' => 'ADMIN-TEST-1']);
    }

    public function test_checkout_creates_order_and_decrements_stock(): void
    {
        $user = User::where('email', 'andrei.popescu@example.com')->firstOrFail();
        $product = Product::firstOrFail();
        $stock = $product->stock_quantity;

        $response = $this
            ->actingAs($user)
            ->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', [
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => '+373 60 000 000',
                'shipping_city' => 'Chisinau',
                'shipping_address' => 'Str. Test nr. 12',
                'shipping_postcode' => '2001',
                'payment_method' => 'cash_on_delivery',
                'shipping_method' => 'courier',
                'terms_accepted' => '1',
            ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('/order/', $location);
        $this->assertStringContainsString('signature=', $location);

        $this->assertDatabaseCount('orders', 1);
        $order = Order::firstOrFail();

        $this->get('/order/'.$order->order_number)->assertForbidden();
        $this->get($location)->assertOk()->assertSee($order->order_number);

        $this->assertSame($stock - 1, $product->fresh()->stock_quantity);
    }

    public function test_guest_can_create_checkout_order(): void
    {
        $product = Product::firstOrFail();

        $response = $this
            ->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', [
                'customer_name' => 'Guest Buyer',
                'customer_email' => 'guest@example.com',
                'customer_phone' => '+373 60 111 222',
                'shipping_city' => 'Chisinau',
                'shipping_address' => 'Bd. Stefan cel Mare 1',
                'shipping_postcode' => '2001',
                'payment_method' => 'cash_on_delivery',
                'shipping_method' => 'courier',
                'terms_accepted' => '1',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'guest@example.com',
            'user_id' => null,
            'currency' => 'MDL',
        ]);
    }

    public function test_online_card_waits_for_paid_callback_before_decrementing_stock(): void
    {
        Http::fake([
            'https://maib.test/*' => Http::response([
                'checkoutId' => 'checkout-test-123',
                'checkoutUrl' => 'https://pay.example/checkout',
            ]),
        ]);

        config([
            'services.maib.base_url' => 'https://maib.test',
            'services.maib.create_payment_path' => '/v2/checkouts',
            'services.maib.project_id' => 'project-test',
            'services.maib.secret' => 'secret-test',
            'services.maib.signature_secret' => 'callback-secret-test',
        ]);

        $product = Product::where('stock_quantity', '>', 1)->firstOrFail();
        $stock = $product->stock_quantity;

        $this
            ->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', [
                'customer_name' => 'Online Buyer',
                'customer_email' => 'online@example.com',
                'customer_phone' => '+373 60 111 333',
                'shipping_city' => 'Chisinau',
                'shipping_address' => 'Bd. Test 2',
                'shipping_postcode' => '2001',
                'payment_method' => 'online_card',
                'shipping_method' => 'courier',
                'terms_accepted' => '1',
            ])
            ->assertRedirect('https://pay.example/checkout');

        Http::assertSent(fn ($request) => $request->url() === 'https://maib.test/v2/checkouts');

        $order = Order::where('payment_reference', 'checkout-test-123')->firstOrFail();

        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->stock_deducted_at);
        $this->assertSame($stock, $product->fresh()->stock_quantity);
        $this->assertSame(1, PaymentTransaction::where('order_id', $order->id)->count());
        $this->assertSame('waiting_for_payment', PaymentTransaction::where('order_id', $order->id)->firstOrFail()->status);

        $waitingPayload = [
            'checkoutId' => 'checkout-test-123',
            'status' => 'WaitingForPayment',
        ];

        $this
            ->postJson('/payment/maib/callback', $waitingPayload)
            ->assertForbidden();

        $this
            ->withHeaders(['X-Maib-Signature' => $this->maibSignature($waitingPayload)])
            ->postJson('/payment/maib/callback', $waitingPayload)
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'waiting_for_payment', 'stock_captured' => false]);

        $this->assertSame($stock, $product->fresh()->stock_quantity);

        $wrongAmountPayload = [
            'checkoutId' => 'checkout-test-123',
            'status' => 'Completed',
            'amount' => 100,
            'currency' => 'MDL',
        ];

        $this
            ->withHeaders(['X-Maib-Signature' => $this->maibSignature($wrongAmountPayload)])
            ->postJson('/payment/maib/callback', $wrongAmountPayload)
            ->assertUnprocessable()
            ->assertJson(['ok' => false, 'status' => 'payment_mismatch', 'stock_captured' => false]);

        $this->assertSame($stock, $product->fresh()->stock_quantity);

        $paidPayload = [
            'checkoutId' => 'checkout-test-123',
            'status' => 'Completed',
            'amount' => (int) round((float) $order->total * 100),
            'currency' => $order->currency,
        ];

        $this
            ->withHeaders(['X-Maib-Signature' => $this->maibSignature($paidPayload)])
            ->postJson('/payment/maib/callback', $paidPayload)
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'completed', 'stock_captured' => true]);

        $this->assertSame($stock - 1, $product->fresh()->stock_quantity);
        $this->assertNotNull($order->fresh()->stock_deducted_at);

        $this
            ->withHeaders(['X-Maib-Signature' => $this->maibSignature($paidPayload)])
            ->postJson('/payment/maib/callback', $paidPayload)
            ->assertOk();

        $this->assertSame($stock - 1, $product->fresh()->stock_quantity);
        $this->assertSame(1, PaymentTransaction::where('order_id', $order->id)->count());
    }

    public function test_online_card_failure_keeps_cart_and_marks_order_payment_failed(): void
    {
        Http::fake([
            'https://maib.test/*' => Http::response(['message' => 'Rejected'], 500),
        ]);

        config([
            'services.maib.base_url' => 'https://maib.test',
            'services.maib.create_payment_path' => '/v2/checkouts',
            'services.maib.project_id' => 'project-test',
            'services.maib.secret' => 'secret-test',
            'services.maib.signature_secret' => 'callback-secret-test',
        ]);

        $product = Product::where('stock_quantity', '>', 1)->firstOrFail();
        $stock = $product->stock_quantity;

        $this
            ->withSession(['cart' => [$product->id => 1]])
            ->post('/checkout', [
                'customer_name' => 'Online Buyer',
                'customer_email' => 'online@example.com',
                'customer_phone' => '+373 60 111 333',
                'shipping_city' => 'Chisinau',
                'shipping_address' => 'Bd. Test 2',
                'shipping_postcode' => '2001',
                'payment_method' => 'online_card',
                'shipping_method' => 'courier',
                'terms_accepted' => '1',
            ])
            ->assertRedirect(route('checkout.show'))
            ->assertSessionHasErrors('payment')
            ->assertSessionHas('cart', [$product->id => 1]);

        $order = Order::firstOrFail();

        $this->assertSame('payment_failed', $order->status);
        $this->assertSame('failed', $order->payment_status);
        $this->assertNull($order->stock_deducted_at);
        $this->assertSame($stock, $product->fresh()->stock_quantity);
    }

    private function maibSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), 'callback-secret-test');
    }

    public function test_ai_assistant_is_hidden_when_feature_disabled(): void
    {
        config(['features.ai_assistant' => false]);

        $this->get('/')->assertOk()->assertDontSee('AI-консультант');
        $this->get('/ai/tool-advisor')->assertNotFound();
        $this->postJson('/ai/tool-advisor', ['prompt' => 'test'])->assertNotFound();
    }

    public function test_frontend_ai_entry_points_are_not_rendered(): void
    {
        config(['features.ai_assistant' => true]);

        $this
            ->get('/')
            ->assertOk()
            ->assertDontSee('data-ai-open', false)
            ->assertDontSee('floating-ai')
            ->assertDontSee('AI-консультант');

        $this
            ->get('/product/king-tony-7596mr')
            ->assertOk()
            ->assertDontSee('data-ai-open', false)
            ->assertDontSee(__('ui.ask_ai_about_product'));
    }

    public function test_disabled_wishlist_and_compare_routes_are_not_accessible(): void
    {
        config([
            'features.wishlist' => false,
            'features.compare' => false,
        ]);

        $this->get('/wishlist')->assertNotFound();
        $this->get('/compare')->assertNotFound();
    }

    public function test_catalog_search_uses_synonyms_brand_category_and_sku(): void
    {
        $this->get('/catalog?q='.urlencode('гайковерт'))->assertOk()->assertSee('NC-4255Q');
        $this->get('/catalog?q=set')->assertOk()->assertSee('7596MR');
        $this->get('/catalog?q=7596MR')->assertOk()->assertSee('7596MR');
        $this->get('/catalog?q=M7')->assertOk()->assertSee('M7');
    }

    public function test_regular_user_cannot_open_admin_products(): void
    {
        $user = User::where('email', 'andrei.popescu@example.com')->firstOrFail();

        $this->actingAs($user)->get('/admin/products')->assertForbidden();
    }

    public function test_admin_can_open_product_parser(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();

        $this
            ->actingAs($admin)
            ->get('/admin/parser')
            ->assertOk()
            ->assertSee(__('ui.parser_products'))
            ->assertSee(__('ui.parser_safety_title'))
            ->assertSee('SKU');
    }

    public function test_regular_user_cannot_open_product_parser(): void
    {
        $user = User::where('email', 'andrei.popescu@example.com')->firstOrFail();

        $this->actingAs($user)->get('/admin/parser')->assertForbidden();
    }

    public function test_parser_price_list_upload_accepts_safe_csv_and_dispatches_job(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('price.csv', implode("\n", [
            'Артикул;Наименование;ОтпускЦена;Остаток',
            'NC-4233-SAFE;Пистолет пневматический M7 NC-4233-SAFE;799;5',
        ]));

        $this
            ->actingAs($admin)
            ->post(route('admin.parser.price-list'), [
                'supplier_name' => 'Test supplier',
                'price_file' => $file,
                'price_type' => 'retail_price',
                'import_mode' => 'create_drafts',
                'search_images' => '0',
                'translate_descriptions' => '1',
                'create_drafts_automatically' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_parser_batches', [
            'source_type' => 'price_list',
            'file_type' => 'csv',
            'import_mode' => 'dry_run',
            'status' => 'pending',
        ]);
        Queue::assertPushed(ParsePriceListJob::class);
    }

    public function test_price_list_dry_run_tracks_brand_vehicle_context_without_creating_products(): void
    {
        Storage::fake('local');
        config()->set('product_parser.official_sources_enabled', false);
        $tristools = Mockery::mock(TrisToolsEnrichmentService::class);
        $tristools->shouldReceive('enrich')->once()->with('JTC-4902', 'JTC')->andReturn([
            'found' => true,
            'title' => 'Головка TORX для механизма стеклоочистителей BMW',
            'description' => 'Специальная головка TORX для обслуживания автомобилей BMW.',
            'title_ru' => 'Головка TORX для механизма стеклоочистителей BMW',
            'title_ro' => 'Cap TORX pentru mecanismul ștergătoarelor BMW',
            'description_ru' => 'Специальная головка TORX для обслуживания автомобилей BMW.',
            'description_ro' => 'Cap TORX special pentru întreținerea automobilelor BMW.',
            'breadcrumb' => ['Специальный автоинструмент', 'Тормозной инструмент'],
            'breadcrumb_ro' => ['Scule auto speciale', 'Scule pentru frâne'],
            'specs' => [],
            'images' => ['https://tristool.md/uploaded_files/JTC-4902.jpg'],
            'source_urls' => ['https://tristool.md/ru/products/1/4902'],
            'confidence' => 96,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $tristools);

        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $csv = implode("\n", [
            'Артикул;Наименование;ОтпускЦена;Остаток',
            ';JTC;;',
            ';BENZ & BMW;;',
            'JTC-4902;Головка TORX 1/2" для снятия механизма стеклоочистителей BMW;163;15',
        ]);
        Storage::disk('local')->put('parser/test/jtc.csv', $csv);

        $batch = ProductParserBatch::create([
            'user_id' => $admin->id,
            'title' => 'JTC dry-run test',
            'source_type' => 'price_list',
            'file_name' => 'jtc.csv',
            'file_path' => 'parser/test/jtc.csv',
            'file_type' => 'csv',
            'price_type' => 'retail_price',
            'import_mode' => 'dry_run',
            'status' => 'pending',
            'options_json' => [
                'search_images' => false,
                'process_images' => false,
                'create_drafts_automatically' => false,
            ],
        ]);

        app(ProductPriceListImportService::class)->dryRun($batch);

        $batch->refresh();
        $this->assertSame('dry_run_completed', $batch->status);
        $this->assertSame(1, $batch->product_rows);
        $this->assertSame(2, $batch->service_rows);
        $this->assertSame(1, $batch->new_sku_count);
        $this->assertSame(1, $batch->planned_drafts);
        $this->assertSame(0, $batch->created_drafts);

        $item = ProductParserItem::where('sku', 'JTC-4902')->firstOrFail();
        $this->assertSame('JTC', $item->brand);
        $this->assertSame('BENZ & BMW', $item->vehicle_application);
        $this->assertSame('dry_run_ready', $item->status);
        $this->assertFalse((bool) $item->needs_category_review);
        $this->assertDatabaseMissing('products', ['sku' => 'JTC-4902']);
    }

    public function test_large_price_list_stages_rows_and_finishes_after_the_last_queued_row(): void
    {
        Queue::fake();
        Storage::fake('local');
        config()->set('product_parser.official_sources_enabled', false);
        $tristools = Mockery::mock(TrisToolsEnrichmentService::class);
        $tristools->shouldReceive('enrich')->twice()->andReturnUsing(function (string $sku) {
            return [
                'found' => true,
                'title' => "Пневмогайковерт M7 {$sku}",
                'description' => "Пневматический гайковерт M7 {$sku}.",
                'title_ru' => "Пневмогайковерт M7 {$sku}",
                'title_ro' => "Cheie pneumatică M7 {$sku}",
                'description_ru' => "Пневматический гайковерт M7 {$sku}.",
                'description_ro' => "Cheie pneumatică M7 {$sku}.",
                'breadcrumb' => ['Пневматический инструмент', 'Гайковерты'],
                'breadcrumb_ro' => ['Scule pneumatice', 'Chei pneumatice'],
                'specs' => [],
                'images' => ["https://tristool.md/uploaded_files/{$sku}.jpg"],
                'source_urls' => ["https://tristool.md/ru/product/{$sku}"],
                'confidence' => 98,
            ];
        });
        $this->app->instance(TrisToolsEnrichmentService::class, $tristools);

        $csv = implode("\n", [
            'Артикул;Наименование;ОтпускЦена;Остаток',
            ';M7 (Mighty Seven);;',
            'NC-STREAM-001;Пневматический гайковерт M7 NC-STREAM-001;799;5',
            'NC-STREAM-002;Пневматический гайковерт M7 NC-STREAM-002;899;7',
        ]);
        Storage::disk('local')->put('parser/test/large-stream.csv', $csv);

        $batch = ProductParserBatch::create([
            'title' => 'Large streaming import test',
            'source_type' => 'price_list',
            'file_name' => 'large-stream.csv',
            'file_path' => 'parser/test/large-stream.csv',
            'file_type' => 'csv',
            'price_type' => 'retail_price',
            'import_mode' => 'review_only',
            'status' => 'pending',
            'options_json' => [
                'search_images' => false,
                'process_images' => false,
                'create_drafts_automatically' => false,
            ],
        ]);

        $importer = app(ProductPriceListImportService::class);
        $importer->queueImport($batch);

        $batch->refresh();
        $this->assertSame('processing', $batch->status);
        $this->assertTrue((bool) $batch->options_json['staging_complete']);
        $this->assertSame(2, $batch->product_rows);
        $this->assertSame(2, $batch->items()->where('status', 'tristool_queued')->count());
        Queue::assertPushed(ProcessPriceListRowJob::class, 2);

        $jobs = Queue::pushed(ProcessPriceListRowJob::class)->values();
        $jobs[0]->handle($importer);
        $this->assertSame('processing', $batch->fresh()->status);

        $jobs[1]->handle($importer);
        $this->assertSame('completed', $batch->fresh()->status);
        $this->assertNotNull($batch->fresh()->finished_at);
        $this->assertSame(0, $batch->fresh()->created_drafts);
        $this->assertSame(2, $batch->items()->where('status', 'ready_for_review')->count());
    }

    public function test_queued_import_schedules_rows_that_need_category_resolution(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::disk('local')->put('parser/test/needs-category.csv', implode("\n", [
            'Артикул;Наименование;ОтпускЦена;Остаток',
            'ZZ-QUEUE-001;Деталь без понятной категории;100;3',
        ]));

        $batch = ProductParserBatch::create([
            'title' => 'Category review queue test',
            'source_type' => 'price_list',
            'file_name' => 'needs-category.csv',
            'file_path' => 'parser/test/needs-category.csv',
            'file_type' => 'csv',
            'price_type' => 'retail_price',
            'import_mode' => 'create_drafts',
            'status' => 'pending',
            'options_json' => [
                'search_images' => true,
                'process_images' => true,
                'create_drafts_automatically' => true,
            ],
        ]);

        app(ProductPriceListImportService::class)->queueImport($batch);

        $item = $batch->items()->where('sku', 'ZZ-QUEUE-001')->firstOrFail();
        $this->assertSame('tristool_queued', $item->status);
        $this->assertSame('tristool_queued', $item->processing_stage);
        $this->assertTrue((bool) $item->needs_category_review);
        $this->assertSame('processing', $batch->fresh()->status);
        $this->assertSame(1, $batch->fresh()->dry_run_report_json['queued_rows']);
        Queue::assertPushed(ProcessPriceListRowJob::class, 1);
    }

    public function test_fast_price_list_job_defers_a_missing_tristool_sku_without_blocking_the_fast_queue(): void
    {
        Queue::fake();
        config()->set('product_parser.official_sources_enabled', true);
        $tristools = Mockery::mock(TrisToolsEnrichmentService::class);
        $tristools->shouldReceive('enrich')->once()->with('KT-MISSING-1', 'King Tony')->andReturn([
            'found' => false,
            'confidence' => 0,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $tristools);

        $batch = ProductParserBatch::create([
            'title' => 'Fast TrisTool deferral',
            'source_type' => 'price_list',
            'import_mode' => 'dry_run',
            'status' => 'processing',
            'options_json' => ['staging_complete' => true],
        ]);
        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'KT-MISSING-1',
            'brand' => 'King Tony',
            'status' => 'tristool_queued',
            'processing_stage' => 'tristool_queued',
        ]);

        (new ProcessPriceListRowJob($item->id))->handle(app(ProductPriceListImportService::class));

        $item->refresh();
        $this->assertSame('external_check_queued', $item->status);
        $this->assertSame('external_queued', $item->processing_stage);
        $this->assertNotNull($item->tristool_checked_at);
        Queue::assertPushed(ProcessExternalPriceListRowJob::class, 1);
    }

    public function test_queued_import_learns_unknown_category_from_tristool_and_reuses_it(): void
    {
        Queue::fake();
        Storage::fake('local');
        config([
            'product_parser.tristools.rate_limit_ms' => 0,
            'product_parser.min_fallback_confidence' => 80,
            'product_parser.official_sources_enabled' => false,
        ]);
        $tristools = Mockery::mock(TrisToolsEnrichmentService::class);
        $tristools->shouldReceive('enrich')->twice()->andReturnUsing(function (string $sku) {
            return [
                'found' => true,
                'title' => "King Tony VDE diagonal cutters {$sku}",
                'description' => "VDE insulated diagonal cutters {$sku}.",
                'title_ru' => "Диэлектрические бокорезы King Tony {$sku}",
                'title_ro' => "Clește diagonal VDE King Tony {$sku}",
                'description_ru' => "Изолированные диагональные кусачки VDE {$sku}.",
                'description_ro' => "Clește diagonal izolat VDE {$sku}.",
                'breadcrumb' => ['Hand tools', 'VDE insulated tools'],
                'breadcrumb_ro' => ['Scule manuale', 'Scule izolate VDE'],
                'specs' => [],
                'images' => ["https://tristool.md/uploaded_files/{$sku}.jpg"],
                'source_urls' => ["https://tristool.md/ru/product/{$sku}"],
                'confidence' => 98,
            ];
        });
        $this->app->instance(TrisToolsEnrichmentService::class, $tristools);

        Storage::disk('local')->put('parser/test/category-learning.csv', implode("\n", [
            'sku;name;price;stock;brand;group;subgroup',
            '6216-06A;Diagonal cutters;100;3;King Tony;VDE;',
            '6216-07A;Long diagonal cutters;110;4;King Tony;VDE;',
        ]));

        $batch = ProductParserBatch::create([
            'title' => 'Category learning queue test',
            'source_type' => 'price_list',
            'file_name' => 'category-learning.csv',
            'file_path' => 'parser/test/category-learning.csv',
            'file_type' => 'csv',
            'price_type' => 'retail_price',
            'import_mode' => 'review_only',
            'status' => 'pending',
            'options_json' => [
                'search_images' => false,
                'process_images' => false,
                'create_drafts_automatically' => false,
            ],
        ]);

        $importer = app(ProductPriceListImportService::class);
        $importer->queueImport($batch);

        Queue::assertPushed(ProcessPriceListRowJob::class, 2);
        $jobs = Queue::pushed(ProcessPriceListRowJob::class)->values();
        $jobs[0]->handle($importer);
        $jobs[1]->handle($importer);

        $category = Category::where('slug', 'instrumente-electromontaj')->firstOrFail();
        $items = $batch->items()->orderBy('id')->get();

        $this->assertCount(2, $items);
        $this->assertSame([$category->id, $category->id], $items->pluck('category_id')->all());
        $this->assertSame(['ready_for_review', 'ready_for_review'], $items->pluck('status')->all());
        $this->assertFalse((bool) $items[0]->needs_category_review);
        $this->assertFalse((bool) $items[1]->needs_category_review);
        $this->assertSame('tristools_category', $items[0]->category_detection_method);
        $this->assertSame('learned_tristools_breadcrumb', $items[1]->category_detection_method);
        $this->assertSame('completed', $batch->fresh()->status);
        $this->assertSame(0, $batch->fresh()->dry_run_report_json['queued_rows']);
        $this->assertDatabaseHas('product_parser_category_learnings', [
            'key_type' => 'sku',
            'key_value' => '6216-06A',
            'category_id' => $category->id,
            'source' => 'tristools',
        ]);
        $this->assertDatabaseHas('product_parser_category_learnings', [
            'key_type' => 'group',
            'key_value' => 'VDE',
            'category_id' => $category->id,
        ]);
        $this->assertSame(4, ProductParserCategoryLearning::count());
    }

    public function test_cancellation_before_staging_preserves_the_dry_run_snapshot(): void
    {
        $batch = ProductParserBatch::create([
            'title' => 'Cancelled before staging test',
            'source_type' => 'price_list',
            'file_name' => 'cancelled.csv',
            'file_path' => 'parser/test/cancelled.csv',
            'file_type' => 'csv',
            'price_type' => 'retail_price',
            'import_mode' => 'create_drafts',
            'status' => 'dry_run_completed',
            'product_rows' => 1,
            'parsed_rows' => 1,
            'options_json' => ['search_images' => false],
        ]);
        ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'KEEP-DRY-RUN-1',
            'status' => 'dry_run_ready',
        ]);

        $reader = Mockery::mock(ProductPriceListReader::class);
        $reader->shouldReceive('stream')->once()->andReturnUsing(function () use ($batch) {
            $batch->forceFill(['status' => 'cancelled', 'finished_at' => now()])->save();

            return [
                'sheet' => 'CSV',
                'headers' => ['Артикул', 'Наименование', 'ОтпускЦена'],
                'mapping' => ['sku' => 0, 'name' => 1, 'price' => 2],
                'total_rows' => 1,
                'rows' => new \ArrayIterator([]),
            ];
        });
        $this->app->instance(ProductPriceListReader::class, $reader);

        app(ProductPriceListImportService::class)->queueImport($batch);

        $this->assertSame('cancelled', $batch->fresh()->status);
        $this->assertSame(1, $batch->fresh()->product_rows);
        $this->assertDatabaseHas('product_parser_items', [
            'batch_id' => $batch->id,
            'sku' => 'KEEP-DRY-RUN-1',
            'status' => 'dry_run_ready',
        ]);
    }

    public function test_active_parser_batch_must_be_cancelled_before_deletion(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $batch = ProductParserBatch::create([
            'title' => 'Active delete guard test',
            'source_type' => 'price_list',
            'status' => 'processing',
        ]);
        foreach (['queued', 'searching', 'processing_images', 'parsed'] as $index => $status) {
            ProductParserItem::create([
                'batch_id' => $batch->id,
                'sku' => 'CANCEL-'.$index,
                'status' => $status,
            ]);
        }

        $this->actingAs($admin)
            ->delete(route('admin.parser.batches.destroy', $batch))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('product_parser_batches', ['id' => $batch->id]);

        $this->actingAs($admin)
            ->post(route('admin.parser.batches.cancel', $batch))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $batch->fresh()->status);
        $this->assertSame(4, $batch->items()->where('status', 'rejected')->count());
    }

    public function test_parser_batch_does_not_count_queued_category_flags_as_manual_exceptions(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $batch = ProductParserBatch::create([
            'title' => 'Parser counter test',
            'source_type' => 'price_list',
            'status' => 'processing',
        ]);

        ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'QUEUED-CATEGORY',
            'status' => 'queued',
            'needs_category_review' => true,
        ]);
        ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'SEARCHING-CATEGORY',
            'status' => 'searching',
            'needs_category_review' => true,
        ]);
        ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'FINAL-CATEGORY',
            'status' => 'needs_category_review',
            'needs_category_review' => true,
        ]);
        ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'FAILED-PARSER',
            'status' => 'failed',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.parser.batches.show', $batch))
            ->assertOk()
            ->assertViewHas('bulkStats', fn (array $stats) => $stats['exceptions'] === 2)
            ->assertViewHas('filterCounts', fn (array $counts) => $counts['processing'] === 2
                && $counts['completed'] === 2
                && $counts['progress_percent'] === 50
                && $counts['needs_category'] === 1
                && $counts['failed'] === 1);

        $this->actingAs($admin)
            ->get(route('admin.parser.batches.show', ['batch' => $batch, 'status' => 'processing_auto']))
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->pluck('sku')->sort()->values()->all() === [
                'QUEUED-CATEGORY',
                'SEARCHING-CATEGORY',
            ]);

        $this->actingAs($admin)
            ->get(route('admin.parser.batches.show', ['batch' => $batch, 'needs_category' => 1]))
            ->assertOk()
            ->assertViewHas('items', fn ($items) => $items->pluck('sku')->all() === ['FINAL-CATEGORY']);
    }

    public function test_parser_price_list_upload_rejects_disguised_php_file(): void
    {
        Queue::fake();
        Storage::fake('local');

        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $file = UploadedFile::fake()->createWithContent('bad.csv', "<?php echo 'bad';");

        $this
            ->actingAs($admin)
            ->from(route('admin.parser.index'))
            ->post(route('admin.parser.price-list'), [
                'supplier_name' => 'Test supplier',
                'price_file' => $file,
                'price_type' => 'retail_price',
                'import_mode' => 'create_drafts',
            ])
            ->assertRedirect(route('admin.parser.index'))
            ->assertSessionHasErrors('price_file');

        $this->assertDatabaseMissing('product_parser_batches', ['file_name' => 'bad.csv']);
        Queue::assertNothingPushed();
    }

    public function test_parser_single_sku_uses_existing_product_without_duplicate(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $product = Product::where('sku', '7596MR')->firstOrFail();
        $initialProductCount = Product::count();
        config()->set('product_parser.official_sources_enabled', false);
        $tristools = Mockery::mock(TrisToolsEnrichmentService::class);
        $tristools->shouldReceive('enrich')->once()->with('7596MR', 'King Tony')->andReturn([
            'found' => true,
            'title' => 'Набор инструментов King Tony 7596MR',
            'description' => 'Профессиональный набор инструментов King Tony 7596MR.',
            'title_ru' => 'Набор инструментов King Tony 7596MR',
            'title_ro' => 'Trusă de scule King Tony 7596MR',
            'description_ru' => 'Профессиональный набор инструментов King Tony 7596MR.',
            'description_ro' => 'Trusă profesională de scule King Tony 7596MR.',
            'breadcrumb' => ['Ручной инструмент', 'Наборы инструментов'],
            'breadcrumb_ro' => ['Scule manuale', 'Truse de scule'],
            'specs' => [],
            'images' => ['https://tristool.md/uploaded_files/7596MR.jpg'],
            'source_urls' => ['https://tristool.md/ru/product/7596MR'],
            'confidence' => 98,
        ]);
        $this->app->instance(TrisToolsEnrichmentService::class, $tristools);

        $this
            ->actingAs($admin)
            ->post('/admin/parser/single', [
                'sku' => '7596MR',
                'brand' => 'King Tony',
                'category_id' => $product->category_id,
                'language' => 'auto',
                'image_limit' => 4,
            ])
            ->assertRedirect();

        $item = ProductParserItem::where('sku', '7596MR')->firstOrFail();

        $this->assertSame($product->id, $item->existing_product_id);
        $this->assertSame('ready_for_review', $item->status);
        $this->assertSame(98, $item->confidence_score);
        $this->assertSame($initialProductCount, Product::count());

        $this->actingAs($admin)->get(route('admin.parser.items.show', $item))->assertOk()->assertSee('7596MR');
    }

    public function test_parser_batch_creates_items_and_dispatches_queue_job(): void
    {
        Queue::fake();

        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();

        $this
            ->actingAs($admin)
            ->post('/admin/parser/batch', [
                'title' => 'Parser batch test',
                'sku_text' => "7596MR\nNC-4233",
                'brand' => 'auto',
                'language' => 'auto',
                'mode' => 'find_prepare_photos',
            ])
            ->assertRedirect();

        $batch = ProductParserBatch::where('title', 'Parser batch test')->firstOrFail();

        $this->assertSame(2, $batch->items()->count());
        $this->assertSame('pending', $batch->status);

        Queue::assertPushed(ParseSkuBatchJob::class, fn (ParseSkuBatchJob $job) => $job->batchId === $batch->id);
    }

    public function test_parser_image_processor_rejects_unsafe_remote_image_urls(): void
    {
        Http::fake();

        $batch = ProductParserBatch::create([
            'title' => 'Unsafe image URL',
            'source_type' => 'single',
            'status' => 'pending',
        ]);

        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'UNSAFE-IMG',
            'brand' => 'King Tony',
            'status' => 'ready_for_review',
        ]);

        $asset = ProductParserImageAsset::create([
            'parser_item_id' => $item->id,
            'source_url' => 'http://127.0.0.1/internal.jpg',
            'source_domain' => '127.0.0.1',
            'status' => 'found',
            'is_selected' => true,
            'is_main' => true,
        ]);

        app(ProductImageProcessorService::class)->processSelected($item);

        $this->assertSame('ready_for_review', $item->fresh()->status);
        $this->assertTrue((bool) $item->fresh()->needs_image_review);
        $this->assertSame('failed', $asset->fresh()->status);
        $this->assertStringContainsString('HTTPS', $asset->fresh()->error_message);
    }

    public function test_parser_creates_inactive_draft_for_new_sku(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $category = Category::firstOrFail();

        $batch = ProductParserBatch::create([
            'user_id' => $admin->id,
            'title' => 'Draft parser test',
            'source_type' => 'single',
            'sku_count' => 1,
            'status' => 'completed',
        ]);

        $item = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'PARSER-DRAFT-1',
            'brand' => 'King Tony',
            'category_id' => $category->id,
            'status' => 'ready_for_review',
            'confidence_score' => 92,
            'found_title' => 'Parser draft product',
            'found_description' => 'Draft description from parser review.',
            'found_specs_json' => ['Brand' => 'King Tony', 'Cod produs' => 'PARSER-DRAFT-1'],
            'source_urls_json' => ['https://tristool.md/parser-draft-1'],
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.parser.items.draft', $item))
            ->assertRedirect();

        $product = Product::where('sku', 'PARSER-DRAFT-1')->firstOrFail();

        $this->assertFalse((bool) $product->is_active);
        $this->assertSame('draft', $product->status);
        $this->assertEquals(0, $product->price);
        $this->assertSame(0, $product->stock_quantity);
        $this->assertSame($product->id, $item->fresh()->created_product_id);
    }

    public function test_parser_bulk_action_keeps_incomplete_drafts_unpublished(): void
    {
        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $category = Category::firstOrFail();

        $batch = ProductParserBatch::create([
            'user_id' => $admin->id,
            'title' => 'Bulk parser test',
            'source_type' => 'price_list',
            'sku_count' => 2,
            'status' => 'completed',
        ]);

        $safeItem = ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'PARSER-BULK-1',
            'brand' => 'King Tony',
            'category_id' => $category->id,
            'status' => 'ready_for_review',
            'confidence_score' => 95,
            'parsed_price' => 321,
            'parsed_stock' => 7,
            'found_title' => 'Bulk parser product',
            'found_description' => 'Bulk parser description.',
            'found_specs_json' => ['Brand' => 'King Tony'],
        ]);

        ProductParserItem::create([
            'batch_id' => $batch->id,
            'sku' => 'PARSER-BULK-NEEDS-CAT',
            'brand' => 'King Tony',
            'status' => 'needs_category_review',
            'needs_category_review' => true,
            'found_title' => 'Needs category product',
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.parser.batches.bulk-action', $batch), [
                'action' => 'create_safe_drafts',
                'limit' => 20000,
            ])
            ->assertRedirect();

        $draft = Product::where('sku', 'PARSER-BULK-1')->firstOrFail();
        $this->assertSame('draft', $draft->status);
        $this->assertNull(Product::where('sku', 'PARSER-BULK-NEEDS-CAT')->first());
        $this->assertSame($draft->id, $safeItem->fresh()->created_product_id);

        $this
            ->actingAs($admin)
            ->post(route('admin.parser.batches.bulk-action', $batch), [
                'action' => 'publish_drafts',
                'limit' => 20000,
            ])
            ->assertRedirect();

        $draft->refresh();
        $this->assertSame('draft', $draft->status);
        $this->assertFalse((bool) $draft->is_active);
        $this->assertSame('draft_created', $safeItem->fresh()->status);
    }

    public function test_price_list_import_creates_draft_and_keeps_existing_sku_safe(): void
    {
        Storage::fake('local');

        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $existing = Product::where('sku', '7596MR')->firstOrFail();
        $initialExistingCount = Product::where('sku', '7596MR')->count();
        $pneumaticCategory = Category::where('slug', 'chei-pneumatice')->firstOrFail();
        ProductParserCategoryLearning::create([
            'key_type' => 'sku',
            'key_hash' => sha1('nc 4233 test'),
            'key_value' => 'NC-4233-TEST',
            'brand_key' => '*',
            'category_id' => $pneumaticCategory->id,
            'source' => 'test',
            'confidence' => 99,
            'observations' => 1,
        ]);

        $csv = implode("\n", [
            'Артикул;Наименование;ОтпускЦена;Остаток',
            ';M7 (Mighty Seven);;',
            ';Авторемонтный Пневмоинструмент;;',
            'NC-4233-TEST;Пистолет пневматический M7 NC-4233-TEST;799;5',
            ';KING TONY;;',
            ';Ручной инструмент;;',
            '7596MR;Set de scule King Tony 7596MR;1999;12',
        ]);
        Storage::disk('local')->put('parser/test/price.csv', $csv);

        $batch = ProductParserBatch::create([
            'user_id' => $admin->id,
            'title' => 'Price import test',
            'source_type' => 'price_list',
            'supplier_name' => 'Tristool',
            'file_name' => 'price.csv',
            'file_path' => 'parser/test/price.csv',
            'file_type' => 'csv',
            'price_type' => 'retail_price',
            'import_mode' => 'create_drafts',
            'status' => 'pending',
            'options_json' => [
                'search_images' => false,
                'process_images' => false,
                'create_drafts_automatically' => true,
                'add_photos_to_existing' => false,
            ],
        ]);

        app(ProductPriceListImportService::class)->import($batch);

        $batch->refresh();
        $this->assertSame('completed', $batch->status);
        $this->assertSame(2, $batch->product_rows);
        $this->assertSame(1, $batch->created_drafts);
        $this->assertSame(1, $batch->updated_existing);

        $item = ProductParserItem::where('sku', 'NC-4233-TEST')->firstOrFail();
        $this->assertSame('M7 / Mighty Seven', $item->brand);
        $this->assertEquals(799.00, (float) $item->parsed_price);
        $this->assertSame(5, $item->parsed_stock);
        $this->assertFalse((bool) $item->needs_category_review);
        $this->assertSame('draft_created', $item->status);

        $draft = Product::where('sku', 'NC-4233-TEST')->firstOrFail();
        $this->assertSame('draft', $draft->status);
        $this->assertFalse((bool) $draft->is_active);
        $this->assertSame('pending_review', $draft->approval_status);
        $this->assertEquals(799.00, (float) $draft->price);
        $this->assertSame(5, $draft->stock_quantity);

        $existingItem = ProductParserItem::where('sku', '7596MR')->firstOrFail();
        $this->assertSame('existing_product_found', $existingItem->status);
        $this->assertSame($existing->id, $existingItem->existing_product_id);
        $this->assertSame($initialExistingCount, Product::where('sku', '7596MR')->count());
        $this->assertDatabaseMissing('product_parser_items', ['sku' => 'M7 (Mighty Seven)']);
    }

    public function test_tristool_import_keeps_mdl_price_without_ron_conversion(): void
    {
        $this->assertTrue(function_exists('parseMdlPrice'));
        $this->assertEquals(2600.00, parseMdlPrice('2 600,00'));
        $this->assertEquals(195.00, parseMdlPrice('195,00'));
    }

    public function test_price_list_import_requires_category_review_for_weak_match(): void
    {
        Storage::fake('local');

        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $csv = implode("\n", [
            'Артикул;Наименование;ОтпускЦена;Остаток',
            'ZZ-001;Деталь без понятной категории;100;3',
        ]);
        Storage::disk('local')->put('parser/test/weak.csv', $csv);

        $batch = ProductParserBatch::create([
            'user_id' => $admin->id,
            'title' => 'Weak category import test',
            'source_type' => 'price_list',
            'file_name' => 'weak.csv',
            'file_path' => 'parser/test/weak.csv',
            'file_type' => 'csv',
            'price_type' => 'retail_price',
            'import_mode' => 'create_drafts',
            'status' => 'pending',
            'options_json' => [
                'search_images' => false,
                'process_images' => false,
                'create_drafts_automatically' => true,
            ],
        ]);

        app(ProductPriceListImportService::class)->import($batch);

        $item = ProductParserItem::where('sku', 'ZZ-001')->firstOrFail();
        $this->assertTrue((bool) $item->needs_category_review);
        $this->assertSame('needs_category_review', $item->status);
        $this->assertNull($item->created_product_id);
        $this->assertDatabaseMissing('products', ['sku' => 'ZZ-001']);
    }

    public function test_catalog_structure_assigns_tools_by_work_type(): void
    {
        $brand = Brand::firstOrFail();
        $wrongCategory = Category::where('slug', 'chei-si-surubelnite')->firstOrFail();

        foreach ([
            ['AUDIT-RICHT', '9CF110-AUDIT', 'Audit body repair hammer', 'tinichigerie-si-richtuire'],
            ['AUDIT-SCREW', '14271003-AUDIT', 'Surubelnita TORX audit', 'chei-si-surubelnite'],
            ['AUDIT-DRILL', 'QE-AUDIT', 'Audit pneumatic drill', 'burghie-pneumatice'],
            ['AUDIT-NAILER', 'SJ-TN64-AUDIT', 'Audit pneumatic nailer', 'nituitoare-capsatoare-si-cuie-pneumatice'],
            ['AUDIT-SOLDER', '6BC24A-AUDIT', 'Audit soldering iron', 'lipire-si-consumabile'],
            ['AUDIT-CLAMP', '9AA13-AUDIT', 'Audit hose clamp pliers', 'scule-motor-frane-suspensie'],
        ] as [$slug, $sku, $name, $expectedCategory]) {
            Product::create([
                'brand_id' => $brand->id,
                'category_id' => $wrongCategory->id,
                'name' => $name,
                'name_ro' => $name,
                'slug' => strtolower($slug),
                'sku' => $sku,
                'price' => 100,
                'stock_quantity' => 1,
                'stock_status' => 'in_stock',
                'main_image' => '/images/products/product-placeholder-toolbox.svg',
                'is_active' => true,
            ]);

            $expected[$sku] = $expectedCategory;
        }

        $this->seed(CatalogStructureSeeder::class);

        foreach ($expected as $sku => $expectedCategory) {
            $product = Product::with('category')->where('sku', $sku)->firstOrFail();

            $this->assertSame($expectedCategory, $product->category->slug, $sku);
        }
    }
}
