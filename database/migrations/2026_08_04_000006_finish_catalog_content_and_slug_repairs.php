<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_slug_redirects')) {
            Schema::create('product_slug_redirects', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('old_slug')->unique();
                $table->timestamps();
            });
        }

        DB::transaction(function (): void {
            $now = now();
            $this->repairRemainingRepeatedText($now);
            $this->enrichThinDescriptions($now);
            $this->makeDuplicateDescriptionsProductSpecific($now);
            $this->repairKnownSlugs($now);
        });
    }

    private function repairRemainingRepeatedText($now): void
    {
        $titles = [
            '1026CQ-AM' => 'Set de biți KING TONY 1026CQ-AM, 26 piese, 1/4″',
            '1032CQ01' => 'Set de biți KING TONY 1032CQ01, 32 piese, 1/4″',
            '9-90217GRV' => 'Set de clești, extractoare pentru siguranțe, ciocan și poansoane KING TONY 9-90217GRV, 17 piese',
            '9-90217GRV02' => 'Set de clești, extractoare pentru siguranțe, ciocan și poansoane KING TONY 9-90217GRV02, 17 piese',
        ];

        foreach ($titles as $sku => $title) {
            DB::table('products')->where('sku', $sku)->update(['name_ro' => $title, 'updated_at' => $now]);
        }

        $replacements = [
            'Biți Torx TORX' => 'Biți TORX',
            'biti Torx TORX' => 'biți TORX',
            'Poanson poanson' => 'Poanson',
            'poanson poanson' => 'poanson',
            'clesti, clesti' => 'clești și extractoare',
            'INSTRUMENTE DE ÎNDRIRE' => 'SCULE DE REDRESARE',
            'TAIĂTĂTĂ' => 'TĂIERE',
            'SOLID WIRE MIG' => 'SÂRMĂ PLINĂ MIG',
            'SARMA SOLID MIG' => 'SÂRMĂ PLINĂ MIG',
            'SARMA SOLID PENTRU SUDARE' => 'SÂRMĂ PLINĂ PENTRU SUDARE',
            'ALIEII DE ALUMINIU' => 'ALIAJE DE ALUMINIU',
        ];

        DB::table('products')->orderBy('id')->chunkById(250, function ($products) use ($replacements, $now): void {
            foreach ($products as $product) {
                $updates = [];
                foreach (['short_description_ro', 'description_ro'] as $column) {
                    $value = (string) $product->{$column};
                    $updated = str_replace(array_keys($replacements), array_values($replacements), $value);
                    if ($updated !== $value) {
                        $updates[$column] = $updated;
                    }
                }
                if ($updates !== []) {
                    $updates['updated_at'] = $now;
                    DB::table('products')->where('id', $product->id)->update($updates);
                }
            }
        });

        DB::table('products')->where('sku', '9-90217GRV')->update([
            'short_description_ro' => DB::raw("REPLACE(short_description_ro, 'Poanson poanson', 'Poanson')"),
            'description_ro' => DB::raw("REPLACE(description_ro, 'Poanson poanson', 'Poanson')"),
            'updated_at' => $now,
        ]);

        $tapProducts = DB::table('products')
            ->where('status', 'published')
            ->where('name_ru', 'like', 'Метчик %')
            ->get(['id', 'sku', 'name_ru', 'name_ro']);
        foreach ($tapProducts as $product) {
            $size = trim((string) preg_replace('/^Метчик\s+/u', '', (string) $product->name_ru));
            DB::table('products')->where('id', $product->id)->update([
                'name_ro' => "Tarod {$size} KING TONY {$product->sku}",
                'short_description_ro' => "Tarod KING TONY {$product->sku} pentru realizarea sau refacerea filetului {$size}.",
                'description_ro' => "Tarodul KING TONY {$product->sku} este destinat realizării sau refacerii filetului {$size}. Dimensiunea nominală și pasul filetului sunt indicate în denumirea produsului.",
                'updated_at' => $now,
            ]);
        }
    }

    private function enrichThinDescriptions($now): void
    {
        $products = DB::table('products')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('products.status', 'published')
            ->get([
                'products.id', 'products.sku', 'products.name_ru', 'products.name_ro',
                'products.description_ru', 'products.description_ro',
                'brands.name as brand_name', 'categories.name as category_ru', 'categories.name_ro as category_ro',
            ]);

        foreach ($products as $product) {
            $updates = [];
            $brand = trim((string) $product->brand_name);
            $sku = trim((string) $product->sku);

            $descriptionRu = trim(strip_tags((string) $product->description_ru));
            if (mb_strlen($descriptionRu) < 80) {
                $base = rtrim(trim((string) $product->description_ru), " \t\n\r\0\x0B.;").'.';
                $category = trim((string) $product->category_ru);
                $updates['description_ru'] = trim($base." Модель {$brand} {$sku} относится к категории «{$category}»; основные параметры и совместимость указаны в характеристиках товара.");
                $updates['description'] = $updates['description_ru'];
            }

            $descriptionRo = trim(strip_tags((string) $product->description_ro));
            if (mb_strlen($descriptionRo) < 80) {
                $base = rtrim(trim((string) $product->description_ro), " \t\n\r\0\x0B.;").'.';
                $category = trim((string) $product->category_ro);
                $updates['description_ro'] = trim($base." Modelul {$brand} {$sku} face parte din categoria „{$category}”; parametrii principali și compatibilitatea sunt indicați în caracteristicile produsului.");
            }

            if ($updates !== []) {
                $updates['updated_at'] = $now;
                DB::table('products')->where('id', $product->id)->update($updates);
            }
        }
    }

    private function makeDuplicateDescriptionsProductSpecific($now): void
    {
        foreach ([['description_ru', 'ru'], ['description_ro', 'ro']] as [$column, $locale]) {
            $duplicates = DB::table('products')
                ->where('status', 'published')
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->pluck($column);

            foreach ($duplicates as $duplicate) {
                $products = DB::table('products')
                    ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
                    ->where('products.status', 'published')
                    ->where("products.{$column}", $duplicate)
                    ->get(['products.id', 'products.sku', 'brands.name as brand_name']);

                foreach ($products as $product) {
                    $identity = trim((string) $product->brand_name.' '.(string) $product->sku);
                    $suffix = $locale === 'ru'
                        ? " Модель: {$identity}."
                        : " Model: {$identity}.";
                    $updated = rtrim(trim((string) $duplicate), " \t\n\r\0\x0B.").'.'.$suffix;
                    $values = [$column => $updated, 'updated_at' => $now];
                    if ($locale === 'ru') {
                        $values['description'] = $updated;
                    }
                    DB::table('products')->where('id', $product->id)->update($values);
                }
            }
        }
    }

    private function repairKnownSlugs($now): void
    {
        $products = DB::table('products')
            ->where('status', 'published')
            ->where(function ($query): void {
                $query->where('slug', 'like', '%jtc-jtc%')
                    ->orWhere('slug', 'like', '%freizer%')
                    ->orWhere('slug', 'like', '%sineia%');
            })
            ->get(['id', 'sku', 'slug', 'name_ro']);

        foreach ($products as $product) {
            $oldSlug = (string) $product->slug;
            $base = Str::slug(trim((string) $product->name_ro.' '.(string) $product->sku));
            $newSlug = $base;
            if (DB::table('products')->where('slug', $newSlug)->where('id', '!=', $product->id)->exists()) {
                $newSlug = $base.'-'.$product->id;
            }

            DB::table('product_slug_redirects')->updateOrInsert(
                ['old_slug' => $oldSlug],
                ['product_id' => $product->id, 'created_at' => $now, 'updated_at' => $now],
            );
            DB::table('products')->where('id', $product->id)->update(['slug' => $newSlug, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        // Content corrections and canonical redirects are intentionally retained.
    }
};
