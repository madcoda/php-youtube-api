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
        $publishPath = $this->app['path.config'] . __DIR__ . '/config/youtube.php';
        $this->publishes([
            __DIR__.'/config/youtube.php' => $publishPath,
        ]);
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Madcoda\Youtube', function ($app) {
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
        return ['Madcoda\Youtube'];
    }
}
