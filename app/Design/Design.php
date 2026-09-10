<?php

namespace App\Design;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One design: a brief plus the seven decisions, stored as a JSON file in
 * resources/designs. No database. The tokens layer is derived on read, never
 * stored by hand, so a file can only ever disagree with the standard in its
 * decisions. A generated design also carries a small record of how it was
 * made: model, prompt hash, duration, token usage.
 */
final class Design
{
    public const SCHEMA = 'dashcore-design/1';

    /**
     * @param  array<string,mixed>  $brief
     * @param  array<string,mixed>  $decisions
     * @param  array<string,mixed>|null  $generator
     */
    public function __construct(
        public string $slug,
        public array $brief,
        public array $decisions,
        public ?string $generatedAt = null,
        public ?array $generator = null,
    ) {}

    public static function directory(): string
    {
        return resource_path('designs');
    }

    public static function path(string $slug): string
    {
        return self::directory().DIRECTORY_SEPARATOR.$slug.'.json';
    }

    public static function exists(string $slug): bool
    {
        return preg_match('/^[a-z0-9-]+$/', $slug) === 1 && File::exists(self::path($slug));
    }

    public static function load(string $slug): self
    {
        if (! self::exists($slug)) {
            throw new InvalidArgumentException("No design named {$slug}.");
        }

        $data = json_decode(File::get(self::path($slug)), true, 512, JSON_THROW_ON_ERROR);

        return new self(
            slug: $slug,
            brief: $data['brief'] ?? [],
            decisions: $data['decisions'] ?? [],
            generatedAt: $data['generated_at'] ?? null,
            generator: $data['generator'] ?? null,
        );
    }

    /** @return Collection<int, Design> */
    public static function all(): Collection
    {
        return collect(File::glob(self::directory().'/*.json'))
            ->map(fn (string $path) => self::load(basename($path, '.json')))
            ->sortBy(fn (Design $d) => $d->slug === 'dashcore' ? '' : $d->slug)
            ->values();
    }

    /** @return array<string,mixed> */
    public function tokens(): array
    {
        return Resolver::resolve($this->decisions);
    }

    /**
     * The whole file as it is exported: schema, brief, decisions, tokens.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'schema' => self::SCHEMA,
            'generated_at' => $this->generatedAt ?? now()->toIso8601String(),
            'generator' => $this->generator,
            'brief' => $this->brief,
            'decisions' => $this->decisions,
            'tokens' => $this->tokens(),
        ], fn ($v) => $v !== null);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    /** Persist decisions and brief. Tokens are not written; they are derived on read. */
    public function save(): void
    {
        // Form controls hand back strings; the file keeps the pairing as an id.
        if (isset($this->decisions['pairing']['value'])) {
            $this->decisions['pairing']['value'] = (int) $this->decisions['pairing']['value'];
        }
        if (isset($this->decisions['accent']['value'])) {
            $this->decisions['accent']['value'] = strtolower(trim($this->decisions['accent']['value']));
        }

        File::ensureDirectoryExists(self::directory());
        File::put(self::path($this->slug), json_encode(array_filter([
            'schema' => self::SCHEMA,
            'generated_at' => $this->generatedAt ?? now()->toIso8601String(),
            'generator' => $this->generator,
            'brief' => $this->brief,
            'decisions' => $this->decisions,
        ], fn ($v) => $v !== null), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }

    public function name(): string
    {
        return $this->brief['client'] ?? Str::headline($this->slug);
    }

    /** @return array<string,mixed> */
    public function pairing(): array
    {
        return Pairings::find((int) ($this->decisions['pairing']['value'] ?? 1));
    }

    public function decision(string $key): mixed
    {
        return $this->decisions[$key]['value'] ?? null;
    }
}
