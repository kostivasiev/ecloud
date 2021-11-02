<?php
namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteInstances extends Job
{

    use Batchable, LoggableModelJob, AwaitResources, AwaitTask;

    private LoadBalancer $model;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancer = $this->model;
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
