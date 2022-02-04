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
            $instance = Instance::find($loadBalancerNode->instance_id);
            if (!$instance) {
                $this->info('Instance not found, nothing to delete', [
                    'loadbalancer_node_id' => $loadBalancerNode->id,
                    'instance_id' => $loadBalancerNode->instance_id,
                ]);
                return;
            }
            $instance->syncDelete();
            $this->task->updateData('instance_id', $instance->id);
        }
        $this->awaitSyncableResources([$this->task->data['instance_id']]);
    }
}
