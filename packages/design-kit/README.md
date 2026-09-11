# dashcore/design-kit

Applies a design exported from design.dashcore.com to a Laravel Livewire Flux kit, and ships the three custom components the Dashcore Standard defines.

## Install

The source of truth is `packages/design-kit` inside the design.dashcore.com repo. It is mirrored to `dashcoretech/design-kit` on GitHub, which is what a kit installs from. Never edit the mirror; the mirror is republished with:

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
