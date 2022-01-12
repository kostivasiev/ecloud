<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerNode;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class CreateNodes extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;
    
    private LoadBalancer $model;
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $loadBalancer = $this->model;
        if (empty($this->task->data['loadbalancer_node_ids'])) {
            $nodeArray = [];
            for ($i = 0; $i < $loadBalancer->loadBalancerSpec->node_count; $i++) {
                $node = app()->make(LoadBalancerNode::class);
                $node->fill([
                    'load_balancer_id' => $loadBalancer->id,
                    'instance_id' => null,
                    'node_id' => null,
                ]);
                $node->syncSave([
                    'node_index' => $i + 1,
                ]);
                $nodeArray[] = $node->id;
            }
            $this->task->setAttribute('data', ['loadbalancer_node_ids' => json_encode($nodeArray)])->saveQuietly();
        } else {
            $this->awaitSyncableResources($this->task->data['loadbalancer_node_ids']);
        }
    }
}
