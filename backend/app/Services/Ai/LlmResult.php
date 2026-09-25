<?php

namespace App\Services\Ai;

final readonly class LlmResult
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  list<array{provider: string, error: string}>  $failures
     */
    public function __construct(
        public ?array $data,
        public ?string $provider,
        public array $failures,
    ) {}

    public function succeeded(): bool
    {
        return $this->data !== null;
    }
}
