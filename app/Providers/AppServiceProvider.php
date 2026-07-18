<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\Catalog\CatalogStorefrontNavigation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CatalogStorefrontNavigation::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);

        View::composer('*', function ($view): void {
            $view->with('navCategories', app(CatalogStorefrontNavigation::class)->roots());
            $view->with('navBrands', Brand::where('is_active', true)->orderBy('name')->get());
            $view->with('cartCount', array_sum(session('cart', [])));
        });
    }
}
