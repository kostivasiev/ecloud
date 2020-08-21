<?php

namespace App\Listeners\V2;

use App\Services\NsxService;
use App\Events\V2\DhcpCreated;
use App\Models\V2\Router;
use App\Models\V2\FirewallRule;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class DhcpDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * This needs replacing with a lookup to find the edge cluster for
     * the VPC this router belongs too
     */
    const EDGE_CLUSTER_ID = '8bc61267-583e-4988-b5d9-16b46f7fe900';

    /**
     * @var NsxService
     */
    private $nsxService;

    /**
     * @param NsxService $nsxService
     * @return void
     */
    public function __construct(NsxService $nsxService)
    {
        $this->nsxService = $nsxService;
    }

    /**
     * @param DhcpCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(DhcpCreated $event)
    {
        exit(print_r(
            $event->dhcp->vpc->region->availabilityZones()->get()
        ));


//        /** @var Router $router */
//        $router = $event->router;
//        try {
//            $this->nsxService->put('policy/api/v1/infra/tier-1s/' . $router->id, [
//                'json' => [
//                    'tier0_path' => '/infra/tier-0s/T0',
//                ],
//            ]);
//
//            $this->nsxService->put('policy/api/v1/in:wfra/tier-1s/' . $router->id . '/locale-services/' . $router->id, [
//                'json' => [
//                    'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/' . self::EDGE_CLUSTER_ID,
//                ],
//            ]);
//        } catch (GuzzleException $exception) {
//            $json = json_decode($exception->getResponse()->getBody()->getContents());
//            throw new \Exception($json);
//        }
//        $router->deployed = true;
//        $router->save();
//
//        $firewallRule = app()->make(FirewallRule::class);
//        $firewallRule->router()->attach($router);
//        $firewallRule->save();
    }
}
