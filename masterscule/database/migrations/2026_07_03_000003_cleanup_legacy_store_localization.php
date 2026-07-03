<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $phone = config('store.phone', '+373 60 000 000');
        $email = config('store.email', 'contact@masterscule.md');
        $domain = config('store.domain_label', 'MasterScule.md');
        $country = config('store.country', 'Moldova');
        $address = config('store.address', 'Chisinau, Republica Moldova');

        $replacements = [
            'MasterScule.ro' => $domain,
            'masterscule.ro' => strtolower($domain),
            'contact@masterscule.ro' => $email,
            '0724 123 456' => $phone,
            '0724123456' => config('store.phone_href', '+37360000000'),
            'Voluntari' => 'Chisinau',
            'Romania' => $country,
            'România' => $country,
            'RON' => config('store.currency', 'MDL'),
            'pret in RON' => 'pret in MDL',
            'pret în RON' => 'pret in MDL',
        ];

        foreach (['title', 'content', 'meta_title', 'meta_description'] as $column) {
            if (! DB::getSchemaBuilder()->hasColumn('pages', $column)) {
                continue;
            }

            DB::table('pages')->select('id', $column)->orderBy('id')->chunkById(100, function ($pages) use ($column, $replacements) {
                foreach ($pages as $page) {
                    DB::table('pages')
                        ->where('id', $page->id)
                        ->update([$column => $this->replaceLegacyText($page->{$column}, $replacements)]);
                }
            });
        }

        foreach (['description', 'description_ro', 'short_description', 'meta_title', 'meta_description'] as $column) {
            if (! DB::getSchemaBuilder()->hasColumn('products', $column)) {
                continue;
            }

            DB::table('products')->select('id', $column)->orderBy('id')->chunkById(100, function ($products) use ($column, $replacements) {
                foreach ($products as $product) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([$column => $this->replaceLegacyText($product->{$column}, $replacements)]);
                }
            });
        }

        DB::table('users')
            ->where('email', 'admin@masterscule.ro')
            ->update([
                'email' => 'admin@masterscule.md',
                'phone' => $phone,
                'country' => $country,
                'city' => 'Chisinau',
                'company_name' => config('store.legal_name', 'MasterScule Moldova'),
            ]);

        DB::table('users')
            ->where(function ($query) {
                $query->where('phone', 'like', '%0724%')
                    ->orWhere('city', 'Voluntari')
                    ->orWhere('country', 'Romania')
                    ->orWhere('email', 'like', '%@masterscule.ro');
            })
            ->update([
                'phone' => $phone,
                'country' => $country,
                'city' => 'Chisinau',
            ]);

        DB::table('addresses')
            ->where(function ($query) {
                $query->where('country', 'Romania')->orWhere('city', 'Voluntari');
            })
            ->update([
                'country' => $country,
                'city' => 'Chisinau',
                'address' => $address,
            ]);
    }

    public function down(): void
    {
        // This migration removes legacy public localization strings and is intentionally not reversible.
    }

    private function replaceLegacyText(?string $value, array $replacements): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $value);
    }
};
