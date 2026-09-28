<?php

namespace App\Services\Ai;

use App\Enums\RefundDecision;
use App\Services\Refunds\PolicyResult;

/**
 * Drafts the customer-facing reply for a decision that has already been made.
 * The model receives the decision and reasons only, never the raw customer message.
 */
class ReplyWriter
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
    You write short customer support replies for an online store's refund team.
    The refund decision has already been made and is final. You must not change it,
    soften it into a different outcome, or promise anything beyond it.
    Write 2 to 4 sentences in plain text: polite, clear and specific to the reasons given.
    Do not mention internal rule codes, AI, or automated systems.
    Return ONLY a JSON object: {"reply": "<your reply>"}
    PROMPT;

    /**
     * Phrases that would contradict a non-approved decision if the model drifted.
     */
    private const APPROVAL_LANGUAGE = '/\b((has been|is|was|have|we\'ve) approved|refund (has been|is being|will be) (issued|processed|sent)|you will (be refunded|receive a refund))\b/i';

    public function __construct(private readonly LlmClient $llm) {}

    /**
     * @return array{reply: string, provider: string, failures: list<array{provider: string, error: string}>}
     */
    public function write(PolicyResult $result, ?string $firstName, ?string $itemName, string $reference): array
    {
        $prompt = implode("\n", array_filter([
            'DECISION: '.strtoupper($result->decision->value),
            'REASONS: '.implode(' ', $result->ruleDescriptions()),
            $firstName ? "CUSTOMER FIRST NAME: {$firstName}" : null,
            $itemName ? "ITEM: {$itemName}" : null,
            "REFERENCE: {$reference}",
        ]));

        $llmResult = $this->llm->chatJson(
            self::SYSTEM_PROMPT,
            $prompt,
            fn (array $data) => $this->isValid($data, $result->decision),
            temperature: 0.3,
        );

        if ($llmResult->succeeded()) {
            return ['reply' => trim($llmResult->data['reply']), 'provider' => $llmResult->provider, 'failures' => $llmResult->failures];
        }

        return ['reply' => $this->template($result->decision, $firstName, $reference), 'provider' => 'template', 'failures' => $llmResult->failures];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isValid(array $data, RefundDecision $decision): bool
    {
        $reply = $data['reply'] ?? null;

        if (! is_string($reply) || trim($reply) === '' || mb_strlen($reply) > 1000) {
            return false;
        }

        return $decision === RefundDecision::Approved || ! preg_match(self::APPROVAL_LANGUAGE, $reply);
    }

    public function template(RefundDecision $decision, ?string $firstName, string $reference): string
    {
        $greeting = $firstName ? "Hi {$firstName}, " : 'Hi, ';

        return $greeting.match ($decision) {
            RefundDecision::Approved => "your refund request has been approved. The amount will be returned to your original payment method within 5-7 business days. Reference: {$reference}.",
            RefundDecision::Denied => "we're sorry, but this request isn't eligible for a refund under our refund policy. If you believe this is a mistake, please reply quoting reference {$reference}.",
            RefundDecision::Escalated => "thanks for your request. It needs a quick review by our support team, who will get back to you within 1-2 business days. Reference: {$reference}.",
        };
    }
}
