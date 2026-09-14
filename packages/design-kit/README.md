# dashcore/design-kit

> **This is a mirror. Do not edit it here.** The source is `packages/design-kit` in
> [design.dashcore.com](https://github.com/dashcoretech/design.dashcore.com). Edits made
> to this repo are overwritten the next time it is republished. Change it there, then
> republish with the command below.

Applies a design exported from design.dashcore.com to a Laravel Livewire Flux kit, and ships the five custom components the Dashcore Standard defines.

## Why two places

The package lives in the design repo because it changes with the standard: a rule and the code that applies it move together, in one commit, tested against the real design files. But Composer cannot install a package from a subdirectory, so kits need it as a repo of its own. That repo is this one, published from the other by subtree push, with history.

One is written, the other is published. Never the reverse.

## Install

This repo is republished automatically. A push to master in the design repo updates it, and a `design-kit/vX.Y.Z` tag there releases that version here:

```bash
git tag design-kit/v1.1.0 && git push origin design-kit/v1.1.0
```

If the workflow is down, the same thing by hand:

```bash
git subtree push --prefix=packages/design-kit https://github.com/dashcoretech/design-kit.git master
```

In a kit:

```bash
composer config repositories.flux-pro composer https://composer.fluxui.dev
composer require livewire/flux-pro
composer config repositories.design-kit vcs https://github.com/dashcoretech/design-kit.git
composer require dashcore/design-kit
```

The first two lines are Flux Pro, skipped if the app already has it. It is not on Packagist, so without the repository line Composer says the package does not exist. Install it before `npm run build`, or Tailwind never sees the Pro views. An app on free Flux is still on the standard: section 06 names a free stand-in for eleven of the nineteen Pro components, and `design:apply` reports which Flux it found.

The mirror is private, so Composer needs its own GitHub credentials once per machine. The GitHub CLI's git credential helper is not enough: Composer runs git non-interactively and will fail with "Repository not found". Add a token once, and every private Dashcore package works afterwards:

```bash
composer config --global github-oauth.github.com <a GitHub token with repo scope>
```

## Versions

Releases are tagged, and `composer require` writes `^1.0` for you. Every later 1.x release then reaches every kit on the next `composer update`, which is the whole point: a rule added to the standard should not need fifteen repos edited by hand to arrive. A change that would break an app already on the standard goes to 2.0 instead, so it stops and waits for a person rather than arriving quietly.

Before 1.0 this was a 0.x package, where Composer reads each minor as breaking. A kit that had required `^0.2.0` never saw v0.3.0 at all, and said so as "Nothing to modify in lock file" rather than as an error. If you have one of those, change the constraint to `^1.0` once and it will keep up from then on.

Working on the design repo and a kit side by side, a path repository skips the mirror and picks up edits immediately:

```bash
composer config repositories.design-kit path ../design.dashcore.com/packages/design-kit
composer require dashcore/design-kit:@dev
```

## Apply a design

Give it the design's slug, its pack URL from design.dashcore.com, or a local design file:

```bash
php artisan design:apply https://design.dashcore.com/designs/meridian-credit-union/pack.json
php artisan design:apply meridian-credit-union      # with DESIGN_SOURCE in .env pointing at your design.dashcore.com
php artisan design:apply design/design.json         # a pack unzipped into the repo root
npm run build
```

A slug or a URL pulls the pack's four files into `design/` first, so the repo ends up with exactly what a downloaded zip would have given it, then applies.

What it changes: the four pack files under `design/` when pulled, a marked block at the end of `resources/css/app.css` (Flux theme variables, the `--ds-*` roles, the dark muted-text correction), the fonts array in `vite.config.js`, the brand and design keys in `.env` and `.env.example`, `resources/views/layouts/auth.blade.php` (now the two-panel sign-in), the logo copied to `public/images/` if the brief had one, the four Lucide-named icon overrides deleted, and the kit's three stock sign-in layouts under `layouts/auth/` deleted once nothing references them. It asks before each delete; `--force` skips the questions. Running it twice changes nothing. `--check` prints the plan and exits non-zero if anything would change.

`php artisan design:check design/design.json` measures contrast on the file.

## Audit and migrate

```bash
php artisan design:audit                                             # every view, the map
php artisan design:audit resources/views/pages/dashboard.blade.php   # one screen, the check
php artisan design:audit --fix                                       # weights and off-scale spacing, nothing needing judgment
```

It lists what is off the standard per file and by rule, and exits non-zero while anything is. It reports what a text scan can see; whether a screen is composed well is for a person with the screen open. The pack's `design/MIGRATION.md` is the procedure an agent follows to bring an existing app over, one screen per commit, using the audit on each file as its check.

A line that is deliberately off says so, and the audit leaves it alone. A whole file that sits outside the app standard, a landing page or a legal document, says so once at the top and is listed as skipped:

```blade
<div class="p-5"> {{-- @ds-audit-ignore the brand mark is fixed at this size --}}

{{-- @ds-audit-ignore-file public landing page, composed outside the app standard --}}
```

An ignore without a reason is worse than the finding.

## Components

```blade
<x-ds::page-header title="Today" description="Tuesday 15 September.">
    <flux:button variant="primary">New item</flux:button>
</x-ds::page-header>

<x-ds::stat-row>
    <x-ds::stat label="Open items" value="148" delta="+12 since yesterday" tone="up" first />
    <x-ds::stat label="Needs attention" value="9" delta="3 waiting on you" tone="warn" />
</x-ds::stat-row>

<x-ds::empty icon="inbox" title="Nothing here yet." description="Add one from the button above." />
```

They read the motif and title face from `config('brand')`, which `design:apply` writes, so they match the mockups in the tool.

## Publish to edit

```bash
php artisan vendor:publish --tag=design-kit-views
php artisan vendor:publish --tag=design-kit-config
```
