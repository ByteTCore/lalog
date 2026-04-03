<?php

namespace Lalog;

use Illuminate\Support\ServiceProvider;

class LalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/lalog.php', 'lalog');

        $this->app->singleton(QueryLogger::class, function ($app) {
            return new QueryLogger($app['filesystem'], $app['config']['lalog']);
        });
    }

    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/lalog.php' => config_path('lalog.php'),], 'lalog-config');

        if (config('lalog.enabled')) {
            $this->app->make(QueryLogger::class)->listen();
        }
    }
}
