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
        $this->app->bind(KingpinService::class, function ($app, $data)
        {
            if (!is_a($data[0], AvailabilityZone::class)) {
                $message = 'Invalid AvailabilityZone Object';
                Log::error($message, [
                    typeOf($data[0])
                ]);
                throw new \Exception('Failed to create Kingpin connection: ' . $message);
            }

            $availabilityZone = $data[0];

            $credentials = $availabilityZone
                ->credentials()
                ->where('user', '=', config('kingpin.user'))
                ->firstOrFail();





//            if (!empty($credentials->port)) {
//                $baseUri .= ':' . $serverDetail->server_detail_login_port;
//            }
//

//            exit(print_r(
//                [
//                    $credentials->host,
//                    $credentials->password,
//                    $credentials->port
//                ]
//            ));



            $auth = base64_encode(config('kingpin.user') . ':' . $credentials->password);


            return new KingpinService(
                new Client([
                    'base_uri' => $credentials->host,
                    'headers' => [
                        'Authorization' => ['Basic ' . $auth],
                    ],
                    'timeout'  => 10,
                    'verify' => false, //$this->app->environment() === 'production',
                ]),
                $data['nsx_edge_cluster_id']
            );
        });
    }
}
