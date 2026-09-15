<?php

namespace Tests\Feature;

use App\Design\Standard;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportEndpointTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string,mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'standard_version' => Standard::version(),
            'package_version' => 'v3.0.0',
            'design_slug' => 'harbor-point',
            'findings' => ['tap target under 36px' => 6],
            'total_findings' => 6,
            'files_with_findings' => 4,
            'files_checked' => 40,
            'reported_at' => now()->toIso8601String(),
        ], $overrides);
    }

    #[Test]
    public function a_site_reports_with_its_token(): void
    {
        $site = Site::factory()->create(['design_slug' => null]);
        $token = $site->issueToken();

        $response = $this->withToken($token)->postJson('/api/reports', $this->payload());

        $response->assertCreated();
        // The tool says what the standard reads today, so the app learns it is
        // behind by reporting rather than by asking separately.
        $response->assertJson(['standard_version' => Standard::version()]);

        $report = $site->reports()->sole();
        $this->assertSame(6, $report->total_findings);
        $this->assertSame(['tap target under 36px' => 6], $report->findings);

        // The site picks up the design it is running from the first report
        // that names one.
        $this->assertSame('harbor-point', $site->fresh()?->design_slug);
        $this->assertNotNull($site->fresh()?->last_reported_at);
    }

    #[Test]
    public function no_token_and_a_wrong_token_get_the_same_answer(): void
    {
        $this->postJson('/api/reports', $this->payload())->assertStatus(401);
        $this->withToken('not-a-real-token')->postJson('/api/reports', $this->payload())->assertStatus(401);

        // A site that was never added here cannot add itself.
        $this->assertSame(0, Site::query()->count());
    }

    #[Test]
    public function a_rotated_token_stops_reporting(): void
    {
        $site = Site::factory()->create();
        $old = $site->issueToken();
        $site->issueToken();

        $this->withToken($old)->postJson('/api/reports', $this->payload())->assertStatus(401);
        $this->assertSame(0, $site->reports()->count());
    }

    #[Test]
    public function the_total_is_recomputed_rather_than_believed(): void
    {
        $site = Site::factory()->create();
        $token = $site->issueToken();

        // A caller claiming nothing is wrong while sending 50 findings does not
        // get to have the page agree with it.
        $this->withToken($token)->postJson('/api/reports', $this->payload([
            'findings' => ['a' => 44, 'b' => 6],
            'total_findings' => 0,
        ]))->assertCreated();

        $this->assertSame(50, $site->reports()->sole()->total_findings);
    }

    #[Test]
    public function a_report_from_the_future_is_stamped_on_arrival(): void
    {
        $site = Site::factory()->create();
        $token = $site->issueToken();

        // A wrong clock in CI would otherwise sort above every real report for
        // ever, and the fleet view reads the newest one.
        $this->withToken($token)->postJson('/api/reports', $this->payload([
            'reported_at' => now()->addYears(5)->toIso8601String(),
        ]))->assertCreated();

        $this->assertTrue($site->reports()->sole()->reported_at->isBefore(now()->addMinute()));
    }

    #[Test]
    public function rubbish_is_refused_rather_than_stored(): void
    {
        $site = Site::factory()->create();
        $token = $site->issueToken();

        foreach ([
            ['standard_version' => 'not a hash'],
            ['design_slug' => '../../etc/passwd'],
            ['findings' => 'lots'],
            ['findings' => ['a rule' => 'many']],
            ['files_checked' => -1],
        ] as $bad) {
            $this->withToken($token)
                ->postJson('/api/reports', $this->payload($bad))
                ->assertStatus(422, json_encode($bad));
        }

        $this->assertSame(0, $site->reports()->count());
    }

    #[Test]
    public function a_rule_this_tool_has_never_heard_of_is_still_stored(): void
    {
        $site = Site::factory()->create();
        $token = $site->issueToken();

        // A kit can be running an older package, or a newer one. Matching rule
        // names against a list here would drop exactly the counts worth seeing.
        $this->withToken($token)->postJson('/api/reports', $this->payload([
            'findings' => ['a rule from some other version' => 3],
        ]))->assertCreated();

        $this->assertSame(3, $site->reports()->sole()->total_findings);
    }

    #[Test]
    public function an_app_with_nothing_wrong_reports_zero(): void
    {
        $site = Site::factory()->create();
        $token = $site->issueToken();

        $this->withToken($token)->postJson('/api/reports', $this->payload([
            'findings' => [],
            'total_findings' => 0,
            'files_with_findings' => 0,
        ]))->assertCreated();

        // Zero is an answer. A site that stops reporting is a different thing
        // from a site with nothing to report, and the page has to tell them apart.
        $this->assertSame(0, $site->reports()->sole()->total_findings);
        $this->assertNotNull($site->fresh()?->last_reported_at);
    }
}
