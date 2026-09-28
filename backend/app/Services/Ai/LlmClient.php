<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Minimal client for OpenAI-compatible chat APIs (Groq, xAI Grok, Ollama...).
 * Providers are tried in order until one returns a usable response.
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
                $response = $this->post($provider, [
                    'temperature' => $temperature,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

                $data = json_decode((string) $response->json('choices.0.message.content'), true);

                if (is_array($data) && $isValid($data)) {
                    return new LlmResult($data, $name, $failures);
                }

                $failures[] = ['provider' => $name, 'error' => 'Response failed schema validation'];
            } catch (Throwable $e) {
                $failures[] = $this->failure($name, $e);
            }
        }

        return new LlmResult(null, null, $failures);
    }

    /**
     * One step of a tool-using conversation. The result data is the assistant message
     * (content and/or tool_calls) exactly as the provider returned it.
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     */
    public function chatWithTools(array $messages, array $tools, float $temperature = 0.2): LlmResult
    {
        $failures = [];

        foreach ($this->availableProviders() as $name => $provider) {
            try {
                $message = $this->post($provider, [
                    'temperature' => $temperature,
                    'messages' => $messages,
                    'tools' => $tools,
                    'tool_choice' => 'auto',
                    // Replies are short; the cap stops runaway generations and keeps within rate limits.
                    'max_tokens' => 700,
                ])->json('choices.0.message');

                if (is_array($message) && (filled($message['content'] ?? null) || filled($message['tool_calls'] ?? null))) {
                    return new LlmResult($message, $name, $failures);
                }

                $failures[] = ['provider' => $name, 'error' => 'Empty response'];
            } catch (Throwable $e) {
                $failures[] = $this->failure($name, $e);
            }
        }

        return new LlmResult(null, null, $failures);
    }

    /**
     * @param  array{base_url: string, api_key: string, model: string, options?: array<string, mixed>}  $provider
     * @param  array<string, mixed>  $payload
     */
    private function post(array $provider, array $payload): Response
    {
        return Http::baseUrl($provider['base_url'])
            ->withToken($provider['api_key'])
            ->timeout($this->timeout)
            // Free tiers rate-limit per minute: wait as long as the provider asks (capped), retry once,
            // then fall through to the next provider.
            ->retry(
                2,
                fn (int $attempt, Throwable $e) => $e instanceof RequestException
                    ? min((int) $e->response->header('retry-after') ?: 2, 8) * 1000
                    : 1000,
                fn (Throwable $e) => $e instanceof RequestException && $e->response->status() === 429,
            )
            ->acceptJson()
            ->post('/chat/completions', ['model' => $provider['model'], ...($provider['options'] ?? []), ...$payload])
            ->throw();
    }

    /**
     * @return array{provider: string, error: string}
     */
    private function failure(string $provider, Throwable $e): array
    {
        Log::warning('LLM provider failed', ['provider' => $provider, 'error' => $e->getMessage()]);

        return ['provider' => $provider, 'error' => class_basename($e)];
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
