<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Traits\V2\TaskJobs\AwaitResources;

class PrepareNodes extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $loadBalancer = $this->task->resource;
        $taskData = $this->task->data;
        if (empty($taskData['load_balancer_node_ids'])) {
            $loadBalancer->loadBalancerNodes()->each(function ($loadBalancerNode) {
                $loadBalancerNode->syncDelete();
            });
            $taskData['instance_ids'] = $loadBalancer->instances()->get()->pluck('id')->toArray();
            $this->task->setAttribute('data', $taskData)->saveQuietly();
        }
    }
}