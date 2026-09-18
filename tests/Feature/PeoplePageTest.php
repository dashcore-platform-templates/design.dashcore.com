<?php

namespace Tests\Feature;

use App\Models\PersonalToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Adding a person without opening a terminal on the production host.
 *
 * `fleet:invite` still exists and still works. It needed somebody with shell
 * access to the live site, and it printed a password, so the first password
 * every account had was one another person had read.
 */
class PeoplePageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function inviting_someone_hands_back_a_link_and_no_password(): void
    {
        $page = Livewire::actingAs(User::factory()->create())
            ->test('pages::people')
            ->set('name', 'Travis')
            ->set('email', 'travis@dashcore.com')
            ->call('invite')
            ->assertHasNoErrors();

        $invited = User::whereEmail('travis@dashcore.com')->sole();
        $this->assertSame('Travis', $invited->name);

        // A link, not a password. They choose their own and nobody sees it.
        $link = $page->get('link');
        $this->assertStringContainsString('/reset-password/', $link);
        $this->assertStringContainsString('travis%40dashcore.com', $link);
        $page->assertSee('Send this to Travis');
    }

    #[Test]
    public function the_link_actually_lets_them_in(): void
    {
        // The whole thing is worthless if the link does not work, so this
        // follows it rather than checking its shape.
        $page = Livewire::actingAs(User::factory()->create())
            ->test('pages::people')
            ->set('name', 'Travis')
            ->set('email', 'travis@dashcore.com')
            ->call('invite');

        $link = $page->get('link');

        // Signed out, because that is who opens an invite. Fortify pushes an
        // authenticated visitor off its guest pages, so staying signed in as
        // the inviter tests nothing Travis will ever do.
        auth()->logout();
        session()->flush();

        $this->get($link)->assertOk();

        $invited = User::whereEmail('travis@dashcore.com')->sole();
        $token = Password::broker()->createToken($invited);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'travis@dashcore.com',
            'password' => 'a-password-they-picked-99',
            'password_confirmation' => 'a-password-they-picked-99',
        ])->assertSessionHasNoErrors();

        $this->post('/login', [
            'email' => 'travis@dashcore.com',
            'password' => 'a-password-they-picked-99',
        ])->assertRedirect('/fleet');
    }

    #[Test]
    public function the_same_address_is_not_invited_twice(): void
    {
        User::factory()->create(['email' => 'taken@dashcore.com']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::people')
            ->set('name', 'Again')
            ->set('email', 'taken@dashcore.com')
            ->call('invite')
            ->assertHasErrors('email');

        $this->assertSame(2, User::query()->count());
    }

    #[Test]
    public function a_stale_link_can_be_replaced(): void
    {
        $user = User::factory()->create(['name' => 'Travis']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::people')
            ->call('newLink', $user->id)
            ->assertSee('Send this to Travis');
    }

    #[Test]
    public function nobody_locks_themselves_out(): void
    {
        $me = User::factory()->create();
        User::factory()->create();

        Livewire::actingAs($me)
            ->test('pages::people')
            ->call('remove', $me->id);

        $this->assertNotNull(User::find($me->id));
    }

    #[Test]
    public function the_last_person_cannot_be_removed(): void
    {
        // There is no sign-up form, so an empty users table is a locked
        // building with nobody holding a key.
        $only = User::factory()->create();
        $other = User::factory()->create();

        Livewire::actingAs($only)->test('pages::people')->call('remove', $other->id);
        $this->assertSame(1, User::query()->count());

        Livewire::actingAs($only)->test('pages::people')->call('remove', $only->id);
        $this->assertSame(1, User::query()->count());
    }

    #[Test]
    public function removing_somebody_takes_their_tokens(): void
    {
        $me = User::factory()->create();
        $them = User::factory()->create(['name' => 'Leaving']);
        PersonalToken::issueFor($them, 'Their laptop');

        Livewire::actingAs($me)
            ->test('pages::people')
            ->call('remove', $them->id)
            ->assertDontSee('Leaving');

        $this->assertSame(0, PersonalToken::query()->count());
    }

    #[Test]
    public function a_stranger_cannot_invite_or_remove(): void
    {
        $user = User::factory()->create();

        Livewire::test('pages::people')->call('newLink', $user->id)->assertForbidden();
        Livewire::test('pages::people')->call('remove', $user->id)->assertForbidden();

        $this->assertNotNull(User::find($user->id));
    }
}
