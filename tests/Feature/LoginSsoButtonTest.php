<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A fleet running sign-in in optional mode held the owner's account by subject,
 * but the sign-in page had no way to start it, so nobody could get in through
 * login. The button shows once the bridge registers its redirect route, names
 * the install's company, and leaves the password form as the break-glass way in.
 */
class LoginSsoButtonTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_button_is_absent_while_fleet_sign_in_is_off(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('data-test="sso-button"', false)
            ->assertSee('name="password"', false);
    }

    #[Test]
    public function the_button_leads_to_the_fleet_sign_in_when_it_is_on(): void
    {
        $this->registerSsoRoute();

        $this->withSession(['bridge.sso.error' => 'You cannot open this app.'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in with Dashcore')
            ->assertSee('href="'.route('bridge.sso.redirect').'"', false)
            ->assertSee('You cannot open this app.')
            ->assertSee('name="password"', false);
    }

    #[Test]
    public function the_button_names_the_installs_company(): void
    {
        config(['fleet.company.name' => 'Sarah Practice Consulting']);
        $this->registerSsoRoute();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in with Sarah Practice Consulting')
            ->assertDontSee('Sign in with Dashcore');
    }

    private function registerSsoRoute(): void
    {
        // The bridge registers this route at boot when BRIDGE_SSO_MODE is set.
        Route::get('auth/sso/redirect', fn () => '')->name('bridge.sso.redirect');
        app('router')->getRoutes()->refreshNameLookups();
    }
}
