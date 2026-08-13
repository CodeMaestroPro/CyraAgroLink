<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | CyraAI provider
    |--------------------------------------------------------------------------
    |
    | "local" uses the built-in farming knowledge engine (works offline).
    | "openai" calls an OpenAI-compatible Chat Completions API when a key is set,
    | and falls back to local if the request fails.
    |
    */

    'provider' => env('CYRA_AI_PROVIDER', 'local'),

    'api_key' => env('CYRA_AI_API_KEY'),

    'base_url' => rtrim((string) env('CYRA_AI_BASE_URL', 'https://api.openai.com/v1'), '/'),

    'model' => env('CYRA_AI_MODEL', 'gpt-4o-mini'),

    'timeout' => (int) env('CYRA_AI_TIMEOUT', 35),

    'max_tokens' => (int) env('CYRA_AI_MAX_TOKENS', 700),

];
