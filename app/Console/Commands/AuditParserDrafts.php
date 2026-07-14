<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductParserItem;
use Illuminate\Console\Command;

class AuditParserDrafts extends Command
{
    protected $signature = 'masterscule:audit-parser-drafts';

    protected $description = 'Audit parser drafts and unsafe parser publication states';

    public function handle(): int
    {
        $stats = [
            'parser_items' => ProductParserItem::count(),
            'draft_products' => Product::whereNotNull('source_parser_item_id')->where('status', 'draft')->count(),
            'published_parser_products' => Product::whereNotNull('source_parser_item_id')->where('status', 'published')->count(),
            'active_unapproved_parser_products' => Product::whereNotNull('source_parser_item_id')->where('is_active', true)->where('approval_status', '!=', 'approved')->count(),
            'items_needing_category_review' => ProductParserItem::where('needs_category_review', true)->count(),
            'items_needing_image_review' => ProductParserItem::where('needs_image_review', true)->count(),
            'items_needing_translation_review' => ProductParserItem::where('needs_translation_review', true)->count(),
            'duplicate_item_skus' => ProductParserItem::select('sku')->groupBy('sku')->havingRaw('COUNT(*) > 1')->get()->count(),
        ];

        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all());

        return self::SUCCESS;
    }
}
