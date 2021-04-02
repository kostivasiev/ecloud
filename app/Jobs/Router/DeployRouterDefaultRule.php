<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployRouterDefaultRule extends Job
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

        $response = $this->router->availabilityZone->nsxService()->get('policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra-tier1-' . $this->router->id . '/rules/default_rule');
        $original = json_decode($response->getBody()->getContents(), true);
        $original['action'] = 'REJECT';
        $original = array_filter($original, function ($key) {
            return strpos($key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);

        $this->router->availabilityZone->nsxService()->patch(
            'policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra-tier1-' . $this->router->id . '/rules/default_rule',
            [
                'json' => $original
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}
