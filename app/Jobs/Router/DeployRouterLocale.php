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

        // Deploy the router locale
        $this->router->availabilityZone->nsxService()->patch('policy/api/v1/infra/tier-1s/' . $this->router->id . '/locale-services/' . $this->router->id, [
            'json' => [
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/' . $this->router->availabilityZone->nsxService()->getEdgeClusterId(),
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
