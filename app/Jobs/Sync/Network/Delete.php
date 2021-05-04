<?php

namespace App\Jobs\Sync\Network;

use App\Jobs\Job;
use App\Jobs\Network\AwaitPortRemoval;
use App\Jobs\Network\Undeploy;
use App\Jobs\Network\UndeployCheck;
use App\Jobs\Network\UndeployDiscoveryProfiles;
use App\Jobs\Network\UndeployQoSProfiles;
use App\Jobs\Network\UndeploySecurityProfiles;
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

        $this->deleteTaskBatch([
            [
                new AwaitPortRemoval($this->task->resource),
                new UndeploySecurityProfiles($this->task->resource),
                new UndeployDiscoveryProfiles($this->task->resource),
                new UndeployQoSProfiles($this->task->resource),
                new Undeploy($this->task->resource),
                new UndeployCheck($this->task->resource),
            ],
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
