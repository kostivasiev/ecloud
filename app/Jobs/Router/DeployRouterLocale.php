<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Jobs\TaskJob;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeployRouterLocale extends TaskJob
{
    /**
     * @throws \Exception
     */
    public function handle()
    {
        $router = $this->task->resource;

        // Deploy the router locale
        $router->availabilityZone->nsxService()->patch('policy/api/v1/infra/tier-1s/' . $router->id . '/locale-services/' . $router->id, [
            'json' => [
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/' .
                    $router->availabilityZone->getNsxEdgeClusterId($router->vpc->advanced_networking, $router->is_management),
                'tags' => [
                    [
                        'scope' => config('defaults.tag.scope'),
                        'tag' => $router->vpc_id,
                    ],
                ],
            ],
        ]);
    }
}
