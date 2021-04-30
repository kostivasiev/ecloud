<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Job;
use App\Models\V2\Task;
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

        $host = $this->task->resource;

        $this->deleteTaskBatch([
            new \App\Jobs\Kingpin\Host\MaintenanceMode($host),
            new \App\Jobs\Kingpin\Host\DeleteInVmware($host),
            new \App\Jobs\Conjurer\Host\PowerOff($host),
            new \App\Jobs\Artisan\Host\RemoveFrom3Par($host),
            new \App\Jobs\Conjurer\Host\DeleteServiceProfile($host),
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $host->id]);
    }
}
