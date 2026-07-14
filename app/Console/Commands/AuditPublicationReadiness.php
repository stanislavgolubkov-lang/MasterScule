<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Catalog\ProductPublicationGuard;
use Illuminate\Console\Command;

class AuditPublicationReadiness extends Command
{
    protected $signature = 'masterscule:audit-publication-readiness {--unpublish-invalid} {--force}';

    protected $description = 'Evaluate every product through the publication guard';

    public function handle(ProductPublicationGuard $guard): int
    {
        $unpublish = (bool) $this->option('unpublish-invalid');
        if ($unpublish && ! $this->option('force') && ! $this->confirm('Move invalid published products to draft?')) {
            return self::FAILURE;
        }

        $stats = [
            'total_products' => 0,
            'published_products' => 0,
            'draft_products' => 0,
            'ready_to_publish' => 0,
            'blocked_from_publish' => 0,
            'published_but_invalid' => 0,
            'unpublished' => 0,
        ];
        $reasons = [];

        Product::with(['brand', 'category'])->orderBy('id')->chunkById(300, function ($products) use ($guard, $unpublish, &$stats, &$reasons) {
            foreach ($products as $product) {
                $stats['total_products']++;
                $published = $product->status === 'published' || $product->is_active;
                $stats[$published ? 'published_products' : 'draft_products']++;
                $result = $guard->evaluate($product, ! $published);

                if ($result['allowed']) {
                    $stats['ready_to_publish']++;

                    continue;
                }

                $stats['blocked_from_publish']++;
                foreach ($result['error_codes'] as $code) {
                    $reasons[$code] = ($reasons[$code] ?? 0) + 1;
                }

                if ($published) {
                    $stats['published_but_invalid']++;
                    if ($unpublish) {
                        $guard->unpublish($product);
                        $stats['unpublished']++;
                    }
                }
            }
        });

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());
        if ($reasons !== []) {
            arsort($reasons);
            $this->table(['Blocked reason', 'Count'], collect($reasons)->map(fn ($count, $reason) => [$reason, $count])->values()->all());
        }

        return self::SUCCESS;
    }
}
