<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categories = [
            [
                'slug' => 'sisteme-tpms',
                'parent' => 'scule-speciale-auto',
                'name' => 'Системы контроля давления в шинах TPMS',
                'name_ro' => 'Sisteme de monitorizare a presiunii TPMS',
                'description' => 'Датчики, вентили, программаторы и диагностические приборы TPMS.',
                'description_ro' => 'Senzori, valve, programatoare și dispozitive de diagnosticare TPMS.',
                'sort_order' => 88,
            ],
            [
                'slug' => 'elevatoare-auto',
                'parent' => 'echipamente-pentru-service',
                'name' => 'Автомобильные подъёмники и аксессуары',
                'name_ro' => 'Elevatoare auto și accesorii',
                'description' => 'Автомобильные подъёмники, адаптеры, опоры и запасные части.',
                'description_ro' => 'Elevatoare auto, adaptoare, suporturi și piese de schimb.',
                'sort_order' => 78,
            ],
        ];

        foreach ($categories as $category) {
            $parentId = DB::table('categories')->where('slug', $category['parent'])->value('id');
            if (! $parentId) {
                continue;
            }

            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'parent_id' => $parentId,
                    'name' => $category['name'],
                    'name_ro' => $category['name_ro'],
                    'description' => $category['description'],
                    'description_ro' => $category['description_ro'],
                    'image' => '/images/categories/echipamente-service.svg',
                    'sort_order' => $category['sort_order'],
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
    }

    public function down(): void
    {
        foreach (['sisteme-tpms', 'elevatoare-auto'] as $slug) {
            $categoryId = DB::table('categories')->where('slug', $slug)->value('id');
            if ($categoryId && ! DB::table('products')->where('category_id', $categoryId)->exists()) {
                DB::table('categories')->where('id', $categoryId)->delete();
            }
        }
    }
};
