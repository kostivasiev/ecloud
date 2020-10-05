<?php

namespace App\Providers\V2;

use App\Models\V2\AvailabilityZone;
use App\Services\V2\NsxService;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class NsxServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(NsxService::class, function ($app, $data) {
            $availabilityZone = array_shift($data);
            if (!$availabilityZone instanceof AvailabilityZone) {
                throw new \Exception('Failed to create NSX connection: Invalid AvailabilityZone');
            }
            $credentials = $availabilityZone->credentials()
                ->where('name', '=', 'NSX')
                ->firstOrFail();
            $auth = base64_encode($credentials->user . ':' . $credentials->password);
            return new NsxService(
                new Client([
                    'base_uri' => $credentials->host . (empty($credentials->port) ? '' : ':' . $credentials->port),
                    'headers' => [
                        'Authorization' => ['Basic ' . $auth],
                    ],
                    'timeout' => 10,
                    'verify' => false, //$this->app->environment() === 'production',
                ]),
                $availabilityZone->nsx_edge_cluster_id
            );
        });
    }
}
