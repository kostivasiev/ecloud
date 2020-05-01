<?php

namespace App\Providers;

use App\Services\IntapiService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class IntapiServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // ConfigService
        $this->app->bind('App\Services\IntapiService', function () {
            $httpClient = new Client([
                'base_uri' => env('INTAPI_HOST'),
                'auth' => [env('INTAPI_USER'), env('INTAPI_PASS')],
                'verify' => app()->environment() === 'production',
            ]);

            $intapiService = new IntapiService($httpClient);
            return $intapiService;
        });
    }
}
