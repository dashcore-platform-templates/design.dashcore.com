# dashcore/design-kit

Applies a design exported from design.dashcore.com to a Laravel Livewire Flux kit, and ships the three custom components the Dashcore Standard defines.

## Install

While the package lives inside the design repo, install it as a path dependency:

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
