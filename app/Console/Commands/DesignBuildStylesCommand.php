<?php

namespace App\Console\Commands;

use App\Design\Pack;
use App\Design\Standard;
use App\Design\Styles;
use Dashcore\DesignKit\Stylesheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Writes each built-in style into the package: its tokens as JSON and the
 * stylesheet an app imports from vendor/. Writes the standard beside them, as
 * STANDARD.md, the same file a design pack carries. Both are committed, because the
 * package is published as a subtree split and has no build step of its own.
 *
 * A test fails when the committed files differ from a fresh build, so a change
 * to the style that nobody rebuilt cannot reach the fleet half-done.
 */
class DesignBuildStylesCommand extends Command
{
    protected $signature = 'design:build-styles';

    protected $description = 'Build the built-in styles into packages/design-kit (styles/*.json and dist/*.css)';

    public function handle(): int
    {
        foreach (Styles::names() as $name) {
            [$json, $css] = self::build($name);

            File::ensureDirectoryExists(dirname(self::jsonPath($name)));
            File::ensureDirectoryExists(dirname(self::cssPath($name)));
            File::put(self::jsonPath($name), $json);
            File::put(self::cssPath($name), $css);

            $this->components->info("Built {$name}: lowest contrast ".Styles::get($name)['contrast']['lowest']['ratio'].':1');
        }

        // The standard itself ships with the package, so an app on a style has
        // the rules its agents read, and a stamp to report, without a pack.
        File::put(self::standardPath(), Pack::standardFile());
        $this->components->info('Wrote the standard, '.Standard::version().'.');

        return self::SUCCESS;
    }

    /** @return array{0:string,1:string} The style's JSON and its stylesheet. */
    public static function build(string $name): array
    {
        $tokens = Styles::get($name);

        $json = json_encode(['schema' => 'dashcore-style/1', 'style' => $name, 'motif' => Styles::MOTIFS[$name], 'tokens' => $tokens], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

        return [$json, Stylesheet::build($name, $tokens)];
    }

    public static function standardPath(): string
    {
        return base_path('packages/design-kit/STANDARD.md');
    }

    public static function jsonPath(string $name): string
    {
        return base_path("packages/design-kit/styles/{$name}.json");
    }

    public static function cssPath(string $name): string
    {
        return base_path("packages/design-kit/dist/{$name}.css");
    }
}
