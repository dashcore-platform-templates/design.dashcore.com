<?php

namespace App\Design\Generation;

use App\Design\Design;
use App\Design\Standard;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Brief in, Design out. Assembles the system prompt from the standard and the
 * generator instructions, asks the model for the seven decisions in the
 * schema's shape, runs the rules over the answer, and returns an unsaved Design
 * carrying a record of how it was made. The caller decides whether to save.
 */
final class DesignGenerator
{
    public function __construct(private DecisionSource $source) {}

    /**
     * @param  array<string,mixed>  $brief
     */
    public function generate(array $brief, ?string $slug = null): Design
    {
        $brief = self::normaliseBrief($brief);
        $slug ??= self::slugFor($brief['client']);
        $system = self::systemBlocks();

        set_time_limit(max(120, (int) config('design.timeout') + 30));
        $started = microtime(true);

        $outcome = $this->source->decide(
            model: (string) config('design.model'),
            system: $system,
            user: self::userMessage($brief),
            schema: Schema::decisions(),
            effort: (string) config('design.effort'),
            maxTokens: (int) config('design.max_tokens'),
        );

        $decisions = Rules::apply($outcome->decisions, $brief);

        $design = new Design(
            slug: $slug,
            brief: $brief,
            decisions: $decisions,
            generatedAt: now()->toIso8601String(),
            generator: [
                'model' => $outcome->model,
                'prompt_sha1' => sha1(self::systemText()),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'usage' => $outcome->usage,
            ],
        );

        $this->keepRaw($design, $outcome);

        return $design;
    }

    /**
     * The standard, then the answering instructions. Both static, so both cached.
     *
     * @return list<array{type: 'text', text: string, cacheControl?: array{type: 'ephemeral'}}>
     */
    public static function systemBlocks(): array
    {
        return [
            ['type' => 'text', 'text' => Standard::prompt()],
            ['type' => 'text', 'text' => self::instructions(), 'cacheControl' => ['type' => 'ephemeral']],
        ];
    }

    public static function systemText(): string
    {
        return Standard::prompt()."\n\n---\n\n".self::instructions();
    }

    public static function instructions(): string
    {
        return File::get(resource_path('prompts/generator.md'));
    }

    /** @param  array<string,mixed>  $brief */
    public static function userMessage(array $brief): string
    {
        return "Here is the brief for the new project, as JSON.\n\n".
            json_encode($brief, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).
            "\n\nChoose the seven decisions for this project.";
    }

    /**
     * @param  array<string,mixed>  $brief
     * @return array<string,mixed>
     */
    public static function normaliseBrief(array $brief): array
    {
        $shell = in_array($brief['shell'] ?? null, ['sidebar', 'top-nav'], true) ? $brief['shell'] : 'sidebar';
        $brand = strtolower(trim((string) ($brief['brand_colour'] ?? '')));

        return [
            'client' => trim((string) ($brief['client'] ?? '')),
            'product' => trim((string) ($brief['product'] ?? '')),
            'shell' => $shell,
            'brand_colour' => preg_match('/^#[0-9a-f]{6}$/', $brand) ? $brand : null,
            'logo' => filled($brief['logo'] ?? null) ? trim((string) $brief['logo']) : null,
            'notes' => filled($brief['notes'] ?? null) ? trim((string) $brief['notes']) : null,
        ];
    }

    /** A slug from the client name that does not collide with an existing design. */
    public static function slugFor(string $client): string
    {
        $base = Str::slug($client) ?: 'design';
        $slug = $base;
        for ($i = 2; Design::exists($slug); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private function keepRaw(Design $design, Outcome $outcome): void
    {
        if (! config('design.keep_raw')) {
            return;
        }

        $dir = storage_path('app/generations');
        File::ensureDirectoryExists($dir);
        File::put(
            $dir.DIRECTORY_SEPARATOR.$design->slug.'-'.now()->format('Ymd-His').'.json',
            json_encode([
                'model' => $outcome->model,
                'stop_reason' => $outcome->stopReason,
                'usage' => $outcome->usage,
                'brief' => $design->brief,
                'raw' => $outcome->raw,
                'after_rules' => $design->decisions,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );
    }
}
