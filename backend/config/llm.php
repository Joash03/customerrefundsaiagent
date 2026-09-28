<?php

return [
    /*
     * Providers are tried in this order. Any provider without an API key is
     * skipped. Every provider must expose an OpenAI-compatible /chat/completions API.
     * Rate limits are per model, so a second Groq model doubles free-tier capacity.
     */
    'order' => array_filter(array_map('trim', explode(',', env('LLM_PROVIDER_ORDER', 'groq,groq_backup,grok')))),

    'timeout' => (int) env('LLM_TIMEOUT', 20),

    'providers' => [
        'groq' => [
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
            'options' => ['reasoning_effort' => env('GROQ_REASONING_EFFORT', 'medium')],
        ],
        'groq_backup' => [
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_BACKUP_MODEL', 'openai/gpt-oss-20b'),
            'options' => ['reasoning_effort' => env('GROQ_BACKUP_REASONING_EFFORT', 'low')],
        ],
        'grok' => [
            'base_url' => env('GROK_BASE_URL', 'https://api.x.ai/v1'),
            'api_key' => env('GROK_API_KEY'),
            'model' => env('GROK_MODEL', 'grok-3-mini'),
            'options' => [],
        ],
    ],
];
