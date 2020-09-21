<?php

namespace App\Providers\V2;

use App\Models\V2\AvailabilityZone;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class KingpinServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(KingpinService::class, function ($app, $data) {
            if (!is_a($data[0], AvailabilityZone::class)) {
                $message = 'Invalid AvailabilityZone Object';
                Log::error($message);
                throw new \Exception('Failed to create Kingpin connection: ' . $message);
            }

            $availabilityZone = $data[0];

            $credentials = $availabilityZone
                ->credentials()
                ->where('user', '=', config('kingpin.user'))
                ->firstOrFail();

            return new KingpinService(
                new Client([
                    'base_uri' => empty($credentials->port) ?
                        $credentials->host :
                        $credentials->host . ':' . $credentials->port,
                    'auth' => [config('kingpin.user'), $credentials->password],
                    'timeout'  => 10,
                    'verify' => $this->app->environment() === 'production'
                ])
            );
        });
    }
}
