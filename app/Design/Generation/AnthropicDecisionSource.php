<?php

namespace App\Design\Generation;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\APITimeoutException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\Messages\OutputConfig\Effort;
use Anthropic\Messages\TextBlock;
use JsonException;
use ValueError;

/**
 * The real DecisionSource: one Messages API call through the Anthropic SDK with
 * the standard as a cached system prompt and the decisions schema as the
 * required output shape. Every failure becomes a GenerationFailed with a
 * message fit for the form.
 */
final class AnthropicDecisionSource implements DecisionSource
{
    private ?Client $client = null;

    public function decide(string $model, array $system, string $user, array $schema, string $effort, int $maxTokens): Outcome
    {
        try {
            $effortLevel = Effort::from($effort);
        } catch (ValueError) {
            throw new GenerationFailed("DESIGN_EFFORT must be low, medium, high, xhigh or max; got '{$effort}'.");
        }

        try {
            $message = $this->client()->messages->create(
                model: $model,
                maxTokens: $maxTokens,
                system: $system,
                messages: [['role' => 'user', 'content' => $user]],
                outputConfig: [
                    'effort' => $effortLevel,
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
            );
        } catch (AuthenticationException) {
            throw new GenerationFailed('The API key was rejected. Set ANTHROPIC_API_KEY in .env and try again.');
        } catch (RateLimitException) {
            throw new GenerationFailed('The API is rate limiting this key. Wait a minute and try again.');
        } catch (APITimeoutException) {
            throw new GenerationFailed('The model did not answer within '.config('design.timeout').' seconds.');
        } catch (APIConnectionException $e) {
            throw new GenerationFailed('Could not reach the API: '.$e->getMessage());
        } catch (APIStatusException $e) {
            throw new GenerationFailed('The API returned an error ('.($e->type->value ?? $e->getCode()).'): '.$e->getMessage());
        }

        if ($message->stopReason === 'refusal') {
            $why = $message->stopDetails->explanation ?? 'no explanation given';

            throw new GenerationFailed("The model declined this brief ({$why}). Reword it and try again.");
        }

        if ($message->stopReason === 'max_tokens') {
            throw new GenerationFailed('The answer was cut off before it finished. Raise DESIGN_MAX_TOKENS and try again.');
        }

        $raw = '';
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                $raw = $block->text;
                break;
            }
        }

        try {
            $decisions = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new GenerationFailed('The model answered but not with valid JSON. The raw answer is in storage/app/generations if logging is on.');
        }

        if (! is_array($decisions)) {
            throw new GenerationFailed('The model answered with something other than the seven decisions.');
        }

        return new Outcome(
            decisions: $decisions,
            raw: $raw,
            model: $message->model,
            usage: [
                'input_tokens' => $message->usage->inputTokens,
                'output_tokens' => $message->usage->outputTokens,
                'cache_read_input_tokens' => $message->usage->cacheReadInputTokens,
                'cache_creation_input_tokens' => $message->usage->cacheCreationInputTokens,
            ],
            stopReason: $message->stopReason,
        );
    }

    private function client(): Client
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }

        $key = (string) config('design.api_key');
        if ($key === '') {
            throw new GenerationFailed('No API key. Set ANTHROPIC_API_KEY in .env to use the generator.');
        }

        return $this->client = new Client(
            apiKey: $key,
            requestOptions: ['timeout' => (float) config('design.timeout'), 'maxRetries' => 2],
        );
    }
}
