<?php namespace Qlcorp\VextFramework\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Qlcorp\VextFramework\Console\GenerateModelsCommand;
use Qlcorp\VextFramework\VextBuilder;
use Qlcorp\VextFramework\VextValidate;
use Qlcorp\VextFramework\VextMigrator;

class VextFrameworkServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('qlcorp/vext-framework');
        include __DIR__.'/../../routes.php';
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app->bind('vextSchema', function() {
            return new VextBuilder(Schema::getConnection());
        });

        $this->app->bind('vextValidate', function() {
            return new VextValidate();
        });

        $this->app->booting(function(){
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('VextSchema', 'Qlcorp\VextFramework\Facades\VextSchema');
            $loader->alias('VextValidate', 'Qlcorp\VextFramework\Facades\VextValidate');
        });

        $this->app->bindShared('command.migrate.generate', function($app) {
            $packagePath = $app['path.base'].'/vendor';
            $repository = $app['migration.repository'];
            $migrator = new VextMigrator($repository, $app['db'], $app['files']);
            return new GenerateModelsCommand($migrator, $packagePath);
        });

        $this->commands('command.migrate.generate');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('VextSchema', 'VextValidate', 'command.migrate.generate');
	}

}