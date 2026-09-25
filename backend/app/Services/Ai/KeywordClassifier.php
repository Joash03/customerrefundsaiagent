<?php

namespace App\Services\Ai;

use App\Enums\RefundReason;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

/**
 * Deterministic fallback used when no LLM provider is configured or reachable,
 * so the system keeps working (conservatively) without an API key.
 */
class KeywordClassifier
{
    private const REASON_KEYWORDS = [
        'damaged' => ['damaged', 'broken', 'cracked', 'defective', 'faulty', 'torn', 'scratched', 'dented', 'not working', 'stopped working', 'smashed'],
        'wrong_item' => ['wrong item', 'wrong size', 'wrong colour', 'wrong color', 'incorrect', 'not what i ordered', 'different item', 'sent the wrong'],
        'not_received' => ['never arrived', 'not received', "didn't receive", 'did not receive', 'never received', 'missing package', 'lost package'],
        'changed_mind' => ['changed my mind', "don't want", 'do not want', "don't need", 'no longer need', "doesn't fit", 'does not fit', 'not needed'],
    ];

    /**
     * @param  list<string>  $inputFlags
     */
    public function classify(string $message, Order $order, array $inputFlags): Classification
    {
        $text = Str::lower($message);

        $reasons = [];
        foreach (self::REASON_KEYWORDS as $reason => $keywords) {
            if (Str::contains($text, $keywords)) {
                $reasons[] = RefundReason::from($reason);
            }
        }

        $reason = count($reasons) === 1 ? $reasons[0] : RefundReason::Other;

        return new Classification(
            itemId: $this->matchItem($text, $order)?->id,
            reason: $reason,
            confidence: count($reasons) === 1 ? 0.7 : 0.3,
            flags: $inputFlags === [] ? [] : ['injection_attempt'],
            summary: $reason === RefundReason::Other
                ? 'Keyword fallback could not determine a single refund reason.'
                : "Keyword fallback matched reason: {$reason->value}.",
            provider: 'keyword-fallback',
        );
    }

    private function matchItem(string $text, Order $order): ?OrderItem
    {
        if ($order->items->count() === 1) {
            return $order->items->first();
        }

        $matches = $order->items->filter(function (OrderItem $item) use ($text) {
            $words = array_filter(explode(' ', Str::lower($item->product_name)), fn (string $word) => strlen($word) > 3);

            return Str::contains($text, $words);
        });

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
