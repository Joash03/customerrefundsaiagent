<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Minimal client for OpenAI-compatible chat APIs (xAI Grok, Groq Llama, Ollama...).
 * Providers are tried in order until one returns JSON that passes validation.
 */
class LlmClient
{
    /**
     * @param  list<string>  $order
     * @param  array<string, array{base_url: ?string, api_key: ?string, model: ?string}>  $providers
     */
    public function __construct(
        private readonly array $order,
        private readonly array $providers,
        private readonly int $timeout,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('llm.order'), config('llm.providers'), config('llm.timeout'));
    }

    public function isConfigured(): bool
    {
        return $this->availableProviders() !== [];
    }

    /**
     * @param  callable(array<string, mixed>): bool  $isValid
     */
    public function chatJson(string $system, string $user, callable $isValid, float $temperature = 0.0): LlmResult
    {
        $failures = [];

        foreach ($this->availableProviders() as $name => $provider) {
            try {
                $response = Http::baseUrl($provider['base_url'])
                    ->withToken($provider['api_key'])
                    ->timeout($this->timeout)
                    ->acceptJson()
                    ->post('/chat/completions', [
                        'model' => $provider['model'],
                        'temperature' => $temperature,
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            ['role' => 'system', 'content' => $system],
                            ['role' => 'user', 'content' => $user],
                        ],
                    ])
                    ->throw();

                $data = json_decode((string) $response->json('choices.0.message.content'), true);

                if (is_array($data) && $isValid($data)) {
                    return new LlmResult($data, $name, $failures);
                }

                $failures[] = ['provider' => $name, 'error' => 'Response failed schema validation'];
            } catch (Throwable $e) {
                Log::warning('LLM provider failed', ['provider' => $name, 'error' => $e->getMessage()]);
                $failures[] = ['provider' => $name, 'error' => class_basename($e)];
            }
        }

        return new LlmResult(null, null, $failures);
    }

    /**
     * @return array<string, array{base_url: string, api_key: string, model: string}>
     */
    private function availableProviders(): array
    {
        $available = [];
        foreach ($this->order as $name) {
            $provider = $this->providers[$name] ?? null;
            if (! empty($provider['api_key']) && ! empty($provider['base_url']) && ! empty($provider['model'])) {
                $available[$name] = $provider;
            }
        }

        return $available;
    }
}
