<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categories = DB::table('categories as categories')
            ->join('categories as parents', 'parents.id', '=', 'categories.parent_id')
            ->where('categories.is_menu_visible', true)
            ->where(fn ($query) => $query->whereNull('categories.image')->orWhere('categories.image', ''))
            ->whereNotNull('parents.image')
            ->where('parents.image', '!=', '')
            ->get(['categories.id', 'parents.image']);

        foreach ($categories as $category) {
            DB::table('categories')->where('id', $category->id)->update([
                'image' => $category->image,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Inherited category artwork is intentionally retained.
    }
};
