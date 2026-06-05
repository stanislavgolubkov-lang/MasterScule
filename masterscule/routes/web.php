<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/catalog/{category?}', [ShopController::class, 'catalog'])->name('catalog');
Route::get('/product/{slug}', [ShopController::class, 'product'])->name('product.show');
Route::get('/brands', [ShopController::class, 'brands'])->name('brands');
Route::get('/brand/{slug}', [ShopController::class, 'brand'])->name('brand.show');

Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('auth')->name('checkout.store');

Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/products', [AdminController::class, 'products'])->name('admin.products');
    Route::post('/admin/products', [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::patch('/admin/products/{product}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/admin/products/{product}', [AdminController::class, 'destroyProduct'])->name('admin.products.destroy');
    Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
});

Route::get('/ai/tool-advisor', [AiController::class, 'advisor'])->name('ai.advisor');
Route::post('/ai/tool-advisor', [AiController::class, 'ask'])->name('ai.ask');

Route::get('/wishlist', [ShopController::class, 'wishlist'])->name('wishlist');
Route::get('/compare', [ShopController::class, 'compare'])->name('compare');
Route::get('/promotions', [ShopController::class, 'promotions'])->name('promotions');
Route::get('/new', [ShopController::class, 'newProducts'])->name('new');
Route::get('/bestsellers', [ShopController::class, 'bestsellers'])->name('bestsellers');
Route::get('/{slug}', [ShopController::class, 'page'])->where('slug', 'about|delivery-payment|warranty|returns|contacts|privacy-policy|terms|cookie-policy')->name('page');
