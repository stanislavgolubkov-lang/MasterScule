<?php

namespace App\Services\ProductSources;

class SourceMatchConfidenceCalculator
{
    public function calculate(ProductSourceProductData $data, bool $official = true): int
    {
        $score = 0;
        $score += $data->search->exactSku ? 50 : 0;
        $score += $data->search->brand !== '' ? 20 : 0;
        $score += $official ? 20 : 0;
        $score += $data->images !== [] ? 5 : 0;
        $score += $data->specifications !== [] ? 5 : 0;
        $score += $data->breadcrumb !== [] ? 5 : 0;

        return min(100, $score);
    }
}
