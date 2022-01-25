<?php
namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteInstance extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;

        if (empty($this->task->data['instance_id'])) {
            $this->info('No instance to remove, skipping', [
                'loadbalancer_node_id' => $loadBalancerNode->id,
            ]);
            return;
        }
        $instance = Instance::find($this->task->data['instance_id']);

        if (!$instance) {
            $this->info('Instance not found, nothing to delete', [
                'loadbalancer_node_id' => $loadBalancerNode->id,
                'instance_id' => $this->task->data['instance_id'],
            ]);
            return;
        }
        $instance->syncDelete();
        $this->awaitSyncableResources([$this->task->data['instance_id']]);
    }
}
