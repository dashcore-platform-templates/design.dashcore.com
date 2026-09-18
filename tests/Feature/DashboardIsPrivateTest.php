<?php

namespace Tests\Feature;

use App\Models\PersonalToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Where the line is.
 *
 * The dashboard lists every Dashcore product, the designs behind them and how
 * far off the standard each one is. None of that is a public fact. The exports
 * are the exception, and they are an exception on purpose: a kit's CI pulls
 * its pack over plain HTTP with no session.
 */
class DashboardIsPrivateTest extends TestCase
{
    use RefreshDatabase;

    /** Every page a person looks at, in the order the nav offers them. */
    public const PAGES = [
        '/designs',
        '/designs/new',
        '/designs/dashcore',
        '/standard',
        '/changelog',
        '/connect',
        '/people',
        '/fleet',
        '/account',
        '/account/security',
    ];

    #[Test]
    public function every_page_sends_a_stranger_to_sign_in(): void
    {
        foreach (self::PAGES as $page) {
            $this->get($page)->assertRedirect('/login', "{$page} is open");
        }

        // The front door lands on one of them, so it needs no gate of its own.
        $this->get('/')->assertRedirect('/designs');
    }

    #[Test]
    public function no_page_can_be_added_without_one(): void
    {
        // The list above is written by hand and the next page will not be on
        // it. This reads the router instead, so a page added without the
        // middleware fails here rather than being found by a stranger.
        $open = collect(Route::getRoutes())
            ->filter(fn (RoutingRoute $route) => str_contains($route->getActionName(), 'LivewirePageController'))
            ->reject(fn (RoutingRoute $route) => in_array('auth', $route->gatherMiddleware(), true))
            ->map(fn (RoutingRoute $route) => $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $open, 'these pages carry no auth middleware');
    }

    #[Test]
    public function the_exports_take_a_token_rather_than_a_session(): void
    {
        // They cannot sit behind the page gate: design:apply fetches them from
        // a terminal, which has no session. They are not open either, because a
        // pack carries the brief. PullingADesignTest holds the rest.
        $this->getJson('/designs/dashcore/pack.json')->assertUnauthorized();

        $token = PersonalToken::issueFor(User::factory()->create(), 'Laptop');
        $this->withToken($token)->get('/designs/dashcore/pack.json')->assertOk();
    }

    #[Test]
    public function the_way_in_stays_open(): void
    {
        // A login behind a login is a locked building with the key inside.
        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
    }

    #[Test]
    public function a_signed_in_person_reaches_all_of_it(): void
    {
        // The other half of the same rule: a gate that turns the app off is not
        // a gate, it is an outage.
        $user = User::factory()->create();

        foreach (self::PAGES as $page) {
            $response = $this->actingAs($user)
                ->withSession(['auth.password_confirmed_at' => time()])
                ->get($page);

            $response->assertOk("{$page} does not render for somebody signed in");
        }
    }
}
