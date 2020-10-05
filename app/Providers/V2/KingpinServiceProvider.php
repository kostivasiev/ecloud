<?php

namespace App\Providers\V2;

use App\Models\V2\AvailabilityZone;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class KingpinServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(KingpinService::class, function ($app, $data) {
            $availabilityZone = array_shift($data);
            if (!$availabilityZone instanceof AvailabilityZone) {
                throw new \Exception('Failed to create NSX connection: Invalid AvailabilityZone');
            }
            $credentials = $availabilityZone->credentials()
                ->where('user', '=', config('kingpin.user'))
                ->firstOrFail();
            return new KingpinService(
                new Client([
                    'base_uri' => $credentials->host . (empty($credentials->port) ? '' : ':' . $credentials->port),
                    'auth' => [$credentials->user, $credentials->password],
                    'timeout' => config('kingpin.timeout'),
                    'verify' => $this->app->environment() === 'production'
                ])
            );
        });
    }
}
