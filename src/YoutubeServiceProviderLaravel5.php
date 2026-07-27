<?php

namespace Madcoda\Youtube;

use Illuminate\Support\ServiceProvider;
use Madcoda\Youtube\Youtube;

class YoutubeServiceProviderLaravel5 extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Lumen has no config_path() helper, and publishing is a no-op there.
        if (function_exists('config_path')) {
            $this->publishes(array(
                __DIR__ . '/config/youtube.php' => config_path('youtube.php'),
            ), 'config');
        }
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        // Merging the shipped defaults means the binding still resolves when
        // the config has not been published. Without this, get('youtube')
        // returns null and the constructor rejects it with "the configuration
        // options must be an array", which says nothing about the real problem.
        $this->mergeConfigFrom(__DIR__ . '/config/youtube.php', 'youtube');

        $this->app->bind('Madcoda\Youtube\Youtube', function ($app) {
            $config = $app['config']->get('youtube');

            return new Youtube(is_array($config) ? $config : array());
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('Madcoda\Youtube\Youtube');
    }
}
