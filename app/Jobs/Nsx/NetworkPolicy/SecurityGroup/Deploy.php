<?php

namespace App\Jobs\Nsx\NetworkPolicy\SecurityGroup;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable;

    private NetworkPolicy $networkPolicy;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->networkPolicy = $networkPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->networkPolicy->id]);

        $network = $this->networkPolicy->network;
        $router = $network->router;
        $availabilityZone = $router->availabilityZone;

        /**
         * Create a security group for the network policy
         */
        $availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/domains/default/groups/' . $this->networkPolicy->id,
            [
                'json' => [
                    'id' => $this->networkPolicy->id,
                    'display_name' => $this->networkPolicy->id,
                    'resource_type' => 'Group',
                    'expression' => [
                        [
                            'resource_type' => 'PathExpression',
                            'paths' => [
                                '/infra/tier-1s/' . $router->id . '/segments/' . $network->id
                            ]
                        ]
                    ]
                ]
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->networkPolicy->id]);
    }
}
