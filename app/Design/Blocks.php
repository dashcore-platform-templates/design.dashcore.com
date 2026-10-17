<?php

namespace App\Design;

use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;

/**
 * The blocks: the screens a design does not decide, each one drawn by a
 * component of its own and most of them handed over as a prompt.
 *
 * A design decides how an app feels. It does not decide that every app's
 * sidebar puts the account menu in the same corner, or that a 404 looks the
 * same in fifteen products. Those are screens, and until now each app built
 * its own from the standard's prose, which is how fifteen apps ended up with
 * the account menu in three different places.
 *
 * A block owns its markup. `resources/views/components/block/` is where it
 * lives, one component per block, and both the blocks page and a design page
 * render the same component. They used to live under the design page as its
 * mockups, which had it backwards: a design page is one of the things that
 * draws a block, not the place a block comes from.
 *
 * Handed over as a prompt rather than as markup. Pasted markup is a fork the
 * moment it lands, and installed markup takes away an app's ability to differ
 * at all; a prompt builds the screen in that app, in that app's design, and
 * the repo owns what it gets. That is the first answer rather than the last
 * one, and the cost of changing our mind later is a prompt nobody pastes again.
 *
 * No prompt restates a rule. Each points at the sections of
 * `design/STANDARD.md` that already carry it, so a rule cannot say one thing
 * in the standard and another here. What the prompts do add is the trap each
 * screen has: the mistake that gets made every time and is not obvious from a
 * careful reading.
 */
final class Blocks
{
    /** Opened first on a design page, because it is the one people look at. */
    public const DEFAULT = 'dashboard';

    /**
     * What a person is building, which is how they look for a block.
     *
     * Two levels rather than one long list, and the reason is arithmetic. A
     * block draws as a whole document in a frame of its own, about 31KB of it,
     * so a flat page of every block costs more the more there are: fine at
     * eight, three megabytes at a hundred, and long dead before a thousand.
     * An index of categories costs the same whether a category holds one block
     * or forty, because only the one you open draws its blocks.
     *
     * Most hold one today. The shape is here so the second and the fortieth
     * cost nothing to add.
     *
     * @var array<string,array<string,string>>
     */
    public const CATEGORIES = [
        'shells' => [
            'label' => 'App shells',
            'what' => 'The frame every signed-in screen sits in.',
        ],
        'auth' => [
            'label' => 'Sign-in',
            'what' => 'Everything somebody sees before they are in.',
        ],
        'dashboards' => [
            'label' => 'Dashboards',
            'what' => 'The screen an app opens on.',
        ],
        'settings' => [
            'label' => 'Settings',
            'what' => 'Where somebody changes their account and the workspace.',
        ],
        'errors' => [
            'label' => 'Error pages',
            'what' => 'The rare screens that give an app away.',
        ],
        'public' => [
            'label' => 'Public pages',
            'what' => 'Anything a signed-out visitor reaches.',
        ],
        'email' => [
            'label' => 'Email',
            'what' => 'The one surface the stylesheet cannot reach.',
        ],
        'reference' => [
            'label' => 'Reference',
            'what' => 'Not screens to install. Drawings for judging a design.',
        ],
    ];

    /**
     * Every block, in the order they are shown.
     *
     * One list, not two. A block used to be a row here and a screen in a
     * second list that the design page validated against, and the two had to
     * be kept in step by hand.
     *
     * `prompt` says whether this is a screen an app builds. Three are not:
     * sign-in arrives with the setup, and the dashboard and the component
     * gallery are drawings of a design rather than screens anybody installs.
     *
     * `gap` is what the standard does not yet say about a screen. It reaches
     * the prompt rather than staying hidden, because an agent that guesses
     * silently is how the fleet drifted in the first place.
     *
     * @var array<string,array<string,string|bool|null>>
     */
    public const ALL = [
        'sign-in' => [
            'category' => 'auth',
            'label' => 'Sign-in',
            'what' => 'The two-panel sign-in, carrying the copy that is one of the seven decisions.',
            'rules' => '02 for the two panels, 09 for the copy.',
            'builds' => null,
            'prompt' => false,
            'note' => 'No prompt because it needs none. Its copy is one of the seven decisions, and design:apply points this app\'s auth layout at the one in the package, so it arrives with the setup.',
            'gap' => null,
        ],
        'shell' => [
            'category' => 'shells',
            'label' => 'App shell',
            'what' => 'The frame every signed-in screen sits in: navigation, brand block, and the account menu.',
            'rules' => '02 for both shells and every region in them, 06 for navlist, navmenu and profile, 08 for icons.',
            'builds' => 'The app layout the kit ships, and the nav beside it.',
            'prompt' => true,
            'note' => null,
            'gap' => null,
        ],
        'dashboard' => [
            'category' => 'dashboards',
            'label' => 'Dashboard',
            'what' => 'The shell with a real screen in it: stat row, table, the lot.',
            'rules' => '02 for the content grid, 05 for the spacing, 06 for every component on it.',
            'builds' => null,
            'prompt' => false,
            'note' => 'A drawing rather than a screen to install. What is on a dashboard is that app\'s, and the frame around it is the shell block.',
            'gap' => null,
        ],
        'settings' => [
            'category' => 'settings',
            'label' => 'Settings',
            'what' => 'Where somebody changes their name, their password and anything else about their account.',
            'rules' => '06 for the navlist beside the panel it switches, 02 for the page header, 05 for spacing.',
            'builds' => 'The settings section the kit ships, rebuilt on the standard.',
            'prompt' => true,
            'note' => null,
            // The screen Travis was pointing at when he said the account menu
            // is bottom left in one app and top right in another. Naming it in
            // the prompt is how the section gets written: the reports say what
            // fifteen apps each had to decide alone.
            'gap' => 'The standard has no section for settings. 06 and 02 decide how it is built, and nothing decides what it contains or in what order.',
        ],
        'errors' => [
            'category' => 'errors',
            'label' => 'Error pages',
            'what' => '404, 403, 419, 500 and 503, plus the first run of an app with nothing in it yet.',
            'rules' => '11, in full.',
            'builds' => 'resources/views/errors/, one file per status.',
            'prompt' => true,
            'note' => null,
            'gap' => null,
        ],
        'landing' => [
            'category' => 'public',
            'label' => 'Landing page',
            'what' => 'Anything a signed-out visitor sees that is not the sign-in form.',
            'rules' => '13, and 03 and 04 for type and colour like any other screen.',
            'builds' => 'The public route this app serves, if it serves one.',
            'prompt' => true,
            'note' => null,
            'gap' => null,
        ],
        'email' => [
            'category' => 'email',
            'label' => 'Email',
            'what' => 'The one surface the stylesheet cannot reach, so every rule has to arrive inline.',
            'rules' => '10, which is the only section that applies.',
            'builds' => 'The mail layout and the notification templates.',
            'prompt' => true,
            'note' => null,
            'gap' => null,
        ],
        'components' => [
            'category' => 'reference',
            'label' => 'Components',
            'what' => 'Every component the standard names, drawn once, on this design.',
            'rules' => '06 for the mapping onto Flux, 03 and 04 for type and colour.',
            'builds' => null,
            'prompt' => false,
            'note' => 'A gallery rather than a screen. Nothing installs it; it is here so a decision can be judged against every component at once.',
            'gap' => null,
        ],
    ];

