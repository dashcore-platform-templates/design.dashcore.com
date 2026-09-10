<?php

namespace Tests\Support;

use App\Design\Generation\DecisionSource;
use App\Design\Generation\Outcome;

/**
 * Returns canned decisions and records what it was asked, so tests can assert
 * on the prompt without touching the network.
 */
final class FakeDecisionSource implements DecisionSource
{
    /** @var array<string,mixed>|null */
    public ?array $lastSystem = null;

    public ?string $lastUser = null;

    /** @var array<string,mixed>|null */
    public ?array $lastSchema = null;

    public ?string $lastModel = null;

    public int $calls = 0;

    /** @param  array<string,mixed>  $decisions */
    public function __construct(private array $decisions) {}

    public function decide(string $model, array $system, string $user, array $schema, string $effort, int $maxTokens): Outcome
    {
        $this->calls++;
        $this->lastModel = $model;
        $this->lastSystem = $system;
        $this->lastUser = $user;
        $this->lastSchema = $schema;

        return new Outcome(
            decisions: $this->decisions,
            raw: json_encode($this->decisions, JSON_THROW_ON_ERROR),
            model: $model,
            usage: ['input_tokens' => 9000, 'output_tokens' => 450, 'cache_read_input_tokens' => 8500],
            stopReason: 'end_turn',
        );
    }

    /** @return array<string,mixed> */
    public static function sample(): array
    {
        return [
            'accent' => ['value' => '#1d6f8a', 'why' => 'A steady blue-green for a maritime client.'],
            'temperature' => ['value' => 'cool', 'why' => 'Software used at a desk.'],
            'pairing' => ['value' => 3, 'why' => 'Numbers everywhere; the mono face carries them.'],
            'radius' => ['value' => 'soft', 'why' => 'No reason to go sharp or round.'],
            'density' => ['value' => 'compact', 'why' => 'Dispatchers use it all shift.'],
            'motif' => ['value' => 'accent-bar', 'why' => 'A sidebar tool with a saturated accent.'],
            'copy' => ['eyebrow' => 'Tidewater', 'headline' => 'Every berth, every hour.', 'tagline' => 'Sign in to see what is docked, due and delayed.', 'footer' => null, 'why' => 'Names what a dispatcher opens it to see.'],
        ];
    }
}
