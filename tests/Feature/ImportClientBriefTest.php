<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * fleets hands each app of a new client fleet its brief. login is the only
 * way in and apps never make accounts on sign-in, so without this the owner
 * signs in to login and design turns them away.
 */
class ImportClientBriefTest extends TestCase
{
    use RefreshDatabase;

    /** @param  list<array<string, mixed>>  $admins */
    private function brief(array $admins): string
    {
        return base64_encode((string) json_encode([
            'format' => 'dashcore.client-brief/1',
            'company' => ['name' => 'Sarah Practice Consulting'],
            'admins' => $admins,
            'staff' => [],
        ]));
    }

    /** @return list<array<string, mixed>> */
    private function admins(): array
    {
        return [
            ['name' => 'Maria Lopez', 'email' => 'maria@example.com'],
            ['name' => 'Sarah Practice', 'email' => 'Sarah@Dashcore.com ', 'owner' => true],
            ['name' => null, 'email' => 'james.smith@example.com'],
            ['name' => 'Nobody', 'email' => 'not-an-address'],
        ];
    }

    #[Test]
    public function each_admin_gets_a_verified_account_owner_first(): void
    {
        $this->artisan('brief:import', ['--base64' => $this->brief($this->admins())])
            ->expectsOutput('Accounts made: sarah@dashcore.com, maria@example.com, james.smith@example.com.')
            ->assertSuccessful();

        $this->assertSame(
            ['sarah@dashcore.com', 'maria@example.com', 'james.smith@example.com'],
            User::query()->orderBy('id')->pluck('email')->all(),
        );

        $sarah = User::whereEmail('sarah@dashcore.com')->sole();
        $this->assertSame('Sarah Practice', $sarah->name);
        $this->assertNotNull($sarah->email_verified_at);
        $this->assertSame('James Smith', User::whereEmail('james.smith@example.com')->sole()->name);
    }

    #[Test]
    public function without_an_owner_flag_the_order_stands(): void
    {
        $this->artisan('brief:import', ['--base64' => $this->brief([
            ['name' => 'Sarah', 'email' => 'sarah@dashcore.com'],
            ['name' => 'Maria', 'email' => 'maria@example.com'],
        ])])->assertSuccessful();

        $this->assertSame(['sarah@dashcore.com', 'maria@example.com'], User::query()->orderBy('id')->pluck('email')->all());
    }

    #[Test]
    public function running_it_again_makes_nobody_twice(): void
    {
        $brief = $this->brief($this->admins());

        $this->artisan('brief:import', ['--base64' => $brief])->assertSuccessful();
        $this->artisan('brief:import', ['--base64' => $brief])
            ->expectsOutput('Already had an account, left alone: sarah@dashcore.com, maria@example.com, james.smith@example.com.')
            ->assertSuccessful();

        $this->assertSame(3, User::count());
    }

    #[Test]
    public function an_existing_account_is_left_alone(): void
    {
        $existing = User::factory()->unverified()->create([
            'name' => 'Sarah P',
            'email' => 'SARAH@dashcore.com',
            'password' => Hash::make('her-own-password'),
        ]);

        $this->artisan('brief:import', ['--base64' => $this->brief($this->admins())])->assertSuccessful();

        $existing->refresh();
        $this->assertTrue(Hash::check('her-own-password', $existing->password));
        $this->assertSame('Sarah P', $existing->name);
        $this->assertNull($existing->email_verified_at);
        $this->assertSame(1, User::query()->whereRaw('lower(email) = ?', ['sarah@dashcore.com'])->count());
    }

    #[Test]
    public function nobody_is_emailed_and_no_password_is_printed(): void
    {
        Mail::fake();
        Notification::fake();

        $this->artisan('brief:import', ['--base64' => $this->brief($this->admins())])
            ->doesntExpectOutputToContain('Password')
            ->assertSuccessful();

        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
    }

    #[Test]
    public function a_file_works_the_same(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'brief');
        file_put_contents((string) $path, base64_decode($this->brief($this->admins())));

        $this->artisan('brief:import', ['--file' => $path])->assertSuccessful();
        unlink((string) $path);

        $this->assertSame(3, User::count());
    }

    #[Test]
    public function something_that_is_not_a_brief_is_refused(): void
    {
        $this->artisan('brief:import', ['--base64' => base64_encode('{"format":"something/else","admins":[{"email":"a@b.com"}]}')])
            ->expectsOutputToContain('Not a client brief')
            ->assertFailed();

        $this->artisan('brief:import', ['--base64' => 'not base64 !!'])->assertFailed();
        $this->artisan('brief:import')->assertFailed();

        $this->assertSame(0, User::count());
    }

    #[Test]
    public function a_brief_with_no_admins_does_nothing(): void
    {
        $this->artisan('brief:import', ['--base64' => $this->brief([])])
            ->expectsOutput('Nothing for design in this brief.')
            ->assertSuccessful();

        $this->assertSame(0, User::count());
    }
}
