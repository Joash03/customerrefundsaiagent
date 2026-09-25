<?php

namespace App\Services\Refunds;

use App\Enums\PolicyRule;
use App\Enums\RefundDecision;

final readonly class PolicyResult
{
    /**
     * @param  list<PolicyRule>  $rules  Rules that produced the decision.
     * @param  list<string>  $signals  Risk signals behind an R7 escalation.
     */
    public function __construct(
        public RefundDecision $decision,
        public array $rules,
        public array $signals = [],
    ) {}

    /**
     * @return list<string>
     */
    public function ruleIds(): array
    {
        return array_map(fn (PolicyRule $rule) => $rule->value, $this->rules);
    }

    /**
     * @return list<string>
     */
    public function ruleDescriptions(): array
    {
        return array_map(fn (PolicyRule $rule) => $rule->description(), $this->rules);
    }
}
