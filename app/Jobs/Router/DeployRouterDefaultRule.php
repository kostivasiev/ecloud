<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployRouterDefaultRule extends Job
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
        $response = $this->model->availabilityZone->nsxService()
            ->get(
                'policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra-tier1-' . $this->model->id . '/rules/default_rule'
            );
        $original = json_decode($response->getBody()->getContents(), true);
        $original['action'] = 'REJECT';
        $original = array_filter($original, function ($key) {
            return strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->model->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra-tier1-' . $this->model->id . '/rules/default_rule',
            [
                'json' => $original
            ]
        );
    }
}
