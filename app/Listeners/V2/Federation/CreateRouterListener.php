<?php

namespace App\Listeners\V2\Federation;

use App\Services\NsxService;
use App\Events\V2\Router\AfterCreateEvent;
use Elastica\Response;
use Illuminate\Queue\InteractsWithQueue;

final class CreateRouterListener //implements ShouldQueue TODO...
{
    //use InteractsWithQueue; TODO...

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
     * @param AfterCreateEvent $event
     * @return void
     */
    public function handle(AfterCreateEvent $event)
    {
        try {
            $response = $this->nsxService->post('api/v1/logical-routers', [
                'resource_type' => 'LogicalRouter',
                'description' => 'Test Router Made Via API',
                'display_name' => 'tier-1',
                'edge_cluster_id' => 'a9dc562c-effd-4225-883d-3f7d2c887c6b',
                'advanced_config' => [
                    'external_transit_networks' => [
                        '100.64.1.0/10'
                    ],
                    'internal_transit_network' => '169.254.0.0/28'
                ],
                'allocation_profile' => [
                    'enable_standby_relocation' => false
                ],
                'router_type' => 'TIER0',
                'high_availability_mode' => 'ACTIVE_ACTIVE'
            ]);
        } catch (\Exception $exception) {
            var_dump($exception->getMessage());
            die();
        }

        $obj = json_decode($response->getBody()->getContents());
        var_dump($obj);
        die();
    }

    public function failed(AfterCreateEvent $event, $exception)
    {
        // TODO :- Handle it
    }
}
