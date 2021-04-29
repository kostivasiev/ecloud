<?php

namespace App\Jobs\Task\Network;

use App\Jobs\Job;
use App\Jobs\Network\AwaitRouterTask;
use App\Jobs\Network\DeployDiscoveryProfile;
use App\Jobs\Network\DeploySecurityProfile;
use App\Jobs\Network\Deploy;
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

        $this->updateTaskBatch([
            [
                new AwaitRouterTask($this->task->resource),
                new Deploy($this->task->resource),
                new DeploySecurityProfile($this->task->resource),
                new DeployDiscoveryProfile($this->task->resource),
            ],
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
