<?php

namespace App\Services\Ai;

use App\Enums\RefundReason;

final readonly class Classification
{
    /**
     * @param  list<string>  $flags
     */
    public function __construct(
        public ?int $itemId,
        public RefundReason $reason,
        public float $confidence,
        public array $flags,
        public string $summary,
        public string $provider,
    ) {}
}
