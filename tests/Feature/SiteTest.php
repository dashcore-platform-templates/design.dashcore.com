<?php

namespace Tests\Feature;

use App\Design\Standard;
use App\Models\Site;
use App\Models\SiteReport;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_token_is_shown_once_and_stored_as_a_hash(): void
    {
        $site = Site::factory()->create();
        $token = $site->issueToken();

        $this->assertSame(40, strlen($token));
        $this->assertNotSame($token, $site->token_hash, 'the token itself is never stored');
        $this->assertSame(hash('sha256', $token), $site->fresh()?->token_hash);

        // Nothing that renders a site can leak the hash by accident.
        $this->assertArrayNotHasKey('token_hash', $site->toArray());
    }

    #[Test]
    public function a_site_is_found_by_its_token_and_by_nothing_else(): void
    {
        $site = Site::factory()->create();
        $token = $site->issueToken();
        Site::factory()->create();

        $this->assertSame($site->id, Site::byToken($token)?->id);
        $this->assertSame($site->id, Site::byToken("  {$token}  ")?->id, 'a token pasted into CI picks up whitespace');

        $this->assertNull(Site::byToken('not-a-token'));
        $this->assertNull(Site::byToken(''));
        $this->assertNull(Site::byToken('   '));
        $this->assertNull(Site::byToken(strtoupper($token)), 'the hash is of the exact string');
    }

    #[Test]
    public function issuing_a_token_again_retires_the_old_one(): void
    {
        $site = Site::factory()->create();
        $first = $site->issueToken();
        $second = $site->issueToken();

        $this->assertNotSame($first, $second);
        $this->assertNull(Site::byToken($first), 'a rotated token stops working immediately');
        $this->assertSame($site->id, Site::byToken($second)?->id);
    }

    #[Test]
    public function a_site_can_be_created_without_inventing_a_token_first(): void
    {
        // Found by trying to add one: issueToken is what sets the hash, and the
        // column cannot be null, so creating a row needed a hash that only
        // creating a row could give you.
        $site = Site::create(['name' => 'New Project', 'repository' => 'dashcoretech/newproject.dashcore.com']);

        $this->assertNotEmpty($site->token_hash);

        // The token behind that hash has never existed, so the site cannot
        // report until somebody issues a real one. That is the right default.
        $token = $site->issueToken();
        $this->assertSame($site->id, Site::byToken($token)?->id);
    }

    #[Test]
    public function a_site_knows_where_its_secrets_are_set(): void
    {
        $site = Site::factory()->create(['repository' => 'dashcoretech/executiveos.dashcore.com']);

        // The row holds owner/name, so this is exact rather than assembled
        // from a convention about how repos happen to be named.
        $this->assertSame(
            'https://github.com/dashcoretech/executiveos.dashcore.com/settings/secrets/actions',
            $site->secretsUrl(),
        );
    }

    #[Test]
    public function two_sites_cannot_share_a_repository(): void
    {
        Site::factory()->create(['repository' => 'dashcoretech/executiveos.dashcore.com']);

        $this->expectException(QueryException::class);
        Site::factory()->create(['repository' => 'dashcoretech/executiveos.dashcore.com']);
    }

    #[Test]
    public function a_report_knows_whether_its_site_is_on_the_standard(): void
    {
        $current = SiteReport::factory()->create();
        $this->assertTrue($current->isCurrent());

        $behind = SiteReport::factory()->behind()->create();
        $this->assertFalse($behind->isCurrent());

        // No stamp is behind, not unknown. The stamp arrived with the standard,
        // so a file without one predates it. It still counts as applied,
        // because the slug says a design is there.
        $unstamped = SiteReport::factory()->create(['standard_version' => null]);
        $this->assertFalse($unstamped->isCurrent());
        $this->assertTrue($unstamped->hasDesign());

        // Neither stamp means design/ is not there at all, which is a repo
        // added to the board before anyone set it up.
        $bare = SiteReport::factory()->create(['standard_version' => null, 'design_slug' => null]);
        $this->assertFalse($bare->hasDesign());

        $this->assertSame(Standard::version(), $current->standard_version);
    }

    #[Test]
    public function findings_come_back_as_counts_by_rule(): void
    {
        $report = SiteReport::factory()
            ->withFindings(['Flux heading at section size' => 44, 'tap target under 36px' => 6])
            ->create();

        $this->assertSame(50, $report->total_findings);
        $this->assertSame(44, $report->fresh()?->findings['Flux heading at section size']);
        $this->assertIsArray($report->fresh()?->findings);
    }

    #[Test]
    public function a_sites_reports_are_kept_rather_than_overwritten(): void
    {
        $site = Site::factory()->create();

        SiteReport::factory()->for($site)->withFindings(['colour off the roles' => 244])->create(['reported_at' => now()->subDays(2)]);
        SiteReport::factory()->for($site)->withFindings(['colour off the roles' => 40])->create(['reported_at' => now()->subDay()]);
        SiteReport::factory()->for($site)->create(['reported_at' => now()]);

        // The question is not only how far off a site is, but whether it is
        // getting better, and that needs every report rather than the last.
        $this->assertSame(3, $site->reports()->count());
        $this->assertSame(0, $site->latestReport()->first()?->total_findings);
        $this->assertSame([0, 40, 244], $site->reports()->orderByDesc('reported_at')->pluck('total_findings')->all());
    }

    #[Test]
    public function deleting_a_site_takes_its_reports_with_it(): void
    {
        $site = Site::factory()->create();
        SiteReport::factory()->for($site)->count(3)->create();

        $site->delete();

        $this->assertSame(0, SiteReport::query()->count());
    }
}
