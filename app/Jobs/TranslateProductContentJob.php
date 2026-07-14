<?php

namespace App\Jobs;

use App\Models\ProductParserItem;
use App\Services\ProductParserContentBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TranslateProductContentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $itemId) {}

    public function handle(ProductParserContentBuilder $builder): void
    {
        $item = ProductParserItem::with('category')->find($this->itemId);
        if (! $item) {
            return;
        }

        $content = $builder->build(
            $item->sku,
            (string) ($item->raw_name ?: $item->found_title ?: $item->sku),
            $item->brand,
            $item->detected_group,
            [
                'category_slug' => $item->category?->slug,
                'category_name_ru' => $item->category?->name,
                'category_name_ro' => $item->category?->name_ro,
            ],
        );
        $content = $builder->mergeOfficialContent($content, $item->found_title, $item->found_description, $item->sku, $item->brand);

        $item->forceFill($content + ['translation_source_type' => $content['translation_source_type'] ?? 'generated_pending_review'])->save();
    }
}
