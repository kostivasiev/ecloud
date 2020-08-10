<?php

namespace App\Listeners\V2;

use App\Services\NsxService;
use App\Events\V2\RouterCreated;
use App\Models\V2\Router;
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
     */
    public function handle(RouterCreated $event)
    {
        /** @var Router $router */
        $router = $event->router;

//        try {
            $response = $this->nsxService->post('api/v1/logical-routers', [
                'json' => [
                    'resource_type' => 'LogicalRouter',
                    'description' => $router->name,
                    'display_name' => $router->id,
                    'edge_cluster_id' => self::EDGE_CLUSTER_ID,
                    'router_type' => 'TIER1',
                    'high_availability_mode' => 'ACTIVE_ACTIVE'
                ],
            ]);
//        } catch (GuzzleException $exception) {
//            $error = $exception->getResponse()->getBody()->getContents();
//            dd($error);
//        }

        $router->active = true;
        $router->save();
    }
}
