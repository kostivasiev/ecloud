<?php

namespace App\Providers;

//use App\Clients\ApioClient;
use App\Services\NetworkingService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class NetworkingServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // ConfigService
        $this->app->bind('App\Services\NetworkingService', function () {
            $httpClient = new Client([
                'base_uri' => env('APIO_NETWORKING_HOST'),
                'timeout' => 10,
                'verify' => app()->environment() === 'production',
            ]);

            $networkingService = new NetworkingService($httpClient);
            return $networkingService;
        });
    }
}
