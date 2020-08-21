<?php

namespace App\Listeners\V2;

use App\Models\V2\AvailabilityZone;
use App\Services\NsxService;
use App\Events\V2\DhcpCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class DhcpDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param DhcpCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(DhcpCreated $event)
    {
        $dhcp = $event->dhcp;

        $event->dhcp->vpc->region->availabilityZones()->each(function ($availabilityZone) use ($dhcp) {
            try {
                $availabilityZone->nsxClient()->put('/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->getKey(), [
                    'json' => [
                        'lease_time' => '86400',
                        //'server_addresses' => ['192.168.0.1/24']
                    ]
                ]);
            } catch (GuzzleException $exception) {
                $json = json_decode($exception->getResponse()->getBody()->getContents());
                throw new \Exception($json);
            }
        });
    }
}
