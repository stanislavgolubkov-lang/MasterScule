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
            ['slug' => 'prelungitoare-si-tamburi-cablu'],
            [
                'parent_id' => $parentId,
                'name' => 'Удлинители и кабельные катушки',
                'name_ro' => 'Prelungitoare și tamburi de cablu',
                'description' => 'Силовые удлинители, электрические кабели и катушки.',
                'description_ro' => 'Prelungitoare electrice, cabluri de alimentare și tamburi.',
                'image' => '/images/categories/echipamente-service.svg',
                'sort_order' => 74,
                'is_active' => true,
                'is_assignable' => true,
                'is_menu_visible' => true,
                'source' => 'catalog_taxonomy',
                'taxonomy_version' => '2026-08-04.thinkcar',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $categoryId = DB::table('categories')->where('slug', 'prelungitoare-si-tamburi-cablu')->value('id');
        if ($categoryId && ! DB::table('products')->where('category_id', $categoryId)->exists()) {
            DB::table('categories')->where('id', $categoryId)->delete();
        }
    }
};
