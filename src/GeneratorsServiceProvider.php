<?php

namespace Laracasts\Generators;

use Illuminate\Support\ServiceProvider;

class GeneratorsServiceProvider extends ServiceProvider {

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
		$this->registerSeedGenerator();
		$this->registerMigrationGenerator();
	}

	/**
	 * Register the make:seed generator.
	 */
	private function registerSeedGenerator()
	{
		$this->app->singleton('command.laracasts.seed', function ($app) {
			return $app['Laracasts\Generators\Commands\SeedMakeCommand'];
		});

		$this->commands('command.laracasts.seed');
	}

	private function registerMigrationGenerator()
	{
		$this->app->singleton('command.laracasts.migration', function ($app) {
			return $app['Laracasts\Generators\Commands\MigrationMakeCommand'];
		});

		$this->commands('command.laracasts.migration');
	}

}
