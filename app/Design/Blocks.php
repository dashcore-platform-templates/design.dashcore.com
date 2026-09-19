<?php

namespace App\Design;

use InvalidArgumentException;

/**
 * The foundation screens every fleet app has to build for itself, and the
 * prompt that builds each one.
 *
 * A design decides how an app feels. It does not decide that every app's
 * sidebar puts the account menu in the same corner, or that a 404 looks the
 * same in fifteen products. Those are screens, and until now each app built
 * its own from the standard's prose, which is how fifteen apps ended up with
 * the account menu in three different places.
 *
 * Delivered as a prompt rather than as markup. Pasted markup is a fork the
 * moment it lands, and installed markup takes away an app's ability to differ
 * at all; a prompt builds the screen in that app, in that app's design, and
 * the repo owns what it gets. That is the first answer rather than the last
 * one, and the cost of changing our mind later is a prompt nobody pastes again.
 *
 * Nothing here restates a rule. Each prompt points at the sections of
 * `design/STANDARD.md` that already carry it, so a rule cannot say one thing
 * in the standard and another here. What the prompts do add is the trap each
 * screen has: the mistake that gets made every time and is not obvious from a
 * careful reading.
 */
final class Blocks
{
    /**
     * Sign-in is not on this list on purpose. Its copy is one of the seven
     * decisions, and `design:apply` already points the app's auth layout at
     * the package's two-panel one, so it arrives built. Said on the page,
     * because its absence otherwise reads as an oversight.
     */
    public const SHIPPED_ALREADY = 'sign-in';

    /**
     * Each block: what it is, which sections decide it, what it writes, and
     * which mockup screen draws it.
     *
     * `gap` is what the standard does not yet say about this screen. It is
     * part of the prompt rather than hidden, because an agent that guesses
     * silently is how the fleet drifted in the first place.
     *
     * @var array<string,array<string,string|null>>
     */
    public const ALL = [
        'shell' => [
            'label' => 'App shell',
            'what' => 'The frame every signed-in screen sits in: navigation, brand block, and the account menu.',
            'rules' => '02 for both shells and every region in them, 06 for navlist, navmenu and profile, 08 for icons.',
            'builds' => 'The app layout the kit ships, and the nav beside it.',
            'screen' => 'shell',
            'gap' => null,
        ],
        'errors' => [
            'label' => 'Error pages',
            'what' => '404, 403, 419, 500 and 503, plus the first run of an app with nothing in it yet.',
            'rules' => '11, in full.',
            'builds' => 'resources/views/errors/, one file per status.',
            'screen' => 'errors',
            'gap' => null,
        ],
        'settings' => [
            'label' => 'Settings',
            'what' => 'Where somebody changes their name, their password and anything else about their account.',
            'rules' => '06 for the navlist beside the panel it switches, 02 for the page header, 05 for spacing.',
            'builds' => 'The settings section the kit ships, rebuilt on the standard.',
            'screen' => 'settings',
            // The screen Travis was pointing at when he said the account menu
            // is bottom left in one app and top right in another. Naming it in
            // the prompt is how the section gets written: the reports say what
            // fifteen apps each had to decide alone.
            'gap' => 'The standard has no section for settings. 06 and 02 decide how it is built, and nothing decides what it contains or in what order.',
        ],
        'landing' => [
            'label' => 'Landing page',
            'what' => 'Anything a signed-out visitor sees that is not the sign-in form.',
            'rules' => '13, and 03 and 04 for type and colour like any other screen.',
            'builds' => 'The public route this app serves, if it serves one.',
            'screen' => 'landing',
            'gap' => null,
        ],
        'email' => [
            'label' => 'Email',
            'what' => 'The one surface the stylesheet cannot reach, so every rule has to arrive inline.',
            'rules' => '10, which is the only section that applies.',
            'builds' => 'The mail layout and the notification templates.',
            'screen' => 'email',
            'gap' => null,
        ],
    ];

    /** The trap each screen has, kept beside the prompt that has to warn about it. */
    private const TRAPS = [
        'shell' => 'Build only the shell `brief.shell` names, not both. The sidebar is navigation and identity: no content, no search, no stats. The account menu holds settings and sign out and nothing else, so anything else this app wants to put there is a change to the standard rather than a decision here.',
        'errors' => 'None of these uses the danger colour. Red says data is gone, and a wrong address is not that. None of them draws the shell either, because the page that failed may be the one that draws it.',
        'settings' => 'The navlist here is a sub-navigation beside the panel it switches, one level, no nesting. It is never a second main nav: the sidebar or top nav still says which section you are in.',
        'landing' => 'A public page opens with a hero, not a page header, so the page-title rule does not apply to it. Mark the file `@ds-public` or the audit reports the hero as a missing page header.',
        'email' => 'Nothing else in the standard reaches here. No utility classes, no custom properties, pixel values inline, because that is all an email client honours. Mark the file `@ds-audit-ignore-file` with that reason.',
    ];

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::ALL);
    }

    /** @return array<string,string|null> */
    public static function find(string $key): array
    {
        return self::ALL[$key] ?? throw new InvalidArgumentException("Unknown block: {$key}");
    }

    /**
     * What to paste into Claude Code in the target repo.
     *
     * It takes no design. The app already holds one in `design/`, and a prompt
     * that named a slug would be wrong the moment the app's design changed.
     */
    public static function prompt(string $key): string
    {
        $block = self::find($key);
        $label = strtolower((string) $block['label']);
        $trap = self::TRAPS[$key];

        $gap = $block['gap'] === null
            ? ''
            : "\n\nOne thing the standard does not decide: {$block['gap']} Build what this app needs, and list every such choice in the report. Those reports are how the missing section gets written, so do not quietly pick and move on.";

        return <<<TXT
        Build this app's {$label} to the Dashcore standard.

        This app has to be on the standard already: `design/design.json` and `design/STANDARD.md` are in the repo and `php artisan design:audit` runs. If they are not, stop and tell me, because the setup is a different prompt.

        1. Read `design/STANDARD.md`, sections {$block['rules']} Read `design/design.json` for the seven decisions this app was built on, including `brief.shell`.
        2. Build it in this app, on the `--ds-*` roles and the `x-ds::` components, never on raw colours or a component the standard does not name. If something is missing that you need, stop and say so rather than inventing it.
        3. {$trap}
        4. Check every file you touched with `php artisan design:audit <file>` and get it clean before committing. Commit the block on its own.
        5. Report back: the files you added or replaced, the audit count before and after, anything the standard has no rule for, and anything you left undone and why.{$gap}
        TXT;
    }
}
