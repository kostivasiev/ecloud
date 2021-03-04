<?php

namespace App\Jobs\Nsx\NetworkPolicy\SecurityGroup;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $model;

    public function __construct(NetworkPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

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

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason(json_decode($exception->getResponse()->getBody()->getContents()));
    }
}
