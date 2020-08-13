<?php

namespace App\Listeners\V2;

use App\Services\NsxService;
use App\Events\V2\RouterCreated;
use App\Models\V2\Router;
use App\Models\V2\FirewallRule;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use GuzzleHttp\Exception\GuzzleException;

class RouterDeploy implements ShouldQueue
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
     * @param RouterCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(RouterCreated $event)
    {
        /** @var Router $router */
        $router = $event->router;
        try {
            $this->nsxService->put('policy/api/v1/infra/tier-1s/' . $router->id, [
                'json' => [
                    'tier0_path' => '/infra/tier-0s/T0',
                ],
            ]);
//            $this->nsxService->post('api/v1/logical-routers', [
//                'json' => [
//                    'resource_type' => 'LogicalRouter',
//                    'description' => $router->name,
//                    'display_name' => $router->id,
//                    'edge_cluster_id' => self::EDGE_CLUSTER_ID,
//                    'router_type' => 'TIER1',
//                    //'high_availability_mode' => 'ACTIVE_ACTIVE' // <- Causes it to fail 100% of the time for debugging
//                ],
//            ]);
        } catch (GuzzleException $exception) {
            $json = json_decode($exception->getResponse()->getBody()->getContents());
            throw new \Exception($json);
        }
        $router->deployed = true;
        $router->save();

        $firewallRule = app()->make(FirewallRule::class);
        $firewallRule->router_id = $router->id;
        $firewallRule->save();
    }
}
