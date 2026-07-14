<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ParserAuditSources extends Command
{
    protected $signature = 'masterscule:parser-audit-sources {--mark-review}';

    protected $description = 'Audit official and fallback source provenance for parser products';

    public function handle(): int
    {
        $query = Product::whereNotNull('source_import_batch_id');
        $stats = [
            'parser_products' => (clone $query)->count(),
            'without_official_source' => (clone $query)->where(fn ($q) => $q->whereNull('source_url')->orWhere('source_url', ''))->count(),
            'fallback_source_used' => (clone $query)->where('fallback_source_used', true)->count(),
            'needs_source_review' => (clone $query)->where('needs_source_review', true)->count(),
        ];

        if ($this->option('mark-review')) {
            $stats['marked_for_review'] = Product::whereNotNull('source_import_batch_id')
                ->where(fn ($q) => $q->whereNull('source_url')->orWhere('fallback_source_used', true))
                ->update(['needs_source_review' => true]);
        }

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());

        return self::SUCCESS;
    }
}
