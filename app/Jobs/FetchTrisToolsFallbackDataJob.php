<?php

namespace App\Jobs;

class FetchTrisToolsFallbackDataJob extends ParseSingleSkuJob
{
    public function __construct(int $itemId, bool $processImages = false, bool $createDraft = false)
    {
        parent::__construct($itemId, $processImages, $createDraft, forceFallback: true);
    }
}
