<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Jobs\TaskJob;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeployRouterDefaultRule extends TaskJob
{
    /**
     * @throws \Exception
     */
    public function handle()
    {
        $router = $this->task->resource;

        $response = $router->availabilityZone->nsxService()
            ->get(
                'policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra-tier1-' . $router->id . '/rules/default_rule'
            );
        $original = json_decode($response->getBody()->getContents(), true);
        $original['action'] = 'REJECT';
        $original = array_filter($original, function ($key) {
            return strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        $router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra-tier1-' . $router->id . '/rules/default_rule',
            [
                'json' => $original
            ]
        );
    }
}
