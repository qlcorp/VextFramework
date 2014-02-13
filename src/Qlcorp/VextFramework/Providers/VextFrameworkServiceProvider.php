<?php namespace Qlcorp\VextFramework\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Qlcorp\VextFramework\VextBuilder;
use Qlcorp\VextFramework\VextValidate;

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

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}