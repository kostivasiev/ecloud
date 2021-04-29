<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\NetworkPolicy\DeleteChildResources;
use App\Models\V2\Sync;
use App\Models\V2\Task;
use App\Traits\V2\SyncableBatch;
use App\Traits\V2\TaskableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    use TaskableBatch;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);

        $this->deleteTaskBatch([
            [
                new DeleteChildResources($this->task->resource),
                new \App\Jobs\Nsx\NetworkPolicy\Undeploy($this->task->resource),
                new \App\Jobs\Nsx\NetworkPolicy\UndeployCheck($this->task->resource),
                new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Undeploy($this->task->resource),
                new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\UndeployCheck($this->task->resource),
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
