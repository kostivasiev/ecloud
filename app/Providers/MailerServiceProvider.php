<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MailerServiceProvider extends ServiceProvider
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
        $this->app->alias('mailer', Illuminate\Mail\Mailer::class);
        $this->app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
        $this->app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);
    }
}
