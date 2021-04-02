<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployRouterLocale extends Job
{
    use Batchable;
    
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->router->id]);

        $availabilityZone = $this->router->availabilityZone;
        if (!$availabilityZone) {
            $this->fail(new \Exception('Failed to find AZ for router ' . $this->router->id));
            return;
        }

        $nsxService = $availabilityZone->nsxService();
        if (!$nsxService) {
            $this->fail(new \Exception('Failed to find NSX Service for router ' . $this->router->id));
            return;
        }

        // Deploy the router locale
        $nsxService->patch('policy/api/v1/infra/tier-1s/' . $this->router->id . '/locale-services/' . $this->router->id, [
            'json' => [
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/' . $nsxService->getEdgeClusterId(),
                'tags' => [
                    [
                        'scope' => config('defaults.tag.scope'),
                        'tag' => $this->router->vpc_id,
                    ],
                ],
            ],
        ]);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}
