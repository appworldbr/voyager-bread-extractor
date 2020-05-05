<?php

namespace AppWorld\VoyagerBreadExtractor;

use AppWorld\VoyagerBreadExtractor\Console\Commands\MakeBREADSeeder;
use Illuminate\Support\ServiceProvider;

class VoyagerBreadExtractorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeBREADSeeder::class
            ]);
        }
    }
}
