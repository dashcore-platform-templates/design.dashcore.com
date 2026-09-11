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

Republish the mirror after changing the package in the design repo:

```bash
git subtree push --prefix=packages/design-kit https://github.com/dashcoretech/design-kit.git master
```

In a kit:

```bash
composer config repositories.design-kit vcs https://github.com/dashcoretech/design-kit.git
composer require dashcore/design-kit:dev-master
```

The mirror is private, so Composer needs its own GitHub credentials once per machine. The GitHub CLI's git credential helper is not enough: Composer runs git non-interactively and will fail with "Repository not found". Add a token once, and every private Dashcore package works afterwards:

```bash
composer config --global github-oauth.github.com <a GitHub token with repo scope>
```

Working on the design repo and a kit side by side, a path repository skips the mirror and picks up edits immediately:

```bash
composer config repositories.design-kit path ../design.dashcore.com/packages/design-kit
composer require dashcore/design-kit:@dev
```

## Apply a design

Unzip the pack from design.dashcore.com into the repo root so `design/design.json` exists, then:

```bash
php artisan design:apply design/design.json
npm run build
```

What it changes: a marked block at the end of `resources/css/app.css` (Flux theme variables, the `--ds-*` roles, the dark muted-text correction), the fonts array in `vite.config.js`, the brand and design keys in `.env` and `.env.example`, `resources/views/layouts/auth.blade.php` (now the two-panel sign-in), the logo copied to `public/images/` if the brief had one, and the four Lucide-named icon overrides deleted (it asks first; `--force` skips the question). Running it twice changes nothing. `--check` prints the plan and exits non-zero if anything would change.

`php artisan design:check design/design.json` measures contrast on the file.

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
