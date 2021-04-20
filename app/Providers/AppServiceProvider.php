<?php

namespace App\Providers;

use App\Models\V2\Dhcp;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Router;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
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
            $client = $this->app->makeWith(Client::class, [
                'config' => [
                    'base_uri' => config('encryption.keystore_host'),
                    'timeout' => 2,
                    'verify' => app()->environment() === 'production',
                ]
            ]);
            $key = (new RemoteKeyStore($client))->getKey(config('encryption.keystore_host_key'));
            Cache::put('encryption_key', $key, new \DateInterval('PT120S'));
            return $key;
        });

        Relation::morphMap([
            'nic' => Nic::class,
            'fip' => FloatingIp::class,
            'i' => Instance::class,
            'rtr' => Router::class,
            'vol' => Volume::class,
            'vpn' => Vpn::class,
            'vpc' => Vpc::class,
            'dhcp' => Dhcp::class,
            'net' => Network::class,
        ]);

        Queue::exceptionOccurred(function (JobExceptionOccurred $event) {
            Log::error($event->job->getName() . " : Job exception occurred", ['exception' => $event->exception]);
        });

        Queue::failing(function (JobFailed $event) {
            Log::error($event->job->getName() . " : Job failed", ['exception' => $event->exception]);
        });
    }
}
