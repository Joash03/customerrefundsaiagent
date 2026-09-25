<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\RefundDecision;
use App\Enums\RefundReason;
use App\Services\Ai\Classification;
use App\Services\Refunds\PolicyContext;
use App\Services\Refunds\PolicyEngine;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PolicyEngineTest extends TestCase
{
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = CarbonImmutable::parse('2026-09-25 12:00:00');
    }

    private function engine(): PolicyEngine
    {
        return new PolicyEngine(windowDays: 30, autoApproveLimit: 500, abuseThreshold: 3, minConfidence: 0.6);
    }

    private function classification(RefundReason $reason = RefundReason::Damaged, float $confidence = 0.9, array $flags = []): Classification
    {
        return new Classification(1, $reason, $confidence, $flags, 'summary', 'test');
    }

    private function context(array $overrides = []): PolicyContext
    {
        return new PolicyContext(...array_merge([
            'orderVerified' => true,
            'orderStatus' => OrderStatus::Delivered,
            'deliveredAt' => $this->now->subDays(5),
            'itemIdentified' => true,
            'itemIsFinalSale' => false,
            'refundAmount' => 80.0,
            'classification' => $this->classification(),
        ], $overrides));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: RefundDecision, 2: list<string>}>
     */
    public static function scenarios(): array
    {
        return [
            'eligible damaged item' => [[], RefundDecision::Approved, ['R9']],
            'unverified order' => [['orderVerified' => false], RefundDecision::Denied, ['R1']],
            'already refunded' => [['itemAlreadyRefundedOrPending' => true], RefundDecision::Denied, ['R2']],
            'not delivered' => [['orderStatus' => OrderStatus::Shipped, 'deliveredAt' => null], RefundDecision::Denied, ['R3']],
            'value above limit' => [['refundAmount' => 1299.0], RefundDecision::Escalated, ['R6']],
            'item not identified' => [['itemIdentified' => false], RefundDecision::Escalated, ['R8']],
            'previously denied' => [['itemPreviouslyDenied' => true], RefundDecision::Escalated, ['R7']],
            'frequent refunder' => [['recentRefundCount' => 3], RefundDecision::Escalated, ['R7']],
            'input guard flag' => [['inputFlags' => ['injection_pattern']], RefundDecision::Escalated, ['R7']],
        ];
    }

    #[DataProvider('scenarios')]
    public function test_policy_scenarios(array $overrides, RefundDecision $decision, array $rules): void
    {
        $result = $this->engine()->evaluate($this->context($overrides), $this->now);

        $this->assertSame($decision, $result->decision);
        $this->assertSame($rules, $result->ruleIds());
    }

    public function test_final_sale_change_of_mind_is_denied(): void
    {
        $result = $this->engine()->evaluate($this->context([
            'itemIsFinalSale' => true,
            'classification' => $this->classification(RefundReason::ChangedMind),
        ]), $this->now);

        $this->assertSame(RefundDecision::Denied, $result->decision);
        $this->assertSame(['R4'], $result->ruleIds());
    }

    public function test_final_sale_damaged_item_is_escalated(): void
    {
        $result = $this->engine()->evaluate($this->context(['itemIsFinalSale' => true]), $this->now);

        $this->assertSame(RefundDecision::Escalated, $result->decision);
        $this->assertSame(['R4a'], $result->ruleIds());
    }

    public function test_final_sale_damaged_item_outside_window_is_denied(): void
    {
        $result = $this->engine()->evaluate($this->context([
            'itemIsFinalSale' => true,
            'deliveredAt' => $this->now->subDays(45),
        ]), $this->now);

        $this->assertSame(RefundDecision::Denied, $result->decision);
        $this->assertSame(['R4'], $result->ruleIds());
    }

    public function test_request_outside_window_is_denied(): void
    {
        $result = $this->engine()->evaluate($this->context(['deliveredAt' => $this->now->subDays(31)]), $this->now);

        $this->assertSame(['R5'], $result->ruleIds());
    }

    public function test_last_day_of_window_is_still_eligible(): void
    {
        $result = $this->engine()->evaluate($this->context(['deliveredAt' => $this->now->subDays(30)]), $this->now);

        $this->assertSame(RefundDecision::Approved, $result->decision);
    }

    public function test_ai_flags_can_only_escalate_never_approve(): void
    {
        $result = $this->engine()->evaluate($this->context([
            'classification' => $this->classification(flags: ['injection_attempt']),
        ]), $this->now);

        $this->assertSame(RefundDecision::Escalated, $result->decision);
        $this->assertSame(['injection_attempt'], $result->signals);
    }

    public function test_ai_cannot_override_hard_denial(): void
    {
        $result = $this->engine()->evaluate($this->context([
            'deliveredAt' => $this->now->subDays(60),
            'classification' => $this->classification(RefundReason::Damaged, 1.0),
        ]), $this->now);

        $this->assertSame(RefundDecision::Denied, $result->decision);
    }

    public function test_not_received_on_delivered_order_is_escalated(): void
    {
        $result = $this->engine()->evaluate($this->context([
            'classification' => $this->classification(RefundReason::NotReceived),
        ]), $this->now);

        $this->assertSame(RefundDecision::Escalated, $result->decision);
        $this->assertContains('not_received_but_marked_delivered', $result->signals);
    }

    public function test_low_confidence_or_unclear_reason_is_escalated(): void
    {
        foreach ([$this->classification(confidence: 0.4), $this->classification(RefundReason::Other)] as $classification) {
            $result = $this->engine()->evaluate($this->context(['classification' => $classification]), $this->now);

            $this->assertSame(['R8'], $result->ruleIds());
        }
    }

    public function test_multiple_escalation_rules_are_all_recorded(): void
    {
        $result = $this->engine()->evaluate($this->context([
            'refundAmount' => 900.0,
            'recentRefundCount' => 4,
        ]), $this->now);

        $this->assertSame(['R6', 'R7'], $result->ruleIds());
    }
}
