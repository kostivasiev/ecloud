<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployRouterLocale extends Job
{
    use Batchable, JobModel;
    
    private Router $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        // Deploy the router locale
        $this->model->availabilityZone->nsxService()->patch('policy/api/v1/infra/tier-1s/' . $this->model->id . '/locale-services/' . $this->model->id, [
            'json' => [
                'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/' . $this->model->availabilityZone->nsxService()->getEdgeClusterId(),
                'tags' => [
                    [
                        'scope' => config('defaults.tag.scope'),
                        'tag' => $this->model->vpc_id,
                    ],
                ],
            ],
        ]);
    }
}
