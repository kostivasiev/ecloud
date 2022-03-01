<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class QueueManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(\Illuminate\Queue\QueueManager::class, function ($app) {
            return new \Illuminate\Queue\QueueManager($app);
        });
    }
}
