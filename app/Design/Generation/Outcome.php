<?php

namespace App\Design\Generation;

/**
 * What came back from the model: the decoded decisions, the raw text for the
 * debugging log, and enough usage detail to see whether the cache is working.
 */
final class Outcome
{
    /**
     * @param  array<string,mixed>  $decisions
     * @param  array<string,int|null>  $usage
     */
    public function __construct(
        public array $decisions,
        public string $raw,
        public string $model,
        public array $usage = [],
        public ?string $stopReason = null,
    ) {}
}
