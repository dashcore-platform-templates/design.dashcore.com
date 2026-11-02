<?php

namespace Tests\Feature;

use App\Design\Blocks;
use App\Design\Design;
use App\Design\Standard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * registry reached production with no sign-in through login and nothing
 * noticed. The must is in the standard now, and the sign-in every design hands
 * over leads with it, so an app copying the drawing gets the button too.
 */
class LoginSignInStandardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_standard_states_the_login_sign_in_must(): void
    {
        $full = Standard::full();

        $this->assertStringContainsString('## Signing in', $full);
        $this->assertStringContainsString('signs them in through the fleet\'s login', $full);
        $this->assertStringContainsString('**Existing accounts only.**', $full);
        $this->assertStringContainsString('**No public registration.**', $full);
        $this->assertStringContainsString('**Sign in with Dashcore**', $full);
    }

    #[Test]
    public function the_must_stays_out_of_the_generator_prompt(): void
    {
        // The generator writes copy, not the form, and a client design must
        // never learn the fleet's rules from it.
        $this->assertStringNotContainsString('Sign in with Dashcore', Standard::prompt());
    }

    #[Test]
    public function the_changelog_says_who_has_to_act(): void
    {
        $this->assertStringContainsString('**Fleet apps sign in through the fleet\'s login.**', Standard::changelog());
    }

    #[Test]
    public function the_sign_in_block_leads_with_the_login_button_and_offers_no_sign_up(): void
    {
        $html = Blocks::draw('sign-in', Design::load(Design::OWN));

        $this->assertStringContainsString('Sign in with Dashcore', $html);
        $this->assertLessThan(
            strpos($html, 'type="email"'),
            strpos($html, 'Sign in with Dashcore'),
            'the login button comes before the email form',
        );
        $this->assertStringNotContainsStringIgnoringCase('sign up', $html);
        $this->assertStringNotContainsStringIgnoringCase('register', $html);
        $this->assertStringNotContainsStringIgnoringCase('create an account', $html);
    }
}
