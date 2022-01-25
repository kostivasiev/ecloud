<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteLoadBalancerNodes extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $loadBalancer = $this->task->resource;
        $taskData = $this->task->data ?? [];
        if (empty($taskData['load_balancer_node_ids'])) {
            $loadBalancer->loadBalancerNodes()->each(function ($loadBalancerNode) use (&$taskData) {
                $taskData['load_balancer_node_ids'][] = $loadBalancerNode->id;
                $loadBalancerNode->syncDelete([
                    'instance_id' => $loadBalancerNode->instance_id,
                ]);
            });
            $this->task->setAttribute('data', $taskData)->saveQuietly();
        }
        $this->awaitSyncableResources($this->task->data['load_balancer_node_ids']);
    }
}
