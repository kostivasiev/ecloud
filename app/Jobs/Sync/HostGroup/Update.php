<?php

namespace App\Jobs\Sync\HostGroup;

use App\Jobs\Job;
use App\Jobs\Kingpin\HostGroup\CreateCluster;
use App\Jobs\Nsx\HostGroup\CreateTransportNode;
use App\Jobs\Nsx\HostGroup\PrepareCluster;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;
use Illuminate\Support\Facades\Log;

class Update extends Job
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

        $hostGroup = $this->task->resource;

        $this->updateTaskBatch([
            [
                new CreateCluster($hostGroup),
                new CreateTransportNode($hostGroup),
                new PrepareCluster($hostGroup)
            ],
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
