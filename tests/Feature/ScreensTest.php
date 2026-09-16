<?php

namespace Tests\Feature;

use App\Design\Screens;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScreensTest extends TestCase
{
    // A design is a row now, and the migration seeds the five in
    // database/seeders/designs as it creates the table.
    use RefreshDatabase;

    #[Test]
    public function every_listed_screen_has_a_mockup_and_renders(): void
    {
        foreach (array_keys(Screens::ALL) as $screen) {
            $this->assertFileExists(
                resource_path("views/mockups/{$screen}.blade.php"),
                "the switcher offers {$screen} with no mockup behind it"
            );

            $this->get("/designs/calder-street-veterinary?screen={$screen}")
                ->assertOk();
        }
    }

    #[Test]
    public function the_surfaces_the_standard_covers_are_all_shown(): void
    {
        // A rule nobody can look at is a rule nobody checks. Sections 10 and 11
        // were written before either had a screen.
        foreach (['email', 'errors'] as $screen) {
            $this->assertArrayHasKey($screen, Screens::ALL);
        }
    }

    #[Test]
    public function a_screen_from_the_url_cannot_reach_a_view_that_is_not_a_mockup(): void
    {
        // The value ends up in a Blade include, so it is resolved against the
        // list rather than trusted.
        $this->assertSame(Screens::DEFAULT, Screens::resolve('pages.designs.show'));
        $this->assertSame(Screens::DEFAULT, Screens::resolve('partials.shell'));
        $this->assertSame(Screens::DEFAULT, Screens::resolve(''));
        $this->assertSame(Screens::DEFAULT, Screens::resolve(null));
        $this->assertSame('email', Screens::resolve('email'));

        $this->get('/designs/calder-street-veterinary?screen=pages.designs.show')
            ->assertOk()
            ->assertSee('Recent activity');
    }
}
