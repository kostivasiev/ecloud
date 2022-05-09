<?php

namespace App\Jobs\LoadBalancerNetwork;

use App\Jobs\TaskJob;
use App\Models\V2\Nic;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateNics extends TaskJob
{
    use AwaitResources;

    public function __construct($task)
    {
        parent::__construct($task);
        $this->tries = 180;
    }

    /**
     * Create a new NIC on the lb's instances if the supplied network doesn't have a NIC already
     * @return void
     */
    public function handle()
    {
        $loadBalancerNetwork = $this->task->resource;

        $loadBalancer = $loadBalancerNetwork->loadBalancer;

        $loadBalancer->instances->each(function ($instance) use ($loadBalancerNetwork, &$data) {
            $this->createResource(Nic::class, [
                'network_id' => $loadBalancerNetwork->network_id,
                'instance_id' => $instance->id,
            ]);

            if ($this->job->hasFailed() || $this->job->isReleased()) {
                return false;
            }
        });
    }
}
