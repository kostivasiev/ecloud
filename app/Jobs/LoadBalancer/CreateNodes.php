<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Models\V2\LoadBalancerNode;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateNodes extends TaskJob
{
    use AwaitResources;

    public function __construct($task)
    {
        parent::__construct($task);
        // Set timeout to 15 mins per node
        $this->tries = (180 * $this->task->resource->loadBalancerSpec->node_count);
    }

    public function handle()
    {
        $loadBalancer = $this->task->resource;
        $loadBalancerNodes = [];
        if (empty($this->task->data['loadbalancer_node_ids'])) {
            for ($i = 0; $i < $loadBalancer->loadBalancerSpec->node_count; $i++) {
                $node = app()->make(LoadBalancerNode::class);
                $node->fill([
                    'load_balancer_id' => $loadBalancer->id,
                ]);
                $node->syncSave();
                $loadBalancerNodes[] = $node->id;
            }
            $this->task->updateData('loadbalancer_node_ids', $loadBalancerNodes);
        } else {
            $loadBalancerNodes = LoadBalancerNode::whereIn('id', $this->task->data['loadbalancer_node_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }
        $this->awaitSyncableResources($loadBalancerNodes);
    }
}
