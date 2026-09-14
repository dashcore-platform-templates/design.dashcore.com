<?php

namespace Dashcore\DesignKit;

use Dashcore\DesignKit\Commands\ApplyCommand;
use Dashcore\DesignKit\Commands\AuditCommand;
use Dashcore\DesignKit\Commands\CheckCommand;
use Dashcore\DesignKit\Commands\UpdateCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Installs into a Laravel Livewire Flux kit. Registers the three commands, the
 * brand config the sign-in page reads, the source design:apply pulls from, the
 * five custom components from standard/06 under <x-ds::…>, and the two-panel
 * sign-in layout as <x-design-kit::layouts.auth>.
 */
class DesignKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/brand.php', 'brand');
        $this->mergeConfigFrom(__DIR__.'/../config/design-kit.php', 'design-kit');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'design-kit');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/ds', 'ds');

        $this->publishes([
            __DIR__.'/../config/brand.php' => config_path('brand.php'),
            __DIR__.'/../config/design-kit.php' => config_path('design-kit.php'),
        ], 'design-kit-config');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/design-kit')], 'design-kit-views');

        if ($this->app->runningInConsole()) {
            $this->commands([ApplyCommand::class, AuditCommand::class, CheckCommand::class, UpdateCommand::class]);
        }
    }
}
