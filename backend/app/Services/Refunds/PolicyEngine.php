<?php

namespace App\Services\Refunds;

use App\Enums\OrderStatus;
use App\Enums\PolicyRule;
use App\Enums\RefundDecision;
use App\Enums\RefundReason;
use Carbon\CarbonInterface;

/**
 * Deterministic refund policy. The AI layer only supplies a classification;
 * it can add escalation signals but can never produce an approval on its own.
 */
class PolicyEngine
{
    public function __construct(
        private readonly int $windowDays,
        private readonly float $autoApproveLimit,
        private readonly int $abuseThreshold,
        private readonly float $minConfidence,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            windowDays: config('refunds.window_days'),
            autoApproveLimit: config('refunds.auto_approve_limit'),
            abuseThreshold: config('refunds.abuse_threshold'),
            minConfidence: config('refunds.min_confidence'),
        );
    }

    public function evaluate(PolicyContext $context, CarbonInterface $now): PolicyResult
    {
        if (! $context->orderVerified) {
            return $this->deny(PolicyRule::OwnershipUnverified);
        }

        if ($context->itemAlreadyRefundedOrPending) {
            return $this->deny(PolicyRule::AlreadyRefundedOrPending);
        }

        if ($context->orderStatus !== OrderStatus::Delivered || $context->deliveredAt === null) {
            return $this->deny(PolicyRule::NotDelivered);
        }

        $reason = $context->classification?->reason ?? RefundReason::Other;
        $withinWindow = $context->deliveredAt->copy()->addDays($this->windowDays)->gte($now);
        $escalations = [];

        if ($context->itemIsFinalSale) {
            if (! ($reason->isProductFault() && $withinWindow)) {
                return $this->deny(PolicyRule::FinalSale);
            }
            $escalations[] = PolicyRule::FinalSaleProductFault;
        }

        if (! $withinWindow) {
            return $this->deny(PolicyRule::OutsideRefundWindow);
        }

        if ($context->refundAmount !== null && $context->refundAmount > $this->autoApproveLimit) {
            $escalations[] = PolicyRule::HighValue;
        }

        $signals = $this->riskSignals($context, $reason);
        if ($signals !== []) {
            $escalations[] = PolicyRule::Suspicious;
        }

        $unclassifiable = $reason === RefundReason::Other;
        $lowConfidence = ($context->classification?->confidence ?? 0) < $this->minConfidence;

        if (! $context->itemIdentified || $unclassifiable || $lowConfidence) {
            $escalations[] = PolicyRule::LowConfidence;
        }

        if ($escalations !== []) {
            return new PolicyResult(RefundDecision::Escalated, $escalations, $signals);
        }

        return new PolicyResult(RefundDecision::Approved, [PolicyRule::Eligible]);
    }

    /**
     * @return list<string>
     */
    private function riskSignals(PolicyContext $context, RefundReason $reason): array
    {
        $signals = array_merge($context->inputFlags, $context->classification?->flags ?? []);

        if ($reason === RefundReason::NotReceived) {
            $signals[] = 'not_received_but_marked_delivered';
        }

        if ($context->recentRefundCount >= $this->abuseThreshold) {
            $signals[] = 'frequent_refunds';
        }

        if ($context->itemPreviouslyDenied) {
            $signals[] = 'previously_denied';
        }

        return array_values(array_unique($signals));
    }

    private function deny(PolicyRule $rule): PolicyResult
    {
        return new PolicyResult(RefundDecision::Denied, [$rule]);
    }
}
