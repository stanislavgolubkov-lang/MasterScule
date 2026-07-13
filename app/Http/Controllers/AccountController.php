<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function dashboard()
    {
        return view('account.dashboard', [
            'user' => Auth::user(),
            'orders' => Auth::user()->orders()->with('items')->latest()->limit(6)->get(),
            'featuredProducts' => Product::with('brand')->availableForSale()->where('is_featured', true)->limit(4)->get(),
        ]);
    }
}
