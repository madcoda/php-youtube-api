<?php 

namespace Madcoda\Youtube;

use Config;
use Madcoda\Youtube;
use Illuminate\Support\ServiceProvider;


class YoutubeServiceProviderLaravel4 extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('madcoda/youtube');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('youtube', function($app){
			return new Youtube($app['config']->get('youtube::youtube'));
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('youtube');
	}

}