    /** The trap each screen has, kept beside the prompt that has to warn about it. */
    private const TRAPS = [
        'shell' => 'Build only the shell `brief.shell` names, not both. The sidebar is navigation and identity: no content, no search, no stats. The account menu holds settings and sign out and nothing else, so anything else this app wants to put there is a change to the standard rather than a decision here.',
        'errors' => 'None of these uses the danger colour. Red says data is gone, and a wrong address is not that. None of them draws the shell either, because the page that failed may be the one that draws it.',
        'settings' => 'The navlist here is a sub-navigation beside the panel it switches, one level, no nesting. It is never a second main nav: the sidebar or top nav still says which section you are in.',
        'landing' => 'A public page keeps its own brand and is outside what the standard unifies, so 13 is advice here rather than rules. Mark the file `@ds-public` and the audit leaves it alone; without the marker the hero is reported as a missing page header.',
        'email' => 'Nothing else in the standard reaches here. No utility classes, no custom properties, pixel values inline, because that is all an email client honours. Mark the file `@ds-audit-ignore-file` with that reason.',
    ];

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::ALL);
    }

    /**
     * The block asked for, or the default. Never anything else, because this
     * reaches a component name.
     */
    public static function resolve(?string $key): string
    {
        return $key !== null && self::has($key) ? $key : self::DEFAULT;
    }

    public static function hasCategory(string $key): bool
    {
        return array_key_exists($key, self::CATEGORIES);
    }

    /**
     * The blocks in one category, in list order.
     *
     * @return array<string,array<string,string|bool|null>>
     */
    public static function inCategory(string $category): array
    {
        return array_filter(self::ALL, fn (array $b) => $b['category'] === $category);
    }

    /**
     * The block a category's card shows, which is its first.
     *
     * A category with nothing in it is a card that draws an empty frame and
     * teaches somebody the tool is broken, so it is not listed at all.
     */
    public static function faceOf(string $category): ?string
    {
        return array_key_first(self::inCategory($category));
    }

    /**
     * Categories with at least one block, each carrying its blocks.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function categories(): array
    {
        $out = [];

        foreach (self::CATEGORIES as $key => $category) {
            $blocks = self::inCategory($key);

            if ($blocks === []) {
                continue;
            }

            $out[$key] = $category + ['blocks' => $blocks, 'face' => array_key_first($blocks)];
        }

        return $out;
    }

    /**
     * The ones an app builds, which are the ones with a prompt.
     *
     * @return array<string,array<string,string|bool|null>>
     */
    public static function buildable(): array
    {
        return array_filter(self::ALL, fn (array $b) => $b['prompt'] === true);
    }

    /** @return array<string,string|bool|null> */
    public static function find(string $key): array
    {
        return self::ALL[$key] ?? throw new InvalidArgumentException("Unknown block: {$key}");
    }

    /**
     * One block, drawn for one design, as markup.
     *
     * The only way in. Every caller used to name a view and hand it five keys,
     * which is a contract three files had to keep by hand. The key is resolved
     * before it reaches a component name, so nothing off the list can be drawn
     * whatever a URL says.
     */
    public static function draw(string $key, Design $design, string $register = 'light'): string
    {
        return Blade::render(
            '<x-dynamic-component :component="$component" :design="$design" :register="$register" />',
            [
                'component' => 'block.'.self::resolve($key),
                'design' => $design,
                'register' => $register === 'dark' ? 'dark' : 'light',
            ],
        );
    }

    /**
     * What every block component needs beyond the design itself.
     *
     * Derived in one place rather than assembled by each caller. Three callers
     * built this array by hand, and one of them read the motif off a different
     * object than the others, which is a difference that shows on screen and
     * nothing would have reported.
     *
     * @return array{t: array<string,mixed>, motif: string, shell: string}
     */
    public static function context(Design $design): array
    {
        return [
            't' => $design->tokens(),
            'motif' => (string) $design->decision('motif'),
            'shell' => $design->brief['shell'] ?? 'sidebar',
        ];
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

        if ($block['prompt'] !== true) {
            throw new InvalidArgumentException("{$key} is drawn here but not built by anybody: ".$block['note']);
        }

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
