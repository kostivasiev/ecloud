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
        $loadBalancer = $this->task->resource;
        if (empty($this->task->data['instance_ids'])) {
            $instanceIds = [];
            $loadBalancer->instances()->each(function ($instance) use (&$instanceIds) {
                $instance->syncDelete();
                $instanceIds[] = $instance->id;
            });
            $this->task->setAttribute('data', [
                'instance_ids' => $instanceIds
            ])->saveQuietly();
        } else {
            $instanceIds = Instance::whereIn('id', $this->task->data['instance_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }
        $this->awaitSyncableResources($instanceIds);
    }
}
