<?php

namespace App\Providers\V2;

use App\Models\V2\AvailabilityZone;
use App\Services\V2\ArtisanService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class ArtisanServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ArtisanService::class, function ($app, $data) {
            $availabilityZone = array_shift($data);
            if (!$availabilityZone instanceof AvailabilityZone) {
                throw new \Exception(get_class($this) . ' : Failed to create connection: Invalid AvailabilityZone');
            }
            $artisanCredentials = $availabilityZone->credentials()
                ->where('username', '=', config('artisan.user'))
                ->first();
            if (!$artisanCredentials) {
                throw new \Exception(get_class($this) . ' : Failed to find Artisan credentials for user ' . config('artisan.user'));
            }

            $sanCredentials = $availabilityZone->credentials()
                ->where('username', '=', config('artisan.san_user'))
                ->first();
            if (!$sanCredentials) {
                throw new \Exception(get_class($this) . ' : Failed to find SAN credentials for user ' . config('artisan.san_user'));
            }

            return new ArtisanService(
                new Client([
                    'base_uri' => $artisanCredentials->host . (empty($artisanCredentials->port) ? '' : ':' . $artisanCredentials->port),
                    'auth' => [$artisanCredentials->username, $artisanCredentials->password],
                    'headers'=>[
                        "X-UKFast-SAN-Username" => $sanCredentials->username,
                        "X-UKFast-SAN-Password" => $sanCredentials->password
                    ],
                    'timeout' => config('conjurer.timeout'),
                    'verify' => $this->app->environment() === 'production'
                ])
            );
        });
    }
}
