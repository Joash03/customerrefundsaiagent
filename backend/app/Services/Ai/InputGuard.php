<?php

namespace App\Services\Ai;

/**
 * First line of defence, applied to every message before any text reaches a model.
 * It normalises the message and flags common prompt-injection and pressure phrasing.
 * A flag never blocks the request; it routes it to human review.
 */
class InputGuard
{
    private const INJECTION_PATTERNS = [
        'instruction_override' => '/\b(ignore|disregard|forget|override)\b.{0,40}\b(instructions?|rules?|polic(y|ies)|prompts?|guidelines)\b/i',
        'role_change' => '/\b(you are now|act as|pretend (to be|you are)|from now on you)\b/i',
        'role_label' => '/(^|[\n.!?]\s*)(system|assistant|developer|admin)\s*:/i',
        'prompt_probe' => '/\b(system|developer|hidden)\s+(prompt|message|instructions?)\b/i',
        'forced_outcome' => '/\b(approve|authori[sz]e|accept)\b.{0,30}\b(regardless|anyway|no matter|without (review|checking)|every|all)\b/i',
        'markup_injection' => '/<\/?\s*(system|assistant|user|customer_message|instructions?)\s*>/i',
        'structured_payload' => '/["\']?(decision|reason|confidence|flags)["\']?\s*:\s*["\'\[{0-9]/i',
    ];

    private const PRESSURE_PATTERNS = [
        'claimed_authority' => '/\b(pre-?approved|already (been )?approved|i\'?m (a |the )?(manager|supervisor|admin|staff)|i am (a |the )?(manager|supervisor|admin|staff)|work (for|at) your company)\b/i',
    ];

    public function screen(string $message): ScreenedInput
    {
        $clean = preg_replace('/[^\P{C}\n\t]/u', '', $message) ?? '';
        $clean = trim(preg_replace("/\n{3,}/", "\n\n", $clean) ?? '');

        $injection = $this->matches(self::INJECTION_PATTERNS, $clean);
        $pressure = $this->matches(self::PRESSURE_PATTERNS, $clean);

        // Neutralise our own prompt delimiters so the message cannot close its data block.
        $clean = preg_replace('/<\/?\s*customer_message\s*>/i', '', $clean) ?? '';

        return new ScreenedInput(
            message: $clean,
            flags: array_values(array_filter([
                $injection !== [] ? 'injection_pattern' : null,
                $pressure !== [] ? 'policy_pressure' : null,
            ])),
            matchedPatterns: [...$injection, ...$pressure],
        );
    }

    /**
     * @param  array<string, string>  $patterns
     * @return list<string>
     */
    private function matches(array $patterns, string $text): array
    {
        return array_keys(array_filter($patterns, fn (string $pattern) => preg_match($pattern, $text) === 1));
    }
}
