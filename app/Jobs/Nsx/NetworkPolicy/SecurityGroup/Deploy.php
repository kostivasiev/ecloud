<?php

namespace App\Jobs\Nsx\NetworkPolicy\SecurityGroup;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable, JobModel;

    private NetworkPolicy $model;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->model = $networkPolicy;
    }

    public function handle()
    {
        $network = $this->model->network;
        $router = $network->router;
        $availabilityZone = $router->availabilityZone;

        /**
         * Create a security group for the network policy
         */
        $availabilityZone->nsxService()->patch(
            '/policy/api/v1/infra/domains/default/groups/' . $this->model->id,
            [
                'json' => [
                    'id' => $this->model->id,
                    'display_name' => $this->model->id,
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
    }
}
