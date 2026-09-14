<?php

namespace App\Design;

/**
 * The handoff from a finished design to the repo that will wear it. The
 * person's job ends at judging the mockup; from here one pasted prompt lets
 * an agent in the target repo install the adapter, pull and apply the pack,
 * build, and migrate the screens. The design page's panel, the walkthrough
 * and the tests all read the commands from here so they cannot drift.
 */
final class Handoff
{
    public const REPO = 'https://github.com/dashcoretech/design-kit.git';

    /** Where a kit pulls this design's pack from. */
    public static function packUrl(Design $design): string
    {
        return route('designs.manifest', $design->slug);
    }

    /** Flux Pro is not on Packagist, so a kit has to be told where it lives. */
    public const FLUX_REPO = 'https://composer.fluxui.dev';

    /** Once per machine: the package is private, and Composer runs git non-interactively. */
    public static function tokenCommand(): string
    {
        return 'composer config --global github-oauth.github.com <token>';
    }

    /** Once per machine: the Flux Pro licence, which Composer will otherwise stop and prompt for. */
    public static function fluxCommand(): string
    {
        return 'composer config --global http-basic.composer.fluxui.dev <email> <licence key>';
    }

    /**
     * The by-hand path, in order. The agent runs the same seven.
     *
     * Flux Pro comes first, and not for tidiness. Tailwind has to see the Pro
     * views before `npm run build`, or their classes are missing from the
     * stylesheet and the components render unstyled.
     *
     * @return list<string>
     */
    public static function commands(string $packUrl): array
    {
        return [
            'composer config repositories.flux-pro composer '.self::FLUX_REPO,
            'composer require livewire/flux-pro',
            'composer config repositories.design-kit vcs '.self::REPO,
            'composer require dashcore/design-kit',
            'php artisan design:apply '.$packUrl,
            'npm install',
            'npm run build',
        ];
    }

    /** What to paste into Claude Code in the target repo. */
    public static function prompt(Design $design, ?string $packUrl = null): string
    {
        $packUrl ??= self::packUrl($design);
        $name = $design->name();
        $repo = self::REPO;
        $fluxRepo = self::FLUX_REPO;

        return <<<TXT
        Bring this app onto the Dashcore design for {$name}.

        1. If this app does not already require `livewire/flux-pro`, add it: `composer config repositories.flux-pro composer {$fluxRepo}` then `composer require livewire/flux-pro`. It is not on Packagist, which is why the repository line comes first. Our licence covers every Dashcore site, and the standard names components that only Pro has. If Composer cannot authenticate to that host, stop and ask me to add the licence myself. Never ask me for the licence key.
        2. Install the adapter: `composer config repositories.design-kit vcs {$repo}` then `composer require dashcore/design-kit`. It is a private package. If Composer cannot reach the repository, stop and ask me to run `composer config --global github-oauth.github.com <token>` myself. Never ask for the token value.
        3. Run `php artisan design:apply {$packUrl} --force`. It pulls the design pack into design/ and applies the theme, fonts, sign-in layout and icons. Then `npm install` and `npm run build`, in that order and after step 1, so the build sees the Pro views.
        4. Add the contents of design/CLAUDE.snippet.md to this repo's CLAUDE.md, creating the file if there is none. Commit design/ and everything the first three steps changed as one commit.
        5. If this app already has screens, follow design/MIGRATION.md: run `php artisan design:audit` for the map, do the mechanical pass in its own commit, then migrate one screen per commit, most-used first, checking each with `php artisan design:audit <file>` before committing. Do not invent a component or a colour the standard lacks; leave it and say so.
        6. End with a report: the audit count before and after, the screens migrated, what is left and why, and anything the standard has no rule for.
        TXT;
    }
}
