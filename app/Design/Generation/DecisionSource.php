<?php

namespace App\Design\Generation;

/**
 * The one seam between the generator and the model. The real implementation
 * calls Claude through the Anthropic SDK; tests bind a fake that returns canned
 * decisions, so the suite never touches the network.
 */
interface DecisionSource
{
    /**
     * @param  list<array{type: 'text', text: string, cacheControl?: array{type: 'ephemeral'}}>  $system  System prompt as content blocks, cache control already placed.
     * @param  array<string,mixed>  $schema  JSON schema the reply must satisfy.
     */
    public function decide(string $model, array $system, string $user, array $schema, string $effort, int $maxTokens): Outcome;
}
