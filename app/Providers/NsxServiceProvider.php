<?php

namespace App\Providers;

use App\Services\NsxService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class NsxServiceProvider extends ServiceProvider
{
    public function register()
    {
        // TODO - We will need to expand this to support NSX Managers on each AZ
        $this->app->bind('App\Services\NsxService', function () {
            $auth = base64_encode(config('nsx.username') . ':' . config('nsx.password'));
            return new NsxService(new Client([
                'base_uri' => config('nsx.hostname'),
                'headers' => [
                    'Authorization' => ['Basic ' . $auth],
                ],
                'timeout'  => 10,
                'verify' => $this->app->environment() === 'production',
            ]));
        });
    }
}
