<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ProductParserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/language/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['ru', 'ro'], true), 404);

    session(['locale' => $locale]);

    return back();
})->name('language.switch');

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/catalog/{category?}', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/product/{slug}', [ShopController::class, 'product'])->name('product.show');
Route::get('/brands', [ShopController::class, 'brands'])->name('brands');
Route::get('/brand/{slug}', [ShopController::class, 'brand'])->name('brand.show');

Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::get('/admin', [AuthController::class, 'adminLoginForm'])->name('admin.dashboard');
Route::post('/admin', [AuthController::class, 'adminLogin'])->middleware('throttle:5,1')->name('admin.login.store');
Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/order/{order:order_number}', [CheckoutController::class, 'thankYou'])->middleware('signed')->name('checkout.thank-you');
Route::post('/payment/maib/callback', [CheckoutController::class, 'maibCallback'])->name('payment.maib.callback');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
});

Route::middleware('admin.only')->group(function () {
    Route::get('/admin/products', [AdminController::class, 'products'])->name('admin.products');
    Route::post('/admin/products', [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::patch('/admin/products/{product}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::patch('/admin/orders/{order}', [AdminController::class, 'updateOrder'])->name('admin.orders.update');
    Route::get('/admin/payments', [AdminController::class, 'payments'])->name('admin.payments');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/parser', [ProductParserController::class, 'index'])->name('admin.parser.index');
    Route::post('/admin/parser/price-list', [ProductParserController::class, 'storePriceList'])->name('admin.parser.price-list');
    Route::get('/admin/parser/drafts', [ProductParserController::class, 'drafts'])->name('admin.parser.drafts');
    Route::get('/admin/parser/category-rules', [ProductParserController::class, 'rules'])->name('admin.parser.rules');
    Route::post('/admin/parser/category-rules', [ProductParserController::class, 'updateRules'])->name('admin.parser.rules.update');
    Route::post('/admin/parser/single', [ProductParserController::class, 'storeSingle'])->name('admin.parser.single');
    Route::post('/admin/parser/batch', [ProductParserController::class, 'storeBatch'])->name('admin.parser.batch');
    Route::post('/admin/parser/settings', [ProductParserController::class, 'updateSettings'])->name('admin.parser.settings.update');
    Route::get('/admin/parser/batches/{batch}', [ProductParserController::class, 'showBatch'])->name('admin.parser.batches.show');
    Route::post('/admin/parser/batches/{batch}/run-import', [ProductParserController::class, 'runPriceListImport'])->name('admin.parser.batches.run-import');
    Route::post('/admin/parser/batches/{batch}/bulk-action', [ProductParserController::class, 'bulkBatchAction'])->name('admin.parser.batches.bulk-action');
    Route::post('/admin/parser/batches/{batch}/retry-deferred', [ProductParserController::class, 'retryDeferredBatch'])->name('admin.parser.batches.retry-deferred');
    Route::post('/admin/parser/batches/{batch}/cancel', [ProductParserController::class, 'cancelBatch'])->name('admin.parser.batches.cancel');
    Route::delete('/admin/parser/batches/{batch}', [ProductParserController::class, 'destroyBatch'])->name('admin.parser.batches.destroy');
    Route::get('/admin/parser/items/{item}', [ProductParserController::class, 'showItem'])->name('admin.parser.items.show');
    Route::post('/admin/parser/items/{item}/select-images', [ProductParserController::class, 'selectImages'])->name('admin.parser.items.select-images');
    Route::post('/admin/parser/items/{item}/process-images', [ProductParserController::class, 'processImages'])->name('admin.parser.items.process-images');
    Route::post('/admin/parser/items/{item}/draft', [ProductParserController::class, 'createDraft'])->name('admin.parser.items.draft');
    Route::post('/admin/parser/items/{item}/category', [ProductParserController::class, 'updateItemCategory'])->name('admin.parser.items.category');
    Route::post('/admin/parser/items/{item}/publish', [ProductParserController::class, 'publishDraft'])->name('admin.parser.items.publish');
    Route::post('/admin/parser/items/{item}/update-existing', [ProductParserController::class, 'updateExisting'])->name('admin.parser.items.update-existing');
    Route::post('/admin/parser/items/{item}/reject', [ProductParserController::class, 'reject'])->name('admin.parser.items.reject');
    Route::post('/admin/parser/items/{item}/retry', [ProductParserController::class, 'retry'])->name('admin.parser.items.retry');
    Route::post('/admin/parser/items/{item}/retry-official', [ProductParserController::class, 'retryOfficial'])->name('admin.parser.items.retry-official');
    Route::post('/admin/parser/items/{item}/retry-official-images', [ProductParserController::class, 'retryOfficialImages'])->name('admin.parser.items.retry-official-images');
    Route::post('/admin/parser/items/{item}/use-fallback', [ProductParserController::class, 'useFallback'])->name('admin.parser.items.use-fallback');
    Route::post('/admin/parser/items/{item}/reject-fallback', [ProductParserController::class, 'rejectFallback'])->name('admin.parser.items.reject-fallback');
    Route::post('/admin/parser/items/{item}/approve-quality/{type}', [ProductParserController::class, 'approveQuality'])->name('admin.parser.items.approve-quality');
});

if (config('features.ai_assistant')) {
    Route::get('/ai/tool-advisor', [AiController::class, 'advisor'])->name('ai.advisor');
    Route::post('/ai/tool-advisor', [AiController::class, 'ask'])->name('ai.ask');
} else {
    Route::get('/ai/tool-advisor', fn () => abort(404))->name('ai.advisor');
    Route::post('/ai/tool-advisor', fn () => abort(404))->name('ai.ask');
}

if (config('features.wishlist')) {
    Route::get('/wishlist', [ShopController::class, 'wishlist'])->name('wishlist');
} else {
    Route::get('/wishlist', fn () => abort(404))->name('wishlist');
}

if (config('features.compare')) {
    Route::get('/compare', [ShopController::class, 'compare'])->name('compare');
} else {
    Route::get('/compare', fn () => abort(404))->name('compare');
}
Route::get('/promotions', [ShopController::class, 'promotions'])->name('promotions');
Route::get('/new', [ShopController::class, 'newProducts'])->name('new');
Route::get('/bestsellers', [ShopController::class, 'bestsellers'])->name('bestsellers');
Route::get('/{slug}', [ShopController::class, 'page'])->where('slug', 'about|delivery-payment|warranty|returns|contacts|privacy-policy|terms|cookie-policy')->name('page');
