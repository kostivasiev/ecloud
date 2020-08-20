<?php

namespace App\Providers;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Vpn;
use App\Observers\V2\InstanceObserver;
use App\Observers\V2\NetworkObserver;
use App\Observers\V2\RouterObserver;
use App\Observers\V2\VpnObserver;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use UKFast\Helpers\Encryption\RemoteKeyStore;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->app->singleton('encryption_key', function () {
            if (Cache::has('encryption_key')) {
                return Cache::get('encryption_key');
            }
            $client = $this->app->makeWith(Client::class, ['config' => [
                'base_uri' => config('encryption.keystore_host'),
                'timeout'  => 2
            ]]);
            $key = (new RemoteKeyStore($client))->getKey(config('encryption.keystore_host_key'));
            Cache::put('encryption_key', $key, new \DateInterval('PT120S'));
            return $key;
        });

        Vpn::observe(VpnObserver::class);
        Router::observe(RouterObserver::class);
        Network::observe(NetworkObserver::class);
        Instance::observe(InstanceObserver::class);
    }
}
