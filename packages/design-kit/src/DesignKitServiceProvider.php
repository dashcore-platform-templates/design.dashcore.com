<?php

namespace Dashcore\DesignKit;

use Dashcore\DesignKit\Commands\ApplyCommand;
use Dashcore\DesignKit\Commands\AuditCommand;
use Dashcore\DesignKit\Commands\CheckCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Installs into a Laravel Livewire Flux kit. Registers the two commands, the
 * brand config the sign-in page reads, the three custom components from
 * standard/06 as <x-ds::page-header>, <x-ds::stat> and <x-ds::empty>, and the
 * two-panel sign-in layout as <x-design-kit::layouts.auth>.
 */
class DesignKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/brand.php', 'brand');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'design-kit');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/ds', 'ds');

        $this->publishes([__DIR__.'/../config/brand.php' => config_path('brand.php')], 'design-kit-config');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/design-kit')], 'design-kit-views');

        if ($this->app->runningInConsole()) {
            $this->commands([ApplyCommand::class, AuditCommand::class, CheckCommand::class]);
        }
    }
}
