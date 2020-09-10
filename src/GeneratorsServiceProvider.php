<?php

namespace Laracasts\Generators;

use Illuminate\Support\ServiceProvider;

class GeneratorsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMigrationGenerator();
        $this->registerPivotMigrationGenerator();
    }

    /**
     * Register the make:migration generator.
     */
    private function registerMigrationGenerator()
    {
        $this->app->singleton('command.laracasts.migrate', function ($app) {
            return $app['Laracasts\Generators\Commands\MigrationMakeCommand'];
        });

        $this->commands('command.laracasts.migrate');
    }

    /**
     * Register the make:pivot generator.
     */
    private function registerPivotMigrationGenerator()
    {
        $this->app->singleton('command.laracasts.migrate.pivot', function ($app) {
            return $app['Laracasts\Generators\Commands\PivotMigrationMakeCommand'];
        });

        $this->commands('command.laracasts.migrate.pivot');
    }
}
