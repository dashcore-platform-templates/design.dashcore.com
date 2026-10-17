<?php

namespace Dashcore\DesignKit\Tests;

use Dashcore\DesignKit\Allowance;
use Dashcore\DesignKit\Audit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AllowanceTest extends TestCase
{
    /** @return array{known: list<string>, rules: array<string,int>} */
    private function allowance(array $rules, ?array $known = null): array
    {
        return ['known' => $known ?? Audit::RULES, 'rules' => $rules];
    }

    #[Test]
    public function the_rule_list_is_every_rule_the_audit_raises(): void
    {
        // A rule missing from the list would be treated as new forever, and
        // never held to anything.
        preg_match_all("/finding\\('([^']+)'/", (string) file_get_contents(__DIR__.'/../src/Audit.php'), $m);

        $this->assertEqualsCanonicalizing(array_values(array_unique($m[1])), Audit::RULES);
    }

    #[Test]
    public function a_rise_fails_and_equal_or_fewer_passes(): void
    {
        $allowance = $this->allowance(['hardcoded colour' => 5]);

        $this->assertSame(['hardcoded colour' => [6, 5]], Allowance::compare(['hardcoded colour' => 6], $allowance)['rises']);
        $this->assertSame([], Allowance::compare(['hardcoded colour' => 5], $allowance)['rises']);
        $this->assertSame(['hardcoded colour' => [2, 5]], Allowance::compare(['hardcoded colour' => 2], $allowance)['below']);
    }

    #[Test]
    public function a_known_rule_the_app_had_none_of_is_held_at_zero(): void
    {
        $this->assertSame(['Flux badge' => [1, 0]], Allowance::compare(['Flux badge' => 1], $this->allowance([]))['rises']);
    }

    #[Test]
    public function a_rule_this_release_added_is_allowed_until_recorded(): void
    {
        $older = $this->allowance([], ['hardcoded colour']);

        $result = Allowance::compare(['Flux badge' => 9], $older);

        $this->assertSame([], $result['rises']);
        $this->assertSame(['Flux badge' => 9], $result['unrecorded']);
    }

    #[Test]
    public function recording_again_only_ever_lowers(): void
    {
        $old = $this->allowance(['hardcoded colour' => 5, 'Flux badge' => 2]);

        $new = json_decode(Allowance::record(['hardcoded colour' => 3, 'Flux badge' => 4], $old), true);

        $this->assertSame(['Flux badge' => 2, 'hardcoded colour' => 3], $new['rules']);
        $this->assertSame(Audit::RULES, $new['known']);
    }

    #[Test]
    public function recording_takes_a_new_rule_at_todays_count(): void
    {
        $new = json_decode(Allowance::record(['Flux badge' => 4], $this->allowance([], ['hardcoded colour'])), true);

        $this->assertSame(['Flux badge' => 4], $new['rules']);
    }
}
