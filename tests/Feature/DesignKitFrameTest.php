<?php

namespace Tests\Feature;

use Dashcore\DesignKit\DesignKitServiceProvider;
use Dashcore\DesignKit\Frame\Menu;
use Dashcore\DesignKit\Testing\Shell;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The frame dashcore/ui drew, now in design-kit: x-ds::shell and its parts,
 * on the role tokens. The tool does not boot the package's provider, so its
 * views are registered here. Adapted from dashcore/ui's own suite.
 */
class DesignKitFrameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Blade::anonymousComponentPath(base_path('packages/design-kit/resources/views/ds'), 'ds');
        Blade::anonymousComponentPath(base_path('packages/design-kit/resources/views/ds'), 'dashcore');
        View::addNamespace('design-kit', base_path('packages/design-kit/resources/views'));
    }

    /** @param  array<int, array<string, mixed>>  $menu */
    private function shell(array $menu, string $extra = ''): string
    {
        return Blade::render('<x-ds::shell :menu="$menu" brand="Dashcore" product="Finance" '.$extra.'><p>Page</p></x-ds::shell>', ['menu' => $menu]);
    }

    #[Test]
    public function the_menu_renders_in_the_order_the_app_gave_it(): void
    {
        $shell = Shell::of($this->shell([
            ['label' => 'Home', 'href' => '/'],
            ['label' => 'Inbox', 'href' => '/inbox'],
            ['label' => 'Automate', 'items' => [['label' => 'Rules', 'href' => '/rules']]],
            ['label' => 'Docs', 'href' => '/docs'],
        ]));

        $this->assertSame(['Home', 'Inbox', 'Rules', 'Docs'], $shell->links());
        $this->assertSame(['Automate'], $shell->groups());
        $this->assertSame(['Rules'], $shell->linksUnder('Automate'));
        // Consecutive top-level entries are one list, not a section each.
        $this->assertSame(3, $shell->count('//nav[@data-dc-nav]/*'));
    }

    #[Test]
    public function an_entry_whose_route_does_not_exist_is_dropped_not_thrown(): void
    {
        $shell = Shell::of($this->shell([
            ['label' => 'Empty', 'items' => ['missing.route' => 'Gone']],
            ['label' => 'Home', 'href' => '/'],
        ]));

        $this->assertSame(['Home'], $shell->links());
        $this->assertSame([], $shell->groups());
    }

    #[Test]
    public function the_current_page_is_marked_by_aria_current_and_the_accent_bar(): void
    {
        $html = $this->shell([
            ['label' => 'Overview', 'href' => '/', 'active' => false],
            ['label' => 'Elsewhere', 'href' => '/x', 'active' => true],
        ]);

        $this->assertSame(['Elsewhere'], Shell::of($html)->active());
        // The motif's bar is drawn from aria-current in the frame CSS.
        $css = (string) file_get_contents(base_path('packages/design-kit/resources/css/frame.css'));
        $this->assertStringContainsString(".dc-nav-item[aria-current='page'] {\n        border-inline-start-color: var(--ds-accent);\n        color: var(--ds-text-strong);", $css);
    }

    #[Test]
    public function a_count_shows_and_zero_shows_nothing(): void
    {
        $html = $this->shell([
            ['label' => 'Inbox', 'href' => '/inbox', 'badge' => 3],
            ['label' => 'Alerts', 'href' => '/alerts', 'badge' => 0],
        ]);

        $this->assertSame(1, substr_count($html, 'dc-nav-badge'));
    }

    #[Test]
    public function the_shell_follows_02(): void
    {
        $html = $this->shell([['label' => 'Home', 'href' => '/']]);

        // 16rem, on the sidebar role (sunken in light, surface in dark).
        $this->assertStringContainsString('w-64', $html);
        $this->assertStringContainsString('bg-(--ds-sidebar)', $html);
        $this->assertStringContainsString('lg:ps-64', $html);
        // No max width in the sidebar shell (05).
        $this->assertStringNotContainsString('max-w-7xl', $html);
        // The brand block is body type, not a serif under 24px (03).
        $this->assertStringNotContainsString('font-display', $html);

        Shell::assertConforms($html);
    }

    #[Test]
    public function navigate_adds_wire_navigate_and_contained_false_drops_the_gutter(): void
    {
        $html = $this->shell([['label' => 'Home', 'href' => '/']], ':navigate="true" :contained="false"');

        $this->assertStringContainsString('wire:navigate', $html);
        $this->assertStringNotContainsString('px-(--ds-gutter)', $html);
    }

    #[Test]
    public function the_conformance_check_fails_a_menu_beside_the_shell(): void
    {
        $html = $this->shell([['label' => 'Home', 'href' => '/']]).'<nav aria-label="Main"><a href="/">Home</a></nav>';

        $this->expectException(AssertionFailedError::class);
        Shell::assertConforms($html);
    }

    #[Test]
    public function the_flash_shows_each_message_in_its_own_tone(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['name' => ['A name is needed.']]));

        // Validation errors reach a component as shared view data, the way
        // Laravel's ShareErrorsFromSession puts them there.
        View::share('errors', $errors);
        $html = Blade::render('<x-ds::flash status="Saved." warning="Check this." error="It broke." />');

        $this->assertStringContainsString('var(--ds-success-soft)', $html);
        $this->assertStringContainsString('var(--ds-warning-soft)', $html);
        $this->assertStringContainsString('data-dc-flash="error"', $html);
        $this->assertStringContainsString('A name is needed.', $html);

        View::share('errors', new ViewErrorBag);
        $this->assertSame('', trim(Blade::render('<x-ds::flash :status="null" :warning="null" :error="null" />')));
    }

    #[Test]
    public function the_fonts_load_the_styles_two_faces(): void
    {
        $this->assertStringContainsString('family=inter:400,500,600|fraunces:500,600', Blade::render('<x-ds::fonts />'));
        $this->assertSame('', trim(Blade::render('<x-ds::fonts style="nope" />')));
    }

    #[Test]
    public function dashcore_ui_names_still_render(): void
    {
        // An app moving across keeps <x-dashcore::…>, `subtitle` and the
        // `actions` slot for one major.
        $html = Blade::render('<x-dashcore::page-header title="Runway" subtitle="Twelve months."><x-slot:actions><button>Export</button></x-slot:actions></x-dashcore::page-header>');

        $this->assertStringContainsString('Runway', $html);
        $this->assertStringContainsString('Twelve months.', $html);
        $this->assertStringContainsString('<button>Export</button>', $html);
        // dashcore/ui's marker, which apps' own tests look for.
        $this->assertStringContainsString('data-dc-page-header', $html);
        $this->assertStringContainsString('data-dc-account', Blade::render('<x-dashcore::account name="Ada" />'));
    }

    #[Test]
    public function dashcore_ui_classes_resolve_to_the_kits(): void
    {
        (new DesignKitServiceProvider($this->app))->register();

        $this->assertSame(Menu::class, (new \ReflectionClass('Dashcore\\Ui\\Menu'))->getName());
        $this->assertSame(Shell::class, (new \ReflectionClass('Dashcore\\Ui\\Testing\\Shell'))->getName());
    }
}
