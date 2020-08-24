<?php

namespace App\Providers;

use App\Services\NsxService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class NsxServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('App\Services\NsxService', function ($app, $data) {
            $auth = base64_encode(config('nsx.username') . ':' . config('nsx.password'));
            return new NsxService(
                new Client([
                    'base_uri' => $data['nsx_manager_endpoint'],
                    'headers' => [
                        'Authorization' => ['Basic ' . $auth],
                    ],
                    'timeout'  => 10,
                    'verify' => $this->app->environment() === 'production',
                ]),
                $data['nsx_edge_cluster_id']
            );
        });
    }
}
