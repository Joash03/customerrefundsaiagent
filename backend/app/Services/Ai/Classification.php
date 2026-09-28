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
        public ?string $productMentioned = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'reason' => $this->reason->value,
            'confidence' => $this->confidence,
            'flags' => $this->flags,
            'summary' => $this->summary,
            'provider' => $this->provider,
            'product_mentioned' => $this->productMentioned,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            itemId: $data['item_id'],
            reason: RefundReason::from($data['reason']),
            confidence: (float) $data['confidence'],
            flags: $data['flags'],
            summary: $data['summary'],
            provider: $data['provider'],
            productMentioned: $data['product_mentioned'] ?? null,
        );
    }
}
