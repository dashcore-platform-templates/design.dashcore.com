<?php

namespace Tests\Feature;

use App\Models\User;
use Dashcore\Bridge\Sso\DatabaseUserResolver;
use Dashcore\Bridge\Sso\SsoIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The sign-in server's `sub` is how a person is known here after their first
 * fleet sign-in. These pin the column the bridge's resolver writes it to: an
 * address is matched once, the handle is written down, and a later change of
 * address still lands on the same account.
 */
class SsoSubjectTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_first_fleet_sign_in_links_an_existing_account_by_its_subject(): void
    {
        $user = User::factory()->create(['email' => 'pat@example.com']);

        $resolved = app(DatabaseUserResolver::class)->resolve($this->identity('sub-123', 'pat@example.com'));

        $this->assertNotNull($resolved);
        $this->assertSame($user->id, $resolved->getAuthIdentifier());
        $this->assertSame('sub-123', $user->fresh()?->sso_subject);
    }

    #[Test]
    public function a_person_who_changed_their_address_is_still_found_by_their_subject(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $user->forceFill(['sso_subject' => 'sub-123'])->save();

        $resolved = app(DatabaseUserResolver::class)->resolve($this->identity('sub-123', 'new@example.com'));

        $this->assertNotNull($resolved);
        $this->assertSame($user->id, $resolved->getAuthIdentifier());
    }

    #[Test]
    public function nobody_is_created_for_an_address_this_app_has_never_seen(): void
    {
        $this->assertNull(app(DatabaseUserResolver::class)->resolve($this->identity('sub-999', 'stranger@example.com')));
        $this->assertFalse(User::whereEmail('stranger@example.com')->exists());
    }

    #[Test]
    public function the_subject_is_neither_mass_assignable_nor_serialised(): void
    {
        $user = User::factory()->create();
        $user->fill(['sso_subject' => 'sub-forged'])->save();

        $this->assertNull($user->fresh()?->sso_subject);

        $user->forceFill(['sso_subject' => 'sub-123'])->save();

        $this->assertArrayNotHasKey('sso_subject', $user->fresh()?->toArray() ?? []);
    }

    private function identity(string $subject, string $email): SsoIdentity
    {
        return new SsoIdentity($subject, $email, true, null, null, []);
    }
}
