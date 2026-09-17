<?php

namespace Tests\Feature;

use App\Models\PersonalToken;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who may read a pack.
 *
 * It carries the brief, which names the client and says what their product
 * does. That is the one genuinely private thing in an export, and a slug used
 * to be enough to read it.
 */
class PullingADesignTest extends TestCase
{
    use RefreshDatabase;

    private const EXPORTS = [
        '/designs/harbor-point/pack.json',
        '/designs/harbor-point/design.json',
        '/designs/harbor-point/pack.zip',
    ];

    #[Test]
    public function a_slug_is_no_longer_enough(): void
    {
        foreach (self::EXPORTS as $export) {
            $this->getJson($export)->assertUnauthorized();
        }
    }

    #[Test]
    public function the_refusal_says_where_to_get_a_token(): void
    {
        // Almost always read by somebody at a terminal, so it is the fix rather
        // than the word Unauthorized.
        $this->getJson('/designs/harbor-point/pack.json')
            ->assertUnauthorized()
            ->assertJsonFragment(['message' => 'This needs your token. Make one at '.rtrim(config('app.url'), '/').'/account and put it in this app\'s .env as DESIGN_TOKEN.']);
    }

    #[Test]
    public function a_personal_token_gets_through(): void
    {
        $token = PersonalToken::issueFor(User::factory()->create(), 'Work laptop');

        foreach (self::EXPORTS as $export) {
            $this->withToken($token)->get($export)->assertOk();
        }
    }

    #[Test]
    public function a_signed_in_person_gets_through_without_one(): void
    {
        // Download pack on the design page is a browser following a link, and
        // a browser has a session rather than a bearer token.
        $this->actingAs(User::factory()->create())
            ->get('/designs/harbor-point/pack.json')
            ->assertOk();
    }

    #[Test]
    public function a_revoked_token_stops_working(): void
    {
        $user = User::factory()->create();
        $token = PersonalToken::issueFor($user, 'Old laptop');

        $this->withToken($token)->get('/designs/harbor-point/pack.json')->assertOk();

        $user->personalTokens()->delete();

        $this->withToken($token)->getJson('/designs/harbor-point/pack.json')->assertUnauthorized();
    }

    #[Test]
    public function a_repository_token_is_not_a_way_in(): void
    {
        // A site's token is for reporting. A repository never pulls, so one
        // that leaked out of a CI log still cannot read anybody's brief.
        $site = Site::factory()->create();

        $this->withToken($site->issueToken())
            ->getJson('/designs/harbor-point/pack.json')
            ->assertUnauthorized();
    }

    #[Test]
    public function the_pack_is_still_byte_for_byte_what_it_was(): void
    {
        // Gating it must not change it. Kits compare these bytes.
        $token = PersonalToken::issueFor(User::factory()->create(), 'Laptop');

        $this->assertSame(
            file_get_contents(base_path('packages/design-kit/tests/fixtures/harbor-point.json')),
            $this->withToken($token)->get('/designs/harbor-point/design.json')->getContent()
        );
    }
}
