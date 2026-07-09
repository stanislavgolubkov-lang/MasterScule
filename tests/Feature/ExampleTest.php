<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ParsePriceListJob;
use App\Jobs\ParseSkuBatchJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductParserBatch;
use App\Models\ProductParserItem;
use App\Models\User;
use App\Services\ProductPriceListImportService;
use Database\Seeders\CatalogStructureSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

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
            ->assertSee('Удалить товар');

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
            ->assertRedirect();

        $this->assertDatabaseMissing('products', ['sku' => 'ADMIN-TEST-1']);
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
        $this->assertStringContainsString('/order/', $response->headers->get('Location'));

        $this->assertDatabaseCount('orders', 1);
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
            'services.maib.signature_secret' => null,
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

        $this
            ->postJson('/payment/maib/callback', [
                'checkoutId' => 'checkout-test-123',
                'status' => 'WaitingForPayment',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'waiting_for_payment', 'stock_captured' => false]);

        $this->assertSame($stock, $product->fresh()->stock_quantity);

        $this
            ->postJson('/payment/maib/callback', [
                'checkoutId' => 'checkout-test-123',
                'status' => 'Completed',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'completed', 'stock_captured' => true]);

        $this->assertSame($stock - 1, $product->fresh()->stock_quantity);
        $this->assertNotNull($order->fresh()->stock_deducted_at);

        $this
            ->postJson('/payment/maib/callback', [
                'checkoutId' => 'checkout-test-123',
                'status' => 'Completed',
            ])
            ->assertOk();

        $this->assertSame($stock - 1, $product->fresh()->stock_quantity);
        $this->assertSame(1, PaymentTransaction::where('order_id', $order->id)->count());
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

    public function test_price_list_import_creates_draft_and_keeps_existing_sku_safe(): void
    {
        Storage::fake('local');

        $admin = User::where('email', 'admin@masterscule.md')->firstOrFail();
        $existing = Product::where('sku', '7596MR')->firstOrFail();
        $initialExistingCount = Product::where('sku', '7596MR')->count();

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
