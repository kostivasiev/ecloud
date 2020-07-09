<?php

namespace App\Providers;

use App\Services\NsxService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class NsxServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('App\Services\NsxService', function () {
            $auth = base64_encode(config('nsx.username') . ':' . config('nsx.password'));
            return new NsxService(new Client([
                'base_uri' => config('nsx.hostname'),
                'headers' => [
                    'Authorization' => ['Basic ' . $auth],
                ],
                //'auth' => [config('nsx.username'), config('nsx.password')],
                'timeout'  => 10,
                'verify' => $this->app->environment() === 'production',
            ]));
        });
    }
}
