<?php

namespace App\Services\Refunds;

use App\Enums\OrderStatus;
use App\Services\Ai\Classification;
use Carbon\CarbonInterface;

/**
 * Everything the policy engine needs, resolved from the database up front
 * so the engine itself stays pure and easy to test.
 */
final readonly class PolicyContext
{
    /**
     * @param  list<string>  $inputFlags  Flags raised by the input guard before any AI call.
     */
    public function __construct(
        public bool $orderVerified,
        public ?OrderStatus $orderStatus = null,
        public ?CarbonInterface $deliveredAt = null,
        public bool $itemIdentified = false,
        public bool $itemIsFinalSale = false,
        public ?float $refundAmount = null,
        public bool $itemAlreadyRefundedOrPending = false,
        public bool $itemPreviouslyDenied = false,
        public int $recentRefundCount = 0,
        public ?Classification $classification = null,
        public array $inputFlags = [],
    ) {}
}
