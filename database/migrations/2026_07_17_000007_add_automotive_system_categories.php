<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('categories')->where('slug', 'scule-speciale-auto')->value('id');
        if (! $parentId) {
            return;
        }

        $categories = [
            [
                'slug' => 'scule-transmisie-ambreiaj',
                'name' => 'Инструмент для трансмиссии и сцепления',
                'name_ro' => 'Scule pentru transmisie si ambreiaj',
                'sort_order' => 85,
            ],
            [
                'slug' => 'scule-aer-conditionat-auto',
                'name' => 'Инструмент для автокондиционеров',
                'name_ro' => 'Scule pentru aer conditionat auto',
                'sort_order' => 86,
            ],
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
                    'taxonomy_version' => '2026-07-17.5',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        foreach (['scule-transmisie-ambreiaj', 'scule-aer-conditionat-auto'] as $slug) {
            $categoryId = DB::table('categories')->where('slug', $slug)->value('id');
            if ($categoryId && ! DB::table('products')->where('category_id', $categoryId)->exists()) {
                DB::table('categories')->where('id', $categoryId)->delete();
            }
        }
    }
};
