<?php

return [
    'enabled' => env('PRODUCT_PARSER_ENABLED', true),
    'max_sku_per_batch' => (int) env('PRODUCT_PARSER_MAX_SKU_PER_BATCH', 100),
    'max_images_per_product' => (int) env('PRODUCT_PARSER_MAX_IMAGES_PER_PRODUCT', 4),
    'min_confidence_score' => (int) env('PRODUCT_PARSER_MIN_CONFIDENCE_SCORE', 70),
    'image_size' => (int) env('PRODUCT_PARSER_IMAGE_SIZE', 1200),
    'thumb_size' => (int) env('PRODUCT_PARSER_THUMB_SIZE', 300),
    'webp_quality' => (int) env('PRODUCT_PARSER_WEBP_QUALITY', 88),
    'allowed_domains' => [
        'tristool.md',
        'kingtony.com',
        'mighty-seven.com',
        'm7tools.com',
    ],
    'blocked_domains' => [],
    'official_source_priority' => true,
    'watermark' => [
        'enabled' => env('PRODUCT_PARSER_WATERMARK_ENABLED', true),
        'file' => env('PRODUCT_PARSER_WATERMARK_FILE', '/images/brand/master-scule-logo.png'),
        'position' => env('PRODUCT_PARSER_WATERMARK_POSITION', 'bottom_right'),
        'opacity' => (int) env('PRODUCT_PARSER_WATERMARK_OPACITY', 14),
        'size_percent' => (int) env('PRODUCT_PARSER_WATERMARK_SIZE_PERCENT', 18),
    ],
];
