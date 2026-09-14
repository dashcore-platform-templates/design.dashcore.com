<?php

namespace Dashcore\DesignKit\Tests;

use Dashcore\DesignKit\Source;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

class SourceTest extends TestCase
{
    #[Test]
    public function it_tells_a_slug_from_a_url_from_a_file(): void
    {
        $this->assertSame('slug', Source::kind('harbor-point'));
        $this->assertSame('url', Source::kind('https://design.dashcore.com/designs/harbor-point/pack.json'));
        $this->assertSame('url', Source::kind('http://design.dashcore.com.test/designs/harbor-point/pack.json'));
        $this->assertSame('file', Source::kind('design/design.json'));
        $this->assertSame('file', Source::kind('design.json'));
        $this->assertSame('file', Source::kind('C:\\repo\\design\\design.json'));
    }

    #[Test]
    public function a_slug_becomes_the_manifest_url_on_the_configured_source(): void
    {
        $this->assertSame('https://design.dashcore.com/designs/harbor-point/pack.json', Source::url('harbor-point', 'https://design.dashcore.com/'));
        $this->assertSame('http://design.dashcore.com.test/designs/meridian-credit-union/pack.json', Source::url('meridian-credit-union', 'http://design.dashcore.com.test'));
    }

    #[Test]
    public function a_manifest_stands_for_its_four_files_and_a_bare_design_for_one(): void
    {
        $manifest = ['schema' => Source::MANIFEST_SCHEMA, 'slug' => 'x', 'files' => ['design.json' => '{"tokens":{}}', 'STANDARD.md' => '# S', 'CLAUDE.snippet.md' => '## D', 'MIGRATION.md' => '# M']];
        $this->assertSame(['design.json', 'STANDARD.md', 'CLAUDE.snippet.md', 'MIGRATION.md'], array_keys(Source::files($manifest)));

        $bare = ['schema' => 'dashcore-design/1', 'tokens' => ['light' => []]];
        $files = Source::files($bare);
        $this->assertSame(['design.json'], array_keys($files));
        $this->assertSame($bare, json_decode($files['design.json'], true));
    }

    #[Test]
    public function a_manifest_cannot_write_outside_design(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Source::files(['schema' => Source::MANIFEST_SCHEMA, 'files' => ['design.json' => '{}', '../.env' => 'APP_KEY=x']]);
    }

    #[Test]
    public function a_pack_states_its_standard_version_or_the_stamp_is_read_out_of_the_standard(): void
    {
        $stamped = '# The Dashcore Standard'."\n\n".Source::VERSION_LINE.'a1b2c3d4'."\n";

        $this->assertSame('a1b2c3d4', Source::publishedStandardVersion(['standard_version' => 'a1b2c3d4']));

        // Packs made before the manifest stated it still carry the stamp inside
        // STANDARD.md. Reading that keeps an older app able to tell it is behind.
        $this->assertSame('a1b2c3d4', Source::publishedStandardVersion(['files' => ['STANDARD.md' => $stamped]]));

        // What the manifest states wins, because it is what the tool published,
        // and a file inside the pack could have been edited after the fact.
        $this->assertSame('a1b2c3d4', Source::publishedStandardVersion(['standard_version' => 'a1b2c3d4', 'files' => ['STANDARD.md' => str_replace('a1b2c3d4', 'deadbeef', $stamped)]]));
    }

    #[Test]
    public function a_pack_that_states_no_version_says_so_rather_than_guessing(): void
    {
        $this->assertNull(Source::publishedStandardVersion([]));
        $this->assertNull(Source::publishedStandardVersion(['standard_version' => 'not a hash']));
        $this->assertNull(Source::publishedStandardVersion(['files' => ['STANDARD.md' => '# The Dashcore Standard, unstamped']]));
        $this->assertNull(Source::publishedStandardVersion(['files' => 'not an array']));
    }

    #[Test]
    public function anything_else_is_refused(): void
    {
        $this->expectExceptionMessage('neither a design pack nor a design file');
        Source::files(['hello' => 'world']);
    }
}
