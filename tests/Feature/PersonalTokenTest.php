<?php

namespace Tests\Feature;

use App\Models\PersonalToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A person's own credential, for the things a person runs.
 *
 * CI proves which repository it is to GitHub and carries nothing. This is what
 * `design:apply` and `design:update` use on a laptop, where there is nothing
 * to prove it to.
 */
class PersonalTokenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_token_is_shown_once_and_never_stored(): void
    {
        $user = User::factory()->create();

        $page = Livewire::actingAs($user)
            ->test('pages::account.index')
            ->set('tokenName', 'Work laptop')
            ->call('issueToken')
            ->assertHasNoErrors();

        $issued = $page->get('issued');

        $this->assertNotNull($issued);
        $page->assertSee($issued);

        // What is kept is a hash, so nothing here can show it back to somebody
        // who lost it, only replace it.
        $row = $user->personalTokens()->sole();
        $this->assertSame('Work laptop', $row->name);
        $this->assertNotSame($issued, $row->token_hash);
        $this->assertSame(PersonalToken::hash($issued), $row->token_hash);
    }

    #[Test]
    public function it_says_what_it_is_in_the_value(): void
    {
        // One found in a log or a pasted file should read as a credential
        // rather than as forty characters of noise.
        $token = PersonalToken::issueFor(User::factory()->create(), 'Laptop');

        $this->assertStringStartsWith('dsn_', $token);
    }

    #[Test]
    public function a_token_finds_its_own_row_and_nothing_else(): void
    {
        $mine = PersonalToken::issueFor(User::factory()->create(), 'Mine');

        $this->assertNotNull(PersonalToken::byToken($mine));
        $this->assertNull(PersonalToken::byToken('dsn_'.str_repeat('a', 40)));
        $this->assertNull(PersonalToken::byToken(''));
        // Refused on the prefix, before it costs a query.
        $this->assertNull(PersonalToken::byToken(str_repeat('a', 44)));
    }

    #[Test]
    public function using_one_is_stamped_but_not_on_every_call(): void
    {
        $token = PersonalToken::issueFor(User::factory()->create(), 'Laptop');

        $first = PersonalToken::byToken($token);
        $this->assertNotNull($first->last_used_at);

        // A pull would otherwise be a write every time, and the question this
        // answers does not need to be answered to the second.
        $stamped = $first->last_used_at;
        PersonalToken::byToken($token);

        $this->assertTrue($stamped->equalTo(PersonalToken::byToken($token)->last_used_at));
    }

    #[Test]
    public function a_name_is_required(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::account.index')
            ->set('tokenName', '')
            ->call('issueToken')
            ->assertHasErrors('tokenName');

        $this->assertSame(0, PersonalToken::query()->count());
    }

    #[Test]
    public function revoking_one_stops_it_working(): void
    {
        $user = User::factory()->create();
        $token = PersonalToken::issueFor($user, 'Old laptop');
        $row = $user->personalTokens()->sole();

        Livewire::actingAs($user)
            ->test('pages::account.index')
            ->call('revokeToken', $row->id)
            ->assertDontSee('Old laptop');

        $this->assertNull(PersonalToken::byToken($token));
    }

    #[Test]
    public function one_person_cannot_revoke_another_persons_token(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        PersonalToken::issueFor($theirs, 'Theirs');
        $row = $theirs->personalTokens()->sole();

        Livewire::actingAs($mine)
            ->test('pages::account.index')
            ->call('revokeToken', $row->id);

        $this->assertSame(1, $theirs->personalTokens()->count());
    }

    // There is no signed-out test here on purpose. The account page is behind
    // auth, which DashboardIsPrivateTest holds, and the component reads the
    // signed-in person to render at all, so a guest never reaches the methods.
    // Both carry abort_unless anyway, for the same reason the design pages do.

    #[Test]
    public function losing_an_account_takes_its_tokens(): void
    {
        $user = User::factory()->create();
        $token = PersonalToken::issueFor($user, 'Laptop');

        $user->delete();

        $this->assertNull(PersonalToken::byToken($token));
        $this->assertSame(0, DB::table('personal_tokens')->count());
    }
}
