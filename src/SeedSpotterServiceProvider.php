<?php

namespace Abdulmannans\SeedSpotter;

use Illuminate\Support\ServiceProvider;

class SeedSpotterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('seed-spotter', function ($app) {
            return new SeedSpotter(
                $app->make('seeder'),
                config('seed-spotter.table', 'users'),
                config('seed-spotter.ignore_columns', [])
            );
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/seed-spotter.php',
            'seed-spotter'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/seed-spotter.php' => config_path('seed-spotter.php'),
            ], 'config');

            $this->commands([
                Console\CompareSeedsCommand::class,
            ]);
        }
    }
}
