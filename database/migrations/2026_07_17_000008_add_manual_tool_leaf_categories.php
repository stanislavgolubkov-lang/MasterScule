<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('categories')->where('slug', 'instrument-manual')->value('id');
        if (! $parentId) {
            return;
        }

        $categories = [
            ['slug' => 'menghine-si-cleme', 'name' => 'Тиски и зажимы', 'name_ro' => 'Menghine si cleme', 'sort_order' => 110],
            ['slug' => 'ciocane-si-unelte-lovire', 'name' => 'Молотки и ударный ручной инструмент', 'name_ro' => 'Ciocane si scule de lovire', 'sort_order' => 120],
            ['slug' => 'nituitoare-manuale', 'name' => 'Ручные заклёпочники', 'name_ro' => 'Nituitoare manuale', 'sort_order' => 130],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'parent_id' => $parentId,
                    'name' => $category['name'],
                    'name_ro' => $category['name_ro'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                    'is_assignable' => true,
                    'is_menu_visible' => true,
                    'source' => 'catalog_taxonomy',
                    'taxonomy_version' => '2026-07-17.6',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        foreach (['menghine-si-cleme', 'ciocane-si-unelte-lovire', 'nituitoare-manuale'] as $slug) {
            $categoryId = DB::table('categories')->where('slug', $slug)->value('id');
            if ($categoryId && ! DB::table('products')->where('category_id', $categoryId)->exists()) {
                DB::table('categories')->where('id', $categoryId)->delete();
            }
        }
    }
};
