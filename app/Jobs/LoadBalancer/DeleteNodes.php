<?php
namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteNodes extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $taskData = $this->task->data;
        if (empty($taskData['instances_deleting'])) {
            if (!empty($taskData['instance_ids'])) {
                Instance::whereIn('id', $taskData['instance_ids'])
                    ->each(function ($instance) {
                        $instance->syncDelete();
                    });
                $taskData['instances_deleting'] = true;
                $this->task->setAttribute('data', $taskData)->saveQuietly();
            }
        } else {
            $this->awaitSyncableResources($taskData['instance_ids']);
        }
    }
}
