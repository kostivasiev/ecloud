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
        $taskData = $this->task->data;

        // if we just delete the nodes we break the loadBalancer->instances relationship,
        // so before we do store instance_ids in task data
        if (empty($taskData['instance_ids'])) {
            $taskData['instance_ids'] = $loadBalancer->instances()->get()->pluck('id')->toArray();
        }

        if (empty($taskData['loadbalancer_node_ids'])) {
            $loadBalancer->loadBalancerNodes()->each(function ($loadBalancerNode) use (&$nodeIds) {
                $nodeIds[] = $loadBalancerNode->id;
                $loadBalancerNode->syncDelete();
            });
            $taskData['loadbalancer_node_ids'] = $nodeIds;
            $this->task->setAttribute('data', $taskData)->saveQuietly();
        } else {
            $nodeIds = LoadBalancerNode::whereIn('id', $taskData['loadbalancer_node_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }
        $this->awaitSyncableResources($nodeIds);
    }
}
