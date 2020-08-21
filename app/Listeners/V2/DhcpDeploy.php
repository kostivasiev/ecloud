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

        $availabilityZone = AvailabilityZone::findOrFail('avz-2b66bb79');

        exit(print_r(
            $availabilityZone->nsxClient()
        ));

        $event->dhcp->vpc->region->availabilityZones()->each(function($availabilityZone) use ($dhcp) {


            exit(print_r(
                $availabilityZone->nsxClient()
            ));

            try {
                //$nsxClient = $availabilityZone->nsxClient();


            } catch (GuzzleException $exception) {
                $json = json_decode($exception->getResponse()->getBody()->getContents());
                throw new \Exception($json);
            }




        });


        // Loop over the NSX managers for each availability zone.
//        exit(print_r(
//            $event->dhcp->vpc->region->availabilityZones()->get()
//        ));

        // availability zones nsxClient()

//        try {
//            // Create the DHCP server profile
//            $res = $this->nsxService->put('/policy/api/v1/infra/dhcp-server-configs/' . $event->dhcp->getKey(), [
//                'json' => [
//                    '_revision' => '0',
//                    'display_name' => $event->dhcp->getKey(),
//                    //'edge_cluster_id' => $this->nsxService->getEdgeClusterId()
//                ]
//            ]);
//
//            exit(print_r(
//                $res->getBody()->getContents()
//            ));
//
//            $responseData = json_decode($res->response->getBody()->getContents());
//
//            exit(print_r(
//                $responseData
//            ));
//        } catch (GuzzleException $exception) {
//            $json = json_decode($exception->getResponse()->getBody()->getContents());
//            exit(print_r(
//                $json
//            ));
//            throw new \Exception($json);
//        }



//        try {
//            // Check whether the DHCP exists on the NSX server, the display name will be the resource ID.
//            $res = $this->nsxService->get('/api/v1/search/query?query=resource_type:DhcpProfile AND display_name:' . $event->dhcp->getKey() . '&page_size=2');
//
//            $count = json_decode($res->getBody()->getContents());
//
//            exit(print_r(
//
//            ));
//        } catch (GuzzleException $exception) {
//            $json = json_decode($exception->getResponse()->getBody()->getContents());
//            exit(print_r(
//                $json
//            ));
//            throw new \Exception($json);
//        }
    }
}
