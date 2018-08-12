<?php

namespace Madcoda\Youtube;

use Madcoda\Youtube\Youtube;
use Illuminate\Support\ServiceProvider;

class YoutubeServiceProviderLaravel5 extends ServiceProvider
{
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/youtube.php' => config_path('youtube.php'),
        ]);
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Madcoda\Youtube\Youtube', function ($app) {
            return new Youtube($app['config']->get('youtube'));
        });
    }


    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Madcoda\Youtube\Youtube'];
    }
}
