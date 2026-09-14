<?php

namespace Tests\Unit;

use App\Design\Pack;
use App\Design\Standard;
use Dashcore\DesignKit\Source;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The stamp is the only way a site already on the standard finds out the rules
 * moved. Applying overwrites the files either way, so without it the change is
 * silent and nobody reads 50,000 characters of markdown to find the new lines.
 */
class StandardVersionTest extends TestCase
{
    #[Test]
    public function the_version_is_the_standard_hashed_rather_than_a_number_to_remember(): void
    {
        $this->assertSame(substr(sha1(Standard::full()), 0, 8), Standard::version());
        $this->assertSame(Standard::version(), Standard::version(), 'the same text must give the same stamp');
    }

    #[Test]
    public function the_changelog_is_shipped_but_is_not_a_section_of_the_standard(): void
    {
        $this->assertStringContainsString('What changed in the standard', Standard::changelog());

        // If it were a section it would enter the generator prompt and make the
        // version circular, since the version hashes the sections.
        foreach (Standard::sections() as $section) {
            $this->assertNotSame('CHANGELOG.md', $section['file']);
        }
        $this->assertStringNotContainsString('What changed in the standard', Standard::full());
    }

    #[Test]
    public function the_package_reads_back_the_stamp_the_tool_writes(): void
    {
        $standard = Pack::standardFile();

        $this->assertSame(Standard::version(), Source::standardVersion($standard));
    }

    #[Test]
    public function a_standard_from_before_stamping_reads_as_unknown_rather_than_failing(): void
    {
        $this->assertNull(Source::standardVersion("# The Dashcore Standard\n\nNo stamp here.\n"));
        $this->assertNull(Source::standardVersion(''));
    }

    #[Test]
    public function changing_the_standard_changes_the_stamp(): void
    {
        $before = Source::standardVersion(Pack::standardFile());
        $after = substr(sha1(Standard::full()."\n\nA new rule.\n"), 0, 8);

        $this->assertNotSame($before, $after);
    }
}
