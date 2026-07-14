<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class AuditProducts extends Command
{
    protected $signature = 'masterscule:audit-products';

    protected $description = 'Show a read-only catalog integrity summary';

    public function handle(): int
    {
        $stats = [
            'total_products' => Product::count(),
            'published_products' => Product::where('status', 'published')->count(),
            'draft_products' => Product::where('status', 'draft')->count(),
            'active_unpublished' => Product::where('is_active', true)->where('status', '!=', 'published')->count(),
            'unapproved_active' => Product::where('is_active', true)->where('approval_status', '!=', 'approved')->count(),
            'duplicate_skus' => Product::select('sku')->groupBy('sku')->havingRaw('COUNT(*) > 1')->get()->count(),
            'missing_category' => Product::whereNull('category_id')->count(),
            'non_positive_price' => Product::where('price', '<=', 0)->count(),
        ];

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());

        return self::SUCCESS;
    }
}
