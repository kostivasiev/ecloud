<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Models\V2\LoadBalancerNode;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteNodes extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $loadBalancer = $this->task->resource;
        $nodeIds = [];
        if (empty($this->task->data['loadbalancer_node_ids'])) {
            $loadBalancer->loadBalancerNodes()->each(function ($loadBalancerNode) use (&$nodeIds) {
                $nodeIds[] = $loadBalancerNode->id;
                $loadBalancerNode->syncDelete();
            });
            $this->task->setAttribute('data', $nodeIds)->saveQuietly();
        } else {
            $nodeIds = LoadBalancerNode::whereIn('id', $this->task->data['loadbalancer_node_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }
        $this->awaitSyncableResources($nodeIds);
    }
}
