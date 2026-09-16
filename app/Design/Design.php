<?php

namespace App\Design;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One design: a brief plus the seven decisions, a row in `designs`. The tokens
 * layer is derived on read, never stored, so a design can only ever disagree
 * with the standard in its decisions. A generated design also carries a small
 * record of how it was made: model, prompt hash, duration, token usage.
 *
 * Not an Eloquent model on purpose. The generator hands back a design nobody
 * has saved yet, and a plain object says that where a model would have to be
 * talked out of writing itself. The handful of methods below are the only place
 * in the app that knows where a design is kept.
 *
 * Designs were JSON files under resources/designs until 17 September 2026. The
 * migration says why that stopped working.
 */
final class Design
{
    public const SCHEMA = 'dashcore-design/1';

    /**
     * The design the tool itself wears. Its tokens are on :root in every
     * layout, so it is not one of the designs a person can throw away.
     */
    public const OWN = 'dashcore';

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

    /**
     * A slug is checked before it reaches a query, because it arrives from a
     * URL and is also a file name inside an exported pack.
     */
    public static function valid(string $slug): bool
    {
        return preg_match('/^[a-z0-9-]+$/', $slug) === 1;
    }

    public static function exists(string $slug): bool
    {
        return self::valid($slug) && self::table()->where('slug', $slug)->exists();
    }

    public static function load(string $slug): self
    {
        $row = self::valid($slug) ? self::table()->where('slug', $slug)->first() : null;

        if ($row === null) {
            throw new InvalidArgumentException("No design named {$slug}.");
        }

        return self::fromRow($row);
    }

    /**
     * Every design, the tool's own first and the rest by slug. That is the
     * nav's order, decided here rather than in the sidebar.
     *
     * @return Collection<int, Design>
     */
    public static function all(): Collection
    {
        return self::table()
            ->orderByRaw('CASE WHEN slug = ? THEN 0 ELSE 1 END', [self::OWN])
            ->orderBy('slug')
            ->get()
            ->map(fn (object $row) => self::fromRow($row));
    }

    /** @return array<string,mixed> */
    public function tokens(): array
    {
        return Resolver::resolve($this->decisions);
    }

    /**
     * The whole design as it is exported: schema, brief, decisions, tokens.
     * Deterministic: the same design exports the same bytes every time, so a
     * kit that pulls it twice has nothing to change. A design that was never
     * generated has no generated_at rather than a fresh one per export.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'schema' => self::SCHEMA,
            // The design's own name for itself. Without it an applied kit holds
            // a design it cannot name, and working out which one it is means
            // comparing accent colours against every design in the tool. Every
            // command that takes a slug reads it from the pack instead.
            'slug' => $this->slug,
            'generated_at' => $this->generatedAt,
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
        // Form controls hand back strings; the row keeps the pairing as an id.
        if (isset($this->decisions['pairing']['value'])) {
            $this->decisions['pairing']['value'] = (int) $this->decisions['pairing']['value'];
        }
        if (isset($this->decisions['accent']['value'])) {
            $this->decisions['accent']['value'] = strtolower(trim($this->decisions['accent']['value']));
        }

        $values = [
            'brief' => json_encode($this->brief),
            'decisions' => json_encode($this->decisions),
            // Left exactly as the generator reported it. generated_at is when
            // the model chose, not when a person last hit Save: stamping it on
            // save changed the pack's bytes, so a kit that had pulled the design
            // saw a change that was not one.
            'generated_at' => $this->generatedAt,
            'generator' => $this->generator === null ? null : json_encode($this->generator),
            'updated_at' => now(),
        ];

        // Not updateOrInsert, which passes the same values to both branches: it
        // would rewrite created_at every time somebody changed a radius.
        if (self::exists($this->slug)) {
            self::table()->where('slug', $this->slug)->update($values);

            return;
        }

        self::table()->insert($values + ['slug' => $this->slug, 'created_at' => now()]);
    }

    /** Everything except the one the tool is wearing. */
    public function deletable(): bool
    {
        return $this->slug !== self::OWN;
    }

    public function delete(): void
    {
        if (! $this->deletable()) {
            throw new InvalidArgumentException('The tool wears '.self::OWN.'. Deleting it takes every page down, sign-in included.');
        }

        self::table()->where('slug', $this->slug)->delete();
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

    private static function table(): Builder
    {
        return DB::table('designs');
    }

    /** The query builder hands back a bare object, so the row is read as the array it is. */
    private static function fromRow(object $row): self
    {
        $data = (array) $row;

        return new self(
            slug: (string) $data['slug'],
            brief: self::decode($data['brief']),
            decisions: self::decode($data['decisions']),
            // isset is false for a null column, which is the answer wanted here.
            generatedAt: isset($data['generated_at']) ? (string) $data['generated_at'] : null,
            generator: isset($data['generator']) ? self::decode($data['generator']) : null,
        );
    }

    /** @return array<string,mixed> */
    private static function decode(mixed $value): array
    {
        return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR) ?: [];
    }
}
