<?php

namespace App\Jobs;

class FindOfficialProductSourceJob extends ParseSingleSkuJob
{
    public function __construct(int $itemId, bool $processImages = false, bool $createDraft = false)
    {
        parent::__construct($itemId, $processImages, $createDraft, officialOnly: true);
    }
}
