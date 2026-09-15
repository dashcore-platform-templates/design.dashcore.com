<?php

namespace App\Providers;

use App\Design\Generation\AnthropicDecisionSource;
use App\Design\Generation\DecisionSource;
use Carbon\CarbonImmutable;
use Dashcore\DesignKit\Commands\AuditCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DecisionSource::class, AnthropicDecisionSource::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->allowBuiltAssetsWhileDevServerRuns();
        $this->registerKitComponents();
        $this->auditThisAppToo();
    }

    /**
     * The package's own audit, so the tool is held to the standard it publishes.
     *
     * The package's command rather than a second implementation of it. Two
     * audits would drift, and this repo would end up checked against a slightly
     * different standard from the fleet, which is the exact failure the whole
     * project exists to stop.
     *
     * Only that one command. The package also ships design:apply, design:update
     * and its own design:check, and this app is not a kit: it has no design to
     * apply, and design:check here is a different command that measures a design
     * in the tool rather than a file in a repo.
     */
    protected function auditThisAppToo(): void
    {
        if ($this->app->runningInConsole() && class_exists(AuditCommand::class)) {
            $this->commands([AuditCommand::class]);
        }
    }

    /**
     * The package's own components, under <x-ds::…>, in the tool as well.
     *
     * The tool maps the package's namespace but does not install it, so its
     * service provider never runs and the components were unavailable here.
     * That is why the mockups rebuild page-header and badge by hand, and why
     * a mockup can drift from what a kit actually renders.
     *
     * This is the seam that lets the canvas stop guessing. The mockups are not
     * moved over yet; the fleet page uses them now.
     */
    protected function registerKitComponents(): void
    {
        $views = base_path('packages/design-kit/resources/views/ds');

        if (is_dir($views)) {
            Blade::anonymousComponentPath($views, 'ds');
        }
    }

    /**
     * Local only. While `npm run dev` is running, every page points at the Vite
     * dev server. A browser that cannot reach that address (the in-app preview,
     * a phone on the LAN) then gets no stylesheet at all. Adding ?built=1 to a
     * URL serves the last `npm run build` output instead, for that request.
     */
    protected function allowBuiltAssetsWhileDevServerRuns(): void
    {
        if (app()->environment('local') && request()->boolean('built')) {
            Vite::useHotFile(storage_path('framework/vite.hot.disabled'));
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
