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
                    $loadBalancerNode->syncDelete();
                });
            $this->task->updateData('load_balancer_node_ids', loadBalancerNodeIds);
        } else {
            $loadBalancerNodeIds = LoadBalancerNode::whereIn('id', $this->task->data['load_balancer_node_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }
        $this->awaitSyncableResources($loadBalancerNodeIds);
    }
}
