<?php

namespace App\Providers;

use App\Services\BillingService;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $client = $this->getClient();

        $this->app->bind('App\Services\BillingService', function () use ($client) {
            return new BillingService($client);
        });
    }

    /**
     * get a Client instance
     * @return GuzzleClient
     */
    protected function getClient()
    {
        return new GuzzleClient([
            'base_uri' => env('APIO_BILLING_HOST'),
            'timeout' => 2,
            'verify' => app()->environment() === 'production',
        ]);
    }
}
