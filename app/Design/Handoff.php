<?php

namespace App\Design;

/**
 * The handoff from a finished design to the repo that will use it. The
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

    /**
     * The package major the fleet is being moved onto.
     *
     * A major does not arrive on its own, which is the point of one: an app
     * pinned to 3 never sees 4 and says nothing about it. So the constraint has
     * to be named somewhere a person reads, and it changes here when a major
     * ships, beside the release note in the package README.
     *
     * Written 5.* rather than ^5.0 because this ends up in a command somebody
     * pastes into a shell. On Windows, composer is a .bat, so cmd.exe sees the
     * arguments and eats the caret as its own escape character: ^5.0 arrives as
     * 5.0 and Composer resolves an exact version that does not exist. 5.* means
     * the same thing and has no character any shell argues about.
     */
    public const MAJOR = '5.*';

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

    /**
     * The update path, for an app already on the standard. Setting one up is a
     * different job with one-time steps in it, so this is not that.
     *
     * Composer comes first and is separate: the components and the audit rules
     * live in the package, and a command cannot safely update the package it is
     * running from.
     *
     * @return list<string>
     */
    public static function updateCommands(?Design $design = null): array
    {
        return [
            // Plain update, which is what taking a release is everywhere else
            // and what every app already on this major needs. Naming a version
            // here was wrong: it made the ordinary case look like a decision,
            // and a decision people would then copy into the one place it does
            // matter without knowing why it was there.
            'composer update dashcore/design-kit',
            // No argument. The app's design/design.json names its own slug, so
            // asking somebody to fill in a placeholder is asking them for
            // something the app already knows. A file written before designs
            // carried a slug says so and asks for it once.
            'php artisan design:update'.($design === null ? '' : ' '.$design->slug),
        ];
    }

    /**
     * The one command that needs a version, for an app still on an older major.
     *
     * `composer update` will not cross a major, on purpose: a release that can
     * fail a passing build stops and waits for a person. This is a person
     * deciding. A bare `composer require dashcore/design-kit` would cross every
     * major at once and rewrite the constraint to match, which is the same
     * decision made by accident.
     */
    public static function crossMajorCommand(): string
    {
        return 'composer require "dashcore/design-kit:'.self::MAJOR.'"';
    }

    /**
     * What to paste into Claude Code to pick up a change to the standard.
     * Without a design it reads generically, for the changelog page, where the
     * reader has not said which app they are updating.
     */
    public static function updatePrompt(?Design $design = null): string
    {
        $on = $design === null ? 'already on the Dashcore standard' : 'already on the '.$design->name().' design';
        [$composer, $update] = self::updateCommands($design);
        $crossMajor = self::crossMajorCommand();

        return <<<TXT
        The Dashcore design standard has changed. Pick the change up in this app, which is {$on}.

        1. Run `{$composer}` first. The components and the audit rules arrive with the package. If it reports nothing to do, this app is on an older major and needs `{$crossMajor}` once instead.
        2. Run `{$update}`. It re-applies the design, says whether the standard moved, and audits this app against the rules as they now read.
        3. Read design/CHANGELOG.md. Each entry says whether it is a relaxation, which needs nothing, or a tightening, which means screens have drifted. It also says whether the audit can check it. Where the audit cannot, look for those screens yourself, because nothing will report them.
        4. Work the audit findings the way design/MIGRATION.md says: one screen per commit, most-used first, checking each with `php artisan design:audit <file>` before committing. Do not invent a component or a colour the standard lacks; leave it and say so.
        5. End with a report: the standard version before and after, the audit count before and after, the screens changed, and anything left and why.

        Do not re-run the setup. The adapter is installed, the sign-in layout is in place, and the CLAUDE snippet is already in CLAUDE.md.
        TXT;
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
