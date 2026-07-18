<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('categories')->where('slug', 'instrumente-electromontaj')->value('id');
        if (! $parentId) {
            return;
        }

        DB::table('categories')->updateOrInsert(
            ['slug' => 'instrumente-izolate-vde'],
            [
                'parent_id' => $parentId,
                'name' => 'Диэлектрический инструмент VDE',
                'name_ro' => 'Scule izolate VDE',
                'description' => 'Изолированный ручной инструмент для безопасной работы до 1000 В.',
                'description_ro' => 'Scule de mana izolate pentru lucrari in siguranta pana la 1000 V.',
                'sort_order' => 35,
                'is_active' => true,
                'is_assignable' => true,
                'is_menu_visible' => true,
                'source' => 'catalog_taxonomy',
                'taxonomy_version' => '2026-07-17.4',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $categoryId = DB::table('categories')->where('slug', 'instrumente-izolate-vde')->value('id');
        if ($categoryId && ! DB::table('products')->where('category_id', $categoryId)->exists()) {
            DB::table('categories')->where('id', $categoryId)->delete();
        }
    }
};
