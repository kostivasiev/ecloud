<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Models\V2\LoadBalancerNode;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteLoadBalancerNodes extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $loadBalancer = $this->task->resource;
        $loadBalancerNodeIds = [];
        if (empty($taskData['load_balancer_node_ids'])) {
            $loadBalancer->loadBalancerNodes()
                ->each(function ($loadBalancerNode) use (&$loadBalancerNodeIds) {
                    $loadBalancerNodeIds[] = $loadBalancerNode->id;
                    $loadBalancerNode->syncDelete([
                        'instance_id' => $loadBalancerNode->instance_id,
                    ]);
                });
            $taskData = $this->task->data;
            $taskData['load_balancer_node_ids'] = $loadBalancerNodeIds;
            $this->task->setAttribute('data', $taskData)->saveQuietly();
        } else {
            $loadBalancerNodeIds = LoadBalancerNode::whereIn('id', $this->task->data['load_balancer_node_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }
        $this->awaitSyncableResources($loadBalancerNodeIds);
    }
}
