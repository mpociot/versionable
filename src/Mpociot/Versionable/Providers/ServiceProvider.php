<?php

namespace Mpociot\Versionable\Providers;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register ()
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/config.php', 'versionable');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../../config/config.php' => config_path('versionable.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../../../migrations/' => database_path('/migrations'),
        ], 'migrations');

        // $this->loadMigrationsFrom(__DIR__.'/../../../migrations');
    }
}
