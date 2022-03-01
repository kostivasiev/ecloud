<?php

namespace App\Providers;

use App\Services\AccountsService;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;

class AccountsServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $client = $this->getClient();

        $this->app->bind('App\Services\AccountsService', function () use ($client) {
            return new AccountsService($client);
        });
    }

    /**
     * get a Client instance
     * @return GuzzleClient
     */
    protected function getClient()
    {
        return new GuzzleClient([
            'base_uri' => env('APIO_ACCOUNT_HOST'),
            'timeout' => 2,
            'verify' => app()->environment() === 'production',
        ]);
    }
}
