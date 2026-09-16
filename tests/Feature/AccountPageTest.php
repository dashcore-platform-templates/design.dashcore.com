<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_invite_points_at_a_page_that_exists(): void
    {
        // It printed /user/profile, which is not a route in this app and never
        // was, so everybody invited was sent somewhere that 404s.
        $this->artisan('fleet:invite', ['name' => 'New Person', 'email' => 'new@dashcore.com'])
            ->expectsOutputToContain(route('account'))
            ->assertSuccessful();

        $this->actingAs(User::whereEmail('new@dashcore.com')->sole())
            ->get(route('account'))
            ->assertOk();
    }

    #[Test]
    public function the_account_is_behind_sign_in(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/account/security')->assertRedirect('/login');
    }

    #[Test]
    public function the_nav_links_to_the_account_only_for_people_who_can_open_it(): void
    {
        $this->get('/designs')->assertOk()->assertDontSee('>Account<', false);

        $this->actingAs(User::factory()->create())
            ->get('/designs')
            ->assertOk()
            ->assertSee('>Account<', false);
    }

    #[Test]
    public function the_account_page_renders(): void
    {
        // Asserting the route exists is not the same as loading it. This is the
        // page I told somebody to open when it did not exist at all.
        $this->actingAs(User::factory()->create(['email' => 'first@dashcore.com']))
            ->get('/account')
            ->assertOk()
            ->assertSee('first@dashcore.com');
    }

    #[Test]
    public function a_name_and_an_address_can_be_changed(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@dashcore.com']);

        Livewire::actingAs($user)
            ->test('pages::account.index')
            ->set('name', 'New Name')
            ->set('email', 'new@dashcore.com')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame('New Name', $user->refresh()->name);
        $this->assertSame('new@dashcore.com', $user->email);
    }

    #[Test]
    public function an_address_somebody_else_holds_is_refused(): void
    {
        User::factory()->create(['email' => 'taken@dashcore.com']);
        $user = User::factory()->create(['email' => 'mine@dashcore.com']);

        Livewire::actingAs($user)
            ->test('pages::account.index')
            ->set('email', 'taken@dashcore.com')
            ->call('saveProfile')
            ->assertHasErrors('email');

        $this->assertSame('mine@dashcore.com', $user->refresh()->email);
    }

    #[Test]
    public function the_password_changes_and_the_old_one_stops_working(): void
    {
        // The whole reason this module exists: fleet:invite prints a password
        // into somebody else's terminal, and until now there was nowhere to go
        // and change it.
        $user = User::factory()->create(['password' => Hash::make('the-invite-one')]);

        Livewire::actingAs($user)
            ->test('pages::account.index')
            ->set('current_password', 'the-invite-one')
            ->set('password', 'a-much-longer-one-99')
            ->set('password_confirmation', 'a-much-longer-one-99')
            ->call('savePassword')
            ->assertHasNoErrors()
            // Left in the component's state, a password is a password sitting
            // in the page payload.
            ->assertSet('password', '')
            ->assertSet('current_password', '');

        $this->assertTrue(Hash::check('a-much-longer-one-99', $user->refresh()->password));
    }

    #[Test]
    public function the_current_password_has_to_be_right(): void
    {
        $user = User::factory()->create(['password' => Hash::make('the-invite-one')]);

        Livewire::actingAs($user)
            ->test('pages::account.index')
            ->set('current_password', 'a-guess')
            ->set('password', 'a-much-longer-one-99')
            ->set('password_confirmation', 'a-much-longer-one-99')
            ->call('savePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('the-invite-one', $user->refresh()->password));
    }

    #[Test]
    public function security_asks_for_the_password_again(): void
    {
        // Everything on that page changes how somebody proves who they are, so
        // an open session is not enough to reach it.
        $this->actingAs(User::factory()->create())
            ->get('/account/security')
            ->assertRedirect(route('password.confirm'));

        // Asserting the redirect target is not the same as loading it. A gate
        // that 500s is a page nobody can reach.
        $this->actingAs(User::factory()->create())
            ->get(route('password.confirm'))
            ->assertOk();

        $this->actingAs(User::factory()->create())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/account/security')
            ->assertOk()
            ->assertSee('Two-factor');
    }

    #[Test]
    public function two_factor_goes_on_with_a_real_code_and_off_again(): void
    {
        $user = User::factory()->create();

        $page = Livewire::actingAs($user)->test('pages::account.security');

        $page->call('enableTwoFactor');
        $this->assertNotNull($user->refresh()->two_factor_secret);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());

        $page->set('code', '000000')->call('confirmTwoFactor')->assertHasErrors('code');
        $this->assertFalse($user->refresh()->hasEnabledTwoFactorAuthentication());

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $page->set('code', app(Google2FA::class)->getCurrentOtp($secret))
            ->call('confirmTwoFactor')
            ->assertHasNoErrors()
            ->assertSet('showingRecoveryCodes', true);

        $this->assertTrue($user->refresh()->hasEnabledTwoFactorAuthentication());
        $this->assertCount(8, $user->recoveryCodes());

        $page->call('disableTwoFactor');
        $this->assertNull($user->refresh()->two_factor_secret);
    }

    #[Test]
    public function a_passkey_can_be_removed(): void
    {
        $user = User::factory()->create();
        $passkey = $user->passkeys()->create([
            'name' => 'Chrome on Windows',
            'credential_id' => 'abc123',
            'credential' => ['aaguid' => 'nope'],
        ]);

        Livewire::actingAs($user)
            ->test('pages::account.security')
            ->assertSee('Chrome on Windows')
            ->call('forgetPasskey', $passkey->id)
            ->assertDontSee('Chrome on Windows');

        $this->assertSame(0, $user->passkeys()->count());
    }

    #[Test]
    public function one_account_cannot_remove_another_accounts_passkey(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $passkey = $theirs->passkeys()->create([
            'name' => 'Theirs',
            'credential_id' => 'xyz789',
            'credential' => ['aaguid' => 'nope'],
        ]);

        Livewire::actingAs($mine)
            ->test('pages::account.security')
            ->call('forgetPasskey', $passkey->id);

        $this->assertSame(1, $theirs->passkeys()->count());
    }
}
