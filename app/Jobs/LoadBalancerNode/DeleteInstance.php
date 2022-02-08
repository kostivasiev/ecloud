<?php
namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Traits\V2\TaskJobs\AwaitResources;
use App\Traits\V2\TaskJobs\AwaitTask;

class DeleteInstance extends TaskJob
{
    use AwaitTask;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        $taskIdKey = 'task.' . Sync::TASK_NAME_DELETE . '.id';

        if (empty($this->task->data[$taskIdKey])) {
            $instance = Instance::find($loadBalancerNode->instance_id);
            if (!$instance) {
                $this->info('Instance not found, nothing to delete', [
                    'loadbalancer_node_id' => $loadBalancerNode->id,
                    'instance_id' => $loadBalancerNode->instance_id,
                ]);
                return;
            }
            $task = $instance->syncDelete();
            $this->task->updateData($taskIdKey, $task->id);
        }

        if (isset($this->task->data[$taskIdKey])) {
            $this->awaitTasks([$this->task->data[$taskIdKey]]);
        }
    }
}
