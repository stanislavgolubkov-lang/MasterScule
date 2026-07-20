<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $clean = static function (?string $value): ?string {
            if ($value === null) {
                return null;
            }

            $value = preg_replace(
                '/\b(?:https?:\/\/)?(?:www\.)?tristool(?:\.md)?\b\s*(?:[-–—:|]\s*)?/iu',
                '',
                $value,
            ) ?? $value;

            return trim(preg_replace('/\s+/u', ' ', $value) ?? $value, " \t\n\r\0\x0B-–—:|");
        };

        DB::table('products')
            ->select(['id', 'name', 'name_ru', 'name_ro', 'meta_title'])
            ->where(function ($query): void {
                $query->where('name', 'like', '%tristool%')
                    ->orWhere('name_ru', 'like', '%tristool%')
                    ->orWhere('name_ro', 'like', '%tristool%')
                    ->orWhere('meta_title', 'like', '%tristool%');
            })
            ->chunkById(100, function ($products) use ($clean): void {
                foreach ($products as $product) {
                    DB::table('products')->where('id', $product->id)->update([
                        'name' => $clean($product->name),
                        'name_ru' => $clean($product->name_ru),
                        'name_ro' => $clean($product->name_ro),
                        'meta_title' => $clean($product->meta_title),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Source-store labels are intentionally not restored.
    }
};
