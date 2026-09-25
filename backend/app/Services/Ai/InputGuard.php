<?php

namespace App\Services\Ai;

/**
 * First line of defence, applied before any text reaches a model.
 * It normalises the message and flags common prompt-injection phrasing.
 * A flag never blocks the request; it routes it to human review.
 */
class InputGuard
{
    private const INJECTION_PATTERNS = [
        'instruction_override' => '/\b(ignore|disregard|forget|override)\b.{0,40}\b(instructions?|rules?|polic(y|ies)|prompts?|guidelines)\b/i',
        'role_change' => '/\b(you are now|act as|pretend (to be|you are)|from now on you)\b/i',
        'prompt_probe' => '/\b(system|developer|hidden)\s+(prompt|message|instructions?)\b/i',
        'forced_outcome' => '/\b(approve|authori[sz]e|accept)\b.{0,30}\b(regardless|anyway|no matter|without (review|checking))\b/i',
        'markup_injection' => '/<\/?\s*(system|assistant|user|customer_message|instructions?)\s*>/i',
        'structured_payload' => '/["\']?(decision|reason|confidence|flags)["\']?\s*:\s*["\'\[{0-9]/i',
    ];

    public function screen(string $message): ScreenedInput
    {
        $clean = preg_replace('/[^\P{C}\n\t]/u', '', $message) ?? '';
        $clean = trim(preg_replace("/\n{3,}/", "\n\n", $clean) ?? '');

        $matched = [];
        foreach (self::INJECTION_PATTERNS as $name => $pattern) {
            if (preg_match($pattern, $clean)) {
                $matched[] = $name;
            }
        }

        // Neutralise our own prompt delimiters so the message cannot close its data block.
        $clean = preg_replace('/<\/?\s*customer_message\s*>/i', '', $clean) ?? '';

        return new ScreenedInput(
            message: $clean,
            flags: $matched === [] ? [] : ['injection_pattern'],
            matchedPatterns: $matched,
        );
    }
}
