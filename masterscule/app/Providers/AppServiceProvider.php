<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $view->with('navCategories', Category::where('is_active', true)->orderBy('sort_order')->get());
            $view->with('navBrands', Brand::where('is_active', true)->orderBy('name')->get());
            $view->with('cartCount', array_sum(session('cart', [])));
        });
    }
}
