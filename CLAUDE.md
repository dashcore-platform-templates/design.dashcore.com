# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Read `PURPOSE.md` before adding a feature. It says what belongs in this app: the Dashcore Standard, the generator, the `design-kit` adapter and the drift board. Changes to what any one fleet app does belong in that app.

## Commands

```bash
composer setup                 # first run: install, .env, key, migrate, npm install, build
composer dev                   # php artisan dev (server + vite)
npm run build                  # vite-plus (`vp build`); `npm run dev` for HMR

composer test                  # the full gate CI runs: config:clear, pint --test, phpstan, phpunit, design:audit
composer lint                  # pint --parallel (fixes)
composer types:check           # phpstan, level 7
composer standard:check        # php artisan design:audit — this app is audited against its own standard

php artisan test --filter=ResolverTest                 # one test class
php artisan test tests/Feature/GenerateDesignTest.php  # one file
php artisan test --testsuite=DesignKit                 # suites: Unit, Feature, DesignKit (packages/design-kit/tests)

php artisan design:check       # contrast for every design, both registers — run after any colour rule change
php artisan design:generate "Client" "Two-sentence brief"
php artisan fleet:invite       # first account on an empty database (otherwise use /people)
```

**Tests run on Postgres, never SQLite.** `phpunit.xml` forces `DB_CONNECTION=pgsql` and `DB_DATABASE=design_dashcore_test`. Locally that's Herd's Postgres on port 5433, user `root`. `jsonb` doesn't keep object key order, which once reordered every design in production, so SQLite can't catch these bugs.

Generation needs `ANTHROPIC_API_KEY`, and the model is set by `DESIGN_MODEL`. Everything else works without the key. Tests never call the network: they use `Tests\Support\FakeDecisionSource` in place of the `DecisionSource` binding.

## Architecture

**Standard → generator → pack → kit → report.** One app, because the parts must change in the same commit.

- **`standard/*.md`** is the rule set, in markdown. `App\Design\Standard` reads it. Each file is split at a `<!-- prompt-end -->` marker:
  - Files `01` to `09`, above the marker, become the generator's system prompt (`Standard::prompt()`).
  - The `/standard` page and the exported pack use the whole file (`Standard::full()`).
  - `docs/cut-sheet.md` records which side each rule sits on.
  - `Standard::version()` is a sha1 of the full text. Any edit under `standard/` changes the version kits compare against.
- **`app/Design/`** turns the seven decisions into a design:
  - The seven are accent, temperature, pairing, radius, density, motif and copy. The shell is a human choice in the brief and is never generated.
  - `Resolver::resolve()` turns decisions into colour tokens. It measures every text role against the surface it sits on, in light and dark. A design that fails contrast isn't exported.
  - `Css` emits the CSS, `Pack`/`Handoff` build the export, and `Neutrals`/`Pairings` hold the allowed values.
  - `Design` is a plain class over the `designs` table (query builder, not Eloquent), keyed by slug.
  - The seeds in `database/seeders/designs` are imported by a migration. They are not the live copy.
- **`app/Design/Generation/`**: `DesignGenerator` builds the prompt and calls a `DecisionSource`. `AnthropicDecisionSource` is bound in `AppServiceProvider`. `Schema` (from `standard/09`) is enforced as the structured-output shape, and `Rules` checks the answer afterwards. The prompt wording is in `resources/prompts/generator.md`.
- **Pages** are Livewire single-file components in `resources/views/pages/`, routed with `Route::livewire(..., 'pages::...')` in `routes/web.php`:
  - Every page needs a session. There is no sign-up form.
  - The exports (`designs/{slug}/design.json|pack.zip|pack.json`) sit behind `CanPullADesign`, which accepts a session or a personal token. A slug alone is never enough, because a pack names the client.
- **Blocks**: `resources/views/components/block/` has one component per block, listed in `App\Design\Blocks`. A design page draws them live; `/blocks` hands over the screens a design doesn't decide.
- **Drift board** (`/fleet`):
  - Kits POST to `routes/api.php` → `Fleet\ReportController`. A report carries counts by rule, never source lines.
  - `App\Fleet\GitHubIdentity` checks the GitHub Actions OIDC token, so a repo proves which one it is without storing a secret.
  - Data lives in `sites` and `site_reports`.

## packages/

- **`packages/design-kit/`** is the source of the `dashcore/design-kit` Composer package. Its commands are `design:apply`, `design:audit`, `design:connect` and `design:update`.
  - It's autoloaded straight from `composer.json` (`Dashcore\DesignKit\`). Its tests are the `DesignKit` suite.
  - `AppServiceProvider` registers only its `AuditCommand`, plus its `<x-ds::…>` components. This app's `design:check` is a different command from the package's.
  - `publish-design-kit.yml` publishes it by subtree split to the `dashcoretech/design-kit` mirror. **Edit it here, never in the mirror.**
  - Fleet apps don't install from the mirror. It's private, and their builds can't download it. `packages/design-kit/bin/vendor-into <app> [design-kit/vX.Y.Z]` copies a release into the app's `packages/dashcore/design-kit`, the way bridge is carried.
  - Tag `design-kit/vX.Y.Z` to release.
- **`packages/dashcore/{bridge,testkit}`** are path repositories with `symlink: false`. They're copied into `vendor/`, so an edit there only takes effect after `composer update dashcore/bridge` (or testkit).
  - testkit adds ratchet checks to the suite: route guards, Postgres test DB, migration round-trips, debug calls, `env()` outside `config/`, and `wire:loading` on Livewire actions.

## Rules that are easy to break

- **Seven decisions, and only seven.** Anything the standard doesn't list isn't allowed. A preference for one project goes in its decisions, never into the standard.
- **A tightening ships with its `design:audit` rule in the same change.** Every standard change also gets a `standard/CHANGELOG.md` entry that says whether an app has to act. Entries start a line with `**`, which `Standard::changelogCount()` counts.
- **Contrast is measured.** Run `php artisan design:check` after any colour rule change.
