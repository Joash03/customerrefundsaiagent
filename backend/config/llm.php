<?php

return [
    /*
     * Providers are tried in this order. Any provider without an API key is
     * skipped, and if none respond the built-in keyword classifier is used.
     * Every provider must expose an OpenAI-compatible /chat/completions API.
     */
    'order' => array_filter(array_map('trim', explode(',', env('LLM_PROVIDER_ORDER', 'grok,llama')))),

    'timeout' => (int) env('LLM_TIMEOUT', 20),

    'providers' => [
        'grok' => [
            'base_url' => env('GROK_BASE_URL', 'https://api.x.ai/v1'),
            'api_key' => env('GROK_API_KEY'),
            'model' => env('GROK_MODEL', 'grok-3-mini'),
        ],
        'llama' => [
            'base_url' => env('LLAMA_BASE_URL', 'https://api.groq.com/openai/v1'),
            'api_key' => env('LLAMA_API_KEY'),
            'model' => env('LLAMA_MODEL', 'llama-3.3-70b-versatile'),
        ],
    ],
];
