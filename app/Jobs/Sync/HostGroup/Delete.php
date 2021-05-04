<?php

namespace App\Jobs\Sync\HostGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\HostGroup\DeleteCluster;
use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
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
        Log::info(
            get_class($this) . ' : Started',
            [
                'id' => $this->task->id,
                'resource_id' => $this->task->resource->id
            ]
        );

        $hostGroup = $this->task->resource;

        $this->deleteTaskBatch([
                new DeleteTransportNodeProfile($hostGroup),
                new DeleteCluster($hostGroup),
        ])->dispatch();

        Log::info(
            get_class($this) . ' : Finished',
            [
                'id' => $this->task->id,
                'resource_id' => $this->task->resource->id
            ]
        );
    }
}
