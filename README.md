# design.dashcore.com

**New here? Read [docs/start-here.md](docs/start-here.md).** It gets you from nothing to having tested it, in about ten minutes. This file is for running it locally and changing it.

Every new Dashcore project should get a designed, on-standard look on day one, from a short brief, without building it by hand and without it reading as a template. This is the tool for that.

Three parts, and the order matters.

**The standard** fixes structure for every app in the fleet: two shells, the type and spacing scales, colour roles with a measured contrast floor, a rule for every Flux component free and Pro, one icon set, motion limits, and status colours that mean the same thing everywhere. It lives as markdown in [`standard/`](standard/README.md) and doubles as the generator's instructions.

**The generator** reads the standard and a two-sentence brief, then chooses only the seven things the standard leaves open, with a one-line reason for each that you can agree or disagree with. It never invents structure.

**The adapter** is a Composer package, [`dashcore/design-kit`](packages/design-kit/README.md). One command applies a design to a Laravel Livewire Flux kit: theme variables, fonts, the two-panel sign-in, the custom components, icons.

It is written here, in `packages/design-kit`, because it changes with the standard and the two should move in one commit. Composer cannot install from a subdirectory, so it is published to `dashcoretech/design-kit`, and that is what kits install. The published repo is a mirror: edit it there and the change is lost on the next publish.

Publishing is automatic. A push to master updates the mirror, and a `design-kit/vX.Y.Z` tag releases that version. A release reaches every kit on the next `composer update`, including a new audit rule: rules show up as findings on the fleet board and reporting never fails a build, so there is nothing to stop and wait for. A major is the package's own interface breaking, which is rare.

## The seven decisions

Everything else is fixed. The shell is a human choice in the brief, never generated.

| Decision | Values |
|---|---|
| accent | a hex, at least 30 degrees from red and amber, saturation 45 to 85 |
| temperature | pure, cool, warm |
| pairing | one of five approved type pairings |
| radius | sharp, soft, round |
| density | comfortable, compact |
| motif | accent bar, hairline, eyebrow, filled stat |
| copy | the sign-in eyebrow, headline, tagline and footer |

## The loop

Step by step, with what to look for at each stage: [docs/walkthrough.md](docs/walkthrough.md).

1. Open `/designs/new`, describe the client in two sentences, pick a shell, hit Generate.
2. Look at the five mockups: sign-in, shell, dashboard, settings, and every component in that design. Change any decision and they update live.
3. Open Use this design and paste the prompt into Claude Code in the target repo. The agent installs the adapter, pulls and applies the pack, builds, commits, and migrates existing screens one commit at a time. The same panel holds the five by-hand commands and the zip.
4. `design/` is committed in that repo. Every later agent session there reads it before touching a screen.
5. An existing app: review the agent's migration commits, one per screen, against the mockups here.

## Running it

PHP 8.3 and Node. Served by Herd at `design.dashcore.com.test`.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run dev
```

Postgres, not SQLite, and the same for the test suite. Production and CI run Postgres, and the two engines disagree in ways a test on SQLite cannot see: `jsonb` does not keep object key order, which reordered every design on the way out and reached production once. Herd ships Postgres on port 5433 with a `root` user and no password, so make the two databases before migrating:

```bash
psql -h 127.0.0.1 -p 5433 -U root -d postgres -c "create database design_dashcore; create database design_dashcore_test;"
```

Generation needs `ANTHROPIC_API_KEY` in `.env`. Without it the viewer still works in full; generating is a suggest button, never a dependency. The model is `DESIGN_MODEL`, defaulting to Claude Fable 5.1 after a three-way comparison recorded on DAS-2016.

Designs are rows in `designs`, one per project. They were JSON files in the repo until 17 September 2026, which did not survive a deploy once the tool was hosted; the five that existed then are in `database/seeders/designs` and the migration imports them, so a fresh clone has them too. Reading a design needs no login. Making or changing one does.

```bash
php artisan design:check                      # contrast for every design, both registers
php artisan design:generate "Client" "What it does and who uses it"
```

## Where things are

| Path | What |
|---|---|
| `standard/` | The standard. `00` is the audit, `01` to `09` are the rules |
| `app/Design/` | Resolver, colour maths, the temperature and pairing lists, the CSS emitter, the pack |
| `app/Design/Generation/` | The schema, the rules pass, the SDK call behind a `DecisionSource` interface |
| `resources/prompts/generator.md` | How the model is told to answer |
| `resources/views/mockups/` | The five screens |
| `packages/design-kit/` | The adapter package |
| `docs/` | The discovery note that started this, the cut sheet, a status summary |

## Two things worth knowing before you change anything

**The standard is split.** Each file carries a `<!-- prompt-end -->` marker. The generator reads what is above it; the Standard page and the exported pack read the whole file. Rules only a screen-builder acts on sit below the marker so they do not cost tokens on every call. [`docs/cut-sheet.md`](docs/cut-sheet.md) records which rule sits on which side and why.

**Contrast is measured, not eyeballed.** The resolver checks every text role against the surface it actually sits on, in both registers, and a design that fails is not exported. If you change a colour rule, run `design:check`.

Tracked in Linear under the design.dashcore.com project.
