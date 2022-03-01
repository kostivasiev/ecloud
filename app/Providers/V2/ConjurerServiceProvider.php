<?php

namespace App\Providers\V2;

use App\Models\V2\AvailabilityZone;
use App\Services\V2\ConjurerService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class ConjurerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ConjurerService::class, function ($app, $data) {
            $availabilityZone = array_shift($data);
            if (!$availabilityZone instanceof AvailabilityZone) {
                throw new \Exception(get_class($this) . ' : Failed to create connection: Invalid AvailabilityZone');
            }

            $conjurerCredentials = $availabilityZone->credentials()
                ->where('username', '=', config('conjurer.user'))
                ->first();
            if (!$conjurerCredentials) {
                throw new \Exception(get_class($this) . ' : Failed to find Conjurer credentials for user ' . config('conjurer.user'));
            }

            $ucsCredentials = $availabilityZone->credentials()
                ->where('username', '=', config('conjurer.ucs_user'))
                ->first();
            if (!$ucsCredentials) {
                throw new \Exception(get_class($this) . ' : Failed to find UCS credentials for user ' . config('conjurer.ucs_user'));
            }

            return new ConjurerService(
                new Client([
                    'base_uri' => $conjurerCredentials->host . (empty($conjurerCredentials->port) ? '' : ':' . $conjurerCredentials->port),
                    'auth' => [$conjurerCredentials->username, $conjurerCredentials->password],
                    'headers' => [
                        'X-UKFast-Compute-Username' => $ucsCredentials->username,
                        'X-UKFast-Compute-Password' => $ucsCredentials->password,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => config('conjurer.timeout'),
                    'verify' => $this->app->environment() === 'production'
                ])
            );
        });
    }
}
