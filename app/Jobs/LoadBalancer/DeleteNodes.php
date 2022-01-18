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
        $instanceIds = [];
        if (!empty($taskData['instance_ids'])) {
            Instance::whereIn('id', $taskData['instance_ids'])
                ->each(function ($instance) use (&$instanceIds) {
                    $instance->syncDelete();
                    $instanceIds[] = $instance->id;
                });
            $taskData['instance_ids'] = $instanceIds;
            $this->task->setAttribute('data', $taskData)->saveQuietly();
        }
        $this->awaitSyncableResources($instanceIds);
    }
}
