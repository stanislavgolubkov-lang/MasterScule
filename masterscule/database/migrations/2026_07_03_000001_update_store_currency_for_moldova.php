<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->where('currency', 'RON')->update(['currency' => 'MDL']);
        DB::table('orders')->where('currency', 'RON')->update(['currency' => 'MDL']);
        DB::table('orders')->where('shipping_country', 'Romania')->update(['shipping_country' => 'Moldova']);
        DB::table('users')->where('country', 'Romania')->update(['country' => 'Moldova']);
        DB::table('addresses')->where('country', 'Romania')->update(['country' => 'Moldova']);
    }

    public function down(): void
    {
        DB::table('products')->where('currency', 'MDL')->update(['currency' => 'RON']);
        DB::table('orders')->where('currency', 'MDL')->update(['currency' => 'RON']);
    }
};
