<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->allowBuiltAssetsWhileDevServerRuns();
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
