<?php

return [
    'enabled' => env('CATALOG_AI_ENABLED', true),
    'auto_apply' => env('CATALOG_AI_AUTO_APPLY', true),
    'api_key' => env('OPENAI_API_KEY'),
    'base_url' => rtrim(env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
    'model' => env('CATALOG_AI_MODEL', 'gpt-5.6-terra'),
    'verifier_model' => env('CATALOG_AI_VERIFIER_MODEL', 'gpt-5.6-terra'),
    'reasoning_effort' => env('CATALOG_AI_REASONING_EFFORT', 'medium'),
    'minimum_confidence' => (float) env('CATALOG_AI_MIN_CONFIDENCE', 0.96),
    'deterministic_minimum_confidence' => (float) env('CATALOG_AI_DETERMINISTIC_MIN_CONFIDENCE', 0.97),
    'timeout' => (int) env('CATALOG_AI_TIMEOUT', 60),
    'candidate_limit' => (int) env('CATALOG_AI_CANDIDATE_LIMIT', 14),
    'taxonomy_version' => env('CATALOG_AI_TAXONOMY_VERSION', '2026-07-17.6'),
];
