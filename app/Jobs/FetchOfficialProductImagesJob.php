<?php

namespace App\Jobs;

class FetchOfficialProductImagesJob extends FindOfficialProductSourceJob
{
    public function __construct(int $itemId)
    {
        parent::__construct($itemId, processImages: true);
    }
}
