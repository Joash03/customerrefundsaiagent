<?php

namespace App\Services\Ai;

final readonly class ScreenedInput
{
    /**
     * @param  list<string>  $flags
     * @param  list<string>  $matchedPatterns
     */
    public function __construct(
        public string $message,
        public array $flags,
        public array $matchedPatterns,
    ) {}
}
